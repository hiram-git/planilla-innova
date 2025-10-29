-- =====================================================
-- MIGRACIÓN DE CORRECCIÓN: Problemas Críticos MySQL 8.0
-- Fecha: 28 de Octubre, 2025
-- Descripción: Corrige problemas de migración MariaDB 10.4 → MySQL 8.0
--              en el módulo de asistencias
-- =====================================================

USE planilla_prod;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- PASO 1: Corregir Foreign Key de attendance_calculations
-- =====================================================

-- Verificar si la FK existe apuntando a attendance
SELECT
    'Verificando FK actual de attendance_calculations...' as mensaje;

-- Eliminar FK incorrecta si existe (apunta a attendance en lugar de attendance_detail)
SET @fk_name = (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'planilla_prod'
    AND TABLE_NAME = 'attendance_calculations'
    AND COLUMN_NAME = 'attendance_id'
    AND REFERENCED_TABLE_NAME = 'attendance'
    LIMIT 1
);

SET @sql = IF(@fk_name IS NOT NULL,
    CONCAT('ALTER TABLE attendance_calculations DROP FOREIGN KEY ', @fk_name),
    'SELECT "FK antigua no encontrada, continuando..." as mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar índice único antiguo si existe
SET @idx_name = (
    SELECT INDEX_NAME
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'planilla_prod'
    AND TABLE_NAME = 'attendance_calculations'
    AND COLUMN_NAME = 'attendance_id'
    AND INDEX_NAME = 'unique_attendance_calc'
    LIMIT 1
);

SET @sql = IF(@idx_name IS NOT NULL,
    'ALTER TABLE attendance_calculations DROP INDEX unique_attendance_calc',
    'SELECT "Índice único antiguo no encontrado, continuando..." as mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y limpiar registros huérfanos antes de renombrar
SELECT COUNT(*) as registros_huerfanos
FROM attendance_calculations ac
LEFT JOIN attendance_detail ad ON ac.attendance_id = ad.id
WHERE ad.id IS NULL;

DELETE ac FROM attendance_calculations ac
LEFT JOIN attendance_detail ad ON ac.attendance_id = ad.id
WHERE ad.id IS NULL;

-- Renombrar columna attendance_id a attendance_detail_id si aún no está renombrada
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'planilla_prod'
    AND TABLE_NAME = 'attendance_calculations'
    AND COLUMN_NAME = 'attendance_id'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE attendance_calculations
     CHANGE COLUMN attendance_id attendance_detail_id INT NOT NULL
     COMMENT "FK a tabla attendance_detail"',
    'SELECT "Columna ya renombrada a attendance_detail_id" as mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar nueva FK apuntando a attendance_detail (solo si no existe)
SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'planilla_prod'
    AND TABLE_NAME = 'attendance_calculations'
    AND CONSTRAINT_NAME = 'fk_attendance_detail'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE attendance_calculations
     ADD CONSTRAINT fk_attendance_detail
     FOREIGN KEY (attendance_detail_id) REFERENCES attendance_detail(id) ON DELETE CASCADE',
    'SELECT "FK fk_attendance_detail ya existe" as mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar índice único sobre attendance_detail_id (solo si no existe)
SET @idx_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'planilla_prod'
    AND TABLE_NAME = 'attendance_calculations'
    AND INDEX_NAME = 'unique_attendance_detail_calc'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE attendance_calculations
     ADD UNIQUE KEY unique_attendance_detail_calc (attendance_detail_id)',
    'SELECT "Índice único ya existe" as mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'FK de attendance_calculations corregida exitosamente' as resultado;

-- =====================================================
-- PASO 2: Corregir tipo de dato JSON en attendance_alerts
-- =====================================================

-- Modificar columna metadata de longtext a json
ALTER TABLE attendance_alerts
MODIFY COLUMN metadata JSON COMMENT 'Datos adicionales específicos del tipo de alerta';

SELECT 'Tipo de dato metadata corregido a JSON' as resultado;

-- =====================================================
-- PASO 3: Crear tabla payroll_attendance_summary (si no existe)
-- =====================================================

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

SELECT 'Tabla payroll_attendance_summary creada' as resultado;

-- =====================================================
-- PASO 4: Crear tabla payroll_attendance_details (si no existe)
-- =====================================================

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

SELECT 'Tabla payroll_attendance_details creada' as resultado;

-- =====================================================
-- PASO 5: Verificación Final
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;

SELECT
    'Correcciones críticas completadas' as mensaje,
    (SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = 'planilla_prod'
     AND TABLE_NAME IN ('payroll_attendance_summary', 'payroll_attendance_details')) as tablas_creadas,
    (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = 'planilla_prod'
     AND TABLE_NAME = 'attendance_calculations'
     AND CONSTRAINT_NAME = 'fk_attendance_detail') as fk_corregida;

-- Verificar estructura de attendance_calculations
SELECT
    'Estructura attendance_calculations' as info,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'planilla_prod'
AND TABLE_NAME = 'attendance_calculations'
AND COLUMN_NAME = 'attendance_detail_id';

-- =====================================================
-- FIN DE LA MIGRACIÓN CRÍTICA
-- =====================================================
