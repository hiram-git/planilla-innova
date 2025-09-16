<?php

namespace App\Libraries;

use PDO;
use PDOException;
use Exception;

/**
 * Calculadora de conceptos de planilla
 * Utiliza evaluación matemática segura para cálculos de nómina
 */
class PlanillaConceptCalculator
{
    private $db;
    private array $conceptos = [];
    private ?float $montoAcreedor = null;
    private array $cacheAcreedores = [];
    private array $evaluando = [];
    private array $variablesColaborador = [];
    private array $variables = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->cargarConceptos();
    }

    /**
     * Cargar conceptos desde la tabla conceptos
     */
    private function cargarConceptos(): void
    {
        try {
            $sql = "SELECT id, descripcion, formula, tipo FROM concepto WHERE activo = 1";
            $stmt = $this->db->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->conceptos[$row['descripcion']] = [
                    'formula' => $row['formula'] ?: 0,
                    'tipo' => $row['tipo'],
                    'id' => $row['id']
                ];
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
            $sql = "SELECT e.created_on, p.sueldo, s.time_in, s.time_out, e.employee_id
                    FROM employees e 
                    LEFT JOIN position p ON p.id = e.position_id 
                    LEFT JOIN schedules s ON s.id = e.schedule_id 
                    WHERE e.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                // SALARIO
                $salario = floatval($employee['sueldo'] ?? 0);
                $ficha = $employee['employee_id'] ?? '';
                
                $this->setVariable('SALARIO', $salario);
                $this->setVariable('SUELDO', $salario);
                $this->setVariable('FICHA', $ficha);
                $this->setVariable('EMPLEADO', $employee_id);

                // HORAS (diferencia en horas * 5 días/semana por defecto)
                $horas = 40; // Por defecto
                if ($employee['time_in'] && $employee['time_out']) {
                    $time_in = strtotime($employee['time_in']);
                    $time_out = strtotime($employee['time_out']);
                    $horas_diarias = ($time_out - $time_in) / 3600;
                    $horas = $horas_diarias * 5;
                }
                $this->setVariable('HORAS', $horas);

                // ANTIGUEDAD (años desde created_on)
                $antiguedad = 0;
                if ($employee['created_on']) {
                    $created_on = new \DateTime($employee['created_on']);
                    $now = new \DateTime();
                    $antiguedad = $created_on->diff($now)->y;
                }
                $this->setVariable('ANTIGUEDAD', $antiguedad);

                // Guardar en variablesColaborador para validaciones
                $this->variablesColaborador = [
                    'sueldo' => $salario,
                    'employee_id' => $ficha,
                    'horas' => $horas,
                    'antiguedad' => $antiguedad,
                ];
            }
        } catch (PDOException $e) {
            error_log("Error estableciendo variables del colaborador: " . $e->getMessage());
        }
    }

    /**
     * Establecer una variable
     */
    public function setVariable(string $name, $value): void
    {
        $this->variables[$name] = $value;
    }

    /**
     * Obtener una variable
     */
    public function getVariable(string $name)
    {
        return $this->variables[$name] ?? null;
    }

    /**
     * Evaluar una fórmula para un concepto específico
     */
    public function evaluarFormula(string $concepto, ?float $monto = null): float
    {
        try {
            // Validar que el concepto existe
            if (!isset($this->conceptos[$concepto])) {
                throw new Exception("Concepto '$concepto' no definido");
            }

            // Establecer monto como variable si se proporciona
            if ($monto !== null) {
                $this->setVariable('MONTO', $monto);
            } else {
                $this->setVariable('MONTO', $this->montoAcreedor ?? 0);
            }

            // Evaluar el concepto
            $resultado = $this->evaluarConcepto($concepto);
            
            return is_numeric($resultado) ? floatval($resultado) : 0;
        } catch (Exception $e) {
            error_log("Error evaluando fórmula para '$concepto': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Evaluar un concepto específico
     */
    private function evaluarConcepto(string $nombre): float
    {
        if (!isset($this->conceptos[$nombre])) {
            throw new Exception("Concepto '$nombre' no definido");
        }

        // Prevenir dependencias cíclicas
        if (in_array($nombre, $this->evaluando)) {
            throw new Exception("Dependencia cíclica detectada en '$nombre'");
        }
        $this->evaluando[] = $nombre;

        try {
            $formula = $this->conceptos[$nombre]['formula'];
            
            // Si es un valor numérico directo
            if (is_numeric($formula)) {
                $resultado = floatval($formula);
                array_pop($this->evaluando);
                return $resultado;
            }

            // Si es una fórmula, evaluarla
            $resultado = $this->evaluarExpresion($formula);
            array_pop($this->evaluando);
            return floatval($resultado);
        } catch (Exception $e) {
            array_pop($this->evaluando);
            throw $e;
        }
    }

    /**
     * Evaluar una expresión matemática
     */
    private function evaluarExpresion(string $expresion): float
    {
        // Reemplazar variables y conceptos en la expresión
        $expresion = $this->reemplazarVariables($expresion);
        
        // Evaluar expresión matemática de forma segura
        return $this->evaluarMatematicas($expresion);
    }

    /**
     * Reemplazar variables y conceptos en una expresión
     */
    private function reemplazarVariables(string $expresion): string
    {
        // Reemplazar funciones especiales
        $expresion = $this->reemplazarFunciones($expresion);
        
        // Reemplazar variables del colaborador
        foreach ($this->variables as $var => $valor) {
            $expresion = str_replace($var, strval($valor), $expresion);
        }
        
        // Reemplazar conceptos referenciados
        foreach ($this->conceptos as $concepto => $data) {
            if ($concepto !== end($this->evaluando)) { // Evitar auto-referencia
                $expresion = str_replace($concepto, strval($this->evaluarConcepto($concepto)), $expresion);
            }
        }
        
        return $expresion;
    }

    /**
     * Reemplazar funciones especiales en la expresión
     */
    private function reemplazarFunciones(string $expresion): string
    {
        // Función ACREEDOR(FICHA, id_deduction)
        $expresion = preg_replace_callback(
            '/ACREEDOR\s*\(\s*([^,]+)\s*,\s*(\d+)\s*\)/',
            [$this, 'procesarFuncionAcreedor'],
            $expresion
        );

        // Función SI(condicion, valor_verdadero, valor_falso)
        $expresion = preg_replace_callback(
            '/SI\s*\(\s*([^,]+)\s*,\s*([^,]+)\s*,\s*([^)]+)\s*\)/',
            [$this, 'procesarFuncionSi'],
            $expresion
        );

        // Función MAX(a, b, ...)
        $expresion = preg_replace_callback(
            '/MAX\s*\(\s*([^)]+)\s*\)/',
            [$this, 'procesarFuncionMax'],
            $expresion
        );

        // Función MIN(a, b, ...)
        $expresion = preg_replace_callback(
            '/MIN\s*\(\s*([^)]+)\s*\)/',
            [$this, 'procesarFuncionMin'],
            $expresion
        );

        return $expresion;
    }

    /**
     * Procesar función ACREEDOR
     */
    private function procesarFuncionAcreedor(array $matches): string
    {
        $ficha = trim($matches[1]);
        $id_acreedor = intval($matches[2]);
        
        // Usar FICHA del empleado actual si se especifica como variable
        if ($ficha === 'FICHA') {
            $ficha = $this->variablesColaborador['employee_id'] ?? '';
        }
        
        $monto = $this->calcularMontoAcreedor($ficha, $id_acreedor);
        return strval($monto);
    }

    /**
     * Procesar función SI (condicional)
     */
    private function procesarFuncionSi(array $matches): string
    {
        $condicion = trim($matches[1]);
        $valor_verdadero = trim($matches[2]);
        $valor_falso = trim($matches[3]);
        
        // Evaluar condición
        $resultado_condicion = $this->evaluarMatematicas($this->reemplazarVariables($condicion));
        
        if ($resultado_condicion) {
            return $this->reemplazarVariables($valor_verdadero);
        } else {
            return $this->reemplazarVariables($valor_falso);
        }
    }

    /**
     * Procesar función MAX
     */
    private function procesarFuncionMax(array $matches): string
    {
        $valores = explode(',', $matches[1]);
        $max = PHP_FLOAT_MIN;
        
        foreach ($valores as $valor) {
            $valor = trim($valor);
            $evaluado = $this->evaluarMatematicas($this->reemplazarVariables($valor));
            $max = max($max, $evaluado);
        }
        
        return strval($max);
    }

    /**
     * Procesar función MIN
     */
    private function procesarFuncionMin(array $matches): string
    {
        $valores = explode(',', $matches[1]);
        $min = PHP_FLOAT_MAX;
        
        foreach ($valores as $valor) {
            $valor = trim($valor);
            $evaluado = $this->evaluarMatematicas($this->reemplazarVariables($valor));
            $min = min($min, $evaluado);
        }
        
        return strval($min);
    }

    /**
     * Calcular monto de acreedor
     */
    private function calcularMontoAcreedor(string $employeeId, int $id_acreedor): float
    {
        $cacheKey = "$employeeId:$id_acreedor";
        
        if (!isset($this->cacheAcreedores[$cacheKey])) {
            try {
                $sql = "SELECT amount FROM deductions WHERE employee_id = ? AND creditor_id = ? AND activo = 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$employeeId, $id_acreedor]);
                $deduction = $stmt->fetch(PDO::FETCH_ASSOC);

                $monto = $deduction ? floatval($deduction['amount']) : 0;
                $this->cacheAcreedores[$cacheKey] = $monto;
                $this->montoAcreedor = $monto;
            } catch (PDOException $e) {
                error_log("Error calculando monto acreedor: " . $e->getMessage());
                $this->cacheAcreedores[$cacheKey] = 0;
                $this->montoAcreedor = 0;
            }
        }
        
        return $this->cacheAcreedores[$cacheKey];
    }

    /**
     * Evaluar expresiones matemáticas de forma segura
     */
    private function evaluarMatematicas(string $expresion): float
    {
        // Limpiar la expresión
        $expresion = preg_replace('/[^0-9+\-*\/().%\s]/', '', $expresion);
        
        if (empty($expresion)) {
            return 0;
        }

        // Si es solo un número
        if (is_numeric(trim($expresion))) {
            return floatval(trim($expresion));
        }

        try {
            // Usar eval de forma controlada (solo para expresiones matemáticas simples)
            $resultado = null;
            
            // Validar que solo contiene operaciones matemáticas seguras
            if (preg_match('/^[0-9+\-*\/().\s%]+$/', $expresion)) {
                // Reemplazar % por operación de módulo válida en PHP
                $expresion = str_replace('%', ' % ', $expresion);
                
                // Evaluar usando eval (controlado)
                $codigo = "return $expresion;";
                $resultado = @eval($codigo);
                
                if ($resultado === false || $resultado === null) {
                    return 0;
                }
            }
            
            return floatval($resultado);
        } catch (Exception $e) {
            error_log("Error evaluando expresión '$expresion': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener todos los conceptos cargados
     */
    public function getConceptos(): array
    {
        return $this->conceptos;
    }

    /**
     * Obtener variables del colaborador actual
     */
    public function getVariablesColaborador(): array
    {
        return $this->variablesColaborador;
    }

    /**
     * Limpiar cache y variables
     */
    public function limpiarCache(): void
    {
        $this->cacheAcreedores = [];
        $this->evaluando = [];
        $this->montoAcreedor = null;
        $this->variables = [];
        $this->variablesColaborador = [];
    }

    /**
     * Validar fórmula sin evaluar
     */
    public function validarFormula(string $formula): array
    {
        try {
            if (empty($formula)) {
                return ['valida' => true, 'mensaje' => 'Fórmula vacía'];
            }

            // Verificar sintaxis básica
            $variables_encontradas = [];
            $conceptos_encontrados = [];
            
            // Buscar referencias a variables
            if (preg_match_all('/\b[A-Z_]+\b/', $formula, $matches)) {
                foreach ($matches[0] as $match) {
                    if (isset($this->conceptos[$match])) {
                        $conceptos_encontrados[] = $match;
                    } else {
                        $variables_encontradas[] = $match;
                    }
                }
            }

            return [
                'valida' => true,
                'mensaje' => 'Fórmula válida',
                'variables' => $variables_encontradas,
                'conceptos' => $conceptos_encontrados
            ];
        } catch (Exception $e) {
            return [
                'valida' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }
}