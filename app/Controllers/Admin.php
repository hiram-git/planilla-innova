<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Config;
use App\Core\Security;
use App\Core\ActivityLogger;
use App\Middleware\AuthMiddleware;

class Admin extends Controller
{
    public function __construct()
    {
        Config::load();
    }

    public function index()
    {

        // Resolver tenant si viene parámetro ?tenant=
        if (isset($_GET['tenant'])) {
            \App\Core\TenantResolver::resolve();

            // Si se resolvió correctamente, resetear Database para usar nuevo tenant
            if (\App\Core\TenantResolver::hasTenant()) {
                \App\Core\Database::resetInstance();

                $tenantInfo = \App\Core\TenantResolver::getTenantInfo();
                error_log("Tenant resolved in index: " . ($tenantInfo['company_name'] ?? 'Unknown'));
            }
        }

        AuthMiddleware::redirectIfAuthenticated();

        $data = [
            'title' => 'Administración - Login',
            'csrf_token' => AuthMiddleware::generateCSRF(),
            'tenant_name' => \App\Core\TenantResolver::hasTenant()
                ? \App\Core\TenantResolver::getTenantInfo()['company_name'] ?? null
                : null
        ];

        $this->view('admin/login', $data);
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Si es GET, mostrar el formulario de login

            // Resolver tenant si viene parámetro ?tenant=
            if (isset($_GET['tenant'])) {
                \App\Core\TenantResolver::resolve();

                // Si se resolvió correctamente, resetear Database para usar nuevo tenant
                if (\App\Core\TenantResolver::hasTenant()) {
                    \App\Core\Database::resetInstance();

                    // Opcional: Log para debugging
                    $tenantInfo = \App\Core\TenantResolver::getTenantInfo();
                    error_log("Tenant resolved: " . ($tenantInfo['company_name'] ?? 'Unknown'));
                }
            }
            AuthMiddleware::redirectIfAuthenticated();

            $data = [
                'title' => 'Administración - Login',
                'csrf_token' => AuthMiddleware::generateCSRF(),
                'tenant_name' => \App\Core\TenantResolver::hasTenant()
                    ? \App\Core\TenantResolver::getTenantInfo()['company_name'] ?? null
                    : null
            ];
            $this->view('admin/login', $data);
            return;
        }

        AuthMiddleware::rateLimit(5, 120);
        AuthMiddleware::validateCSRF();

        $username = Security::sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $companyCode = Security::sanitizeInput($_POST['company_code'] ?? '');

        // Intentar resolver tenant por código de empresa antes de autenticar
        if (!empty($companyCode)) {
            $tenantResolved = \App\Core\TenantResolver::resolveByCompanyCode($companyCode);

            if ($tenantResolved) {
                // Tenant encontrado - resetear conexión de base de datos
                \App\Core\Database::resetInstance();

                $tenantInfo = \App\Core\TenantResolver::getTenantInfo();
                error_log("Tenant resolved for login: {$tenantInfo['company_name']} (RUC/Code: {$companyCode})");
            } else {
                // Código de empresa no encontrado
                Security::logSecurityEvent('login_invalid_company_code', [
                    'username' => $username,
                    'company_code' => $companyCode
                ]);
                $_SESSION['error'] = 'Código de empresa no válido o inactivo';
                $this->redirect('/panel');
                return;
            }
        } else {
            // Sin código de empresa - usar base de datos por defecto (planilla_prod)
            error_log("Login without company code - using default database");
        }

        if (empty($username) || empty($password)) {
            Security::logSecurityEvent('login_attempt_empty_fields', ['username' => $username]);
            $_SESSION['error'] = 'Usuario y contraseña requeridos';
            $this->redirect('/admin');
        }

        $adminModel = $this->model('Admin');
        $admin = $adminModel->authenticate($username, $password);

        if ($admin) {
            // ✅ REFACTORIZADO: Almacenar datos completos con información de rol dinámica
            $_SESSION['admin'] = $admin['id'];
            $_SESSION['admin_name'] = $adminModel->getFullName($admin);
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'] ?? '';
            $_SESSION['admin_role'] = $admin['role_name'] ?? 'Usuario';
            $_SESSION['admin_role_id'] = $admin['role_id'];
            $_SESSION['admin_role_description'] = $admin['role_description'] ?? '';
            $_SESSION['is_super_admin'] = $adminModel->isSuperAdmin($admin);
            $_SESSION['admin_login_time'] = date('Y-m-d H:i:s');

            // Guardar información del tenant para validación de storage
            $licenseKey = null;
            if (\App\Core\TenantResolver::hasTenant()) {
                $tenantInfo = \App\Core\TenantResolver::getTenantInfo();
                $_SESSION['tenant_id'] = $tenantInfo['id'] ?? null;
                $_SESSION['tenant_license'] = $tenantInfo['db_name'] ?? null;
                $_SESSION['tenant_db'] = $tenantInfo['db_name'] ?? null;
                $licenseKey = $tenantInfo['license_key'] ?? null;
                $_SESSION['license_days_remaining'] = $tenantInfo['license_expires_at'] ?? null;
            } else {
                // BD principal (planilla_prod) - sin validación de licencia
                $_SESSION['tenant_id'] = 'default';
                $_SESSION['tenant_license'] = 'default';
                $_SESSION['tenant_db'] = $_ENV['DB_NAME'] ?? 'planilla_prod';
                $_SESSION['license_days_remaining'] = "-";
            }

            // ✅ VALIDAR LICENCIA para tenants (solo si no es la BD principal)
            if ($licenseKey && $licenseKey !== 'default') {
                $licenseValidator = new \App\Services\LicenseValidator();
                $validationResult = $licenseValidator->validateAndStore($licenseKey);

                if (!$validationResult['valid']) {
                    // Licencia inválida o expirada - destruir sesión
                    $companyName = $tenantInfo['company_name'] ?? 'Su empresa';
                    $errorMessage = $validationResult['message'] ?? 'Licencia no válida';

                    // Log del intento de acceso con licencia inválida
                    error_log("Acceso denegado - Licencia inválida: {$licenseKey} - Usuario: {$username}");
                    Security::logSecurityEvent('login_license_invalid', [
                        'admin_id' => $admin['id'],
                        'license_key' => $licenseKey,
                        'error' => $errorMessage
                    ]);

                    // Destruir la sesión
                    session_unset();
                    session_destroy();
                    session_start();

                    // Mostrar mensaje de error
                    $_SESSION['error'] = $errorMessage;
                    $this->redirect('/panel');
                    return;
                }

                // Licencia válida - mostrar advertencia si está próxima a vencer
                if (isset($_SESSION['license_warning'])) {
                    $_SESSION['info'] = $_SESSION['license_warning'];
                }
            }

            $_SESSION['success'] = 'Sesión iniciada con éxito';

            // Log successful login
            ActivityLogger::logLogin($admin['id'], $username, true);
            Security::logSecurityEvent('login_success', ['admin_id' => $admin['id']]);
            $this->redirect('/panel/dashboard');
        } else {
            // Log failed login attempt
            ActivityLogger::logLogin(null, $username, false);
            Security::logSecurityEvent('login_failed', ['username' => $username]);
            $_SESSION['error'] = 'Credenciales incorrectas';
            $this->redirect('/panel');
        }
    }

    public function dashboard()
    {
        $this->requireAuth();

        $employee = $this->model('Employee');
        $attendance = $this->model('Attendance');
        $position = $this->model('Posicion');
        $cargo = $this->model('Cargo');
        $schedule = $this->model('Schedule');
        $acumuladoController = new \App\Controllers\AcumuladoController();

        // Obtener tipos de planilla para filtro
        $tiposPlanilla = $this->getTiposPlanilla();
        $tipoSeleccionadoRaw = $_GET['tipo_planilla'] ?? null;

        // Normalizar valor: si es cadena vacía, convertir a null
        $tipoSeleccionado = (!empty($tipoSeleccionadoRaw) && $tipoSeleccionadoRaw !== '') ? $tipoSeleccionadoRaw : null;

        // Obtener estadísticas básicas (filtradas por tipo de planilla si se seleccionó)
        $employeesList = $tipoSeleccionado
            ? $employee->getEmployeesByTipoPlanilla($tipoSeleccionado)
            : $employee->all();

        $totalEmployees = count($employeesList);
        $activeEmployees = $this->getActiveEmployees($tipoSeleccionado);
        $totalPositions = count($position->all());
        $totalCargos = count($cargo->all());
        $totalSchedules = count($schedule->all());

        // Asistencia de hoy (filtrada por tipo de planilla)
        $todayAttendance = $attendance->getAttendanceByDateRange(
            date('Y-m-d'),
            date('Y-m-d'),
            null,
            $tipoSeleccionado
        );
        $todayStats = $this->calculateTodayStats($todayAttendance, $totalEmployees);

        // Puntualidad mensual (filtrada por tipo de planilla)
        $monthlyPunctuality = $this->calculateMonthlyPunctuality($attendance, $tipoSeleccionado);

        // Estadísticas para las tarjetas (últimos 30 días, filtradas)
        $monthlyStats = $this->getMonthlyAttendanceStats($attendance, $tipoSeleccionado);

        // Asistencia reciente (últimos 7 días) para la tabla (filtrada)
        $recentAttendance = $attendance->getAttendanceByDateRange(
            date('Y-m-d', strtotime('-7 days')),
            date('Y-m-d'),
            null,
            $tipoSeleccionado
        );

        // Datos para la gráfica (últimos 30 días, filtrados)
        $attendanceChartData = $this->getAttendanceChartData($attendance, $tipoSeleccionado);

        // Obtener datos de acumulados para el dashboard (filtrados)
        $acumuladosData = $this->getAcumuladosData($tipoSeleccionado);

        // Obtener datos del módulo de asistencias avanzado (nuevos)
        $activeAlerts = $this->getActiveAlerts($tipoSeleccionado, 5);
        $alertStats = $this->getAlertStats($tipoSeleccionado);
        $attendanceMonthStats = $this->getCurrentMonthAttendanceStats($tipoSeleccionado);
        $absenceMonthStats = $this->getCurrentMonthAbsenceStats($tipoSeleccionado);
        $topTardinessEmployees = $this->getTopTardinessEmployees($tipoSeleccionado, 5);

        // Contratos próximos a vencer (60 días = 2 meses)
        $expiringContracts = $employee->getExpiringContracts($tipoSeleccionado, 60);

        $data = [
            'title' => 'Dashboard Administrativo',
            'page_title' => 'Panel de Control',
            'total_employees' => $totalEmployees,
            'active_employees' => $activeEmployees,
            'total_positions' => $totalPositions,
            'total_cargos' => $totalCargos,
            'total_schedules' => $totalSchedules,
            'today_attendance' => $todayStats['count'],
            'employees_present' => $todayStats['present'],
            'employees_late' => $todayStats['late'],
            'attendance_percentage' => $todayStats['percentage'],
            'monthly_punctuality' => $monthlyPunctuality,
            'monthly_stats' => $monthlyStats,
            'recent_attendance' => array_slice($recentAttendance, 0, 10),
            'attendance_chart_data' => $attendanceChartData,
            'tipos_planilla' => $tiposPlanilla,
            'tipo_seleccionado' => $tipoSeleccionado,
            'acumulados_data' => $acumuladosData,
            // Nuevos datos del módulo de asistencias
            'active_alerts' => $activeAlerts,
            'alert_stats' => $alertStats,
            'attendance_month_stats' => $attendanceMonthStats,
            'absence_month_stats' => $absenceMonthStats,
            'top_tardiness_employees' => $topTardinessEmployees,
            // Contratos próximos a vencer
            'expiring_contracts' => $expiringContracts
        ];

        $this->view('admin/dashboard', $data);
    }

    private function getActiveEmployees($tipoSeleccionado = null)
    {
        $employee = $this->model('Employee');
        $whereClause = " (situacion_id = 1 OR situacion_id LIKE '%activ%' OR situacion_id LIKE '%ACTIV%') ";

        // Soporte para múltiples tipos de planilla separados por comas
        if ($tipoSeleccionado) {
            $whereClause .= " AND FIND_IN_SET(" . (int)$tipoSeleccionado . ", tipo_planilla_id)";
        }

        $sql = "SELECT COUNT(*) as count FROM employees WHERE " . $whereClause;
        $result = $employee->db->find($sql);
        return $result['count'] ?? 0;
    }

    private function calculateTodayStats($todayAttendance, $totalEmployees)
    {
        $employeesPresent = 0;
        $employeesLate = 0;
        $todayCount = count($todayAttendance);

        foreach ($todayAttendance as $att) {
            if ($att['time_in']) {
                $employeesPresent++;
                if ($att['status'] == 0) {
                    $employeesLate++;
                }
            }
        }

        $percentage = $totalEmployees > 0 ? round(($employeesPresent / $totalEmployees) * 100, 1) : 0;

        return [
            'count' => $todayCount,
            'present' => $employeesPresent,
            'late' => $employeesLate,
            'percentage' => $percentage
        ];
    }

    private function calculateMonthlyPunctuality($attendance, $tipoSeleccionado = null)
    {
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d');

        $monthlyAttendance = $attendance->getAttendanceByDateRange($startDate, $endDate, null, $tipoSeleccionado);

        $totalRecords = count($monthlyAttendance);
        $punctualRecords = 0;

        foreach ($monthlyAttendance as $record) {
            if ($record['status'] == 1) {
                $punctualRecords++;
            }
        }

        return $totalRecords > 0 ? round(($punctualRecords / $totalRecords) * 100, 1) : 0;
    }

    private function getMonthlyAttendanceStats($attendance, $tipoSeleccionado = null)
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate = date('Y-m-d');

            // Primero intentar obtener datos de attendance_calculations (datos avanzados)
            $sqlAdvanced = "SELECT
                                COUNT(*) as total_records,
                                SUM(CASE WHEN ac.is_late = 0 AND ac.is_absent = 0 THEN 1 ELSE 0 END) as total_on_time,
                                SUM(CASE WHEN ac.is_late = 1 THEN 1 ELSE 0 END) as total_late,
                                SUM(CASE WHEN ac.is_absent = 1 THEN 1 ELSE 0 END) as total_absent,
                                COALESCE(AVG(ac.punctuality_score), 0) as avg_punctuality_score
                            FROM attendance_calculations ac
                            INNER JOIN employees e ON ac.employee_id = e.id
                            WHERE ac.date BETWEEN :start_date AND :end_date";

            if ($tipoSeleccionado) {
                $sqlAdvanced .= " AND FIND_IN_SET(:tipo_planilla, e.tipo_planilla_id)";
            }

            $stmt = $db->prepare($sqlAdvanced);
            $stmt->bindValue(':start_date', $startDate);
            $stmt->bindValue(':end_date', $endDate);

            if ($tipoSeleccionado) {
                $stmt->bindValue(':tipo_planilla', $tipoSeleccionado, \PDO::PARAM_INT);
            }

            $stmt->execute();
            $advancedResult = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Si hay datos avanzados, usarlos
            if ($advancedResult && $advancedResult['total_records'] > 0) {
                $totalPresent = (int)($advancedResult['total_on_time'] ?? 0) + (int)($advancedResult['total_late'] ?? 0);
                $totalLate = (int)($advancedResult['total_late'] ?? 0);
                $totalAbsent = (int)($advancedResult['total_absent'] ?? 0);
                $averageDaily = $totalPresent > 0 ? round($totalPresent / 30, 1) : 0;
                $punctualityPercentage = $totalPresent > 0 ? round((($totalPresent - $totalLate) / $totalPresent) * 100, 1) : 0;

                return [
                    'average_daily' => $averageDaily,
                    'total_present' => $totalPresent,
                    'total_late' => $totalLate,
                    'total_absent' => $totalAbsent,
                    'punctuality_percentage' => $punctualityPercentage,
                    'avg_punctuality_score' => round((float)($advancedResult['avg_punctuality_score'] ?? 0), 1)
                ];
            } else {
                // Fallback: usar datos básicos de la tabla attendance
                $monthlyAttendance = $attendance->getAttendanceByDateRange($startDate, $endDate, null, $tipoSeleccionado);

                $totalPresent = 0;
                $totalLate = 0;

                foreach ($monthlyAttendance as $record) {
                    if (isset($record['time_in']) && $record['time_in']) {
                        $totalPresent++;
                        if (isset($record['status']) && $record['status'] == 0) {
                            $totalLate++;
                        }
                    }
                }

                $averageDaily = $totalPresent > 0 ? round($totalPresent / 30, 1) : 0;
                $punctualityPercentage = $totalPresent > 0 ? round((($totalPresent - $totalLate) / $totalPresent) * 100, 1) : 0;

                return [
                    'average_daily' => $averageDaily,
                    'total_present' => $totalPresent,
                    'total_late' => $totalLate,
                    'total_absent' => 0, // No podemos calcular ausencias con datos básicos
                    'punctuality_percentage' => $punctualityPercentage,
                    'avg_punctuality_score' => 0 // No disponible en datos básicos
                ];
            }

        } catch (\Exception $e) {
            error_log("Error getting monthly attendance stats: " . $e->getMessage());
            return [
                'average_daily' => 0,
                'total_present' => 0,
                'total_late' => 0,
                'total_absent' => 0,
                'punctuality_percentage' => 0,
                'avg_punctuality_score' => 0
            ];
        }
    }

    private function getAttendanceChartData($attendance, $tipoSeleccionado = null)
    {
        $chartData = [];

        try {
            $db = \App\Core\Database::getInstance()->getConnection();

            // Obtener datos de los últimos 30 días
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));

                // Primero intentar obtener datos de attendance_calculations (datos avanzados)
                $sqlAdvanced = "SELECT
                                    COUNT(DISTINCT ac.employee_id) as total_employees,
                                    SUM(CASE WHEN ac.is_late = 0 AND ac.is_absent = 0 THEN 1 ELSE 0 END) as present_on_time,
                                    SUM(CASE WHEN ac.is_late = 1 THEN 1 ELSE 0 END) as late,
                                    SUM(CASE WHEN ac.is_absent = 1 THEN 1 ELSE 0 END) as absent,
                                    COALESCE(SUM(ac.overtime_hours), 0) as total_overtime,
                                    COALESCE(AVG(ac.punctuality_score), 0) as avg_punctuality
                                FROM attendance_calculations ac
                                INNER JOIN employees e ON ac.employee_id = e.id
                                WHERE ac.date = :date";

                if ($tipoSeleccionado) {
                    $sqlAdvanced .= " AND FIND_IN_SET(:tipo_planilla, e.tipo_planilla_id)";
                }

                $stmt = $db->prepare($sqlAdvanced);
                $stmt->bindValue(':date', $date);

                if ($tipoSeleccionado) {
                    $stmt->bindValue(':tipo_planilla', $tipoSeleccionado, \PDO::PARAM_INT);
                }

                $stmt->execute();
                $advancedResult = $stmt->fetch(\PDO::FETCH_ASSOC);

                // Si hay datos avanzados, usarlos
                if ($advancedResult && $advancedResult['total_employees'] > 0) {
                    $chartData[] = [
                        'date' => $date,
                        'formatted_date' => date('d/m', strtotime($date)),
                        'present' => (int)($advancedResult['present_on_time'] ?? 0) + (int)($advancedResult['late'] ?? 0),
                        'on_time' => (int)($advancedResult['present_on_time'] ?? 0),
                        'late' => (int)($advancedResult['late'] ?? 0),
                        'absent' => (int)($advancedResult['absent'] ?? 0),
                        'total' => (int)($advancedResult['total_employees'] ?? 0),
                        'overtime_hours' => round((float)($advancedResult['total_overtime'] ?? 0), 2),
                        'avg_punctuality' => round((float)($advancedResult['avg_punctuality'] ?? 0), 1)
                    ];
                } else {
                    // Fallback: usar datos básicos de la tabla attendance
                    $dayAttendance = $attendance->getAttendanceByDateRange($date, $date, null, $tipoSeleccionado);

                    if (!is_array($dayAttendance)) {
                        $dayAttendance = [];
                    }

                    $present = 0;
                    $onTime = 0;
                    $late = 0;

                    foreach ($dayAttendance as $record) {
                        if (isset($record['time_in']) && $record['time_in']) {
                            $present++;
                            if (isset($record['status']) && $record['status'] == 1) {
                                $onTime++;
                            } else {
                                $late++;
                            }
                        }
                    }

                    $chartData[] = [
                        'date' => $date,
                        'formatted_date' => date('d/m', strtotime($date)),
                        'present' => $present,
                        'on_time' => $onTime,
                        'late' => $late,
                        'absent' => 0, // No podemos calcular ausencias con datos básicos
                        'total' => count($dayAttendance),
                        'overtime_hours' => 0,
                        'avg_punctuality' => 0
                    ];
                }
            }

        } catch (\Exception $e) {
            error_log("Error generating attendance chart data: " . $e->getMessage());
            // Devolver datos de ejemplo si falla
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $chartData[] = [
                    'date' => $date,
                    'formatted_date' => date('d/m', strtotime($date)),
                    'present' => 0,
                    'on_time' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'total' => 0,
                    'overtime_hours' => 0,
                    'avg_punctuality' => 0
                ];
            }
        }

        return $chartData;
    }

    /**
     * Obtener tipos de planilla para filtro
     */
    private function getTiposPlanilla()
    {
        try {
            $db = \App\Core\Database::getInstance();
            $sql = "SELECT id, descripcion FROM tipos_planilla ORDER BY descripcion ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error obteniendo tipos de planilla: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener datos de acumulados para dashboard
     */
    private function getAcumuladosData($tipoSeleccionado = null)
    {
        try {
            $acumuladoModel = $this->model('Acumulado');
            $currentYear = date('Y');

            // Obtener resumen por conceptos (en lugar de tipos)
            $tiposAcumulados = $acumuladoModel->getAcumuladosByConceptoAndYear($currentYear, $tipoSeleccionado);

            // Obtener empleados con acumulados
            $empleadosAcumulados = $acumuladoModel->getEmployeesWithAcumulados($currentYear, $tipoSeleccionado);

            // Obtener años disponibles
            $yearsData = $acumuladoModel->getAvailableYears();
            $availableYears = [];
            foreach ($yearsData as $yearData) {
                if (!empty($yearData['ano'])) {
                    $availableYears[] = $yearData['ano'];
                }
            }
            if (!in_array('todos', $availableYears)) {
                array_unshift($availableYears, 'todos');
            }

            return [
                'year' => $currentYear,
                'tipos_acumulados' => $tiposAcumulados,
                'empleados_acumulados' => $empleadosAcumulados,
                'available_years' => $availableYears,
                'selected_year' => $currentYear
            ];
        } catch (\Exception $e) {
            error_log("Error obteniendo datos de acumulados: " . $e->getMessage());
            return [
                'year' => date('Y'),
                'tipos_acumulados' => [],
                'empleados_acumulados' => [],
                'available_years' => [date('Y')],
                'selected_year' => date('Y')
            ];
        }
    }

    public function logout()
    {
        // Log logout before clearing session
        if (isset($_SESSION['admin']) && isset($_SESSION['admin_username'])) {
            ActivityLogger::logLogout($_SESSION['admin'], $_SESSION['admin_username']);
        }

        // Limpiar sesión completa
        session_unset();
        session_destroy();

        // Iniciar nueva sesión para mensaje de éxito
        session_start();
        $_SESSION['success'] = 'Sesión cerrada con éxito';
        $_SESSION['clear_storage'] = true;  // Flag para limpiar storage en el frontend

        // Redirigir con parámetro logout=1 para trigger limpieza de storage
        $this->redirect('/panel?logout=1');
    }

    protected function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }

    /**
     * Obtener alertas activas del sistema de asistencias
     */
    private function getActiveAlerts($tipoSeleccionado = null, $limit = 5)
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();

            $sql = "SELECT aa.*, e.firstname, e.lastname, e.employee_id
                    FROM attendance_alerts aa
                    INNER JOIN employees e ON aa.employee_id = e.id
                    WHERE aa.status = 'PENDING'";

            if ($tipoSeleccionado) {
                $sql .= " AND FIND_IN_SET(:tipo_planilla, e.tipo_planilla_id)";
            }

            $sql .= " ORDER BY
                      CASE aa.severity
                        WHEN 'CRITICAL' THEN 1
                        WHEN 'WARNING' THEN 2
                        WHEN 'INFO' THEN 3
                      END,
                      aa.created_at DESC
                    LIMIT :limit";

            $stmt = $db->prepare($sql);

            if ($tipoSeleccionado) {
                $stmt->bindValue(':tipo_planilla', $tipoSeleccionado, \PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error obteniendo alertas activas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de alertas por severidad
     */
    private function getAlertStats($tipoSeleccionado = null)
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();

            $sql = "SELECT
                        aa.severity,
                        COUNT(*) as count
                    FROM attendance_alerts aa
                    INNER JOIN employees e ON aa.employee_id = e.id
                    WHERE aa.status = 'PENDING'";

            if ($tipoSeleccionado) {
                $sql .= " AND FIND_IN_SET(:tipo_planilla, e.tipo_planilla_id)";
            }

            $sql .= " GROUP BY aa.severity";

            $stmt = $db->prepare($sql);

            if ($tipoSeleccionado) {
                $stmt->bindValue(':tipo_planilla', $tipoSeleccionado, \PDO::PARAM_INT);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Organizar por severidad
            $stats = [
                'CRITICAL' => 0,
                'WARNING' => 0,
                'INFO' => 0,
                'total' => 0
            ];

            foreach ($results as $row) {
                $stats[$row['severity']] = (int)$row['count'];
                $stats['total'] += (int)$row['count'];
            }

            return $stats;
        } catch (\Exception $e) {
            error_log("Error obteniendo estadísticas de alertas: " . $e->getMessage());
            return ['CRITICAL' => 0, 'WARNING' => 0, 'INFO' => 0, 'total' => 0];
        }
    }

    /**
     * Obtener estadísticas de asistencias del mes actual
     */
    private function getCurrentMonthAttendanceStats($tipoSeleccionado = null)
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-d');

            $sql = "SELECT
                        COUNT(DISTINCT ac.employee_id) as employees_with_data,
                        COALESCE(SUM(ac.overtime_25_hours), 0) as total_overtime_25,
                        COALESCE(SUM(ac.overtime_50_hours), 0) as total_overtime_50,
                        COALESCE(SUM(ac.tardiness_minutes), 0) as total_tardiness_minutes,
                        COALESCE(AVG(ac.punctuality_score), 0) as avg_punctuality_score,
                        COALESCE(SUM(CASE WHEN ac.is_perfect_attendance = 1 THEN 1 ELSE 0 END), 0) as perfect_attendance_days
                    FROM attendance_calculations ac
                    INNER JOIN employees e ON ac.employee_id = e.id
                    WHERE ac.date BETWEEN :start_date AND :end_date";

            if ($tipoSeleccionado) {
                $sql .= " AND FIND_IN_SET(:tipo_planilla, e.tipo_planilla_id)";
            }

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':start_date', $startDate);
            $stmt->bindValue(':end_date', $endDate);

            if ($tipoSeleccionado) {
                $stmt->bindValue(':tipo_planilla', $tipoSeleccionado, \PDO::PARAM_INT);
            }

            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    'total_overtime_hours' => round((float)$result['total_overtime_25'] + (float)$result['total_overtime_50'], 2),
                    'overtime_25' => round((float)$result['total_overtime_25'], 2),
                    'overtime_50' => round((float)$result['total_overtime_50'], 2),
                    'total_tardiness_hours' => round((float)$result['total_tardiness_minutes'] / 60, 2),
                    'avg_punctuality' => round((float)$result['avg_punctuality_score'], 1),
                    'perfect_days' => (int)$result['perfect_attendance_days']
                ];
            }

            return [
                'total_overtime_hours' => 0,
                'overtime_25' => 0,
                'overtime_50' => 0,
                'total_tardiness_hours' => 0,
                'avg_punctuality' => 0,
                'perfect_days' => 0
            ];
        } catch (\Exception $e) {
            error_log("Error obteniendo estadísticas de asistencias del mes: " . $e->getMessage());
            return [
                'total_overtime_hours' => 0,
                'overtime_25' => 0,
                'overtime_50' => 0,
                'total_tardiness_hours' => 0,
                'avg_punctuality' => 0,
                'perfect_days' => 0
            ];
        }
    }

    /**
     * Obtener estadísticas de ausencias del mes
     */
    private function getCurrentMonthAbsenceStats($tipoSeleccionado = null)
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-d');

            $sql = "SELECT
                        COUNT(*) as total_absences,
                        SUM(CASE WHEN aal.status = 'UNJUSTIFIED' THEN 1 ELSE 0 END) as unjustified,
                        SUM(CASE WHEN aal.status = 'JUSTIFIED' THEN 1 ELSE 0 END) as justified,
                        SUM(CASE WHEN aal.status = 'PENDING' THEN 1 ELSE 0 END) as pending
                    FROM attendance_absence_log aal
                    INNER JOIN employees e ON aal.employee_id = e.id
                    WHERE aal.absence_date BETWEEN :start_date AND :end_date";

            if ($tipoSeleccionado) {
                $sql .= " AND FIND_IN_SET(:tipo_planilla, e.tipo_planilla_id)";
            }

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':start_date', $startDate);
            $stmt->bindValue(':end_date', $endDate);

            if ($tipoSeleccionado) {
                $stmt->bindValue(':tipo_planilla', $tipoSeleccionado, \PDO::PARAM_INT);
            }

            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    'total' => (int)$result['total_absences'],
                    'unjustified' => (int)$result['unjustified'],
                    'justified' => (int)$result['justified'],
                    'pending' => (int)$result['pending']
                ];
            }

            return ['total' => 0, 'unjustified' => 0, 'justified' => 0, 'pending' => 0];
        } catch (\Exception $e) {
            error_log("Error obteniendo estadísticas de ausencias: " . $e->getMessage());
            return ['total' => 0, 'unjustified' => 0, 'justified' => 0, 'pending' => 0];
        }
    }

    /**
     * Obtener top empleados con más tardanzas del mes
     */
    private function getTopTardinessEmployees($tipoSeleccionado = null, $limit = 5)
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-d');

            $sql = "SELECT
                        e.id,
                        e.employee_id,
                        e.firstname,
                        e.lastname,
                        COUNT(*) as tardiness_count,
                        SUM(ac.tardiness_minutes) as total_minutes
                    FROM attendance_calculations ac
                    INNER JOIN employees e ON ac.employee_id = e.id
                    WHERE ac.date BETWEEN :start_date AND :end_date
                      AND ac.is_late = 1";

            if ($tipoSeleccionado) {
                $sql .= " AND FIND_IN_SET(:tipo_planilla, e.tipo_planilla_id)";
            }

            $sql .= " GROUP BY e.id, e.employee_id, e.firstname, e.lastname
                      ORDER BY tardiness_count DESC, total_minutes DESC
                      LIMIT :limit";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':start_date', $startDate);
            $stmt->bindValue(':end_date', $endDate);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);

            if ($tipoSeleccionado) {
                $stmt->bindValue(':tipo_planilla', $tipoSeleccionado, \PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error obteniendo top empleados con tardanzas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Página de acceso denegado con módulos permitidos
     */
    public function access()
    {
        $this->requireAuth();

        // Obtener permisos del usuario actual
        $permissions = \App\Middleware\PermissionMiddleware::getUserPermissions();

        // Obtener información completa de menús desde BD
        $menu_items = $this->getMenuItems();

        $data = [
            'title' => 'Acceso Denegado',
            'page_title' => 'Acceso Denegado',
            'permissions' => $permissions,
            'menu_items' => $menu_items
        ];

        $this->view('admin/access', $data);
    }

    /**
     * Obtener información completa de los items del menú
     */
    private function getMenuItems()
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();

            $sql = "SELECT id, name, url, icon, description, display_order, status
                    FROM menu_items
                    WHERE status = 1
                    ORDER BY display_order ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute();
            $menuItems = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Indexar por URL para fácil acceso
            $indexedMenu = [];
            foreach ($menuItems as $item) {
                $indexedMenu[$item['url']] = $item;
            }

            return $indexedMenu;
        } catch (\Exception $e) {
            error_log("Error obteniendo menu items: " . $e->getMessage());
            return [];
        }
    }
}