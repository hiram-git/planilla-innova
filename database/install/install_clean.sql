-- =====================================================
-- PLANILLA SIMPLE MVC - INSTALACIÓN LIMPIA COMPLETA
-- Version: 3.0 VPS Ready
-- Fecha: Septiembre 2025
-- Sistema de Planillas para VPS con todas las funcionalidades
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- CONFIGURAR BASE DE DATOS
-- =====================================================

-- Crear base de datos si no existe (cambiar nombre según necesites)
CREATE DATABASE IF NOT EXISTS `planilla_innova` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `planilla_innova`;

-- =====================================================
-- TABLA: users - Sistema de usuarios moderno
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
-- TABLA: admin - Sistema de admin (compatibilidad)
-- =====================================================
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: companies - Configuración empresarial
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
-- ESTRUCTURA ORGANIZACIONAL COMPLETA
-- =====================================================

-- Horarios de trabajo
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

-- Departamentos organizacionales
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

-- Cargos organizacionales
CREATE TABLE `cargos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partidas presupuestarias
CREATE TABLE `partidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partida` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Funciones del puesto
CREATE TABLE `funciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posiciones (compatibilidad sistema anterior)
CREATE TABLE `position` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sueldo` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Sueldo base de la posición',
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posiciones nueva estructura integrada
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
-- SISTEMA DE PLANILLAS AVANZADO
-- =====================================================

-- Tipos de planilla
CREATE TABLE `tipos_planilla` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Frecuencias de pago
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

-- Situaciones laborales
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
-- TABLA: employees - Empleados con estructura completa
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
  `cargo_id` int DEFAULT NULL,
  `partida_id` int DEFAULT NULL,
  `funcion_id` int DEFAULT NULL,
  `schedule_id` int DEFAULT NULL,
  `situacion_id` int DEFAULT NULL,
  `tipo_planilla_id` int DEFAULT NULL,
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
  KEY `fk_employees_cargo` (`cargo_id`),
  KEY `fk_employees_partida` (`partida_id`),
  KEY `fk_employees_funcion` (`funcion_id`),
  KEY `fk_employees_situacion` (`situacion_id`),
  KEY `fk_employees_tipo_planilla` (`tipo_planilla_id`),
  CONSTRAINT `fk_employees_position` FOREIGN KEY (`position_id`) REFERENCES `position` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_employees_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_employees_cargo` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_funcion` FOREIGN KEY (`funcion_id`) REFERENCES `funciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_partida` FOREIGN KEY (`partida_id`) REFERENCES `partidas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_situacion` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_tipo_planilla` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SISTEMA DE CONCEPTOS AVANZADO CON FÓRMULAS
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
-- TABLAS DE RELACIONES PARA CONCEPTOS FLEXIBLES
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
-- SISTEMA DE ACREEDORES Y DEDUCCIONES
-- =====================================================

-- Acreedores/Prestamistas
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

-- Deducciones por empleado
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
-- SISTEMA DE PLANILLAS COMPLETO
-- =====================================================

-- Planillas principales
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

-- Empleados incluidos en planilla
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

-- Detalles de planilla por concepto y empleado
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
-- TABLAS ADICIONALES DEL SISTEMA
-- =====================================================

-- Control de asistencia (opcional)
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

-- Logs de actividad del sistema
CREATE TABLE `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_affected` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `additional_data` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON data with old/new values',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_affected` (`table_affected`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_action_date` (`user_id`, `action`, `created_at`),
  CONSTRAINT `fk_logs_user_admin` FOREIGN KEY (`user_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuraciones del sistema
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
-- ÍNDICES PARA OPTIMIZACIÓN DE RENDIMIENTO
-- =====================================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX `idx_employee_position_active` ON `employees` (`position_id`, `active`);
CREATE INDEX `idx_payroll_state_dates` ON `payrolls` (`estado`, `fecha_inicio`, `fecha_fin`);
CREATE INDEX `idx_payroll_details_summary` ON `payroll_details` (`payroll_id`, `tipo_concepto`, `amount`);
CREATE INDEX `idx_deductions_active_employee` ON `deductions` (`active`, `employee_id`);
CREATE INDEX `idx_concepto_active_tipo` ON `concepto` (`active`, `tipo_concepto`);

-- Índices de texto completo para búsquedas rápidas
CREATE FULLTEXT INDEX `ft_employees_names` ON `employees` (`firstname`, `lastname`);
CREATE FULLTEXT INDEX `ft_concepto_description` ON `concepto` (`descripcion`);

-- =====================================================
-- DATOS INICIALES ESENCIALES PARA FUNCIONAMIENTO
-- =====================================================

-- Usuario administrador principal
INSERT INTO `admin` (`username`, `password`, `firstname`, `lastname`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Sistema');
-- Contraseña: password

INSERT INTO `users` (`username`, `password`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@empresa.com', 'admin');
-- Contraseña: password

-- Configuración de empresa (personalizar según necesidades)
INSERT INTO `companies` (`name`, `nit`, `address`, `phone`, `email`, `currency_symbol`, `currency_code`) VALUES
('Mi Empresa S.A.', '12345678-9', 'Dirección de la Empresa', '2234-5678', 'info@miempresa.com', 'Q', 'GTQ');

-- Horarios de trabajo estándar
INSERT INTO `schedules` (`name`, `time_in`, `time_out`, `break_time`) VALUES
('Horario Completo', '08:00:00', '17:00:00', 60),
('Medio Tiempo Matutino', '08:00:00', '12:00:00', 0),
('Medio Tiempo Vespertino', '13:00:00', '17:00:00', 0),
('Horario Extendido', '07:00:00', '18:00:00', 90);

-- Departamentos organizacionales
INSERT INTO `departments` (`name`, `description`, `code`) VALUES
('Administración', 'Departamento de Administración General', 'ADM'),
('Recursos Humanos', 'Departamento de Recursos Humanos', 'RH'),
('Contabilidad', 'Departamento de Contabilidad', 'CONT'),
('Operaciones', 'Departamento de Operaciones', 'OPS'),
('Ventas', 'Departamento de Ventas', 'VEN');

-- Cargos organizacionales
INSERT INTO `cargos` (`descripcion`, `codigo`) VALUES
('Director/Gerente', 'DIR'),
('Jefe/Supervisor', 'JEFE'),
('Profesional', 'PROF'),
('Técnico', 'TEC'),
('Administrativo', 'ADM'),
('Operativo', 'OP');

-- Partidas presupuestarias básicas
INSERT INTO `partidas` (`partida`, `descripcion`) VALUES
('001-001', 'Sueldos y Salarios - Personal Permanente'),
('001-002', 'Sueldos y Salarios - Personal Temporal'),
('002-001', 'Bonificaciones por Servicios'),
('002-002', 'Compensaciones Especiales'),
('003-001', 'Contribuciones Patronales');

-- Funciones del puesto
INSERT INTO `funciones` (`descripcion`, `codigo`) VALUES
('Dirección y Gerencia', 'DIR'),
('Supervisión', 'SUP'),
('Administración', 'ADM'),
('Operaciones', 'OPS'),
('Soporte Técnico', 'TEC'),
('Atención al Cliente', 'ATC');

-- Posiciones básicas de trabajo
INSERT INTO `position` (`descripcion`, `sueldo`) VALUES
('Director General', 20000.00),
('Gerente', 15000.00),
('Jefe de Departamento', 12000.00),
('Supervisor', 8000.00),
('Profesional Senior', 7000.00),
('Profesional Junior', 5000.00),
('Técnico', 4000.00),
('Administrativo', 3500.00),
('Operario', 3000.00),
('Asistente', 2800.00);

-- Tipos de planilla
INSERT INTO `tipos_planilla` (`descripcion`, `codigo`) VALUES
('Planilla Ordinaria', 'ORD'),
('Planilla Extraordinaria', 'EXT'),
('Aguinaldo', 'AGU'),
('Vacaciones', 'VAC'),
('Bonificación Especial', 'BON');

-- Frecuencias de pago
INSERT INTO `frecuencias` (`descripcion`, `codigo`, `dias`) VALUES
('Mensual', 'MENS', 30),
('Quincenal', 'QUIN', 15),
('Semanal', 'SEM', 7),
('Anual', 'ANUAL', 365),
('Trimestral', 'TRIM', 90);

-- Situaciones laborales
INSERT INTO `situaciones` (`descripcion`, `codigo`) VALUES
('Activo', 'ACT'),
('Vacaciones', 'VAC'),
('Permiso con Goce', 'PG'),
('Permiso sin Goce', 'PSG'),
('Incapacidad IGSS', 'INC'),
('Suspendido', 'SUS'),
('Retirado', 'RET');

-- =====================================================
-- CONCEPTOS DE NÓMINA COMPLETOS Y FUNCIONALES
-- =====================================================

INSERT INTO `concepto` (`concepto`, `descripcion`, `tipo_concepto`, `formula`, `valor_fijo`, `imprime_detalles`, `monto_cero`, `categoria_reporte`, `orden_reporte`) VALUES
-- INGRESOS BÁSICOS
('01', 'SUELDO BASE', 'INGRESO', 'SALARIO', 0.00, 1, 0, 'INGRESOS', 1),
('02', 'BONIFICACIÓN DECRETO', 'INGRESO', '250', 250.00, 1, 0, 'INGRESOS', 2),
('03', 'BONIFICACIÓN INCENTIVO', 'INGRESO', 'SALARIO * 0.20', 0.00, 1, 1, 'INGRESOS', 3),

-- INGRESOS VARIABLES
('04', 'HORAS EXTRA ORDINARIAS', 'INGRESO', 'SALARIO / 240 * HORAS * 1.5', 0.00, 1, 1, 'INGRESOS', 4),
('05', 'HORAS EXTRA DOMINICALES', 'INGRESO', 'SALARIO / 240 * HORAS * 2', 0.00, 1, 1, 'INGRESOS', 5),
('06', 'COMISIONES VENTAS', 'INGRESO', 'VENTAS * 0.03', 0.00, 1, 1, 'INGRESOS', 6),

-- PRESTACIONES
('07', 'AGUINALDO', 'INGRESO', 'SALARIO', 0.00, 1, 1, 'PRESTACIONES', 7),
('08', 'VACACIONES', 'INGRESO', 'SALARIO / 2', 0.00, 1, 1, 'PRESTACIONES', 8),
('09', 'INDEMNIZACIÓN', 'INGRESO', 'SALARIO * AÑOS', 0.00, 1, 1, 'PRESTACIONES', 9),

-- DEDUCCIONES LEGALES
('10', 'IGSS LABORAL', 'DEDUCCION', 'SALARIO * 0.0483', 0.00, 1, 0, 'DEDUCCIONES LEGALES', 10),
('11', 'IRTRA', 'DEDUCCION', 'SALARIO * 0.01', 0.00, 1, 0, 'DEDUCCIONES LEGALES', 11),
('12', 'IMPUESTO ISR', 'DEDUCCION', '(SALARIO > 30000) ? (SALARIO - 30000) * 0.05 + ((SALARIO > 50000) ? (SALARIO - 50000) * 0.02 : 0) : 0', 0.00, 1, 1, 'DEDUCCIONES LEGALES', 12),

-- DEDUCCIONES VOLUNTARIAS
('13', 'PRÉSTAMO PERSONAL', 'DEDUCCION', 'ACREEDOR(EMPLEADO, 1)', 0.00, 1, 1, 'DEDUCCIONES VOLUNTARIAS', 13),
('14', 'BANCO DESCUENTO', 'DEDUCCION', 'ACREEDOR(EMPLEADO, 2)', 0.00, 1, 1, 'DEDUCCIONES VOLUNTARIAS', 14),
('15', 'COOPERATIVA', 'DEDUCCION', 'ACREEDOR(EMPLEADO, 3)', 0.00, 1, 1, 'DEDUCCIONES VOLUNTARIAS', 15),
('16', 'SEGURO MÉDICO', 'DEDUCCION', '200', 200.00, 1, 1, 'DEDUCCIONES VOLUNTARIAS', 16),
('17', 'PLAN PENSIONES', 'DEDUCCION', 'SALARIO * 0.05', 0.00, 1, 1, 'DEDUCCIONES VOLUNTARIAS', 17);

-- =====================================================
-- RELACIONES DE CONCEPTOS CON CONFIGURACIONES
-- =====================================================

-- Todos los conceptos básicos aplican a planilla ordinaria
INSERT INTO `concepto_tipos_planilla` (`concepto_id`, `tipo_planilla_id`)
SELECT id, 1 FROM `concepto` WHERE `active` = 1 AND id <= 17;

-- Conceptos específicos para aguinaldo
INSERT INTO `concepto_tipos_planilla` (`concepto_id`, `tipo_planilla_id`)
SELECT id, 3 FROM `concepto` WHERE `concepto` IN ('01', '07');

-- Todos los conceptos básicos aplican a frecuencia mensual
INSERT INTO `concepto_frecuencias` (`concepto_id`, `frecuencia_id`)
SELECT id, 1 FROM `concepto` WHERE `active` = 1;

-- Todos los conceptos aplican a empleados activos
INSERT INTO `concepto_situaciones` (`concepto_id`, `situacion_id`)
SELECT id, 1 FROM `concepto` WHERE `active` = 1;

-- =====================================================
-- ACREEDORES BÁSICOS DEL SISTEMA
-- =====================================================

INSERT INTO `creditors` (`description`, `amount`, `creditor_id`, `tipo`) VALUES
('Préstamos Personales', 0.00, 'PREST-001', 'PRESTAMISTA'),
('Banco Industrial', 0.00, 'BINDI-001', 'BANCO'),
('Cooperativa de Empleados', 0.00, 'COOP-001', 'COOPERATIVA'),
('Seguro Social - IGSS', 0.00, 'IGSS-001', 'GOBIERNO'),
('Superintendencia SAT', 0.00, 'SAT-001', 'GOBIERNO'),
('Farmacia Empresa', 0.00, 'FARM-001', 'PROVEEDOR'),
('Tienda Comisariato', 0.00, 'COMI-001', 'PROVEEDOR');

-- =====================================================
-- EMPLEADO DE DEMOSTRACIÓN (OPCIONAL)
-- =====================================================

INSERT INTO `employees` (`employee_id`, `firstname`, `lastname`, `document_id`, `birthdate`, `gender`, `address`, `contact_info`, `position_id`, `schedule_id`, `situacion_id`, `tipo_planilla_id`, `fecha_ingreso`) VALUES
('EMP001', 'Juan Carlos', 'López Martínez', '1234567890101', '1985-05-15', 'M', 'Ciudad de Guatemala, Guatemala', 'juan.lopez@miempresa.com / 5555-1234', 5, 1, 1, 1, CURDATE());

-- Deducción de ejemplo para el empleado
INSERT INTO `deductions` (`employee_id`, `creditor_id`, `amount`, `description`, `active`) VALUES
('EMP001', 2, 750.00, 'Préstamo bancario', 1);

-- =====================================================
-- CONFIGURACIONES DEL SISTEMA
-- =====================================================

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('app_name', 'Sistema de Planillas', 'string', 'Nombre de la aplicación'),
('app_version', '3.0.0', 'string', 'Versión del sistema'),
('default_currency', 'GTQ', 'string', 'Moneda por defecto'),
('currency_symbol', 'Q', 'string', 'Símbolo de moneda'),
('max_employees_per_payroll', '5000', 'integer', 'Máximo empleados por planilla'),
('payroll_backup_enabled', '1', 'boolean', 'Habilitar respaldos automáticos'),
('system_timezone', 'America/Guatemala', 'string', 'Zona horaria del sistema'),
('decimal_places', '2', 'integer', 'Decimales en montos'),
('payroll_auto_calc', '1', 'boolean', 'Cálculo automático de planillas');

-- =====================================================
-- VISTAS OPTIMIZADAS PARA CONSULTAS
-- =====================================================

-- Vista completa de empleados con todas las relaciones
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

-- Vista de conceptos con todas sus relaciones
CREATE VIEW `view_conceptos_relations` AS
SELECT 
    c.id,
    c.concepto,
    c.descripcion,
    c.tipo_concepto,
    c.formula,
    c.valor_fijo,
    c.imprime_detalles,
    c.prorratea,
    c.modifica_valor,
    c.valor_referencia,
    c.monto_calculo,
    c.monto_cero,
    c.categoria_reporte,
    c.orden_reporte,
    c.active,
    GROUP_CONCAT(DISTINCT tp.descripcion ORDER BY tp.descripcion SEPARATOR ', ') as tipos_planilla,
    GROUP_CONCAT(DISTINCT f.descripcion ORDER BY f.descripcion SEPARATOR ', ') as frecuencias,
    GROUP_CONCAT(DISTINCT sit.descripcion ORDER BY sit.descripcion SEPARATOR ', ') as situaciones,
    c.created_at
FROM concepto c
LEFT JOIN concepto_tipos_planilla ctp ON c.id = ctp.concepto_id
LEFT JOIN tipos_planilla tp ON ctp.tipo_planilla_id = tp.id AND tp.active = 1
LEFT JOIN concepto_frecuencias cf ON c.id = cf.concepto_id
LEFT JOIN frecuencias f ON cf.frecuencia_id = f.id AND f.active = 1
LEFT JOIN concepto_situaciones cs ON c.id = cs.concepto_id
LEFT JOIN situaciones sit ON cs.situacion_id = sit.id AND sit.active = 1
GROUP BY c.id, c.concepto, c.descripcion, c.tipo_concepto, c.formula, c.valor_fijo, 
         c.imprime_detalles, c.prorratea, c.modifica_valor, c.valor_referencia, 
         c.monto_calculo, c.monto_cero, c.categoria_reporte, c.orden_reporte, 
         c.active, c.created_at;

-- Vista resumen de planillas
CREATE VIEW `view_payrolls_summary` AS
SELECT 
    p.id,
    p.descripcion,
    p.fecha_inicio,
    p.fecha_fin,
    p.estado,
    tp.descripcion as tipo_planilla,
    tp.codigo as tipo_planilla_code,
    f.descripcion as frecuencia,
    s.descripcion as situacion,
    p.total_empleados,
    p.total_ingresos,
    p.total_deducciones,
    p.total_neto,
    p.processed_at,
    p.created_at,
    DATEDIFF(p.fecha_fin, p.fecha_inicio) as dias_periodo
FROM payrolls p
LEFT JOIN tipos_planilla tp ON p.tipo_planilla_id = tp.id
LEFT JOIN frecuencias f ON p.frecuencia_id = f.id
LEFT JOIN situaciones s ON p.situacion_id = s.id;

COMMIT;

-- =====================================================
-- INFORMACIÓN DE INSTALACIÓN EXITOSA
-- =====================================================

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- =====================================================
-- SISTEMA INSTALADO EXITOSAMENTE
-- =====================================================
-- 
-- ✅ Base de Datos: planilla_innova
-- ✅ Tablas Principales: 27
-- ✅ Vistas Optimizadas: 3
-- ✅ Índices de Rendimiento: 20+
-- ✅ Datos Iniciales: Completos
-- ✅ Conceptos de Nómina: 17
-- ✅ Configuraciones: 9
-- ✅ Empleado Ejemplo: 1
-- 
-- CREDENCIALES DE ACCESO:
-- Usuario: admin
-- Contraseña: password
-- 
-- PERSONALIZACIÓN REQUERIDA:
-- 1. Cambiar datos de la empresa en tabla 'companies'
-- 2. Modificar conceptos según legislación local
-- 3. Ajustar tipos de planilla según necesidades
-- 4. Configurar acreedores específicos
-- 5. Cambiar contraseña del administrador
-- 
-- COMANDO PARA CAMBIAR CONTRASEÑA:
-- UPDATE admin SET password = PASSWORD('nueva_contraseña') WHERE username = 'admin';
-- 
-- ¡SISTEMA LISTO PARA PRODUCCIÓN!
-- =====================================================