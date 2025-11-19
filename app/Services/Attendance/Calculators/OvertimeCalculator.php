<?php

namespace App\Services\Attendance\Calculators;

use App\Models\BusinessCalendar;
use DateTime;

/**
 * OvertimeCalculator
 *
 * Calcula horas extras según legislación laboral panameña:
 * - Artículo 31: Jornada ordinaria 8 horas diarias / 48 horas semanales
 * - Artículo 38: Jornada nocturna (6PM-6AM) recargo 50%
 * - Artículo 39: Primeras 3 horas extras +25%, siguientes +50%
 * - Artículo 48: Domingos/feriados recargo 50%
 *
 * @package App\Services\Attendance\Calculators
 * @version 1.0
 */
class OvertimeCalculator
{
    // Constantes legislación panameña
    const REGULAR_DAILY_HOURS = 8;
    const REGULAR_WEEKLY_HOURS = 48;
    const OVERTIME_25_LIMIT = 3; // Primeras 3 horas al 25%
    const NIGHT_SHIFT_START = 18; // 6:00 PM
    const NIGHT_SHIFT_END = 6;    // 6:00 AM

    private $businessCalendar;
    private $schedule;

    public function __construct()
    {
        $this->businessCalendar = new BusinessCalendar();
        $this->schedule = null;
    }

    /**
     * Calcular horas extras según legislación panameña
     *
     * @param float $totalHours Total de horas trabajadas
     * @param float $regularHours Horas regulares esperadas (default 8)
     * @return array Array con breakdown de horas extras
     */
    public function calculateOvertime($totalHours, $regularHours = self::REGULAR_DAILY_HOURS)
    {
        $overtimeHours = max(0, $totalHours - $regularHours);

        // Distribuir según legislación: primeras 3h al 25%, resto al 50%
        $overtime25 = min($overtimeHours, self::OVERTIME_25_LIMIT);
        $overtime50 = max(0, $overtimeHours - self::OVERTIME_25_LIMIT);

        return [
            'total_overtime' => round($overtimeHours, 2),
            'overtime_25' => round($overtime25, 2),
            'overtime_50' => round($overtime50, 2)
        ];
    }

    /**
     * Calcular horas nocturnas trabajadas (6PM - 6AM)
     * Código de Trabajo Art. 38: jornada nocturna con recargo 50%
     *
     * @param string $timeIn Hora de entrada (HH:MM:SS o YYYY-MM-DD HH:MM:SS)
     * @param string $timeOut Hora de salida (HH:MM:SS o YYYY-MM-DD HH:MM:SS)
     * @return float Horas trabajadas en jornada nocturna
     */
    public function calculateNightHours($timeIn, $timeOut)
    {
        if (empty($timeIn) || empty($timeOut)) {
            return 0;
        }

        $start = new DateTime($timeIn);
        $end = new DateTime($timeOut);

        // Si el time_out es menor que time_in, asumimos que cruza al día siguiente
        if ($end < $start) {
            $end->modify('+1 day');
        }

        $nightHours = 0;
        $current = clone $start;

        // Iterar cada minuto es más preciso pero costoso, iteramos por segmentos de 1 hora
        while ($current < $end) {
            $hour = (int)$current->format('H');

            // Verificar si está en rango nocturno (18:00-23:59 o 00:00-05:59)
            if ($hour >= self::NIGHT_SHIFT_START || $hour < self::NIGHT_SHIFT_END) {
                // Calcular tiempo hasta el final de esta hora o hasta time_out
                $nextHour = clone $current;
                $nextHour->modify('+1 hour');
                $nextHour->setTime((int)$nextHour->format('H'), 0, 0);

                if ($nextHour > $end) {
                    $nextHour = $end;
                }

                $interval = $current->diff($nextHour);
                $minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i + ($interval->s / 60);
                $nightHours += $minutes / 60;
            }

            $current->modify('+1 hour');
        }

        return round($nightHours, 2);
    }

    /**
     * Verificar si una fecha es feriado o día no laboral
     *
     * @param string $date Fecha en formato Y-m-d
     * @return array Array con información del día
     */
    public function checkHolidayStatus($date)
    {
        $dayInfo = $this->businessCalendar->getDayInfo($date);

        if ($dayInfo) {
            return [
                'is_holiday' => $dayInfo['day_type'] === 'FERIADO',
                'is_non_working' => $dayInfo['day_type'] !== 'LABORAL',
                'day_type' => $dayInfo['day_type'],
                'description' => $dayInfo['description'] ?? ''
            ];
        }

        // Fallback si no hay info en BD
        $dayOfWeek = date('N', strtotime($date));
        $isWeekend = $dayOfWeek >= 6;

        return [
            'is_holiday' => false,
            'is_non_working' => $isWeekend,
            'day_type' => $isWeekend ? 'NO_LABORAL' : 'LABORAL',
            'description' => ''
        ];
    }

    /**
     * Calcular horas trabajadas en feriado/domingo con recargo 50%
     * Código de Trabajo Art. 48
     *
     * @param float $totalHours Total de horas trabajadas
     * @param string $date Fecha de la asistencia
     * @return float Horas con recargo por feriado
     */
    public function calculateHolidayHours($totalHours, $date)
    {
        $holidayStatus = $this->checkHolidayStatus($date);

        if ($holidayStatus['is_holiday'] || $holidayStatus['is_non_working']) {
            return round($totalHours, 2);
        }

        return 0;
    }

    /**
     * Calcular breakdown completo de horas con todos los recargos aplicables
     *
     * @param string $timeIn Hora de entrada
     * @param string $timeOut Hora de salida
     * @param string $date Fecha de la asistencia
     * @param float $regularHours Horas regulares esperadas
     * @param int $lunchMinutes Minutos de almuerzo
     * @return array Breakdown completo con todas las horas calculadas
     */
    public function calculateCompleteBreakdown($timeIn, $timeOut, $date, $regularHours = self::REGULAR_DAILY_HOURS, $lunchMinutes = 60, $schedule = null)
    {
        if (empty($timeIn) || empty($timeOut)) {
            return $this->getEmptyBreakdown();
        }

        // Calcular horas totales trabajadas (aplicando tolerancias si hay horario)
        $totalHours = $this->calculateTotalHours($timeIn, $timeOut, $lunchMinutes, $schedule);

        // Verificar si es feriado/domingo
        $holidayStatus = $this->checkHolidayStatus($date);

        // Si es feriado, clasificar correctamente: holiday_hours (horario regular) + extras + nocturnas
        if ($holidayStatus['is_holiday'] || $holidayStatus['is_non_working']) {
            // Horas de feriado = las del horario regular (máximo 8h o las horas esperadas)
            $actualHolidayHours = min($totalHours, $regularHours);

            // Horas nocturnas (se calculan independientemente)
            $nightHours = $this->calculateNightHours($timeIn, $timeOut);

            // Horas extras = las que exceden el horario regular
            $overtimeTotal = max(0, $totalHours - $regularHours);

            // En feriados, las extras se clasifican igual (primeras 3h al 25%, resto al 50%)
            // pero ya llevan recargo base del 50% por ser feriado
            $overtime25 = min($overtimeTotal, self::OVERTIME_25_LIMIT);
            $overtime50 = max(0, $overtimeTotal - self::OVERTIME_25_LIMIT);

            return [
                'total_hours' => $totalHours,
                'regular_hours' => 0, // En feriado no hay horas regulares, son holiday_hours
                'overtime_hours' => round($overtimeTotal, 2),
                'overtime_25_hours' => round($overtime25, 2),
                'overtime_50_hours' => round($overtime50, 2),
                'night_hours' => $nightHours,
                'holiday_hours' => round($actualHolidayHours, 2), // Solo las del horario
                'is_holiday' => true,
                'day_type' => $holidayStatus['day_type']
            ];
        }

        // Día normal: calcular horas regulares y extras
        $actualRegularHours = min($totalHours, $regularHours);
        $overtime = $this->calculateOvertime($totalHours, $regularHours);
        $nightHours = $this->calculateNightHours($timeIn, $timeOut);

        return [
            'total_hours' => $totalHours,
            'regular_hours' => $actualRegularHours,
            'overtime_hours' => $overtime['total_overtime'],
            'overtime_25_hours' => $overtime['overtime_25'],
            'overtime_50_hours' => $overtime['overtime_50'],
            'night_hours' => $nightHours,
            'holiday_hours' => 0,
            'is_holiday' => false,
            'day_type' => $holidayStatus['day_type']
        ];
    }

    /**
     * Calcular total de horas trabajadas descontando almuerzo
     *
     * @param string $timeIn
     * @param string $timeOut
     * @param int $lunchMinutes
     * @return float
     */
    private function calculateTotalHours($timeIn, $timeOut, $lunchMinutes = 60, $schedule = null)
    {
        $start = new DateTime($timeIn);
        $end = new DateTime($timeOut);

        // Si time_out es menor, asumimos cruce de medianoche
        if ($end < $start) {
            $end->modify('+1 day');
        }

        // Aplicar tolerancias de horario si están disponibles para ajustar entrada/salida efectivas
        $effectiveStart = clone $start;
        $effectiveEnd = clone $end;

        if (is_array($schedule) && !empty($schedule['time_in']) && !empty($schedule['time_out'])) {
            $schedIn = new DateTime($schedule['time_in']);
            $schedOut = new DateTime($schedule['time_out']);

            if ($schedOut < $schedIn) {
                $schedOut->modify('+1 day');
            }

            $inBefore = (int)($schedule['time_in_tolerance_before'] ?? 0);
            $inAfter  = (int)($schedule['time_in_tolerance_after'] ?? 0);
            $outBefore= (int)($schedule['time_out_tolerance_before'] ?? 0);
            $outAfter = (int)($schedule['time_out_tolerance_after'] ?? 0);

            $inWinStart = (clone $schedIn)->modify("-{$inBefore} minutes");
            $inWinEnd   = (clone $schedIn)->modify("+{$inAfter} minutes");
            $outWinStart= (clone $schedOut)->modify("-{$outBefore} minutes");
            $outWinEnd  = (clone $schedOut)->modify("+{$outAfter} minutes");

            // Ajuste entrada efectiva
            if ($effectiveStart <= $schedIn) {
                $effectiveStart = ($effectiveStart >= $inWinStart) ? clone $schedIn : $effectiveStart; 
            } else {
                $effectiveStart = ($effectiveStart <= $inWinEnd) ? clone $schedIn : $effectiveStart;
            }

            // Ajuste salida efectiva
            if ($effectiveEnd >= $schedOut) {
                $effectiveEnd = ($effectiveEnd <= $outWinEnd) ? clone $schedOut : $effectiveEnd;
            } else {
                $effectiveEnd = ($effectiveEnd >= $outWinStart) ? clone $schedOut : $effectiveEnd;
            }

        }

        $interval = $effectiveStart->diff($effectiveEnd);
        $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

        // Descontar almuerzo si trabajó más de 4 horas
        if ($totalMinutes > 240) { // 4 horas = 240 minutos
            $totalMinutes -= $lunchMinutes;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Retornar breakdown vacío para casos sin marcaciones
     *
     * @return array
     */
    private function getEmptyBreakdown()
    {
        return [
            'total_hours' => 0,
            'regular_hours' => 0,
            'overtime_hours' => 0,
            'overtime_25_hours' => 0,
            'overtime_50_hours' => 0,
            'night_hours' => 0,
            'holiday_hours' => 0,
            'is_holiday' => false,
            'day_type' => 'UNKNOWN'
        ];
    }

    /**
     * Verificar si supera límite semanal de horas (48h)
     * Útil para validaciones y alertas
     *
     * @param float $weeklyHours Total de horas trabajadas en la semana
     * @return array
     */
    public function checkWeeklyLimit($weeklyHours)
    {
        $exceeds = $weeklyHours > self::REGULAR_WEEKLY_HOURS;
        $excess = max(0, $weeklyHours - self::REGULAR_WEEKLY_HOURS);

        return [
            'exceeds_limit' => $exceeds,
            'limit' => self::REGULAR_WEEKLY_HOURS,
            'total_hours' => $weeklyHours,
            'excess_hours' => round($excess, 2)
        ];
    }
}
