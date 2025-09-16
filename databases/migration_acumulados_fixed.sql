-- =====================================================
-- MIGRACIÓN: Estructura de Acumulados (CORREGIDA)
-- Fecha: 13 de Septiembre, 2025
-- Propósito: Crear tablas faltantes para acumulados
-- =====================================================

-- 1. AGREGAR CAMPOS DE CIERRE A PLANILLA_CABECERA
ALTER TABLE planilla_cabecera 
ADD COLUMN IF NOT EXISTS fecha_cierre TIMESTAMP NULL COMMENT 'Fecha de cierre de la planilla',
ADD COLUMN IF NOT EXISTS usuario_cierre VARCHAR(100) NULL COMMENT 'Usuario que cerró la planilla',
ADD COLUMN IF NOT EXISTS acumulados_generados TINYINT(1) DEFAULT 0 COMMENT 'Si ya se generaron acumulados';

-- 2. CREAR TABLA EMPLEADOS_ACUMULADOS_HISTORICOS
CREATE TABLE IF NOT EXISTS `empleados_acumulados_historicos` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `empleado_id` INT(11) NOT NULL COMMENT 'ID del empleado',
    `tipo_acumulado_id` INT(11) NOT NULL COMMENT 'ID del tipo de acumulado',
    `total_acumulado` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total acumulado',
    `total_conceptos_incluidos` INT(11) DEFAULT 0 COMMENT 'Total de conceptos incluidos',
    `periodo_inicio` DATE NOT NULL COMMENT 'Fecha inicio del período',
    `periodo_fin` DATE NOT NULL COMMENT 'Fecha fin del período',
    `fecha_ultimo_calculo` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Última actualización',
    `ultima_planilla_id` INT(11) NULL COMMENT 'ID de la última planilla que actualizó este acumulado',
    `activo` TINYINT(1) DEFAULT 1 COMMENT 'Si está activo',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_empleado_tipo_periodo` (`empleado_id`, `tipo_acumulado_id`, `periodo_inicio`, `periodo_fin`),
    INDEX `idx_empleado_id` (`empleado_id`),
    INDEX `idx_tipo_acumulado_id` (`tipo_acumulado_id`),
    INDEX `idx_periodo` (`periodo_inicio`, `periodo_fin`),
    INDEX `idx_ultima_planilla` (`ultima_planilla_id`),
    
    CONSTRAINT `fk_eah_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_eah_tipo_acumulado` FOREIGN KEY (`tipo_acumulado_id`) REFERENCES `tipos_acumulados` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_eah_planilla` FOREIGN KEY (`ultima_planilla_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Histórico de acumulados por empleado para consultas optimizadas';

-- 3. CREAR TABLA ACUMULADOS_POR_EMPLEADO (Detalle transaccional)
CREATE TABLE IF NOT EXISTS `acumulados_por_empleado` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `employee_id` INT(11) NOT NULL COMMENT 'ID del empleado',
    `concepto_id` INT(11) NOT NULL COMMENT 'ID del concepto',
    `planilla_id` INT(11) NOT NULL COMMENT 'ID de la planilla donde se generó',
    `tipo_acumulado_id` INT(11) NOT NULL COMMENT 'ID del tipo de acumulado',
    `monto_concepto` DECIMAL(10,2) NOT NULL COMMENT 'Monto del concepto original',
    `factor_acumulacion` DECIMAL(5,4) DEFAULT 1.0000 COMMENT 'Factor de acumulación aplicado',
    `monto_acumulado` DECIMAL(10,2) NOT NULL COMMENT 'Monto acumulado final',
    `mes` INT(2) NOT NULL COMMENT 'Mes del acumulado (1-12)',
    `ano` INT(4) NOT NULL COMMENT 'Año del acumulado',
    `periodo_inicio` DATE NOT NULL COMMENT 'Inicio del período de acumulación',
    `periodo_fin` DATE NOT NULL COMMENT 'Fin del período de acumulación',
    `frecuencia` ENUM('QUINCENAL', 'MENSUAL', 'ANUAL', 'ESPECIAL') NOT NULL DEFAULT 'QUINCENAL',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_employee_id` (`employee_id`),
    INDEX `idx_concepto_id` (`concepto_id`),
    INDEX `idx_planilla_id` (`planilla_id`),
    INDEX `idx_tipo_acumulado_id` (`tipo_acumulado_id`),
    INDEX `idx_mes_ano` (`mes`, `ano`),
    INDEX `idx_employee_periodo` (`employee_id`, `mes`, `ano`),
    INDEX `idx_employee_concepto` (`employee_id`, `concepto_id`),
    
    CONSTRAINT `fk_acum_emp_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_acum_emp_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_acum_emp_planilla` FOREIGN KEY (`planilla_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_acum_emp_tipo` FOREIGN KEY (`tipo_acumulado_id`) REFERENCES `tipos_acumulados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Acumulados detallados por empleado y transacción para auditoría';

-- 4. CREAR TABLA PLANILLA_AUDITORIA (si no existe)
CREATE TABLE IF NOT EXISTS `planilla_auditoria` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `planilla_id` INT(11) NOT NULL COMMENT 'ID de la planilla',
    `estado_anterior` VARCHAR(20) NOT NULL COMMENT 'Estado anterior',
    `estado_nuevo` VARCHAR(20) NOT NULL COMMENT 'Estado nuevo',
    `usuario` VARCHAR(100) NOT NULL COMMENT 'Usuario que realizó el cambio',
    `motivo` TEXT NULL COMMENT 'Motivo del cambio',
    `acumulados_afectados` INT(11) DEFAULT 0 COMMENT 'Número de acumulados afectados',
    `fecha_cambio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_planilla_id` (`planilla_id`),
    INDEX `idx_fecha_cambio` (`fecha_cambio`),
    INDEX `idx_usuario` (`usuario`),
    
    CONSTRAINT `fk_auditoria_planilla` FOREIGN KEY (`planilla_id`) REFERENCES `planilla_cabecera` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Auditoría de cambios de estado en planillas';

-- 5. ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
CREATE INDEX IF NOT EXISTS `idx_planilla_estado_fecha` ON `planilla_cabecera` (`estado`, `fecha`);
CREATE INDEX IF NOT EXISTS `idx_planilla_cierre` ON `planilla_cabecera` (`fecha_cierre`, `acumulados_generados`);

-- 6. VERIFICAR RESULTADO
SELECT 'Migración de acumulados completada exitosamente' as resultado;

-- 7. MOSTRAR TABLAS CREADAS
SHOW TABLES LIKE '%acumulados%';
SHOW TABLES LIKE '%auditoria%';