-- Migration: Update tarifa_hora precision from 2 to 4 decimals
-- Date: 2025-12-18
-- Purpose: Allow more precise hourly rate calculations

-- Update employees table
ALTER TABLE `employees`
MODIFY COLUMN `tarifa_hora` DECIMAL(10,4) NULL DEFAULT 0.0000
COMMENT 'Tarifa por hora del empleado (4 decimales de precisión)';

-- Update employee_payroll_salaries table
ALTER TABLE `employee_payroll_salaries`
MODIFY COLUMN `tarifa_hora` DECIMAL(10,4) NULL DEFAULT 0.0000
COMMENT 'Tarifa por hora del empleado para este tipo de planilla (4 decimales de precisión)';

-- Verify changes
SELECT 'Migration completed successfully' AS status;

SELECT
    'employees' AS tabla,
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'employees'
  AND COLUMN_NAME = 'tarifa_hora';

SELECT
    'employee_payroll_salaries' AS tabla,
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'employee_payroll_salaries'
  AND COLUMN_NAME = 'tarifa_hora';
