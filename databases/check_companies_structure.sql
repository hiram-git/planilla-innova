-- ================================================================
-- VERIFICADOR DE ESTRUCTURA - TABLA COMPANIES
-- Fecha: 16 de Septiembre 2024
-- Descripción: Analiza la estructura actual de la tabla companies
--              para determinar qué migración aplicar
-- ================================================================

-- IMPORTANTE: Cambiar el nombre de la base de datos según tu entorno
USE planilla_test; -- ⚠️ CAMBIAR POR TU BD: planilla_innova, planilla_test, etc.

SELECT '=================================================' as separador;
SELECT 'ANÁLISIS DE ESTRUCTURA DE TABLA COMPANIES' as titulo;
SELECT '=================================================' as separador;

-- ================================================================
-- 1. VERIFICAR EXISTENCIA DE TABLA COMPANIES
-- ================================================================

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ TABLA COMPANIES EXISTE'
        ELSE '❌ TABLA COMPANIES NO EXISTE'
    END as estado_tabla
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'companies';

-- ================================================================
-- 2. MOSTRAR TODOS LOS CAMPOS ACTUALES
-- ================================================================

SELECT 'ESTRUCTURA ACTUAL DE COMPANIES:' as seccion;

SELECT 
    ORDINAL_POSITION as posicion,
    COLUMN_NAME as campo,
    DATA_TYPE as tipo,
    CHARACTER_MAXIMUM_LENGTH as longitud,
    IS_NULLABLE as acepta_null,
    COLUMN_DEFAULT as valor_defecto,
    COLUMN_COMMENT as comentario
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'companies'
ORDER BY ORDINAL_POSITION;

-- ================================================================
-- 3. VERIFICAR CAMPOS RELACIONADOS CON FIRMAS
-- ================================================================

SELECT 'CAMPOS RELACIONADOS CON FIRMAS/CARGOS:' as seccion;

SELECT 
    COLUMN_NAME as campo_existente,
    DATA_TYPE as tipo,
    COLUMN_COMMENT as comentario,
    '✓ EXISTE' as estado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'companies'
AND (
    COLUMN_NAME LIKE '%firma%' OR 
    COLUMN_NAME LIKE '%cargo%' OR 
    COLUMN_NAME LIKE '%director%' OR 
    COLUMN_NAME LIKE '%contador%' OR
    COLUMN_NAME LIKE '%elaborador%' OR
    COLUMN_NAME LIKE '%jefe%' OR
    COLUMN_NAME LIKE '%representante%'
)
ORDER BY ORDINAL_POSITION;

-- ================================================================
-- 4. VERIFICAR CAMPOS ESPECÍFICOS NECESARIOS
-- ================================================================

SELECT 'VERIFICACIÓN DE CAMPOS NECESARIOS PARA FIRMAS:' as seccion;

SELECT 
    'firma_director_planilla' as campo_requerido,
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ YA EXISTE'
        ELSE '❌ FALTA - NECESARIO AGREGAR'
    END as estado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'companies' 
AND COLUMN_NAME = 'firma_director_planilla'

UNION ALL

SELECT 
    'cargo_director_planilla' as campo_requerido,
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ YA EXISTE'
        ELSE '❌ FALTA - NECESARIO AGREGAR'
    END as estado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'companies' 
AND COLUMN_NAME = 'cargo_director_planilla'

UNION ALL

SELECT 
    'firma_contador_planilla' as campo_requerido,
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ YA EXISTE'
        ELSE '❌ FALTA - NECESARIO AGREGAR'
    END as estado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'companies' 
AND COLUMN_NAME = 'firma_contador_planilla'

UNION ALL

SELECT 
    'cargo_contador_planilla' as campo_requerido,
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ YA EXISTE'
        ELSE '❌ FALTA - NECESARIO AGREGAR'
    END as estado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'companies' 
AND COLUMN_NAME = 'cargo_contador_planilla';

-- ================================================================
-- 5. DATOS ACTUALES EN COMPANIES
-- ================================================================

SELECT 'DATOS ACTUALES EN COMPANIES (ID = 1):' as seccion;

SELECT * FROM companies WHERE id = 1;

-- ================================================================
-- 6. VERIFICAR TABLA ORGANIGRAMA
-- ================================================================

SELECT 'VERIFICACIÓN DE TABLA ORGANIGRAMA:' as seccion;

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ TABLA ORGANIGRAMA EXISTE'
        ELSE '❌ TABLA ORGANIGRAMA NO EXISTE'
    END as estado_organigrama
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'organigrama';

-- Contar registros si existe
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN CONCAT('📊 ORGANIGRAMA TIENE ', COUNT(*), ' ELEMENTOS')
        ELSE '📭 ORGANIGRAMA ESTÁ VACÍO'
    END as datos_organigrama
FROM organigrama
WHERE EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'organigrama'
);

-- ================================================================
-- 7. RECOMENDACIONES
-- ================================================================

SELECT 'RECOMENDACIONES DE MIGRACIÓN:' as seccion;

-- Campos faltantes
SET @campos_firmas_faltantes = (
    SELECT 4 - COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'companies'
    AND COLUMN_NAME IN ('firma_director_planilla', 'cargo_director_planilla', 'firma_contador_planilla', 'cargo_contador_planilla')
);

-- Tabla organigrama
SET @tabla_organigrama_existe = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'organigrama'
);

SELECT 
    CASE 
        WHEN @campos_firmas_faltantes > 0 AND @tabla_organigrama_existe = 0 THEN 
            '🚀 EJECUTAR: migration_v3.2_fix_campos_firmas_safe.sql Y migration_v3.2_fix_organigrama_safe.sql'
        WHEN @campos_firmas_faltantes > 0 THEN 
            '📄 EJECUTAR: migration_v3.2_fix_campos_firmas_safe.sql'
        WHEN @tabla_organigrama_existe = 0 THEN 
            '🏢 EJECUTAR: migration_v3.2_fix_organigrama_safe.sql'
        ELSE 
            '✅ SISTEMA YA CONFIGURADO - NO SE NECESITA MIGRACIÓN'
    END as recomendacion;

SELECT '=================================================' as separador_final;
SELECT 'ANÁLISIS COMPLETADO' as fin;
SELECT 'Revisar recomendaciones arriba para siguiente paso' as instruccion;
SELECT '=================================================' as separador_final;

-- ================================================================
-- FIN DEL ANÁLISIS
-- ================================================================