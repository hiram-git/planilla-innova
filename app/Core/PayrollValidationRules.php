<?php

namespace App\Core;

use App\Core\Database;
use PDO;

/**
 * Reglas de validación y negocio para el sistema de planillas
 */
class PayrollValidationRules
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Validar dependencias entre conceptos
     * 
     * @param int $conceptId ID del concepto principal
     * @param int $employeeId ID del empleado
     * @param array $activeConceptIds IDs de conceptos activos para el empleado
     * @return array [valid => bool, message => string, dependencies => array]
     */
    public function validateConceptDependencies($conceptId, $employeeId, $activeConceptIds = [])
    {
        try {
            // Obtener información del concepto
            $sql = "SELECT * FROM concepto WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conceptId]);
            $concept = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$concept) {
                return ['valid' => false, 'message' => 'Concepto no encontrado', 'dependencies' => []];
            }

            $dependencies = [];
            $issues = [];

            // Analizar fórmula para encontrar dependencias
            if (!empty($concept['formula'])) {
                // Buscar referencias a otros conceptos en la fórmula
                $formula = $concept['formula'];
                
                // Patrones comunes de dependencias
                if (strpos($formula, 'ACREEDOR') !== false) {
                    preg_match_all('/ACREEDOR\s*\(\s*.*?,\s*(\d+)\s*\)/', $formula, $matches);
                    foreach ($matches[1] as $creditorId) {
                        $dependencies[] = [
                            'type' => 'creditor',
                            'id' => $creditorId,
                            'description' => "Requiere acreedor ID: $creditorId"
                        ];
                    }
                }

                // Verificar si depende de variables del empleado específicas
                if (strpos($formula, 'HORAS') !== false) {
                    // Verificar que el empleado tenga horario definido
                    $sql = "SELECT schedule_id FROM employees WHERE id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$employeeId]);
                    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$employee['schedule_id']) {
                        $issues[] = "El empleado no tiene horario definido para calcular HORAS";
                    }
                }
            }

            return [
                'valid' => empty($issues),
                'message' => empty($issues) ? 'Dependencias validadas correctamente' : implode('; ', $issues),
                'dependencies' => $dependencies
            ];

        } catch (\Exception $e) {
            error_log("Error validando dependencias: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Error en validación de dependencias', 'dependencies' => []];
        }
    }

    /**
     * Validar elegibilidad de empleado para un concepto
     * 
     * @param int $conceptId ID del concepto
     * @param int $employeeId ID del empleado
     * @param int $payrollTypeId Tipo de planilla
     * @return array [valid => bool, message => string, reasons => array]
     */
    public function validateEmployeeEligibility($conceptId, $employeeId, $payrollTypeId)
    {
        try {
            // Obtener configuración del concepto
            $sql = "SELECT * FROM concepto WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conceptId]);
            $concept = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$concept) {
                return ['valid' => false, 'message' => 'Concepto no encontrado', 'reasons' => []];
            }

            // Obtener datos del empleado
            $sql = "SELECT e.*, p.sueldo FROM employees e 
                    LEFT JOIN posiciones p ON e.position_id = p.id 
                    WHERE e.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                return ['valid' => false, 'message' => 'Empleado no encontrado', 'reasons' => []];
            }

            $reasons = [];

            // Validar situación del empleado (asumiendo empleado activo por ahora)
            if (!empty($concept['situaciones'])) {
                $allowedSituations = explode(',', $concept['situaciones']);
                if (!in_array('activo', array_map('trim', $allowedSituations))) {
                    $reasons[] = "Concepto no aplica para empleados activos";
                }
            }

            // Validar sueldo mínimo si es requerido
            if ($concept['tipo_concepto'] === 'A' && !empty($employee['sueldo'])) {
                if ($employee['sueldo'] <= 0) {
                    $reasons[] = "El empleado no tiene sueldo base definido";
                }
            }

            // Validar posición
            if (!$employee['position_id']) {
                $reasons[] = "El empleado no tiene posición asignada";
            }

            return [
                'valid' => empty($reasons),
                'message' => empty($reasons) ? 'Empleado elegible para el concepto' : implode('; ', $reasons),
                'reasons' => $reasons
            ];

        } catch (\Exception $e) {
            error_log("Error validando elegibilidad: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Error en validación de elegibilidad', 'reasons' => []];
        }
    }

    /**
     * Validar límites por tipo de planilla
     * 
     * @param int $payrollId ID de la planilla
     * @param int $conceptId ID del concepto
     * @param float $value Valor a validar
     * @return array [valid => bool, message => string, limits => array]
     */
    public function validatePayrollLimits($payrollId, $conceptId, $value)
    {
        try {
            // Obtener información de la planilla
            $sql = "SELECT pc.*, tp.nombre as tipo_nombre 
                    FROM planilla_cabecera pc
                    JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                    WHERE pc.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payroll) {
                return ['valid' => false, 'message' => 'Planilla no encontrada', 'limits' => []];
            }

            // Obtener concepto
            $sql = "SELECT * FROM concepto WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conceptId]);
            $concept = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$concept) {
                return ['valid' => false, 'message' => 'Concepto no encontrado', 'limits' => []];
            }

            $issues = [];

            // Límites específicos por tipo de planilla
            switch (strtolower($payroll['tipo_nombre'])) {
                case 'quincenal':
                    if ($concept['tipo_concepto'] === 'D' && $value > 50000) {
                        $issues[] = "Deducción excesiva para planilla quincenal (máximo Q50,000)";
                    }
                    break;

                case 'mensual':
                    if ($concept['tipo_concepto'] === 'D' && $value > 100000) {
                        $issues[] = "Deducción excesiva para planilla mensual (máximo Q100,000)";
                    }
                    break;

                case 'semanal':
                    if ($concept['tipo_concepto'] === 'D' && $value > 25000) {
                        $issues[] = "Deducción excesiva para planilla semanal (máximo Q25,000)";
                    }
                    break;
            }

            return [
                'valid' => empty($issues),
                'message' => empty($issues) ? 'Límites validados correctamente' : implode('; ', $issues),
                'limits' => []
            ];

        } catch (\Exception $e) {
            error_log("Error validando límites: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Error en validación de límites', 'limits' => []];
        }
    }

    /**
     * Validación integral de concepto para empleado
     * 
     * @param int $conceptId
     * @param int $employeeId
     * @param int $payrollId
     * @param float $value
     * @return array [valid => bool, message => string, details => array]
     */
    public function validateConceptForEmployee($conceptId, $employeeId, $payrollId, $value)
    {
        try {
            $results = [];

            // Obtener tipo de planilla
            $sql = "SELECT tipo_planilla_id FROM planilla_cabecera WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payroll) {
                return ['valid' => false, 'message' => 'Planilla no encontrada', 'details' => []];
            }

            // Validar dependencias
            $results['dependencies'] = $this->validateConceptDependencies($conceptId, $employeeId);
            
            // Validar elegibilidad
            $results['eligibility'] = $this->validateEmployeeEligibility($conceptId, $employeeId, $payroll['tipo_planilla_id']);
            
            // Validar límites
            $results['limits'] = $this->validatePayrollLimits($payrollId, $conceptId, $value);

            // Determinar resultado general
            $allValid = $results['dependencies']['valid'] && 
                       $results['eligibility']['valid'] && 
                       $results['limits']['valid'];

            $messages = [];
            if (!$results['dependencies']['valid']) {
                $messages[] = $results['dependencies']['message'];
            }
            if (!$results['eligibility']['valid']) {
                $messages[] = $results['eligibility']['message'];
            }
            if (!$results['limits']['valid']) {
                $messages[] = $results['limits']['message'];
            }

            return [
                'valid' => $allValid,
                'message' => $allValid ? 'Todas las validaciones pasaron' : implode('; ', $messages),
                'details' => $results
            ];

        } catch (\Exception $e) {
            error_log("Error en validación integral: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Error en validación integral', 'details' => []];
        }
    }
}