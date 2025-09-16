-- ================================================================
-- MIGRACIÓN V3.2.2 - ESTRUCTURA ORGANIZACIONAL COMPLETA
-- Fecha: 16 de Septiembre 2024
-- Descripción: Creación y configuración de tabla organigrama con datos iniciales
--              y corrección de AUTO_INCREMENT para evitar conflictos de ID
-- ================================================================

USE planilla_innova;

-- ================================================================
-- VERIFICACIÓN Y CREACIÓN DE TABLA ORGANIGRAMA
-- ================================================================

-- Verificar si la tabla organigrama ya existe
SET @table_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = 'planilla_innova' 
    AND TABLE_NAME = 'organigrama'
);

SELECT IF(@table_exists > 0, 'Tabla organigrama YA EXISTE', 'Tabla organigrama NO EXISTE - Se creará') as estado_tabla;

-- Crear tabla organigrama si no existe
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estructura organizacional de la empresa';

-- ================================================================
-- CONFIGURACIÓN AUTO_INCREMENT
-- ================================================================

-- Verificar y corregir AUTO_INCREMENT
SELECT 'CONFIGURANDO AUTO_INCREMENT...' as mensaje;

-- Eliminar foreign key temporalmente si existe (para evitar conflictos)
SET @fk_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'planilla_innova' 
    AND TABLE_NAME = 'organigrama' 
    AND CONSTRAINT_NAME != 'PRIMARY'
);

-- Solo eliminar FK si existe
SET @sql = IF(@fk_exists > 0, 
    'ALTER TABLE organigrama DROP FOREIGN KEY organigrama_ibfk_1',
    'SELECT "No hay FK que eliminar" as mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Asegurar AUTO_INCREMENT correcto
ALTER TABLE organigrama AUTO_INCREMENT = 1;

-- Recrear foreign key
ALTER TABLE organigrama 
ADD CONSTRAINT fk_organigrama_padre 
FOREIGN KEY (id_padre) REFERENCES organigrama(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- ================================================================
-- DATOS ORGANIZACIONALES POR DEFECTO
-- ================================================================

-- Verificar si hay datos existentes
SET @data_exists = (SELECT COUNT(*) FROM organigrama);

SELECT IF(@data_exists > 0, 
    CONCAT('La tabla organigrama ya tiene ', @data_exists, ' registros'), 
    'La tabla organigrama está vacía - Se insertarán datos por defecto'
) as estado_datos;

-- Insertar datos por defecto solo si la tabla está vacía
INSERT INTO organigrama (descripcion, id_padre, path, nivel)
SELECT * FROM (
    SELECT 'Junta Directiva' as descripcion, NULL as id_padre, '/junta-directiva/' as path, 0 as nivel
    UNION ALL
    SELECT 'Dirección General', 1, '/junta-directiva/direccion-general/', 1
    UNION ALL  
    SELECT 'Recursos Humanos', 2, '/junta-directiva/direccion-general/recursos-humanos/', 2
    UNION ALL
    SELECT 'Contabilidad', 2, '/junta-directiva/direccion-general/contabilidad/', 2
    UNION ALL
    SELECT 'Operaciones', 2, '/junta-directiva/direccion-general/operaciones/', 2
    UNION ALL
    SELECT 'Sistemas e Informática', 2, '/junta-directiva/direccion-general/sistemas-informatica/', 2
    UNION ALL
    SELECT 'Nóminas', 3, '/junta-directiva/direccion-general/recursos-humanos/nominas/', 3
    UNION ALL
    SELECT 'Selección y Reclutamiento', 3, '/junta-directiva/direccion-general/recursos-humanos/seleccion-reclutamiento/', 3
    UNION ALL
    SELECT 'Cuentas por Pagar', 4, '/junta-directiva/direccion-general/contabilidad/cuentas-por-pagar/', 3
    UNION ALL
    SELECT 'Cuentas por Cobrar', 4, '/junta-directiva/direccion-general/contabilidad/cuentas-por-cobrar/', 3
) as temp_data
WHERE @data_exists = 0;

-- ================================================================
-- VERIFICACIONES FINALES
-- ================================================================

-- Mostrar estructura de la tabla
SELECT 'ESTRUCTURA DE TABLA ORGANIGRAMA:' as mensaje;
DESCRIBE organigrama;

-- Mostrar datos insertados
SELECT 'DATOS EN ORGANIGRAMA:' as mensaje;
SELECT 
    o.id,
    o.descripcion,
    o.id_padre,
    p.descripcion as padre_descripcion,
    o.path,
    o.nivel
FROM organigrama o
LEFT JOIN organigrama p ON o.id_padre = p.id
ORDER BY o.nivel, o.id;

-- Verificar integridad de la jerarquía
SELECT 'VERIFICACIÓN DE JERARQUÍA:' as mensaje;
SELECT 
    nivel,
    COUNT(*) as elementos_por_nivel
FROM organigrama 
GROUP BY nivel 
ORDER BY nivel;

-- Verificar elementos huérfanos (que referencian padres inexistentes)
SELECT 'VERIFICACIÓN DE INTEGRIDAD:' as mensaje;
SELECT 
    COUNT(*) as elementos_huerfanos
FROM organigrama o
WHERE o.id_padre IS NOT NULL 
AND NOT EXISTS (SELECT 1 FROM organigrama p WHERE p.id = o.id_padre);

-- ================================================================
-- LOG DE MIGRACIÓN
-- ================================================================

-- Registrar migración en log
INSERT INTO migration_log (version, descripcion, fecha_aplicacion, archivo) VALUES 
('3.2.2', 'Estructura organizacional completa con datos por defecto', NOW(), 'migration_v3.2.2_estructura_organizacional.sql')
ON DUPLICATE KEY UPDATE 
    descripcion = VALUES(descripcion),
    fecha_aplicacion = VALUES(fecha_aplicacion),
    archivo = VALUES(archivo);

SELECT 'MIGRACIÓN V3.2.2 COMPLETADA - ESTRUCTURA ORGANIZACIONAL LISTA' as resultado;

-- ================================================================
-- FIN DE MIGRACIÓN V3.2.2
-- ================================================================