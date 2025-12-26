-- ============================================
-- Migration: Add License Sync Fields to Tenants
-- Date: 2025-11-24
-- Purpose: Agregar campos para rastrear estado de sincronización de licencias offline
-- IDEMPOTENT: ✅ Safe to run multiple times
-- ============================================

USE planilla_master;

SET @dbname = 'planilla_master';
SET @tablename = 'tenants';

-- Add license_sync_pending column if it doesn't exist
SET @columnname = 'license_sync_pending';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE tenants ADD COLUMN license_sync_pending TINYINT(1) DEFAULT 0 COMMENT ''Indica si la licencia está pendiente de sincronización con servidor remoto'' AFTER license_status'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add license_sync_error column if it doesn't exist
SET @columnname = 'license_sync_error';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE tenants ADD COLUMN license_sync_error TEXT NULL COMMENT ''Mensaje de error de la última sincronización fallida'' AFTER license_sync_pending'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add license_last_sync_attempt column if it doesn't exist
SET @columnname = 'license_last_sync_attempt';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE tenants ADD COLUMN license_last_sync_attempt DATETIME NULL COMMENT ''Fecha del último intento de sincronización con servidor remoto'' AFTER license_sync_error'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add index for license_sync_pending lookups if it doesn't exist
SET @indexname = 'idx_license_sync_pending';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = @indexname)
  ) > 0,
  'SELECT 1',
  'CREATE INDEX idx_license_sync_pending ON tenants(license_sync_pending, license_last_sync_attempt)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Comentario de migración
SELECT 'Migration completed: License sync fields added to tenants table (idempotent)' as message;
