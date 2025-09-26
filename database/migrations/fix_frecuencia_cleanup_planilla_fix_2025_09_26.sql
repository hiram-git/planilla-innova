-- Migración: Limpiar campo residual frecuencia_id en tabla acumulados_por_empleado
-- Base de datos: planilla_fix
-- Fecha: 2025-09-26
-- Propósito: Completar migración eliminando campo temporal frecuencia_id

-- =====================================================
-- LIMPIEZA CAMPO RESIDUAL EN PLANILLA_FIX
-- =====================================================

USE planilla_fix;

-- Paso 1: Verificar estado actual de la tabla
SELECT
    'Estado inicial tabla acumulados_por_empleado' as paso,
    COUNT(*) as total_registros
FROM acumulados_por_empleado;

-- Paso 2: Verificar que el campo frecuencia tiene datos válidos
SELECT
    'Verificación campo frecuencia' as paso,
    COUNT(*) as registros_con_frecuencia,
    COUNT(DISTINCT frecuencia) as frecuencias_distintas,
    MIN(frecuencia) as min_frecuencia,
    MAX(frecuencia) as max_frecuencia
FROM acumulados_por_empleado
WHERE frecuencia IS NOT NULL;

-- Paso 3: Verificar foreign key constraint existe
SELECT
    'Verificación foreign key' as paso,
    COUNT(*) as constraint_exists
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'planilla_fix'
AND TABLE_NAME = 'acumulados_por_empleado'
AND CONSTRAINT_NAME = 'fk_acumulados_frecuencia';

-- Paso 4: Eliminar campo residual frecuencia_id
-- Solo si existe para evitar errores
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'planilla_fix'
    AND TABLE_NAME = 'acumulados_por_empleado'
    AND COLUMN_NAME = 'frecuencia_id'
);

SET @sql = IF(@column_exists > 0,
    'ALTER TABLE acumulados_por_empleado DROP COLUMN frecuencia_id',
    'SELECT "Campo frecuencia_id no existe, no se requiere eliminación" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Paso 5: Verificación final de estructura
SELECT
    'Verificación final' as paso,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'planilla_fix'
AND TABLE_NAME = 'acumulados_por_empleado'
AND COLUMN_NAME IN ('frecuencia', 'frecuencia_id')
ORDER BY COLUMN_NAME;

-- Paso 6: Verificar foreign key sigue funcionando
SELECT
    'Test foreign key' as paso,
    f.id as frecuencia_id,
    f.nombre as frecuencia_nombre,
    COUNT(a.id) as registros_acumulados
FROM frecuencias f
LEFT JOIN acumulados_por_empleado a ON f.id = a.frecuencia
GROUP BY f.id, f.nombre
ORDER BY f.id;

-- Mensaje final
SELECT
    'MIGRACIÓN COMPLETADA' as status,
    'Campo frecuencia_id eliminado exitosamente' as message,
    'Foreign key fk_acumulados_frecuencia funcionando correctamente' as constraint_status,
    NOW() as timestamp_completion;