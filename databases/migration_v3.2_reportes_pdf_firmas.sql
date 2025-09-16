-- ================================================================
-- MIGRACIÓN V3.2 - SISTEMA DE REPORTES PDF CON FIRMAS EMPRESARIALES
-- Fecha: 16 de Septiembre 2024
-- Descripción: Campos adicionales para firmas en reportes de planilla
--              y estructura organizacional mejorada
-- ================================================================

USE planilla_innova;

-- ================================================================
-- 1. CAMPOS DE FIRMAS PARA REPORTES DE PLANILLA
-- ================================================================

-- Agregar campos de firmas específicas para reportes de planilla
ALTER TABLE companies 
ADD COLUMN firma_director_planilla VARCHAR(255) NULL COMMENT 'Nombre del director para firmar reportes de planilla' AFTER cargo_elaborador,
ADD COLUMN cargo_director_planilla VARCHAR(255) NULL DEFAULT 'Director General' COMMENT 'Cargo del director para reportes' AFTER firma_director_planilla,
ADD COLUMN firma_contador_planilla VARCHAR(255) NULL COMMENT 'Nombre del contador para firmar reportes de planilla' AFTER cargo_director_planilla,
ADD COLUMN cargo_contador_planilla VARCHAR(255) NULL DEFAULT 'Contador General' COMMENT 'Cargo del contador para reportes' AFTER firma_contador_planilla;

-- Datos por defecto para empresa existente
UPDATE companies SET 
    firma_director_planilla = COALESCE(firma_director_planilla, legal_representative),
    cargo_director_planilla = COALESCE(cargo_director_planilla, 'Director General'),
    firma_contador_planilla = COALESCE(firma_contador_planilla, 'Contador General'),
    cargo_contador_planilla = COALESCE(cargo_contador_planilla, 'Contador General')
WHERE id = 1;

-- ================================================================
-- 2. ESTRUCTURA ORGANIZACIONAL - TABLA ORGANIGRAMA
-- ================================================================

-- Verificar si la tabla organigrama existe, si no, crearla
CREATE TABLE IF NOT EXISTS organigrama (
    id INT(11) NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(255) NOT NULL COMMENT 'Nombre del elemento organizacional',
    id_padre INT(11) NULL DEFAULT NULL COMMENT 'ID del elemento padre (NULL para raíz)',
    path VARCHAR(500) NULL DEFAULT NULL COMMENT 'Ruta jerárquica completa',
    nivel INT(11) DEFAULT 0 COMMENT 'Nivel en la jerarquía (0 = raíz)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (id_padre) REFERENCES organigrama(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_organigrama_padre (id_padre),
    INDEX idx_organigrama_path (path),
    INDEX idx_organigrama_nivel (nivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estructura organizacional de la empresa';

-- Asegurar que la tabla tenga AUTO_INCREMENT configurado correctamente
ALTER TABLE organigrama AUTO_INCREMENT = 1;

-- Datos organizacionales por defecto si la tabla está vacía
INSERT IGNORE INTO organigrama (id, descripcion, id_padre, path, nivel) VALUES
(1, 'Junta Directiva', NULL, '/junta-directiva/', 0),
(2, 'Dirección General', 1, '/junta-directiva/direccion-general/', 1),
(3, 'Recursos Humanos', 2, '/junta-directiva/direccion-general/recursos-humanos/', 2),
(4, 'Contabilidad', 2, '/junta-directiva/direccion-general/contabilidad/', 2),
(5, 'Operaciones', 2, '/junta-directiva/direccion-general/operaciones/', 2);

-- ================================================================
-- 3. VERIFICACIONES DE INTEGRIDAD
-- ================================================================

-- Verificar estructura de companies
SELECT 'VERIFICACIÓN: Campos de firmas en companies' as mensaje;
DESCRIBE companies;

-- Verificar estructura de organigrama  
SELECT 'VERIFICACIÓN: Estructura de organigrama' as mensaje;
DESCRIBE organigrama;

-- Contar registros en organigrama
SELECT 'VERIFICACIÓN: Registros en organigrama' as mensaje, COUNT(*) as total FROM organigrama;

-- Mostrar jerarquía organizacional
SELECT 
    o.id,
    o.descripcion,
    o.id_padre,
    o.path,
    o.nivel,
    p.descripcion as padre_descripcion
FROM organigrama o
LEFT JOIN organigrama p ON o.id_padre = p.id
ORDER BY o.nivel, o.id;

-- ================================================================
-- 4. INFORMACIÓN DE MIGRACIÓN
-- ================================================================

-- Registro de migración completada
INSERT INTO migration_log (version, descripcion, fecha_aplicacion, archivo) VALUES 
('3.2.0', 'Campos de firmas para reportes PDF + estructura organizacional', NOW(), 'migration_v3.2_reportes_pdf_firmas.sql')
ON DUPLICATE KEY UPDATE 
    descripcion = VALUES(descripcion),
    fecha_aplicacion = VALUES(fecha_aplicacion);

-- Crear tabla de log de migraciones si no existe
CREATE TABLE IF NOT EXISTS migration_log (
    id INT(11) NOT NULL AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL UNIQUE,
    descripcion TEXT,
    fecha_aplicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archivo VARCHAR(255),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'MIGRACIÓN V3.2 COMPLETADA EXITOSAMENTE' as resultado;
SELECT 'Nuevos campos agregados para firmas en reportes PDF' as detalle1;
SELECT 'Estructura organizacional verificada y datos por defecto insertados' as detalle2;

-- ================================================================
-- FIN DE MIGRACIÓN V3.2
-- ================================================================