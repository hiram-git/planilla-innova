-- Migración: Agregar campo referencia_valor a planilla_detalle
-- Fecha: 2025-01-16
-- Descripción: Campo para almacenar el valor de referencia específico para cada cálculo
--              según la unidad del concepto (días, horas, monto, porcentaje)

-- Agregar campo referencia_valor a planilla_detalle
ALTER TABLE planilla_detalle
ADD COLUMN referencia_valor DECIMAL(10,2) DEFAULT NULL
COMMENT 'Valor de referencia para cálculos según unidad del concepto (días, horas, monto, %)';

-- Crear índice para optimizar consultas
CREATE INDEX idx_planilla_detalle_referencia ON planilla_detalle(referencia_valor);

-- Comentario en la tabla
ALTER TABLE planilla_detalle
COMMENT = 'Tabla de detalles de planilla con valores de referencia para cálculos';