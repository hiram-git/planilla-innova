-- =====================================================
-- Migración: Fix organigrama column names
-- Fecha: 29-Dic-2025
-- Descripción: Renombrar columna 'nivel' a 'nivel_jerarquico' y eliminar columnas legacy
-- =====================================================

-- 1. Renombrar columna 'nivel' a 'nivel_jerarquico' si existe
-- Verificar primero si existe la columna 'nivel'
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organigrama'
    AND COLUMN_NAME = 'nivel'
);

-- Renombrar si existe
SET @sql = IF(@column_exists > 0,
    'ALTER TABLE organigrama CHANGE COLUMN nivel nivel_jerarquico INT NULL DEFAULT 0',
    'SELECT "Column nivel does not exist, skipping rename" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Eliminar foreign keys legacy si existen
SET @fk_cargo_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organigrama'
    AND CONSTRAINT_NAME = 'fk_organigrama_cargo'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@fk_cargo_exists > 0,
    'ALTER TABLE organigrama DROP FOREIGN KEY fk_organigrama_cargo',
    'SELECT "FK fk_organigrama_cargo does not exist, skipping" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_funcion_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organigrama'
    AND CONSTRAINT_NAME = 'fk_organigrama_funcion'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@fk_funcion_exists > 0,
    'ALTER TABLE organigrama DROP FOREIGN KEY fk_organigrama_funcion',
    'SELECT "FK fk_organigrama_funcion does not exist, skipping" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Eliminar columnas legacy si existen
SET @cargo_id_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organigrama'
    AND COLUMN_NAME = 'cargo_id'
);

SET @sql = IF(@cargo_id_exists > 0,
    'ALTER TABLE organigrama DROP COLUMN cargo_id',
    'SELECT "Column cargo_id does not exist, skipping" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @funcion_id_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organigrama'
    AND COLUMN_NAME = 'funcion_id'
);

SET @sql = IF(@funcion_id_exists > 0,
    'ALTER TABLE organigrama DROP COLUMN funcion_id',
    'SELECT "Column funcion_id does not exist, skipping" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Comentario: Migración completada
-- La tabla organigrama ahora tiene la estructura estándar:
-- - nivel_jerarquico (en lugar de nivel)
-- - Sin columnas legacy cargo_id y funcion_id
