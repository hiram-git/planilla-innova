<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

/**
 * TipoAcumulado Model
 * Modelo para gestión de tipos de acumulados
 */
class TipoAcumulado
{
    private $db;
    private $table = 'tipos_acumulados';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los tipos de acumulados
     */
    public function getAll($includeInactive = false)
    {
        $whereClause = $includeInactive ? '' : 'WHERE activo = 1';
        
        $sql = "
            SELECT 
                id,
                codigo,
                descripcion,
                periodicidad,
                fecha_inicio_periodo,
                fecha_fin_periodo,
                reinicia_automaticamente,
                activo,
                created_at,
                updated_at,
                (SELECT COUNT(*) FROM conceptos_acumulados ca WHERE ca.tipo_acumulado_id = ta.id) as conceptos_asociados
            FROM {$this->table} ta
            {$whereClause}
            ORDER BY codigo ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener solo tipos de acumulados activos
     */
    public function getActivos()
    {
        return $this->getAll(false);
    }

    /**
     * Obtener tipo de acumulado por ID
     */
    public function getById($id)
    {
        $sql = "
            SELECT 
                id,
                codigo,
                descripcion,
                periodicidad,
                fecha_inicio_periodo,
                fecha_fin_periodo,
                reinicia_automaticamente,
                activo,
                created_at,
                updated_at,
                (SELECT COUNT(*) FROM conceptos_acumulados ca WHERE ca.tipo_acumulado_id = ta.id) as conceptos_asociados
            FROM {$this->table} ta
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crear nuevo tipo de acumulado
     */
    public function create($data)
    {
        $sql = "
            INSERT INTO {$this->table} (
                codigo, descripcion, periodicidad, fecha_inicio_periodo,
                fecha_fin_periodo, reinicia_automaticamente, activo
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['codigo'],
            $data['descripcion'],
            $data['periodicidad'],
            $data['fecha_inicio_periodo'],
            $data['fecha_fin_periodo'],
            $data['reinicia_automaticamente'],
            $data['activo']
        ]);

        if (!$result) {
            throw new Exception('Error al crear tipo de acumulado');
        }

        return $this->db->lastInsertId();
    }

    /**
     * Actualizar tipo de acumulado
     */
    public function update($id, $data)
    {
        $sql = "
            UPDATE {$this->table} 
            SET 
                codigo = ?,
                descripcion = ?,
                periodicidad = ?,
                fecha_inicio_periodo = ?,
                fecha_fin_periodo = ?,
                reinicia_automaticamente = ?,
                activo = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['codigo'],
            $data['descripcion'],
            $data['periodicidad'],
            $data['fecha_inicio_periodo'],
            $data['fecha_fin_periodo'],
            $data['reinicia_automaticamente'],
            $data['activo'],
            $id
        ]);

        if (!$result) {
            throw new Exception('Error al actualizar tipo de acumulado');
        }

        return $stmt->rowCount() > 0;
    }

    /**
     * Eliminar tipo de acumulado
     */
    public function delete($id)
    {
        // Verificar que no tenga conceptos asociados
        $conceptosAsociados = $this->getConceptosAsociados($id);
        if ($conceptosAsociados > 0) {
            throw new Exception("No se puede eliminar: tiene {$conceptosAsociados} concepto(s) asociado(s)");
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$id]);

        if (!$result) {
            throw new Exception('Error al eliminar tipo de acumulado');
        }

        return $stmt->rowCount() > 0;
    }

    /**
     * Alternar estado activo/inactivo
     */
    public function toggleStatus($id)
    {
        $current = $this->getById($id);
        if (!$current) {
            throw new Exception('Tipo de acumulado no encontrado');
        }

        $newStatus = $current['activo'] ? 0 : 1;
        
        $sql = "UPDATE {$this->table} SET activo = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$newStatus, $id]);

        if (!$result) {
            throw new Exception('Error al cambiar estado');
        }

        return ['new_status' => $newStatus];
    }

    /**
     * Obtener datos para DataTables
     */
    public function getDatatables($start, $length, $search = '')
    {
        // Consulta base
        $baseQuery = "
            FROM {$this->table} ta
            LEFT JOIN (
                SELECT tipo_acumulado_id, COUNT(*) as conceptos_count
                FROM conceptos_acumulados 
                GROUP BY tipo_acumulado_id
            ) ca ON ta.id = ca.tipo_acumulado_id
        ";

        $whereClause = '';
        $params = [];

        // Filtro de búsqueda
        if (!empty($search)) {
            $whereClause = "WHERE (ta.codigo LIKE ? OR ta.descripcion LIKE ? OR ta.periodicidad LIKE ?)";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam];
        }

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total " . $baseQuery;
        $stmt = $this->db->prepare($totalQuery);
        $stmt->execute();
        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Contar registros filtrados
        $filteredQuery = "SELECT COUNT(*) as total " . $baseQuery . " " . $whereClause;
        $stmt = $this->db->prepare($filteredQuery);
        $stmt->execute($params);
        $filteredRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Obtener datos paginados
        $dataQuery = "
            SELECT 
                ta.id,
                ta.codigo,
                ta.descripcion,
                ta.periodicidad,
                ta.fecha_inicio_periodo,
                ta.fecha_fin_periodo,
                ta.reinicia_automaticamente,
                ta.activo,
                ta.created_at,
                COALESCE(ca.conceptos_count, 0) as conceptos_asociados
            " . $baseQuery . " " . $whereClause . "
            ORDER BY ta.codigo ASC
            LIMIT ? OFFSET ?
        ";

        $params[] = $length;
        $params[] = $start;

        $stmt = $this->db->prepare($dataQuery);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $totalRecords,
            'filtered' => $filteredRecords,
            'data' => $data
        ];
    }

    /**
     * Obtener opciones para select2
     */
    public function getOptions($search = '', $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $whereClause = "WHERE activo = 1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (codigo LIKE ? OR descripcion LIKE ?)";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam];
        }

        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} " . $whereClause;
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Obtener datos
        $sql = "
            SELECT id, codigo, descripcion, periodicidad
            FROM {$this->table} 
            {$whereClause}
            ORDER BY codigo ASC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($tipos as $tipo) {
            $results[] = [
                'id' => $tipo['id'],
                'text' => "[{$tipo['codigo']}] {$tipo['descripcion']} ({$tipo['periodicidad']})"
            ];
        }

        return [
            'results' => $results,
            'pagination' => [
                'more' => ($offset + $perPage) < $total
            ]
        ];
    }

    /**
     * Verificar si existe código duplicado
     */
    public function existsCodigo($codigo, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE codigo = ?";
        $params = [$codigo];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }

    /**
     * Obtener cantidad de conceptos asociados
     */
    public function getConceptosAsociados($id)
    {
        $sql = "SELECT COUNT(*) as count FROM conceptos_acumulados WHERE tipo_acumulado_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'];
    }

    /**
     * Obtener tipos de acumulados activos para un concepto
     */
    public function getTiposForConcepto($conceptoId)
    {
        $sql = "
            SELECT 
                ta.id,
                ta.codigo,
                ta.descripcion,
                ta.periodicidad,
                COALESCE(ca.factor_acumulacion, 1.0000) as factor_acumulacion,
                COALESCE(ca.incluir_en_acumulado, 0) as incluir_en_acumulado,
                ca.observaciones
            FROM {$this->table} ta
            LEFT JOIN conceptos_acumulados ca ON ta.id = ca.tipo_acumulado_id AND ca.concepto_id = ?
            WHERE ta.activo = 1
            ORDER BY ta.codigo ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$conceptoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener acumulados actuales de un empleado
     */
    public function getAcumuladosEmpleado($empleadoId, $tipoAcumuladoId = null)
    {
        $whereClause = $tipoAcumuladoId ? "AND eah.tipo_acumulado_id = ?" : "";
        $params = [$empleadoId];
        
        if ($tipoAcumuladoId) {
            $params[] = $tipoAcumuladoId;
        }

        $sql = "
            SELECT 
                ta.codigo,
                ta.descripcion,
                ta.periodicidad,
                MAX(eah.periodo_acumulado) as periodo_actual,
                MAX(eah.monto_acumulado_actual) as monto_actual,
                COUNT(eah.id) as planillas_procesadas,
                MAX(eah.fecha_planilla) as ultima_planilla
            FROM empleados_acumulados_historicos eah
            INNER JOIN tipos_acumulados ta ON eah.tipo_acumulado_id = ta.id
            WHERE eah.empleado_id = ? {$whereClause}
            AND ta.activo = 1
            GROUP BY ta.id, ta.codigo, ta.descripcion, ta.periodicidad
            ORDER BY ta.codigo
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}