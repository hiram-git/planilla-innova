-- =====================================================
-- TABLAS PARA SISTEMA DE PLANILLAS - MVC
-- Version: 2.0.0
-- =====================================================

-- =====================================================
-- TABLA: posiciones (actualización con sueldo)
-- =====================================================

ALTER TABLE `position` 
ADD COLUMN `sueldo` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Sueldo base de la posición';

-- =====================================================
-- TABLA: conceptos (conceptos de nómina)
-- =====================================================

CREATE TABLE `concepto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `formula` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fórmula de cálculo',
  `tipo` enum('INGRESO','DEDUCCION') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INGRESO',
  `monto_cero` tinyint(1) DEFAULT 0 COMMENT 'Si permite monto cero',
  `activo` tinyint(1) DEFAULT 1,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: creditors (acreedores)
-- =====================================================

CREATE TABLE `creditors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_info` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('BANCO','COOPERATIVA','PRESTAMISTA','GOBIERNO','OTRO') DEFAULT 'OTRO',
  `activo` tinyint(1) DEFAULT 1,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: deductions (deducciones por empleado)
-- =====================================================

CREATE TABLE `deductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Employee ID, no el internal ID',
  `creditor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_deductions_creditor` (`creditor_id`),
  KEY `idx_deductions_employee` (`employee_id`),
  KEY `idx_deductions_active` (`activo`),
  CONSTRAINT `fk_deductions_creditor` FOREIGN KEY (`creditor_id`) REFERENCES `creditors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: planilla_cabecera (cabeceras de planillas)
-- =====================================================

CREATE TABLE `planilla_cabecera` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `periodo_inicio` date NOT NULL,
  `periodo_fin` date NOT NULL,
  `estado` enum('PENDIENTE','PROCESADA','CERRADA','ANULADA') DEFAULT 'PENDIENTE',
  `total_ingresos` decimal(12,2) DEFAULT 0.00,
  `total_deducciones` decimal(12,2) DEFAULT 0.00,
  `total_neto` decimal(12,2) DEFAULT 0.00,
  `usuario_creacion` int(11) DEFAULT NULL,
  `fecha_procesamiento` timestamp NULL DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_planilla_usuario` (`usuario_creacion`),
  KEY `idx_planilla_fecha` (`fecha`),
  KEY `idx_planilla_estado` (`estado`),
  CONSTRAINT `fk_planilla_usuario` FOREIGN KEY (`usuario_creacion`) REFERENCES `admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: planilla_detalle (detalles de planillas por empleado)
-- =====================================================

CREATE TABLE `planilla_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cabecera_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `salario_base` decimal(10,2) DEFAULT 0.00,
  `horas_trabajadas` decimal(5,2) DEFAULT 0.00,
  `total_ingresos` decimal(10,2) DEFAULT 0.00,
  `total_deducciones` decimal(10,2) DEFAULT 0.00,
  `salario_neto` decimal(10,2) DEFAULT 0.00,
  `observaciones` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cabecera_employee` (`cabecera_id`, `employee_id`),
  KEY `fk_detalle_employee` (`employee_id`),
  CONSTRAINT `fk_detalle_cabecera` FOREIGN KEY (`cabecera_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: planilla_conceptos (conceptos aplicados por empleado en planilla)
-- =====================================================

CREATE TABLE `planilla_conceptos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `detalle_id` int(11) NOT NULL,
  `concepto_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_detalle_concepto` (`detalle_id`, `concepto_id`),
  KEY `fk_planilla_concepto` (`concepto_id`),
  CONSTRAINT `fk_planilla_detalle` FOREIGN KEY (`detalle_id`) REFERENCES `planilla_detalle` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_planilla_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: nomina_transacciones (transacciones individuales)
-- =====================================================

CREATE TABLE `nomina_transacciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `concepto_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tipo` enum('INGRESO','DEDUCCION') NOT NULL,
  `organigrama_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_transaccion` timestamp NOT NULL DEFAULT current_timestamp(),
  `observaciones` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_creacion` int(11) DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_transaccion_employee` (`employee_id`),
  KEY `fk_transaccion_concepto` (`concepto_id`),
  KEY `fk_transaccion_usuario` (`usuario_creacion`),
  KEY `idx_transaccion_fecha` (`fecha_transaccion`),
  CONSTRAINT `fk_transaccion_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_transaccion_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_transaccion_usuario` FOREIGN KEY (`usuario_creacion`) REFERENCES `admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTAS PARA PLANILLAS
-- =====================================================

-- Vista para planilla cabecera con estadísticas
CREATE VIEW `view_planilla_cabecera` AS
SELECT 
    pc.id,
    pc.descripcion,
    pc.fecha,
    pc.periodo_inicio,
    pc.periodo_fin,
    pc.estado,
    pc.total_ingresos,
    pc.total_deducciones,
    pc.total_neto,
    pc.observaciones,
    a.firstname as usuario_nombre,
    a.lastname as usuario_apellido,
    COUNT(pd.id) as total_empleados,
    pc.created_on
FROM planilla_cabecera pc
LEFT JOIN admin a ON pc.usuario_creacion = a.id
LEFT JOIN planilla_detalle pd ON pc.id = pd.cabecera_id
GROUP BY pc.id;

-- Vista para detalle de planilla con información completa
CREATE VIEW `view_planilla_detalle` AS
SELECT 
    pd.id,
    pd.cabecera_id,
    pc.descripcion as planilla_descripcion,
    pc.fecha as planilla_fecha,
    pd.employee_id,
    e.employee_id as employee_code,
    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
    e.firstname,
    e.lastname,
    p.descripcion as position_name,
    pd.salario_base,
    pd.horas_trabajadas,
    pd.total_ingresos,
    pd.total_deducciones,
    pd.salario_neto,
    pd.observaciones,
    pd.created_on
FROM planilla_detalle pd
INNER JOIN planilla_cabecera pc ON pd.cabecera_id = pc.id
INNER JOIN employees e ON pd.employee_id = e.id
LEFT JOIN position p ON e.position_id = p.id;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar conceptos básicos
INSERT INTO `concepto` (`descripcion`, `formula`, `tipo`, `monto_cero`) VALUES
('SALARIO_BASE', 'SALARIO', 'INGRESO', 0),
('HORAS_EXTRA', 'SALARIO / 240 * HORAS * 1.5', 'INGRESO', 1),
('BONIFICACION', '250', 'INGRESO', 0),
('VACACIONES', 'SALARIO / 12', 'INGRESO', 1),
('AGUINALDO', 'SALARIO / 12', 'INGRESO', 1),
('IGSS_LABORAL', 'SALARIO * 0.0483', 'DEDUCCION', 0),
('IRTRA', 'SALARIO * 0.01', 'DEDUCCION', 0),
('PRESTAMO_PERSONAL', 'ACREEDOR(FICHA, 1)', 'DEDUCCION', 1),
('ANTICIPO', 'monto', 'DEDUCCION', 1);

-- Insertar acreedores básicos
INSERT INTO `creditors` (`name`, `tipo`) VALUES
('Banco Industrial', 'BANCO'),
('Cooperativa Universitaria', 'COOPERATIVA'),
('IGSS', 'GOBIERNO'),
('SAT', 'GOBIERNO'),
('Préstamos Personales', 'PRESTAMISTA');

-- Actualizar sueldos en posiciones existentes
UPDATE `position` SET `sueldo` = 15000.00 WHERE `descripcion` = 'Director General';
UPDATE `position` SET `sueldo` = 12000.00 WHERE `descripcion` = 'Gerente de Recursos Humanos';
UPDATE `position` SET `sueldo` = 10000.00 WHERE `descripcion` = 'Contador General';
UPDATE `position` SET `sueldo` = 8000.00 WHERE `descripcion` = 'Analista de Sistemas';
UPDATE `position` SET `sueldo` = 5000.00 WHERE `descripcion` = 'Asistente Administrativo';
UPDATE `position` SET `sueldo` = 4000.00 WHERE `descripcion` = 'Operario';
UPDATE `position` SET `sueldo` = 6000.00 WHERE `descripcion` = 'Supervisor';
UPDATE `position` SET `sueldo` = 7000.00 WHERE `descripcion` = 'Coordinador';

-- =====================================================
-- TRIGGERS PARA PLANILLAS
-- =====================================================

DELIMITER $$

-- Trigger para actualizar totales en planilla_cabecera
CREATE TRIGGER `update_planilla_totales` 
    AFTER INSERT ON `planilla_detalle` 
    FOR EACH ROW 
BEGIN
    UPDATE planilla_cabecera 
    SET 
        total_ingresos = (
            SELECT COALESCE(SUM(total_ingresos), 0) 
            FROM planilla_detalle 
            WHERE cabecera_id = NEW.cabecera_id
        ),
        total_deducciones = (
            SELECT COALESCE(SUM(total_deducciones), 0) 
            FROM planilla_detalle 
            WHERE cabecera_id = NEW.cabecera_id
        ),
        total_neto = (
            SELECT COALESCE(SUM(salario_neto), 0) 
            FROM planilla_detalle 
            WHERE cabecera_id = NEW.cabecera_id
        )
    WHERE id = NEW.cabecera_id;
END$$

-- Trigger para actualizar totales cuando se actualiza detalle
CREATE TRIGGER `update_planilla_totales_update` 
    AFTER UPDATE ON `planilla_detalle` 
    FOR EACH ROW 
BEGIN
    UPDATE planilla_cabecera 
    SET 
        total_ingresos = (
            SELECT COALESCE(SUM(total_ingresos), 0) 
            FROM planilla_detalle 
            WHERE cabecera_id = NEW.cabecera_id
        ),
        total_deducciones = (
            SELECT COALESCE(SUM(total_deducciones), 0) 
            FROM planilla_detalle 
            WHERE cabecera_id = NEW.cabecera_id
        ),
        total_neto = (
            SELECT COALESCE(SUM(salario_neto), 0) 
            FROM planilla_detalle 
            WHERE cabecera_id = NEW.cabecera_id
        )
    WHERE id = NEW.cabecera_id;
END$$

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

CREATE INDEX `idx_concepto_tipo` ON `concepto` (`tipo`);
CREATE INDEX `idx_concepto_activo` ON `concepto` (`activo`);
CREATE INDEX `idx_creditors_tipo` ON `creditors` (`tipo`);
CREATE INDEX `idx_planilla_detalle_salario` ON `planilla_detalle` (`salario_neto`);
CREATE INDEX `idx_transacciones_tipo` ON `nomina_transacciones` (`tipo`);