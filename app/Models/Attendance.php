<?php

namespace App\Models;

use App\Core\Model;
use DateTime;

class Attendance extends Model
{
    public $table = 'attendance';
    public $fillable = [
        'employee_id', 'date', 'time_in', 'time_out', 'num_hr', 'status'
    ];

    public function timeIn($employeeId)
    {
        $dateNow = date('Y-m-d');
        
        $existingAttendance = $this->getTodayAttendance($employeeId);
        if ($existingAttendance && $existingAttendance['time_in']) {
            return [
                'error' => true,
                'message' => 'Ya registró entrada para hoy'
            ];
        }

        $employee = new Employee();
        $employeeData = $employee->find($employeeId);
        $schedule = $employee->getSchedule($employeeId);

        $timeNow = date('H:i:s');
        $status = ($timeNow > $schedule['time_in']) ? 0 : 1; // 0 = tarde, 1 = a tiempo

        $attendanceData = [
            'employee_id' => $employeeId,
            'date' => $dateNow,
            'time_in' => date('Y-m-d H:i:s'),
            'time_out' => null,
            'num_hr' => 0,
            'status' => $status
        ];

        try {
            $this->create($attendanceData);
            return [
                'error' => false,
                'message' => 'Entrada registrada: ' . $employee->getFullName($employeeData)
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Error al registrar entrada'
            ];
        }
    }

    public function timeOut($employeeId)
    {
        $dateNow = date('Y-m-d');
        
        $attendance = $this->getTodayAttendance($employeeId);
        if (!$attendance) {
            return [
                'error' => true,
                'message' => 'No puede registrar salida. No hay entrada registrada.'
            ];
        }

        if ($attendance['time_out'] && $attendance['time_out'] !== '00:00:00') {
            return [
                'error' => true,
                'message' => 'Ya registró salida para hoy'
            ];
        }

        $employee = new Employee();
        $employeeData = $employee->find($employeeId);
        $schedule = $employee->getSchedule($employeeId);

        try {
            $timeOut = date('Y-m-d H:i:s');
            $this->update($attendance['id'], ['time_out' => $timeOut]);

            $workedHours = $this->calculateWorkedHours($attendance['time_in'], $timeOut, $schedule);
            $this->update($attendance['id'], ['num_hr' => $workedHours]);

            return [
                'error' => false,
                'message' => 'Salida registrada: ' . $employee->getFullName($employeeData)
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Error al registrar salida'
            ];
        }
    }

    public function getTodayAttendance($employeeId)
    {
        $dateNow = date('Y-m-d');
        $sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        return $this->db->find($sql, [$employeeId, $dateNow]);
    }

    private function calculateWorkedHours($timeIn, $timeOut, $schedule)
    {
        $startTime = new DateTime($timeIn);
        $endTime = new DateTime($timeOut);
        
        $scheduleStart = new DateTime($schedule['time_in']);
        $scheduleEnd = new DateTime($schedule['time_out']);

        if ($startTime < $scheduleStart) {
            $startTime = $scheduleStart;
        }
        
        if ($endTime > $scheduleEnd) {
            $endTime = $scheduleEnd;
        }

        $interval = $startTime->diff($endTime);
        $hours = $interval->h + ($interval->i / 60);

        if ($hours > 4) {
            $hours = $hours - 1; // Descontar hora de almuerzo
        }

        return round($hours, 2);
    }

    public function getAttendanceByDateRange($startDate, $endDate, $employeeId = null)
    {
        $sql = "SELECT a.*, e.firstname, e.lastname, e.employee_id
                FROM attendance a
                LEFT JOIN employees e ON a.employee_id = e.id
                WHERE a.date BETWEEN ? AND ?";
        $params = [$startDate, $endDate];

        if ($employeeId) {
            $sql .= " AND a.employee_id = ?";
            $params[] = $employeeId;
        }

        $sql .= " ORDER BY a.date DESC, e.lastname, e.firstname";
        
        return $this->db->findAll($sql, $params);
    }

    public function validateAttendanceData($data)
    {
        $rules = [
            'employee_id' => 'required',
            'date' => 'required',
            'time_in' => 'required'
        ];

        $errors = $this->validate($data, $rules);

        // Validar que time_out sea posterior a time_in si ambos están presentes
        if (!empty($data['time_in']) && !empty($data['time_out'])) {
            if (strtotime($data['time_in']) >= strtotime($data['time_out'])) {
                $errors['time_out'] = 'La hora de salida debe ser posterior a la hora de entrada';
            }
        }

        // Verificar si ya existe un registro para el empleado en esa fecha
        if (!empty($data['employee_id']) && !empty($data['date'])) {
            $existing = $this->db->find(
                "SELECT id FROM attendance WHERE employee_id = ? AND date = ?",
                [$data['employee_id'], $data['date']]
            );
            if ($existing) {
                $errors['date'] = 'Ya existe un registro de asistencia para este empleado en esta fecha';
            }
        }

        return $errors;
    }

    public function getMonthlyAttendance($year, $month, $employeeId = null)
    {
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        return $this->getAttendanceByDateRange($startDate, $endDate, $employeeId);
    }
}