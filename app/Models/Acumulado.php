<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

/**
 * Acumulado Model
 * Modelo para gestión de acumulados por empleado
 */
class Acumulado
{
    private $db;
    private $table = 'acumulados_por_empleado';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener resumen de acumulados por tipo y año
     *
     * @param int $year Año a consultar
     * @param int|null $tipoPlanillaId ID del tipo de planilla para filtrar empleados (opcional)
     * @return array
     */
    public function getAcumuladosByTipoAndYear($year, $tipoPlanillaId = null)
    {
        try {
            $whereConditions = ["ape.ano = ?"];
            $params = [$year];

            // Filtrar por tipo de planilla del empleado si se proporciona
            // Soporte para múltiples tipos de planilla separados por comas
            if ($tipoPlanillaId && is_numeric($tipoPlanillaId)) {
                $whereConditions[] = "FIND_IN_SET(?, e.tipo_planilla_id)";
                $params[] = (int)$tipoPlanillaId;
            }

            $whereClause = implode(" AND ", $whereConditions);

            $sql = "
                SELECT
                    ape.tipo_acumulado AS tipo_codigo,
                    COALESCE(c.descripcion, CONCAT('Tipo: ', ape.tipo_acumulado)) AS descripcion,
                    SUM(ape.monto) AS total_acumulado,
                    COUNT(DISTINCT ape.employee_id) AS total_empleados,
                    COUNT(DISTINCT ape.concepto_id) AS total_conceptos_incluidos,
                    GROUP_CONCAT(DISTINCT c.descripcion ORDER BY c.descripcion SEPARATOR ', ') AS conceptos_incluidos
                FROM {$this->table} ape
                INNER JOIN employees e ON ape.employee_id = e.id
                LEFT JOIN tipos_acumulados ta ON ape.tipo_acumulado = ta.codigo
                LEFT JOIN concepto c ON ape.concepto_id = c.id
                WHERE {$whereClause}
                  AND ape.tipo_acumulado IS NOT NULL
                  AND ape.tipo_acumulado != ''
                GROUP BY ape.tipo_acumulado, c.descripcion
                ORDER BY ape.tipo_acumulado
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("getAcumuladosByTipoAndYear - Year: $year, TipoPlanillaId: " . ($tipoPlanillaId ?? 'null') . ", Results: " . count($results));

            return $results;
        } catch (\PDOException $e) {
            error_log("Error en getAcumuladosByTipoAndYear: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados agrupados por concepto para un año específico
     *
     * @param int $year Año a consultar
     * @param int|null $tipoPlanillaId ID del tipo de planilla para filtrar empleados (opcional)
     * @return array
     */
    public function getAcumuladosByConceptoAndYear($year, $tipoPlanillaId = null)
    {
        try {
            $whereConditions = ["ape.ano = ?"];
            $params = [$year];

            // Filtrar por tipo de planilla del empleado si se proporciona
            // Soporte para múltiples tipos de planilla separados por comas
            if ($tipoPlanillaId && is_numeric($tipoPlanillaId)) {
                $whereConditions[] = "FIND_IN_SET(?, e.tipo_planilla_id)";
                $params[] = (int)$tipoPlanillaId;
            }

            $whereClause = implode(" AND ", $whereConditions);

            // Si hay filtro por tipo de planilla, agregar LEFT JOIN con concepto_tipos_planilla
            // Lógica: mostrar conceptos que están asociados al tipo O que no tienen asociaciones (aplican a todos)
            $conceptoJoin = "";
            $conceptoFilter = "";
            if ($tipoPlanillaId && is_numeric($tipoPlanillaId)) {
                $conceptoJoin = "LEFT JOIN concepto_tipos_planilla ctp ON c.id = ctp.concepto_id";
                $conceptoFilter = " AND (ctp.tipo_planilla_id = ? OR ctp.tipo_planilla_id IS NULL)";
                $params[] = (int)$tipoPlanillaId;
            }

            $sql = "
                SELECT
                    ape.concepto_id,
                    c.concepto AS concepto_codigo,
                    c.descripcion,
                    c.tipo_concepto,
                    SUM(ape.monto) AS total_acumulado,
                    COUNT(DISTINCT ape.employee_id) AS total_empleados,
                    COUNT(DISTINCT ape.planilla_id) AS total_planillas,
                    ape.tipo_acumulado
                FROM {$this->table} ape
                INNER JOIN employees e ON ape.employee_id = e.id
                INNER JOIN concepto c ON ape.concepto_id = c.id
                {$conceptoJoin}
                WHERE {$whereClause}
                  AND ape.concepto_id IS NOT NULL
                  {$conceptoFilter}
                GROUP BY ape.concepto_id, c.concepto, c.descripcion, c.tipo_concepto, ape.tipo_acumulado
                ORDER BY c.descripcion
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            

            error_log("getAcumuladosByConceptoAndYear - Year: $year, TipoPlanillaId: " . ($tipoPlanillaId ?? 'null') . ", Results: " . count($results));

            return $results;
        } catch (\PDOException $e) {
            error_log("Error en getAcumuladosByConceptoAndYear: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener empleados con acumulados en un año específico
     *
     * @param int $year Año a consultar
     * @param int|null $tipoPlanillaId ID del tipo de planilla para filtrar empleados (opcional)
     * @return array
     */
    public function getEmployeesWithAcumulados($year, $tipoPlanillaId = null)
    {
        try {
            $whereConditions = ["ape.ano = ?"];
            $params = [$year];

            // Filtrar por tipo de planilla del empleado si se proporciona
            // Soporte para múltiples tipos de planilla separados por comas
            if ($tipoPlanillaId && is_numeric($tipoPlanillaId)) {
                $whereConditions[] = "FIND_IN_SET(?, e.tipo_planilla_id)";
                $params[] = (int)$tipoPlanillaId;
            }

            $whereClause = implode(" AND ", $whereConditions);

            $sql = "
                SELECT
                    e.id as employee_id,
                    e.document_id,
                    CONCAT(e.firstname, ' ', e.lastname) as nombre_completo,
                    COUNT(DISTINCT ape.tipo_acumulado) as tipos_acumulados,
                    SUM(CASE WHEN ape.tipo_concepto = 'ASIGNACION' THEN ape.monto ELSE 0 END) as total_asignaciones,
                    SUM(CASE WHEN ape.tipo_concepto = 'DEDUCCION' THEN ape.monto ELSE 0 END) as total_deducciones,
                    SUM(ape.monto) as total_acumulado,
                    COUNT(DISTINCT ape.planilla_id) as total_planillas,
                    MAX(ape.created_at) as ultimo_calculo
                FROM {$this->table} ape
                INNER JOIN employees e ON ape.employee_id = e.id
                WHERE {$whereClause}
                  AND ape.tipo_acumulado IS NOT NULL
                  AND ape.tipo_acumulado != ''
                GROUP BY e.id, e.document_id, e.firstname, e.lastname
                ORDER BY e.lastname, e.firstname
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en getEmployeesWithAcumulados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener años disponibles en acumulados
     *
     * @return array
     */
    public function getAvailableYears()
    {
        try {
            $sql = "
                SELECT DISTINCT ano
                FROM {$this->table}
                WHERE ano IS NOT NULL
                ORDER BY ano DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en getAvailableYears: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados de un empleado por tipo y año
     *
     * @param int $empleadoId ID del empleado
     * @param string|null $tipoAcumulado Código del tipo de acumulado
     * @param int|null $year Año a consultar
     * @return array
     */
    public function getAcumuladosByEmployee($empleadoId, $tipoAcumulado = null, $year = null)
    {
        try {
            $whereConditions = ["ape.employee_id = ?"];
            $params = [$empleadoId];

            if ($tipoAcumulado && $tipoAcumulado !== 'todos') {
                $whereConditions[] = "ape.tipo_acumulado = ?";
                $params[] = $tipoAcumulado;
            }

            if ($year && $year !== 'todos') {
                $whereConditions[] = "ape.ano = ?";
                $params[] = $year;
            }

            $whereClause = implode(" AND ", $whereConditions);

            $sql = "
                SELECT
                    c.id as concepto_id,
                    c.concepto as concepto_codigo,
                    c.descripcion as concepto_descripcion,
                    ape.tipo_concepto,
                    ape.tipo_acumulado,
                    SUM(ape.monto) as total_acumulado,
                    COUNT(ape.planilla_id) as total_planillas,
                    MIN(ape.created_at) as fecha_primer_calculo,
                    MAX(ape.created_at) as fecha_ultimo_calculo,
                    ape.frecuencia,
                    ape.ano
                FROM {$this->table} ape
                INNER JOIN concepto c ON ape.concepto_id = c.id
                WHERE {$whereClause}
                GROUP BY c.id, c.concepto, c.descripcion, ape.tipo_concepto, ape.tipo_acumulado, ape.frecuencia, ape.ano
                ORDER BY ape.tipo_concepto, ape.tipo_acumulado, c.descripcion
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en getAcumuladosByEmployee: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener total acumulado de un empleado para un tipo específico
     *
     * @param int $empleadoId ID del empleado
     * @param string $tipoAcumulado Código del tipo de acumulado
     * @param int|null $year Año a consultar (opcional, por defecto año actual)
     * @return float
     */
    public function getTotalAcumuladoByTipo($empleadoId, $tipoAcumulado, $year = null)
    {
        try {
            $year = $year ?? date('Y');

            $sql = "
                SELECT SUM(monto) as total
                FROM {$this->table}
                WHERE employee_id = ?
                  AND tipo_acumulado = ?
                  AND ano = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$empleadoId, $tipoAcumulado, $year]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'] ?? 0.0;
        } catch (\PDOException $e) {
            error_log("Error en getTotalAcumuladoByTipo: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtener acumulados de un empleado en un rango de fechas
     *
     * @param int $empleadoId ID del empleado
     * @param string $tipoAcumulado Código del tipo de acumulado
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @return float
     */
    public function getAcumuladoByDateRange($empleadoId, $tipoAcumulado, $fechaInicio, $fechaFin)
    {
        try {
            $sql = "
                SELECT SUM(ape.monto) as total
                FROM {$this->table} ape
                INNER JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                WHERE ape.employee_id = ?
                  AND ape.tipo_acumulado = ?
                  AND pc.periodo_inicio >= ?
                  AND pc.periodo_fin <= ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$empleadoId, $tipoAcumulado, $fechaInicio, $fechaFin]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'] ?? 0.0;
        } catch (\PDOException $e) {
            error_log("Error en getAcumuladoByDateRange: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtener tipos de acumulados disponibles
     *
     * @return array
     */
    public function getTiposAcumulados()
    {
        try {
            $sql = "
                SELECT DISTINCT
                    ape.tipo_acumulado as codigo,
                    COALESCE(ta.descripcion, ape.tipo_acumulado) as descripcion
                FROM {$this->table} ape
                LEFT JOIN tipos_acumulados ta ON ape.tipo_acumulado = ta.codigo
                WHERE ape.tipo_acumulado IS NOT NULL
                  AND ape.tipo_acumulado != ''
                ORDER BY ape.tipo_acumulado
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en getTiposAcumulados: " . $e->getMessage());
            return [];
        }
    }
}
