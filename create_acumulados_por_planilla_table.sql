-- ============================================
-- CREACIÓN DE TABLA: acumulados_por_planilla
-- Fecha: 2025-01-13
-- Propósito: Registrar acumulados específicos por planilla procesada
-- ============================================

USE planilla_innova;

-- Crear tabla acumulados_por_planilla si no existe
CREATE TABLE IF NOT EXISTS acumulados_por_planilla (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planilla_id INT NOT NULL,
    empleado_id INT NOT NULL,
    concepto_id INT NOT NULL,
    tipo_acumulado_id INT NOT NULL,
    monto_concepto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    factor_acumulacion DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
    monto_acumulado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    periodo_inicio DATE NOT NULL,
    periodo_fin DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para optimizar consultas
    INDEX idx_planilla (planilla_id),
    INDEX idx_empleado (empleado_id),
    INDEX idx_tipo_acumulado (tipo_acumulado_id),
    INDEX idx_concepto (concepto_id),
    INDEX idx_planilla_empleado (planilla_id, empleado_id),
    INDEX idx_empleado_tipo (empleado_id, tipo_acumulado_id),
    
    -- Claves foráneas
    FOREIGN KEY (planilla_id) REFERENCES planilla_cabecera(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES employees(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (concepto_id) REFERENCES concepto(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (tipo_acumulado_id) REFERENCES tipos_acumulados(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verificar que la tabla se creó correctamente
DESCRIBE acumulados_por_planilla;

-- Contar registros existentes (debería ser 0 si es nueva)
SELECT COUNT(*) as total_registros FROM acumulados_por_planilla;

-- ============================================
-- INFORMACIÓN DE LA TABLA
-- 
-- Propósito: Esta tabla almacena el detalle específico de qué acumulados
-- se generaron en cada planilla procesada, permitiendo:
-- 
-- 1. Rollback preciso al reabrir planillas cerradas
-- 2. Auditoría de qué se acumuló en cada proceso
-- 3. Consultas por planilla específica
-- 4. Eliminación completa al pasar a PENDIENTE
-- 
-- Relaciones:
-- - planilla_id: Referencia a planilla_cabecera
-- - empleado_id: Referencia a employees
-- - concepto_id: Referencia a concepto
-- - tipo_acumulado_id: Referencia a tipos_acumulados
-- ============================================