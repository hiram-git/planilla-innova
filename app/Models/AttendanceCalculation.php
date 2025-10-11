<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * AttendanceCalculation Model
 *
 * Gestiona registros de cálculos avanzados de asistencias.
 * Almacena métricas procesadas: horas trabajadas, extras, tardanzas, etc.
 *
 * @package App\Models
 * @version 1.0
 */
class AttendanceCalculation extends Model
{
    public $table = 'attendance_calculations';

    public $fillable = [
        'attendance_id', 'employee_id', 'date', 'schedule_id',
        'time_in', 'time_out', 'scheduled_time_in', 'scheduled_time_out',
        'total_hours', 'regular_hours', 'overtime_hours',
        'overtime_25_hours', 'overtime_50_hours', 'night_hours', 'holiday_hours',
        'tardiness_minutes', 'is_late', 'early_departure_minutes',
        'is_absent', 'absence_type',
        'is_working_day', 'is_holiday', 'is_weekend', 'day_type',
        'lunch_time_minutes', 'is_perfect_attendance', 'punctuality_score',
        'notes', 'calculation_details', 'calculation_version',
        'calculated_at', 'recalculated_at'
    ];

    /**
     * Guardar o actualizar un cálculo
     * Si ya existe para ese attendance_id, actualiza
     *
     * @param array $calculation Datos del cálculo
     * @return int|bool ID del registro o false en error
     */
    public function saveCalculation($calculation)
    {
        try {
            // Verificar si ya existe
            $existing = $this->findByAttendanceId($calculation['attendance_id']);

            if ($existing) {
                // Actualizar existente
                $calculation['recalculated_at'] = date('Y-m-d H:i:s');
                return $this->update($existing['id'], $calculation);
            }

            // Insertar nuevo
            return $this->create($calculation);

        } catch (PDOException $e) {
            error_log("Error saving calculation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar cálculo por attendance_id
     *
     * @param int $attendanceId
     * @return array|null
     */
    public function findByAttendanceId($attendanceId)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE attendance_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$attendanceId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {
            error_log("Error finding calculation: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener cálculos de un empleado en un rango de fechas
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getByEmployeeAndDateRange($employeeId, $startDate, $endDate)
    {
        try {
            $sql = "SELECT ac.*, e.firstname, e.lastname, e.employee_id as emp_code
                    FROM {$this->table} ac
                    LEFT JOIN employees e ON ac.employee_id = e.id
                    WHERE ac.employee_id = ?
                    AND ac.date BETWEEN ? AND ?
                    ORDER BY ac.date DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting calculations by employee: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener resumen de métricas de un empleado en un período
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getEmployeeSummary($employeeId, $startDate, $endDate)
    {
        try {
            $sql = "SELECT
                        COUNT(*) as total_days,
                        SUM(total_hours) as total_hours,
                        SUM(regular_hours) as regular_hours,
                        SUM(overtime_hours) as overtime_hours,
                        SUM(overtime_25_hours) as overtime_25_hours,
                        SUM(overtime_50_hours) as overtime_50_hours,
                        SUM(night_hours) as night_hours,
                        SUM(holiday_hours) as holiday_hours,
                        SUM(is_late) as total_tardiness,
                        SUM(tardiness_minutes) as total_tardiness_minutes,
                        SUM(is_perfect_attendance) as perfect_days,
                        AVG(punctuality_score) as avg_punctuality_score
                    FROM {$this->table}
                    WHERE employee_id = ?
                    AND date BETWEEN ? AND ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $startDate, $endDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: [];

        } catch (PDOException $e) {
            error_log("Error getting employee summary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener empleados con más tardanzas en un período
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @return array
     */
    public function getTopLateEmployees($startDate, $endDate, $limit = 10)
    {
        try {
            $sql = "SELECT
                        ac.employee_id,
                        e.firstname,
                        e.lastname,
                        e.employee_id as emp_code,
                        COUNT(*) as total_late_days,
                        SUM(ac.tardiness_minutes) as total_tardiness_minutes,
                        AVG(ac.tardiness_minutes) as avg_tardiness_minutes
                    FROM {$this->table} ac
                    LEFT JOIN employees e ON ac.employee_id = e.id
                    WHERE ac.date BETWEEN ? AND ?
                    AND ac.is_late = 1
                    GROUP BY ac.employee_id
                    ORDER BY total_late_days DESC, total_tardiness_minutes DESC
                    LIMIT ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting top late employees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener empleados con asistencia perfecta en un período
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getPerfectAttendanceEmployees($startDate, $endDate)
    {
        try {
            $sql = "SELECT
                        ac.employee_id,
                        e.firstname,
                        e.lastname,
                        e.employee_id as emp_code,
                        COUNT(*) as total_days,
                        SUM(ac.is_perfect_attendance) as perfect_days,
                        AVG(ac.punctuality_score) as avg_punctuality_score
                    FROM {$this->table} ac
                    LEFT JOIN employees e ON ac.employee_id = e.id
                    WHERE ac.date BETWEEN ? AND ?
                    GROUP BY ac.employee_id
                    HAVING SUM(ac.is_perfect_attendance) = COUNT(*)
                    ORDER BY total_days DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting perfect attendance employees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de horas extras por empleado
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getOvertimeStatistics($startDate, $endDate)
    {
        try {
            $sql = "SELECT
                        ac.employee_id,
                        e.firstname,
                        e.lastname,
                        e.employee_id as emp_code,
                        SUM(ac.overtime_hours) as total_overtime,
                        SUM(ac.overtime_25_hours) as total_overtime_25,
                        SUM(ac.overtime_50_hours) as total_overtime_50,
                        SUM(ac.night_hours) as total_night_hours,
                        SUM(ac.holiday_hours) as total_holiday_hours
                    FROM {$this->table} ac
                    LEFT JOIN employees e ON ac.employee_id = e.id
                    WHERE ac.date BETWEEN ? AND ?
                    AND ac.overtime_hours > 0
                    GROUP BY ac.employee_id
                    ORDER BY total_overtime DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting overtime statistics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener totales generales de un período
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getPeriodTotals($startDate, $endDate)
    {
        try {
            $sql = "SELECT
                        COUNT(DISTINCT employee_id) as total_employees,
                        COUNT(*) as total_records,
                        SUM(total_hours) as total_hours,
                        SUM(overtime_hours) as total_overtime,
                        SUM(is_late) as total_tardiness,
                        SUM(is_perfect_attendance) as total_perfect_days,
                        AVG(punctuality_score) as avg_punctuality_score
                    FROM {$this->table}
                    WHERE date BETWEEN ? AND ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: [];

        } catch (PDOException $e) {
            error_log("Error getting period totals: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Eliminar cálculos de un período (útil para reprocesamiento)
     *
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    public function deletePeriodCalculations($startDate, $endDate)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE date BETWEEN ? AND ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$startDate, $endDate]);

        } catch (PDOException $e) {
            error_log("Error deleting period calculations: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener cálculos del día actual
     *
     * @return array
     */
    public function getTodayCalculations()
    {
        $today = date('Y-m-d');
        return $this->getByDateRange($today, $today);
    }

    /**
     * Obtener cálculos en un rango de fechas
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getByDateRange($startDate, $endDate)
    {
        try {
            $sql = "SELECT ac.*, e.firstname, e.lastname, e.employee_id as emp_code
                    FROM {$this->table} ac
                    LEFT JOIN employees e ON ac.employee_id = e.id
                    WHERE ac.date BETWEEN ? AND ?
                    ORDER BY ac.date DESC, e.lastname, e.firstname";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting calculations by date range: " . $e->getMessage());
            return [];
        }
    }
}
