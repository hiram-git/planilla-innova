<?php

namespace App\Models;

use App\Core\ReferenceModel;

/**
 * Modelo para gestión de cargos
 * Hereda funcionalidad CRUD básica de ReferenceModel
 */
class Cargo extends ReferenceModel
{
    public $table = 'cargos';

    /**
     * Verificar si el cargo está en uso por posiciones
     */
    public function delete($id)
    {
        // Verificar si está en uso antes de eliminar
        if ($this->hasPositions($id)) {
            throw new \Exception("No se puede eliminar. El cargo está en uso por posiciones.");
        }
        
        return parent::delete($id);
    }

    /**
     * Verificar si el cargo tiene posiciones asociadas
     */
    public function hasPositions($cargoId)
    {
        $sql = "SELECT COUNT(*) as count FROM posiciones WHERE id_cargo = ?";
        $result = $this->db->find($sql, [$cargoId]);
        return $result['count'] > 0;
    }

    /**
     * Obtener cargos con contador de posiciones
     */
    public function getCargosWithPositionCount()
    {
        $sql = "SELECT c.*, COUNT(p.id) as position_count
                FROM cargos c
                LEFT JOIN posiciones p ON c.id = p.id_cargo
                GROUP BY c.id
                ORDER BY c.codigo ASC";
        
        return $this->db->findAll($sql);
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
        $sql = "SELECT codigo FROM cargos WHERE codigo LIKE 'CAR-%' ORDER BY codigo DESC LIMIT 1";
        $result = $this->db->find($sql);
        
        if ($result && preg_match('/CAR-(\d+)/', $result['codigo'], $matches)) {
            $lastNumber = intval($matches[1]);
            $nextNumber = $lastNumber + 1;
            return 'CAR-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }
        
        return 'CAR-001'; // Primer código si no existe ninguno
    }
}