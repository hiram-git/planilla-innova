-- Migración: Agregar planilla_id a loan_installments (Idempotente)
-- Fecha: 2026-01-06
-- Descripción: Fix para error "Column 'planilla_id' not found" al reabrir planillas
-- Esta migración es IDEMPOTENTE - puede ejecutarse múltiples veces sin errores

-- ============================================================
-- 1. Verificar y agregar columna status a tabla loans
-- ============================================================
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'loans'
    AND COLUMN_NAME = 'status'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `loans` ADD COLUMN `status` ENUM(''activo'', ''completado'', ''anulado'') NOT NULL DEFAULT ''activo'' AFTER `start_date`',
    'SELECT "Columna loans.status ya existe" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 2. Migrar datos y actualizar ENUM de status en loan_installments
-- ============================================================
-- Valores actuales: 'generada', 'pagada', 'cancelada', 'anulado'
-- Valores nuevos: 'pendiente', 'pagada', 'anulada'

-- PASO 2A: Primero agregar los nuevos valores al ENUM sin eliminar los antiguos
SET @current_enum = (
    SELECT COLUMN_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'loan_installments'
    AND COLUMN_NAME = 'status'
);

-- Solo agregar nuevos valores si aún no existen
SET @sql = IF(
    @current_enum NOT LIKE '%pendiente%',
    'ALTER TABLE `loan_installments` MODIFY COLUMN `status` ENUM(''generada'', ''pendiente'', ''pagada'', ''cancelada'', ''anulado'', ''anulada'') NOT NULL DEFAULT ''pendiente''',
    'SELECT "ENUM loan_installments.status temporal ya tiene todos los valores" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- PASO 2B: Migrar datos antiguos a nuevos valores
-- 'generada' -> 'pendiente'
UPDATE `loan_installments` SET `status` = 'pendiente' WHERE `status` = 'generada';

-- 'cancelada' -> 'anulada'
UPDATE `loan_installments` SET `status` = 'anulada' WHERE `status` = 'cancelada';

-- 'anulado' -> 'anulada' (normalizar género)
UPDATE `loan_installments` SET `status` = 'anulada' WHERE `status` = 'anulado';

-- PASO 2C: Ahora sí, eliminar valores antiguos del ENUM
SET @sql = IF(
    @current_enum NOT LIKE '%pendiente%',
    'ALTER TABLE `loan_installments` MODIFY COLUMN `status` ENUM(''pendiente'', ''pagada'', ''anulada'') NOT NULL DEFAULT ''pendiente''',
    'SELECT "ENUM loan_installments.status ya actualizado (comprobación final)" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. Crear índice idx_status_loan si no existe
-- ============================================================
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'loan_installments'
    AND INDEX_NAME = 'idx_status_loan'
);

SET @sql = IF(@index_exists = 0,
    'CREATE INDEX idx_status_loan ON loan_installments(status, loan_id)',
    'SELECT "Índice idx_status_loan ya existe" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 4. Crear índice idx_employee_creditor_status si no existe
-- ============================================================
-- Primero verificar si la columna creditor_id existe
SET @creditor_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'loans'
    AND COLUMN_NAME = 'creditor_id'
);

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'loans'
    AND INDEX_NAME = 'idx_employee_creditor_status'
);

-- Solo crear índice si creditor_id existe y el índice no existe
SET @sql = IF(@creditor_column_exists = 1 AND @index_exists = 0,
    'CREATE INDEX idx_employee_creditor_status ON loans(employee_id, creditor_id, status)',
    IF(@creditor_column_exists = 0,
        'SELECT "Columna creditor_id no existe, saltando creación de índice" AS message',
        'SELECT "Índice idx_employee_creditor_status ya existe" AS message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 5. Agregar columna planilla_id (CRÍTICO PARA EL FIX)
-- ============================================================
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'loan_installments'
    AND COLUMN_NAME = 'planilla_id'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `loan_installments` ADD COLUMN `planilla_id` INT NULL AFTER `status`',
    'SELECT "Columna loan_installments.planilla_id ya existe" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 6. Agregar columna paid_date
-- ============================================================
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'loan_installments'
    AND COLUMN_NAME = 'paid_date'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `loan_installments` ADD COLUMN `paid_date` DATE NULL AFTER `planilla_id`',
    'SELECT "Columna loan_installments.paid_date ya existe" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 7. Agregar Foreign Key fk_installment_planilla si no existe
-- ============================================================
SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'loan_installments'
    AND CONSTRAINT_NAME = 'fk_installment_planilla'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE `loan_installments`
     ADD CONSTRAINT `fk_installment_planilla`
     FOREIGN KEY (`planilla_id`) REFERENCES `planilla_cabecera`(`id`)
     ON DELETE SET NULL
     ON UPDATE CASCADE',
    'SELECT "Foreign key fk_installment_planilla ya existe" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- Verificación final
-- ============================================================
SELECT
    'Migración completada exitosamente' AS status,
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'loan_installments' AND COLUMN_NAME = 'planilla_id') AS planilla_id_exists,
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'loan_installments' AND COLUMN_NAME = 'paid_date') AS paid_date_exists,
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'loan_installments' AND CONSTRAINT_NAME = 'fk_installment_planilla') AS fk_exists;
