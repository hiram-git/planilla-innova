<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para gestión de estructura organizacional (departamentos)
 * Estructura jerárquica con soporte para árbol de departamentos
 */
class Organigrama extends Model
{
    public $table = 'organigrama';
    public $fillable = ['descripcion', 'id_padre', 'path', 'nivel_jerarquico'];

    /**
     * Obtener todos los departamentos con información jerárquica
     */
    public function getAllWithHierarchy()
    {
        $sql = "SELECT o.*,
                       padre.descripcion as padre_descripcion,
                       (SELECT COUNT(*) FROM organigrama WHERE id_padre = o.id) as hijos_count
                FROM organigrama o
                LEFT JOIN organigrama padre ON o.id_padre = padre.id
                ORDER BY o.path ASC";

        return $this->db->findAll($sql);
    }

    /**
     * Obtener departamentos raíz (sin padre)
     */
    public function getRootDepartments()
    {
        $sql = "SELECT * FROM organigrama
                WHERE id_padre IS NULL
                ORDER BY descripcion ASC";

        return $this->db->findAll($sql);
    }

    /**
     * Obtener hijos de un departamento
     */
    public function getChildren($departamentoId)
    {
        $sql = "SELECT * FROM organigrama
                WHERE id_padre = ?
                ORDER BY descripcion ASC";

        return $this->db->findAll($sql, [$departamentoId]);
    }

    /**
     * Obtener departamento con sus cargos asociados
     */
    public function getDepartamentoWithCargos($departamentoId)
    {
        $sql = "SELECT o.*,
                       COUNT(c.id) as cargos_count,
                       GROUP_CONCAT(c.nombre SEPARATOR ', ') as cargos_nombres
                FROM organigrama o
                LEFT JOIN cargos c ON o.id = c.departamento_id AND c.activo = 1
                WHERE o.id = ?
                GROUP BY o.id";

        return $this->db->find($sql, [$departamentoId]);
    }

    /**
     * Obtener todos los departamentos con conteo de cargos
     */
    public function getAllWithCargosCount()
    {
        $sql = "SELECT o.*,
                       COUNT(c.id) as cargos_count,
                       padre.descripcion as padre_descripcion
                FROM organigrama o
                LEFT JOIN cargos c ON o.id = c.departamento_id AND c.activo = 1
                LEFT JOIN organigrama padre ON o.id_padre = padre.id
                GROUP BY o.id
                ORDER BY o.path ASC";

        return $this->db->findAll($sql);
    }

    /**
     * Obtener cargos de un departamento
     */
    public function getCargos($departamentoId)
    {
        $sql = "SELECT c.*
                FROM cargos c
                WHERE c.departamento_id = ? AND c.activo = 1
                ORDER BY c.nombre ASC";

        return $this->db->findAll($sql, [$departamentoId]);
    }

    /**
     * Obtener empleados de un departamento
     */
    public function getEmpleados($departamentoId)
    {
        $sql = "SELECT e.*, CONCAT(e.firstname, ' ', e.lastname) as full_name,
                       c.nombre as cargo_nombre
                FROM employees e
                LEFT JOIN cargos c ON e.cargo_id = c.id
                WHERE e.departamento_id = ?
                ORDER BY e.lastname, e.firstname";

        return $this->db->findAll($sql, [$departamentoId]);
    }

    /**
     * Verificar si el departamento tiene hijos
     */
    public function hasChildren($departamentoId)
    {
        $sql = "SELECT COUNT(*) as count FROM organigrama WHERE id_padre = ?";
        $result = $this->db->find($sql, [$departamentoId]);
        return $result['count'] > 0;
    }

    /**
     * Verificar si el departamento tiene cargos asociados
     */
    public function hasCargos($departamentoId)
    {
        $sql = "SELECT COUNT(*) as count FROM cargos WHERE departamento_id = ?";
        $result = $this->db->find($sql, [$departamentoId]);
        return $result['count'] > 0;
    }

    /**
     * Verificar si el departamento tiene empleados asociados
     */
    public function hasEmpleados($departamentoId)
    {
        $sql = "SELECT COUNT(*) as count FROM employees WHERE departamento_id = ?";
        $result = $this->db->find($sql, [$departamentoId]);
        return $result['count'] > 0;
    }

    /**
     * Generar path automáticamente basado en la jerarquía
     */
    public function generatePath($descripcion, $idPadre = null)
    {
        $slug = $this->slugify($descripcion);

        if ($idPadre === null) {
            // Departamento raíz
            return '/' . $slug . '/';
        }

        // Obtener path del padre
        $padre = $this->find($idPadre);
        if (!$padre) {
            return '/' . $slug . '/';
        }

        return $padre['path'] . $slug . '/';
    }

    /**
     * Calcular nivel jerárquico automáticamente
     */
    public function calculateNivel($idPadre = null)
    {
        if ($idPadre === null) {
            return 0; // Nivel raíz
        }

        $padre = $this->find($idPadre);
        if (!$padre) {
            return 0;
        }

        return (int)$padre['nivel_jerarquico'] + 1;
    }

    /**
     * Convertir texto a slug (sin tildes ni espacios)
     */
    private function slugify($text)
    {
        // Convertir a minúsculas
        $text = mb_strtolower($text, 'UTF-8');

        // Reemplazar tildes
        $unwanted_array = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'ñ' => 'n', 'Ñ' => 'n'
        ];
        $text = strtr($text, $unwanted_array);

        // Reemplazar espacios y caracteres especiales por guiones
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        // Eliminar guiones al inicio y final
        $text = trim($text, '-');

        return $text;
    }

    /**
     * Validar que no se cree un bucle en la jerarquía
     */
    public function isValidHierarchy($departamentoId, $nuevoPadreId)
    {
        if ($nuevoPadreId === null) {
            return true; // Puede ser raíz
        }

        if ($departamentoId == $nuevoPadreId) {
            return false; // No puede ser su propio padre
        }

        // Verificar que el nuevo padre no sea descendiente del departamento actual
        $padre = $this->find($nuevoPadreId);
        while ($padre && $padre['id_padre'] !== null) {
            if ($padre['id_padre'] == $departamentoId) {
                return false; // Bucle detectado
            }
            $padre = $this->find($padre['id_padre']);
        }

        return true;
    }

    /**
     * Obtener árbol completo de departamentos (para select jerárquico)
     */
    public function getTreeOptions($excludeId = null)
    {
        $departments = $this->all();
        $tree = [];

        foreach ($departments as $dept) {
            if ($excludeId && $dept['id'] == $excludeId) {
                continue; // Excluir departamento específico
            }

            $level = $dept['nivel_jerarquico'];
            $prefix = str_repeat('—', $level) . ' ';

            $tree[] = [
                'id' => $dept['id'],
                'descripcion' => $prefix . $dept['descripcion'],
                'nivel' => $level
            ];
        }

        return $tree;
    }
}
