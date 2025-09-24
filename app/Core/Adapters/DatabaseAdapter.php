<?php

namespace App\Core\Adapters;

/**
 * Database Adapter Interface
 *
 * Provides database-specific implementations for different engines
 * Supports MySQL, PostgreSQL, and future database systems
 */
interface DatabaseAdapter
{
    /**
     * Build LIMIT clause specific to database engine
     *
     * @param int $count Number of records to limit
     * @param int $offset Number of records to skip
     * @return string SQL LIMIT clause
     */
    public function buildLimitClause($count, $offset = 0): string;

    /**
     * Get last inserted ID method for the database engine
     *
     * @return string SQL expression or method name
     */
    public function getLastInsertIdMethod(): string;

    /**
     * Build AUTO_INCREMENT column definition
     *
     * @return string SQL column definition
     */
    public function buildAutoIncrementColumn(): string;

    /**
     * Build date/time functions specific to database
     *
     * @param string $function Function name (NOW, CURRENT_DATE, etc.)
     * @return string Database-specific function
     */
    public function buildDateFunction($function): string;

    /**
     * Build string concatenation for database
     *
     * @param array $columns Columns to concatenate
     * @return string Database-specific concatenation
     */
    public function buildConcatenation(array $columns): string;

    /**
     * Build ENUM field definition
     *
     * @param array $values Enum values
     * @return string Database-specific enum definition
     */
    public function buildEnumField(array $values): string;

    /**
     * Build boolean field definition
     *
     * @param bool $default Default value
     * @return string Database-specific boolean field
     */
    public function buildBooleanField($default = false): string;

    /**
     * Quote identifier (table/column names) for database
     *
     * @param string $identifier Table or column name
     * @return string Quoted identifier
     */
    public function quoteIdentifier($identifier): string;

    /**
     * Get database-specific query optimizations
     *
     * @return array Optimization hints
     */
    public function getQueryOptimizations(): array;

    /**
     * Build bulk insert query with database-specific optimizations
     *
     * @param string $table Table name
     * @param array $columns Column names
     * @param int $dataCount Number of rows to insert
     * @return string SQL query for bulk insert
     */
    public function buildBulkInsertQuery($table, $columns, $dataCount): string;

    /**
     * Get supported database features
     *
     * @return array Feature support matrix
     */
    public function getSupportedFeatures(): array;

    /**
     * Build payroll aggregation query optimized for database
     *
     * @param array $employeeIds Employee IDs
     * @param string $period Payroll period
     * @return string Optimized aggregation query
     */
    public function buildPayrollAggregationQuery($employeeIds, $period): string;

    /**
     * Build XIII month calculation query
     *
     * @param int $employeeId Employee ID
     * @param int $year Year for calculation
     * @return string XIII month query
     */
    public function buildXIIIMonthQuery($employeeId, $year): string;

    /**
     * Explain query execution plan
     *
     * @param string $query SQL query to analyze
     * @return string Explain query
     */
    public function explainQuery($query): string;

    /**
     * Build database-specific transaction query
     *
     * @param string $isolationLevel Transaction isolation level
     * @return string Transaction start query
     */
    public function buildTransactionQuery($isolationLevel = 'READ_COMMITTED'): string;

    /**
     * Build database-specific index hint
     *
     * @param string $table Table name
     * @param array $indexes Index names
     * @param string $type Hint type (USE, FORCE, IGNORE)
     * @return string Index hint clause
     */
    public function buildIndexHint($table, $indexes, $type = 'USE'): string;
}