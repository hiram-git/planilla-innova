-- ==================================================
-- MIGRACIÓN: CAMPOS TERMINACIÓN EMPLEADOS
-- Descripción: Agregar campos fecha_terminacion y motivo_terminacion a tabla employees
-- Versión: 1.0
-- Fecha: 24 de Septiembre, 2025 - 15:20
-- ==================================================

-- AGREGAR CAMPOS DE TERMINACIÓN A LA TABLA EMPLOYEES
-- Estos campos son para mostrar información básica de terminación en vistas
-- La información completa está en employee_terminations

ALTER TABLE `employees`
ADD COLUMN IF NOT EXISTS `fecha_terminacion` DATE NULL DEFAULT NULL COMMENT 'Fecha de terminación del empleado (para vista rápida)',
ADD COLUMN IF NOT EXISTS `motivo_terminacion` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Motivo básico de terminación (para vista rápida)';

-- AGREGAR ÍNDICES PARA MEJOR PERFORMANCE
CREATE INDEX IF NOT EXISTS `idx_employees_fecha_terminacion` ON `employees` (`fecha_terminacion`);
CREATE INDEX IF NOT EXISTS `idx_employees_terminacion_composite` ON `employees` (`situacion_id`, `fecha_terminacion`);

-- ==================================================
-- VERIFICACIÓN DE MIGRACIÓN
-- ==================================================

-- Verificar que los campos fueron agregados
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'employees'
  AND COLUMN_NAME IN ('fecha_terminacion', 'motivo_terminacion');

-- Contar empleados con fecha de terminación
SELECT
    COUNT(*) as total_employees,
    COUNT(fecha_terminacion) as employees_with_termination,
    COUNT(CASE WHEN fecha_terminacion IS NOT NULL THEN 1 END) as terminated_count
FROM employees;

-- ==================================================
-- NOTAS DE IMPLEMENTACIÓN
-- ==================================================
/*
PROPÓSITO:
- Campos adicionales en employees para acceso rápido en vistas
- Complementan la información detallada en employee_terminations
- Mejoran performance al evitar JOINs innecesarios en listados simples

CAMPOS AGREGADOS:
1. fecha_terminacion (DATE, NULL): Fecha de terminación para vista rápida
2. motivo_terminacion (VARCHAR(255), NULL): Motivo básico para vista rápida

CONSIDERACIONES:
- Estos campos son redundantes con employee_terminations pero mejoran performance
- Se mantienen sincronizados mediante triggers o lógica de aplicación
- Los índices mejoran consultas de filtrado por empleados terminados

COMPATIBILIDAD:
- Compatible con sistema existente de liquidaciones
- No afecta funcionalidad actual
- Mejora performance en vistas de empleados
*/