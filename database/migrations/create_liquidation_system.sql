-- ==================================================
-- MIGRACIÓN: SISTEMA DE LIQUIDACIONES
-- Descripción: Estructura completa para planillas de liquidación
-- Versión: 1.0
-- Fecha: 20 de Septiembre, 2025
-- ==================================================

-- 1. VERIFICAR TIPO DE PLANILLA DE LIQUIDACIÓN (YA EXISTE)
-- Tipo LIQUIDACION ya existe en BD (id=10)

-- 2. TABLA DE TERMINACIONES DE EMPLEADOS
CREATE TABLE IF NOT EXISTS `employee_terminations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `termination_date` date NOT NULL,
  `termination_type` enum('DESPIDO_CON_CAUSA','DESPIDO_SIN_CAUSA','RENUNCIA','MUTUO_ACUERDO') NOT NULL,
  `notice_period_days` int(11) DEFAULT 30,
  `reason` text DEFAULT NULL,
  `liquidation_payroll_id` int(11) DEFAULT NULL,
  `years_worked` decimal(6,2) NOT NULL DEFAULT 0.00,
  `months_worked_current_year` int(11) DEFAULT 0,
  `accumulated_vacations` decimal(8,2) DEFAULT 0.00,
  `status` enum('PENDIENTE','CALCULADA','PROCESADA','PAGADA') DEFAULT 'PENDIENTE',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_termination_employee` (`employee_id`),
  KEY `fk_termination_payroll` (`liquidation_payroll_id`),
  KEY `fk_termination_approved_by` (`approved_by`),
  CONSTRAINT `fk_termination_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_termination_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. TABLA DE CÁLCULOS DE LIQUIDACIÓN
CREATE TABLE IF NOT EXISTS `liquidation_calculations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `termination_id` int(11) NOT NULL,
  `concept_code` varchar(50) NOT NULL,
  `concept_description` varchar(200) NOT NULL,
  `calculation_base` decimal(12,2) NOT NULL DEFAULT 0.00,
  `years_worked` decimal(6,2) NOT NULL DEFAULT 0.00,
  `weeks_entitled` decimal(6,2) NOT NULL DEFAULT 0.00,
  `days_entitled` decimal(6,2) NOT NULL DEFAULT 0.00,
  `calculated_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `formula_used` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_liquidation_termination` (`termination_id`),
  CONSTRAINT `fk_liquidation_termination` FOREIGN KEY (`termination_id`) REFERENCES `employee_terminations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. CAMPOS DE TERMINACIÓN EN EMPLEADOS (YA EXISTEN)
-- Los campos fecha_terminacion, motivo_terminacion y estado_laboral ya están en la tabla employees

-- 5. AGREGAR 'LIQUIDACION' A categoria_reporte ENUM
ALTER TABLE `concepto`
MODIFY COLUMN `categoria_reporte` enum('seguro_social','seguro_educativo','impuesto_renta','otras_deducciones','liquidacion','otro') DEFAULT 'otro';

-- 6. CONCEPTOS DE LIQUIDACIÓN BÁSICOS
INSERT INTO `concepto` (`concepto`, `descripcion`, `tipo_concepto`, `formula`, `categoria_reporte`) VALUES
('LIQ001', 'Prima de Antigüedad', 'ASIGNACION', 'SUELDO_SEMANAL * ANOS_TRABAJADOS', 'liquidacion'),
('LIQ002', 'Indemnización por Despido', 'ASIGNACION', 'LIQUIDACION_INDEMNIZACION(ANOS_TRABAJADOS, SUELDO_SEMANAL)', 'liquidacion'),
('LIQ003', 'Preaviso', 'ASIGNACION', 'SUELDO_MENSUAL', 'liquidacion'),
('LIQ004', 'Vacaciones Proporcionales', 'ASIGNACION', 'VACACIONES_PROPORCIONALES()', 'liquidacion'),
('LIQ005', 'XIII Mes Proporcional', 'ASIGNACION', 'XIII_MES_PROPORCIONAL()', 'liquidacion'),
('LIQ006', 'Salario Pendiente', 'ASIGNACION', 'SALARIO_PENDIENTE()', 'liquidacion'),
('LIQ007', 'Descuento Préstamos', 'DEDUCCION', 'DESCUENTO_PRESTAMOS()', 'liquidacion'),
('LIQ008', 'Descuento Anticipos', 'DEDUCCION', 'DESCUENTO_ANTICIPOS()', 'liquidacion')
ON DUPLICATE KEY UPDATE
descripcion = VALUES(descripcion),
formula = VALUES(formula),
categoria_reporte = VALUES(categoria_reporte);

-- 7. FRECUENCIA ESPECIAL PARA LIQUIDACIÓN
INSERT INTO `frecuencias` (`codigo`, `nombre`, `descripcion`)
VALUES ('LIQUIDACION', 'Liquidación', 'Frecuencia especial para planillas de liquidación')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion);

-- 8. TABLA DE HISTORIAL DE LIQUIDACIONES (para auditoría)
CREATE TABLE IF NOT EXISTS `liquidation_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `termination_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_history_termination` (`termination_id`),
  KEY `fk_history_user` (`user_id`),
  CONSTRAINT `fk_history_termination` FOREIGN KEY (`termination_id`) REFERENCES `employee_terminations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `admin` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. TABLA DE CONFIGURACIÓN DE LIQUIDACIÓN
CREATE TABLE IF NOT EXISTS `liquidation_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parameter_name` varchar(100) NOT NULL,
  `parameter_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `data_type` enum('STRING','NUMBER','BOOLEAN','JSON') DEFAULT 'STRING',
  `category` varchar(50) DEFAULT 'GENERAL',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_parameter` (`parameter_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 10. CONFIGURACIÓN INICIAL DE LIQUIDACIÓN
INSERT INTO `liquidation_config` (`parameter_name`, `parameter_value`, `description`, `data_type`, `category`) VALUES
('PRIMA_ANTIGUEDAD_WEEKS', '1', 'Semanas de salario por año trabajado para prima de antigüedad', 'NUMBER', 'CALCULO'),
('INDEMNIZACION_WEEKS_FIRST_10', '3.4', 'Semanas de salario por año para los primeros 10 años de indemnización', 'NUMBER', 'CALCULO'),
('INDEMNIZACION_WEEKS_AFTER_10', '1', 'Semanas de salario por año después de 10 años para indemnización', 'NUMBER', 'CALCULO'),
('PREAVISO_DAYS', '30', 'Días de preaviso estándar', 'NUMBER', 'CALCULO'),
('VACATION_DAYS_PER_YEAR', '30', 'Días de vacaciones por año trabajado', 'NUMBER', 'CALCULO'),
('AUTO_CALCULATE', 'true', 'Calcular automáticamente al crear liquidación', 'BOOLEAN', 'SISTEMA'),
('REQUIRE_APPROVAL', 'true', 'Requiere aprobación antes de generar planilla', 'BOOLEAN', 'SISTEMA')
ON DUPLICATE KEY UPDATE
parameter_value = VALUES(parameter_value),
description = VALUES(description);

-- 11. ÍNDICES ADICIONALES PARA PERFORMANCE
CREATE INDEX idx_employees_fecha_ingreso ON employees(fecha_ingreso);
CREATE INDEX idx_employees_estado_laboral ON employees(estado_laboral);
CREATE INDEX idx_terminations_date ON employee_terminations(termination_date);
CREATE INDEX idx_terminations_status ON employee_terminations(status);
CREATE INDEX idx_calculations_concept ON liquidation_calculations(concept_code);

-- ==================================================
-- VERIFICACIÓN DE MIGRACIÓN
-- ==================================================

-- Verificar que las tablas fueron creadas
SELECT
  TABLE_NAME,
  TABLE_ROWS,
  CREATE_TIME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
    'employee_terminations',
    'liquidation_calculations',
    'liquidation_history',
    'liquidation_config'
  );

-- Verificar conceptos de liquidación
SELECT concepto, descripcion, tipo_concepto, categoria_reporte
FROM concepto
WHERE categoria_reporte = 'LIQUIDACION';

-- Verificar tipo de planilla
SELECT id, descripcion, codigo
FROM tipos_planilla
WHERE codigo = 'LIQUIDACION';

-- ==================================================
-- NOTAS DE IMPLEMENTACIÓN
-- ==================================================
/*
1. Esta migración crea toda la estructura necesaria para el sistema de liquidaciones
2. Los conceptos incluyen fórmulas que deben ser implementadas en PlanillaConceptCalculator
3. El sistema incluye auditoría completa y configuración flexible
4. Compatible con la estructura MVC existente
5. Incluye validaciones y constraints de integridad referencial
6. Preparado para integración con el sistema de acumulados existente
*/