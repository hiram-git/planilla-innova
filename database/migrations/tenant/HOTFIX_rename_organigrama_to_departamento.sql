-- =====================================================
-- HOTFIX: Rename organigrama_id to departamento_id
-- Fecha: 30-Dic-2025
-- Descripción: Script de emergencia para producción
-- Ejecuta el renombrado de forma segura y reversible
-- =====================================================

-- ========================================
-- PASO 1: TABLA EMPLOYEES
-- ========================================

-- Verificar si existe organigrama_id en employees
SET @emp_org_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND COLUMN_NAME = 'organigrama_id'
);

-- Verificar si ya existe departamento_id en employees
SET @emp_dep_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND COLUMN_NAME = 'departamento_id'
);

-- Renombrar columna en employees si existe y departamento_id no existe
SET @sql_emp = IF(@emp_org_exists > 0 AND @emp_dep_exists = 0,
    'ALTER TABLE employees CHANGE COLUMN organigrama_id departamento_id INT(11) NULL COMMENT ''FK al departamento (organigrama) del empleado''',
    'SELECT "Employees: Column already renamed or does not exist" AS notice'
);

PREPARE stmt FROM @sql_emp;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar índice antiguo en employees si existe
SET @emp_old_index = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND INDEX_NAME = 'idx_employees_organigrama'
);

SET @sql_emp_idx_drop = IF(@emp_old_index > 0,
    'DROP INDEX idx_employees_organigrama ON employees',
    'SELECT "Employees: Old index does not exist" AS notice'
);

PREPARE stmt FROM @sql_emp_idx_drop;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear nuevo índice en employees si no existe
SET @emp_new_index = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND INDEX_NAME = 'idx_departamento'
);

SET @sql_emp_idx_new = IF(@emp_new_index = 0,
    'CREATE INDEX idx_departamento ON employees(departamento_id)',
    'SELECT "Employees: New index already exists" AS notice'
);

PREPARE stmt FROM @sql_emp_idx_new;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar FK antigua en employees si existe
SET @emp_old_fk = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND CONSTRAINT_NAME = 'fk_employees_organigrama'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql_emp_fk_drop = IF(@emp_old_fk > 0,
    'ALTER TABLE employees DROP FOREIGN KEY fk_employees_organigrama',
    'SELECT "Employees: Old FK does not exist" AS notice'
);

PREPARE stmt FROM @sql_emp_fk_drop;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear nueva FK en employees si no existe
SET @emp_new_fk = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND CONSTRAINT_NAME = 'fk_employees_departamento'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql_emp_fk_new = IF(@emp_new_fk = 0,
    'ALTER TABLE employees ADD CONSTRAINT fk_employees_departamento FOREIGN KEY (departamento_id) REFERENCES organigrama(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "Employees: New FK already exists" AS notice'
);

PREPARE stmt FROM @sql_emp_fk_new;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ========================================
-- PASO 2: TABLA CARGOS
-- ========================================

-- Verificar si existe organigrama_id en cargos
SET @cargo_org_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cargos'
    AND COLUMN_NAME = 'organigrama_id'
);

-- Verificar si ya existe departamento_id en cargos
SET @cargo_dep_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cargos'
    AND COLUMN_NAME = 'departamento_id'
);

-- Renombrar columna en cargos si existe y departamento_id no existe
SET @sql_cargo = IF(@cargo_org_exists > 0 AND @cargo_dep_exists = 0,
    'ALTER TABLE cargos CHANGE COLUMN organigrama_id departamento_id INT(11) NULL COMMENT ''FK al departamento (organigrama)''',
    'SELECT "Cargos: Column already renamed or does not exist" AS notice'
);

PREPARE stmt FROM @sql_cargo;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar FK antigua en cargos si existe
SET @cargo_old_fk = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cargos'
    AND CONSTRAINT_NAME = 'fk_cargos_organigrama'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql_cargo_fk_drop = IF(@cargo_old_fk > 0,
    'ALTER TABLE cargos DROP FOREIGN KEY fk_cargos_organigrama',
    'SELECT "Cargos: Old FK does not exist" AS notice'
);

PREPARE stmt FROM @sql_cargo_fk_drop;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear nueva FK en cargos si no existe
SET @cargo_new_fk = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cargos'
    AND CONSTRAINT_NAME = 'fk_cargos_departamento'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql_cargo_fk_new = IF(@cargo_new_fk = 0,
    'ALTER TABLE cargos ADD CONSTRAINT fk_cargos_departamento FOREIGN KEY (departamento_id) REFERENCES organigrama(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "Cargos: New FK already exists" AS notice'
);

PREPARE stmt FROM @sql_cargo_fk_new;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ========================================
-- VERIFICACIÓN FINAL
-- ========================================

SELECT '=== VERIFICACIÓN FINAL ===' AS status;

SELECT
    CASE
        WHEN COUNT(*) > 0 THEN '✅ Employees: departamento_id EXISTS'
        ELSE '❌ Employees: departamento_id NOT FOUND'
    END AS employees_check
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'employees'
AND COLUMN_NAME = 'departamento_id';

SELECT
    CASE
        WHEN COUNT(*) > 0 THEN '✅ Cargos: departamento_id EXISTS'
        ELSE '❌ Cargos: departamento_id NOT FOUND'
    END AS cargos_check
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'cargos'
AND COLUMN_NAME = 'departamento_id';

-- Comentario: Migración completada
-- Las tablas employees y cargos ahora usan departamento_id en lugar de organigrama_id
