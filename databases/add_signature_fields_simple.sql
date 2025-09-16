-- ================================================================
-- SCRIPT SIMPLE - AGREGAR SOLO CAMPOS DE FIRMAS
-- Fecha: 16 de Septiembre 2024
-- Descripción: Agrega únicamente los 4 campos de firmas sin asumir
--              posición o estructura existente
-- ================================================================

-- ⚠️ IMPORTANTE: Cambiar el nombre de la base de datos
USE planilla_test; -- Cambiar por: planilla_innova, planilla_test, etc.

-- ================================================================
-- AGREGAR CAMPOS UNO POR UNO (MÉTODO MÁS SEGURO)
-- ================================================================

-- Campo 1: firma_director_planilla
ALTER TABLE companies 
ADD COLUMN firma_director_planilla VARCHAR(255) NULL 
COMMENT 'Nombre del director para firmar reportes de planilla';

-- Campo 2: cargo_director_planilla  
ALTER TABLE companies 
ADD COLUMN cargo_director_planilla VARCHAR(255) NULL DEFAULT 'Director General' 
COMMENT 'Cargo del director para reportes';

-- Campo 3: firma_contador_planilla
ALTER TABLE companies 
ADD COLUMN firma_contador_planilla VARCHAR(255) NULL 
COMMENT 'Nombre del contador para firmar reportes de planilla';

-- Campo 4: cargo_contador_planilla
ALTER TABLE companies 
ADD COLUMN cargo_contador_planilla VARCHAR(255) NULL DEFAULT 'Contador General' 
COMMENT 'Cargo del contador para reportes';

-- ================================================================
-- CONFIGURAR DATOS POR DEFECTO
-- ================================================================

UPDATE companies SET 
    firma_director_planilla = 'Director General',
    cargo_director_planilla = 'Director General',
    firma_contador_planilla = 'Contador General', 
    cargo_contador_planilla = 'Contador General'
WHERE id = 1 
AND (firma_director_planilla IS NULL OR firma_director_planilla = '');

-- ================================================================
-- VERIFICAR RESULTADO
-- ================================================================

SELECT 'CAMPOS AGREGADOS EXITOSAMENTE' as resultado;

-- Mostrar campos agregados
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'companies' 
AND COLUMN_NAME IN (
    'firma_director_planilla', 
    'cargo_director_planilla', 
    'firma_contador_planilla', 
    'cargo_contador_planilla'
);

-- Mostrar datos configurados  
SELECT 
    id,
    company_name,
    firma_director_planilla,
    cargo_director_planilla, 
    firma_contador_planilla,
    cargo_contador_planilla
FROM companies WHERE id = 1;

-- ================================================================
-- FIN DEL SCRIPT SIMPLE
-- ================================================================