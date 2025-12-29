-- =====================================================
-- Migración: Renombrar columna referencia_valor a unidad en planilla_detalle
-- Fecha: 2025-12-28
-- Descripción: Cambiar nombre de columna para mejor semántica
--              El campo "unidad" en planilla_detalle almacena el valor
--              de la unidad base de cálculo para cada concepto aplicado
--              a cada empleado en una planilla específica.
-- =====================================================

-- Renombrar la columna en planilla_detalle
ALTER TABLE planilla_detalle
CHANGE COLUMN referencia_valor unidad VARCHAR(50) DEFAULT NULL
COMMENT 'Valor de la unidad base de cálculo del concepto';
