-- =====================================================
-- Script: Inicializar Calendario Empresarial 2025 Completo
-- Fecha: 06 de Noviembre, 2025
-- Descripción: Genera todos los 365 días del año 2025
-- =====================================================

-- Procedimiento temporal para generar todos los días
DELIMITER $$

DROP PROCEDURE IF EXISTS init_calendar_2025$$

CREATE PROCEDURE init_calendar_2025()
BEGIN
    DECLARE v_date DATE;
    DECLARE v_end_date DATE;
    DECLARE v_day_of_week INT;
    DECLARE v_day_type VARCHAR(20);
    DECLARE v_is_weekend TINYINT;
    DECLARE v_description VARCHAR(255);
    DECLARE v_day_name VARCHAR(20);

    SET v_date = '2025-01-01';
    SET v_end_date = '2025-12-31';

    WHILE v_date <= v_end_date DO
        -- Obtener día de la semana (1=Lunes, 7=Domingo)
        SET v_day_of_week = DAYOFWEEK(v_date);

        -- Convertir a formato ISO (1=Lunes, 7=Domingo)
        -- MySQL DAYOFWEEK: 1=Domingo, 2=Lunes, ..., 7=Sábado
        -- ISO: 1=Lunes, 2=Martes, ..., 7=Domingo
        IF v_day_of_week = 1 THEN
            SET v_day_of_week = 7; -- Domingo
        ELSE
            SET v_day_of_week = v_day_of_week - 1;
        END IF;

        -- Determinar si es fin de semana
        SET v_is_weekend = IF(v_day_of_week >= 6, 1, 0);

        -- Determinar tipo de día y descripción
        CASE v_day_of_week
            WHEN 1 THEN SET v_day_name = 'Lunes';
            WHEN 2 THEN SET v_day_name = 'Martes';
            WHEN 3 THEN SET v_day_name = 'Miércoles';
            WHEN 4 THEN SET v_day_name = 'Jueves';
            WHEN 5 THEN SET v_day_name = 'Viernes';
            WHEN 6 THEN SET v_day_name = 'Sábado';
            WHEN 7 THEN SET v_day_name = 'Domingo';
        END CASE;

        IF v_is_weekend = 1 THEN
            SET v_day_type = 'NO_LABORAL';
            SET v_description = v_day_name;
        ELSE
            SET v_day_type = 'LABORAL';
            SET v_description = CONCAT('Día laboral - ', v_day_name);
        END IF;

        -- Insertar solo si no existe (respeta feriados ya existentes)
        INSERT IGNORE INTO business_calendar (
            date_value,
            year_value,
            month_value,
            day_of_week,
            day_type,
            status,
            description,
            is_weekend
        ) VALUES (
            v_date,
            YEAR(v_date),
            MONTH(v_date),
            v_day_of_week,
            v_day_type,
            'NORMAL',
            v_description,
            v_is_weekend
        );

        -- Siguiente día
        SET v_date = DATE_ADD(v_date, INTERVAL 1 DAY);
    END WHILE;

END$$

DELIMITER ;

-- Ejecutar procedimiento
CALL init_calendar_2025();

-- Eliminar procedimiento temporal
DROP PROCEDURE IF EXISTS init_calendar_2025;

-- Verificar resultados
SELECT
    '===== RESUMEN CALENDARIO 2025 =====' as mensaje;

SELECT
    day_type,
    COUNT(*) as total_dias
FROM business_calendar
WHERE year_value = 2025
GROUP BY day_type
ORDER BY day_type;

SELECT
    CONCAT('Total días 2025: ', COUNT(*)) as resumen
FROM business_calendar
WHERE year_value = 2025;

-- Verificar días específicos de noviembre
SELECT
    '===== NOVIEMBRE 2025 =====' as mensaje;

SELECT
    date_value,
    day_type,
    description
FROM business_calendar
WHERE date_value BETWEEN '2025-11-01' AND '2025-11-10'
ORDER BY date_value;
