<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Config;

class Home extends Controller
{
    public function __construct()
    {
        Config::load();
    }

    public function index()
    {
        $data = [
            'title' => 'Sistema de Marcaciones',
            'csrf_token' => $this->generateCSRF()
        ];
        
        $this->view('home/index', $data);
    }

    public function attendance()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        $this->validateCSRF();

        $employeeId = $_POST['employee'] ?? '';
        $status = $_POST['status'] ?? '';

        if (empty($employeeId) || empty($status)) {
            $this->json(['error' => true, 'message' => 'Datos incompletos']);
        }

        $employee = $this->model('Employee');
        $attendance = $this->model('Attendance');

        $employeeData = $employee->findByEmployeeId($employeeId);
        
        if (!$employeeData) {
            $this->json(['error' => true, 'message' => 'ID de empleado no encontrado']);
        }

        try {
            if ($status === 'in') {
                $result = $attendance->timeIn($employeeData['id']);
            } else {
                $result = $attendance->timeOut($employeeData['id']);
            }

            $this->json($result);
        } catch (\Exception $e) {
            $this->json(['error' => true, 'message' => 'Error del servidor']);
        }
    }
}