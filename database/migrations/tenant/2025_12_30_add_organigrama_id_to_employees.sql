-- =====================================================
-- Migración: Add organigrama_id to employees
-- Fecha: 30-Dic-2025
-- Descripción: Agregar columna organigrama_id a employees con verificación idempotente
-- =====================================================

-- 1. Verificar si existe la columna organigrama_id
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND COLUMN_NAME = 'organigrama_id'
);

-- 2. Agregar columna si no existe
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE employees ADD COLUMN organigrama_id INT(11) NULL COMMENT ''ID del elemento organizacional al que pertenece el empleado''',
    'SELECT "Column organigrama_id already exists, skipping" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Verificar si existe el índice
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND INDEX_NAME = 'idx_employees_organigrama'
);

-- 4. Crear índice si no existe
SET @sql = IF(@index_exists = 0,
    'CREATE INDEX idx_employees_organigrama ON employees(organigrama_id)',
    'SELECT "Index idx_employees_organigrama already exists, skipping" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Verificar si existe la foreign key
SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND CONSTRAINT_NAME = 'fk_employees_organigrama'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

-- 6. Crear foreign key si no existe
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE employees ADD CONSTRAINT fk_employees_organigrama FOREIGN KEY (organigrama_id) REFERENCES organigrama(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "FK fk_employees_organigrama already exists, skipping" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Comentario: Migración completada
-- La tabla employees ahora tiene la columna organigrama_id para relacionar empleados con el organigrama
