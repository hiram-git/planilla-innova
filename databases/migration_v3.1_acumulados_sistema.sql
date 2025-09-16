-- ================================================================
-- MIGRACIÓN V3.1 - SISTEMA DE ACUMULADOS + VALIDACIONES CONCEPTOS
-- Fecha: 15 de Septiembre 2025
-- Descripción: Migraciones para sistema completo de acumulados automáticos
--              con legislación panameña y validaciones estrictas de conceptos
-- ================================================================

USE planilla_innova;

-- ================================================================
-- 1. TABLAS DE ACUMULADOS AUTOMÁTICOS
-- ================================================================

-- Tabla: acumulados_por_empleado
-- Propósito: Registro detallado por cada concepto/empleado/planilla para auditoría
CREATE TABLE IF NOT EXISTS `acumulados_por_empleado` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL COMMENT 'ID del empleado',
    `concepto_id` int(11) NOT NULL COMMENT 'ID del concepto aplicado',
    `planilla_id` int(11) NOT NULL COMMENT 'ID de la planilla que generó el acumulado',
    `monto` decimal(10,2) NOT NULL COMMENT 'Monto del concepto en esta planilla',
    `mes` int(2) NOT NULL COMMENT 'Mes de la planilla (1-12)',
    `ano` int(4) NOT NULL COMMENT 'Año de la planilla',
    `frecuencia` enum('QUINCENAL','MENSUAL','ANUAL','ESPECIAL') NOT NULL DEFAULT 'QUINCENAL',
    `tipo_concepto` enum('ASIGNACION','DEDUCCION') NOT NULL COMMENT 'Tipo del concepto',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    
    PRIMARY KEY (`id`),
    KEY `idx_employee_id` (`employee_id`),
    KEY `idx_concepto_id` (`concepto_id`),
    KEY `idx_planilla_id` (`planilla_id`),
    KEY `idx_mes_ano` (`mes`, `ano`),
    KEY `idx_employee_concepto_ano` (`employee_id`, `concepto_id`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Acumulados detallados por empleado/concepto/planilla para auditoría';

-- Tabla: acumulados_por_planilla  
-- Propósito: Consolidado optimizado por empleado/planilla para reportes rápidos
CREATE TABLE IF NOT EXISTS `acumulados_por_planilla` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL COMMENT 'ID del empleado',
    `planilla_id` int(11) NOT NULL COMMENT 'ID de la planilla',
    `mes` int(2) NOT NULL COMMENT 'Mes de la planilla (1-12)',
    `ano` int(4) NOT NULL COMMENT 'Año de la planilla',
    `frecuencia` enum('QUINCENAL','MENSUAL','ANUAL','ESPECIAL') NOT NULL DEFAULT 'QUINCENAL',
    
    -- ASIGNACIONES (Ingresos)
    `sueldos` decimal(10,2) DEFAULT 0.00 COMMENT 'Sueldos base (conceptos 1,2,3)',
    `gastos_representacion` decimal(10,2) DEFAULT 0.00 COMMENT 'Gastos de representación',
    `otras_asignaciones` decimal(10,2) DEFAULT 0.00 COMMENT 'Otras asignaciones',
    `total_asignaciones` decimal(10,2) DEFAULT 0.00 COMMENT 'Total de ingresos',
    
    -- DEDUCCIONES LEGALES
    `seguro_social` decimal(10,2) DEFAULT 0.00 COMMENT 'Descuento Seguro Social',
    `seguro_educativo` decimal(10,2) DEFAULT 0.00 COMMENT 'Descuento Seguro Educativo', 
    `impuesto_renta` decimal(10,2) DEFAULT 0.00 COMMENT 'Retención ISR',
    
    -- DESCUENTOS SOBRE GASTOS DE REPRESENTACIÓN
    `desc_gastos_ss` decimal(10,2) DEFAULT 0.00 COMMENT 'Desc. SS sobre gastos rep.',
    `desc_gastos_se` decimal(10,2) DEFAULT 0.00 COMMENT 'Desc. SE sobre gastos rep.',
    `desc_gastos_isr` decimal(10,2) DEFAULT 0.00 COMMENT 'Desc. ISR sobre gastos rep.',
    
    -- OTRAS DEDUCCIONES Y TOTALES
    `otras_deducciones` decimal(10,2) DEFAULT 0.00 COMMENT 'Otras deducciones',
    `total_deducciones` decimal(10,2) DEFAULT 0.00 COMMENT 'Total de deducciones',
    `total_neto` decimal(10,2) DEFAULT 0.00 COMMENT 'Neto a pagar',
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_employee_planilla` (`employee_id`, `planilla_id`),
    KEY `idx_planilla_id` (`planilla_id`),
    KEY `idx_mes_ano` (`mes`, `ano`),
    KEY `idx_employee_ano` (`employee_id`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Acumulados consolidados por empleado/planilla para reportes optimizados';

-- ================================================================
-- 2. TABLAS RELACIONALES PARA VALIDACIONES DE CONCEPTOS
-- ================================================================

-- Tabla: concepto_tipos_planilla
-- Propósito: Relación N:N entre conceptos y tipos de planilla
CREATE TABLE IF NOT EXISTS `concepto_tipos_planilla` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `concepto_id` int(11) NOT NULL COMMENT 'ID del concepto',
    `tipo_planilla_id` int(11) NOT NULL COMMENT 'ID del tipo de planilla',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_concepto_tipo` (`concepto_id`, `tipo_planilla_id`),
    KEY `tipo_planilla_id` (`tipo_planilla_id`),
    
    CONSTRAINT `concepto_tipos_planilla_ibfk_1` 
        FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE,
    CONSTRAINT `concepto_tipos_planilla_ibfk_2` 
        FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Relación conceptos con tipos de planilla permitidos';

-- Tabla: concepto_situaciones
-- Propósito: Relación N:N entre conceptos y situaciones de empleados
CREATE TABLE IF NOT EXISTS `concepto_situaciones` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `concepto_id` int(11) NOT NULL COMMENT 'ID del concepto',
    `situacion_id` int(11) NOT NULL COMMENT 'ID de la situación del empleado',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_concepto_situacion` (`concepto_id`, `situacion_id`),
    KEY `situacion_id` (`situacion_id`),
    
    CONSTRAINT `concepto_situaciones_ibfk_1` 
        FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE,
    CONSTRAINT `concepto_situaciones_ibfk_2` 
        FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Relación conceptos con situaciones de empleados permitidas';

-- Tabla: concepto_frecuencias
-- Propósito: Relación N:N entre conceptos y frecuencias de planilla
CREATE TABLE IF NOT EXISTS `concepto_frecuencias` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `concepto_id` int(11) NOT NULL COMMENT 'ID del concepto',
    `frecuencia_id` int(11) NOT NULL COMMENT 'ID de la frecuencia',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_concepto_frecuencia` (`concepto_id`, `frecuencia_id`),
    KEY `frecuencia_id` (`frecuencia_id`),
    
    CONSTRAINT `concepto_frecuencias_ibfk_1` 
        FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE,
    CONSTRAINT `concepto_frecuencias_ibfk_2` 
        FOREIGN KEY (`frecuencia_id`) REFERENCES `frecuencias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Relación conceptos con frecuencias de planilla permitidas';

-- ================================================================
-- 3. MODIFICACIONES A TABLAS EXISTENTES
-- ================================================================

-- Agregar campos de control de reapertura en planilla_cabecera
-- Verificar y agregar campos solo si no existen
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE table_name='planilla_cabecera' 
     AND table_schema='planilla_innova' 
     AND column_name='fecha_reapertura') = 0,
    'ALTER TABLE planilla_cabecera ADD COLUMN fecha_reapertura timestamp NULL COMMENT ''Fecha de reapertura de planilla cerrada''',
    'SELECT ''Campo fecha_reapertura ya existe'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE table_name='planilla_cabecera' 
     AND table_schema='planilla_innova' 
     AND column_name='usuario_reapertura') = 0,
    'ALTER TABLE planilla_cabecera ADD COLUMN usuario_reapertura varchar(100) NULL COMMENT ''Usuario que reabrió la planilla''',
    'SELECT ''Campo usuario_reapertura ya existe'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE table_name='planilla_cabecera' 
     AND table_schema='planilla_innova' 
     AND column_name='motivo_reapertura') = 0,
    'ALTER TABLE planilla_cabecera ADD COLUMN motivo_reapertura text NULL COMMENT ''Motivo de reapertura de la planilla''',
    'SELECT ''Campo motivo_reapertura ya existe'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE table_name='planilla_cabecera' 
     AND table_schema='planilla_innova' 
     AND column_name='fecha_cierre') = 0,
    'ALTER TABLE planilla_cabecera ADD COLUMN fecha_cierre timestamp NULL COMMENT ''Fecha de cierre de la planilla''',
    'SELECT ''Campo fecha_cierre ya existe'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE table_name='planilla_cabecera' 
     AND table_schema='planilla_innova' 
     AND column_name='usuario_cierre') = 0,
    'ALTER TABLE planilla_cabecera ADD COLUMN usuario_cierre varchar(100) NULL COMMENT ''Usuario que cerró la planilla''',
    'SELECT ''Campo usuario_cierre ya existe'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE table_name='planilla_cabecera' 
     AND table_schema='planilla_innova' 
     AND column_name='acumulados_generados') = 0,
    'ALTER TABLE planilla_cabecera ADD COLUMN acumulados_generados tinyint(1) DEFAULT 0 COMMENT ''Flag: acumulados fueron generados (1=sí, 0=no)''',
    'SELECT ''Campo acumulados_generados ya existe'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================================================
-- 4. ÍNDICES ADICIONALES PARA PERFORMANCE
-- ================================================================

-- Índices para consultas de acumulados por empleado (crear solo si no existen)
SET @sql = 'CREATE INDEX `idx_emp_concepto_periodo` ON `acumulados_por_empleado` (`employee_id`, `concepto_id`, `ano`, `mes`)';
SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS 
               WHERE table_schema='planilla_innova' 
               AND table_name='acumulados_por_empleado' 
               AND index_name='idx_emp_concepto_periodo') = 0, @sql, 'SELECT ''Índice idx_emp_concepto_periodo ya existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'CREATE INDEX `idx_planilla_tipo_concepto` ON `acumulados_por_empleado` (`planilla_id`, `tipo_concepto`)';
SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS 
               WHERE table_schema='planilla_innova' 
               AND table_name='acumulados_por_empleado' 
               AND index_name='idx_planilla_tipo_concepto') = 0, @sql, 'SELECT ''Índice idx_planilla_tipo_concepto ya existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índices para reportes consolidados (crear solo si no existen)
SET @sql = 'CREATE INDEX `idx_consolidado_periodo` ON `acumulados_por_planilla` (`ano`, `mes`, `frecuencia`)';
SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS 
               WHERE table_schema='planilla_innova' 
               AND table_name='acumulados_por_planilla' 
               AND index_name='idx_consolidado_periodo') = 0, @sql, 'SELECT ''Índice idx_consolidado_periodo ya existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'CREATE INDEX `idx_consolidado_empleado_periodo` ON `acumulados_por_planilla` (`employee_id`, `ano`, `frecuencia`)';
SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS 
               WHERE table_schema='planilla_innova' 
               AND table_name='acumulados_por_planilla' 
               AND index_name='idx_consolidado_empleado_periodo') = 0, @sql, 'SELECT ''Índice idx_consolidado_empleado_periodo ya existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================================================
-- 5. VISTAS PARA REPORTES COMUNES
-- ================================================================

-- Vista: Resumen anual de acumulados por empleado
CREATE OR REPLACE VIEW `v_acumulados_anuales_empleado` AS
SELECT 
    e.id as employee_id,
    CONCAT(e.firstname, ' ', e.lastname) as empleado_nombre,
    e.document_id as cedula,
    ape.ano,
    ape.concepto_id,
    c.descripcion as concepto_descripcion,
    c.tipo_concepto as tipo,
    SUM(ape.monto) as total_anual,
    COUNT(ape.planilla_id) as cantidad_planillas
FROM acumulados_por_empleado ape
INNER JOIN employees e ON ape.employee_id = e.id
INNER JOIN concepto c ON ape.concepto_id = c.id
GROUP BY e.id, ape.ano, ape.concepto_id
ORDER BY e.firstname, e.lastname, ape.ano, c.descripcion;

-- Vista: XIII Mes automático por empleado
CREATE OR REPLACE VIEW `v_xiii_mes_empleados` AS
SELECT 
    e.id as employee_id,
    CONCAT(e.firstname, ' ', e.lastname) as empleado_nombre,
    e.document_id as cedula,
    ape.ano,
    SUM(CASE WHEN c.id IN (1,2,3) THEN ape.monto ELSE 0 END) as salario_anual,
    (SUM(CASE WHEN c.id IN (1,2,3) THEN ape.monto ELSE 0 END) / 3) as xiii_mes_teorico,
    -- TODO: Agregar cálculo de días no trabajados cuando esté disponible
    (SUM(CASE WHEN c.id IN (1,2,3) THEN ape.monto ELSE 0 END) / 3) as xiii_mes_final
FROM acumulados_por_empleado ape
INNER JOIN employees e ON ape.employee_id = e.id
INNER JOIN concepto c ON ape.concepto_id = c.id
WHERE c.id IN (1,2,3) -- Conceptos que van al XIII Mes
GROUP BY e.id, ape.ano
ORDER BY e.firstname, e.lastname, ape.ano;

-- ================================================================
-- 6. DATOS INICIALES Y CONFIGURACIONES
-- ================================================================

-- Insertar tipos de acumulados básicos (si no existen)
INSERT IGNORE INTO `tipos_acumulados` (`codigo`, `descripcion`, `activo`) VALUES
('XIII_MES', 'Décimo Tercer Mes', 1),
('VACACIONES', 'Vacaciones Proporcionales', 1),
('PRIMA_ANTIGUEDAD', 'Prima de Antigüedad', 1),
('INDEMNIZACION', 'Indemnización por Despido', 1),
('GASTO_REPRES', 'Gastos de Representación', 1);

-- ================================================================
-- 7. CONFIGURACIONES PARA CONCEPTOS EXISTENTES
-- ================================================================

-- Configurar conceptos básicos con sus restricciones
-- NOTA: Estos INSERTs son seguros con IGNORE, no duplicarán registros

-- Concepto 1 (Sueldo) - Todos los tipos, frecuencias y situaciones
INSERT IGNORE INTO concepto_tipos_planilla (concepto_id, tipo_planilla_id) 
SELECT 1, id FROM tipos_planilla WHERE id <= 3;

INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id) 
SELECT 1, id FROM frecuencias WHERE id <= 5;

INSERT IGNORE INTO concepto_situaciones (concepto_id, situacion_id) 
SELECT 1, id FROM situaciones WHERE id = 1; -- Situación activo

-- Concepto 2 (Seguro Social) - Restringir según configuración
INSERT IGNORE INTO concepto_tipos_planilla (concepto_id, tipo_planilla_id) 
VALUES (2, 1), (2, 2);

INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id) 
VALUES (2, 1);

INSERT IGNORE INTO concepto_situaciones (concepto_id, situacion_id) 
VALUES (2, 1);

-- Concepto 3 (Seguro Educativo) - Similar al Seguro Social  
INSERT IGNORE INTO concepto_tipos_planilla (concepto_id, tipo_planilla_id) 
VALUES (3, 1), (3, 2);

INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id) 
VALUES (3, 1), (3, 2);

INSERT IGNORE INTO concepto_situaciones (concepto_id, situacion_id) 
VALUES (3, 1);

-- ================================================================
-- 8. VALIDACIÓN FINAL Y ESTADÍSTICAS
-- ================================================================

-- Mostrar estadísticas de las tablas creadas
SELECT 'RESUMEN DE MIGRACIÓN V3.1' as resumen;

SELECT 
    'acumulados_por_empleado' as tabla,
    COUNT(*) as registros,
    'Auditoría detallada por concepto' as proposito
FROM acumulados_por_empleado
UNION ALL
SELECT 
    'acumulados_por_planilla' as tabla,
    COUNT(*) as registros,
    'Consolidado para reportes rápidos' as proposito
FROM acumulados_por_planilla
UNION ALL
SELECT 
    'concepto_tipos_planilla' as tabla,
    COUNT(*) as registros,
    'Validaciones tipo de planilla' as proposito
FROM concepto_tipos_planilla
UNION ALL
SELECT 
    'concepto_situaciones' as tabla,
    COUNT(*) as registros,
    'Validaciones situación empleado' as proposito
FROM concepto_situaciones  
UNION ALL
SELECT 
    'concepto_frecuencias' as tabla,
    COUNT(*) as registros,
    'Validaciones frecuencia planilla' as proposito
FROM concepto_frecuencias;

-- ================================================================
-- MIGRACIÓN COMPLETADA EXITOSAMENTE
-- ================================================================

SELECT CONCAT(
    'MIGRACIÓN V3.1 COMPLETADA - ', 
    NOW(), 
    ' - Sistema de Acumulados Automáticos con Legislación Panameña Listo'
) as estado_migracion;