<?php

namespace App\Services;

use PDO;
use PDOException;

/**
 * 🔒 Calculadora de conceptos para planillas (VERSIÓN SEGURA)
 *
 * Extiende PlanillaConceptCalculatorSecure para heredar evaluación segura con NXP\MathExecutor.
 * Agrega funcionalidades específicas de liquidaciones, vacaciones y XIII mes trimestral.
 *
 * ⚠️ IMPORTANTE: Esta clase YA NO USA eval() - todo se evalúa con MathExecutor
 */
class PlanillaConceptCalculator extends PlanillaConceptCalculatorSecure
{
    private XIIIMesPeriodoTrimestralCalculator $xiiiMesCalculator;

    public function __construct()
    {
        // Inicializar clase padre (segura con MathExecutor)
        parent::__construct();

        // Inicializar calculador trimestral
        $this->xiiiMesCalculator = new XIIIMesPeriodoTrimestralCalculator();
    }

    // ========================================
    // MÉTODOS SOBRESCRITOS DE LA CLASE PADRE
    // ========================================

    /**
     * Establecer variables específicas del colaborador
     * Sobrescribe el método del padre para agregar lógica de múltiples tipos de planilla
     *
     * @param int $employee_id ID del empleado
     * @param int|null $tipo_planilla_id ID del tipo de planilla (si aplica)
     */
    public function setVariablesColaborador(int $employee_id, int $tipo_planilla_id = null): void
    {
        try {
            // Limpiar caché de asistencias al cambiar de empleado
            $this->limpiarCacheAsistencias();

            // Obtener tipo de empresa de la configuración
            $companyType = $this->getCompanyType();

            $sql = "SELECT e.id, e.fecha_ingreso, e.employee_id, e.firstname, e.lastname, e.created_on,
                           e.sueldo_individual, e.gastos_representacion, e.clave_seguro_social,
                           e.marca_asistencia,
                           p.sueldo as sueldo_posicion,
                           s.time_in, s.time_out
                    FROM employees e
                    LEFT JOIN posiciones p ON p.id = e.position_id
                    LEFT JOIN schedules s ON s.id = e.schedule_id
                    WHERE e.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                error_log("ERROR: No se encontró empleado con ID $employee_id");
                return;
            }

            // Determinar sueldo según tipo de empresa y tipo de planilla
            $salario = 0;
            $gastos_representacion = 0;

            if ($companyType === 'publica') {
                // Empresa pública: sueldo viene de la posición
                $salario = (float)($employee['sueldo_posicion'] ?: 0);
                $gastos_representacion = (float)($employee['gastos_representacion'] ?: 0);
            } else {
                // Empresa privada: intentar obtener salario de la nueva tabla
                if ($tipo_planilla_id !== null) {
                    $salario_planilla = $this->getSalarioByTipoPlanilla($employee_id, $tipo_planilla_id);
                    if ($salario_planilla) {
                        $salario = $salario_planilla['sueldo_base'];
                        $gastos_representacion = $salario_planilla['gastos_representacion'];
                    } else {
                        // Fallback: usar campos antiguos si no hay registro en la nueva tabla
                        $salario = (float)($employee['sueldo_individual'] ?: 0);
                        $gastos_representacion = (float)($employee['gastos_representacion'] ?: 0);
                    }
                } else {
                    // Si no se especifica tipo_planilla_id, usar campos antiguos
                    $salario = (float)($employee['sueldo_individual'] ?: 0);
                    $gastos_representacion = (float)($employee['gastos_representacion'] ?: 0);
                }

                // Fallback final: usar sueldo de posición si no hay nada
                if ($salario == 0) {
                    $salario = (float)($employee['sueldo_posicion'] ?: 0);
                }
            }

            $ficha = $employee['employee_id'] ?: '0';

            // Calcular horas de trabajo
            $horas = $this->calcularHorasTrabajo($employee['time_in'], $employee['time_out']);

            // Calcular antigüedad
            $antiguedadDias = $this->calcularAntiguedad($employee['created_on'] ?? $employee['fecha_ingreso']);

            // Calcular antiguedad en años y meses
            $fecha_ingreso = new \DateTime($employee['fecha_ingreso'] ?? $employee['created_on']);
            $now = new \DateTime();
            $diff = $fecha_ingreso->diff($now);

            // Obtener clave de seguro social
            $clave_seguro_social = $employee['clave_seguro_social'] ?: '';

            // Obtener marca_asistencia (si empleado paga por horas trabajadas)
            $marca_asistencia = (int)($employee['marca_asistencia'] ?? 0);

            // Calcular tarifa por hora (220 horas mensuales estándar)
            // Esta es la base para empleados que cobran por hora
            $tarifa_hora = $salario > 0 ? ($salario / 220) : 0;

            // Establecer variables en el executor (heredado de la clase padre)
            $this->executor->setVar('SUELDO', $salario);
            $this->executor->setVar('SALARIO', $salario);
            $this->executor->setVar('GASTOS_REP', $gastos_representacion);
            $this->executor->setVar('GASTOS_REPRESENTACION', $gastos_representacion);
            $this->executor->setVar('CLAVE_SS', $clave_seguro_social);
            $this->executor->setVar('CLAVE_SEGURO_SOCIAL', $clave_seguro_social);
            $this->executor->setVar('FICHA', $ficha);
            $this->executor->setVar('EMPLEADO', $ficha);
            $this->executor->setVar('EMPLOYEE_ID', $employee_id);
            $this->executor->setVar('HORAS', $horas);
            $this->executor->setVar('ANTIGUEDAD', (float)($antiguedadDias / 365));
            $this->executor->setVar('ANTIGUEDAD_DIAS', (float)$antiguedadDias);
            $this->executor->setVar('ANTIGUEDAD_ANUAL', (float)$diff->y);
            $this->executor->setVar('ANTIGUEDAD_MES', (float)$diff->m);

            // Variables para cálculo de salario basado en asistencia
            $this->executor->setVar('MARCA_ASISTENCIA', $marca_asistencia);
            $this->executor->setVar('TARIFA_HORA', $tarifa_hora);

            // Guardar para referencia interna (heredado de la clase padre)
            $this->variablesColaborador = [
                'SUELDO' => $salario,
                'SALARIO' => $salario,
                'GASTOS_REP' => $gastos_representacion,
                'GASTOS_REPRESENTACION' => $gastos_representacion,
                'CLAVE_SS' => $clave_seguro_social,
                'CLAVE_SEGURO_SOCIAL' => $clave_seguro_social,
                'FICHA' => $ficha,
                'EMPLEADO' => $ficha,
                'EMPLOYEE_ID' => $employee_id,
                'HORAS' => $horas,
                'ANTIGUEDAD' => (float)($antiguedadDias / 365),
                'ANTIGUEDAD_DIAS' => (float)$antiguedadDias,
                'ANTIGUEDAD_ANUAL' => (float)$diff->y,
                'ANTIGUEDAD_MES' => (float)$diff->m,
                'MARCA_ASISTENCIA' => $marca_asistencia,
                'TARIFA_HORA' => $tarifa_hora,
            ];

        } catch (PDOException $e) {
            error_log("Error estableciendo variables colaborador: " . $e->getMessage());
        }
    }

    /**
     * Establecer una variable específica del colaborador
     * Sobrescribe variable en el executor y en el array de variables
     *
     * @param string $nombre Nombre de la variable
     * @param mixed $valor Valor de la variable
     */
    public function setVariable(string $nombre, $valor): void
    {
        $this->executor->setVar($nombre, $valor);
        $this->variablesColaborador[$nombre] = $valor;
    }

    // ========================================
    // MÉTODOS AUXILIARES ESPECÍFICOS
    // ========================================

    /**
     * Obtener tipo de empresa desde configuración
     * @return string 'publica' | 'privada'
     */
    private function getCompanyType(): string
    {
        try {
            $sql = "SELECT tipo_institucion FROM companies WHERE id = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['tipo_institucion'] ?? 'privada';
        } catch (PDOException $e) {
            error_log("Error obteniendo tipo de empresa: " . $e->getMessage());
            return 'privada'; // Default fallback
        }
    }

    // ===============================================================
    // FUNCIONES ESPECÍFICAS PARA LIQUIDACIONES - LEGISLACIÓN PANAMÁ
    // ===============================================================

    /**
     * Calcular indemnización por despido según legislación panameña
     * Primeros 10 años: 3.4 semanas × año
     * Después de 10 años: 1 semana × año
     *
     * @param float $anosTrabjados Años trabajados
     * @param float $sueldoSemanal Sueldo semanal base
     * @return float Monto de indemnización
     */
    public function LIQUIDACION_INDEMNIZACION(float $anosTrabjados, float $sueldoSemanal): float
    {
        try {
            if ($anosTrabjados <= 0 || $sueldoSemanal <= 0) {
                return 0;
            }

            $indemnizacion = 0;

            if ($anosTrabjados <= 10) {
                // Primeros 10 años: 3.4 semanas por año
                $indemnizacion = $anosTrabjados * 3.4 * $sueldoSemanal;
            } else {
                // Primeros 10 años: 3.4 semanas por año
                $indemnizacion = 10 * 3.4 * $sueldoSemanal;
                // Años adicionales: 1 semana por año
                $anosAdicionales = $anosTrabjados - 10;
                $indemnizacion += $anosAdicionales * 1 * $sueldoSemanal;
            }

            return round($indemnizacion, 2);

        } catch (\Exception $e) {
            error_log("Error calculando indemnización: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular vacaciones proporcionales no disfrutadas
     * 30 días de vacaciones por año × proporción del año trabajado
     *
     * @return float Monto de vacaciones proporcionales
     */
    public function VACACIONES_PROPORCIONALES(): float
    {
        try {
            if (!isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return 0;
            }

            $employeeId = $this->variablesColaborador['EMPLOYEE_ID'];

            // Obtener datos del empleado y fechas
            $sql = "SELECT fecha_ingreso FROM employees WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                return 0;
            }

            $fechaIngreso = new \DateTime($employee['fecha_ingreso']);
            $fechaActual = new \DateTime(); // Fecha de liquidación

            // Calcular días desde última fecha de corte de vacaciones (ej: enero 1)
            $inicioAnoActual = new \DateTime(date('Y-01-01'));

            // Usar la fecha más tardía entre ingreso y inicio del año
            $fechaInicioCalculo = $fechaIngreso > $inicioAnoActual ? $fechaIngreso : $inicioAnoActual;

            // Calcular días trabajados en el año actual
            $diasTrabajados = $fechaInicioCalculo->diff($fechaActual)->days + 1;

            // Calcular proporción de vacaciones (30 días por año / 365 días)
            $proporcionVacaciones = ($diasTrabajados / 365) * 30;

            // Obtener salario diario
            $salarioDiario = $this->calcularSalarioDiario($employeeId);

            // Calcular monto de vacaciones proporcionales
            $montoVacaciones = $proporcionVacaciones * $salarioDiario;

            return round($montoVacaciones, 2);

        } catch (\Exception $e) {
            error_log("Error calculando vacaciones proporcionales: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular XIII mes proporcional para liquidación
     * Basado en meses trabajados en el año actual
     *
     * @return float Monto XIII mes proporcional
     */
    public function XIII_MES_PROPORCIONAL(): float
    {
        try {
            if (!isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return 0;
            }

            $employeeId = $this->variablesColaborador['EMPLOYEE_ID'];

            // Obtener datos del empleado
            $sql = "SELECT fecha_ingreso FROM employees WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                return 0;
            }

            $fechaIngreso = new \DateTime($employee['fecha_ingreso']);
            $fechaActual = new \DateTime(); // Fecha de liquidación
            $inicioAno = new \DateTime(date('Y-01-01'));

            // Usar la fecha más tardía entre ingreso y inicio del año
            $fechaInicioCalculo = $fechaIngreso > $inicioAno ? $fechaIngreso : $inicioAno;

            // Calcular meses trabajados en el año actual
            $diferencia = $fechaInicioCalculo->diff($fechaActual);
            $mesesTrabajados = ($diferencia->y * 12) + $diferencia->m;

            // Si trabajó días adicionales, contar como mes parcial
            if ($diferencia->d > 15) {
                $mesesTrabajados += 1;
            }

            // Obtener salario mensual
            $salarioMensual = $this->calcularSalarioMensual($employeeId);

            // Calcular XIII mes proporcional: (salario × meses trabajados) / 12
            $xiiiMesProporcional = ($salarioMensual * $mesesTrabajados) / 12;

            return round($xiiiMesProporcional, 2);

        } catch (\Exception $e) {
            error_log("Error calculando XIII mes proporcional: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular salario pendiente (últimos días trabajados)
     *
     * @return float Monto de salario pendiente
     */
    public function SALARIO_PENDIENTE(): float
    {
        try {
            if (!isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return 0;
            }

            $employeeId = $this->variablesColaborador['EMPLOYEE_ID'];

            // Por ahora retornar 0, ya que esto se calcula típicamente
            // en base a días trabajados desde última planilla procesada
            // TODO: Implementar lógica específica basada en última planilla

            return 0;

        } catch (\Exception $e) {
            error_log("Error calculando salario pendiente: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular descuentos por préstamos pendientes
     *
     * @return float Monto de descuentos por préstamos
     */
    public function DESCUENTO_PRESTAMOS(): float
    {
        try {
            if (!isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return 0;
            }

            $employeeId = $this->variablesColaborador['EMPLOYEE_ID'];

            // TODO: Implementar cuando se tenga módulo de préstamos
            // Por ahora retornar 0

            return 0;

        } catch (\Exception $e) {
            error_log("Error calculando descuento préstamos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular descuentos por anticipos pendientes
     *
     * @return float Monto de descuentos por anticipos
     */
    public function DESCUENTO_ANTICIPOS(): float
    {
        try {
            if (!isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return 0;
            }

            $employeeId = $this->variablesColaborador['EMPLOYEE_ID'];

            // Buscar anticipos pendientes en tabla cashadvance
            $sql = "SELECT COALESCE(SUM(amount), 0) as total_anticipos
                    FROM cashadvance
                    WHERE employee_id = (SELECT employee_id FROM employees WHERE id = ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (float)($result['total_anticipos'] ?? 0);

        } catch (\Exception $e) {
            error_log("Error calculando descuento anticipos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Agregar variable ANOS_TRABAJADOS para fórmulas de liquidación
     * Calcula años trabajados desde fecha de ingreso hasta fecha de liquidación
     *
     * @return float Años trabajados
     */
    public function getAnosTrabajados(): float
    {
        try {
            if (!isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return 0;
            }

            $employeeId = $this->variablesColaborador['EMPLOYEE_ID'];

            $sql = "SELECT fecha_ingreso FROM employees WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                return 0;
            }

            $fechaIngreso = new \DateTime($employee['fecha_ingreso']);
            $fechaActual = new \DateTime(); // Fecha de liquidación

            $diferencia = $fechaIngreso->diff($fechaActual);

            // Calcular años con decimales (incluir meses como fracción)
            $anos = $diferencia->y + ($diferencia->m / 12);

            return round($anos, 2);

        } catch (\Exception $e) {
            error_log("Error calculando años trabajados: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular salario semanal base
     *
     * @param int $employeeId ID del empleado
     * @return float Salario semanal
     */
    private function calcularSalarioSemanal(int $employeeId): float
    {
        $salarioMensual = $this->calcularSalarioMensual($employeeId);
        return $salarioMensual / 4.33; // 4.33 semanas promedio por mes
    }

    /**
     * Calcular salario mensual base
     *
     * @param int $employeeId ID del empleado
     * @return float Salario mensual
     */
    private function calcularSalarioMensual(int $employeeId): float
    {
        try {
            $companyType = $this->getCompanyType();

            $sql = "SELECT e.sueldo_individual, p.sueldo as sueldo_posicion, e.gastos_representacion
                    FROM employees e
                    LEFT JOIN posiciones p ON p.id = e.position_id
                    WHERE e.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                return 0;
            }

            $salarioBase = 0;
            if ($companyType === 'publica') {
                $salarioBase = (float)($employee['sueldo_posicion'] ?? 0);
            } else {
                $sueldo_individual = (float)($employee['sueldo_individual'] ?? 0);
                $salarioBase = $sueldo_individual > 0 ? $sueldo_individual : (float)($employee['sueldo_posicion'] ?? 0);
            }

            // Agregar gastos de representación al salario base
            $gastosRepresentacion = (float)($employee['gastos_representacion'] ?? 0);

            return $salarioBase + $gastosRepresentacion;

        } catch (\Exception $e) {
            error_log("Error calculando salario mensual: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular salario diario base
     *
     * @param int $employeeId ID del empleado
     * @return float Salario diario
     */
    private function calcularSalarioDiario(int $employeeId): float
    {
        $salarioMensual = $this->calcularSalarioMensual($employeeId);
        return $salarioMensual / 30; // 30 días promedio por mes
    }

    /**
     * Agregar variables específicas para liquidación
     */
    public function setVariablesLiquidacion(int $employeeId, int $terminationId = null): void
    {
        $this->setVariablesColaborador($employeeId);

        // Establecer fechas de liquidación (últimos 11 meses desde fecha de terminación)
        if ($terminationId) {
            $this->setFechasLiquidacion($terminationId);
            $this->variablesColaborador['DIAS_PREAVISO'] = $this->getDiasPreaviso($terminationId);
        } else {
            $this->variablesColaborador['DIAS_PREAVISO'] = 30; // Fallback por defecto
        }

        // Agregar variables específicas de liquidación
        $this->variablesColaborador['ANOS_TRABAJADOS'] = $this->getAnosTrabajados();
        $this->variablesColaborador['SUELDO_SEMANAL'] = $this->calcularSalarioSemanal($employeeId);
        $this->variablesColaborador['SUELDO_MENSUAL'] = $this->calcularSalarioMensual($employeeId);
        $this->variablesColaborador['SUELDO_DIARIO'] = $this->calcularSalarioDiario($employeeId);
    }

    /**
     * Establecer fechas de liquidación (últimos 11 meses desde fecha de terminación)
     */
    private function setFechasLiquidacion(int $terminationId): void
    {
        try {
            // Obtener fecha de terminación y employee_id
            $sql = "SELECT et.termination_date, et.employee_id, e.fecha_ingreso
                    FROM employee_terminations et
                    INNER JOIN employees e ON et.employee_id = e.id
                    WHERE et.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$terminationId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['termination_date'])) {
                $fechaTerminacion = new \DateTime($result['termination_date']);

                // Calcular fecha de inicio: 11 meses atrás desde la fecha de terminación
                $fechaInicio = clone $fechaTerminacion;
                $fechaInicio->modify('-11 months');

                // Establecer las fechas para INIPERIODO y FINPERIODO (período de liquidación)
                $this->establecerFechasPlanilla(
                    $fechaInicio->format('Y-m-d'),
                    $fechaTerminacion->format('Y-m-d'),
                    $fechaTerminacion->format('Y-m-d')
                );
            }
        } catch (Exception $e) {
            error_log("ERROR en setFechasLiquidacion: " . $e->getMessage());
            // En caso de error, usar fechas por defecto del año actual
            $fechaActual = new \DateTime();
            $fechaInicio = new \DateTime($fechaActual->format('Y-01-01'));
            $fechaFin = new \DateTime($fechaActual->format('Y-12-31'));

            $this->establecerFechasPlanilla(
                $fechaInicio->format('Y-m-d'),
                $fechaFin->format('Y-m-d'),
                $fechaActual->format('Y-m-d')
            );
        }
    }
     // ========================================
    // FUNCIONES XIII MES PERÍODOS TRIMESTRALES
    // ========================================

    /**
     * Obtiene las variables de fecha dinámicas para XIII mes trimestral
     *
     * @param int $empleadoId
     * @return array
     */
    private function obtenerVariablesFechaXIIIMes(int $empleadoId): array
    {
        try {
            // Obtener fecha de liquidación del empleado
            $fechaLiquidacion = $this->obtenerFechaLiquidacionEmpleado($empleadoId);

            if (!$fechaLiquidacion) {
                // Fallback: usar fechas de planilla actual si no hay liquidación
                return [
                    'INICIO_PERIODO_XIII' => $this->fechasActuales['fecha_desde'] ?? date('Y-01-01'),
                    'FIN_PERIODO_XIII' => $this->fechasActuales['fecha_hasta'] ?? date('Y-12-31'),
                    'PERIODO_XIII_NUMERO' => 0,
                    'PERIODO_XIII_ESTADO' => 'SIN_LIQUIDACION'
                ];
            }

            // Obtener fechas del período trimestral correcto
            $periodoInfo = $this->xiiiMesCalculator->determinarPeriodoTrimestral($fechaLiquidacion);

            return [
                'INICIO_PERIODO_XIII' => $periodoInfo['fecha_inicio'],
                'FIN_PERIODO_XIII' => $periodoInfo['fecha_fin'],
                'PERIODO_XIII_NUMERO' => $periodoInfo['periodo'],
                'PERIODO_XIII_AÑO' => $periodoInfo['año'],
                'PERIODO_XIII_ESTADO' => $periodoInfo['estado'],
                'FECHA_LIQUIDACION' => $fechaLiquidacion
            ];

        } catch (Exception $e) {
            error_log("Error obteniendo variables fecha XIII mes: " . $e->getMessage());

            return [
                'INICIO_PERIODO_XIII' => date('Y-01-01'),
                'FIN_PERIODO_XIII' => date('Y-12-31'),
                'PERIODO_XIII_NUMERO' => 0,
                'PERIODO_XIII_ESTADO' => 'ERROR'
            ];
        }
    }

    /**
     * Obtiene la fecha de liquidación de un empleado
     *
     * @param int $empleadoId
     * @return string|null
     */
    private function obtenerFechaLiquidacionEmpleado(int $empleadoId): ?string
    {
        try {
            // Buscar en employee_terminations
            $stmt = $this->db->prepare("
                SELECT termination_date
                FROM employee_terminations
                WHERE employee_id = ?
                ORDER BY termination_date DESC
                LIMIT 1
            ");

            $stmt->execute([$empleadoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['termination_date'] : null;

        } catch (Exception $e) {
            error_log("Error obteniendo fecha liquidación empleado {$empleadoId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene la fecha de ingreso de un empleado
     *
     * @param int $empleadoId
     * @return string
     */
    private function obtenerFechaIngresoEmpleado(int $empleadoId): string
    {
        try {
            $stmt = $this->db->prepare("
                SELECT fecha_ingreso
                FROM employee
                WHERE id = ?
            ");

            $stmt->execute([$empleadoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['fecha_ingreso'] ?? date('Y-01-01');

        } catch (Exception $e) {
            error_log("Error obteniendo fecha ingreso empleado {$empleadoId}: " . $e->getMessage());
            return date('Y-01-01');
        }
    }

    /**
     * Obtiene acumulados de conceptos en un período específico
     *
     * @param int $empleadoId
     * @param string $conceptos
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return float
     */
    private function obtenerAcumuladosPeriodoTrimestral(
        int $empleadoId,
        string $conceptos,
        string $fechaInicio,
        string $fechaFin
    ): float {
        try {
            $conceptosArray = array_map('trim', explode(',', str_replace(['"', "'"], '', $conceptos)));
            $total = 0;

            foreach ($conceptosArray as $concepto) {
                if (empty($concepto)) continue;

                $stmt = $this->db->prepare("
                    SELECT COALESCE(SUM(pd.monto), 0) as total
                    FROM planilla_detalle pd
                    INNER JOIN planilla_cabecera pc ON pd.planilla_id = pc.id
                    INNER JOIN concepto c ON pd.concepto_id = c.id
                    WHERE pd.employee_id = ?
                        AND c.concepto = ?
                        AND pc.fecha_inicio >= ?
                        AND pc.fecha_fin <= ?
                        AND pc.estado = 'CERRADA'
                        AND pd.tipo = 'A'
                ");

                $stmt->execute([$empleadoId, $concepto, $fechaInicio, $fechaFin]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                $total += (float)($result['total'] ?? 0);
            }

            return $total;

        } catch (Exception $e) {
            error_log("Error obteniendo acumulados período trimestral: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Procesa la función XIII_MES_PROPORCIONAL_TRIMESTRAL en fórmulas
     *
     * @param string $formula
     * @return string
     */
    private function procesarXIIIMesProporcionalTrimestral(string $formula): string
    {
        return preg_replace_callback(
            '/XIII_MES_PROPORCIONAL_TRIMESTRAL\s*\(\s*([^,]+)\s*,\s*([^)]+)\s*\)/',
            function($matches) {
                $conceptos = trim($matches[1], '"\'');
                $fichaVariable = trim($matches[2]);

                // Obtener employee_id
                $empleadoId = (int)$this->reemplazarVariables($fichaVariable);

                if (!$empleadoId) {
                    return '0';
                }

                // Obtener fecha de liquidación
                $fechaLiquidacion = $this->obtenerFechaLiquidacionEmpleado($empleadoId);

                if (!$fechaLiquidacion) {
                    error_log("No se encontró fecha de liquidación para empleado {$empleadoId}");
                    return '0';
                }

                // Obtener fechas del período correcto
                $periodoInfo = $this->xiiiMesCalculator->determinarPeriodoTrimestral($fechaLiquidacion);

                // Obtener acumulados del período
                $acumulados = $this->obtenerAcumuladosPeriodoTrimestral(
                    $empleadoId,
                    $conceptos,
                    $periodoInfo['fecha_inicio'],
                    $periodoInfo['fecha_fin']
                );

                // Obtener fecha de ingreso
                $fechaIngreso = $this->obtenerFechaIngresoEmpleado($empleadoId);

                // Calcular XIII mes proporcional
                $resultado = $this->xiiiMesCalculator->calcularXIIIMesProporcional(
                    $fechaLiquidacion,
                    $acumulados,
                    $fechaIngreso
                );

                return (string)$resultado['xiii_mes_proporcional'];
            },
            $formula
        );
    }

    /**
     * Obtener días de preaviso reales de la liquidación
     */
    private function getDiasPreaviso(int $terminationId): int
    {
        try {
            $sql = "SELECT notice_period_days FROM employee_terminations WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$terminationId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['notice_period_days'])) {
                return (int)$result['notice_period_days'];
            }

            // Fallback si no se encuentra
            error_log("ADVERTENCIA: No se pudo obtener notice_period_days para termination_id: $terminationId");
            return 30; // Valor por defecto según legislación panameña
        } catch (Exception $e) {
            error_log("ERROR en getDiasPreaviso: " . $e->getMessage());
            return 30; // Valor por defecto en caso de error
        }
    }

    // ========================================
    // FUNCIONES ESPECÍFICAS PARA VACACIONES
    // ========================================

    /**
     * Calcular días de vacaciones ganados por un empleado según legislación panameña
     * Base: 30 días por cada 11 meses trabajados
     *
     * @param int $employeeId ID del empleado
     * @param string|null $fechaReferencia Fecha de referencia para el cálculo (default: hoy)
     * @return float Días de vacaciones ganados
     */
    public function VACATION_DAYS_EARNED(int $employeeId, string $fechaReferencia = null): float
    {
        try {
            if (!$fechaReferencia) {
                $fechaReferencia = date('Y-m-d');
            }

            // Obtener fecha de ingreso
            $sql = "SELECT fecha_ingreso FROM employees WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                return 0;
            }

            $fechaIngreso = $employee['fecha_ingreso'];

            // Calcular meses trabajados
            $mesesTrabajados = (int)$this->calcularMesesTrabajados($fechaIngreso, $fechaReferencia);

            // Legislación panameña: 30 días por cada 11 meses
            $diasPorAno = 30;
            $mesesMinimos = 11;

            if ($mesesTrabajados >= $mesesMinimos) {
                // Calcular años completos trabajados
                $anosCompletos = (int)round(floor($mesesTrabajados / 12), 2);
                $mesesRestantes = (int)round($mesesTrabajados % 12, 2);

                // Días por años completos
                $diasPorAnosCompletos = (int) round($anosCompletos * $diasPorAno);

                // Días proporcionales por meses restantes (si >= 11 meses)
                $diasProporcionales = 0;
                if ($mesesRestantes >= $mesesMinimos) {
                    $diasProporcionales = $diasPorAno;
                }

                return $diasPorAnosCompletos + $diasProporcionales;
            } else {
                // No ha cumplido 11 meses, no tiene derecho a vacaciones completas
                return 0;
            }

        } catch (PDOException $e) {
            error_log("Error calculando días de vacaciones ganados: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular balance actual de vacaciones para un empleado
     *
     * @param int $employeeId ID del empleado
     * @param int|null $year Año para el cálculo (default: año actual)
     * @return float Balance de días de vacaciones
     */
    public function VACATION_BALANCE(int $employeeId, int $year = null): float
    {
        try {
            if (!$year) {
                $year = (int)date('Y');
            }

            // Intentar obtener balance de la tabla vacation_balances
            $sql = "SELECT current_balance FROM vacation_balances
                    WHERE employee_id = ? AND year = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $year]);
            $balance = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($balance) {
                return (float)$balance['current_balance'];
            }

            // Si no existe balance calculado, calcular manualmente
            $diasGanados = $this->VACATION_DAYS_EARNED($employeeId, "$year-12-31");
            $diasTomados = $this->calcularDiasTomadosEnAno($employeeId, $year);
            $diasCompensados = $this->calcularDiasCompensadosEnAno($employeeId, $year);
            $diasAcumuladosAnteriores = $this->obtenerDiasAcumuladosAnteriores($employeeId, $year);

            return $diasGanados + $diasAcumuladosAnteriores - $diasTomados - $diasCompensados;

        } catch (PDOException $e) {
            error_log("Error calculando balance de vacaciones: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular monto de compensación por días de vacaciones
     *
     * @param int $employeeId ID del empleado
     * @param int $dias Número de días a compensar
     * @return float Monto de compensación
     */
    public function VACATION_COMPENSATION_AMOUNT(int $employeeId, int $dias): float
    {
        try {
            // Obtener salario base + gastos de representación
            $sql = "SELECT e.sueldo_individual, e.gastos_representacion, p.sueldo as sueldo_posicion
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    WHERE e.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                return 0;
            }

            // Determinar salario base (priorizar sueldo individual)
            $salarioBase = (float)($employee['sueldo_individual'] ?: $employee['sueldo_posicion'] ?: 0);
            $gastosRep = (float)($employee['gastos_representacion'] ?: 0);

            // Salario mensual para vacaciones incluye gastos de representación
            $salarioMensual = $salarioBase + $gastosRep;

            // Salario diario (30 días por mes según legislación)
            $salarioDiario = $salarioMensual / 30;

            return $salarioDiario * $dias;

        } catch (PDOException $e) {
            error_log("Error calculando compensación de vacaciones: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular tasa de acumulación de vacaciones por mes
     *
     * @param int $employeeId ID del empleado
     * @return float Días de vacaciones que se acumulan por mes
     */
    public function VACATION_ACCRUAL_RATE(int $employeeId): float
    {
        // Legislación panameña: 30 días / 11 meses = 2.727 días por mes
        return 30.0 / 11.0;
    }

    /**
     * Verificar si un empleado tiene derecho a vacaciones
     *
     * @param int $employeeId ID del empleado
     * @param string|null $fechaReferencia Fecha de referencia
     * @return bool True si tiene derecho a vacaciones
     */
    public function VACATION_ELIGIBLE(int $employeeId, string $fechaReferencia = null): bool
    {
        try {
            if (!$fechaReferencia) {
                $fechaReferencia = date('Y-m-d');
            }

            $sql = "SELECT fecha_ingreso FROM employees WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                return false;
            }

            $mesesTrabajados = $this->calcularMesesTrabajados($employee['fecha_ingreso'], $fechaReferencia);

            // Debe tener al menos 11 meses trabajados
            return $mesesTrabajados >= 11;

        } catch (PDOException $e) {
            error_log("Error verificando elegibilidad vacaciones: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular días hábiles entre dos fechas (excluyendo feriados)
     *
     * @param string $fechaInicio Fecha inicio (Y-m-d)
     * @param string $fechaFin Fecha fin (Y-m-d)
     * @return int Número de días hábiles
     */
    public function VACATION_BUSINESS_DAYS(string $fechaInicio, string $fechaFin): int
    {
        try {
            $inicio = new \DateTime($fechaInicio);
            $fin = new \DateTime($fechaFin);
            $diasHabiles = 0;

            // Obtener feriados del período
            $sql = "SELECT date FROM vacation_calendar
                    WHERE date BETWEEN ? AND ? AND day_type IN ('HOLIDAY', 'NON_WORKING')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fechaInicio, $fechaFin]);
            $feriados = $stmt->fetchAll(PDO::FETCH_COLUMN);

            while ($inicio <= $fin) {
                $diaSemana = (int)$inicio->format('w'); // 0=domingo, 6=sábado
                $fechaActual = $inicio->format('Y-m-d');

                // Contar solo días laborables (lunes a viernes) que no sean feriados
                if ($diaSemana >= 1 && $diaSemana <= 5 && !in_array($fechaActual, $feriados)) {
                    $diasHabiles++;
                }

                $inicio->add(new \DateInterval('P1D'));
            }

            return $diasHabiles;

        } catch (\Exception $e) {
            error_log("Error calculando días hábiles: " . $e->getMessage());
            return 0;
        }
    }

    // ========================================
    // MÉTODOS AUXILIARES PARA VACACIONES
    // ========================================

    /**
     * Calcular meses trabajados entre dos fechas
     */
    private function calcularMesesTrabajados(string $fechaIngreso, string $fechaReferencia): float
    {
        $ingreso = new \DateTime($fechaIngreso);
        $referencia = new \DateTime($fechaReferencia);

        $interval = $ingreso->diff($referencia);

        return ($interval->y * 12) + $interval->m + ($interval->d / 30);
    }

    /**
     * Calcular días tomados en un año específico
     */
    private function calcularDiasTomadosEnAno(int $employeeId, int $year): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(dias_solicitados_disfrute), 0) as total_days
                    FROM vacation_requests
                    WHERE employee_id = ?
                    AND YEAR(start_date) = ?
                    AND status = 'APPROVED'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $year]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (float)($result['total_days'] ?? 0);

        } catch (PDOException $e) {
            error_log("Error calculando días tomados: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular días compensados monetariamente en un año
     */
    private function calcularDiasCompensadosEnAno(int $employeeId, int $year): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(dias_solicitados_pagar), 0) as total_days
                    FROM vacation_requests
                    WHERE employee_id = ?
                    AND YEAR(request_date) = ?
                    AND vacation_type in ('COMPENSATION', 'ANNUAL')
                    AND status = 'APPROVED'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $year]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (float)($result['total_days'] ?? 0);

        } catch (PDOException $e) {
            error_log("Error calculando días compensados: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener días acumulados de años anteriores
     */
    private function obtenerDiasAcumuladosAnteriores(int $employeeId, int $year): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(current_balance), 0) as total_accumulated
                    FROM vacation_balances
                    WHERE employee_id = ? AND year < ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $year]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (float)($result['total_accumulated'] ?? 0);

        } catch (PDOException $e) {
            error_log("Error obteniendo días acumulados: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Agregar variables específicas para vacaciones
     */
    public function setVariablesVacaciones(int $employeeId, int $year = null): void
    {
        $this->setVariablesColaborador($employeeId);

        if (!$year) {
            $year = (int)date('Y');
        }

        // Agregar variables específicas de vacaciones
        $this->variablesColaborador['VACATION_DAYS_EARNED'] = $this->VACATION_DAYS_EARNED($employeeId);
        $this->variablesColaborador['VACATION_BALANCE'] = $this->VACATION_BALANCE($employeeId, $year);
        $this->variablesColaborador['VACATION_ACCRUAL_RATE'] = $this->VACATION_ACCRUAL_RATE($employeeId);
        $this->variablesColaborador['VACATION_ELIGIBLE'] = $this->VACATION_ELIGIBLE($employeeId) ? 1 : 0;
        $this->variablesColaborador['VACATION_DAILY_SALARY'] = $this->VACATION_COMPENSATION_AMOUNT($employeeId, 1);
    }

    /**
     * Obtener salario del empleado según tipo de planilla desde la tabla employee_payroll_salaries
     *
     * @param int $employee_id ID del empleado
     * @param int $tipo_planilla_id ID del tipo de planilla
     * @return array|null Array con sueldo_base y gastos_representacion o null si no existe
     */
    private function getSalarioByTipoPlanilla(int $employee_id, int $tipo_planilla_id): ?array
    {
        try {
            $sql = "SELECT sueldo_base, gastos_representacion
                    FROM employee_payroll_salaries
                    WHERE employee_id = ?
                    AND tipo_planilla_id = ?
                    AND is_active = 1
                    AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                    ORDER BY fecha_inicio DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id, $tipo_planilla_id]);
            $salary = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($salary) {
                return [
                    'sueldo_base' => (float)$salary['sueldo_base'],
                    'gastos_representacion' => (float)($salary['gastos_representacion'] ?? 0)
                ];
            }

            return null;

        } catch (PDOException $e) {
            error_log("Error obteniendo salario por tipo planilla: " . $e->getMessage());
            return null;
        }
    }
}