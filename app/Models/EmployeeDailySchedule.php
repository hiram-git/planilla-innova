<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class EmployeeDailySchedule extends Model
{
    public $table = 'employee_daily_schedules';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get schedules for an employee within a date range
     */
    public function getForRange($employeeId, $startDate, $endDate)
    {
        $sql = "SELECT eds.*, s.codigo, s.nombre, s.time_in, s.time_out 
                FROM {$this->table} eds
                JOIN schedules s ON eds.schedule_id = s.id
                WHERE eds.employee_id = ? AND eds.date BETWEEN ? AND ?";
        
        return $this->db->findAll($sql, [$employeeId, $startDate, $endDate]);
    }

    /**
     * Save or update a daily schedule
     */
    public function saveDay($employeeId, $date, $scheduleId)
    {
        $sql = "INSERT INTO {$this->table} (employee_id, date, schedule_id) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE schedule_id = ?";
        
        return $this->db->query($sql, [$employeeId, $date, $scheduleId, $scheduleId]);
    }

    /**
     * Delete a daily schedule (revert to default)
     */
    public function deleteDay($employeeId, $date)
    {
        $sql = "DELETE FROM {$this->table} WHERE employee_id = ? AND date = ?";
        return $this->db->query($sql, [$employeeId, $date]);
    }
}
