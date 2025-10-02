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
        AuthMiddleware::redirectIfAuthenticated();

        $data = [
            'title' => 'Administración - Login',
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/login', $data);
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Si es GET, mostrar el formulario de login
            AuthMiddleware::redirectIfAuthenticated();
            
            $data = [
                'title' => 'Administración - Login',
                'csrf_token' => AuthMiddleware::generateCSRF()
            ];

            $this->view('admin/login', $data);
            return;
        }

        AuthMiddleware::rateLimit(5, 300);
        AuthMiddleware::validateCSRF();

        $username = Security::sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

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
            'acumulados_data' => $acumuladosData
        ];

        $this->view('admin/dashboard', $data);
    }

    private function getActiveEmployees($tipoSeleccionado = null)
    {
        $employee = $this->model('Employee');
        $whereClause = "created_on >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

        if ($tipoSeleccionado) {
            $whereClause .= " AND tipo_planilla_id = " . (int)$tipoSeleccionado;
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
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');

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
            'punctuality_percentage' => $punctualityPercentage
        ];
    }

    private function getAttendanceChartData($attendance, $tipoSeleccionado = null)
    {
        $chartData = [];

        try {
            // Obtener datos de los últimos 30 días
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $dayAttendance = $attendance->getAttendanceByDateRange($date, $date, null, $tipoSeleccionado);
                
                // Asegurar que $dayAttendance sea un array
                if (!is_array($dayAttendance)) {
                    $dayAttendance = [];
                }
                
                $present = 0;
                $late = 0;
                
                foreach ($dayAttendance as $record) {
                    if (isset($record['time_in']) && $record['time_in']) {
                        $present++;
                        if (isset($record['status']) && $record['status'] == 0) {
                            $late++;
                        }
                    }
                }
                
                $chartData[] = [
                    'date' => $date,
                    'formatted_date' => date('d/m', strtotime($date)),
                    'present' => (int)$present,
                    'late' => (int)$late,
                    'total' => count($dayAttendance)
                ];
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
                    'late' => 0,
                    'total' => 0
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

            // Obtener resumen por tipos de acumulados
            $tiposAcumulados = $acumuladoModel->getAcumuladosByTipoAndYear($currentYear, $tipoSeleccionado);

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
        
        $this->redirect('/panel');
    }

    protected function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }
}