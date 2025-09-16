-- ================================================================
-- SCRIPT MAESTRO DE MIGRACIÓN V3.2 - EJECUCIÓN COMPLETA
-- Fecha: 16 de Septiembre 2024
-- Descripción: Ejecuta todas las migraciones v3.2 en orden correcto
--              para sistemas de reportes PDF con firmas + organigrama
-- ================================================================

-- IMPORTANTE: Ejecutar este script completo o los scripts individuales según necesidad:
-- 1. migration_v3.2_reportes_pdf_firmas.sql (COMPLETO - incluye todo)
-- 2. migration_v3.2.1_solo_campos_firmas.sql (SOLO campos companies)
-- 3. migration_v3.2.2_estructura_organizacional.sql (SOLO organigrama)

USE planilla_innova;

-- ================================================================
-- INFORMACIÓN PREVIA
-- ================================================================

SELECT '========================================' as separador;
SELECT 'INICIANDO MIGRACIÓN V3.2 COMPLETA' as titulo;
SELECT 'Fecha y hora:', NOW() as timestamp;
SELECT '========================================' as separador;

-- Mostrar estado actual de la base de datos
SELECT 'ESTADO ACTUAL DE LA BASE DE DATOS:' as mensaje;
SELECT TABLE_NAME, TABLE_COMMENT 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'planilla_innova' 
AND TABLE_NAME IN ('companies', 'organigrama', 'migration_log')
ORDER BY TABLE_NAME;

-- ================================================================
-- PASO 1: CREAR TABLA DE LOG DE MIGRACIONES
-- ================================================================

SELECT 'PASO 1: Creando tabla de log de migraciones...' as paso;

CREATE TABLE IF NOT EXISTS migration_log (
    id INT(11) NOT NULL AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL,
    descripcion TEXT,
    fecha_aplicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archivo VARCHAR(255),
    PRIMARY KEY (id),
    UNIQUE KEY unique_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Log de migraciones aplicadas al sistema';

-- ================================================================
-- PASO 2: CAMPOS DE FIRMAS EN COMPANIES
-- ================================================================

SELECT 'PASO 2: Agregando campos de firmas en tabla companies...' as paso;

-- Verificar existencia de campos
SET @firma_director_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'planilla_innova' 
    AND TABLE_NAME = 'companies' 
    AND COLUMN_NAME = 'firma_director_planilla'
);

-- Agregar campos solo si no existen
SET @sql = IF(@firma_director_exists = 0, 
    'ALTER TABLE companies 
     ADD COLUMN firma_director_planilla VARCHAR(255) NULL COMMENT "Nombre del director para firmar reportes de planilla" AFTER cargo_elaborador,
     ADD COLUMN cargo_director_planilla VARCHAR(255) NULL DEFAULT "Director General" COMMENT "Cargo del director para reportes" AFTER firma_director_planilla,
     ADD COLUMN firma_contador_planilla VARCHAR(255) NULL COMMENT "Nombre del contador para firmar reportes de planilla" AFTER cargo_director_planilla,
     ADD COLUMN cargo_contador_planilla VARCHAR(255) NULL DEFAULT "Contador General" COMMENT "Cargo del contador para reportes" AFTER firma_contador_planilla',
    'SELECT "Campos de firmas ya existen en companies" as mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Datos por defecto
UPDATE companies SET 
    firma_director_planilla = COALESCE(firma_director_planilla, legal_representative, 'Director General'),
    cargo_director_planilla = COALESCE(cargo_director_planilla, 'Director General'),
    firma_contador_planilla = COALESCE(firma_contador_planilla, 'Contador General'),
    cargo_contador_planilla = COALESCE(cargo_contador_planilla, 'Contador General')
WHERE id = 1;

SELECT 'Campos de firmas configurados en companies' as resultado_paso2;

-- ================================================================
-- PASO 3: TABLA ORGANIGRAMA
-- ================================================================

SELECT 'PASO 3: Configurando estructura organizacional...' as paso;

-- Crear tabla organigrama
CREATE TABLE IF NOT EXISTS organigrama (
    id INT(11) NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(255) NOT NULL COMMENT 'Nombre del elemento organizacional',
    id_padre INT(11) NULL DEFAULT NULL COMMENT 'ID del elemento padre (NULL para raíz)',
    path VARCHAR(500) NULL DEFAULT NULL COMMENT 'Ruta jerárquica completa',
    nivel INT(11) DEFAULT 0 COMMENT 'Nivel en la jerarquía (0 = raíz)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_organigrama_padre (id_padre),
    INDEX idx_organigrama_path (path),
    INDEX idx_organigrama_nivel (nivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Estructura organizacional de la empresa';

-- Configurar AUTO_INCREMENT
ALTER TABLE organigrama AUTO_INCREMENT = 1;

-- Verificar datos existentes
SET @data_exists = (SELECT COUNT(*) FROM organigrama);

-- Insertar datos por defecto solo si está vacía
INSERT INTO organigrama (descripcion, id_padre, path, nivel)
SELECT * FROM (
    SELECT 'Junta Directiva' as descripcion, NULL as id_padre, '/junta-directiva/' as path, 0 as nivel
    UNION ALL SELECT 'Dirección General', 1, '/junta-directiva/direccion-general/', 1
    UNION ALL SELECT 'Recursos Humanos', 2, '/junta-directiva/direccion-general/recursos-humanos/', 2
    UNION ALL SELECT 'Contabilidad', 2, '/junta-directiva/direccion-general/contabilidad/', 2
    UNION ALL SELECT 'Operaciones', 2, '/junta-directiva/direccion-general/operaciones/', 2
) as temp_data
WHERE @data_exists = 0;

-- Agregar foreign key si no existe
SET @fk_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'planilla_innova' 
    AND TABLE_NAME = 'organigrama' 
    AND CONSTRAINT_NAME LIKE '%fk%'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE organigrama ADD CONSTRAINT fk_organigrama_padre FOREIGN KEY (id_padre) REFERENCES organigrama(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "Foreign key ya existe en organigrama" as mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Estructura organizacional configurada' as resultado_paso3;

-- ================================================================
-- PASO 4: REGISTRO DE MIGRACIÓN
-- ================================================================

SELECT 'PASO 4: Registrando migración completada...' as paso;

INSERT INTO migration_log (version, descripcion, fecha_aplicacion, archivo) VALUES 
('3.2.0', 'Sistema completo de reportes PDF con firmas + estructura organizacional', NOW(), 'run_migration_v3.2_complete.sql')
ON DUPLICATE KEY UPDATE 
    descripcion = VALUES(descripcion),
    fecha_aplicacion = VALUES(fecha_aplicacion),
    archivo = VALUES(archivo);

-- ================================================================
-- VERIFICACIONES FINALES
-- ================================================================

SELECT 'VERIFICACIONES FINALES:' as titulo_final;

-- Estructura de companies
SELECT 'Campos de firmas en companies:' as verificacion1;
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'planilla_innova' 
AND TABLE_NAME = 'companies' 
AND COLUMN_NAME LIKE '%firma%' OR COLUMN_NAME LIKE '%cargo%'
ORDER BY ORDINAL_POSITION;

-- Datos de empresa
SELECT 'Datos de firmas configurados:' as verificacion2;
SELECT 
    company_name,
    firma_director_planilla,
    cargo_director_planilla,
    firma_contador_planilla,
    cargo_contador_planilla
FROM companies WHERE id = 1;

-- Estructura organizacional
SELECT 'Elementos organizacionales creados:' as verificacion3;
SELECT 
    o.id,
    o.descripcion,
    o.id_padre,
    p.descripcion as padre,
    o.path,
    o.nivel
FROM organigrama o
LEFT JOIN organigrama p ON o.id_padre = p.id
ORDER BY o.nivel, o.id;

-- Log de migraciones
SELECT 'Migraciones aplicadas:' as verificacion4;
SELECT version, descripcion, fecha_aplicacion FROM migration_log ORDER BY fecha_aplicacion DESC LIMIT 5;

-- ================================================================
-- RESULTADO FINAL
-- ================================================================

SELECT '========================================' as separador_final;
SELECT 'MIGRACIÓN V3.2 COMPLETADA EXITOSAMENTE' as resultado_final;
SELECT 'Sistema listo para reportes PDF con firmas' as detalle1;
SELECT 'Estructura organizacional configurada' as detalle2;
SELECT 'Fecha de finalización:', NOW() as timestamp_final;
SELECT '========================================' as separador_final;

-- ================================================================
-- FIN DEL SCRIPT MAESTRO V3.2
-- ================================================================