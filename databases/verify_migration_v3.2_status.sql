-- ================================================================
-- SCRIPT DE VERIFICACIÓN V3.2 - ESTADO DE MIGRACIÓN
-- Fecha: 16 de Septiembre 2024
-- Descripción: Verifica que todas las migraciones v3.2 estén aplicadas correctamente
--              y que el sistema esté listo para reportes PDF con firmas
-- ================================================================

USE planilla_innova;

SELECT '================================================' as separador;
SELECT 'VERIFICACIÓN DE ESTADO DE MIGRACIÓN V3.2' as titulo;
SELECT '================================================' as separador;

-- ================================================================
-- 1. VERIFICACIÓN DE TABLAS PRINCIPALES
-- ================================================================

SELECT '1. VERIFICANDO EXISTENCIA DE TABLAS...' as seccion;

SELECT 
    TABLE_NAME as tabla,
    CASE 
        WHEN TABLE_NAME = 'companies' THEN '✓ EXISTE'
        WHEN TABLE_NAME = 'organigrama' THEN '✓ EXISTE'
        WHEN TABLE_NAME = 'migration_log' THEN '✓ EXISTE'
        ELSE '✗ FALTA'
    END as estado,
    TABLE_COMMENT as comentario
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'planilla_innova' 
AND TABLE_NAME IN ('companies', 'organigrama', 'migration_log')
UNION ALL
SELECT 
    'companies' as tabla,
    '✗ FALTA' as estado,
    'Tabla principal no encontrada' as comentario
WHERE NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = 'planilla_innova' AND TABLE_NAME = 'companies'
)
UNION ALL
SELECT 
    'organigrama' as tabla,
    '✗ FALTA' as estado,
    'Tabla de estructura organizacional no encontrada' as comentario
WHERE NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = 'planilla_innova' AND TABLE_NAME = 'organigrama'
)
ORDER BY tabla;

-- ================================================================
-- 2. VERIFICACIÓN DE CAMPOS DE FIRMAS EN COMPANIES
-- ================================================================

SELECT '2. VERIFICANDO CAMPOS DE FIRMAS EN COMPANIES...' as seccion;

SELECT 
    COLUMN_NAME as campo,
    DATA_TYPE as tipo,
    IS_NULLABLE as permite_null,
    COLUMN_DEFAULT as valor_defecto,
    CASE 
        WHEN COLUMN_NAME IN ('firma_director_planilla', 'cargo_director_planilla', 'firma_contador_planilla', 'cargo_contador_planilla') 
        THEN '✓ CORRECTO'
        ELSE '? REVISAR'
    END as estado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'planilla_innova' 
AND TABLE_NAME = 'companies' 
AND (COLUMN_NAME LIKE '%firma%' OR COLUMN_NAME LIKE '%cargo%')
AND COLUMN_NAME NOT IN ('legal_representative', 'jefe_recursos_humanos', 'cargo_jefe_rrhh', 'elaborado_por', 'cargo_elaborador')
ORDER BY ORDINAL_POSITION;

-- Verificar si faltan campos críticos
SELECT 
    'firma_director_planilla' as campo_requerido,
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ EXISTE'
        ELSE '✗ FALTA'
    END as estado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'planilla_innova' 
AND TABLE_NAME = 'companies' 
AND COLUMN_NAME = 'firma_director_planilla'

UNION ALL

SELECT 
    'firma_contador_planilla' as campo_requerido,
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ EXISTE'
        ELSE '✗ FALTA'
    END as estado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'planilla_innova' 
AND TABLE_NAME = 'companies' 
AND COLUMN_NAME = 'firma_contador_planilla';

-- ================================================================
-- 3. VERIFICACIÓN DE DATOS EN COMPANIES
-- ================================================================

SELECT '3. VERIFICANDO DATOS DE FIRMAS EN EMPRESA...' as seccion;

SELECT 
    id,
    company_name,
    CASE 
        WHEN firma_director_planilla IS NOT NULL AND firma_director_planilla != '' 
        THEN CONCAT('✓ ', firma_director_planilla)
        ELSE '✗ SIN CONFIGURAR'
    END as director_planilla,
    CASE 
        WHEN cargo_director_planilla IS NOT NULL AND cargo_director_planilla != '' 
        THEN CONCAT('✓ ', cargo_director_planilla)
        ELSE '✗ SIN CONFIGURAR'
    END as cargo_director,
    CASE 
        WHEN firma_contador_planilla IS NOT NULL AND firma_contador_planilla != '' 
        THEN CONCAT('✓ ', firma_contador_planilla)
        ELSE '✗ SIN CONFIGURAR'
    END as contador_planilla,
    CASE 
        WHEN cargo_contador_planilla IS NOT NULL AND cargo_contador_planilla != '' 
        THEN CONCAT('✓ ', cargo_contador_planilla)
        ELSE '✗ SIN CONFIGURAR'
    END as cargo_contador
FROM companies 
WHERE id = 1;

-- ================================================================
-- 4. VERIFICACIÓN DE ESTRUCTURA ORGANIZACIONAL
-- ================================================================

SELECT '4. VERIFICANDO ESTRUCTURA ORGANIZACIONAL...' as seccion;

-- Verificar tabla organigrama
SELECT 
    'Tabla organigrama' as verificacion,
    CASE 
        WHEN COUNT(*) > 0 THEN CONCAT('✓ EXISTE con ', COUNT(*), ' elementos')
        ELSE '✗ VACÍA O NO EXISTE'
    END as estado
FROM organigrama;

-- Verificar integridad de jerarquía
SELECT 
    'Integridad jerárquica' as verificacion,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ SIN ELEMENTOS HUÉRFANOS'
        ELSE CONCAT('✗ ', COUNT(*), ' ELEMENTOS HUÉRFANOS')
    END as estado
FROM organigrama o
WHERE o.id_padre IS NOT NULL 
AND NOT EXISTS (SELECT 1 FROM organigrama p WHERE p.id = o.id_padre);

-- Mostrar estructura actual
SELECT 
    o.id,
    o.descripcion,
    o.nivel,
    COALESCE(p.descripcion, 'RAÍZ') as padre,
    o.path as ruta
FROM organigrama o
LEFT JOIN organigrama p ON o.id_padre = p.id
ORDER BY o.nivel, o.id;

-- ================================================================
-- 5. VERIFICACIÓN DE MIGRACIONES APLICADAS
-- ================================================================

SELECT '5. VERIFICANDO LOG DE MIGRACIONES...' as seccion;

SELECT 
    version,
    descripcion,
    fecha_aplicacion,
    archivo,
    CASE 
        WHEN version LIKE '3.2%' THEN '✓ MIGRACIÓN V3.2'
        ELSE '• OTRA VERSIÓN'
    END as tipo
FROM migration_log 
ORDER BY fecha_aplicacion DESC;

-- ================================================================
-- 6. RESUMEN DE VERIFICACIÓN
-- ================================================================

SELECT '6. RESUMEN DE VERIFICACIÓN...' as seccion;

-- Contar elementos correctos
SET @campos_firmas = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'planilla_innova' 
    AND TABLE_NAME = 'companies' 
    AND COLUMN_NAME IN ('firma_director_planilla', 'cargo_director_planilla', 'firma_contador_planilla', 'cargo_contador_planilla')
);

SET @datos_empresa = (
    SELECT COUNT(*)
    FROM companies 
    WHERE id = 1 
    AND firma_director_planilla IS NOT NULL 
    AND firma_contador_planilla IS NOT NULL
);

SET @tabla_organigrama = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = 'planilla_innova' 
    AND TABLE_NAME = 'organigrama'
);

SET @elementos_organigrama = (SELECT COUNT(*) FROM organigrama);

-- Resultado final
SELECT 
    'RESUMEN DE VERIFICACIÓN V3.2' as componente,
    CASE 
        WHEN @campos_firmas = 4 AND @datos_empresa = 1 AND @tabla_organigrama = 1 AND @elementos_organigrama > 0
        THEN '✅ SISTEMA COMPLETAMENTE CONFIGURADO'
        WHEN @campos_firmas = 4 AND @datos_empresa = 1
        THEN '⚠️ FIRMAS OK - VERIFICAR ORGANIGRAMA'
        WHEN @tabla_organigrama = 1 AND @elementos_organigrama > 0
        THEN '⚠️ ORGANIGRAMA OK - VERIFICAR FIRMAS'
        ELSE '❌ MIGRACIÓN INCOMPLETA'
    END as estado_final;

-- Detalles específicos
SELECT 'DETALLES DE VERIFICACIÓN:' as detalle;
SELECT CONCAT('Campos de firmas: ', @campos_firmas, '/4') as campos;
SELECT CONCAT('Datos empresa configurados: ', @datos_empresa, '/1') as empresa;
SELECT CONCAT('Tabla organigrama: ', @tabla_organigrama, '/1') as tabla_org;
SELECT CONCAT('Elementos organizacionales: ', @elementos_organigrama) as elementos;

-- ================================================================
-- RECOMENDACIONES
-- ================================================================

SELECT '7. RECOMENDACIONES...' as seccion;

SELECT 
    CASE 
        WHEN @campos_firmas < 4 THEN 'EJECUTAR: migration_v3.2.1_solo_campos_firmas.sql'
        WHEN @datos_empresa = 0 THEN 'CONFIGURAR: Nombres de firmas en tabla companies'
        WHEN @tabla_organigrama = 0 THEN 'EJECUTAR: migration_v3.2.2_estructura_organizacional.sql'
        WHEN @elementos_organigrama = 0 THEN 'INSERTAR: Datos organizacionales por defecto'
        ELSE '✅ SISTEMA LISTO PARA REPORTES PDF CON FIRMAS'
    END as recomendacion;

SELECT '================================================' as separador_final;
SELECT 'VERIFICACIÓN COMPLETADA' as fin;
SELECT '================================================' as separador_final;

-- ================================================================
-- FIN DE VERIFICACIÓN V3.2
-- ================================================================