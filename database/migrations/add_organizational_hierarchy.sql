-- =====================================================
-- Migración: Agregar Jerarquía Organizacional
-- Fecha: Septiembre 2025
-- Descripción: Agregar estructura padre-hijo a las tablas organizacionales
-- =====================================================

-- Agregar campos de jerarquía a la tabla funciones
ALTER TABLE `funciones` 
ADD COLUMN `parent_id` int DEFAULT NULL AFTER `codigo`,
ADD COLUMN `nivel_jerarquico` int DEFAULT 1 AFTER `parent_id`,
ADD COLUMN `orden_organizacional` int DEFAULT 0 AFTER `nivel_jerarquico`,
ADD COLUMN `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `descripcion`,
ADD INDEX `idx_parent_id` (`parent_id`),
ADD INDEX `idx_nivel_jerarquico` (`nivel_jerarquico`),
ADD CONSTRAINT `fk_funciones_parent` FOREIGN KEY (`parent_id`) REFERENCES `funciones` (`id`) ON DELETE SET NULL;

-- Agregar campos de jerarquía a la tabla cargos
ALTER TABLE `cargos` 
ADD COLUMN `parent_id` int DEFAULT NULL AFTER `codigo`,
ADD COLUMN `nivel_jerarquico` int DEFAULT 1 AFTER `parent_id`,
ADD COLUMN `orden_organizacional` int DEFAULT 0 AFTER `nivel_jerarquico`,
ADD COLUMN `nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `descripcion`,
ADD INDEX `idx_cargos_parent` (`parent_id`),
ADD INDEX `idx_cargos_nivel` (`nivel_jerarquico`),
ADD CONSTRAINT `fk_cargos_parent` FOREIGN KEY (`parent_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL;

-- =====================================================
-- Datos de ejemplo para jerarquía organizacional
-- =====================================================

-- Funciones con estructura jerárquica
INSERT INTO `funciones` (`id`, `descripcion`, `codigo`, `nombre`, `parent_id`, `nivel_jerarquico`, `orden_organizacional`, `active`) VALUES
(1, 'Dirección General', 'DIR_GEN', 'Dirección General', NULL, 1, 1, 1),
(2, 'Administración', 'ADMIN', 'Administración', 1, 2, 2, 1),
(3, 'Recursos Humanos', 'RRHH', 'Recursos Humanos', 2, 3, 3, 1),
(4, 'Finanzas', 'FIN', 'Finanzas', 2, 3, 4, 1),
(5, 'Operaciones', 'OPS', 'Operaciones', 1, 2, 5, 1),
(6, 'Producción', 'PROD', 'Producción', 5, 3, 6, 1),
(7, 'Logística', 'LOG', 'Logística', 5, 3, 7, 1),
(8, 'Ventas', 'VEN', 'Ventas', 1, 2, 8, 1),
(9, 'Marketing', 'MKT', 'Marketing', 8, 3, 9, 1),
(10, 'Servicio al Cliente', 'SAC', 'Servicio al Cliente', 8, 3, 10, 1)
ON DUPLICATE KEY UPDATE 
    `nombre` = VALUES(`nombre`),
    `parent_id` = VALUES(`parent_id`),
    `nivel_jerarquico` = VALUES(`nivel_jerarquico`),
    `orden_organizacional` = VALUES(`orden_organizacional`);

-- Cargos con estructura jerárquica
INSERT INTO `cargos` (`id`, `descripcion`, `codigo`, `nombre`, `parent_id`, `nivel_jerarquico`, `orden_organizacional`, `active`) VALUES
(1, 'Director General', 'DIR_001', 'Director General', NULL, 1, 1, 1),
(2, 'Subdirector', 'SUB_001', 'Subdirector', 1, 2, 2, 1),
(3, 'Gerente Administrativo', 'GER_ADM', 'Gerente Administrativo', 2, 3, 3, 1),
(4, 'Gerente de Operaciones', 'GER_OPS', 'Gerente de Operaciones', 2, 3, 4, 1),
(5, 'Gerente de Ventas', 'GER_VEN', 'Gerente de Ventas', 2, 3, 5, 1),
(6, 'Jefe de RRHH', 'JEFE_RH', 'Jefe de Recursos Humanos', 3, 4, 6, 1),
(7, 'Jefe de Finanzas', 'JEFE_FIN', 'Jefe de Finanzas', 3, 4, 7, 1),
(8, 'Supervisor de Producción', 'SUP_PROD', 'Supervisor de Producción', 4, 4, 8, 1),
(9, 'Supervisor de Logística', 'SUP_LOG', 'Supervisor de Logística', 4, 4, 9, 1),
(10, 'Ejecutivo de Ventas', 'EJE_VEN', 'Ejecutivo de Ventas', 5, 4, 10, 1),
(11, 'Especialista en Nóminas', 'ESP_NOM', 'Especialista en Nóminas', 6, 5, 11, 1),
(12, 'Contador', 'CONT', 'Contador', 7, 5, 12, 1),
(13, 'Operario de Producción', 'OP_PROD', 'Operario de Producción', 8, 5, 13, 1),
(14, 'Operario de Almacén', 'OP_ALM', 'Operario de Almacén', 9, 5, 14, 1),
(15, 'Vendedor', 'VEND', 'Vendedor', 10, 5, 15, 1)
ON DUPLICATE KEY UPDATE 
    `nombre` = VALUES(`nombre`),
    `parent_id` = VALUES(`parent_id`),
    `nivel_jerarquico` = VALUES(`nivel_jerarquico`),
    `orden_organizacional` = VALUES(`orden_organizacional`);

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
        f.active,
        CAST(f.nombre AS CHAR(500)) as path,
        0 as depth
    FROM funciones f 
    WHERE f.parent_id IS NULL AND f.active = 1
    
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
        f.active,
        CONCAT(ft.path, ' > ', f.nombre) as path,
        ft.depth + 1 as depth
    FROM funciones f
    INNER JOIN FuncionTree ft ON f.parent_id = ft.id
    WHERE f.active = 1
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
        c.active,
        CAST(c.nombre AS CHAR(500)) as path,
        0 as depth
    FROM cargos c 
    WHERE c.parent_id IS NULL AND c.active = 1
    
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
        c.active,
        CONCAT(ct.path, ' > ', c.nombre) as path,
        ct.depth + 1 as depth
    FROM cargos c
    INNER JOIN CargoTree ct ON c.parent_id = ct.id
    WHERE c.active = 1
)
SELECT * FROM CargoTree
ORDER BY orden_organizacional, nivel_jerarquico, nombre;

-- =====================================================
-- Funciones almacenadas para consultas jerárquicas
-- =====================================================

DELIMITER $$

-- Función para obtener el path completo de una función
CREATE FUNCTION GetFunctionPath(func_id INT) RETURNS TEXT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE current_id INT DEFAULT func_id;
    DECLARE parent_id INT;
    DECLARE func_name VARCHAR(100);
    DECLARE path_result TEXT DEFAULT '';
    
    -- Cursor para navegar hacia arriba en la jerarquía
    DECLARE cur CURSOR FOR 
        SELECT parent_id, nombre 
        FROM funciones 
        WHERE id = current_id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Construir el path desde el nodo actual hasta la raíz
    WHILE current_id IS NOT NULL AND NOT done DO
        OPEN cur;
        FETCH cur INTO parent_id, func_name;
        
        IF NOT done THEN
            IF path_result = '' THEN
                SET path_result = func_name;
            ELSE
                SET path_result = CONCAT(func_name, ' > ', path_result);
            END IF;
            SET current_id = parent_id;
        END IF;
        
        CLOSE cur;
        SET done = (current_id IS NULL);
    END WHILE;
    
    RETURN path_result;
END$$

-- Función para obtener el path completo de un cargo
CREATE FUNCTION GetCargoPath(cargo_id INT) RETURNS TEXT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE current_id INT DEFAULT cargo_id;
    DECLARE parent_id INT;
    DECLARE cargo_name VARCHAR(100);
    DECLARE path_result TEXT DEFAULT '';
    
    -- Cursor para navegar hacia arriba en la jerarquía
    DECLARE cur CURSOR FOR 
        SELECT parent_id, nombre 
        FROM cargos 
        WHERE id = current_id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Construir el path desde el nodo actual hasta la raíz
    WHILE current_id IS NOT NULL AND NOT done DO
        OPEN cur;
        FETCH cur INTO parent_id, cargo_name;
        
        IF NOT done THEN
            IF path_result = '' THEN
                SET path_result = cargo_name;
            ELSE
                SET path_result = CONCAT(cargo_name, ' > ', path_result);
            END IF;
            SET current_id = parent_id;
        END IF;
        
        CLOSE cur;
        SET done = (current_id IS NULL);
    END WHILE;
    
    RETURN path_result;
END$$

DELIMITER ;

-- =====================================================
-- Índices adicionales para optimización
-- =====================================================

-- Índices compuestos para mejorar consultas jerárquicas
CREATE INDEX `idx_funciones_hierarchy` ON `funciones` (`parent_id`, `nivel_jerarquico`, `orden_organizacional`);
CREATE INDEX `idx_cargos_hierarchy` ON `cargos` (`parent_id`, `nivel_jerarquico`, `orden_organizacional`);
CREATE INDEX `idx_funciones_active_order` ON `funciones` (`active`, `orden_organizacional`);
CREATE INDEX `idx_cargos_active_order` ON `cargos` (`active`, `orden_organizacional`);