-- =====================================================
-- MIGRACIÓN DE CORRECCIÓN: Vistas Faltantes MySQL 8.0
-- Fecha: 28 de Octubre, 2025
-- Descripción: Crea todas las vistas del módulo de asistencias
--              con sintaxis compatible MySQL 8.0
-- =====================================================

USE planilla_prod;

-- =====================================================
-- PASO 1: Eliminar vistas existentes (si existen)
-- =====================================================

DROP VIEW IF EXISTS v_active_alerts;
DROP VIEW IF EXISTS v_critical_alerts;
DROP VIEW IF EXISTS v_employee_alert_stats;
DROP VIEW IF EXISTS v_alerts_by_type;
DROP VIEW IF EXISTS v_payroll_attendance_overview;
DROP VIEW IF EXISTS v_employees_legal_risk;
DROP VIEW IF EXISTS v_active_attendance_mappings;

-- =====================================================
-- VISTAS DEL SISTEMA DE ALERTAS
-- =====================================================

-- Vista 1: Alertas activas (pendientes o reconocidas)
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

SELECT 'Vista v_active_alerts creada' as resultado;

-- Vista 2: Alertas críticas sin resolver
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

SELECT 'Vista v_critical_alerts recreada' as resultado;

-- Vista 3: Estadísticas de alertas por empleado
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

SELECT 'Vista v_employee_alert_stats creada' as resultado;

-- Vista 4: Resumen de alertas por tipo
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

SELECT 'Vista v_alerts_by_type creada' as resultado;

-- =====================================================
-- VISTAS DEL SISTEMA DE INTEGRACIÓN PLANILLAS-ASISTENCIAS
-- =====================================================

-- Vista 5: Resumen de asistencias por planilla
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

SELECT 'Vista v_payroll_attendance_overview creada' as resultado;

-- Vista 6: Empleados con cumplimiento legal comprometido
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

SELECT 'Vista v_employees_legal_risk creada' as resultado;

-- Vista 7: Mapeos activos de conceptos
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

SELECT 'Vista v_active_attendance_mappings creada' as resultado;

-- =====================================================
-- VERIFICACIÓN FINAL
-- =====================================================

SELECT
    'Todas las vistas creadas exitosamente' as mensaje,
    COUNT(*) as total_vistas
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = 'planilla_prod'
AND TABLE_NAME IN (
    'v_active_alerts',
    'v_critical_alerts',
    'v_employee_alert_stats',
    'v_alerts_by_type',
    'v_payroll_attendance_overview',
    'v_employees_legal_risk',
    'v_active_attendance_mappings'
);

-- Listar todas las vistas creadas
SELECT
    TABLE_NAME as vista,
    'Creada correctamente' as estado
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = 'planilla_prod'
AND TABLE_NAME LIKE 'v_%alert%'
OR TABLE_NAME LIKE 'v_%attendance%'
OR TABLE_NAME LIKE 'v_%legal%'
ORDER BY TABLE_NAME;

-- =====================================================
-- FIN DE LA MIGRACIÓN DE VISTAS
-- =====================================================
