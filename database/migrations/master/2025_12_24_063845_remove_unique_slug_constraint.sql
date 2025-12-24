-- Migración: Eliminar restricción UNIQUE de slug en tabla tenants (BD master)
-- Fecha: 2025-12-24
-- Descripción: Permite múltiples instalaciones de la misma empresa
--              (reinstalaciones, renovaciones, configuraciones complejas)
--              El slug ahora incluirá timestamp para garantizar unicidad

-- IMPORTANTE: Esta migración debe ejecutarse en la base de datos MASTER (planilla_master)
-- NO en las bases de datos tenant

-- Verificar si existe el índice UNIQUE 'slug'
SET @index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tenants'
    AND INDEX_NAME = 'slug'
);

-- Eliminar índice UNIQUE del slug si existe
SET @sql = IF(@index_exists > 0,
    'ALTER TABLE tenants DROP INDEX slug',
    'SELECT "Index slug does not exist" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear índice NO-UNIQUE para slug (para búsquedas rápidas pero permite duplicados)
-- Primero verificar si el índice ya existe
SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tenants'
    AND INDEX_NAME = 'idx_tenants_slug'
);

SET @sql_idx = IF(@idx_exists = 0,
    'CREATE INDEX idx_tenants_slug ON tenants(slug)',
    'SELECT "Index idx_tenants_slug already exists" AS message'
);

PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- Agregar comentario a la columna slug para documentar el cambio
ALTER TABLE tenants
MODIFY COLUMN slug VARCHAR(80) NOT NULL
COMMENT 'Slug de la empresa (formato: nombre-empresa-TIMESTAMP) - permite duplicados para reinstalaciones';
