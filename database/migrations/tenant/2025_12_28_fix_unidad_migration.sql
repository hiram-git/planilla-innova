-- =====================================================
-- Script de Corrección: Verificar y Aplicar Cambio UNIDAD
-- Fecha: 2025-12-28
-- Tabla: planilla_detalle
-- =====================================================

-- 1. Verificar estado actual de la columna en planilla_detalle
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'planilla_detalle'
  AND COLUMN_NAME IN ('referencia_valor', 'unidad');

-- 2. Verificar si la migración está registrada
SELECT
    filename,
    status,
    executed_at,
    error_message
FROM migrations_history
WHERE filename LIKE '%unidad%'
ORDER BY executed_at DESC;

-- 3. Eliminar registro de migración fallida (si es necesario)
-- DELETE FROM migrations_history
-- WHERE filename = '2025_12_28_rename_referencia_valor_to_unidad.sql';

-- 4. Si la columna aún se llama 'referencia_valor', ejecutar el cambio
-- (Ejecutar manualmente solo si es necesario)
-- ALTER TABLE planilla_detalle
-- CHANGE COLUMN referencia_valor unidad VARCHAR(50) DEFAULT NULL
-- COMMENT 'Valor de la unidad base de cálculo del concepto';
