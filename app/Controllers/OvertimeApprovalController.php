<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\Attendance\OvertimeApprovalService as AttendanceOvertimeService;
use App\Services\OvertimeApprovalService;
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
    private AttendanceOvertimeService $service;
    private OvertimeApprovalService $generatorService;
    private OvertimeApproval $approvalModel;
    private OvertimeApprovalHistory $historyModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        // La autenticación se maneja por el middleware en App.php (checkRoutePermissions)
        $this->service = new AttendanceOvertimeService();
        $this->generatorService = new OvertimeApprovalService();
        $this->approvalModel = new OvertimeApproval();
        $this->historyModel = new OvertimeApprovalHistory();
    }

    /**
     * Índice principal - Listado de solicitudes diarias
     *
     * GET /overtime-approvals
     */
    public function index()
    {
        // Usar $_SESSION['admin'] que es el ID del usuario
        $userId = $_SESSION['admin'] ?? null;

        if (!$userId) {
            error_log("OvertimeApprovalController::index - No admin in session");
            $this->redirect('/panel/login');
            return;
        }

        // Obtener estadísticas de registros pendientes y aprobados
        try {
            $pendingStats = $this->service->getDailyOvertimeStats(['status' => 'PENDING']);
            $approvedStats = $this->service->getDailyOvertimeStats(['status' => 'APPROVED']);
        } catch (\Exception $e) {
            error_log("Error getting stats: " . $e->getMessage());
            $pendingStats = [
                'total_records' => 0,
                'total_hours' => 0,
                'total_overtime_25' => 0,
                'total_overtime_50' => 0
            ];
            $approvedStats = [
                'total_records' => 0,
                'total_hours' => 0,
                'total_overtime_25' => 0,
                'total_overtime_50' => 0
            ];
        }

        $data = [
            'title' => 'Aprobación de Horas Extras',
            'page_title' => 'Aprobación de Horas Extras - Registros Diarios',
            'pending_stats' => $pendingStats,
            'approved_stats' => $approvedStats,
            'csrf_token' => \App\Core\Security::generateToken()
        ];

        $this->render('admin/overtime_approvals/index', $data);
    }

    /**
     * Obtener registros diarios para DataTables (AJAX)
     *
     * GET /overtime-approvals/pending-data
     */
    public function getPendingData()
    {
        header('Content-Type: application/json');

        try {
            $userId = $_SESSION['admin'] ?? null;

            if (!$userId) {
                error_log("OvertimeApprovalController::getPendingData - No admin in session");
                echo json_encode([
                    'error' => 'Usuario no autenticado',
                    'redirect' => \App\Core\UrlHelper::base() . '/panel/login'
                ]);
                return;
            }

            // Obtener filtros opcionales
            $filters = [];

            if (isset($_GET['status']) && $_GET['status'] !== '') {
                $filters['status'] = $_GET['status'];
            }
            // No aplicar filtro por defecto - mostrar todos los registros

            if (isset($_GET['employee_id']) && $_GET['employee_id'] !== '') {
                $filters['employee_id'] = $_GET['employee_id'];
            }

            if (isset($_GET['date_from']) && $_GET['date_from'] !== '') {
                $filters['date_from'] = $_GET['date_from'];
            }

            if (isset($_GET['date_to']) && $_GET['date_to'] !== '') {
                $filters['date_to'] = $_GET['date_to'];
            }

            // Obtener registros diarios
            $records = $this->service->getDailyOvertimeRecords($filters);

            // Formatear datos para DataTables
            $data = [];
            foreach ($records as $record) {
                $data[] = [
                    'id' => $record['id'],
                    'employee_code' => $record['employee_code'],
                    'employee_name' => $record['employee_name'],
                    'schedule_name' => $record['schedule_name'] ?? 'N/A',
                    'date' => date('d/m/Y', strtotime($record['date'])),
                    'time_in' => $record['time_in'] ? substr($record['time_in'], 0, 5) : '-',
                    'time_out' => $record['time_out'] ? substr($record['time_out'], 0, 5) : '-',
                    'total_hours' => number_format($record['total_hours'], 2),
                    'regular_hours' => number_format($record['regular_hours'], 2),
                    'overtime_25_hours' => number_format($record['overtime_25_hours'], 2),
                    'overtime_50_hours' => number_format($record['overtime_50_hours'], 2),
                    'overtime_hours' => number_format($record['overtime_hours'], 2),
                    'amount_25' => number_format($record['amount_25'], 2),
                    'amount_50' => number_format($record['amount_50'], 2),
                    'total_amount' => number_format($record['total_amount'], 2),
                    'status' => $record['overtime_status'],
                    'status_badge' => $this->getStatusBadge($record['overtime_status']),
                    'approved_by_name' => $record['approved_by_name'] ?? '-',
                    'approved_at' => $record['overtime_approved_at'] ? date('d/m/Y H:i', strtotime($record['overtime_approved_at'])) : '-',
                    'notes' => $record['overtime_notes'] ?? ''
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
     * Aprobar registro diario (AJAX)
     *
     * POST /overtime-approvals/approve
     */
    public function approve()
    {
        header('Content-Type: application/json');

        // Verificar CSRF token
        $csrfToken = $_POST['csrf_token'] ?? null;
        if (!$csrfToken || !\App\Core\Security::validateToken($csrfToken)) {
            echo json_encode(['error' => 'Token de seguridad inválido']);
            return;
        }

        // Verificar permisos
        if (!$this->checkPermission('overtime_approvals', 'update')) {
            echo json_encode(['error' => 'No tiene permisos para aprobar solicitudes']);
            return;
        }

        try {
            $calculationId = $_POST['calculation_id'] ?? null;
            $notes = $_POST['notes'] ?? null;
            $userId = $_SESSION['admin'] ?? null;

            if (!$calculationId || !$userId) {
                echo json_encode(['error' => 'Datos incompletos']);
                return;
            }

            $result = $this->service->approveDailyRecord($calculationId, $userId, $notes);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Horas extras aprobadas exitosamente'
                ]);
            } else {
                echo json_encode(['error' => 'Error al aprobar registro. Verifique que esté en estado PENDING.']);
            }

        } catch (\Exception $e) {
            error_log("Error en approve: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Rechazar registro diario (AJAX)
     *
     * POST /overtime-approvals/reject
     */
    public function reject()
    {
        header('Content-Type: application/json');

        // Verificar CSRF token
        $csrfToken = $_POST['csrf_token'] ?? null;
        if (!$csrfToken || !\App\Core\Security::validateToken($csrfToken)) {
            echo json_encode(['error' => 'Token de seguridad inválido']);
            return;
        }

        // Verificar permisos
        if (!$this->checkPermission('overtime_approvals', 'update')) {
            echo json_encode(['error' => 'No tiene permisos para rechazar solicitudes']);
            return;
        }

        try {
            $calculationId = $_POST['calculation_id'] ?? null;
            $reason = $_POST['reason'] ?? null;
            $userId = $_SESSION['admin'] ?? null;

            if (!$calculationId || !$reason || !$userId) {
                echo json_encode(['error' => 'Datos incompletos']);
                return;
            }

            $result = $this->service->rejectDailyRecord($calculationId, $userId, $reason);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Horas extras rechazadas exitosamente'
                ]);
            } else {
                echo json_encode(['error' => 'Error al rechazar registro. Verifique que esté en estado PENDING.']);
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
            $userId = $_SESSION['admin'] ?? null;

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
            $userId = $_SESSION['admin'] ?? null;

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
            // Estados en inglés (desde BD)
            'PENDING' => '<span class="badge badge-warning">Pendiente</span>',
            'APPROVED' => '<span class="badge badge-success">Aprobado</span>',
            'REJECTED' => '<span class="badge badge-danger">Rechazado</span>',
            'NOT_APPLICABLE' => '<span class="badge badge-secondary">No Aplica</span>',
            // Estados en español (retrocompatibilidad)
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
     * Generar solicitudes automáticamente desde attendance_records (AJAX)
     *
     * POST /overtime-approvals/generate
     */
    public function generateFromAttendance()
    {
        header('Content-Type: application/json');

        // Verificar permisos
        if (!$this->checkPermission('overtime_approvals', 'create')) {
            echo json_encode(['error' => 'No tiene permisos para generar solicitudes']);
            return;
        }

        try {
            $periodStart = $_POST['period_start'] ?? null;
            $periodEnd = $_POST['period_end'] ?? null;
            $employeeIds = isset($_POST['employee_ids']) ? json_decode($_POST['employee_ids'], true) : null;

            if (!$periodStart || !$periodEnd) {
                echo json_encode(['error' => 'Debe especificar el período de generación']);
                return;
            }

            // Generar solicitudes
            $result = $this->generatorService->generateApprovalsFromAttendance(
                $periodStart,
                $periodEnd,
                $employeeIds
            );

            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => "Solicitudes generadas exitosamente",
                    'stats' => [
                        'created' => $result['created'],
                        'skipped' => $result['skipped'],
                        'errors' => $result['errors']
                    ],
                    'details' => $result['details']
                ]);
            } else {
                echo json_encode([
                    'error' => $result['error'] ?? 'Error al generar solicitudes'
                ]);
            }

        } catch (\Exception $e) {
            error_log("Error en generateFromAttendance: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Crear solicitud manual (AJAX)
     *
     * POST /overtime-approvals/create-manual
     */
    public function createManual()
    {
        header('Content-Type: application/json');

        // Verificar permisos
        if (!$this->checkPermission('overtime_approvals', 'create')) {
            echo json_encode(['error' => 'No tiene permisos para crear solicitudes']);
            return;
        }

        try {
            $employeeId = $_POST['employee_id'] ?? null;
            $periodStart = $_POST['period_start'] ?? null;
            $periodEnd = $_POST['period_end'] ?? null;
            $overtime25 = floatval($_POST['overtime_25'] ?? 0);
            $overtime50 = floatval($_POST['overtime_50'] ?? 0);
            $comments = $_POST['comments'] ?? null;
            $userId = $_SESSION['admin'] ?? null;

            // Validar datos requeridos
            if (!$employeeId || !$periodStart || !$periodEnd) {
                echo json_encode(['error' => 'Datos incompletos']);
                return;
            }

            if ($overtime25 <= 0 && $overtime50 <= 0) {
                echo json_encode(['error' => 'Debe especificar al menos un tipo de horas extras']);
                return;
            }

            // Crear solicitud
            $data = [
                'employee_id' => $employeeId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'total_overtime_25' => $overtime25,
                'total_overtime_50' => $overtime50,
                'comments' => $comments
            ];

            $result = $this->generatorService->createManualApproval($data, $userId);

            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Solicitud creada exitosamente',
                    'approval_id' => $result['approval_id']
                ]);
            } else {
                echo json_encode([
                    'error' => $result['error'] ?? 'Error al crear solicitud'
                ]);
            }

        } catch (\Exception $e) {
            error_log("Error en createManual: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Mostrar formulario de creación manual
     *
     * GET /overtime-approvals/create
     */
    public function create()
    {
        $this->render('admin/overtime_approvals/create', [
            'title' => 'Crear Solicitud de Horas Extras'
        ]);
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
