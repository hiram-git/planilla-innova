-- ================================================================
-- MIGRACIÓN V3.2 FIX - ORGANIGRAMA SEGURO
-- Fecha: 16 de Septiembre 2024
-- Descripción: Crea estructura organizacional sin depender de migration_log
--              y maneja errores de forma robusta
-- ================================================================

USE planilla_test; -- Cambia el nombre de la BD según tu entorno

-- ================================================================
-- PASO 1: CREAR TABLA DE LOG SI NO EXISTE
-- ================================================================

SELECT 'PASO 1: Creando tabla de log de migraciones...' as paso;

CREATE TABLE IF NOT EXISTS migration_log (
    id INT(11) NOT NULL AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL,
    descripcion TEXT,
    fecha_aplicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archivo VARCHAR(255),
    estado ENUM('COMPLETADA', 'FALLIDA', 'EN_PROCESO') DEFAULT 'COMPLETADA',
    notas TEXT,
    PRIMARY KEY (id),
    UNIQUE KEY unique_version_file (version, archivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Log de migraciones aplicadas al sistema';

SELECT 'Tabla migration_log verificada/creada' as resultado_paso1;

-- ================================================================
-- PASO 2: VERIFICAR TABLA ORGANIGRAMA
-- ================================================================

SELECT 'PASO 2: Verificando tabla organigrama...' as paso;

-- Verificar si la tabla organigrama existe
SET @tabla_existe = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'organigrama'
);

SELECT IF(@tabla_existe > 0, 
    'Tabla organigrama YA EXISTE', 
    'Tabla organigrama NO EXISTE - Se creará'
) as estado_tabla;

-- ================================================================
-- PASO 3: CREAR TABLA ORGANIGRAMA SI NO EXISTE
-- ================================================================

SELECT 'PASO 3: Creando/verificando estructura de organigrama...' as paso;

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
    INDEX idx_organigrama_path (path(191)), -- Limitado para compatibilidad
    INDEX idx_organigrama_nivel (nivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Estructura organizacional de la empresa';

-- ================================================================
-- PASO 4: CONFIGURAR AUTO_INCREMENT
-- ================================================================

SELECT 'PASO 4: Configurando AUTO_INCREMENT...' as paso;

-- Verificar el valor actual de AUTO_INCREMENT
SET @max_id = COALESCE((SELECT MAX(id) FROM organigrama), 0);
SET @auto_increment_value = @max_id + 1;

-- Configurar AUTO_INCREMENT de forma segura
SET @sql = CONCAT('ALTER TABLE organigrama AUTO_INCREMENT = ', @auto_increment_value);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT CONCAT('AUTO_INCREMENT configurado en: ', @auto_increment_value) as resultado_paso4;

-- ================================================================
-- PASO 5: AGREGAR FOREIGN KEY DE FORMA SEGURA
-- ================================================================

SELECT 'PASO 5: Configurando foreign key...' as paso;

-- Verificar si ya existe una foreign key
SET @fk_existe = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'organigrama' 
    AND REFERENCED_TABLE_NAME = 'organigrama'
    AND CONSTRAINT_NAME != 'PRIMARY'
);

-- Agregar foreign key solo si no existe
SET @sql = IF(@fk_existe = 0,
    'ALTER TABLE organigrama ADD CONSTRAINT fk_organigrama_padre FOREIGN KEY (id_padre) REFERENCES organigrama(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "Foreign key ya existe en organigrama" as mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(@fk_existe = 0, 'Foreign key agregada', 'Foreign key ya existía') as resultado_paso5;

-- ================================================================
-- PASO 6: INSERTAR DATOS POR DEFECTO
-- ================================================================

SELECT 'PASO 6: Insertando datos organizacionales por defecto...' as paso;

-- Verificar si hay datos existentes
SET @registros_existentes = (SELECT COUNT(*) FROM organigrama);

SELECT CONCAT('Registros existentes en organigrama: ', @registros_existentes) as estado_datos;

-- Insertar datos por defecto solo si la tabla está vacía
INSERT IGNORE INTO organigrama (descripcion, id_padre, path, nivel) 
SELECT 'Junta Directiva', NULL, '/junta-directiva/', 0
WHERE @registros_existentes = 0;

INSERT IGNORE INTO organigrama (descripcion, id_padre, path, nivel) 
SELECT 'Dirección General', 
       (SELECT id FROM organigrama WHERE descripcion = 'Junta Directiva' LIMIT 1), 
       '/junta-directiva/direccion-general/', 1
WHERE @registros_existentes = 0 
AND EXISTS (SELECT 1 FROM organigrama WHERE descripcion = 'Junta Directiva');

INSERT IGNORE INTO organigrama (descripcion, id_padre, path, nivel) 
SELECT 'Recursos Humanos', 
       (SELECT id FROM organigrama WHERE descripcion = 'Dirección General' LIMIT 1), 
       '/junta-directiva/direccion-general/recursos-humanos/', 2
WHERE @registros_existentes = 0 
AND EXISTS (SELECT 1 FROM organigrama WHERE descripcion = 'Dirección General');

INSERT IGNORE INTO organigrama (descripcion, id_padre, path, nivel) 
SELECT 'Contabilidad', 
       (SELECT id FROM organigrama WHERE descripcion = 'Dirección General' LIMIT 1), 
       '/junta-directiva/direccion-general/contabilidad/', 2
WHERE @registros_existentes = 0 
AND EXISTS (SELECT 1 FROM organigrama WHERE descripcion = 'Dirección General');

INSERT IGNORE INTO organigrama (descripcion, id_padre, path, nivel) 
SELECT 'Operaciones', 
       (SELECT id FROM organigrama WHERE descripcion = 'Dirección General' LIMIT 1), 
       '/junta-directiva/direccion-general/operaciones/', 2
WHERE @registros_existentes = 0 
AND EXISTS (SELECT 1 FROM organigrama WHERE descripcion = 'Dirección General');

-- Contar registros insertados
SET @registros_finales = (SELECT COUNT(*) FROM organigrama);
SELECT CONCAT('Registros finales en organigrama: ', @registros_finales) as resultado_paso6;

-- ================================================================
-- PASO 7: VERIFICACIONES FINALES
-- ================================================================

SELECT 'PASO 7: Verificaciones finales...' as paso;

-- Mostrar estructura de la tabla
SELECT 'ESTRUCTURA DE ORGANIGRAMA:' as titulo_estructura;
DESCRIBE organigrama;

-- Mostrar datos insertados con jerarquía
SELECT 'JERARQUÍA ORGANIZACIONAL:' as titulo_jerarquia;
SELECT 
    o.id,
    o.descripcion,
    o.id_padre,
    COALESCE(p.descripcion, 'RAÍZ') as padre,
    o.path,
    o.nivel
FROM organigrama o
LEFT JOIN organigrama p ON o.id_padre = p.id
ORDER BY o.nivel, o.id;

-- Verificar integridad referencial
SELECT 'VERIFICACIÓN DE INTEGRIDAD:' as titulo_integridad;
SELECT 
    COUNT(*) as elementos_huerfanos,
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ INTEGRIDAD CORRECTA'
        ELSE '⚠️ HAY ELEMENTOS HUÉRFANOS'
    END as estado_integridad
FROM organigrama o
WHERE o.id_padre IS NOT NULL 
AND NOT EXISTS (SELECT 1 FROM organigrama p WHERE p.id = o.id_padre);

-- ================================================================
-- PASO 8: REGISTRAR MIGRACIÓN
-- ================================================================

SELECT 'PASO 8: Registrando migración en log...' as paso;

INSERT INTO migration_log (version, descripcion, fecha_aplicacion, archivo, estado, notas) VALUES 
('3.2.2', 'Estructura organizacional completa con datos por defecto', NOW(), 'migration_v3.2_fix_organigrama_safe.sql', 'COMPLETADA', CONCAT('Registros insertados: ', @registros_finales))
ON DUPLICATE KEY UPDATE 
    descripcion = VALUES(descripcion),
    fecha_aplicacion = VALUES(fecha_aplicacion),
    estado = VALUES(estado),
    notas = VALUES(notas);

-- ================================================================
-- RESULTADO FINAL
-- ================================================================

SELECT '=================================================' as separador;
SELECT '✅ MIGRACIÓN DE ORGANIGRAMA COMPLETADA EXITOSAMENTE' as resultado_final;
SELECT CONCAT('📊 Elementos organizacionales: ', @registros_finales) as detalle1;
SELECT '🏗️ Estructura jerárquica configurada' as detalle2;
SELECT '🔗 Foreign keys y constraints aplicados' as detalle3;
SELECT '📝 Migración registrada en log' as detalle4;
SELECT '=================================================' as separador;

-- ================================================================
-- FIN DE MIGRACIÓN SEGURA DE ORGANIGRAMA
-- ================================================================