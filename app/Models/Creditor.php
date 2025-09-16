<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;
use Exception;

/**
 * Modelo Creditor - Gestión de acreedores del sistema
 * Integra con tabla legacy 'creditors'
 */
class Creditor extends Model
{
    public $table = 'creditors';
    protected $fillable = ['description', 'amount', 'creditor_id', 'employee_id', 'tipo', 'activo', 'observaciones'];

    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Tipos de acreedores disponibles
     */
    const TIPOS_ACREEDOR = [
        'PERSONAL' => 'Préstamo Personal',
        'VEHICULAR' => 'Préstamo Vehicular', 
        'HIPOTECARIO' => 'Crédito Hipotecario',
        'EMBARGO' => 'Embargo de Sueldo',
        'JUDICIAL' => 'Retención Judicial',
        'PENSION' => 'Pensión Alimenticia',
        'SEGURO' => 'Seguro/Prima',
        'COOPERATIVA' => 'Cooperativa',
        'OTRO' => 'Otro'
    ];

    /**
     * Estados del acreedor
     */
    const ESTADOS = [
        'ACTIVO' => 1,
        'INACTIVO' => 0,
        'PAUSADO' => 2
    ];

    /**
     * Obtener acreedor por ID
     */
    public function findById($id)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error finding creditor by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los acreedores activos
     */
    public function getAllActive()
    {
        try {
            $sql = "SELECT c.*, 
                           COUNT(d.id) as empleados_asignados,
                           SUM(d.amount) as monto_total_asignado
                    FROM {$this->table} c
                    LEFT JOIN deductions d ON c.id = d.creditor_id
                    GROUP BY c.id
                    ORDER BY c.description";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting active creditors: " . $e->getMessage());
            return [];
        }
    }
    public function getActive()
    {
        try {
            $sql = "SELECT c.*, 
                           COUNT(d.id) as empleados_asignados,
                           SUM(d.amount) as monto_total_asignado
                    FROM {$this->table} c
                    LEFT JOIN deductions d ON c.id = d.creditor_id
                    GROUP BY c.id
                    ORDER BY c.description";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting active creditors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear nuevo acreedor
     */
    public function create($data)
    {
        try {
            // Validaciones específicas
            $validation = $this->validateCreditorData($data);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            // Generar código de acreedor si no se proporciona
            if (empty($data['creditor_id'])) {
                $data['creditor_id'] = $this->generateCreditorCode($data['tipo'] ?? 'OTRO');
            }

            $sql = "INSERT INTO {$this->table} (description, amount, creditor_id, employee_id, tipo, activo, observaciones)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['description'],
                $data['amount'] ?? 0,
                $data['creditor_id'],
                $data['employee_id'] ?? '',
                $data['tipo'] ?? 'OTRO',
                $data['activo'] ?? 1,
                $data['observaciones'] ?? ''
            ]);

            if ($result) {
                $creditorId = $this->pdo->lastInsertId();
                // Acreedor creado exitosamente
                return ['success' => true, 'id' => $creditorId];
            }

            return ['success' => false, 'message' => 'Error al crear acreedor'];
        } catch (Exception $e) {
            error_log("Error creating creditor: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en creación de acreedor'];
        }
    }

    /**
     * Actualizar acreedor
     */
    public function update($id, $data)
    {
        try {
            // Validaciones específicas
            $validation = $this->validateCreditorData($data, $id);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            $sql = "UPDATE {$this->table} 
                    SET description = ?, amount = ?, creditor_id = ?, employee_id = ?, tipo = ?, activo = ?, observaciones = ?
                    WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['description'],
                $data['amount'] ?? 0,
                $data['creditor_id'] ?? '',
                $data['employee_id'] ?? '',
                $data['tipo'] ?? 'OTRO',
                $data['activo'] ?? 1,
                $data['observaciones'] ?? '',
                $id
            ]);

            if ($result) {
                // Acreedor actualizado exitosamente
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'Error al actualizar acreedor'];
        } catch (Exception $e) {
            error_log("Error updating creditor: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en actualización de acreedor'];
        }
    }

    /**
     * Eliminar acreedor
     */
    public function delete($id)
    {
        try {
            // Verificar si tiene deducciones asignadas
            if ($this->hasActiveDeductions($id)) {
                return ['success' => false, 'message' => 'No se puede eliminar un acreedor con deducciones activas'];
            }

            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);

            if ($result) {
                // Acreedor eliminado exitosamente
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'Error al eliminar acreedor'];
        } catch (Exception $e) {
            error_log("Error deleting creditor: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en eliminación de acreedor'];
        }
    }

    /**
     * Obtener acreedores con sus deducciones
     */
    public function getWithDeductions($id)
    {
        try {
            // Obtener acreedor
            $creditor = $this->findById($id);
            if (!$creditor) {
                return null;
            }

            // Obtener deducciones asociadas
            $sql = "SELECT d.*, e.firstname, e.lastname, e.employee_id as emp_code
                    FROM deductions d
                    LEFT JOIN employees e ON d.employee_id = e.employee_id
                    WHERE d.creditor_id = ?
                    ORDER BY e.firstname, e.lastname";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            
            $creditor['deductions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $creditor;
        } catch (Exception $e) {
            error_log("Error getting creditor with deductions: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar acreedores por término
     */
    public function search($term)
    {
        try {
            $sql = "SELECT c.*, 
                           COUNT(d.id) as empleados_asignados
                    FROM {$this->table} c
                    LEFT JOIN deductions d ON c.id = d.creditor_id
                    WHERE c.description LIKE ? OR c.creditor_id LIKE ?
                    GROUP BY c.id
                    ORDER BY c.description
                    LIMIT 20";
            
            $searchTerm = "%$term%";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error searching creditors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de acreedores
     */
    public function getStats()
    {
        try {
            $stats = [];
            
            // Total acreedores
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['total_acreedores'] = $stmt->fetchColumn();
            
            // Total deducciones activas
            $sql = "SELECT COUNT(*) as total FROM deductions";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['total_deducciones'] = $stmt->fetchColumn();
            
            // Monto total de deducciones
            $sql = "SELECT SUM(amount) as total FROM deductions";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['monto_total'] = $stmt->fetchColumn() ?: 0;
            
            // Empleados con deducciones
            $sql = "SELECT COUNT(DISTINCT employee_id) as total FROM deductions";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stats['empleados_con_deducciones'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error getting creditor stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validar datos del acreedor
     */
    private function validateCreditorData($data, $excludeId = null)
    {
        $errors = [];

        // Descripción requerida
        if (empty($data['description'])) {
            $errors[] = 'La descripción del acreedor es requerida';
        } elseif (strlen($data['description']) < 3) {
            $errors[] = 'La descripción debe tener al menos 3 caracteres';
        }

        // Monto debe ser numérico si se proporciona
        if (isset($data['amount']) && !is_numeric($data['amount'])) {
            $errors[] = 'El monto debe ser un valor numérico';
        }

        return [
            'valid' => empty($errors),
            'message' => implode(', ', $errors)
        ];
    }

    /**
     * Generar código de acreedor
     */
    private function generateCreditorCode($tipo = 'OTRO')
    {
        try {
            $prefix = substr($tipo, 0, 3);
            
            // Buscar último código con este prefijo
            $sql = "SELECT creditor_id FROM {$this->table} 
                    WHERE creditor_id LIKE ? 
                    ORDER BY creditor_id DESC 
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$prefix . '%']);
            $lastCode = $stmt->fetchColumn();
            
            if ($lastCode) {
                // Extraer número y aumentar
                $number = intval(substr($lastCode, strlen($prefix))) + 1;
            } else {
                $number = 1;
            }
            
            return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log("Error generating creditor code: " . $e->getMessage());
            return 'ACR' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Verificar si el acreedor tiene deducciones activas
     */
    private function hasActiveDeductions($creditorId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM deductions WHERE creditor_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$creditorId]);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking active deductions: " . $e->getMessage());
            return true; // Por seguridad
        }
    }

    /**
     * Obtener opciones para selects
     */
    public function getOptions()
    {
        try {
            $sql = "SELECT id, description, creditor_id FROM {$this->table} ORDER BY description";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting creditor options: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tipos de acreedor
     */
    public function getTiposAcreedor()
    {
        return self::TIPOS_ACREEDOR;
    }

    /**
     * Obtener empleados con deducciones de un acreedor
     */
    public function getEmployeesWithDeductions($creditorId)
    {
        try {
            $sql = "SELECT d.*, e.firstname, e.lastname, e.employee_id as emp_code,
                           p.descripcion as position_name
                    FROM deductions d
                    JOIN employees e ON d.employee_id = e.employee_id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    WHERE d.creditor_id = ?
                    ORDER BY e.firstname, e.lastname";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$creditorId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting employees with deductions: " . $e->getMessage());
            return [];
        }
    }
}