<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para gestión de estructura organizacional (organigrama)
 */
class Organizational extends Model
{
    public $table = 'organigrama';

    /**
     * Obtener estructura organizacional completa con jerarquía
     */
    public function getOrganizationalHierarchy()
    {
        $sql = "SELECT 
                    o.*,
                    p.descripcion as parent_descripcion
                FROM organigrama o
                LEFT JOIN organigrama p ON o.id_padre = p.id
                ORDER BY o.path ASC";
                
        $items = $this->db->findAll($sql);
        
        return $this->buildHierarchyTree($items, 'id_padre');
    }

    /**
     * Obtener todos los elementos planos para selects
     */
    public function getOrganizationalFlat()
    {
        $sql = "SELECT id, descripcion, id_padre, path
                FROM organigrama 
                ORDER BY path ASC";
                
        return $this->db->findAll($sql);
    }

    /**
     * Obtener elemento por ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM organigrama WHERE id = ?";
        return $this->db->find($sql, [$id]);
    }

    /**
     * Crear nuevo elemento organizacional
     */
    public function create($data)
    {
        // Calcular el path automáticamente
        $path = $this->calculatePath($data['id_padre'], $data['descripcion']);
        
        $sql = "INSERT INTO organigrama (descripcion, id_padre, path) 
                VALUES (?, ?, ?)";
                
        return $this->db->query($sql, [
            $data['descripcion'],
            $data['id_padre'],
            $path
        ]);
    }

    /**
     * Actualizar elemento organizacional
     */
    public function update($id, $data)
    {
        // Recalcular el path si cambió el padre o descripción
        $currentItem = $this->getById($id);
        $newPath = $this->calculatePath($data['id_padre'], $data['descripcion']);
        
        $sql = "UPDATE organigrama SET 
                    descripcion = ?, 
                    id_padre = ?, 
                    path = ?
                WHERE id = ?";
                
        $result = $this->db->query($sql, [
            $data['descripcion'],
            $data['id_padre'],
            $newPath,
            $id
        ]);
        
        // Si cambió el path, actualizar todos los hijos
        if ($newPath !== $currentItem['path']) {
            $this->updateChildrenPaths($id);
        }
        
        return $result;
    }

    /**
     * Eliminar elemento organizacional
     */
    public function delete($id)
    {
        // Verificar que no tenga hijos
        $children = $this->getChildren($id);
        if (!empty($children)) {
            throw new \Exception('No se puede eliminar un elemento que tiene elementos hijos');
        }
        
        $sql = "DELETE FROM organigrama WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    /**
     * Obtener hijos directos de un elemento
     */
    public function getChildren($parentId)
    {
        $sql = "SELECT * FROM organigrama WHERE id_padre = ? ORDER BY descripcion ASC";
        return $this->db->findAll($sql, [$parentId]);
    }

    /**
     * Obtener toda la descendencia de un elemento
     */
    public function getAllDescendants($parentId)
    {
        $parent = $this->getById($parentId);
        if (!$parent) {
            return [];
        }
        
        $sql = "SELECT * FROM organigrama 
                WHERE path LIKE ? AND id != ?
                ORDER BY path ASC";
                
        return $this->db->findAll($sql, [
            $parent['path'] . '%',
            $parentId
        ]);
    }

    /**
     * Obtener ruta completa desde la raíz hasta un elemento
     */
    public function getPathToRoot($id)
    {
        $item = $this->getById($id);
        if (!$item) {
            return [];
        }
        
        $pathParts = explode('/', trim($item['path'], '/'));
        $fullPath = [];
        $currentPath = '';
        
        foreach ($pathParts as $part) {
            $currentPath .= '/' . $part;
            $sql = "SELECT * FROM organigrama WHERE path = ?";
            $pathItem = $this->db->find($sql, [$currentPath . '/']);
            if ($pathItem) {
                $fullPath[] = $pathItem;
            }
        }
        
        return $fullPath;
    }

    /**
     * Obtener estructura para gráfico de organigrama
     */
    public function getOrganizationalChart()
    {
        $items = $this->getOrganizationalFlat();
        return $this->buildChartData($items, 'id_padre');
    }

    /**
     * Mover elemento a nuevo padre
     */
    public function moveToParent($id, $newParentId)
    {
        // Verificar que no se esté moviendo a sí mismo o a un descendiente
        if ($id == $newParentId) {
            throw new \Exception('Un elemento no puede ser padre de sí mismo');
        }
        
        $descendants = $this->getAllDescendants($id);
        foreach ($descendants as $descendant) {
            if ($descendant['id'] == $newParentId) {
                throw new \Exception('No se puede mover a un descendiente propio');
            }
        }
        
        // Obtener elemento actual
        $item = $this->getById($id);
        
        // Actualizar con nuevo padre
        return $this->update($id, [
            'descripcion' => $item['descripcion'],
            'id_padre' => $newParentId
        ]);
    }

    /**
     * Calcular path para un elemento
     */
    private function calculatePath($parentId, $descripcion)
    {
        if (!$parentId) {
            // Es un elemento raíz
            return '/' . $this->slugify($descripcion) . '/';
        }
        
        $parent = $this->getById($parentId);
        if (!$parent) {
            throw new \Exception('El elemento padre no existe');
        }
        
        return $parent['path'] . $this->slugify($descripcion) . '/';
    }

    /**
     * Actualizar paths de todos los hijos cuando cambia un padre
     */
    private function updateChildrenPaths($parentId)
    {
        $children = $this->getChildren($parentId);
        
        foreach ($children as $child) {
            $newPath = $this->calculatePath($parentId, $child['descripcion']);
            
            $sql = "UPDATE organigrama SET path = ? WHERE id = ?";
            $this->db->query($sql, [$newPath, $child['id']]);
            
            // Recursivamente actualizar los hijos de este hijo
            $this->updateChildrenPaths($child['id']);
        }
    }

    /**
     * Convertir texto a slug para path
     */
    private function slugify($text)
    {
        // Reemplazar caracteres especiales y espacios
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug ?: 'elemento';
    }

    /**
     * Construir árbol jerárquico a partir de datos planos
     */
    private function buildHierarchyTree($items, $parentField = 'id_padre', $parentId = null)
    {
        $tree = [];
        
        foreach ($items as $item) {
            if ($item[$parentField] == $parentId) {
                $children = $this->buildHierarchyTree($items, $parentField, $item['id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        
        return $tree;
    }

    /**
     * Construir datos para gráfico de organigrama
     */
    private function buildChartData($items, $parentField = 'id_padre', $parentId = null)
    {
        $chartData = [];
        
        foreach ($items as $item) {
            if ($item[$parentField] == $parentId) {
                $node = [
                    'id' => $item['id'],
                    'name' => $item['descripcion'],
                    'title' => $item['path'],
                    'children' => []
                ];
                
                $children = $this->buildChartData($items, $parentField, $item['id']);
                if (!empty($children)) {
                    $node['children'] = $children;
                }
                
                $chartData[] = $node;
            }
        }
        
        return $chartData;
    }

    /**
     * Obtener estadísticas de la estructura organizacional
     */
    public function getStatistics()
    {
        $total = $this->db->findAll("SELECT COUNT(*) as total FROM organigrama")[0]['total'];
        $roots = $this->db->findAll("SELECT COUNT(*) as total FROM organigrama WHERE id_padre IS NULL")[0]['total'];
        
        // Calcular profundidad máxima basada en el path más largo
        $maxDepth = $this->db->findAll("
            SELECT MAX(LENGTH(path) - LENGTH(REPLACE(path, '/', ''))) - 1 as max_depth 
            FROM organigrama
        ")[0]['max_depth'] ?? 0;
        
        return [
            'total_elementos' => (int)$total,
            'elementos_raiz' => (int)$roots,
            'profundidad_maxima' => (int)$maxDepth
        ];
    }

    /**
     * Buscar elementos por descripción
     */
    public function search($query)
    {
        $sql = "SELECT * FROM organigrama 
                WHERE descripcion LIKE ? 
                ORDER BY path ASC";
                
        return $this->db->findAll($sql, ['%' . $query . '%']);
    }
}