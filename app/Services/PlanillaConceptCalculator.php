<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Calculadora de conceptos para planillas
 * Evalúa fórmulas tipo Excel con variables del empleado
 */
class PlanillaConceptCalculator
{
    private $db;
    private array $conceptos = [];
    private array $variablesColaborador = [];
    private array $cacheAcreedores = [];
    private array $evaluando = [];
    private ?float $montoAcreedor = null;
    private array $fechasActuales = [];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->cargarConceptos();
    }

    /**
     * Establecer las fechas de la planilla actual para variables INIPERIODO/FINPERIODO
     */
    public function establecerFechasPlanilla(string $fechaDesde, string $fechaHasta, string $fechaPlanilla = null): void
    {
        $this->fechasActuales = [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'fecha' => $fechaPlanilla ?? $fechaHasta
        ];
    }

    /**
     * Cargar conceptos desde la base de datos
     */
    private function cargarConceptos(): void
    {
        try {
            $sql = "SELECT id, concepto, descripcion, formula FROM concepto";
            $stmt = $this->db->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data = [
                    'id' => $row['id'],
                    'formula' => $row['formula'] ?: '0'
                ];

                // Agregar tanto por concepto como por descripción
                if (!empty($row['concepto'])) {
                    $this->conceptos[$row['concepto']] = $data;
                }
                if (!empty($row['descripcion'])) {
                    $this->conceptos[$row['descripcion']] = $data;
                }
            }
        } catch (PDOException $e) {
            error_log("Error cargando conceptos: " . $e->getMessage());
        }
    }

    /**
     * Establecer variables específicas del colaborador
     */
    public function setVariablesColaborador(int $employee_id): void
    {
        try {
            // Obtener tipo de empresa de la configuración
            $companyType = $this->getCompanyType();
            
            $sql = "SELECT e.fecha_ingreso, e.employee_id, e.sueldo_individual, p.sueldo as sueldo_posicion, s.time_in, s.time_out
                    FROM employees e 
                    LEFT JOIN posiciones p ON p.id = e.position_id 
                    LEFT JOIN schedules s ON s.id = e.schedule_id 
                    WHERE e.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                // Determinar sueldo según tipo de empresa
                $salario = 0;
                if ($companyType === 'publica') {
                    // Empresa pública: sueldo viene de la posición
                    $salario = (float)($employee['sueldo_posicion'] ?: 0);
                } else {
                    // Empresa privada: sueldo individual del empleado
                    $sueldo_individual = (float)($employee['sueldo_individual'] ?: 0);
                    
                    if ($sueldo_individual > 0) {
                        $salario = $sueldo_individual;
                    } else {
                        // Fallback: usar sueldo de posición si no hay sueldo individual
                        $salario = (float)($employee['sueldo_posicion'] ?: 0);
                    }
                }
                
                $ficha = $employee['employee_id'] ?: '0';
                
                // Calcular horas (diferencia en horas * 5 días/semana)
                $time_in = strtotime($employee['time_in']);
                $time_out = strtotime($employee['time_out']);
                $horas_diarias = ($time_out - $time_in) / 3600;
                $horas = $horas_diarias * 5;

                // Calcular antigüedad (días desde fecha_ingreso)
                $fecha_ingreso = new \DateTime($employee['fecha_ingreso']);
                $now = new \DateTime();
                $antiguedad_anual = $fecha_ingreso->diff($now)->y;   // Años
                $antiguedad_mes = $fecha_ingreso->diff($now)->m;
                $antiguedad = $fecha_ingreso->diff($now)->days;

                $this->variablesColaborador = [
                    'SALARIO' => $salario,
                    'SUELDO' => $salario,
                    'FICHA' => $ficha,
                    'EMPLEADO' => $ficha, // Usar employee_id (código) en lugar del ID numérico
                    'EMPLOYEE_ID' => $employee_id, // ID numérico para cálculos internos
                    'HORAS' => (float)$horas,
                    'ANTIGUEDAD_ANUAL' => (float)$antiguedad_anual,
                    'ANTIGUEDAD_MES' => (float)$antiguedad_mes,
                    'ANTIGUEDAD_DIAS' => (float)$antiguedad,
                    'ANTIGUEDAD' => (float)$antiguedad,
                ];
                
            } else {
                error_log("ERROR: No se encontró empleado con ID $employee_id");
            }
        } catch (PDOException $e) {
            error_log("Error estableciendo variables colaborador: " . $e->getMessage());
        }
    }

    /**
     * Obtener variables del colaborador actual
     */
    public function getVariablesColaborador(): array
    {
        return $this->variablesColaborador;
    }

    /**
     * Evaluar fórmula directamente (recibe la fórmula, no el nombre del concepto)
     */
    public function evaluarFormula(string $formula): float
    {
        try {
            // Si es solo un número, devolverlo directamente
            if (is_numeric($formula)) {
                return (float)$formula;
            }

            // Si está vacío, devolver 0
            if (empty(trim($formula))) {
                return 0;
            }

            // Evaluar fórmula simple
            return $this->evaluarFormulaSimple($formula);
            
        } catch (\Exception $e) {
            error_log("Error evaluando fórmula '$formula': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Evaluar fórmula para un concepto específico por nombre
     * Incluye manejo especial para XIII_MES
     */
    public function evaluarFormulaPorConcepto(string $concepto): float
    {
        try {
            if (!isset($this->conceptos[$concepto])) {
                return 0;
            }

            // Manejo especial para XIII_MES (buscar por descripción también)
            if (($concepto === 'XIII_MES' || $concepto === 'Décimo Tercer Mes (XIII Mes)') && isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return $this->calcularXIIIMesConFechasPlanilla();
            }

            $formula = $this->conceptos[$concepto]['formula'];
            return $this->evaluarFormula($formula);

        } catch (\Exception $e) {
            error_log("Error evaluando fórmula para concepto '$concepto': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular XIII mes usando las fechas de la planilla actual (contexto automático)
     * Requiere que se hayan establecido las variables del colaborador
     */
    private function calcularXIIIMesConFechasPlanilla(): float
    {
        try {
            // Obtener el employee_id real desde las variables del colaborador
            if (!isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return 0;
            }

            $employee_id = $this->variablesColaborador['EMPLOYEE_ID'];

            // Para el cálculo automático, usar periodo del año en curso
            $año_actual = date('Y');
            $fecha_inicio_periodo = $año_actual . '-01-01';
            $fecha_fin_periodo = $año_actual . '-12-31';

            return $this->calcularXIIIMes($employee_id, $fecha_inicio_periodo, $fecha_fin_periodo);

        } catch (\Exception $e) {
            error_log("Error calculando XIII mes con fechas de planilla: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Evaluador simple de fórmulas (versión básica sin librerías externas)
     */
    private function evaluarFormulaSimple(string $formula): float
    {
        // Inicializar contexto de variables locales
        $variablesLocales = [];

        // Dividir por líneas ANTES de procesar variables globales
        // Soportar múltiples tipos de separadores
        $lineas = $this->dividirFormulaEnLineas($formula);
        $ultimoResultado = 0;

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            // Verificar si es una asignación (variable = expresión)
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/', $linea, $matches)) {
                $nombreVariable = $matches[1];
                $expresion = trim($matches[2]);

                // Procesar la expresión: reemplazar variables globales y locales
                $expresionProcesada = $this->procesarExpresionConVariables($expresion, $variablesLocales);

                // Evaluar la expresión y almacenar el resultado
                $valor = $this->evaluarExpresionMatematica($expresionProcesada);
                $variablesLocales[$nombreVariable] = $valor;
                $ultimoResultado = $valor;

                // error_log("DEBUG: Asignación '$nombreVariable = $expresion' -> '$expresionProcesada' = $valor");

            } else {
                // Es una expresión final (no asignación)
                $expresionProcesada = $this->procesarExpresionConVariables($linea, $variablesLocales);
                $ultimoResultado = $this->evaluarExpresionMatematica($expresionProcesada);

                // error_log("DEBUG: Expresión final '$linea' -> '$expresionProcesada' = $ultimoResultado");

                return $ultimoResultado;
            }
        }

        // Si solo hubo asignaciones (sin expresión final), retornar el último valor calculado
        return $ultimoResultado;
    }

    /**
     * Procesar una expresión reemplazando variables globales y locales
     */
    private function procesarExpresionConVariables(string $expresion, array $variablesLocales): string
    {
        // 1. Primero reemplazar variables globales (colaborador + especiales)
        $expresionProcesada = $this->reemplazarVariables($expresion);

        // 2. Procesar funciones básicas
        $expresionProcesada = $this->procesarFunciones($expresionProcesada);

        // 3. Finalmente reemplazar variables locales
        foreach ($variablesLocales as $varNombre => $varValor) {
            // Usar regex para reemplazar solo palabras completas
            $expresionProcesada = preg_replace('/\b' . preg_quote($varNombre, '/') . '\b/', (string)$varValor, $expresionProcesada);
        }

        return $expresionProcesada;
    }

    /**
     * Dividir fórmula en líneas manejando diferentes separadores
     */
    private function dividirFormulaEnLineas(string $formula): array
    {
        // Normalizar diferentes tipos de saltos de línea
        $formula = str_replace(["\r\n", "\r"], "\n", $formula);

        // Si no hay saltos de línea, intentar detectar patrones de asignación
        if (strpos($formula, "\n") === false) {
            // Buscar patrones como "variable = expresión" seguido de otra variable o función
            // Usar regex para dividir en asignaciones y expresión final
            $lineas = $this->detectarLineasEnFormulaPlana($formula);
        } else {
            // Usar saltos de línea normales
            $lineas = explode("\n", $formula);
        }

        // Filtrar líneas vacías
        return array_filter(array_map('trim', $lineas), function($linea) {
            return !empty($linea);
        });
    }

    /**
     * Detectar líneas en una fórmula sin saltos de línea
     */
    private function detectarLineasEnFormulaPlana(string $formula): array
    {
        $lineas = [];
        $formula = trim($formula);

        // Estrategia más simple y robusta: usar múltiples pasadas con patrones específicos

        // 1. Primero, dividir usando patrones conocidos específicos
        $patronesDivision = [
            // Caso específico: ANTIGUEDAD_DIASacumulados =
            '/ANTIGUEDAD_DIAS\s*acumulados\s*=/', // Reemplazar con ANTIGUEDAD_DIAS\nacumulados =
            // Paréntesis seguido de función (específicos conocidos)
            '/\)\s*SI\s*\(/',  // )SI( -> )\nSI(
            '/\)\s*ACUMULADOS\s*\(/',  // )ACUMULADOS( -> )\nACUMULADOS(
            // Número seguido de variable minúscula
            '/([0-9]+)\s*([a-z_][a-zA-Z0-9_]*\s*=)/',  // 100b = -> 100\nb =
            // Número seguido de paréntesis de expresión
            '/([0-9]+)\s*(\([a-z_+\-*\/\s]+\))/',  // 200(a + b) -> 200\n(a + b)
        ];

        $textoActual = $formula;

        // Aplicar reemplazos específicos primero
        $textoActual = str_replace('ANTIGUEDAD_DIASacumulados =', "ANTIGUEDAD_DIAS\nacumulados =", $textoActual);
        $textoActual = preg_replace('/\)\s*SI\s*\(/', ")\nSI(", $textoActual);
        $textoActual = preg_replace('/\)\s*ACUMULADOS\s*\(/', ")\nACUMULADOS(", $textoActual);
        $textoActual = preg_replace('/([0-9]+)\s*([a-z_][a-zA-Z0-9_]*\s*=)/', "$1\n$2", $textoActual);
        $textoActual = preg_replace('/([0-9]+)\s*(\([a-z_+\-*\/\s]+\))/', "$1\n$2", $textoActual);

        // Dividir por saltos de línea
        $segmentos = explode("\n", $textoActual);

        foreach ($segmentos as $segmento) {
            $segmento = trim($segmento);
            if (!empty($segmento)) {
                $lineas[] = $segmento;
            }
        }

        // Si no se pudo dividir, retornar la fórmula completa
        if (empty($lineas) || count($lineas) == 1) {
            return [$formula];
        }

        return $lineas;
    }

    /**
     * Evaluar una expresión matemática simple
     */
    private function evaluarExpresionMatematica(string $expresion): float
    {
        try {
            // Sanitizar y evaluar expresión básica
            $expresionLimpia = preg_replace('/[^0-9+\-*\/\(\)\.\s]/', '', $expresion);

            if (empty($expresionLimpia)) {
                return 0;
            }

            // Validar que la expresión no contenga caracteres peligrosos
            if (!preg_match('/^[0-9+\-*\/\(\)\.\s]+$/', $expresionLimpia)) {
                error_log("Expresión contiene caracteres no válidos: $expresionLimpia");
                return 0;
            }

            // Usar eval de forma segura solo para expresiones matemáticas
            try {
                $result = eval("return $expresionLimpia;");
                return (float)$result;
            } catch (\ParseError $e) {
                error_log("Error de sintaxis en expresión '$expresionLimpia': " . $e->getMessage());
                return 0;
            }

        } catch (\Exception $e) {
            error_log("Error en evaluación matemática: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Reemplazar variables en la fórmula
     */
    private function reemplazarVariables(string $formula): string
    {
        $formulaOriginal = $formula;

        // Mapear variables especiales a campos de BD
        $variablesEspeciales = [
            'INIPERIODO' => $this->fechasActuales['fecha_desde'] ?? null,
            'FINPERIODO' => $this->fechasActuales['fecha_hasta'] ?? null,
            'FECHA' => $this->fechasActuales['fecha'] ?? null
        ];

        // Reemplazar variables especiales primero (solo fuera de comillas)
        foreach ($variablesEspeciales as $variable => $valor) {
            if ($valor !== null) {
                $formula = $this->reemplazarVariableFueraDeComillas($formula, $variable, '"' . $valor . '"');
            }
        }

        // Reemplazar variables del colaborador (solo fuera de comillas)
        foreach ($this->variablesColaborador as $variable => $valor) {
            $formula = $this->reemplazarVariableFueraDeComillas($formula, $variable, (string)$valor);
        }

        return $formula;
    }

    /**
     * Reemplaza una variable solo si NO está dentro de comillas dobles
     */
    private function reemplazarVariableFueraDeComillas(string $formula, string $variable, string $valor): string
    {
        // Usar regex para encontrar el patrón pero solo fuera de comillas
        // Esto es más simple: dividir por comillas y procesar segmentos alternos

        $partes = explode('"', $formula);
        $resultado = '';

        for ($i = 0; $i < count($partes); $i++) {
            if ($i % 2 === 0) {
                // Índices pares = fuera de comillas, SÍ reemplazar
                $partes[$i] = str_replace($variable, $valor, $partes[$i]);
            }
            // Índices impares = dentro de comillas, NO reemplazar

            $resultado .= $partes[$i];

            // Agregar comillas de vuelta, excepto en el último segmento
            if ($i < count($partes) - 1) {
                $resultado .= '"';
            }
        }

        return $resultado;
    }

    /**
     * Procesar funciones básicas como SI(), ACREEDOR()
     */
    private function procesarFunciones(string $formula): string
    {
        // Procesar función SI(condicion, valor_si_verdadero, valor_si_falso)
        $formula = preg_replace_callback('/SI\(([^,]+),([^,]+),([^)]+)\)/', function($matches) {
            $condicion = trim($matches[1]);
            $valorVerdadero = trim($matches[2]);
            $valorFalso = trim($matches[3]);
            
            // Evaluar condición simple
            $condicionResult = $this->evaluarCondicion($condicion);
            return $condicionResult ? $valorVerdadero : $valorFalso;
        }, $formula);

        // Procesar función ACREEDOR(FICHA, id_deduction)
        $formula = preg_replace_callback('/ACREEDOR\(([^,]+),([^)]+)\)/', function($matches) {
            $fichaVariable = trim($matches[1]);
            $idDeduction = trim($matches[2]);

            // CRÍTICO: Resolver la variable antes de usarla
            $fichaValue = $this->reemplazarVariables($fichaVariable);


            return (string)$this->calcularMontoAcreedor($fichaValue, (int)$idDeduction);
        }, $formula);

        // Procesar función XIII_MES() - para uso en fórmulas
        $formula = preg_replace_callback('/XIII_MES\(\)/', function($matches) {
            if (isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return (string)$this->calcularXIIIMesConFechasPlanilla();
            }
            return '0';
        }, $formula);

        // Procesar función ANTIGUEDAD(FICHA, FECHA_FINAL, TIPO)
        $formula = preg_replace_callback('/ANTIGUEDAD\(([^,]+),([^,]+),([^)]+)\)/', function($matches) {
            $fichaVariable = trim($matches[1]);
            $fechaFinal = trim($matches[2], '"\'');
            $tipo = trim($matches[3], '"\'');

            // Resolver variables
            $fichaValue = $this->reemplazarVariables($fichaVariable);

            return (string)$this->calcularAntiguedad($fichaValue, $fechaFinal, $tipo);
        }, $formula);

        // Procesar función ACUMULADOS(CONCEPTOS, FICHA, FECHA_INICIO, FECHA_FIN)
        // Ahora soporta múltiples conceptos separados por comas: "SALARIO,HORAS_EXTRAS,COMISIONES"
        $formula = preg_replace_callback('/ACUMULADOS\(("(?:[^"\\\\]|\\\\.)*"|[^,]+),\s*([^,]+),\s*([^,]+),\s*([^)]+)\)/', function($matches) {
            $conceptos = trim($matches[1], '"\'');
            $fichaVariable = trim($matches[2]);
            $fechaInicio = trim($matches[3], '"\'');
            $fechaFin = trim($matches[4], '"\'');
            // Resolver variables
            $fichaValue = $this->reemplazarVariables($fichaVariable);
            $fechaInicio = $this->reemplazarVariables($fechaInicio);
            $fechaFin = $this->reemplazarVariables($fechaFin);
            // Resolver fechas directamente con los valores de la planilla
            $fechaInicioValue = $fechaInicio;
            $fechaFinValue = $fechaFin;

            // Reemplazar variables especiales de fecha
            if ($fechaInicio === 'INIPERIODO' && isset($this->fechasActuales['fecha_desde'])) {
                $fechaInicioValue = $this->fechasActuales['fecha_desde'];
            } elseif ($fechaInicio === 'INIPERIODO') {
                $fechaInicioValue = date('Y-01-01');
            }

            if ($fechaFin === 'FINPERIODO' && isset($this->fechasActuales['fecha_hasta'])) {
                $fechaFinValue = $this->fechasActuales['fecha_hasta'];
            } elseif ($fechaFin === 'FINPERIODO') {
                $fechaFinValue = date('Y-12-31');
            }

            // Limpiar comillas de las fechas
            $fechaInicioValue = trim($fechaInicioValue, '"\'');
            $fechaFinValue = trim($fechaFinValue, '"\'');

            // Dividir conceptos si hay múltiples separados por comas
            $listaConceptos = array_map('trim', explode(',', $conceptos));

            $totalAcumulado = 0;
            foreach ($listaConceptos as $concepto) {
                $totalAcumulado += $this->calcularAcumulados($concepto, $fichaValue, $fechaInicioValue, $fechaFinValue);
            }

            return (string)$totalAcumulado;
        }, $formula);

        return $formula;
    }

    /**
     * Evaluar condición simple
     */
    private function evaluarCondicion(string $condicion): bool
    {
        // Reemplazar variables
        $condicion = $this->reemplazarVariables($condicion);
        
        // Evaluar condiciones básicas
        if (preg_match('/([0-9.]+)\s*([><=]+)\s*([0-9.]+)/', $condicion, $matches)) {
            $valor1 = (float)$matches[1];
            $operador = $matches[2];
            $valor2 = (float)$matches[3];
            
            switch ($operador) {
                case '>': return $valor1 > $valor2;
                case '<': return $valor1 < $valor2;
                case '>=': return $valor1 >= $valor2;
                case '<=': return $valor1 <= $valor2;
                case '==': return $valor1 == $valor2;
                default: return false;
            }
        }
        
        return false;
    }

    /**
     * Calcular monto de acreedor desde tabla deductions
     */
    private function calcularMontoAcreedor(string $employeeId, int $idAcreedor): float
    {
        $cacheKey = "$employeeId:$idAcreedor";
        
        if (!isset($this->cacheAcreedores[$cacheKey])) {
            try {
                // CRÍTICO: Buscar por el employee_id (código del empleado)
                // La tabla deductions guarda employee_id como el código del empleado (ej: 'RVP280963')
                $sql = "SELECT d.amount as monto 
                        FROM deductions d 
                        WHERE d.employee_id = ? AND d.creditor_id = ? ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$employeeId, $idAcreedor]);
                $deduction = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $monto = $deduction ? (float)$deduction['monto'] : 0;
                $this->cacheAcreedores[$cacheKey] = $monto;
                $this->montoAcreedor = $monto;
                
                
            } catch (PDOException $e) {
                error_log("Error calculando monto acreedor: " . $e->getMessage());
                $this->cacheAcreedores[$cacheKey] = 0;
            }
        }
        
        return $this->cacheAcreedores[$cacheKey];
    }

    /**
     * Obtener todos los conceptos cargados
     */
    public function getConceptos(): array
    {
        return $this->conceptos;
    }

    /**
     * Calcular XIII mes (décimo tercer mes) para un empleado en un periodo específico
     *
     * @param int $employee_id ID del empleado
     * @param string $fecha_inicio_periodo Fecha inicio del periodo (Y-m-d)
     * @param string $fecha_fin_periodo Fecha fin del periodo (Y-m-d)
     * @return float Monto del XIII mes calculado
     */
    public function calcularXIIIMes(int $employee_id, string $fecha_inicio_periodo, string $fecha_fin_periodo): float
    {
        try {
            // 1. Obtener fecha de ingreso del empleado
            $sql = "SELECT fecha_ingreso FROM employees WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                error_log("Empleado con ID $employee_id no encontrado");
                return 0;
            }

            $fecha_ingreso = $employee['fecha_ingreso'];

            // 2. Calcular días trabajados en el periodo
            $dias_trabajados = $this->calcularDiasTrabajados($fecha_ingreso, $fecha_inicio_periodo, $fecha_fin_periodo);

            // 3. Calcular total de salarios en el periodo
            $total_salarios = $this->calcularTotalSalariosEnPeriodo($employee_id, $fecha_inicio_periodo, $fecha_fin_periodo);

            // 4. Aplicar lógica del XIII mes
            if ($dias_trabajados >= 122) {
                // Trabajó todo el periodo mínimo requerido
                $xiii_mes = $total_salarios / 12;
            } else {
                // Trabajó menos del periodo mínimo, calcular proporción
                $proporcion = $dias_trabajados / 122;
                $xiii_mes = ($total_salarios / 12) * $proporcion;
            }

            return round($xiii_mes, 2);

        } catch (PDOException $e) {
            error_log("Error calculando XIII mes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular días trabajados dentro del periodo considerando fecha de ingreso
     *
     * @param string $fecha_ingreso Fecha de ingreso del empleado (Y-m-d)
     * @param string $fecha_inicio_periodo Fecha inicio del periodo (Y-m-d)
     * @param string $fecha_fin_periodo Fecha fin del periodo (Y-m-d)
     * @return int Número de días trabajados
     */
    private function calcularDiasTrabajados(string $fecha_ingreso, string $fecha_inicio_periodo, string $fecha_fin_periodo): int
    {
        $fecha_ingreso_dt = new \DateTime($fecha_ingreso);
        $fecha_inicio_dt = new \DateTime($fecha_inicio_periodo);
        $fecha_fin_dt = new \DateTime($fecha_fin_periodo);

        // Determinar fecha de inicio real para el cálculo
        if ($fecha_ingreso_dt <= $fecha_inicio_dt) {
            // El empleado ingresó antes del periodo, contar desde el inicio del periodo
            $fecha_inicio_calculo = $fecha_inicio_dt;
        } else {
            // El empleado ingresó durante el periodo, contar desde su fecha de ingreso
            $fecha_inicio_calculo = $fecha_ingreso_dt;
        }

        // Calcular diferencia en días
        $diferencia = $fecha_inicio_calculo->diff($fecha_fin_dt);
        return $diferencia->days + 1; // +1 para incluir el día final
    }

    /**
     * Calcular total de salarios en el periodo
     * Suma: salario_base + horas_extras + comisiones + bonificaciones
     *
     * @param int $employee_id ID del empleado
     * @param string $fecha_inicio_periodo Fecha inicio del periodo (Y-m-d)
     * @param string $fecha_fin_periodo Fecha fin del periodo (Y-m-d)
     * @return float Total de salarios en el periodo
     */
    private function calcularTotalSalariosEnPeriodo(int $employee_id, string $fecha_inicio_periodo, string $fecha_fin_periodo): float
    {
        try {
            // Consulta para obtener todos los conceptos de tipo INGRESO en el periodo
            $sql = "
                SELECT SUM(pd.monto) as total_salarios
                FROM planilla_detalle pd
                INNER JOIN planilla_cabecera pc ON pd.planilla_cabecera_id = pc.id
                INNER JOIN concepto c ON pd.concepto_id = c.id
                WHERE pd.employee_id = ?
                AND pc.fecha >= ?
                AND pc.fecha <= ?
                AND pd.tipo = 'A'
                AND c.tipo_concepto IN ('INGRESO', 'A')
                AND c.concepto NOT IN ('XIII_MES', 'PRIMA_ANTIGUEDAD') -- Excluir XIII mes y prima de antigüedad
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id, $fecha_inicio_periodo, $fecha_fin_periodo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $total = (float)($result['total_salarios'] ?? 0);

            // Si no hay registros en planilla_detalle, usar el salario base del empleado
            if ($total == 0) {
                $total = $this->obtenerSalarioBaseEmpleado($employee_id);
            }

            return $total;

        } catch (PDOException $e) {
            error_log("Error calculando total salarios en periodo: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener salario base del empleado (fallback cuando no hay datos en planilla)
     *
     * @param int $employee_id ID del empleado
     * @return float Salario base del empleado
     */
    private function obtenerSalarioBaseEmpleado(int $employee_id): float
    {
        try {
            $companyType = $this->getCompanyType();

            $sql = "SELECT e.sueldo_individual, p.sueldo as sueldo_posicion
                    FROM employees e
                    LEFT JOIN posiciones p ON p.id = e.position_id
                    WHERE e.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                if ($companyType === 'publica') {
                    return (float)($employee['sueldo_posicion'] ?? 0);
                } else {
                    $sueldo_individual = (float)($employee['sueldo_individual'] ?? 0);
                    return $sueldo_individual > 0 ? $sueldo_individual : (float)($employee['sueldo_posicion'] ?? 0);
                }
            }

            return 0;
        } catch (PDOException $e) {
            error_log("Error obteniendo salario base empleado: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular antigüedad de un empleado
     *
     * @param string $employeeCode Código del empleado
     * @param string $fechaFinal Fecha final para el cálculo o 'FINPERIODO'
     * @param string $tipo Tipo de cálculo: 'D' (días), 'M' (meses), 'A' (años)
     * @return float Antigüedad calculada
     */
    private function calcularAntiguedad(string $employeeCode, string $fechaFinal, string $tipo): float
    {
        try {
            // Obtener fecha de ingreso del empleado por su código
            $sql = "SELECT fecha_ingreso FROM employees WHERE employee_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeCode]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                return 0;
            }

            $fechaIngreso = new \DateTime($employee['fecha_ingreso']);

            // Determinar fecha final - usar fechas de planilla actual si están disponibles
            if ($fechaFinal === 'FINPERIODO' && isset($this->fechasActuales['fecha_hasta'])) {
                $fechaFin = new \DateTime($this->fechasActuales['fecha_hasta']);
            } elseif ($fechaFinal === 'FINPERIODO') {
                $fechaFin = new \DateTime(date('Y-12-31')); // Fallback al fin del año actual
            } else {
                $fechaFin = new \DateTime($fechaFinal);
            }

            // Si la fecha de ingreso es posterior a la fecha final, retornar 0
            if ($fechaIngreso > $fechaFin) {
                return 0;
            }

            // Calcular diferencia
            $diferencia = $fechaIngreso->diff($fechaFin);

            switch (strtoupper($tipo)) {
                case 'D': // Días
                    return $diferencia->days + 1; // +1 para incluir el día final
                case 'M': // Meses
                    return ($diferencia->y * 12) + $diferencia->m;
                case 'A': // Años
                    return $diferencia->y;
                default:
                    return $diferencia->days + 1;
            }

        } catch (\Exception $e) {
            error_log("Error calculando antigüedad: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular acumulados de un concepto específico en un periodo
     * Busca directamente por el campo 'concepto' de la tabla concepto
     *
     * @param string $concepto Nombre del concepto (ej: 'SALARIO', 'HORAS_EXTRAS')
     * @param string $employeeCode Código del empleado (employee_id)
     * @param string $fechaInicio Fecha inicio del periodo
     * @param string $fechaFin Fecha fin del periodo
     * @return float Total acumulado del concepto
     */
    private function calcularAcumulados(string $concepto, string $employeeCode, string $fechaInicio, string $fechaFin): float
    {
        try {
            // Usar las fechas de la planilla actual si están disponibles
            if ($fechaInicio === 'INIPERIODO' && isset($this->fechasActuales['fecha_desde'])) {
                $fechaInicio = $this->fechasActuales['fecha_desde'];
            } elseif ($fechaInicio === 'INIPERIODO') {
                $fechaInicio = date('Y-01-01'); // Fallback al inicio del año
            }

            if ($fechaFin === 'FINPERIODO' && isset($this->fechasActuales['fecha_hasta'])) {
                $fechaFin = $this->fechasActuales['fecha_hasta'];
            } elseif ($fechaFin === 'FINPERIODO') {
                $fechaFin = date('Y-12-31'); // Fallback al fin del año
            }

            // Obtener employee_id real de la tabla employees
            $sql = "SELECT id FROM employees WHERE employee_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeCode]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                error_log("Empleado no encontrado con employee_id: $employeeCode");
                return 0;
            }

            $employeeId = "{$employee['id']}";

            // Buscar directamente por el campo 'concepto' de la tabla concepto
            $sql = "
                SELECT COALESCE
                    ( SUM( ae.monto ), 0 ) AS total 
                FROM
                    acumulados_por_empleado ae
                    INNER JOIN planilla_cabecera pc ON ae.planilla_id = pc.id
                    INNER JOIN conceptos_acumulados ca ON ca.concepto_id = ae.concepto_id
                    INNER JOIN tipos_acumulados ta ON ca.tipo_acumulado_id = ta.id 
                WHERE
                    ae.employee_id = ?
                AND pc.fecha_desde >= ?
                AND pc.fecha_hasta <= ?
                AND ta.codigo = ?
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $fechaInicio, $fechaFin, $concepto]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $total = (float)($result['total'] ?? 0);

            // Log para debugging
            error_log("Acumulados para concepto '$concepto', empleado '$employeeCode': $total (periodo: $fechaInicio a $fechaFin)");

            return $total;

        } catch (\Exception $e) {
            error_log("Error calculando acumulados para concepto '$concepto': " . $e->getMessage());
            return 0;
        }
    }

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
}