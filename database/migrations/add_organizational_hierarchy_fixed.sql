-- =====================================================
-- Migración: Agregar Jerarquía Organizacional (Corregida)
-- Fecha: Septiembre 2025
-- Descripción: Agregar estructura padre-hijo a las tablas organizacionales
-- =====================================================

-- Agregar campos de jerarquía a la tabla funciones
ALTER TABLE `funciones` 
ADD COLUMN `parent_id` int DEFAULT NULL AFTER `codigo`,
ADD COLUMN `nivel_jerarquico` int DEFAULT 1 AFTER `parent_id`,
ADD COLUMN `orden_organizacional` int DEFAULT 0 AFTER `nivel_jerarquico`,
ADD INDEX `idx_parent_id` (`parent_id`),
ADD INDEX `idx_nivel_jerarquico` (`nivel_jerarquico`),
ADD CONSTRAINT `fk_funciones_parent` FOREIGN KEY (`parent_id`) REFERENCES `funciones` (`id`) ON DELETE SET NULL;

-- Agregar campos de jerarquía a la tabla cargos
ALTER TABLE `cargos` 
ADD COLUMN `parent_id` int DEFAULT NULL AFTER `codigo`,
ADD COLUMN `nivel_jerarquico` int DEFAULT 1 AFTER `parent_id`,
ADD COLUMN `orden_organizacional` int DEFAULT 0 AFTER `nivel_jerarquico`,
ADD INDEX `idx_cargos_parent` (`parent_id`),
ADD INDEX `idx_cargos_nivel` (`nivel_jerarquico`),
ADD CONSTRAINT `fk_cargos_parent` FOREIGN KEY (`parent_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL;

-- =====================================================
-- Limpiar datos existentes para evitar conflictos
-- =====================================================
DELETE FROM `funciones` WHERE id > 0;
DELETE FROM `cargos` WHERE id > 0;

-- Reiniciar auto_increment
ALTER TABLE `funciones` AUTO_INCREMENT = 1;
ALTER TABLE `cargos` AUTO_INCREMENT = 1;

-- =====================================================
-- Datos de ejemplo para jerarquía organizacional
-- =====================================================

-- Funciones con estructura jerárquica
INSERT INTO `funciones` (`id`, `codigo`, `nombre`, `descripcion`, `parent_id`, `nivel_jerarquico`, `orden_organizacional`, `activo`) VALUES
(1, 'DIR_GEN', 'Dirección General', 'Función de dirección general de la organización', NULL, 1, 1, 1),
(2, 'ADMIN', 'Administración', 'Función administrativa y de gestión', 1, 2, 2, 1),
(3, 'RRHH', 'Recursos Humanos', 'Gestión de recursos humanos y personal', 2, 3, 3, 1),
(4, 'FIN', 'Finanzas', 'Gestión financiera y contable', 2, 3, 4, 1),
(5, 'OPS', 'Operaciones', 'Operaciones y procesos productivos', 1, 2, 5, 1),
(6, 'PROD', 'Producción', 'Procesos de producción y manufactura', 5, 3, 6, 1),
(7, 'LOG', 'Logística', 'Logística y cadena de suministro', 5, 3, 7, 1),
(8, 'VEN', 'Ventas', 'Ventas y comercialización', 1, 2, 8, 1),
(9, 'MKT', 'Marketing', 'Marketing y promoción', 8, 3, 9, 1),
(10, 'SAC', 'Servicio al Cliente', 'Atención y servicio al cliente', 8, 3, 10, 1);

-- Cargos con estructura jerárquica
INSERT INTO `cargos` (`id`, `codigo`, `nombre`, `descripcion`, `parent_id`, `nivel_jerarquico`, `orden_organizacional`, `activo`) VALUES
(1, 'DIR_001', 'Director General', 'Máxima autoridad de la organización', NULL, 1, 1, 1),
(2, 'SUB_001', 'Subdirector', 'Subdirección general', 1, 2, 2, 1),
(3, 'GER_ADM', 'Gerente Administrativo', 'Gerencia del área administrativa', 2, 3, 3, 1),
(4, 'GER_OPS', 'Gerente de Operaciones', 'Gerencia del área operativa', 2, 3, 4, 1),
(5, 'GER_VEN', 'Gerente de Ventas', 'Gerencia del área de ventas', 2, 3, 5, 1),
(6, 'JEFE_RH', 'Jefe de Recursos Humanos', 'Jefatura de recursos humanos', 3, 4, 6, 1),
(7, 'JEFE_FIN', 'Jefe de Finanzas', 'Jefatura del área financiera', 3, 4, 7, 1),
(8, 'SUP_PROD', 'Supervisor de Producción', 'Supervisión de procesos productivos', 4, 4, 8, 1),
(9, 'SUP_LOG', 'Supervisor de Logística', 'Supervisión de logística', 4, 4, 9, 1),
(10, 'EJE_VEN', 'Ejecutivo de Ventas', 'Ejecutivo del área comercial', 5, 4, 10, 1),
(11, 'ESP_NOM', 'Especialista en Nóminas', 'Especialista en gestión de nóminas', 6, 5, 11, 1),
(12, 'CONT', 'Contador', 'Contador general', 7, 5, 12, 1),
(13, 'OP_PROD', 'Operario de Producción', 'Operario de línea de producción', 8, 5, 13, 1),
(14, 'OP_ALM', 'Operario de Almacén', 'Operario de almacén y bodega', 9, 5, 14, 1),
(15, 'VEND', 'Vendedor', 'Vendedor de campo', 10, 5, 15, 1);

-- =====================================================
-- Vistas para consultas jerárquicas
-- =====================================================

-- Vista para funciones con jerarquía completa
CREATE OR REPLACE VIEW `funciones_hierarchy` AS
WITH RECURSIVE FuncionTree AS (
    -- Nodos raíz (sin padre)
    SELECT 
        f.id,
        f.codigo,
        f.nombre,
        f.descripcion,
        f.parent_id,
        f.nivel_jerarquico,
        f.orden_organizacional,
        f.activo,
        CAST(f.nombre AS CHAR(500)) as path,
        0 as depth
    FROM funciones f 
    WHERE f.parent_id IS NULL AND f.activo = 1
    
    UNION ALL
    
    -- Nodos hijos (recursivo)
    SELECT 
        f.id,
        f.codigo,
        f.nombre,
        f.descripcion,
        f.parent_id,
        f.nivel_jerarquico,
        f.orden_organizacional,
        f.activo,
        CONCAT(ft.path, ' > ', f.nombre) as path,
        ft.depth + 1 as depth
    FROM funciones f
    INNER JOIN FuncionTree ft ON f.parent_id = ft.id
    WHERE f.activo = 1
)
SELECT * FROM FuncionTree
ORDER BY orden_organizacional, nivel_jerarquico, nombre;

-- Vista para cargos con jerarquía completa
CREATE OR REPLACE VIEW `cargos_hierarchy` AS
WITH RECURSIVE CargoTree AS (
    -- Nodos raíz (sin padre)
    SELECT 
        c.id,
        c.codigo,
        c.nombre,
        c.descripcion,
        c.parent_id,
        c.nivel_jerarquico,
        c.orden_organizacional,
        c.activo,
        CAST(c.nombre AS CHAR(500)) as path,
        0 as depth
    FROM cargos c 
    WHERE c.parent_id IS NULL AND c.activo = 1
    
    UNION ALL
    
    -- Nodos hijos (recursivo)
    SELECT 
        c.id,
        c.codigo,
        c.nombre,
        c.descripcion,
        c.parent_id,
        c.nivel_jerarquico,
        c.orden_organizacional,
        c.activo,
        CONCAT(ct.path, ' > ', c.nombre) as path,
        ct.depth + 1 as depth
    FROM cargos c
    INNER JOIN CargoTree ct ON c.parent_id = ct.id
    WHERE c.activo = 1
)
SELECT * FROM CargoTree
ORDER BY orden_organizacional, nivel_jerarquico, nombre;

-- =====================================================
-- Índices adicionales para optimización
-- =====================================================

-- Índices compuestos para mejorar consultas jerárquicas
CREATE INDEX `idx_funciones_hierarchy` ON `funciones` (`parent_id`, `nivel_jerarquico`, `orden_organizacional`);
CREATE INDEX `idx_cargos_hierarchy` ON `cargos` (`parent_id`, `nivel_jerarquico`, `orden_organizacional`);
CREATE INDEX `idx_funciones_active_order` ON `funciones` (`activo`, `orden_organizacional`);
CREATE INDEX `idx_cargos_active_order` ON `cargos` (`activo`, `orden_organizacional`);