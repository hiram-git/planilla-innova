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
                'FECHA',
                'INICIO_PERIODO_XIII',  // Fechas períodos XIII mes trimestral
                'FIN_PERIODO_XIII',
                'PERIODO_XIII_ESTADO',  // Estado del período XIII: 'SIN_LIQUIDACION', 'ERROR', 'PENDIENTE', 'LIQUIDADO'
                'FECHA_LIQUIDACION',  // Fecha de liquidación en formato string
                'UNIDAD'  // Unidad base de cálculo (días, horas, %, monto)
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

        // 💳 Función CUOTASPRESTAMOS - Obtener cuota pendiente de préstamo por acreedor
        // Mismos parámetros que ACREEDOR: empleado (ficha o ID), id_acreedor
        // Retorna el monto de la cuota pendiente del préstamo activo del empleado con ese acreedor
        $this->executor->addFunction('CUOTASPRESTAMOS', function ($empleado, $idAcreedor) {
            return $this->calcularCuotaPrestamoSeguro($empleado, $idAcreedor);
        }, 2);

        // 💳 Alias CUOTAPRESTAMO (singular) - apunta a la misma función
        $this->executor->addFunction('CUOTAPRESTAMO', function ($empleado, $idAcreedor) {
            return $this->calcularCuotaPrestamoSeguro($empleado, $idAcreedor);
        }, 2);

        // Función ACUMULADOS avanzada y segura
        // Acepta 4 parámetros: conceptos, ficha (ignorado), fechaDesde, fechaHasta
        // El parámetro ficha se ignora porque se usa EMPLOYEE_ID de variablesColaborador
        $this->executor->addFunction('ACUMULADOS', function ($conceptos, $ficha, $fechaDesde, $fechaHasta) {
            // Ignorar $ficha - se usa EMPLOYEE_ID de $this->variablesColaborador
            return $this->calcularAcumuladosSeguro($conceptos, $fechaDesde, $fechaHasta);
        }, 4);

        // Función CONCEPTO para referenciar y evaluar otros conceptos
        $this->executor->addFunction('CONCEPTO', function ($nombreConcepto) {
            try {
                // Remover comillas si existen
                $nombreConcepto = trim($nombreConcepto, '"\'');

                // Verificar si el concepto existe
                if (!isset($this->conceptos[$nombreConcepto])) {
                    // Retornar 0 si el concepto no existe (en lugar de error)
                    // Esto permite que las fórmulas con SI(CONCEPTO("X") > 0, ..., ...) funcionen
                    return 0;
                }

                // Evaluar el concepto de forma segura
                return $this->evaluarConceptoSeguro($nombreConcepto);
            } catch (\Exception $e) {
                error_log("Error en función CONCEPTO('$nombreConcepto'): " . $e->getMessage());
                return 0;
            }
        }, 1);

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

        // Horas extras al 25% (solo si empleado permite horas extras)
        $this->executor->addFunction('HORAS_EXTRAS_25', function () {
            if (!$this->empleadoPermiteHorasExtras()) {
                return 0;
            }
            return $this->obtenerDatoAsistencia('overtime_hours_25');
        }, 0);

        // Horas extras al 50% (solo si empleado permite horas extras)
        $this->executor->addFunction('HORAS_EXTRAS_50', function () {
            if (!$this->empleadoPermiteHorasExtras()) {
                return 0;
            }
            return $this->obtenerDatoAsistencia('overtime_hours_50');
        }, 0);

        // Total horas extras (25% + 50%) (solo si empleado permite horas extras)
        $this->executor->addFunction('HORAS_EXTRAS', function () {
            if (!$this->empleadoPermiteHorasExtras()) {
                return 0;
            }
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
            $campo = $this->obtenerDatoAsistencia('holiday_hours');
            return $campo;
        }, 0);

        // Horas en domingos (solo domingos, no incluye sábados)
        $this->executor->addFunction('HORAS_DOMINICALES', function () {
            return $this->obtenerDatoAsistencia('sunday_hours');
        }, 0);

        // Horas en sábados
        $this->executor->addFunction('HORAS_SABADO', function () {
            return $this->obtenerDatoAsistencia('saturday_hours');
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
            $sql = "SELECT id, concepto, descripcion, formula, unidad FROM concepto";
            $stmt = $this->db->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data = [
                    'id' => $row['id'],
                    'concepto' => $row['concepto'],
                    'formula' => $row['formula'] ?: '0',
                    'unidad' => $row['unidad'] ?? ''
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
                           s.time_in, s.time_out,
                           eps.gastos_representacion
                    FROM employees e
                    LEFT JOIN posiciones p ON p.id = e.position_id
                    LEFT JOIN schedules s ON s.id = e.schedule_id
                    LEFT JOIN employee_payroll_salaries eps ON eps.employee_id = e.id
                        AND eps.is_active = 1
                    WHERE e.id = ?
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                throw new MathExecutorException("Empleado con ID $employeeId no encontrado");
            }

            // Calcular variables del empleado de forma segura
            $sueldo = (float)($employee['sueldo'] ?? 0);
            $gastosRep = (float)($employee['gastos_representacion'] ?? 0);
            $ficha = $employee['employee_id'] ?? '';

            // Calcular horas de trabajo
            $horas = $this->calcularHorasTrabajo($employee['time_in'], $employee['time_out']);

            // Calcular antigüedad
            $antiguedadDias = $this->calcularAntiguedad($employee['created_on']);

            // Establecer variables en el executor con validación
            $this->executor->setVar('SUELDO', $sueldo);
            $this->executor->setVar('SALARIO', $sueldo); // Alias
            $this->executor->setVar('GASTOS_REP', $gastosRep);
            $this->executor->setVar('GASTOS_REPRESENTACION', $gastosRep); // Alias completo
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
                'GASTOS_REPRESENTACION' => $gastosRep,
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
     * Obtener array de conceptos cargados
     * @return array
     */
    public function getConceptos(): array
    {
        return $this->conceptos;
    }

    /**
     * Agregar un concepto temporal (útil para validación)
     * @param string $nombre Nombre del concepto
     * @param array $data Data del concepto (debe incluir 'id' y 'formula')
     */
    public function agregarConceptoTemporal(string $nombre, array $data): void
    {
        $this->conceptos[$nombre] = $data;
    }

    /**
     * Eliminar un concepto temporal
     * @param string $nombre Nombre del concepto a eliminar
     */
    public function eliminarConceptoTemporal(string $nombre): void
    {
        if (isset($this->conceptos[$nombre])) {
            unset($this->conceptos[$nombre]);
        }
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

            // Establecer variable UNIDAD inicial desde los datos del concepto
            // La fórmula puede sobrescribir este valor con asignaciones
            if (isset($conceptoData['unidad'])) {
                $this->executor->setVar('UNIDAD', $conceptoData['unidad']);
            }

            return $this->evaluarFormula($formula);

        } catch (\Exception $e) {
            error_log("Error evaluando fórmula para concepto '$concepto': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener valor actual de la variable UNIDAD
     * (Útil para capturar el valor calculado dinámicamente en fórmulas)
     */
    public function obtenerUnidadCalculada(): mixed
    {
        try {
            return $this->executor->getVar('UNIDAD');
        } catch (\Exception $e) {
            return null;
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

            // Establecer variable UNIDAD desde los datos del concepto
            if (isset($conceptoData['unidad'])) {
                $this->executor->setVar('UNIDAD', $conceptoData['unidad']);
            }

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

                // 🔍 DEBUG: Log comentado para evitar headers HTTP demasiado grandes en producción
                // error_log("ACREEDOR - Employee: $empleado | Creditor: $idDeduction | Monto: $monto");
            }

            return $this->cacheAcreedores[$cacheKey];

        } catch (PDOException $e) {
            error_log("Error calculando monto acreedor: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 💳 Calcular cuota de préstamo pendiente de forma segura
     *
     * Obtiene la cuota de préstamo que corresponde al período de la planilla actual.
     * Busca cuotas cuya fecha de vencimiento esté dentro del rango de fechas de la planilla.
     * Solo considera préstamos activos y cuotas en estado 'pendiente' o 'generada'.
     *
     * @param mixed $empleado Ficha del empleado (string) o ID (int)
     * @param mixed $idAcreedor ID numérico del acreedor (int) o código del acreedor (string, varchar creditor_id)
     * @return float Monto de la cuota pendiente o 0 si no hay cuotas pendientes
     */
    protected function calcularCuotaPrestamoSeguro($empleado, $idAcreedor): float
    {
        try {
            // Validar parámetros
            if (!is_numeric($empleado) && !is_string($empleado)) {
                throw new MathExecutorException("EMPLEADO debe ser numérico o string");
            }

            if (!is_numeric($idAcreedor) && !is_string($idAcreedor)) {
                throw new MathExecutorException("ACREEDOR debe ser numérico (ID) o string (código)");
            }

            // Obtener el employee_id real
            $employeeId = $this->obtenerEmployeeId($empleado);

            if (!$employeeId) {
                return 0;
            }

            // Obtener el creditor_id real (puede ser INT o buscar por código string)
            $creditorId = $this->obtenerCreditorId($idAcreedor);

            if (!$creditorId) {
                error_log("CUOTAPRESTAMO - No se encontró acreedor con identificador: $idAcreedor");
                return 0;
            }

            // Obtener fechas de la planilla actual
            $fechaDesde = $this->fechasActuales['fecha_desde'] ?? null;
            $fechaHasta = $this->fechasActuales['fecha_hasta'] ?? null;

            // Si no hay fechas establecidas, buscar la próxima cuota pendiente (comportamiento legacy)
            if (!$fechaDesde || !$fechaHasta) {
                error_log("CUOTAPRESTAMO - ADVERTENCIA: No hay fechas de planilla establecidas. Usando comportamiento legacy (próxima cuota pendiente).");

                $sql = "SELECT li.amount
                        FROM loan_installments li
                        INNER JOIN loans l ON l.id = li.loan_id
                        WHERE l.employee_id = ?
                        AND l.creditor_id = ?
                        AND l.status = 'activo'
                        AND li.status IN ('pendiente', 'generada')
                        ORDER BY li.due_date ASC, li.installment_number ASC
                        LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$employeeId, $creditorId]);
            } else {
                // Buscar cuota cuya fecha de vencimiento esté dentro del período de la planilla
                // O la primera cuota pendiente si no hay ninguna en el rango
                $sql = "SELECT li.amount, li.due_date, li.installment_number
                        FROM loan_installments li
                        INNER JOIN loans l ON l.id = li.loan_id
                        WHERE l.employee_id = ?
                        AND l.creditor_id = ?
                        AND l.status = 'activo'
                        AND li.status IN ('pendiente', 'generada')
                        AND li.due_date >= ?
                        AND li.due_date <= ?
                        ORDER BY li.due_date ASC, li.installment_number ASC
                        LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$employeeId, $creditorId, $fechaDesde, $fechaHasta]);
            }

            $installment = $stmt->fetch(PDO::FETCH_ASSOC);

            $monto = $installment ? (float)$installment['amount'] : 0;

            // Log detallado
            if ($fechaDesde && $fechaHasta) {
                $dueDate = $installment ? $installment['due_date'] : 'N/A';
                $installmentNum = $installment ? $installment['installment_number'] : 'N/A';
                error_log("CUOTAPRESTAMO - Employee: $employeeId | Acreedor: $idAcreedor (ID: $creditorId) | " .
                          "Período: $fechaDesde a $fechaHasta | Cuota #$installmentNum | Vence: $dueDate | Monto: $monto");
            } else {
                error_log("CUOTAPRESTAMO - Employee: $employeeId | Acreedor: $idAcreedor (ID: $creditorId) | Cuota: $monto (legacy mode)");
            }

            return $monto;

        } catch (PDOException $e) {
            error_log("Error calculando cuota de préstamo: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener ID numérico del acreedor a partir de ID o código
     *
     * @param mixed $idAcreedor ID numérico (int) o código (string, varchar creditor_id)
     * @return int|null ID numérico del acreedor o null si no se encuentra
     */
    protected function obtenerCreditorId($idAcreedor): ?int
    {
        try {
            // Si es numérico, asumimos que es el ID
            if (is_numeric($idAcreedor) && (int)$idAcreedor > 0) {
                return (int)$idAcreedor;
            }

            // Si es string, buscar por creditor_id (código varchar)
            if (is_string($idAcreedor)) {
                $sql = "SELECT id FROM creditors WHERE creditor_id = ? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$idAcreedor]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                return $result ? (int)$result['id'] : null;
            }

            return null;

        } catch (PDOException $e) {
            error_log("Error obteniendo creditor_id: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener employee_id a partir de ficha o ID
     *
     * @param mixed $empleado Ficha (string) o ID (int)
     * @return int|null ID del empleado o null si no se encuentra
     */
    protected function obtenerEmployeeId($empleado): ?int
    {
        try {
            // Si es numérico, asumimos que es el ID
            if (is_numeric($empleado) && (int)$empleado > 0) {
                return (int)$empleado;
            }

            // Si es string, buscar por employee_id (ficha)
            if (is_string($empleado)) {
                $sql = "SELECT id FROM employees WHERE employee_id = ? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$empleado]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                return $result ? (int)$result['id'] : null;
            }

            return null;

        } catch (PDOException $e) {
            error_log("Error obteniendo employee_id: " . $e->getMessage());
            return null;
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

            // LEFT JOIN con planilla_cabecera para incluir acumulados importados (planilla_id = 0)
            // Lógica dual:
            // - Para planilla_id = 0 (importados): filtrar por año/mes construyendo fecha
            // - Para planilla_id != 0: usar lógica de solapamiento con fechas de planilla_cabecera
            $sql = "SELECT SUM(ape.monto) as total
                    FROM acumulados_por_empleado ape
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE ape.employee_id = ?
                    AND ape.tipo_acumulado IN ($placeholders)
                    AND (
                        (ape.planilla_id = 0 AND DATE(CONCAT(ape.ano, '-', LPAD(ape.mes, 2, '0'), '-01')) BETWEEN ? AND ?)
                        OR
                        (ape.planilla_id != 0 AND pc.fecha_hasta >= ? AND pc.fecha_desde <= ?)
                    )";
            error_log("SQL ACUMULADOS: $sql");
            error_log("Parametros ACUMULADOS: " . implode(',', array_merge([$employeeId], $conceptosArray, [$fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta])));

            $params = array_merge([$employeeId], $conceptosArray, [$fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta]);

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $total = (float)($result['total'] ?? 0);

            // Log detallado del resultado de la transacción
            error_log("ACUMULADOS - Employee: $employeeId | Conceptos: " . implode(',', $conceptosArray) .
                      " | Período: $fechaDesde a $fechaHasta | Total: $total");

            return $total;

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
            // LEFT JOIN para incluir acumulados importados (planilla_id = 0)
            $sql = "SELECT SUM(ape.monto) as total_ingresos
                    FROM acumulados_por_empleado ape
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE ape.employee_id = ?
                    AND c.tipo_concepto = 'A'
                    AND (
                        (ape.planilla_id = 0 AND DATE(CONCAT(ape.ano, '-', LPAD(ape.mes, 2, '0'), '-01')) BETWEEN ? AND ?)
                        OR
                        (ape.planilla_id != 0 AND pc.fecha_desde >= ? AND pc.fecha_hasta <= ?)
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalIngresos = (float)($result['total_ingresos'] ?? 0);
            $xiiiMes = $totalIngresos / 3;

            // Log detallado del resultado del cálculo
            error_log("XIII MES - Employee: $employeeId | Período: $fechaDesde a $fechaHasta | " .
                      "Ingresos: $totalIngresos | XIII Mes (÷3): $xiiiMes");

            return $xiiiMes;

        } catch (\Exception $e) {
            error_log("Error calculando XIII mes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * ⏰ Obtener dato de asistencia del empleado actual
     *
     * Consulta attendance_calculations directamente para obtener datos agregados
     * del empleado y período actuales. Retorna 0 si no hay datos (opcional).
     *
     * @param string $campo Nombre del campo a obtener (mapeo de funciones)
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

            // Mapeo de funciones a queries SQL
            return $this->ejecutarQueryAsistencia($campo, $employeeId, $fechaDesde, $fechaHasta);

        } catch (\Exception $e) {
            error_log("Error obteniendo dato de asistencia '$campo': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ejecutar query específico según el tipo de dato de asistencia solicitado
     *
     * @param string $campo Nombre del campo/función
     * @param int $employeeId ID del empleado
     * @param string $fechaDesde Fecha inicio período
     * @param string $fechaHasta Fecha fin período
     * @return float Resultado de la consulta
     */
    protected function ejecutarQueryAsistencia(string $campo, int $employeeId, string $fechaDesde, string $fechaHasta): float
    {
        // Mapeo de funciones a campos y agregaciones de attendance_calculations
        $mapeoDirecto = [
            // Horas (SUM de campos numéricos)
            'total_hours_worked' => 'SUM(total_hours)',
            'regular_hours' => 'SUM(regular_hours)',
            'overtime_hours' => 'SUM(overtime_hours)',
            'overtime_hours_25' => 'SUM(overtime_25_hours)',
            'overtime_hours_50' => 'SUM(overtime_50_hours)',
            'night_hours' => 'SUM(night_hours)',
            'holiday_hours' => 'SUM(holiday_hours)',

            // Tardanzas (SUM de minutos)
            'total_tardiness_minutes' => 'SUM(tardiness_minutes)',

            // Puntualidad (AVG de score)
            'punctuality_score' => 'AVG(punctuality_score)',
        ];

        // Mapeo de conteos especiales
        $mapeoConteos = [
            'tardiness_count' => 'COUNT(*) WHERE is_late = 1',
            'perfect_attendance_days' => 'COUNT(*) WHERE is_perfect_attendance = 1',
            'total_days_worked' => 'COUNT(*) WHERE is_absent = 0',
        ];

        // Campos que requieren query especial
        $mapeoEspecial = [
            'sunday_hours' => 'SUM(total_hours) WHERE DAYOFWEEK(date) = 1',  // Solo domingos (DAYOFWEEK: 1=Domingo, 7=Sábado)
            'saturday_hours' => 'SUM(total_hours) WHERE DAYOFWEEK(date) = 7',  // Solo sábados
            'unjustified_absences' => 'absence_log_unjustified',
            'total_absences' => 'absence_log_total',
            'justified_absences' => 'absence_log_justified',
        ];

        // Intentar mapeo directo primero
        if (isset($mapeoDirecto[$campo])) {
            return $this->queryAggregation($mapeoDirecto[$campo], $employeeId, $fechaDesde, $fechaHasta);
        }

        // Intentar mapeo de conteos
        if (isset($mapeoConteos[$campo])) {
            return $this->queryCount($mapeoConteos[$campo], $employeeId, $fechaDesde, $fechaHasta);
        }

        // Intentar mapeo especial
        if (isset($mapeoEspecial[$campo])) {
            $tipo = $mapeoEspecial[$campo];

            if ($tipo === 'absence_log_unjustified') {
                return $this->queryAbsences($employeeId, $fechaDesde, $fechaHasta, 'unjustified');
            } elseif ($tipo === 'absence_log_total') {
                return $this->queryAbsences($employeeId, $fechaDesde, $fechaHasta, 'all');
            } elseif ($tipo === 'absence_log_justified') {
                return $this->queryAbsences($employeeId, $fechaDesde, $fechaHasta, 'justified');
            } elseif (strpos($tipo, 'SUM') !== false) {
                return $this->queryAggregation($tipo, $employeeId, $fechaDesde, $fechaHasta);
            }
        }

        error_log("Campo de asistencia no mapeado: $campo");
        return 0;
    }

    /**
     * Query genérico de agregación (SUM, AVG, etc.) sobre attendance_calculations
     */
    protected function queryAggregation(string $agregacion, int $employeeId, string $fechaDesde, string $fechaHasta): float
    {
        try {
            // Extraer WHERE adicional si existe
            $whereExtra = '';
            if (strpos($agregacion, 'WHERE') !== false) {
                list($agregacion, $whereExtra) = explode('WHERE', $agregacion, 2);
                $whereExtra = trim($whereExtra);
            }

            $sql = "SELECT " . trim($agregacion) . " as result
                    FROM attendance_calculations
                    WHERE employee_id = ?
                    AND date >= ?
                    AND date <= ?";

            if (!empty($whereExtra)) {
                $sql .= " AND " . $whereExtra;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $fechaDesde, $fechaHasta]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $valor = (float)($result['result'] ?? 0);

            // Log del resultado de la transacción
            error_log("ASISTENCIA (Agregación) - Employee: $employeeId | Agregación: $agregacion | " .
                      "Período: $fechaDesde a $fechaHasta | Valor: $valor");

            return $valor;

        } catch (PDOException $e) {
            error_log("Error en queryAggregation: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Query de conteo con condiciones sobre attendance_calculations
     */
    protected function queryCount(string $condicion, int $employeeId, string $fechaDesde, string $fechaHasta): float
    {
        try {
            // Extraer WHERE adicional
            $whereExtra = '';
            if (strpos($condicion, 'WHERE') !== false) {
                list($count, $whereExtra) = explode('WHERE', $condicion, 2);
                $whereExtra = trim($whereExtra);
            }

            $sql = "SELECT COUNT(*) as result
                    FROM attendance_calculations
                    WHERE employee_id = ?
                    AND date >= ?
                    AND date <= ?";

            if (!empty($whereExtra)) {
                $sql .= " AND " . $whereExtra;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $fechaDesde, $fechaHasta]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $valor = (float)($result['result'] ?? 0);

            // Log del resultado de la transacción
            error_log("ASISTENCIA (Conteo) - Employee: $employeeId | Condición: $condicion | " .
                      "Período: $fechaDesde a $fechaHasta | Count: $valor");

            return $valor;

        } catch (PDOException $e) {
            error_log("Error en queryCount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Query de ausencias desde attendance_absence_log
     */
    protected function queryAbsences(int $employeeId, string $fechaDesde, string $fechaHasta, string $tipo): float
    {
        try {
            $sql = "SELECT COUNT(*) as result
                    FROM attendance_absence_log
                    WHERE employee_id = ?
                    AND absence_date >= ?
                    AND absence_date <= ?";

            if ($tipo === 'justified') {
                $sql .= " AND justified = 1";
            } elseif ($tipo === 'unjustified') {
                $sql .= " AND justified = 0";
            }
            // 'all' no necesita condición adicional

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $fechaDesde, $fechaHasta]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $valor = (float)($result['result'] ?? 0);

            // Log del resultado de la transacción
            error_log("AUSENCIAS - Employee: $employeeId | Tipo: $tipo | " .
                      "Período: $fechaDesde a $fechaHasta | Count: $valor");

            return $valor;

        } catch (PDOException $e) {
            error_log("Error en queryAbsences: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * ⏰ Verificar si el empleado actual permite horas extras
     *
     * Consulta el campo permite_horas_extras de la tabla employees.
     * Si el empleado no permite horas extras (exento/gerente), las funciones
     * HORAS_EXTRAS_25(), HORAS_EXTRAS_50() y HORAS_EXTRAS() retornarán 0.
     *
     * @return bool True si permite horas extras, False si es exento
     */
    protected function empleadoPermiteHorasExtras(): bool
    {
        try {
            // Validar que hay empleado establecido
            if (!isset($this->variablesColaborador['EMPLOYEE_ID'])) {
                return true; // Default permite horas extras si no hay empleado
            }

            $employeeId = $this->variablesColaborador['EMPLOYEE_ID'];

            $sql = "SELECT permite_horas_extras FROM employees WHERE id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return (bool) $result['permite_horas_extras'];
            }

            return true; // Default permite horas extras si no se encuentra

        } catch (PDOException $e) {
            error_log("Error verificando permite_horas_extras empleado: " . $e->getMessage());
            return true; // Default permite horas extras en caso de error
        }
    }

    /**
     * Limpiar caché de asistencias
     * (útil cuando se cambia de empleado)
     * @deprecated Ya no se usa caché, las consultas se hacen directamente a attendance_calculations
     */
    public function limpiarCacheAsistencias(): void
    {
        // Método mantenido por compatibilidad, pero ya no hace nada
        // Las consultas ahora se hacen directamente a attendance_calculations
        $this->attendanceSummaryCache = null;
    }
}