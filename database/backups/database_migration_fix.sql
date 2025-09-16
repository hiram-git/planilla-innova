-- =====================================================
-- MIGRACIÓN CRÍTICA - CORRECCIÓN ESQUEMA BASE DE DATOS
-- Sistema de Planillas MVC - Campos Faltantes
-- Fecha: Septiembre 2025
-- =====================================================

-- IMPORTANTE: Ejecutar este script DESPUÉS de la instalación básica
-- para agregar campos y tablas faltantes encontradas en el análisis

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- CORRECCIONES TABLA COMPANIES
-- =====================================================

-- Agregar campos faltantes utilizados por CompanyController
ALTER TABLE `companies` 
ADD COLUMN `company_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `name`,
ADD COLUMN `ruc` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `nit`,
ADD COLUMN `legal_representative` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `ruc`,
ADD COLUMN `business_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `legal_representative`,
ADD COLUMN `registration_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `business_type`;

-- Sincronizar datos existentes si los hay
UPDATE `companies` SET 
    `company_name` = `name`,
    `ruc` = `nit`
WHERE `company_name` IS NULL OR `ruc` IS NULL;

-- =====================================================
-- CORRECCIONES TABLA EMPLOYEES - CAMPOS ORGANIZACIONALES
-- =====================================================

-- Verificar y agregar campos organizacionales faltantes
ALTER TABLE `employees` 
ADD COLUMN IF NOT EXISTS `situacion_id` int DEFAULT NULL AFTER `tipo_planilla_id`,
ADD COLUMN IF NOT EXISTS `cargo_id` int DEFAULT NULL AFTER `situacion_id`,
ADD COLUMN IF NOT EXISTS `funcion_id` int DEFAULT NULL AFTER `cargo_id`,
ADD COLUMN IF NOT EXISTS `partida_id` int DEFAULT NULL AFTER `funcion_id`;

-- Agregar foreign keys para los nuevos campos
ALTER TABLE `employees`
ADD CONSTRAINT `fk_employees_situacion_fix` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `fk_employees_cargo_fix` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `fk_employees_funcion_fix` FOREIGN KEY (`funcion_id`) REFERENCES `funciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `fk_employees_partida_fix` FOREIGN KEY (`partida_id`) REFERENCES `partidas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Agregar índices para mejor rendimiento
CREATE INDEX IF NOT EXISTS `idx_employees_situacion` ON `employees` (`situacion_id`);
CREATE INDEX IF NOT EXISTS `idx_employees_cargo` ON `employees` (`cargo_id`);
CREATE INDEX IF NOT EXISTS `idx_employees_funcion` ON `employees` (`funcion_id`);
CREATE INDEX IF NOT EXISTS `idx_employees_partida` ON `partida_id`);

-- =====================================================
-- TABLA PLANILLA_DETALLE - CAMPOS GESTIÓN MANUAL
-- =====================================================

-- Verificar y agregar campos para gestión manual de valores
ALTER TABLE `payroll_details`
ADD COLUMN IF NOT EXISTS `valor_original` decimal(10,2) DEFAULT NULL AFTER `amount`,
ADD COLUMN IF NOT EXISTS `valor_manual` decimal(10,2) DEFAULT NULL AFTER `valor_original`,
ADD COLUMN IF NOT EXISTS `es_manual` tinyint(1) DEFAULT '0' AFTER `valor_manual`,
ADD COLUMN IF NOT EXISTS `observaciones` text COLLATE utf8mb4_unicode_ci AFTER `es_manual`,
ADD COLUMN IF NOT EXISTS `usuario_modificacion` int DEFAULT NULL AFTER `observaciones`,
ADD COLUMN IF NOT EXISTS `fecha_modificacion` timestamp NULL DEFAULT NULL AFTER `usuario_modificacion`;

-- Índice para búsquedas de valores modificados manualmente
CREATE INDEX IF NOT EXISTS `idx_payroll_details_manual` ON `payroll_details` (`es_manual`, `payroll_id`);

-- =====================================================
-- SISTEMA DE PLANILLA_CABECERA (SI NO EXISTE)
-- =====================================================

-- Tabla para cabecera de planillas (usada en algunos controladores)
CREATE TABLE IF NOT EXISTS `planilla_cabecera` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `tipo_planilla_id` int DEFAULT NULL,
  `frecuencia_id` int DEFAULT NULL,
  `situacion_id` int DEFAULT NULL,
  `estado` enum('NUEVA','PROCESANDO','PROCESADA','CERRADA','ANULADA') COLLATE utf8mb4_unicode_ci DEFAULT 'NUEVA',
  `total_empleados` int DEFAULT '0',
  `total_ingresos` decimal(12,2) DEFAULT '0.00',
  `total_deducciones` decimal(12,2) DEFAULT '0.00',
  `total_neto` decimal(12,2) DEFAULT '0.00',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo_planilla` (`tipo_planilla_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fechas` (`fecha_inicio`, `fecha_fin`),
  CONSTRAINT `fk_planilla_cabecera_tipo` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_planilla_cabecera_frecuencia` FOREIGN KEY (`frecuencia_id`) REFERENCES `frecuencias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_planilla_cabecera_situacion` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA ROLES (SISTEMA DE PERMISOS)
-- =====================================================

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA USER_ROLES (RELACIÓN USUARIOS-ROLES)
-- =====================================================

CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_roles_user_role_unique` (`user_id`, `role_id`),
  KEY `fk_user_roles_role` (`role_id`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS INICIALES PARA ROLES
-- =====================================================

-- Insertar roles básicos
INSERT IGNORE INTO `roles` (`name`, `display_name`, `description`, `permissions`) VALUES
('super_admin', 'Super Administrador', 'Acceso total al sistema', '{"*": ["create", "read", "update", "delete"]}'),
('admin', 'Administrador', 'Administrador del sistema', '{"employees": ["create", "read", "update", "delete"], "payrolls": ["create", "read", "update", "delete"], "concepts": ["create", "read", "update", "delete"], "reports": ["read"]}'),
('user', 'Usuario', 'Usuario básico del sistema', '{"employees": ["read"], "payrolls": ["read"], "reports": ["read"]}'),
('hr_manager', 'Gerente RRHH', 'Gestión de recursos humanos', '{"employees": ["create", "read", "update"], "payrolls": ["read", "update"], "reports": ["read"]}');

-- Asignar rol super_admin al usuario admin existente
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) 
SELECT u.id, r.id FROM `users` u, `roles` r 
WHERE u.username = 'admin' AND r.name = 'super_admin' LIMIT 1;

-- =====================================================
-- TABLA ASIGNACIONES (ASIGNACIÓN DE EMPLEADOS A PLANILLAS)
-- =====================================================

CREATE TABLE IF NOT EXISTS `asignaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `tipo_planilla_id` int NOT NULL,
  `frecuencia_id` int NOT NULL,
  `situacion_id` int NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_tipo_planilla` (`employee_id`, `tipo_planilla_id`),
  KEY `idx_tipo_planilla` (`tipo_planilla_id`),
  KEY `idx_frecuencia` (`frecuencia_id`),
  KEY `idx_situacion` (`situacion_id`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_asignaciones_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asignaciones_tipo_planilla` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asignaciones_frecuencia` FOREIGN KEY (`frecuencia_id`) REFERENCES `frecuencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asignaciones_situacion` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CORRECCIONES TABLA CONCEPTO - CAMPOS FALTANTES
-- =====================================================

-- Agregar campos que pueden estar faltando
ALTER TABLE `concepto`
ADD COLUMN IF NOT EXISTS `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `concepto`,
ADD COLUMN IF NOT EXISTS `aplica_isr` tinyint(1) DEFAULT '0' AFTER `monto_cero`,
ADD COLUMN IF NOT EXISTS `exento_isr` tinyint(1) DEFAULT '0' AFTER `aplica_isr`,
ADD COLUMN IF NOT EXISTS `formula_alternativa` text COLLATE utf8mb4_unicode_ci AFTER `formula`,
ADD COLUMN IF NOT EXISTS `requiere_autorizacion` tinyint(1) DEFAULT '0' AFTER `exento_isr`;

-- Índice para búsqueda por código
CREATE INDEX IF NOT EXISTS `idx_concepto_codigo` ON `concepto` (`codigo`);

-- =====================================================
-- ACTUALIZACIÓN DATOS EMPRESA POR DEFECTO
-- =====================================================

-- Actualizar datos de la empresa ejemplo con campos nuevos
UPDATE `companies` SET 
    `company_name` = COALESCE(`company_name`, `name`),
    `ruc` = COALESCE(`ruc`, `nit`),
    `legal_representative` = COALESCE(`legal_representative`, 'Representante Legal'),
    `business_type` = COALESCE(`business_type`, 'Sociedad Anónima'),
    `registration_number` = COALESCE(`registration_number`, '12345-2025')
WHERE `id` = 1;

-- =====================================================
-- VISTAS ACTUALIZADAS CON NUEVOS CAMPOS
-- =====================================================

-- Actualizar vista de empleados completos
DROP VIEW IF EXISTS `view_employees_complete`;
CREATE VIEW `view_employees_complete` AS
SELECT 
    e.id,
    e.employee_id,
    e.firstname,
    e.lastname,
    CONCAT(e.firstname, ' ', e.lastname) as full_name,
    e.document_id,
    e.birthdate,
    e.gender,
    e.address,
    e.contact_info,
    e.fecha_ingreso,
    p.descripcion as position_name,
    p.sueldo as salary,
    c.descripcion as cargo_name,
    part.partida as partida_code,
    part.descripcion as partida_name,
    f.descripcion as funcion_name,
    s.name as schedule_name,
    CONCAT(TIME_FORMAT(s.time_in, '%H:%i'), ' - ', TIME_FORMAT(s.time_out, '%H:%i')) as schedule_hours,
    sit.descripcion as situacion_name,
    sit.codigo as situacion_code,
    tp.descripcion as tipo_planilla_name,
    tp.codigo as tipo_planilla_code,
    e.active,
    e.created_on
FROM employees e
LEFT JOIN position p ON e.position_id = p.id
LEFT JOIN cargos c ON e.cargo_id = c.id
LEFT JOIN partidas part ON e.partida_id = part.id
LEFT JOIN funciones f ON e.funcion_id = f.id
LEFT JOIN schedules s ON e.schedule_id = s.id
LEFT JOIN situaciones sit ON e.situacion_id = sit.id
LEFT JOIN tipos_planilla tp ON e.tipo_planilla_id = tp.id;

-- =====================================================
-- ÍNDICES ADICIONALES PARA RENDIMIENTO
-- =====================================================

-- Índices compuestos para consultas frecuentes con nuevos campos
CREATE INDEX IF NOT EXISTS `idx_employees_org_structure` ON `employees` (`cargo_id`, `partida_id`, `funcion_id`, `active`);
CREATE INDEX IF NOT EXISTS `idx_payroll_details_processing` ON `payroll_details` (`payroll_id`, `tipo_concepto`, `es_manual`);
CREATE INDEX IF NOT EXISTS `idx_asignaciones_active` ON `asignaciones` (`activo`, `tipo_planilla_id`, `situacion_id`);

-- =====================================================
-- LIMPIEZA Y OPTIMIZACIÓN
-- =====================================================

-- Actualizar estadísticas de las tablas para mejor rendimiento
ANALYZE TABLE `companies`, `employees`, `concepto`, `payroll_details`, `roles`, `user_roles`, `asignaciones`;

COMMIT;

-- =====================================================
-- VERIFICACIÓN DE INTEGRIDAD
-- =====================================================

-- Verificar que todas las foreign keys están correctamente configuradas
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE 
    REFERENCED_TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('employees', 'payroll_details', 'asignaciones', 'user_roles')
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- =====================================================
-- FINALIZACIÓN
-- =====================================================

-- Mostrar resumen de tablas actualizadas
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME,
    UPDATE_TIME
FROM 
    INFORMATION_SCHEMA.TABLES 
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('companies', 'employees', 'concepto', 'payroll_details', 'roles', 'user_roles', 'asignaciones', 'planilla_cabecera')
ORDER BY TABLE_NAME;

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================

/*
MIGRACIÓN COMPLETADA:

✅ Tabla companies: Agregados 5 campos organizacionales
✅ Tabla employees: Agregados 4 campos estructura organizacional + foreign keys
✅ Tabla payroll_details: Agregados 6 campos gestión manual
✅ Tabla planilla_cabecera: Creada si no existe
✅ Sistema roles: Tablas roles + user_roles + datos iniciales
✅ Tabla asignaciones: Sistema asignación empleados-planillas
✅ Tabla concepto: Campos adicionales para funcionalidad completa
✅ Vistas: Actualizadas con nuevos campos
✅ Índices: Optimización rendimiento
✅ Datos: Empresa actualizada con nuevos campos

PRÓXIMOS PASOS:
1. Verificar que no hay errores en la aplicación
2. Probar funcionalidades de CompanyController
3. Verificar procesamiento de planillas
4. Validar sistema de roles y permisos

NOTAS:
- Todos los cambios son compatibles hacia atrás
- Se preservan datos existentes
- Se usan IF NOT EXISTS para evitar duplicados
- Foreign keys con ON DELETE SET NULL para seguridad
*/