-- =====================================================
-- MIGRACIÓN: Tabla attendance_records
-- Versión: v3.5.2
-- Fecha: 30 de Octubre, 2025
-- Descripción: Capa intermedia para almacenar marcaciones individuales
--              normalizadas antes de consolidarlas en attendance_detail
-- =====================================================


-- =====================================================
-- TABLA: attendance_records
-- Propósito: Almacenar cada marcación individual (CHECK_IN/CHECK_OUT)
--            de forma normalizada para posterior procesamiento
-- =====================================================

CREATE TABLE IF NOT EXISTS attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Relación con raw_data
    raw_data_id INT COMMENT 'FK a attendance_raw_data - Origen del registro',
    external_id VARCHAR(100) COMMENT 'ID del registro en sistema externo (API/dispositivo)',

    -- Datos normalizados de la marcación
    employee_id INT NOT NULL COMMENT 'FK a employees - Empleado que realizó la marcación',
    timestamp DATETIME NOT NULL COMMENT 'Fecha y hora exacta de la marcación',
    punch_date DATE NOT NULL COMMENT 'Fecha de la marcación (para agrupación)',
    punch_time TIME NOT NULL COMMENT 'Hora de la marcación',
    punch_type ENUM('CHECK_IN', 'CHECK_OUT') NOT NULL COMMENT 'Tipo de marcación',

    -- Dispositivo
    device_id INT COMMENT 'FK a attendance_devices - Dispositivo que registró',
    device_serial VARCHAR(100) COMMENT 'Serial del dispositivo físico',

    -- Origen y procesamiento
    source ENUM('API', 'TXT', 'MANUAL') DEFAULT 'API' COMMENT 'Origen de la marcación',
    is_processed TINYINT(1) DEFAULT 0 COMMENT '¿Ya se consolidó en attendance_detail?',
    processed_at TIMESTAMP NULL COMMENT 'Cuándo se procesó a detail',
    detail_id INT COMMENT 'FK a attendance_detail al que pertenece',

    -- Detección de duplicados
    is_duplicate TINYINT(1) DEFAULT 0 COMMENT 'Marcación duplicada detectada',
    duplicate_of INT COMMENT 'ID del registro original del cual es duplicado',

    -- Hash para detección de duplicados
    -- Formato: MD5(employee_id|timestamp|punch_type)
    record_hash VARCHAR(64) UNIQUE COMMENT 'Hash único para deduplicación',

    -- Metadata adicional
    metadata JSON COMMENT 'Datos adicionales del API o dispositivo',
    notes TEXT COMMENT 'Notas adicionales sobre la marcación',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices para performance
    INDEX idx_employee (employee_id),
    INDEX idx_date (punch_date),
    INDEX idx_timestamp (timestamp),
    INDEX idx_processed (is_processed),
    INDEX idx_duplicate (is_duplicate),
    INDEX idx_external_id (external_id),
    INDEX idx_source (source),
    INDEX idx_device (device_id),
    INDEX idx_detail (detail_id),

    -- Índice compuesto para búsquedas frecuentes
    INDEX idx_employee_date (employee_id, punch_date),
    INDEX idx_employee_date_type (employee_id, punch_date, punch_type),
    INDEX idx_date_processed (punch_date, is_processed),

    -- Foreign Keys
    CONSTRAINT fk_records_raw_data
        FOREIGN KEY (raw_data_id)
        REFERENCES attendance_raw_data(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_records_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_records_device
        FOREIGN KEY (device_id)
        REFERENCES attendance_devices(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_records_detail
        FOREIGN KEY (detail_id)
        REFERENCES attendance_detail(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_records_duplicate
        FOREIGN KEY (duplicate_of)
        REFERENCES attendance_records(id)
        ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Marcaciones individuales normalizadas (capa intermedia)';

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista de registros sin procesar
CREATE OR REPLACE VIEW v_unprocessed_records AS
SELECT
    r.*,
    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
    e.employee_id as employee_code,
    d.device_name
FROM attendance_records r
INNER JOIN employees e ON r.employee_id = e.id
LEFT JOIN attendance_devices d ON r.device_id = d.id
WHERE r.is_processed = 0
  AND r.is_duplicate = 0
ORDER BY r.punch_date DESC, r.timestamp ASC;

-- Vista de duplicados detectados
CREATE OR REPLACE VIEW v_duplicate_records AS
SELECT
    r.*,
    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
    e.employee_id as employee_code,
    orig.timestamp as original_timestamp
FROM attendance_records r
INNER JOIN employees e ON r.employee_id = e.id
LEFT JOIN attendance_records orig ON r.duplicate_of = orig.id
WHERE r.is_duplicate = 1
ORDER BY r.created_at DESC;

-- Vista de estadísticas por día
CREATE OR REPLACE VIEW v_records_stats_by_date AS
SELECT
    punch_date,
    COUNT(*) as total_records,
    COUNT(DISTINCT employee_id) as total_employees,
    SUM(CASE WHEN punch_type = 'CHECK_IN' THEN 1 ELSE 0 END) as check_ins,
    SUM(CASE WHEN punch_type = 'CHECK_OUT' THEN 1 ELSE 0 END) as check_outs,
    SUM(CASE WHEN is_processed = 1 THEN 1 ELSE 0 END) as processed,
    SUM(CASE WHEN is_processed = 0 THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN is_duplicate = 1 THEN 1 ELSE 0 END) as duplicates
FROM attendance_records
GROUP BY punch_date
ORDER BY punch_date DESC;

-- Vista de marcaciones incompletas (solo entrada o solo salida)
CREATE OR REPLACE VIEW v_incomplete_attendance_records AS
SELECT
    r.employee_id,
    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
    r.punch_date,
    SUM(CASE WHEN r.punch_type = 'CHECK_IN' THEN 1 ELSE 0 END) as check_ins,
    SUM(CASE WHEN r.punch_type = 'CHECK_OUT' THEN 1 ELSE 0 END) as check_outs,
    COUNT(*) as total_punches
FROM attendance_records r
INNER JOIN employees e ON r.employee_id = e.id
WHERE r.is_processed = 0
  AND r.is_duplicate = 0
GROUP BY r.employee_id, r.punch_date
HAVING check_ins = 0 OR check_outs = 0 OR check_ins != check_outs
ORDER BY r.punch_date DESC;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Procedimiento para marcar registros como procesados
DROP PROCEDURE IF EXISTS sp_mark_records_processed;

DELIMITER $$
CREATE PROCEDURE sp_mark_records_processed(
    IN p_employee_id INT,
    IN p_punch_date DATE,
    IN p_detail_id INT
)
BEGIN
    UPDATE attendance_records
    SET is_processed = 1,
        processed_at = NOW(),
        detail_id = p_detail_id
    WHERE employee_id = p_employee_id
      AND punch_date = p_punch_date
      AND is_processed = 0
      AND is_duplicate = 0;

    SELECT ROW_COUNT() as records_marked;
END$$
DELIMITER ;

-- Procedimiento para detectar duplicados
DROP PROCEDURE IF EXISTS sp_detect_duplicates;

DELIMITER $$
CREATE PROCEDURE sp_detect_duplicates()
BEGIN
    -- Marcar registros duplicados basándose en record_hash
    UPDATE attendance_records r1
    INNER JOIN (
        SELECT MIN(id) as original_id, record_hash
        FROM attendance_records
        WHERE is_duplicate = 0
        GROUP BY record_hash
        HAVING COUNT(*) > 1
    ) dups ON r1.record_hash = dups.record_hash
    SET r1.is_duplicate = 1,
        r1.duplicate_of = dups.original_id
    WHERE r1.id != dups.original_id
      AND r1.is_duplicate = 0;

    SELECT ROW_COUNT() as duplicates_found;
END$$
DELIMITER ;

-- Procedimiento para obtener registros agrupados por empleado/día
DROP PROCEDURE IF EXISTS sp_get_grouped_records;

DELIMITER $$
CREATE PROCEDURE sp_get_grouped_records(
    IN p_date_from DATE,
    IN p_date_to DATE
)
BEGIN
    SELECT
        employee_id,
        punch_date,
        MIN(CASE WHEN punch_type = 'CHECK_IN' THEN timestamp END) as first_check_in,
        MAX(CASE WHEN punch_type = 'CHECK_OUT' THEN timestamp END) as last_check_out,
        COUNT(*) as total_punches,
        GROUP_CONCAT(id ORDER BY timestamp) as record_ids
    FROM attendance_records
    WHERE punch_date BETWEEN p_date_from AND p_date_to
      AND is_processed = 0
      AND is_duplicate = 0
    GROUP BY employee_id, punch_date
    ORDER BY punch_date DESC, employee_id;
END$$
DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger para calcular automáticamente punch_date y punch_time
DROP TRIGGER IF EXISTS before_insert_attendance_records;

DELIMITER $$
CREATE TRIGGER before_insert_attendance_records
BEFORE INSERT ON attendance_records
FOR EACH ROW
BEGIN
    -- Calcular punch_date y punch_time desde timestamp si no están definidos
    IF NEW.punch_date IS NULL THEN
        SET NEW.punch_date = DATE(NEW.timestamp);
    END IF;

    IF NEW.punch_time IS NULL THEN
        SET NEW.punch_time = TIME(NEW.timestamp);
    END IF;

    -- Calcular record_hash si no está definido
    IF NEW.record_hash IS NULL OR NEW.record_hash = '' THEN
        SET NEW.record_hash = MD5(CONCAT(
            NEW.employee_id, '|',
            NEW.timestamp, '|',
            NEW.punch_type
        ));
    END IF;
END$$
DELIMITER ;

-- Trigger para actualizar punch_date y punch_time en UPDATE
DROP TRIGGER IF EXISTS before_update_attendance_records;

DELIMITER $$
CREATE TRIGGER before_update_attendance_records
BEFORE UPDATE ON attendance_records
FOR EACH ROW
BEGIN
    -- Si se modifica timestamp, actualizar punch_date y punch_time
    IF NEW.timestamp != OLD.timestamp THEN
        SET NEW.punch_date = DATE(NEW.timestamp);
        SET NEW.punch_time = TIME(NEW.timestamp);

        -- Recalcular hash
        SET NEW.record_hash = MD5(CONCAT(
            NEW.employee_id, '|',
            NEW.timestamp, '|',
            NEW.punch_type
        ));
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- DATOS INICIALES / MIGRACIÓN
-- =====================================================

-- Nota: Los datos de attendance_raw_data existentes NO se migrarán automáticamente.
-- Se procesarán en la próxima sincronización. Si se requiere migración histórica,
-- ejecutar script separado de migración de datos.

-- =====================================================
-- ESTADÍSTICAS DE LA MIGRACIÓN
-- =====================================================

SELECT 'Migración completada: attendance_records creada exitosamente' as status;
SELECT COUNT(*) as total_records FROM attendance_records;

-- Verificar vistas
SELECT COUNT(*) as vistas_creadas
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = 'planilla_prod'
  AND TABLE_NAME LIKE 'v_%_records%';

-- Verificar stored procedures
SELECT COUNT(*) as procedures_creados
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = 'planilla_prod'
  AND ROUTINE_NAME LIKE 'sp_%_records%';

-- =====================================================
-- FIN DE LA MIGRACIÓN
-- =====================================================
