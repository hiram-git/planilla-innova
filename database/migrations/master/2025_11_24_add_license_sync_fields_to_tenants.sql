-- ============================================
-- Migration: Add License Sync Fields to Tenants
-- Date: 2025-11-24
-- Purpose: Agregar campos para rastrear estado de sincronización de licencias offline
-- ============================================

USE planilla_master;

-- Agregar campo para indicar si la licencia fue generada en modo offline
ALTER TABLE tenants
ADD COLUMN license_sync_pending TINYINT(1) DEFAULT 0
COMMENT 'Indica si la licencia está pendiente de sincronización con servidor remoto'
AFTER license_status;

-- Agregar campo para almacenar el error de sincronización (si existe)
ALTER TABLE tenants
ADD COLUMN license_sync_error TEXT NULL
COMMENT 'Mensaje de error de la última sincronización fallida'
AFTER license_sync_pending;

-- Agregar campo para fecha de último intento de sincronización
ALTER TABLE tenants
ADD COLUMN license_last_sync_attempt DATETIME NULL
COMMENT 'Fecha del último intento de sincronización con servidor remoto'
AFTER license_sync_error;

-- Agregar índice para consultas rápidas de licencias pendientes
CREATE INDEX idx_license_sync_pending ON tenants(license_sync_pending, license_last_sync_attempt);

-- Comentario de migración
SELECT 'Migration completed: License sync fields added to tenants table' as message;
