-- =====================================================
-- MIGRACIÓN: Tabla calendar_sync_log para tracking de sincronización
-- Fecha: 2025-11-13
-- Versión: v3.5.7 (WIP)
-- Descripción: Tabla para registrar sincronizaciones del calendario empresarial desde API Base44
-- =====================================================

CREATE TABLE IF NOT EXISTS `calendar_sync_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sync_type` ENUM('MANUAL', 'SCHEDULED', 'WEBHOOK') NOT NULL DEFAULT 'MANUAL' COMMENT 'Tipo de sincronización',
    `start_time` DATETIME NOT NULL COMMENT 'Hora de inicio de sincronización',
    `end_time` DATETIME NULL COMMENT 'Hora de fin de sincronización',
    `duration_seconds` INT UNSIGNED NULL COMMENT 'Duración en segundos',
    `status` ENUM('RUNNING', 'SUCCESS', 'FAILED', 'PARTIAL') NOT NULL DEFAULT 'RUNNING' COMMENT 'Estado de la sincronización',
    `status_message` TEXT NULL COMMENT 'Mensaje descriptivo del estado',
    `triggered_by` VARCHAR(100) NULL COMMENT 'Usuario o proceso que inició la sincronización',
    `records_fetched` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de registros obtenidos desde API',
    `records_inserted` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de registros insertados',
    `records_updated` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de registros actualizados',
    `records_skipped` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de registros omitidos',
    `records_deleted` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de registros eliminados (modo replace)',
    `errors_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de errores encontrados',
    `error_details` JSON NULL COMMENT 'Detalles de errores en formato JSON',
    `options_json` JSON NULL COMMENT 'Opciones de sincronización (year, replace, dry_run)',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sync_type` (`sync_type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_start_time` (`start_time`),
    INDEX `idx_triggered_by` (`triggered_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de sincronizaciones del calendario empresarial';

-- =====================================================
-- Insertar registro de ejemplo (opcional, comentado)
-- =====================================================
-- INSERT INTO `calendar_sync_log`
-- (`sync_type`, `start_time`, `end_time`, `duration_seconds`, `status`, `status_message`, `triggered_by`, `records_fetched`, `records_inserted`)
-- VALUES
-- ('MANUAL', NOW(), NOW(), 5, 'SUCCESS', 'Sincronización de prueba exitosa', 'USER_1', 100, 95);
