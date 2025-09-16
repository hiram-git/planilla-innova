-- =====================================================
-- PLANILLA SIMPLE MVC - INSTALACIÓN ESQUEMA REAL
-- Version: 4.0 Real Schema
-- Fecha: Septiembre 2025
-- Basado en ESQUEMA REAL extraído de la base de datos actual
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- =====================================================
-- CONFIGURAR BASE DE DATOS
-- =====================================================

CREATE DATABASE IF NOT EXISTS `planilla_sistema` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `planilla_sistema`;

-- =====================================================
-- TABLA: admin - ESTRUCTURA REAL CON TODOS LOS CAMPOS
-- =====================================================
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `password` varchar(60) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `photo` varchar(200) NOT NULL,
  `created_on` date NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- =====================================================
-- TABLA: attendance - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` time NOT NULL,
  `status` int(1) NOT NULL,
  `time_out` time NOT NULL,
  `num_hr` double NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- =====================================================
-- TABLA: cargos - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `cargos`;
CREATE TABLE `cargos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: cashadvance - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `cashadvance`;
CREATE TABLE `cashadvance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_advance` date NOT NULL,
  `employee_id` varchar(15) NOT NULL,
  `amount` double NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- =====================================================
-- TABLA: companies - ESTRUCTURA REAL COMPLETA
-- =====================================================
DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `ruc` varchar(50) NOT NULL,
  `legal_representative` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `currency_symbol` varchar(10) DEFAULT 'Q',
  `currency_code` varchar(3) DEFAULT 'GTQ',
  `logo_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: concepto - ESTRUCTURA REAL COMPLETA
-- =====================================================
DROP TABLE IF EXISTS `concepto`;
CREATE TABLE `concepto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `concepto` varchar(10) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `cuenta_contable` varchar(20) DEFAULT NULL,
  `tipo_concepto` enum('ingreso','deduccion','otro') NOT NULL DEFAULT 'ingreso',
  `unidad` enum('fijo','calculado') NOT NULL DEFAULT 'fijo',
  `formula` text DEFAULT NULL,
  `valor_fijo` decimal(10,2) DEFAULT 0.00,
  `imprime_detalles` int(11) DEFAULT NULL,
  `prorratea` int(11) DEFAULT NULL,
  `modifica_valor` int(11) DEFAULT NULL,
  `valor_referencia` int(11) DEFAULT NULL,
  `monto_calculo` int(11) DEFAULT NULL,
  `monto_cero` int(11) DEFAULT NULL,
  `incluir_reporte` tinyint(1) DEFAULT 1 COMMENT 'Si debe incluirse en reportes PDF',
  `categoria_reporte` enum('seguro_social','seguro_educativo','impuesto_renta','otras_deducciones','otro') DEFAULT 'otro' COMMENT 'Categoría para totales en reporte',
  `orden_reporte` int(11) DEFAULT 0 COMMENT 'Orden de aparición en el reporte',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: concepto_frecuencias - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `concepto_frecuencias`;
CREATE TABLE `concepto_frecuencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `concepto_id` int(11) NOT NULL,
  `frecuencia_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_concepto_frecuencia` (`concepto_id`,`frecuencia_id`),
  KEY `frecuencia_id` (`frecuencia_id`),
  CONSTRAINT `concepto_frecuencias_ibfk_1` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE,
  CONSTRAINT `concepto_frecuencias_ibfk_2` FOREIGN KEY (`frecuencia_id`) REFERENCES `frecuencias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: concepto_situaciones - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `concepto_situaciones`;
CREATE TABLE `concepto_situaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `concepto_id` int(11) NOT NULL,
  `situacion_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_concepto_situacion` (`concepto_id`,`situacion_id`),
  KEY `situacion_id` (`situacion_id`),
  CONSTRAINT `concepto_situaciones_ibfk_1` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE,
  CONSTRAINT `concepto_situaciones_ibfk_2` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: concepto_tipos_planilla - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `concepto_tipos_planilla`;
CREATE TABLE `concepto_tipos_planilla` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `concepto_id` int(11) NOT NULL,
  `tipo_planilla_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_concepto_tipo` (`concepto_id`,`tipo_planilla_id`),
  KEY `tipo_planilla_id` (`tipo_planilla_id`),
  CONSTRAINT `concepto_tipos_planilla_ibfk_1` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE,
  CONSTRAINT `concepto_tipos_planilla_ibfk_2` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: creditors - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `creditors`;
CREATE TABLE `creditors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(100) NOT NULL,
  `amount` double NOT NULL,
  `creditor_id` varchar(11) NOT NULL,
  `tipo` varchar(20) DEFAULT 'OTRO',
  `activo` tinyint(1) DEFAULT 1,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `employee_id` varchar(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- =====================================================
-- TABLA: creditors_table - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `creditors_table`;
CREATE TABLE `creditors_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `creditor_id` varchar(12) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `creditor_id` (`creditor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: deductions - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `deductions`;
CREATE TABLE `deductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(12) NOT NULL,
  `creditor_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_deductions_creditor` (`creditor_id`),
  CONSTRAINT `fk_deductions_creditor` FOREIGN KEY (`creditor_id`) REFERENCES `creditors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: employees - ESTRUCTURA REAL COMPLETA
-- =====================================================
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(15) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `birthdate` date NOT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `contact_info` varchar(100) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `photo` varchar(200) NOT NULL,
  `created_on` date NOT NULL,
  `id_partida` int(11) DEFAULT NULL,
  `id_cargo` int(11) DEFAULT NULL,
  `id_funcion` int(11) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `organigrama_path` varchar(255) DEFAULT NULL,
  `document_id` varchar(31) DEFAULT NULL,
  `situacion_id` int(11) DEFAULT NULL,
  `tipo_planilla_id` int(11) DEFAULT NULL,
  `cargo_id` int(11) DEFAULT NULL,
  `partida_id` int(11) DEFAULT NULL,
  `funcion_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `partida_fk` (`id_partida`),
  KEY `cargo_fk` (`id_cargo`),
  KEY `funcion_fk` (`id_funcion`),
  KEY `position_fk` (`position_id`),
  KEY `situacion_fk` (`situacion_id`),
  KEY `tipo_planilla_fk` (`tipo_planilla_id`),
  KEY `cargo_fk2` (`cargo_id`),
  KEY `partida_fk2` (`partida_id`),
  KEY `funcion_fk2` (`funcion_id`),
  CONSTRAINT `cargo_fk` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `cargo_fk2` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `funcion_fk` FOREIGN KEY (`id_funcion`) REFERENCES `funciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `funcion_fk2` FOREIGN KEY (`funcion_id`) REFERENCES `funciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `partida_fk` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `partida_fk2` FOREIGN KEY (`partida_id`) REFERENCES `partidas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `position_fk` FOREIGN KEY (`position_id`) REFERENCES `position` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `situacion_fk` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `tipo_planilla_fk` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- =====================================================
-- TABLA: frecuencias - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `frecuencias`;
CREATE TABLE `frecuencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `dias` int(11) DEFAULT 30,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: funciones - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `funciones`;
CREATE TABLE `funciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: menu_items - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `order_position` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: organigrama - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `organigrama`;
CREATE TABLE `organigrama` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `descripcion` varchar(200) NOT NULL,
  `sueldo` decimal(10,2) DEFAULT 0.00,
  `id_cargo` int(11) DEFAULT NULL,
  `id_partida` int(11) DEFAULT NULL,
  `id_funcion` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `id_cargo` (`id_cargo`),
  KEY `id_partida` (`id_partida`),
  KEY `id_funcion` (`id_funcion`),
  CONSTRAINT `organigrama_ibfk_1` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `organigrama_ibfk_2` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `organigrama_ibfk_3` FOREIGN KEY (`id_funcion`) REFERENCES `funciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: overtime - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `overtime`;
CREATE TABLE `overtime` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(15) NOT NULL,
  `hours` double NOT NULL,
  `rate` double NOT NULL,
  `date_overtime` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- =====================================================
-- TABLA: partidas - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `partidas`;
CREATE TABLE `partidas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: planilla_cabecera - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `planilla_cabecera`;
CREATE TABLE `planilla_cabecera` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `tipo_planilla_id` int(11) NOT NULL,
  `situacion_id` int(11) NOT NULL,
  `estado` enum('NUEVA','PROCESANDO','PROCESADA','CERRADA','ANULADA') NOT NULL DEFAULT 'NUEVA',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tipo_planilla_id` (`tipo_planilla_id`),
  KEY `situacion_id` (`situacion_id`),
  CONSTRAINT `planilla_cabecera_ibfk_1` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `planilla_cabecera_ibfk_2` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: planilla_detalle - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `planilla_detalle`;
CREATE TABLE `planilla_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `planilla_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `concepto_id` int(11) NOT NULL,
  `valor_calculado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_manual` decimal(10,2) DEFAULT NULL,
  `usar_manual` tinyint(1) NOT NULL DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_planilla_employee_concepto` (`planilla_id`,`employee_id`,`concepto_id`),
  KEY `employee_id` (`employee_id`),
  KEY `concepto_id` (`concepto_id`),
  CONSTRAINT `planilla_detalle_ibfk_1` FOREIGN KEY (`planilla_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `planilla_detalle_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `planilla_detalle_ibfk_3` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: posiciones - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `posiciones`;
CREATE TABLE `posiciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `descripcion` varchar(200) NOT NULL,
  `sueldo` decimal(10,2) DEFAULT 0.00,
  `id_cargo` int(11) DEFAULT NULL,
  `id_partida` int(11) DEFAULT NULL,
  `id_funcion` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `id_cargo` (`id_cargo`),
  KEY `id_partida` (`id_partida`),
  KEY `id_funcion` (`id_funcion`),
  CONSTRAINT `posiciones_ibfk_1` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `posiciones_ibfk_2` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `posiciones_ibfk_3` FOREIGN KEY (`id_funcion`) REFERENCES `funciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: position - ESTRUCTURA REAL (COMPATIBILIDAD)
-- =====================================================
DROP TABLE IF EXISTS `position`;
CREATE TABLE `position` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `rate` double NOT NULL,
  `created_on` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- =====================================================
-- TABLA: role_permissions - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_type` enum('create','read','update','delete','manage') NOT NULL DEFAULT 'read',
  `resource` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`,`permission_name`,`resource`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: roles - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: route_permissions - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `route_permissions`;
CREATE TABLE `route_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `route` varchar(255) NOT NULL,
  `permission_type` enum('create','read','update','delete','manage') NOT NULL DEFAULT 'read',
  `roles` json NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_route_permission` (`route`,`permission_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: schedules - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `schedule` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- =====================================================
-- TABLA: situaciones - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `situaciones`;
CREATE TABLE `situaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: tipos_planilla - ESTRUCTURA REAL
-- =====================================================
DROP TABLE IF EXISTS `tipos_planilla`;
CREATE TABLE `tipos_planilla` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- DATOS INICIALES BÁSICOS
-- =====================================================

-- Usuario admin con estructura real
INSERT INTO `admin` (`username`, `password`, `firstname`, `lastname`, `photo`, `created_on`, `role_id`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Sistema', '', CURDATE(), 1, 1);

-- Roles básicos
INSERT INTO `roles` (`name`, `description`) VALUES
('super_admin', 'Super Administrador con acceso total'),
('admin', 'Administrador del sistema'),
('user', 'Usuario básico del sistema');

-- Configuración de empresa con estructura real
INSERT INTO `companies` (`company_name`, `ruc`, `legal_representative`, `address`, `phone`, `email`, `currency_symbol`, `currency_code`) VALUES
('Mi Empresa S.A.', '12345678-9', 'Representante Legal', 'Dirección de la Empresa', '2234-5678', 'info@miempresa.com', 'Q', 'GTQ');

-- Horarios básicos
INSERT INTO `schedules` (`time_in`, `time_out`, `schedule`) VALUES
('08:00:00', '17:00:00', 'Normal'),
('07:00:00', '16:00:00', 'Matutino'),
('13:00:00', '22:00:00', 'Nocturno');

-- Estructura organizacional
INSERT INTO `cargos` (`codigo`, `nombre`, `descripcion`) VALUES
('DIR', 'Director', 'Director General'),
('GER', 'Gerente', 'Gerente de Área'),
('SUP', 'Supervisor', 'Supervisor de Operaciones'),
('TEC', 'Técnico', 'Técnico Especializado'),
('ADM', 'Administrativo', 'Personal Administrativo');

INSERT INTO `partidas` (`codigo`, `nombre`, `descripcion`) VALUES
('001', 'Sueldos Permanentes', 'Personal permanente'),
('002', 'Sueldos Temporales', 'Personal temporal'),
('003', 'Bonificaciones', 'Bonificaciones varias');

INSERT INTO `funciones` (`codigo`, `nombre`, `descripcion`) VALUES
('DIR', 'Dirección', 'Funciones de dirección'),
('ADM', 'Administración', 'Funciones administrativas'),
('OPE', 'Operaciones', 'Funciones operativas'),
('TEC', 'Técnicas', 'Funciones técnicas');

-- Posiciones básicas
INSERT INTO `position` (`description`, `rate`, `created_on`) VALUES
('Director General', 20000.00, CURDATE()),
('Gerente', 15000.00, CURDATE()),
('Supervisor', 8000.00, CURDATE()),
('Técnico', 5000.00, CURDATE()),
('Administrativo', 4000.00, CURDATE());

-- Tipos de planilla
INSERT INTO `tipos_planilla` (`descripcion`, `codigo`) VALUES
('Planilla Ordinaria', 'ORD'),
('Planilla Extraordinaria', 'EXT'),
('Aguinaldo', 'AGU'),
('Vacaciones', 'VAC');

-- Frecuencias
INSERT INTO `frecuencias` (`descripcion`, `codigo`, `dias`) VALUES
('Mensual', 'MENS', 30),
('Quincenal', 'QUIN', 15),
('Semanal', 'SEM', 7),
('Anual', 'ANUAL', 365);

-- Situaciones laborales
INSERT INTO `situaciones` (`descripcion`, `codigo`) VALUES
('Activo', 'ACT'),
('Vacaciones', 'VAC'),
('Permiso', 'PER'),
('Suspendido', 'SUS');

-- Conceptos básicos
INSERT INTO `concepto` (`concepto`, `descripcion`, `tipo_concepto`, `unidad`, `valor_fijo`) VALUES
('01', 'Sueldo Base', 'ingreso', 'calculado', 0.00),
('02', 'Bonificación', 'ingreso', 'fijo', 250.00),
('10', 'IGSS', 'deduccion', 'calculado', 0.00),
('11', 'ISR', 'deduccion', 'calculado', 0.00);

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

COMMIT;

-- =====================================================
-- INFORMACIÓN DE INSTALACIÓN
-- =====================================================
-- 
-- ✅ BASE DE DATOS: planilla_sistema
-- ✅ ESQUEMA: Extraído directamente de BD actual
-- ✅ TABLAS: 29 con estructura REAL
-- ✅ FOREIGN KEYS: Todas las relaciones reales
-- ✅ DATOS INICIALES: Básicos para funcionamiento
-- 
-- TABLA ADMIN CORREGIDA:
-- ✅ Incluye: role_id, status (campos faltantes)
-- ✅ Varchar sizes reales: username(30), password(60)
-- 
-- TABLA EMPLOYEES COMPLETA:
-- ✅ Todos los campos organizacionales
-- ✅ Both id_partida/partida_id (compatibilidad)
-- ✅ Foreign keys reales implementadas
-- 
-- CREDENCIALES:
-- Usuario: admin
-- Contraseña: password
-- 
-- ¡ESQUEMA REAL IMPLEMENTADO CORRECTAMENTE!
-- =====================================================