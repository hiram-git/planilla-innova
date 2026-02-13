-- ============================================================================
-- MIGRACIÓN: Sistema de Aprobación de Horas Extras
-- Fecha: 2026-02-02
-- Versión: 1.0
-- Descripción: Crea tablas para gestión de aprobaciones de horas extras
-- ============================================================================

-- Tabla principal: overtime_approvals
CREATE TABLE IF NOT EXISTS `overtime_approvals` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` INT NOT NULL COMMENT 'FK a employees',
    `approver_id` INT NULL COMMENT 'FK a admin (usuario que aprobó/rechazó)',
    `period_start` DATE NOT NULL COMMENT 'Fecha inicio del período',
    `period_end` DATE NOT NULL COMMENT 'Fecha fin del período',
    `total_overtime_25` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total horas extras al 25%',
    `total_overtime_50` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total horas extras al 50%',
    `total_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total horas extras (suma de ambas)',
    `hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Tarifa horaria base del empleado',
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto total a pagar',
    `status` ENUM('PENDIENTE', 'EN_REVISION', 'APROBADO', 'RECHAZADO', 'CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
    `approval_level` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Nivel de aprobación actual (1=supervisor, 2=gerente)',
    `rejection_reason` TEXT NULL COMMENT 'Razón del rechazo (si aplica)',
    `comments` TEXT NULL COMMENT 'Comentarios adicionales',
    `approved_at` DATETIME NULL COMMENT 'Fecha y hora de aprobación',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_employee_id` (`employee_id`),
    INDEX `idx_approver_id` (`approver_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_period` (`period_start`, `period_end`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_employee_period` (`employee_id`, `period_start`, `period_end`),
    CONSTRAINT `fk_overtime_approvals_employee`
        FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_overtime_approvals_approver`
        FOREIGN KEY (`approver_id`) REFERENCES `admin` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla principal de aprobaciones de horas extras';

-- Tabla de historial: overtime_approval_history
CREATE TABLE IF NOT EXISTS `overtime_approval_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `approval_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK a overtime_approvals',
    `user_id` INT NOT NULL COMMENT 'FK a admin (usuario que realizó la acción)',
    `action` ENUM('CREADO', 'APROBADO_L1', 'APROBADO_L2', 'APROBADO_FINAL', 'RECHAZADO', 'MODIFICADO', 'CANCELADO', 'REABIERTO') NOT NULL,
    `previous_status` ENUM('PENDIENTE', 'EN_REVISION', 'APROBADO', 'RECHAZADO', 'CANCELADO') NULL COMMENT 'Estado anterior',
    `new_status` ENUM('PENDIENTE', 'EN_REVISION', 'APROBADO', 'RECHAZADO', 'CANCELADO') NOT NULL COMMENT 'Estado nuevo',
    `comments` TEXT NULL COMMENT 'Comentarios de la acción',
    `ip_address` VARCHAR(45) NULL COMMENT 'IP desde donde se realizó la acción',
    `user_agent` VARCHAR(255) NULL COMMENT 'User agent del navegador',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_approval_id` (`approval_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_overtime_history_approval`
        FOREIGN KEY (`approval_id`) REFERENCES `overtime_approvals` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_overtime_history_user`
        FOREIGN KEY (`user_id`) REFERENCES `admin` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de cambios de aprobaciones de horas extras';

-- Vista materializada para consultas rápidas
CREATE OR REPLACE VIEW `v_overtime_approvals_with_employees` AS
SELECT
    oa.id,
    oa.employee_id,
    CONCAT(e.firstname, ' ', e.lastname) AS employee_full_name,
    e.employee_id AS employee_code,
    e.email AS employee_email,
    oa.approver_id,
    CONCAT(a.firstname, ' ', a.lastname) AS approver_full_name,
    oa.period_start,
    oa.period_end,
    oa.total_overtime_25,
    oa.total_overtime_50,
    oa.total_hours,
    oa.hourly_rate,
    oa.total_amount,
    oa.status,
    oa.approval_level,
    oa.rejection_reason,
    oa.comments,
    oa.approved_at,
    oa.created_at,
    oa.updated_at,
    DATEDIFF(CURRENT_DATE, oa.created_at) AS days_pending
FROM overtime_approvals oa
INNER JOIN employees e ON oa.employee_id = e.id
LEFT JOIN admin a ON oa.approver_id = a.id;

-- ============================================================================
-- FIN DE MIGRACIÓN
-- ============================================================================
