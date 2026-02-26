-- ============================================================================
-- Migración: Agregar menu item para Aprobación de Horas Extras
-- Fecha: 2026-02-02
-- Descripción: Agrega el módulo de Aprobación de Horas Extras al sistema de permisos
-- Tipo: TENANT (Aplicar a todas las bases de datos tenant)
-- ============================================================================

-- Insertar menu item para Overtime Approvals (ID 27)
INSERT INTO menu_items (id, name, url)
VALUES (27, 'Aprobación Horas Extras', 'overtime-approvals')
ON DUPLICATE KEY UPDATE
    name = 'Aprobación Horas Extras',
    url = 'overtime-approvals';

-- Asignar permiso completo al rol ADMIN (ID 1)
INSERT INTO role_permissions (role_id, menu_id, read_perm, write_perm, delete_perm)
VALUES (1, 27, 1, 1, 1)
ON DUPLICATE KEY UPDATE
    read_perm = 1,
    write_perm = 1,
    delete_perm = 1;

-- ============================================================================
-- FIN DE MIGRACIÓN
-- ============================================================================
