<?php

namespace App\Models;

use App\Core\ReferenceModel;

/**
 * Modelo para gestión de cuentas contables (antes partidas)
 * Hereda funcionalidad CRUD básica de ReferenceModel
 * NOTA: Este modelo se mantiene por retrocompatibilidad pero apunta a cuentas_contables
 */
class Partida extends ReferenceModel
{
    public $table = 'cuentas_contables';

    /**
     * Verificar si la partida está en uso por posiciones
     */
    public function delete($id)
    {
        // Verificar si está en uso antes de eliminar
        if ($this->hasPositions($id)) {
            throw new \Exception("No se puede eliminar. La partida está en uso por posiciones.");
        }
        
        return parent::delete($id);
    }

    /**
     * Verificar si la partida tiene posiciones asociadas
     */
    public function hasPositions($partidaId)
    {
        $sql = "SELECT COUNT(*) as count FROM posiciones WHERE id_partida = ?";
        $result = $this->db->find($sql, [$partidaId]);
        return $result['count'] > 0;
    }

    /**
     * Obtener cuentas contables con contador de posiciones
     */
    public function getPartidasWithPositionCount()
    {
        $sql = "SELECT p.*, COUNT(pos.id) as position_count
                FROM cuentas_contables p
                LEFT JOIN posiciones pos ON p.id = pos.id_partida
                GROUP BY p.id
                ORDER BY p.codigo ASC";

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
     * NOTA: Usa formato 6.10.10.XXX para cuentas contables
     */
    public function getNextCode()
    {
        $sql = "SELECT codigo FROM cuentas_contables WHERE codigo LIKE '6.10.10.%' ORDER BY codigo DESC LIMIT 1";
        $result = $this->db->find($sql);

        if ($result && preg_match('/6\.10\.10\.(\d+)/', $result['codigo'], $matches)) {
            $lastNumber = intval($matches[1]);
            $nextNumber = $lastNumber + 1;
            return '6.10.10.' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        return '6.10.10.001'; // Primer código si no existe ninguno
    }
}