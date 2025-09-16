-- Script para crear tablas necesarias para el sistema de acumulados
-- Ejecutar en la base de datos planilla_innova

USE planilla_innova;

-- Crear tabla empleados_acumulados_historicos si no existe
CREATE TABLE IF NOT EXISTS empleados_acumulados_historicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo_acumulado_id INT NOT NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fin DATE NOT NULL,
    total_acumulado DECIMAL(10,2) DEFAULT 0.00,
    total_conceptos_incluidos INT DEFAULT 0,
    ultima_planilla_id INT NULL,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empleado_tipo (empleado_id, tipo_acumulado_id),
    INDEX idx_periodo (periodo_inicio, periodo_fin),
    FOREIGN KEY (empleado_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_acumulado_id) REFERENCES tipos_acumulados(id) ON DELETE CASCADE,
    FOREIGN KEY (ultima_planilla_id) REFERENCES planilla_cabecera(id) ON DELETE SET NULL
);

-- Crear tabla planillas_acumulados_consolidados si no existe
CREATE TABLE IF NOT EXISTS planillas_acumulados_consolidados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planilla_id INT NOT NULL,
    tipo_acumulado_id INT NOT NULL,
    empleado_id INT NOT NULL,
    concepto_id INT NOT NULL,
    monto_concepto DECIMAL(10,2) NOT NULL,
    factor_acumulacion DECIMAL(8,4) DEFAULT 1.0000,
    monto_acumulado DECIMAL(10,2) NOT NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fin DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_planilla (planilla_id),
    INDEX idx_empleado_tipo (empleado_id, tipo_acumulado_id),
    FOREIGN KEY (planilla_id) REFERENCES planilla_cabecera(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_acumulado_id) REFERENCES tipos_acumulados(id) ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (concepto_id) REFERENCES concepts(id) ON DELETE CASCADE
);

-- Crear tabla conceptos_acumulados si no existe
CREATE TABLE IF NOT EXISTS conceptos_acumulados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concepto_id INT NOT NULL,
    tipo_acumulado_id INT NOT NULL,
    factor_acumulacion DECIMAL(8,4) DEFAULT 1.0000,
    incluir_en_acumulado TINYINT(1) DEFAULT 1,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_concepto_tipo (concepto_id, tipo_acumulado_id),
    FOREIGN KEY (concepto_id) REFERENCES concepts(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_acumulado_id) REFERENCES tipos_acumulados(id) ON DELETE CASCADE
);

-- Insertar datos de ejemplo si no existen
INSERT IGNORE INTO tipos_acumulados (codigo, descripcion, periodicidad, reinicia_automaticamente, activo) VALUES
('XIII_MES', 'Décimo Tercer Mes (Aguinaldo)', 'ANUAL', 1, 1),
('VACACIONES', 'Acumulado de Vacaciones', 'ANUAL', 1, 1),
('PRIMA_ANTIGUEDAD', 'Prima de Antigüedad', 'ANUAL', 0, 1),
('INDEMNIZACION', 'Indemnización por Despido', 'ESPECIAL', 0, 1),
('GASTO_REPRES', 'Gasto de Representación', 'MENSUAL', 1, 1);

-- Crear algunos registros de ejemplo en conceptos_acumulados si existe algún concepto
INSERT IGNORE INTO conceptos_acumulados (concepto_id, tipo_acumulado_id, factor_acumulacion)
SELECT c.id, ta.id, 1.0000 
FROM concepts c 
CROSS JOIN tipos_acumulados ta 
WHERE c.nombre LIKE '%SUELDO%' AND ta.codigo = 'XIII_MES'
LIMIT 5;

-- Mostrar información de las tablas creadas
SELECT 'Tablas de acumulados creadas exitosamente' as status;

SELECT TABLE_NAME, TABLE_ROWS 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'planilla_innova' 
AND TABLE_NAME IN ('tipos_acumulados', 'empleados_acumulados_historicos', 'planillas_acumulados_consolidados', 'conceptos_acumulados');