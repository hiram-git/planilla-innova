<?php

namespace App\Models;

use App\Core\ReferenceModel;

/**
 * Modelo para gestión de horarios
 * Hereda funcionalidad CRUD básica de ReferenceModel + métodos específicos de horarios
 */
class Schedule extends ReferenceModel
{
    public $table = 'schedules';
    
    /**
     * Campos permitidos para inserción masiva
     * Incluye campos base de ReferenceModel + campos específicos de horarios
     */
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo', 'time_in', 'time_out'];

    /**
     * Validación específica para horarios
     */
    public function validateReferenceData($data)
    {
        // Primero validar campos base de referencia
        $errors = parent::validateReferenceData($data);
        
        // Agregar validaciones específicas de horarios
        if (!isset($data['time_in']) || empty($data['time_in'])) {
            $errors['time_in'] = 'La hora de entrada es obligatoria';
        }
        
        if (!isset($data['time_out']) || empty($data['time_out'])) {
            $errors['time_out'] = 'La hora de salida es obligatoria';
        }
        
        if (empty($errors) && $data['time_in'] >= $data['time_out']) {
            $errors['time_out'] = 'La hora de salida debe ser posterior a la hora de entrada';
        }
        
        return $errors;
    }

    /**
     * Validación específica para actualización de horarios
     */
    public function validateReferenceUpdateData($data)
    {
        // Primero validar campos base de referencia
        $errors = parent::validateReferenceUpdateData($data);
        
        // Agregar validaciones específicas de horarios
        if (!isset($data['edit_time_in']) || empty($data['edit_time_in'])) {
            $errors['edit_time_in'] = 'La hora de entrada es obligatoria';
        }
        
        if (!isset($data['edit_time_out']) || empty($data['edit_time_out'])) {
            $errors['edit_time_out'] = 'La hora de salida es obligatoria';
        }
        
        if (empty($errors) && $data['edit_time_in'] >= $data['edit_time_out']) {
            $errors['edit_time_out'] = 'La hora de salida debe ser posterior a la hora de entrada';
        }
        
        return $errors;
    }

    /**
     * Verificar si el horario está en uso por empleados
     */
    public function delete($id)
    {
        // Verificar si está en uso antes de eliminar
        if ($this->hasEmployees($id)) {
            throw new \Exception("No se puede eliminar. El horario está en uso por empleados.");
        }
        
        return parent::delete($id);
    }

    /**
     * Verificar si el horario tiene empleados asociados
     */
    public function hasEmployees($scheduleId)
    {
        $sql = "SELECT COUNT(*) as count FROM employees WHERE schedule_id = ?";
        $result = $this->db->find($sql, [$scheduleId]);
        return $result['count'] > 0;
    }

    /**
     * Obtener horarios con contador de empleados y formato de display
     */
    public function getSchedulesWithEmployeeCount()
    {
        $sql = "SELECT s.*, COUNT(e.id) as employee_count,
                       CONCAT(TIME_FORMAT(s.time_in, '%h:%i %p'), ' - ', TIME_FORMAT(s.time_out, '%h:%i %p')) as schedule_display
                FROM schedules s
                LEFT JOIN employees e ON s.id = e.schedule_id
                GROUP BY s.id
                ORDER BY s.codigo ASC";
        return $this->db->findAll($sql);
    }

    /**
     * Obtener formato de display para un horario
     */
    public function getScheduleDisplay($schedule)
    {
        $timeIn = date('h:i A', strtotime($schedule['time_in']));
        $timeOut = date('h:i A', strtotime($schedule['time_out']));
        return "$timeIn - $timeOut";
    }

    /**
     * Buscar por código (método específico adicional)
     */
    public function findByCodigo($codigo)
    {
        return $this->first('codigo', $codigo);
    }

    /**
     * Generar el siguiente código correlativo
     */
    public function getNextCode()
    {
        $sql = "SELECT codigo FROM schedules WHERE codigo LIKE 'SCH-%' ORDER BY codigo DESC LIMIT 1";
        $result = $this->db->find($sql);
        
        if ($result && preg_match('/SCH-(\d+)/', $result['codigo'], $matches)) {
            $lastNumber = intval($matches[1]);
            $nextNumber = $lastNumber + 1;
            return 'SCH-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }
        
        return 'SCH-001'; // Primer código si no existe ninguno
    }

    /**
     * Obtener horarios activos con formato de display
     */
    public function getActiveSchedules()
    {
        $sql = "SELECT *, CONCAT(TIME_FORMAT(time_in, '%h:%i %p'), ' - ', TIME_FORMAT(time_out, '%h:%i %p')) as schedule_display 
                FROM {$this->table} 
                WHERE activo = 1 
                ORDER BY time_in";
        return $this->db->findAll($sql);
    }
}