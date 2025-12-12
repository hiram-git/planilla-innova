<?php

namespace App\Models;

use App\Core\ReferenceModel;

/**
 * Modelo para gestión de cuentas contables (antes: partidas)
 * Hereda funcionalidad CRUD básica de ReferenceModel
 *
 * Diferencias con PartidaPresupuestaria:
 * - CuentaContable: Se asigna a conceptos de planilla (clasificación contable)
 * - PartidaPresupuestaria: Se asigna a empleados y posiciones (clasificación presupuestaria)
 */
class CuentaContable extends ReferenceModel
{
    public $table = 'cuentas_contables';

    /**
     * Verificar si la cuenta contable está en uso por conceptos
     */
    public function delete($id)
    {
        // Verificar si está en uso antes de eliminar
        if ($this->hasConcepts($id)) {
            throw new \Exception("No se puede eliminar. La cuenta contable está en uso por conceptos de planilla.");
        }

        if ($this->hasPositions($id)) {
            throw new \Exception("No se puede eliminar. La cuenta contable está en uso por posiciones.");
        }

        return parent::delete($id);
    }

    /**
     * Verificar si la cuenta contable tiene conceptos asociados
     */
    public function hasConcepts($cuentaContableId)
    {
        $sql = "SELECT COUNT(*) as count FROM conceptos WHERE partida_id = ?";
        $result = $this->db->find($sql, [$cuentaContableId]);
        return $result['count'] > 0;
    }

    /**
     * Verificar si la cuenta contable tiene posiciones asociadas
     */
    public function hasPositions($cuentaContableId)
    {
        $sql = "SELECT COUNT(*) as count FROM posiciones WHERE id_partida = ?";
        $result = $this->db->find($sql, [$cuentaContableId]);
        return $result['count'] > 0;
    }

    /**
     * Obtener cuentas contables con contador de conceptos
     */
    public function getCuentasWithConceptCount()
    {
        $sql = "SELECT cc.*, COUNT(c.id) as concept_count
                FROM cuentas_contables cc
                LEFT JOIN conceptos c ON cc.id = c.partida_id
                GROUP BY cc.id
                ORDER BY cc.codigo ASC";

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
        $sql = "SELECT codigo FROM cuentas_contables WHERE codigo LIKE '6.10.10.%' ORDER BY codigo DESC LIMIT 1";
        $result = $this->db->find($sql);

        if ($result && preg_match('/6\.10\.10\.(\d+)/', $result['codigo'], $matches)) {
            $lastNumber = intval($matches[1]);
            $nextNumber = $lastNumber + 1;
            return '6.10.10.' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        return '6.10.10.001'; // Primer código si no existe ninguno
    }

    /**
     * Obtener cuentas contables activas para selects
     */
    public function getActivasForSelect()
    {
        $sql = "SELECT id, codigo, nombre
                FROM cuentas_contables
                WHERE activo = 1
                ORDER BY codigo ASC";

        return $this->db->findAll($sql);
    }
}
