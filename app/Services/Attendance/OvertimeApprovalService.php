<?php

namespace App\Services\Attendance;

use App\Models\OvertimeApproval;
use App\Models\OvertimeApprovalHistory;
use App\Models\Employee;
use App\Core\Database;
use PDO;
use PDOException;
use Exception;

/**
 * OvertimeApprovalService
 *
 * Servicio principal para gestión de aprobaciones de horas extras
 *
 * @package App\Services\Attendance
 * @version 1.0
 * @date 2026-02-02
 */
class OvertimeApprovalService
{
    private OvertimeApproval $approvalModel;
    private OvertimeApprovalHistory $historyModel;
    private Employee $employeeModel;
    private Database $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->approvalModel = new OvertimeApproval();
        $this->historyModel = new OvertimeApprovalHistory();
        $this->employeeModel = new Employee();
    }

    /**
     * Generar solicitudes de aprobación diarias
     *
     * Lee attendance_calculations del día y crea solicitudes automáticas
     * Solo para empleados con permite_horas_extras = 1
     *
     * @param string $date Fecha a procesar (YYYY-MM-DD)
     * @param int $performedBy ID del usuario que ejecuta (sistema = 1)
     * @return array Array con resultados [created, skipped, errors]
     */
    public function generateDailyApprovals(string $date, int $performedBy = 1): array
    {
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        try {
            // Obtener empleados con horas extras del día que permiten HE
            $sql = "SELECT
                        ac.employee_id,
                        e.employee_id as employee_code,
                        CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                        e.permite_horas_extras,
                        ac.overtime_25_hours,
                        ac.overtime_50_hours,
                        (ac.overtime_25_hours + ac.overtime_50_hours) as total_overtime,
                        e.sueldo as base_salary
                    FROM attendance_calculations ac
                    INNER JOIN employees e ON ac.employee_id = e.id
                    WHERE ac.date = ?
                      AND (ac.overtime_25_hours > 0 OR ac.overtime_50_hours > 0)
                      AND e.permite_horas_extras = 1
                    ORDER BY e.lastname, e.firstname";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$date]);
            $overtimeRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($overtimeRecords as $record) {
                try {
                    // Verificar si ya existe solicitud para este período (día único)
                    if ($this->approvalModel->existsForPeriod(
                        $record['employee_id'],
                        $date,
                        $date
                    )) {
                        $results['skipped']++;
                        $results['details'][] = [
                            'employee' => $record['employee_name'],
                            'status' => 'skipped',
                            'reason' => 'Ya existe solicitud para este día'
                        ];
                        continue;
                    }

                    // Calcular tarifa horaria (sueldo mensual / 220 horas)
                    $hourlyRate = $record['base_salary'] / 220;

                    // Calcular monto total según legislación panameña
                    $amount25 = $record['overtime_25_hours'] * $hourlyRate * 1.25;
                    $amount50 = $record['overtime_50_hours'] * $hourlyRate * 1.50;
                    $totalAmount = $amount25 + $amount50;

                    // Determinar aprobador (supervisor directo)
                    $approverId = $this->getEmployeeSupervisor($record['employee_id']);

                    if (!$approverId) {
                        $results['errors']++;
                        $results['details'][] = [
                            'employee' => $record['employee_name'],
                            'status' => 'error',
                            'reason' => 'No se encontró supervisor asignado'
                        ];
                        continue;
                    }

                    // Crear solicitud de aprobación
                    $approvalData = [
                        'employee_id' => $record['employee_id'],
                        'period_start' => $date,
                        'period_end' => $date,
                        'total_overtime_25' => $record['overtime_25_hours'],
                        'total_overtime_50' => $record['overtime_50_hours'],
                        'total_hours' => $record['total_overtime'],
                        'hourly_rate' => $hourlyRate,
                        'total_amount' => $totalAmount,
                        'approval_level' => 1,
                        'requires_two_levels' => 0, // Por ahora solo 1 nivel
                        'current_approver_id' => $approverId,
                        'status' => OvertimeApproval::STATUS_PENDING,
                        'notes' => "Solicitud generada automáticamente desde attendance_calculations",
                        'created_by' => null // Sistema
                    ];

                    $approvalId = $this->approvalModel->create($approvalData);

                    if ($approvalId) {
                        // Registrar en historial
                        $this->historyModel->logAction(
                            $approvalId,
                            OvertimeApprovalHistory::ACTION_CREATED,
                            $performedBy,
                            null,
                            OvertimeApproval::STATUS_PENDING,
                            "Solicitud creada automáticamente",
                            "Horas extras: 25%={$record['overtime_25_hours']}h, 50%={$record['overtime_50_hours']}h"
                        );

                        $results['created']++;
                        $results['details'][] = [
                            'employee' => $record['employee_name'],
                            'status' => 'created',
                            'approval_id' => $approvalId,
                            'total_hours' => $record['total_overtime'],
                            'total_amount' => $totalAmount
                        ];
                    } else {
                        $results['errors']++;
                        $results['details'][] = [
                            'employee' => $record['employee_name'],
                            'status' => 'error',
                            'reason' => 'Error al crear solicitud en BD'
                        ];
                    }

                } catch (Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'employee' => $record['employee_name'],
                        'status' => 'error',
                        'reason' => $e->getMessage()
                    ];
                    error_log("Error generando aprobación para empleado {$record['employee_id']}: " . $e->getMessage());
                }
            }

        } catch (PDOException $e) {
            error_log("Error generando solicitudes diarias: " . $e->getMessage());
            throw new Exception("Error generando solicitudes diarias: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Aprobar solicitud (Nivel 1 o Nivel 2)
     *
     * @param int $approvalId ID de la solicitud
     * @param int $approverId ID del aprobador
     * @param string|null $comments Comentarios del aprobador
     * @return bool True si se aprobó correctamente
     * @throws Exception Si no se puede aprobar
     */
    public function approve(int $approvalId, int $approverId, ?string $comments = null): bool
    {
        try {
            $this->db->beginTransaction();

            // Obtener solicitud actual
            $approval = $this->approvalModel->findById($approvalId);

            if (!$approval) {
                throw new Exception("Solicitud de aprobación no encontrada");
            }

            // Validar que el aprobador sea el asignado
            if ($approval['current_approver_id'] != $approverId) {
                throw new Exception("No tiene permisos para aprobar esta solicitud");
            }

            // Validar estado actual
            if (!in_array($approval['status'], [
                OvertimeApproval::STATUS_PENDING,
                OvertimeApproval::STATUS_IN_REVIEW
            ])) {
                throw new Exception("La solicitud no está en estado pendiente");
            }

            $previousStatus = $approval['status'];
            $currentLevel = $approval['approval_level'];

            // Actualizar según nivel
            if ($currentLevel == 1) {
                // Aprobación Nivel 1 (Supervisor)
                $updateData = [
                    'approved_by_level_1' => $approverId,
                    'approved_at_level_1' => date('Y-m-d H:i:s'),
                    'comments_level_1' => $comments
                ];

                // Determinar si requiere segundo nivel
                if ($approval['requires_two_levels'] == 1) {
                    // Avanzar a Nivel 2
                    $updateData['approval_level'] = 2;
                    $updateData['status'] = OvertimeApproval::STATUS_IN_REVIEW;
                    $updateData['current_approver_id'] = $this->getLevel2Approver($approval['employee_id']);

                    $newStatus = OvertimeApproval::STATUS_IN_REVIEW;
                    $historyAction = OvertimeApprovalHistory::ACTION_APPROVED_L1;
                } else {
                    // Aprobación final en Nivel 1
                    $updateData['status'] = OvertimeApproval::STATUS_APPROVED;
                    $updateData['current_approver_id'] = null;

                    $newStatus = OvertimeApproval::STATUS_APPROVED;
                    $historyAction = OvertimeApprovalHistory::ACTION_APPROVED_FINAL;
                }

            } elseif ($currentLevel == 2) {
                // Aprobación Nivel 2 (Gerente)
                $updateData = [
                    'approved_by_level_2' => $approverId,
                    'approved_at_level_2' => date('Y-m-d H:i:s'),
                    'comments_level_2' => $comments,
                    'status' => OvertimeApproval::STATUS_APPROVED,
                    'current_approver_id' => null
                ];

                $newStatus = OvertimeApproval::STATUS_APPROVED;
                $historyAction = OvertimeApprovalHistory::ACTION_APPROVED_FINAL;
            } else {
                throw new Exception("Nivel de aprobación inválido");
            }

            // Actualizar solicitud
            $updated = $this->approvalModel->update($approvalId, $updateData);

            if (!$updated) {
                throw new Exception("Error al actualizar solicitud");
            }

            // Registrar en historial
            $this->historyModel->logAction(
                $approvalId,
                $historyAction,
                $approverId,
                $previousStatus,
                $newStatus,
                $comments,
                "Aprobado en nivel {$currentLevel}"
            );

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error aprobando solicitud: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Rechazar solicitud
     *
     * @param int $approvalId ID de la solicitud
     * @param int $rejectorId ID del usuario que rechaza
     * @param string $reason Razón del rechazo
     * @return bool True si se rechazó correctamente
     * @throws Exception Si no se puede rechazar
     */
    public function reject(int $approvalId, int $rejectorId, string $reason): bool
    {
        try {
            $this->db->beginTransaction();

            // Obtener solicitud actual
            $approval = $this->approvalModel->findById($approvalId);

            if (!$approval) {
                throw new Exception("Solicitud de aprobación no encontrada");
            }

            // Validar que el rechazador sea el aprobador asignado
            if ($approval['current_approver_id'] != $rejectorId) {
                throw new Exception("No tiene permisos para rechazar esta solicitud");
            }

            // Validar estado actual
            if (!in_array($approval['status'], [
                OvertimeApproval::STATUS_PENDING,
                OvertimeApproval::STATUS_IN_REVIEW
            ])) {
                throw new Exception("La solicitud no está en estado pendiente");
            }

            $previousStatus = $approval['status'];
            $currentLevel = $approval['approval_level'];

            // Actualizar solicitud
            $updateData = [
                'status' => OvertimeApproval::STATUS_REJECTED,
                'rejected_by' => $rejectorId,
                'rejected_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason,
                'rejection_level' => "NIVEL_{$currentLevel}",
                'current_approver_id' => null
            ];

            $updated = $this->approvalModel->update($approvalId, $updateData);

            if (!$updated) {
                throw new Exception("Error al actualizar solicitud");
            }

            // Registrar en historial
            $this->historyModel->logAction(
                $approvalId,
                OvertimeApprovalHistory::ACTION_REJECTED,
                $rejectorId,
                $previousStatus,
                OvertimeApproval::STATUS_REJECTED,
                $reason,
                "Rechazado en nivel {$currentLevel}"
            );

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error rechazando solicitud: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener cola de aprobaciones pendientes para un aprobador
     *
     * @param int $approverId ID del aprobador
     * @return array Array de solicitudes pendientes con detalles
     */
    public function getApproverQueue(int $approverId): array
    {
        try {
            return $this->approvalModel->getPendingForApprover($approverId);
        } catch (Exception $e) {
            error_log("Error obteniendo cola de aprobaciones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener dashboard de aprobador con estadísticas
     *
     * @param int $approverId ID del aprobador
     * @return array Array con estadísticas y solicitudes pendientes
     */
    public function getApproverDashboard(int $approverId): array
    {
        try {
            return [
                'pending_requests' => $this->approvalModel->getPendingForApprover($approverId),
                'pending_count' => $this->approvalModel->countPendingForApprover($approverId),
                'pending_totals' => $this->approvalModel->getPendingTotals($approverId),
                'recent_actions' => $this->historyModel->getRecentActionsByUser($approverId, 10)
            ];
        } catch (Exception $e) {
            error_log("Error obteniendo dashboard: " . $e->getMessage());
            return [
                'pending_requests' => [],
                'pending_count' => 0,
                'pending_totals' => ['count' => 0, 'total_hours' => 0, 'total_amount' => 0],
                'recent_actions' => []
            ];
        }
    }

    /**
     * Obtener detalle completo de una solicitud con historial
     *
     * @param int $approvalId ID de la solicitud
     * @return array|null Array con detalles + historial o null
     */
    public function getApprovalDetails(int $approvalId): ?array
    {
        try {
            $approval = $this->approvalModel->getDetailById($approvalId);

            if (!$approval) {
                return null;
            }

            $approval['history'] = $this->historyModel->getDetailedHistory($approvalId);
            $approval['timeline'] = $this->historyModel->getTimeline($approvalId);

            return $approval;
        } catch (Exception $e) {
            error_log("Error obteniendo detalles de aprobación: " . $e->getMessage());
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
    public function cancelApproval(int $approvalId, int $cancelledBy, ?string $reason = null): bool
    {
        try {
            $this->db->beginTransaction();

            $approval = $this->approvalModel->findById($approvalId);

            if (!$approval) {
                throw new Exception("Solicitud no encontrada");
            }

            $previousStatus = $approval['status'];

            // Cancelar solicitud
            $cancelled = $this->approvalModel->cancel($approvalId, $cancelledBy, $reason);

            if (!$cancelled) {
                throw new Exception("Error al cancelar solicitud");
            }

            // Registrar en historial
            $this->historyModel->logAction(
                $approvalId,
                OvertimeApprovalHistory::ACTION_CANCELLED,
                $cancelledBy,
                $previousStatus,
                OvertimeApproval::STATUS_CANCELLED,
                $reason,
                "Solicitud cancelada manualmente"
            );

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error cancelando solicitud: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener supervisor de un empleado
     *
     * @param int $employeeId ID del empleado
     * @return int|null ID del supervisor o null
     */
    private function getEmployeeSupervisor(int $employeeId): ?int
    {
        try {
            $sql = "SELECT
                        op.admin_id as supervisor_id
                    FROM employees e
                    INNER JOIN organizational_positions op ON e.position_id = op.id
                    WHERE e.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? (int)$result['supervisor_id'] : null;

        } catch (PDOException $e) {
            error_log("Error obteniendo supervisor: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener aprobador de Nivel 2 (Gerente)
     *
     * @param int $employeeId ID del empleado
     * @return int|null ID del gerente o null
     */
    private function getLevel2Approver(int $employeeId): ?int
    {
        try {
            // Por ahora retornar gerente general (admin_id = 1)
            // TODO: Implementar lógica jerárquica completa
            return 1;
        } catch (Exception $e) {
            error_log("Error obteniendo aprobador nivel 2: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener resumen de solicitudes por estado
     *
     * @param array $filters Filtros opcionales
     * @return array Array con conteos por estado
     */
    public function getSummaryByStatus(array $filters = []): array
    {
        try {
            $sql = "SELECT
                        status,
                        COUNT(*) as count,
                        SUM(total_hours) as total_hours,
                        SUM(total_amount) as total_amount
                    FROM overtime_approvals
                    WHERE 1=1";

            $params = [];

            if (isset($filters['period_start'])) {
                $sql .= " AND period_start >= ?";
                $params[] = $filters['period_start'];
            }

            if (isset($filters['period_end'])) {
                $sql .= " AND period_end <= ?";
                $params[] = $filters['period_end'];
            }

            $sql .= " GROUP BY status ORDER BY status";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo resumen: " . $e->getMessage());
            return [];
        }
    }
}
