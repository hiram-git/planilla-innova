<?php

namespace App\Core\Adapters;

/**
 * MySQL Database Adapter
 *
 * Implements MySQL-specific SQL generation and optimizations
 * Optimized for payroll system performance requirements
 */
class MySQLAdapter implements DatabaseAdapter
{
    /**
     * Build MySQL LIMIT clause
     */
    public function buildLimitClause($count, $offset = 0): string
    {
        if ($offset > 0) {
            return " LIMIT $offset, $count";
        }
        return " LIMIT $count";
    }

    /**
     * MySQL uses LAST_INSERT_ID()
     */
    public function getLastInsertIdMethod(): string
    {
        return 'LAST_INSERT_ID()';
    }

    /**
     * MySQL AUTO_INCREMENT column
     */
    public function buildAutoIncrementColumn(): string
    {
        return 'INT AUTO_INCREMENT PRIMARY KEY';
    }

    /**
     * MySQL date functions
     */
    public function buildDateFunction($function): string
    {
        $functions = [
            'NOW' => 'NOW()',
            'CURRENT_DATE' => 'CURDATE()',
            'CURRENT_TIME' => 'CURTIME()',
            'CURRENT_TIMESTAMP' => 'CURRENT_TIMESTAMP',
            'YEAR' => 'YEAR',
            'MONTH' => 'MONTH',
            'DAY' => 'DAY',
            'DATEDIFF' => 'DATEDIFF'
        ];

        return $functions[strtoupper($function)] ?? $function;
    }

    /**
     * MySQL CONCAT function
     */
    public function buildConcatenation(array $columns): string
    {
        return 'CONCAT(' . implode(', ', $columns) . ')';
    }

    /**
     * MySQL ENUM field
     */
    public function buildEnumField(array $values): string
    {
        $quotedValues = array_map(function($value) {
            return "'" . addslashes($value) . "'";
        }, $values);

        return 'ENUM(' . implode(',', $quotedValues) . ')';
    }

    /**
     * MySQL boolean field (TINYINT)
     */
    public function buildBooleanField($default = false): string
    {
        $defaultValue = $default ? '1' : '0';
        return "TINYINT(1) DEFAULT $defaultValue";
    }

    /**
     * MySQL identifier quoting with backticks
     */
    public function quoteIdentifier($identifier): string
    {
        // Handle table.column format
        if (strpos($identifier, '.') !== false) {
            $parts = explode('.', $identifier);
            return '`' . implode('`.`', $parts) . '`';
        }

        return '`' . $identifier . '`';
    }

    /**
     * MySQL-specific query optimizations for payroll system
     */
    public function getQueryOptimizations(): array
    {
        return [
            // Index hints for large payroll tables
            'payroll_optimization' => [
                'USE INDEX (idx_employee_period)' => 'For employee-specific payroll queries',
                'USE INDEX (idx_concept_type)' => 'For concept type aggregations',
                'USE INDEX (idx_created_date)' => 'For time-based queries'
            ],

            // Query cache optimizations
            'cache_hints' => [
                'SQL_CACHE' => 'Cache frequently used summary queries',
                'SQL_NO_CACHE' => 'Skip cache for real-time calculations'
            ],

            // Performance hints for large datasets
            'performance_hints' => [
                'STRAIGHT_JOIN' => 'Force join order for complex payroll queries',
                'HIGH_PRIORITY' => 'Prioritize critical payroll calculations',
                'QUICK' => 'Fast table scans for summary reports'
            ],

            // MySQL-specific optimizations for payroll
            'payroll_specific' => [
                'group_concat_max_len' => 'SET SESSION group_concat_max_len = 1000000',
                'tmp_table_size' => 'SET SESSION tmp_table_size = 256M',
                'max_heap_table_size' => 'SET SESSION max_heap_table_size = 256M'
            ]
        ];
    }

    /**
     * Build optimized aggregation query for MySQL
     */
    public function buildPayrollAggregationQuery($employeeIds, $period): string
    {
        return "
            SELECT
                pd.employee_id,
                e.firstname,
                e.lastname,
                SUM(CASE WHEN c.tipo_concepto = 'ASIGNACION' THEN pd.monto ELSE 0 END) as total_asignaciones,
                SUM(CASE WHEN c.tipo_concepto = 'DEDUCCION' THEN pd.monto ELSE 0 END) as total_deducciones,
                SUM(CASE WHEN c.tipo_concepto = 'ASIGNACION' THEN pd.monto ELSE -pd.monto END) as total_neto
            FROM planilla_detalle pd
            USE INDEX (idx_employee_period)
            INNER JOIN employees e ON pd.employee_id = e.id
            INNER JOIN concepto c ON pd.concepto_id = c.id
            WHERE pd.employee_id IN (" . implode(',', $employeeIds) . ")
            AND pd.periodo = '$period'
            GROUP BY pd.employee_id, e.firstname, e.lastname
            ORDER BY e.firstname, e.lastname
        ";
    }

    /**
     * Build optimized XIII month calculation for MySQL
     */
    public function buildXIIIMonthQuery($employeeId, $year): string
    {
        return "
            SELECT
                SUM(pd.monto) / 12 as xiii_mes_amount,
                COUNT(DISTINCT pd.periodo) as periods_worked,
                MIN(pd.created_at) as first_period,
                MAX(pd.created_at) as last_period
            FROM planilla_detalle pd
            USE INDEX (idx_employee_date)
            INNER JOIN concepto c ON pd.concepto_id = c.id
            WHERE pd.employee_id = $employeeId
            AND YEAR(pd.created_at) = $year
            AND c.tipo_concepto = 'ASIGNACION'
            AND c.concepto NOT IN ('XIII_MES', 'PRIMA_ANTIGUEDAD')
            HAVING periods_worked >= 11
        ";
    }

    /**
     * Build bulk insert query optimized for MySQL
     */
    public function buildBulkInsertQuery($table, $columns, $dataCount): string
    {
        $columnList = implode(',', array_map([$this, 'quoteIdentifier'], $columns));
        $placeholders = '(' . str_repeat('?,', count($columns) - 1) . '?)';
        $valuesList = str_repeat($placeholders . ',', $dataCount - 1) . $placeholders;

        return "INSERT INTO {$this->quoteIdentifier($table)} ($columnList) VALUES $valuesList";
    }

    /**
     * Get MySQL version-specific features
     */
    public function getSupportedFeatures(): array
    {
        return [
            'window_functions' => true, // MySQL 8.0+
            'cte' => true, // MySQL 8.0+
            'json_functions' => true, // MySQL 5.7+
            'generated_columns' => true, // MySQL 5.7+
            'full_text_search' => true,
            'spatial_data' => true,
            'partitioning' => true
        ];
    }

    /**
     * Build performance monitoring query
     */
    public function buildPerformanceMonitoringQuery(): string
    {
        return "
            SELECT
                TABLE_NAME,
                TABLE_ROWS,
                DATA_LENGTH,
                INDEX_LENGTH,
                (DATA_LENGTH + INDEX_LENGTH) as TOTAL_SIZE
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME IN ('employees', 'planilla_detalle', 'planilla_cabecera', 'concepto')
            ORDER BY TOTAL_SIZE DESC
        ";
    }

    /**
     * Build database-specific transaction query
     */
    public function buildTransactionQuery($isolationLevel = 'READ_COMMITTED'): string
    {
        $levels = [
            'READ_UNCOMMITTED' => 'READ UNCOMMITTED',
            'READ_COMMITTED' => 'READ COMMITTED',
            'REPEATABLE_READ' => 'REPEATABLE READ',
            'SERIALIZABLE' => 'SERIALIZABLE'
        ];

        $level = $levels[$isolationLevel] ?? 'READ COMMITTED';
        return "SET TRANSACTION ISOLATION LEVEL $level; START TRANSACTION;";
    }

    /**
     * Build MySQL index hint
     */
    public function buildIndexHint($table, $indexes, $type = 'USE'): string
    {
        if (empty($indexes)) {
            return '';
        }

        $quotedIndexes = array_map(function($index) {
            return "`$index`";
        }, $indexes);

        $indexList = implode(', ', $quotedIndexes);
        return " $type INDEX ($indexList)";
    }

    /**
     * Build MySQL-specific vacuum/optimization command
     */
    public function buildOptimizeCommand($table): string
    {
        return "OPTIMIZE TABLE `$table`";
    }

    /**
     * Build MySQL connection charset query
     */
    public function buildCharsetQuery($charset = 'utf8mb4', $collation = 'utf8mb4_unicode_ci'): string
    {
        return "SET NAMES '$charset' COLLATE '$collation'";
    }

    /**
     * Build MySQL session variables for payroll optimization
     */
    public function buildSessionOptimizationQuery(): string
    {
        return "
            SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
            SET SESSION group_concat_max_len = 1000000;
            SET SESSION tmp_table_size = 256*1024*1024;
            SET SESSION max_heap_table_size = 256*1024*1024;
            SET SESSION sort_buffer_size = 8*1024*1024;
        ";
    }

    /**
     * Build MySQL backup command
     */
    public function buildBackupCommand($database, $tables = []): string
    {
        $tableList = empty($tables) ? '' : implode(' ', $tables);
        return "mysqldump --single-transaction --routines --triggers $database $tableList";
    }

    /**
     * Get MySQL-specific query execution statistics
     */
    public function buildQueryStatsQuery(): string
    {
        return "
            SELECT
                SCHEMA_NAME as database_name,
                SUM(COUNT_READ) as total_reads,
                SUM(COUNT_WRITE) as total_writes,
                SUM(SUM_TIMER_READ) as total_read_time,
                SUM(SUM_TIMER_WRITE) as total_write_time
            FROM performance_schema.events_statements_summary_by_digest
            WHERE SCHEMA_NAME = DATABASE()
            GROUP BY SCHEMA_NAME
        ";
    }
}