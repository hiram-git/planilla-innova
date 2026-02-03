<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * OvertimeApproval Model
 *
 * Gestiona solicitudes de aprobación de horas extras
 *
 * @package App\Models
 * @version 1.0
 * @date 2026-02-02
 */
class OvertimeApproval extends Model
{
    public $table = 'overtime_approvals';

    public $fillable = [
        'employee_id', 'period_start', 'period_end',
        'total_overtime_25', 'total_overtime_50', 'total_hours',
        'hourly_rate', 'total_amount',
        'approval_level', 'requires_two_levels', 'current_approver_id',
        'status',
        'approved_by_level_1', 'approved_at_level_1', 'comments_level_1',
        'approved_by_level_2', 'approved_at_level_2', 'comments_level_2',
        'rejected_by', 'rejected_at', 'rejection_reason', 'rejection_level',
        'notes', 'details', 'created_by'
    ];

    /**
     * Constantes de estado
     */
    const STATUS_PENDING = 'PENDIENTE';
    const STATUS_IN_REVIEW = 'EN_REVISION';
    const STATUS_APPROVED = 'APROBADO';
    const STATUS_REJECTED = 'RECHAZADO';
    const STATUS_CANCELLED = 'CANCELADO';

    /**
     * Obtener solicitudes pendientes para un aprobador
     *
     * @param int $approverId ID del aprobador
     * @return array Array de solicitudes pendientes
     */
    public function getPendingForApprover(int $approverId): array
    {
        try {
            $sql = "SELECT * FROM v_overtime_approvals_with_employees
                    WHERE current_approver_id = ?
                      AND status IN (?, ?)
                    ORDER BY created_at ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$approverId, self::STATUS_PENDING, self::STATUS_IN_REVIEW]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo solicitudes pendientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener solicitudes por empleado y rango de fechas
     *
     * @param int $employeeId ID del empleado
     * @param string $startDate Fecha inicio
     * @param string $endDate Fecha fin
     * @return array Array de solicitudes
     */
    public function getByEmployeeAndPeriod(int $employeeId, string $startDate, string $endDate): array
    {
        try {
            $sql = "SELECT * FROM {$this->table}
                    WHERE employee_id = ?
                      AND period_start >= ?
                      AND period_end <= ?
                    ORDER BY period_start DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo solicitudes por empleado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si ya existe solicitud para el período
     *
     * @param int $employeeId ID del empleado
     * @param string $startDate Fecha inicio
     * @param string $endDate Fecha fin
     * @return bool True si existe
     */
    public function existsForPeriod(int $employeeId, string $startDate, string $endDate): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table}
                    WHERE employee_id = ?
                      AND period_start = ?
                      AND period_end = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $startDate, $endDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['count'] > 0;

        } catch (PDOException $e) {
            error_log("Error verificando existencia de solicitud: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de aprobaciones por período
     *
     * @param string $startDate Fecha inicio
     * @param string $endDate Fecha fin
     * @return array Estadísticas agrupadas por status
     */
    public function getStatsByPeriod(string $startDate, string $endDate): array
    {
        try {
            $sql = "SELECT
                        status,
                        COUNT(*) as count,
                        SUM(total_hours) as total_hours,
                        SUM(total_amount) as total_amount
                    FROM {$this->table}
                    WHERE period_start >= ? AND period_end <= ?
                    GROUP BY status";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener horas extras aprobadas para un empleado en un período
     * (Para integración con AttendanceConceptMapper)
     *
     * @param int $employeeId ID del empleado
     * @param string $periodStart Fecha inicio
     * @param string $periodEnd Fecha fin
     * @return array Array con horas aprobadas
     */
    public function getApprovedOvertimeHours(
        int $employeeId,
        string $periodStart,
        string $periodEnd
    ): array {
        try {
            $sql = "SELECT
                        total_overtime_25,
                        total_overtime_50,
                        total_hours,
                        total_amount
                    FROM {$this->table}
                    WHERE employee_id = ?
                      AND period_start = ?
                      AND period_end = ?
                      AND status = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $periodStart, $periodEnd, self::STATUS_APPROVED]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: [
                'total_overtime_25' => 0,
                'total_overtime_50' => 0,
                'total_hours' => 0,
                'total_amount' => 0
            ];

        } catch (PDOException $e) {
            error_log("Error obteniendo horas aprobadas: " . $e->getMessage());
            return [
                'total_overtime_25' => 0,
                'total_overtime_50' => 0,
                'total_hours' => 0,
                'total_amount' => 0
            ];
        }
    }

    /**
     * Obtener solicitudes por estado
     *
     * @param string $status Estado de la solicitud
     * @param int|null $limit Límite de resultados
     * @return array Array de solicitudes
     */
    public function getByStatus(string $status, ?int $limit = null): array
    {
        try {
            $sql = "SELECT * FROM v_overtime_approvals_with_employees
                    WHERE status = ?
                    ORDER BY created_at DESC";

            if ($limit !== null) {
                $sql .= " LIMIT " . (int)$limit;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo solicitudes por estado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener solicitudes con información completa (JOIN con vista)
     *
     * @param array $filters Filtros opcionales ['employee_id', 'status', 'approver_id']
     * @param int|null $limit Límite de resultados
     * @return array Array de solicitudes con datos completos
     */
    public function getWithDetails(array $filters = [], ?int $limit = null): array
    {
        try {
            $sql = "SELECT * FROM v_overtime_approvals_with_employees WHERE 1=1";
            $params = [];

            if (isset($filters['employee_id'])) {
                $sql .= " AND employee_id = ?";
                $params[] = $filters['employee_id'];
            }

            if (isset($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }

            if (isset($filters['approver_id'])) {
                $sql .= " AND current_approver_id = ?";
                $params[] = $filters['approver_id'];
            }

            if (isset($filters['period_start'])) {
                $sql .= " AND period_start >= ?";
                $params[] = $filters['period_start'];
            }

            if (isset($filters['period_end'])) {
                $sql .= " AND period_end <= ?";
                $params[] = $filters['period_end'];
            }

            $sql .= " ORDER BY created_at DESC";

            if ($limit !== null) {
                $sql .= " LIMIT " . (int)$limit;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo solicitudes con detalles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar solicitudes pendientes para un aprobador
     *
     * @param int $approverId ID del aprobador
     * @return int Cantidad de solicitudes pendientes
     */
    public function countPendingForApprover(int $approverId): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table}
                    WHERE current_approver_id = ?
                      AND status IN (?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$approverId, self::STATUS_PENDING, self::STATUS_IN_REVIEW]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($result['count'] ?? 0);

        } catch (PDOException $e) {
            error_log("Error contando solicitudes pendientes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener total de horas y monto pendiente de aprobación
     *
     * @param int|null $approverId ID del aprobador (null = todos)
     * @return array Array con totales
     */
    public function getPendingTotals(?int $approverId = null): array
    {
        try {
            $sql = "SELECT
                        COUNT(*) as count,
                        SUM(total_hours) as total_hours,
                        SUM(total_amount) as total_amount
                    FROM {$this->table}
                    WHERE status IN (?, ?)";

            $params = [self::STATUS_PENDING, self::STATUS_IN_REVIEW];

            if ($approverId !== null) {
                $sql .= " AND current_approver_id = ?";
                $params[] = $approverId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'count' => (int)($result['count'] ?? 0),
                'total_hours' => (float)($result['total_hours'] ?? 0),
                'total_amount' => (float)($result['total_amount'] ?? 0)
            ];

        } catch (PDOException $e) {
            error_log("Error obteniendo totales pendientes: " . $e->getMessage());
            return [
                'count' => 0,
                'total_hours' => 0,
                'total_amount' => 0
            ];
        }
    }

    /**
     * Actualizar estado de la solicitud
     *
     * @param int $approvalId ID de la solicitud
     * @param string $newStatus Nuevo estado
     * @param array $additionalData Datos adicionales a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function updateStatus(int $approvalId, string $newStatus, array $additionalData = []): bool
    {
        try {
            $data = array_merge(['status' => $newStatus], $additionalData);
            return $this->update($approvalId, $data) !== false;

        } catch (PDOException $e) {
            error_log("Error actualizando estado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener solicitud con detalles completos por ID
     *
     * @param int $approvalId ID de la solicitud
     * @return array|null Datos completos de la solicitud
     */
    public function getDetailById(int $approvalId): ?array
    {
        try {
            $sql = "SELECT * FROM v_overtime_approvals_with_employees WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$approvalId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;

        } catch (PDOException $e) {
            error_log("Error obteniendo detalle por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancelar solicitud
     *
     * @param int $approvalId ID de la solicitud
     * @param int $cancelledBy ID del usuario que cancela
     * @param string|null $reason Razón de cancelación
     * @return bool True si se canceló correctamente
     */
    public function cancel(int $approvalId, int $cancelledBy, ?string $reason = null): bool
    {
        try {
            $data = [
                'status' => self::STATUS_CANCELLED,
                'rejected_by' => $cancelledBy,
                'rejected_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason ?? 'Solicitud cancelada',
                'rejection_level' => 'CANCELADO'
            ];

            return $this->update($approvalId, $data) !== false;

        } catch (PDOException $e) {
            error_log("Error cancelando solicitud: " . $e->getMessage());
            return false;
        }
    }
}
