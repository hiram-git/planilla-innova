<?php

namespace App\Services\Attendance\Calculators;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\BusinessCalendar;
use DateTime;
use DatePeriod;
use DateInterval;

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

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->attendanceModel = new Attendance();
        $this->businessCalendar = new BusinessCalendar();
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
}
