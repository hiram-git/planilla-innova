-- =============================================
-- MIGRACIÓN: Módulo de Campos Adicionales para Empleados
-- Fecha: 2026-01-16
-- Versión: 3.6.0
-- Descripción: Sistema flexible de campos personalizados
--              para empleados con integración al motor de fórmulas
-- =============================================

-- Tabla 1: Catálogo de campos adicionales
CREATE TABLE IF NOT EXISTS employee_additional_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código único del campo (usado en fórmulas)',
    etiqueta VARCHAR(100) NOT NULL COMMENT 'Etiqueta visible en interfaz',
    descripcion VARCHAR(255) COMMENT 'Descripción del campo',
    tipo_dato ENUM('TEXTO', 'NUMERO', 'FECHA', 'BOOLEAN') NOT NULL DEFAULT 'TEXTO' COMMENT 'Tipo de dato del campo',
    valor_defecto VARCHAR(255) COMMENT 'Valor por defecto al crear empleado',
    orden INT DEFAULT 0 COMMENT 'Orden de visualización en formularios',
    activo TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_activo (activo),
    INDEX idx_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de campos adicionales personalizados';

-- Tabla 2: Valores de campos adicionales por empleado
CREATE TABLE IF NOT EXISTS employee_additional_field_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT 'ID del empleado',
    field_id INT NOT NULL COMMENT 'ID del campo adicional',
    valor TEXT COMMENT 'Valor del campo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES employee_additional_fields(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_field (employee_id, field_id) COMMENT 'Un empleado solo puede tener un valor por campo',
    INDEX idx_employee (employee_id),
    INDEX idx_field (field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Valores de campos adicionales por empleado';

-- =============================================
-- DATOS DE EJEMPLO (OPCIONAL)
-- =============================================

-- Insertar campos adicionales de ejemplo
INSERT INTO employee_additional_fields (codigo, etiqueta, descripcion, tipo_dato, valor_defecto, orden, activo) VALUES
('BONO_TRANSPORTE', 'Bono de Transporte', 'Monto mensual de bono de transporte', 'NUMERO', '50', 1, 1),
('TIENE_DEPENDIENTES', 'Tiene Dependientes', 'Indica si el empleado tiene dependientes económicos', 'BOOLEAN', '0', 2, 1),
('NIVEL_EDUCATIVO', 'Nivel Educativo', 'Nivel educativo del empleado', 'TEXTO', 'Bachiller', 3, 1),
('FECHA_ULTIMA_EVALUACION', 'Fecha Última Evaluación', 'Fecha de la última evaluación de desempeño', 'FECHA', NULL, 4, 1)
ON DUPLICATE KEY UPDATE codigo = codigo;

-- =============================================
-- VERIFICACIÓN
-- =============================================
SELECT 'Migración de Campos Adicionales completada exitosamente' AS status;
SELECT COUNT(*) AS total_campos FROM employee_additional_fields;
