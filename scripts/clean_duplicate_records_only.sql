-- =====================================================
-- Script para limpiar SOLO registros duplicados
-- Mantiene un registro por cada hash único
-- =====================================================

USE planilla_prod;

-- 1. Ver cuántos duplicados hay antes de limpiar
SELECT
    'Antes de la limpieza' as estado,
    COUNT(*) as total_records,
    COUNT(DISTINCT record_hash) as unique_hashes,
    COUNT(*) - COUNT(DISTINCT record_hash) as duplicates_to_delete
FROM attendance_records;

-- 2. Eliminar duplicados (mantener el registro más antiguo de cada hash)
DELETE ar1 FROM attendance_records ar1
INNER JOIN attendance_records ar2
WHERE ar1.record_hash = ar2.record_hash
  AND ar1.id > ar2.id;

-- 3. Verificar después de la limpieza
SELECT
    'Después de la limpieza' as estado,
    COUNT(*) as total_records,
    COUNT(DISTINCT record_hash) as unique_hashes,
    COUNT(*) - COUNT(DISTINCT record_hash) as remaining_duplicates
FROM attendance_records;

-- 4. Marcar registros duplicados si quedan algunos
CALL sp_detect_duplicates();

SELECT 'Limpieza de duplicados completada' as status;
