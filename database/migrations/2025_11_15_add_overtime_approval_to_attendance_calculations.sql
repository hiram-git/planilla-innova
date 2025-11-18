-- =====================================================
-- Migración: Sistema de aprobación de horas extras
-- Fecha: 15 de Noviembre, 2025
-- Propósito: Implementar workflow de aprobación de horas extras
-- Impacto: Las horas extras requieren aprobación antes de incluirse en planilla
-- =====================================================

-- ========================================
-- PASO 1: Agregar campos de aprobación
-- ========================================

ALTER TABLE attendance_calculations
ADD COLUMN overtime_status ENUM('PENDING', 'APPROVED', 'REJECTED', 'NOT_APPLICABLE')
    NOT NULL DEFAULT 'NOT_APPLICABLE'
    COMMENT 'Estado de aprobación de horas extras: PENDING=Pendiente, APPROVED=Aprobada, REJECTED=Rechazada, NOT_APPLICABLE=No hay horas extras'
    AFTER overtime_50_hours,

ADD COLUMN overtime_approved_by INT UNSIGNED NULL
    COMMENT 'ID del usuario que aprobó/rechazó las horas extras'
    AFTER overtime_status,

ADD COLUMN overtime_approved_at TIMESTAMP NULL
    COMMENT 'Fecha y hora de aprobación/rechazo'
    AFTER overtime_approved_by,

ADD COLUMN overtime_rejection_reason VARCHAR(500) NULL
    COMMENT 'Razón del rechazo (obligatorio si status = REJECTED)'
    AFTER overtime_approved_at,

ADD COLUMN overtime_notes TEXT NULL
    COMMENT 'Notas adicionales sobre las horas extras (justificación, proyecto, etc.)'
    AFTER overtime_rejection_reason;

-- ========================================
-- PASO 2: Agregar Foreign Keys
-- ========================================

-- FK a tabla de usuarios (approved_by)
ALTER TABLE attendance_calculations
ADD CONSTRAINT fk_attendance_calc_approved_by
    FOREIGN KEY (overtime_approved_by)
    REFERENCES admin(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- ========================================
-- PASO 3: Crear índices de búsqueda
-- ========================================

-- Índice para buscar horas extras pendientes de aprobación
CREATE INDEX idx_overtime_pending
ON attendance_calculations(overtime_status, date, employee_id)
WHERE overtime_status IN ('PENDING', 'APPROVED');

-- Índice para buscar aprobaciones por usuario
CREATE INDEX idx_overtime_approved_by
ON attendance_calculations(overtime_approved_by, overtime_approved_at);

-- Índice compuesto para reportes de horas extras
CREATE INDEX idx_overtime_status_date
ON attendance_calculations(overtime_status, date, overtime_hours);

-- ========================================
-- PASO 4: Actualizar registros existentes
-- ========================================

-- Marcar registros sin horas extras como NOT_APPLICABLE
UPDATE attendance_calculations
SET overtime_status = 'NOT_APPLICABLE'
WHERE overtime_hours = 0 OR overtime_hours IS NULL;

-- Marcar registros con horas extras existentes como APPROVED automáticamente
-- (para mantener compatibilidad con planillas ya procesadas)
UPDATE attendance_calculations
SET overtime_status = 'APPROVED',
    overtime_approved_at = calculated_at,
    overtime_notes = 'Auto-aprobado durante migración (horas extras pre-existentes)'
WHERE overtime_hours > 0
  AND overtime_status = 'NOT_APPLICABLE';

-- ========================================
-- PASO 5: Crear vista útil para supervisores
-- ========================================

CREATE OR REPLACE VIEW v_overtime_pending_approval AS
SELECT
    ac.id AS calculation_id,
    ac.employee_id,
    CONCAT(e.firstname, ' ', e.lastname) AS employee_name,
    e.employee_id AS employee_code,
    d.nombre AS department,
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
LEFT JOIN departamentos d ON e.department_id = d.id
WHERE ac.overtime_status = 'PENDING'
  AND ac.overtime_hours > 0
ORDER BY ac.date DESC, e.lastname ASC;

-- Vista para estadísticas de aprobaciones por aprobador
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
-- PASO 6: Crear triggers de validación
-- ========================================

DELIMITER //

-- Trigger para validar que rechazos tengan razón
CREATE TRIGGER trg_overtime_rejection_reason
BEFORE UPDATE ON attendance_calculations
FOR EACH ROW
BEGIN
    -- Si se rechaza, debe haber razón
    IF NEW.overtime_status = 'REJECTED' AND
       (NEW.overtime_rejection_reason IS NULL OR NEW.overtime_rejection_reason = '') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Se requiere razón de rechazo (overtime_rejection_reason) cuando status = REJECTED';
    END IF;

    -- Si se aprueba o rechaza, debe establecer timestamp
    IF NEW.overtime_status IN ('APPROVED', 'REJECTED') AND
       OLD.overtime_status = 'PENDING' AND
       NEW.overtime_approved_at IS NULL THEN
        SET NEW.overtime_approved_at = NOW();
    END IF;
END//

-- Trigger para auto-detectar si hay horas extras
CREATE TRIGGER trg_overtime_status_auto
BEFORE INSERT ON attendance_calculations
FOR EACH ROW
BEGIN
    -- Si no tiene horas extras, marcar como NOT_APPLICABLE
    IF NEW.overtime_hours = 0 OR NEW.overtime_hours IS NULL THEN
        SET NEW.overtime_status = 'NOT_APPLICABLE';

    -- Si tiene horas extras pero no se especificó status, marcar como PENDING
    ELSEIF NEW.overtime_hours > 0 AND NEW.overtime_status IS NULL THEN
        SET NEW.overtime_status = 'PENDING';
    END IF;
END//

DELIMITER ;

-- ========================================
-- VERIFICACIÓN
-- ========================================

-- Consulta para verificar que los campos se agregaron correctamente
-- SELECT id, employee_id, date, overtime_hours,
--        overtime_status, overtime_approved_by, overtime_approved_at
-- FROM attendance_calculations
-- WHERE overtime_hours > 0
-- LIMIT 10;

-- Verificar vista de pendientes
-- SELECT * FROM v_overtime_pending_approval LIMIT 5;

-- Verificar estadísticas de aprobadores
-- SELECT * FROM v_overtime_approval_stats;

-- ========================================
-- ROLLBACK (si es necesario)
-- ========================================

/*
-- Para revertir esta migración:

DROP TRIGGER IF EXISTS trg_overtime_rejection_reason;
DROP TRIGGER IF EXISTS trg_overtime_status_auto;
DROP VIEW IF EXISTS v_overtime_pending_approval;
DROP VIEW IF EXISTS v_overtime_approval_stats;

ALTER TABLE attendance_calculations
DROP FOREIGN KEY fk_attendance_calc_approved_by,
DROP INDEX idx_overtime_pending,
DROP INDEX idx_overtime_approved_by,
DROP INDEX idx_overtime_status_date,
DROP COLUMN overtime_status,
DROP COLUMN overtime_approved_by,
DROP COLUMN overtime_approved_at,
DROP COLUMN overtime_rejection_reason,
DROP COLUMN overtime_notes;
*/

-- ========================================
-- NOTAS DE IMPLEMENTACIÓN
-- ========================================

/*
NOTAS IMPORTANTES:

1. FLUJO DE APROBACIÓN:
   - AttendanceCalculator detecta horas extras → marca status = PENDING
   - Supervisor/RRHH revisa y aprueba/rechaza desde UI
   - Si APPROVED → se incluye en planilla
   - Si REJECTED → se excluye de planilla (o se calcula con horas regulares solamente)

2. ESTADOS:
   - NOT_APPLICABLE: No hay horas extras (0 horas)
   - PENDING: Hay horas extras esperando aprobación
   - APPROVED: Aprobadas, se incluyen en planilla
   - REJECTED: Rechazadas, NO se incluyen en planilla

3. VALIDACIONES:
   - Trigger trg_overtime_rejection_reason: Obliga a dar razón si se rechaza
   - Trigger trg_overtime_status_auto: Auto-detecta si hay horas extras al insertar

4. PERMISOS REQUERIDOS:
   - Crear permiso "aprobar_horas_extras" en tabla permisos
   - Asignar a roles: Admin, RRHH, Supervisores
   - Validar en controllers antes de permitir aprobar/rechazar

5. VISTAS ÚTILES:
   - v_overtime_pending_approval: Para supervisores (qué aprobar)
   - v_overtime_approval_stats: Para reportes (quién aprobó qué)

6. INTEGRACIÓN CON PLANILLAS:
   - Modificar PlanillaConceptCalculator para verificar overtime_status
   - Funciones HORAS_EXTRAS_25() y HORAS_EXTRAS_50() deben filtrar por APPROVED
   - Query: WHERE overtime_status = 'APPROVED'

7. CASOS ESPECIALES:
   - Horas extras pre-existentes: Auto-aprobadas durante migración
   - Empleados exentos de aprobación: Configurar en tabla employees (futuro)
   - Aprobación automática: Posible agregar regla de negocio (ej: <1h auto-aprueba)

8. SIGUIENTE PASO:
   - Crear OvertimeApprovalController.php
   - Crear vista attendance/overtime_approvals.php
   - Modificar AttendanceCalculator.php para marcar PENDING
   - Modificar queries de planillas para filtrar APPROVED only
*/
