-- ========================================================================
-- MIGRACIÓN: Sistema de Aprobación de Horas Extras (Opción B)
-- Fecha: 02 de Febrero, 2026
-- Versión: v3.6.0
-- Descripción: Workflow de aprobación multinivel para horas extras
-- Autor: Claude Code
-- ========================================================================

-- ========================================================================
-- 1. TABLA: overtime_approvals
-- Solicitudes de aprobación de horas extras por período
-- ========================================================================

CREATE TABLE IF NOT EXISTS overtime_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único de la solicitud',

    -- Empleado y período
    employee_id INT NOT NULL COMMENT 'ID del empleado (FK a employees)',
    period_start DATE NOT NULL COMMENT 'Fecha inicio del período',
    period_end DATE NOT NULL COMMENT 'Fecha fin del período',

    -- Métricas de horas extras
    total_overtime_25 DECIMAL(10,2) DEFAULT 0 COMMENT 'Total horas extras 25%',
    total_overtime_50 DECIMAL(10,2) DEFAULT 0 COMMENT 'Total horas extras 50%',
    total_hours DECIMAL(10,2) DEFAULT 0 COMMENT 'Total horas extras (25% + 50%)',

    -- Cálculos monetarios
    hourly_rate DECIMAL(10,2) DEFAULT 0 COMMENT 'Tarifa horaria del empleado',
    total_amount DECIMAL(10,2) DEFAULT 0 COMMENT 'Monto total calculado',

    -- Workflow de aprobación
    approval_level INT DEFAULT 1 COMMENT 'Nivel actual: 1=supervisor, 2=gerente',
    requires_two_levels TINYINT(1) DEFAULT 0 COMMENT 'Si requiere 2 niveles de aprobación',
    current_approver_id INT DEFAULT NULL COMMENT 'ID del aprobador actual',

    status ENUM('PENDIENTE', 'EN_REVISION', 'APROBADO', 'RECHAZADO', 'CANCELADO')
        DEFAULT 'PENDIENTE' COMMENT 'Estado de la solicitud',

    -- Aprobación Nivel 1 (Supervisor)
    approved_by_level_1 INT DEFAULT NULL COMMENT 'ID del supervisor que aprobó',
    approved_at_level_1 TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha/hora aprobación nivel 1',
    comments_level_1 TEXT DEFAULT NULL COMMENT 'Comentarios del supervisor',

    -- Aprobación Nivel 2 (Gerente) - Opcional
    approved_by_level_2 INT DEFAULT NULL COMMENT 'ID del gerente que aprobó',
    approved_at_level_2 TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha/hora aprobación nivel 2',
    comments_level_2 TEXT DEFAULT NULL COMMENT 'Comentarios del gerente',

    -- Rechazo
    rejected_by INT DEFAULT NULL COMMENT 'ID del usuario que rechazó',
    rejected_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha/hora de rechazo',
    rejection_reason TEXT DEFAULT NULL COMMENT 'Motivo del rechazo',
    rejection_level VARCHAR(20) DEFAULT NULL COMMENT 'Nivel donde se rechazó (NIVEL_1, NIVEL_2)',

    -- Metadata
    notes TEXT DEFAULT NULL COMMENT 'Notas adicionales',
    details JSON DEFAULT NULL COMMENT 'Detalles diarios en formato JSON',

    -- Auditoría
    created_by INT DEFAULT NULL COMMENT 'Usuario que creó (sistema = NULL)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    UNIQUE KEY idx_unique_period (employee_id, period_start, period_end),
    KEY idx_status (status),
    KEY idx_approver (current_approver_id),
    KEY idx_employee (employee_id),
    KEY idx_period (period_start, period_end),
    KEY idx_approval_level (approval_level),
    KEY idx_created_at (created_at),

    -- Foreign Keys
    CONSTRAINT fk_ota_employee FOREIGN KEY (employee_id)
        REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_ota_current_approver FOREIGN KEY (current_approver_id)
        REFERENCES admin(id) ON DELETE SET NULL,
    CONSTRAINT fk_ota_approver_l1 FOREIGN KEY (approved_by_level_1)
        REFERENCES admin(id) ON DELETE SET NULL,
    CONSTRAINT fk_ota_approver_l2 FOREIGN KEY (approved_by_level_2)
        REFERENCES admin(id) ON DELETE SET NULL,
    CONSTRAINT fk_ota_rejector FOREIGN KEY (rejected_by)
        REFERENCES admin(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Solicitudes de aprobación de horas extras por período';


-- ========================================================================
-- 2. TABLA: overtime_approval_history
-- Historial auditable de cambios en solicitudes
-- ========================================================================

CREATE TABLE IF NOT EXISTS overtime_approval_history (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único del registro de historial',

    -- Relación
    approval_id INT NOT NULL COMMENT 'ID de la solicitud (FK a overtime_approvals)',

    -- Acción realizada
    action ENUM(
        'CREADO',
        'APROBADO_L1',
        'APROBADO_L2',
        'APROBADO_FINAL',
        'RECHAZADO',
        'MODIFICADO',
        'CANCELADO',
        'REABIERTO'
    ) NOT NULL COMMENT 'Tipo de acción realizada',

    -- Usuario que realizó la acción
    performed_by INT NOT NULL COMMENT 'ID del usuario (FK a admin)',

    -- Cambios de estado
    previous_status VARCHAR(50) DEFAULT NULL COMMENT 'Estado anterior',
    new_status VARCHAR(50) DEFAULT NULL COMMENT 'Nuevo estado',

    -- Detalles
    comments TEXT DEFAULT NULL COMMENT 'Comentarios de la acción',
    changes_summary TEXT DEFAULT NULL COMMENT 'Resumen de cambios (si aplica)',

    -- Metadata técnica
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP del usuario',
    user_agent VARCHAR(255) DEFAULT NULL COMMENT 'User agent del navegador',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Índices
    KEY idx_approval (approval_id),
    KEY idx_action (action),
    KEY idx_performer (performed_by),
    KEY idx_created (created_at),

    -- Foreign Keys
    CONSTRAINT fk_ot_history_approval FOREIGN KEY (approval_id)
        REFERENCES overtime_approvals(id) ON DELETE CASCADE,
    CONSTRAINT fk_ot_history_user FOREIGN KEY (performed_by)
        REFERENCES admin(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial auditable de acciones en aprobaciones de horas extras';


-- ========================================================================
-- 3. ÍNDICE ADICIONAL: attendance_calculations
-- Para mejorar performance en queries de horas extras
-- ========================================================================

-- Verificar si el índice ya existe antes de crearlo
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'attendance_calculations'
    AND index_name = 'idx_calc_overtime_eligible'
);

SET @sql = IF(
    @index_exists = 0,
    'CREATE INDEX idx_calc_overtime_eligible ON attendance_calculations(employee_id, date, overtime_25_hours, overtime_50_hours)',
    'SELECT "Index idx_calc_overtime_eligible already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ========================================================================
-- 4. VISTA: v_overtime_approvals_with_employees
-- Vista consolidada con información de empleados
-- ========================================================================

CREATE OR REPLACE VIEW v_overtime_approvals_with_employees AS
SELECT
    oa.id,
    oa.employee_id,
    e.employee_id as employee_code,
    e.firstname,
    e.lastname,
    CONCAT(e.firstname, ' ', e.lastname) as employee_full_name,
    e.permite_horas_extras,
    oa.period_start,
    oa.period_end,
    DATEDIFF(oa.period_end, oa.period_start) + 1 as period_days,
    oa.total_overtime_25,
    oa.total_overtime_50,
    oa.total_hours,
    oa.hourly_rate,
    oa.total_amount,
    oa.approval_level,
    oa.requires_two_levels,
    oa.status,
    oa.current_approver_id,
    approver.firstname as approver_firstname,
    approver.lastname as approver_lastname,
    CONCAT(approver.firstname, ' ', approver.lastname) as approver_full_name,
    oa.approved_by_level_1,
    sup.firstname as supervisor_firstname,
    sup.lastname as supervisor_lastname,
    CONCAT(sup.firstname, ' ', sup.lastname) as supervisor_full_name,
    oa.approved_at_level_1,
    oa.comments_level_1,
    oa.approved_by_level_2,
    mgr.firstname as manager_firstname,
    mgr.lastname as manager_lastname,
    CONCAT(mgr.firstname, ' ', mgr.lastname) as manager_full_name,
    oa.approved_at_level_2,
    oa.comments_level_2,
    oa.rejected_by,
    rej.firstname as rejector_firstname,
    rej.lastname as rejector_lastname,
    CONCAT(rej.firstname, ' ', rej.lastname) as rejector_full_name,
    oa.rejected_at,
    oa.rejection_reason,
    oa.rejection_level,
    oa.notes,
    oa.created_at,
    oa.updated_at,
    -- Campos calculados
    CASE
        WHEN oa.status = 'PENDIENTE' THEN DATEDIFF(NOW(), oa.created_at)
        WHEN oa.status = 'EN_REVISION' THEN DATEDIFF(NOW(), oa.approved_at_level_1)
        ELSE NULL
    END as days_pending,
    CASE
        WHEN oa.status = 'APROBADO' AND oa.approved_at_level_1 IS NOT NULL THEN
            DATEDIFF(
                COALESCE(oa.approved_at_level_2, oa.approved_at_level_1),
                oa.created_at
            )
        ELSE NULL
    END as days_to_approve
FROM overtime_approvals oa
INNER JOIN employees e ON oa.employee_id = e.id
LEFT JOIN admin approver ON oa.current_approver_id = approver.id
LEFT JOIN admin sup ON oa.approved_by_level_1 = sup.id
LEFT JOIN admin mgr ON oa.approved_by_level_2 = mgr.id
LEFT JOIN admin rej ON oa.rejected_by = rej.id;


-- ========================================================================
-- 5. VISTA: v_overtime_approvals_pending_by_approver
-- Solicitudes pendientes agrupadas por aprobador
-- ========================================================================

CREATE OR REPLACE VIEW v_overtime_approvals_pending_by_approver AS
SELECT
    oa.current_approver_id,
    a.firstname as approver_firstname,
    a.lastname as approver_lastname,
    CONCAT(a.firstname, ' ', a.lastname) as approver_full_name,
    COUNT(*) as pending_count,
    SUM(oa.total_hours) as total_pending_hours,
    SUM(oa.total_amount) as total_pending_amount,
    MIN(oa.created_at) as oldest_request_date,
    MAX(oa.created_at) as newest_request_date,
    AVG(DATEDIFF(NOW(), oa.created_at)) as avg_days_pending
FROM overtime_approvals oa
INNER JOIN admin a ON oa.current_approver_id = a.id
WHERE oa.status IN ('PENDIENTE', 'EN_REVISION')
GROUP BY oa.current_approver_id, a.firstname, a.lastname;


-- ========================================================================
-- 6. VISTA: v_overtime_approvals_stats
-- Estadísticas generales de aprobaciones
-- ========================================================================

CREATE OR REPLACE VIEW v_overtime_approvals_stats AS
SELECT
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'PENDIENTE' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'EN_REVISION' THEN 1 ELSE 0 END) as in_review_count,
    SUM(CASE WHEN status = 'APROBADO' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status = 'RECHAZADO' THEN 1 ELSE 0 END) as rejected_count,
    SUM(CASE WHEN status = 'CANCELADO' THEN 1 ELSE 0 END) as cancelled_count,
    SUM(total_hours) as total_hours_requested,
    SUM(CASE WHEN status = 'APROBADO' THEN total_hours ELSE 0 END) as total_hours_approved,
    SUM(CASE WHEN status = 'RECHAZADO' THEN total_hours ELSE 0 END) as total_hours_rejected,
    SUM(total_amount) as total_amount_requested,
    SUM(CASE WHEN status = 'APROBADO' THEN total_amount ELSE 0 END) as total_amount_approved,
    SUM(CASE WHEN status = 'RECHAZADO' THEN total_amount ELSE 0 END) as total_amount_rejected,
    AVG(CASE
        WHEN status = 'APROBADO' AND approved_at_level_1 IS NOT NULL
        THEN DATEDIFF(COALESCE(approved_at_level_2, approved_at_level_1), created_at)
        ELSE NULL
    END) as avg_approval_days,
    COUNT(DISTINCT employee_id) as total_employees_with_requests,
    COUNT(DISTINCT current_approver_id) as total_approvers
FROM overtime_approvals;


-- ========================================================================
-- 7. DATOS INICIALES (Opcional - Para Testing)
-- Descomentar solo para ambiente de desarrollo
-- ========================================================================

/*
-- Ejemplo de solicitud de prueba (usar IDs reales de tu BD)
INSERT INTO overtime_approvals (
    employee_id, period_start, period_end,
    total_overtime_25, total_overtime_50, total_hours,
    hourly_rate, total_amount,
    current_approver_id, status,
    notes, created_by
) VALUES (
    1,  -- ID empleado (ajustar según tu BD)
    '2026-02-01',
    '2026-02-15',
    3.5,
    2.0,
    5.5,
    10.50,
    75.25,
    1,  -- ID admin aprobador (ajustar según tu BD)
    'PENDIENTE',
    'Solicitud generada automáticamente por el sistema',
    NULL  -- Sistema
);

-- Registrar acción en historial
INSERT INTO overtime_approval_history (
    approval_id, action, performed_by,
    previous_status, new_status,
    comments
) VALUES (
    LAST_INSERT_ID(),
    'CREADO',
    1,  -- ID admin (ajustar según tu BD)
    NULL,
    'PENDIENTE',
    'Solicitud creada automáticamente desde attendance_calculations'
);
*/


-- ========================================================================
-- 8. VERIFICACIÓN DE LA MIGRACIÓN
-- ========================================================================

-- Verificar tablas creadas
SELECT 'overtime_approvals' as tabla, COUNT(*) as registros FROM overtime_approvals
UNION ALL
SELECT 'overtime_approval_history' as tabla, COUNT(*) as registros FROM overtime_approval_history;

-- Verificar vistas creadas
SELECT TABLE_NAME as vista_creada
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'v_overtime%'
ORDER BY TABLE_NAME;

-- Verificar índices en overtime_approvals
SELECT DISTINCT INDEX_NAME as indice
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'overtime_approvals'
ORDER BY INDEX_NAME;

-- Verificar foreign keys
SELECT
    CONSTRAINT_NAME as foreign_key,
    TABLE_NAME as tabla,
    REFERENCED_TABLE_NAME as tabla_referenciada
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('overtime_approvals', 'overtime_approval_history')
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;


-- ========================================================================
-- FIN DE LA MIGRACIÓN
-- ========================================================================

-- Mensaje de éxito
SELECT '✅ MIGRACIÓN COMPLETADA EXITOSAMENTE' as mensaje,
       'Sistema de Aprobación de Horas Extras v3.6.0' as sistema,
       NOW() as fecha_ejecucion;
