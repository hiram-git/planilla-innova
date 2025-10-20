<?php

namespace App\Services\Attendance\Calculators;

use App\Core\Database;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\BusinessCalendar;
use DateTime;
use DatePeriod;
use DateInterval;
use Exception;

/**
 * AbsenceDetector
 *
 * Detecta ausencias de empleados en días laborables.
 * Identifica días sin marcación y los clasifica como:
 * - JUSTIFIED: Ausencia justificada (permisos, incapacidades)
 * - UNJUSTIFIED: Ausencia sin justificación
 * - UNKNOWN: Pendiente de verificación
 *
 * @package App\Services\Attendance\Calculators
 * @version 1.0
 */
class AbsenceDetector
{
    private $employeeModel;
    private $attendanceModel;
    private $businessCalendar;
    private $db;

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->attendanceModel = new Attendance();
        $this->businessCalendar = new BusinessCalendar();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Detectar ausencias de un empleado en un rango de fechas
     *
     * @param int $employeeId ID del empleado
     * @param string $startDate Fecha inicial (Y-m-d)
     * @param string $endDate Fecha final (Y-m-d)
     * @return array Array de ausencias detectadas
     */
    public function detectAbsences($employeeId, $startDate, $endDate)
    {
        $absences = [];

        // Verificar que el empleado existe
        $employee = $this->employeeModel->find($employeeId);
        if (!$employee) {
            return $absences;
        }

        // Obtener asistencias del empleado en el rango
        $attendances = $this->attendanceModel->getAttendanceByDateRange($startDate, $endDate, $employeeId);
        $attendanceDates = array_column($attendances, 'date');

        // Crear periodo de fechas
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

        // Iterar cada día del período
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');

            // Verificar si es día laboral
            $isWorkingDay = $this->businessCalendar->isWorkingDay($dateStr);

            // Si no es día laboral, no es ausencia
            if (!$isWorkingDay) {
                continue;
            }

            // Verificar si el empleado ya estaba contratado ese día
            if (!$this->isEmployeeActive($employee, $dateStr)) {
                continue;
            }

            // Si no hay marcación ese día, es ausencia
            if (!in_array($dateStr, $attendanceDates)) {
                $absences[] = [
                    'employee_id' => $employeeId,
                    'date' => $dateStr,
                    'absence_type' => 'UNJUSTIFIED', // Por default, sin justificar
                    'is_working_day' => true,
                    'day_type' => 'LABORAL',
                    'detected_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        return $absences;
    }

    /**
     * Detectar ausencias de múltiples empleados
     *
     * @param array $employeeIds Array de IDs de empleados
     * @param string $startDate Fecha inicial
     * @param string $endDate Fecha final
     * @return array Array agrupado por empleado
     */
    public function detectBulkAbsences($employeeIds, $startDate, $endDate)
    {
        $results = [];

        foreach ($employeeIds as $employeeId) {
            $absences = $this->detectAbsences($employeeId, $startDate, $endDate);
            if (!empty($absences)) {
                $results[$employeeId] = $absences;
            }
        }

        return $results;
    }

    /**
     * Detectar ausencias de todos los empleados activos en un rango
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function detectAllAbsences($startDate, $endDate)
    {
        // Obtener todos los empleados activos
        $employees = $this->employeeModel->all();
        $activeEmployees = array_filter($employees, function($emp) {
            return $emp['situacion_id'] == 1; // Activos
        });

        $employeeIds = array_column($activeEmployees, 'id');

        return $this->detectBulkAbsences($employeeIds, $startDate, $endDate);
    }

    /**
     * Verificar si un empleado estaba activo en una fecha específica
     *
     * @param array $employee Datos del empleado
     * @param string $date Fecha a verificar
     * @return bool
     */
    private function isEmployeeActive($employee, $date)
    {
        // Verificar fecha de ingreso
        $fechaIngreso = $employee['fecha_ingreso'] ?? null;
        if ($fechaIngreso && $date < $fechaIngreso) {
            return false;
        }

        // Verificar si tiene fecha de terminación
        $terminationDate = $employee['termination_date'] ?? null;
        if ($terminationDate && $date > $terminationDate) {
            return false;
        }

        return true;
    }

    /**
     * Contar ausencias de un empleado en un período
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array Estadísticas de ausencias
     */
    public function countAbsences($employeeId, $startDate, $endDate)
    {
        $absences = $this->detectAbsences($employeeId, $startDate, $endDate);

        return [
            'total_absences' => count($absences),
            'unjustified' => count(array_filter($absences, fn($a) => $a['absence_type'] === 'UNJUSTIFIED')),
            'justified' => count(array_filter($absences, fn($a) => $a['absence_type'] === 'JUSTIFIED')),
            'pending' => count(array_filter($absences, fn($a) => $a['absence_type'] === 'UNKNOWN')),
            'absences' => $absences
        ];
    }

    /**
     * Detectar ausencias del día actual
     *
     * @return array Empleados ausentes hoy
     */
    public function detectTodayAbsences()
    {
        $today = date('Y-m-d');
        return $this->detectAllAbsences($today, $today);
    }

    /**
     * Detectar ausencias de la semana actual
     *
     * @return array
     */
    public function detectWeekAbsences()
    {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));

        return $this->detectAllAbsences($startOfWeek, $endOfWeek);
    }

    /**
     * Detectar ausencias del mes actual
     *
     * @return array
     */
    public function detectMonthAbsences()
    {
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');

        return $this->detectAllAbsences($startOfMonth, $endOfMonth);
    }

    /**
     * Generar reporte de ausencias por empleado
     *
     * @param string $startDate
     * @param string $endDate
     * @return array Array con resumen por empleado
     */
    public function generateAbsenceReport($startDate, $endDate)
    {
        $allAbsences = $this->detectAllAbsences($startDate, $endDate);
        $report = [];

        foreach ($allAbsences as $employeeId => $absences) {
            $employee = $this->employeeModel->find($employeeId);

            $report[] = [
                'employee_id' => $employeeId,
                'employee_name' => $employee ? "{$employee['firstname']} {$employee['lastname']}" : 'Desconocido',
                'employee_code' => $employee['employee_id'] ?? '',
                'total_absences' => count($absences),
                'unjustified' => count(array_filter($absences, fn($a) => $a['absence_type'] === 'UNJUSTIFIED')),
                'dates' => array_column($absences, 'date')
            ];
        }

        // Ordenar por mayor número de ausencias
        usort($report, function($a, $b) {
            return $b['total_absences'] - $a['total_absences'];
        });

        return $report;
    }

    /**
     * Verificar si un empleado tiene ausencias recurrentes
     * (3 o más ausencias en un mes = patrón preocupante)
     *
     * @param int $employeeId
     * @param string $month Mes en formato Y-m
     * @return array
     */
    public function checkRecurrentAbsences($employeeId, $month = null)
    {
        if (!$month) {
            $month = date('Y-m');
        }

        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $absences = $this->detectAbsences($employeeId, $startDate, $endDate);
        $total = count($absences);

        return [
            'is_recurrent' => $total >= 3,
            'total_absences' => $total,
            'threshold' => 3,
            'absences' => $absences
        ];
    }

    /**
     * Calcular tasa de ausentismo de un empleado (%)
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function calculateAbsenteeismRate($employeeId, $startDate, $endDate)
    {
        $workingDays = $this->businessCalendar->getWorkingDaysBetween($startDate, $endDate);
        $absences = $this->detectAbsences($employeeId, $startDate, $endDate);
        $totalAbsences = count($absences);

        $rate = $workingDays > 0 ? ($totalAbsences / $workingDays) * 100 : 0;

        return [
            'working_days' => $workingDays,
            'absences' => $totalAbsences,
            'rate' => round($rate, 2),
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }

    // ========================================
    // MÉTODOS DE PERSISTENCIA EN BD
    // ========================================

    /**
     * Guardar ausencia en la tabla attendance_absence_log
     *
     * @param array $absence Datos de la ausencia
     * @return int ID del registro insertado
     * @throws Exception Si falla la inserción
     */
    public function saveAbsence(array $absence): int
    {
        try {
            // Verificar si ya existe
            $existing = $this->getExistingAbsence($absence['employee_id'], $absence['date']);

            if ($existing) {
                // Actualizar existente
                return $this->updateAbsence($existing['id'], $absence);
            } else {
                // Insertar nuevo
                return $this->insertAbsence($absence);
            }

        } catch (Exception $e) {
            error_log("Error guardando ausencia: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Inserta una nueva ausencia en BD
     *
     * @param array $absence
     * @return int
     */
    private function insertAbsence(array $absence): int
    {
        $sql = "INSERT INTO attendance_absence_log (
            employee_id, absence_date, absence_type,
            justified, justification_type, justification_document, justification_notes,
            is_working_day, day_type,
            detected_at, detection_method,
            resolved, resolved_at, resolved_by
        ) VALUES (
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            ?, ?,
            ?, ?, ?
        )";

        $params = [
            $absence['employee_id'],
            $absence['date'],
            $absence['absence_type'] ?? 'UNJUSTIFIED',
            $absence['justified'] ?? 0,
            $absence['justification_type'] ?? null,
            $absence['justification_document'] ?? null,
            $absence['justification_notes'] ?? null,
            $absence['is_working_day'] ?? 1,
            $absence['day_type'] ?? 'LABORAL',
            $absence['detected_at'] ?? date('Y-m-d H:i:s'),
            $absence['detection_method'] ?? 'AUTO',
            $absence['resolved'] ?? 0,
            $absence['resolved_at'] ?? null,
            $absence['resolved_by'] ?? null,
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza una ausencia existente
     *
     * @param int $id
     * @param array $absence
     * @return int
     */
    private function updateAbsence(int $id, array $absence): int
    {
        $sql = "UPDATE attendance_absence_log SET
            absence_type = ?,
            justified = ?,
            justification_type = ?,
            justification_document = ?,
            justification_notes = ?,
            is_working_day = ?,
            day_type = ?,
            detection_method = ?
        WHERE id = ?";

        $params = [
            $absence['absence_type'] ?? 'UNJUSTIFIED',
            $absence['justified'] ?? 0,
            $absence['justification_type'] ?? null,
            $absence['justification_document'] ?? null,
            $absence['justification_notes'] ?? null,
            $absence['is_working_day'] ?? 1,
            $absence['day_type'] ?? 'LABORAL',
            $absence['detection_method'] ?? 'AUTO',
            $id,
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $id;
    }

    /**
     * Obtiene ausencia existente por empleado y fecha
     *
     * @param int $employeeId
     * @param string $date
     * @return array|null
     */
    private function getExistingAbsence(int $employeeId, string $date): ?array
    {
        $sql = "SELECT * FROM attendance_absence_log
                WHERE employee_id = ? AND absence_date = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $date]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Detecta y guarda ausencias automáticamente
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @param bool $saveToDb Si debe guardar en BD (default: true)
     * @return array Estadísticas del procesamiento
     */
    public function detectAndSaveAbsences(int $employeeId, string $startDate, string $endDate, bool $saveToDb = true): array
    {
        $absences = $this->detectAbsences($employeeId, $startDate, $endDate);
        $savedCount = 0;
        $errors = [];

        if ($saveToDb) {
            foreach ($absences as $absence) {
                try {
                    $this->saveAbsence($absence);
                    $savedCount++;
                } catch (Exception $e) {
                    $errors[] = [
                        'date' => $absence['date'],
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return [
            'employee_id' => $employeeId,
            'detected' => count($absences),
            'saved' => $savedCount,
            'errors' => count($errors),
            'absences' => $absences,
            'error_details' => $errors,
        ];
    }

    /**
     * Detecta y guarda ausencias de múltiples empleados en batch
     *
     * @param array $employeeIds
     * @param string $startDate
     * @param string $endDate
     * @param bool $saveToDb
     * @return array
     */
    public function detectAndSaveBulk(array $employeeIds, string $startDate, string $endDate, bool $saveToDb = true): array
    {
        $results = [];
        $totalDetected = 0;
        $totalSaved = 0;
        $totalErrors = 0;

        foreach ($employeeIds as $employeeId) {
            $result = $this->detectAndSaveAbsences($employeeId, $startDate, $endDate, $saveToDb);
            $results[] = $result;

            $totalDetected += $result['detected'];
            $totalSaved += $result['saved'];
            $totalErrors += $result['errors'];
        }

        return [
            'employees_processed' => count($employeeIds),
            'total_detected' => $totalDetected,
            'total_saved' => $totalSaved,
            'total_errors' => $totalErrors,
            'results' => $results,
        ];
    }

    /**
     * Justificar una ausencia existente
     *
     * @param int $absenceId ID de la ausencia en attendance_absence_log
     * @param string $justificationType MEDICAL, PERMISSION, VACATION, OTHER
     * @param string|null $document Ruta del documento justificativo
     * @param string|null $notes Notas adicionales
     * @param int|null $resolvedBy ID del usuario que resolvió
     * @return bool
     */
    public function justifyAbsence(int $absenceId, string $justificationType, ?string $document = null, ?string $notes = null, ?int $resolvedBy = null): bool
    {
        try {
            $sql = "UPDATE attendance_absence_log SET
                justified = 1,
                justification_type = ?,
                justification_document = ?,
                justification_notes = ?,
                absence_type = 'JUSTIFIED',
                resolved = 1,
                resolved_at = ?,
                resolved_by = ?
            WHERE id = ?";

            $params = [
                $justificationType,
                $document,
                $notes,
                date('Y-m-d H:i:s'),
                $resolvedBy,
                $absenceId,
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Error justificando ausencia: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rechazar justificación de ausencia
     *
     * @param int $absenceId
     * @param int|null $resolvedBy
     * @param string|null $notes
     * @return bool
     */
    public function rejectJustification(int $absenceId, ?int $resolvedBy = null, ?string $notes = null): bool
    {
        try {
            $sql = "UPDATE attendance_absence_log SET
                justified = 0,
                justification_notes = ?,
                absence_type = 'UNJUSTIFIED',
                resolved = 1,
                resolved_at = ?,
                resolved_by = ?
            WHERE id = ?";

            $params = [
                $notes,
                date('Y-m-d H:i:s'),
                $resolvedBy,
                $absenceId,
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Error rechazando justificación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todas las ausencias pendientes de resolver
     *
     * @return array
     */
    public function getPendingAbsences(): array
    {
        $sql = "SELECT aal.*,
                e.firstname, e.lastname, e.employee_id as employee_code, e.document_id
                FROM attendance_absence_log aal
                LEFT JOIN employees e ON aal.employee_id = e.id
                WHERE aal.resolved = 0
                ORDER BY aal.absence_date DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ausencias de un empleado desde BD
     *
     * @param int $employeeId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getEmployeeAbsences(int $employeeId, ?string $startDate = null, ?string $endDate = null): array
    {
        $sql = "SELECT * FROM attendance_absence_log WHERE employee_id = ?";
        $params = [$employeeId];

        if ($startDate && $endDate) {
            $sql .= " AND absence_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql .= " ORDER BY absence_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Elimina una ausencia de la BD
     *
     * @param int $absenceId
     * @return bool
     */
    public function deleteAbsence(int $absenceId): bool
    {
        try {
            $sql = "DELETE FROM attendance_absence_log WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$absenceId]);

            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Error eliminando ausencia: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas de ausencias por tipo
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getAbsenceStatistics(string $startDate, string $endDate): array
    {
        $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN justified = 1 THEN 1 ELSE 0 END) as justified,
                SUM(CASE WHEN justified = 0 THEN 1 ELSE 0 END) as unjustified,
                SUM(CASE WHEN resolved = 0 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN absence_type = 'JUSTIFIED' THEN 1 ELSE 0 END) as total_justified,
                SUM(CASE WHEN absence_type = 'UNJUSTIFIED' THEN 1 ELSE 0 END) as total_unjustified,
                COUNT(DISTINCT employee_id) as employees_with_absences
                FROM attendance_absence_log
                WHERE absence_date BETWEEN ? AND ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }
}
