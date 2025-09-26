-- Fix: Agregar foreign key constraint faltante en base local
-- Fecha: 2025-09-26
-- Propósito: Sincronizar constraint foreign key frecuencia con base de producción

-- Verificar que no existe ya el constraint
SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'planilla_innova'
    AND TABLE_NAME = 'acumulados_por_empleado'
    AND CONSTRAINT_NAME = 'fk_acumulados_frecuencia'
);

-- Solo agregar si no existe
SET @sql = IF(@constraint_exists = 0,
    'ALTER TABLE acumulados_por_empleado ADD CONSTRAINT fk_acumulados_frecuencia FOREIGN KEY (frecuencia) REFERENCES frecuencias(id) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT "Foreign key constraint already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear índice si no existe
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'planilla_innova'
    AND TABLE_NAME = 'acumulados_por_empleado'
    AND INDEX_NAME = 'idx_frecuencia_id'
);

SET @sql = IF(@index_exists = 0,
    'CREATE INDEX idx_frecuencia_id ON acumulados_por_empleado(frecuencia)',
    'SELECT "Index already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificación final
SELECT
    'Foreign key constraint added successfully' as status,
    COUNT(*) as constraint_count
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'planilla_innova'
AND TABLE_NAME = 'acumulados_por_empleado'
AND CONSTRAINT_NAME = 'fk_acumulados_frecuencia';