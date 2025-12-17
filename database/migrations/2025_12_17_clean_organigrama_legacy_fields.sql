-- ============================================================================
-- Migration: Limpiar campos legacy en tabla organigrama
-- Fecha: 2025-12-17
-- Descripción: Elimina campos cargo_id y funcion_id que no tienen sentido
--              en la estructura departamental
-- Autor: Sistema de Planillas v3.5.14
-- ============================================================================

-- PASO 1: Verificar estructura actual
SELECT 'Estado actual de tabla organigrama:' as info;
DESCRIBE organigrama;

-- PASO 2: Verificar que los campos legacy están vacíos
SELECT 'PASO 2: Verificando datos en campos legacy...' as info;
SELECT
    COUNT(*) as total_registros,
    SUM(CASE WHEN cargo_id IS NOT NULL THEN 1 ELSE 0 END) as con_cargo_id,
    SUM(CASE WHEN funcion_id IS NOT NULL THEN 1 ELSE 0 END) as con_funcion_id
FROM organigrama;

-- PASO 3: Verificar foreign keys existentes
SELECT 'PASO 3: Verificando Foreign Keys actuales...' as info;
SELECT
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'organigrama'
  AND CONSTRAINT_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- PASO 4: Eliminar Foreign Key de cargo_id (si existe)
SELECT 'PASO 4: Eliminando Foreign Key de cargo_id...' as info;

SET @fk_cargo = (
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'organigrama'
      AND COLUMN_NAME = 'cargo_id'
      AND CONSTRAINT_SCHEMA = DATABASE()
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

SET @sql_drop_fk_cargo = IF(@fk_cargo IS NOT NULL,
    CONCAT('ALTER TABLE organigrama DROP FOREIGN KEY ', @fk_cargo),
    'SELECT "No FK cargo_id found" as info'
);

PREPARE stmt FROM @sql_drop_fk_cargo;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- PASO 5: Eliminar Foreign Key de funcion_id (si existe)
SELECT 'PASO 5: Eliminando Foreign Key de funcion_id...' as info;

SET @fk_funcion = (
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'organigrama'
      AND COLUMN_NAME = 'funcion_id'
      AND CONSTRAINT_SCHEMA = DATABASE()
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

SET @sql_drop_fk_funcion = IF(@fk_funcion IS NOT NULL,
    CONCAT('ALTER TABLE organigrama DROP FOREIGN KEY ', @fk_funcion),
    'SELECT "No FK funcion_id found" as info'
);

PREPARE stmt FROM @sql_drop_fk_funcion;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- PASO 6: Eliminar columna cargo_id
SELECT 'PASO 6: Eliminando columna cargo_id...' as info;
ALTER TABLE organigrama DROP COLUMN cargo_id;

-- PASO 7: Eliminar columna funcion_id
SELECT 'PASO 7: Eliminando columna funcion_id...' as info;
ALTER TABLE organigrama DROP COLUMN funcion_id;

-- PASO 8: Renombrar 'nivel' a 'nivel_jerarquico' para mayor claridad
SELECT 'PASO 8: Renombrando columna nivel a nivel_jerarquico...' as info;
ALTER TABLE organigrama
CHANGE COLUMN nivel nivel_jerarquico INT DEFAULT 0
COMMENT 'Nivel en la jerarquía organizacional (0=raíz)';

-- PASO 9: Actualizar comentario de tabla
SELECT 'PASO 9: Actualizando comentario de tabla...' as info;
ALTER TABLE organigrama
COMMENT = 'Estructura departamental jerárquica de la organización';

-- PASO 10: Verificar estructura final
SELECT 'PASO 10: Verificando estructura final...' as info;
DESCRIBE organigrama;

-- PASO 11: Verificar datos finales
SELECT 'PASO 11: Verificando datos de departamentos...' as info;
SELECT
    id,
    descripcion,
    id_padre,
    nivel_jerarquico,
    path
FROM organigrama
ORDER BY path;

-- ============================================================================
-- RESULTADO ESPERADO:
-- ============================================================================
-- Tabla organigrama limpia con estructura:
-- - id, descripcion, id_padre, path, nivel_jerarquico
-- - Sin campos cargo_id ni funcion_id
-- - Relación jerárquica clara entre departamentos
-- ============================================================================

SELECT '✅ Migración completada exitosamente!' as resultado;
SELECT 'ℹ️  Tabla organigrama ahora representa únicamente departamentos' as nota;
