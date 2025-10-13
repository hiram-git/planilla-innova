-- =====================================================
-- MIGRACIÓN: Cálculos Avanzados de Asistencias - Subfase 7.2
-- Fecha: 10 de Octubre, 2025
-- Descripción: Tabla para almacenar cálculos procesados de asistencias
-- =====================================================

-- =====================================================
-- Tabla: attendance_calculations
-- Almacena resultados de cálculos avanzados de asistencias
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance_calculations (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Referencias
    attendance_id INT NOT NULL COMMENT 'FK a tabla attendance',
    employee_id INT NOT NULL COMMENT 'FK a tabla employees',

    -- Fecha y horario
    date DATE NOT NULL COMMENT 'Fecha de la asistencia',
    schedule_id INT COMMENT 'Horario asignado al empleado ese día',

    -- Marcaciones
    time_in TIME COMMENT 'Hora de entrada real',
    time_out TIME COMMENT 'Hora de salida real',
    scheduled_time_in TIME COMMENT 'Hora de entrada programada',
    scheduled_time_out TIME COMMENT 'Hora de salida programada',

    -- Horas trabajadas
    total_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Total horas trabajadas',
    regular_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Horas regulares',
    overtime_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Horas extras totales',
    overtime_25_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Horas extras al 25%',
    overtime_50_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Horas extras al 50%',
    night_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Horas nocturnas (6PM-6AM)',
    holiday_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Horas trabajadas en feriado',

    -- Tardanzas y ausencias
    tardiness_minutes INT DEFAULT 0 COMMENT 'Minutos de tardanza',
    is_late TINYINT(1) DEFAULT 0 COMMENT 'Llegó tarde',
    early_departure_minutes INT DEFAULT 0 COMMENT 'Minutos salida anticipada',
    is_absent TINYINT(1) DEFAULT 0 COMMENT 'Ausencia completa',
    absence_type VARCHAR(50) COMMENT 'JUSTIFIED, UNJUSTIFIED, UNKNOWN',

    -- Tipo de día
    is_working_day TINYINT(1) DEFAULT 1 COMMENT 'Es día laboral',
    is_holiday TINYINT(1) DEFAULT 0 COMMENT 'Es feriado',
    is_weekend TINYINT(1) DEFAULT 0 COMMENT 'Es fin de semana',
    day_type VARCHAR(50) COMMENT 'LABORAL, FERIADO, NO_LABORAL',

    -- Almuerzo
    lunch_time_minutes INT DEFAULT 60 COMMENT 'Minutos descontados por almuerzo',

    -- Métricas adicionales
    is_perfect_attendance TINYINT(1) DEFAULT 0 COMMENT 'Asistencia perfecta (sin tardanza, completo)',
    punctuality_score DECIMAL(5,2) COMMENT 'Score de puntualidad (0-100)',

    -- Notas y observaciones
    notes TEXT COMMENT 'Observaciones del cálculo',
    calculation_details JSON COMMENT 'Detalles adicionales en JSON',

    -- Procesamiento
    calculation_version VARCHAR(20) DEFAULT 'v1.0' COMMENT 'Versión del algoritmo de cálculo',
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp del cálculo',
    recalculated_at TIMESTAMP NULL COMMENT 'Última recalculación',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE SET NULL,

    -- Índices
    UNIQUE KEY unique_attendance_calc (attendance_id),
    INDEX idx_employee_date (employee_id, date),
    INDEX idx_date (date),
    INDEX idx_is_late (is_late),
    INDEX idx_is_absent (is_absent),
    INDEX idx_is_holiday (is_holiday),
    INDEX idx_overtime (overtime_hours),
    INDEX idx_calculated_at (calculated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Cálculos avanzados de asistencias con métricas detalladas';

-- =====================================================
-- Tabla: attendance_absence_log
-- Registro de ausencias detectadas (días sin marcación)
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance_absence_log (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Referencias
    employee_id INT NOT NULL COMMENT 'FK a tabla employees',

    -- Fecha y tipo
    absence_date DATE NOT NULL COMMENT 'Fecha de la ausencia',
    absence_type VARCHAR(50) NOT NULL DEFAULT 'UNJUSTIFIED' COMMENT 'JUSTIFIED, UNJUSTIFIED, PENDING',

    -- Justificación
    justified TINYINT(1) DEFAULT 0 COMMENT 'Ausencia justificada',
    justification_type VARCHAR(50) COMMENT 'MEDICAL, PERMISSION, VACATION, OTHER',
    justification_document VARCHAR(255) COMMENT 'Ruta del documento de justificación',
    justification_notes TEXT COMMENT 'Notas de la justificación',

    -- Día laboral
    is_working_day TINYINT(1) DEFAULT 1 COMMENT 'Era día laboral esperado',
    day_type VARCHAR(50) COMMENT 'LABORAL, FERIADO, NO_LABORAL',

    -- Detección
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Cuándo se detectó la ausencia',
    detection_method VARCHAR(50) DEFAULT 'AUTO' COMMENT 'AUTO, MANUAL, SYNC',

    -- Resolución
    resolved TINYINT(1) DEFAULT 0 COMMENT 'Ausencia resuelta/justificada',
    resolved_at TIMESTAMP NULL COMMENT 'Cuándo se resolvió',
    resolved_by INT COMMENT 'Usuario que resolvió (FK users)',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,

    -- Índices
    UNIQUE KEY unique_employee_absence (employee_id, absence_date),
    INDEX idx_absence_date (absence_date),
    INDEX idx_absence_type (absence_type),
    INDEX idx_justified (justified),
    INDEX idx_resolved (resolved),
    INDEX idx_detected_at (detected_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de ausencias detectadas con justificaciones';

-- =====================================================
-- Verificación de tablas creadas
-- =====================================================
SELECT
    'Tablas creadas exitosamente' as mensaje,
    (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE()
     AND table_name IN ('attendance_calculations', 'attendance_absence_log')) as tablas_creadas;
