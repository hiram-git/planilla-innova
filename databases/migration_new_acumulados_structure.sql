-- =====================================================
-- MIGRACIÓN: Nueva Estructura de Acumulados
-- Fecha: 13 de Septiembre, 2025
-- Propósito: Crear sistema optimizado de acumulados
-- =====================================================

-- 1. TABLA ACUMULADOS_POR_EMPLEADO (Registro detallado por transacción)
CREATE TABLE IF NOT EXISTS `acumulados_por_empleado` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `employee_id` INT(11) NOT NULL COMMENT 'ID del empleado',
    `concepto_id` INT(11) NOT NULL COMMENT 'ID del concepto',
    `planilla_id` INT(11) NOT NULL COMMENT 'ID de la planilla donde se generó',
    `monto` DECIMAL(10,2) NOT NULL COMMENT 'Monto del concepto',
    `mes` INT(2) NOT NULL COMMENT 'Mes del acumulado (1-12)',
    `ano` INT(4) NOT NULL COMMENT 'Año del acumulado',
    `frecuencia` ENUM('QUINCENAL', 'MENSUAL', 'ANUAL', 'ESPECIAL') NOT NULL DEFAULT 'QUINCENAL' COMMENT 'Frecuencia de la planilla',
    `tipo_concepto` ENUM('ASIGNACION', 'DEDUCCION') NOT NULL COMMENT 'Tipo de concepto',
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del registro',
    `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización',
    
    PRIMARY KEY (`id`),
    INDEX `idx_employee_id` (`employee_id`),
    INDEX `idx_concepto_id` (`concepto_id`),
    INDEX `idx_planilla_id` (`planilla_id`),
    INDEX `idx_mes_ano` (`mes`, `ano`),
    INDEX `idx_employee_periodo` (`employee_id`, `mes`, `ano`),
    INDEX `idx_employee_concepto` (`employee_id`, `concepto_id`),
    
    CONSTRAINT `fk_acum_emp_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_acum_emp_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `conceptos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_acum_emp_planilla` FOREIGN KEY (`planilla_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Acumulados detallados por empleado y transacción';

-- 2. REESTRUCTURAR TABLA ACUMULADOS_POR_PLANILLA (Consolidado optimizado)
-- Primero renombrar la tabla actual como backup
RENAME TABLE `acumulados_por_planilla` TO `acumulados_por_planilla_backup`;

-- Crear nueva tabla consolidada
CREATE TABLE `acumulados_por_planilla` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `employee_id` INT(11) NOT NULL COMMENT 'ID del empleado',
    `planilla_id` INT(11) NOT NULL COMMENT 'ID de la planilla',
    `mes` INT(2) NOT NULL COMMENT 'Mes de la planilla',
    `ano` INT(4) NOT NULL COMMENT 'Año de la planilla',
    `frecuencia` ENUM('QUINCENAL', 'MENSUAL', 'ANUAL', 'ESPECIAL') NOT NULL DEFAULT 'QUINCENAL',
    
    -- ASIGNACIONES (Ingresos)
    `sueldos` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total de sueldos y salarios',
    `gastos_representacion` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Gastos de representación',
    `otras_asignaciones` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Otras asignaciones (bonos, comisiones, etc.)',
    `total_asignaciones` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total de todas las asignaciones',
    
    -- DEDUCCIONES LEGALES
    `seguro_social` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Seguro Social (SS)',
    `seguro_educativo` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Seguro Educativo (SE)',
    `impuesto_renta` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Impuesto Sobre la Renta (ISR)',
    
    -- DESCUENTOS DE LEY PARA GASTOS DE REPRESENTACIÓN
    `desc_gastos_ss` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Descuento SS sobre gastos de representación',
    `desc_gastos_se` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Descuento SE sobre gastos de representación',
    `desc_gastos_isr` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Descuento ISR sobre gastos de representación',
    
    -- OTRAS DEDUCCIONES
    `otras_deducciones` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Otras deducciones (préstamos, embargos, etc.)',
    `total_deducciones` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total de todas las deducciones',
    
    -- TOTALES FINALES
    `total_neto` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total neto a pagar (asignaciones - deducciones)',
    
    -- METADATOS
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_employee_planilla` (`employee_id`, `planilla_id`),
    INDEX `idx_employee_id` (`employee_id`),
    INDEX `idx_planilla_id` (`planilla_id`),
    INDEX `idx_mes_ano` (`mes`, `ano`),
    INDEX `idx_employee_periodo` (`employee_id`, `mes`, `ano`),
    
    CONSTRAINT `fk_acum_planilla_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_acum_planilla_planilla` FOREIGN KEY (`planilla_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Acumulados consolidados por empleado y planilla para optimización';

-- 3. CREAR VISTA PARA FACILITAR CONSULTAS DE ACUMULADOS ANUALES
CREATE OR REPLACE VIEW `vista_acumulados_anuales` AS
SELECT 
    e.id as employee_id,
    CONCAT(e.firstname, ' ', e.lastname) as nombre_completo,
    e.document_id as cedula,
    ap.ano,
    SUM(ap.sueldos) as total_sueldos_ano,
    SUM(ap.gastos_representacion) as total_gastos_rep_ano,
    SUM(ap.otras_asignaciones) as total_otras_asig_ano,
    SUM(ap.total_asignaciones) as total_asignaciones_ano,
    SUM(ap.seguro_social) as total_ss_ano,
    SUM(ap.seguro_educativo) as total_se_ano,
    SUM(ap.impuesto_renta) as total_isr_ano,
    SUM(ap.desc_gastos_ss + ap.desc_gastos_se + ap.desc_gastos_isr) as total_desc_gastos_ano,
    SUM(ap.otras_deducciones) as total_otras_ded_ano,
    SUM(ap.total_deducciones) as total_deducciones_ano,
    SUM(ap.total_neto) as total_neto_ano,
    COUNT(DISTINCT ap.planilla_id) as total_planillas_procesadas
FROM employees e
INNER JOIN acumulados_por_planilla ap ON e.id = ap.employee_id
GROUP BY e.id, ap.ano
ORDER BY e.lastname, e.firstname, ap.ano;

-- 4. ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
CREATE INDEX `idx_acum_emp_mes_ano_tipo` ON `acumulados_por_empleado` (`mes`, `ano`, `tipo_concepto`);
CREATE INDEX `idx_acum_planilla_totales` ON `acumulados_por_planilla` (`total_asignaciones`, `total_deducciones`, `total_neto`);

-- 5. COMENTARIOS FINALES
-- Esta migración crea un sistema dual:
-- - acumulados_por_empleado: Para tracking detallado y auditoría
-- - acumulados_por_planilla: Para consultas rápidas y reportes optimizados
-- - vista_acumulados_anuales: Para reportes anuales sin queries complejos

SELECT 'Migración completada exitosamente' as resultado;