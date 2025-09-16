-- ================================================================
-- SCRIPT SIMPLE - CREAR TABLA ORGANIGRAMA
-- Fecha: 16 de Septiembre 2024
-- Descripción: Crea únicamente la tabla organigrama con datos básicos
-- ================================================================

-- ⚠️ IMPORTANTE: Cambiar el nombre de la base de datos
USE planilla_test; -- Cambiar por: planilla_innova, planilla_test, etc.

-- ================================================================
-- CREAR TABLA ORGANIGRAMA
-- ================================================================

CREATE TABLE organigrama (
    id INT(11) NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(255) NOT NULL COMMENT 'Nombre del elemento organizacional',
    id_padre INT(11) NULL DEFAULT NULL COMMENT 'ID del elemento padre (NULL para raíz)',
    path VARCHAR(500) NULL DEFAULT NULL COMMENT 'Ruta jerárquica completa',
    nivel INT(11) DEFAULT 0 COMMENT 'Nivel en la jerarquía (0 = raíz)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_organigrama_padre (id_padre),
    INDEX idx_organigrama_path (path(191)),
    INDEX idx_organigrama_nivel (nivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Estructura organizacional de la empresa';

-- ================================================================
-- INSERTAR DATOS BÁSICOS
-- ================================================================

INSERT INTO organigrama (descripcion, id_padre, path, nivel) VALUES
('Junta Directiva', NULL, '/junta-directiva/', 0),
('Dirección General', 1, '/junta-directiva/direccion-general/', 1),
('Recursos Humanos', 2, '/junta-directiva/direccion-general/recursos-humanos/', 2),
('Contabilidad', 2, '/junta-directiva/direccion-general/contabilidad/', 2),
('Operaciones', 2, '/junta-directiva/direccion-general/operaciones/', 2);

-- ================================================================
-- AGREGAR FOREIGN KEY
-- ================================================================

ALTER TABLE organigrama 
ADD CONSTRAINT fk_organigrama_padre 
FOREIGN KEY (id_padre) REFERENCES organigrama(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- ================================================================
-- VERIFICAR RESULTADO
-- ================================================================

SELECT 'TABLA ORGANIGRAMA CREADA EXITOSAMENTE' as resultado;

-- Mostrar estructura
DESCRIBE organigrama;

-- Mostrar datos insertados
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

-- ================================================================
-- FIN DEL SCRIPT SIMPLE
-- ================================================================