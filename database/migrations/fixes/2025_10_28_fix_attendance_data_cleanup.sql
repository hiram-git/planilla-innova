-- =====================================================
-- MIGRACIÓN FIX: Limpieza de Datos de Asistencias
-- Versión: v3.5.1
-- Fecha: 28 de Octubre, 2025
-- Descripción: Correcciones de datos para sistema de asistencias
-- =====================================================
-- IMPORTANTE: Este script debe ejecutarse en producción después de:
--   1. Hacer backup completo de la base de datos
--   2. Verificar que no hay sincronizaciones en curso
--   3. Tener ventana de mantenimiento aprobada
-- =====================================================

USE planilla_prod;

-- =====================================================
-- PASO 0: VERIFICACIONES PRE-EJECUCIÓN
-- =====================================================

-- Verificar versión MySQL (debe ser 8.0+)
SELECT
    'Verificando versión MySQL...' as paso,
    VERSION() as version;

-- Verificar que las tablas existen
SELECT
    'Verificando tablas requeridas...' as paso,
    COUNT(*) as tablas_encontradas
FROM information_schema.tables
WHERE table_schema = 'planilla_prod'
  AND table_name IN ('attendance_header', 'attendance_detail', 'employees', 'attendance_raw_data');

-- Verificar estado actual antes de cambios
SELECT 'Estado actual - attendance_header' as tabla, COUNT(*) as total_registros FROM attendance_header
UNION ALL
SELECT 'Estado actual - attendance_detail', COUNT(*) FROM attendance_detail
UNION ALL
SELECT 'Estado actual - employees', COUNT(*) FROM employees;

-- =====================================================
-- PASO 1: BACKUP DE DATOS (Crear tablas de respaldo)
-- =====================================================

-- Crear tabla de respaldo de employees (solo los que vamos a modificar)
DROP TABLE IF EXISTS employees_backup_20251028;
CREATE TABLE employees_backup_20251028 AS
SELECT * FROM employees
WHERE id IN (2, 3, 5) AND situacion_id = 1;

SELECT 'Backup creado - employees_backup_20251028' as paso, COUNT(*) as registros FROM employees_backup_20251028;

-- Crear tabla de respaldo de attendance_header con valores incorrectos
DROP TABLE IF EXISTS attendance_header_backup_20251028;
CREATE TABLE attendance_header_backup_20251028 AS
SELECT * FROM attendance_header
WHERE synced_from NOT IN ('API', 'FILE', 'MANUAL') OR synced_from IS NULL OR synced_from = '';

SELECT 'Backup creado - attendance_header_backup_20251028' as paso, COUNT(*) as registros FROM attendance_header_backup_20251028;

-- Crear tabla de respaldo de attendance_detail con registros inválidos
DROP TABLE IF EXISTS attendance_detail_backup_20251028;
CREATE TABLE attendance_detail_backup_20251028 AS
SELECT * FROM attendance_detail
WHERE time_in IS NULL AND time_out IS NULL;

SELECT 'Backup creado - attendance_detail_backup_20251028' as paso, COUNT(*) as registros FROM attendance_detail_backup_20251028;

-- =====================================================
-- PASO 2: CORRECCIÓN DE EMAILS DE EMPLEADOS
-- =====================================================

SELECT '=== INICIANDO CORRECCIÓN DE EMAILS ===' as paso;

-- Actualizar emails de empleados activos (solo si existen)
UPDATE employees
SET email = 'soporte4nms@gmail.com'
WHERE id = 2
  AND situacion_id = 1
  AND email != 'soporte4nms@gmail.com';

UPDATE employees
SET email = 'nmolina@nmspanama.com'
WHERE id = 3
  AND situacion_id = 1
  AND email != 'nmolina@nmspanama.com';

UPDATE employees
SET email = 'dilsaquintana@gmail.com'
WHERE id = 5
  AND situacion_id = 1
  AND email != 'dilsaquintana@gmail.com';

-- Verificar correcciones
SELECT 'Emails actualizados:' as resultado;
SELECT id, firstname, lastname, email, situacion_id
FROM employees
WHERE id IN (2, 3, 5) AND situacion_id = 1;

-- =====================================================
-- PASO 3: LIMPIEZA DE VALORES INCORRECTOS EN synced_from
-- =====================================================

SELECT '=== INICIANDO CORRECCIÓN DE synced_from ===' as paso;

-- Ver valores actuales antes de corregir
SELECT
    'Valores synced_from antes de corrección:' as info,
    synced_from,
    COUNT(*) as total
FROM attendance_header
GROUP BY synced_from;

-- Corregir valores vacíos o NULL
UPDATE attendance_header
SET synced_from = 'API'
WHERE synced_from = '' OR synced_from IS NULL;

-- Verificar que no haya valores incorrectos después de la corrección
SELECT
    'Valores synced_from después de corrección:' as info,
    synced_from,
    COUNT(*) as total
FROM attendance_header
GROUP BY synced_from;

-- =====================================================
-- PASO 4: LIMPIEZA DE REGISTROS INVÁLIDOS
-- =====================================================

SELECT '=== INICIANDO LIMPIEZA DE REGISTROS INVÁLIDOS ===' as paso;

-- Ver registros inválidos antes de eliminar
SELECT
    'Registros con time_in y time_out NULL:' as info,
    COUNT(*) as total
FROM attendance_detail
WHERE time_in IS NULL AND time_out IS NULL;

-- Eliminar registros inválidos (time_in y time_out NULL)
-- Nota: El backup ya fue creado en PASO 1
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM attendance_detail
WHERE time_in IS NULL AND time_out IS NULL;
SET FOREIGN_KEY_CHECKS = 1;

-- Verificar eliminación
SELECT
    'Registros inválidos después de limpieza:' as info,
    COUNT(*) as total
FROM attendance_detail
WHERE time_in IS NULL AND time_out IS NULL;

-- =====================================================
-- PASO 5: MARCAR REGISTROS RAW DUPLICADOS COMO PROCESADOS
-- =====================================================

SELECT '=== INICIANDO MARCADO DE REGISTROS RAW DUPLICADOS ===' as paso;

-- Ver estado actual de registros raw
SELECT
    'Estado registros raw antes:' as info,
    processed,
    COUNT(*) as total
FROM attendance_raw_data
GROUP BY processed;

-- Marcar como procesados los registros de empleados que ya existen
-- (para evitar reprocesarlos innecesariamente)
UPDATE attendance_raw_data
SET processed = 1,
    processed_at = NOW()
WHERE processed = 0
  AND JSON_EXTRACT(raw_json, '$.employee_email') IN (
    SELECT CONCAT('"', email, '"')
    FROM employees
    WHERE email IN (
        'hiram_loreto@yahoo.com',
        'soporte4nms@gmail.com',
        'dilsaquintana@gmail.com',
        'nmolina@nmspanama.com'
    )
    AND situacion_id = 1
  );

-- Ver estado después de marcado
SELECT
    'Estado registros raw después:' as info,
    processed,
    COUNT(*) as total
FROM attendance_raw_data
GROUP BY processed;

-- =====================================================
-- PASO 6: RECALCULAR ESTADÍSTICAS DE CABECERAS
-- =====================================================

SELECT '=== RECALCULANDO ESTADÍSTICAS DE CABECERAS ===' as paso;

-- Actualizar estadísticas de cada cabecera basándose en sus detalles
UPDATE attendance_header h
SET
    h.total_records = (
        SELECT COUNT(*)
        FROM attendance_detail d
        WHERE d.header_id = h.id
    ),
    h.total_employees = (
        SELECT COUNT(DISTINCT employee_id)
        FROM attendance_detail d
        WHERE d.header_id = h.id
    ),
    h.total_on_time = (
        SELECT COUNT(*)
        FROM attendance_detail d
        WHERE d.header_id = h.id
          AND d.status = 'PRESENT'
          AND d.is_late = 0
    ),
    h.total_late = (
        SELECT COUNT(*)
        FROM attendance_detail d
        WHERE d.header_id = h.id
          AND (d.status = 'LATE' OR (d.status = 'PRESENT' AND d.is_late = 1))
    ),
    h.total_absent = (
        SELECT COUNT(*)
        FROM attendance_detail d
        WHERE d.header_id = h.id
          AND d.status = 'ABSENT'
    );

-- Verificar estadísticas actualizadas
SELECT
    'Estadísticas actualizadas:' as info,
    COUNT(*) as total_cabeceras,
    SUM(total_records) as total_marcaciones,
    SUM(total_employees) as suma_empleados
FROM attendance_header;

-- =====================================================
-- PASO 7: VALIDACIONES POST-EJECUCIÓN
-- =====================================================

SELECT '=== VALIDACIONES FINALES ===' as paso;

-- Validación 1: No debe haber valores incorrectos en synced_from
SELECT
    'Validación synced_from (debe ser 0):' as validacion,
    COUNT(*) as registros_incorrectos
FROM attendance_header
WHERE synced_from NOT IN ('API', 'FILE', 'MANUAL');

-- Validación 2: No debe haber registros inválidos en attendance_detail
SELECT
    'Validación registros inválidos (debe ser 0):' as validacion,
    COUNT(*) as registros_invalidos
FROM attendance_detail
WHERE time_in IS NULL AND time_out IS NULL;

-- Validación 3: Verificar que los emails fueron actualizados
SELECT
    'Validación emails actualizados:' as validacion,
    COUNT(*) as emails_correctos
FROM employees
WHERE (id = 2 AND email = 'soporte4nms@gmail.com')
   OR (id = 3 AND email = 'nmolina@nmspanama.com')
   OR (id = 5 AND email = 'dilsaquintana@gmail.com');

-- Validación 4: Verificar integridad referencial
SELECT
    'Validación integridad (debe ser 0):' as validacion,
    COUNT(*) as registros_huerfanos
FROM attendance_detail d
LEFT JOIN attendance_header h ON d.header_id = h.id
WHERE h.id IS NULL;

-- =====================================================
-- PASO 8: RESUMEN FINAL
-- =====================================================

SELECT '=== RESUMEN DE MIGRACIÓN ===' as paso;

SELECT
    'Cabeceras totales' as metrica,
    COUNT(*) as valor
FROM attendance_header
UNION ALL
SELECT
    'Detalles totales',
    COUNT(*)
FROM attendance_detail
UNION ALL
SELECT
    'Empleados con marcaciones',
    COUNT(DISTINCT employee_id)
FROM attendance_detail
UNION ALL
SELECT
    'Registros raw procesados',
    COUNT(*)
FROM attendance_raw_data
WHERE processed = 1
UNION ALL
SELECT
    'Registros raw pendientes',
    COUNT(*)
FROM attendance_raw_data
WHERE processed = 0;

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================
--
-- TABLAS DE BACKUP CREADAS (conservar por 30 días):
--   - employees_backup_20251028
--   - attendance_header_backup_20251028
--   - attendance_detail_backup_20251028
--
-- ROLLBACK (en caso de problemas):
--   1. Restaurar employees:
--      UPDATE e SET e.email = b.email
--      FROM employees e
--      INNER JOIN employees_backup_20251028 b ON e.id = b.id;
--
--   2. Restaurar attendance_header:
--      UPDATE h SET h.synced_from = b.synced_from
--      FROM attendance_header h
--      INNER JOIN attendance_header_backup_20251028 b ON h.id = b.id;
--
--   3. Restaurar attendance_detail:
--      INSERT INTO attendance_detail
--      SELECT * FROM attendance_detail_backup_20251028;
--
-- =====================================================
-- FIN DE LA MIGRACIÓN
-- =====================================================

SELECT 'MIGRACIÓN COMPLETADA EXITOSAMENTE' as estado, NOW() as fecha_hora;
