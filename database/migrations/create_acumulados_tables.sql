-- ==============================================
-- SISTEMA DE ACUMULADOS PARA PLANILLAS
-- Fecha: 11 Septiembre 2025
-- Descripción: Tablas para manejo de acumulados de conceptos
-- ==============================================

-- Tabla: tipos_acumulados
-- Descripción: Define los tipos de acumulados disponibles
CREATE TABLE IF NOT EXISTS tipos_acumulados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE COMMENT 'Código único del tipo de acumulado (ej: VACAC, AGUINALDO, BONO14)',
    descripcion VARCHAR(100) NOT NULL COMMENT 'Descripción del tipo de acumulado',
    periodicidad ENUM('MENSUAL', 'TRIMESTRAL', 'SEMESTRAL', 'ANUAL', 'ESPECIAL') NOT NULL DEFAULT 'ANUAL' COMMENT 'Cada cuánto se reinicia el acumulado',
    fecha_inicio_periodo DATE NULL COMMENT 'Fecha de inicio del período actual',
    fecha_fin_periodo DATE NULL COMMENT 'Fecha de fin del período actual',
    reinicia_automaticamente TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Se reinicia automáticamente, 0=Manual',
    activo TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_codigo (codigo),
    INDEX idx_activo (activo),
    INDEX idx_periodicidad (periodicidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tipos de acumulados para conceptos de planilla';

-- Tabla: conceptos_acumulados
-- Descripción: Relaciona conceptos con tipos de acumulados
CREATE TABLE IF NOT EXISTS conceptos_acumulados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concepto_id INT NOT NULL COMMENT 'ID del concepto',
    tipo_acumulado_id INT NOT NULL COMMENT 'ID del tipo de acumulado',
    factor_acumulacion DECIMAL(10,4) NOT NULL DEFAULT 1.0000 COMMENT 'Factor multiplicador para el acumulado (1.0 = 100%)',
    incluir_en_acumulado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Incluir en acumulado, 0=No incluir',
    observaciones TEXT NULL COMMENT 'Observaciones sobre la relación',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (concepto_id) REFERENCES conceptos(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_acumulado_id) REFERENCES tipos_acumulados(id) ON DELETE CASCADE,
    UNIQUE KEY uk_concepto_tipo (concepto_id, tipo_acumulado_id),
    INDEX idx_concepto (concepto_id),
    INDEX idx_tipo_acumulado (tipo_acumulado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relación entre conceptos y tipos de acumulados';

-- Tabla: planillas_acumulados_consolidados
-- Descripción: Consolidados por planilla al momento del cierre
CREATE TABLE IF NOT EXISTS planillas_acumulados_consolidados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planilla_id INT NOT NULL COMMENT 'ID de la planilla procesada',
    tipo_acumulado_id INT NOT NULL COMMENT 'ID del tipo de acumulado',
    concepto_id INT NOT NULL COMMENT 'ID del concepto que genera el acumulado',
    periodo_acumulado VARCHAR(20) NOT NULL COMMENT 'Período del acumulado (ej: 2025, 2025-Q1, 2025-01)',
    total_empleados INT NOT NULL DEFAULT 0 COMMENT 'Cantidad de empleados que tuvieron este concepto',
    monto_total DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Monto total consolidado del concepto',
    monto_promedio DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Monto promedio por empleado',
    fecha_cierre TIMESTAMP NOT NULL COMMENT 'Fecha y hora del cierre de planilla',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (planilla_id) REFERENCES planillas(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_acumulado_id) REFERENCES tipos_acumulados(id) ON DELETE CASCADE,
    FOREIGN KEY (concepto_id) REFERENCES conceptos(id) ON DELETE CASCADE,
    INDEX idx_planilla (planilla_id),
    INDEX idx_tipo_acumulado (tipo_acumulado_id),
    INDEX idx_concepto (concepto_id),
    INDEX idx_periodo (periodo_acumulado),
    INDEX idx_fecha_cierre (fecha_cierre),
    UNIQUE KEY uk_planilla_tipo_concepto (planilla_id, tipo_acumulado_id, concepto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Consolidados de acumulados por planilla (para reportes)';

-- Tabla: empleados_acumulados_historicos
-- Descripción: Acumulados históricos por empleado (para liquidaciones)
CREATE TABLE IF NOT EXISTS empleados_acumulados_historicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL COMMENT 'ID del empleado',
    planilla_id INT NOT NULL COMMENT 'ID de la planilla donde se generó',
    tipo_acumulado_id INT NOT NULL COMMENT 'ID del tipo de acumulado',
    concepto_id INT NOT NULL COMMENT 'ID del concepto que genera el acumulado',
    periodo_acumulado VARCHAR(20) NOT NULL COMMENT 'Período del acumulado (ej: 2025, 2025-Q1)',
    monto_planilla DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Monto del concepto en esta planilla',
    monto_acumulado_anterior DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Acumulado anterior a esta planilla',
    monto_acumulado_actual DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Nuevo acumulado (anterior + actual)',
    dias_trabajados INT NOT NULL DEFAULT 0 COMMENT 'Días trabajados en el período de la planilla',
    fecha_planilla DATE NOT NULL COMMENT 'Fecha de la planilla procesada',
    observaciones TEXT NULL COMMENT 'Observaciones del cálculo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (planilla_id) REFERENCES planillas(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_acumulado_id) REFERENCES tipos_acumulados(id) ON DELETE CASCADE,
    FOREIGN KEY (concepto_id) REFERENCES conceptos(id) ON DELETE CASCADE,
    INDEX idx_empleado (empleado_id),
    INDEX idx_planilla (planilla_id),
    INDEX idx_tipo_acumulado (tipo_acumulado_id),
    INDEX idx_concepto (concepto_id),
    INDEX idx_periodo (periodo_acumulado),
    INDEX idx_fecha_planilla (fecha_planilla),
    INDEX idx_empleado_tipo_periodo (empleado_id, tipo_acumulado_id, periodo_acumulado),
    UNIQUE KEY uk_empleado_planilla_tipo_concepto (empleado_id, planilla_id, tipo_acumulado_id, concepto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de acumulados por empleado para liquidaciones';

-- Insertar tipos de acumulados básicos de Guatemala
INSERT INTO tipos_acumulados (codigo, descripcion, periodicidad, reinicia_automaticamente) VALUES
('VACAC', 'Acumulado de Vacaciones', 'ANUAL', 1),
('AGUINALDO', 'Acumulado de Aguinaldo', 'ANUAL', 1),
('BONO14', 'Acumulado de Bono 14', 'ANUAL', 1),
('INDEMN', 'Acumulado de Indemnización', 'ESPECIAL', 0),
('SALARIO_BASE', 'Acumulado de Salario Base', 'ANUAL', 1),
('COMISIONES', 'Acumulado de Comisiones', 'ANUAL', 1),
('HORAS_EXTRA', 'Acumulado de Horas Extraordinarias', 'ANUAL', 1),
('BONOS', 'Acumulado de Bonificaciones', 'ANUAL', 1);

-- ==============================================
-- VISTAS PARA CONSULTAS RÁPIDAS
-- ==============================================

-- Vista: v_acumulados_actuales_empleados
-- Descripción: Acumulados actuales por empleado
CREATE OR REPLACE VIEW v_acumulados_actuales_empleados AS
SELECT 
    e.id as empleado_id,
    CONCAT(e.nombres, ' ', e.apellidos) as empleado_nombre,
    ta.codigo as tipo_acumulado_codigo,
    ta.descripcion as tipo_acumulado_descripcion,
    ta.periodicidad,
    MAX(eah.periodo_acumulado) as periodo_actual,
    SUM(CASE 
        WHEN eah.periodo_acumulado = (
            SELECT MAX(periodo_acumulado) 
            FROM empleados_acumulados_historicos eah2 
            WHERE eah2.empleado_id = e.id 
            AND eah2.tipo_acumulado_id = ta.id
        ) THEN eah.monto_acumulado_actual 
        ELSE 0 
    END) as monto_acumulado_actual,
    COUNT(eah.id) as planillas_procesadas,
    MAX(eah.fecha_planilla) as ultima_planilla
FROM empleados e
LEFT JOIN empleados_acumulados_historicos eah ON e.id = eah.empleado_id
LEFT JOIN tipos_acumulados ta ON eah.tipo_acumulado_id = ta.id
WHERE ta.activo = 1
GROUP BY e.id, ta.id, ta.codigo, ta.descripcion, ta.periodicidad;

-- Vista: v_consolidados_acumulados_periodo
-- Descripción: Consolidados de acumulados por período
CREATE OR REPLACE VIEW v_consolidados_acumulados_periodo AS
SELECT 
    ta.codigo as tipo_acumulado_codigo,
    ta.descripcion as tipo_acumulado_descripcion,
    pac.periodo_acumulado,
    COUNT(DISTINCT pac.planilla_id) as planillas_procesadas,
    SUM(pac.total_empleados) as total_empleados,
    SUM(pac.monto_total) as monto_total_periodo,
    AVG(pac.monto_promedio) as monto_promedio_general,
    MIN(pac.fecha_cierre) as primera_planilla,
    MAX(pac.fecha_cierre) as ultima_planilla
FROM planillas_acumulados_consolidados pac
INNER JOIN tipos_acumulados ta ON pac.tipo_acumulado_id = ta.id
WHERE ta.activo = 1
GROUP BY ta.id, ta.codigo, ta.descripcion, pac.periodo_acumulado
ORDER BY ta.codigo, pac.periodo_acumulado DESC;

-- ==============================================
-- ÍNDICES DE PERFORMANCE
-- ==============================================

-- Índices compuestos para búsquedas frecuentes
CREATE INDEX idx_empleado_periodo_tipo ON empleados_acumulados_historicos (empleado_id, periodo_acumulado, tipo_acumulado_id);
CREATE INDEX idx_consolidado_periodo_tipo ON planillas_acumulados_consolidados (periodo_acumulado, tipo_acumulado_id);
CREATE INDEX idx_concepto_activo ON conceptos_acumulados (concepto_id, incluir_en_acumulado);

-- ==============================================
-- COMENTARIOS Y DOCUMENTACIÓN
-- ==============================================

/*
DOCUMENTACIÓN DEL SISTEMA DE ACUMULADOS:

1. FLUJO DE PROCESAMIENTO:
   - Al procesar una planilla, se calculan los conceptos normalmente
   - Los conceptos que tienen tipos_acumulados asociados se procesan adicionalmente
   - Se actualizan las tablas de consolidados y históricos de empleados
   - Los acumulados se usan para cálculos de liquidaciones, aguinaldo, vacaciones, etc.

2. TIPOS DE ACUMULADOS:
   - MENSUAL: Se reinicia cada mes (ej: horas extra mensuales)
   - TRIMESTRAL: Se reinicia cada trimestre
   - SEMESTRAL: Se reinicia cada semestre  
   - ANUAL: Se reinicia cada año (ej: aguinaldo, bono 14)
   - ESPECIAL: No se reinicia automáticamente (ej: indemnización)

3. FACTOR DE ACUMULACIÓN:
   - 1.0000 = El concepto se acumula al 100%
   - 0.5000 = Solo se acumula el 50% del concepto
   - 1.5000 = Se acumula 150% del concepto (incluye recargos)

4. CASOS DE USO:
   - Cálculo de aguinaldo (requiere acumulado anual de salarios)
   - Cálculo de vacaciones (requiere acumulado anual de salario base)
   - Liquidaciones (requiere histórico completo de acumulados)
   - Bono 14 (requiere acumulado anual específico)
   - Reportes de costos laborales por período

5. CONSULTAS COMUNES:
   - Acumulado actual de un empleado por tipo
   - Consolidados de una planilla específica
   - Histórico de acumulados para liquidación
   - Reportes de costos por período y tipo
*/