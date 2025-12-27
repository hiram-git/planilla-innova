-- =====================================================
-- Migración: Agregar Campos de Almuerzo a Detalle de Asistencias
-- Fecha: 2025-11-03
-- Descripción: Agrega campos para registrar marcaciones de salida/entrada
--              de almuerzo y sus horarios programados
-- =====================================================


-- Agregar campos de marcaciones de almuerzo a attendance_detail
ALTER TABLE attendance_detail
ADD COLUMN lunch_out DATETIME NULL
    COMMENT 'Fecha/hora real de salida a almuerzo'
    AFTER time_out,
ADD COLUMN lunch_in DATETIME NULL
    COMMENT 'Fecha/hora real de entrada después de almuerzo'
    AFTER lunch_out,
ADD COLUMN scheduled_lunch_out TIME NULL
    COMMENT 'Hora programada de salida a almuerzo (del schedule)'
    AFTER lunch_in,
ADD COLUMN scheduled_lunch_in TIME NULL
    COMMENT 'Hora programada de entrada después de almuerzo (del schedule)'
    AFTER scheduled_lunch_out,
ADD COLUMN lunch_duration_minutes INT DEFAULT 0
    COMMENT 'Duración real del almuerzo en minutos'
    AFTER scheduled_lunch_in,
ADD COLUMN lunch_exceeded_minutes INT DEFAULT 0
    COMMENT 'Minutos de exceso en el período de almuerzo'
    AFTER lunch_duration_minutes;

-- Índices para mejorar rendimiento en consultas de almuerzo
CREATE INDEX idx_lunch_times ON attendance_detail(lunch_out, lunch_in);
CREATE INDEX idx_lunch_duration ON attendance_detail(lunch_duration_minutes);

-- =====================================================
-- Trigger para calcular duración de almuerzo automáticamente
-- =====================================================
DELIMITER //

DROP TRIGGER IF EXISTS trg_calculate_lunch_duration//

CREATE TRIGGER trg_calculate_lunch_duration
BEFORE UPDATE ON attendance_detail
FOR EACH ROW
BEGIN
    DECLARE scheduled_duration INT;

    -- Si hay marcaciones de almuerzo, calcular duración
    IF NEW.lunch_out IS NOT NULL AND NEW.lunch_in IS NOT NULL THEN
        SET NEW.lunch_duration_minutes = TIMESTAMPDIFF(MINUTE, NEW.lunch_out, NEW.lunch_in);

        -- Calcular exceso si hay horarios programados
        IF NEW.scheduled_lunch_out IS NOT NULL AND NEW.scheduled_lunch_in IS NOT NULL THEN
            SET scheduled_duration = TIMESTAMPDIFF(MINUTE,
                TIMESTAMP(DATE(NEW.lunch_out), NEW.scheduled_lunch_out),
                TIMESTAMP(DATE(NEW.lunch_in), NEW.scheduled_lunch_in)
            );

            -- Exceso = duración real - duración programada (si es positivo)
            SET NEW.lunch_exceeded_minutes = GREATEST(0, NEW.lunch_duration_minutes - scheduled_duration);
        END IF;
    ELSE
        SET NEW.lunch_duration_minutes = 0;
        SET NEW.lunch_exceeded_minutes = 0;
    END IF;
END//

DELIMITER ;

-- =====================================================
-- Vista auxiliar: Resumen de marcaciones con almuerzo
-- =====================================================
CREATE OR REPLACE VIEW v_attendance_detail_with_lunch AS
SELECT
    ad.id,
    ad.header_id,
    ad.employee_id,
    e.employee_id as employee_code,
    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
    ad.schedule_id,
    s.nombre as schedule_name,
    -- Marcaciones
    ad.time_in as entrada,
    ad.lunch_out as salida_almuerzo,
    ad.lunch_in as entrada_almuerzo,
    ad.time_out as salida,
    -- Horarios programados
    ad.scheduled_time_in,
    ad.scheduled_lunch_out,
    ad.scheduled_lunch_in,
    ad.scheduled_time_out,
    -- Métricas
    ad.hours_worked,
    ad.lunch_duration_minutes,
    ad.lunch_exceeded_minutes,
    ad.tardiness_minutes,
    ad.early_departure_minutes,
    ad.status,
    ad.created_at
FROM attendance_detail ad
LEFT JOIN employees e ON e.id = ad.employee_id
LEFT JOIN schedules s ON s.id = ad.schedule_id
ORDER BY ad.created_at DESC;

-- =====================================================
-- Procedimiento: Validar Completitud de Marcaciones
-- =====================================================
DELIMITER //

DROP PROCEDURE IF EXISTS sp_validate_attendance_completeness//

CREATE PROCEDURE sp_validate_attendance_completeness(
    IN p_attendance_detail_id INT,
    OUT p_status VARCHAR(20),
    OUT p_missing_punches TEXT
)
BEGIN
    DECLARE v_time_in DATETIME;
    DECLARE v_lunch_out DATETIME;
    DECLARE v_lunch_in DATETIME;
    DECLARE v_time_out DATETIME;
    DECLARE v_has_lunch_schedule BOOLEAN;

    -- Obtener marcaciones y verificar si tiene horario de almuerzo
    SELECT
        ad.time_in,
        ad.lunch_out,
        ad.lunch_in,
        ad.time_out,
        (s.salida_almuerzo IS NOT NULL AND s.entrada_almuerzo IS NOT NULL) as has_lunch
    INTO
        v_time_in,
        v_lunch_out,
        v_lunch_in,
        v_time_out,
        v_has_lunch_schedule
    FROM attendance_detail ad
    LEFT JOIN schedules s ON s.id = ad.schedule_id
    WHERE ad.id = p_attendance_detail_id;

    -- Inicializar
    SET p_missing_punches = '';

    -- Validar según si tiene almuerzo programado o no
    IF v_has_lunch_schedule THEN
        -- Requiere 4 marcaciones
        IF v_time_in IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Entrada, ');
        END IF;
        IF v_lunch_out IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Salida Almuerzo, ');
        END IF;
        IF v_lunch_in IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Entrada Almuerzo, ');
        END IF;
        IF v_time_out IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Salida, ');
        END IF;

        -- Determinar estado
        IF v_time_in IS NOT NULL AND v_lunch_out IS NOT NULL
           AND v_lunch_in IS NOT NULL AND v_time_out IS NOT NULL THEN
            SET p_status = 'COMPLETE';
        ELSEIF v_time_in IS NULL AND v_lunch_out IS NULL
               AND v_lunch_in IS NULL AND v_time_out IS NULL THEN
            SET p_status = 'ABSENT';
        ELSE
            SET p_status = 'INCOMPLETE';
        END IF;
    ELSE
        -- Requiere solo 2 marcaciones (entrada/salida)
        IF v_time_in IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Entrada, ');
        END IF;
        IF v_time_out IS NULL THEN
            SET p_missing_punches = CONCAT(p_missing_punches, 'Salida, ');
        END IF;

        -- Determinar estado
        IF v_time_in IS NOT NULL AND v_time_out IS NOT NULL THEN
            SET p_status = 'COMPLETE';
        ELSEIF v_time_in IS NULL AND v_time_out IS NULL THEN
            SET p_status = 'ABSENT';
        ELSE
            SET p_status = 'INCOMPLETE';
        END IF;
    END IF;

    -- Limpiar trailing comma
    IF LENGTH(p_missing_punches) > 0 THEN
        SET p_missing_punches = TRIM(TRAILING ', ' FROM p_missing_punches);
    END IF;
END//

DELIMITER ;

-- =====================================================
-- Verificación
-- =====================================================
-- NOTA: SELECT comentado para evitar error PDO "pending result sets"
-- SELECT 'Migración completada: Campos de almuerzo agregados a attendance_detail' as status;
-- DESCRIBE attendance_detail;

-- SELECT 'Vista v_attendance_detail_with_lunch creada' as status;
-- SELECT 'Procedimiento sp_validate_attendance_completeness creado' as status;
-- SELECT 'Trigger trg_calculate_lunch_duration creado' as status;
-- 