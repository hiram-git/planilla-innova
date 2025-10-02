-- ==================================================
-- MIGRACIÓN: EXTENSIÓN VACATION_REQUESTS + TABLAS ANUALES
-- Descripción: Campos adicionales para gestión detallada de vacaciones
-- Versión: 1.1
-- Fecha: 2 de Octubre, 2025
-- Relacionado: V3.3.11 - Dashboard Filtros + Vacation Module Enhancement
-- ==================================================

-- 1. AGREGAR CAMPOS ADICIONALES A vacation_requests
-- Campos para tracking detallado de días de vacaciones

ALTER TABLE `vacation_requests`
ADD COLUMN `dias_vacaciones_anuales` INT DEFAULT 30 AFTER `compensation_amount`
    COMMENT 'Días de vacaciones anuales según contrato (default 30 días legislación Panamá)';

ALTER TABLE `vacation_requests`
ADD COLUMN `dias_solicitados_pagar` INT DEFAULT 0 AFTER `dias_vacaciones_anuales`
    COMMENT 'Días solicitados para compensación monetaria';

ALTER TABLE `vacation_requests`
ADD COLUMN `dias_solicitados_disfrute` INT DEFAULT 0 AFTER `dias_solicitados_pagar`
    COMMENT 'Días solicitados para disfrute efectivo';

ALTER TABLE `vacation_requests`
ADD COLUMN `ano_vacaciones` INT DEFAULT NULL AFTER `dias_solicitados_disfrute`
    COMMENT 'Año al que corresponden las vacaciones solicitadas';

ALTER TABLE `vacation_requests`
ADD COLUMN `periodo_vacaciones` VARCHAR(50) DEFAULT NULL AFTER `ano_vacaciones`
    COMMENT 'Período específico de vacaciones (ej: 2025-01, 2025-Trimestre-1)';

ALTER TABLE `vacation_requests`
ADD COLUMN `dias_calculados_fechas` INT DEFAULT 0 AFTER `periodo_vacaciones`
    COMMENT 'Días calculados automáticamente entre start_date y end_date';

-- 2. CREAR TABLA vacation_annual_balances
-- Tracking de balances anuales por empleado con desglose detallado

CREATE TABLE IF NOT EXISTS `vacation_annual_balances` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `year` INT NOT NULL COMMENT 'Año del balance',
    `dias_vacaciones_anuales` INT DEFAULT 30 COMMENT 'Días disponibles para este año',
    `dias_pagados_year` DECIMAL(6,2) DEFAULT 0.00 COMMENT 'Días compensados monetariamente en el año',
    `dias_disfrutados_year` DECIMAL(6,2) DEFAULT 0.00 COMMENT 'Días disfrutados efectivamente en el año',
    `saldo_disponible_year` DECIMAL(6,2) DEFAULT 30.00 COMMENT 'Saldo disponible restante del año',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_employee_year` (`employee_id`, `year`),
    KEY `idx_vab_employee` (`employee_id`),
    KEY `idx_vab_year` (`year`),
    KEY `idx_vab_saldo` (`saldo_disponible_year`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Balance anual detallado de vacaciones por empleado y año';

-- 3. CREAR TABLA vacation_general_totals
-- Totales generales acumulados de todo el histórico del empleado

CREATE TABLE IF NOT EXISTS `vacation_general_totals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL UNIQUE,
    `total_dias_pagados` DECIMAL(8,2) DEFAULT 0.00 COMMENT 'Total histórico días compensados monetariamente',
    `total_dias_disfrutados` DECIMAL(8,2) DEFAULT 0.00 COMMENT 'Total histórico días disfrutados',
    `total_saldo_acumulado` DECIMAL(8,2) DEFAULT 0.00 COMMENT 'Saldo total acumulado disponible',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_vgt_employee` (`employee_id`),
    KEY `idx_vgt_saldo` (`total_saldo_acumulado`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Totales generales acumulados históricos de vacaciones por empleado';

-- ==================================================
-- ÍNDICES ADICIONALES PARA PERFORMANCE
-- ==================================================

-- Optimizar consultas de vacation_requests con nuevos campos
CREATE INDEX `idx_vr_ano_periodo` ON `vacation_requests` (`ano_vacaciones`, `periodo_vacaciones`);
CREATE INDEX `idx_vr_employee_ano` ON `vacation_requests` (`employee_id`, `ano_vacaciones`, `status`);
CREATE INDEX `idx_vr_dias_calculados` ON `vacation_requests` (`dias_calculados_fechas`);

-- Optimizar consultas de balances anuales
CREATE INDEX `idx_vab_employee_year_saldo` ON `vacation_annual_balances` (`employee_id`, `year`, `saldo_disponible_year`);

-- ==================================================
-- DATOS INICIALES (OPCIONAL)
-- ==================================================

-- Inicializar balances anuales para empleados activos con fecha_ingreso existente
-- Solo si deseas crear registros automáticos para el año actual

INSERT INTO `vacation_annual_balances` (`employee_id`, `year`, `dias_vacaciones_anuales`, `saldo_disponible_year`)
SELECT
    e.id,
    YEAR(CURDATE()) as year,
    30 as dias_vacaciones_anuales,
    30.00 as saldo_disponible_year
FROM `employees` e
LEFT JOIN `vacation_annual_balances` vab ON vab.employee_id = e.id AND vab.year = YEAR(CURDATE())
WHERE e.situacion_id = 1 -- Solo empleados activos
  AND e.fecha_ingreso IS NOT NULL
  AND vab.id IS NULL; -- Solo si no existe balance para este año

-- Inicializar totales generales para empleados activos
INSERT INTO `vacation_general_totals` (`employee_id`, `total_saldo_acumulado`)
SELECT
    e.id,
    30.00 as total_saldo_acumulado
FROM `employees` e
LEFT JOIN `vacation_general_totals` vgt ON vgt.employee_id = e.id
WHERE e.situacion_id = 1 -- Solo empleados activos
  AND e.fecha_ingreso IS NOT NULL
  AND vgt.id IS NULL; -- Solo si no existe registro

-- ==================================================
-- COMENTARIOS FINALES
-- ==================================================

/*
NUEVOS CAMPOS vacation_requests:
- dias_vacaciones_anuales: Días anuales base (30 por defecto legislación Panamá)
- dias_solicitados_pagar: Cantidad específica de días para compensación monetaria
- dias_solicitados_disfrute: Cantidad específica de días para disfrute efectivo
- ano_vacaciones: Año al que corresponden estas vacaciones
- periodo_vacaciones: Identificador de período (útil para múltiples solicitudes del año)
- dias_calculados_fechas: Cálculo automático de días entre start_date y end_date

NUEVA TABLA vacation_annual_balances:
- Balance anual específico por empleado y año
- Desglose detallado: días pagados, disfrutados y saldo
- Permite tracking histórico año por año
- Foreign key a employees con CASCADE DELETE

NUEVA TABLA vacation_general_totals:
- Totales acumulados globales por empleado
- Un solo registro por empleado (UNIQUE constraint)
- Saldo total disponible considerando todos los años
- Actualización automática con TIMESTAMP

VENTAJAS DEL DISEÑO:
✅ Separación clara entre balances anuales y totales generales
✅ Tracking detallado por año permite auditorías precisas
✅ Saldo calculado automáticamente con fórmulas SQL
✅ Índices optimizados para consultas frecuentes
✅ Foreign keys garantizan integridad referencial
✅ Compatible con legislación panameña (30 días/año)

PRÓXIMOS PASOS DESARROLLO:
1. VacationBalanceService: Clase PHP para calcular y actualizar balances
2. VacationController: CRUD completo con validaciones legislación
3. Vistas: Formularios solicitud + aprobación + reportes
4. Integración: Motor fórmulas PlanillaConceptCalculator con variables DIAS_VACACIONES
*/
