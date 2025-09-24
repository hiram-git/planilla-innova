-- =====================================================
-- CALENDARIO EMPRESARIAL - PANAMÁ
-- Sistema de días laborables según legislación panameña
-- =====================================================

CREATE TABLE IF NOT EXISTS business_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_value DATE NOT NULL UNIQUE,
    day_type ENUM('LABORAL', 'NO_LABORAL', 'FERIADO', 'DUELO_NACIONAL', 'ESPECIAL') NOT NULL DEFAULT 'LABORAL',
    status ENUM('NORMAL', 'RECUPERABLE', 'MEDIO_DIA', 'HORARIO_ESPECIAL') NOT NULL DEFAULT 'NORMAL',
    description VARCHAR(255) NULL,
    is_weekend BOOLEAN DEFAULT FALSE,
    year_value YEAR NOT NULL,
    month_value TINYINT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '1=Lunes, 7=Domingo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_date (date_value),
    INDEX idx_year_month (year_value, month_value),
    INDEX idx_day_type (day_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERTAR FERIADOS NACIONALES DE PANAMÁ 2024-2025
-- =====================================================

INSERT IGNORE INTO business_calendar (date_value, day_type, status, description, is_weekend, year_value, month_value, day_of_week) VALUES
-- FERIADOS FIJOS ANUALES
('2024-01-01', 'FERIADO', 'NORMAL', 'Año Nuevo', FALSE, 2024, 1, 1),
('2024-01-09', 'FERIADO', 'NORMAL', 'Día de los Mártires', FALSE, 2024, 1, 2),
('2024-02-13', 'FERIADO', 'NORMAL', 'Martes de Carnaval', FALSE, 2024, 2, 2),
('2024-02-14', 'FERIADO', 'NORMAL', 'Miércoles de Ceniza', FALSE, 2024, 2, 3),
('2024-03-29', 'FERIADO', 'NORMAL', 'Viernes Santo', FALSE, 2024, 3, 5),
('2024-05-01', 'FERIADO', 'NORMAL', 'Día del Trabajador', FALSE, 2024, 5, 3),
('2024-11-03', 'FERIADO', 'NORMAL', 'Día de la Independencia de Colombia', FALSE, 2024, 11, 7),
('2024-11-04', 'FERIADO', 'NORMAL', 'Día de la Bandera', FALSE, 2024, 11, 1),
('2024-11-05', 'FERIADO', 'NORMAL', 'Día de Colón', FALSE, 2024, 11, 2),
('2024-11-10', 'FERIADO', 'NORMAL', 'Primer Grito de Independencia', FALSE, 2024, 11, 7),
('2024-11-28', 'FERIADO', 'NORMAL', 'Independencia de España', FALSE, 2024, 11, 4),
('2024-12-08', 'FERIADO', 'NORMAL', 'Día de la Madre', FALSE, 2024, 12, 7),
('2024-12-25', 'FERIADO', 'NORMAL', 'Navidad', FALSE, 2024, 12, 3),

-- FERIADOS 2025
('2025-01-01', 'FERIADO', 'NORMAL', 'Año Nuevo', FALSE, 2025, 1, 3),
('2025-01-09', 'FERIADO', 'NORMAL', 'Día de los Mártires', FALSE, 2025, 1, 4),
('2025-03-03', 'FERIADO', 'NORMAL', 'Lunes de Carnaval', FALSE, 2025, 3, 1),
('2025-03-04', 'FERIADO', 'NORMAL', 'Martes de Carnaval', FALSE, 2025, 3, 2),
('2025-04-18', 'FERIADO', 'NORMAL', 'Viernes Santo', FALSE, 2025, 4, 5),
('2025-05-01', 'FERIADO', 'NORMAL', 'Día del Trabajador', FALSE, 2025, 5, 4),
('2025-11-03', 'FERIADO', 'NORMAL', 'Día de la Independencia de Colombia', FALSE, 2025, 11, 1),
('2025-11-04', 'FERIADO', 'NORMAL', 'Día de la Bandera', FALSE, 2025, 11, 2),
('2025-11-05', 'FERIADO', 'NORMAL', 'Día de Colón', FALSE, 2025, 11, 3),
('2025-11-10', 'FERIADO', 'NORMAL', 'Primer Grito de Independencia', FALSE, 2025, 11, 1),
('2025-11-28', 'FERIADO', 'NORMAL', 'Independencia de España', FALSE, 2025, 11, 5),
('2025-12-08', 'FERIADO', 'NORMAL', 'Día de la Madre', FALSE, 2025, 12, 1),
('2025-12-25', 'FERIADO', 'NORMAL', 'Navidad', FALSE, 2025, 12, 4);

-- =====================================================
-- CONFIGURAR FINES DE SEMANA
-- =====================================================

-- Sábados y Domingos como días no laborables (ejemplo para algunos meses)
INSERT IGNORE INTO business_calendar (date_value, day_type, status, description, is_weekend, year_value, month_value, day_of_week)
SELECT
    DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY) as date_value,
    'NO_LABORAL' as day_type,
    'NORMAL' as status,
    CASE
        WHEN DAYOFWEEK(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) = 1 THEN 'Domingo'
        WHEN DAYOFWEEK(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) = 7 THEN 'Sábado'
    END as description,
    TRUE as is_weekend,
    YEAR(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) as year_value,
    MONTH(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) as month_value,
    CASE
        WHEN DAYOFWEEK(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) = 1 THEN 7
        ELSE DAYOFWEEK(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) - 1
    END as day_of_week
FROM
    (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3) AS c
WHERE
    DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY) <= '2025-12-31'
    AND (
        DAYOFWEEK(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) = 1 OR
        DAYOFWEEK(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) = 7
    );

-- =====================================================
-- DÍAS LABORABLES POR DEFECTO
-- =====================================================

INSERT IGNORE INTO business_calendar (date_value, day_type, status, description, is_weekend, year_value, month_value, day_of_week)
SELECT
    DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY) as date_value,
    'LABORAL' as day_type,
    'NORMAL' as status,
    CONCAT('Día laboral - ', DAYNAME(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY))) as description,
    FALSE as is_weekend,
    YEAR(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) as year_value,
    MONTH(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) as month_value,
    CASE
        WHEN DAYOFWEEK(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) = 1 THEN 7
        ELSE DAYOFWEEK(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) - 1
    END as day_of_week
FROM
    (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3) AS c
WHERE
    DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY) <= '2025-12-31'
    AND DAYOFWEEK(DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) BETWEEN 2 AND 6
    AND NOT EXISTS (
        SELECT 1 FROM business_calendar bc
        WHERE bc.date_value = DATE_ADD('2024-01-01', INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)
    );