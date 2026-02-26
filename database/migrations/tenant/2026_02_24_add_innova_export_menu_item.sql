-- ============================================================================
-- Migración: Agregar menu item para Exportación INNOVA
-- Fecha: 2026-02-24
-- Descripción: Agrega el módulo de Exportación a ERP INNOVA al sistema de permisos
-- Tipo: TENANT (Aplicar a todas las bases de datos tenant)
-- ============================================================================

-- Insertar menu item para Innova Export (ID 28)
INSERT INTO menu_items (id, name, url)
VALUES (28, 'Exportar a INNOVA', 'innova-export')
ON DUPLICATE KEY UPDATE
    name = 'Exportar a INNOVA',
    url = 'innova-export';

-- Asignar permiso completo al rol ADMIN (ID 1)
INSERT INTO role_permissions (role_id, menu_id, read_perm, write_perm, delete_perm)
VALUES (1, 28, 1, 1, 1)
ON DUPLICATE KEY UPDATE
    read_perm = 1,
    write_perm = 1,
    delete_perm = 1;

-- ============================================================================
-- FIN DE MIGRACIÓN
-- ============================================================================
