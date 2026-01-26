-- =====================================================
-- Migración: Agregar columna is_system_admin a tabla admin
-- Fecha: 2026-01-26
-- Descripción: Agrega columna para identificar al super administrador
--              del sistema que puede acceder incluso con licencia expirada
-- =====================================================

-- Verificar si la columna ya existe antes de agregarla
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'admin'
      AND COLUMN_NAME = 'is_system_admin'
);

-- Agregar columna solo si no existe
SET @sql = IF(
    @column_exists = 0,
    'ALTER TABLE admin ADD COLUMN is_system_admin TINYINT(1) DEFAULT 0 NOT NULL COMMENT ''1 = Super Administrador del Sistema'' AFTER role_id',
    'SELECT ''Column is_system_admin already exists'' AS msg'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear índice para consultas rápidas
SET @index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'admin'
      AND INDEX_NAME = 'idx_is_system_admin'
);

SET @sql_index = IF(
    @index_exists = 0,
    'CREATE INDEX idx_is_system_admin ON admin(is_system_admin)',
    'SELECT ''Index idx_is_system_admin already exists'' AS msg'
);

PREPARE stmt FROM @sql_index;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- Nota: Marcar manualmente al super admin después de ejecutar esta migración
-- UPDATE admin SET is_system_admin = 1 WHERE username = 'tu_usuario_admin';
-- =====================================================
