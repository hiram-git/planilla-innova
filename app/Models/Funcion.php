<?php

namespace App\Models;

use App\Core\ReferenceModel;

/**
 * Modelo para gestión de funciones
 * Hereda funcionalidad CRUD básica de ReferenceModel
 */
class Funcion extends ReferenceModel
{
    public $table = 'funciones';

    /**
     * Verificar si la función está en uso por posiciones
     */
    public function delete($id)
    {
        // Verificar si está en uso antes de eliminar
        if ($this->hasPositions($id)) {
            throw new \Exception("No se puede eliminar. La función está en uso por posiciones.");
        }
        
        return parent::delete($id);
    }

    /**
     * Verificar si la función tiene posiciones asociadas
     */
    public function hasPositions($funcionId)
    {
        $sql = "SELECT COUNT(*) as count FROM posiciones WHERE id_funcion = ?";
        $result = $this->db->find($sql, [$funcionId]);
        return $result['count'] > 0;
    }

    /**
     * Obtener funciones con contador de posiciones
     */
    public function getFuncionesWithPositionCount()
    {
        $sql = "SELECT f.*, COUNT(p.id) as position_count
                FROM funciones f
                LEFT JOIN posiciones p ON f.id = p.id_funcion
                GROUP BY f.id
                ORDER BY f.codigo ASC";
        
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
        $sql = "SELECT codigo FROM funciones WHERE codigo LIKE 'FUN-%' ORDER BY codigo DESC LIMIT 1";
        $result = $this->db->find($sql);
        
        if ($result && preg_match('/FUN-(\d+)/', $result['codigo'], $matches)) {
            $lastNumber = intval($matches[1]);
            $nextNumber = $lastNumber + 1;
            return 'FUN-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }
        
        return 'FUN-001'; // Primer código si no existe ninguno
    }
}