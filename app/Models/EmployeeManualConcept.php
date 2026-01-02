<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * EmployeeManualConcept Model
 *
 * Gestiona asignaciones manuales de conceptos a empleados específicos.
 * Permite a RR.HH. asignar ausencias, tardanzas, bonos y otros conceptos
 * sin necesidad de sincronización automática de marcaciones.
 *
 * @package App\Models
 * @version 3.5.18
 */
class EmployeeManualConcept extends Model
{
    public $table = 'employee_manual_concepts';

    public $fillable = [
        'employee_id', 'concepto_id', 'tipo_planilla_id', 'frecuencia_id',
        'unidad', 'hora', 'monto_fijo', 'observaciones',
        'fecha_inicio', 'fecha_fin', 'aplica_una_vez',
        'estado', 'planilla_id', 'fecha_aplicacion', 'created_by'
    ];

    /**
     * Estados de conceptos manuales
     */
    const ESTADO_ACTIVO = 'ACTIVO';
    const ESTADO_APLICADO = 'APLICADO';
    const ESTADO_CANCELADO = 'CANCELADO';

    /**
     * Obtener todos los conceptos manuales con información completa
     *
     * @param array $filters Filtros opcionales
     * @return array
     */
    public function getAllWithDetails($filters = [])
    {
        try {
            $sql = "SELECT
                        emc.*,
                        e.firstname, e.lastname, e.employee_id as emp_code,
                        c.concepto as concepto_code, c.descripcion as concepto_descripcion,
                        c.tipo_concepto,
                        tp.nombre as tipo_planilla_nombre,
                        f.nombre as frecuencia_nombre,
                        u.username as created_by_username
                    FROM {$this->table} emc
                    INNER JOIN employees e ON emc.employee_id = e.id
                    INNER JOIN concepto c ON emc.concepto_id = c.id
                    LEFT JOIN tipos_planilla tp ON emc.tipo_planilla_id = tp.id
                    LEFT JOIN frecuencias f ON emc.frecuencia_id = f.id
                    LEFT JOIN users u ON emc.created_by = u.id
                    WHERE 1=1";

            $params = [];

            // Aplicar filtros
            if (!empty($filters['employee_id'])) {
                $sql .= " AND emc.employee_id = ?";
                $params[] = $filters['employee_id'];
            }

            if (!empty($filters['concepto_id'])) {
                $sql .= " AND emc.concepto_id = ?";
                $params[] = $filters['concepto_id'];
            }

            if (!empty($filters['estado'])) {
                $sql .= " AND emc.estado = ?";
                $params[] = $filters['estado'];
            }

            if (!empty($filters['tipo_planilla_id'])) {
                $sql .= " AND (emc.tipo_planilla_id = ? OR emc.tipo_planilla_id IS NULL)";
                $params[] = $filters['tipo_planilla_id'];
            }

            if (!empty($filters['fecha_desde'])) {
                $sql .= " AND emc.fecha_inicio >= ?";
                $params[] = $filters['fecha_desde'];
            }

            if (!empty($filters['fecha_hasta'])) {
                $sql .= " AND (emc.fecha_fin <= ? OR emc.fecha_fin IS NULL)";
                $params[] = $filters['fecha_hasta'];
            }

            $sql .= " ORDER BY emc.created_at DESC, e.lastname, e.firstname";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting manual concepts with details: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos manuales activos para un empleado en una fecha específica
     *
     * @param int $employeeId
     * @param string $fecha Fecha de la planilla
     * @param int|null $tipoPlanillaId
     * @param int|null $frecuenciaId
     * @return array
     */
    public function getActiveForEmployee($employeeId, $fecha, $tipoPlanillaId = null, $frecuenciaId = null)
    {
        try {
            $sql = "SELECT
                        emc.*,
                        c.concepto as concepto_code,
                        c.descripcion as concepto_descripcion,
                        c.tipo_concepto,
                        c.formula
                    FROM {$this->table} emc
                    INNER JOIN concepto c ON emc.concepto_id = c.id
                    WHERE emc.employee_id = ?
                    AND emc.estado = ?
                    AND emc.fecha_inicio <= ?
                    AND (emc.fecha_fin IS NULL OR emc.fecha_fin >= ?)";

            $params = [$employeeId, self::ESTADO_ACTIVO, $fecha, $fecha];

            // Filtrar por tipo de planilla (aplica si coincide o es NULL)
            if ($tipoPlanillaId) {
                $sql .= " AND (emc.tipo_planilla_id = ? OR emc.tipo_planilla_id IS NULL)";
                $params[] = $tipoPlanillaId;
            }

            // Filtrar por frecuencia (aplica si coincide o es NULL)
            if ($frecuenciaId) {
                $sql .= " AND (emc.frecuencia_id = ? OR emc.frecuencia_id IS NULL)";
                $params[] = $frecuenciaId;
            }

            $sql .= " ORDER BY c.tipo_concepto DESC, c.descripcion";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting active manual concepts for employee: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marcar concepto manual como aplicado
     *
     * @param int $id
     * @param int $planillaId
     * @return bool
     */
    public function markAsApplied($id, $planillaId)
    {
        try {
            $sql = "UPDATE {$this->table}
                    SET estado = ?,
                        planilla_id = ?,
                        fecha_aplicacion = NOW()
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([self::ESTADO_APLICADO, $planillaId, $id]);

        } catch (PDOException $e) {
            error_log("Error marking manual concept as applied: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancelar concepto manual
     *
     * @param int $id
     * @return bool
     */
    public function cancel($id)
    {
        try {
            $sql = "UPDATE {$this->table}
                    SET estado = ?
                    WHERE id = ? AND estado = ?";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([self::ESTADO_CANCELADO, $id, self::ESTADO_ACTIVO]);

        } catch (PDOException $e) {
            error_log("Error canceling manual concept: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reactivar concepto manual cancelado
     *
     * @param int $id
     * @return bool
     */
    public function reactivate($id)
    {
        try {
            $sql = "UPDATE {$this->table}
                    SET estado = ?
                    WHERE id = ? AND estado = ?";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([self::ESTADO_ACTIVO, $id, self::ESTADO_CANCELADO]);

        } catch (PDOException $e) {
            error_log("Error reactivating manual concept: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de conceptos manuales
     *
     * @return array
     */
    public function getStats()
    {
        try {
            $sql = "SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as activos,
                        SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as aplicados,
                        SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as cancelados,
                        COUNT(DISTINCT employee_id) as empleados_afectados,
                        COUNT(DISTINCT concepto_id) as conceptos_diferentes
                    FROM {$this->table}";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([self::ESTADO_ACTIVO, self::ESTADO_APLICADO, self::ESTADO_CANCELADO]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            error_log("Error getting manual concepts stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos manuales por empleado
     *
     * @param int $employeeId
     * @param string|null $estado
     * @return array
     */
    public function getByEmployee($employeeId, $estado = null)
    {
        try {
            $sql = "SELECT
                        emc.*,
                        c.concepto as concepto_code,
                        c.descripcion as concepto_descripcion,
                        c.tipo_concepto
                    FROM {$this->table} emc
                    INNER JOIN concepto c ON emc.concepto_id = c.id
                    WHERE emc.employee_id = ?";

            $params = [$employeeId];

            if ($estado) {
                $sql .= " AND emc.estado = ?";
                $params[] = $estado;
            }

            $sql .= " ORDER BY emc.fecha_inicio DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting manual concepts by employee: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un concepto manual puede ser editado
     *
     * @param int $id
     * @return bool
     */
    public function canEdit($id)
    {
        try {
            $concept = $this->find($id);

            if (!$concept) {
                return false;
            }

            // Solo se pueden editar conceptos ACTIVOS
            return $concept['estado'] === self::ESTADO_ACTIVO;

        } catch (PDOException $e) {
            error_log("Error checking if manual concept can be edited: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un concepto manual puede ser eliminado
     *
     * @param int $id
     * @return bool
     */
    public function canDelete($id)
    {
        try {
            $concept = $this->find($id);

            if (!$concept) {
                return false;
            }

            // Solo se pueden eliminar conceptos ACTIVOS o CANCELADOS
            // No se pueden eliminar conceptos APLICADOS
            return in_array($concept['estado'], [self::ESTADO_ACTIVO, self::ESTADO_CANCELADO]);

        } catch (PDOException $e) {
            error_log("Error checking if manual concept can be deleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar datos antes de crear/actualizar
     *
     * @param array $data
     * @param int|null $excludeId
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate($data, $excludeId = null)
    {
        $errors = [];

        // Validar employee_id
        if (empty($data['employee_id'])) {
            $errors[] = 'El empleado es requerido';
        }

        // Validar concepto_id
        if (empty($data['concepto_id'])) {
            $errors[] = 'El concepto es requerido';
        }

        // Validar fecha_inicio
        if (empty($data['fecha_inicio'])) {
            $errors[] = 'La fecha de inicio es requerida';
        }

        // Validar que fecha_fin sea posterior a fecha_inicio
        if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin'])) {
            if (strtotime($data['fecha_fin']) < strtotime($data['fecha_inicio'])) {
                $errors[] = 'La fecha fin debe ser posterior a la fecha de inicio';
            }
        }

        // Validar unidad o monto_fijo
        if (empty($data['monto_fijo'])) {
            if (empty($data['unidad']) || $data['unidad'] <= 0) {
                $errors[] = 'Debe especificar una unidad válida o un monto fijo';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Obtener conceptos que vencen pronto
     *
     * @param int $dias Días de anticipación
     * @return array
     */
    public function getExpiringSoon($dias = 7)
    {
        try {
            $fechaLimite = date('Y-m-d', strtotime("+{$dias} days"));

            $sql = "SELECT
                        emc.*,
                        e.firstname, e.lastname, e.employee_id as emp_code,
                        c.descripcion as concepto_descripcion
                    FROM {$this->table} emc
                    INNER JOIN employees e ON emc.employee_id = e.id
                    INNER JOIN concepto c ON emc.concepto_id = c.id
                    WHERE emc.estado = ?
                    AND emc.fecha_fin IS NOT NULL
                    AND emc.fecha_fin BETWEEN CURDATE() AND ?
                    ORDER BY emc.fecha_fin ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([self::ESTADO_ACTIVO, $fechaLimite]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting expiring manual concepts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos manuales activos para una planilla
     * Agrupa por empleado para facilitar el procesamiento
     *
     * @param string $fecha Fecha de la planilla
     * @param int|null $tipoPlanillaId
     * @param int|null $frecuenciaId
     * @return array Conceptos manuales agrupados por employee_id
     */
    public function getActiveForPayroll($fecha, $tipoPlanillaId = null, $frecuenciaId = null)
    {
        try {
            $sql = "SELECT
                        emc.*,
                        c.concepto as concepto_code,
                        c.descripcion as concepto_descripcion,
                        c.tipo_concepto,
                        c.formula
                    FROM {$this->table} emc
                    INNER JOIN concepto c ON emc.concepto_id = c.id
                    WHERE emc.estado = ?
                    AND emc.fecha_inicio <= ?
                    AND (emc.fecha_fin IS NULL OR emc.fecha_fin >= ?)";

            $params = [self::ESTADO_ACTIVO, $fecha, $fecha];

            // Filtrar por tipo de planilla (aplica si coincide o es NULL)
            if ($tipoPlanillaId) {
                $sql .= " AND (emc.tipo_planilla_id = ? OR emc.tipo_planilla_id IS NULL)";
                $params[] = $tipoPlanillaId;
            }

            // Filtrar por frecuencia (aplica si coincide o es NULL)
            if ($frecuenciaId) {
                $sql .= " AND (emc.frecuencia_id = ? OR emc.frecuencia_id IS NULL)";
                $params[] = $frecuenciaId;
            }

            $sql .= " ORDER BY emc.employee_id, c.tipo_concepto DESC, c.descripcion";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Agrupar por employee_id para fácil acceso
            $grouped = [];
            foreach ($results as $row) {
                $employeeId = $row['employee_id'];
                if (!isset($grouped[$employeeId])) {
                    $grouped[$employeeId] = [];
                }
                $grouped[$employeeId][] = $row;
            }

            return $grouped;

        } catch (PDOException $e) {
            error_log("Error getting active manual concepts for payroll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marcar conceptos manuales como aplicados en batch
     * Usado cuando se procesa una planilla completa
     *
     * @param array $conceptIds IDs de conceptos manuales a marcar
     * @param int $planillaId ID de la planilla
     * @return bool
     */
    public function markMultipleAsApplied($conceptIds, $planillaId)
    {
        try {
            if (empty($conceptIds)) {
                return true;
            }

            $placeholders = implode(',', array_fill(0, count($conceptIds), '?'));
            $sql = "UPDATE {$this->table}
                    SET estado = ?,
                        planilla_id = ?,
                        fecha_aplicacion = NOW()
                    WHERE id IN ($placeholders)
                    AND aplica_una_vez = 1";

            $params = array_merge([self::ESTADO_APLICADO, $planillaId], $conceptIds);

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log("Error marking multiple manual concepts as applied: " . $e->getMessage());
            return false;
        }
    }
}
