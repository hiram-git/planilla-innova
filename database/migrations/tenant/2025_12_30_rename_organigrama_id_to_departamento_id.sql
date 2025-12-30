-- =====================================================
-- Migración: Rename organigrama_id to departamento_id in employees
-- Fecha: 30-Dic-2025
-- Descripción: Renombrar organigrama_id a departamento_id para mayor claridad semántica
-- =====================================================

-- 1. Verificar si existe la columna organigrama_id
SET @organigrama_id_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND COLUMN_NAME = 'organigrama_id'
);

-- 2. Verificar si ya existe departamento_id
SET @departamento_id_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND COLUMN_NAME = 'departamento_id'
);

-- 3. Renombrar organigrama_id a departamento_id si existe y departamento_id no existe
SET @sql = IF(@organigrama_id_exists > 0 AND @departamento_id_exists = 0,
    'ALTER TABLE employees CHANGE COLUMN organigrama_id departamento_id INT(11) NULL COMMENT ''FK al departamento (organigrama) del empleado''',
    'SELECT "Column already renamed or does not exist" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Verificar si existe el índice antiguo idx_employees_organigrama
SET @old_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND INDEX_NAME = 'idx_employees_organigrama'
);

-- 5. Eliminar índice antiguo si existe
SET @sql = IF(@old_index_exists > 0,
    'DROP INDEX idx_employees_organigrama ON employees',
    'SELECT "Index idx_employees_organigrama does not exist" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Verificar si existe el nuevo índice idx_departamento
SET @new_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND INDEX_NAME = 'idx_departamento'
);

-- 7. Crear nuevo índice si no existe
SET @sql = IF(@new_index_exists = 0,
    'CREATE INDEX idx_departamento ON employees(departamento_id)',
    'SELECT "Index idx_departamento already exists" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. Verificar si existe la FK antigua fk_employees_organigrama
SET @old_fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND CONSTRAINT_NAME = 'fk_employees_organigrama'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

-- 9. Eliminar FK antigua si existe
SET @sql = IF(@old_fk_exists > 0,
    'ALTER TABLE employees DROP FOREIGN KEY fk_employees_organigrama',
    'SELECT "FK fk_employees_organigrama does not exist" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 10. Verificar si existe la nueva FK fk_employees_departamento
SET @new_fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND CONSTRAINT_NAME = 'fk_employees_departamento'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

-- 11. Crear nueva FK si no existe
SET @sql = IF(@new_fk_exists = 0,
    'ALTER TABLE employees ADD CONSTRAINT fk_employees_departamento FOREIGN KEY (departamento_id) REFERENCES organigrama(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "FK fk_employees_departamento already exists" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Comentario: Migración completada
-- La tabla employees ahora usa departamento_id en lugar de organigrama_id
