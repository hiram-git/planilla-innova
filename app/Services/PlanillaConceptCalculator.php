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

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->cargarConceptos();
    }

    /**
     * Cargar conceptos desde la base de datos
     */
    private function cargarConceptos(): void
    {
        try {
            $sql = "SELECT id, descripcion, formula FROM concepto";
            $stmt = $this->db->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->conceptos[$row['descripcion']] = [
                    'id' => $row['id'],
                    'formula' => $row['formula'] ?: '0'
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
            // Obtener tipo de empresa de la configuración
            $companyType = $this->getCompanyType();
            
            $sql = "SELECT e.created_on, e.employee_id, e.sueldo_individual, p.sueldo as sueldo_posicion, s.time_in, s.time_out
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
                
                // Calcular antigüedad (años desde created_on)
                $created_on = new \DateTime($employee['created_on']);
                $now = new \DateTime();
                $antiguedad = $created_on->diff($now)->y;

                $this->variablesColaborador = [
                    'SALARIO' => $salario,
                    'SUELDO' => $salario,
                    'FICHA' => $ficha,
                    'EMPLEADO' => $ficha, // Usar employee_id (código) en lugar del ID numérico
                    'HORAS' => (float)$horas,
                    'ANTIGUEDAD' => (float)$antiguedad
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
     */
    public function evaluarFormulaPorConcepto(string $concepto): float
    {
        try {
            if (!isset($this->conceptos[$concepto])) {
                return 0;
            }

            $formula = $this->conceptos[$concepto]['formula'];
            return $this->evaluarFormula($formula);
            
        } catch (\Exception $e) {
            error_log("Error evaluando fórmula para concepto '$concepto': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Evaluador simple de fórmulas (versión básica sin librerías externas)
     */
    private function evaluarFormulaSimple(string $formula): float
    {
        // Reemplazar variables
        $formulaProcessed = $this->reemplazarVariables($formula);
        
        // Procesar funciones básicas
        $formulaProcessed = $this->procesarFunciones($formulaProcessed);
        
        // Evaluar expresión matemática simple
        try {
            // Sanitizar y evaluar expresión básica
            $formulaProcessed = preg_replace('/[^0-9+\-*\/\(\)\.]/', '', $formulaProcessed);
            
            if (empty($formulaProcessed)) {
                return 0;
            }
            
            // Validar que la fórmula no contenga caracteres peligrosos
            if (!preg_match('/^[0-9+\-*\/\(\)\.\s]+$/', $formulaProcessed)) {
                error_log("Fórmula contiene caracteres no válidos: $formulaProcessed");
                return 0;
            }
            
            // Evaluando fórmula procesada
            
            // Usar eval de forma segura solo para expresiones matemáticas
            try {
                $result = eval("return $formulaProcessed;");
                return (float)$result;
            } catch (\ParseError $e) {
                error_log("Error de sintaxis en fórmula '$formulaProcessed': " . $e->getMessage());
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
        foreach ($this->variablesColaborador as $variable => $valor) {
            $formula = str_replace($variable, (string)$valor, $formula);
        }
        
        return $formula;
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