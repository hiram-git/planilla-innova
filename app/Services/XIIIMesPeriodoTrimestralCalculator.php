<?php

namespace App\Services;

use DateTime;
use DateInterval;
use Exception;

/**
 * Calculadora para XIII Mes con períodos trimestrales según legislación panameña
 *
 * Períodos:
 * - Período 1: 16 Diciembre → 15 Abril
 * - Período 2: 16 Abril → 15 Agosto
 * - Período 3: 16 Agosto → 15 Diciembre
 */
class XIIIMesPeriodoTrimestralCalculator
{
    /**
     * Determina el período trimestral del XIII mes según fecha de liquidación
     *
     * @param string $fechaLiquidacion Fecha en formato Y-m-d
     * @return array Información del período trimestral
     */
    public function determinarPeriodoTrimestral(string $fechaLiquidacion): array
    {
        $fecha = new DateTime($fechaLiquidacion);
        $mes = (int)$fecha->format('n');
        $dia = (int)$fecha->format('j');
        $año = (int)$fecha->format('Y');

        // Período 1: Segunda quincena Dic → Primera quincena Abr
        if (($mes === 12 && $dia >= 16) || in_array($mes, [1, 2, 3]) || ($mes === 4 && $dia <= 15)) {

            if ($mes === 12 && $dia >= 16) {
                // Diciembre 16-31: inicio nuevo período para año siguiente
                return [
                    'periodo' => 1,
                    'año' => $año + 1,
                    'fecha_inicio' => "{$año}-12-16",
                    'fecha_fin' => ($año + 1) . "-04-15",
                    'descripcion' => "Período 1: Dic {$año} - Abr " . ($año + 1),
                    'estado' => 'INICIO_PERIODO',
                    'tipo_pago' => 'TRIMESTRAL_1'
                ];
            } else {
                // Enero-Abril 15: continuación período del año
                return [
                    'periodo' => 1,
                    'año' => $año,
                    'fecha_inicio' => ($año - 1) . "-12-16",
                    'fecha_fin' => "{$año}-04-15",
                    'descripcion' => "Período 1: Dic " . ($año - 1) . " - Abr {$año}",
                    'estado' => 'CONTINUACION_PERIODO',
                    'tipo_pago' => 'TRIMESTRAL_1'
                ];
            }
        }

        // Período 2: Segunda quincena Abr → Primera quincena Ago
        elseif (($mes === 4 && $dia >= 16) || in_array($mes, [5, 6, 7]) || ($mes === 8 && $dia <= 15)) {
            return [
                'periodo' => 2,
                'año' => $año,
                'fecha_inicio' => "{$año}-04-16",
                'fecha_fin' => "{$año}-08-15",
                'descripcion' => "Período 2: Abr {$año} - Ago {$año}",
                'estado' => 'PERIODO_MEDIO',
                'tipo_pago' => 'TRIMESTRAL_2'
            ];
        }

        // Período 3: Segunda quincena Ago → Primera quincena Dic
        else {
            return [
                'periodo' => 3,
                'año' => $año,
                'fecha_inicio' => "{$año}-08-16",
                'fecha_fin' => "{$año}-12-15",
                'descripcion' => "Período 3: Ago {$año} - Dic {$año}",
                'estado' => 'PERIODO_FINAL',
                'tipo_pago' => 'TRIMESTRAL_3'
            ];
        }
    }

    /**
     * Obtiene las fechas del período XIII mes para una fecha de liquidación específica
     *
     * @param string $fechaLiquidacion
     * @return array ['inicio' => 'Y-m-d', 'fin' => 'Y-m-d']
     */
    public function obtenerFechasPeriodoXIII(string $fechaLiquidacion): array
    {
        $periodoInfo = $this->determinarPeriodoTrimestral($fechaLiquidacion);

        return [
            'inicio' => $periodoInfo['fecha_inicio'],
            'fin' => $periodoInfo['fecha_fin'],
            'periodo' => $periodoInfo['periodo'],
            'descripcion' => $periodoInfo['descripcion']
        ];
    }

    /**
     * Calcula días laborables entre dos fechas (excluyendo fines de semana)
     *
     * @param DateTime $inicio
     * @param DateTime $fin
     * @return int
     */
    public function calcularDiasLaborables(DateTime $inicio, DateTime $fin): int
    {
        $dias = 0;
        $current = clone $inicio;

        while ($current <= $fin) {
            $diaSemana = (int)$current->format('N'); // 1=Lunes, 7=Domingo

            if ($diaSemana <= 5) { // Lunes a Viernes
                // TODO: Integrar con BusinessCalendar para excluir feriados cuando esté disponible
                if (!$this->esFeriado($current)) {
                    $dias++;
                }
            }

            $current->add(new DateInterval('P1D'));
        }

        return $dias;
    }

    /**
     * Verifica si una fecha es feriado (implementación básica)
     *
     * @param DateTime $fecha
     * @return bool
     */
    private function esFeriado(DateTime $fecha): bool
    {
        // Lista básica de feriados fijos de Panamá
        $feriados = [
            '01-01', // Año Nuevo
            '01-09', // Día de los Mártires
            '05-01', // Día del Trabajador
            '11-03', // Independencia de Colombia
            '11-10', // Primer Grito de Independencia
            '11-28', // Independencia de España
            '12-08', // Día de la Madre
            '12-25'  // Navidad
        ];

        $fechaFormato = $fecha->format('m-d');
        return in_array($fechaFormato, $feriados);
    }

    /**
     * Calcula el XIII mes proporcional para liquidación
     *
     * @param string $fechaLiquidacion
     * @param float $acumuladosPeriodo
     * @param string $fechaIngreso
     * @return array
     */
    public function calcularXIIIMesProporcional(
        string $fechaLiquidacion,
        float $acumuladosPeriodo,
        string $fechaIngreso
    ): array {
        try {
            $periodoInfo = $this->determinarPeriodoTrimestral($fechaLiquidacion);

            $fechaIngresoDate = new DateTime($fechaIngreso);
            $fechaInicioPeriodo = new DateTime($periodoInfo['fecha_inicio']);
            $fechaLiquidacionDate = new DateTime($fechaLiquidacion);

            // Fecha de inicio para cálculo: la mayor entre ingreso y inicio de período
            $fechaInicioCalculo = ($fechaIngresoDate > $fechaInicioPeriodo)
                ? $fechaIngresoDate
                : $fechaInicioPeriodo;

            // Calcular días trabajados
            $diasTrabajados = $this->calcularDiasLaborables($fechaInicioCalculo, $fechaLiquidacionDate);
            $diasTotalPeriodo = $this->calcularDiasLaborables(
                $fechaInicioPeriodo,
                new DateTime($periodoInfo['fecha_fin'])
            );

            // Cálculo proporcional
            $proporcion = $diasTotalPeriodo > 0 ? $diasTrabajados / $diasTotalPeriodo : 0;
            $xiiiMesTrimestral = $acumuladosPeriodo / 4; // División entre 4 cuotas anuales
            $xiiiMesProporcional = $xiiiMesTrimestral * $proporcion;

            return [
                'periodo_info' => $periodoInfo,
                'fecha_inicio_calculo' => $fechaInicioCalculo->format('Y-m-d'),
                'fecha_fin_calculo' => $fechaLiquidacionDate->format('Y-m-d'),
                'dias_trabajados' => $diasTrabajados,
                'dias_total_periodo' => $diasTotalPeriodo,
                'proporcion' => round($proporcion, 4),
                'acumulados_periodo' => $acumuladosPeriodo,
                'xiii_mes_trimestral' => round($xiiiMesTrimestral, 2),
                'xiii_mes_proporcional' => round($xiiiMesProporcional, 2),
                'formula' => "({$acumuladosPeriodo} / 4) * ({$diasTrabajados} / {$diasTotalPeriodo})"
            ];

        } catch (Exception $e) {
            error_log("Error calculando XIII mes proporcional: " . $e->getMessage());

            return [
                'periodo_info' => ['periodo' => 0, 'descripcion' => 'Error'],
                'fecha_inicio_calculo' => $fechaLiquidacion,
                'fecha_fin_calculo' => $fechaLiquidacion,
                'dias_trabajados' => 0,
                'dias_total_periodo' => 1,
                'proporcion' => 0,
                'acumulados_periodo' => $acumuladosPeriodo,
                'xiii_mes_trimestral' => 0,
                'xiii_mes_proporcional' => 0,
                'formula' => 'Error en cálculo'
            ];
        }
    }

    /**
     * Obtiene información completa del período XIII mes
     *
     * @param string $fechaLiquidacion
     * @return array
     */
    public function obtenerInfoCompletaPeriodo(string $fechaLiquidacion): array
    {
        $periodoInfo = $this->determinarPeriodoTrimestral($fechaLiquidacion);

        $fechaInicio = new DateTime($periodoInfo['fecha_inicio']);
        $fechaFin = new DateTime($periodoInfo['fecha_fin']);
        $diasTotales = $this->calcularDiasLaborables($fechaInicio, $fechaFin);

        return array_merge($periodoInfo, [
            'dias_totales_periodo' => $diasTotales,
            'fecha_liquidacion' => $fechaLiquidacion,
            'porcentaje_año' => round((4 / 12) * 100, 2) // 33.33% del año
        ]);
    }
}