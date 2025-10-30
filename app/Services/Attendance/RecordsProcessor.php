<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use App\Models\AttendanceHeader;
use App\Models\AttendanceDetail;
use App\Models\Employee;
use App\Core\Database;
use Exception;

/**
 * Procesador de marcaciones desde attendance_records a attendance_detail
 *
 * Responsabilidades:
 * 1. Leer registros sin procesar de attendance_records
 * 2. Agrupar por empleado y fecha
 * 3. Consolidar CHECK_IN + CHECK_OUT
 * 4. Comparar con attendance_detail existente
 * 5. INSERT o UPDATE según corresponda
 * 6. Marcar records como procesados
 */
class RecordsProcessor
{
    private $recordModel;
    private $headerModel;
    private $detailModel;
    private $employeeModel;
    private $db;

    private $stats = [
        'total_records' => 0,
        'groups_processed' => 0,
        'details_created' => 0,
        'details_updated' => 0,
        'details_skipped' => 0,
        'records_marked' => 0,
        'errors' => 0
    ];

    private $errors = [];

    public function __construct()
    {
        $this->recordModel = new AttendanceRecord();
        $this->headerModel = new AttendanceHeader();
        $this->detailModel = new AttendanceDetail();
        $this->employeeModel = new Employee();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Procesar registros a detalles por rango de fechas
     *
     * @param string $dateFrom Fecha desde (YYYY-MM-DD)
     * @param string $dateTo Fecha hasta (YYYY-MM-DD)
     * @return array Estadísticas del procesamiento
     */
    public function processToDetails($dateFrom, $dateTo)
    {
        $this->resetStats();

        try {
            // 1. Obtener registros agrupados por empleado y fecha
            $groups = $this->recordModel->getGroupedByEmployeeAndDate($dateFrom, $dateTo);
            $this->stats['total_records'] = count($groups);

            if (empty($groups)) {
                return $this->getStats();
            }

            // 2. Procesar cada grupo (empleado + fecha)
            foreach ($groups as $group) {
                $this->processGroup($group);
                $this->stats['groups_processed']++;
            }

            // 3. Detectar y marcar duplicados
            $this->recordModel->detectDuplicates();

            return $this->getStats();

        } catch (Exception $e) {
            $this->errors[] = "Error en processToDetails: " . $e->getMessage();
            $this->stats['errors']++;
            error_log("RecordsProcessor Error: " . $e->getMessage());
            return $this->getStats();
        }
    }

    /**
     * Procesar un grupo específico (empleado + fecha)
     *
     * @param array $group Datos del grupo
     */
    private function processGroup($group)
    {
        try {
            $employeeId = $group['employee_id'];
            $date = $group['punch_date'];
            $firstCheckIn = $group['first_check_in'];
            $lastCheckOut = $group['last_check_out'];
            $recordIds = explode(',', $group['record_ids']);

            // Extraer solo las horas
            $timeIn = $firstCheckIn ? date('H:i:s', strtotime($firstCheckIn)) : null;
            $timeOut = $lastCheckOut ? date('H:i:s', strtotime($lastCheckOut)) : null;

            // 1. Verificar si ya existe un detail para este empleado y fecha
            $existingDetail = $this->detailModel->getByDateAndEmployee($date, $employeeId);

            if ($existingDetail) {
                // Ya existe - comparar y decidir si actualizar
                $needsUpdate = $this->needsUpdate($existingDetail, $timeIn, $timeOut);

                if ($needsUpdate) {
                    $this->updateDetail($existingDetail['id'], $timeIn, $timeOut, $recordIds);
                } else {
                    // No necesita actualización, solo marcar records como procesados
                    $this->recordModel->markMultipleAsProcessed($recordIds, $existingDetail['id']);
                    $this->stats['details_skipped']++;
                    $this->stats['records_marked'] += count($recordIds);
                }
            } else {
                // No existe - crear nuevo detail
                $this->createDetail($employeeId, $date, $timeIn, $timeOut, $recordIds);
            }

        } catch (Exception $e) {
            $this->errors[] = "Error procesando grupo {$group['employee_id']}/{$group['punch_date']}: " . $e->getMessage();
            $this->stats['errors']++;
            error_log("Error en processGroup: " . $e->getMessage());
        }
    }

    /**
     * Crear nuevo registro en attendance_detail
     *
     * @param int $employeeId
     * @param string $date
     * @param string|null $timeIn
     * @param string|null $timeOut
     * @param array $recordIds
     */
    private function createDetail($employeeId, $date, $timeIn, $timeOut, $recordIds)
    {
        try {
            // 1. Obtener o crear header para esta fecha
            $header = $this->headerModel->getByDate($date);

            if (!$header) {
                $headerId = $this->headerModel->create([
                    'attendance_date' => $date,
                    'device_id' => null,
                    'synced_from' => 'API',
                    'total_records' => 0,
                    'total_employees' => 0,
                    'is_processed' => 0
                ]);

                if (!$headerId) {
                    throw new Exception("No se pudo crear header para fecha {$date}");
                }
            } else {
                $headerId = $header['id'];
            }

            // 2. Obtener información del empleado (horario, etc.)
            $employee = $this->employeeModel->find($employeeId);

            if (!$employee) {
                throw new Exception("Empleado {$employeeId} no encontrado");
            }

            // 3. Obtener horario programado
            $scheduledTimeIn = null;
            $scheduledTimeOut = null;

            if (!empty($employee['schedule_id'])) {
                $schedule = $this->getSchedule($employee['schedule_id']);
                if ($schedule) {
                    $scheduledTimeIn = $schedule['time_in'];
                    $scheduledTimeOut = $schedule['time_out'];
                }
            }

            // 4. Determinar estado inicial
            $status = $this->determineStatus($timeIn, $timeOut);

            // 5. Crear registro en attendance_detail
            $detailData = [
                'header_id' => $headerId,
                'employee_id' => $employeeId,
                'schedule_id' => $employee['schedule_id'] ?? null,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'scheduled_time_in' => $scheduledTimeIn,
                'scheduled_time_out' => $scheduledTimeOut,
                'device_id' => null,
                'status' => $status,
                'is_late' => 0,
                'tardiness_minutes' => 0,
                'hours_worked' => 0,
                'notes' => 'Procesado desde attendance_records'
            ];

            $detailId = $this->detailModel->create($detailData);

            if ($detailId) {
                // 6. Marcar records como procesados
                $this->recordModel->markMultipleAsProcessed($recordIds, $detailId);

                $this->stats['details_created']++;
                $this->stats['records_marked'] += count($recordIds);

                error_log("Created detail ID {$detailId} for employee {$employeeId} on {$date}");
            } else {
                throw new Exception("No se pudo crear detail");
            }

        } catch (Exception $e) {
            $this->errors[] = "Error creando detail: " . $e->getMessage();
            $this->stats['errors']++;
            error_log("Error en createDetail: " . $e->getMessage());
        }
    }

    /**
     * Actualizar registro existente en attendance_detail
     *
     * @param int $detailId
     * @param string|null $timeIn
     * @param string|null $timeOut
     * @param array $recordIds
     */
    private function updateDetail($detailId, $timeIn, $timeOut, $recordIds)
    {
        try {
            // Obtener el detail actual
            $detail = $this->detailModel->getById($detailId);

            if (!$detail) {
                throw new Exception("Detail {$detailId} no encontrado");
            }

            // Preparar datos de actualización
            $updateData = [
                'time_in' => $timeIn ?? $detail['time_in'],
                'time_out' => $timeOut ?? $detail['time_out'],
                'status' => $this->determineStatus($timeIn ?? $detail['time_in'], $timeOut ?? $detail['time_out']),
                'notes' => ($detail['notes'] ?? '') . ' | Actualizado desde records',
                'is_late' => $detail['is_late'] ?? 0,
                'tardiness_minutes' => $detail['tardiness_minutes'] ?? 0,
                'hours_worked' => $detail['hours_worked'] ?? 0,
                'scheduled_time_in' => $detail['scheduled_time_in'] ?? null,
                'scheduled_time_out' => $detail['scheduled_time_out'] ?? null,
                'justification_type' => $detail['justification_type'] ?? null,
                'justification_notes' => $detail['justification_notes'] ?? null,
                'justification_document' => $detail['justification_document'] ?? null
            ];

            $result = $this->detailModel->update($detailId, $updateData);

            if ($result) {
                // Marcar records como procesados
                $this->recordModel->markMultipleAsProcessed($recordIds, $detailId);

                $this->stats['details_updated']++;
                $this->stats['records_marked'] += count($recordIds);

                error_log("Updated detail ID {$detailId} with new times from records");
            } else {
                throw new Exception("No se pudo actualizar detail {$detailId}");
            }

        } catch (Exception $e) {
            $this->errors[] = "Error actualizando detail: " . $e->getMessage();
            $this->stats['errors']++;
            error_log("Error en updateDetail: " . $e->getMessage());
        }
    }

    /**
     * Determinar si un detail necesita actualización
     *
     * @param array $existingDetail
     * @param string|null $newTimeIn
     * @param string|null $newTimeOut
     * @return bool
     */
    private function needsUpdate($existingDetail, $newTimeIn, $newTimeOut)
    {
        // 1. Si no tiene time_in y ahora sí hay, actualizar
        if (empty($existingDetail['time_in']) && !empty($newTimeIn)) {
            return true;
        }

        // 2. Si no tiene time_out y ahora sí hay, actualizar
        if (empty($existingDetail['time_out']) && !empty($newTimeOut)) {
            return true;
        }

        // 3. Si los horarios son diferentes, actualizar
        if ($existingDetail['time_in'] !== $newTimeIn || $existingDetail['time_out'] !== $newTimeOut) {
            return true;
        }

        // 4. Si los horarios programados están NULL, actualizar
        if (empty($existingDetail['scheduled_time_in']) || empty($existingDetail['scheduled_time_out'])) {
            return true;
        }

        return false;
    }

    /**
     * Determinar estado basado en time_in y time_out
     *
     * @param string|null $timeIn
     * @param string|null $timeOut
     * @return string
     */
    private function determineStatus($timeIn, $timeOut)
    {
        if (empty($timeIn) && empty($timeOut)) {
            return 'ABSENT';
        }

        if (empty($timeIn) || empty($timeOut)) {
            return 'INCOMPLETE';
        }

        return 'PRESENT';
    }

    /**
     * Obtener información de horario
     *
     * @param int $scheduleId
     * @return array|false
     */
    private function getSchedule($scheduleId)
    {
        try {
            $sql = "SELECT time_in, time_out FROM schedules WHERE id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$scheduleId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo schedule: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesar un día específico completo
     *
     * @param string $date Fecha (YYYY-MM-DD)
     * @return array Estadísticas
     */
    public function processDay($date)
    {
        return $this->processToDetails($date, $date);
    }

    /**
     * Procesar registros pendientes hasta una fecha
     *
     * @param string|null $upToDate Fecha límite (default: hoy)
     * @return array
     */
    public function processUpToDate($upToDate = null)
    {
        if (!$upToDate) {
            $upToDate = date('Y-m-d');
        }

        // Obtener fecha del registro más antiguo sin procesar
        $sql = "SELECT MIN(punch_date) as oldest_date
                FROM attendance_records
                WHERE is_processed = 0 AND is_duplicate = 0";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $dateFrom = $result['oldest_date'] ?? $upToDate;

        return $this->processToDetails($dateFrom, $upToDate);
    }

    /**
     * Reprocesar un día eliminando details existentes
     *
     * @param string $date
     * @return array
     */
    public function reprocessDay($date)
    {
        try {
            // 1. Obtener header del día
            $header = $this->headerModel->getByDate($date);

            if ($header) {
                // 2. Eliminar details existentes
                $this->detailModel->deleteByHeader($header['id']);
                error_log("Deleted existing details for date {$date}");
            }

            // 3. Marcar records como no procesados
            $sql = "UPDATE attendance_records
                    SET is_processed = 0,
                        processed_at = NULL,
                        detail_id = NULL
                    WHERE punch_date = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$date]);

            error_log("Unmarked records for reprocessing: {$date}");

            // 4. Procesar de nuevo
            return $this->processDay($date);

        } catch (Exception $e) {
            $this->errors[] = "Error en reprocessDay: " . $e->getMessage();
            $this->stats['errors']++;
            error_log("Error en reprocessDay: " . $e->getMessage());
            return $this->getStats();
        }
    }

    /**
     * Resetear estadísticas
     */
    private function resetStats()
    {
        $this->stats = [
            'total_records' => 0,
            'groups_processed' => 0,
            'details_created' => 0,
            'details_updated' => 0,
            'details_skipped' => 0,
            'records_marked' => 0,
            'errors' => 0
        ];
        $this->errors = [];
    }

    /**
     * Obtener estadísticas del procesamiento
     *
     * @return array
     */
    public function getStats()
    {
        return array_merge($this->stats, [
            'errors_detail' => $this->errors
        ]);
    }

    /**
     * Obtener errores
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
