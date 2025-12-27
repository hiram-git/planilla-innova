-- =====================================================
-- Migración: Agregar campo is_paid_holiday a business_calendar
-- Fecha: 13 de Noviembre, 2025
-- Propósito: Identificar feriados pagados por el empleador
--            para generar 8 horas trabajadas automáticas
-- =====================================================

-- Agregar columna is_paid_holiday
ALTER TABLE business_calendar
ADD COLUMN is_paid_holiday TINYINT(1) NOT NULL DEFAULT 0
COMMENT 'Indica si el feriado es pagado por el empleador (genera 8 horas en asistencias)'
AFTER status;

-- Crear índice para consultas optimizadas
CREATE INDEX idx_paid_holiday ON business_calendar(is_paid_holiday, day_type);

-- Marcar TODOS los feriados nacionales de 2025 como pagados por defecto
-- (se puede ajustar manualmente después desde la UI)
UPDATE business_calendar
SET is_paid_holiday = 1
WHERE year_value = 2025
AND day_type = 'FERIADO';

-- Verificar resultado
SELECT
    date_value,
    description,
    day_type,
    is_paid_holiday,
    CASE
        WHEN is_paid_holiday = 1 THEN 'SÍ - Genera 8h automáticas'
        ELSE 'NO - Sin generación automática'
    END as estado_pago
FROM business_calendar
WHERE year_value = 2025
AND day_type = 'FERIADO'
ORDER BY date_value;

-- =====================================================
-- Notas de Deployment:
-- 1. Ejecutar esta migración ANTES de implementar el código
-- 2. Todos los feriados 2025 quedan marcados como pagados
-- 3. Se puede modificar individualmente desde la UI
-- 4. El índice optimiza queries de processDay() en asistencias
-- =====================================================
