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
            $recordIds = explode(',', $group['record_ids']);

            // 1. Obtener información del empleado y su horario
            $employee = $this->employeeModel->find($employeeId);
            if (!$employee) {
                throw new Exception("Empleado {$employeeId} no encontrado");
            }

            $schedule = null;
            if (!empty($employee['schedule_id'])) {
                $schedule = $this->getSchedule($employee['schedule_id']);
            }

            // 2. Determinar si el horario tiene período de almuerzo
            $hasLunchPeriod = false;
            if ($schedule && !empty($schedule['salida_almuerzo']) && !empty($schedule['entrada_almuerzo'])) {
                $hasLunchPeriod = true;
            }

            // 3. Obtener y clasificar marcaciones
            $timeIn = null;
            $timeOut = null;
            $lunchOut = null;
            $lunchIn = null;

            if ($hasLunchPeriod) {
                // Si tiene almuerzo: obtener todas las marcaciones individuales para clasificar
                $allPunches = $this->recordModel->getByEmployeeAndDate($employeeId, $date);
                $classified = $this->classifyPunches($allPunches, $schedule);

                $timeIn = $classified['time_in'];
                $lunchOut = $classified['lunch_out'];
                $lunchIn = $classified['lunch_in'];
                $timeOut = $classified['time_out'];
            } else {
                // Si NO tiene almuerzo: usar lógica tradicional (primera/última)
                $firstCheckIn = $group['first_check_in'];
                $lastCheckOut = $group['last_check_out'];

                $timeIn = $firstCheckIn ? date('H:i:s', strtotime($firstCheckIn)) : null;
                $timeOut = $lastCheckOut ? date('H:i:s', strtotime($lastCheckOut)) : null;
            }

            // 4. Verificar si ya existe un detail para este empleado y fecha
            $existingDetail = $this->detailModel->getByDateAndEmployee($date, $employeeId);

            if ($existingDetail) {
                // Ya existe - comparar y decidir si actualizar
                $needsUpdate = $this->needsUpdate($existingDetail, $timeIn, $timeOut, $lunchOut, $lunchIn);

                if ($needsUpdate) {
                    $this->updateDetail($existingDetail['id'], $timeIn, $timeOut, $lunchOut, $lunchIn, $schedule, $recordIds);
                } else {
                    // No necesita actualización, solo marcar records como procesados
                    $this->recordModel->markMultipleAsProcessed($recordIds, $existingDetail['id']);
                    $this->stats['details_skipped']++;
                    $this->stats['records_marked'] += count($recordIds);
                }
            } else {
                // No existe - crear nuevo detail
                $this->createDetail($employeeId, $date, $timeIn, $timeOut, $lunchOut, $lunchIn, $schedule, $recordIds);
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
     * @param string|null $lunchOut
     * @param string|null $lunchIn
     * @param array|null $schedule
     * @param array $recordIds
     */
    private function createDetail($employeeId, $date, $timeIn, $timeOut, $lunchOut, $lunchIn, $schedule, $recordIds)
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

            // 2. Obtener información del empleado
            $employee = $this->employeeModel->find($employeeId);

            if (!$employee) {
                throw new Exception("Empleado {$employeeId} no encontrado");
            }

            // 3. Preparar horarios programados
            $scheduledTimeIn = $schedule['time_in'] ?? null;
            $scheduledTimeOut = $schedule['time_out'] ?? null;
            $scheduledLunchOut = $schedule['salida_almuerzo'] ?? null;
            $scheduledLunchIn = $schedule['entrada_almuerzo'] ?? null;

            // 4. Convertir horas a DATETIME para lunch_out y lunch_in
            $lunchOutDateTime = null;
            $lunchInDateTime = null;

            if ($lunchOut) {
                $lunchOutDateTime = $date . ' ' . $lunchOut;
            }

            if ($lunchIn) {
                $lunchInDateTime = $date . ' ' . $lunchIn;
            }

            // 5. Determinar estado inicial
            $status = $this->determineStatus($timeIn, $timeOut, $lunchOut, $lunchIn, $schedule);

            // 6. Crear registro en attendance_detail
            $detailData = [
                'header_id' => $headerId,
                'employee_id' => $employeeId,
                'schedule_id' => $employee['schedule_id'] ?? null,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'lunch_out' => $lunchOutDateTime,
                'lunch_in' => $lunchInDateTime,
                'scheduled_time_in' => $scheduledTimeIn,
                'scheduled_time_out' => $scheduledTimeOut,
                'scheduled_lunch_out' => $scheduledLunchOut,
                'scheduled_lunch_in' => $scheduledLunchIn,
                'device_id' => null,
                'status' => $status,
                'is_late' => 0,
                'tardiness_minutes' => 0,
                'hours_worked' => 0,
                'notes' => 'Procesado desde attendance_records'
            ];

            $detailId = $this->detailModel->create($detailData);

            if ($detailId) {
                // 7. Marcar records como procesados
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
     * @param string|null $lunchOut
     * @param string|null $lunchIn
     * @param array|null $schedule
     * @param array $recordIds
     */
    private function updateDetail($detailId, $timeIn, $timeOut, $lunchOut, $lunchIn, $schedule, $recordIds)
    {
        try {
            // Obtener el detail actual
            $detail = $this->detailModel->getById($detailId);

            if (!$detail) {
                throw new Exception("Detail {$detailId} no encontrado");
            }

            // Convertir horas a DATETIME para lunch_out y lunch_in
            $lunchOutDateTime = null;
            $lunchInDateTime = null;

            if ($lunchOut && !empty($detail['header_id'])) {
                // Extraer la fecha del detail
                $header = $this->headerModel->getById($detail['header_id']);
                $date = $header['attendance_date'] ?? date('Y-m-d');
                $lunchOutDateTime = $date . ' ' . $lunchOut;
            }

            if ($lunchIn && !empty($detail['header_id'])) {
                $header = $this->headerModel->getById($detail['header_id']);
                $date = $header['attendance_date'] ?? date('Y-m-d');
                $lunchInDateTime = $date . ' ' . $lunchIn;
            }

            // Preparar datos de actualización
            $updateData = [
                'time_in' => $timeIn ?? $detail['time_in'],
                'time_out' => $timeOut ?? $detail['time_out'],
                'lunch_out' => $lunchOutDateTime ?? $detail['lunch_out'],
                'lunch_in' => $lunchInDateTime ?? $detail['lunch_in'],
                'scheduled_lunch_out' => $schedule['salida_almuerzo'] ?? $detail['scheduled_lunch_out'],
                'scheduled_lunch_in' => $schedule['entrada_almuerzo'] ?? $detail['scheduled_lunch_in'],
                'status' => $this->determineStatus(
                    $timeIn ?? $detail['time_in'],
                    $timeOut ?? $detail['time_out'],
                    $lunchOut ?? null,
                    $lunchIn ?? null,
                    $schedule
                ),
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
     * @param string|null $newLunchOut
     * @param string|null $newLunchIn
     * @return bool
     */
    private function needsUpdate($existingDetail, $newTimeIn, $newTimeOut, $newLunchOut = null, $newLunchIn = null)
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

        // 4. Si no tiene lunch_out y ahora sí hay, actualizar
        if (empty($existingDetail['lunch_out']) && !empty($newLunchOut)) {
            return true;
        }

        // 5. Si no tiene lunch_in y ahora sí hay, actualizar
        if (empty($existingDetail['lunch_in']) && !empty($newLunchIn)) {
            return true;
        }

        // 6. Si los horarios programados están NULL, actualizar
        if (empty($existingDetail['scheduled_time_in']) || empty($existingDetail['scheduled_time_out'])) {
            return true;
        }

        // 7. Si los horarios de almuerzo programados están NULL y deberían estar, actualizar
        if (empty($existingDetail['scheduled_lunch_out']) && empty($existingDetail['scheduled_lunch_in'])
            && (!empty($newLunchOut) || !empty($newLunchIn))) {
            return true;
        }

        return false;
    }

    /**
     * Determinar estado basado en marcaciones y horario
     *
     * @param string|null $timeIn
     * @param string|null $timeOut
     * @param string|null $lunchOut
     * @param string|null $lunchIn
     * @param array|null $schedule
     * @return string
     */
    private function determineStatus($timeIn, $timeOut, $lunchOut = null, $lunchIn = null, $schedule = null)
    {
        // Cambio solicitado: si hay hora de entrada y de salida, marcar PRESENTE
        if (!empty($timeIn) && !empty($timeOut)) {
            return 'PRESENT';
        }

        // Determinar si el horario requiere almuerzo
        $requiresLunch = false;
        if ($schedule && !empty($schedule['salida_almuerzo']) && !empty($schedule['entrada_almuerzo'])) {
            $requiresLunch = true;
        }

        // Si no hay ninguna marcación: ABSENT
        if (empty($timeIn) && empty($timeOut) && empty($lunchOut) && empty($lunchIn)) {
            return 'ABSENT';
        }

        // Si requiere almuerzo y no tiene ambas de almuerzo ni ambas principales, considerar INCOMPLETE
        if ($requiresLunch) {
            $missing = [];
            if (empty($timeIn)) $missing[] = 'entrada';
            if (empty($lunchOut)) $missing[] = 'salida_almuerzo';
            if (empty($lunchIn)) $missing[] = 'entrada_almuerzo';
            if (empty($timeOut)) $missing[] = 'salida';

            if (count($missing) > 0) {
                return 'INCOMPLETE';
            }
        }

        // Por defecto, si falta una de las principales y no aplica la regla de arriba, INCOMPLETE
        if (empty($timeIn) || empty($timeOut)) {
            return 'INCOMPLETE';
        }

        return 'PRESENT';
    }

    /**
     * Obtener información de horario (incluyendo período de almuerzo)
     *
     * @param int $scheduleId
     * @return array|false
     */
    private function getSchedule($scheduleId)
    {
        try {
            $sql = "SELECT time_in, time_out, salida_almuerzo, entrada_almuerzo
                    FROM schedules
                    WHERE id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$scheduleId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo schedule: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clasificar marcaciones del día en entrada/salida/almuerzo
     *
     * @param array $punches Array de marcaciones ordenadas por timestamp ASC
     * @param array|null $schedule Horario del empleado con campos de almuerzo
     * @return array Array con time_in, lunch_out, lunch_in, time_out
     */
    private function classifyPunches($punches, $schedule = null)
    {
        $result = [
            'time_in' => null,
            'lunch_out' => null,
            'lunch_in' => null,
            'time_out' => null
        ];

        // Si no hay marcaciones, retornar vacío
        if (empty($punches)) {
            return $result;
        }

        // Determinar si el horario tiene período de almuerzo configurado
        $hasLunchPeriod = false;
        if ($schedule && !empty($schedule['salida_almuerzo']) && !empty($schedule['entrada_almuerzo'])) {
            $hasLunchPeriod = true;
        }

        // Si NO tiene período de almuerzo: lógica tradicional (2 marcaciones)
        if (!$hasLunchPeriod) {
            // Primera marcación = entrada
            $result['time_in'] = !empty($punches[0]['punch_time'])
                ? $punches[0]['punch_time']
                : date('H:i:s', strtotime($punches[0]['timestamp']));

            // Última marcación = salida
            $lastIndex = count($punches) - 1;
            $result['time_out'] = !empty($punches[$lastIndex]['punch_time'])
                ? $punches[$lastIndex]['punch_time']
                : date('H:i:s', strtotime($punches[$lastIndex]['timestamp']));

            return $result;
        }

        // Si TIENE período de almuerzo: clasificar 4 marcaciones
        $scheduledLunchOut = strtotime($schedule['salida_almuerzo']);
        $scheduledLunchIn = strtotime($schedule['entrada_almuerzo']);

        // Convertir marcaciones a timestamps comparables
        $timestamps = [];
        foreach ($punches as $punch) {
            $time = !empty($punch['punch_time'])
                ? $punch['punch_time']
                : date('H:i:s', strtotime($punch['timestamp']));

            $timestamps[] = [
                'time' => $time,
                'timestamp' => strtotime($time)
            ];
        }

        // Clasificación basada en cercanía a horarios programados
        switch (count($timestamps)) {
            case 1:
                // Solo 1 marcación: asumir entrada
                $result['time_in'] = $timestamps[0]['time'];
                break;

            case 2:
                // 2 marcaciones: entrada y salida (sin almuerzo registrado)
                $result['time_in'] = $timestamps[0]['time'];
                $result['time_out'] = $timestamps[1]['time'];
                break;

            case 3:
                // 3 marcaciones: falta una (determinar cuál)
                $result['time_in'] = $timestamps[0]['time'];

                // Si la segunda está cerca de salida_almuerzo
                if (abs($timestamps[1]['timestamp'] - $scheduledLunchOut) < 1800) { // 30 min tolerancia
                    $result['lunch_out'] = $timestamps[1]['time'];
                    $result['time_out'] = $timestamps[2]['time'];
                }
                // Si la segunda está cerca de entrada_almuerzo
                elseif (abs($timestamps[1]['timestamp'] - $scheduledLunchIn) < 1800) {
                    $result['lunch_in'] = $timestamps[1]['time'];
                    $result['time_out'] = $timestamps[2]['time'];
                }
                // Si no coincide, asumir secuencia normal faltando entrada_almuerzo
                else {
                    $result['lunch_out'] = $timestamps[1]['time'];
                    $result['time_out'] = $timestamps[2]['time'];
                }
                break;

            case 4:
            default:
                // 4 o más marcaciones: asignar en orden
                $result['time_in'] = $timestamps[0]['time'];
                $result['lunch_out'] = $timestamps[1]['time'];
                $result['lunch_in'] = $timestamps[2]['time'];
                $result['time_out'] = $timestamps[3]['time'];
                break;
        }

        return $result;
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
