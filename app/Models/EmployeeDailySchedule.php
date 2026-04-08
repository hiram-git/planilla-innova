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
     * Obtener el override de horario personal para un empleado en una fecha específica.
     * Retorna el registro completo del horario (con tolerancias y almuerzo) o null.
     */
    public function getForDate($employeeId, $date)
    {
        $sql = "SELECT eds.id, eds.employee_id, eds.date, eds.schedule_id,
                       s.codigo, s.nombre,
                       s.time_in, s.time_out,
                       s.salida_almuerzo, s.entrada_almuerzo,
                       s.time_in_tolerance_before, s.time_in_tolerance_after,
                       s.time_out_tolerance_before, s.time_out_tolerance_after,
                       s.lunch_out_tolerance_before, s.lunch_out_tolerance_after,
                       s.lunch_in_tolerance_before, s.lunch_in_tolerance_after
                FROM {$this->table} eds
                JOIN schedules s ON eds.schedule_id = s.id
                WHERE eds.employee_id = ? AND eds.date = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $date]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtener IDs de empleados que tienen override personal para una fecha específica.
     * Usado para determinar si un día no laboral debe procesarse igualmente.
     */
    public function getEmployeeIdsWithOverrideForDate($date): array
    {
        $sql = "SELECT employee_id FROM {$this->table} WHERE date = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$date]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($r) => (int)$r['employee_id'], $rows);
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
