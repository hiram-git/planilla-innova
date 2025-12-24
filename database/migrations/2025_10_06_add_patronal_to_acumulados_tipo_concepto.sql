-- =====================================================
-- Migración: Agregar 'PATRONAL' al campo tipo_concepto
-- Tabla: acumulados_por_empleado
-- Fecha: 2025-10-06
-- Descripción: Agrega el valor 'PATRONAL' al ENUM tipo_concepto
--              para permitir clasificar conceptos patronales
--              en el sistema de acumulados
-- =====================================================


-- Verificar la estructura actual antes del cambio
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE
FROM
    INFORMATION_SCHEMA.COLUMNS
WHERE
    TABLE_SCHEMA = 'planilla_innova29092025'
    AND TABLE_NAME = 'acumulados_por_empleado'
    AND COLUMN_NAME = 'tipo_concepto';

-- Modificar el campo tipo_concepto para incluir 'PATRONAL'
ALTER TABLE acumulados_por_empleado
MODIFY COLUMN tipo_concepto ENUM('ASIGNACION', 'DEDUCCION', 'PATRONAL') NOT NULL;

-- Verificar el cambio
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE
FROM
    INFORMATION_SCHEMA.COLUMNS
WHERE
    TABLE_SCHEMA = 'planilla_innova29092025'
    AND TABLE_NAME = 'acumulados_por_empleado'
    AND COLUMN_NAME = 'tipo_concepto';

-- Contar registros existentes por tipo_concepto
SELECT
    tipo_concepto,
    COUNT(*) as total_registros
FROM
    acumulados_por_empleado
GROUP BY
    tipo_concepto
ORDER BY
    tipo_concepto;

-- =====================================================
-- ROLLBACK (Si es necesario revertir)
-- =====================================================
-- ALTER TABLE acumulados_por_empleado
-- MODIFY COLUMN tipo_concepto ENUM('ASIGNACION', 'DEDUCCION') NOT NULL;
-- =====================================================

-- Notas de implementación:
-- 1. Este cambio es compatible hacia adelante (no rompe datos existentes)
-- 2. Los registros con tipo_concepto = 'PATRONAL' ya pueden existir en BD
-- 3. Validar que la aplicación maneje correctamente los 3 tipos
-- 4. Actualizar vistas y reportes que filtren por tipo_concepto
-- 5. Esta migración es segura de ejecutar múltiples veces (idempotente)

-- =====================================================
-- ÉXITO: Migración completada exitosamente
-- =====================================================
