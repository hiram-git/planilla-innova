-- =====================================================
-- SCRIPT DE VERIFICACIÓN: Sistema de Asistencias
-- Versión: v3.5.1
-- Fecha: 28 de Octubre, 2025
-- Uso: Ejecutar ANTES y DESPUÉS del deployment
-- =====================================================

USE planilla_prod;

SELECT '============================================' as '';
SELECT '  VERIFICACIÓN SISTEMA DE ASISTENCIAS' as '';
SELECT '============================================' as '';
SELECT NOW() as fecha_verificacion;

-- =====================================================
-- SECCIÓN 1: INFORMACIÓN DEL SISTEMA
-- =====================================================

SELECT '' as '';
SELECT '=== INFORMACIÓN DEL SISTEMA ===' as seccion;

SELECT 'Versión MySQL' as info, VERSION() as valor
UNION ALL
SELECT 'Base de datos', DATABASE()
UNION ALL
SELECT 'Fecha actual', NOW();

-- =====================================================
-- SECCIÓN 2: ESTADO DE TABLAS PRINCIPALES
-- =====================================================

SELECT '' as '';
SELECT '=== ESTADO DE TABLAS PRINCIPALES ===' as seccion;

SELECT
    table_name as tabla,
    table_rows as filas_aprox,
    ROUND(data_length / 1024 / 1024, 2) as tamano_mb
FROM information_schema.tables
WHERE table_schema = 'planilla_prod'
  AND table_name IN (
    'attendance_header',
    'attendance_detail',
    'attendance_raw_data',
    'attendance_sync_log',
    'employees'
  )
ORDER BY table_name;

-- =====================================================
-- SECCIÓN 3: VERIFICACIÓN DE VALORES synced_from
-- =====================================================

SELECT '' as '';
SELECT '=== VALORES synced_from (CRÍTICO) ===' as seccion;

SELECT
    synced_from,
    COUNT(*) as total,
    MIN(attendance_date) as fecha_minima,
    MAX(attendance_date) as fecha_maxima
FROM attendance_header
GROUP BY synced_from
ORDER BY total DESC;

-- ⚠️ ALERTA: Esta consulta debe retornar 0
SELECT '' as '';
SELECT '⚠️ VALORES INCORRECTOS EN synced_from (debe ser 0):' as alerta;
SELECT COUNT(*) as total_incorrectos
FROM attendance_header
WHERE synced_from NOT IN ('API', 'FILE', 'MANUAL')
   OR synced_from IS NULL
   OR synced_from = '';

-- =====================================================
-- SECCIÓN 4: VERIFICACIÓN DE EMAILS DE EMPLEADOS
-- =====================================================

SELECT '' as '';
SELECT '=== EMAILS DE EMPLEADOS CRÍTICOS ===' as seccion;

SELECT
    id,
    firstname,
    lastname,
    email,
    situacion_id,
    CASE
        WHEN id = 2 AND email = 'soporte4nms@gmail.com' THEN '✓ CORRECTO'
        WHEN id = 3 AND email = 'nmolina@nmspanama.com' THEN '✓ CORRECTO'
        WHEN id = 5 AND email = 'dilsaquintana@gmail.com' THEN '✓ CORRECTO'
        WHEN id = 8 AND email = 'hiram_loreto@yahoo.com' THEN '✓ CORRECTO'
        ELSE '✗ INCORRECTO'
    END as estado
FROM employees
WHERE id IN (2, 3, 5, 8)
ORDER BY id;

-- =====================================================
-- SECCIÓN 5: REGISTROS INVÁLIDOS
-- =====================================================

SELECT '' as '';
SELECT '=== REGISTROS INVÁLIDOS (CRÍTICO) ===' as seccion;

-- ⚠️ ALERTA: Esta consulta debe retornar 0
SELECT '⚠️ Detalles con time_in y time_out NULL (debe ser 0):' as alerta;
SELECT COUNT(*) as total_invalidos
FROM attendance_detail
WHERE time_in IS NULL AND time_out IS NULL;

-- Registros con solo time_in (normales durante el día)
SELECT 'Detalles solo con time_in (marcaciones pendientes):' as info;
SELECT COUNT(*) as total_pendientes
FROM attendance_detail
WHERE time_in IS NOT NULL AND time_out IS NULL;

-- =====================================================
-- SECCIÓN 6: ESTADÍSTICAS GENERALES
-- =====================================================

SELECT '' as '';
SELECT '=== ESTADÍSTICAS GENERALES ===' as seccion;

SELECT
    'Total cabeceras' as metrica,
    COUNT(*) as valor
FROM attendance_header
UNION ALL
SELECT
    'Total detalles',
    COUNT(*)
FROM attendance_detail
UNION ALL
SELECT
    'Empleados únicos con marcaciones',
    COUNT(DISTINCT employee_id)
FROM attendance_detail
UNION ALL
SELECT
    'Registros raw total',
    COUNT(*)
FROM attendance_raw_data
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
-- SECCIÓN 7: RANGO DE FECHAS
-- =====================================================

SELECT '' as '';
SELECT '=== RANGO DE FECHAS DE ASISTENCIAS ===' as seccion;

SELECT
    MIN(attendance_date) as fecha_inicial,
    MAX(attendance_date) as fecha_final,
    DATEDIFF(MAX(attendance_date), MIN(attendance_date)) + 1 as dias_totales,
    COUNT(*) as dias_con_marcaciones,
    ROUND(COUNT(*) * 100.0 / (DATEDIFF(MAX(attendance_date), MIN(attendance_date)) + 1), 2) as porcentaje_cobertura
FROM attendance_header;

-- =====================================================
-- SECCIÓN 8: ÚLTIMAS SINCRONIZACIONES
-- =====================================================

SELECT '' as '';
SELECT '=== ÚLTIMAS 5 SINCRONIZACIONES ===' as seccion;

SELECT
    id,
    sync_type,
    status,
    records_fetched as obtenidos,
    records_inserted as insertados,
    records_updated as actualizados,
    errors_count as errores,
    DATE_FORMAT(start_time, '%Y-%m-%d %H:%i') as inicio,
    duration_seconds as duracion_seg
FROM attendance_sync_log
ORDER BY start_time DESC
LIMIT 5;

-- =====================================================
-- SECCIÓN 9: EMPLEADOS CON MÁS MARCACIONES
-- =====================================================

SELECT '' as '';
SELECT '=== TOP 10 EMPLEADOS CON MÁS MARCACIONES ===' as seccion;

SELECT
    e.id,
    e.firstname,
    e.lastname,
    e.email,
    COUNT(d.id) as total_marcaciones,
    MIN(h.attendance_date) as primera_marcacion,
    MAX(h.attendance_date) as ultima_marcacion
FROM attendance_detail d
INNER JOIN employees e ON d.employee_id = e.id
INNER JOIN attendance_header h ON d.header_id = h.id
GROUP BY e.id, e.firstname, e.lastname, e.email
ORDER BY total_marcaciones DESC
LIMIT 10;

-- =====================================================
-- SECCIÓN 10: INTEGRIDAD REFERENCIAL
-- =====================================================

SELECT '' as '';
SELECT '=== VERIFICACIÓN DE INTEGRIDAD REFERENCIAL ===' as seccion;

-- ⚠️ ALERTA: Todas estas consultas deben retornar 0
SELECT '⚠️ Detalles huérfanos (sin header):' as alerta;
SELECT COUNT(*) as total_huerfanos
FROM attendance_detail d
LEFT JOIN attendance_header h ON d.header_id = h.id
WHERE h.id IS NULL;

SELECT '⚠️ Detalles con empleado inexistente:' as alerta;
SELECT COUNT(*) as total_invalidos
FROM attendance_detail d
LEFT JOIN employees e ON d.employee_id = e.id
WHERE e.id IS NULL;

-- =====================================================
-- SECCIÓN 11: ESTADO DE BACKUPS (POST-DEPLOYMENT)
-- =====================================================

SELECT '' as '';
SELECT '=== TABLAS DE BACKUP (solo post-deployment) ===' as seccion;

SELECT
    table_name as tabla_backup,
    table_rows as registros_respaldados,
    create_time as fecha_creacion
FROM information_schema.tables
WHERE table_schema = 'planilla_prod'
  AND table_name LIKE '%backup_20251028'
ORDER BY table_name;

-- =====================================================
-- RESUMEN FINAL
-- =====================================================

SELECT '' as '';
SELECT '============================================' as '';
SELECT '         RESUMEN DE VERIFICACIÓN' as '';
SELECT '============================================' as '';

SELECT
    CASE
        WHEN (
            SELECT COUNT(*)
            FROM attendance_header
            WHERE synced_from NOT IN ('API', 'FILE', 'MANUAL')
        ) = 0
        AND (
            SELECT COUNT(*)
            FROM attendance_detail
            WHERE time_in IS NULL AND time_out IS NULL
        ) = 0
        AND (
            SELECT COUNT(*)
            FROM attendance_detail d
            LEFT JOIN attendance_header h ON d.header_id = h.id
            WHERE h.id IS NULL
        ) = 0
        THEN '✅ SISTEMA SALUDABLE - TODAS LAS VERIFICACIONES PASARON'
        ELSE '⚠️ ADVERTENCIA - HAY PROBLEMAS QUE REQUIEREN ATENCIÓN'
    END as estado_sistema;

SELECT NOW() as fecha_verificacion_completada;

-- =====================================================
-- FIN DEL SCRIPT DE VERIFICACIÓN
-- =====================================================
