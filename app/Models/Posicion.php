<?php

namespace App\Models;

use App\Core\Model;

class Posicion extends Model
{
    public $table = 'posiciones';
    public $fillable = ['codigo', 'id_partida', 'id_cargo', 'id_funcion', 'sueldo'];

    public function getAllWithRelations()
    {
        $sql = "SELECT 
                    posiciones.id AS posid,
                    posiciones.codigo,
                    posiciones.sueldo,
                    COALESCE(cargos.nombre, cargos.descripcion, 'Sin cargo') AS descripcion_cargo,
                    COALESCE(partidas.nombre, partidas.descripcion, 'Sin partida') AS descripcion_partida,
                    COALESCE(funciones.nombre, funciones.descripcion, 'Sin función') AS descripcion_funcion
                FROM posiciones 
                LEFT JOIN cargos ON cargos.id = posiciones.id_cargo 
                LEFT JOIN partidas ON partidas.id = posiciones.id_partida
                LEFT JOIN funciones ON funciones.id = posiciones.id_funcion
                ORDER BY posiciones.id DESC";
        
        return $this->db->findAll($sql);
    }

    public function getPositionWithRelations($id)
    {
        $sql = "SELECT 
                    posiciones.id AS posid,
                    posiciones.codigo,
                    posiciones.sueldo,
                    posiciones.id_partida,
                    posiciones.id_cargo,
                    posiciones.id_funcion,
                    COALESCE(cargos.nombre, cargos.descripcion, 'Sin cargo') AS descripcion_cargo,
                    COALESCE(partidas.nombre, partidas.descripcion, 'Sin partida') AS descripcion_partida,
                    COALESCE(funciones.nombre, funciones.descripcion, 'Sin función') AS descripcion_funcion
                FROM posiciones 
                LEFT JOIN cargos ON cargos.id = posiciones.id_cargo 
                LEFT JOIN partidas ON partidas.id = posiciones.id_partida
                LEFT JOIN funciones ON funciones.id = posiciones.id_funcion
                WHERE posiciones.id = ?";
        
        return $this->db->find($sql, [$id]);
    }

    public function validatePositionData($data)
    {
        $rules = [
            'codigo' => 'required|min:1|max:100',
            'partida' => 'required',
            'cargo' => 'required',
            'funcion' => 'required',
            'sueldo' => 'required|numeric'
        ];

        // Para actualización, usar los campos con prefijo edit_
        if (isset($data['edit_codigo'])) {
            $rules = [
                'edit_codigo' => 'required|min:1|max:100',
                'edit_partida' => 'required',
                'edit_cargo' => 'required',
                'edit_funcion' => 'required',
                'edit_sueldo' => 'required|numeric'
            ];
        }

        return $this->validate($data, $rules);
    }

    public function hasEmployees($positionId)
    {
        $sql = "SELECT COUNT(*) as count FROM employees WHERE position_id = ?";
        $result = $this->db->find($sql, [$positionId]);
        return $result['count'] > 0;
    }

    public function isDescriptionUnique($description, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM posiciones WHERE codigo = ?";
        $params = [$description];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->find($sql, $params);
        return $result['count'] == 0;
    }

    public function getPositionsWithEmployeeCount()
    {
        $sql = "SELECT 
                    p.*,
                    COUNT(e.id) as employee_count,
                    COALESCE(c.nombre, c.descripcion, 'Sin cargo') as cargo_descripcion,
                    COALESCE(pt.nombre, pt.descripcion, 'Sin partida') as partida_descripcion,
                    COALESCE(f.nombre, f.descripcion, 'Sin función') as funcion_descripcion
                FROM posiciones p
                LEFT JOIN employees e ON p.id = e.position_id
                LEFT JOIN cargos c ON p.id_cargo = c.id
                LEFT JOIN partidas pt ON p.id_partida = pt.id
                LEFT JOIN funciones f ON p.id_funcion = f.id
                GROUP BY p.id
                ORDER BY p.codigo";
        
        return $this->db->findAll($sql);
    }

    public function getBySalaryRange($minSalary, $maxSalary)
    {
        $sql = "SELECT * FROM posiciones WHERE sueldo BETWEEN ? AND ? ORDER BY sueldo DESC";
        return $this->db->findAll($sql, [$minSalary, $maxSalary]);
    }

    /**
     * Generar el siguiente código correlativo
     */
    public function getNextCode()
    {
        $sql = "SELECT codigo FROM posiciones WHERE codigo REGEXP '^[0-9]+$' ORDER BY CAST(codigo AS UNSIGNED) DESC LIMIT 1";
        $result = $this->db->find($sql);
        
        if ($result && is_numeric($result['codigo'])) {
            $lastNumber = intval($result['codigo']);
            $nextNumber = $lastNumber + 1;
            return str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }
        
        return '001'; // Primer código si no existe ninguno
    }
}