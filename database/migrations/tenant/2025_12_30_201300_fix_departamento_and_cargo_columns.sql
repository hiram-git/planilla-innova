-- =====================================================
-- Migración: Fix departamento_id en cargos y cargo_id en funciones
-- Fecha: 30-Dic-2025 20:13
-- Descripción: Agregar columnas faltantes en nuevas empresas
-- =====================================================

-- TABLA CARGOS - departamento_id
SET @cargo_dep_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cargos'
    AND COLUMN_NAME = 'departamento_id'
);

SET @sql = IF(@cargo_dep_exists = 0,
    'ALTER TABLE cargos ADD COLUMN departamento_id INT NULL COMMENT ''FK al departamento (organigrama)'' AFTER descripcion',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @cargo_idx_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cargos'
    AND INDEX_NAME = 'idx_departamento'
);

SET @sql = IF(@cargo_idx_exists = 0,
    'CREATE INDEX idx_departamento ON cargos(departamento_id)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @cargo_fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cargos'
    AND CONSTRAINT_NAME = 'fk_cargos_departamento'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@cargo_fk_exists = 0,
    'ALTER TABLE cargos ADD CONSTRAINT fk_cargos_departamento FOREIGN KEY (departamento_id) REFERENCES organigrama(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- TABLA FUNCIONES - cargo_id
SET @funcion_cargo_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'funciones'
    AND COLUMN_NAME = 'cargo_id'
);

SET @sql = IF(@funcion_cargo_exists = 0,
    'ALTER TABLE funciones ADD COLUMN cargo_id INT NULL COMMENT ''FK opcional al cargo. NULL = función genérica'' AFTER descripcion',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @funcion_idx_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'funciones'
    AND INDEX_NAME = 'idx_cargo'
);

SET @sql = IF(@funcion_idx_exists = 0,
    'CREATE INDEX idx_cargo ON funciones(cargo_id)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @funcion_fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'funciones'
    AND CONSTRAINT_NAME = 'fk_funciones_cargo'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@funcion_fk_exists = 0,
    'ALTER TABLE funciones ADD CONSTRAINT fk_funciones_cargo FOREIGN KEY (cargo_id) REFERENCES cargos(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
