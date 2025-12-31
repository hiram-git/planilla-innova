-- =====================================================
-- Migration: Add document correlative metadata to employee_files
-- Date: 2025-12-31
-- Description: Adds document_year/document_sequence for auto correlatives
-- =====================================================

SET @document_year_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'employee_files'
      AND COLUMN_NAME = 'document_year'
);

SET @sql = IF(@document_year_exists = 0,
    'ALTER TABLE employee_files ADD COLUMN document_year SMALLINT UNSIGNED NULL AFTER document_date',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @document_sequence_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'employee_files'
      AND COLUMN_NAME = 'document_sequence'
);

SET @sql = IF(@document_sequence_exists = 0,
    'ALTER TABLE employee_files ADD COLUMN document_sequence INT UNSIGNED NULL AFTER document_year',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @correlative_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'employee_files'
      AND INDEX_NAME = 'idx_employee_files_correlative'
);

SET @sql = IF(@correlative_index_exists = 0,
    'CREATE INDEX idx_employee_files_correlative ON employee_files(type_id, subtype_id, document_year, document_sequence)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill year metadata for existing rows (no changes to document_number)
UPDATE employee_files
SET document_year = YEAR(document_date)
WHERE document_year IS NULL;
