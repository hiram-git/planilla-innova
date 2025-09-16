<?php

namespace App\Core;

use App\Core\Model;

/**
 * Modelo base abstracto para entidades de referencia
 * Tablas con estructura: id, codigo, nombre, descripcion, activo, created_at, updated_at
 */
abstract class ReferenceModel extends Model
{
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];
    protected $timestamps = true;

    public function validateReferenceData($data)
    {
        $rules = [
            'codigo' => 'required|min:1|max:20',
            'nombre' => 'required|min:2|max:100',
            'descripcion' => 'max:500'
        ];

        return $this->validate($data, $rules);
    }
    
    public function validateReferenceUpdateData($data)
    {
        $rules = [
            'edit_codigo' => 'required|min:1|max:20',
            'edit_nombre' => 'required|min:2|max:100',
            'edit_descripcion' => 'max:500'
        ];

        return $this->validate($data, $rules);
    }

    public function isCodigoUnique($codigo, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE codigo = ?";
        $params = [$codigo];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->find($sql, $params);
        return $result['count'] == 0;
    }

    public function getActive()
    {
        return $this->where('activo', 1);
    }

    public function getAllSorted()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY codigo ASC";
        return $this->db->findAll($sql);
    }

    public function updateStatus($id, $status)
    {
        try {
            $sql = "UPDATE {$this->table} SET activo = :status WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':status' => $status,
                ':id' => $id
            ]);
        } catch (\Exception $e) {
            error_log("Error updating status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener registros activos para usar en selects
     */
    public function getActiveForSelect()
    {
        try {
            $sql = "SELECT id, codigo, nombre FROM {$this->table} WHERE activo = 1 ORDER BY codigo ASC";
            return $this->db->findAll($sql);
        } catch (\Exception $e) {
            error_log("Error getting active options: " . $e->getMessage());
            return [];
        }
    }
}