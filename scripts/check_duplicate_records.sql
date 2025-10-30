-- Script para diagnosticar registros duplicados en attendance_records

-- 1. Ver total de registros en attendance_records
SELECT
    COUNT(*) as total_records,
    COUNT(DISTINCT record_hash) as unique_hashes,
    COUNT(*) - COUNT(DISTINCT record_hash) as duplicate_count
FROM attendance_records;

-- 2. Ver registros por fecha
SELECT
    punch_date,
    COUNT(*) as total_records,
    COUNT(DISTINCT employee_id) as unique_employees
FROM attendance_records
GROUP BY punch_date
ORDER BY punch_date DESC
LIMIT 10;

-- 3. Ver si hay registros sin procesar
SELECT
    is_processed,
    is_duplicate,
    COUNT(*) as count
FROM attendance_records
GROUP BY is_processed, is_duplicate;

-- 4. Ver hashes duplicados
SELECT
    record_hash,
    COUNT(*) as count,
    GROUP_CONCAT(id) as record_ids,
    MIN(timestamp) as first_timestamp,
    MAX(timestamp) as last_timestamp
FROM attendance_records
GROUP BY record_hash
HAVING COUNT(*) > 1
LIMIT 10;

-- 5. Ver últimos 5 registros creados
SELECT
    id,
    employee_id,
    timestamp,
    punch_type,
    is_processed,
    is_duplicate,
    created_at
FROM attendance_records
ORDER BY created_at DESC
LIMIT 5;
