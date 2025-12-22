<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\UrlHelper;
use App\Core\Security;
use App\Middleware\AuthMiddleware;
use App\Models\BusinessCalendar;

class PersonalScheduleController extends Controller
{
    public function __construct()
    {
        AuthMiddleware::requireAuth();
    }

    /**
     * Main view for Personal Calendar
     */
    public function index($employeeId)
    {
        $employeeModel = $this->model('Employee');
        $employee = $employeeModel->find($employeeId);

        if (!$employee) {
            $_SESSION['error'] = 'Empleado no encontrado';
            $this->redirect(UrlHelper::employee());
        }

        $scheduleModel = $this->model('Schedule');
        $schedules = $scheduleModel->db->findAll("SELECT * FROM schedules WHERE activo = 1 ORDER BY codigo ASC");

        $data = [
            'title' => 'Horario Personal',
            'page_title' => 'Horario Personal: ' . $employee['firstname'] . ' ' . $employee['lastname'],
            'employee' => $employee,
            'schedules' => $schedules,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/employees/personal_calendar', $data);
    }

    /**
     * API: Get events for calendar
     */
    public function events($employeeId)
    {
        header('Content-Type: application/json');

        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');

        $events = [];

        // 1. Business calendar as base layer (background events)
        $businessCalendar = $this->model('BusinessCalendar');
        $dayTypeColors = BusinessCalendar::getDayTypeColors();

        $calendarDays = $businessCalendar->db->findAll(
            "SELECT date_value, day_type, status, description 
             FROM business_calendar 
             WHERE date_value BETWEEN ? AND ?",
            [$start, $end]
        );

        $businessByDate = [];
        foreach ($calendarDays as $day) {
            $businessByDate[$day['date_value']] = $day;
            $color = $dayTypeColors[$day['day_type']] ?? '#6c757d';

            $events[] = [
                'title' => $day['description'] ?: $day['day_type'],
                'start' => $day['date_value'],
                'allDay' => true,
                'display' => 'background',
                'color' => $color,
                'extendedProps' => [
                    'source' => 'business',
                    'day_type' => $day['day_type'],
                    'status' => $day['status'] ?? null,
                    'description' => $day['description'] ?? null
                ]
            ];
        }

        // 2. Get Employee Default Schedule
        $employeeModel = $this->model('Employee');
        $employee = $employeeModel->find($employeeId);
        $scheduleModel = $this->model('Schedule');
        $defaultSchedule = $scheduleModel->find($employee['schedule_id']);

        // 3. Get Personal Schedule Overrides
        $dailyScheduleModel = $this->model('EmployeeDailySchedule');
        $overrides = $dailyScheduleModel->getForRange($employeeId, $start, $end);
        
        // Map overrides by date
        $overridesByDate = [];
        foreach ($overrides as $override) {
            $overridesByDate[$override['date']] = $override;
        }

        // Generate day-by-day events
        $currentDate = new \DateTime($start);
        $endDate = new \DateTime($end);

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->format('N'); // 1-7 (Mon-Sun)
            $dayInfo = $businessByDate[$dateStr] ?? null;
            $dayType = $dayInfo['day_type'] ?? null;
            $isNonWorking = $dayType && in_array($dayType, ['FERIADO', 'NO_LABORAL', 'DUELO_NACIONAL']);
            $isWorkingDay = $dayType
                ? in_array($dayType, ['LABORAL', 'ESPECIAL'])
                : $dayOfWeek <= 5; // Fallback to Mon-Fri when no business calendar entry
            
            // Check for overrides first
            if (isset($overridesByDate[$dateStr])) {
                $schedule = $overridesByDate[$dateStr];
                $events[] = [
                    'title' => $schedule['codigo'] . ' (' . date('H:i', strtotime($schedule['time_in'])) . '-' . date('H:i', strtotime($schedule['time_out'])) . ')',
                    'start' => $dateStr . 'T' . $schedule['time_in'],
                    'end' => $dateStr . 'T' . $schedule['time_out'],
                    'color' => '#28a745', // Green for personal assignment
                    'schedule_id' => $schedule['schedule_id'],
                    'extendedProps' => [
                        'source' => 'personal',
                        'is_default' => false,
                        'schedule_id' => $schedule['schedule_id'],
                        'day_type' => $dayType,
                        'business_status' => $dayInfo['status'] ?? null
                    ]
                ];
            } else {
                // Default Schedule only if the day is considered working
                if ($defaultSchedule && $isWorkingDay && !$isNonWorking) {
                    $events[] = [
                        'title' => $defaultSchedule['codigo'] . ' (Std)',
                        'start' => $dateStr . 'T' . $defaultSchedule['time_in'],
                        'end' => $dateStr . 'T' . $defaultSchedule['time_out'],
                        'color' => '#007bff', // Blue for default
                        'schedule_id' => $defaultSchedule['id'],
                        'is_default' => true,
                        'extendedProps' => [
                            'source' => 'default',
                            'is_default' => true,
                            'schedule_id' => $defaultSchedule['id'],
                            'day_type' => $dayType,
                            'business_status' => $dayInfo['status'] ?? null
                        ]
                    ];
                }
            }
            
            $currentDate->modify('+1 day');
        }

        echo json_encode($events);
        exit;
    }

    /**
     * API: Save specific day schedule
     */
    public function saveDay()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['employee_id']) || empty($data['date']) || empty($data['schedule_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            exit;
        }

        $dailyScheduleModel = $this->model('EmployeeDailySchedule');
        $success = $dailyScheduleModel->saveDay($data['employee_id'], $data['date'], $data['schedule_id']);

        echo json_encode(['success' => $success]);
        exit;
    }
    
    /**
     * API: Delete specific day (revert to default)
     */
    public function deleteDay()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             echo json_encode(['success' => false, 'message' => 'Invalid method']);
             exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['employee_id']) || empty($data['date'])) {
             echo json_encode(['success' => false, 'message' => 'Missing data']);
             exit;
        }
        
        $dailyScheduleModel = $this->model('EmployeeDailySchedule');
        $dailyScheduleModel->deleteDay($data['employee_id'], $data['date']);
        
        echo json_encode(['success' => true]);
        exit;
    }
    /**
     * Initialize Range
     */
    public function initialize()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             echo json_encode(['success' => false, 'message' => 'Invalid method']);
             exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['employee_id']) || empty($data['start_date']) || empty($data['end_date']) || empty($data['schedule_id'])) {
             echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
             exit;
        }
        
        $dailyScheduleModel = $this->model('EmployeeDailySchedule');
        $start = new \DateTime($data['start_date']);
        $end = new \DateTime($data['end_date']);
        
        if ($start > $end) {
             echo json_encode(['success' => false, 'message' => 'La fecha de inicio no puede ser mayor a la fecha fin']);
             exit;
        }
        
        $count = 0;
        try {
            $db = \App\Core\Database::getInstance();
            $db->beginTransaction();
            
            while ($start <= $end) {
                // Apply setting for every day in range
                // Future improvement: Filter by days of week if passed
                $dateStr = $start->format('Y-m-d');
                $dailyScheduleModel->saveDay($data['employee_id'], $dateStr, $data['schedule_id']);
                $count++;
                $start->modify('+1 day');
            }
            
            $db->commit();
            echo json_encode(['success' => true, 'message' => "Se actualizaron $count días."]);
            
        } catch (\Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Import Schedules from CSV
     */
    public function import()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             echo json_encode(['success' => false, 'message' => 'Invalid method']);
             exit;
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
             echo json_encode(['success' => false, 'message' => 'Error en el archivo']);
             exit;
        }
        
        $file = $_FILES['file']['tmp_name'];
        $handle = fopen($file, "r");
        
        if ($handle === FALSE) {
             echo json_encode(['success' => false, 'message' => 'No se pudo abrir el archivo']);
             exit;
        }
        
        $employeeModel = $this->model('Employee');
        $scheduleModel = $this->model('Schedule');
        $dailyScheduleModel = $this->model('EmployeeDailySchedule');
        
        // Cache schedules and employees to avoid repetitive queries
        $schedules = $scheduleModel->findAll("SELECT id, codigo FROM schedules");
        $scheduleMap = []; // code -> id
        foreach ($schedules as $s) {
            $scheduleMap[strtoupper(trim($s['codigo']))] = $s['id'];
        }
        
        // We will look up employees by cedula dynamically or cache them if list is not huge.
        // Assuming reasonably sized employee base, let's look up individually or build a map if needed.
        // For efficiency, let's process row by row.
        
        $successCount = 0;
        $errors = [];
        $rowNum = 0;
        
        try {
            $db = \App\Core\Database::getInstance();
            $db->beginTransaction();
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowNum++;
                // Expects: Cedula, Date, ScheduleCode
                if (count($data) < 3) continue;
                
                $cedula = trim($data[0]);
                $date = trim($data[1]);
                $scheduleCode = strtoupper(trim($data[2]));
                
                // Header check (heuristic)
                if ($rowNum === 1 && !is_numeric(str_replace('-', '', $cedula)) && stripos($cedula, 'cedula') !== false) {
                    continue;
                }
                
                // Find Employee
                // Note: Employee model doesn't have findByCedula generic method in basic "Model", 
                // but we can query using find or custom query.
                // Assuming "cedula" column exists in employees table.
                $emp = $employeeModel->find("SELECT id FROM employees WHERE cedula = ? LIMIT 1", [$cedula]);
                
                if (!$emp) {
                    $errors[] = "Fila $rowNum: Empleado con cédula '$cedula' no encontrado";
                    continue;
                }
                
                // Find Schedule
                if (!isset($scheduleMap[$scheduleCode])) {
                    $errors[] = "Fila $rowNum: Horario '$scheduleCode' no existe";
                    continue;
                }
                
                $scheduleId = $scheduleMap[$scheduleCode];
                
                // Validate Date
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                     $errors[] = "Fila $rowNum: Fecha inválida '$date' (Formato YYYY-MM-DD)";
                     continue;
                }
                
                // Save
                $dailyScheduleModel->saveDay($emp['id'], $date, $scheduleId);
                $successCount++;
            }
            
            $db->commit();
            fclose($handle);
            
            if (count($errors) > 0) {
                 $msg = "Importado parcialmente. $successCount registros procesados. Errores: " . implode("; ", array_slice($errors, 0, 5));
                 if (count($errors) > 5) $msg .= "... (y más)";
                 echo json_encode(['success' => $successCount > 0, 'message' => $msg]);
            } else {
                 echo json_encode(['success' => true, 'message' => "Se importaron $successCount registros exitosamente."]);
            }
            
        } catch (\Exception $e) {
            $db->rollback();
            fclose($handle); // Ensure content handle is closed
            echo json_encode(['success' => false, 'message' => 'Error de excepción: ' . $e->getMessage()]);
        }
        
        exit;
    }
}
