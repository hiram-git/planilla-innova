-- Migración: Agregar campo tipo_acumulado a tabla acumulados_por_empleado
-- Fecha: 2025-09-18
-- Propósito: Permitir categorizar los acumulados por tipo específico

-- Agregar nueva columna tipo_acumulado
ALTER TABLE acumulados_por_empleado
ADD COLUMN tipo_acumulado VARCHAR(50) NULL
COMMENT 'Tipo específico de acumulado (XIII_MES, VACACIONES, etc.)'
AFTER tipo_concepto;

-- Crear índice para mejorar consultas por tipo_acumulado
CREATE INDEX idx_tipo_acumulado ON acumulados_por_empleado(tipo_acumulado);