-- ================================================================
-- FIX PRODUCCIÓN - TABLA tipos_acumulados
-- Fecha: 15 de Septiembre 2025
-- Descripción: Crear/actualizar tabla tipos_acumulados que falta en producción
-- ================================================================

USE planilla_innova;

-- ================================================================
-- 1. CREAR/ACTUALIZAR TABLA tipos_acumulados
-- ================================================================

-- Crear tabla completa si no existe
CREATE TABLE IF NOT EXISTS `tipos_acumulados` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `codigo` varchar(20) NOT NULL COMMENT 'Código único del tipo de acumulado (ej: XIII_MES)',
    `descripcion` varchar(100) NOT NULL COMMENT 'Descripción del tipo de acumulado',
    `periodicidad` enum('MENSUAL','TRIMESTRAL','SEMESTRAL','ANUAL','ESPECIAL') NOT NULL DEFAULT 'ANUAL' COMMENT 'Periodicidad del acumulado',
    `fecha_inicio_periodo` date DEFAULT NULL COMMENT 'Fecha de inicio del período de acumulación',
    `fecha_fin_periodo` date DEFAULT NULL COMMENT 'Fecha de fin del período de acumulación',
    `reinicia_automaticamente` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Se reinicia automáticamente cada período',
    `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Estado del tipo de acumulado (1=activo, 0=inactivo)',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_codigo` (`codigo`),
    KEY `idx_activo` (`activo`),
    KEY `idx_periodicidad` (`periodicidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Tipos de acumulados configurables (XIII Mes, Vacaciones, etc.)';

-- ================================================================
-- 2. VERIFICAR Y AGREGAR COLUMNAS FALTANTES (por si la tabla existe pero incompleta)
-- ================================================================

-- Verificar y agregar fecha_inicio_periodo si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE table_name='tipos_acumulados' 
     AND table_schema='planilla_innova' 
     AND column_name='fecha_inicio_periodo') = 0,
    'ALTER TABLE tipos_acumulados ADD COLUMN fecha_inicio_periodo date DEFAULT NULL COMMENT ''Fecha de inicio del período de acumulación''',
    'SELECT ''Campo fecha_inicio_periodo ya existe'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar fecha_fin_periodo si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE table_name='tipos_acumulados' 
     AND table_schema='planilla_innova' 
     AND column_name='fecha_fin_periodo') = 0,
    'ALTER TABLE tipos_acumulados ADD COLUMN fecha_fin_periodo date DEFAULT NULL COMMENT ''Fecha de fin del período de acumulación''',
    'SELECT ''Campo fecha_fin_periodo ya existe'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar periodicidad si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE table_name='tipos_acumulados' 
     AND table_schema='planilla_innova' 
     AND column_name='periodicidad') = 0,
    'ALTER TABLE tipos_acumulados ADD COLUMN periodicidad enum(''MENSUAL'',''TRIMESTRAL'',''SEMESTRAL'',''ANUAL'',''ESPECIAL'') NOT NULL DEFAULT ''ANUAL'' COMMENT ''Periodicidad del acumulado''',
    'SELECT ''Campo periodicidad ya existe'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar reinicia_automaticamente si no existe
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS 
     WHERE table_name='tipos_acumulados' 
     AND table_schema='planilla_innova' 
     AND column_name='reinicia_automaticamente') = 0,
    'ALTER TABLE tipos_acumulados ADD COLUMN reinicia_automaticamente tinyint(1) NOT NULL DEFAULT 1 COMMENT ''Se reinicia automáticamente cada período''',
    'SELECT ''Campo reinicia_automaticamente ya existe'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================================================
-- 3. INSERTAR DATOS BÁSICOS DE TIPOS DE ACUMULADOS
-- ================================================================

-- Insertar tipos básicos de acumulados según legislación panameña
INSERT IGNORE INTO `tipos_acumulados` (`codigo`, `descripcion`, `periodicidad`, `activo`) VALUES
('XIII_MES', 'Décimo Tercer Mes', 'ANUAL', 1),
('VACACIONES', 'Vacaciones Proporcionales', 'ANUAL', 1),
('PRIMA_ANTIGUEDAD', 'Prima de Antigüedad', 'ANUAL', 1),
('INDEMNIZACION', 'Indemnización por Despido', 'ESPECIAL', 1),
('GASTO_REPRES', 'Gastos de Representación', 'MENSUAL', 1);

-- ================================================================
-- 4. CREAR TABLA RELACIONADA conceptos_acumulados SI NO EXISTE
-- ================================================================

-- Tabla para relacionar conceptos con tipos de acumulados
CREATE TABLE IF NOT EXISTS `conceptos_acumulados` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tipo_acumulado_id` int(11) NOT NULL COMMENT 'ID del tipo de acumulado',
    `concepto_id` int(11) NOT NULL COMMENT 'ID del concepto que acumula',
    `factor_acumulacion` decimal(5,4) NOT NULL DEFAULT 1.0000 COMMENT 'Factor de acumulación (ej: 1.0 = 100%, 0.0833 = 1/12)',
    `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Relación activa',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_tipo_concepto` (`tipo_acumulado_id`, `concepto_id`),
    KEY `idx_tipo_acumulado` (`tipo_acumulado_id`),
    KEY `idx_concepto` (`concepto_id`),
    
    CONSTRAINT `conceptos_acumulados_ibfk_1` 
        FOREIGN KEY (`tipo_acumulado_id`) REFERENCES `tipos_acumulados` (`id`) ON DELETE CASCADE,
    CONSTRAINT `conceptos_acumulados_ibfk_2` 
        FOREIGN KEY (`concepto_id`) REFERENCES `concepto` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Relación entre tipos de acumulados y conceptos que los alimentan';

-- ================================================================
-- 5. CONFIGURAR CONCEPTOS BÁSICOS PARA XIII MES
-- ================================================================

-- Relacionar conceptos básicos de sueldo con XIII Mes
INSERT IGNORE INTO `conceptos_acumulados` (`tipo_acumulado_id`, `concepto_id`, `factor_acumulacion`) 
SELECT 
    (SELECT id FROM tipos_acumulados WHERE codigo = 'XIII_MES') as tipo_acumulado_id,
    c.id as concepto_id,
    1.0000 as factor_acumulacion
FROM concepto c 
WHERE c.id IN (1, 2, 3) -- Conceptos básicos de sueldo
AND EXISTS(SELECT 1 FROM tipos_acumulados WHERE codigo = 'XIII_MES');

-- ================================================================
-- 6. VERIFICACIÓN FINAL
-- ================================================================

-- Mostrar estructura actualizada
SELECT 'ESTRUCTURA ACTUALIZADA DE tipos_acumulados:' as mensaje;
DESCRIBE tipos_acumulados;

-- Mostrar registros insertados
SELECT 'TIPOS DE ACUMULADOS CONFIGURADOS:' as mensaje;
SELECT id, codigo, descripcion, periodicidad, activo FROM tipos_acumulados;

-- Mostrar relaciones configuradas
SELECT 'CONCEPTOS RELACIONADOS CON ACUMULADOS:' as mensaje;
SELECT 
    ta.codigo as tipo_acumulado,
    c.descripcion as concepto,
    ca.factor_acumulacion
FROM conceptos_acumulados ca
INNER JOIN tipos_acumulados ta ON ca.tipo_acumulado_id = ta.id
INNER JOIN concepto c ON ca.concepto_id = c.id
WHERE ca.activo = 1;

SELECT CONCAT(
    'FIX TIPOS_ACUMULADOS COMPLETADO - ', 
    NOW(), 
    ' - Tabla y datos básicos configurados'
) as estado_fix;