<?php

namespace App\Services\Attendance\Calculators;

/**
 * OvertimeRateCalculator
 *
 * Calcula las tarifas y montos monetarios de horas extras según legislación panameña.
 * Considera:
 * - Art. 39: Primeras 3 horas extras +25%, siguientes +50%
 * - Art. 38: Jornada nocturna +50%
 * - Art. 48: Domingos/feriados +50%
 * - Combinaciones de recargos (nocturno + feriado, etc.)
 *
 * @package App\Services\Attendance\Calculators
 * @version 1.0
 */
class OvertimeRateCalculator
{
    // Porcentajes de recargo según legislación
    const OVERTIME_25_RATE = 1.25;      // +25% Art. 39
    const OVERTIME_50_RATE = 1.50;      // +50% Art. 39
    const NIGHT_RATE = 1.50;            // +50% Art. 38
    const HOLIDAY_RATE = 1.50;          // +50% Art. 48
    const DOUBLE_SURCHARGE_RATE = 2.00; // Doble recargo (nocturno + feriado)

    /**
     * Calcular tarifa horaria base del empleado
     *
     * @param float $monthlySalary Salario mensual
     * @param int $monthlyHours Horas mensuales esperadas (default 192 = 48h/semana * 4 semanas)
     * @return float Tarifa horaria
     */
    public function calculateHourlyRate(float $monthlySalary, int $monthlyHours = 192): float
    {
        if ($monthlyHours <= 0) {
            $monthlyHours = 192; // Default según legislación (48h/semana * 4 semanas)
        }

        return round($monthlySalary / $monthlyHours, 2);
    }

    /**
     * Calcular monto de horas extras al 25%
     *
     * @param float $hourlyRate Tarifa horaria base
     * @param float $hours Horas trabajadas
     * @return array Desglose del cálculo
     */
    public function calculateOvertime25Amount(float $hourlyRate, float $hours): array
    {
        $rate = $hourlyRate * self::OVERTIME_25_RATE;
        $amount = $rate * $hours;

        return [
            'hours' => $hours,
            'hourly_rate_base' => $hourlyRate,
            'rate_multiplier' => self::OVERTIME_25_RATE,
            'hourly_rate_with_surcharge' => round($rate, 2),
            'surcharge_amount' => round($hourlyRate * 0.25 * $hours, 2),
            'total_amount' => round($amount, 2),
            'article' => 'Art. 39 - Primeras 3 horas extras',
            'description' => sprintf('%.2f horas extras al 25%%', $hours)
        ];
    }

    /**
     * Calcular monto de horas extras al 50%
     *
     * @param float $hourlyRate Tarifa horaria base
     * @param float $hours Horas trabajadas
     * @return array Desglose del cálculo
     */
    public function calculateOvertime50Amount(float $hourlyRate, float $hours): array
    {
        $rate = $hourlyRate * self::OVERTIME_50_RATE;
        $amount = $rate * $hours;

        return [
            'hours' => $hours,
            'hourly_rate_base' => $hourlyRate,
            'rate_multiplier' => self::OVERTIME_50_RATE,
            'hourly_rate_with_surcharge' => round($rate, 2),
            'surcharge_amount' => round($hourlyRate * 0.50 * $hours, 2),
            'total_amount' => round($amount, 2),
            'article' => 'Art. 39 - Horas extras adicionales',
            'description' => sprintf('%.2f horas extras al 50%%', $hours)
        ];
    }

    /**
     * Calcular monto de horas nocturnas (6PM-6AM) con recargo 50%
     *
     * @param float $hourlyRate Tarifa horaria base
     * @param float $hours Horas nocturnas trabajadas
     * @return array Desglose del cálculo
     */
    public function calculateNightHoursAmount(float $hourlyRate, float $hours): array
    {
        $rate = $hourlyRate * self::NIGHT_RATE;
        $amount = $rate * $hours;

        return [
            'hours' => $hours,
            'hourly_rate_base' => $hourlyRate,
            'rate_multiplier' => self::NIGHT_RATE,
            'hourly_rate_with_surcharge' => round($rate, 2),
            'surcharge_amount' => round($hourlyRate * 0.50 * $hours, 2),
            'total_amount' => round($amount, 2),
            'article' => 'Art. 38 - Jornada Nocturna',
            'description' => sprintf('%.2f horas nocturnas (6PM-6AM) al 50%%', $hours)
        ];
    }

    /**
     * Calcular monto de horas en feriado/domingo con recargo 50%
     *
     * @param float $hourlyRate Tarifa horaria base
     * @param float $hours Horas trabajadas en feriado
     * @return array Desglose del cálculo
     */
    public function calculateHolidayHoursAmount(float $hourlyRate, float $hours): array
    {
        $rate = $hourlyRate * self::HOLIDAY_RATE;
        $amount = $rate * $hours;

        return [
            'hours' => $hours,
            'hourly_rate_base' => $hourlyRate,
            'rate_multiplier' => self::HOLIDAY_RATE,
            'hourly_rate_with_surcharge' => round($rate, 2),
            'surcharge_amount' => round($hourlyRate * 0.50 * $hours, 2),
            'total_amount' => round($amount, 2),
            'article' => 'Art. 48 - Trabajo en Día de Descanso',
            'description' => sprintf('%.2f horas en feriado/domingo al 50%%', $hours)
        ];
    }

    /**
     * Calcular monto de horas regulares (sin recargo)
     *
     * @param float $hourlyRate Tarifa horaria base
     * @param float $hours Horas regulares trabajadas
     * @return array Desglose del cálculo
     */
    public function calculateRegularHoursAmount(float $hourlyRate, float $hours): array
    {
        $amount = $hourlyRate * $hours;

        return [
            'hours' => $hours,
            'hourly_rate_base' => $hourlyRate,
            'rate_multiplier' => 1.00,
            'hourly_rate_with_surcharge' => $hourlyRate,
            'surcharge_amount' => 0,
            'total_amount' => round($amount, 2),
            'article' => 'Art. 31 - Jornada Ordinaria',
            'description' => sprintf('%.2f horas regulares', $hours)
        ];
    }

    /**
     * Calcular monto con doble recargo (nocturno + feriado)
     * Cuando se trabajan horas nocturnas en un feriado/domingo
     *
     * @param float $hourlyRate Tarifa horaria base
     * @param float $hours Horas con doble recargo
     * @return array Desglose del cálculo
     */
    public function calculateDoubleSurchargeAmount(float $hourlyRate, float $hours): array
    {
        $rate = $hourlyRate * self::DOUBLE_SURCHARGE_RATE;
        $amount = $rate * $hours;

        return [
            'hours' => $hours,
            'hourly_rate_base' => $hourlyRate,
            'rate_multiplier' => self::DOUBLE_SURCHARGE_RATE,
            'hourly_rate_with_surcharge' => round($rate, 2),
            'surcharge_amount' => round($hourlyRate * 1.00 * $hours, 2),
            'total_amount' => round($amount, 2),
            'article' => 'Art. 38 + Art. 48 - Jornada Nocturna en Feriado',
            'description' => sprintf('%.2f horas nocturnas en feriado al 100%% (doble recargo)', $hours)
        ];
    }

    /**
     * Calcular desglose completo de montos por tipo de hora
     *
     * @param float $monthlySalary Salario mensual del empleado
     * @param array $hoursBreakdown Desglose de horas por tipo
     * @return array Cálculo monetario completo
     */
    public function calculateCompleteAmounts(float $monthlySalary, array $hoursBreakdown): array
    {
        // Calcular tarifa horaria base
        $hourlyRate = $this->calculateHourlyRate($monthlySalary);

        $result = [
            'monthly_salary' => $monthlySalary,
            'hourly_rate' => $hourlyRate,
            'breakdown' => [],
            'totals' => [
                'total_hours' => 0,
                'total_amount' => 0,
                'total_surcharges' => 0,
                'regular_amount' => 0
            ]
        ];

        // Calcular horas regulares
        if (!empty($hoursBreakdown['regular_hours'])) {
            $regular = $this->calculateRegularHoursAmount(
                $hourlyRate,
                $hoursBreakdown['regular_hours']
            );
            $result['breakdown']['regular'] = $regular;
            $result['totals']['total_hours'] += $regular['hours'];
            $result['totals']['total_amount'] += $regular['total_amount'];
            $result['totals']['regular_amount'] += $regular['total_amount'];
        }

        // Calcular horas extras 25%
        if (!empty($hoursBreakdown['overtime_25_hours'])) {
            $overtime25 = $this->calculateOvertime25Amount(
                $hourlyRate,
                $hoursBreakdown['overtime_25_hours']
            );
            $result['breakdown']['overtime_25'] = $overtime25;
            $result['totals']['total_hours'] += $overtime25['hours'];
            $result['totals']['total_amount'] += $overtime25['total_amount'];
            $result['totals']['total_surcharges'] += $overtime25['surcharge_amount'];
        }

        // Calcular horas extras 50%
        if (!empty($hoursBreakdown['overtime_50_hours'])) {
            $overtime50 = $this->calculateOvertime50Amount(
                $hourlyRate,
                $hoursBreakdown['overtime_50_hours']
            );
            $result['breakdown']['overtime_50'] = $overtime50;
            $result['totals']['total_hours'] += $overtime50['hours'];
            $result['totals']['total_amount'] += $overtime50['total_amount'];
            $result['totals']['total_surcharges'] += $overtime50['surcharge_amount'];
        }

        // Calcular horas nocturnas
        if (!empty($hoursBreakdown['night_hours'])) {
            $night = $this->calculateNightHoursAmount(
                $hourlyRate,
                $hoursBreakdown['night_hours']
            );
            $result['breakdown']['night'] = $night;
            $result['totals']['total_amount'] += $night['surcharge_amount']; // Solo el recargo adicional
            $result['totals']['total_surcharges'] += $night['surcharge_amount'];
        }

        // Calcular horas en feriado
        if (!empty($hoursBreakdown['holiday_hours'])) {
            $holiday = $this->calculateHolidayHoursAmount(
                $hourlyRate,
                $hoursBreakdown['holiday_hours']
            );
            $result['breakdown']['holiday'] = $holiday;
            $result['totals']['total_hours'] += $holiday['hours'];
            $result['totals']['total_amount'] += $holiday['total_amount'];
            $result['totals']['total_surcharges'] += $holiday['surcharge_amount'];
        }

        // Redondear totales
        $result['totals']['total_hours'] = round($result['totals']['total_hours'], 2);
        $result['totals']['total_amount'] = round($result['totals']['total_amount'], 2);
        $result['totals']['total_surcharges'] = round($result['totals']['total_surcharges'], 2);
        $result['totals']['regular_amount'] = round($result['totals']['regular_amount'], 2);

        return $result;
    }

    /**
     * Calcular monto a pagar por un período específico
     *
     * @param float $monthlySalary Salario mensual
     * @param array $periodAttendances Array de asistencias del período con cálculos
     * @return array Resumen de pago del período
     */
    public function calculatePeriodPayment(float $monthlySalary, array $periodAttendances): array
    {
        $hourlyRate = $this->calculateHourlyRate($monthlySalary);

        $totals = [
            'regular_hours' => 0,
            'overtime_25_hours' => 0,
            'overtime_50_hours' => 0,
            'night_hours' => 0,
            'holiday_hours' => 0,
            'total_days' => count($periodAttendances),
            'total_hours' => 0
        ];

        // Sumar horas de todas las asistencias
        foreach ($periodAttendances as $attendance) {
            $totals['regular_hours'] += $attendance['regular_hours'] ?? 0;
            $totals['overtime_25_hours'] += $attendance['overtime_25_hours'] ?? 0;
            $totals['overtime_50_hours'] += $attendance['overtime_50_hours'] ?? 0;
            $totals['night_hours'] += $attendance['night_hours'] ?? 0;
            $totals['holiday_hours'] += $attendance['holiday_hours'] ?? 0;
            $totals['total_hours'] += $attendance['total_hours'] ?? 0;
        }

        // Calcular montos
        $amounts = $this->calculateCompleteAmounts($monthlySalary, $totals);

        return [
            'period_summary' => $totals,
            'payment_details' => $amounts,
            'monthly_salary' => $monthlySalary,
            'hourly_rate' => $hourlyRate,
            'total_to_pay' => $amounts['totals']['total_amount']
        ];
    }

    /**
     * Generar resumen legible de cálculo de tarifas
     *
     * @param array $calculation Cálculo completo de montos
     * @return string Resumen en texto
     */
    public function generatePaymentSummary(array $calculation): string
    {
        $lines = [];
        $lines[] = "RESUMEN DE PAGO";
        $lines[] = str_repeat('-', 60);
        $lines[] = sprintf("Salario Mensual: $%.2f", $calculation['monthly_salary']);
        $lines[] = sprintf("Tarifa Horaria: $%.2f/hora", $calculation['hourly_rate']);
        $lines[] = "";
        $lines[] = "DESGLOSE POR TIPO DE HORA:";
        $lines[] = str_repeat('-', 60);

        foreach ($calculation['breakdown'] as $type => $detail) {
            $lines[] = sprintf(
                "%s: %.2f horas x $%.2f = $%.2f",
                strtoupper($type),
                $detail['hours'],
                $detail['hourly_rate_with_surcharge'],
                $detail['total_amount']
            );

            if ($detail['surcharge_amount'] > 0) {
                $lines[] = sprintf("  (Recargo: $%.2f)", $detail['surcharge_amount']);
            }
        }

        $lines[] = "";
        $lines[] = str_repeat('-', 60);
        $lines[] = sprintf("Total Horas: %.2f", $calculation['totals']['total_hours']);
        $lines[] = sprintf("Total Recargos: $%.2f", $calculation['totals']['total_surcharges']);
        $lines[] = sprintf("TOTAL A PAGAR: $%.2f", $calculation['totals']['total_amount']);
        $lines[] = str_repeat('=', 60);

        return implode("\n", $lines);
    }

    /**
     * Obtener multiplicadores de tarifa según legislación
     *
     * @return array Array de multiplicadores
     */
    public function getRateMultipliers(): array
    {
        return [
            'regular' => [
                'multiplier' => 1.00,
                'percent' => 0,
                'description' => 'Horas regulares',
                'article' => 'Art. 31'
            ],
            'overtime_25' => [
                'multiplier' => self::OVERTIME_25_RATE,
                'percent' => 25,
                'description' => 'Primeras 3 horas extras',
                'article' => 'Art. 39'
            ],
            'overtime_50' => [
                'multiplier' => self::OVERTIME_50_RATE,
                'percent' => 50,
                'description' => 'Horas extras adicionales',
                'article' => 'Art. 39'
            ],
            'night' => [
                'multiplier' => self::NIGHT_RATE,
                'percent' => 50,
                'description' => 'Jornada nocturna (6PM-6AM)',
                'article' => 'Art. 38'
            ],
            'holiday' => [
                'multiplier' => self::HOLIDAY_RATE,
                'percent' => 50,
                'description' => 'Domingos y feriados',
                'article' => 'Art. 48'
            ],
            'double_surcharge' => [
                'multiplier' => self::DOUBLE_SURCHARGE_RATE,
                'percent' => 100,
                'description' => 'Nocturno en feriado (doble recargo)',
                'article' => 'Art. 38 + Art. 48'
            ]
        ];
    }
}
