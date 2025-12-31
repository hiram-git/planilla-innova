-- =====================================================
-- Migración: Rename organigrama_id to departamento_id
-- Fecha: 30-Dic-2025 20:14
-- Descripción: Renombrar organigrama_id a departamento_id en employees y cargos
-- =====================================================

-- TABLA EMPLOYEES
SET @emp_org_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND COLUMN_NAME = 'organigrama_id'
);

SET @emp_dep_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND COLUMN_NAME = 'departamento_id'
);

SET @emp_old_fk = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND CONSTRAINT_NAME = 'fk_employees_organigrama'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@emp_old_fk > 0,
    'ALTER TABLE employees DROP FOREIGN KEY fk_employees_organigrama',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @emp_old_index = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND INDEX_NAME = 'idx_employees_organigrama'
);

SET @sql = IF(@emp_old_index > 0,
    'DROP INDEX idx_employees_organigrama ON employees',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@emp_org_exists > 0 AND @emp_dep_exists = 0,
    'ALTER TABLE employees CHANGE COLUMN organigrama_id departamento_id INT(11) NULL COMMENT ''FK al departamento (organigrama) del empleado''',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @emp_new_index = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND INDEX_NAME = 'idx_departamento'
);

SET @sql = IF(@emp_new_index = 0,
    'CREATE INDEX idx_departamento ON employees(departamento_id)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @emp_new_fk = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND CONSTRAINT_NAME = 'fk_employees_departamento'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@emp_new_fk = 0,
    'ALTER TABLE employees ADD CONSTRAINT fk_employees_departamento FOREIGN KEY (departamento_id) REFERENCES organigrama(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- TABLA CARGOS
SET @cargo_org_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cargos'
    AND COLUMN_NAME = 'organigrama_id'
);

SET @cargo_dep_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cargos'
    AND COLUMN_NAME = 'departamento_id'
);

SET @cargo_old_fk = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cargos'
    AND CONSTRAINT_NAME = 'fk_cargos_organigrama'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@cargo_old_fk > 0,
    'ALTER TABLE cargos DROP FOREIGN KEY fk_cargos_organigrama',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@cargo_org_exists > 0 AND @cargo_dep_exists = 0,
    'ALTER TABLE cargos CHANGE COLUMN organigrama_id departamento_id INT(11) NULL COMMENT ''FK al departamento (organigrama)''',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @cargo_new_fk = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cargos'
    AND CONSTRAINT_NAME = 'fk_cargos_departamento'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@cargo_new_fk = 0,
    'ALTER TABLE cargos ADD CONSTRAINT fk_cargos_departamento FOREIGN KEY (departamento_id) REFERENCES organigrama(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
