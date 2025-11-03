-- =====================================================
-- MIGRACIÓN: Agregar campo marca_asistencia a employees
-- Fecha: 03 de Noviembre, 2025
-- Versión: V3.5.5
-- Descripción: Campo checkbox para controlar si empleado
--              debe registrar marcaciones o pagar sueldo quincenal fijo
-- =====================================================

-- Agregar campo marca_asistencia
ALTER TABLE employees
ADD COLUMN marca_asistencia TINYINT(1) NOT NULL DEFAULT 0
COMMENT 'Indica si empleado registra marcaciones (1=Sí por horas, 0=No sueldo fijo)'
AFTER email;

-- Crear índice para optimizar queries de filtrado en sincronización
CREATE INDEX idx_marca_asistencia ON employees(marca_asistencia);

-- =====================================================
-- Verificación
-- =====================================================
SELECT
    'Campo marca_asistencia agregado exitosamente' as mensaje,
    COUNT(*) as total_empleados,
    SUM(CASE WHEN marca_asistencia = 1 THEN 1 ELSE 0 END) as con_marcacion,
    SUM(CASE WHEN marca_asistencia = 0 THEN 1 ELSE 0 END) as sin_marcacion
FROM employees;

-- =====================================================
-- NOTAS DE DEPLOYMENT
-- =====================================================
-- Por defecto, todos los empleados existentes tendrán marca_asistencia = 1
-- Si deseas cambiar empleados específicos a sueldo fijo (sin marcación):
--
-- UPDATE employees SET marca_asistencia = 0 WHERE id IN (1, 2, 3);
--
-- Lógica del sistema:
-- - marca_asistencia = 0: Pago sueldo quincenal fijo (NO sincronizar marcaciones)
-- - marca_asistencia = 1: Pago por horas trabajadas (SÍ sincronizar marcaciones)
-- =====================================================
