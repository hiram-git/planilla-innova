-- =====================================================
-- MIGRACIÓN DE CORRECCIÓN: Stored Procedures MySQL 8.0
-- Fecha: 28 de Octubre, 2025
-- Descripción: Crea stored procedures del módulo de asistencias
--              sin DELIMITER (compatible GUI/PHP)
-- =====================================================

USE planilla_prod;

-- =====================================================
-- PASO 1: Eliminar procedures existentes (si existen)
-- =====================================================

DROP PROCEDURE IF EXISTS sp_get_employee_active_alerts;
DROP PROCEDURE IF EXISTS sp_cleanup_old_resolved_alerts;
DROP PROCEDURE IF EXISTS sp_daily_alerts_summary;

-- =====================================================
-- STORED PROCEDURES DEL SISTEMA DE ALERTAS
-- =====================================================

DELIMITER //

-- Procedimiento 1: Obtener alertas activas de un empleado
CREATE PROCEDURE sp_get_employee_active_alerts(
    IN p_employee_id INT,
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

-- Procedimiento 2: Limpiar alertas resueltas antiguas
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

-- Procedimiento 3: Estadísticas de alertas del día
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

SELECT 'Procedures creados exitosamente' as resultado;

-- =====================================================
-- VERIFICACIÓN FINAL
-- =====================================================

SELECT
    'Stored procedures creados exitosamente' as mensaje,
    COUNT(*) as total_procedures
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = 'planilla_prod'
AND ROUTINE_TYPE = 'PROCEDURE'
AND ROUTINE_NAME IN (
    'sp_get_employee_active_alerts',
    'sp_cleanup_old_resolved_alerts',
    'sp_daily_alerts_summary'
);

-- Listar procedures creados
SELECT
    ROUTINE_NAME as procedimiento,
    ROUTINE_TYPE as tipo,
    'Creado correctamente' as estado
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = 'planilla_prod'
AND ROUTINE_TYPE = 'PROCEDURE'
AND ROUTINE_NAME LIKE 'sp_%alert%'
ORDER BY ROUTINE_NAME;

-- =====================================================
-- FIN DE LA MIGRACIÓN DE PROCEDURES
-- =====================================================
