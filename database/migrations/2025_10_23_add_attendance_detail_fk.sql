-- =====================================================
-- MIGRACIÓN: Agregar Foreign Key a attendance_detail
-- Fecha: 23 de Octubre, 2025
-- Descripción: Agregar FK de attendance_detail_id a attendance_detail
-- =====================================================

-- 1. Verificar registros huérfanos
SELECT COUNT(*) as registros_huerfanos
FROM attendance_calculations ac
LEFT JOIN attendance_detail ad ON ac.attendance_detail_id = ad.id
WHERE ad.id IS NULL;

-- 2. Eliminar registros huérfanos si existen
DELETE ac FROM attendance_calculations ac
LEFT JOIN attendance_detail ad ON ac.attendance_detail_id = ad.id
WHERE ad.id IS NULL;

-- 3. Agregar foreign key apuntando a attendance_detail
ALTER TABLE attendance_calculations
ADD CONSTRAINT fk_attendance_detail
FOREIGN KEY (attendance_detail_id) REFERENCES attendance_detail(id) ON DELETE CASCADE;

-- 4. Agregar índice único sobre attendance_detail_id
ALTER TABLE attendance_calculations
ADD UNIQUE KEY unique_attendance_detail_calc (attendance_detail_id);

-- Verificación final
SELECT
    'Migración completada' as mensaje,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'attendance_calculations'
AND CONSTRAINT_NAME = 'fk_attendance_detail';
