-- =====================================================
-- Migración: Agregar campo tarifa_hora
-- Fecha: 2025-12-12
-- Descripción: Agrega campo tarifa_hora a employees y employee_payroll_salaries
--              para almacenar la tarifa por hora de los empleados
-- =====================================================

-- 1. Agregar campo tarifa_hora a tabla employees
ALTER TABLE employees
ADD COLUMN tarifa_hora DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Tarifa por hora del empleado (usado en variable TARIFA_HORA del calculador)'
AFTER sueldo_individual;

-- 2. Agregar campo tarifa_hora a tabla employee_payroll_salaries
ALTER TABLE employee_payroll_salaries
ADD COLUMN tarifa_hora DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Tarifa por hora específica para este tipo de planilla'
AFTER gastos_representacion;

-- 3. Calcular tarifa_hora inicial para registros existentes en employees
--    (sueldo_individual ÷ 220 horas mensuales estándar)
UPDATE employees
SET tarifa_hora = ROUND(sueldo_individual / 220, 2)
WHERE sueldo_individual > 0;

-- 4. Calcular tarifa_hora inicial para registros existentes en employee_payroll_salaries
--    (sueldo_base ÷ 220 horas mensuales estándar)
UPDATE employee_payroll_salaries
SET tarifa_hora = ROUND(sueldo_base / 220, 2)
WHERE sueldo_base > 0;

-- 5. Crear índice para optimizar consultas por tarifa_hora
CREATE INDEX idx_employees_tarifa_hora ON employees(tarifa_hora);
CREATE INDEX idx_payroll_salaries_tarifa_hora ON employee_payroll_salaries(tarifa_hora);

-- Notas:
-- - La tarifa_hora se calcula como: salario_mensual ÷ 220 horas (8h/día × 22 días laborables promedio)
-- - Este campo será usado por la variable TARIFA_HORA en PlanillaConceptCalculator
-- - Los formularios de empleados permitirán editar manualmente este valor
-- - Se sugiere calcular automáticamente: sueldo_base ÷ 220 pero permitir override manual
