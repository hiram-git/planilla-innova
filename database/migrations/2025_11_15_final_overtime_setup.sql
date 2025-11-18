-- Final setup: actualizar datos, vistas y triggers

-- ========================================
-- PASO 1: Actualizar registros existentes
-- ========================================

-- Marcar registros sin horas extras como NOT_APPLICABLE
UPDATE attendance_calculations
SET overtime_status = 'NOT_APPLICABLE'
WHERE (overtime_hours = 0 OR overtime_hours IS NULL)
  AND overtime_status != 'NOT_APPLICABLE';

-- Marcar registros con horas extras existentes como APPROVED automáticamente
UPDATE attendance_calculations
SET overtime_status = 'APPROVED',
    overtime_approved_at = COALESCE(calculated_at, NOW()),
    overtime_notes = 'Auto-aprobado durante migración (horas extras pre-existentes)'
WHERE overtime_hours > 0
  AND overtime_status IN ('PENDING', 'NOT_APPLICABLE');

-- ========================================
-- PASO 2: Crear vistas útiles
-- ========================================

CREATE OR REPLACE VIEW v_overtime_pending_approval AS
SELECT
    ac.id AS calculation_id,
    ac.employee_id,
    CONCAT(e.firstname, ' ', e.lastname) AS employee_name,
    e.employee_id AS employee_code,
    ac.date AS work_date,
    ac.time_in,
    ac.time_out,
    ac.total_hours,
    ac.regular_hours,
    ac.overtime_hours,
    ac.overtime_25_hours,
    ac.overtime_50_hours,
    ac.overtime_status,
    ac.overtime_notes,
    ac.calculated_at,
    DATEDIFF(CURDATE(), ac.date) AS days_pending
FROM attendance_calculations ac
INNER JOIN employees e ON ac.employee_id = e.id
WHERE ac.overtime_status = 'PENDING'
  AND ac.overtime_hours > 0
ORDER BY ac.date DESC, e.lastname ASC;

CREATE OR REPLACE VIEW v_overtime_approval_stats AS
SELECT
    ac.overtime_approved_by,
    CONCAT(a.firstname, ' ', a.lastname) AS approver_name,
    ac.overtime_status,
    COUNT(*) AS total_approvals,
    SUM(ac.overtime_hours) AS total_hours_processed,
    MIN(ac.overtime_approved_at) AS first_approval,
    MAX(ac.overtime_approved_at) AS last_approval
FROM attendance_calculations ac
INNER JOIN admin a ON ac.overtime_approved_by = a.id
WHERE ac.overtime_status IN ('APPROVED', 'REJECTED')
  AND ac.overtime_approved_by IS NOT NULL
GROUP BY ac.overtime_approved_by, ac.overtime_status, a.firstname, a.lastname;

-- ========================================
-- PASO 3: Crear triggers de validación
-- ========================================

DELIMITER //

DROP TRIGGER IF EXISTS trg_overtime_rejection_reason//

CREATE TRIGGER trg_overtime_rejection_reason
BEFORE UPDATE ON attendance_calculations
FOR EACH ROW
BEGIN
    IF NEW.overtime_status = 'REJECTED' AND
       (NEW.overtime_rejection_reason IS NULL OR NEW.overtime_rejection_reason = '') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Se requiere razón de rechazo (overtime_rejection_reason) cuando status = REJECTED';
    END IF;

    IF NEW.overtime_status IN ('APPROVED', 'REJECTED') AND
       OLD.overtime_status = 'PENDING' AND
       NEW.overtime_approved_at IS NULL THEN
        SET NEW.overtime_approved_at = NOW();
    END IF;
END//

DROP TRIGGER IF EXISTS trg_overtime_status_auto//

CREATE TRIGGER trg_overtime_status_auto
BEFORE INSERT ON attendance_calculations
FOR EACH ROW
BEGIN
    IF NEW.overtime_hours = 0 OR NEW.overtime_hours IS NULL THEN
        SET NEW.overtime_status = 'NOT_APPLICABLE';
    ELSEIF NEW.overtime_hours > 0 AND NEW.overtime_status IS NULL THEN
        SET NEW.overtime_status = 'PENDING';
    END IF;
END//

DELIMITER ;

-- Verificación
SELECT COUNT(*) as total_pending FROM v_overtime_pending_approval;
SELECT COUNT(*) as total_approved FROM attendance_calculations WHERE overtime_status = 'APPROVED';
SELECT COUNT(*) as total_not_applicable FROM attendance_calculations WHERE overtime_status = 'NOT_APPLICABLE';
