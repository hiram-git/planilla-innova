-- version: 2026_05_13_000001
-- description: Agregar campos lunch_flexible y lunch_flexible_minutes a schedules
--
-- Permite configurar almuerzo flexible: el empleado puede tomar el almuerzo
-- a cualquier hora dentro de su jornada y se descuenta una cantidad fija de
-- minutos sin penalizar exceso ni tardanza por almuerzo.
--
-- Comportamiento esperado en el motor de cálculos:
--   - lunch_flexible = 0  → comportamiento actual (horario rígido con tolerancias)
--   - lunch_flexible = 1  → ignora salida_almuerzo/entrada_almuerzo programadas y
--                           descuenta lunch_flexible_minutes del total trabajado

-- =====================================================
-- 1. Agregar columna lunch_flexible (solo si no existe)
-- =====================================================
SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'schedules'
      AND COLUMN_NAME = 'lunch_flexible'
);

SET @sql := IF(@col_exists = 0,
    'ALTER TABLE schedules ADD COLUMN lunch_flexible TINYINT(1) NOT NULL DEFAULT 0 AFTER entrada_almuerzo',
    'SELECT "Column lunch_flexible already exists, skipping" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 2. Agregar columna lunch_flexible_minutes (solo si no existe)
-- =====================================================
SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'schedules'
      AND COLUMN_NAME = 'lunch_flexible_minutes'
);

SET @sql := IF(@col_exists = 0,
    'ALTER TABLE schedules ADD COLUMN lunch_flexible_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER lunch_flexible',
    'SELECT "Column lunch_flexible_minutes already exists, skipping" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 3. Verificación
-- =====================================================
-- SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE()
--   AND TABLE_NAME = 'schedules'
--   AND COLUMN_NAME IN ('lunch_flexible', 'lunch_flexible_minutes');
