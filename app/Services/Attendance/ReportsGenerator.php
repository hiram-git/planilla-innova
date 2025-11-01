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

    // ==================== NUEVOS REPORTES DETALLADOS ====================

    /**
     * Generar reporte detallado de ausencias
     * Incluye: ID, cédula, nombres, apellidos, departamento
     * Filtrado por tipo de planilla y ordenado por departamento
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $tipoPlanillaId ID del tipo de planilla (filtro opcional)
     * @return array
     */
    public function generateDetailedAbsencesReport($startDate, $endDate, $tipoPlanillaId = null)
    {
        try {
            // Query con todos los campos necesarios
            $sql = "SELECT
                        aal.id as absence_id,
                        aal.absence_date,
                        aal.absence_type,
                        aal.justified,
                        aal.justification_type,
                        aal.justification_notes,
                        aal.is_working_day,
                        e.id as employee_id,
                        e.employee_id as employee_code,
                        e.document_id as cedula,
                        e.firstname,
                        e.lastname,
                        CONCAT(e.firstname, ' ', e.lastname) as full_name,
                        org.descripcion as departamento,
                        org.id as departamento_id,
                        sit.descripcion as situacion,
                        pos.codigo as position_name
                    FROM attendance_absence_log aal
                    INNER JOIN employees e ON aal.employee_id = e.id
                    LEFT JOIN organigrama org ON e.organigrama_id = org.id
                    LEFT JOIN situaciones sit ON e.situacion_id = sit.id
                    LEFT JOIN posiciones pos ON e.position_id = pos.id
                    WHERE aal.absence_date BETWEEN ? AND ?";

            $params = [$startDate, $endDate];

            // Filtro por tipo de planilla si se especifica
            if ($tipoPlanillaId) {
                $sql .= " AND FIND_IN_SET(?, e.tipo_planilla_id)";
                $params[] = $tipoPlanillaId;
            }

            // Ordenar por departamento y luego por apellido
            $sql .= " ORDER BY org.descripcion, e.lastname, e.firstname";

            $stmt = $this->employeeModel->db->prepare($sql);
            $stmt->execute($params);
            $absences = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Agrupar por departamento
            $byDepartment = [];
            foreach ($absences as $absence) {
                $dept = $absence['departamento'] ?? 'Sin Departamento';
                if (!isset($byDepartment[$dept])) {
                    $byDepartment[$dept] = [];
                }
                $byDepartment[$dept][] = $absence;
            }

            // Estadísticas generales
            $stats = [
                'total_absences' => count($absences),
                'justified' => count(array_filter($absences, fn($a) => $a['absence_type'] === 'JUSTIFIED')),
                'unjustified' => count(array_filter($absences, fn($a) => $a['absence_type'] === 'UNJUSTIFIED')),
                'pending' => count(array_filter($absences, fn($a) => $a['absence_type'] === 'PENDING')),
                'affected_employees' => count(array_unique(array_column($absences, 'employee_id'))),
                'departments_affected' => count($byDepartment)
            ];

            // Empleados con más ausencias
            $byEmployee = [];
            foreach ($absences as $absence) {
                $empId = $absence['employee_id'];
                if (!isset($byEmployee[$empId])) {
                    $byEmployee[$empId] = [
                        'employee_id' => $empId,
                        'employee_code' => $absence['employee_code'],
                        'cedula' => $absence['cedula'],
                        'full_name' => $absence['full_name'],
                        'departamento' => $absence['departamento'],
                        'position_name' => $absence['position_name'],
                        'absences' => []
                    ];
                }
                $byEmployee[$empId]['absences'][] = $absence;
            }

            // Top empleados con más ausencias
            usort($byEmployee, function($a, $b) {
                return count($b['absences']) - count($a['absences']);
            });
            $topAbsentEmployees = array_slice($byEmployee, 0, 10);

            // Agregar contador total a cada empleado
            foreach ($topAbsentEmployees as &$emp) {
                $emp['total_absences'] = count($emp['absences']);
                $emp['unjustified_count'] = count(array_filter($emp['absences'], fn($a) => $a['absence_type'] === 'UNJUSTIFIED'));
            }

            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'tipo_planilla_id' => $tipoPlanillaId
                ],
                'summary' => $stats,
                'by_department' => $byDepartment,
                'by_employee' => array_values($byEmployee),
                'top_absent_employees' => $topAbsentEmployees,
                'all_absences' => $absences
            ];

        } catch (\Exception $e) {
            error_log("Error generating detailed absences report: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generar reporte detallado de tardanzas
     * Incluye: ID, cédula, nombres, apellidos, departamento, minutos de tardanza
     * Filtrado por tipo de planilla y ordenado por departamento
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $tipoPlanillaId ID del tipo de planilla (filtro opcional)
     * @param int $minTardinessMinutes Mínimo de minutos de tardanza para incluir (default: 1)
     * @return array
     */
    public function generateDetailedTardinessReport($startDate, $endDate, $tipoPlanillaId = null, $minTardinessMinutes = 1)
    {
        try {
            // Query con todos los campos necesarios
            $sql = "SELECT
                        ac.id as calculation_id,
                        ac.attendance_detail_id,
                        ac.date,
                        ac.is_late,
                        ac.tardiness_minutes,
                        ac.time_in,
                        ac.scheduled_time_in,
                        ac.punctuality_score,
                        ac.total_hours,
                        e.id as employee_id,
                        e.employee_id as employee_code,
                        e.document_id as cedula,
                        e.firstname,
                        e.lastname,
                        CONCAT(e.firstname, ' ', e.lastname) as full_name,
                        org.descripcion as departamento,
                        org.id as departamento_id,
                        sit.descripcion as situacion,
                        pos.codigo as position_name,
                        s.time_in as schedule_time_in
                    FROM attendance_calculations ac
                    INNER JOIN employees e ON ac.employee_id = e.id
                    LEFT JOIN organigrama org ON e.organigrama_id = org.id
                    LEFT JOIN situaciones sit ON e.situacion_id = sit.id
                    LEFT JOIN posiciones pos ON e.position_id = pos.id
                    LEFT JOIN schedules s ON e.schedule_id = s.id
                    WHERE ac.date BETWEEN ? AND ?
                    AND ac.is_late = 1
                    AND ac.tardiness_minutes >= ?";

            $params = [$startDate, $endDate, $minTardinessMinutes];

            // Filtro por tipo de planilla si se especifica
            if ($tipoPlanillaId) {
                $sql .= " AND FIND_IN_SET(?, e.tipo_planilla_id)";
                $params[] = $tipoPlanillaId;
            }

            // Ordenar por departamento, apellido y fecha
            $sql .= " ORDER BY org.descripcion, e.lastname, e.firstname, ac.date";

            $stmt = $this->employeeModel->db->prepare($sql);
            $stmt->execute($params);
            $tardiness = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Agrupar por departamento
            $byDepartment = [];
            foreach ($tardiness as $late) {
                $dept = $late['departamento'] ?? 'Sin Departamento';
                if (!isset($byDepartment[$dept])) {
                    $byDepartment[$dept] = [];
                }
                $byDepartment[$dept][] = $late;
            }

            // Estadísticas generales
            $totalTardinessMinutes = array_sum(array_column($tardiness, 'tardiness_minutes'));
            $stats = [
                'total_late_arrivals' => count($tardiness),
                'affected_employees' => count(array_unique(array_column($tardiness, 'employee_id'))),
                'departments_affected' => count($byDepartment),
                'total_tardiness_minutes' => $totalTardinessMinutes,
                'total_tardiness_hours' => round($totalTardinessMinutes / 60, 2),
                'avg_tardiness_minutes' => count($tardiness) > 0 ? round($totalTardinessMinutes / count($tardiness), 2) : 0
            ];

            // Agrupar por empleado
            $byEmployee = [];
            foreach ($tardiness as $late) {
                $empId = $late['employee_id'];
                if (!isset($byEmployee[$empId])) {
                    $byEmployee[$empId] = [
                        'employee_id' => $empId,
                        'employee_code' => $late['employee_code'],
                        'cedula' => $late['cedula'],
                        'full_name' => $late['full_name'],
                        'departamento' => $late['departamento'],
                        'position_name' => $late['position_name'],
                        'late_arrivals' => [],
                        'total_tardiness_minutes' => 0,
                        'avg_tardiness_minutes' => 0
                    ];
                }
                $byEmployee[$empId]['late_arrivals'][] = $late;
                $byEmployee[$empId]['total_tardiness_minutes'] += $late['tardiness_minutes'];
            }

            // Calcular promedios
            foreach ($byEmployee as &$emp) {
                $emp['total_late_days'] = count($emp['late_arrivals']);
                $emp['avg_tardiness_minutes'] = round($emp['total_tardiness_minutes'] / $emp['total_late_days'], 2);
            }

            // Top empleados con más tardanzas
            usort($byEmployee, function($a, $b) {
                return $b['total_late_days'] - $a['total_late_days'];
            });
            $topLateEmployees = array_slice($byEmployee, 0, 10);

            // Distribución de tardanzas por rangos
            $tardinessRanges = [
                '1-5 min' => 0,
                '6-15 min' => 0,
                '16-30 min' => 0,
                '31-60 min' => 0,
                'Más de 1h' => 0
            ];

            foreach ($tardiness as $late) {
                $mins = $late['tardiness_minutes'];
                if ($mins <= 5) $tardinessRanges['1-5 min']++;
                elseif ($mins <= 15) $tardinessRanges['6-15 min']++;
                elseif ($mins <= 30) $tardinessRanges['16-30 min']++;
                elseif ($mins <= 60) $tardinessRanges['31-60 min']++;
                else $tardinessRanges['Más de 1h']++;
            }

            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'tipo_planilla_id' => $tipoPlanillaId,
                    'min_tardiness_minutes' => $minTardinessMinutes
                ],
                'summary' => $stats,
                'by_department' => $byDepartment,
                'by_employee' => array_values($byEmployee),
                'top_late_employees' => $topLateEmployees,
                'tardiness_ranges' => $tardinessRanges,
                'all_tardiness' => $tardiness
            ];

        } catch (\Exception $e) {
            error_log("Error generating detailed tardiness report: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generar reporte combinado de ausencias y tardanzas por empleado
     * Útil para evaluaciones de desempeño
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $tipoPlanillaId
     * @return array
     */
    public function generateCombinedAttendanceReport($startDate, $endDate, $tipoPlanillaId = null)
    {
        $absencesReport = $this->generateDetailedAbsencesReport($startDate, $endDate, $tipoPlanillaId);
        $tardinessReport = $this->generateDetailedTardinessReport($startDate, $endDate, $tipoPlanillaId);

        // Validar si hay errores en los reportes
        if (isset($absencesReport['error'])) {
            return $absencesReport;
        }
        if (isset($tardinessReport['error'])) {
            return $tardinessReport;
        }

        // Combinar datos por empleado
        $employeesMap = [];

        // Agregar ausencias
        foreach ($absencesReport['by_employee'] ?? [] as $empData) {
            $empId = $empData['employee_id'];
            $employeesMap[$empId] = [
                'employee_id' => $empId,
                'employee_code' => $empData['employee_code'],
                'cedula' => $empData['cedula'],
                'full_name' => $empData['full_name'],
                'departamento' => $empData['departamento'],
                'position_name' => $empData['position_name'],
                'total_absences' => count($empData['absences']),
                'unjustified_absences' => count(array_filter($empData['absences'], fn($a) => $a['absence_type'] === 'UNJUSTIFIED')),
                'total_tardiness' => 0,
                'total_tardiness_minutes' => 0,
                'performance_score' => 100
            ];
        }

        // Agregar tardanzas
        foreach ($tardinessReport['by_employee'] ?? [] as $empData) {
            $empId = $empData['employee_id'];
            if (!isset($employeesMap[$empId])) {
                $employeesMap[$empId] = [
                    'employee_id' => $empId,
                    'employee_code' => $empData['employee_code'],
                    'cedula' => $empData['cedula'],
                    'full_name' => $empData['full_name'],
                    'departamento' => $empData['departamento'],
                    'position_name' => $empData['position_name'],
                    'total_absences' => 0,
                    'unjustified_absences' => 0,
                    'total_tardiness' => 0,
                    'total_tardiness_minutes' => 0,
                    'performance_score' => 100
                ];
            }
            $employeesMap[$empId]['total_tardiness'] = $empData['total_late_days'];
            $employeesMap[$empId]['total_tardiness_minutes'] = $empData['total_tardiness_minutes'];
        }

        // Calcular score de desempeño (100 - penalizaciones)
        foreach ($employeesMap as &$emp) {
            $penalties = 0;
            $penalties += $emp['unjustified_absences'] * 10; // 10 puntos por ausencia injustificada
            $penalties += $emp['total_tardiness'] * 2; // 2 puntos por día de tardanza
            $penalties += floor($emp['total_tardiness_minutes'] / 60) * 5; // 5 puntos por hora de tardanza acumulada

            $emp['performance_score'] = max(0, 100 - $penalties);
        }

        // Agrupar por departamento
        $byDepartment = [];
        foreach ($employeesMap as $emp) {
            $dept = $emp['departamento'] ?? 'Sin Departamento';
            if (!isset($byDepartment[$dept])) {
                $byDepartment[$dept] = [];
            }
            $byDepartment[$dept][] = $emp;
        }

        // Ordenar empleados dentro de cada departamento por score (ascendente = peores primero)
        foreach ($byDepartment as &$employees) {
            usort($employees, function($a, $b) {
                return $a['performance_score'] - $b['performance_score'];
            });
        }

        // Top 10 mejores desempeños (mayor score)
        $allEmployees = array_values($employeesMap);
        usort($allEmployees, function($a, $b) {
            return $b['performance_score'] - $a['performance_score'];
        });
        $topPerformers = array_slice($allEmployees, 0, 10);

        // Top 10 peores desempeños (menor score)
        $bottomPerformers = array_slice(array_reverse($allEmployees), 0, 10);

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'tipo_planilla_id' => $tipoPlanillaId
            ],
            'summary' => [
                'total_employees' => count($employeesMap),
                'total_absences' => $absencesReport['summary']['total_absences'] ?? 0,
                'total_tardiness' => $tardinessReport['summary']['total_late_arrivals'] ?? 0,
                'total_tardiness_minutes' => $tardinessReport['summary']['total_tardiness_minutes'] ?? 0,
                'avg_performance_score' => count($employeesMap) > 0
                    ? round(array_sum(array_column($employeesMap, 'performance_score')) / count($employeesMap), 2)
                    : 100
            ],
            'by_department' => $byDepartment,
            'top_performers' => $topPerformers,
            'bottom_performers' => $bottomPerformers,
            'by_employee' => array_values($employeesMap),
            'absences_detail' => $absencesReport,
            'tardiness_detail' => $tardinessReport
        ];
    }

    /**
     * Generar reporte detallado de marcaciones (punches)
     * Incluye: ID, cédula, nombres, apellidos, departamento, marcaciones (entrada/salida), tardanzas, ausencias, horas trabajadas
     * Filtrado por tipo de planilla y ordenado por departamento
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $tipoPlanillaId ID del tipo de planilla (filtro opcional)
     * @return array
     */
    public function generateDetailedPunchesReport($startDate, $endDate, $tipoPlanillaId = null)
    {
        try {
            // Query con todos los campos necesarios incluyendo marcaciones
            $sql = "SELECT
                        ac.id as calculation_id,
                        ac.attendance_detail_id,
                        ac.date,
                        ac.time_in,
                        ac.time_out,
                        ac.scheduled_time_in,
                        ac.scheduled_time_out,
                        ac.is_late,
                        ac.is_absent,
                        ac.tardiness_minutes,
                        ac.total_hours,
                        ac.overtime_hours,
                        ac.overtime_25_hours,
                        ac.overtime_50_hours,
                        ac.is_perfect_attendance,
                        ac.punctuality_score,
                        e.id as employee_id,
                        e.employee_id as employee_code,
                        e.document_id as cedula,
                        e.firstname,
                        e.lastname,
                        CONCAT(e.firstname, ' ', e.lastname) as full_name,
                        org.descripcion as departamento,
                        org.id as departamento_id,
                        sit.descripcion as situacion,
                        pos.codigo as position_name,
                        s.time_in as schedule_time_in,
                        s.time_out as schedule_time_out
                    FROM attendance_calculations ac
                    INNER JOIN employees e ON ac.employee_id = e.id
                    LEFT JOIN organigrama org ON e.organigrama_id = org.id
                    LEFT JOIN situaciones sit ON e.situacion_id = sit.id
                    LEFT JOIN posiciones pos ON e.position_id = pos.id
                    LEFT JOIN schedules s ON e.schedule_id = s.id
                    WHERE ac.date BETWEEN ? AND ?";

            $params = [$startDate, $endDate];

            // Filtro por tipo de planilla si se especifica
            if ($tipoPlanillaId) {
                $sql .= " AND FIND_IN_SET(?, e.tipo_planilla_id)";
                $params[] = $tipoPlanillaId;
            }

            // Ordenar por departamento, apellido y fecha
            $sql .= " ORDER BY org.descripcion, e.lastname, e.firstname, ac.date";

            $stmt = $this->employeeModel->db->prepare($sql);
            $stmt->execute($params);
            $punches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Agrupar por departamento
            $byDepartment = [];
            foreach ($punches as $punch) {
                $dept = $punch['departamento'] ?? 'Sin Departamento';
                if (!isset($byDepartment[$dept])) {
                    $byDepartment[$dept] = [];
                }
                $byDepartment[$dept][] = $punch;
            }

            // Estadísticas generales
            $totalOnTime = count(array_filter($punches, fn($p) => !$p['is_late'] && !$p['is_absent']));
            $totalLate = count(array_filter($punches, fn($p) => $p['is_late']));
            $totalAbsent = count(array_filter($punches, fn($p) => $p['is_absent']));
            $totalHoursWorked = array_sum(array_column($punches, 'total_hours'));
            $totalOvertime = array_sum(array_column($punches, 'overtime_hours'));
            $totalTardinessMinutes = array_sum(array_column($punches, 'tardiness_minutes'));
            $avgHoursPerDay = count($punches) > 0 ? $totalHoursWorked / count($punches) : 0;

            $stats = [
                'total_punches' => count($punches),
                'total_on_time' => $totalOnTime,
                'total_late' => $totalLate,
                'total_absences' => $totalAbsent,
                'total_hours_worked' => round($totalHoursWorked, 2),
                'total_overtime' => round($totalOvertime, 2),
                'total_tardiness_minutes' => $totalTardinessMinutes,
                'avg_hours_per_day' => round($avgHoursPerDay, 2),
                'affected_employees' => count(array_unique(array_column($punches, 'employee_id'))),
                'departments_affected' => count($byDepartment)
            ];

            // Top empleados con más tardanzas
            $byEmployee = [];
            foreach ($punches as $punch) {
                $empId = $punch['employee_id'];
                if (!isset($byEmployee[$empId])) {
                    $byEmployee[$empId] = [
                        'employee_id' => $empId,
                        'employee_code' => $punch['employee_code'],
                        'cedula' => $punch['cedula'],
                        'full_name' => $punch['full_name'],
                        'departamento' => $punch['departamento'],
                        'position_name' => $punch['position_name'],
                        'total_days' => 0,
                        'total_late' => 0,
                        'total_absent' => 0,
                        'total_on_time' => 0,
                        'total_hours' => 0,
                        'total_overtime' => 0,
                        'total_tardiness_minutes' => 0
                    ];
                }
                $byEmployee[$empId]['total_days']++;
                if ($punch['is_late']) $byEmployee[$empId]['total_late']++;
                if ($punch['is_absent']) $byEmployee[$empId]['total_absent']++;
                if (!$punch['is_late'] && !$punch['is_absent']) $byEmployee[$empId]['total_on_time']++;
                $byEmployee[$empId]['total_hours'] += $punch['total_hours'];
                $byEmployee[$empId]['total_overtime'] += $punch['overtime_hours'];
                $byEmployee[$empId]['total_tardiness_minutes'] += $punch['tardiness_minutes'];
            }

            // Ordenar por tardanzas
            usort($byEmployee, function($a, $b) {
                return $b['total_tardiness_minutes'] - $a['total_tardiness_minutes'];
            });
            $topTardinessEmployees = array_slice($byEmployee, 0, 10);

            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'tipo_planilla_id' => $tipoPlanillaId
                ],
                'summary' => $stats,
                'by_department' => $byDepartment,
                'by_employee' => array_values($byEmployee),
                'top_tardiness_employees' => $topTardinessEmployees,
                'all_punches' => $punches
            ];

        } catch (\Exception $e) {
            error_log("Error generating detailed punches report: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ];
        }
    }
}
