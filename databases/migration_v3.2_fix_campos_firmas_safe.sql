-- ================================================================
-- MIGRACIÓN V3.2 FIX - CAMPOS DE FIRMAS SEGUROS
-- Fecha: 16 de Septiembre 2024
-- Descripción: Agrega campos de firmas verificando estructura existente
--              Sin asumir posición específica de otros campos
-- ================================================================

USE planilla_test; -- Cambia el nombre de la BD según tu entorno

-- ================================================================
-- VERIFICAR ESTRUCTURA EXISTENTE DE COMPANIES
-- ================================================================

SELECT 'ANALIZANDO ESTRUCTURA ACTUAL DE companies...' as estado;

-- Mostrar campos actuales
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'companies'
ORDER BY ORDINAL_POSITION;

-- ================================================================
-- AGREGAR CAMPOS DE FIRMAS DE FORMA SEGURA
-- ================================================================

-- 1. Verificar si firma_director_planilla ya existe
SET @campo_existe = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'companies' 
    AND COLUMN_NAME = 'firma_director_planilla'
);

-- 2. Agregar firma_director_planilla si no existe
SET @sql = IF(@campo_existe = 0, 
    'ALTER TABLE companies ADD COLUMN firma_director_planilla VARCHAR(255) NULL COMMENT "Nombre del director para firmar reportes de planilla"',
    'SELECT "Campo firma_director_planilla ya existe" as mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Verificar si cargo_director_planilla ya existe
SET @campo_existe = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'companies' 
    AND COLUMN_NAME = 'cargo_director_planilla'
);

-- 4. Agregar cargo_director_planilla si no existe
SET @sql = IF(@campo_existe = 0, 
    'ALTER TABLE companies ADD COLUMN cargo_director_planilla VARCHAR(255) NULL DEFAULT "Director General" COMMENT "Cargo del director para reportes"',
    'SELECT "Campo cargo_director_planilla ya existe" as mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Verificar si firma_contador_planilla ya existe
SET @campo_existe = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'companies' 
    AND COLUMN_NAME = 'firma_contador_planilla'
);

-- 6. Agregar firma_contador_planilla si no existe
SET @sql = IF(@campo_existe = 0, 
    'ALTER TABLE companies ADD COLUMN firma_contador_planilla VARCHAR(255) NULL COMMENT "Nombre del contador para firmar reportes de planilla"',
    'SELECT "Campo firma_contador_planilla ya existe" as mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. Verificar si cargo_contador_planilla ya existe
SET @campo_existe = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'companies' 
    AND COLUMN_NAME = 'cargo_contador_planilla'
);

-- 8. Agregar cargo_contador_planilla si no existe
SET @sql = IF(@campo_existe = 0, 
    'ALTER TABLE companies ADD COLUMN cargo_contador_planilla VARCHAR(255) NULL DEFAULT "Contador General" COMMENT "Cargo del contador para reportes"',
    'SELECT "Campo cargo_contador_planilla ya existe" as mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================================================
-- CONFIGURAR DATOS POR DEFECTO
-- ================================================================

-- Actualizar empresa con datos por defecto inteligentes
UPDATE companies SET 
    firma_director_planilla = CASE 
        WHEN firma_director_planilla IS NULL OR firma_director_planilla = '' THEN
            COALESCE(
                (SELECT legal_representative FROM companies c2 WHERE c2.id = companies.id LIMIT 1),
                'Director General'
            )
        ELSE firma_director_planilla 
    END,
    
    cargo_director_planilla = CASE 
        WHEN cargo_director_planilla IS NULL OR cargo_director_planilla = '' THEN 'Director General'
        ELSE cargo_director_planilla 
    END,
    
    firma_contador_planilla = CASE 
        WHEN firma_contador_planilla IS NULL OR firma_contador_planilla = '' THEN 'Contador General'
        ELSE firma_contador_planilla 
    END,
    
    cargo_contador_planilla = CASE 
        WHEN cargo_contador_planilla IS NULL OR cargo_contador_planilla = '' THEN 'Contador General'
        ELSE cargo_contador_planilla 
    END

WHERE id = 1;

-- ================================================================
-- VERIFICACIÓN FINAL
-- ================================================================

SELECT 'VERIFICANDO CAMPOS AGREGADOS...' as verificacion;

-- Mostrar campos de firmas
SELECT 
    COLUMN_NAME as campo,
    DATA_TYPE as tipo,
    IS_NULLABLE as acepta_null,
    COLUMN_DEFAULT as valor_defecto,
    COLUMN_COMMENT as comentario
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'companies' 
AND COLUMN_NAME LIKE '%firma%' OR COLUMN_NAME LIKE '%cargo%'
ORDER BY ORDINAL_POSITION;

-- Mostrar datos configurados
SELECT 'DATOS DE FIRMAS CONFIGURADOS:' as titulo;
SELECT 
    id,
    company_name,
    firma_director_planilla,
    cargo_director_planilla,
    firma_contador_planilla,
    cargo_contador_planilla
FROM companies 
WHERE id = 1;

-- ================================================================
-- RESULTADO
-- ================================================================

SELECT '✅ CAMPOS DE FIRMAS AGREGADOS EXITOSAMENTE' as resultado;
SELECT 'Los campos están listos para usar en reportes PDF' as detalle;

-- ================================================================
-- FIN DE MIGRACIÓN SEGURA
-- ================================================================