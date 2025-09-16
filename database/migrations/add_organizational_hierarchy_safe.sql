-- =====================================================
-- Migración: Agregar Jerarquía Organizacional (Segura)
-- Fecha: Septiembre 2025
-- Descripción: Agregar estructura padre-hijo sin eliminar datos existentes
-- =====================================================

-- Agregar campos de jerarquía a la tabla funciones
ALTER TABLE `funciones` 
ADD COLUMN IF NOT EXISTS `parent_id` int DEFAULT NULL AFTER `codigo`,
ADD COLUMN IF NOT EXISTS `nivel_jerarquico` int DEFAULT 1 AFTER `parent_id`,
ADD COLUMN IF NOT EXISTS `orden_organizacional` int DEFAULT 0 AFTER `nivel_jerarquico`;

-- Agregar índices si no existen
ALTER TABLE `funciones` 
ADD INDEX IF NOT EXISTS `idx_parent_id` (`parent_id`),
ADD INDEX IF NOT EXISTS `idx_nivel_jerarquico` (`nivel_jerarquico`);

-- Agregar foreign key si no existe
SET @sql = 'ALTER TABLE `funciones` ADD CONSTRAINT `fk_funciones_parent` FOREIGN KEY (`parent_id`) REFERENCES `funciones` (`id`) ON DELETE SET NULL';
SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'planilla_innova' AND TABLE_NAME = 'funciones' AND CONSTRAINT_NAME = 'fk_funciones_parent') = 0, @sql, 'SELECT "FK already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar campos de jerarquía a la tabla cargos
ALTER TABLE `cargos` 
ADD COLUMN IF NOT EXISTS `parent_id` int DEFAULT NULL AFTER `codigo`,
ADD COLUMN IF NOT EXISTS `nivel_jerarquico` int DEFAULT 1 AFTER `parent_id`,
ADD COLUMN IF NOT EXISTS `orden_organizacional` int DEFAULT 0 AFTER `nivel_jerarquico`;

-- Agregar índices si no existen
ALTER TABLE `cargos` 
ADD INDEX IF NOT EXISTS `idx_cargos_parent` (`parent_id`),
ADD INDEX IF NOT EXISTS `idx_cargos_nivel` (`nivel_jerarquico`);

-- Agregar foreign key si no existe
SET @sql = 'ALTER TABLE `cargos` ADD CONSTRAINT `fk_cargos_parent` FOREIGN KEY (`parent_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL';
SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'planilla_innova' AND TABLE_NAME = 'cargos' AND CONSTRAINT_NAME = 'fk_cargos_parent') = 0, @sql, 'SELECT "FK already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- Actualizar datos existentes con jerarquía básica
-- =====================================================

-- Actualizar funciones existentes con valores por defecto
UPDATE `funciones` SET 
    `nivel_jerarquico` = 1,
    `orden_organizacional` = id
WHERE `nivel_jerarquico` IS NULL OR `nivel_jerarquico` = 0;

-- Actualizar cargos existentes con valores por defecto
UPDATE `cargos` SET 
    `nivel_jerarquico` = 1,
    `orden_organizacional` = id
WHERE `nivel_jerarquico` IS NULL OR `nivel_jerarquico` = 0;

-- =====================================================
-- Insertar datos jerárquicos de ejemplo si no existen
-- =====================================================

-- Solo insertar si no hay funciones con códigos específicos
INSERT IGNORE INTO `funciones` (`codigo`, `nombre`, `descripcion`, `parent_id`, `nivel_jerarquico`, `orden_organizacional`, `activo`) VALUES
('DIR_GEN', 'Dirección General', 'Función de dirección general de la organización', NULL, 1, 1, 1),
('ADMIN', 'Administración', 'Función administrativa y de gestión', (SELECT id FROM funciones WHERE codigo = 'DIR_GEN' LIMIT 1), 2, 2, 1),
('RRHH', 'Recursos Humanos', 'Gestión de recursos humanos y personal', (SELECT id FROM funciones WHERE codigo = 'ADMIN' LIMIT 1), 3, 3, 1),
('FIN', 'Finanzas', 'Gestión financiera y contable', (SELECT id FROM funciones WHERE codigo = 'ADMIN' LIMIT 1), 3, 4, 1),
('OPS', 'Operaciones', 'Operaciones y procesos productivos', (SELECT id FROM funciones WHERE codigo = 'DIR_GEN' LIMIT 1), 2, 5, 1),
('PROD', 'Producción', 'Procesos de producción y manufactura', (SELECT id FROM funciones WHERE codigo = 'OPS' LIMIT 1), 3, 6, 1),
('LOG', 'Logística', 'Logística y cadena de suministro', (SELECT id FROM funciones WHERE codigo = 'OPS' LIMIT 1), 3, 7, 1),
('VEN', 'Ventas', 'Ventas y comercialización', (SELECT id FROM funciones WHERE codigo = 'DIR_GEN' LIMIT 1), 2, 8, 1),
('MKT', 'Marketing', 'Marketing y promoción', (SELECT id FROM funciones WHERE codigo = 'VEN' LIMIT 1), 3, 9, 1),
('SAC', 'Servicio al Cliente', 'Atención y servicio al cliente', (SELECT id FROM funciones WHERE codigo = 'VEN' LIMIT 1), 3, 10, 1);

-- Solo insertar si no hay cargos con códigos específicos
INSERT IGNORE INTO `cargos` (`codigo`, `nombre`, `descripcion`, `parent_id`, `nivel_jerarquico`, `orden_organizacional`, `activo`) VALUES
('DIR_001', 'Director General', 'Máxima autoridad de la organización', NULL, 1, 1, 1),
('SUB_001', 'Subdirector', 'Subdirección general', (SELECT id FROM cargos WHERE codigo = 'DIR_001' LIMIT 1), 2, 2, 1),
('GER_ADM', 'Gerente Administrativo', 'Gerencia del área administrativa', (SELECT id FROM cargos WHERE codigo = 'SUB_001' LIMIT 1), 3, 3, 1),
('GER_OPS', 'Gerente de Operaciones', 'Gerencia del área operativa', (SELECT id FROM cargos WHERE codigo = 'SUB_001' LIMIT 1), 3, 4, 1),
('GER_VEN', 'Gerente de Ventas', 'Gerencia del área de ventas', (SELECT id FROM cargos WHERE codigo = 'SUB_001' LIMIT 1), 3, 5, 1),
('JEFE_RH', 'Jefe de Recursos Humanos', 'Jefatura de recursos humanos', (SELECT id FROM cargos WHERE codigo = 'GER_ADM' LIMIT 1), 4, 6, 1),
('JEFE_FIN', 'Jefe de Finanzas', 'Jefatura del área financiera', (SELECT id FROM cargos WHERE codigo = 'GER_ADM' LIMIT 1), 4, 7, 1),
('SUP_PROD', 'Supervisor de Producción', 'Supervisión de procesos productivos', (SELECT id FROM cargos WHERE codigo = 'GER_OPS' LIMIT 1), 4, 8, 1),
('SUP_LOG', 'Supervisor de Logística', 'Supervisión de logística', (SELECT id FROM cargos WHERE codigo = 'GER_OPS' LIMIT 1), 4, 9, 1),
('EJE_VEN', 'Ejecutivo de Ventas', 'Ejecutivo del área comercial', (SELECT id FROM cargos WHERE codigo = 'GER_VEN' LIMIT 1), 4, 10, 1);

-- =====================================================
-- Vistas para consultas jerárquicas
-- =====================================================

-- Vista para funciones con jerarquía completa
DROP VIEW IF EXISTS `funciones_hierarchy`;
CREATE VIEW `funciones_hierarchy` AS
SELECT 
    f.id,
    f.codigo,
    f.nombre,
    f.descripcion,
    f.parent_id,
    f.nivel_jerarquico,
    f.orden_organizacional,
    f.activo,
    CASE 
        WHEN f.parent_id IS NULL THEN f.nombre
        ELSE CONCAT(
            (SELECT p.nombre FROM funciones p WHERE p.id = f.parent_id), 
            ' > ', 
            f.nombre
        )
    END as path,
    CASE 
        WHEN f.parent_id IS NULL THEN 0 
        ELSE 1 
    END as depth
FROM funciones f 
WHERE f.activo = 1
ORDER BY f.orden_organizacional, f.nivel_jerarquico, f.nombre;

-- Vista para cargos con jerarquía completa
DROP VIEW IF EXISTS `cargos_hierarchy`;
CREATE VIEW `cargos_hierarchy` AS
SELECT 
    c.id,
    c.codigo,
    c.nombre,
    c.descripcion,
    c.parent_id,
    c.nivel_jerarquico,
    c.orden_organizacional,
    c.activo,
    CASE 
        WHEN c.parent_id IS NULL THEN c.nombre
        ELSE CONCAT(
            (SELECT p.nombre FROM cargos p WHERE p.id = c.parent_id), 
            ' > ', 
            c.nombre
        )
    END as path,
    CASE 
        WHEN c.parent_id IS NULL THEN 0 
        ELSE 1 
    END as depth
FROM cargos c 
WHERE c.activo = 1
ORDER BY c.orden_organizacional, c.nivel_jerarquico, c.nombre;

-- =====================================================
-- Índices adicionales para optimización
-- =====================================================

-- Índices compuestos para mejorar consultas jerárquicas
DROP INDEX IF EXISTS `idx_funciones_hierarchy` ON `funciones`;
CREATE INDEX `idx_funciones_hierarchy` ON `funciones` (`parent_id`, `nivel_jerarquico`, `orden_organizacional`);

DROP INDEX IF EXISTS `idx_cargos_hierarchy` ON `cargos`;
CREATE INDEX `idx_cargos_hierarchy` ON `cargos` (`parent_id`, `nivel_jerarquico`, `orden_organizacional`);

DROP INDEX IF EXISTS `idx_funciones_active_order` ON `funciones`;
CREATE INDEX `idx_funciones_active_order` ON `funciones` (`activo`, `orden_organizacional`);

DROP INDEX IF EXISTS `idx_cargos_active_order` ON `cargos`;
CREATE INDEX `idx_cargos_active_order` ON `cargos` (`activo`, `orden_organizacional`);