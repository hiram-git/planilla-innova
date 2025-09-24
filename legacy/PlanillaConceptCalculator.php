<?php

require_once __DIR__ . '/../vendor/autoload.php';

use NXP\MathExecutor;
use NXP\Exception\MathExecutorException;

class PlanillaConceptCalculator
{
    private MathExecutor $executor;
    private array $conceptos = [];
    private PDO $conn;
    private ?float $montoAcreedor = null;
    private array $cacheAcreedores = [];
    private array $evaluando = [];
    private array $variablesColaborador = [];

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
        $this->executor = new MathExecutor();

        // Configurar funciones personalizadas tipo Excel
        $this->agregarFuncionesExcel();

        // Configurar validación de variables
        $this->configurarValidacionVariables();

        // Configurar manejador de variables no encontradas
        $this->configurarManejadorVariablesNoEncontradas();

        // Cargar conceptos desde la base de datos
        $this->cargarConceptos();
    }

    /**
     * Agregar funciones personalizadas tipo Excel.
     */
    private function agregarFuncionesExcel(): void
    {
        // SUMA: Suma una lista de números
        $this->executor->addFunction('SUMA', function (...$args) {
            return array_sum($args);
        });

        // PROMEDIO: Calcula el promedio de una lista de números
        $this->executor->addFunction('PROMEDIO', function (...$args) {
            return array_sum($args) / count($args);
        });

        // SI: Condicional (SI(condición, valor_si_verdadero, valor_si_falso))
        $this->executor->addFunction('SI', function ($condicion, $valorSiVerdadero, $valorSiFalso) {
            return $condicion ? $valorSiVerdadero : $valorSiFalso;
        }, 3);

        // MIN: Devuelve el valor mínimo
        $this->executor->addFunction('MIN', function (...$args) {
            return min($args);
        });

        // MAX: Devuelve el valor máximo
        $this->executor->addFunction('MAX', function (...$args) {
            return max($args);
        });

        // Función ACREEDOR(FICHA, id_deduction): Obtiene el monto de la tabla deductions
        $this->executor->addFunction('ACREEDOR', function ($EMPLEADO, $id_deduction) {
            // Validar que FICHA coincida con el empleado actual
            /*echo $EMPLEADO;exit;
            if (isset($this->variablesColaborador['employee_id']) && $EMPLEADO !== $this->variablesColaborador['employee_id']) {
                throw new MathExecutorException("FICHA $EMPLEADO no coincide con el empleado actual");
            }*/
            return $this->calcularMontoAcreedor((string)$this->variablesColaborador['employee_id'], (int)$id_deduction);
        }, 2);
    }

    /**
     * Configurar validación de variables para asegurar tipos numéricos.
     */
    private function configurarValidacionVariables(): void
    {
        $this->executor->setVarValidationHandler(function (string $nombre, $valor) {
            if (!is_numeric($valor) and $nombre !== 'FICHA') {
                throw new MathExecutorException("La variable '$nombre' debe ser numérica");
            }
        });
    }

    /**
     * Configurar manejador para variables no encontradas, respetando monto_cero.
     */
    private function configurarManejadorVariablesNoEncontradas(): void
    {
        $this->executor->setVarNotFoundHandler(function (string $nombre) {
            // Manejar variables del colaborador
            if (isset($this->variablesColaborador[$nombre])) {
                return (float)$this->variablesColaborador[$nombre];
            }

            // Manejar la variable 'monto' del acreedor
            if ($nombre === 'monto' && $this->montoAcreedor !== null) {
                return $this->montoAcreedor;
            }

            // Verificar si el concepto existe
            if (isset($this->conceptos[$nombre])) {
                return $this->evaluarConcepto($nombre);
            }

            throw new MathExecutorException("Variable o concepto '$nombre' no encontrado");
        });
    }

    /**
     * Cargar conceptos desde la tabla conceptos.
     */
    private function cargarConceptos(): void
    {
        $sql = "SELECT id, descripcion, formula FROM concepto";
        $stmt = $this->conn->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->conceptos[$row['descripcion']] = $row['formula'] ?: 0;
        }
    }

    /**
     * Establecer variables específicas del colaborador.
     */
    public function setVariablesColaborador(int $employee_id): void
    {
        $sql = "SELECT e.created_on, p.sueldo, s.time_in, s.time_out, e.employee_id
                FROM employees e 
                LEFT JOIN posiciones p ON p.id = e.position_id 
                LEFT JOIN schedules s ON s.id = e.schedule_id 
                WHERE e.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee) {
            // SALARIO
            $salario = $employee['sueldo'] ?: 0;
            $ficha = $employee['employee_id'] ?: 0;
            $this->executor->setVar('SALARIO', (float)$salario);
            $this->executor->setVar('SUELDO', (float)$salario);
            $this->executor->setVar('FICHA', $ficha); // Corregido a int para FICHA
            $this->executor->setVar('EMPLEADO', $employee_id); // Corregido a string para EMPLEADO

            // HORAS (diferencia en horas * 5 días/semana)
            $time_in = strtotime($employee['time_in']);
            $time_out = strtotime($employee['time_out']);
            $horas_diarias = ($time_out - $time_in) / 3600;
            $horas = $horas_diarias * 5;
            $this->executor->setVar('HORAS', (float)$horas);

            // ANTIGUEDAD (años desde created_on)
            $created_on = new DateTime($employee['created_on']);
            $now = new DateTime();
            $antiguedad = $created_on->diff($now)->y;
            $this->executor->setVar('ANTIGUEDAD', (float)$antiguedad);

            // Guardar en variablesColaborador para validaciones
            $this->variablesColaborador = [
                'sueldo' => (float)$salario,
                'employee_id' => (string)$ficha,
                'horas' => (float)$horas,
                'antiguedad' => (float)$antiguedad,
            ];
        }
    }

    /**
     * Obtener el monto de un acreedor desde la tabla deductions.
     */
    private function calcularMontoAcreedor(string $employeeId, int $id_acreedor): float
    {
        $cacheKey = "$employeeId:$id_acreedor";
        if (!isset($this->cacheAcreedores[$cacheKey])) {
            $sql = "SELECT amount as monto FROM deductions WHERE employee_id = '$employeeId' AND creditor_id = '$id_acreedor';";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $deduction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$deduction) {
                $this->cacheAcreedores[$cacheKey] = 0;
                $this->cmontoAcreedor = 0;

            }else {
                $this->montoAcreedor = (float)$deduction['monto'];
                
                $this->cacheAcreedores[$cacheKey] = (float)($deduction['monto'] ?? 0);
            }
            //file_put_contents('log_'.$cacheKey.date('YmdHis').'.txt', print_r($this->montoAcreedor."|", true), FILE_APPEND);

            $this->montoAcreedor = $this->cacheAcreedores[$cacheKey];
        }
        return $this->cacheAcreedores[$cacheKey];
    }

    /**
     * Cargar un concepto desde la base de datos (para compatibilidad).
     */
    private function cargarConcepto(string $nombre): ?array
    {
        if (isset($this->conceptos[$nombre])) {
            return ['descripcion' => $nombre, 'formula' => $this->conceptos[$nombre]];
        }
        return null;
    }

    /**
     * Evaluar un concepto, que puede ser un valor o una fórmula.
     */
    private function evaluarConcepto(string $nombre)
    {
        if (!isset($this->conceptos[$nombre])) {
            throw new MathExecutorException("Concepto '$nombre' no definido");
        }

        if (in_array($nombre, $this->evaluando)) {
            throw new MathExecutorException("Dependencia cíclica detectada en '$nombre'");
        }
        $this->evaluando[] = $nombre;

        $valor = $this->conceptos[$nombre];

        if (is_numeric($valor)) {
            $result = (float)$valor;
            array_pop($this->evaluando);
            return $result;
        }

        // Si es una fórmula, evaluarla
        $result = $this->executor->execute($valor);
        array_pop($this->evaluando);
        return (float)$result;
    }

    /**
     * Evaluar una fórmula para un concepto específico.
     */
    public function evaluarFormula(string $concepto, ?float $monto = null): float
    {
        try {
            // Validar fórmula
            if (!isset($this->conceptos[$concepto])) {
                throw new MathExecutorException("Concepto '$concepto' no definido");
            }

            // Establecer conceptos como variables
            foreach ($this->conceptos as $nombre => $valor) {
                if (is_numeric($valor)) {
                    $this->executor->setVar($nombre, (float)$valor);
                }
            }

            // Establecer variables del colaborador
            foreach ($this->variablesColaborador as $nombre => $valor) {
                $this->executor->setVar($nombre, (float)$valor);
            }

            // Establecer monto como variable si se proporciona
            if ($monto !== null) {
                $this->executor->setVar('MONTO', $monto);
            } else {

                $this->executor->setVar('MONTO', $this->montoAcreedor ?? 0);
            }

            // Ejecutar la fórmula del concepto
            $resultado = $this->evaluarConcepto($concepto);

            return is_numeric($resultado) ? (float)$resultado : 0;
        } catch (Exception $e) {
            throw new MathExecutorException("Error al evaluar fórmula para '$concepto': " . $e->getMessage());
        }
    }
}
?>