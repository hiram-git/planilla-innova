-- =====================================================
-- Migración: Agregar Campos de Almuerzo a Horarios
-- Fecha: 2025-11-03
-- Descripción: Agrega campos salida_almuerzo y entrada_almuerzo
--              a la tabla schedules para gestionar el período de almuerzo
-- =====================================================


-- Agregar campos de período de almuerzo a schedules
ALTER TABLE schedules
ADD COLUMN salida_almuerzo TIME NULL
    COMMENT 'Hora programada de salida a almuerzo'
    AFTER time_out,
ADD COLUMN entrada_almuerzo TIME NULL
    COMMENT 'Hora programada de entrada después de almuerzo'
    AFTER salida_almuerzo;

-- Índice para mejorar consultas que filtren por horarios con almuerzo
CREATE INDEX idx_lunch_schedule ON schedules(salida_almuerzo, entrada_almuerzo);

-- Actualizar horarios existentes con valores predeterminados (ejemplo: 12:00-13:00)
-- Opcional: Puedes descomentar si deseas establecer un período de almuerzo por defecto
-- UPDATE schedules
-- SET salida_almuerzo = '12:00:00',
--     entrada_almuerzo = '13:00:00'
-- WHERE salida_almuerzo IS NULL
--   AND entrada_almuerzo IS NULL;

-- =====================================================
-- Verificación
-- =====================================================
-- NOTA: SELECT comentado para evitar error PDO "pending result sets"
-- SELECT 'Migración completada: Campos de almuerzo agregados a schedules' as status;
-- DESCRIBE schedules;
