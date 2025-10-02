<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Employee;
use App\Services\PlanillaConceptCalculator;
use App\Services\VacationBalanceService;
use PDO;
use PDOException;
use DateTime;
use DateInterval;

class VacationController extends Controller
{
    protected $calculator;
    protected $balanceService;

    public function __construct()
    {
        parent::__construct();
        $this->calculator = new PlanillaConceptCalculator();
        $this->balanceService = new VacationBalanceService();
    }

    /**
     * Lista de empleados activos con sus balances de vacaciones
     */
    public function index()
    {
        try {
            // Obtener empleados activos con datos de vacaciones
            $sql = "SELECT e.id, e.employee_id, e.firstname, e.lastname, e.document_id,
                           e.fecha_ingreso, e.sueldo_individual, cargo.nombre as cargo_nombre,
                           s.nombre as situacion_nombre,
                           DATEDIFF(CURDATE(), e.fecha_ingreso) as dias_trabajados,
                           TIMESTAMPDIFF(MONTH, e.fecha_ingreso, CURDATE()) as meses_trabajados,
                           vb.current_balance, vb.days_earned, vb.days_taken
                    FROM employees e
                    LEFT JOIN cargos cargo ON e.cargo_id = cargo.id
                    LEFT JOIN situaciones s ON e.situacion_id = s.id
                    LEFT JOIN vacation_balances vb ON e.id = vb.employee_id AND vb.year = YEAR(CURDATE())
                    WHERE e.situacion_id = 1
                    ORDER BY e.firstname, e.lastname";

            $stmt = $this->db->query($sql);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular balances para empleados sin registro en vacation_balances
            foreach ($employees as &$employee) {
                if ($employee['current_balance'] === null) {
                    $employee['days_earned'] = $this->calculator->VACATION_DAYS_EARNED($employee['id']);
                    $employee['current_balance'] = $this->calculator->VACATION_BALANCE($employee['id']);
                    $employee['days_taken'] = 0;
                }

                $employee['eligible'] = $this->calculator->VACATION_ELIGIBLE($employee['id']);
                $employee['accrual_rate'] = $this->calculator->VACATION_ACCRUAL_RATE($employee['id']);
            }

            // Obtener solicitudes pendientes de aprobación
            $sql = "SELECT vr.*, e.firstname, e.lastname, e.employee_id
                    FROM vacation_requests vr
                    INNER JOIN employees e ON vr.employee_id = e.id
                    WHERE vr.status = 'PENDING'
                    ORDER BY vr.request_date DESC";

            $stmt = $this->db->query($sql);
            $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->render('admin/vacation/index', [
                'employees' => $employees,
                'pending_requests' => $pending_requests,
                'pageTitle' => 'Gestión de Vacaciones'
            ]);

        } catch (PDOException $e) {
            $this->setFlashMessage('error', 'Error al cargar empleados: ' . $e->getMessage());
            $this->redirect('/panel');
        }
    }

    /**
     * Mostrar formulario para crear nueva solicitud de vacaciones
     */
    public function create($employee_id = null)
    {
        try {
            if (!$employee_id) {
                $this->setFlashMessage('error', 'ID de empleado requerido');
                $this->redirect('/panel/vacation');
                return;
            }

            // Obtener datos del empleado
            $sql = "SELECT e.*, cargo.nombre as cargo_nombre, s.nombre as situacion_nombre
                    FROM employees e
                    LEFT JOIN cargos cargo ON e.cargo_id = cargo.id
                    LEFT JOIN situaciones s ON e.situacion_id = s.id
                    WHERE e.id = ? AND e.situacion_id = 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                $this->setFlashMessage('error', 'Empleado no encontrado o inactivo');
                $this->redirect('/panel/vacation');
                return;
            }

            // Verificar elegibilidad para vacaciones
            if (!$this->calculator->VACATION_ELIGIBLE($employee_id)) {
                $this->setFlashMessage('error', 'El empleado no tiene derecho a vacaciones (requiere 11 meses mínimo)');
                $this->redirect('/panel/vacation');
                return;
            }

            // Calcular datos de vacaciones
            $current_year = date('Y');
            $annual_balance = $this->balanceService->getAnnualBalance($employee_id, $current_year);
            $general_totals = $this->balanceService->getGeneralTotals($employee_id);
            $vacation_history = $this->balanceService->getVacationHistory($employee_id);

            $vacation_data = [
                'days_earned' => $this->calculator->VACATION_DAYS_EARNED($employee_id),
                'current_balance' => $this->calculator->VACATION_BALANCE($employee_id),
                'accrual_rate' => $this->calculator->VACATION_ACCRUAL_RATE($employee_id),
                'daily_salary' => $this->calculator->VACATION_COMPENSATION_AMOUNT($employee_id, 1),
                // Nuevos datos de balance anual
                'annual_balance' => $annual_balance,
                'general_totals' => $general_totals,
                'vacation_history' => $vacation_history,
                'current_year' => $current_year
            ];

            // Obtener solicitudes anteriores
            $sql = "SELECT * FROM vacation_requests
                    WHERE employee_id = ?
                    ORDER BY request_date DESC
                    LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $previous_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->render('admin/vacation/create', [
                'employee' => $employee,
                'vacation_data' => $vacation_data,
                'previous_requests' => $previous_requests,
                'pageTitle' => 'Nueva Solicitud de Vacaciones - ' . $employee['firstname'] . ' ' . $employee['lastname']
            ]);

        } catch (PDOException $e) {
            $this->setFlashMessage('error', 'Error al cargar empleado: ' . $e->getMessage());
            $this->redirect('/panel/vacation');
        }
    }

    /**
     * Guardar nueva solicitud de vacaciones
     */
    public function store()
    {
        try {
            $this->validateCsrfToken();

            $employee_id = $_POST['employee_id'] ?? null;
            $start_date = $_POST['start_date'] ?? null;
            $end_date = $_POST['end_date'] ?? null;
            $vacation_type = $_POST['vacation_type'] ?? 'ANNUAL';
            $comments = $_POST['comments'] ?? '';

            // Nuevos campos de vacaciones
            $dias_vacaciones_anuales = $_POST['dias_vacaciones_anuales'] ?? 30;
            $saldo_vacaciones = $_POST['saldo_vacaciones'] ?? 0;
            $dias_solicitados_pagar = $_POST['dias_solicitados_pagar'] ?? 0;
            $dias_vacaciones_disfrute = $_POST['dias_vacaciones_disfrute'] ?? 0;
            $saldo_dias_disfrute = $_POST['saldo_dias_disfrute'] ?? 0;
            $dias_solicitados_disfrute = $_POST['dias_solicitados_disfrute'] ?? 0;
            $estado_solicitud = $_POST['estado_solicitud'] ?? 'PENDING';
            $total_dias_solicitados = $_POST['total_dias_solicitados'] ?? 0;

            // Campos de año y días calculados
            $ano_vacaciones = $_POST['ano_vacaciones'] ?? null;
            $dias_calculados_fechas = $_POST['dias_calculados_fechas'] ?? 0;

            // Validaciones básicas
            if (!$employee_id || !$start_date || !$end_date || !$ano_vacaciones) {
                $this->setFlashMessage('error', 'Todos los campos obligatorios deben ser completados');
                $this->redirect('/panel/vacation/create/' . $employee_id);
                return;
            }

            // Validar fechas
            $start_datetime = new DateTime($start_date);
            $end_datetime = new DateTime($end_date);
            $today = new DateTime();

            if ($start_datetime < $today) {
                $this->setFlashMessage('error', 'La fecha de inicio no puede ser en el pasado');
                $this->redirect('/panel/vacation/create/' . $employee_id);
                return;
            }

            // Validar anticipación de 15 días
            $anticipation_days = $today->diff($start_datetime)->days;
            if ($start_datetime > $today && $anticipation_days < 15) {
                $this->setFlashMessage('error', "Las vacaciones deben solicitarse con al menos 15 días de anticipación. Días de anticipación actual: {$anticipation_days} días.");
                $this->redirect('/panel/vacation/create/' . $employee_id);
                return;
            }

            if ($end_datetime <= $start_datetime) {
                $this->setFlashMessage('error', 'La fecha de fin debe ser posterior a la fecha de inicio');
                $this->redirect('/panel/vacation/create/' . $employee_id);
                return;
            }

            // Verificar elegibilidad
            if (!$this->calculator->VACATION_ELIGIBLE($employee_id)) {
                $this->setFlashMessage('error', 'El empleado no tiene derecho a vacaciones');
                $this->redirect('/panel/vacation/create/' . $employee_id);
                return;
            }

            // Calcular días solicitados
            $total_days = $start_datetime->diff($end_datetime)->days + 1;
            $business_days = $this->calculator->VACATION_BUSINESS_DAYS($start_date, $end_date);

            // Verificar balance disponible
            $current_balance = $this->calculator->VACATION_BALANCE($employee_id);

            // Validación usando el servicio de balance anual
            $validation = $this->balanceService->canProcessVacationRequest(
                $employee_id,
                $ano_vacaciones,
                $dias_solicitados_pagar,
                $dias_solicitados_disfrute
            );

            if (!$validation['valid']) {
                $this->setFlashMessage('error', $validation['message']);
                $this->redirect('/panel/vacation/create/' . $employee_id);
                return;
            }

            // Validaciones adicionales para los nuevos campos
            if ($total_dias_solicitados > $current_balance) {
                $this->setFlashMessage('error', "Total de días solicitados ($total_dias_solicitados) excede el balance disponible ($current_balance)");
                $this->redirect('/panel/vacation/create/' . $employee_id);
                return;
            }

            if ($dias_solicitados_pagar + $dias_solicitados_disfrute != $total_dias_solicitados) {
                $this->setFlashMessage('error', 'La suma de días por pagar y disfrute debe coincidir con el total solicitado');
                $this->redirect('/panel/vacation/create/' . $employee_id);
                return;
            }

            if ($business_days > $current_balance) {
                $this->setFlashMessage('error', "Días solicitados ($business_days) exceden el balance disponible ($current_balance)");
                $this->redirect('/panel/vacation/create/' . $employee_id);
                return;
            }

            // Verificar solapamientos con otras solicitudes aprobadas
            if ($this->hasOverlapWithApprovedRequests($employee_id, $start_date, $end_date)) {
                $this->setFlashMessage('error', 'Las fechas solicitadas se solapan con vacaciones ya aprobadas');
                $this->redirect('/panel/vacation/create/' . $employee_id);
                return;
            }

            // Calcular datos adicionales
            $years_worked = $this->calculateYearsWorked($employee_id);
            $accumulated_days = $this->calculator->VACATION_DAYS_EARNED($employee_id);
            $available_days = $current_balance;
            $remaining_days = $available_days - $business_days;
            $daily_salary = $this->calculator->VACATION_COMPENSATION_AMOUNT($employee_id, 1);
            $compensation_amount = ($vacation_type === 'COMPENSATION') ? $daily_salary * $business_days : 0;

            // Calcular compensación para días solicitados por pagar
            $compensation_amount_new = $daily_salary * $dias_solicitados_pagar;

            // Insertar solicitud
            $sql = "INSERT INTO vacation_requests (
                        employee_id, request_date, start_date, end_date, total_days, business_days,
                        vacation_type, status, comments, years_worked, accumulated_days,
                        available_days, remaining_days, daily_salary, compensation_amount,
                        dias_vacaciones_anuales, dias_solicitados_pagar, dias_solicitados_disfrute,
                        ano_vacaciones, dias_calculados_fechas
                    ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $employee_id, $start_date, $end_date, $total_days, $business_days,
                $vacation_type, $estado_solicitud, $comments, $years_worked, $accumulated_days,
                $available_days, $remaining_days, $daily_salary, $compensation_amount_new,
                $dias_vacaciones_anuales, $dias_solicitados_pagar, $dias_solicitados_disfrute,
                $ano_vacaciones, $dias_calculados_fechas
            ]);

            $request_id = $this->db->lastInsertId();

            // Actualizar balances anuales y totales generales
            $this->balanceService->updateAnnualBalance(
                $employee_id,
                $ano_vacaciones,
                $dias_solicitados_pagar,
                $dias_solicitados_disfrute
            );

            // Crear registro de cálculo para auditoría
            $this->createCalculationRecord($request_id, 'DAYS_REQUESTED', $business_days, $business_days, $compensation_amount);

            $this->setFlashMessage('success', 'Solicitud de vacaciones creada exitosamente. ID: ' . $request_id);
            $this->redirect('/panel/vacation');

        } catch (PDOException $e) {
            $this->setFlashMessage('error', 'Error al crear solicitud: ' . $e->getMessage());
            $this->redirect('/panel/vacation');
        }
    }

    /**
     * Mostrar detalles de una solicitud de vacaciones
     */
    public function show($request_id)
    {
        try {
            // Obtener datos de la solicitud
            $sql = "SELECT vr.*, e.firstname, e.lastname, e.employee_id, e.fecha_ingreso,
                           cargo.nombre as cargo_nombre, a.name as approver_name
                    FROM vacation_requests vr
                    INNER JOIN employees e ON vr.employee_id = e.id
                    LEFT JOIN cargos cargo ON e.cargo_id = cargo.id
                    LEFT JOIN admin a ON vr.approved_by = a.id
                    WHERE vr.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $this->setFlashMessage('error', 'Solicitud de vacaciones no encontrada');
                $this->redirect('/panel/vacation');
                return;
            }

            // Obtener cálculos relacionados
            $sql = "SELECT * FROM vacation_calculations WHERE vacation_request_id = ? ORDER BY id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$request_id]);
            $calculations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular balance actual del empleado
            $current_balance = $this->calculator->VACATION_BALANCE($request['employee_id']);

            $this->render('admin/vacation/show', [
                'request' => $request,
                'calculations' => $calculations,
                'current_balance' => $current_balance,
                'pageTitle' => 'Solicitud de Vacaciones #' . $request_id
            ]);

        } catch (PDOException $e) {
            $this->setFlashMessage('error', 'Error al cargar solicitud: ' . $e->getMessage());
            $this->redirect('/panel/vacation');
        }
    }

    /**
     * Aprobar solicitud de vacaciones
     */
    public function approve($request_id)
    {
        try {
            $this->validateCsrfToken();

            // Verificar que la solicitud existe y está pendiente
            $sql = "SELECT * FROM vacation_requests WHERE id = ? AND status = 'PENDING'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $this->setFlashMessage('error', 'Solicitud no encontrada o ya procesada');
                $this->redirect('/panel/vacation');
                return;
            }

            // Verificar balance actual
            $current_balance = $this->calculator->VACATION_BALANCE($request['employee_id']);
            if ($request['business_days'] > $current_balance) {
                $this->setFlashMessage('error', 'El empleado ya no tiene suficientes días disponibles');
                $this->redirect('/panel/vacation/show/' . $request_id);
                return;
            }

            // Aprobar solicitud
            $sql = "UPDATE vacation_requests
                    SET status = 'APPROVED', approved_by = ?, approved_at = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$_SESSION['admin_id'], $request_id]);

            // Actualizar balance del empleado
            $this->updateEmployeeBalance($request['employee_id'], $request['business_days'], 'APPROVED');

            // Crear registro de cálculo
            $this->createCalculationRecord($request_id, 'DAYS_APPROVED', $request['business_days'], $request['business_days'], 0);

            $this->setFlashMessage('success', 'Solicitud de vacaciones aprobada exitosamente');
            $this->redirect('/panel/vacation');

        } catch (PDOException $e) {
            $this->setFlashMessage('error', 'Error al aprobar solicitud: ' . $e->getMessage());
            $this->redirect('/panel/vacation');
        }
    }

    /**
     * Rechazar solicitud de vacaciones
     */
    public function reject($request_id)
    {
        try {
            $this->validateCsrfToken();

            $rejection_reason = $_POST['rejection_reason'] ?? '';

            if (empty($rejection_reason)) {
                $this->setFlashMessage('error', 'Debe proporcionar una razón para el rechazo');
                $this->redirect('/panel/vacation/show/' . $request_id);
                return;
            }

            // Verificar que la solicitud existe y está pendiente
            $sql = "SELECT * FROM vacation_requests WHERE id = ? AND status = 'PENDING'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $this->setFlashMessage('error', 'Solicitud no encontrada o ya procesada');
                $this->redirect('/panel/vacation');
                return;
            }

            // Rechazar solicitud
            $sql = "UPDATE vacation_requests
                    SET status = 'REJECTED', approved_by = ?, approved_at = NOW(), rejection_reason = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$_SESSION['admin_id'], $rejection_reason, $request_id]);

            // Crear registro de cálculo
            $this->createCalculationRecord($request_id, 'DAYS_REJECTED', $request['business_days'], 0, 0);

            $this->setFlashMessage('success', 'Solicitud de vacaciones rechazada');
            $this->redirect('/panel/vacation');

        } catch (PDOException $e) {
            $this->setFlashMessage('error', 'Error al rechazar solicitud: ' . $e->getMessage());
            $this->redirect('/panel/vacation');
        }
    }

    /**
     * Vista calendario de vacaciones
     */
    public function calendar()
    {
        try {
            // Obtener todas las vacaciones aprobadas del año actual
            $year = $_GET['year'] ?? date('Y');

            $sql = "SELECT vr.*, e.firstname, e.lastname, e.employee_id
                    FROM vacation_requests vr
                    INNER JOIN employees e ON vr.employee_id = e.id
                    WHERE vr.status IN ('APPROVED', 'TAKEN')
                    AND YEAR(vr.start_date) = ?
                    ORDER BY vr.start_date";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$year]);
            $vacation_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener feriados del año
            $sql = "SELECT * FROM vacation_calendar
                    WHERE YEAR(date) = ?
                    ORDER BY date";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$year]);
            $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->render('admin/vacation/calendar', [
                'vacation_events' => $vacation_events,
                'holidays' => $holidays,
                'year' => $year,
                'pageTitle' => 'Calendario de Vacaciones ' . $year
            ]);

        } catch (PDOException $e) {
            $this->setFlashMessage('error', 'Error al cargar calendario: ' . $e->getMessage());
            $this->redirect('/panel/vacation');
        }
    }

    /**
     * Reporte de balance por empleado
     */
    public function balance($employee_id)
    {
        try {
            // Obtener datos del empleado
            $sql = "SELECT e.*, cargo.nombre as cargo_nombre
                    FROM employees e
                    LEFT JOIN cargos cargo ON e.cargo_id = cargo.id
                    WHERE e.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                $this->setFlashMessage('error', 'Empleado no encontrado');
                $this->redirect('/panel/vacation');
                return;
            }

            // Calcular balance detallado
            $vacation_data = [
                'days_earned' => $this->calculator->VACATION_DAYS_EARNED($employee_id),
                'current_balance' => $this->calculator->VACATION_BALANCE($employee_id),
                'accrual_rate' => $this->calculator->VACATION_ACCRUAL_RATE($employee_id),
                'eligible' => $this->calculator->VACATION_ELIGIBLE($employee_id),
                'daily_salary' => $this->calculator->VACATION_COMPENSATION_AMOUNT($employee_id, 1)
            ];

            // Obtener historial de solicitudes
            $sql = "SELECT * FROM vacation_requests
                    WHERE employee_id = ?
                    ORDER BY request_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $vacation_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener balance anual
            $sql = "SELECT * FROM vacation_balances
                    WHERE employee_id = ?
                    ORDER BY year DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $annual_balances = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->render('admin/vacation/balance', [
                'employee' => $employee,
                'vacation_data' => $vacation_data,
                'vacation_history' => $vacation_history,
                'annual_balances' => $annual_balances,
                'pageTitle' => 'Balance de Vacaciones - ' . $employee['firstname'] . ' ' . $employee['lastname']
            ]);

        } catch (PDOException $e) {
            $this->setFlashMessage('error', 'Error al cargar balance: ' . $e->getMessage());
            $this->redirect('/panel/vacation');
        }
    }

    // ========================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ========================================

    /**
     * Verificar solapamiento con solicitudes aprobadas
     */
    private function hasOverlapWithApprovedRequests($employee_id, $start_date, $end_date)
    {
        $sql = "SELECT COUNT(*) as overlap_count
                FROM vacation_requests
                WHERE employee_id = ?
                AND status IN ('APPROVED', 'TAKEN')
                AND (
                    (start_date <= ? AND end_date >= ?) OR
                    (start_date <= ? AND end_date >= ?) OR
                    (start_date >= ? AND end_date <= ?)
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employee_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['overlap_count'] > 0;
    }

    /**
     * Calcular años trabajados
     */
    private function calculateYearsWorked($employee_id)
    {
        $sql = "SELECT TIMESTAMPDIFF(YEAR, fecha_ingreso, CURDATE()) as years_worked
                FROM employees WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employee_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['years_worked'] ?? 0;
    }

    /**
     * Crear registro de cálculo para auditoría
     */
    private function createCalculationRecord($request_id, $type, $days, $calculated_days, $amount)
    {
        $sql = "INSERT INTO vacation_calculations
                (vacation_request_id, calculation_type, calculation_base, days_calculated, amount_calculated, formula_used)
                VALUES (?, ?, ?, ?, ?, ?)";

        $formula = "Legislación Panamá: 30 días por 11 meses trabajados";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$request_id, $type, $days, $calculated_days, $amount, $formula]);
    }

    /**
     * Actualizar balance del empleado
     */
    private function updateEmployeeBalance($employee_id, $days_used, $operation)
    {
        $year = date('Y');

        // Verificar si existe balance para el año actual
        $sql = "SELECT id FROM vacation_balances WHERE employee_id = ? AND year = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employee_id, $year]);

        if ($stmt->fetch()) {
            // Actualizar balance existente
            if ($operation === 'APPROVED') {
                $sql = "UPDATE vacation_balances
                        SET days_taken = days_taken + ?, last_calculation_date = CURDATE()
                        WHERE employee_id = ? AND year = ?";
            }
        } else {
            // Crear nuevo balance
            $days_earned = $this->calculator->VACATION_DAYS_EARNED($employee_id);
            $sql = "INSERT INTO vacation_balances
                    (employee_id, year, days_earned, days_taken, last_calculation_date)
                    VALUES (?, ?, ?, ?, CURDATE())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id, $year, $days_earned, $days_used]);
            return;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days_used, $employee_id, $year]);
    }
}