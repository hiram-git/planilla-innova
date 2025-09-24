<?php

namespace App\Core\Adapters;

/**
 * PostgreSQL Database Adapter
 *
 * Implements PostgreSQL-specific SQL generation and optimizations
 * Designed for enterprise-scale payroll systems with advanced PostgreSQL features
 */
class PostgreSQLAdapter implements DatabaseAdapter
{
    /**
     * Build PostgreSQL LIMIT/OFFSET clause
     */
    public function buildLimitClause($count, $offset = 0): string
    {
        $clause = " LIMIT $count";
        if ($offset > 0) {
            $clause .= " OFFSET $offset";
        }
        return $clause;
    }

    /**
     * PostgreSQL uses currval() or RETURNING
     */
    public function getLastInsertIdMethod(): string
    {
        return 'RETURNING id';
    }

    /**
     * PostgreSQL SERIAL column
     */
    public function buildAutoIncrementColumn(): string
    {
        return 'SERIAL PRIMARY KEY';
    }

    /**
     * PostgreSQL date functions
     */
    public function buildDateFunction($function): string
    {
        $functions = [
            'NOW' => 'NOW()',
            'CURRENT_DATE' => 'CURRENT_DATE',
            'CURRENT_TIME' => 'CURRENT_TIME',
            'CURRENT_TIMESTAMP' => 'CURRENT_TIMESTAMP',
            'YEAR' => 'EXTRACT(YEAR FROM',
            'MONTH' => 'EXTRACT(MONTH FROM',
            'DAY' => 'EXTRACT(DAY FROM',
            'DATEDIFF' => 'DATE_PART(\'day\', AGE('
        ];

        return $functions[strtoupper($function)] ?? $function;
    }

    /**
     * PostgreSQL concatenation with ||
     */
    public function buildConcatenation(array $columns): string
    {
        return implode(' || ', $columns);
    }

    /**
     * PostgreSQL custom type (replaces ENUM)
     */
    public function buildEnumField(array $values): string
    {
        // PostgreSQL uses CHECK constraints instead of ENUM for better flexibility
        $quotedValues = array_map(function($value) {
            return "'" . addslashes($value) . "'";
        }, $values);

        return 'VARCHAR(50) CHECK (value IN (' . implode(',', $quotedValues) . '))';
    }

    /**
     * PostgreSQL boolean field
     */
    public function buildBooleanField($default = false): string
    {
        $defaultValue = $default ? 'true' : 'false';
        return "BOOLEAN DEFAULT $defaultValue";
    }

    /**
     * PostgreSQL identifier quoting with double quotes
     */
    public function quoteIdentifier($identifier): string
    {
        // Handle schema.table.column format
        if (strpos($identifier, '.') !== false) {
            $parts = explode('.', $identifier);
            return '"' . implode('"."', $parts) . '"';
        }

        return '"' . $identifier . '"';
    }

    /**
     * PostgreSQL-specific query optimizations for payroll system
     */
    public function getQueryOptimizations(): array
    {
        return [
            // Index strategies for large datasets
            'index_optimizations' => [
                'USING btree' => 'B-tree indexes for exact matches and ranges',
                'USING hash' => 'Hash indexes for equality comparisons',
                'USING gin' => 'GIN indexes for array and JSON data',
                'USING gist' => 'GiST indexes for geometric and full-text data'
            ],

            // Query planning hints
            'planning_hints' => [
                'enable_hashjoin = on' => 'Enable hash joins for large table joins',
                'enable_mergejoin = on' => 'Enable merge joins for sorted data',
                'work_mem = \'256MB\'' => 'Increase work memory for complex queries',
                'random_page_cost = 1.1' => 'Optimize for SSD storage'
            ],

            // Parallel processing for large payroll datasets
            'parallel_processing' => [
                'max_parallel_workers_per_gather = 4' => 'Parallel query execution',
                'parallel_tuple_cost = 0.1' => 'Optimize parallel tuple processing',
                'parallel_setup_cost = 1000' => 'Parallel setup cost optimization'
            ],

            // PostgreSQL-specific payroll optimizations
            'payroll_specific' => [
                'effective_cache_size = \'4GB\'' => 'PostgreSQL cache optimization',
                'shared_buffers = \'1GB\'' => 'Shared buffer optimization',
                'checkpoint_completion_target = 0.9' => 'Checkpoint optimization',
                'wal_buffers = \'64MB\'' => 'WAL buffer optimization'
            ]
        ];
    }

    /**
     * Build optimized aggregation query with PostgreSQL features
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
                SUM(CASE WHEN c.tipo_concepto = 'ASIGNACION' THEN pd.monto ELSE -pd.monto END) as total_neto,
                -- PostgreSQL-specific window functions for analytics
                AVG(pd.monto) OVER (PARTITION BY c.tipo_concepto) as avg_by_type,
                RANK() OVER (ORDER BY SUM(pd.monto) DESC) as salary_rank
            FROM planilla_detalle pd
            INNER JOIN employees e ON pd.employee_id = e.id
            INNER JOIN concepto c ON pd.concepto_id = c.id
            WHERE pd.employee_id = ANY($1::int[])
            AND pd.periodo = $2
            GROUP BY pd.employee_id, e.firstname, e.lastname
            ORDER BY e.firstname, e.lastname
        ";
    }

    /**
     * Build XIII month calculation with PostgreSQL date functions
     */
    public function buildXIIIMonthQuery($employeeId, $year): string
    {
        return "
            WITH monthly_totals AS (
                SELECT
                    DATE_TRUNC('month', pd.created_at) as month,
                    SUM(pd.monto) as monthly_total
                FROM planilla_detalle pd
                INNER JOIN concepto c ON pd.concepto_id = c.id
                WHERE pd.employee_id = $1
                AND EXTRACT(YEAR FROM pd.created_at) = $2
                AND c.tipo_concepto = 'ASIGNACION'
                AND c.concepto NOT IN ('XIII_MES', 'PRIMA_ANTIGUEDAD')
                GROUP BY DATE_TRUNC('month', pd.created_at)
                HAVING SUM(pd.monto) > 0
            )
            SELECT
                SUM(monthly_total) / 12 as xiii_mes_amount,
                COUNT(*) as periods_worked,
                MIN(month) as first_period,
                MAX(month) as last_period,
                -- PostgreSQL array aggregation for detailed analysis
                ARRAY_AGG(monthly_total ORDER BY month) as monthly_breakdown
            FROM monthly_totals
            HAVING COUNT(*) >= 11
        ";
    }

    /**
     * Build bulk insert with PostgreSQL COPY or unnest()
     */
    public function buildBulkInsertQuery($table, $columns, $dataCount): string
    {
        $columnList = implode(',', array_map([$this, 'quoteIdentifier'], $columns));

        // Use unnest() for efficient bulk inserts in PostgreSQL
        $unnestColumns = [];
        $unnestValues = [];

        foreach ($columns as $i => $column) {
            $unnestColumns[] = "unnest($" . ($i + 1) . "::text[]) AS " . $this->quoteIdentifier($column);
        }

        return "INSERT INTO {$this->quoteIdentifier($table)} ($columnList)
                SELECT " . implode(', ', $unnestColumns);
    }

    /**
     * Build advanced analytics query with window functions
     */
    public function buildPayrollAnalyticsQuery($startDate, $endDate): string
    {
        return "
            WITH payroll_metrics AS (
                SELECT
                    pd.employee_id,
                    e.firstname || ' ' || e.lastname as employee_name,
                    DATE_TRUNC('month', pd.created_at) as period,
                    c.tipo_concepto,
                    SUM(pd.monto) as total_amount,
                    -- Window functions for trend analysis
                    LAG(SUM(pd.monto)) OVER (
                        PARTITION BY pd.employee_id, c.tipo_concepto
                        ORDER BY DATE_TRUNC('month', pd.created_at)
                    ) as previous_period_amount,
                    -- Moving average
                    AVG(SUM(pd.monto)) OVER (
                        PARTITION BY pd.employee_id, c.tipo_concepto
                        ORDER BY DATE_TRUNC('month', pd.created_at)
                        ROWS BETWEEN 2 PRECEDING AND CURRENT ROW
                    ) as moving_avg_3_months
                FROM planilla_detalle pd
                INNER JOIN employees e ON pd.employee_id = e.id
                INNER JOIN concepto c ON pd.concepto_id = c.id
                WHERE pd.created_at BETWEEN $1 AND $2
                GROUP BY pd.employee_id, e.firstname, e.lastname,
                         DATE_TRUNC('month', pd.created_at), c.tipo_concepto
            )
            SELECT
                employee_id,
                employee_name,
                period,
                tipo_concepto,
                total_amount,
                previous_period_amount,
                moving_avg_3_months,
                -- Calculate percentage change
                CASE
                    WHEN previous_period_amount > 0 THEN
                        ROUND(((total_amount - previous_period_amount) / previous_period_amount * 100)::numeric, 2)
                    ELSE NULL
                END as percentage_change
            FROM payroll_metrics
            ORDER BY employee_name, period, tipo_concepto
        ";
    }

    /**
     * Get PostgreSQL version-specific features
     */
    public function getSupportedFeatures(): array
    {
        return [
            'window_functions' => true, // PostgreSQL 8.4+
            'cte' => true, // PostgreSQL 8.4+
            'recursive_cte' => true, // PostgreSQL 8.4+
            'json_functions' => true, // PostgreSQL 9.2+
            'jsonb' => true, // PostgreSQL 9.4+
            'upsert' => true, // PostgreSQL 9.5+
            'parallel_queries' => true, // PostgreSQL 9.6+
            'partitioning' => true, // PostgreSQL 10+
            'stored_procedures' => true, // PostgreSQL 11+
            'generated_columns' => true // PostgreSQL 12+
        ];
    }

    /**
     * Build performance monitoring query for PostgreSQL
     */
    public function buildPerformanceMonitoringQuery(): string
    {
        return "
            SELECT
                schemaname,
                tablename,
                n_tup_ins as inserts,
                n_tup_upd as updates,
                n_tup_del as deletes,
                n_live_tup as live_tuples,
                n_dead_tup as dead_tuples,
                last_vacuum,
                last_autovacuum,
                last_analyze,
                last_autoanalyze
            FROM pg_stat_user_tables
            WHERE tablename IN ('employees', 'planilla_detalle', 'planilla_cabecera', 'concepto')
            ORDER BY n_live_tup DESC
        ";
    }

    /**
     * Build index usage analysis query
     */
    public function buildIndexAnalysisQuery(): string
    {
        return "
            SELECT
                schemaname,
                tablename,
                indexname,
                idx_tup_read,
                idx_tup_fetch,
                idx_scan,
                idx_tup_read / NULLIF(idx_scan, 0) as avg_tuples_per_scan
            FROM pg_stat_user_indexes
            WHERE tablename IN ('employees', 'planilla_detalle', 'planilla_cabecera', 'concepto')
            ORDER BY idx_scan DESC
        ";
    }

    /**
     * Build query plan analysis
     */
    public function explainQuery($query): string
    {
        return "EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON) " . $query;
    }

    /**
     * Build PostgreSQL-specific transaction query
     */
    public function buildTransactionQuery($isolationLevel = 'READ_committed'): string
    {
        $levels = [
            'read_uncommitted' => 'READ UNCOMMITTED',
            'read_committed' => 'READ COMMITTED',
            'repeatable_read' => 'REPEATABLE READ',
            'serializable' => 'SERIALIZABLE'
        ];

        $level = $levels[strtolower($isolationLevel)] ?? 'READ COMMITTED';
        return "BEGIN TRANSACTION ISOLATION LEVEL $level;";
    }

    /**
     * Build PostgreSQL index hint (table hints)
     */
    public function buildIndexHint($table, $indexes, $type = 'USE'): string
    {
        // PostgreSQL doesn't support index hints like MySQL
        // Instead, we can use query planner hints or configuration
        if (empty($indexes)) {
            return '';
        }

        // Return planner configuration suggestions
        $indexList = implode(', ', $indexes);
        return "/* Suggested indexes for $table: $indexList */";
    }

    /**
     * Build PostgreSQL vacuum command
     */
    public function buildVacuumCommand($table, $analyze = true): string
    {
        $analyzeClause = $analyze ? ' ANALYZE' : '';
        return "VACUUM$analyzeClause \"$table\"";
    }

    /**
     * Build PostgreSQL connection charset query
     */
    public function buildCharsetQuery($charset = 'UTF8', $collation = 'en_US.UTF-8'): string
    {
        // PostgreSQL handles encoding differently
        return "SET client_encoding TO '$charset'";
    }

    /**
     * Build PostgreSQL session variables for payroll optimization
     */
    public function buildSessionOptimizationQuery(): string
    {
        return "
            SET work_mem = '256MB';
            SET effective_cache_size = '4GB';
            SET random_page_cost = 1.1;
            SET seq_page_cost = 1.0;
            SET cpu_tuple_cost = 0.01;
            SET cpu_index_tuple_cost = 0.005;
            SET enable_hashjoin = on;
            SET enable_mergejoin = on;
            SET enable_nestloop = on;
        ";
    }

    /**
     * Build PostgreSQL backup command
     */
    public function buildBackupCommand($database, $tables = []): string
    {
        $tableList = empty($tables) ? '' : '--table=' . implode(' --table=', $tables);
        return "pg_dump --format=custom --compress=9 --no-owner --no-privileges $tableList $database";
    }

    /**
     * Build PostgreSQL connection pool status query
     */
    public function buildConnectionStatsQuery(): string
    {
        return "
            SELECT
                datname as database_name,
                numbackends as active_connections,
                xact_commit as transactions_committed,
                xact_rollback as transactions_rolled_back,
                blks_read as blocks_read,
                blks_hit as blocks_hit,
                tup_returned as tuples_returned,
                tup_fetched as tuples_fetched,
                tup_inserted as tuples_inserted,
                tup_updated as tuples_updated,
                tup_deleted as tuples_deleted
            FROM pg_stat_database
            WHERE datname = current_database()
        ";
    }

    /**
     * Build PostgreSQL query to analyze table statistics
     */
    public function buildTableAnalysisQuery(): string
    {
        return "
            SELECT
                schemaname,
                tablename,
                attname as column_name,
                n_distinct,
                most_common_vals,
                most_common_freqs,
                histogram_bounds
            FROM pg_stats
            WHERE schemaname = 'public'
            AND tablename IN ('employees', 'planilla_detalle', 'planilla_cabecera', 'concepto')
            ORDER BY tablename, attname
        ";
    }

    /**
     * Build PostgreSQL extension check query
     */
    public function buildExtensionCheckQuery(): string
    {
        return "
            SELECT
                extname as extension_name,
                extversion as version,
                extrelocatable as relocatable
            FROM pg_extension
            WHERE extname IN ('pg_stat_statements', 'pgcrypto', 'uuid-ossp', 'hstore')
        ";
    }

    /**
     * Build PostgreSQL session locks query
     */
    public function buildLocksAnalysisQuery(): string
    {
        return "
            SELECT
                pg_class.relname as table_name,
                pg_locks.locktype,
                pg_locks.mode,
                pg_locks.granted,
                pg_stat_activity.query,
                pg_stat_activity.query_start,
                pg_stat_activity.state
            FROM pg_locks
            JOIN pg_class ON pg_locks.relation = pg_class.oid
            JOIN pg_stat_activity ON pg_locks.pid = pg_stat_activity.pid
            WHERE pg_class.relname IN ('employees', 'planilla_detalle', 'planilla_cabecera', 'concepto')
            ORDER BY pg_stat_activity.query_start DESC
        ";
    }

    /**
     * Build PostgreSQL replication status query
     */
    public function buildReplicationStatusQuery(): string
    {
        return "
            SELECT
                client_addr,
                client_hostname,
                client_port,
                state,
                sent_lsn,
                write_lsn,
                flush_lsn,
                replay_lsn,
                write_lag,
                flush_lag,
                replay_lag
            FROM pg_stat_replication
        ";
    }
}