<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Middleware\AuthMiddleware;

class Attendance extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index()
    {
        $attendance = $this->model('Attendance');
        $employee = $this->model('Employee');
        
        $data = [
            'title' => 'Gestión de Asistencia',
            'page_title' => 'Asistencia',
            'attendances' => $attendance->getAttendanceByDateRange(
                date('Y-m-d', strtotime('-30 days')), 
                date('Y-m-d')
            ),
            'employees' => $employee->all(),
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/attendance/index', $data);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::attendance());
        }

        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);
        $attendance = $this->model('Attendance');

        $errors = $attendance->validateAttendanceData($data);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect(\App\Core\UrlHelper::attendance());
        }

        try {
            $attendance->create([
                'employee_id' => $data['employee_id'],
                'date' => $data['date'],
                'time_in' => $data['time_in'] ? date('H:i:s', strtotime($data['time_in'])) : null,
                'time_out' => $data['time_out'] ? date('H:i:s', strtotime($data['time_out'])) : null,
                'num_hr' => $this->calculateHours($data['time_in'], $data['time_out']),
                'status' => $this->calculateStatus($data['employee_id'], $data['time_in'])
            ]);
            
            $_SESSION['success'] = 'Registro de asistencia agregado exitosamente';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al agregar registro de asistencia';
        }

        $this->redirect(\App\Core\UrlHelper::attendance());
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::attendance());
        }

        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);
        $attendance = $this->model('Attendance');

        $attendanceData = $attendance->find($id);
        if (!$attendanceData) {
            $_SESSION['error'] = 'Registro de asistencia no encontrado';
            $this->redirect(\App\Core\UrlHelper::attendance());
        }

        $errors = $this->validateAttendanceDataForUpdate($data);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect(\App\Core\UrlHelper::attendance());
        }

        try {
            $attendance->update($id, [
                'date' => $data['edit_date'],
                'time_in' => $data['edit_time_in'] ? date('H:i:s', strtotime($data['edit_time_in'])) : null,
                'time_out' => $data['edit_time_out'] ? date('H:i:s', strtotime($data['edit_time_out'])) : null,
                'num_hr' => $this->calculateHours($data['edit_time_in'], $data['edit_time_out']),
                'status' => $this->calculateStatus($attendanceData['employee_id'], $data['edit_time_in'])
            ]);
            
            $_SESSION['success'] = 'Registro de asistencia actualizado exitosamente';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al actualizar registro de asistencia';
        }

        $this->redirect(\App\Core\UrlHelper::attendance());
    }

    public function delete($id)
    {
        AuthMiddleware::requireAuth();
        
        $attendance = $this->model('Attendance');
        
        try {
            $attendance->delete($id);
            $_SESSION['success'] = 'Registro de asistencia eliminado exitosamente';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al eliminar registro de asistencia';
        }

        $this->redirect(\App\Core\UrlHelper::attendance());
    }

    public function getRow()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            $this->json(['error' => 'ID requerido'], 400);
        }

        $attendance = $this->model('Attendance');
        $employee = $this->model('Employee');
        
        $sql = "SELECT a.*, e.firstname, e.lastname, e.employee_id 
                FROM attendance a 
                LEFT JOIN employees e ON a.employee_id = e.id 
                WHERE a.id = ?";
        $attendanceData = $attendance->db->find($sql, [$id]);

        if ($attendanceData) {
            $this->json([
                'id' => $attendanceData['id'],
                'employee_id' => $attendanceData['employee_id'],
                'date' => $attendanceData['date'],
                'time_in' => $attendanceData['time_in'],
                'time_out' => $attendanceData['time_out'],
                'firstname' => $attendanceData['firstname'],
                'lastname' => $attendanceData['lastname'],
                'employee_code' => $attendanceData['employee_id']
            ]);
        } else {
            $this->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    public function reports()
    {
        $attendance = $this->model('Attendance');
        $employee = $this->model('Employee');
        
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $employeeId = $_GET['employee_id'] ?? null;

        $data = [
            'title' => 'Reportes de Asistencia',
            'page_title' => 'Reportes de Asistencia',
            'attendances' => $attendance->getAttendanceByDateRange($startDate, $endDate, $employeeId),
            'employees' => $employee->all(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_employee' => $employeeId,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/attendance/reports', $data);
    }

    private function validateAttendanceDataForUpdate($data)
    {
        $rules = [
            'edit_date' => 'required',
            'edit_time_in' => 'required'
        ];

        $errors = Security::validateInput($data, $rules);

        // Validar que time_out sea posterior a time_in si ambos están presentes
        $timeIn = $data['edit_time_in'] ?? '';
        $timeOut = $data['edit_time_out'] ?? '';
        
        if (!empty($timeIn) && !empty($timeOut)) {
            if (strtotime($timeIn) >= strtotime($timeOut)) {
                $errors['edit_time_out'] = 'La hora de salida debe ser posterior a la hora de entrada';
            }
        }

        return $errors;
    }

    private function calculateHours($timeIn, $timeOut)
    {
        if (empty($timeIn) || empty($timeOut)) {
            return 0;
        }

        $start = new \DateTime($timeIn);
        $end = new \DateTime($timeOut);
        $interval = $start->diff($end);
        
        $hours = $interval->h + ($interval->i / 60);
        
        // Descontar hora de almuerzo si trabajó más de 4 horas
        if ($hours > 4) {
            $hours = $hours - 1;
        }

        return round($hours, 2);
    }

    private function calculateStatus($employeeId, $timeIn)
    {
        if (empty($timeIn)) {
            return 0;
        }

        $employee = $this->model('Employee');
        $schedule = $employee->getSchedule($employeeId);
        
        if (!$schedule) {
            return 1; // Si no hay horario, se considera a tiempo
        }

        $timeInHour = date('H:i:s', strtotime($timeIn));
        return ($timeInHour <= $schedule['time_in']) ? 1 : 0;
    }

    private function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }
}