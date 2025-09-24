-- Migration: Eliminar campo redundante estado_laboral ENUM
-- Fecha: 2025-09-20
-- Descripción: Eliminar campo estado_laboral ya que se usa situacion_id para manejar estados

USE planilla_innova;

-- Verificar datos antes de eliminar (para backup)
SELECT
    id,
    CONCAT(firstname, ' ', lastname) as empleado,
    situacion_id,
    estado_laboral,
    fecha_terminacion
FROM employees
WHERE estado_laboral != 'ACTIVO' OR situacion_id != 1
LIMIT 10;

-- Eliminar el campo estado_laboral redundante
ALTER TABLE employees
DROP COLUMN estado_laboral;

-- Verificar la estructura después del cambio
DESCRIBE employees;