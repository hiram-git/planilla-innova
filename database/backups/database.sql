-- =====================================================
-- Planilla Simple - Base de Datos
-- Version: 2.0.0
-- Arquitectura: MVC
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- CONFIGURACIÓN INICIAL
-- =====================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- TABLA: admin
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
-- TABLA: schedules (horarios)
-- =====================================================

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: position (posiciones)
-- =====================================================

CREATE TABLE `position` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cargos
-- =====================================================

CREATE TABLE `cargos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: partidas
-- =====================================================

CREATE TABLE `partidas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partida` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: funciones
-- =====================================================

CREATE TABLE `funciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: employees (empleados)
-- =====================================================

CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `contact_info` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `cargo_id` int(11) DEFAULT NULL,
  `partida_id` int(11) DEFAULT NULL,
  `funcion_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organigrama_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `fk_employees_position` (`position_id`),
  KEY `fk_employees_cargo` (`cargo_id`),
  KEY `fk_employees_partida` (`partida_id`),
  KEY `fk_employees_funcion` (`funcion_id`),
  KEY `fk_employees_schedule` (`schedule_id`),
  CONSTRAINT `fk_employees_cargo` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_funcion` FOREIGN KEY (`funcion_id`) REFERENCES `funciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_partida` FOREIGN KEY (`partida_id`) REFERENCES `partidas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_position` FOREIGN KEY (`position_id`) REFERENCES `position` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: attendance (asistencia)
-- =====================================================

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `num_hr` decimal(4,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 0 COMMENT '0=tarde, 1=a tiempo',
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_date` (`employee_id`, `date`),
  KEY `idx_date` (`date`),
  KEY `idx_employee_date` (`employee_id`, `date`),
  CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar usuario administrador por defecto
INSERT INTO `admin` (`username`, `password`, `firstname`, `lastname`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Sistema');
-- Contraseña: password

-- Insertar horarios por defecto
INSERT INTO `schedules` (`time_in`, `time_out`) VALUES
('08:00:00', '17:00:00'),
('07:00:00', '15:00:00'),
('09:00:00', '18:00:00'),
('06:00:00', '14:00:00');

-- Insertar posiciones por defecto
INSERT INTO `position` (`descripcion`) VALUES
('Director General'),
('Gerente de Recursos Humanos'),
('Contador General'),
('Analista de Sistemas'),
('Asistente Administrativo'),
('Operario'),
('Supervisor'),
('Coordinador');

-- Insertar cargos por defecto
INSERT INTO `cargos` (`descripcion`) VALUES
('Ejecutivo'),
('Profesional'),
('Técnico'),
('Administrativo'),
('Operativo'),
('Supervisor'),
('Coordinador'),
('Especialista');

-- Insertar partidas por defecto
INSERT INTO `partidas` (`partida`, `descripcion`) VALUES
('001-001', 'Sueldos y Salarios - Ejecutivos'),
('001-002', 'Sueldos y Salarios - Profesionales'),
('001-003', 'Sueldos y Salarios - Técnicos'),
('001-004', 'Sueldos y Salarios - Administrativos'),
('001-005', 'Sueldos y Salarios - Operativos'),
('002-001', 'Bonificaciones'),
('002-002', 'Compensaciones'),
('003-001', 'Prestaciones Sociales');

-- Insertar funciones por defecto
INSERT INTO `funciones` (`descripcion`) VALUES
('Dirección y Administración'),
('Recursos Humanos'),
('Contabilidad y Finanzas'),
('Tecnología de la Información'),
('Operaciones'),
('Logística'),
('Ventas y Marketing'),
('Atención al Cliente');

-- Insertar empleado de ejemplo
INSERT INTO `employees` (`employee_id`, `firstname`, `lastname`, `address`, `birthdate`, `fecha_ingreso`, `contact_info`, `gender`, `position_id`, `cargo_id`, `partida_id`, `funcion_id`, `schedule_id`, `document_id`) VALUES
('EMP001', 'Juan Carlos', 'García López', 'Ciudad de Guatemala', '1985-03-15', '2024-01-01', 'juan.garcia@empresa.com', 'M', 1, 1, 1, 1, 1, '1234567890101');

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista para empleados con información completa
CREATE VIEW `view_employees_full` AS
SELECT 
    e.id,
    e.employee_id,
    e.firstname,
    e.lastname,
    CONCAT(e.firstname, ' ', e.lastname) as full_name,
    e.address,
    e.birthdate,
    e.fecha_ingreso,
    e.contact_info,
    e.gender,
    e.document_id,
    p.descripcion as position_name,
    c.descripcion as cargo_name,
    part.partida as partida_code,
    part.descripcion as partida_name,
    f.descripcion as funcion_name,
    s.time_in,
    s.time_out,
    CONCAT(TIME_FORMAT(s.time_in, '%H:%i'), ' - ', TIME_FORMAT(s.time_out, '%H:%i')) as horario,
    e.created_on
FROM employees e
LEFT JOIN position p ON e.position_id = p.id
LEFT JOIN cargos c ON e.cargo_id = c.id
LEFT JOIN partidas part ON e.partida_id = part.id
LEFT JOIN funciones f ON e.funcion_id = f.id
LEFT JOIN schedules s ON e.schedule_id = s.id;

-- Vista para asistencia con información de empleados
CREATE VIEW `view_attendance_full` AS
SELECT 
    a.id,
    a.employee_id,
    e.employee_id as employee_code,
    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
    e.firstname,
    e.lastname,
    a.date,
    a.time_in,
    a.time_out,
    a.num_hr,
    a.status,
    CASE 
        WHEN a.status = 1 THEN 'A tiempo'
        ELSE 'Tarde'
    END as status_text,
    p.descripcion as position_name,
    s.time_in as schedule_in,
    s.time_out as schedule_out,
    a.created_on
FROM attendance a
INNER JOIN employees e ON a.employee_id = e.id
LEFT JOIN position p ON e.position_id = p.id
LEFT JOIN schedules s ON e.schedule_id = s.id;

-- =====================================================
-- ÍNDICES PARA OPTIMIZACIÓN
-- =====================================================

-- Índices adicionales para mejorar performance
CREATE INDEX `idx_employees_name` ON `employees` (`lastname`, `firstname`);
CREATE INDEX `idx_employees_position` ON `employees` (`position_id`);
CREATE INDEX `idx_employees_schedule` ON `employees` (`schedule_id`);
CREATE INDEX `idx_attendance_status` ON `attendance` (`status`);
CREATE INDEX `idx_attendance_time_in` ON `attendance` (`time_in`);

-- =====================================================
-- TRIGGERS PARA AUDITORÍA
-- =====================================================

-- Trigger para actualizar timestamp en employees
DELIMITER $$
CREATE TRIGGER `employees_updated_at` 
    BEFORE UPDATE ON `employees` 
    FOR EACH ROW 
BEGIN
    SET NEW.created_on = CURRENT_TIMESTAMP;
END$$
DELIMITER ;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

DELIMITER $$

-- Procedimiento para obtener estadísticas de asistencia
CREATE PROCEDURE `GetAttendanceStats`(IN fecha_inicio DATE, IN fecha_fin DATE)
BEGIN
    SELECT 
        COUNT(*) as total_registros,
        COUNT(DISTINCT employee_id) as empleados_unicos,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as a_tiempo,
        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as tarde,
        AVG(num_hr) as promedio_horas,
        SUM(num_hr) as total_horas
    FROM attendance 
    WHERE date BETWEEN fecha_inicio AND fecha_fin;
END$$

-- Procedimiento para calcular horas trabajadas
CREATE PROCEDURE `CalculateWorkedHours`(IN attendance_id INT)
BEGIN
    DECLARE v_time_in DATETIME;
    DECLARE v_time_out DATETIME;
    DECLARE v_schedule_in TIME;
    DECLARE v_schedule_out TIME;
    DECLARE v_worked_hours DECIMAL(4,2);
    DECLARE v_employee_id INT;
    
    -- Obtener datos de asistencia
    SELECT employee_id, time_in, time_out INTO v_employee_id, v_time_in, v_time_out
    FROM attendance WHERE id = attendance_id;
    
    -- Obtener horario del empleado
    SELECT s.time_in, s.time_out INTO v_schedule_in, v_schedule_out
    FROM employees e 
    JOIN schedules s ON e.schedule_id = s.id 
    WHERE e.id = v_employee_id;
    
    -- Calcular horas si ambos tiempos están presentes
    IF v_time_in IS NOT NULL AND v_time_out IS NOT NULL THEN
        SET v_worked_hours = TIMESTAMPDIFF(MINUTE, v_time_in, v_time_out) / 60.0;
        
        -- Descontar hora de almuerzo si trabajó más de 4 horas
        IF v_worked_hours > 4 THEN
            SET v_worked_hours = v_worked_hours - 1;
        END IF;
        
        -- Actualizar el registro
        UPDATE attendance SET num_hr = v_worked_hours WHERE id = attendance_id;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- FINALIZACIÓN
-- =====================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- =====================================================
-- INFORMACIÓN DE LA BASE DE DATOS
-- =====================================================

-- Versión: 2.0.0
-- Fecha: 2024
-- Sistema: Planilla Simple - MVC
-- Tablas: 8 principales + 2 vistas
-- Triggers: 1
-- Procedimientos: 2
-- Características: UTF8MB4, Foreign Keys, Índices optimizados