<?php

namespace App\Services\Attendance;

use App\Models\AttendanceCalculation;
use App\Models\Employee;
use App\Services\Attendance\Calculators\AbsenceDetector;

/**
 * ReportsGenerator
 *
 * Generador de reportes de asistencias:
 * - Reportes diarios, semanales, mensuales
 * - Estadísticas de puntualidad y horas extras
 * - Rankings de asistencia
 *
 * @package App\Services\Attendance
 * @version 1.0
 */
class ReportsGenerator
{
    private $calculationModel;
    private $employeeModel;
    private $absenceDetector;

    public function __construct()
    {
        $this->calculationModel = new AttendanceCalculation();
        $this->employeeModel = new Employee();
        $this->absenceDetector = new AbsenceDetector();
    }

    /**
     * Generar reporte diario de asistencias
     *
     * @param string $date Fecha en formato Y-m-d
     * @return array
     */
    public function generateDailyReport($date)
    {
        $calculations = $this->calculationModel->getByDateRange($date, $date);

        // Agrupar por estado
        $onTime = array_filter($calculations, fn($c) => $c['is_late'] == 0);
        $late = array_filter($calculations, fn($c) => $c['is_late'] == 1);
        $perfectAttendance = array_filter($calculations, fn($c) => $c['is_perfect_attendance'] == 1);

        // Detectar ausencias del día
        $absences = $this->absenceDetector->detectTodayAbsences();
        $totalAbsences = array_sum(array_map('count', $absences));

        // Calcular totales
        $totalHours = array_sum(array_column($calculations, 'total_hours'));
        $totalOvertime = array_sum(array_column($calculations, 'overtime_hours'));
        $avgPunctuality = !empty($calculations)
            ? array_sum(array_column($calculations, 'punctuality_score')) / count($calculations)
            : 0;

        return [
            'date' => $date,
            'day_of_week' => date('l', strtotime($date)),
            'summary' => [
                'total_employees' => count($calculations),
                'on_time' => count($onTime),
                'late' => count($late),
                'perfect_attendance' => count($perfectAttendance),
                'absences' => $totalAbsences,
                'total_hours' => round($totalHours, 2),
                'total_overtime' => round($totalOvertime, 2),
                'avg_punctuality_score' => round($avgPunctuality, 2)
            ],
            'details' => [
                'on_time_employees' => $this->formatEmployeeList($onTime),
                'late_employees' => $this->formatEmployeeList($late, true),
                'absent_employees' => $this->formatAbsenceList($absences)
            ]
        ];
    }

    /**
     * Generar reporte semanal de asistencias
     *
     * @param int $year
     * @param int $week Número de semana (1-52)
     * @return array
     */
    public function generateWeeklyReport($year, $week)
    {
        // Calcular fecha de inicio y fin de la semana
        $dto = new \DateTime();
        $dto->setISODate($year, $week);
        $startDate = $dto->format('Y-m-d');

        $dto->modify('+6 days');
        $endDate = $dto->format('Y-m-d');

        $calculations = $this->calculationModel->getByDateRange($startDate, $endDate);

        // Agrupar por empleado
        $byEmployee = [];
        foreach ($calculations as $calc) {
            $empId = $calc['employee_id'];
            if (!isset($byEmployee[$empId])) {
                $byEmployee[$empId] = [
                    'employee_id' => $empId,
                    'firstname' => $calc['firstname'],
                    'lastname' => $calc['lastname'],
                    'emp_code' => $calc['emp_code'],
                    'days_worked' => 0,
                    'total_hours' => 0,
                    'overtime_hours' => 0,
                    'late_days' => 0,
                    'perfect_days' => 0,
                    'avg_punctuality' => 0
                ];
            }

            $byEmployee[$empId]['days_worked']++;
            $byEmployee[$empId]['total_hours'] += $calc['total_hours'];
            $byEmployee[$empId]['overtime_hours'] += $calc['overtime_hours'];
            $byEmployee[$empId]['late_days'] += $calc['is_late'];
            $byEmployee[$empId]['perfect_days'] += $calc['is_perfect_attendance'];
        }

        // Calcular promedios
        foreach ($byEmployee as &$emp) {
            $empCalcs = array_filter($calculations, fn($c) => $c['employee_id'] == $emp['employee_id']);
            $emp['avg_punctuality'] = !empty($empCalcs)
                ? round(array_sum(array_column($empCalcs, 'punctuality_score')) / count($empCalcs), 2)
                : 0;
        }

        // Detectar ausencias
        $absences = $this->absenceDetector->detectAllAbsences($startDate, $endDate);

        // Totales generales
        $totals = $this->calculationModel->getPeriodTotals($startDate, $endDate);

        return [
            'period' => [
                'year' => $year,
                'week' => $week,
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'summary' => [
                'total_employees' => count($byEmployee),
                'total_hours' => round($totals['total_hours'] ?? 0, 2),
                'total_overtime' => round($totals['total_overtime'] ?? 0, 2),
                'total_tardiness' => $totals['total_tardiness'] ?? 0,
                'total_perfect_days' => $totals['total_perfect_days'] ?? 0,
                'avg_punctuality_score' => round($totals['avg_punctuality_score'] ?? 0, 2),
                'total_absences' => array_sum(array_map('count', $absences))
            ],
            'by_employee' => array_values($byEmployee),
            'top_performers' => $this->getTopPerformers($byEmployee, 5),
            'attendance_issues' => $this->getAttendanceIssues($byEmployee, $absences)
        ];
    }

    /**
     * Generar reporte mensual de asistencias
     *
     * @param int $year
     * @param int $month
     * @return array
     */
    public function generateMonthlyReport($year, $month)
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $calculations = $this->calculationModel->getByDateRange($startDate, $endDate);

        // Obtener resúmenes por empleado
        $employees = $this->employeeModel->findAll();
        $employeeSummaries = [];

        foreach ($employees as $employee) {
            if ($employee['situacion_id'] != 1) continue; // Solo activos

            $summary = $this->calculationModel->getEmployeeSummary(
                $employee['id'],
                $startDate,
                $endDate
            );

            if ($summary && $summary['total_days'] > 0) {
                $employeeSummaries[] = array_merge([
                    'employee_id' => $employee['id'],
                    'firstname' => $employee['firstname'],
                    'lastname' => $employee['lastname'],
                    'emp_code' => $employee['employee_id']
                ], $summary);
            }
        }

        // Detectar ausencias del mes
        $absences = $this->absenceDetector->detectAllAbsences($startDate, $endDate);
        $absenceReport = $this->absenceDetector->generateAbsenceReport($startDate, $endDate);

        // Estadísticas de horas extras
        $overtimeStats = $this->calculationModel->getOvertimeStatistics($startDate, $endDate);

        // Empleados con asistencia perfecta
        $perfectAttendance = $this->calculationModel->getPerfectAttendanceEmployees($startDate, $endDate);

        // Totales generales
        $totals = $this->calculationModel->getPeriodTotals($startDate, $endDate);

        return [
            'period' => [
                'year' => $year,
                'month' => $month,
                'month_name' => date('F', strtotime($startDate)),
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'summary' => [
                'total_employees' => count($employeeSummaries),
                'total_hours' => round($totals['total_hours'] ?? 0, 2),
                'total_overtime' => round($totals['total_overtime'] ?? 0, 2),
                'total_tardiness' => $totals['total_tardiness'] ?? 0,
                'total_perfect_days' => $totals['total_perfect_days'] ?? 0,
                'avg_punctuality_score' => round($totals['avg_punctuality_score'] ?? 0, 2),
                'total_absences' => array_sum(array_map('count', $absences)),
                'employees_with_absences' => count($absences)
            ],
            'by_employee' => $employeeSummaries,
            'overtime_statistics' => $overtimeStats,
            'perfect_attendance' => $perfectAttendance,
            'absence_report' => $absenceReport,
            'top_performers' => $this->getTopPerformersFromSummaries($employeeSummaries, 10),
            'attendance_alerts' => $this->generateAttendanceAlerts($employeeSummaries, $absenceReport)
        ];
    }

    /**
     * Generar reporte de puntualidad
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generatePunctualityReport($startDate, $endDate)
    {
        $topLate = $this->calculationModel->getTopLateEmployees($startDate, $endDate, 20);

        $calculations = $this->calculationModel->getByDateRange($startDate, $endDate);
        $avgPunctuality = !empty($calculations)
            ? array_sum(array_column($calculations, 'punctuality_score')) / count($calculations)
            : 0;

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'summary' => [
                'total_records' => count($calculations),
                'avg_punctuality_score' => round($avgPunctuality, 2),
                'employees_with_tardiness' => count($topLate)
            ],
            'top_late_employees' => $topLate,
            'punctuality_distribution' => $this->getPunctualityDistribution($calculations)
        ];
    }

    // ==================== MÉTODOS AUXILIARES ====================

    private function formatEmployeeList($calculations, $includeTardiness = false)
    {
        $list = [];
        foreach ($calculations as $calc) {
            $item = [
                'employee_id' => $calc['employee_id'],
                'name' => "{$calc['firstname']} {$calc['lastname']}",
                'emp_code' => $calc['emp_code'],
                'time_in' => $calc['time_in'],
                'time_out' => $calc['time_out'],
                'total_hours' => $calc['total_hours']
            ];

            if ($includeTardiness) {
                $item['tardiness_minutes'] = $calc['tardiness_minutes'];
            }

            $list[] = $item;
        }
        return $list;
    }

    private function formatAbsenceList($absences)
    {
        $list = [];
        foreach ($absences as $employeeId => $empAbsences) {
            $employee = $this->employeeModel->find($employeeId);
            $list[] = [
                'employee_id' => $employeeId,
                'name' => $employee ? "{$employee['firstname']} {$employee['lastname']}" : 'Desconocido',
                'emp_code' => $employee['employee_id'] ?? '',
                'absence_count' => count($empAbsences)
            ];
        }
        return $list;
    }

    private function getTopPerformers($byEmployee, $limit = 5)
    {
        $performers = $byEmployee;
        usort($performers, function($a, $b) {
            return $b['avg_punctuality'] - $a['avg_punctuality'];
        });
        return array_slice($performers, 0, $limit);
    }

    private function getTopPerformersFromSummaries($summaries, $limit = 10)
    {
        $performers = $summaries;
        usort($performers, function($a, $b) {
            return $b['avg_punctuality_score'] - $a['avg_punctuality_score'];
        });
        return array_slice($performers, 0, $limit);
    }

    private function getAttendanceIssues($byEmployee, $absences)
    {
        $issues = [];

        // Empleados con muchas tardanzas
        foreach ($byEmployee as $emp) {
            if ($emp['late_days'] >= 3) {
                $issues[] = [
                    'type' => 'excessive_tardiness',
                    'employee_id' => $emp['employee_id'],
                    'name' => "{$emp['firstname']} {$emp['lastname']}",
                    'late_days' => $emp['late_days']
                ];
            }
        }

        // Empleados con muchas ausencias
        foreach ($absences as $employeeId => $empAbsences) {
            if (count($empAbsences) >= 3) {
                $employee = $this->employeeModel->find($employeeId);
                $issues[] = [
                    'type' => 'excessive_absences',
                    'employee_id' => $employeeId,
                    'name' => $employee ? "{$employee['firstname']} {$employee['lastname']}" : 'Desconocido',
                    'absence_count' => count($empAbsences)
                ];
            }
        }

        return $issues;
    }

    private function generateAttendanceAlerts($summaries, $absenceReport)
    {
        $alerts = [];

        // Alertas por tardanzas excesivas
        foreach ($summaries as $summary) {
            if (($summary['total_tardiness'] ?? 0) >= 5) {
                $alerts[] = [
                    'level' => 'warning',
                    'type' => 'excessive_tardiness',
                    'employee' => "{$summary['firstname']} {$summary['lastname']}",
                    'message' => "Tardanzas excesivas: {$summary['total_tardiness']} días"
                ];
            }
        }

        // Alertas por ausencias
        foreach ($absenceReport as $absence) {
            if ($absence['total_absences'] >= 3) {
                $alerts[] = [
                    'level' => 'danger',
                    'type' => 'excessive_absences',
                    'employee' => $absence['employee_name'],
                    'message' => "Ausencias excesivas: {$absence['total_absences']} días"
                ];
            }
        }

        return $alerts;
    }

    private function getPunctualityDistribution($calculations)
    {
        $ranges = [
            'excellent' => ['min' => 90, 'max' => 100, 'count' => 0],
            'good' => ['min' => 75, 'max' => 89, 'count' => 0],
            'fair' => ['min' => 60, 'max' => 74, 'count' => 0],
            'poor' => ['min' => 0, 'max' => 59, 'count' => 0]
        ];

        foreach ($calculations as $calc) {
            $score = $calc['punctuality_score'];
            if ($score >= 90) $ranges['excellent']['count']++;
            elseif ($score >= 75) $ranges['good']['count']++;
            elseif ($score >= 60) $ranges['fair']['count']++;
            else $ranges['poor']['count']++;
        }

        return $ranges;
    }
}
