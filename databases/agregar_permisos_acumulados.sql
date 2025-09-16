-- ================================================================
-- AGREGAR MÓDULO ACUMULADOS A ROLES Y PERMISOS
-- Fecha: 15 de Septiembre 2025
-- Descripción: Configura permisos para el módulo de acumulados
-- ================================================================

USE planilla_innova;

-- ================================================================
-- 1. AGREGAR ITEM DE MENÚ PARA ACUMULADOS
-- ================================================================

-- Insertar items de menú para acumulados
INSERT IGNORE INTO `menu_items` (`id`, `name`, `url`) VALUES 
(18, 'Acumulados', 'panel/acumulados'),
(19, 'Tipos de Acumulados', 'panel/tipos-acumulados');

-- Obtener los IDs de los menús
SET @menu_acumulados_id = 18;
SET @menu_tipos_acumulados_id = 19;

-- ================================================================
-- 2. AGREGAR PERMISOS DE RUTAS PARA ACUMULADOS
-- ================================================================

-- Insertar permisos de rutas para el módulo de acumulados
INSERT IGNORE INTO `route_permissions` (`route`, `menu_id`, `permission_type`, `description`) VALUES 
-- Rutas principales de acumulados (menu_id = 18)
('panel/acumulados', @menu_acumulados_id, 'read', 'Ver módulo de acumulados'),
('panel/acumulados/index', @menu_acumulados_id, 'read', 'Ver resumen de acumulados'),
('panel/acumulados/byEmployee', @menu_acumulados_id, 'read', 'Ver acumulados por empleado'),
('panel/acumulados/byType', @menu_acumulados_id, 'read', 'Ver acumulados por tipo'),
('panel/acumulados/allEmployees', @menu_acumulados_id, 'read', 'Ver acumulados desglosados por empleados'),
('panel/acumulados/*/byPayroll', @menu_acumulados_id, 'read', 'Ver acumulados por planilla específica'),

-- Permisos de exportación
('panel/acumulados/export', @menu_acumulados_id, 'read', 'Exportar acumulados a CSV'),

-- Permisos para tipos de acumulados (administración) (menu_id = 19)
('panel/tipos-acumulados', @menu_tipos_acumulados_id, 'read', 'Ver tipos de acumulados'),
('panel/tipos-acumulados/create', @menu_tipos_acumulados_id, 'write', 'Crear tipos de acumulados'),
('panel/tipos-acumulados/*/edit', @menu_tipos_acumulados_id, 'write', 'Editar tipos de acumulados'),
('panel/tipos-acumulados/*/delete', @menu_tipos_acumulados_id, 'delete', 'Eliminar tipos de acumulados'),
('panel/tipos-acumulados/toggle-status', @menu_tipos_acumulados_id, 'write', 'Activar/desactivar tipos de acumulados');

-- ================================================================
-- 3. ASIGNAR PERMISOS A ROLES EXISTENTES
-- ================================================================

-- Obtener IDs de roles existentes
SET @role_super_admin_id = (SELECT id FROM roles WHERE name = 'super_admin' LIMIT 1);
SET @role_admin_id = (SELECT id FROM roles WHERE name = 'admin' LIMIT 1);
SET @role_hr_manager_id = (SELECT id FROM roles WHERE name = 'hr_manager' LIMIT 1);
SET @role_user_id = (SELECT id FROM roles WHERE name = 'user' LIMIT 1);

-- SUPER ADMIN: Acceso total a ambos módulos
INSERT IGNORE INTO `role_permissions` (`role_id`, `menu_id`, `read_perm`, `write_perm`, `delete_perm`) VALUES 
(@role_super_admin_id, @menu_acumulados_id, 1, 1, 1),
(@role_super_admin_id, @menu_tipos_acumulados_id, 1, 1, 1);

-- ADMIN: Acceso completo a ambos módulos
INSERT IGNORE INTO `role_permissions` (`role_id`, `menu_id`, `read_perm`, `write_perm`, `delete_perm`) VALUES 
(@role_admin_id, @menu_acumulados_id, 1, 1, 1),
(@role_admin_id, @menu_tipos_acumulados_id, 1, 1, 1);

-- HR MANAGER: Solo lectura de acumulados, sin acceso a tipos
INSERT IGNORE INTO `role_permissions` (`role_id`, `menu_id`, `read_perm`, `write_perm`, `delete_perm`) VALUES 
(@role_hr_manager_id, @menu_acumulados_id, 1, 0, 0);

-- USER: Solo lectura básica de acumulados
INSERT IGNORE INTO `role_permissions` (`role_id`, `menu_id`, `read_perm`, `write_perm`, `delete_perm`) VALUES 
(@role_user_id, @menu_acumulados_id, 1, 0, 0);

-- ================================================================
-- 4. ACTUALIZAR PERMISOS JSON EN TABLA ROLES (si se usa)
-- ================================================================

-- Actualizar permisos JSON para super_admin
UPDATE `roles` SET 
    `permissions` = JSON_SET(
        COALESCE(`permissions`, '{}'),
        '$.acumulados', JSON_ARRAY('create', 'read', 'update', 'delete'),
        '$.tipos_acumulados', JSON_ARRAY('create', 'read', 'update', 'delete')
    )
WHERE `name` = 'super_admin';

-- Actualizar permisos JSON para admin
UPDATE `roles` SET 
    `permissions` = JSON_SET(
        COALESCE(`permissions`, '{}'),
        '$.acumulados', JSON_ARRAY('create', 'read', 'update', 'delete'),
        '$.tipos_acumulados', JSON_ARRAY('create', 'read', 'update', 'delete')
    )
WHERE `name` = 'admin';

-- Actualizar permisos JSON para hr_manager
UPDATE `roles` SET 
    `permissions` = JSON_SET(
        COALESCE(`permissions`, '{}'),
        '$.acumulados', JSON_ARRAY('read'),
        '$.tipos_acumulados', JSON_ARRAY('read')
    )
WHERE `name` = 'hr_manager';

-- Actualizar permisos JSON para user
UPDATE `roles` SET 
    `permissions` = JSON_SET(
        COALESCE(`permissions`, '{}'),
        '$.acumulados', JSON_ARRAY('read')
    )
WHERE `name` = 'user';

-- ================================================================
-- 5. AGREGAR PERMISOS ESPECÍFICOS AL RouteHelper
-- ================================================================

-- Nota: Los permisos también deben agregarse en el archivo RouteHelper.php
-- para que funcionen correctamente con el middleware de permisos

-- ================================================================
-- 6. VERIFICACIÓN DE CONFIGURACIÓN
-- ================================================================

-- Mostrar configuración de menús
SELECT 'ITEMS DE MENÚ CONFIGURADOS:' as mensaje;
SELECT id, name, url FROM menu_items WHERE url LIKE '%acumulados%';

-- Mostrar permisos de rutas configurados
SELECT 'PERMISOS DE RUTAS CONFIGURADOS:' as mensaje;
SELECT rp.route, rp.permission_type, rp.description, mi.name as menu_name
FROM route_permissions rp
INNER JOIN menu_items mi ON rp.menu_id = mi.id
WHERE mi.url LIKE '%acumulados%'
ORDER BY rp.route;

-- Mostrar permisos por rol
SELECT 'PERMISOS POR ROL:' as mensaje;
SELECT 
    r.name as rol,
    mi.name as menu,
    rpr.read_perm as lectura,
    rpr.write_perm as escritura,
    rpr.delete_perm as eliminacion
FROM role_permissions rpr
INNER JOIN roles r ON rpr.role_id = r.id
INNER JOIN menu_items mi ON rpr.menu_id = mi.id
WHERE mi.url LIKE '%acumulados%'
ORDER BY r.name, mi.name;

-- Mostrar permisos JSON
SELECT 'PERMISOS JSON EN ROLES:' as mensaje;
SELECT 
    name as rol,
    JSON_EXTRACT(permissions, '$.acumulados') as permisos_acumulados,
    JSON_EXTRACT(permissions, '$.tipos_acumulados') as permisos_tipos_acumulados
FROM roles 
WHERE permissions IS NOT NULL;

SELECT CONCAT(
    'CONFIGURACIÓN DE PERMISOS ACUMULADOS COMPLETADA - ', 
    NOW(), 
    ' - Módulo agregado a roles y permisos'
) as estado_configuracion;