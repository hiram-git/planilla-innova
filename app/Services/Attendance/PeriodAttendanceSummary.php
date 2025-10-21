<?php

namespace App\Services\Attendance;

use App\Core\Database;
use App\Services\Attendance\Calculators\AttendanceCalculator;
use App\Services\Attendance\Calculators\LegalComplianceChecker;
use PDO;
use PDOException;

/**
 * PeriodAttendanceSummary
 *
 * Genera resumen consolidado de asistencias de un empleado para un período
 * Utilizado para integración con sistema de planillas
 *
 * @version 1.0.0
 * @date 2025-10-20
 */
class PeriodAttendanceSummary
{
    private $db;
    private AttendanceCalculator $calculator;
    private LegalComplianceChecker $complianceChecker;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->calculator = new AttendanceCalculator();
        $this->complianceChecker = new LegalComplianceChecker();
    }

    /**
     * Generar resumen de asistencias para un empleado en un período
     *
     * @param int $employee_id ID del empleado
     * @param string $period_start Fecha inicio período (Y-m-d)
     * @param string $period_end Fecha fin período (Y-m-d)
     * @return array|false Resumen consolidado o false en caso de error
     */
    public function generateSummary(int $employee_id, string $period_start, string $period_end)
    {
        try {
            // Obtener todas las asistencias calculadas del período
            $calculations = $this->getEmployeeCalculations($employee_id, $period_start, $period_end);

            if (empty($calculations)) {
                return $this->getEmptySummary($employee_id, $period_start, $period_end);
            }

            // Agregar métricas
            $summary = $this->aggregateMetrics($calculations, $employee_id, $period_start, $period_end);

            // Verificar cumplimiento legal
            $legalCompliance = $this->checkLegalCompliance($summary, $calculations);
            $summary = array_merge($summary, $legalCompliance);

            // Verificar alertas generadas
            $alertsInfo = $this->getAlertsInfo($employee_id, $period_start, $period_end);
            $summary = array_merge($summary, $alertsInfo);

            return $summary;

        } catch (PDOException $e) {
            error_log("Error generando resumen asistencias: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener cálculos de asistencia del empleado en el período
     */
    private function getEmployeeCalculations(int $employee_id, string $period_start, string $period_end): array
    {
        $sql = "SELECT *
                FROM attendance_calculations
                WHERE employee_id = ?
                AND date BETWEEN ? AND ?
                ORDER BY date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employee_id, $period_start, $period_end]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Agregar métricas de todos los cálculos del período
     */
    private function aggregateMetrics(array $calculations, int $employee_id, string $period_start, string $period_end): array
    {
        $summary = [
            'employee_id' => $employee_id,
            'period_start' => $period_start,
            'period_end' => $period_end,

            // Horas
            'total_hours_worked' => 0,
            'regular_hours' => 0,
            'overtime_hours_25' => 0,
            'overtime_hours_50' => 0,
            'night_hours' => 0,
            'holiday_hours' => 0,
            'sunday_hours' => 0,

            // Días
            'total_days_worked' => 0,
            'perfect_attendance_days' => 0,

            // Puntualidad
            'total_tardiness_minutes' => 0,
            'tardiness_count' => 0,
            'early_departures_count' => 0,
            'punctuality_score' => 100,

            // Almuerzo
            'total_lunch_time_minutes' => 0,
            'lunch_violations' => 0,
        ];

        foreach ($calculations as $calc) {
            $summary['total_hours_worked'] += (float)$calc['total_hours'];
            $summary['regular_hours'] += min((float)$calc['total_hours'], 8); // Máximo 8h regulares por día
            $summary['overtime_hours_25'] += (float)($calc['overtime_hours_25'] ?? 0);
            $summary['overtime_hours_50'] += (float)($calc['overtime_hours_50'] ?? 0);
            $summary['night_hours'] += (float)($calc['night_hours'] ?? 0);
            $summary['holiday_hours'] += (float)($calc['holiday_hours'] ?? 0);
            $summary['sunday_hours'] += (float)($calc['sunday_hours'] ?? 0);

            $summary['total_days_worked']++;

            if (($calc['perfect_attendance'] ?? 0) == 1) {
                $summary['perfect_attendance_days']++;
            }

            $summary['total_tardiness_minutes'] += (int)($calc['tardiness_minutes'] ?? 0);
            if (($calc['tardiness_minutes'] ?? 0) > 0) {
                $summary['tardiness_count']++;
            }

            if (($calc['early_departure_minutes'] ?? 0) > 0) {
                $summary['early_departures_count']++;
            }

            $summary['total_lunch_time_minutes'] += (int)($calc['lunch_time_minutes'] ?? 0);
            if (($calc['lunch_time_minutes'] ?? 0) < 30) {
                $summary['lunch_violations']++;
            }
        }

        // Calcular score de puntualidad promedio
        $totalPunctuality = 0;
        foreach ($calculations as $calc) {
            $totalPunctuality += (float)($calc['punctuality_score'] ?? 100);
        }
        $summary['punctuality_score'] = count($calculations) > 0 ?
            round($totalPunctuality / count($calculations), 2) : 100;

        // Obtener ausencias
        $absences = $this->getAbsencesInfo($employee_id, $period_start, $period_end);
        $summary = array_merge($summary, $absences);

        // Calcular montos (serán calculados por AttendanceConceptMapper posteriormente)
        $summary['total_regular_pay'] = 0;
        $summary['total_overtime_pay'] = 0;
        $summary['total_night_pay'] = 0;
        $summary['total_holiday_pay'] = 0;
        $summary['total_deductions'] = 0;
        $summary['total_bonuses'] = 0;
        $summary['net_attendance_pay'] = 0;

        return $summary;
    }

    /**
     * Obtener información de ausencias del período
     */
    private function getAbsencesInfo(int $employee_id, string $period_start, string $period_end): array
    {
        $sql = "SELECT
                    COUNT(*) as total_absences,
                    SUM(CASE WHEN status = 'JUSTIFIED' THEN 1 ELSE 0 END) as justified_absences,
                    SUM(CASE WHEN status = 'UNJUSTIFIED' THEN 1 ELSE 0 END) as unjustified_absences,
                    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_absences
                FROM attendance_absence_log
                WHERE employee_id = ?
                AND absence_date BETWEEN ? AND ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employee_id, $period_start, $period_end]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_absences' => (int)($result['total_absences'] ?? 0),
            'justified_absences' => (int)($result['justified_absences'] ?? 0),
            'unjustified_absences' => (int)($result['unjustified_absences'] ?? 0),
            'pending_absences' => (int)($result['pending_absences'] ?? 0),
        ];
    }

    /**
     * Verificar cumplimiento legal del resumen
     */
    private function checkLegalCompliance(array $summary, array $calculations): array
    {
        // Preparar datos agregados para verificación legal
        $weeklyHours = $summary['total_hours_worked'];
        $dailyMax = 0;

        foreach ($calculations as $calc) {
            $dailyMax = max($dailyMax, (float)$calc['total_hours']);
        }

        // Verificar jornada diaria
        $dailyCheck = $this->complianceChecker->validateDailyHours($dailyMax, $summary['period_start']);

        // Verificar jornada semanal
        $weeklyCheck = $this->complianceChecker->validateWeeklyHours($weeklyHours, $summary['period_start']);

        // Verificar ausencias graves
        $absencesCheck = $this->complianceChecker->validateAbsences($summary['unjustified_absences']);

        // Consolidar resultados
        $violations = 0;
        $warnings = 0;

        if (!$dailyCheck['is_compliant']) $violations++;
        if (!$weeklyCheck['is_compliant']) $violations++;
        if (!$absencesCheck['is_compliant']) $violations++;

        foreach ([$dailyCheck, $weeklyCheck, $absencesCheck] as $check) {
            $warnings += count($check['warnings'] ?? []);
        }

        // Determinar nivel de riesgo
        $riskLevel = 'NINGUNO';
        if ($violations >= 3) $riskLevel = 'CRÍTICO';
        elseif ($violations >= 2) $riskLevel = 'ALTO';
        elseif ($violations >= 1) $riskLevel = 'MEDIO';
        elseif ($warnings > 0) $riskLevel = 'BAJO';

        return [
            'legal_compliant' => ($violations == 0),
            'legal_violations_count' => $violations,
            'legal_warnings_count' => $warnings,
            'legal_risk_level' => $riskLevel,
            'legal_compliance_notes' => $this->buildComplianceNotes($dailyCheck, $weeklyCheck, $absencesCheck),
        ];
    }

    /**
     * Construir notas de cumplimiento legal
     */
    private function buildComplianceNotes(...$checks): ?string
    {
        $notes = [];

        foreach ($checks as $check) {
            if (!$check['is_compliant']) {
                foreach ($check['violations'] ?? [] as $violation) {
                    $notes[] = $violation['message'] ?? 'Violación detectada';
                }
            }
        }

        return !empty($notes) ? implode('; ', $notes) : null;
    }

    /**
     * Obtener información de alertas generadas
     */
    private function getAlertsInfo(int $employee_id, string $period_start, string $period_end): array
    {
        $sql = "SELECT
                    COUNT(*) as alerts_generated,
                    SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) as critical_alerts,
                    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_alerts
                FROM attendance_alerts
                WHERE employee_id = ?
                AND date BETWEEN ? AND ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employee_id, $period_start, $period_end]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'alerts_generated' => (int)($result['alerts_generated'] ?? 0),
            'critical_alerts' => (int)($result['critical_alerts'] ?? 0),
            'pending_alerts' => (int)($result['pending_alerts'] ?? 0),
        ];
    }

    /**
     * Retornar resumen vacío cuando no hay cálculos
     */
    private function getEmptySummary(int $employee_id, string $period_start, string $period_end): array
    {
        return [
            'employee_id' => $employee_id,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'total_hours_worked' => 0,
            'regular_hours' => 0,
            'overtime_hours_25' => 0,
            'overtime_hours_50' => 0,
            'night_hours' => 0,
            'holiday_hours' => 0,
            'sunday_hours' => 0,
            'total_days_worked' => 0,
            'total_absences' => 0,
            'justified_absences' => 0,
            'unjustified_absences' => 0,
            'pending_absences' => 0,
            'perfect_attendance_days' => 0,
            'total_tardiness_minutes' => 0,
            'tardiness_count' => 0,
            'early_departures_count' => 0,
            'punctuality_score' => 100,
            'total_lunch_time_minutes' => 0,
            'lunch_violations' => 0,
            'total_regular_pay' => 0,
            'total_overtime_pay' => 0,
            'total_night_pay' => 0,
            'total_holiday_pay' => 0,
            'total_deductions' => 0,
            'total_bonuses' => 0,
            'net_attendance_pay' => 0,
            'legal_compliant' => 1,
            'legal_violations_count' => 0,
            'legal_warnings_count' => 0,
            'legal_risk_level' => 'NINGUNO',
            'legal_compliance_notes' => 'Sin datos de asistencia en el período',
            'alerts_generated' => 0,
            'critical_alerts' => 0,
            'pending_alerts' => 0,
        ];
    }

    /**
     * Generar resumen batch para múltiples empleados
     *
     * @param array $employee_ids Array de IDs de empleados
     * @param string $period_start Fecha inicio
     * @param string $period_end Fecha fin
     * @return array Array con summaries por empleado
     */
    public function generateBatchSummaries(array $employee_ids, string $period_start, string $period_end): array
    {
        $summaries = [];

        foreach ($employee_ids as $employee_id) {
            $summary = $this->generateSummary($employee_id, $period_start, $period_end);
            if ($summary !== false) {
                $summaries[$employee_id] = $summary;
            }
        }

        return $summaries;
    }
}
