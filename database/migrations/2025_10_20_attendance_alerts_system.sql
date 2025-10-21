-- =====================================================
-- MIGRACIÓN: Sistema de Alertas de Asistencia
-- Versión: v3.4.6
-- Fecha: 20 de Octubre, 2025
-- Descripción: Implementación sistema de alertas y notificaciones
--              automáticas basadas en legislación panameña
-- =====================================================

USE planilla_prod;

-- =====================================================
-- TABLA: attendance_alerts (Alertas de Cumplimiento Legal)
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Referencias
    employee_id INT NOT NULL COMMENT 'ID del empleado asociado',
    attendance_id INT COMMENT 'ID de asistencia específica (si aplica)',
    calculation_id INT COMMENT 'ID del cálculo de asistencia (si aplica)',

    -- Tipo y severidad de alerta
    alert_type VARCHAR(100) NOT NULL COMMENT 'Tipo de alerta',
    severity ENUM('INFO', 'WARNING', 'CRITICAL') NOT NULL DEFAULT 'INFO' COMMENT 'Nivel de severidad',

    -- Contenido de la alerta
    title VARCHAR(255) NOT NULL COMMENT 'Título descriptivo corto',
    message TEXT NOT NULL COMMENT 'Descripción detallada del problema',
    article_reference VARCHAR(100) COMMENT 'Referencia legal (Ej: Art. 31 - Código de Trabajo)',

    -- Datos adicionales flexibles (JSON)
    metadata JSON COMMENT 'Datos adicionales específicos del tipo de alerta',

    -- Período temporal
    date DATE NOT NULL COMMENT 'Fecha principal de la alerta',
    period_start DATE COMMENT 'Fecha inicial del período (para alertas de rango)',
    period_end DATE COMMENT 'Fecha final del período (para alertas de rango)',

    -- Estado y seguimiento
    status ENUM('PENDING', 'ACKNOWLEDGED', 'RESOLVED', 'DISMISSED')
        NOT NULL DEFAULT 'PENDING' COMMENT 'Estado de la alerta',

    acknowledged_by INT COMMENT 'Usuario que reconoció la alerta',
    acknowledged_at DATETIME COMMENT 'Fecha y hora de reconocimiento',
    resolved_at DATETIME COMMENT 'Fecha y hora de resolución',
    notes TEXT COMMENT 'Notas de seguimiento y resolución',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices para rendimiento
    INDEX idx_employee_id (employee_id),
    INDEX idx_attendance_id (attendance_id),
    INDEX idx_calculation_id (calculation_id),
    INDEX idx_alert_type (alert_type),
    INDEX idx_severity (severity),
    INDEX idx_status (status),
    INDEX idx_date (date),
    INDEX idx_employee_status (employee_id, status),
    INDEX idx_severity_status (severity, status),
    INDEX idx_date_range (period_start, period_end),
    INDEX idx_created_at (created_at),

    -- Foreign Keys
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE SET NULL,
    FOREIGN KEY (calculation_id) REFERENCES attendance_calculations(id) ON DELETE SET NULL,
    FOREIGN KEY (acknowledged_by) REFERENCES admin(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Alertas automáticas de cumplimiento legal en asistencias';

-- =====================================================
-- COMENTARIOS SOBRE TIPOS DE ALERTAS
-- =====================================================
/*
alert_type puede contener los siguientes valores:

ALERTAS DE JORNADA:
- EXCESO_JORNADA_DIARIA: Excede 8 horas diarias (Art. 31)
- EXCESO_JORNADA_SEMANAL: Excede 48 horas semanales (Art. 31)
- EXCESO_JORNADA_MAXIMA: Excede 11 horas (8 reg + 3 extras)
- TIEMPO_COMIDA_INSUFICIENTE: Menos de 30 min almuerzo (Art. 35)
- EXCESO_JORNADA_NOCTURNA: Excede 7 horas nocturnas (Art. 38)

ALERTAS DE DESCANSO:
- DIAS_CONSECUTIVOS_SIN_DESCANSO: Más de 6 días consecutivos (Art. 48)
- VIOLACION_DESCANSO_SEMANAL: No cumple descanso semanal obligatorio
- TRABAJO_FERIADO_SIN_COMPENSACION: Trabajo en feriado sin compensatorio

ALERTAS DE AUSENCIAS:
- AUSENCIAS_INJUSTIFICADAS_GRAVES: 3+ ausencias en mes (Art. 213)
- AUSENCIA_SIN_NOTIFICAR: Ausencia sin justificación
- AUSENCIA_PENDIENTE_JUSTIFICAR: Ausencia requiere justificación

ALERTAS DE PUNTUALIDAD:
- TARDANZAS_RECURRENTES: Múltiples tardanzas en período
- PATRON_TARDANZAS: Patrón consistente de impuntualidad
- SALIDA_ANTICIPADA_RECURRENTE: Salidas anticipadas frecuentes

ALERTAS CRÍTICAS:
- RIESGO_DESPIDO: Condiciones para despido justificado
- VIOLACION_LEGAL_MULTIPLE: Múltiples violaciones simultáneas
- RIESGO_ALTO_EMPLEADO: Empleado con múltiples alertas críticas

ALERTAS DE INFORMACIÓN:
- CERCA_LIMITE_JORNADA: Cerca del límite de horas (>90%)
- CERCA_LIMITE_SEMANAL: Cerca del límite semanal
- RECORDATORIO_DESCANSO: Recordatorio próximo día descanso

metadata JSON puede contener:
{
  "hours_exceeded": 3.5,
  "total_hours": 11.5,
  "max_allowed": 8,
  "absences_count": 3,
  "threshold": 3,
  "risk_level": "CRÍTICO",
  "consecutive_days": 7,
  "tardiness_count": 5,
  "pattern_detected": "Lunes tardanzas recurrentes",
  "legal_article": "Art. 31",
  "violation_details": "..."
}
*/

-- =====================================================
-- ÍNDICES COMPUESTOS ADICIONALES PARA CONSULTAS FRECUENTES
-- =====================================================

-- Para dashboard de alertas pendientes por severidad
CREATE INDEX idx_status_severity_date ON attendance_alerts(status, severity, date DESC);

-- Para alertas del empleado con estado
CREATE INDEX idx_employee_severity_status ON attendance_alerts(employee_id, severity, status);

-- Para reportes de alertas por tipo y fecha
CREATE INDEX idx_type_date ON attendance_alerts(alert_type, date DESC);

-- Para búsqueda de alertas críticas no resueltas
-- NOTA: MariaDB no soporta índices con WHERE clause (filtered indexes)
-- Se usa índice compuesto estándar que también beneficia estas consultas
-- CREATE INDEX idx_critical_pending ON attendance_alerts(severity, status, date);

-- =====================================================
-- VISTAS ÚTILES PARA CONSULTAS FRECUENTES
-- =====================================================

-- Vista de alertas activas (pendientes o reconocidas)
CREATE OR REPLACE VIEW v_active_alerts AS
SELECT
    aa.*,
    e.firstname,
    e.lastname,
    e.employee_id as dni,
    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
    u.username as acknowledged_by_username
FROM attendance_alerts aa
INNER JOIN employees e ON aa.employee_id = e.id
LEFT JOIN admin u ON aa.acknowledged_by = u.id
WHERE aa.status IN ('PENDING', 'ACKNOWLEDGED')
ORDER BY
    FIELD(aa.severity, 'CRITICAL', 'WARNING', 'INFO'),
    aa.date DESC;

-- Vista de alertas críticas sin resolver
CREATE OR REPLACE VIEW v_critical_alerts AS
SELECT
    aa.*,
    e.firstname,
    e.lastname,
    e.employee_id as dni,
    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
    DATEDIFF(CURDATE(), aa.date) as days_pending
FROM attendance_alerts aa
INNER JOIN employees e ON aa.employee_id = e.id
WHERE aa.severity = 'CRITICAL'
AND aa.status IN ('PENDING', 'ACKNOWLEDGED')
ORDER BY aa.date DESC;

-- Vista estadísticas de alertas por empleado
CREATE OR REPLACE VIEW v_employee_alert_stats AS
SELECT
    e.id as employee_id,
    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
    COUNT(*) as total_alerts,
    SUM(CASE WHEN aa.severity = 'CRITICAL' THEN 1 ELSE 0 END) as critical_count,
    SUM(CASE WHEN aa.severity = 'WARNING' THEN 1 ELSE 0 END) as warning_count,
    SUM(CASE WHEN aa.severity = 'INFO' THEN 1 ELSE 0 END) as info_count,
    SUM(CASE WHEN aa.status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN aa.status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved_count,
    MAX(aa.date) as last_alert_date
FROM employees e
LEFT JOIN attendance_alerts aa ON e.id = aa.employee_id
WHERE aa.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY e.id, e.firstname, e.lastname
HAVING total_alerts > 0
ORDER BY critical_count DESC, warning_count DESC;

-- Vista resumen de alertas por tipo
CREATE OR REPLACE VIEW v_alerts_by_type AS
SELECT
    aa.alert_type,
    aa.severity,
    COUNT(*) as total_count,
    SUM(CASE WHEN aa.status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN aa.status = 'ACKNOWLEDGED' THEN 1 ELSE 0 END) as acknowledged_count,
    SUM(CASE WHEN aa.status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved_count,
    SUM(CASE WHEN aa.status = 'DISMISSED' THEN 1 ELSE 0 END) as dismissed_count,
    MIN(aa.date) as first_occurrence,
    MAX(aa.date) as last_occurrence
FROM attendance_alerts aa
WHERE aa.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY aa.alert_type, aa.severity
ORDER BY
    FIELD(aa.severity, 'CRITICAL', 'WARNING', 'INFO'),
    total_count DESC;

-- =====================================================
-- TRIGGERS PARA AUDITORÍA AUTOMÁTICA
-- =====================================================

DELIMITER //

-- Trigger: Registrar reconocimiento de alerta
CREATE TRIGGER trg_alert_acknowledged
BEFORE UPDATE ON attendance_alerts
FOR EACH ROW
BEGIN
    IF NEW.status = 'ACKNOWLEDGED' AND OLD.status = 'PENDING' THEN
        IF NEW.acknowledged_at IS NULL THEN
            SET NEW.acknowledged_at = NOW();
        END IF;
    END IF;
END//

-- Trigger: Registrar resolución de alerta
CREATE TRIGGER trg_alert_resolved
BEFORE UPDATE ON attendance_alerts
FOR EACH ROW
BEGIN
    IF NEW.status = 'RESOLVED' AND OLD.status != 'RESOLVED' THEN
        IF NEW.resolved_at IS NULL THEN
            SET NEW.resolved_at = NOW();
        END IF;
    END IF;
END//

DELIMITER ;

-- =====================================================
-- STORED PROCEDURES ÚTILES
-- =====================================================

DELIMITER //

-- Procedimiento: Obtener alertas activas de un empleado
CREATE PROCEDURE sp_get_employee_active_alerts(
    IN p_employee_id BIGINT,
    IN p_severity VARCHAR(20)
)
BEGIN
    SELECT
        aa.*,
        DATEDIFF(CURDATE(), aa.date) as days_pending
    FROM attendance_alerts aa
    WHERE aa.employee_id = p_employee_id
    AND aa.status IN ('PENDING', 'ACKNOWLEDGED')
    AND (p_severity IS NULL OR aa.severity = p_severity)
    ORDER BY
        FIELD(aa.severity, 'CRITICAL', 'WARNING', 'INFO'),
        aa.date DESC;
END//

-- Procedimiento: Limpiar alertas resueltas antiguas
CREATE PROCEDURE sp_cleanup_old_resolved_alerts(
    IN p_days_old INT
)
BEGIN
    DECLARE rows_deleted INT;

    DELETE FROM attendance_alerts
    WHERE status = 'RESOLVED'
    AND resolved_at < DATE_SUB(CURDATE(), INTERVAL p_days_old DAY);

    SET rows_deleted = ROW_COUNT();

    SELECT
        rows_deleted as deleted_count,
        CONCAT('Eliminadas ', rows_deleted, ' alertas resueltas con más de ', p_days_old, ' días') as message;
END//

-- Procedimiento: Estadísticas de alertas del día
CREATE PROCEDURE sp_daily_alerts_summary(
    IN p_date DATE
)
BEGIN
    SELECT
        COUNT(*) as total_alerts,
        SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) as critical_count,
        SUM(CASE WHEN severity = 'WARNING' THEN 1 ELSE 0 END) as warning_count,
        SUM(CASE WHEN severity = 'INFO' THEN 1 ELSE 0 END) as info_count,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
        COUNT(DISTINCT employee_id) as affected_employees,
        COUNT(DISTINCT alert_type) as different_alert_types
    FROM attendance_alerts
    WHERE date = p_date;
END//

DELIMITER ;

-- =====================================================
-- DATOS DE PRUEBA (COMENTADOS - Descomentar si se necesita)
-- =====================================================

/*
-- Insertar alerta de prueba - Exceso jornada diaria
INSERT INTO attendance_alerts (
    employee_id,
    alert_type,
    severity,
    title,
    message,
    article_reference,
    metadata,
    date
) VALUES (
    1,
    'EXCESO_JORNADA_DIARIA',
    'WARNING',
    'Exceso de jornada diaria detectado',
    'El empleado trabajó 9.5 horas, excediendo las 8 horas regulares permitidas en 1.5 horas.',
    'Art. 31 - Código de Trabajo',
    JSON_OBJECT(
        'total_hours', 9.5,
        'max_allowed', 8,
        'hours_exceeded', 1.5,
        'overtime_hours', 1.5
    ),
    CURDATE()
);

-- Insertar alerta de prueba - Ausencias graves
INSERT INTO attendance_alerts (
    employee_id,
    alert_type,
    severity,
    title,
    message,
    article_reference,
    metadata,
    date,
    period_start,
    period_end
) VALUES (
    2,
    'AUSENCIAS_INJUSTIFICADAS_GRAVES',
    'CRITICAL',
    'Falta grave: 3 ausencias injustificadas en el mes',
    'El empleado ha acumulado 3 ausencias injustificadas en octubre 2025, lo cual constituye causal de despido sin responsabilidad patronal.',
    'Art. 213 - Código de Trabajo',
    JSON_OBJECT(
        'absences_count', 3,
        'threshold', 3,
        'risk_level', 'CRÍTICO',
        'can_be_dismissed', true
    ),
    CURDATE(),
    DATE_FORMAT(CURDATE(), '%Y-%m-01'),
    LAST_DAY(CURDATE())
);
*/

-- =====================================================
-- ESTADÍSTICAS DE LA MIGRACIÓN
-- =====================================================

SELECT 'Migración de Sistema de Alertas completada' as status;
SELECT COUNT(*) as total_alerts FROM attendance_alerts;
SELECT 'Vistas creadas: v_active_alerts, v_critical_alerts, v_employee_alert_stats, v_alerts_by_type' as info;
SELECT 'Triggers creados: trg_alert_acknowledged, trg_alert_resolved' as info;
SELECT 'Stored Procedures: sp_get_employee_active_alerts, sp_cleanup_old_resolved_alerts, sp_daily_alerts_summary' as info;

-- =====================================================
-- FIN DE LA MIGRACIÓN
-- =====================================================
