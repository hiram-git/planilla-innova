<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Custom Query Builder optimized for Payroll System
 *
 * Features:
 * - Fluent interface for complex payroll queries
 * - Multi-database support (MySQL, PostgreSQL)
 * - Performance optimized for 5-1000+ employees
 * - Seamless integration with existing PDO Database class
 * - Payroll-specific aggregation methods
 *
 * @version 1.0
 * @author Claude Code Assistant
 */
class QueryBuilder extends Database
{
    protected $table;
    protected $select = ['*'];
    protected $joins = [];
    protected $wheres = [];
    protected $whereBindings = [];
    protected $groups = [];
    protected $havings = [];
    protected $orders = [];
    protected $limitCount;
    protected $offsetCount;
    protected $unions = [];

    // Database adapter for multi-DB support
    protected $adapter;

    public function __construct()
    {
        parent::__construct();
        $this->initializeAdapter();
    }

    /**
     * Initialize database adapter based on current driver
     */
    private function initializeAdapter()
    {
        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);

        switch ($driver) {
            case 'mysql':
                $this->adapter = new \App\Core\Adapters\MySQLAdapter();
                break;
            case 'pgsql':
                $this->adapter = new \App\Core\Adapters\PostgreSQLAdapter();
                break;
            default:
                $this->adapter = new \App\Core\Adapters\MySQLAdapter(); // Default
        }
    }

    /**
     * Set the table for the query
     */
    public function table($table)
    {
        $this->resetQuery();
        $this->table = $table;
        return $this;
    }

    /**
     * Add SELECT columns
     */
    public function select($columns = ['*'])
    {
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        }

        $this->select = array_map('trim', $columns);
        return $this;
    }

    /**
     * Add raw SELECT expression
     */
    public function selectRaw($expression, $bindings = [])
    {
        $this->select[] = $expression;
        $this->whereBindings = array_merge($this->whereBindings, $bindings);
        return $this;
    }

    /**
     * Add JOIN clause
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'INNER')
    {
        if ($operator === null && $second === null) {
            // Assume $first is a closure or complete join condition
            $this->joins[] = "$type JOIN $table ON $first";
        } else {
            $this->joins[] = "$type JOIN $table ON $first $operator $second";
        }

        return $this;
    }

    /**
     * Add LEFT JOIN clause
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add RIGHT JOIN clause
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add WHERE clause
     */
    public function where($column, $operator = null, $value = null)
    {
        if ($operator === null) {
            // Assume $column is a raw WHERE condition
            $this->wheres[] = "AND $column";
        } else if ($value === null) {
            // $operator is actually the value, assume equality
            $this->wheres[] = "AND $column = ?";
            $this->whereBindings[] = $operator;
        } else {
            $this->wheres[] = "AND $column $operator ?";
            $this->whereBindings[] = $value;
        }

        return $this;
    }

    /**
     * Add OR WHERE clause
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        if ($operator === null) {
            $this->wheres[] = "OR $column";
        } else if ($value === null) {
            $this->wheres[] = "OR $column = ?";
            $this->whereBindings[] = $operator;
        } else {
            $this->wheres[] = "OR $column $operator ?";
            $this->whereBindings[] = $value;
        }

        return $this;
    }

    /**
     * Add WHERE IN clause
     */
    public function whereIn($column, $values)
    {
        if (empty($values)) {
            $this->wheres[] = "AND 1 = 0"; // Always false
            return $this;
        }

        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        $this->wheres[] = "AND $column IN ($placeholders)";
        $this->whereBindings = array_merge($this->whereBindings, $values);

        return $this;
    }

    /**
     * Add WHERE NOT IN clause
     */
    public function whereNotIn($column, $values)
    {
        if (empty($values)) {
            return $this; // No restriction
        }

        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        $this->wheres[] = "AND $column NOT IN ($placeholders)";
        $this->whereBindings = array_merge($this->whereBindings, $values);

        return $this;
    }

    /**
     * Add WHERE BETWEEN clause
     */
    public function whereBetween($column, $values)
    {
        $this->wheres[] = "AND $column BETWEEN ? AND ?";
        $this->whereBindings[] = $values[0];
        $this->whereBindings[] = $values[1];

        return $this;
    }

    /**
     * Add WHERE NULL clause
     */
    public function whereNull($column)
    {
        $this->wheres[] = "AND $column IS NULL";
        return $this;
    }

    /**
     * Add WHERE NOT NULL clause
     */
    public function whereNotNull($column)
    {
        $this->wheres[] = "AND $column IS NOT NULL";
        return $this;
    }

    /**
     * Add GROUP BY clause
     */
    public function groupBy($columns)
    {
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        }

        $this->groups = array_merge($this->groups, array_map('trim', $columns));
        return $this;
    }

    /**
     * Add HAVING clause
     */
    public function having($column, $operator, $value)
    {
        $this->havings[] = "AND $column $operator ?";
        $this->whereBindings[] = $value;
        return $this;
    }

    /**
     * Add ORDER BY clause
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $direction = strtoupper($direction);
        $this->orders[] = "$column $direction";
        return $this;
    }

    /**
     * Add LIMIT clause
     */
    public function limit($count, $offset = 0)
    {
        $this->limitCount = $count;
        $this->offsetCount = $offset;
        return $this;
    }

    /**
     * Execute query and return all results
     */
    public function get()
    {
        $query = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($query);
        $stmt->execute($this->whereBindings);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute query and return first result
     */
    public function first()
    {
        $originalLimit = $this->limitCount;
        $this->limit(1);

        $query = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($query);
        $stmt->execute($this->whereBindings);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->limitCount = $originalLimit;

        return $result ?: null;
    }

    /**
     * Get count of records
     */
    public function count($column = '*')
    {
        $originalSelect = $this->select;
        $this->select = ["COUNT($column) as count"];

        $query = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($query);
        $stmt->execute($this->whereBindings);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->select = $originalSelect;

        return (int) $result['count'];
    }

    /**
     * Get sum of column
     */
    public function sum($column)
    {
        $originalSelect = $this->select;
        $this->select = ["SUM($column) as sum"];

        $query = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($query);
        $stmt->execute($this->whereBindings);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->select = $originalSelect;

        return (float) ($result['sum'] ?? 0);
    }

    /**
     * Get average of column
     */
    public function avg($column)
    {
        $originalSelect = $this->select;
        $this->select = ["AVG($column) as avg"];

        $query = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($query);
        $stmt->execute($this->whereBindings);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->select = $originalSelect;

        return (float) ($result['avg'] ?? 0);
    }

    /**
     * Get minimum value of column
     */
    public function min($column)
    {
        $originalSelect = $this->select;
        $this->select = ["MIN($column) as min"];

        $query = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($query);
        $stmt->execute($this->whereBindings);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->select = $originalSelect;

        return $result['min'];
    }

    /**
     * Get maximum value of column
     */
    public function max($column)
    {
        $originalSelect = $this->select;
        $this->select = ["MAX($column) as max"];

        $query = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($query);
        $stmt->execute($this->whereBindings);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->select = $originalSelect;

        return $result['max'];
    }

    // ========================================
    // PAYROLL-SPECIFIC OPTIMIZED METHODS
    // ========================================

    /**
     * Get monthly payroll summary for reporting
     */
    public function monthlyPayrollSummary($year, $month = null)
    {
        $query = $this->table('planilla_cabecera pc')
            ->leftJoin('planilla_detalle pd', 'pc.id', '=', 'pd.planilla_cabecera_id')
            ->leftJoin('concepto c', 'pd.concepto_id', '=', 'c.id')
            ->leftJoin('tipos_planilla tp', 'pc.tipo_planilla_id', '=', 'tp.id')
            ->select([
                'pc.id as planilla_id',
                'pc.periodo',
                'pc.fecha_inicio',
                'pc.fecha_fin',
                'tp.nombre as tipo_planilla'
            ])
            ->selectRaw('COUNT(DISTINCT pd.employee_id) as total_empleados')
            ->selectRaw('SUM(CASE WHEN c.tipo_concepto = \'ASIGNACION\' THEN pd.monto ELSE 0 END) as total_asignaciones')
            ->selectRaw('SUM(CASE WHEN c.tipo_concepto = \'DEDUCCION\' THEN pd.monto ELSE 0 END) as total_deducciones')
            ->selectRaw('SUM(CASE WHEN c.tipo_concepto = \'ASIGNACION\' THEN pd.monto ELSE -pd.monto END) as total_neto')
            ->selectRaw('YEAR(pc.fecha_inicio) as year')
            ->selectRaw('MONTH(pc.fecha_inicio) as month')
            ->where('YEAR(pc.fecha_inicio)', $year);

        if ($month !== null) {
            $query->where('MONTH(pc.fecha_inicio)', $month);
        }

        return $query->groupBy(['pc.id', 'pc.periodo', 'pc.fecha_inicio', 'pc.fecha_fin', 'tp.nombre'])
            ->orderBy('pc.fecha_inicio', 'DESC')
            ->get();
    }

    /**
     * Get employee payroll history with performance optimization
     */
    public function employeePayrollHistory($employeeId, $limit = 12)
    {
        return $this->table('planilla_detalle pd')
            ->leftJoin('planilla_cabecera pc', 'pd.planilla_cabecera_id', '=', 'pc.id')
            ->leftJoin('concepto c', 'pd.concepto_id', '=', 'c.id')
            ->leftJoin('tipos_planilla tp', 'pc.tipo_planilla_id', '=', 'tp.id')
            ->select([
                'pc.periodo',
                'pc.fecha_inicio',
                'pc.fecha_fin',
                'tp.nombre as tipo_planilla',
                'c.concepto',
                'c.descripcion',
                'c.tipo_concepto',
                'pd.monto',
                'pd.tipo'
            ])
            ->where('pd.employee_id', $employeeId)
            ->orderBy('pc.fecha_inicio', 'DESC')
            ->orderBy('c.tipo_concepto')
            ->orderBy('c.concepto')
            ->limit($limit)
            ->get();
    }

    /**
     * Get XIII Month calculation data for multiple employees
     */
    public function xiiiMonthCalculationData($year, $employeeIds = null)
    {
        $query = $this->table('planilla_detalle pd')
            ->leftJoin('planilla_cabecera pc', 'pd.planilla_cabecera_id', '=', 'pc.id')
            ->leftJoin('concepto c', 'pd.concepto_id', '=', 'c.id')
            ->leftJoin('employees e', 'pd.employee_id', '=', 'e.id')
            ->select([
                'pd.employee_id',
                'e.firstname',
                'e.lastname',
                'e.employee_id as employee_code'
            ])
            ->selectRaw('SUM(pd.monto) as total_salario_anual')
            ->selectRaw('COUNT(DISTINCT pc.id) as planillas_procesadas')
            ->selectRaw('MIN(pc.fecha_inicio) as primera_planilla')
            ->selectRaw('MAX(pc.fecha_fin) as ultima_planilla')
            ->where('c.tipo_concepto', 'ASIGNACION')
            ->where('c.tipo_acumulado', 'XIII_MES')
            ->where('YEAR(pc.fecha_inicio)', $year)
            ->groupBy(['pd.employee_id', 'e.firstname', 'e.lastname', 'e.employee_id'])
            ->having('planillas_procesadas', '>=', 11); // Mínimo 11 meses

        if ($employeeIds !== null) {
            $query->whereIn('pd.employee_id', $employeeIds);
        }

        return $query->orderBy('e.firstname')->get();
    }

    /**
     * Get concept usage statistics for optimization
     */
    public function conceptUsageStatistics($startDate = null, $endDate = null)
    {
        $query = $this->table('planilla_detalle pd')
            ->leftJoin('concepto c', 'pd.concepto_id', '=', 'c.id')
            ->leftJoin('planilla_cabecera pc', 'pd.planilla_cabecera_id', '=', 'pc.id')
            ->select([
                'c.id',
                'c.concepto',
                'c.descripcion',
                'c.tipo_concepto',
                'c.tipo_acumulado'
            ])
            ->selectRaw('COUNT(pd.id) as veces_usado')
            ->selectRaw('COUNT(DISTINCT pd.employee_id) as empleados_afectados')
            ->selectRaw('SUM(pd.monto) as monto_total')
            ->selectRaw('AVG(pd.monto) as monto_promedio')
            ->selectRaw('MIN(pd.monto) as monto_minimo')
            ->selectRaw('MAX(pd.monto) as monto_maximo');

        if ($startDate && $endDate) {
            $query->whereBetween('pc.fecha_inicio', [$startDate, $endDate]);
        }

        return $query->groupBy(['c.id', 'c.concepto', 'c.descripcion', 'c.tipo_concepto', 'c.tipo_acumulado'])
            ->orderBy('veces_usado', 'DESC')
            ->get();
    }

    /**
     * Get employees with liquidation pending or completed
     */
    public function liquidationEmployeesStatus()
    {
        return $this->table('employees e')
            ->leftJoin('employee_terminations et', 'e.id', '=', 'et.employee_id')
            ->leftJoin('admin a', 'et.approved_by', '=', 'a.id')
            ->select([
                'e.id',
                'e.employee_id',
                'e.firstname',
                'e.lastname',
                'e.document_id',
                'e.fecha_ingreso',
                'e.situacion_id',
                'et.termination_date',
                'et.termination_reason',
                'et.status as liquidation_status',
                'a.name as approved_by_name'
            ])
            ->selectRaw('DATEDIFF(IFNULL(et.termination_date, CURDATE()), e.fecha_ingreso) as dias_trabajados')
            ->selectRaw('ROUND(DATEDIFF(IFNULL(et.termination_date, CURDATE()), e.fecha_ingreso) / 365.25, 2) as anos_trabajados')
            ->where(function($query) {
                $query->where('e.situacion_id', '!=', 1)
                      ->orWhereNotNull('et.id');
            })
            ->orderBy('et.created_at', 'DESC')
            ->get();
    }

    /**
     * Get department payroll costs breakdown
     */
    public function departmentPayrollCosts($planillaId)
    {
        return $this->table('planilla_detalle pd')
            ->leftJoin('employees e', 'pd.employee_id', '=', 'e.id')
            ->leftJoin('posiciones p', 'e.position_id', '=', 'p.id')
            ->leftJoin('departamentos d', 'p.department_id', '=', 'd.id')
            ->leftJoin('concepto c', 'pd.concepto_id', '=', 'c.id')
            ->select([
                'd.id as department_id',
                'd.nombre as department_name'
            ])
            ->selectRaw('COUNT(DISTINCT e.id) as total_empleados')
            ->selectRaw('SUM(CASE WHEN c.tipo_concepto = \'ASIGNACION\' THEN pd.monto ELSE 0 END) as total_asignaciones')
            ->selectRaw('SUM(CASE WHEN c.tipo_concepto = \'DEDUCCION\' THEN pd.monto ELSE 0 END) as total_deducciones')
            ->selectRaw('SUM(CASE WHEN c.tipo_concepto = \'ASIGNACION\' THEN pd.monto ELSE -pd.monto END) as total_neto')
            ->selectRaw('AVG(CASE WHEN c.tipo_concepto = \'ASIGNACION\' THEN pd.monto ELSE 0 END) as promedio_asignaciones')
            ->where('pd.planilla_cabecera_id', $planillaId)
            ->groupBy(['d.id', 'd.nombre'])
            ->orderBy('total_neto', 'DESC')
            ->get();
    }

    /**
     * Get top earning employees for period
     */
    public function topEarningEmployees($planillaId, $limit = 10)
    {
        return $this->table('planilla_detalle pd')
            ->leftJoin('employees e', 'pd.employee_id', '=', 'e.id')
            ->leftJoin('concepto c', 'pd.concepto_id', '=', 'c.id')
            ->leftJoin('posiciones p', 'e.position_id', '=', 'p.id')
            ->select([
                'e.id',
                'e.employee_id',
                'e.firstname',
                'e.lastname',
                'p.nombre as position_name'
            ])
            ->selectRaw('SUM(CASE WHEN c.tipo_concepto = \'ASIGNACION\' THEN pd.monto ELSE 0 END) as total_asignaciones')
            ->selectRaw('SUM(CASE WHEN c.tipo_concepto = \'DEDUCCION\' THEN pd.monto ELSE 0 END) as total_deducciones')
            ->selectRaw('SUM(CASE WHEN c.tipo_concepto = \'ASIGNACION\' THEN pd.monto ELSE -pd.monto END) as salario_neto')
            ->where('pd.planilla_cabecera_id', $planillaId)
            ->groupBy(['e.id', 'e.employee_id', 'e.firstname', 'e.lastname', 'p.nombre'])
            ->orderBy('salario_neto', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get payroll processing performance metrics
     */
    public function payrollProcessingMetrics($startDate, $endDate)
    {
        return $this->table('planilla_cabecera pc')
            ->leftJoin('planilla_detalle pd', 'pc.id', '=', 'pd.planilla_cabecera_id')
            ->leftJoin('tipos_planilla tp', 'pc.tipo_planilla_id', '=', 'tp.id')
            ->select([
                'tp.nombre as tipo_planilla',
                'pc.estado as planilla_status'
            ])
            ->selectRaw('COUNT(DISTINCT pc.id) as total_planillas')
            ->selectRaw('COUNT(DISTINCT pd.employee_id) as total_empleados_procesados')
            ->selectRaw('COUNT(pd.id) as total_registros_detalle')
            ->selectRaw('SUM(pd.monto) as monto_total_procesado')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, pc.created_at, pc.updated_at)) as tiempo_promedio_procesamiento')
            ->whereBetween('pc.created_at', [$startDate, $endDate])
            ->groupBy(['tp.nombre', 'pc.estado'])
            ->orderBy('tp.nombre')
            ->get();
    }

    /**
     * Get accumulated vacation balance for employees
     */
    public function vacationBalanceReport($year = null)
    {
        $query = $this->table('acumulados_por_empleado ape')
            ->leftJoin('employees e', 'ape.employee_id', '=', 'e.id')
            ->leftJoin('concepto c', 'ape.concepto_id', '=', 'c.id')
            ->leftJoin('posiciones p', 'e.position_id', '=', 'p.id')
            ->select([
                'e.id',
                'e.employee_id',
                'e.firstname',
                'e.lastname',
                'e.fecha_ingreso',
                'p.nombre as position_name'
            ])
            ->selectRaw('SUM(ape.total_acumulado) as dias_vacaciones_acumulados')
            ->selectRaw('ROUND(DATEDIFF(CURDATE(), e.fecha_ingreso) / 365.25, 2) as anos_trabajados')
            ->selectRaw('ROUND(DATEDIFF(CURDATE(), e.fecha_ingreso) / 365.25 * 30, 0) as dias_ganados_total')
            ->where('c.tipo_acumulado', 'VACACIONES')
            ->where('e.situacion_id', 1);

        if ($year) {
            $query->where('YEAR(ape.periodo)', $year);
        }

        return $query->groupBy(['e.id', 'e.employee_id', 'e.firstname', 'e.lastname', 'e.fecha_ingreso', 'p.nombre'])
            ->orderBy('dias_vacaciones_acumulados', 'DESC')
            ->get();
    }

    /**
     * Get payroll summary for specific employee and period
     * Optimized for frequent payroll calculations
     */
    public function payrollSummary($employeeId, $period)
    {
        return $this->table('planilla_detalle pd')
            ->leftJoin('concepto c', 'pd.concepto_id', '=', 'c.id')
            ->leftJoin('employees e', 'pd.employee_id', '=', 'e.id')
            ->select([
                'c.concepto',
                'c.descripcion',
                'c.tipo_concepto',
                'pd.monto',
                'pd.tipo',
                'e.firstname',
                'e.lastname'
            ])
            ->where('pd.employee_id', $employeeId)
            ->where('pd.periodo', $period)
            ->orderBy('c.tipo_concepto')
            ->orderBy('c.concepto')
            ->get();
    }

    /**
     * Get accumulated values by type for date range
     * Critical for XIII month, vacation, and seniority calculations
     */
    public function acumuladosByType($tipo, $startDate, $endDate, $employeeId = null)
    {
        $query = $this->table('planilla_detalle pd')
            ->leftJoin('concepto c', 'pd.concepto_id', '=', 'c.id')
            ->leftJoin('employees e', 'pd.employee_id', '=', 'e.id')
            ->select([
                'pd.employee_id',
                'e.firstname',
                'e.lastname',
                'e.employee_id as employee_code'
            ])
            ->selectRaw('SUM(pd.monto) as total_acumulado')
            ->selectRaw('COUNT(pd.id) as total_registros')
            ->where('c.tipo_acumulado', $tipo)
            ->whereBetween('pd.created_at', [$startDate, $endDate])
            ->groupBy(['pd.employee_id', 'e.firstname', 'e.lastname', 'e.employee_id']);

        if ($employeeId !== null) {
            $query->where('pd.employee_id', $employeeId);
        }

        return $query->orderBy('e.firstname')->get();
    }

    /**
     * Get payroll totals by type (optimized for dashboard)
     */
    public function payrollTotalsByType($payrollId)
    {
        return $this->table('planilla_detalle pd')
            ->leftJoin('concepto c', 'pd.concepto_id', '=', 'c.id')
            ->select(['c.tipo_concepto'])
            ->selectRaw('SUM(pd.monto) as total')
            ->selectRaw('COUNT(pd.id) as cantidad_conceptos')
            ->where('pd.planilla_cabecera_id', $payrollId)
            ->groupBy('c.tipo_concepto')
            ->get();
    }

    /**
     * Get employees with liquidation eligibility
     * Optimized for liquidation module
     */
    public function liquidationEligibleEmployees()
    {
        return $this->table('employees e')
            ->leftJoin('posiciones p', 'e.position_id', '=', 'p.id')
            ->select([
                'e.id',
                'e.employee_id',
                'e.firstname',
                'e.lastname',
                'e.document_id',
                'e.fecha_ingreso',
                'e.sueldo_individual',
                'p.nombre as position_name'
            ])
            ->selectRaw('DATEDIFF(CURDATE(), e.fecha_ingreso) as dias_trabajados')
            ->selectRaw('YEAR(CURDATE()) - YEAR(e.fecha_ingreso) as anos_trabajados')
            ->where('e.estado_laboral', 'ACTIVO')
            ->orderBy('e.firstname')
            ->orderBy('e.lastname')
            ->get();
    }

    /**
     * Bulk insert for payroll processing (performance optimized)
     */
    public function bulkInsert($table, $data)
    {
        if (empty($data)) {
            return true;
        }

        $keys = array_keys($data[0]);
        $columns = implode(',', $keys);
        $placeholders = '(' . str_repeat('?,', count($keys) - 1) . '?)';
        $allPlaceholders = str_repeat($placeholders . ',', count($data) - 1) . $placeholders;

        $query = "INSERT INTO $table ($columns) VALUES $allPlaceholders";

        $values = [];
        foreach ($data as $row) {
            foreach ($keys as $key) {
                $values[] = $row[$key];
            }
        }

        $stmt = $this->connection->prepare($query);
        return $stmt->execute($values);
    }

    /**
     * Bulk update for payroll status changes
     */
    public function bulkUpdate($table, $data, $whereColumn = 'id')
    {
        if (empty($data)) {
            return true;
        }

        $this->connection->beginTransaction();

        try {
            foreach ($data as $item) {
                $whereValue = $item[$whereColumn];
                unset($item[$whereColumn]);

                $setParts = [];
                $values = [];

                foreach ($item as $column => $value) {
                    $setParts[] = "$column = ?";
                    $values[] = $value;
                }

                $values[] = $whereValue;
                $setClause = implode(', ', $setParts);
                $query = "UPDATE $table SET $setClause WHERE $whereColumn = ?";

                $stmt = $this->connection->prepare($query);
                $stmt->execute($values);
            }

            $this->connection->commit();
            return true;

        } catch (PDOException $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    // ========================================
    // INTERNAL QUERY BUILDING METHODS
    // ========================================

    /**
     * Build complete SELECT query
     */
    protected function buildSelectQuery()
    {
        $query = 'SELECT ' . implode(', ', $this->select);
        $query .= ' FROM ' . $this->table;

        if (!empty($this->joins)) {
            $query .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $query .= ' WHERE ' . ltrim(implode(' ', $this->wheres), 'AND ');
        }

        if (!empty($this->groups)) {
            $query .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        if (!empty($this->havings)) {
            $query .= ' HAVING ' . ltrim(implode(' ', $this->havings), 'AND ');
        }

        if (!empty($this->orders)) {
            $query .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limitCount !== null) {
            $query .= $this->adapter->buildLimitClause($this->limitCount, $this->offsetCount);
        }

        return $query;
    }

    /**
     * Reset query builder state
     */
    protected function resetQuery()
    {
        $this->table = null;
        $this->select = ['*'];
        $this->joins = [];
        $this->wheres = [];
        $this->whereBindings = [];
        $this->groups = [];
        $this->havings = [];
        $this->orders = [];
        $this->limitCount = null;
        $this->offsetCount = null;
    }

    /**
     * Create raw expression for complex SQL
     */
    public function raw($expression)
    {
        return $expression;
    }

    /**
     * Debug current query
     */
    public function toSql()
    {
        return $this->buildSelectQuery();
    }

    /**
     * Debug current bindings
     */
    public function getBindings()
    {
        return $this->whereBindings;
    }

    // ========================================
    // CRUD OPERATIONS (INSERT, UPDATE, DELETE)
    // ========================================

    /**
     * Insert data into table
     */
    public function insert($data)
    {
        if (empty($data)) {
            return false;
        }

        // Handle single row or multiple rows
        if (!isset($data[0])) {
            $data = [$data];
        }

        return $this->bulkInsert($this->table, $data);
    }

    /**
     * Insert data and return last insert ID
     */
    public function insertGetId($data)
    {
        $result = $this->insert($data);
        if ($result) {
            return $this->connection->lastInsertId();
        }
        return false;
    }

    /**
     * Update records matching current WHERE conditions
     */
    public function update($data)
    {
        if (empty($data) || empty($this->wheres)) {
            return false;
        }

        $setParts = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setParts[] = "{$this->adapter->quoteIdentifier($column)} = ?";
            $values[] = $value;
        }

        // Combine update values with where bindings
        $allBindings = array_merge($values, $this->whereBindings);
        $setClause = implode(', ', $setParts);
        $whereClause = ltrim(implode(' ', $this->wheres), 'AND ');

        $query = "UPDATE {$this->adapter->quoteIdentifier($this->table)} SET $setClause WHERE $whereClause";

        $stmt = $this->connection->prepare($query);
        return $stmt->execute($allBindings);
    }

    /**
     * Delete records matching current WHERE conditions
     */
    public function delete()
    {
        if (empty($this->wheres)) {
            throw new \Exception('DELETE queries must have WHERE conditions for safety');
        }

        $whereClause = ltrim(implode(' ', $this->wheres), 'AND ');
        $query = "DELETE FROM {$this->adapter->quoteIdentifier($this->table)} WHERE $whereClause";

        $stmt = $this->connection->prepare($query);
        return $stmt->execute($this->whereBindings);
    }

    /**
     * Upsert (INSERT ... ON DUPLICATE KEY UPDATE for MySQL, INSERT ... ON CONFLICT for PostgreSQL)
     */
    public function upsert($data, $uniqueColumns = ['id'])
    {
        if (empty($data)) {
            return false;
        }

        // Handle single row
        if (!isset($data[0])) {
            $data = [$data];
        }

        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            return $this->mysqlUpsert($data, $uniqueColumns);
        } elseif ($driver === 'pgsql') {
            return $this->postgresqlUpsert($data, $uniqueColumns);
        } else {
            // Fallback to regular insert
            return $this->bulkInsert($this->table, $data);
        }
    }

    /**
     * MySQL-specific UPSERT implementation
     */
    private function mysqlUpsert($data, $uniqueColumns)
    {
        $keys = array_keys($data[0]);
        $quotedTable = $this->adapter->quoteIdentifier($this->table);
        $quotedColumns = array_map([$this->adapter, 'quoteIdentifier'], $keys);
        $columnList = implode(',', $quotedColumns);

        // Build VALUES clause
        $placeholders = '(' . str_repeat('?,', count($keys) - 1) . '?)';
        $valuesList = str_repeat($placeholders . ',', count($data) - 1) . $placeholders;

        // Build ON DUPLICATE KEY UPDATE clause
        $updateParts = [];
        foreach ($keys as $key) {
            if (!in_array($key, $uniqueColumns)) {
                $quotedKey = $this->adapter->quoteIdentifier($key);
                $updateParts[] = "$quotedKey = VALUES($quotedKey)";
            }
        }
        $updateClause = implode(', ', $updateParts);

        $query = "INSERT INTO $quotedTable ($columnList) VALUES $valuesList";
        if (!empty($updateParts)) {
            $query .= " ON DUPLICATE KEY UPDATE $updateClause";
        }

        // Flatten data for binding
        $values = [];
        foreach ($data as $row) {
            foreach ($keys as $key) {
                $values[] = $row[$key];
            }
        }

        $stmt = $this->connection->prepare($query);
        return $stmt->execute($values);
    }

    /**
     * PostgreSQL-specific UPSERT implementation
     */
    private function postgresqlUpsert($data, $uniqueColumns)
    {
        $keys = array_keys($data[0]);
        $quotedTable = $this->adapter->quoteIdentifier($this->table);
        $quotedColumns = array_map([$this->adapter, 'quoteIdentifier'], $keys);
        $columnList = implode(',', $quotedColumns);

        // Build VALUES clause with unnest() for PostgreSQL efficiency
        $unnestColumns = [];
        foreach ($keys as $i => $key) {
            $unnestColumns[] = "unnest($" . ($i + 1) . "::text[]) AS " . $this->adapter->quoteIdentifier($key);
        }

        // Build conflict resolution
        $quotedUniqueColumns = array_map([$this->adapter, 'quoteIdentifier'], $uniqueColumns);
        $conflictTarget = implode(',', $quotedUniqueColumns);

        $updateParts = [];
        foreach ($keys as $key) {
            if (!in_array($key, $uniqueColumns)) {
                $quotedKey = $this->adapter->quoteIdentifier($key);
                $updateParts[] = "$quotedKey = EXCLUDED.$quotedKey";
            }
        }

        $query = "INSERT INTO $quotedTable ($columnList) SELECT " . implode(', ', $unnestColumns);
        if (!empty($updateParts)) {
            $updateClause = implode(', ', $updateParts);
            $query .= " ON CONFLICT ($conflictTarget) DO UPDATE SET $updateClause";
        } else {
            $query .= " ON CONFLICT ($conflictTarget) DO NOTHING";
        }

        // Prepare data arrays for unnest()
        $dataArrays = [];
        foreach ($keys as $key) {
            $dataArrays[] = array_column($data, $key);
        }

        $stmt = $this->connection->prepare($query);
        return $stmt->execute($dataArrays);
    }

    /**
     * Increment a column value
     */
    public function increment($column, $amount = 1)
    {
        $quotedColumn = $this->adapter->quoteIdentifier($column);
        return $this->update([$column => $this->raw("$quotedColumn + $amount")]);
    }

    /**
     * Decrement a column value
     */
    public function decrement($column, $amount = 1)
    {
        $quotedColumn = $this->adapter->quoteIdentifier($column);
        return $this->update([$column => $this->raw("$quotedColumn - $amount")]);
    }

    /**
     * Check if records exist matching current WHERE conditions
     */
    public function exists()
    {
        return $this->count() > 0;
    }

    /**
     * Create a new record if it doesn't exist, otherwise return existing
     */
    public function firstOrCreate($attributes, $values = [])
    {
        // Try to find existing record
        foreach ($attributes as $column => $value) {
            $this->where($column, $value);
        }

        $existing = $this->first();
        if ($existing) {
            return $existing;
        }

        // Create new record
        $data = array_merge($attributes, $values);
        $id = $this->insertGetId($data);

        if ($id) {
            // Return the created record
            return $this->table($this->table)->where('id', $id)->first();
        }

        return false;
    }

    /**
     * Update existing record or create new one
     */
    public function updateOrCreate($attributes, $values = [])
    {
        foreach ($attributes as $column => $value) {
            $this->where($column, $value);
        }

        $existing = $this->first();
        if ($existing) {
            // Update existing
            $this->update($values);
            return array_merge($existing, $values);
        }

        // Create new
        $data = array_merge($attributes, $values);
        $id = $this->insertGetId($data);

        if ($id) {
            return $this->table($this->table)->where('id', $id)->first();
        }

        return false;
    }
}