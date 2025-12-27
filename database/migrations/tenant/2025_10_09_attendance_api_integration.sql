-- =====================================================
-- MIGRACIÓN: Integración API - Subfase 7.1
-- Fecha: 9 de Octubre, 2025
-- Descripción: Tablas para integración con API externa de asistencias
-- =====================================================

-- =====================================================
-- Tabla 1: attendance_api_config
-- Configuración de conexión a API externa (multi-tenant ready)
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance_api_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    api_provider VARCHAR(50) NOT NULL DEFAULT 'api' COMMENT 'Proveedor de API (api, clockify, etc.)',
    api_key VARCHAR(255) NOT NULL COMMENT 'API Key para autenticación',
    app_id VARCHAR(255) NOT NULL COMMENT 'Application ID',
    api_url VARCHAR(500) NOT NULL DEFAULT 'https://app.api.com/api' COMMENT 'URL base de la API',

    -- Configuración de sincronización
    sync_enabled TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Sincronización automática habilitada',
    sync_interval_minutes INT NOT NULL DEFAULT 15 COMMENT 'Intervalo de sincronización en minutos',
    last_sync_at TIMESTAMP NULL COMMENT 'Timestamp de última sincronización exitosa',
    last_sync_status VARCHAR(20) DEFAULT 'NEVER' COMMENT 'SUCCESS, FAILED, PARTIAL, NEVER',

    -- Configuración de webhooks
    webhook_url VARCHAR(500) COMMENT 'URL del webhook para notificaciones en tiempo real',
    webhook_secret VARCHAR(255) COMMENT 'Secret para validar firmas de webhooks',

    -- Configuraciones adicionales en JSON
    config_json TEXT COMMENT 'Configuraciones adicionales específicas del proveedor',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    INDEX idx_provider (api_provider),
    INDEX idx_sync_enabled (sync_enabled),
    INDEX idx_last_sync (last_sync_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuración de conexión a API externa de asistencias';

-- =====================================================
-- Tabla 2: attendance_raw_data
-- Backup completo de respuestas de la API para auditoría y reprocesamiento
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance_raw_data (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Identificación
    external_id VARCHAR(100) COMMENT 'ID de la entidad desde API externa',
    api_provider VARCHAR(50) NOT NULL DEFAULT 'api' COMMENT 'Proveedor de API',
    entity_type VARCHAR(50) NOT NULL COMMENT 'Tipo de entidad: Employee, Attendance',

    -- Datos crudos
    raw_json TEXT NOT NULL COMMENT 'Payload completo de la API en formato JSON',

    -- Estado de procesamiento
    processed TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el registro ya fue procesado',
    processed_at TIMESTAMP NULL COMMENT 'Timestamp de procesamiento',
    processing_error TEXT COMMENT 'Error durante el procesamiento (si existe)',

    -- Auditoría
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp de recepción desde API',
    sync_batch_id INT COMMENT 'ID del batch de sincronización',

    -- Índices
    INDEX idx_external_id (external_id),
    INDEX idx_provider (api_provider),
    INDEX idx_entity_type (entity_type),
    INDEX idx_processed (processed),
    INDEX idx_received_at (received_at),
    INDEX idx_sync_batch (sync_batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Backup de datos crudos desde API externa para auditoría';

-- =====================================================
-- Tabla 3: attendance_sync_log
-- Historial completo de todas las sincronizaciones
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance_sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Tipo de sincronización
    sync_type VARCHAR(50) NOT NULL COMMENT 'FULL, INCREMENTAL, MANUAL, WEBHOOK',

    -- Timestamps
    start_time TIMESTAMP NOT NULL COMMENT 'Inicio de la sincronización',
    end_time TIMESTAMP NULL COMMENT 'Fin de la sincronización',
    duration_seconds INT COMMENT 'Duración en segundos',

    -- Estadísticas
    records_fetched INT DEFAULT 0 COMMENT 'Registros obtenidos desde API',
    records_inserted INT DEFAULT 0 COMMENT 'Registros insertados en BD local',
    records_updated INT DEFAULT 0 COMMENT 'Registros actualizados en BD local',
    records_skipped INT DEFAULT 0 COMMENT 'Registros omitidos (duplicados/inválidos)',
    errors_count INT DEFAULT 0 COMMENT 'Número de errores encontrados',

    -- Detalles de errores
    error_details TEXT COMMENT 'Detalles de errores en formato JSON',

    -- Estado final
    status VARCHAR(20) NOT NULL DEFAULT 'RUNNING' COMMENT 'RUNNING, SUCCESS, FAILED, PARTIAL',
    status_message TEXT COMMENT 'Mensaje descriptivo del resultado',

    -- Origen
    triggered_by VARCHAR(100) COMMENT 'CRON, USER_ID:123, WEBHOOK',
    api_provider VARCHAR(50) DEFAULT 'base44',

    -- Filtros aplicados
    filters_json TEXT COMMENT 'Filtros aplicados durante la sincronización (JSON)',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Índices
    INDEX idx_sync_type (sync_type),
    INDEX idx_status (status),
    INDEX idx_start_time (start_time),
    INDEX idx_api_provider (api_provider),
    INDEX idx_triggered_by (triggered_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de sincronizaciones con API externa';

-- =====================================================
-- Insertar configuración inicial
-- =====================================================
INSERT INTO attendance_api_config (
    api_provider,
    api_key,
    app_id,
    api_url,
    sync_enabled,
    sync_interval_minutes,
    webhook_url,
    config_json
) VALUES (
    'api',
    '40162908d71941b98636b38106be556e',
    '68dd9181444436f4bd157e1d',
    'https://app.api.com/api',
    1,
    15,
    NULL,
    '{"timeout": 30, "max_retries": 3, "log_enabled": true}'
) ON DUPLICATE KEY UPDATE
    api_key = VALUES(api_key),
    app_id = VALUES(app_id),
    api_url = VALUES(api_url);

-- =====================================================
-- Verificación de tablas creadas
-- =====================================================
-- NOTA: SELECT comentado para evitar error "Cannot execute queries while there are pending result sets"
-- en ejecuciones con PDO. Para verificar manualmente, ejecutar:
-- SELECT COUNT(*) FROM information_schema.tables
--  WHERE table_schema = DATABASE()
--  AND table_name IN ('attendance_api_config', 'attendance_raw_data', 'attendance_sync_log');
