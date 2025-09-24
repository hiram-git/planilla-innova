-- ==================================================
-- MIGRACIÓN: SISTEMA DE VACACIONES PANAMÁ
-- Descripción: Estructura completa para gestión de vacaciones
-- Basado en: Sistema de liquidaciones existente
-- Versión: 1.0
-- Fecha: 21 de Septiembre, 2025
-- ==================================================

-- 1. TABLA DE SOLICITUDES DE VACACIONES
-- Basada en employee_terminations pero adaptada para vacaciones
CREATE TABLE IF NOT EXISTS `vacation_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `request_date` date NOT NULL DEFAULT (CURDATE()),
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `business_days` int(11) NOT NULL COMMENT 'Días hábiles solicitados',
  `vacation_type` enum('ANNUAL','ACCUMULATED','COMPENSATION','PROPORTIONAL') NOT NULL DEFAULT 'ANNUAL',
  `status` enum('PENDING','APPROVED','REJECTED','TAKEN','CANCELLED') DEFAULT 'PENDING',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  -- Campos de cálculo (reutilizando lógica liquidaciones)
  `years_worked` decimal(6,2) NOT NULL DEFAULT 0.00,
  `months_worked_current_year` int(11) DEFAULT 0,
  `accumulated_days` decimal(8,2) DEFAULT 0.00 COMMENT 'Días acumulados disponibles',
  `available_days` decimal(8,2) DEFAULT 0.00 COMMENT 'Días disponibles antes solicitud',
  `remaining_days` decimal(8,2) DEFAULT 0.00 COMMENT 'Días restantes después solicitud',
  -- Campos financieros para compensación
  `daily_salary` decimal(12,2) DEFAULT 0.00 COMMENT 'Salario diario para compensación',
  `compensation_amount` decimal(12,2) DEFAULT 0.00 COMMENT 'Monto compensación si aplica',
  -- Metadatos
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_vacation_employee` (`employee_id`),
  KEY `fk_vacation_approver` (`approved_by`),
  KEY `idx_vacation_dates` (`start_date`, `end_date`),
  KEY `idx_vacation_status` (`status`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA DE CÁLCULOS DE VACACIONES
-- Basada en liquidation_calculations para mantener consistencia
CREATE TABLE IF NOT EXISTS `vacation_calculations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vacation_request_id` int(11) NOT NULL,
  `calculation_type` varchar(50) NOT NULL COMMENT 'DAYS_EARNED, DAYS_TAKEN, BALANCE, COMPENSATION',
  `calculation_base` decimal(12,2) NOT NULL COMMENT 'Base del cálculo (salario, días, etc)',
  `days_calculated` decimal(6,2) NOT NULL,
  `amount_calculated` decimal(12,2) NOT NULL DEFAULT 0.00,
  `formula_used` text COMMENT 'Fórmula aplicada para auditoría',
  `period_start` date DEFAULT NULL COMMENT 'Inicio período de cálculo',
  `period_end` date DEFAULT NULL COMMENT 'Fin período de cálculo',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_calc_vacation_request` (`vacation_request_id`),
  KEY `idx_calc_type` (`calculation_type`),
  FOREIGN KEY (`vacation_request_id`) REFERENCES `vacation_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA DE CALENDARIO EMPRESARIAL
-- Para manejar días feriados y no laborables
CREATE TABLE IF NOT EXISTS `vacation_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `day_type` enum('HOLIDAY','NON_WORKING','COMPANY_CLOSURE') NOT NULL,
  `description` varchar(255) NOT NULL COMMENT 'Nombre del feriado o evento',
  `is_recurring` tinyint(1) DEFAULT 0 COMMENT 'Si se repite anualmente',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_calendar_date` (`date`),
  KEY `idx_calendar_date` (`date`),
  KEY `idx_calendar_type` (`day_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA DE POLÍTICAS DE VACACIONES
-- Para configuraciones específicas por empresa/departamento
CREATE TABLE IF NOT EXISTS `vacation_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_name` varchar(100) NOT NULL,
  `description` text,
  `days_per_year` int(11) NOT NULL DEFAULT 30 COMMENT 'Días por año según legislación',
  `minimum_months` int(11) NOT NULL DEFAULT 11 COMMENT 'Meses mínimos para derecho completo',
  `max_accumulated_days` int(11) DEFAULT 60 COMMENT 'Máximo días acumulables',
  `advance_notice_days` int(11) DEFAULT 15 COMMENT 'Días de anticipación requeridos',
  `min_consecutive_days` int(11) DEFAULT 15 COMMENT 'Mínimo días consecutivos legislación',
  `allow_fractional_days` tinyint(1) DEFAULT 0 COMMENT 'Permitir días fraccionados',
  `compensation_allowed` tinyint(1) DEFAULT 1 COMMENT 'Permitir compensación monetaria',
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'Política por defecto',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_policy_default` (`is_default`),
  KEY `idx_policy_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABLA DE BALANCE DE VACACIONES POR EMPLEADO
-- Para tracking automático de balances
CREATE TABLE IF NOT EXISTS `vacation_balances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `days_earned` decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT 'Días ganados en el año',
  `days_taken` decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT 'Días tomados en el año',
  `days_compensated` decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT 'Días compensados monetariamente',
  `days_accumulated_previous` decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT 'Días acumulados año anterior',
  `current_balance` decimal(6,2) GENERATED ALWAYS AS
    (`days_earned` + `days_accumulated_previous` - `days_taken` - `days_compensated`) STORED,
  `last_calculation_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_year` (`employee_id`, `year`),
  KEY `idx_balance_year` (`year`),
  KEY `idx_balance_employee` (`employee_id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================
-- INSERTAR DATOS INICIALES
-- ==================================================

-- 1. POLÍTICA POR DEFECTO LEGISLACIÓN PANAMEÑA
INSERT INTO `vacation_policies`
(`policy_name`, `description`, `days_per_year`, `minimum_months`, `max_accumulated_days`,
 `advance_notice_days`, `min_consecutive_days`, `allow_fractional_days`, `compensation_allowed`, `is_default`)
VALUES
('Legislación Panamá Standard',
 'Política estándar según Código de Trabajo de Panamá: 30 días por cada 11 meses trabajados',
 30, 11, 60, 15, 15, 0, 1, 1);

-- 2. FERIADOS NACIONALES PANAMÁ 2025 (BÁSICOS)
INSERT INTO `vacation_calendar` (`date`, `day_type`, `description`, `is_recurring`) VALUES
('2025-01-01', 'HOLIDAY', 'Año Nuevo', 1),
('2025-01-09', 'HOLIDAY', 'Día de los Mártires', 1),
('2025-05-01', 'HOLIDAY', 'Día del Trabajador', 1),
('2025-11-03', 'HOLIDAY', 'Separación de Panamá de Colombia', 1),
('2025-11-04', 'HOLIDAY', 'Día de la Bandera', 1),
('2025-11-10', 'HOLIDAY', 'Primer Grito de Independencia de Villa de Los Santos', 1),
('2025-11-28', 'HOLIDAY', 'Independencia de Panamá de España', 1),
('2025-12-08', 'HOLIDAY', 'Día de la Madre', 1),
('2025-12-25', 'HOLIDAY', 'Navidad', 1);

-- 3. INICIALIZAR BALANCES PARA EMPLEADOS ACTIVOS
-- Se ejecutará posteriormente con datos reales

-- ==================================================
-- ÍNDICES ADICIONALES PARA PERFORMANCE
-- ==================================================

-- Optimizar consultas frecuentes
CREATE INDEX `idx_vacation_employee_status` ON `vacation_requests` (`employee_id`, `status`);
CREATE INDEX `idx_vacation_date_range` ON `vacation_requests` (`start_date`, `end_date`, `status`);
CREATE INDEX `idx_balance_current` ON `vacation_balances` (`employee_id`, `year`, `current_balance`);

-- ==================================================
-- COMENTARIOS FINALES
-- ==================================================

/*
TABLA PRINCIPAL: vacation_requests
- Maneja todo el ciclo de vida de solicitudes
- Estados: PENDING → APPROVED → TAKEN
- Integración con sistema de aprobaciones

CÁLCULOS: vacation_calculations
- Auditoría completa de todos los cálculos
- Fórmulas aplicadas para transparencia
- Base para reportes detallados

CALENDARIO: vacation_calendar
- Feriados nacionales y días no laborables
- Configuración flexible por empresa
- Cálculo automático días hábiles

POLÍTICAS: vacation_policies
- Configuración adaptable legislación
- Parámetros específicos por empresa
- Extensible para futuras modificaciones

BALANCES: vacation_balances
- Tracking automático anual
- Acumulación automática
- Base para reportes gerenciales
*/