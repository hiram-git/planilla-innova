<?php

namespace App\Models;

use App\Core\Model;

class Position extends Model
{
    public $table = 'position';
    public $fillable = ['description', 'rate'];

    public function getPositionsWithEmployeeCount()
    {
        $sql = "SELECT p.*, COUNT(e.id) as employee_count
                FROM position p
                LEFT JOIN employees e ON p.id = e.position_id
                GROUP BY p.id
                ORDER BY p.description";
        return $this->db->findAll($sql);
    }

    public function validatePositionData($data)
    {
        $rules = [
            'description' => 'required|min:3|max:100',
            'rate' => 'required'
        ];

        return $this->validate($data, $rules);
    }

    public function isDescriptionUnique($description, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM position WHERE description = ?";
        $params = [$description];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->find($sql, $params);
        return $result['count'] == 0;
    }
}