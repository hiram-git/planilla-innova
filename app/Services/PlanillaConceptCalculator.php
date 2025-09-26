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
            
            $sql = "SELECT e.fecha_ingreso, e.employee_id, e.sueldo_individual, e.gastos_representacion, p.sueldo as sueldo_posicion, s.time_in, s.time_out
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

                // Obtener gastos de representación
                $gastos_representacion = (float)($employee['gastos_representacion'] ?: 0);

                $this->variablesColaborador = [
                    'SALARIO' => $salario,
                    'SUELDO' => $salario,
                    'GASTOS_REPRESENTACION' => $gastos_representacion,
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
            // Log para debug de fórmulas problemáticas
            if (strpos($expresion, '500 * ') !== false || preg_match('/\d+\s*[*+\-\/]\s*$/', $expresion)) {
                error_log("DEBUG: Expresión problemática detectada: '$expresion'");
                error_log("DEBUG: Stack trace: " . json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)));
            }

            // Sanitizar y evaluar expresión básica
            $expresionLimpia = preg_replace('/[^0-9+\-*\/\(\)\.\s]/', '', $expresion);

            if (empty($expresionLimpia)) {
                return 0;
            }

            // Validar que la expresión no termine con operadores
            if (preg_match('/[*+\-\/]\s*$/', $expresionLimpia)) {
                error_log("Error: Expresión termina con operador: '$expresionLimpia'");
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

        // Procesar función CONCEPTO(CODIGO_CONCEPTO) - Retorna monto del cálculo del concepto
        $formula = preg_replace_callback('/CONCEPTO\(\s*["\']?([^"\')\s]+)["\']?\s*\)/', function($matches) {
            $codigoConcepto = trim($matches[1]);

            // Buscar concepto por código y evaluar su fórmula
            if (isset($this->conceptos[$codigoConcepto])) {
                $formulaConcepto = $this->conceptos[$codigoConcepto]['formula'];

                // Prevenir recursión infinita
                if (isset($this->evaluando[$codigoConcepto])) {
                    error_log("ADVERTENCIA: Recursión detectada en concepto '$codigoConcepto'");
                    return '0';
                }

                $this->evaluando[$codigoConcepto] = true;
                $resultado = $this->evaluarFormula($formulaConcepto);
                unset($this->evaluando[$codigoConcepto]);

                return (string)$resultado;
            } else {
                error_log("ADVERTENCIA: Concepto '$codigoConcepto' no encontrado en función CONCEPTO()");
                return '0';
            }
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
                WHERE
                    ae.employee_id = ?
                AND pc.fecha_desde >= ?
                AND pc.fecha_hasta <= ?
                AND ae.tipo_acumulado= ?
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

        // Agregar variables específicas de liquidación
        $this->variablesColaborador['ANOS_TRABAJADOS'] = $this->getAnosTrabajados();
        $this->variablesColaborador['SUELDO_SEMANAL'] = $this->calcularSalarioSemanal($employeeId);
        $this->variablesColaborador['SUELDO_MENSUAL'] = $this->calcularSalarioMensual($employeeId);
        $this->variablesColaborador['SUELDO_DIARIO'] = $this->calcularSalarioDiario($employeeId);

        // Obtener días de preaviso reales de la liquidación si existe termination_id
        if ($terminationId) {
            $this->variablesColaborador['DIAS_PREAVISO'] = $this->getDiasPreaviso($terminationId);
        } else {
            $this->variablesColaborador['DIAS_PREAVISO'] = 30; // Fallback por defecto
        }
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
            $mesesTrabajados = $this->calcularMesesTrabajados($fechaIngreso, $fechaReferencia);

            // Legislación panameña: 30 días por cada 11 meses
            $diasPorAno = 30;
            $mesesMinimos = 11;

            if ($mesesTrabajados >= $mesesMinimos) {
                // Calcular años completos trabajados
                $anosCompletos = round(floor($mesesTrabajados / 12), 2);
                $mesesRestantes = round($mesesTrabajados % 12, 2);

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
            $sql = "SELECT COALESCE(SUM(business_days), 0) as total_days
                    FROM vacation_requests
                    WHERE employee_id = ?
                    AND YEAR(start_date) = ?
                    AND status = 'TAKEN'";
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
            $sql = "SELECT COALESCE(SUM(business_days), 0) as total_days
                    FROM vacation_requests
                    WHERE employee_id = ?
                    AND YEAR(request_date) = ?
                    AND vacation_type = 'COMPENSATION'
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
}