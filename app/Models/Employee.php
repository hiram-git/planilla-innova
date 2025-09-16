<?php

namespace App\Models;

use App\Core\Model;

class Employee extends Model
{
    public $table = 'employees';
    public $fillable = [
        'employee_id', 'firstname', 'lastname', 'address', 'birthdate',
        'fecha_ingreso', 'contact_info', 'gender', 'position_id', 'schedule_id', 
        'photo', 'organigrama_path', 'document_id', 'clave_seguro_social', 
        'situacion_id', 'tipo_planilla_id', 'cargo_id', 'funcion_id', 'partida_id', 
        'sueldo_individual', 'created_on'
    ];

    public function findByEmployeeId($employeeId)
    {
        return $this->first('employee_id', $employeeId);
    }

    public function getFullName($employee)
    {
        return trim($employee['firstname'] . ' ' . $employee['lastname']);
    }

    public function getPosition($employeeId)
    {
        $sql = "SELECT p.codigo as position_name 
                FROM employees e 
                LEFT JOIN posiciones p ON e.position_id = p.id 
                WHERE e.id = ?";
        return $this->db->find($sql, [$employeeId]);
    }

    public function getSchedule($employeeId)
    {
        $sql = "SELECT s.* 
                FROM employees e 
                LEFT JOIN schedules s ON e.schedule_id = s.id 
                WHERE e.id = ?";
        return $this->db->find($sql, [$employeeId]);
    }

    public function getAllWithDetails()
    {
        $sql = "SELECT e.*, 
                       pos.codigo as position_name, 
                       s.time_in, s.time_out,
                       c.descripcion as cargo_name,
                       p.partida as partida_name,
                       f.descripcion as funcion_name
                FROM employees e
                LEFT JOIN posiciones pos ON e.position_id = pos.id
                LEFT JOIN schedules s ON e.schedule_id = s.id
                LEFT JOIN cargos c ON pos.id_cargo = c.id
                LEFT JOIN partidas p ON pos.id_partida = p.id
                LEFT JOIN funciones f ON pos.id_funcion = f.id
                ORDER BY e.lastname, e.firstname";
        return $this->db->findAll($sql);
    }

    public function validateEmployeeData($data)
    {
        // Obtener tipo de empresa para validación condicional
        $companyModel = new \App\Models\Company();
        $companyConfig = $companyModel->getCompanyConfig();
        $isPublicInstitution = ($companyConfig['tipo_institucion'] ?? 'privada') === 'publica';
        
        $rules = [
            'firstname' => 'required|min:2|max:50',
            'lastname' => 'required|min:2|max:50',
            'document_id' => 'required|min:5|max:20',
            'birthdate' => 'required|date',
            'gender' => 'required',
            'schedule' => 'required',
            'situacion' => 'required',
            'tipo_planilla' => 'required'
        ];
        
        // Validación condicional según tipo de empresa
        if ($isPublicInstitution) {
            // Institución pública: posición obligatoria
            $rules['position'] = 'required';
        } else {
            // Empresa privada: cargo, función, partida y sueldo obligatorios
            $rules['cargo_id'] = 'required';
            $rules['funcion_id'] = 'required';
            $rules['partida_id'] = 'required';
            $rules['sueldo_individual'] = 'required|numeric|min:0';
        }

        return $this->validate($data, $rules);
    }
    
    public function validateEmployeeUpdateData($data)
    {
        // Obtener tipo de empresa para validación condicional
        $companyModel = new \App\Models\Company();
        $companyConfig = $companyModel->getCompanyConfig();
        $isPublicInstitution = ($companyConfig['tipo_institucion'] ?? 'privada') === 'publica';
        
        $rules = [
            'edit_firstname' => 'required|min:2|max:50',
            'edit_lastname' => 'required|min:2|max:50',
            'edit_document_id' => 'required|min:5|max:20',
            'edit_birthdate' => 'required|date',
            'edit_gender' => 'required',
            'edit_schedule' => 'required',
            'edit_situacion' => 'required',
            'edit_tipo_planilla' => 'required'
        ];
        
        // Validación condicional según tipo de empresa
        if ($isPublicInstitution) {
            // Institución pública: posición obligatoria
            $rules['edit_position'] = 'required';
        } else {
            // Empresa privada: cargo, función, partida y sueldo obligatorios
            $rules['edit_cargo_id'] = 'required';
            $rules['edit_funcion_id'] = 'required';
            $rules['edit_partida_id'] = 'required';
            $rules['edit_sueldo_individual'] = 'required|numeric|min:0';
        }

        return $this->validate($data, $rules);
    }

    public function isEmployeeIdUnique($employeeId, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM employees WHERE employee_id = ?";
        $params = [$employeeId];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->find($sql, $params);
        return $result['count'] == 0;
    }

    public function isDocumentIdUnique($documentId, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM employees WHERE document_id = ?";
        $params = [$documentId];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->find($sql, $params);
        return $result['count'] == 0;
    }

    public function generateEmployeeId()
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        
        do {
            $employeeId = substr(str_shuffle($letters), 0, 3) . substr(str_shuffle($numbers), 0, 6);
        } while (!$this->isEmployeeIdUnique($employeeId));
        
        return $employeeId;
    }

    public function getEmployeeWithFullDetails($id)
    {
        $sql = "SELECT e.*, 
                       pos.codigo as position_name,
                       pos.sueldo as position_salary,
                       s.time_in, s.time_out,
                       c.descripcion as cargo_name,
                       p.partida as partida_name,
                       f.descripcion as funcion_name,
                       org.descripcion as organigrama_name,
                       sit.descripcion as situacion_nombre,
                       comp.currency_symbol as moneda_simbolo
                FROM employees e
                LEFT JOIN posiciones pos ON e.position_id = pos.id
                LEFT JOIN schedules s ON e.schedule_id = s.id
                LEFT JOIN cargos c ON pos.id_cargo = c.id
                LEFT JOIN partidas p ON pos.id_partida = p.id
                LEFT JOIN funciones f ON pos.id_funcion = f.id
                LEFT JOIN organigrama org ON e.organigrama_path = org.path
                LEFT JOIN situaciones sit ON e.situacion_id = sit.id
                LEFT JOIN companies comp ON 1=1
                WHERE e.id = ?";
        
        return $this->db->find($sql, [$id]);
    }

    public function getActiveEmployees()
    {
        $sql = "SELECT e.*, pos.codigo as position_name, sit.descripcion as situacion_nombre
                FROM employees e
                LEFT JOIN posiciones pos ON e.position_id = pos.id
                LEFT JOIN situaciones sit ON e.situacion_id = sit.id
                WHERE (e.situacion_id = 1 OR sit.descripcion LIKE '%activ%' OR sit.descripcion LIKE '%ACTIV%' OR e.situacion_id IS NULL)
                ORDER BY e.lastname, e.firstname";
        
        return $this->db->findAll($sql);
    }

    /**
     * Obtener opciones de empleados para selects
     */
    public function getOptions()
    {
        try {
            $sql = "SELECT employee_id, firstname, lastname, CONCAT(firstname, ' ', lastname) as full_name
                    FROM employees 
                    ORDER BY firstname, lastname";
            
            return $this->db->findAll($sql);
        } catch (\Exception $e) {
            error_log("Error getting employee options: " . $e->getMessage());
            return [];
        }
    }
}