<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;

class Timeclock extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Control de Asistencia'
        ];

        $this->view('timeclock/index', $data);
    }

    public function punch()
    {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['error' => true, 'message' => 'Método no permitido']);
                exit;
            }

            $employeeCode = $_POST['employee'] ?? '';
            $status = $_POST['status'] ?? '';

            if (empty($employeeCode)) {
                echo json_encode(['error' => true, 'message' => 'Employee ID not found']);
                exit;
            }

            $employee = $this->model('Employee');
            $attendance = $this->model('Attendance');

            // Buscar empleado por código usando consulta directa
            $employeeData = $this->findEmployeeByCode($employeeCode);
            if (!$employeeData) {
                echo json_encode(['error' => true, 'message' => 'Employee ID not found']);
                exit;
            }

            if ($status === 'in') {
                $result = $this->processTimeIn($employeeData['id'], $employeeData);
            } elseif ($status === 'out') {
                $result = $this->processTimeOut($employeeData['id'], $employeeData);
            } else {
                echo json_encode(['error' => true, 'message' => 'Estado de marcación no válido']);
                exit;
            }

            echo json_encode($result);
            exit;
            
        } catch (\Exception $e) {
            error_log("Error en Timeclock@punch: " . $e->getMessage());
            echo json_encode(['error' => true, 'message' => 'Error interno del servidor']);
            exit;
        }
    }
    
    private function findEmployeeByCode($employeeCode)
    {
        $employee = $this->model('Employee');
        $db = $employee->getDatabase();
        $connection = $db->getConnection();
        
        $sql = "SELECT * FROM employees WHERE employee_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$employeeCode]);
        
        return $stmt->fetch();
    }
    
    private function processTimeIn($employeeId, $employeeData)
    {
        $db = $this->model('Employee')->getDatabase();
        $connection = $db->getConnection();
        
        $dateNow = date('Y-m-d');
        
        // Verificar si ya tiene entrada
        $sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ? AND time_in IS NOT NULL";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$employeeId, $dateNow]);
        
        if ($stmt->rowCount() > 0) {
            return ['error' => true, 'message' => 'You have timed in for today'];
        }
        
        // Obtener horario del empleado
        $sql = "SELECT * FROM schedules WHERE id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$employeeData['schedule_id']]);
        $schedule = $stmt->fetch();
        
        $logNow = date('H:i:s');
        $logStatus = ($logNow > $schedule['time_in']) ? 0 : 1;
        
        // Insertar marcación de entrada
        $sql = "INSERT INTO attendance (employee_id, date, time_in, time_out, num_hr, status) VALUES (?, ?, NOW(), '', '0', ?)";
        $stmt = $connection->prepare($sql);
        
        if ($stmt->execute([$employeeId, $dateNow, $logStatus])) {
            return ['error' => false, 'message' => 'Time in: ' . $employeeData['firstname'] . ' ' . $employeeData['lastname']];
        } else {
            return ['error' => true, 'message' => 'Error al registrar entrada'];
        }
    }
    
    private function processTimeOut($employeeId, $employeeData)
    {
        $db = $this->model('Employee')->getDatabase();
        $connection = $db->getConnection();
        
        $dateNow = date('Y-m-d');
        
        // Buscar registro de asistencia del día
        $sql = "SELECT *, attendance.id AS uid FROM attendance 
                LEFT JOIN employees ON employees.id = attendance.employee_id 
                WHERE attendance.employee_id = ? AND date = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$employeeId, $dateNow]);
        $attendanceRecord = $stmt->fetch();
        
        if (!$attendanceRecord) {
            return ['error' => true, 'message' => 'Cannot Timeout. No time in.'];
        }
        
        if ($attendanceRecord['time_out'] != '00:00:00') {
            return ['error' => true, 'message' => 'You have timed out for today'];
        }
        
        // Actualizar salida
        $sql = "UPDATE attendance SET time_out = NOW() WHERE id = ?";
        $stmt = $connection->prepare($sql);
        
        if ($stmt->execute([$attendanceRecord['uid']])) {
            // Calcular horas trabajadas (simplificado como en el legacy)
            $this->calculateWorkedHours($attendanceRecord['uid'], $employeeId);
            
            return ['error' => false, 'message' => 'Time out: ' . $employeeData['firstname'] . ' ' . $employeeData['lastname']];
        } else {
            return ['error' => true, 'message' => 'Error al registrar salida'];
        }
    }
    
    private function calculateWorkedHours($attendanceId, $employeeId)
    {
        $db = $this->model('Employee')->getDatabase();
        $connection = $db->getConnection();
        
        // Obtener datos actualizados de asistencia
        $sql = "SELECT * FROM attendance WHERE id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$attendanceId]);
        $attendance = $stmt->fetch();
        
        // Obtener horario del empleado
        $sql = "SELECT * FROM employees LEFT JOIN schedules ON schedules.id = employees.schedule_id WHERE employees.id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$employeeId]);
        $schedule = $stmt->fetch();
        
        $timeIn = $attendance['time_in'];
        $timeOut = $attendance['time_out'];
        
        if ($schedule['time_in'] > $attendance['time_in']) {
            $timeIn = $schedule['time_in'];
        }
        
        if ($schedule['time_out'] < $attendance['time_out']) {
            $timeOut = $schedule['time_out'];
        }
        
        $timeInObj = new \DateTime($timeIn);
        $timeOutObj = new \DateTime($timeOut);
        $interval = $timeInObj->diff($timeOutObj);
        $hrs = $interval->format('%h');
        $mins = $interval->format('%i');
        $mins = $mins / 60;
        $totalHours = $hrs + $mins;
        
        if ($totalHours > 4) {
            $totalHours = $totalHours - 1; // Descontar almuerzo
        }
        
        // Actualizar horas trabajadas
        $sql = "UPDATE attendance SET num_hr = ? WHERE id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$totalHours, $attendanceId]);
    }

    public function status($employeeCode = null)
    {
        if (!$employeeCode) {
            $this->json(['error' => true, 'message' => 'ID de empleado requerido']);
        }

        $employee = $this->model('Employee');
        $attendance = $this->model('Attendance');

        $employeeData = $employee->findByEmployeeId($employeeCode);
        if (!$employeeData) {
            $this->json(['error' => true, 'message' => 'Empleado no encontrado']);
        }

        $todayAttendance = $attendance->getTodayAttendance($employeeData['id']);
        
        $data = [
            'employee' => $employee->getFullName($employeeData),
            'has_time_in' => $todayAttendance && $todayAttendance['time_in'] ? true : false,
            'has_time_out' => $todayAttendance && $todayAttendance['time_out'] && $todayAttendance['time_out'] !== '00:00:00' ? true : false,
            'time_in' => $todayAttendance['time_in'] ?? null,
            'time_out' => $todayAttendance['time_out'] ?? null,
            'status' => $todayAttendance['status'] ?? null
        ];

        $this->json($data);
    }
}