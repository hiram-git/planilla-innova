-- ================================================================
-- MIGRACIÓN V3.2.1 - SOLO CAMPOS DE FIRMAS PARA REPORTES PDF
-- Fecha: 16 de Septiembre 2024
-- Descripción: Migración específica para agregar únicamente los campos
--              de firmas en reportes de planilla (sin afectar organigrama)
-- ================================================================

USE planilla_innova;

-- ================================================================
-- VERIFICACIÓN PREVIA
-- ================================================================

-- Verificar si los campos ya existen antes de intentar agregarlos
SELECT 'VERIFICANDO ESTRUCTURA ACTUAL DE COMPANIES' as mensaje;

SET @firma_director_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'planilla_innova' 
    AND TABLE_NAME = 'companies' 
    AND COLUMN_NAME = 'firma_director_planilla'
);

SET @firma_contador_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'planilla_innova' 
    AND TABLE_NAME = 'companies' 
    AND COLUMN_NAME = 'firma_contador_planilla'
);

-- ================================================================
-- AGREGAR CAMPOS DE FIRMAS (solo si no existen)
-- ================================================================

-- Agregar firma_director_planilla si no existe
SET @sql = IF(@firma_director_exists = 0, 
    'ALTER TABLE companies ADD COLUMN firma_director_planilla VARCHAR(255) NULL COMMENT "Nombre del director para firmar reportes de planilla" AFTER cargo_elaborador',
    'SELECT "Campo firma_director_planilla ya existe" as mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar cargo_director_planilla si firma_director_planilla fue agregado
SET @sql = IF(@firma_director_exists = 0, 
    'ALTER TABLE companies ADD COLUMN cargo_director_planilla VARCHAR(255) NULL DEFAULT "Director General" COMMENT "Cargo del director para reportes" AFTER firma_director_planilla',
    'SELECT "Campo cargo_director_planilla ya existe o no es necesario" as mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar firma_contador_planilla si no existe
SET @sql = IF(@firma_contador_exists = 0, 
    'ALTER TABLE companies ADD COLUMN firma_contador_planilla VARCHAR(255) NULL COMMENT "Nombre del contador para firmar reportes de planilla" AFTER cargo_director_planilla',
    'SELECT "Campo firma_contador_planilla ya existe" as mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar cargo_contador_planilla si firma_contador_planilla fue agregado
SET @sql = IF(@firma_contador_exists = 0, 
    'ALTER TABLE companies ADD COLUMN cargo_contador_planilla VARCHAR(255) NULL DEFAULT "Contador General" COMMENT "Cargo del contador para reportes" AFTER firma_contador_planilla',
    'SELECT "Campo cargo_contador_planilla ya existe o no es necesario" as mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================================================
-- DATOS POR DEFECTO
-- ================================================================

-- Actualizar datos por defecto solo si los campos existen y están vacíos
UPDATE companies SET 
    firma_director_planilla = CASE 
        WHEN firma_director_planilla IS NULL OR firma_director_planilla = '' 
        THEN COALESCE(legal_representative, 'Director General') 
        ELSE firma_director_planilla 
    END,
    cargo_director_planilla = CASE 
        WHEN cargo_director_planilla IS NULL OR cargo_director_planilla = '' 
        THEN 'Director General' 
        ELSE cargo_director_planilla 
    END,
    firma_contador_planilla = CASE 
        WHEN firma_contador_planilla IS NULL OR firma_contador_planilla = '' 
        THEN 'Contador General' 
        ELSE firma_contador_planilla 
    END,
    cargo_contador_planilla = CASE 
        WHEN cargo_contador_planilla IS NULL OR cargo_contador_planilla = '' 
        THEN 'Contador General' 
        ELSE cargo_contador_planilla 
    END
WHERE id = 1;

-- ================================================================
-- VERIFICACIÓN FINAL
-- ================================================================

-- Mostrar estructura actualizada de companies
SELECT 'ESTRUCTURA FINAL DE COMPANIES:' as mensaje;
DESCRIBE companies;

-- Mostrar datos de la empresa para verificar campos de firma
SELECT 
    id,
    company_name,
    legal_representative,
    jefe_recursos_humanos,
    cargo_jefe_rrhh,
    elaborado_por,
    cargo_elaborador,
    firma_director_planilla,
    cargo_director_planilla,
    firma_contador_planilla,
    cargo_contador_planilla
FROM companies 
WHERE id = 1;

-- ================================================================
-- LOG DE MIGRACIÓN
-- ================================================================

-- Crear tabla de log si no existe
CREATE TABLE IF NOT EXISTS migration_log (
    id INT(11) NOT NULL AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL,
    descripcion TEXT,
    fecha_aplicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archivo VARCHAR(255),
    PRIMARY KEY (id),
    UNIQUE KEY unique_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registrar migración
INSERT INTO migration_log (version, descripcion, fecha_aplicacion, archivo) VALUES 
('3.2.1', 'Campos de firmas para reportes PDF (solo campos companies)', NOW(), 'migration_v3.2.1_solo_campos_firmas.sql')
ON DUPLICATE KEY UPDATE 
    descripcion = VALUES(descripcion),
    fecha_aplicacion = VALUES(fecha_aplicacion),
    archivo = VALUES(archivo);

SELECT 'MIGRACIÓN V3.2.1 COMPLETADA - CAMPOS DE FIRMAS AGREGADOS' as resultado;

-- ================================================================
-- FIN DE MIGRACIÓN V3.2.1
-- ================================================================