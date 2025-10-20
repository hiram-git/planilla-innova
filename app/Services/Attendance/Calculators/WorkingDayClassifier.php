<?php

namespace App\Services\Attendance\Calculators;

use App\Models\BusinessCalendar;
use DateTime;
use DatePeriod;
use DateInterval;

/**
 * WorkingDayClassifier
 *
 * Clasifica días según legislación panameña y políticas empresariales.
 * Tipos de días:
 * - LABORAL: Día de trabajo ordinario
 * - FERIADO: Feriado nacional (recargo 50%)
 * - DUELO_NACIONAL: Duelo nacional (no laboral)
 * - FIN_SEMANA: Sábado/Domingo (recargo 50% si se trabaja)
 * - ESPECIAL: Días con horarios especiales
 * - MEDIO_DIA: Días de medio día (ej: vísperas)
 *
 * @package App\Services\Attendance\Calculators
 * @version 1.0
 */
class WorkingDayClassifier
{
    private BusinessCalendar $businessCalendar;

    public function __construct()
    {
        $this->businessCalendar = new BusinessCalendar();
    }

    /**
     * Clasificar un día específico
     *
     * @param string $date Fecha en formato Y-m-d
     * @return array Clasificación completa del día
     */
    public function classifyDay(string $date): array
    {
        // Obtener información del calendario empresarial
        $calendarInfo = $this->businessCalendar->getDayInfo($date);

        // Obtener día de la semana (1=Lunes, 7=Domingo)
        $dateObj = new DateTime($date);
        $dayOfWeek = (int)$dateObj->format('N');
        $dayName = $this->getDayName($dayOfWeek);

        // Clasificación base
        $classification = [
            'date' => $date,
            'day_of_week' => $dayOfWeek,
            'day_name' => $dayName,
            'is_weekend' => $dayOfWeek >= 6,
            'is_monday' => $dayOfWeek === 1,
            'is_friday' => $dayOfWeek === 5,
            'is_working_day' => false,
            'is_holiday' => false,
            'is_national_holiday' => false,
            'is_duelo_nacional' => false,
            'is_special_day' => false,
            'is_half_day' => false,
            'day_type' => 'UNKNOWN',
            'day_state' => 'NORMAL',
            'description' => '',
            'requires_surcharge' => false,
            'surcharge_percent' => 0,
            'work_hours_expected' => 0
        ];

        // Si existe información en el calendario empresarial
        if ($calendarInfo) {
            $classification['day_type'] = $calendarInfo['day_type'];
            $classification['day_state'] = $calendarInfo['day_state'] ?? 'NORMAL';
            $classification['description'] = $calendarInfo['description'] ?? '';

            // Clasificaciones según tipo
            switch ($calendarInfo['day_type']) {
                case 'LABORAL':
                    $classification['is_working_day'] = true;
                    $classification['work_hours_expected'] = 8;
                    break;

                case 'FERIADO':
                    $classification['is_holiday'] = true;
                    $classification['is_national_holiday'] = true;
                    $classification['requires_surcharge'] = true;
                    $classification['surcharge_percent'] = 50;
                    break;

                case 'DUELO_NACIONAL':
                    $classification['is_duelo_nacional'] = true;
                    $classification['is_holiday'] = true;
                    break;

                case 'ESPECIAL':
                    $classification['is_special_day'] = true;
                    $classification['is_working_day'] = true;

                    // Si el estado es MEDIO_DIA
                    if ($classification['day_state'] === 'MEDIO_DIA') {
                        $classification['is_half_day'] = true;
                        $classification['work_hours_expected'] = 4;
                    } else {
                        $classification['work_hours_expected'] = 8;
                    }
                    break;

                case 'NO_LABORAL':
                default:
                    $classification['is_working_day'] = false;
                    break;
            }

            // Si es fin de semana y se trabaja, requiere recargo
            if ($classification['is_weekend'] && !$classification['is_holiday']) {
                $classification['requires_surcharge'] = true;
                $classification['surcharge_percent'] = 50;
            }
        } else {
            // Fallback: clasificación básica sin calendario
            if ($dayOfWeek >= 6) {
                $classification['day_type'] = 'FIN_SEMANA';
                $classification['is_working_day'] = false;
                $classification['requires_surcharge'] = true;
                $classification['surcharge_percent'] = 50;
            } else {
                $classification['day_type'] = 'LABORAL';
                $classification['is_working_day'] = true;
                $classification['work_hours_expected'] = 8;
            }
        }

        return $classification;
    }

    /**
     * Clasificar múltiples días en un rango
     *
     * @param string $startDate Fecha inicial
     * @param string $endDate Fecha final
     * @return array Array de clasificaciones
     */
    public function classifyPeriod(string $startDate, string $endDate): array
    {
        $classifications = [];

        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $classifications[$dateStr] = $this->classifyDay($dateStr);
        }

        return $classifications;
    }

    /**
     * Obtener estadísticas de un período
     *
     * @param string $startDate Fecha inicial
     * @param string $endDate Fecha final
     * @return array Estadísticas del período
     */
    public function getPeriodStatistics(string $startDate, string $endDate): array
    {
        $classifications = $this->classifyPeriod($startDate, $endDate);

        $stats = [
            'total_days' => count($classifications),
            'working_days' => 0,
            'weekends' => 0,
            'holidays' => 0,
            'national_holidays' => 0,
            'special_days' => 0,
            'half_days' => 0,
            'days_with_surcharge' => 0,
            'expected_work_hours' => 0,
            'breakdown_by_type' => []
        ];

        foreach ($classifications as $classification) {
            if ($classification['is_working_day']) {
                $stats['working_days']++;
                $stats['expected_work_hours'] += $classification['work_hours_expected'];
            }

            if ($classification['is_weekend']) {
                $stats['weekends']++;
            }

            if ($classification['is_holiday']) {
                $stats['holidays']++;
            }

            if ($classification['is_national_holiday']) {
                $stats['national_holidays']++;
            }

            if ($classification['is_special_day']) {
                $stats['special_days']++;
            }

            if ($classification['is_half_day']) {
                $stats['half_days']++;
            }

            if ($classification['requires_surcharge']) {
                $stats['days_with_surcharge']++;
            }

            // Contar por tipo
            $type = $classification['day_type'];
            if (!isset($stats['breakdown_by_type'][$type])) {
                $stats['breakdown_by_type'][$type] = 0;
            }
            $stats['breakdown_by_type'][$type]++;
        }

        return $stats;
    }

    /**
     * Obtener días laborables de un período
     *
     * @param string $startDate Fecha inicial
     * @param string $endDate Fecha final
     * @return array Array de fechas laborables
     */
    public function getWorkingDays(string $startDate, string $endDate): array
    {
        $classifications = $this->classifyPeriod($startDate, $endDate);
        $workingDays = [];

        foreach ($classifications as $date => $classification) {
            if ($classification['is_working_day']) {
                $workingDays[] = $date;
            }
        }

        return $workingDays;
    }

    /**
     * Obtener días no laborables de un período
     *
     * @param string $startDate Fecha inicial
     * @param string $endDate Fecha final
     * @return array Array de fechas no laborables
     */
    public function getNonWorkingDays(string $startDate, string $endDate): array
    {
        $classifications = $this->classifyPeriod($startDate, $endDate);
        $nonWorkingDays = [];

        foreach ($classifications as $date => $classification) {
            if (!$classification['is_working_day']) {
                $nonWorkingDays[] = [
                    'date' => $date,
                    'day_type' => $classification['day_type'],
                    'description' => $classification['description']
                ];
            }
        }

        return $nonWorkingDays;
    }

    /**
     * Obtener feriados de un período
     *
     * @param string $startDate Fecha inicial
     * @param string $endDate Fecha final
     * @return array Array de feriados
     */
    public function getHolidays(string $startDate, string $endDate): array
    {
        $classifications = $this->classifyPeriod($startDate, $endDate);
        $holidays = [];

        foreach ($classifications as $date => $classification) {
            if ($classification['is_holiday']) {
                $holidays[] = [
                    'date' => $date,
                    'day_name' => $classification['day_name'],
                    'description' => $classification['description'],
                    'is_national_holiday' => $classification['is_national_holiday']
                ];
            }
        }

        return $holidays;
    }

    /**
     * Verificar si una fecha es día laboral
     *
     * @param string $date Fecha a verificar
     * @return bool True si es día laboral
     */
    public function isWorkingDay(string $date): bool
    {
        $classification = $this->classifyDay($date);
        return $classification['is_working_day'];
    }

    /**
     * Verificar si una fecha es feriado
     *
     * @param string $date Fecha a verificar
     * @return bool True si es feriado
     */
    public function isHoliday(string $date): bool
    {
        $classification = $this->classifyDay($date);
        return $classification['is_holiday'];
    }

    /**
     * Verificar si una fecha es fin de semana
     *
     * @param string $date Fecha a verificar
     * @return bool True si es fin de semana
     */
    public function isWeekend(string $date): bool
    {
        $classification = $this->classifyDay($date);
        return $classification['is_weekend'];
    }

    /**
     * Obtener el siguiente día laboral después de una fecha
     *
     * @param string $date Fecha de referencia
     * @param int $maxDays Máximo de días a buscar (default 14)
     * @return string|null Fecha del siguiente día laboral o null si no se encuentra
     */
    public function getNextWorkingDay(string $date, int $maxDays = 14): ?string
    {
        $currentDate = new DateTime($date);
        $currentDate->modify('+1 day');

        for ($i = 0; $i < $maxDays; $i++) {
            $dateStr = $currentDate->format('Y-m-d');

            if ($this->isWorkingDay($dateStr)) {
                return $dateStr;
            }

            $currentDate->modify('+1 day');
        }

        return null;
    }

    /**
     * Obtener el día laboral anterior a una fecha
     *
     * @param string $date Fecha de referencia
     * @param int $maxDays Máximo de días a buscar (default 14)
     * @return string|null Fecha del día laboral anterior o null si no se encuentra
     */
    public function getPreviousWorkingDay(string $date, int $maxDays = 14): ?string
    {
        $currentDate = new DateTime($date);
        $currentDate->modify('-1 day');

        for ($i = 0; $i < $maxDays; $i++) {
            $dateStr = $currentDate->format('Y-m-d');

            if ($this->isWorkingDay($dateStr)) {
                return $dateStr;
            }

            $currentDate->modify('-1 day');
        }

        return null;
    }

    /**
     * Calcular días laborables entre dos fechas (inclusivo)
     *
     * @param string $startDate Fecha inicial
     * @param string $endDate Fecha final
     * @return int Número de días laborables
     */
    public function countWorkingDays(string $startDate, string $endDate): int
    {
        return count($this->getWorkingDays($startDate, $endDate));
    }

    /**
     * Obtener nombre del día en español
     *
     * @param int $dayOfWeek Número del día (1-7)
     * @return string Nombre del día
     */
    private function getDayName(int $dayOfWeek): string
    {
        $days = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo'
        ];

        return $days[$dayOfWeek] ?? 'Desconocido';
    }

    /**
     * Generar reporte legible de clasificación de días
     *
     * @param string $startDate Fecha inicial
     * @param string $endDate Fecha final
     * @return string Reporte en texto
     */
    public function generateClassificationReport(string $startDate, string $endDate): string
    {
        $stats = $this->getPeriodStatistics($startDate, $endDate);
        $holidays = $this->getHolidays($startDate, $endDate);

        $lines = [];
        $lines[] = "REPORTE DE CLASIFICACIÓN DE DÍAS";
        $lines[] = str_repeat('=', 70);
        $lines[] = "Período: $startDate a $endDate";
        $lines[] = "";
        $lines[] = "ESTADÍSTICAS GENERALES:";
        $lines[] = str_repeat('-', 70);
        $lines[] = sprintf("Total de días: %d", $stats['total_days']);
        $lines[] = sprintf("Días laborables: %d (%.1f%%)",
            $stats['working_days'],
            ($stats['working_days'] / $stats['total_days']) * 100
        );
        $lines[] = sprintf("Fines de semana: %d", $stats['weekends']);
        $lines[] = sprintf("Feriados: %d", $stats['holidays']);
        $lines[] = sprintf("Días especiales: %d", $stats['special_days']);
        $lines[] = sprintf("Horas de trabajo esperadas: %.1f", $stats['expected_work_hours']);
        $lines[] = "";

        if (!empty($holidays)) {
            $lines[] = "FERIADOS EN EL PERÍODO:";
            $lines[] = str_repeat('-', 70);
            foreach ($holidays as $holiday) {
                $lines[] = sprintf("  %s (%s): %s",
                    $holiday['date'],
                    $holiday['day_name'],
                    $holiday['description']
                );
            }
            $lines[] = "";
        }

        $lines[] = "DESGLOSE POR TIPO:";
        $lines[] = str_repeat('-', 70);
        foreach ($stats['breakdown_by_type'] as $type => $count) {
            $lines[] = sprintf("  %s: %d día(s)", $type, $count);
        }

        $lines[] = str_repeat('=', 70);

        return implode("\n", $lines);
    }
}
