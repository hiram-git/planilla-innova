<?php

namespace App\Services\Attendance\Calculators;

use App\Models\BusinessCalendar;
use DateTime;

/**
 * LegalComplianceChecker
 *
 * Valida el cumplimiento de la legislación laboral panameña en las asistencias.
 * Implementa:
 * - Art. 31: Jornada ordinaria máxima 8h/día, 48h/semana
 * - Art. 35: Tiempo de comida mínimo 30 minutos
 * - Art. 38: Jornada nocturna 6PM-6AM con recargo 50%
 * - Art. 39: Límites de horas extras
 * - Art. 48: Trabajo en días de descanso
 * - Art. 213: Faltas graves (3+ ausencias injustificadas)
 *
 * @package App\Services\Attendance\Calculators
 * @version 1.0
 */
class LegalComplianceChecker
{
    // Constantes legislación panameña
    const MAX_DAILY_HOURS = 8;           // Art. 31
    const MAX_WEEKLY_HOURS = 48;         // Art. 31
    const MAX_DAILY_WITH_OVERTIME = 11;  // 8h regulares + 3h extras recomendadas
    const MIN_LUNCH_MINUTES = 30;        // Art. 35
    const MAX_CONSECUTIVE_DAYS = 6;      // Art. 48 - descanso semanal obligatorio
    const SERIOUS_ABSENCES_THRESHOLD = 3; // Art. 213 - faltas graves

    const NIGHT_SHIFT_START = 18;        // 6:00 PM - Art. 38
    const NIGHT_SHIFT_END = 6;           // 6:00 AM - Art. 38

    private BusinessCalendar $businessCalendar;
    private array $violations;

    public function __construct()
    {
        $this->businessCalendar = new BusinessCalendar();
        $this->violations = [];
    }

    /**
     * Validar cumplimiento de jornada diaria
     *
     * @param float $totalHours Horas trabajadas en el día
     * @param string $date Fecha de la asistencia
     * @return array Resultado de validación
     */
    public function validateDailyHours(float $totalHours, string $date): array
    {
        $isCompliant = true;
        $warnings = [];
        $violations = [];

        // Verificar si excede jornada ordinaria
        if ($totalHours > self::MAX_DAILY_HOURS) {
            $excess = $totalHours - self::MAX_DAILY_HOURS;
            $warnings[] = sprintf(
                "Excede jornada ordinaria en %.2f horas (Art. 31). Horas extras: %.2f",
                $excess,
                $excess
            );
        }

        // Verificar si excede jornada máxima recomendada con extras
        if ($totalHours > self::MAX_DAILY_WITH_OVERTIME) {
            $excess = $totalHours - self::MAX_DAILY_WITH_OVERTIME;
            $violations[] = sprintf(
                "VIOLACIÓN: Excede jornada máxima recomendada en %.2f horas. Total: %.2f horas",
                $excess,
                $totalHours
            );
            $isCompliant = false;
        }

        return [
            'is_compliant' => $isCompliant,
            'total_hours' => $totalHours,
            'max_allowed' => self::MAX_DAILY_HOURS,
            'max_recommended' => self::MAX_DAILY_WITH_OVERTIME,
            'warnings' => $warnings,
            'violations' => $violations,
            'article' => 'Art. 31 - Código de Trabajo',
            'date' => $date
        ];
    }

    /**
     * Validar cumplimiento de jornada semanal
     *
     * @param float $weeklyHours Total horas trabajadas en la semana
     * @param string $weekStart Fecha inicio de semana
     * @param string $weekEnd Fecha fin de semana
     * @return array Resultado de validación
     */
    public function validateWeeklyHours(
        float $weeklyHours,
        string $weekStart,
        string $weekEnd
    ): array {
        $isCompliant = true;
        $warnings = [];
        $violations = [];

        // Verificar si excede jornada semanal ordinaria
        if ($weeklyHours > self::MAX_WEEKLY_HOURS) {
            $excess = $weeklyHours - self::MAX_WEEKLY_HOURS;
            $violations[] = sprintf(
                "VIOLACIÓN: Excede jornada semanal ordinaria en %.2f horas. Total: %.2f horas",
                $excess,
                $weeklyHours
            );
            $isCompliant = false;
        }

        // Advertencia si se acerca al límite (90%)
        $threshold = self::MAX_WEEKLY_HOURS * 0.9;
        if ($weeklyHours > $threshold && $weeklyHours <= self::MAX_WEEKLY_HOURS) {
            $warnings[] = sprintf(
                "Cerca del límite semanal (%.2f/%.2f horas - %.1f%%)",
                $weeklyHours,
                self::MAX_WEEKLY_HOURS,
                ($weeklyHours / self::MAX_WEEKLY_HOURS) * 100
            );
        }

        return [
            'is_compliant' => $isCompliant,
            'weekly_hours' => $weeklyHours,
            'max_allowed' => self::MAX_WEEKLY_HOURS,
            'excess_hours' => max(0, $weeklyHours - self::MAX_WEEKLY_HOURS),
            'warnings' => $warnings,
            'violations' => $violations,
            'article' => 'Art. 31 - Código de Trabajo',
            'period' => "$weekStart a $weekEnd"
        ];
    }

    /**
     * Validar tiempo de almuerzo mínimo
     *
     * @param int $lunchMinutes Minutos de almuerzo
     * @param float $totalHours Total de horas trabajadas
     * @return array Resultado de validación
     */
    public function validateLunchTime(int $lunchMinutes, float $totalHours): array
    {
        $isCompliant = true;
        $warnings = [];
        $violations = [];

        // Solo aplicable si trabajó más de 4 horas
        if ($totalHours > 4) {
            if ($lunchMinutes < self::MIN_LUNCH_MINUTES) {
                $violations[] = sprintf(
                    "VIOLACIÓN: Tiempo de comida insuficiente (%d minutos). Mínimo requerido: %d minutos",
                    $lunchMinutes,
                    self::MIN_LUNCH_MINUTES
                );
                $isCompliant = false;
            }
        }

        return [
            'is_compliant' => $isCompliant,
            'lunch_minutes' => $lunchMinutes,
            'min_required' => self::MIN_LUNCH_MINUTES,
            'warnings' => $warnings,
            'violations' => $violations,
            'article' => 'Art. 35 - Código de Trabajo'
        ];
    }

    /**
     * Validar jornada nocturna
     *
     * @param string $timeIn Hora de entrada
     * @param string $timeOut Hora de salida
     * @param float $nightHours Horas nocturnas calculadas
     * @return array Resultado de validación
     */
    public function validateNightShift(
        string $timeIn,
        string $timeOut,
        float $nightHours
    ): array {
        $isCompliant = true;
        $warnings = [];
        $violations = [];
        $requiresSurcharge = false;

        if ($nightHours > 0) {
            $requiresSurcharge = true;
            $warnings[] = sprintf(
                "Jornada nocturna: %.2f horas (6PM-6AM). Requiere recargo 50%% (Art. 38)",
                $nightHours
            );

            // Jornada nocturna máxima: 7 horas según Art. 38
            if ($nightHours > 7) {
                $violations[] = sprintf(
                    "VIOLACIÓN: Excede jornada nocturna máxima (7h). Total: %.2f horas",
                    $nightHours
                );
                $isCompliant = false;
            }
        }

        return [
            'is_compliant' => $isCompliant,
            'night_hours' => $nightHours,
            'max_night_hours' => 7,
            'requires_surcharge' => $requiresSurcharge,
            'surcharge_percent' => 50,
            'warnings' => $warnings,
            'violations' => $violations,
            'article' => 'Art. 38 - Código de Trabajo'
        ];
    }

    /**
     * Validar trabajo en día de descanso (domingo/feriado)
     *
     * @param string $date Fecha de la asistencia
     * @param float $hoursWorked Horas trabajadas
     * @return array Resultado de validación
     */
    public function validateRestDayWork(string $date, float $hoursWorked): array
    {
        $isCompliant = true;
        $warnings = [];
        $violations = [];
        $requiresSurcharge = false;

        $dayInfo = $this->businessCalendar->getDayInfo($date);
        $dayOfWeek = (int)date('N', strtotime($date)); // 1=Lunes, 7=Domingo

        // Verificar si es domingo
        $isSunday = ($dayOfWeek === 7);

        // Verificar si es feriado
        $isHoliday = false;
        if ($dayInfo) {
            $isHoliday = in_array($dayInfo['day_type'], ['FERIADO', 'DUELO_NACIONAL']);
        }

        if (($isSunday || $isHoliday) && $hoursWorked > 0) {
            $requiresSurcharge = true;
            $dayTypeStr = $isSunday ? 'domingo' : 'feriado';
            $warnings[] = sprintf(
                "Trabajo en %s: %.2f horas. Requiere recargo 50%% (Art. 48)",
                $dayTypeStr,
                $hoursWorked
            );

            // El empleado tiene derecho a descanso compensatorio
            $warnings[] = "Empleado tiene derecho a día de descanso compensatorio (Art. 48)";
        }

        return [
            'is_compliant' => $isCompliant,
            'is_rest_day' => ($isSunday || $isHoliday),
            'is_sunday' => $isSunday,
            'is_holiday' => $isHoliday,
            'hours_worked' => $hoursWorked,
            'requires_surcharge' => $requiresSurcharge,
            'surcharge_percent' => 50,
            'requires_compensatory_day' => ($isSunday || $isHoliday) && $hoursWorked > 0,
            'warnings' => $warnings,
            'violations' => $violations,
            'article' => 'Art. 48 - Código de Trabajo',
            'date' => $date
        ];
    }

    /**
     * Validar días consecutivos trabajados sin descanso
     *
     * @param array $workDates Array de fechas trabajadas ordenadas
     * @return array Resultado de validación
     */
    public function validateConsecutiveDays(array $workDates): array
    {
        $isCompliant = true;
        $warnings = [];
        $violations = [];
        $maxConsecutive = 0;
        $currentConsecutive = 1;

        // Ordenar fechas
        sort($workDates);

        for ($i = 1; $i < count($workDates); $i++) {
            $prevDate = new DateTime($workDates[$i - 1]);
            $currDate = new DateTime($workDates[$i]);
            $diff = $prevDate->diff($currDate)->days;

            if ($diff === 1) {
                $currentConsecutive++;
                $maxConsecutive = max($maxConsecutive, $currentConsecutive);
            } else {
                $currentConsecutive = 1;
            }

            // Verificar si excede el máximo permitido
            if ($currentConsecutive > self::MAX_CONSECUTIVE_DAYS) {
                $violations[] = sprintf(
                    "VIOLACIÓN: %d días consecutivos sin descanso (Máximo: %d). " .
                    "Desde %s hasta %s (Art. 48)",
                    $currentConsecutive,
                    self::MAX_CONSECUTIVE_DAYS,
                    $workDates[$i - $currentConsecutive + 1],
                    $workDates[$i]
                );
                $isCompliant = false;
            }
        }

        return [
            'is_compliant' => $isCompliant,
            'max_consecutive_days' => $maxConsecutive,
            'allowed_consecutive_days' => self::MAX_CONSECUTIVE_DAYS,
            'warnings' => $warnings,
            'violations' => $violations,
            'article' => 'Art. 48 - Código de Trabajo (Descanso Semanal)'
        ];
    }

    /**
     * Validar ausencias injustificadas (faltas graves)
     *
     * @param int $unjustifiedAbsences Número de ausencias injustificadas en el mes
     * @param string $month Mes en formato Y-m
     * @return array Resultado de validación
     */
    public function validateAbsences(int $unjustifiedAbsences, string $month): array
    {
        $isCompliant = true;
        $warnings = [];
        $violations = [];
        $isSeriousOffense = false;

        if ($unjustifiedAbsences >= self::SERIOUS_ABSENCES_THRESHOLD) {
            $isSeriousOffense = true;
            $violations[] = sprintf(
                "FALTA GRAVE: %d ausencias injustificadas en el mes %s " .
                "(Umbral: %d ausencias). Constituye causal de despido sin responsabilidad patronal (Art. 213)",
                $unjustifiedAbsences,
                $month,
                self::SERIOUS_ABSENCES_THRESHOLD
            );
            $isCompliant = false;
        } elseif ($unjustifiedAbsences === 2) {
            $warnings[] = sprintf(
                "ADVERTENCIA: 2 ausencias injustificadas en %s. " .
                "Una ausencia más constituye falta grave (Art. 213)",
                $month
            );
        } elseif ($unjustifiedAbsences === 1) {
            $warnings[] = sprintf(
                "Atención: 1 ausencia injustificada en %s",
                $month
            );
        }

        return [
            'is_compliant' => $isCompliant,
            'unjustified_absences' => $unjustifiedAbsences,
            'threshold' => self::SERIOUS_ABSENCES_THRESHOLD,
            'is_serious_offense' => $isSeriousOffense,
            'can_be_dismissed' => $isSeriousOffense,
            'warnings' => $warnings,
            'violations' => $violations,
            'article' => 'Art. 213 - Código de Trabajo (Faltas Graves)',
            'month' => $month
        ];
    }

    /**
     * Realizar validación completa de cumplimiento legal
     *
     * @param array $attendanceData Datos de asistencia del empleado
     * @return array Reporte completo de cumplimiento
     */
    public function performFullCompliance(array $attendanceData): array
    {
        $results = [
            'overall_compliant' => true,
            'total_violations' => 0,
            'total_warnings' => 0,
            'checks' => [],
            'summary' => []
        ];

        // 1. Validar jornada diaria
        if (isset($attendanceData['daily_hours'])) {
            $dailyCheck = $this->validateDailyHours(
                $attendanceData['daily_hours'],
                $attendanceData['date'] ?? date('Y-m-d')
            );
            $results['checks']['daily_hours'] = $dailyCheck;

            if (!$dailyCheck['is_compliant']) {
                $results['overall_compliant'] = false;
            }
            $results['total_violations'] += count($dailyCheck['violations']);
            $results['total_warnings'] += count($dailyCheck['warnings']);
        }

        // 2. Validar jornada semanal
        if (isset($attendanceData['weekly_hours'])) {
            $weeklyCheck = $this->validateWeeklyHours(
                $attendanceData['weekly_hours'],
                $attendanceData['week_start'] ?? date('Y-m-d', strtotime('monday this week')),
                $attendanceData['week_end'] ?? date('Y-m-d', strtotime('sunday this week'))
            );
            $results['checks']['weekly_hours'] = $weeklyCheck;

            if (!$weeklyCheck['is_compliant']) {
                $results['overall_compliant'] = false;
            }
            $results['total_violations'] += count($weeklyCheck['violations']);
            $results['total_warnings'] += count($weeklyCheck['warnings']);
        }

        // 3. Validar tiempo de almuerzo
        if (isset($attendanceData['lunch_minutes']) && isset($attendanceData['total_hours'])) {
            $lunchCheck = $this->validateLunchTime(
                $attendanceData['lunch_minutes'],
                $attendanceData['total_hours']
            );
            $results['checks']['lunch_time'] = $lunchCheck;

            if (!$lunchCheck['is_compliant']) {
                $results['overall_compliant'] = false;
            }
            $results['total_violations'] += count($lunchCheck['violations']);
        }

        // 4. Validar jornada nocturna
        if (isset($attendanceData['night_hours'])) {
            $nightCheck = $this->validateNightShift(
                $attendanceData['time_in'] ?? '',
                $attendanceData['time_out'] ?? '',
                $attendanceData['night_hours']
            );
            $results['checks']['night_shift'] = $nightCheck;

            if (!$nightCheck['is_compliant']) {
                $results['overall_compliant'] = false;
            }
            $results['total_violations'] += count($nightCheck['violations']);
            $results['total_warnings'] += count($nightCheck['warnings']);
        }

        // 5. Validar trabajo en día de descanso
        if (isset($attendanceData['date']) && isset($attendanceData['hours_worked'])) {
            $restDayCheck = $this->validateRestDayWork(
                $attendanceData['date'],
                $attendanceData['hours_worked']
            );
            $results['checks']['rest_day'] = $restDayCheck;

            $results['total_warnings'] += count($restDayCheck['warnings']);
        }

        // 6. Validar ausencias
        if (isset($attendanceData['unjustified_absences'])) {
            $absenceCheck = $this->validateAbsences(
                $attendanceData['unjustified_absences'],
                $attendanceData['month'] ?? date('Y-m')
            );
            $results['checks']['absences'] = $absenceCheck;

            if (!$absenceCheck['is_compliant']) {
                $results['overall_compliant'] = false;
            }
            $results['total_violations'] += count($absenceCheck['violations']);
            $results['total_warnings'] += count($absenceCheck['warnings']);
        }

        // Generar resumen
        $results['summary'] = $this->generateComplianceSummary($results);

        return $results;
    }

    /**
     * Generar resumen del reporte de cumplimiento
     *
     * @param array $results Resultados de las validaciones
     * @return array Resumen
     */
    private function generateComplianceSummary(array $results): array
    {
        $summary = [
            'status' => $results['overall_compliant'] ? 'CUMPLE' : 'NO CUMPLE',
            'risk_level' => $this->calculateRiskLevel($results),
            'total_checks' => count($results['checks']),
            'passed_checks' => 0,
            'failed_checks' => 0,
            'requires_action' => !$results['overall_compliant'],
            'recommendations' => []
        ];

        foreach ($results['checks'] as $checkName => $check) {
            if ($check['is_compliant']) {
                $summary['passed_checks']++;
            } else {
                $summary['failed_checks']++;
            }
        }

        // Generar recomendaciones
        if ($results['total_violations'] > 0) {
            $summary['recommendations'][] = "Revisar y corregir las violaciones identificadas inmediatamente";
        }

        if ($results['total_warnings'] > 0) {
            $summary['recommendations'][] = "Tomar medidas preventivas para evitar futuras violaciones";
        }

        return $summary;
    }

    /**
     * Calcular nivel de riesgo
     *
     * @param array $results Resultados de validaciones
     * @return string Nivel de riesgo (BAJO, MEDIO, ALTO, CRÍTICO)
     */
    private function calculateRiskLevel(array $results): string
    {
        $violations = $results['total_violations'];
        $warnings = $results['total_warnings'];

        if ($violations >= 3) {
            return 'CRÍTICO';
        } elseif ($violations >= 2) {
            return 'ALTO';
        } elseif ($violations >= 1 || $warnings >= 3) {
            return 'MEDIO';
        } elseif ($warnings >= 1) {
            return 'BAJO';
        }

        return 'NINGUNO';
    }

    /**
     * Obtener todas las violaciones acumuladas
     *
     * @return array Lista de violaciones
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * Limpiar violaciones acumuladas
     */
    public function clearViolations(): void
    {
        $this->violations = [];
    }
}
