<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * EmployeePayrollSalary Model
 *
 * Gestiona salarios diferenciados por tipo de planilla para empleados.
 * Un empleado puede tener múltiples salarios, uno por cada tipo de planilla asignado.
 *
 * @package App\Models
 * @version 1.0
 */
class EmployeePayrollSalary extends Model
{
    public $table = 'employee_payroll_salaries';

    public $fillable = [
        'employee_id', 'tipo_planilla_id', 'sueldo_base', 'gastos_representacion',
        'fecha_inicio', 'fecha_fin', 'is_active', 'notes', 'created_by', 'updated_by'
    ];

    /**
     * Obtener salario activo de un empleado para un tipo de planilla específico
     *
     * @param int $employeeId
     * @param int $tipoPlanillaId
     * @return array|null
     */
    public function getSalaryForPayroll($employeeId, $tipoPlanillaId)
    {
        try {
            $sql = "SELECT eps.*, tp.descripcion as tipo_planilla_nombre
                    FROM {$this->table} eps
                    LEFT JOIN tipos_planilla tp ON eps.tipo_planilla_id = tp.id
                    WHERE eps.employee_id = ?
                    AND eps.tipo_planilla_id = ?
                    AND eps.is_active = 1
                    AND (eps.fecha_fin IS NULL OR eps.fecha_fin >= CURDATE())
                    ORDER BY eps.fecha_inicio DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $tipoPlanillaId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {
            error_log("Error getting salary for payroll: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los salarios activos de un empleado
     *
     * @param int $employeeId
     * @return array
     */
    public function getAllSalariesForEmployee($employeeId)
    {
        try {
            $sql = "SELECT eps.*, tp.descripcion as tipo_planilla_nombre, tp.codigo as tipo_planilla_codigo
                    FROM {$this->table} eps
                    LEFT JOIN tipos_planilla tp ON eps.tipo_planilla_id = tp.id
                    WHERE eps.employee_id = ?
                    AND eps.is_active = 1
                    AND (eps.fecha_fin IS NULL OR eps.fecha_fin >= CURDATE())
                    ORDER BY tp.descripcion";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting all salaries for employee: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Guardar o actualizar salario de un empleado para un tipo de planilla
     *
     * @param array $data Datos del salario
     * @return int|bool ID del registro o false en error
     */
    public function saveSalary($data)
    {
        try {
            error_log("[EmployeePayrollSalary::saveSalary] Employee: {$data['employee_id']}, Tipo: {$data['tipo_planilla_id']}");
            error_log("[EmployeePayrollSalary::saveSalary] Data: " . json_encode($data));

            // Verificar si ya existe un registro activo
            $existing = $this->getSalaryForPayroll($data['employee_id'], $data['tipo_planilla_id']);

            if ($existing) {
                // Actualizar existente
                error_log("[EmployeePayrollSalary::saveSalary] Found existing record ID: {$existing['id']}, updating...");
                error_log("[EmployeePayrollSalary::saveSalary] Existing data: " . json_encode($existing));

                $result = $this->update($existing['id'], $data);

                error_log("[EmployeePayrollSalary::saveSalary] Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
                return $result;
            }

            // Insertar nuevo
            error_log("[EmployeePayrollSalary::saveSalary] No existing record, creating new...");
            $result = $this->create($data);
            error_log("[EmployeePayrollSalary::saveSalary] Create result: " . ($result ? "ID {$result}" : 'FAILED'));
            return $result;

        } catch (PDOException $e) {
            error_log("[EmployeePayrollSalary::saveSalary] ❌ PDOException: " . $e->getMessage());
            error_log("[EmployeePayrollSalary::saveSalary] SQL State: " . $e->getCode());
            error_log("[EmployeePayrollSalary::saveSalary] Trace: " . $e->getTraceAsString());
            error_log("[EmployeePayrollSalary::saveSalary] Failed data: " . json_encode($data));
            return false;
        } catch (\Exception $e) {
            error_log("[EmployeePayrollSalary::saveSalary] ❌ Exception: " . $e->getMessage());
            error_log("[EmployeePayrollSalary::saveSalary] Failed data: " . json_encode($data));
            return false;
        }
    }

    /**
     * Guardar múltiples salarios de un empleado (bulk insert/update)
     *
     * @param int $employeeId
     * @param array $salaries Array de salarios con formato: [tipo_planilla_id => ['sueldo_base' => X, 'gastos_rep' => Y]]
     * @param int|null $userId Usuario que realiza la acción
     * @return array Resultado del guardado
     */
    public function saveBulkSalaries($employeeId, $salaries, $userId = null)
    {
        $saved = 0;
        $errors = 0;
        $skipped = 0;
        $errorDetails = [];

        error_log("[EmployeePayrollSalary] saveBulkSalaries() - Employee ID: {$employeeId}, User ID: " . ($userId ?? 'NULL'));
        error_log("[EmployeePayrollSalary] Received salaries count: " . count($salaries));

        foreach ($salaries as $tipoPlanillaId => $salaryData) {
            error_log("[EmployeePayrollSalary] Processing tipo_planilla_id: {$tipoPlanillaId}");
            error_log("[EmployeePayrollSalary] Salary data: " . json_encode($salaryData));

            // Validar que tenga al menos sueldo_base
            if (empty($salaryData['sueldo_base']) || $salaryData['sueldo_base'] <= 0) {
                error_log("[EmployeePayrollSalary] ⚠️ SKIPPED tipo {$tipoPlanillaId}: sueldo_base empty or <= 0");
                $skipped++;
                $errorDetails[] = "Tipo {$tipoPlanillaId}: sueldo_base vacío o inválido";
                continue;
            }

            $data = [
                'employee_id' => $employeeId,
                'tipo_planilla_id' => $tipoPlanillaId,
                'sueldo_base' => $salaryData['sueldo_base'],
                'gastos_representacion' => $salaryData['gastos_representacion'] ?? 0,
                'fecha_inicio' => $salaryData['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_fin' => $salaryData['fecha_fin'] ?? null,
                'is_active' => 1,
                'notes' => $salaryData['notes'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId
            ];

            error_log("[EmployeePayrollSalary] Calling saveSalary() with data: " . json_encode($data));
            $result = $this->saveSalary($data);

            if ($result) {
                error_log("[EmployeePayrollSalary] ✅ SUCCESS tipo {$tipoPlanillaId}: Record ID = {$result}");
                $saved++;
            } else {
                error_log("[EmployeePayrollSalary] ❌ ERROR tipo {$tipoPlanillaId}: saveSalary() returned false");
                $errors++;
                $errorDetails[] = "Tipo {$tipoPlanillaId}: Error al guardar";
            }
        }

        $result = [
            'success' => $errors == 0,
            'saved' => $saved,
            'errors' => $errors,
            'skipped' => $skipped,
            'total' => count($salaries),
            'error_details' => $errorDetails
        ];

        error_log("[EmployeePayrollSalary] saveBulkSalaries() RESULT: " . json_encode($result));
        return $result;
    }

    /**
     * Eliminar salarios huérfanos (salarios de tipos de planilla que ya no están asignados al empleado)
     *
     * @param int $employeeId
     * @param array $currentTiposPlanillaIds Array de IDs de tipos de planilla actualmente seleccionados
     * @return array Resultado de la eliminación
     */
    public function deleteOrphanSalaries($employeeId, $currentTiposPlanillaIds)
    {
        try {
            $deleted = 0;

            // Si no hay tipos de planilla seleccionados, eliminar todos los salarios
            if (empty($currentTiposPlanillaIds)) {
                $sql = "DELETE FROM {$this->table}
                        WHERE employee_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$employeeId]);
                $deleted = $stmt->rowCount();

                return [
                    'success' => true,
                    'deleted' => $deleted,
                    'message' => "Todos los salarios eliminados (empleado sin tipos de planilla)"
                ];
            }

            // Obtener salarios existentes
            $sql = "SELECT id, tipo_planilla_id
                    FROM {$this->table}
                    WHERE employee_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $existingSalaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Identificar salarios huérfanos (tipos de planilla que ya no están asignados)
            foreach ($existingSalaries as $salary) {
                if (!in_array($salary['tipo_planilla_id'], $currentTiposPlanillaIds)) {
                    // Eliminar salario huérfano
                    $deleteSql = "DELETE FROM {$this->table} WHERE id = ?";
                    $deleteStmt = $this->db->prepare($deleteSql);
                    if ($deleteStmt->execute([$salary['id']])) {
                        $deleted++;
                    }
                }
            }

            return [
                'success' => true,
                'deleted' => $deleted,
                'message' => $deleted > 0 ? "Se eliminaron {$deleted} salario(s) huérfano(s)" : "No hay salarios huérfanos"
            ];

        } catch (PDOException $e) {
            error_log("Error deleting orphan salaries: " . $e->getMessage());
            return [
                'success' => false,
                'deleted' => 0,
                'message' => 'Error al eliminar salarios huérfanos',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Desactivar (soft delete) un salario específico
     *
     * @param int $id
     * @return bool
     */
    public function deactivateSalary($id)
    {
        try {
            return $this->update($id, [
                'is_active' => 0,
                'fecha_fin' => date('Y-m-d')
            ]);
        } catch (PDOException $e) {
            error_log("Error deactivating salary: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener salarios históricos de un empleado para un tipo de planilla
     *
     * @param int $employeeId
     * @param int $tipoPlanillaId
     * @return array
     */
    public function getSalaryHistory($employeeId, $tipoPlanillaId)
    {
        try {
            $sql = "SELECT eps.*, tp.descripcion as tipo_planilla_nombre,
                           u1.username as created_by_name,
                           u2.username as updated_by_name
                    FROM {$this->table} eps
                    LEFT JOIN tipos_planilla tp ON eps.tipo_planilla_id = tp.id
                    LEFT JOIN users u1 ON eps.created_by = u1.id
                    LEFT JOIN users u2 ON eps.updated_by = u2.id
                    WHERE eps.employee_id = ?
                    AND eps.tipo_planilla_id = ?
                    ORDER BY eps.fecha_inicio DESC, eps.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $tipoPlanillaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting salary history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un empleado tiene salarios configurados para todos sus tipos de planilla
     *
     * @param int $employeeId
     * @return array
     */
    public function checkEmployeeSalariesCompleteness($employeeId)
    {
        try {
            // Obtener tipos de planilla del empleado
            $employeeModel = new Employee();
            $employee = $employeeModel->find($employeeId);

            if (!$employee || empty($employee['tipo_planilla_id'])) {
                return [
                    'complete' => false,
                    'message' => 'Empleado sin tipos de planilla asignados',
                    'missing' => []
                ];
            }

            // Descomponer tipos de planilla
            $tiposPlanillaIds = explode(',', $employee['tipo_planilla_id']);
            $tiposPlanillaIds = array_map('intval', array_filter($tiposPlanillaIds));

            // Obtener salarios configurados
            $sql = "SELECT DISTINCT tipo_planilla_id
                    FROM {$this->table}
                    WHERE employee_id = ?
                    AND is_active = 1
                    AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $configuredTypes = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'tipo_planilla_id');
            $configuredTypes = array_map('intval', $configuredTypes);

            // Encontrar tipos faltantes
            $missingTypes = array_diff($tiposPlanillaIds, $configuredTypes);

            // Obtener nombres de tipos faltantes
            $missingTypesDetails = [];
            if (!empty($missingTypes)) {
                $placeholders = implode(',', array_fill(0, count($missingTypes), '?'));
                $sql = "SELECT id, descripcion FROM tipos_planilla WHERE id IN ($placeholders)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($missingTypes);
                $missingTypesDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return [
                'complete' => empty($missingTypes),
                'total_types' => count($tiposPlanillaIds),
                'configured_types' => count($configuredTypes),
                'missing_count' => count($missingTypes),
                'missing' => $missingTypesDetails
            ];

        } catch (PDOException $e) {
            error_log("Error checking salary completeness: " . $e->getMessage());
            return [
                'complete' => false,
                'message' => 'Error verificando completitud',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validar datos de salario
     *
     * @param array $data
     * @return array Errores de validación
     */
    public function validateSalaryData($data)
    {
        $errors = [];

        if (empty($data['employee_id'])) {
            $errors['employee_id'] = 'El empleado es requerido';
        }

        if (empty($data['tipo_planilla_id'])) {
            $errors['tipo_planilla_id'] = 'El tipo de planilla es requerido';
        }

        if (empty($data['sueldo_base']) || $data['sueldo_base'] <= 0) {
            $errors['sueldo_base'] = 'El sueldo base debe ser mayor a cero';
        }

        if (isset($data['gastos_representacion']) && $data['gastos_representacion'] < 0) {
            $errors['gastos_representacion'] = 'Los gastos de representación no pueden ser negativos';
        }

        if (empty($data['fecha_inicio'])) {
            $errors['fecha_inicio'] = 'La fecha de inicio es requerida';
        }

        // Validar que fecha_fin sea posterior a fecha_inicio
        if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin'])) {
            if (strtotime($data['fecha_fin']) < strtotime($data['fecha_inicio'])) {
                $errors['fecha_fin'] = 'La fecha de fin debe ser posterior a la fecha de inicio';
            }
        }

        return $errors;
    }

    /**
     * Obtener resumen de salarios por tipo de planilla
     *
     * @param int $tipoPlanillaId
     * @return array
     */
    public function getSalaryStatsByPayrollType($tipoPlanillaId)
    {
        try {
            $sql = "SELECT
                        COUNT(DISTINCT eps.employee_id) as total_employees,
                        ROUND(AVG(eps.sueldo_base), 2) as avg_salary,
                        ROUND(MIN(eps.sueldo_base), 2) as min_salary,
                        ROUND(MAX(eps.sueldo_base), 2) as max_salary,
                        ROUND(SUM(eps.sueldo_base), 2) as total_salary,
                        ROUND(AVG(eps.gastos_representacion), 2) as avg_gastos_rep
                    FROM {$this->table} eps
                    WHERE eps.tipo_planilla_id = ?
                    AND eps.is_active = 1
                    AND (eps.fecha_fin IS NULL OR eps.fecha_fin >= CURDATE())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tipoPlanillaId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            error_log("Error getting salary stats: " . $e->getMessage());
            return [];
        }
    }
}
