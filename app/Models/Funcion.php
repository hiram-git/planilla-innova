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

    /**
     * Obtener cargo asociado a la función
     */
    public function getCargo($funcionId)
    {
        $sql = "SELECT c.*
                FROM funciones f
                LEFT JOIN cargos c ON f.cargo_id = c.id
                WHERE f.id = ?";
        return $this->db->find($sql, [$funcionId]);
    }

    /**
     * Obtener funciones con información de cargo
     */
    public function getFuncionesWithCargo()
    {
        $sql = "SELECT f.*, c.nombre as cargo_nombre
                FROM funciones f
                LEFT JOIN cargos c ON f.cargo_id = c.id
                WHERE f.activo = 1
                ORDER BY c.nombre ASC, f.nombre ASC";

        return $this->db->findAll($sql);
    }

    /**
     * Obtener funciones filtradas por cargo (incluyendo genéricas con cargo_id NULL)
     */
    public function getFuncionesByCargo($cargoId = null)
    {
        if ($cargoId === null) {
            // Retornar solo funciones genéricas
            $sql = "SELECT * FROM funciones
                    WHERE cargo_id IS NULL AND activo = 1
                    ORDER BY nombre ASC";
            return $this->db->findAll($sql);
        }

        // Retornar funciones específicas del cargo + funciones genéricas
        $sql = "SELECT * FROM funciones
                WHERE (cargo_id = ? OR cargo_id IS NULL) AND activo = 1
                ORDER BY CASE WHEN cargo_id IS NULL THEN 1 ELSE 0 END, nombre ASC";

        return $this->db->findAll($sql, [$cargoId]);
    }

    /**
     * Verificar si la función tiene empleados asociados
     */
    public function hasEmployees($funcionId)
    {
        $sql = "SELECT COUNT(*) as count FROM employees WHERE funcion_id = ?";
        $result = $this->db->find($sql, [$funcionId]);
        return $result['count'] > 0;
    }

    /**
     * Obtener solo funciones genéricas (cargo_id = NULL)
     */
    public function getFuncionesGenericas()
    {
        $sql = "SELECT * FROM funciones
                WHERE cargo_id IS NULL AND activo = 1
                ORDER BY nombre ASC";

        return $this->db->findAll($sql);
    }

    /**
     * Obtener todas las funciones activas
     */
    public function getAllActive()
    {
        $sql = "SELECT id, codigo, nombre, cargo_id, activo
                FROM funciones
                WHERE activo = 1
                ORDER BY nombre ASC";

        return $this->db->findAll($sql);
    }

    /**
     * Obtener funciones por departamento (a través de cargos asociados)
     */
    public function getFuncionesByDepartamento($departamentoId)
    {
        $sql = "SELECT f.*
                FROM funciones f
                INNER JOIN cargos c ON f.cargo_id = c.id
                WHERE c.departamento_id = ? AND f.activo = 1
                ORDER BY f.nombre ASC";

        return $this->db->findAll($sql, [$departamentoId]);
    }

    /**
     * Actualizar asociaciones de funciones a cargos de un departamento
     * Actualiza cargo_id para las funciones seleccionadas, asignándolas
     * a un cargo específico del departamento
     *
     * @param int $cargoId ID del cargo al que se asignarán las funciones
     * @param array $funcionIds Array de IDs de funciones a asociar
     * @return int Número de registros actualizados
     */
    public function updateCargoAsociaciones($cargoId, $funcionIds)
    {
        try {
            $conn = $this->db->getConnection();
            $conn->beginTransaction();

            $updated = 0;

            // 1. Desasociar todas las funciones actuales de este cargo
            $sqlRemove = "UPDATE funciones SET cargo_id = NULL WHERE cargo_id = ?";
            $stmtRemove = $conn->prepare($sqlRemove);
            $stmtRemove->execute([$cargoId]);

            // 2. Asociar las funciones seleccionadas al cargo
            if (!empty($funcionIds)) {
                $sqlAssign = "UPDATE funciones SET cargo_id = ? WHERE id = ?";
                $stmtAssign = $conn->prepare($sqlAssign);

                foreach ($funcionIds as $funcionId) {
                    $stmtAssign->execute([$cargoId, $funcionId]);
                    $updated++;
                }
            }

            $conn->commit();
            return $updated;

        } catch (\Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }
            throw new \Exception("Error al actualizar asociaciones: " . $e->getMessage());
        }
    }
}