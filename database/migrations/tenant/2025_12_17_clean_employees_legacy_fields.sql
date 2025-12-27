-- ============================================================================
-- Migration: Limpiar campos legacy y renombrar en tabla employees
-- Fecha: 2025-12-17
-- Descripción: Elimina campos duplicados y renombra organigrama_id a departamento_id
-- Autor: Sistema de Planillas v3.5.14
-- ============================================================================

-- PASO 1: Verificar estructura actual
SELECT 'Estado actual de employees (campos organigrama):' as info;
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'employees'
  AND COLUMN_NAME IN ('organigrama_id', 'cargo_id', 'funcion_id', 'id_cargo', 'id_funcion', 'organigrama_path')
ORDER BY ORDINAL_POSITION;

-- PASO 2: Verificar datos en campos legacy
SELECT 'PASO 2: Verificando datos en campos...' as info;
SELECT
    COUNT(*) as total_empleados,
    SUM(CASE WHEN organigrama_id IS NOT NULL THEN 1 ELSE 0 END) as con_organigrama_id,
    SUM(CASE WHEN cargo_id IS NOT NULL THEN 1 ELSE 0 END) as con_cargo_id,
    SUM(CASE WHEN funcion_id IS NOT NULL THEN 1 ELSE 0 END) as con_funcion_id,
    SUM(CASE WHEN id_cargo IS NOT NULL THEN 1 ELSE 0 END) as con_id_cargo_legacy,
    SUM(CASE WHEN id_funcion IS NOT NULL THEN 1 ELSE 0 END) as con_id_funcion_legacy,
    SUM(CASE WHEN organigrama_path IS NOT NULL AND organigrama_path != '' THEN 1 ELSE 0 END) as con_organigrama_path
FROM employees;

-- PASO 3: Migrar datos de campos legacy a campos principales (por si acaso)
SELECT 'PASO 3: Migrando datos de campos legacy (si existen)...' as info;

UPDATE employees
SET cargo_id = COALESCE(cargo_id, id_cargo),
    funcion_id = COALESCE(funcion_id, id_funcion)
WHERE cargo_id IS NULL OR funcion_id IS NULL;

-- PASO 4: Eliminar Foreign Keys de campos legacy
SELECT 'PASO 4: Eliminando Foreign Keys de campos legacy...' as info;

-- FK de id_cargo (si existe)
SET @fk_id_cargo = (
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'employees'
      AND COLUMN_NAME = 'id_cargo'
      AND CONSTRAINT_SCHEMA = DATABASE()
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

SET @sql_drop_fk_id_cargo = IF(@fk_id_cargo IS NOT NULL,
    CONCAT('ALTER TABLE employees DROP FOREIGN KEY ', @fk_id_cargo),
    'SELECT "No FK id_cargo found" as info'
);

PREPARE stmt FROM @sql_drop_fk_id_cargo;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- FK de id_funcion (si existe)
SET @fk_id_funcion = (
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'employees'
      AND COLUMN_NAME = 'id_funcion'
      AND CONSTRAINT_SCHEMA = DATABASE()
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

SET @sql_drop_fk_id_funcion = IF(@fk_id_funcion IS NOT NULL,
    CONCAT('ALTER TABLE employees DROP FOREIGN KEY ', @fk_id_funcion),
    'SELECT "No FK id_funcion found" as info'
);

PREPARE stmt FROM @sql_drop_fk_id_funcion;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- PASO 5: Eliminar columnas legacy (una por una)
SELECT 'PASO 5: Eliminando columnas legacy...' as info;

-- Eliminar id_cargo
SET @column_exists_id_cargo = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'employees'
      AND COLUMN_NAME = 'id_cargo'
);

SET @sql_drop_id_cargo = IF(@column_exists_id_cargo > 0,
    'ALTER TABLE employees DROP COLUMN id_cargo',
    'SELECT "Column id_cargo does not exist" as info'
);

PREPARE stmt FROM @sql_drop_id_cargo;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar id_funcion
SET @column_exists_id_funcion = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'employees'
      AND COLUMN_NAME = 'id_funcion'
);

SET @sql_drop_id_funcion = IF(@column_exists_id_funcion > 0,
    'ALTER TABLE employees DROP COLUMN id_funcion',
    'SELECT "Column id_funcion does not exist" as info'
);

PREPARE stmt FROM @sql_drop_id_funcion;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar organigrama_path
SET @column_exists_path = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'employees'
      AND COLUMN_NAME = 'organigrama_path'
);

SET @sql_drop_path = IF(@column_exists_path > 0,
    'ALTER TABLE employees DROP COLUMN organigrama_path',
    'SELECT "Column organigrama_path does not exist" as info'
);

PREPARE stmt FROM @sql_drop_path;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- PASO 6: Renombrar organigrama_id a departamento_id (para mayor claridad)
SELECT 'PASO 6: Renombrando organigrama_id a departamento_id...' as info;

ALTER TABLE employees
CHANGE COLUMN organigrama_id departamento_id INT NULL
COMMENT 'FK al departamento (organigrama) del empleado';

-- PASO 7: Verificar y crear índices si no existen
SELECT 'PASO 7: Verificando índices...' as info;

-- Crear índice para departamento_id si no existe
SET @index_exists_depto = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'employees'
      AND INDEX_NAME = 'idx_departamento'
);

SET @sql_create_index_depto = IF(@index_exists_depto = 0,
    'ALTER TABLE employees ADD INDEX idx_departamento (departamento_id)',
    'SELECT "Index idx_departamento already exists" as info'
);

PREPARE stmt FROM @sql_create_index_depto;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear índice para cargo_id si no existe
SET @index_exists_cargo = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'employees'
      AND INDEX_NAME = 'idx_cargo'
);

SET @sql_create_index_cargo = IF(@index_exists_cargo = 0,
    'ALTER TABLE employees ADD INDEX idx_cargo (cargo_id)',
    'SELECT "Index idx_cargo already exists" as info'
);

PREPARE stmt FROM @sql_create_index_cargo;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear índice para funcion_id si no existe
SET @index_exists_funcion = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'employees'
      AND INDEX_NAME = 'idx_funcion'
);

SET @sql_create_index_funcion = IF(@index_exists_funcion = 0,
    'ALTER TABLE employees ADD INDEX idx_funcion (funcion_id)',
    'SELECT "Index idx_funcion already exists" as info'
);

PREPARE stmt FROM @sql_create_index_funcion;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- PASO 8: Verificar estructura final
SELECT 'PASO 8: Verificando estructura final...' as info;
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'employees'
  AND COLUMN_NAME IN ('departamento_id', 'cargo_id', 'funcion_id')
ORDER BY ORDINAL_POSITION;

-- PASO 9: Verificar Foreign Keys finales
SELECT 'PASO 9: Verificando Foreign Keys de employees...' as info;
SELECT
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'employees'
  AND CONSTRAINT_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IN ('organigrama', 'cargos', 'funciones')
ORDER BY COLUMN_NAME;

-- PASO 10: Verificar datos de empleados
SELECT 'PASO 10: Verificando datos de empleados...' as info;
SELECT
    COUNT(*) as total,
    SUM(CASE WHEN departamento_id IS NOT NULL THEN 1 ELSE 0 END) as con_departamento,
    SUM(CASE WHEN cargo_id IS NOT NULL THEN 1 ELSE 0 END) as con_cargo,
    SUM(CASE WHEN funcion_id IS NOT NULL THEN 1 ELSE 0 END) as con_funcion
FROM employees;

-- ============================================================================
-- RESULTADO ESPERADO:
-- ============================================================================
-- Tabla employees limpia con:
-- - departamento_id (antes organigrama_id)
-- - cargo_id
-- - funcion_id
-- Sin campos legacy: id_cargo, id_funcion, organigrama_path
-- ============================================================================

SELECT '✅ Migración completada exitosamente!' as resultado;
SELECT 'ℹ️  Tabla employees ahora usa departamento_id en lugar de organigrama_id' as nota;
