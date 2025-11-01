<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use PDOException;
use NXP\MathExecutor;
use NXP\Exception\MathExecutorException;

/**
 * 🔒 CALCULADORA SEGURA DE CONCEPTOS PARA PLANILLAS
 *
 * Versión híbrida que combina:
 * - Seguridad del legacy (MathExecutor + validaciones)
 * - Funcionalidades avanzadas del actual (multilínea + ACUMULADOS + fechas dinámicas)
 *
 * ⚠️ PROHIBIDO: Usar eval() bajo cualquier circunstancia
 * ✅ OBLIGATORIO: Solo usar nxp/math-executor para evaluación
 */
class PlanillaConceptCalculatorSecure
{
    protected MathExecutor $executor;
    protected PDO $db;
    protected array $conceptos = [];
    protected array $variablesColaborador = [];
    protected array $cacheAcreedores = [];
    protected array $evaluando = [];
    protected ?float $montoAcreedor = null;
    protected array $fechasActuales = [];
    protected ?array $attendanceSummaryCache = null;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->executor = new MathExecutor();
        $this->configurarSistemaSeguro();
        $this->cargarConceptos();
    }

    /**
     * Configurar sistema seguro completo
     */
    protected function configurarSistemaSeguro(): void
    {
        $this->configurarValidacionesEstritas();
        $this->configurarFuncionesPersonalizadas();
        $this->configurarManejadorVariables();
    }

    /**
     * Configurar validaciones estrictas de variables
     */
    protected function configurarValidacionesEstritas(): void
    {
        $this->executor->setVarValidationHandler(function (string $nombre, $valor) {
            // Variables especiales que pueden ser strings
            $variablesEspecialesString = [
                'FICHA',
                'EMPLEADO',  // Alias de FICHA (código del empleado)
                'CLAVE_SS',
                'CLAVE_SEGURO_SOCIAL',  // Cédula o número de seguro social
                'INIPERIODO',
                'FINPERIODO',
                'FECHA'
            ];

            if (!in_array($nombre, $variablesEspecialesString) && !is_numeric($valor)) {
                throw new MathExecutorException("La variable '$nombre' debe ser numérica, recibido: " . gettype($valor));
            }

            // Validar rangos para variables críticas
            if (is_numeric($valor)) {
                $valorFloat = (float)$valor;
                if ($nombre === 'SUELDO' && $valorFloat < 0) {
                    throw new MathExecutorException("SUELDO no puede ser negativo");
                }
                if ($nombre === 'ANTIGUEDAD' && $valorFloat < 0) {
                    throw new MathExecutorException("ANTIGUEDAD no puede ser negativa");
                }
            }
        });
    }

    /**
     * Configurar funciones personalizadas seguras
     */
    protected function configurarFuncionesPersonalizadas(): void
    {
        // Función SI condicional
        $this->executor->addFunction('SI', function ($condicion, $valorSiVerdadero, $valorSiFalso) {
            return $condicion ? $valorSiVerdadero : $valorSiFalso;
        }, 3);

        // Funciones matemáticas Excel
        $this->executor->addFunction('SUMA', function (...$args) {
            return array_sum($args);
        });

        $this->executor->addFunction('PROMEDIO', function (...$args) {
            if (empty($args)) return 0;
            return array_sum($args) / count($args);
        });

        $this->executor->addFunction('MIN', function (...$args) {
            if (empty($args)) return 0;
            return min($args);
        });

        $this->executor->addFunction('MAX', function (...$args) {
            if (empty($args)) return 0;
            return max($args);
        });

        // Función ACREEDOR segura
        $this->executor->addFunction('ACREEDOR', function ($empleado, $id_deduction) {
            return $this->calcularMontoAcreedorSeguro($empleado, $id_deduction);
        }, 2);

        // Función ACUMULADOS avanzada y segura
        $this->executor->addFunction('ACUMULADOS', function ($conceptos, $fechaDesde, $fechaHasta) {
            return $this->calcularAcumuladosSeguro($conceptos, $fechaDesde, $fechaHasta);
        }, 3);

        // Función para obtener días entre fechas
        $this->executor->addFunction('DIAS', function ($fechaInicio, $fechaFin) {
            try {
                $inicio = new \DateTime($fechaInicio);
                $fin = new \DateTime($fechaFin);
                return $inicio->diff($fin)->days;
            } catch (\Exception $e) {
                throw new MathExecutorException("Error calculando días: " . $e->getMessage());
            }
        }, 2);

        // ⏰ FUNCIONES DE ASISTENCIAS (Módulo de Marcaciones)
        // Retornan 0 si no hay datos de asistencias (opcional)

        // Horas trabajadas regulares
        $this->executor->addFunction('HORAS_TRABAJADAS', function () {
            return $this->obtenerDatoAsistencia('total_hours_worked');
        }, 0);

        // Horas regulares (sin extras)
        $this->executor->addFunction('HORAS_REGULARES', function () {
            return $this->obtenerDatoAsistencia('regular_hours');
        }, 0);

        // Horas extras al 25%
        $this->executor->addFunction('HORAS_EXTRAS_25', function () {
            return $this->obtenerDatoAsistencia('overtime_hours_25');
        }, 0);

        // Horas extras al 50%
        $this->executor->addFunction('HORAS_EXTRAS_50', function () {
            return $this->obtenerDatoAsistencia('overtime_hours_50');
        }, 0);

        // Total horas extras (25% + 50%)
        $this->executor->addFunction('HORAS_EXTRAS', function () {
            $h25 = $this->obtenerDatoAsistencia('overtime_hours_25');
            $h50 = $this->obtenerDatoAsistencia('overtime_hours_50');
            return $h25 + $h50;
        }, 0);

        // Horas nocturnas (6PM-6AM)
        $this->executor->addFunction('HORAS_NOCTURNAS', function () {
            return $this->obtenerDatoAsistencia('night_hours');
        }, 0);

        // Horas en días feriados
        $this->executor->addFunction('HORAS_FERIADOS', function () {
            return $this->obtenerDatoAsistencia('holiday_hours');
        }, 0);

        // Horas en domingos
        $this->executor->addFunction('HORAS_DOMINICALES', function () {
            return $this->obtenerDatoAsistencia('sunday_hours');
        }, 0);

        // Minutos de tardanzas
        $this->executor->addFunction('TARDANZAS', function () {
            return $this->obtenerDatoAsistencia('total_tardiness_minutes');
        }, 0);

        // Cantidad de tardanzas
        $this->executor->addFunction('CANTIDAD_TARDANZAS', function () {
            return $this->obtenerDatoAsistencia('tardiness_count');
        }, 0);

        // Ausencias injustificadas (días)
        $this->executor->addFunction('AUSENCIAS', function () {
            return $this->obtenerDatoAsistencia('unjustified_absences');
        }, 0);

        // Total ausencias (justificadas + injustificadas)
        $this->executor->addFunction('TOTAL_AUSENCIAS', function () {
            return $this->obtenerDatoAsistencia('total_absences');
        }, 0);

        // Ausencias justificadas
        $this->executor->addFunction('AUSENCIAS_JUSTIFICADAS', function () {
            return $this->obtenerDatoAsistencia('justified_absences');
        }, 0);

        // Score de puntualidad (0-100)
        $this->executor->addFunction('SCORE_PUNTUALIDAD', function () {
            return $this->obtenerDatoAsistencia('punctuality_score');
        }, 0);

        // Días con asistencia perfecta
        $this->executor->addFunction('DIAS_ASISTENCIA_PERFECTA', function () {
            return $this->obtenerDatoAsistencia('perfect_attendance_days');
        }, 0);

        // Días trabajados
        $this->executor->addFunction('DIAS_TRABAJADOS', function () {
            return $this->obtenerDatoAsistencia('total_days_worked');
        }, 0);
    }

    /**
     * Configurar manejador de variables no encontradas
     */
    protected function configurarManejadorVariables(): void
    {
        $this->executor->setVarNotFoundHandler(function (string $nombre) {
            // Variables del colaborador
            if (isset($this->variablesColaborador[$nombre])) {
                return $this->variablesColaborador[$nombre];
            }

            // Variable monto del acreedor
            if ($nombre === 'monto' && $this->montoAcreedor !== null) {
                return $this->montoAcreedor;
            }

            // Conceptos como variables
            if (isset($this->conceptos[$nombre])) {
                return $this->evaluarConceptoSeguro($nombre);
            }

            throw new MathExecutorException("Variable o concepto '$nombre' no encontrado");
        });
    }

    /**
     * 📅 Establecer fechas de planilla para variables dinámicas
     */
    public function establecerFechasPlanilla(string $fechaDesde, string $fechaHasta, string $fechaPlanilla = null): void
    {
        // Validar formato de fechas
        if (!$this->validarFormatoFecha($fechaDesde) || !$this->validarFormatoFecha($fechaHasta)) {
            throw new MathExecutorException('Formato de fecha inválido. Use YYYY-MM-DD');
        }

        $this->fechasActuales = [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'fecha' => $fechaPlanilla ?? $fechaHasta
        ];

        // Establecer variables de fecha en el executor
        $this->executor->setVar('INIPERIODO', $fechaDesde);
        $this->executor->setVar('FINPERIODO', $fechaHasta);
        $this->executor->setVar('FECHA', $fechaPlanilla ?? $fechaHasta);
    }

    /**
     * Validar formato de fecha
     */
    protected function validarFormatoFecha(string $fecha): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) &&
               \DateTime::createFromFormat('Y-m-d', $fecha) !== false;
    }

    /**
     * Cargar conceptos desde la base de datos
     */
    protected function cargarConceptos(): void
    {
        try {
            $sql = "SELECT id, concepto, descripcion, formula FROM concepto";
            $stmt = $this->db->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data = [
                    'id' => $row['id'],
                    'concepto' => $row['concepto'],
                    'formula' => $row['formula'] ?: '0'
                ];

                // Usar descripción como clave principal, concepto como alternativa
                $this->conceptos[$row['descripcion']] = $data;
                if (!empty($row['concepto'])) {
                    $this->conceptos[$row['concepto']] = $data;
                }
            }
        } catch (PDOException $e) {
            error_log("Error cargando conceptos: " . $e->getMessage());
        }
    }

    /**
     * 👤 Establecer variables del colaborador
     */
    public function setVariablesColaborador(int $employeeId): void
    {
        try {
            // Limpiar caché de asistencias al cambiar de empleado
            $this->limpiarCacheAsistencias();

            $sql = "SELECT e.id, e.employee_id, e.firstname, e.lastname, e.created_on,
                           p.sueldo,
                           s.time_in, s.time_out
                    FROM employees e
                    LEFT JOIN posiciones p ON p.id = e.position_id
                    LEFT JOIN schedules s ON s.id = e.schedule_id
                    WHERE e.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                throw new MathExecutorException("Empleado con ID $employeeId no encontrado");
            }

            // Calcular variables del empleado de forma segura
            $sueldo = (float)($employee['sueldo'] ?? 0);
            $gastosRep = 0; // No existe gastos_representacion en la tabla actual
            $ficha = $employee['employee_id'] ?? '';

            // Calcular horas de trabajo
            $horas = $this->calcularHorasTrabajo($employee['time_in'], $employee['time_out']);

            // Calcular antigüedad
            $antiguedadDias = $this->calcularAntiguedad($employee['created_on']);

            // Establecer variables en el executor con validación
            $this->executor->setVar('SUELDO', $sueldo);
            $this->executor->setVar('SALARIO', $sueldo); // Alias
            $this->executor->setVar('GASTOS_REP', $gastosRep);
            $this->executor->setVar('FICHA', $ficha);
            $this->executor->setVar('EMPLOYEE_ID', $employeeId);
            $this->executor->setVar('HORAS', $horas);
            $this->executor->setVar('ANTIGUEDAD', (float)($antiguedadDias / 365)); // Años
            $this->executor->setVar('ANTIGUEDAD_DIAS', (float)$antiguedadDias);

            // Guardar para referencia interna
            $this->variablesColaborador = [
                'SUELDO' => $sueldo,
                'SALARIO' => $sueldo,
                'GASTOS_REP' => $gastosRep,
                'FICHA' => $ficha,
                'EMPLOYEE_ID' => $employeeId,
                'HORAS' => $horas,
                'ANTIGUEDAD' => (float)($antiguedadDias / 365),
                'ANTIGUEDAD_DIAS' => (float)$antiguedadDias
            ];

        } catch (PDOException $e) {
            throw new MathExecutorException("Error estableciendo variables del colaborador: " . $e->getMessage());
        }
    }

    /**
     * Calcular horas de trabajo por semana
     */
    protected function calcularHorasTrabajo(?string $timeIn, ?string $timeOut): float
    {
        if (!$timeIn || !$timeOut) return 40.0; // Default 40 horas

        try {
            $inicio = strtotime($timeIn);
            $fin = strtotime($timeOut);
            $horasDiarias = ($fin - $inicio) / 3600;
            return max(0, $horasDiarias * 5); // 5 días a la semana
        } catch (\Exception $e) {
            return 40.0;
        }
    }

    /**
     * Calcular antigüedad en días
     */
    protected function calcularAntiguedad(?string $createdOn): int
    {
        if (!$createdOn) return 0;

        try {
            $inicio = new \DateTime($createdOn);
            $ahora = new \DateTime();
            return $inicio->diff($ahora)->days;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtener variables del colaborador
     */
    public function getVariablesColaborador(): array
    {
        return $this->variablesColaborador;
    }

    /**
     * 🧮 Evaluar fórmula directamente (SEGURO)
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

            // Verificar si es fórmula multilínea
            if ($this->esFormulaMultilinea($formula)) {
                return $this->evaluarFormulaMultilinea($formula);
            }

            // Evaluar fórmula simple con MathExecutor
            return (float)$this->executor->execute($formula);

        } catch (MathExecutorException $e) {
            error_log("Error MathExecutor en fórmula '$formula': " . $e->getMessage());
            return 0;
        } catch (\Exception $e) {
            error_log("Error general evaluando fórmula '$formula': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verificar si es fórmula multilínea
     */
    protected function esFormulaMultilinea(string $formula): bool
    {
        return strpos($formula, "\n") !== false ||
               preg_match('/[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*/', $formula);
    }

    /**
     * 📝 Evaluar fórmula multilínea (SEGURO)
     */
    protected function evaluarFormulaMultilinea(string $formula): float
    {
        $lineas = $this->dividirFormulaEnLineas($formula);
        $ultimoResultado = 0;
        $variablesLocales = [];

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            // Verificar si es una asignación
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/', $linea, $matches)) {
                $nombreVariable = $matches[1];
                $expresion = trim($matches[2]);

                // Validar nombre de variable
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $nombreVariable)) {
                    throw new MathExecutorException("Nombre de variable inválido: $nombreVariable");
                }

                // Evaluar expresión de forma segura
                $valor = (float)$this->executor->execute($expresion);

                // Establecer variable local en el executor
                $this->executor->setVar($nombreVariable, $valor);
                $variablesLocales[$nombreVariable] = $valor;
                $ultimoResultado = $valor;

            } else {
                // Es una expresión final
                $ultimoResultado = (float)$this->executor->execute($linea);
                return $ultimoResultado;
            }
        }

        return $ultimoResultado;
    }

    /**
     * Dividir fórmula en líneas
     */
    protected function dividirFormulaEnLineas(string $formula): array
    {
        // Normalizar saltos de línea
        $formula = str_replace(["\r\n", "\r"], "\n", $formula);

        $lineas = explode("\n", $formula);

        return array_filter(array_map('trim', $lineas), function($linea) {
            return !empty($linea);
        });
    }

    /**
     * 📊 Evaluar fórmula por concepto
     */
    public function evaluarFormulaPorConcepto(string $concepto): float
    {
        try {
            if (!isset($this->conceptos[$concepto])) {
                return 0;
            }

            // Manejo especial para XIII_MES
            if (($concepto === 'XIII_MES' || $concepto === 'Décimo Tercer Mes (XIII Mes)') &&
                isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return $this->calcularXIIIMesConFechasPlanilla();
            }

            $conceptoData = $this->conceptos[$concepto];
            $formula = $conceptoData['formula'];

            return $this->evaluarFormula($formula);

        } catch (\Exception $e) {
            error_log("Error evaluando fórmula para concepto '$concepto': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Evaluar concepto de forma segura con prevención de ciclos
     */
    protected function evaluarConceptoSeguro(string $nombre): float
    {
        if (!isset($this->conceptos[$nombre])) {
            throw new MathExecutorException("Concepto '$nombre' no definido");
        }

        // Prevenir dependencias cíclicas
        if (in_array($nombre, $this->evaluando)) {
            throw new MathExecutorException("Dependencia cíclica detectada en '$nombre'");
        }

        $this->evaluando[] = $nombre;

        try {
            $conceptoData = $this->conceptos[$nombre];
            $formula = $conceptoData['formula'];

            if (is_numeric($formula)) {
                $result = (float)$formula;
            } else {
                $result = $this->evaluarFormula($formula);
            }

            array_pop($this->evaluando);
            return $result;

        } catch (\Exception $e) {
            array_pop($this->evaluando);
            throw $e;
        }
    }

    /**
     * 💰 Calcular monto acreedor de forma segura
     */
    protected function calcularMontoAcreedorSeguro($empleado, int $idDeduction): float
    {
        try {
            // Validar parámetros
            if (!is_numeric($empleado) && !is_string($empleado)) {
                throw new MathExecutorException("EMPLEADO debe ser numérico o string");
            }

            if ($idDeduction <= 0) {
                throw new MathExecutorException("ID de deducción debe ser positivo");
            }

            $cacheKey = "$empleado:$idDeduction";

            if (!isset($this->cacheAcreedores[$cacheKey])) {
                $sql = "SELECT amount FROM deductions WHERE employee_id = ? AND creditor_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([(string)$empleado, $idDeduction]);
                $deduction = $stmt->fetch(PDO::FETCH_ASSOC);

                $monto = $deduction ? (float)$deduction['amount'] : 0;
                $this->cacheAcreedores[$cacheKey] = $monto;
                $this->montoAcreedor = $monto;
            }

            return $this->cacheAcreedores[$cacheKey];

        } catch (PDOException $e) {
            error_log("Error calculando monto acreedor: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 📈 Calcular acumulados de forma segura
     */
    protected function calcularAcumuladosSeguro($conceptos, string $fechaDesde, string $fechaHasta): float
    {
        try {
            // Validar fechas
            if (!$this->validarFormatoFecha($fechaDesde) || !$this->validarFormatoFecha($fechaHasta)) {
                throw new MathExecutorException('Formato de fecha inválido en ACUMULADOS');
            }

            // Validar empleado
            if (!isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                throw new MathExecutorException('Empleado no establecido para ACUMULADOS');
            }

            $employeeId = $this->variablesColaborador['EMPLOYEE_ID'];

            // Parsear conceptos de forma segura
            $conceptosArray = $this->parsearConceptosSeguro($conceptos);

            if (empty($conceptosArray)) {
                return 0;
            }

            // Construir consulta segura con placeholders
            $placeholders = str_repeat('?,', count($conceptosArray) - 1) . '?';

            $sql = "SELECT SUM(ape.monto) as total
                    FROM acumulados_por_empleado ape
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    WHERE ape.employee_id = ?
                    AND c.concepto IN ($placeholders)
                    AND ape.fecha >= ?
                    AND ape.fecha <= ?";

            $params = array_merge([$employeeId], $conceptosArray, [$fechaDesde, $fechaHasta]);

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (float)($result['total'] ?? 0);

        } catch (PDOException $e) {
            error_log("Error calculando acumulados: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Parsear conceptos de forma segura
     */
    protected function parsearConceptosSeguro($conceptos): array
    {
        if (is_string($conceptos)) {
            // Remover comillas y dividir por comas
            $conceptos = trim($conceptos, '"\'');
            $conceptosArray = array_map('trim', explode(',', $conceptos));
        } elseif (is_array($conceptos)) {
            $conceptosArray = $conceptos;
        } else {
            throw new MathExecutorException('Formato de conceptos inválido en ACUMULADOS');
        }

        // Validar cada concepto
        foreach ($conceptosArray as $concepto) {
            if (!preg_match('/^[a-zA-Z0-9_\s]+$/', $concepto)) {
                throw new MathExecutorException("Concepto contiene caracteres inválidos: $concepto");
            }
        }

        return array_filter($conceptosArray, function($c) { return !empty($c); });
    }

    /**
     * 🎄 Calcular XIII mes con fechas de planilla
     */
    protected function calcularXIIIMesConFechasPlanilla(): float
    {
        try {
            if (!isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return 0;
            }

            $employeeId = $this->variablesColaborador['EMPLOYEE_ID'];
            $añoActual = date('Y');

            // Usar fechas del periodo actual o año completo
            $fechaDesde = $this->fechasActuales['fecha_desde'] ?? "$añoActual-01-01";
            $fechaHasta = $this->fechasActuales['fecha_hasta'] ?? "$añoActual-12-31";

            // Calcular total de ingresos del período
            $sql = "SELECT SUM(ape.monto) as total_ingresos
                    FROM acumulados_por_empleado ape
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    WHERE ape.employee_id = ?
                    AND c.tipo_concepto = 'A'
                    AND ape.fecha >= ?
                    AND ape.fecha <= ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $fechaDesde, $fechaHasta]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalIngresos = (float)($result['total_ingresos'] ?? 0);

            // XIII mes = ingresos anuales / 3 (según legislación panameña)
            return $totalIngresos / 3;

        } catch (\Exception $e) {
            error_log("Error calculando XIII mes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * ⏰ Obtener dato de asistencia del empleado actual
     *
     * Consulta payroll_attendance_summary para obtener un campo específico
     * del empleado y período actuales. Retorna 0 si no hay datos (opcional).
     *
     * @param string $campo Nombre del campo a obtener
     * @return float Valor del campo o 0 si no existe
     */
    protected function obtenerDatoAsistencia(string $campo): float
    {
        try {
            // Validar que hay empleado establecido
            if (!isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return 0;
            }

            // Validar que hay fechas de planilla establecidas
            if (empty($this->fechasActuales['fecha_desde']) || empty($this->fechasActuales['fecha_hasta'])) {
                return 0;
            }

            $employeeId = $this->variablesColaborador['EMPLOYEE_ID'];
            $fechaDesde = $this->fechasActuales['fecha_desde'];
            $fechaHasta = $this->fechasActuales['fecha_hasta'];

            // Cargar summary en caché si no está cargado
            if ($this->attendanceSummaryCache === null) {
                $this->cargarResumenAsistencia($employeeId, $fechaDesde, $fechaHasta);
            }

            // Si después de cargar sigue siendo null, no hay datos
            if ($this->attendanceSummaryCache === null) {
                return 0;
            }

            // Retornar el campo solicitado o 0 si no existe
            return (float)($this->attendanceSummaryCache[$campo] ?? 0);

        } catch (\Exception $e) {
            error_log("Error obteniendo dato de asistencia '$campo': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cargar resumen de asistencias en caché
     *
     * @param int $employeeId ID del empleado
     * @param string $fechaDesde Fecha inicio período
     * @param string $fechaHasta Fecha fin período
     */
    protected function cargarResumenAsistencia(int $employeeId, string $fechaDesde, string $fechaHasta): void
    {
        try {
            // Buscar resumen que coincida con el empleado y período
            // Tolerancia: el período de la planilla debe estar contenido en el período del summary
            $sql = "SELECT *
                    FROM payroll_attendance_summary
                    WHERE employee_id = ?
                    AND period_start <= ?
                    AND period_end >= ?
                    ORDER BY created_at DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $fechaDesde, $fechaHasta]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Guardar en caché (null si no se encontró)
            $this->attendanceSummaryCache = $result ?: null;

            if ($this->attendanceSummaryCache === null) {
                error_log("No se encontró resumen de asistencias para empleado $employeeId, período $fechaDesde a $fechaHasta");
            }

        } catch (PDOException $e) {
            error_log("Error cargando resumen de asistencias: " . $e->getMessage());
            $this->attendanceSummaryCache = null;
        }
    }

    /**
     * Limpiar caché de asistencias
     * (útil cuando se cambia de empleado)
     */
    public function limpiarCacheAsistencias(): void
    {
        $this->attendanceSummaryCache = null;
    }
}