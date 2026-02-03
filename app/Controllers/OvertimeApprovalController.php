<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\Attendance\OvertimeApprovalService;
use App\Models\OvertimeApproval;
use App\Models\OvertimeApprovalHistory;

/**
 * OvertimeApprovalController
 *
 * Gestiona interfaz de aprobación de horas extras
 *
 * @package App\Controllers
 * @version 1.0
 * @date 2026-02-02
 */
class OvertimeApprovalController extends Controller
{
    private OvertimeApprovalService $service;
    private OvertimeApproval $approvalModel;
    private OvertimeApprovalHistory $historyModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = new OvertimeApprovalService();
        $this->approvalModel = new OvertimeApproval();
        $this->historyModel = new OvertimeApprovalHistory();
    }

    /**
     * Índice principal - Listado de solicitudes
     *
     * GET /overtime-approvals
     */
    public function index()
    {
        error_log("=== OvertimeApprovalController::index() called ===");

        $userId = $_SESSION['admin_id'] ?? 1; // Default a 1 si no hay sesión
        error_log("User ID: " . $userId);

        // Obtener dashboard del aprobador
        try {
            $dashboardData = $this->service->getApproverDashboard($userId);
            error_log("Dashboard data retrieved successfully");
        } catch (\Exception $e) {
            error_log("Error getting dashboard data: " . $e->getMessage());
            // Si falla, usar datos vacíos
            $dashboardData = [
                'pending_count' => 0,
                'pending_totals' => ['total_hours' => 0, 'total_amount' => 0]
            ];
        }

        // Renderizar vista
        error_log("Rendering view: admin/overtime_approvals/index");
        $this->render('admin/overtime_approvals/index', [
            'title' => 'Aprobación de Horas Extras',
            'pending_count' => $dashboardData['pending_count'],
            'pending_totals' => $dashboardData['pending_totals']
        ]);
    }

    /**
     * Obtener solicitudes pendientes para DataTables (AJAX)
     *
     * GET /overtime-approvals/pending-data
     */
    public function getPendingData()
    {
        header('Content-Type: application/json');

        try {
            $userId = $_SESSION['admin_id'] ?? null;

            if (!$userId) {
                echo json_encode(['error' => 'Usuario no autenticado']);
                return;
            }

            $requests = $this->service->getApproverQueue($userId);

            // Formatear datos para DataTables
            $data = [];
            foreach ($requests as $request) {
                $data[] = [
                    'id' => $request['id'],
                    'employee_code' => $request['employee_code'],
                    'employee_name' => $request['employee_full_name'],
                    'period_start' => date('d/m/Y', strtotime($request['period_start'])),
                    'period_end' => date('d/m/Y', strtotime($request['period_end'])),
                    'total_overtime_25' => number_format($request['total_overtime_25'], 2),
                    'total_overtime_50' => number_format($request['total_overtime_50'], 2),
                    'total_hours' => number_format($request['total_hours'], 2),
                    'total_amount' => number_format($request['total_amount'], 2),
                    'status' => $request['status'],
                    'status_badge' => $this->getStatusBadge($request['status']),
                    'created_at' => date('d/m/Y H:i', strtotime($request['created_at'])),
                    'days_pending' => $request['days_pending'] ?? 0
                ];
            }

            echo json_encode([
                'data' => $data,
                'recordsTotal' => count($data),
                'recordsFiltered' => count($data)
            ]);

        } catch (\Exception $e) {
            error_log("Error en getPendingData: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtener todas las solicitudes para DataTables (AJAX)
     *
     * GET /overtime-approvals/all-data
     */
    public function getAllData()
    {
        header('Content-Type: application/json');

        try {
            // Obtener parámetros de filtro
            $filters = [];

            if (isset($_GET['status']) && $_GET['status'] !== '') {
                $filters['status'] = $_GET['status'];
            }

            if (isset($_GET['employee_id']) && $_GET['employee_id'] !== '') {
                $filters['employee_id'] = $_GET['employee_id'];
            }

            if (isset($_GET['period_start']) && $_GET['period_start'] !== '') {
                $filters['period_start'] = $_GET['period_start'];
            }

            if (isset($_GET['period_end']) && $_GET['period_end'] !== '') {
                $filters['period_end'] = $_GET['period_end'];
            }

            $requests = $this->approvalModel->getWithDetails($filters);

            // Formatear datos para DataTables
            $data = [];
            foreach ($requests as $request) {
                $data[] = [
                    'id' => $request['id'],
                    'employee_code' => $request['employee_code'],
                    'employee_name' => $request['employee_full_name'],
                    'period_start' => date('d/m/Y', strtotime($request['period_start'])),
                    'period_end' => date('d/m/Y', strtotime($request['period_end'])),
                    'total_hours' => number_format($request['total_hours'], 2),
                    'total_amount' => number_format($request['total_amount'], 2),
                    'status' => $request['status'],
                    'status_badge' => $this->getStatusBadge($request['status']),
                    'approver_name' => $request['approver_full_name'] ?? '-',
                    'created_at' => date('d/m/Y H:i', strtotime($request['created_at']))
                ];
            }

            echo json_encode([
                'data' => $data,
                'recordsTotal' => count($data),
                'recordsFiltered' => count($data)
            ]);

        } catch (\Exception $e) {
            error_log("Error en getAllData: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtener detalle de una solicitud (AJAX)
     *
     * GET /overtime-approvals/detail/{id}
     */
    public function getDetail($id)
    {
        header('Content-Type: application/json');

        try {
            $details = $this->service->getApprovalDetails($id);

            if (!$details) {
                echo json_encode(['error' => 'Solicitud no encontrada']);
                return;
            }

            // Formatear datos
            $details['period_start_formatted'] = date('d/m/Y', strtotime($details['period_start']));
            $details['period_end_formatted'] = date('d/m/Y', strtotime($details['period_end']));
            $details['total_overtime_25_formatted'] = number_format($details['total_overtime_25'], 2);
            $details['total_overtime_50_formatted'] = number_format($details['total_overtime_50'], 2);
            $details['total_hours_formatted'] = number_format($details['total_hours'], 2);
            $details['hourly_rate_formatted'] = number_format($details['hourly_rate'], 2);
            $details['total_amount_formatted'] = number_format($details['total_amount'], 2);
            $details['status_badge'] = $this->getStatusBadge($details['status']);
            $details['created_at_formatted'] = date('d/m/Y H:i:s', strtotime($details['created_at']));

            // Formatear historial
            foreach ($details['history'] as &$entry) {
                $entry['created_at_formatted'] = date('d/m/Y H:i:s', strtotime($entry['created_at']));
                $entry['action_badge'] = $this->getActionBadge($entry['action']);
            }

            echo json_encode([
                'success' => true,
                'data' => $details
            ]);

        } catch (\Exception $e) {
            error_log("Error en getDetail: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Aprobar solicitud (AJAX)
     *
     * POST /overtime-approvals/approve
     */
    public function approve()
    {
        header('Content-Type: application/json');

        // Verificar permisos
        if (!$this->checkPermission('overtime_approvals', 'update')) {
            echo json_encode(['error' => 'No tiene permisos para aprobar solicitudes']);
            return;
        }

        try {
            $approvalId = $_POST['approval_id'] ?? null;
            $comments = $_POST['comments'] ?? null;
            $userId = $_SESSION['admin_id'] ?? null;

            if (!$approvalId || !$userId) {
                echo json_encode(['error' => 'Datos incompletos']);
                return;
            }

            $result = $this->service->approve($approvalId, $userId, $comments);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Solicitud aprobada exitosamente'
                ]);
            } else {
                echo json_encode(['error' => 'Error al aprobar solicitud']);
            }

        } catch (\Exception $e) {
            error_log("Error en approve: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Rechazar solicitud (AJAX)
     *
     * POST /overtime-approvals/reject
     */
    public function reject()
    {
        header('Content-Type: application/json');

        // Verificar permisos
        if (!$this->checkPermission('overtime_approvals', 'update')) {
            echo json_encode(['error' => 'No tiene permisos para rechazar solicitudes']);
            return;
        }

        try {
            $approvalId = $_POST['approval_id'] ?? null;
            $reason = $_POST['reason'] ?? null;
            $userId = $_SESSION['admin_id'] ?? null;

            if (!$approvalId || !$reason || !$userId) {
                echo json_encode(['error' => 'Datos incompletos']);
                return;
            }

            $result = $this->service->reject($approvalId, $userId, $reason);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Solicitud rechazada exitosamente'
                ]);
            } else {
                echo json_encode(['error' => 'Error al rechazar solicitud']);
            }

        } catch (\Exception $e) {
            error_log("Error en reject: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancelar solicitud (AJAX)
     *
     * POST /overtime-approvals/cancel
     */
    public function cancel()
    {
        header('Content-Type: application/json');

        // Verificar permisos
        if (!$this->checkPermission('overtime_approvals', 'delete')) {
            echo json_encode(['error' => 'No tiene permisos para cancelar solicitudes']);
            return;
        }

        try {
            $approvalId = $_POST['approval_id'] ?? null;
            $reason = $_POST['reason'] ?? null;
            $userId = $_SESSION['admin_id'] ?? null;

            if (!$approvalId || !$userId) {
                echo json_encode(['error' => 'Datos incompletos']);
                return;
            }

            $result = $this->service->cancelApproval($approvalId, $userId, $reason);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Solicitud cancelada exitosamente'
                ]);
            } else {
                echo json_encode(['error' => 'Error al cancelar solicitud']);
            }

        } catch (\Exception $e) {
            error_log("Error en cancel: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Dashboard de estadísticas (AJAX)
     *
     * GET /overtime-approvals/stats
     */
    public function getStats()
    {
        header('Content-Type: application/json');

        try {
            $userId = $_SESSION['admin_id'] ?? null;

            if (!$userId) {
                echo json_encode(['error' => 'Usuario no autenticado']);
                return;
            }

            $dashboardData = $this->service->getApproverDashboard($userId);

            echo json_encode([
                'success' => true,
                'data' => [
                    'pending_count' => $dashboardData['pending_count'],
                    'pending_hours' => number_format($dashboardData['pending_totals']['total_hours'], 2),
                    'pending_amount' => number_format($dashboardData['pending_totals']['total_amount'], 2)
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Error en getStats: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Resumen general por estado (AJAX)
     *
     * GET /overtime-approvals/summary
     */
    public function getSummary()
    {
        header('Content-Type: application/json');

        try {
            $filters = [];

            if (isset($_GET['period_start']) && $_GET['period_start'] !== '') {
                $filters['period_start'] = $_GET['period_start'];
            }

            if (isset($_GET['period_end']) && $_GET['period_end'] !== '') {
                $filters['period_end'] = $_GET['period_end'];
            }

            $summary = $this->service->getSummaryByStatus($filters);

            echo json_encode([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            error_log("Error en getSummary: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Generar badge HTML para estado
     *
     * @param string $status Estado
     * @return string HTML del badge
     */
    private function getStatusBadge(string $status): string
    {
        $badges = [
            'PENDIENTE' => '<span class="badge badge-warning">Pendiente</span>',
            'EN_REVISION' => '<span class="badge badge-info">En Revisión</span>',
            'APROBADO' => '<span class="badge badge-success">Aprobado</span>',
            'RECHAZADO' => '<span class="badge badge-danger">Rechazado</span>',
            'CANCELADO' => '<span class="badge badge-secondary">Cancelado</span>'
        ];

        return $badges[$status] ?? '<span class="badge badge-secondary">' . $status . '</span>';
    }

    /**
     * Generar badge HTML para acción
     *
     * @param string $action Acción
     * @return string HTML del badge
     */
    private function getActionBadge(string $action): string
    {
        $badges = [
            'CREADO' => '<span class="badge badge-secondary"><i class="fas fa-plus-circle"></i> Creado</span>',
            'APROBADO_L1' => '<span class="badge badge-info"><i class="fas fa-check"></i> Aprobado Nivel 1</span>',
            'APROBADO_L2' => '<span class="badge badge-info"><i class="fas fa-check-double"></i> Aprobado Nivel 2</span>',
            'APROBADO_FINAL' => '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Aprobado Final</span>',
            'RECHAZADO' => '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rechazado</span>',
            'MODIFICADO' => '<span class="badge badge-warning"><i class="fas fa-edit"></i> Modificado</span>',
            'CANCELADO' => '<span class="badge badge-dark"><i class="fas fa-ban"></i> Cancelado</span>',
            'REABIERTO' => '<span class="badge badge-primary"><i class="fas fa-redo"></i> Reabierto</span>'
        ];

        return $badges[$action] ?? '<span class="badge badge-secondary">' . $action . '</span>';
    }

    /**
     * Verificar permisos del usuario
     *
     * @param string $module Módulo
     * @param string $action Acción (read, create, update, delete)
     * @return bool True si tiene permiso
     */
    private function checkPermission(string $module, string $action): bool
    {
        // Por ahora retornar true para permitir acceso durante desarrollo
        // TODO: Integrar con sistema de permisos existente
        return true;
    }
}
