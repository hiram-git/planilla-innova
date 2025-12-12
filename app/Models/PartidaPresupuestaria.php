<?php

namespace App\Models;

use App\Core\ReferenceModel;

/**
 * Modelo para gestión de partidas presupuestarias
 * Hereda funcionalidad CRUD básica de ReferenceModel
 *
 * Diferencias con CuentaContable (antes Partida):
 * - CuentaContable: Se asigna a conceptos de planilla (clasificación contable)
 * - PartidaPresupuestaria: Se asigna a empleados y posiciones (clasificación presupuestaria)
 */
class PartidaPresupuestaria extends ReferenceModel
{
    public $table = 'partidas_presupuestarias';

    /**
     * Verificar si la partida está en uso por empleados
     */
    public function delete($id)
    {
        // Verificar si está en uso antes de eliminar
        if ($this->hasEmployees($id)) {
            throw new \Exception("No se puede eliminar. La partida presupuestaria está en uso por empleados.");
        }

        if ($this->hasPositions($id)) {
            throw new \Exception("No se puede eliminar. La partida presupuestaria está en uso por posiciones.");
        }

        return parent::delete($id);
    }

    /**
     * Verificar si la partida presupuestaria tiene empleados asociados
     */
    public function hasEmployees($partidaPresupuestariaId)
    {
        $sql = "SELECT COUNT(*) as count FROM employees WHERE partida_presupuestaria_id = ?";
        $result = $this->db->find($sql, [$partidaPresupuestariaId]);
        return $result['count'] > 0;
    }

    /**
     * Verificar si la partida presupuestaria tiene posiciones asociadas
     */
    public function hasPositions($partidaPresupuestariaId)
    {
        $sql = "SELECT COUNT(*) as count FROM posiciones WHERE partida_presupuestaria_id = ?";
        $result = $this->db->find($sql, [$partidaPresupuestariaId]);
        return $result['count'] > 0;
    }

    /**
     * Obtener partidas presupuestarias con contadores de uso
     */
    public function getPartidasWithUsageCount()
    {
        $sql = "SELECT pp.*,
                       COUNT(DISTINCT e.id) as employee_count,
                       COUNT(DISTINCT pos.id) as position_count
                FROM partidas_presupuestarias pp
                LEFT JOIN employees e ON pp.id = e.partida_presupuestaria_id
                LEFT JOIN posiciones pos ON pp.id = pos.partida_presupuestaria_id
                GROUP BY pp.id
                ORDER BY pp.codigo ASC";

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
     * Formato: 1.01.01.XXX (similar a cuentas contables)
     */
    public function getNextCode()
    {
        $sql = "SELECT codigo FROM partidas_presupuestarias WHERE codigo LIKE '1.01.01.%' ORDER BY codigo DESC LIMIT 1";
        $result = $this->db->find($sql);

        if ($result && preg_match('/1\.01\.01\.(\d+)/', $result['codigo'], $matches)) {
            $lastNumber = intval($matches[1]);
            $nextNumber = $lastNumber + 1;
            return '1.01.01.' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        return '1.01.01.001'; // Primer código si no existe ninguno
    }

    /**
     * Obtener partidas presupuestarias activas para selects
     */
    public function getActivasForSelect()
    {
        $sql = "SELECT id, codigo, nombre
                FROM partidas_presupuestarias
                WHERE activo = 1
                ORDER BY codigo ASC";

        return $this->db->findAll($sql);
    }
}
