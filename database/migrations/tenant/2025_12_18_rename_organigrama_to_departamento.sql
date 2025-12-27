-- Migration: Rename organigrama_id to departamento_id in planilla_detalle
-- Date: 2025-12-18
-- Purpose: Fix column naming to match employees table structure

-- Paso 1: Renombrar la columna organigrama_id a departamento_id
ALTER TABLE `planilla_detalle`
CHANGE COLUMN `organigrama_id` `departamento_id` INT(11) NULL DEFAULT NULL;

-- Paso 2: Actualizar el índice si existe
-- Primero verificar si existe el índice fk_organigrama
SET @index_exists = (
    -- NOTA: SELECT comentado para evitar error PDO "pending result sets"
    -- SELECT COUNT(1)
    -- FROM INFORMATION_SCHEMA.STATISTICS
    -- WHERE TABLE_SCHEMA = DATABASE()
    -- AND TABLE_NAME = 'planilla_detalle'
    -- AND INDEX_NAME = 'fk_organigrama'
-- );

-- Si existe, eliminarlo
SET @drop_index_sql = IF(
    @index_exists > 0,
    'ALTER TABLE planilla_detalle DROP INDEX fk_organigrama',
    'SELECT "Index fk_organigrama does not exist" AS message'
);

PREPARE drop_index_stmt FROM @drop_index_sql;
EXECUTE drop_index_stmt;
DEALLOCATE PREPARE drop_index_stmt;

-- Crear nuevo índice con el nombre correcto
ALTER TABLE `planilla_detalle`
ADD INDEX `idx_departamento_id` (`departamento_id` ASC);

-- Opcional: Agregar comentario a la columna para documentación
ALTER TABLE `planilla_detalle`
MODIFY COLUMN `departamento_id` INT(11) NULL DEFAULT NULL
COMMENT 'ID del departamento organizacional del empleado';

-- Verificación final
SELECT
    'Migration completed successfully' AS status,
    COUNT(*) AS total_records,
    COUNT(DISTINCT departamento_id) AS unique_departamentos
FROM planilla_detalle;
