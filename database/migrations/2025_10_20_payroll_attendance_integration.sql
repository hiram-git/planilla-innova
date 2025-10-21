-- ========================================================================
-- MIGRACIÓN: Integración Sistema Asistencias con Planillas
-- Fecha: 20 de Octubre, 2025
-- Versión: v3.4.7
-- Descripción: Tablas para integración automática asistencias → conceptos planilla
-- ========================================================================

USE planilla_prod;

-- ========================================================================
-- 1. TABLA: attendance_concepts_mapping
-- Mapeo entre tipos de asistencia y conceptos de planilla
-- ========================================================================

CREATE TABLE IF NOT EXISTS attendance_concepts_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único del mapeo',

    -- Identificación
    mapping_name VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo del mapeo',
    mapping_type ENUM(
        'HORAS_TRABAJADAS',
        'HORAS_EXTRAS_25',
        'HORAS_EXTRAS_50',
        'HORAS_NOCTURNAS',
        'HORAS_FERIADOS',
        'HORAS_DOMINICALES',
        'DESCUENTO_TARDANZAS',
        'DESCUENTO_AUSENCIAS',
        'BONO_PUNTUALIDAD',
        'BONO_ASISTENCIA_PERFECTA'
    ) NOT NULL COMMENT 'Tipo de cálculo de asistencia',

    -- Relación con concepto de planilla
    concepto_id INT NOT NULL COMMENT 'ID del concepto destino en tabla concepto',

    -- Configuración del mapeo
    formula_multiplicador VARCHAR(255) DEFAULT NULL COMMENT 'Fórmula para calcular multiplicador (ej: tarifa_hora * 1.25)',
    valor_fijo DECIMAL(10,2) DEFAULT NULL COMMENT 'Valor fijo por unidad (si aplica)',
    usar_referencia_valor TINYINT(1) DEFAULT 1 COMMENT 'Si debe guardarse en referencia_valor de planilla_detalle',

    -- Filtros y condiciones
    tipo_planilla_id INT DEFAULT NULL COMMENT 'Solo aplica a este tipo de planilla (NULL = todos)',
    situacion_id INT DEFAULT NULL COMMENT 'Solo aplica a esta situación (NULL = todos)',
    requiere_aprobacion TINYINT(1) DEFAULT 0 COMMENT 'Requiere aprobación manual antes de aplicar',

    -- Configuración adicional
    descripcion TEXT DEFAULT NULL COMMENT 'Descripción detallada del mapeo',
    notas TEXT DEFAULT NULL COMMENT 'Notas adicionales o reglas especiales',

    -- Estado
    is_active TINYINT(1) DEFAULT 1 COMMENT 'Si el mapeo está activo',
    priority INT DEFAULT 100 COMMENT 'Prioridad de aplicación (menor = más prioritario)',

    -- Auditoría
    created_by INT DEFAULT NULL COMMENT 'Usuario que creó el mapeo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    KEY idx_mapping_type (mapping_type),
    KEY idx_concepto (concepto_id),
    KEY idx_tipo_planilla (tipo_planilla_id),
    KEY idx_active_priority (is_active, priority),

    -- Foreign Keys
    CONSTRAINT fk_mapping_concepto FOREIGN KEY (concepto_id)
        REFERENCES concepto(id) ON DELETE RESTRICT,
    CONSTRAINT fk_mapping_tipo_planilla FOREIGN KEY (tipo_planilla_id)
        REFERENCES tipos_planilla(id) ON DELETE CASCADE,
    CONSTRAINT fk_mapping_situacion FOREIGN KEY (situacion_id)
        REFERENCES situaciones(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Mapeo dinámico entre cálculos de asistencia y conceptos de planilla';


-- ========================================================================
-- 2. TABLA: payroll_attendance_summary
-- Resumen consolidado de asistencias por planilla y empleado
-- ========================================================================

CREATE TABLE IF NOT EXISTS payroll_attendance_summary (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único del resumen',

    -- Relaciones principales
    planilla_cabecera_id INT NOT NULL COMMENT 'ID de la planilla',
    employee_id INT NOT NULL COMMENT 'ID del empleado',

    -- Período del resumen
    period_start DATE NOT NULL COMMENT 'Fecha inicio del período',
    period_end DATE NOT NULL COMMENT 'Fecha fin del período',

    -- Métricas de asistencia (horas)
    total_hours_worked DECIMAL(10,2) DEFAULT 0 COMMENT 'Total horas trabajadas',
    regular_hours DECIMAL(10,2) DEFAULT 0 COMMENT 'Horas regulares (hasta 8h/día)',
    overtime_hours_25 DECIMAL(10,2) DEFAULT 0 COMMENT 'Horas extras con recargo 25%',
    overtime_hours_50 DECIMAL(10,2) DEFAULT 0 COMMENT 'Horas extras con recargo 50%',
    night_hours DECIMAL(10,2) DEFAULT 0 COMMENT 'Horas nocturnas (6PM-6AM)',
    holiday_hours DECIMAL(10,2) DEFAULT 0 COMMENT 'Horas trabajadas en feriados',
    sunday_hours DECIMAL(10,2) DEFAULT 0 COMMENT 'Horas trabajadas en domingos',

    -- Métricas de asistencia (días)
    total_days_worked INT DEFAULT 0 COMMENT 'Total días trabajados',
    total_absences INT DEFAULT 0 COMMENT 'Total ausencias',
    justified_absences INT DEFAULT 0 COMMENT 'Ausencias justificadas',
    unjustified_absences INT DEFAULT 0 COMMENT 'Ausencias injustificadas',
    pending_absences INT DEFAULT 0 COMMENT 'Ausencias pendientes de justificar',
    perfect_attendance_days INT DEFAULT 0 COMMENT 'Días con asistencia perfecta',

    -- Métricas de puntualidad
    total_tardiness_minutes INT DEFAULT 0 COMMENT 'Total minutos de tardanza',
    tardiness_count INT DEFAULT 0 COMMENT 'Cantidad de tardanzas',
    early_departures_count INT DEFAULT 0 COMMENT 'Cantidad de salidas anticipadas',
    punctuality_score DECIMAL(5,2) DEFAULT 100 COMMENT 'Score de puntualidad (0-100)',

    -- Métricas de almuerzo
    total_lunch_time_minutes INT DEFAULT 0 COMMENT 'Total minutos de almuerzo',
    lunch_violations INT DEFAULT 0 COMMENT 'Violaciones tiempo almuerzo (<30min)',

    -- Cálculos monetarios (basados en mapeos)
    total_regular_pay DECIMAL(10,2) DEFAULT 0 COMMENT 'Pago por horas regulares',
    total_overtime_pay DECIMAL(10,2) DEFAULT 0 COMMENT 'Pago por horas extras',
    total_night_pay DECIMAL(10,2) DEFAULT 0 COMMENT 'Pago por horas nocturnas',
    total_holiday_pay DECIMAL(10,2) DEFAULT 0 COMMENT 'Pago por horas feriados',
    total_deductions DECIMAL(10,2) DEFAULT 0 COMMENT 'Total deducciones (tardanzas, ausencias)',
    total_bonuses DECIMAL(10,2) DEFAULT 0 COMMENT 'Total bonificaciones (puntualidad, asistencia perfecta)',
    net_attendance_pay DECIMAL(10,2) DEFAULT 0 COMMENT 'Total neto de asistencias',

    -- Cumplimiento legal
    legal_compliant TINYINT(1) DEFAULT 1 COMMENT 'Si cumple con normativa legal',
    legal_violations_count INT DEFAULT 0 COMMENT 'Cantidad de violaciones legales',
    legal_warnings_count INT DEFAULT 0 COMMENT 'Cantidad de advertencias legales',
    legal_risk_level ENUM('NINGUNO', 'BAJO', 'MEDIO', 'ALTO', 'CRÍTICO') DEFAULT 'NINGUNO'
        COMMENT 'Nivel de riesgo legal',
    legal_compliance_notes TEXT DEFAULT NULL COMMENT 'Notas sobre cumplimiento legal',

    -- Alertas generadas
    alerts_generated INT DEFAULT 0 COMMENT 'Cantidad de alertas generadas',
    critical_alerts INT DEFAULT 0 COMMENT 'Cantidad de alertas críticas',
    pending_alerts INT DEFAULT 0 COMMENT 'Cantidad de alertas pendientes',

    -- Estado del procesamiento
    processing_status ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED', 'ERROR') DEFAULT 'PENDING'
        COMMENT 'Estado del procesamiento',
    error_message TEXT DEFAULT NULL COMMENT 'Mensaje de error si falló',

    -- Metadata adicional
    metadata JSON DEFAULT NULL COMMENT 'Datos adicionales en formato JSON',

    -- Auditoría
    processed_by INT DEFAULT NULL COMMENT 'Usuario que procesó',
    processed_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de procesamiento',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    UNIQUE KEY idx_unique_planilla_employee (planilla_cabecera_id, employee_id),
    KEY idx_employee (employee_id),
    KEY idx_period (period_start, period_end),
    KEY idx_status (processing_status),
    KEY idx_legal_risk (legal_risk_level),
    KEY idx_processed_at (processed_at),

    -- Foreign Keys
    CONSTRAINT fk_summary_planilla FOREIGN KEY (planilla_cabecera_id)
        REFERENCES planilla_cabecera(id) ON DELETE CASCADE,
    CONSTRAINT fk_summary_employee FOREIGN KEY (employee_id)
        REFERENCES employees(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Resumen consolidado de asistencias por planilla y empleado';


-- ========================================================================
-- 3. TABLA: payroll_attendance_details
-- Detalle granular día por día de asistencias incluidas en planilla
-- ========================================================================

CREATE TABLE IF NOT EXISTS payroll_attendance_details (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único del detalle',

    -- Relaciones
    summary_id INT NOT NULL COMMENT 'ID del resumen en payroll_attendance_summary',
    attendance_id INT DEFAULT NULL COMMENT 'ID de la asistencia en tabla attendance',
    calculation_id INT DEFAULT NULL COMMENT 'ID del cálculo en attendance_calculations',

    -- Información del día
    attendance_date DATE NOT NULL COMMENT 'Fecha de la asistencia',
    day_of_week VARCHAR(20) DEFAULT NULL COMMENT 'Día de la semana',
    day_type ENUM('LABORAL', 'FERIADO', 'DUELO_NACIONAL', 'FIN_SEMANA', 'ESPECIAL')
        DEFAULT 'LABORAL' COMMENT 'Tipo de día',

    -- Horarios
    time_in TIME DEFAULT NULL COMMENT 'Hora de entrada',
    time_out TIME DEFAULT NULL COMMENT 'Hora de salida',
    scheduled_in TIME DEFAULT NULL COMMENT 'Hora entrada programada',
    scheduled_out TIME DEFAULT NULL COMMENT 'Hora salida programada',

    -- Métricas del día
    hours_worked DECIMAL(5,2) DEFAULT 0 COMMENT 'Horas trabajadas en el día',
    overtime_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Horas extras del día',
    night_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Horas nocturnas del día',
    tardiness_minutes INT DEFAULT 0 COMMENT 'Minutos de tardanza',
    early_departure_minutes INT DEFAULT 0 COMMENT 'Minutos salida anticipada',
    lunch_time_minutes INT DEFAULT 0 COMMENT 'Minutos de almuerzo',

    -- Estado del día
    status ENUM('PRESENT', 'ABSENT', 'JUSTIFIED', 'HOLIDAY', 'WEEKEND', 'PARTIAL')
        DEFAULT 'PRESENT' COMMENT 'Estado de asistencia del día',
    is_perfect_attendance TINYINT(1) DEFAULT 0 COMMENT 'Asistencia perfecta ese día',

    -- Cálculos aplicados
    concepts_applied JSON DEFAULT NULL COMMENT 'Conceptos aplicados este día en formato JSON',

    -- Notas
    notes TEXT DEFAULT NULL COMMENT 'Notas adicionales del día',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    KEY idx_summary (summary_id),
    KEY idx_attendance (attendance_id),
    KEY idx_calculation (calculation_id),
    KEY idx_date (attendance_date),
    KEY idx_status (status),

    -- Foreign Keys
    CONSTRAINT fk_detail_summary FOREIGN KEY (summary_id)
        REFERENCES payroll_attendance_summary(id) ON DELETE CASCADE,
    CONSTRAINT fk_detail_attendance FOREIGN KEY (attendance_id)
        REFERENCES attendance(id) ON DELETE SET NULL,
    CONSTRAINT fk_detail_calculation FOREIGN KEY (calculation_id)
        REFERENCES attendance_calculations(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Detalle granular día por día de asistencias incluidas en planilla';


-- ========================================================================
-- DATOS INICIALES: Mapeos predeterminados de conceptos
-- ========================================================================

-- NOTA: Estos INSERTs son ejemplos. Los IDs de conceptos deben ajustarse
-- según los conceptos reales existentes en la tabla concepto

-- Ejemplo 1: Horas Extras 25% (primeras 3 horas)
INSERT INTO attendance_concepts_mapping
(mapping_name, mapping_type, concepto_id, formula_multiplicador, usar_referencia_valor, descripcion, priority, is_active)
VALUES
('Horas Extras 25% → Concepto Horas Extras', 'HORAS_EXTRAS_25', 2, 'SUELDO / 220 * 1.25', 1,
 'Mapeo de horas extras con recargo del 25% según Art. 39 Código de Trabajo', 10, 1);

-- Ejemplo 2: Horas Extras 50% (después de 3 horas)
INSERT INTO attendance_concepts_mapping
(mapping_name, mapping_type, concepto_id, formula_multiplicador, usar_referencia_valor, descripcion, priority, is_active)
VALUES
('Horas Extras 50% → Concepto Horas Extras Adicionales', 'HORAS_EXTRAS_50', 3, 'SUELDO / 220 * 1.50', 1,
 'Mapeo de horas extras con recargo del 50% según Art. 39 Código de Trabajo', 20, 1);

-- Ejemplo 3: Horas Nocturnas
INSERT INTO attendance_concepts_mapping
(mapping_name, mapping_type, concepto_id, formula_multiplicador, usar_referencia_valor, descripcion, priority, is_active)
VALUES
('Horas Nocturnas → Concepto Trabajo Nocturno', 'HORAS_NOCTURNAS', 4, 'SUELDO / 220 * 1.50', 1,
 'Mapeo de horas nocturnas (6PM-6AM) con recargo del 50% según Art. 38', 30, 1);

-- Ejemplo 4: Descuento por Tardanzas
INSERT INTO attendance_concepts_mapping
(mapping_name, mapping_type, concepto_id, formula_multiplicador, usar_referencia_valor, descripcion, priority, is_active)
VALUES
('Tardanzas → Descuento por Tardanzas', 'DESCUENTO_TARDANZAS', 5, 'SUELDO / 220 / 60', 1,
 'Mapeo de minutos de tardanza a descuento proporcional del salario', 40, 1);

-- Ejemplo 5: Bono por Asistencia Perfecta
INSERT INTO attendance_concepts_mapping
(mapping_name, mapping_type, concepto_id, valor_fijo, usar_referencia_valor, descripcion, priority, is_active)
VALUES
('Asistencia Perfecta → Bono Puntualidad', 'BONO_ASISTENCIA_PERFECTA', 6, 50.00, 0,
 'Bono fijo por asistencia perfecta en el período (sin tardanzas ni ausencias)', 50, 1);


-- ========================================================================
-- VISTAS ÚTILES
-- ========================================================================

-- Vista 1: Resumen de asistencias por planilla
CREATE OR REPLACE VIEW v_payroll_attendance_overview AS
SELECT
    pc.id as planilla_id,
    pc.descripcion as planilla_descripcion,
    pc.fecha as planilla_fecha,
    COUNT(DISTINCT pas.employee_id) as total_empleados,
    SUM(pas.total_hours_worked) as total_horas_trabajadas,
    SUM(pas.overtime_hours_25 + pas.overtime_hours_50) as total_horas_extras,
    SUM(pas.total_absences) as total_ausencias,
    AVG(pas.punctuality_score) as promedio_puntualidad,
    SUM(pas.net_attendance_pay) as total_pago_asistencias,
    SUM(CASE WHEN pas.legal_compliant = 0 THEN 1 ELSE 0 END) as empleados_con_violaciones
FROM planilla_cabecera pc
LEFT JOIN payroll_attendance_summary pas ON pc.id = pas.planilla_cabecera_id
GROUP BY pc.id, pc.descripcion, pc.fecha;


-- Vista 2: Empleados con cumplimiento legal comprometido
CREATE OR REPLACE VIEW v_employees_legal_risk AS
SELECT
    e.id as employee_id,
    e.employee_id as employee_code,
    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
    pas.planilla_cabecera_id,
    pas.legal_risk_level,
    pas.legal_violations_count,
    pas.legal_warnings_count,
    pas.critical_alerts,
    pas.legal_compliance_notes,
    pas.period_start,
    pas.period_end
FROM employees e
INNER JOIN payroll_attendance_summary pas ON e.id = pas.employee_id
WHERE pas.legal_compliant = 0 OR pas.legal_risk_level IN ('ALTO', 'CRÍTICO')
ORDER BY
    CASE pas.legal_risk_level
        WHEN 'CRÍTICO' THEN 1
        WHEN 'ALTO' THEN 2
        WHEN 'MEDIO' THEN 3
        WHEN 'BAJO' THEN 4
        ELSE 5
    END,
    pas.legal_violations_count DESC;


-- Vista 3: Mapeos activos de conceptos
CREATE OR REPLACE VIEW v_active_attendance_mappings AS
SELECT
    acm.id,
    acm.mapping_name,
    acm.mapping_type,
    c.concepto as concepto_codigo,
    c.descripcion as concepto_descripcion,
    acm.formula_multiplicador,
    acm.valor_fijo,
    tp.descripcion as tipo_planilla,
    sit.descripcion as situacion,
    acm.priority,
    acm.descripcion
FROM attendance_concepts_mapping acm
INNER JOIN concepto c ON acm.concepto_id = c.id
LEFT JOIN tipos_planilla tp ON acm.tipo_planilla_id = tp.id
LEFT JOIN situaciones sit ON acm.situacion_id = sit.id
WHERE acm.is_active = 1
ORDER BY acm.priority ASC, acm.mapping_type;


-- ========================================================================
-- FIN DE LA MIGRACIÓN
-- ========================================================================
