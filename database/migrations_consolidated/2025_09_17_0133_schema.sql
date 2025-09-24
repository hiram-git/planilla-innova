-- =====================================================
-- Sistema de Planillas MVC - Esquema de Base de Datos
-- Versión: 2.1
-- Fecha: Septiembre 2025
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Configurar charset
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- TABLA: users - Usuarios del sistema
-- =====================================================
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_active` (`active`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: roles - Roles del sistema
-- =====================================================
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: companies - Configuración de empresa
-- =====================================================
CREATE TABLE `companies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_symbol` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'Q',
  `currency_code` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'GTQ',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: departments - Departamentos
-- =====================================================
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cargos - Cargos organizacionales
-- =====================================================
CREATE TABLE `cargos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: partidas - Partidas presupuestarias
-- =====================================================
CREATE TABLE `partidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partida` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: funciones - Funciones del puesto
-- =====================================================
CREATE TABLE `funciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: posiciones - Posiciones organizacionales
-- =====================================================
CREATE TABLE `posiciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sueldo` decimal(10,2) DEFAULT '0.00',
  `id_cargo` int DEFAULT NULL,
  `id_partida` int DEFAULT NULL,
  `id_funcion` int DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`),
  KEY `fk_posiciones_cargo` (`id_cargo`),
  KEY `fk_posiciones_partida` (`id_partida`),
  KEY `fk_posiciones_funcion` (`id_funcion`),
  KEY `fk_posiciones_department` (`department_id`),
  CONSTRAINT `fk_posiciones_cargo` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posiciones_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posiciones_funcion` FOREIGN KEY (`id_funcion`) REFERENCES `funciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posiciones_partida` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: schedules - Horarios de trabajo
-- =====================================================
CREATE TABLE `schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `break_time` int DEFAULT '60',
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: employees - Empleados
-- =====================================================
CREATE TABLE `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` enum('M','F','Otro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `contact_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position_id` int DEFAULT NULL,
  `schedule_id` int DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organigrama_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `idx_active` (`active`),
  KEY `idx_position` (`position_id`),
  KEY `idx_schedule` (`schedule_id`),
  KEY `idx_document` (`document_id`),
  CONSTRAINT `fk_employees_position` FOREIGN KEY (`position_id`) REFERENCES `posiciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_employees_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: tipos_planilla - Tipos de planilla
-- =====================================================
CREATE TABLE `tipos_planilla` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: frecuencias - Frecuencias de pago
-- =====================================================
CREATE TABLE `frecuencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dias` int DEFAULT '30',
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: situaciones - Situaciones laborales
-- =====================================================
CREATE TABLE `situaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: concepto - Conceptos de nómina
-- =====================================================
CREATE TABLE `concepto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `concepto` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cuenta_contable` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_concepto` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `formula` text COLLATE utf8mb4_unicode_ci,
  `valor_fijo` decimal(10,2) DEFAULT '0.00',
  `imprime_detalles` tinyint(1) DEFAULT '0',
  `prorratea` tinyint(1) DEFAULT '0',
  `modifica_valor` tinyint(1) DEFAULT '0',
  `valor_referencia` tinyint(1) DEFAULT '0',
  `monto_calculo` tinyint(1) DEFAULT '0',
  `monto_cero` tinyint(1) DEFAULT '0',
  `incluir_reporte` tinyint(1) DEFAULT '1',
  `categoria_reporte` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden_reporte` int DEFAULT '0',
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`),
  KEY `idx_tipo_concepto` (`tipo_concepto`),
  KEY `idx_categoria_reporte` (`categoria_reporte`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLAS DE RELACIONES PARA CONCEPTOS
-- =====================================================

-- Relación concepto - tipos de planilla
CREATE TABLE `concepto_tipos_planilla` (
  `id` int NOT NULL AUTO_INCREMENT,
  `concepto_id` int NOT NULL,
  `tipo_planilla_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_concepto_tipo` (`concepto_id`,`tipo_planilla_id`),
  KEY `fk_ctp_tipo_planilla` (`tipo_planilla_id`),
  CONSTRAINT `fk_ctp_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ctp_tipo_planilla` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relación concepto - frecuencias
CREATE TABLE `concepto_frecuencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `concepto_id` int NOT NULL,
  `frecuencia_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_concepto_frecuencia` (`concepto_id`,`frecuencia_id`),
  KEY `fk_cf_frecuencia` (`frecuencia_id`),
  CONSTRAINT `fk_cf_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cf_frecuencia` FOREIGN KEY (`frecuencia_id`) REFERENCES `frecuencias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relación concepto - situaciones
CREATE TABLE `concepto_situaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `concepto_id` int NOT NULL,
  `situacion_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_concepto_situacion` (`concepto_id`,`situacion_id`),
  KEY `fk_cs_situacion` (`situacion_id`),
  CONSTRAINT `fk_cs_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cs_situacion` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: creditors - Acreedores
-- =====================================================
CREATE TABLE `creditors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `creditor_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activo` (`activo`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_creditor_id` (`creditor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: deductions - Deducciones
-- =====================================================
CREATE TABLE `deductions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `creditor_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_creditor_id` (`creditor_id`),
  KEY `idx_active` (`active`),
  CONSTRAINT `fk_deductions_creditor` FOREIGN KEY (`creditor_id`) REFERENCES `creditors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: payrolls - Planillas de nómina
-- =====================================================
CREATE TABLE `payrolls` (
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
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_tipo_planilla` (`tipo_planilla_id`),
  KEY `idx_fechas` (`fecha_inicio`,`fecha_fin`),
  CONSTRAINT `fk_payrolls_frecuencia` FOREIGN KEY (`frecuencia_id`) REFERENCES `frecuencias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payrolls_situacion` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payrolls_tipo_planilla` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: payroll_employees - Empleados en planilla
-- =====================================================
CREATE TABLE `payroll_employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payroll_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `employee_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_completo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `posicion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salario_base` decimal(10,2) DEFAULT '0.00',
  `total_ingresos` decimal(10,2) DEFAULT '0.00',
  `total_deducciones` decimal(10,2) DEFAULT '0.00',
  `neto_pagar` decimal(10,2) DEFAULT '0.00',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_payroll_employee` (`payroll_id`,`employee_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_employee_code` (`employee_code`),
  CONSTRAINT `fk_pe_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pe_payroll` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: payroll_details - Detalles de planilla por concepto
-- =====================================================
CREATE TABLE `payroll_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payroll_id` int NOT NULL,
  `payroll_employee_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `concepto_id` int NOT NULL,
  `concepto_descripcion` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_concepto` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `formula_usada` text COLLATE utf8mb4_unicode_ci,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `categoria_reporte` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden_reporte` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payroll_id` (`payroll_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_concepto_id` (`concepto_id`),
  KEY `idx_payroll_employee_id` (`payroll_employee_id`),
  KEY `idx_categoria_reporte` (`categoria_reporte`),
  CONSTRAINT `fk_pd_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pd_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pd_payroll` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pd_payroll_employee` FOREIGN KEY (`payroll_employee_id`) REFERENCES `payroll_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: attendance - Asistencia (opcional)
-- =====================================================
CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `hours_worked` decimal(4,2) DEFAULT '0.00',
  `status` enum('PRESENTE','AUSENTE','TARDANZA','PERMISO','VACACIONES') COLLATE utf8mb4_unicode_ci DEFAULT 'PRESENTE',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_date` (`employee_id`,`date`),
  KEY `idx_date` (`date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: system_logs - Logs del sistema
-- =====================================================
CREATE TABLE `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_affected` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_affected` (`table_affected`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: system_settings - Configuraciones del sistema
-- =====================================================
CREATE TABLE `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ÍNDICES ADICIONALES PARA PERFORMANCE
-- =====================================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX `idx_employee_position_active` ON `employees` (`position_id`, `active`);
CREATE INDEX `idx_payroll_state_dates` ON `payrolls` (`estado`, `fecha_inicio`, `fecha_fin`);
CREATE INDEX `idx_payroll_details_summary` ON `payroll_details` (`payroll_id`, `tipo_concepto`, `amount`);
CREATE INDEX `idx_deductions_active_employee` ON `deductions` (`active`, `employee_id`);
CREATE INDEX `idx_concepto_active_tipo` ON `concepto` (`active`, `tipo_concepto`);

-- Índices de texto completo para búsquedas
CREATE FULLTEXT INDEX `ft_employees_names` ON `employees` (`firstname`, `lastname`);
CREATE FULLTEXT INDEX `ft_concepto_description` ON `concepto` (`descripcion`);

COMMIT;

-- =====================================================
-- COMENTARIOS FINALES
-- =====================================================

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;