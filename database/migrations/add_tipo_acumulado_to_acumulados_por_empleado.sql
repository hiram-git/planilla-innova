-- Migración: Agregar campo tipo_acumulado a tabla acumulados_por_empleado
-- Fecha: 2025-09-18
-- Propósito: Permitir categorizar los acumulados por tipo específico

USE planilla_innova;

-- Verificar y agregar campo tipo_acumulado solo si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE table_name='acumulados_por_empleado'
     AND table_schema='planilla_innova'
     AND column_name='tipo_acumulado') = 0,
    'ALTER TABLE acumulados_por_empleado ADD COLUMN tipo_acumulado VARCHAR(50) NULL COMMENT ''Tipo específico de acumulado (XIII_MES, VACACIONES, etc.)'' AFTER tipo_concepto',
    'SELECT ''Campo tipo_acumulado ya existe'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear índice para mejorar consultas por tipo_acumulado
SET @sql = 'CREATE INDEX `idx_tipo_acumulado` ON `acumulados_por_empleado` (`tipo_acumulado`)';
SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE table_schema='planilla_innova'
               AND table_name='acumulados_por_empleado'
               AND index_name='idx_tipo_acumulado') = 0, @sql, 'SELECT ''Índice idx_tipo_acumulado ya existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Mostrar estructura actualizada
DESCRIBE acumulados_por_empleado;