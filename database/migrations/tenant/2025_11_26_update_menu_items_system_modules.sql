-- ============================================================================
-- Migración: Actualización Sistema de Módulos y Permisos
-- Fecha: 2025-11-26
-- Descripción: Sincroniza menu_items con el listado actualizado de módulos
--              del sistema desde Role.php. Agrega campos icon y description,
--              y actualiza nombres y URLs a formato MVC.
-- Tipo: TENANT (Aplicar a todas las bases de datos tenant)
-- ============================================================================

-- 1. Agregar nuevas columnas a menu_items (si no existen)
-- Verifica existencia de cada columna antes de agregar
SET @sql_icon = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'icon') = 0,
    'ALTER TABLE menu_items ADD COLUMN icon VARCHAR(100) DEFAULT NULL COMMENT ''Ícono FontAwesome del módulo''',
    'SELECT ''Column icon already exists'' AS msg'
);
PREPARE stmt FROM @sql_icon;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql_description = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'description') = 0,
    'ALTER TABLE menu_items ADD COLUMN description VARCHAR(255) DEFAULT NULL COMMENT ''Descripción del módulo''',
    'SELECT ''Column description already exists'' AS msg'
);
PREPARE stmt FROM @sql_description;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql_status = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'status') = 0,
    'ALTER TABLE menu_items ADD COLUMN status TINYINT(1) DEFAULT 1 COMMENT ''Estado del módulo (1=activo, 0=inactivo)''',
    'SELECT ''Column status already exists'' AS msg'
);
PREPARE stmt FROM @sql_status;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql_display_order = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'display_order') = 0,
    'ALTER TABLE menu_items ADD COLUMN display_order INT DEFAULT 0 COMMENT ''Orden de visualización''',
    'SELECT ''Column display_order already exists'' AS msg'
);
PREPARE stmt FROM @sql_display_order;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql_created_at = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'created_at') = 0,
    'ALTER TABLE menu_items ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'SELECT ''Column created_at already exists'' AS msg'
);
PREPARE stmt FROM @sql_created_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql_updated_at = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'updated_at') = 0,
    'ALTER TABLE menu_items ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT ''Column updated_at already exists'' AS msg'
);
PREPARE stmt FROM @sql_updated_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Actualizar registros existentes con nueva información
UPDATE menu_items SET
    name = 'Dashboard',
    url = 'dashboard',
    icon = 'fas fa-tachometer-alt',
    description = 'Vista general del sistema',
    display_order = 1
WHERE id = 1;

UPDATE menu_items SET
    name = 'Empresa',
    url = 'company',
    icon = 'fas fa-building',
    description = 'Configuración de la empresa',
    display_order = 2
WHERE id = 2;

UPDATE menu_items SET
    name = 'Posiciones',
    url = 'positions',
    icon = 'fas fa-chair',
    description = 'Gestión de posiciones',
    display_order = 3
WHERE id = 3;

UPDATE menu_items SET
    name = 'Partidas',
    url = 'partidas',
    icon = 'fas fa-money-check-alt',
    description = 'Gestión de partidas presupuestarias',
    display_order = 4
WHERE id = 4;

UPDATE menu_items SET
    name = 'Organigrama',
    url = 'organigrama',
    icon = 'fas fa-sitemap',
    description = 'Visualización de estructura organizacional',
    display_order = 5
WHERE id = 5;

UPDATE menu_items SET
    name = 'Cargos',
    url = 'cargos',
    icon = 'fas fa-briefcase',
    description = 'Catálogo de cargos',
    display_order = 6
WHERE id = 6;

UPDATE menu_items SET
    name = 'Funciones',
    url = 'funciones',
    icon = 'fas fa-tasks',
    description = 'Catálogo de funciones',
    display_order = 7
WHERE id = 7;

UPDATE menu_items SET
    name = 'Empleados',
    url = 'employees',
    icon = 'fas fa-users',
    description = 'Gestión de expedientes de empleados',
    display_order = 8
WHERE id = 8;

UPDATE menu_items SET
    name = 'Horas Extras',
    url = 'overtime',
    icon = 'fas fa-clock',
    description = 'Registro y aprobación de horas extras',
    display_order = 9
WHERE id = 9;

UPDATE menu_items SET
    name = 'Horarios',
    url = 'schedules',
    icon = 'fas fa-calendar-alt',
    description = 'Gestión de turnos y horarios',
    display_order = 10
WHERE id = 10;

UPDATE menu_items SET
    name = 'Asistencia',
    url = 'attendance',
    icon = 'fas fa-user-clock',
    description = 'Control de marcaciones y asistencia',
    display_order = 11
WHERE id = 11;

UPDATE menu_items SET
    name = 'Acreedores',
    url = 'creditors',
    icon = 'fas fa-hand-holding-usd',
    description = 'Gestión de descuentos y acreedores',
    display_order = 12
WHERE id = 12;

UPDATE menu_items SET
    name = 'Planillas',
    url = 'payrolls',
    icon = 'fas fa-file-invoice-dollar',
    description = 'Procesamiento de pagos',
    display_order = 13
WHERE id = 13;

UPDATE menu_items SET
    name = 'Conceptos',
    url = 'concepts',
    icon = 'fas fa-calculator',
    description = 'Configuración de ingresos y deducciones',
    display_order = 14
WHERE id = 14;

UPDATE menu_items SET
    name = 'Tipos de Planilla',
    url = 'tipos-planilla',
    icon = 'fas fa-list-alt',
    description = 'Configuración de tipos de nómina',
    display_order = 15
WHERE id = 15;

-- 3. Insertar o actualizar módulo Usuarios (ID 16)
INSERT INTO menu_items (id, name, url, icon, description, status, display_order)
VALUES (16, 'Usuarios', 'users', 'fas fa-user-cog', 'Administración de usuarios del sistema', 1, 16)
ON DUPLICATE KEY UPDATE
    name = 'Usuarios',
    url = 'users',
    icon = 'fas fa-user-cog',
    description = 'Administración de usuarios del sistema',
    display_order = 16;

-- 4. Actualizar resto de módulos existentes
UPDATE menu_items SET
    name = 'Roles y Permisos',
    url = 'roles',
    icon = 'fas fa-user-shield',
    description = 'Gestión de acceso y seguridad',
    display_order = 17
WHERE id = 17;

UPDATE menu_items SET
    name = 'Acumulados',
    url = 'acumulados',
    icon = 'fas fa-piggy-bank',
    description = 'Control de pasivos laborales',
    display_order = 18
WHERE id = 18;

UPDATE menu_items SET
    name = 'Tipos de Acumulados',
    url = 'tipos-acumulados',
    icon = 'fas fa-coins',
    description = 'Configuración de tipos de acumulados',
    display_order = 19
WHERE id = 19;

UPDATE menu_items SET
    name = 'Frecuencias',
    url = 'frecuencias',
    icon = 'fas fa-history',
    description = 'Configuración de periodicidad de pagos',
    display_order = 20
WHERE id = 20;

UPDATE menu_items SET
    name = 'Situaciones',
    url = 'situaciones',
    icon = 'fas fa-info-circle',
    description = 'Estados y situaciones de empleados',
    display_order = 21
WHERE id = 21;

UPDATE menu_items SET
    name = 'Liquidaciones',
    url = 'liquidation',
    icon = 'fas fa-door-open',
    description = 'Cálculo de prestaciones por retiro',
    display_order = 22
WHERE id = 22;

UPDATE menu_items SET
    name = 'Vacaciones',
    url = 'vacation',
    icon = 'fas fa-umbrella-beach',
    description = 'Control y saldo de vacaciones',
    display_order = 23
WHERE id = 23;

UPDATE menu_items SET
    name = 'Calendario',
    url = 'business-calendar',
    icon = 'fas fa-calendar-day',
    description = 'Días hábiles y feriados',
    display_order = 24
WHERE id = 24;

UPDATE menu_items SET
    name = 'Reportes',
    url = 'reports',
    icon = 'fas fa-chart-bar',
    description = 'Informes y estadísticas',
    display_order = 25
WHERE id = 25;

-- 5. Agregar índices para optimizar consultas
ALTER TABLE menu_items
ADD INDEX idx_status (status),
ADD INDEX idx_display_order (display_order);

-- ============================================================================
-- Verificación final
-- ============================================================================
-- SELECT
--     id,
--     name,
--     url,
--     icon,
--     description,
--     display_order,
--     status
-- FROM menu_items
-- ORDER BY display_order;

-- ============================================================================
-- Notas:
-- - Esta migración actualiza la tabla menu_items para sincronizarla con
--   el sistema de módulos definido en app/Models/Role.php
-- - Se agregan campos icon y description para enriquecer la información
-- - Se actualizan URLs a formato MVC sin extensión .php
-- - Se normaliza el módulo de Conceptos (antes separado en asignaciones/deducciones)
-- - Se agrega módulo Usuarios (ID 16) que faltaba en algunos tenants
-- - Compatible con sistema multitenancy
-- ============================================================================
