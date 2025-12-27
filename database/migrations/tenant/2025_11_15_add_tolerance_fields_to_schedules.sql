-- =====================================================
-- Migración: Agregar campos de tolerancia a schedules
-- Fecha: 15 de Noviembre, 2025
-- Propósito: Implementar tolerancias configurables para cada tipo de marcación
-- Impacto: Permite configurar rangos de tolerancia antes/después para cada marcación
-- =====================================================

-- ========================================
-- PASO 1: Agregar campos de tolerancia
-- ========================================

ALTER TABLE schedules
ADD COLUMN time_in_tolerance_before SMALLINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Minutos antes de entrada permitidos (ej: 30 = puede entrar hasta 30 min antes)'
    AFTER entrada_almuerzo,

ADD COLUMN time_in_tolerance_after SMALLINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Minutos después de entrada permitidos sin tardanza (ej: 15 = puede llegar 15 min tarde sin penalización)'
    AFTER time_in_tolerance_before,

ADD COLUMN time_out_tolerance_before SMALLINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Minutos antes de salida permitidos sin penalización'
    AFTER time_in_tolerance_after,

ADD COLUMN time_out_tolerance_after SMALLINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Minutos después de salida permitidos (para horas extras aprobables)'
    AFTER time_out_tolerance_before,

ADD COLUMN lunch_out_tolerance_before SMALLINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Minutos antes de salida a almuerzo permitidos'
    AFTER time_out_tolerance_after,

ADD COLUMN lunch_out_tolerance_after SMALLINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Minutos después de salida a almuerzo permitidos'
    AFTER lunch_out_tolerance_before,

ADD COLUMN lunch_in_tolerance_before SMALLINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Minutos antes de entrada de almuerzo permitidos'
    AFTER lunch_out_tolerance_after,

ADD COLUMN lunch_in_tolerance_after SMALLINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Minutos después de entrada de almuerzo permitidos sin penalización'
    AFTER lunch_in_tolerance_before;

-- ========================================
-- PASO 2: Establecer valores por defecto razonables
-- ========================================

-- Configuración conservadora por defecto:
-- Entrada: puede llegar hasta 5 minutos tarde sin penalización
-- Salida: puede salir hasta 10 minutos antes sin penalización
-- Almuerzo: 5 minutos de tolerancia en cada marcación

UPDATE schedules SET
    time_in_tolerance_before = 15,      -- Puede llegar 15 min antes
    time_in_tolerance_after = 5,        -- Puede llegar 5 min tarde sin tardanza
    time_out_tolerance_before = 10,     -- Puede salir 10 min antes
    time_out_tolerance_after = 30,      -- Puede quedarse 30 min después (horas extras)
    lunch_out_tolerance_before = 5,     -- 5 min antes salida almuerzo
    lunch_out_tolerance_after = 5,      -- 5 min después salida almuerzo
    lunch_in_tolerance_before = 5,      -- 5 min antes entrada almuerzo
    lunch_in_tolerance_after = 10       -- 10 min después entrada almuerzo
WHERE activo = 1;

-- ========================================
-- PASO 3: Crear índices para optimización
-- ========================================

-- Índice compuesto para búsquedas rápidas de horarios activos
CREATE INDEX idx_schedules_active_tolerances
ON schedules(activo, time_in_tolerance_after, time_out_tolerance_after);

-- ========================================
-- VERIFICACIÓN
-- ========================================

-- Consulta para verificar que los campos se agregaron correctamente
-- SELECT codigo, nombre,
--        time_in, time_in_tolerance_before, time_in_tolerance_after,
--        time_out, time_out_tolerance_before, time_out_tolerance_after
-- FROM schedules
-- WHERE activo = 1
-- LIMIT 5;

-- ========================================
-- ROLLBACK (si es necesario)
-- ========================================

-- Para revertir esta migración:
/*
ALTER TABLE schedules
DROP COLUMN time_in_tolerance_before,
DROP COLUMN time_in_tolerance_after,
DROP COLUMN time_out_tolerance_before,
DROP COLUMN time_out_tolerance_after,
DROP COLUMN lunch_out_tolerance_before,
DROP COLUMN lunch_out_tolerance_after,
DROP COLUMN lunch_in_tolerance_before,
DROP COLUMN lunch_in_tolerance_after;

DROP INDEX idx_schedules_active_tolerances ON schedules;
*/

-- ========================================
-- NOTAS DE IMPLEMENTACIÓN
-- ========================================

/*
NOTAS IMPORTANTES:

1. TOLERANCIA ANTES vs DESPUÉS:
   - BEFORE: Permite marcación anticipada (ej: llegar 30 min antes está permitido)
   - AFTER: Permite marcación tardía (ej: llegar 15 min tarde no se cuenta como tardanza)

2. CASOS DE USO:
   - time_in_tolerance_after = 5: El empleado puede llegar hasta 5 min tarde sin ser marcado como tarde
   - time_in_tolerance_after = 0: Cualquier minuto de retraso cuenta como tardanza
   - time_out_tolerance_after = 30: Puede quedarse hasta 30 min después de su hora de salida

3. HORAS EXTRAS:
   - Si un empleado sale DESPUÉS de (time_out + time_out_tolerance_after),
     esos minutos adicionales se calcularán como horas extras PENDIENTES de aprobación

4. VALORES RECOMENDADOS:
   - Empresas flexibles: tolerance_after = 10-15 min
   - Empresas estrictas: tolerance_after = 0-5 min
   - Industrias 24/7: tolerance_after puede ser mayor

5. COMPATIBILIDAD:
   - Los cálculos existentes seguirán funcionando (DEFAULT 0 = comportamiento actual)
   - Los horarios antiguos tendrán tolerancia 0 hasta que se actualicen

6. SIGUIENTE PASO:
   - Modificar WorkScheduleResolver.php para usar estos campos
   - Actualizar AttendanceCalculator.php para aplicar tolerancias
*/
