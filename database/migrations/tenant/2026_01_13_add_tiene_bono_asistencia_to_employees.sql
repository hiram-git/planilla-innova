-- =====================================================
-- Migración: Agregar campo tiene_bono_asistencia
-- Fecha: 2026-01-13
-- Descripción: Agrega campo tiene_bono_asistencia a employees
--              para controlar qué empleados reciben bono de asistencia
-- =====================================================

-- 1. Agregar campo tiene_bono_asistencia a tabla employees
ALTER TABLE employees
ADD COLUMN tiene_bono_asistencia TINYINT(1) DEFAULT 0 COMMENT 'Indica si el empleado recibe bono de asistencia (0=No, 1=Sí)'
AFTER permite_horas_extras;

-- 2. Crear índice para optimizar consultas por bono de asistencia
CREATE INDEX idx_employees_tiene_bono_asistencia ON employees(tiene_bono_asistencia);

-- Notas:
-- - El campo tiene_bono_asistencia es similar a marca_asistencia y permite_horas_extras
-- - Este campo será usado en el calculador de planilla para validar si se aplica el concepto de bono de asistencia
-- - Los formularios de empleados permitirán marcar/desmarcar este checkbox
-- - Por defecto todos los empleados tienen valor 0 (no reciben bono)
