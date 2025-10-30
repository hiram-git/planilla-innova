-- =====================================================
-- Script para resetear datos de sincronización
-- ADVERTENCIA: Esto eliminará TODOS los datos de sincronización
-- =====================================================

USE planilla_prod;

-- Confirmar antes de ejecutar
SELECT 'ADVERTENCIA: Este script eliminará todos los datos de sincronización.' as mensaje;
SELECT 'Presiona CTRL+C para cancelar, o continúa para proceder.' as accion;

-- 1. Limpiar attendance_records (capa intermedia)
DELETE FROM attendance_records;
ALTER TABLE attendance_records AUTO_INCREMENT = 1;

-- 2. Limpiar attendance_raw_data
DELETE FROM attendance_raw_data;
ALTER TABLE attendance_raw_data AUTO_INCREMENT = 1;

-- 3. Opcional: Limpiar logs de sincronización antiguos (mantener últimos 5)
DELETE FROM attendance_sync_log
WHERE id NOT IN (
    SELECT id FROM (
        SELECT id FROM attendance_sync_log
        ORDER BY start_time DESC
        LIMIT 5
    ) as keep_logs
);

-- 4. Verificar limpieza
SELECT 'Limpieza completada' as status;
SELECT
    (SELECT COUNT(*) FROM attendance_records) as records_count,
    (SELECT COUNT(*) FROM attendance_raw_data) as raw_data_count,
    (SELECT COUNT(*) FROM attendance_sync_log) as sync_log_count;

-- NOTA: Este script NO elimina attendance_detail ni attendance_header
-- porque esos son los datos consolidados ya procesados
