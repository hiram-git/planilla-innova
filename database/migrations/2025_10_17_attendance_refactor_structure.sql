-- =====================================================
-- MIGRACIÓN: Refactorización Sistema de Asistencias
-- Versión: v3.5.0
-- Fecha: 17 de Octubre, 2025
-- Descripción: Implementación arquitectura cabecera/detalle
--              con soporte para múltiples dispositivos y archivos texto
-- =====================================================


-- =====================================================
-- TABLA 1: attendance_devices (Dispositivos de Marcación)
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Código único del dispositivo',
    device_name VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo del dispositivo',
    device_type ENUM('API', 'BIOMETRIC', 'TEXT_FILE', 'MANUAL') DEFAULT 'API' COMMENT 'Tipo de dispositivo',
    location VARCHAR(100) COMMENT 'Ubicación física del dispositivo',
    is_active TINYINT(1) DEFAULT 1 COMMENT 'Estado activo/inactivo',

    -- Configuración específica por tipo
    config_json TEXT COMMENT 'JSON con configuración específica del dispositivo',

    -- Información API (si aplica)
    api_url VARCHAR(255) COMMENT 'URL del API del dispositivo',
    api_key VARCHAR(255) COMMENT 'API Key de autenticación',

    -- Información archivo texto (si aplica)
    file_format VARCHAR(50) COMMENT 'Formato esperado del archivo (CSV, TXT, DAT)',
    delimiter VARCHAR(10) DEFAULT ',' COMMENT 'Delimitador de campos',
    date_format VARCHAR(20) DEFAULT 'Y-m-d' COMMENT 'Formato de fecha en archivo',
    time_format VARCHAR(20) DEFAULT 'H:i:s' COMMENT 'Formato de hora en archivo',
    column_mapping TEXT COMMENT 'JSON con mapeo de columnas del archivo',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT 'ID del usuario que creó el registro',

    INDEX idx_device_code (device_code),
    INDEX idx_device_type (device_type),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dispositivos de marcación de asistencias';

-- =====================================================
-- TABLA 2: attendance_header (Cabecera de Asistencias por Día)
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance_header (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_date DATE NOT NULL COMMENT 'Fecha de las asistencias',
    device_id INT COMMENT 'Dispositivo desde el cual se sincronizaron las marcaciones',

    -- Estadísticas del día
    total_records INT DEFAULT 0 COMMENT 'Total de registros de marcaciones',
    total_employees INT DEFAULT 0 COMMENT 'Total de empleados con marcación',
    total_on_time INT DEFAULT 0 COMMENT 'Total de empleados puntuales',
    total_late INT DEFAULT 0 COMMENT 'Total de empleados con tardanza',
    total_absent INT DEFAULT 0 COMMENT 'Total de empleados ausentes',

    -- Control de procesamiento
    is_processed TINYINT(1) DEFAULT 0 COMMENT 'Indica si ya se calcularon las marcaciones',
    processed_at TIMESTAMP NULL COMMENT 'Fecha y hora de procesamiento',
    processed_by INT COMMENT 'Usuario que procesó las marcaciones',

    -- Información sincronización
    sync_batch_id VARCHAR(100) COMMENT 'ID del lote de sincronización',
    synced_from ENUM('API', 'FILE', 'MANUAL') DEFAULT 'API' COMMENT 'Origen de la sincronización',
    file_import_id INT COMMENT 'ID de importación de archivo (si aplica)',

    -- Notas
    notes TEXT COMMENT 'Notas adicionales sobre las marcaciones del día',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_date_device (attendance_date, device_id),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_processed (is_processed),
    INDEX idx_synced_from (synced_from),
    INDEX idx_sync_batch (sync_batch_id),
    FOREIGN KEY (device_id) REFERENCES attendance_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cabecera de asistencias agrupadas por día';

-- =====================================================
-- TABLA 3: attendance_detail (Detalle de Marcaciones)
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    header_id INT NOT NULL COMMENT 'ID de la cabecera de asistencias',
    employee_id INT NOT NULL COMMENT 'ID del empleado',
    schedule_id INT COMMENT 'ID del horario del empleado',

    -- Marcaciones
    time_in TIME COMMENT 'Hora de entrada',
    time_out TIME COMMENT 'Hora de salida',

    -- Horarios programados (para comparación)
    scheduled_time_in TIME COMMENT 'Hora programada de entrada',
    scheduled_time_out TIME COMMENT 'Hora programada de salida',

    -- Información dispositivo/registro
    device_id INT COMMENT 'Dispositivo que registró la marcación',
    external_id VARCHAR(100) COMMENT 'ID de la marcación en el dispositivo externo',

    -- Cálculos básicos
    tardiness_minutes INT DEFAULT 0 COMMENT 'Minutos de tardanza',
    is_late TINYINT(1) DEFAULT 0 COMMENT 'Indica si llegó tarde',
    early_departure_minutes INT DEFAULT 0 COMMENT 'Minutos de salida anticipada',
    hours_worked DECIMAL(5,2) DEFAULT 0 COMMENT 'Horas trabajadas totales',

    -- Estado
    status ENUM('PRESENT', 'ABSENT', 'LATE', 'INCOMPLETE', 'JUSTIFIED') DEFAULT 'PRESENT' COMMENT 'Estado de la asistencia',

    -- Justificación (si aplica)
    justification_type ENUM('MEDICAL', 'PERMISSION', 'VACATION', 'OTHER') COMMENT 'Tipo de justificación',
    justification_notes TEXT COMMENT 'Notas de justificación',
    justification_document VARCHAR(255) COMMENT 'Ruta del documento de justificación',

    -- Notas
    notes TEXT COMMENT 'Notas adicionales',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_header (header_id),
    INDEX idx_employee (employee_id),
    INDEX idx_device (device_id),
    INDEX idx_status (status),
    INDEX idx_is_late (is_late),
    UNIQUE KEY unique_header_employee (header_id, employee_id),
    FOREIGN KEY (header_id) REFERENCES attendance_header(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE SET NULL,
    FOREIGN KEY (device_id) REFERENCES attendance_devices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de marcaciones de asistencia';

-- =====================================================
-- TABLA 4: attendance_file_imports (Log de Importaciones)
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance_file_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL COMMENT 'Dispositivo asociado a la importación',
    filename VARCHAR(255) NOT NULL COMMENT 'Nombre del archivo importado',
    file_path VARCHAR(500) COMMENT 'Ruta física del archivo',
    file_size INT COMMENT 'Tamaño del archivo en bytes',

    -- Estadísticas procesamiento
    total_lines INT DEFAULT 0 COMMENT 'Total de líneas en el archivo',
    successful_imports INT DEFAULT 0 COMMENT 'Total de importaciones exitosas',
    failed_imports INT DEFAULT 0 COMMENT 'Total de importaciones fallidas',
    skipped_lines INT DEFAULT 0 COMMENT 'Total de líneas omitidas',

    -- Rango de fechas procesado
    date_from DATE COMMENT 'Fecha inicial de marcaciones procesadas',
    date_to DATE COMMENT 'Fecha final de marcaciones procesadas',

    -- Resultado
    status ENUM('PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'PARTIAL') DEFAULT 'PENDING' COMMENT 'Estado de la importación',
    error_log TEXT COMMENT 'Log de errores durante la importación',
    success_log TEXT COMMENT 'Log de éxitos (IDs creados, etc.)',

    -- Procesamiento
    processing_started_at TIMESTAMP NULL COMMENT 'Inicio del procesamiento',
    processing_completed_at TIMESTAMP NULL COMMENT 'Fin del procesamiento',
    processing_duration INT COMMENT 'Duración del procesamiento en segundos',

    -- Auditoría
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    imported_by INT COMMENT 'Usuario que realizó la importación',

    INDEX idx_device (device_id),
    INDEX idx_status (status),
    INDEX idx_imported_at (imported_at),
    INDEX idx_date_range (date_from, date_to),
    FOREIGN KEY (device_id) REFERENCES attendance_devices(id) ON DELETE CASCADE,
    FOREIGN KEY (imported_by) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de importaciones de archivos de marcaciones';

-- =====================================================
-- ÍNDICES ADICIONALES PARA PERFORMANCE
-- =====================================================

-- Índice compuesto para búsquedas frecuentes de detalle
CREATE INDEX idx_detail_composite ON attendance_detail(header_id, employee_id, status);

-- Índice para reportes de asistencia por empleado
CREATE INDEX idx_employee_date ON attendance_detail(employee_id, header_id);

-- Índice para estadísticas de dispositivos
CREATE INDEX idx_device_active ON attendance_devices(is_active, device_type);

-- =====================================================
-- DATOS INICIALES: Dispositivo API (si existe)
-- =====================================================

-- Verificar si existe configuración API y crear dispositivo
INSERT INTO attendance_devices (device_code, device_name, device_type, location, is_active, api_url, api_key, created_by)
SELECT
    'API',
    'API - Principal',
    'API',
    'Servidor Remoto',
    1,
    api_url,
    api_key,
    1
FROM attendance_api_config
WHERE api_provider = 'api' AND sync_enabled = 1
LIMIT 1
ON DUPLICATE KEY UPDATE
    api_url = VALUES(api_url),
    api_key = VALUES(api_key),
    updated_at = CURRENT_TIMESTAMP;

-- =====================================================
-- SCRIPT DE MIGRACIÓN DE DATOS EXISTENTES
-- =====================================================

-- NOTA: Este script migrará los datos de la tabla `attendance`
-- existente a la nueva estructura attendance_header + attendance_detail
-- Se ejecutará en un paso posterior después de verificar la estructura

-- =====================================================
-- ESTADÍSTICAS DE LA MIGRACIÓN
-- =====================================================

-- NOTA: SELECT comentado para evitar error PDO "pending result sets"

-- SELECT 'Migración completada' as status;
-- SELECT COUNT(*) as total_devices FROM attendance_devices;
-- SELECT 'Tablas creadas: attendance_devices, attendance_header, attendance_detail, attendance_file_imports' as info;
-- 
-- =====================================================
-- FIN DE LA MIGRACIÓN
-- =====================================================
-- 