-- =====================================================
-- SCRIPT: Marcar usuario como Super Administrador
-- Fecha: 2026-01-26
-- IMPORTANTE: Ejecutar DESPUÉS de la migración 2026_01_26_add_is_system_admin_to_admin.sql
-- =====================================================

-- INSTRUCCIONES:
-- 1. Primero ejecutar la migración: 2026_01_26_add_is_system_admin_to_admin.sql
-- 2. Luego ejecutar este script, modificando el username del super admin
-- 3. Solo UN usuario debe tener is_system_admin = 1

-- =====================================================
-- PASO 1: Verificar usuarios existentes
-- =====================================================
SELECT id, username, firstname, lastname, role_id, is_system_admin
FROM admin
WHERE status = 1
ORDER BY id;

-- =====================================================
-- PASO 2: Marcar usuario como Super Admin
-- CAMBIAR 'admin' por el username real del super administrador
-- =====================================================

-- Opción A: Marcar por USERNAME (recomendado)
UPDATE admin
SET is_system_admin = 1
WHERE username = 'admin'  -- CAMBIAR ESTE USERNAME
  AND status = 1;

-- Opción B: Marcar por ID (si conoces el ID exacto)
-- UPDATE admin
-- SET is_system_admin = 1
-- WHERE id = 1  -- CAMBIAR ESTE ID
--   AND status = 1;

-- =====================================================
-- PASO 3: Verificar que solo hay UN super admin
-- =====================================================
SELECT id, username, firstname, lastname, role_id, is_system_admin
FROM admin
WHERE is_system_admin = 1
  AND status = 1;

-- RESULTADO ESPERADO: Solo 1 fila

-- =====================================================
-- PASO 4 (OPCIONAL): Si necesitas quitar super admin a alguien
-- =====================================================
-- UPDATE admin
-- SET is_system_admin = 0
-- WHERE username = 'usuario_a_quitar_privilegio';

-- =====================================================
-- NOTAS IMPORTANTES:
-- =====================================================
-- * Solo debe existir UN usuario con is_system_admin = 1
-- * Este usuario podrá acceder al sistema incluso con licencia expirada
-- * La contraseña del super admin no podrá ser modificada desde la interfaz
-- * El super admin no podrá ser eliminado o desactivado desde la interfaz
-- * El super admin tendrá un badge rojo "SUPER ADMIN" visible en el listado
-- =====================================================
