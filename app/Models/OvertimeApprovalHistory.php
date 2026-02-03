<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * OvertimeApprovalHistory Model
 *
 * Gestiona historial auditable de cambios en aprobaciones
 *
 * @package App\Models
 * @version 1.0
 * @date 2026-02-02
 */
class OvertimeApprovalHistory extends Model
{
    public $table = 'overtime_approval_history';

    public $fillable = [
        'approval_id', 'action', 'performed_by',
        'previous_status', 'new_status',
        'comments', 'changes_summary',
        'ip_address', 'user_agent'
    ];

    /**
     * Constantes de acción
     */
    const ACTION_CREATED = 'CREADO';
    const ACTION_APPROVED_L1 = 'APROBADO_L1';
    const ACTION_APPROVED_L2 = 'APROBADO_L2';
    const ACTION_APPROVED_FINAL = 'APROBADO_FINAL';
    const ACTION_REJECTED = 'RECHAZADO';
    const ACTION_MODIFIED = 'MODIFICADO';
    const ACTION_CANCELLED = 'CANCELADO';
    const ACTION_REOPENED = 'REABIERTO';

    /**
     * Registrar acción en historial
     *
     * @param int $approvalId ID de la solicitud
     * @param string $action Acción realizada
     * @param int $performedBy ID del usuario
     * @param string|null $previousStatus Estado anterior
     * @param string|null $newStatus Nuevo estado
     * @param string|null $comments Comentarios
     * @param string|null $changesSummary Resumen de cambios
     * @return int|false ID del registro o false en error
     */
    public function logAction(
        int $approvalId,
        string $action,
        int $performedBy,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        ?string $comments = null,
        ?string $changesSummary = null
    ) {
        try {
            $data = [
                'approval_id' => $approvalId,
                'action' => $action,
                'performed_by' => $performedBy,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'comments' => $comments,
                'changes_summary' => $changesSummary,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];

            return $this->create($data);

        } catch (PDOException $e) {
            error_log("Error registrando acción en historial: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener historial de una solicitud
     *
     * @param int $approvalId ID de la solicitud
     * @return array Array de registros de historial
     */
    public function getByApprovalId(int $approvalId): array
    {
        try {
            $sql = "SELECT
                        h.*,
                        a.firstname,
                        a.lastname,
                        CONCAT(a.firstname, ' ', a.lastname) as performed_by_name
                    FROM {$this->table} h
                    LEFT JOIN admin a ON h.performed_by = a.id
                    WHERE h.approval_id = ?
                    ORDER BY h.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$approvalId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo historial: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener historial con información detallada
     *
     * @param int $approvalId ID de la solicitud
     * @return array Array de registros con detalles
     */
    public function getDetailedHistory(int $approvalId): array
    {
        try {
            $sql = "SELECT
                        h.*,
                        a.firstname as user_firstname,
                        a.lastname as user_lastname,
                        a.email as user_email,
                        CONCAT(a.firstname, ' ', a.lastname) as user_full_name,
                        DATE_FORMAT(h.created_at, '%d/%m/%Y %H:%i:%s') as formatted_date,
                        DATE_FORMAT(h.created_at, '%H:%i:%s') as formatted_time
                    FROM {$this->table} h
                    LEFT JOIN admin a ON h.performed_by = a.id
                    WHERE h.approval_id = ?
                    ORDER BY h.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$approvalId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo historial detallado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener últimas acciones de un usuario
     *
     * @param int $userId ID del usuario
     * @param int $limit Límite de resultados
     * @return array Array de acciones
     */
    public function getRecentActionsByUser(int $userId, int $limit = 10): array
    {
        try {
            $sql = "SELECT
                        h.*,
                        oa.employee_id,
                        e.firstname as employee_firstname,
                        e.lastname as employee_lastname,
                        CONCAT(e.firstname, ' ', e.lastname) as employee_name
                    FROM {$this->table} h
                    INNER JOIN overtime_approvals oa ON h.approval_id = oa.id
                    INNER JOIN employees e ON oa.employee_id = e.id
                    WHERE h.performed_by = ?
                    ORDER BY h.created_at DESC
                    LIMIT ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo acciones recientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar acciones por tipo
     *
     * @param int $approvalId ID de la solicitud
     * @return array Array con conteo por acción
     */
    public function countActionsByType(int $approvalId): array
    {
        try {
            $sql = "SELECT
                        action,
                        COUNT(*) as count
                    FROM {$this->table}
                    WHERE approval_id = ?
                    GROUP BY action
                    ORDER BY action";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$approvalId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error contando acciones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener última acción de una solicitud
     *
     * @param int $approvalId ID de la solicitud
     * @return array|null Última acción o null
     */
    public function getLastAction(int $approvalId): ?array
    {
        try {
            $sql = "SELECT
                        h.*,
                        a.firstname,
                        a.lastname,
                        CONCAT(a.firstname, ' ', a.lastname) as performed_by_name
                    FROM {$this->table} h
                    LEFT JOIN admin a ON h.performed_by = a.id
                    WHERE h.approval_id = ?
                    ORDER BY h.created_at DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$approvalId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;

        } catch (PDOException $e) {
            error_log("Error obteniendo última acción: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener timeline completo de una solicitud
     * (Historial ordenado cronológicamente con formato)
     *
     * @param int $approvalId ID de la solicitud
     * @return array Array de eventos del timeline
     */
    public function getTimeline(int $approvalId): array
    {
        try {
            $sql = "SELECT
                        h.id,
                        h.action,
                        h.performed_by,
                        CONCAT(a.firstname, ' ', a.lastname) as performed_by_name,
                        h.previous_status,
                        h.new_status,
                        h.comments,
                        h.created_at,
                        DATE_FORMAT(h.created_at, '%d/%m/%Y') as date,
                        DATE_FORMAT(h.created_at, '%H:%i') as time,
                        TIMESTAMPDIFF(MINUTE, h.created_at, NOW()) as minutes_ago
                    FROM {$this->table} h
                    LEFT JOIN admin a ON h.performed_by = a.id
                    WHERE h.approval_id = ?
                    ORDER BY h.created_at ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$approvalId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo timeline: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar en historial por acción específica
     *
     * @param int $approvalId ID de la solicitud
     * @param string $action Acción a buscar
     * @return array|null Registro de la acción o null
     */
    public function findByAction(int $approvalId, string $action): ?array
    {
        try {
            $sql = "SELECT
                        h.*,
                        a.firstname,
                        a.lastname
                    FROM {$this->table} h
                    LEFT JOIN admin a ON h.performed_by = a.id
                    WHERE h.approval_id = ?
                      AND h.action = ?
                    ORDER BY h.created_at DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$approvalId, $action]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;

        } catch (PDOException $e) {
            error_log("Error buscando por acción: " . $e->getMessage());
            return null;
        }
    }
}
