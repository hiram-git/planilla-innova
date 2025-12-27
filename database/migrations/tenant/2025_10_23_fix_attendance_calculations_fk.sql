-- =====================================================
-- MIGRACIÓN: Fix Foreign Key attendance_calculations
-- Fecha: 23 de Octubre, 2025
-- Descripción: Cambiar FK de attendance_id para que apunte a attendance_detail
-- =====================================================

-- 1. Eliminar la foreign key existente
ALTER TABLE attendance_calculations
DROP FOREIGN KEY attendance_calculations_ibfk_1;

-- 2. Eliminar el índice único sobre attendance_id
ALTER TABLE attendance_calculations
DROP INDEX unique_attendance_calc;

-- 3. LIMPIAR registros huérfanos (que no tienen correspondencia en attendance_detail)
-- Primero verificamos cuántos hay
-- NOTA: SELECT comentado para evitar error PDO "pending result sets"
-- SELECT COUNT(*) as registros_huerfanos
-- FROM attendance_calculations ac
-- LEFT JOIN attendance_detail ad ON ac.attendance_id = ad.id
-- WHERE ad.id IS NULL;

-- Eliminar registros huérfanos
DELETE ac FROM attendance_calculations ac
LEFT JOIN attendance_detail ad ON ac.attendance_id = ad.id
WHERE ad.id IS NULL;

-- 4. Renombrar la columna attendance_id a attendance_detail_id para mayor claridad
ALTER TABLE attendance_calculations
CHANGE COLUMN attendance_id attendance_detail_id INT NOT NULL COMMENT 'FK a tabla attendance_detail';

-- 5. Agregar nueva foreign key apuntando a attendance_detail
ALTER TABLE attendance_calculations
ADD CONSTRAINT fk_attendance_detail
FOREIGN KEY (attendance_detail_id) REFERENCES attendance_detail(id) ON DELETE CASCADE;

-- 6. Agregar índice único sobre attendance_detail_id
ALTER TABLE attendance_calculations
ADD UNIQUE KEY unique_attendance_detail_calc (attendance_detail_id);

-- Verificación
SELECT
    'Migración completada exitosamente' as mensaje,
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'attendance_calculations'
AND CONSTRAINT_NAME = 'fk_attendance_detail';
