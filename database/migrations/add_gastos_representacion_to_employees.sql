-- Migration: Agregar campo gastos_representacion a tabla employees
-- Fecha: 2025-09-20
-- Descripción: Campo para manejar gastos de representación por empleado

ALTER TABLE employees
ADD COLUMN gastos_representacion DECIMAL(10,2) DEFAULT 0.00
COMMENT 'Gastos de representación asignados al empleado';

-- Verificar que el campo se agregó correctamente
DESCRIBE employees;