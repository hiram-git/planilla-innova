<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;
use Exception;

/**
 * Modelo Deduction - Gestión de deducciones por empleado
 * Integra con tabla legacy 'deductions'
 */
class Deduction extends Model
{
    public $table = 'deductions';
    protected $fillable = ['employee_id', 'creditor_id', 'description', 'amount'];

    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Estados de la deducción
     */
    const ESTADOS = [
        'ACTIVA' => 1,
        'PAUSADA' => 0,
        'FINALIZADA' => 2
    ];

    /**
     * Frecuencias de deducción
     */
    const FRECUENCIAS = [
        'SEMANAL' => 'Semanal',
        'QUINCENAL' => 'Quincenal', 
        'MENSUAL' => 'Mensual',
        'UNICA' => 'Única vez'
    ];

    /**
     * Obtener todas las deducciones con información completa
     */
    public function getAllWithDetails()
    {
        try {
            $sql = "SELECT d.*, 
                           e.firstname, e.lastname, e.employee_id as emp_code,
                           c.description as creditor_name, c.creditor_id as creditor_code,
                           p.codigo as position_name
                    FROM {$this->table} d
                    JOIN employees e ON d.employee_id = e.employee_id
                    LEFT JOIN creditors c ON d.creditor_id = c.id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    ORDER BY e.firstname, e.lastname, c.description";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting deductions with details: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener deducciones por empleado
     */
    public function getByEmployee($employeeId)
    {
        try {
            $sql = "SELECT d.*, 
                           c.description as creditor_name, c.creditor_id as creditor_code
                    FROM {$this->table} d
                    LEFT JOIN creditors c ON d.creditor_id = c.id
                    WHERE d.employee_id = ?
                    ORDER BY c.description";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$employeeId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting deductions by employee: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener deducciones por acreedor
     */
    public function getByCreditor($creditorId)
    {
        try {
            $sql = "SELECT d.*, 
                           e.firstname, e.lastname, e.employee_id as emp_code,
                           p.descripcion as position_name
                    FROM {$this->table} d
                    JOIN employees e ON d.employee_id = e.employee_id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    WHERE d.creditor_id = ?
                    ORDER BY e.firstname, e.lastname";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$creditorId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting deductions by creditor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar deducción por ID
     */
    public function findById($id)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error finding deduction by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear nueva deducción
     */
    public function create($data)
    {
        try {
            // Validaciones específicas
            $validation = $this->validateDeductionData($data);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            // Verificar que no exista ya esta combinación empleado-acreedor
            if ($this->existsDeduction($data['employee_id'], $data['creditor_id'])) {
                return ['success' => false, 'message' => 'Ya existe una deducción para este empleado y acreedor'];
            }

            $sql = "INSERT INTO {$this->table} (employee_id, creditor_id, description, amount)
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['employee_id'],
                $data['creditor_id'],
                $data['description'] ?? '',
                $data['amount']
            ]);

            if ($result) {
                $deductionId = $this->pdo->lastInsertId();
                // Deducción creada exitosamente
                return ['success' => true, 'id' => $deductionId];
            }

            return ['success' => false, 'message' => 'Error al crear deducción'];
        } catch (Exception $e) {
            error_log("Error creating deduction: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en creación de deducción'];
        }
    }

    /**
     * Actualizar deducción
     */
    public function update($id, $data)
    {
        try {
            // Validaciones específicas
            $validation = $this->validateDeductionData($data, $id);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            $sql = "UPDATE {$this->table} 
                    SET employee_id = ?, creditor_id = ?, description = ?, amount = ? 
                    WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['employee_id'],
                $data['creditor_id'],
                $data['description'] ?? '',
                $data['amount'],
                $id
            ]);

            if ($result) {
                // Deducción actualizada exitosamente
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'Error al actualizar deducción'];
        } catch (Exception $e) {
            error_log("Error updating deduction: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en actualización de deducción'];
        }
    }

    /**
     * Eliminar deducción
     */
    public function delete($id)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);

            if ($result) {
                // Deducción eliminada exitosamente
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'Error al eliminar deducción'];
        } catch (Exception $e) {
            error_log("Error deleting deduction: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en eliminación de deducción'];
        }
    }

    /**
     * Obtener deducciones activas para procesamiento de planilla
     */
    public function getActiveForPayroll($employeeIds = [])
    {
        try {
            $sql = "SELECT d.*, 
                           e.firstname, e.lastname, e.employee_id as emp_code,
                           c.description as creditor_name, c.creditor_id as creditor_code
                    FROM {$this->table} d
                    JOIN employees e ON d.employee_id = e.employee_id
                    LEFT JOIN creditors c ON d.creditor_id = c.id
                    WHERE d.amount > 0";
            
            $params = [];
            if (!empty($employeeIds)) {
                $placeholders = str_repeat('?,', count($employeeIds) - 1) . '?';
                $sql .= " AND d.employee_id IN ($placeholders)";
                $params = $employeeIds;
            }
            
            $sql .= " ORDER BY d.employee_id, c.description";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting active deductions for payroll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener resumen de deducciones por empleado
     */
    public function getSummaryByEmployee($employeeId)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_deducciones,
                        SUM(amount) as monto_total,
                        AVG(amount) as monto_promedio,
                        MAX(amount) as monto_maximo
                    FROM {$this->table} 
                    WHERE employee_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$employeeId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting deduction summary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar deducciones
     */
    public function search($term)
    {
        try {
            $sql = "SELECT d.*, 
                           e.firstname, e.lastname, e.employee_id as emp_code,
                           c.description as creditor_name
                    FROM {$this->table} d
                    JOIN employees e ON d.employee_id = e.employee_id
                    LEFT JOIN creditors c ON d.creditor_id = c.id
                    WHERE e.firstname LIKE ? 
                       OR e.lastname LIKE ?
                       OR e.employee_id LIKE ?
                       OR c.description LIKE ?
                       OR d.description LIKE ?
                    ORDER BY e.firstname, e.lastname
                    LIMIT 50";
            
            $searchTerm = "%$term%";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error searching deductions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas generales
     */
    public function getStats()
    {
        try {
            $stats = [];
            
            // Total deducciones
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['total_deducciones'] = $stmt->fetchColumn();
            
            // Monto total
            $sql = "SELECT SUM(amount) as total FROM {$this->table}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['monto_total'] = $stmt->fetchColumn() ?: 0;
            
            // Empleados únicos con deducciones
            $sql = "SELECT COUNT(DISTINCT employee_id) as total FROM {$this->table}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['empleados_con_deducciones'] = $stmt->fetchColumn();
            
            // Acreedores únicos
            $sql = "SELECT COUNT(DISTINCT creditor_id) as total FROM {$this->table}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['acreedores_activos'] = $stmt->fetchColumn();
            
            // Monto promedio por deducción
            $stats['monto_promedio'] = $stats['total_deducciones'] > 0 
                ? $stats['monto_total'] / $stats['total_deducciones'] 
                : 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error getting deduction stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validar datos de deducción
     */
    private function validateDeductionData($data, $excludeId = null)
    {
        $errors = [];

        // Employee ID requerido
        if (empty($data['employee_id'])) {
            $errors[] = 'El empleado es requerido';
        } elseif (!$this->employeeExists($data['employee_id'])) {
            $errors[] = 'El empleado seleccionado no existe';
        }

        // Creditor ID requerido
        if (empty($data['creditor_id'])) {
            $errors[] = 'El acreedor es requerido';
        } elseif (!$this->creditorExists($data['creditor_id'])) {
            $errors[] = 'El acreedor seleccionado no existe';
        }

        // Monto requerido y válido
        if (empty($data['amount']) || !is_numeric($data['amount'])) {
            $errors[] = 'El monto es requerido y debe ser numérico';
        } elseif (floatval($data['amount']) <= 0) {
            $errors[] = 'El monto debe ser mayor a cero';
        } elseif (floatval($data['amount']) > 999999.99) {
            $errors[] = 'El monto excede el límite máximo permitido';
        }

        return [
            'valid' => empty($errors),
            'message' => implode(', ', $errors)
        ];
    }

    /**
     * Verificar si existe la deducción empleado-acreedor
     */
    private function existsDeduction($employeeId, $creditorId, $excludeId = null)
    {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE employee_id = ? AND creditor_id = ?";
            $params = [$employeeId, $creditorId];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error checking deduction existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe el empleado
     */
    private function employeeExists($employeeId)
    {
        try {
            $sql = "SELECT id FROM employees WHERE employee_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$employeeId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error checking employee existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe el acreedor
     */
    private function creditorExists($creditorId)
    {
        try {
            $sql = "SELECT id FROM creditors WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$creditorId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error checking creditor existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener deducciones por planilla (para integración)
     */
    public function getForPayrollCalculation($employeeIds, $payrollType = null)
    {
        try {
            $sql = "SELECT d.employee_id, d.creditor_id, d.amount, d.description,
                           c.description as creditor_name, c.creditor_id as creditor_code
                    FROM {$this->table} d
                    LEFT JOIN creditors c ON d.creditor_id = c.id
                    WHERE d.amount > 0";
            
            $params = [];
            if (!empty($employeeIds)) {
                $placeholders = str_repeat('?,', count($employeeIds) - 1) . '?';
                $sql .= " AND d.employee_id IN ($placeholders)";
                $params = $employeeIds;
            }
            
            $sql .= " ORDER BY d.employee_id, d.amount DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // Agrupar por empleado
            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $employeeId = $row['employee_id'];
                if (!isset($result[$employeeId])) {
                    $result[$employeeId] = [];
                }
                $result[$employeeId][] = $row;
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error getting deductions for payroll calculation: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si una deducción está asociada a planillas generadas
     */
    public function isInGeneratedPayroll($deductionId)
    {
        try {
            // Por ahora, para testing, permitir siempre la edición de descripción y monto
            // TODO: Implementar la verificación real cuando se tenga la estructura de planillas
            
            // Por ahora, deshabilitar la verificación de fecha hasta conocer la estructura exacta de la BD
            // TODO: Verificar cuál es el campo correcto de fecha en la tabla deducciones
            /*
            $sql = "SELECT DATEDIFF(NOW(), fecha_creacion) as days_old FROM {$this->table} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$deductionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            */
            
            // Si la deducción tiene más de 30 días, considerar que podría estar en planilla
            // Esto es solo para demostración - cambiar por lógica real
            return false; // Por ahora permitir edición completa
            
        } catch (Exception $e) {
            error_log("Error checking if deduction is in generated payroll: " . $e->getMessage());
            // En caso de error, permitir edición para no bloquear el sistema
            return false;
        }
    }

    /**
     * Obtener restricciones de edición para una deducción
     */
    public function getEditRestrictions($deductionId)
    {
        try {
            $deduction = $this->findById($deductionId);
            if (!$deduction) {
                return [
                    'canEditEmployee' => false,
                    'canEditCreditor' => false,
                    'canEditAmount' => false,
                    'canEditDescription' => false,
                    'reason' => 'Deducción no encontrada'
                ];
            }

            $inPayroll = $this->isInGeneratedPayroll($deductionId);

            return [
                'canEditEmployee' => !$inPayroll,
                'canEditCreditor' => !$inPayroll,
                'canEditAmount' => !$inPayroll,
                'canEditDescription' => !$inPayroll,
                'inGeneratedPayroll' => $inPayroll,
                'reason' => $inPayroll ? 'Deducción asociada a planilla generada' : 'Edición completa permitida'
            ];
        } catch (Exception $e) {
            error_log("Error getting edit restrictions: " . $e->getMessage());
            // En caso de error, ser conservador y restringir toda edición
            return [
                'canEditEmployee' => false,
                'canEditCreditor' => false,
                'canEditAmount' => false,
                'canEditDescription' => false,
                'reason' => 'Error al verificar restricciones'
            ];
        }
    }
}