<?php

namespace App\Models;

use App\Core\Model;

class Position extends Model
{
    public $table = 'posiciones';
    public $fillable = ['codigo', 'id_cargo', 'id_funcion', 'id_partida', 'sueldo'];

    public function getPositionsWithEmployeeCount()
    {
        $sql = "SELECT p.*, COUNT(e.id) as employee_count
                FROM posiciones p
                LEFT JOIN employees e ON p.id = e.position_id
                GROUP BY p.id
                ORDER BY p.codigo";
        return $this->db->findAll($sql);
    }

    public function validatePositionData($data)
    {
        $rules = [
            'codigo' => 'required|min:1|max:255',
            'sueldo' => 'numeric'
        ];

        return $this->validate($data, $rules);
    }

    public function isCodigoUnique($codigo, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM posiciones WHERE codigo = ?";
        $params = [$codigo];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->db->find($sql, $params);
        return $result['count'] == 0;
    }
}