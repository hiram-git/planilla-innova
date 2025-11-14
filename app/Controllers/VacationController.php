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
     * Reportes de Vacaciones (esqueleto mínimo)
     * Filtros básicos + tabla con DataTables
     */
    public function reports()
    {
        try {
            // Filtros básicos
            $employeeId   = $_GET['employee_id'] ?? null;
            $status       = $_GET['status'] ?? null; // PENDING, APPROVED, REJECTED
            $type         = $_GET['vacation_type'] ?? null; // ANNUAL, COMPENSATION
            $startDate    = $_GET['start_date'] ?? null; // filtra por request_date
            $endDate      = $_GET['end_date'] ?? null;
            $year         = $_GET['year'] ?? null;

            // Listado de empleados activos para el filtro
            $employees = $this->db->query("SELECT id, employee_id, firstname, lastname
                                           FROM employees WHERE situacion_id = 1
                                           ORDER BY firstname, lastname")
                                   ->fetchAll(PDO::FETCH_ASSOC);

            // Construir consulta base de solicitudes
            $sql = "SELECT vr.*, e.firstname, e.lastname, e.employee_id
                    FROM vacation_requests vr
                    INNER JOIN employees e ON vr.employee_id = e.id
                    WHERE 1=1";
            $params = [];

            if (!empty($employeeId)) {
                $sql .= " AND vr.employee_id = ?";
                $params[] = $employeeId;
            }
            if (!empty($status)) {
                $sql .= " AND vr.status = ?";
                $params[] = $status;
            }
            if (!empty($type)) {
                $sql .= " AND vr.vacation_type = ?";
                $params[] = $type;
            }
            if (!empty($startDate) && !empty($endDate)) {
                $sql .= " AND vr.request_date BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            } elseif (!empty($year)) {
                $sql .= " AND YEAR(vr.request_date) = ?";
                $params[] = $year;
            }

            $sql .= " ORDER BY vr.request_date DESC LIMIT 200";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->render('admin/vacation/reports', [
                'employees'     => $employees,
                'requests'      => $requests,
                'filters'       => [
                    'employee_id'   => $employeeId,
                    'status'        => $status,
                    'vacation_type' => $type,
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'year'          => $year,
                ],
                'pageTitle'     => 'Reportes de Vacaciones'
            ]);

        } catch (PDOException $e) {
            $this->redirectWithToastr('/panel/vacation', 'error', 'Error al cargar reportes: ' . $e->getMessage());
        }
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
                           TIMESTAMPDIFF(MONTH, e.fecha_ingreso, CURDATE()) as meses_trabajados
                    FROM employees e
                    LEFT JOIN cargos cargo ON e.cargo_id = cargo.id
                    LEFT JOIN situaciones s ON e.situacion_id = s.id
                    WHERE e.situacion_id = 1
                    ORDER BY e.firstname, e.lastname";

            $stmt = $this->db->query($sql);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular balances usando el nuevo sistema (vacation_annual_balances)
            foreach ($employees as &$employee) {
                // Generar años faltantes si no existen (igual que en create())
                $this->balanceService->generateMissingYears($employee['id']);

                $employee['days_earned'] = $this->calculator->VACATION_DAYS_EARNED($employee['id']);
                $employee['current_balance'] = $this->balanceService->getTotalAccumulatedBalance($employee['id']);
                $employee['eligible'] = $this->calculator->VACATION_ELIGIBLE($employee['id']);
                $employee['accrual_rate'] = $this->calculator->VACATION_ACCRUAL_RATE($employee['id']);

                // Calcular días tomados de todos los años
                $vacation_history = $this->balanceService->getVacationHistory($employee['id']);
                $days_taken = 0;
                foreach ($vacation_history as $year_record) {
                    $days_taken += $year_record['dias_pagados_year'] ?? 0;
                }
                $employee['days_taken'] = $days_taken;
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
            $this->redirectWithToastr('/panel', 'error', 'Error al cargar empleados: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para crear nueva solicitud de vacaciones
     */
    public function create($employee_id = null)
    {
        try {
            if (!$employee_id) {
                $this->redirectWithToastr('/panel/vacation', 'error', 'ID de empleado requerido');
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
                $this->redirectWithToastr('/panel/vacation', 'error', 'Empleado no encontrado o inactivo');
                return;
            }

            // Verificar elegibilidad para vacaciones
            if (!$this->calculator->VACATION_ELIGIBLE($employee_id)) {
                $this->redirectWithToastr('/panel/vacation', 'error', 'El empleado no tiene derecho a vacaciones (requiere 11 meses mínimo)');
                return;
            }

            // Calcular datos de vacaciones
            $current_year = date('Y');
            $annual_balance = $this->balanceService->getAnnualBalance($employee_id, $current_year);
            $vacation_history = $this->balanceService->getVacationHistory($employee_id);
            $total_accumulated_balance = $this->balanceService->getTotalAccumulatedBalance($employee_id);

            $vacation_data = [
                'days_earned' => $this->calculator->VACATION_DAYS_EARNED($employee_id),
                'current_balance' => $total_accumulated_balance, // Saldo total acumulado de todos los años
                'accrual_rate' => $this->calculator->VACATION_ACCRUAL_RATE($employee_id),
                'daily_salary' => $this->calculator->VACATION_COMPENSATION_AMOUNT($employee_id, 1),
                // Datos de balance anual
                'annual_balance' => $annual_balance,
                'vacation_history' => $vacation_history,
                'current_year' => $current_year,
                'total_accumulated_balance' => $total_accumulated_balance // Para mostrar en el formulario
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
            $this->redirectWithToastr('/panel/vacation', 'error', 'Error al cargar empleado: ' . $e->getMessage());
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
                $this->redirectWithToastr('/panel/vacation/create/' . $employee_id, 'error', 'Todos los campos obligatorios deben ser completados');
                return;
            }

            // Validar fechas
            $start_datetime = new DateTime($start_date);
            $end_datetime = new DateTime($end_date);
            $today = new DateTime();

            // COMENTADO: Permitir solicitudes de fechas pasadas para registros históricos
            /*
            if ($start_datetime < $today) {
                $this->redirectWithToastr('/panel/vacation/create/' . $employee_id, 'error', 'La fecha de inicio no puede ser en el pasado');
                return;
            }
            */

            // COMENTADO: Validar anticipación de 15 días - Se permite para permitir solicitudes anteriores en el sistema
            /*
            $anticipation_days = $today->diff($start_datetime)->days;
            if ($start_datetime > $today && $anticipation_days < 15) {
                $this->redirectWithToastr('/panel/vacation/create/' . $employee_id, 'error', "Las vacaciones deben solicitarse con al menos 15 días de anticipación. Días de anticipación actual: {$anticipation_days} días.");
                return;
            }
            */

            if ($end_datetime <= $start_datetime) {
                $this->redirectWithToastr('/panel/vacation/create/' . $employee_id, 'error', 'La fecha de fin debe ser posterior a la fecha de inicio');
                return;
            }

            // Verificar elegibilidad
            if (!$this->calculator->VACATION_ELIGIBLE($employee_id)) {
                $this->redirectWithToastr('/panel/vacation/create/' . $employee_id, 'error', 'El empleado no tiene derecho a vacaciones');
                return;
            }

            // Calcular días solicitados
            $total_days = $start_datetime->diff($end_datetime)->days + 1;
            $business_days = $this->calculator->VACATION_BUSINESS_DAYS($start_date, $end_date);

            // Verificar balance disponible total acumulado (todos los años)
            $current_balance = $this->balanceService->getTotalAccumulatedBalance($employee_id);

            // Validación usando el servicio de balance anual (valida contra saldo total acumulado)
            $validation = $this->balanceService->canProcessVacationRequest(
                $employee_id,
                $ano_vacaciones,
                $dias_solicitados_pagar,
                $dias_solicitados_disfrute
            );

            if (!$validation['valid']) {
                $this->redirectWithToastr('/panel/vacation/create/' . $employee_id, 'error', $validation['message']);
                return;
            }

            // Verificar solapamientos con otras solicitudes aprobadas
            if ($this->hasOverlapWithApprovedRequests($employee_id, $start_date, $end_date)) {
                $this->redirectWithToastr('/panel/vacation/create/' . $employee_id, 'error', 'Las fechas solicitadas se solapan con vacaciones ya aprobadas');
                return;
            }

            // Calcular datos adicionales
            $years_worked = $this->calculateYearsWorked($employee_id);
            $accumulated_days = $this->calculator->VACATION_DAYS_EARNED($employee_id);
            $available_days = $current_balance;
            $remaining_days = $available_days - $business_days;

            // Calcular salario diario basado en promedio de últimos 11 meses
            $daily_salary = $this->calculateVacationDailySalary($employee_id, $start_date);
            $compensation_amount = ($vacation_type === 'COMPENSATION') ? $daily_salary * $business_days : 0;

            // Calcular compensación para días solicitados por pagar (usando promedio 11 meses)
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
            /*$this->balanceService->updateAnnualBalance(
                $employee_id,
                $ano_vacaciones,
                $dias_solicitados_pagar,
                $dias_solicitados_disfrute
            );*/

            // Crear registro de cálculo para auditoría
            $this->createCalculationRecord($request_id, 'DAYS_REQUESTED', $business_days, $business_days, $compensation_amount);

            $this->redirectWithToastr('/panel/vacation', 'success', 'Solicitud de vacaciones creada exitosamente. ID: ' . $request_id);

        } catch (PDOException $e) {
            $this->redirectWithToastr('/panel/vacation', 'error', 'Error al crear solicitud: ' . $e->getMessage());
        }
    }

    

    /**
     * Mostrar detalles de una solicitud de vacaciones
     */
    public function show($request_id)
    {
        try {
            // Obtener datos de la solicitud
            $sql = "SELECT vr.*, e.firstname, e.lastname, e.employee_id as employee_code, e.fecha_ingreso,
                           COALESCE(cargo_pos.nombre, cargo_emp.nombre) as cargo_nombre,
                           CONCAT(a.firstname, ' ', a.lastname) as approver_name
                    
                    FROM
                        vacation_requests vr
                        INNER JOIN employees e ON vr.employee_id = e.id
                        LEFT JOIN posiciones p ON e.position_id = p.id
                        LEFT JOIN cargos cargo_pos ON p.id_cargo = cargo_pos.id
                        LEFT JOIN cargos cargo_emp ON e.cargo_id = cargo_emp.id
                        LEFT JOIN admin a ON vr.approved_by = a.id
                    WHERE vr.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $this->redirectWithToastr('/panel/vacation', 'error', 'Solicitud de vacaciones no encontrada');
                return;
            }

            // Obtener cálculos relacionados
            $sql = "SELECT * FROM vacation_calculations WHERE vacation_request_id = ? ORDER BY id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$request_id]);
            $calculations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular balance actual del empleado usando vacation_annual_balances
            $current_balance = $this->balanceService->getTotalAccumulatedBalance($request['employee_id']);

            $this->render('admin/vacation/show', [
                'request' => $request,
                'calculations' => $calculations,
                'current_balance' => $current_balance,
                'pageTitle' => 'Solicitud de Vacaciones #' . $request_id
            ]);

        } catch (PDOException $e) {
            $this->redirectWithToastr('/panel/vacation', 'error', 'Error al cargar solicitud: ' . $e->getMessage());
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
                $this->redirectWithToastr('/panel/vacation', 'error', 'Solicitud no encontrada o ya procesada');
                return;
            }

            // Verificar balance actual total acumulado
            $current_balance = $this->balanceService->getTotalAccumulatedBalance($request['employee_id']);
            if ($request['dias_solicitados_pagar'] > $current_balance) {
                $this->redirectWithToastr('/panel/vacation/show/' . $request_id, 'error', 'El empleado ya no tiene suficientes días disponibles (saldo total: ' . $current_balance . ' días)');
                return;
            }

            // Recalcular compensation_amount usando promedio de últimos 11 meses
            $daily_salary = $this->calculateVacationDailySalary($request['employee_id'], $request['start_date']);
            $compensation_amount = $daily_salary * $request['dias_solicitados_pagar'];

            // Actualizar balances anuales y totales generales
            $this->balanceService->updateAnnualBalance(
                $request['employee_id'],
                $request['ano_vacaciones'],
                $request['dias_solicitados_pagar'],
                $request['dias_solicitados_disfrute']
            );

            // Aprobar solicitud y actualizar compensation_amount
            $sql = "UPDATE vacation_requests
                    SET status = 'APPROVED', approved_by = ?, approved_at = NOW(),
                        compensation_amount = ?, daily_salary = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$_SESSION['admin_id'], $compensation_amount, $daily_salary, $request_id]);

            // NOTA: Los días ya fueron restados del balance cuando se creó la solicitud (método store())
            // por lo tanto NO se deben restar nuevamente aquí para evitar doble resta
            // El sistema nuevo usa vacation_annual_balances que ya fue actualizado

            // Crear registro de cálculo
            $this->createCalculationRecord($request_id, 'DAYS_APPROVED', $request['business_days'], $request['business_days'], 0);

            // Notificación Toastr y redirección
            $this->redirectWithToastr('/panel/vacation', 'success', 'Solicitud de vacaciones aprobada exitosamente');

        } catch (PDOException $e) {
            $this->redirectWithToastr('/panel/vacation', 'error', 'Error al aprobar solicitud: ' . $e->getMessage());
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
                $this->redirectWithToastr('/panel/vacation/show/' . $request_id, 'error', 'Debe proporcionar una razón para el rechazo');
                return;
            }

            // Verificar que la solicitud existe y está pendiente
            $sql = "SELECT * FROM vacation_requests WHERE id = ? AND status = 'PENDING'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $this->redirectWithToastr('/panel/vacation', 'error', 'Solicitud no encontrada o ya procesada');
                return;
            }

            // Rechazar solicitud
            $sql = "UPDATE vacation_requests
                    SET status = 'REJECTED', approved_by = ?, approved_at = NOW(), rejection_reason = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$_SESSION['admin_id'], $rejection_reason, $request_id]);

            // IMPORTANTE: Revertir los balances que fueron actualizados cuando se creó la solicitud
            // Los días que se restaron deben devolverse al balance del empleado
            if (!empty($request['ano_vacaciones']) &&
                (!empty($request['dias_solicitados_pagar']) || !empty($request['dias_solicitados_disfrute']))) {

                $this->balanceService->revertAnnualBalance(
                    $request['employee_id'],
                    $request['ano_vacaciones'],
                    $request['dias_solicitados_pagar'] ?? 0,
                    $request['dias_solicitados_disfrute'] ?? 0
                );
            }

            // Crear registro de cálculo
            $this->createCalculationRecord($request_id, 'DAYS_REJECTED', $request['business_days'], 0, 0);

            $this->redirectWithToastr('/panel/vacation', 'success', 'Solicitud de vacaciones rechazada. Los días han sido devueltos al balance del empleado.');

        } catch (PDOException $e) {
            $this->redirectWithToastr('/panel/vacation', 'error', 'Error al rechazar solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Obtener balance anual por empleado y año (AJAX)
     */
    public function getAnnualBalance($employee_id)
    {
        try {
            $year = $_GET['year'] ?? date('Y');

            // Obtener balance del año específico
            $annual_balance = $this->balanceService->getAnnualBalance($employee_id, $year);

            // Obtener saldo total acumulado
            $total_accumulated = $this->balanceService->getTotalAccumulatedBalance($employee_id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'dias_vacaciones_anuales' => $annual_balance['dias_vacaciones_anuales'] ?? 30,
                    'dias_pagados_year' => $annual_balance['dias_pagados_year'] ?? 0,
                    'dias_disfrutados_year' => $annual_balance['dias_disfrutados_year'] ?? 0,
                    'saldo_disponible_year' => $annual_balance['saldo_disponible_year'] ?? 30,
                    'total_accumulated_balance' => $total_accumulated
                ]
            ]);
            exit;

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener balance: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Generar años faltantes de vacaciones para un empleado (AJAX)
     * ✅ NUEVO: Detecta y crea automáticamente años desde fecha_ingreso hasta año actual
     */
    public function generateMissingYears($employee_id)
    {
        try {
            $this->validateCsrfToken();

            // Generar años faltantes usando el servicio
            $result = $this->balanceService->generateMissingYears($employee_id);

            header('Content-Type: application/json');
            echo json_encode($result);
            exit;

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar años: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Vista calendario de vacaciones
     * ✅ UNIFICADO: Usa calendario empresarial (business_calendar) como base
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

            // ✅ NUEVO: Usar calendario empresarial (business_calendar) en lugar de vacation_calendar
            // Obtener días especiales del calendario empresarial (feriados, duelos, días especiales)
            $sql = "SELECT date_value as date, description, day_type, status
                    FROM business_calendar
                    WHERE year_value = ?
                    AND day_type IN ('FERIADO', 'DUELO_NACIONAL', 'ESPECIAL')
                    ORDER BY date_value";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$year]);
            $business_days = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener colores del modelo BusinessCalendar para consistencia visual
            $dayTypeColors = \App\Models\BusinessCalendar::getDayTypeColors();

            $this->render('admin/vacation/calendar', [
                'vacation_events' => $vacation_events,
                'business_days' => $business_days, // Días del calendario empresarial
                'day_type_colors' => $dayTypeColors, // Colores consistentes
                'year' => $year,
                'pageTitle' => 'Calendario de Vacaciones ' . $year
            ]);

        } catch (PDOException $e) {
            $this->redirectWithToastr('/panel/vacation', 'error', 'Error al cargar calendario: ' . $e->getMessage());
        }
    }

    /**
     * Reporte de balance por empleado
     * ✅ ACTUALIZADO: Usa sistema simplificado vacation_annual_balances
     */
    public function balance($employee_id)
    {
        try {
            // Obtener datos del empleado
            $sql = "SELECT e.*,
                           COALESCE(cargo_pos.nombre, cargo_emp.nombre) as position_name
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos cargo_pos ON p.id_cargo = cargo_pos.id
                    LEFT JOIN cargos cargo_emp ON e.cargo_id = cargo_emp.id
                    WHERE e.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                $this->redirectWithToastr('/panel/vacation', 'error', 'Empleado no encontrado');
                return;
            }

            // ✅ NUEVO: Usar sistema simplificado vacation_annual_balances
            $annual_balances = $this->balanceService->getVacationHistory($employee_id);

            // Calcular totales desde vacation_annual_balances
            $total_days_earned = 0;
            $total_days_taken = 0;
            $total_days_enjoyed = 0; // Días de disfrute tomados (informativo)

            foreach ($annual_balances as $balance) {
                $total_days_earned += $balance['dias_vacaciones_anuales'] ?? 0;
                $total_days_taken += $balance['dias_pagados_year'] ?? 0;
                $total_days_enjoyed += $balance['dias_disfrutados_year'] ?? 0;
            }

            // Balance actual = Total ganado - Total pagado (tomado)
            $current_balance = $this->balanceService->getTotalAccumulatedBalance($employee_id);

            // Calcular días de disfrute pendientes: Días Tomados - Días Disfrutados
            $total_disfrute_pendiente = $total_days_taken - $total_days_enjoyed;

            // Calcular balance detallado
            $vacation_data = [
                'days_earned' => $total_days_earned, // Total días ganados de todos los años
                'days_taken' => $total_days_taken, // Total días pagados (tomados) de todos los años
                'days_enjoyed' => $total_days_enjoyed, // Total días disfrutados (informativo)
                'days_enjoyed_pending' => max(0, $total_disfrute_pendiente), // Días pendientes de disfrutar (informativo)
                'current_balance' => $current_balance, // Saldo disponible total
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

            $this->render('admin/vacation/balance', [
                'employee' => $employee,
                'vacation_data' => $vacation_data,
                'vacation_history' => $vacation_history,
                'annual_balances' => $annual_balances,
                'pageTitle' => 'Balance de Vacaciones - ' . $employee['firstname'] . ' ' . $employee['lastname']
            ]);

        } catch (PDOException $e) {
            $this->redirectWithToastr('/panel/vacation', 'error', 'Error al cargar balance: ' . $e->getMessage());
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
     * Calcular salario diario de vacaciones basado en promedio de últimos 11 meses
     * Según legislación panameña: (Total ingresos últimos 11 meses) / 11 / 30
     *
     * @param int $employee_id ID del empleado
     * @param string $start_date Fecha de inicio de vacaciones (para calcular 11 meses hacia atrás)
     * @return float Salario diario promedio
     */
    private function calculateVacationDailySalary($employee_id, $start_date)
    {
        try {
            // Calcular fecha 11 meses antes del inicio de vacaciones
            $fecha_inicio_vacaciones = new DateTime($start_date);
            $fecha_11_meses_antes = clone $fecha_inicio_vacaciones;
            $fecha_11_meses_antes->modify('-11 months');

            $fechaDesde = $fecha_11_meses_antes->format('Y-m-d');
            $fechaHasta = $fecha_inicio_vacaciones->format('Y-m-d');

            // Obtener total de ingresos de los últimos 11 meses
            $sql = "SELECT SUM(ape.monto) as total_ingresos
                    FROM acumulados_por_empleado ape
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    WHERE ape.employee_id = ?
                    AND c.tipo_concepto = 'A'
                    AND ape.fecha >= ?
                    AND ape.fecha < ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id, $fechaDesde, $fechaHasta]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $total_ingresos = (float)($result['total_ingresos'] ?? 0);

            // Si no hay acumulados, usar el salario actual
            if ($total_ingresos <= 0) {
                $daily_salary = $this->calculator->VACATION_COMPENSATION_AMOUNT($employee_id, 1);
                error_log("No se encontraron acumulados para empleado $employee_id. Usando salario actual: $daily_salary");
                return $daily_salary;
            }

            // Calcular salario diario: Total ingresos / 11 meses / 30 días
            $salario_mensual_promedio = $total_ingresos / 11;
            $salario_diario = $salario_mensual_promedio / 30;

            error_log("Vacaciones empleado $employee_id: Total ingresos 11 meses = $total_ingresos, Mensual promedio = $salario_mensual_promedio, Diario = $salario_diario");

            return $salario_diario;

        } catch (\Exception $e) {
            error_log("Error calculando salario de vacaciones: " . $e->getMessage());
            // Fallback al salario actual si hay error
            return $this->calculator->VACATION_COMPENSATION_AMOUNT($employee_id, 1);
        }
    }

    /**
     * Exportar PDF de la solicitud de vacaciones (delegado a PDFReportController)
     */
    public function exportPDF($request_id)
    {
        try {
            $pdfController = new \App\Controllers\PDFReportController();
            if (method_exists($pdfController, 'generateVacationRequestPDF')) {
                return $pdfController->generateVacationRequestPDF($request_id);
            }
            throw new \Exception('Método generateVacationRequestPDF no disponible');
        } catch (\Exception $e) {
            error_log('Error generating vacation PDF: ' . $e->getMessage());
            $this->setFlashMessage('Error al generar PDF: ' . $e->getMessage(), 'error');
            return $this->redirect('/panel/vacation/show/' . $request_id);
        }
    }

    /**
     * Generar planilla de vacaciones para una solicitud aprobada
     */
    public function generatePayroll($request_id)
    {
        try {
            $this->validateCsrfToken();

            // Obtener la solicitud de vacaciones
            $sql = "SELECT vr.*, e.id as employee_id
                    FROM vacation_requests vr
                    INNER JOIN employees e ON vr.employee_id = e.id
                    WHERE vr.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Solicitud de vacaciones no encontrada'
                ]);
                exit;
            }

            // Validar que la solicitud esté aprobada
            if ($request['status'] !== 'APPROVED') {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Solo se pueden generar planillas para solicitudes aprobadas'
                ]);
                exit;
            }

            // Obtener tipo de planilla del sessionStorage (viene por POST)
            $tipo_planilla_id = $_POST['tipo_planilla_id'] ?? null;
            if (!$tipo_planilla_id) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Debe seleccionar un tipo de planilla desde el navbar'
                ]);
                exit;
            }

            // Frecuencia de Vacaciones (ID 11)
            $frecuencia_id = 11;

            // Crear cabecera de planilla
            $descripcion = "Planilla de Vacaciones - Solicitud #{$request_id}";
            $payrollData = [
                'descripcion' => $descripcion,
                'tipo_planilla_id' => $tipo_planilla_id,
                'frecuencia_id' => $frecuencia_id,
                'fecha' => date('Y-m-d'),
                'periodo_inicio' => $request['start_date'],
                'periodo_fin' => $request['end_date'],
                'usuario_creacion' => $_SESSION['admin_id'] ?? null
            ];

            $payrollModel = new \App\Models\Payroll();
            $payroll_id = $payrollModel->create($payrollData);

            if (!$payroll_id) {
                throw new \Exception('Error al crear la planilla');
            }

            // Buscar concepto VACACIONES (ID 61)
            $sql = "SELECT id FROM concepto WHERE concepto = 'VACACIONES' LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $concepto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$concepto) {
                throw new \Exception('Concepto VACACIONES no encontrado');
            }

            $concepto_id = $concepto['id'];

            // Obtener información completa del empleado para el detalle
            $sql = "SELECT e.*
                    FROM employees e
                    WHERE e.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$request['employee_id']]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                throw new \Exception('Empleado no encontrado');
            }

            // Obtener tipo de concepto
            $sql = "SELECT tipo_concepto FROM concepto WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$concepto_id]);
            $concepto_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $tipo_concepto = $concepto_data['tipo_concepto'] ?? 'A';

            // Insertar detalle de planilla para el empleado
            $monto_vacaciones = $request['compensation_amount'] ?? 0;

            // Preparar statement para insertar múltiples conceptos
            $sql = "INSERT INTO planilla_detalle
                    (planilla_cabecera_id, employee_id, concepto_id, monto, tipo,
                     organigrama_id, organigrama_path, position_id, schedule_id,
                     firstname, lastname, cargo_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);

            // 1. Insertar concepto VACACIONES
            $stmt->execute([
                $payroll_id,
                $request['employee_id'],
                $concepto_id,
                $monto_vacaciones,
                $tipo_concepto,
                $employee['organigrama_id'],
                $employee['organigrama_path'],
                $employee['position_id'],
                $employee['schedule_id'],
                $employee['firstname'],
                $employee['lastname'],
                $employee['cargo_id']
            ]);

            // 2. Insertar concepto Seguro Social (9.75% del monto de vacaciones)
            $sql_ss = "SELECT id FROM concepto WHERE id = 2 LIMIT 1";
            $stmt_ss = $this->db->prepare($sql_ss);
            $stmt_ss->execute();
            $concepto_ss = $stmt_ss->fetch(PDO::FETCH_ASSOC);

            if ($concepto_ss) {
                $monto_ss = $monto_vacaciones * 0.0975;
                $stmt->execute([
                    $payroll_id,
                    $request['employee_id'],
                    $concepto_ss['id'],
                    $monto_ss,
                    'D', // Deducción
                    $employee['organigrama_id'],
                    $employee['organigrama_path'],
                    $employee['position_id'],
                    $employee['schedule_id'],
                    $employee['firstname'],
                    $employee['lastname'],
                    $employee['cargo_id']
                ]);
            }

            // 3. Insertar concepto Seguro Educativo (1.25% del monto de vacaciones)
            $sql_se = "SELECT id FROM concepto WHERE id = 3 LIMIT 1";
            $stmt_se = $this->db->prepare($sql_se);
            $stmt_se->execute();
            $concepto_se = $stmt_se->fetch(PDO::FETCH_ASSOC);

            if ($concepto_se) {
                $monto_se = $monto_vacaciones * 0.0125;
                $stmt->execute([
                    $payroll_id,
                    $request['employee_id'],
                    $concepto_se['id'],
                    $monto_se,
                    'D', // Deducción
                    $employee['organigrama_id'],
                    $employee['organigrama_path'],
                    $employee['position_id'],
                    $employee['schedule_id'],
                    $employee['firstname'],
                    $employee['lastname'],
                    $employee['cargo_id']
                ]);
            }

            // Actualizar estado de planilla a PROCESADA
            $sql_update = "UPDATE planilla_cabecera SET estado = 'PROCESADA' WHERE id = ?";
            $stmt_update = $this->db->prepare($sql_update);
            $stmt_update->execute([$payroll_id]);

            // Retornar respuesta JSON exitosa
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Planilla de vacaciones generada y procesada exitosamente',
                'payroll_id' => $payroll_id
            ]);
            exit;

        } catch (\Exception $e) {
            error_log('Error generating vacation payroll: ' . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar planilla: ' . $e->getMessage()
            ]);
            exit;
        }
    }

}
