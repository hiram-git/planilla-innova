-- =====================================================
-- MIGRACIÓN COMPLETA: Campo frecuencia ENUM → INT
-- =====================================================
-- Propósito: Cambiar campo frecuencia de ENUM a INT con foreign key
-- Problema original: "SQLSTATE[01000]: Warning: 1265 Data truncated for column 'frecuencia'"
-- Fecha original: 2025-09-25 (aplicado manualmente)
-- Fecha backup: 2025-09-26
-- Estado: RESPALDO COMPLETO para documentación

-- NOTA: Esta migración es un RESPALDO del fix aplicado ayer manualmente
-- Úsala solo si necesitas replicar en una nueva base de datos

-- =====================================================
-- PASO 1: VERIFICACIONES INICIALES
-- =====================================================

-- Verificar estructura actual del campo frecuencia
SELECT
    'PASO 1: Verificación inicial' as paso,
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_KEY
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'acumulados_por_empleado'
AND COLUMN_NAME = 'frecuencia';

-- Verificar datos existentes si el campo es ENUM
SELECT
    'Datos existentes en campo frecuencia (si es ENUM)' as info,
    frecuencia,
    COUNT(*) as cantidad
FROM acumulados_por_empleado
GROUP BY frecuencia
ORDER BY cantidad DESC;

-- =====================================================
-- PASO 2: RESPALDO DE DATOS
-- =====================================================

-- Crear tabla temporal para respaldo
DROP TABLE IF EXISTS acumulados_por_empleado_backup_frecuencia;

CREATE TABLE acumulados_por_empleado_backup_frecuencia AS
SELECT
    id,
    employee_id,
    concepto_id,
    planilla_id,
    frecuencia as frecuencia_original,
    mes,
    ano,
    created_at
FROM acumulados_por_empleado;

SELECT
    'PASO 2: Respaldo creado' as paso,
    COUNT(*) as registros_respaldados
FROM acumulados_por_empleado_backup_frecuencia;

-- =====================================================
-- PASO 3: AGREGAR NUEVA COLUMNA TEMPORAL
-- =====================================================

-- Solo ejecutar si el campo frecuencia es aún ENUM
SET @is_enum = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'acumulados_por_empleado'
    AND COLUMN_NAME = 'frecuencia'
    AND DATA_TYPE = 'enum'
);

-- Agregar columna temporal frecuencia_id solo si frecuencia es ENUM
SET @sql_add_column = IF(@is_enum > 0,
    'ALTER TABLE acumulados_por_empleado ADD COLUMN frecuencia_id INT NULL COMMENT "ID de la frecuencia desde tabla frecuencias" AFTER frecuencia',
    'SELECT "Campo frecuencia ya es INT, omitiendo paso" as message'
);

PREPARE stmt FROM @sql_add_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 4: MIGRAR DATOS ENUM → INT
-- =====================================================

-- Solo ejecutar si acabamos de agregar frecuencia_id
SET @sql_migrate_data = IF(@is_enum > 0,
    'UPDATE acumulados_por_empleado ape
     INNER JOIN frecuencias f ON (
         (ape.frecuencia = "QUINCENAL" AND f.nombre = "Quincenal") OR
         (ape.frecuencia = "MENSUAL" AND f.nombre = "Mensual") OR
         (ape.frecuencia = "SEMANAL" AND f.nombre = "Semanal") OR
         (ape.frecuencia = "ANUAL" AND f.nombre = "ANUAL") OR
         (ape.frecuencia = "ESPECIAL" AND f.nombre = "ESPECIAL") OR
         (ape.frecuencia = "DECIMO" AND f.nombre = "DECIMO") OR
         (ape.frecuencia = "LIQUIDACION" AND f.nombre = "Liquidación")
     )
     SET ape.frecuencia_id = f.id
     WHERE ape.frecuencia_id IS NULL',
    'SELECT "Datos ya migrados, omitiendo paso" as message'
);

PREPARE stmt FROM @sql_migrate_data;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar migración de datos
SELECT
    'PASO 4: Verificación migración datos' as paso,
    COUNT(*) as total_registros,
    COUNT(frecuencia_id) as registros_migrados,
    COUNT(*) - COUNT(frecuencia_id) as registros_sin_migrar
FROM acumulados_por_empleado
WHERE @is_enum > 0;

-- =====================================================
-- PASO 5: HACER COLUMNA TEMPORAL NOT NULL
-- =====================================================

SET @sql_not_null = IF(@is_enum > 0,
    'ALTER TABLE acumulados_por_empleado MODIFY COLUMN frecuencia_id INT NOT NULL',
    'SELECT "Campo ya NOT NULL, omitiendo paso" as message'
);

PREPARE stmt FROM @sql_not_null;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 6: AGREGAR FOREIGN KEY CONSTRAINT
-- =====================================================

-- Verificar si ya existe el constraint
SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'acumulados_por_empleado'
    AND CONSTRAINT_NAME = 'fk_acumulados_frecuencia'
);

SET @sql_fk = IF(@constraint_exists = 0 AND @is_enum > 0,
    'ALTER TABLE acumulados_por_empleado
     ADD CONSTRAINT fk_acumulados_frecuencia
     FOREIGN KEY (frecuencia_id) REFERENCES frecuencias(id)
     ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT "Foreign key ya existe o no necesario, omitiendo paso" as message'
);

PREPARE stmt FROM @sql_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 7: CREAR ÍNDICE
-- =====================================================

-- Verificar si ya existe el índice
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'acumulados_por_empleado'
    AND INDEX_NAME = 'idx_frecuencia_id'
);

SET @sql_index = IF(@index_exists = 0 AND @is_enum > 0,
    'CREATE INDEX idx_frecuencia_id ON acumulados_por_empleado(frecuencia_id)',
    'SELECT "Índice ya existe o no necesario, omitiendo paso" as message'
);

PREPARE stmt FROM @sql_index;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 8: ELIMINAR COLUMNA ENUM ANTIGUA
-- =====================================================

SET @sql_drop_enum = IF(@is_enum > 0,
    'ALTER TABLE acumulados_por_empleado DROP COLUMN frecuencia',
    'SELECT "Campo ENUM ya eliminado, omitiendo paso" as message'
);

PREPARE stmt FROM @sql_drop_enum;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 9: RENOMBRAR COLUMNA NUEVA
-- =====================================================

-- Verificar si existe frecuencia_id para renombrar
SET @temp_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'acumulados_por_empleado'
    AND COLUMN_NAME = 'frecuencia_id'
);

SET @sql_rename = IF(@temp_column_exists > 0,
    'ALTER TABLE acumulados_por_empleado
     CHANGE COLUMN frecuencia_id frecuencia INT NOT NULL
     COMMENT "ID de la frecuencia desde tabla frecuencias"',
    'SELECT "Campo ya renombrado, omitiendo paso" as message'
);

PREPARE stmt FROM @sql_rename;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 10: VERIFICACIÓN FINAL
-- =====================================================

-- Verificar estructura final
SELECT
    'PASO 10: Estructura final campo frecuencia' as paso,
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'acumulados_por_empleado'
AND COLUMN_NAME = 'frecuencia';

-- Verificar foreign key
SELECT
    'Foreign key constraint' as verificacion,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    UPDATE_RULE,
    DELETE_RULE
FROM information_schema.KEY_COLUMN_USAGE kcu
JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
    ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
WHERE kcu.TABLE_SCHEMA = DATABASE()
AND kcu.TABLE_NAME = 'acumulados_por_empleado'
AND kcu.COLUMN_NAME = 'frecuencia';

-- Verificar datos migrados
SELECT
    'Datos por frecuencia después de migración' as verificacion,
    f.id as frecuencia_id,
    f.nombre as frecuencia_nombre,
    COUNT(a.id) as registros_acumulados
FROM frecuencias f
LEFT JOIN acumulados_por_empleado a ON f.id = a.frecuencia
GROUP BY f.id, f.nombre
ORDER BY f.id;

-- Contar registros total
SELECT
    'Total registros después de migración' as verificacion,
    COUNT(*) as total_registros
FROM acumulados_por_empleado;

-- =====================================================
-- MENSAJE FINAL
-- =====================================================

SELECT
    '✅ MIGRACIÓN COMPLETADA EXITOSAMENTE' as status,
    'Campo frecuencia convertido de ENUM a INT con foreign key' as resultado,
    'Respaldo disponible en tabla acumulados_por_empleado_backup_frecuencia' as respaldo,
    NOW() as fecha_completado;

-- =====================================================
-- LIMPIEZA (OPCIONAL)
-- =====================================================

-- Descomentar la siguiente línea si quieres eliminar el respaldo
-- DROP TABLE IF EXISTS acumulados_por_empleado_backup_frecuencia;