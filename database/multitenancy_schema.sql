-- ===================================================
-- SCHEMA MULTITENANCY - Sistema de Planillas MVC
-- Base para arquitectura multi-empresa
-- ===================================================

-- Tabla principal de empresas multitenancy
CREATE TABLE IF NOT EXISTS `multitenancy_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL COMMENT 'Nombre de la empresa',
  `ruc` varchar(20) NOT NULL COMMENT 'RUC único de la empresa',
  `admin_email` varchar(255) NOT NULL COMMENT 'Email del administrador principal',
  `tenant_database` varchar(100) DEFAULT NULL COMMENT 'Nombre de la BD tenant asignada',
  `status` enum('pending','active','inactive','deleted') DEFAULT 'pending' COMMENT 'Estado de la empresa',
  `created_at` datetime NOT NULL COMMENT 'Fecha de creación',
  `updated_at` datetime NOT NULL COMMENT 'Fecha de última actualización',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ruc` (`ruc`),
  KEY `idx_status` (`status`),
  KEY `idx_tenant_db` (`tenant_database`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registro de empresas en sistema multitenancy';

-- Tabla de configuración de tenants
CREATE TABLE IF NOT EXISTS `tenant_configurations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL COMMENT 'ID de la empresa',
  `database_name` varchar(100) NOT NULL COMMENT 'Nombre de la BD tenant',
  `subdomain` varchar(100) DEFAULT NULL COMMENT 'Subdominio asignado (opcional)',
  `custom_domain` varchar(255) DEFAULT NULL COMMENT 'Dominio personalizado (opcional)',
  `max_employees` int(11) DEFAULT 100 COMMENT 'Límite máximo de empleados',
  `max_storage_mb` int(11) DEFAULT 1000 COMMENT 'Límite de almacenamiento en MB',
  `features_enabled` text DEFAULT NULL COMMENT 'Funcionalidades habilitadas (JSON)',
  `backup_schedule` enum('daily','weekly','monthly') DEFAULT 'weekly' COMMENT 'Programación de respaldos',
  `last_backup` datetime DEFAULT NULL COMMENT 'Fecha del último respaldo',
  `status` enum('active','inactive','suspended') DEFAULT 'active' COMMENT 'Estado de la configuración',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_company` (`company_id`),
  UNIQUE KEY `unique_database` (`database_name`),
  UNIQUE KEY `unique_subdomain` (`subdomain`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`company_id`) REFERENCES `multitenancy_companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Configuración detallada de tenants';

-- Tabla de logs del wizard multitenancy
CREATE TABLE IF NOT EXISTS `wizard_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL COMMENT 'ID de empresa (si aplicable)',
  `step` varchar(50) NOT NULL COMMENT 'Paso del wizard ejecutado',
  `action` varchar(100) NOT NULL COMMENT 'Acción específica realizada',
  `data` text DEFAULT NULL COMMENT 'Datos del wizard (JSON)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP del usuario',
  `user_agent` text DEFAULT NULL COMMENT 'User Agent del navegador',
  `status` enum('success','error','warning') NOT NULL COMMENT 'Estado de la operación',
  `error_message` text DEFAULT NULL COMMENT 'Mensaje de error (si aplicable)',
  `execution_time_ms` int(11) DEFAULT NULL COMMENT 'Tiempo de ejecución en milisegundos',
  `created_at` datetime NOT NULL COMMENT 'Fecha de ejecución',
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_step` (`step`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`company_id`) REFERENCES `multitenancy_companies` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Logs de actividad del wizard multitenancy';

-- Tabla de métricas de uso por tenant
CREATE TABLE IF NOT EXISTS `tenant_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL COMMENT 'ID de la empresa',
  `metric_date` date NOT NULL COMMENT 'Fecha de la métrica',
  `employees_count` int(11) DEFAULT 0 COMMENT 'Número de empleados activos',
  `payrolls_processed` int(11) DEFAULT 0 COMMENT 'Planillas procesadas en el día',
  `storage_used_mb` decimal(10,2) DEFAULT 0.00 COMMENT 'Almacenamiento usado en MB',
  `database_size_mb` decimal(10,2) DEFAULT 0.00 COMMENT 'Tamaño de BD en MB',
  `active_users` int(11) DEFAULT 0 COMMENT 'Usuarios activos en el día',
  `api_requests` int(11) DEFAULT 0 COMMENT 'Requests API realizados',
  `login_attempts` int(11) DEFAULT 0 COMMENT 'Intentos de login',
  `reports_generated` int(11) DEFAULT 0 COMMENT 'Reportes PDF generados',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_company_date` (`company_id`, `metric_date`),
  KEY `idx_date` (`metric_date`),
  FOREIGN KEY (`company_id`) REFERENCES `multitenancy_companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Métricas de uso diarias por tenant';

-- ===================================================
-- DATOS INICIALES
-- ===================================================

-- Configuraciones globales del sistema multitenancy
INSERT IGNORE INTO `system_settings` (`key`, `value`, `description`, `created_at`, `updated_at`) VALUES
('multitenancy_enabled', '1', 'Sistema multitenancy habilitado', NOW(), NOW()),
('wizard_enabled', '1', 'Wizard de setup habilitado', NOW(), NOW()),
('max_companies_allowed', '100', 'Máximo número de empresas permitidas', NOW(), NOW()),
('default_employee_limit', '100', 'Límite por defecto de empleados por empresa', NOW(), NOW()),
('default_storage_limit_mb', '1000', 'Límite por defecto de almacenamiento por empresa', NOW(), NOW()),
('backup_retention_days', '30', 'Días de retención de respaldos', NOW(), NOW()),
('wizard_admin_users', 'admin,superadmin', 'Usuarios administradores del wizard', NOW(), NOW());

-- ===================================================
-- ÍNDICES ADICIONALES PARA PERFORMANCE
-- ===================================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX IF NOT EXISTS `idx_company_status_created` ON `multitenancy_companies` (`status`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_tenant_company_status` ON `tenant_configurations` (`company_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_metrics_company_date` ON `tenant_metrics` (`company_id`, `metric_date` DESC);
CREATE INDEX IF NOT EXISTS `idx_logs_company_step` ON `wizard_logs` (`company_id`, `step`, `created_at` DESC);

-- ===================================================
-- TRIGGERS PARA AUDITORÍA Y AUTOMATIZACIÓN
-- ===================================================

-- Trigger para actualizar updated_at automáticamente
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS `tr_companies_updated_at`
    BEFORE UPDATE ON `multitenancy_companies`
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END$$

CREATE TRIGGER IF NOT EXISTS `tr_tenant_config_updated_at`
    BEFORE UPDATE ON `tenant_configurations`
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END$$

DELIMITER ;

-- ===================================================
-- VISTAS PARA CONSULTAS FRECUENTES
-- ===================================================

-- Vista completa de empresas con su configuración
CREATE OR REPLACE VIEW `v_companies_overview` AS
SELECT 
    c.id,
    c.company_name,
    c.ruc,
    c.admin_email,
    c.tenant_database,
    c.status as company_status,
    c.created_at,
    tc.subdomain,
    tc.max_employees,
    tc.max_storage_mb,
    tc.last_backup,
    tc.status as config_status,
    COALESCE(tm.employees_count, 0) as current_employees,
    COALESCE(tm.storage_used_mb, 0) as storage_used
FROM multitenancy_companies c
LEFT JOIN tenant_configurations tc ON c.id = tc.company_id
LEFT JOIN tenant_metrics tm ON c.id = tm.company_id 
    AND tm.metric_date = (
        SELECT MAX(metric_date) 
        FROM tenant_metrics tm2 
        WHERE tm2.company_id = c.id
    );

-- Vista de estadísticas del sistema multitenancy
CREATE OR REPLACE VIEW `v_multitenancy_stats` AS
SELECT 
    COUNT(*) as total_companies,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_companies,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_companies,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_companies,
    AVG(DATEDIFF(NOW(), created_at)) as avg_company_age_days,
    COUNT(DISTINCT DATE(created_at)) as creation_activity_days
FROM multitenancy_companies;

-- ===================================================
-- COMENTARIOS FINALES
-- ===================================================

-- Este esquema proporciona:
-- 1. ✅ Gestión completa de empresas multitenancy
-- 2. ✅ Configuración flexible por tenant
-- 3. ✅ Logging completo del wizard
-- 4. ✅ Métricas de uso y rendimiento
-- 5. ✅ Auditoría automática con triggers
-- 6. ✅ Vistas optimizadas para consultas
-- 7. ✅ Índices para performance en producción
-- 8. ✅ Escalabilidad para crecimiento futuro