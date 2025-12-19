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

    /**
     * Obtener departamento asociado al cargo
     */
    public function getDepartamento($cargoId)
    {
        $sql = "SELECT o.*
                FROM cargos c
                LEFT JOIN organigrama o ON c.departamento_id = o.id
                WHERE c.id = ?";
        return $this->db->find($sql, [$cargoId]);
    }

    /**
     * Obtener cargos con información de departamento
     */
    public function getCargosWithDepartamento()
    {
        $sql = "SELECT c.*, o.descripcion as departamento_nombre
                FROM cargos c
                LEFT JOIN organigrama o ON c.departamento_id = o.id
                WHERE c.activo = 1
                ORDER BY o.descripcion ASC, c.nombre ASC";

        return $this->db->findAll($sql);
    }

    /**
     * Obtener cargos filtrados por departamento (solo departamento específico)
     *
     * @param int $departamentoId ID del departamento
     * @return array Lista de cargos activos del departamento
     */
    public function getCargosByDepartamento($departamentoId)
    {
        $sql = "SELECT * FROM cargos
                WHERE departamento_id = ? AND activo = 1
                ORDER BY nombre ASC";

        return $this->db->findAll($sql, [$departamentoId]);
    }

    /**
     * Verificar si el cargo tiene empleados asociados
     */
    public function hasEmployees($cargoId)
    {
        $sql = "SELECT COUNT(*) as count FROM employees WHERE cargo_id = ?";
        $result = $this->db->find($sql, [$cargoId]);
        return $result['count'] > 0;
    }

    /**
     * Obtener todos los cargos activos
     */
    public function getAllActive()
    {
        $sql = "SELECT id, codigo, nombre, departamento_id, activo
                FROM cargos
                WHERE activo = 1
                ORDER BY nombre ASC";

        return $this->db->findAll($sql);
    }

    /**
     * Actualizar asociaciones de cargos a un departamento
     * Actualiza departamento_id para los cargos seleccionados
     * y establece NULL para los no seleccionados del mismo departamento
     *
     * @param int $departamentoId ID del departamento
     * @param array $cargoIds Array de IDs de cargos a asociar
     * @return int Número de registros actualizados
     */
    public function updateDepartamentoAsociaciones($departamentoId, $cargoIds)
    {
        try {
            $conn = $this->db->getConnection();
            $conn->beginTransaction();

            $updated = 0;

            // 1. Desasociar todos los cargos actuales de este departamento
            $sqlRemove = "UPDATE cargos SET departamento_id = NULL WHERE departamento_id = ?";
            $stmtRemove = $conn->prepare($sqlRemove);
            $stmtRemove->execute([$departamentoId]);

            // 2. Asociar los cargos seleccionados al departamento
            if (!empty($cargoIds)) {
                $sqlAssign = "UPDATE cargos SET departamento_id = ? WHERE id = ?";
                $stmtAssign = $conn->prepare($sqlAssign);

                foreach ($cargoIds as $cargoId) {
                    $stmtAssign->execute([$departamentoId, $cargoId]);
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