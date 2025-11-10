-- =====================================================
-- Script: Insertar Feriados Nacionales Panamá 2025
-- Fecha: 08 de Noviembre, 2025
-- Descripción: Inserta o actualiza feriados nacionales
-- =====================================================

-- Feriados Nacionales de Panamá 2025
INSERT INTO business_calendar (date_value, year_value, month_value, day_of_week, day_type, status, description, is_weekend)
VALUES
    -- Enero
    ('2025-01-01', 2025, 1, 3, 'FERIADO', 'NORMAL', 'Año Nuevo', 0),
    ('2025-01-09', 2025, 1, 4, 'FERIADO', 'NORMAL', 'Día de los Mártires', 0),

    -- Febrero - Carnaval (fechas móviles - verificar)
    ('2025-03-03', 2025, 3, 1, 'FERIADO', 'NORMAL', 'Lunes de Carnaval', 0),
    ('2025-03-04', 2025, 3, 2, 'FERIADO', 'NORMAL', 'Martes de Carnaval', 0),

    -- Abril - Semana Santa
    ('2025-04-18', 2025, 4, 5, 'FERIADO', 'NORMAL', 'Viernes Santo', 0),

    -- Mayo
    ('2025-05-01', 2025, 5, 4, 'FERIADO', 'NORMAL', 'Día del Trabajo', 0),

    -- Agosto
    ('2025-08-15', 2025, 8, 5, 'FERIADO', 'NORMAL', 'Fundación de Panamá La Vieja (Ciudad de Panamá)', 0),

    -- Noviembre
    ('2025-11-03', 2025, 11, 1, 'FERIADO', 'NORMAL', 'Día de la Separación de Colombia', 0),
    ('2025-11-04', 2025, 11, 2, 'FERIADO', 'NORMAL', 'Día de la Bandera', 0),
    ('2025-11-05', 2025, 11, 3, 'FERIADO', 'NORMAL', 'Día de Consolidación de la Separación (Colón)', 0),
    ('2025-11-10', 2025, 11, 1, 'FERIADO', 'NORMAL', 'Primer Grito de Independencia de la Villa de Los Santos', 0),
    ('2025-11-28', 2025, 11, 5, 'FERIADO', 'NORMAL', 'Independencia de Panamá de España', 0),

    -- Diciembre
    ('2025-12-08', 2025, 12, 1, 'FERIADO', 'NORMAL', 'Día de la Madre', 0),
    ('2025-12-25', 2025, 12, 4, 'FERIADO', 'NORMAL', 'Navidad', 0)

ON DUPLICATE KEY UPDATE
    day_type = VALUES(day_type),
    status = VALUES(status),
    description = VALUES(description),
    is_weekend = VALUES(is_weekend);

-- Verificar feriados insertados
SELECT
    '===== FERIADOS NACIONALES PANAMÁ 2025 =====' as mensaje;

SELECT
    date_value,
    day_type,
    description,
    CASE DAYOFWEEK(date_value)
        WHEN 1 THEN 'Domingo'
        WHEN 2 THEN 'Lunes'
        WHEN 3 THEN 'Martes'
        WHEN 4 THEN 'Miércoles'
        WHEN 5 THEN 'Jueves'
        WHEN 6 THEN 'Viernes'
        WHEN 7 THEN 'Sábado'
    END as dia_semana
FROM business_calendar
WHERE year_value = 2025
AND day_type = 'FERIADO'
ORDER BY date_value;

-- Resumen de tipos de día
SELECT
    '===== RESUMEN POR TIPO DE DÍA =====' as mensaje;

SELECT
    day_type,
    status,
    COUNT(*) as total
FROM business_calendar
WHERE year_value = 2025
GROUP BY day_type, status
ORDER BY day_type, status;
