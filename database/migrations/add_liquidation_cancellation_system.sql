-- =====================================================
-- MIGRACIÓN: Sistema de Cancelación de Liquidaciones
-- =====================================================
-- Descripción: Implementa soft delete con estado de reversa
-- Autor: Claude Code Assistant
-- Fecha: 2025-09-23
-- Versión: 3.3.1

-- Usar la base de datos correcta

-- 1. Agregar estado CANCELADA al ENUM de status
ALTER TABLE employee_terminations
MODIFY COLUMN status ENUM('PENDIENTE','CALCULADA','PROCESADA','PAGADA','CANCELADA')
DEFAULT 'PENDIENTE'
COMMENT 'Estado de la liquidación: PENDIENTE->CALCULADA->PROCESADA->PAGADA, o CANCELADA en cualquier momento';

-- 2. Agregar campos de auditoría para cancelación
ALTER TABLE employee_terminations
ADD COLUMN cancelled_at TIMESTAMP NULL COMMENT 'Fecha y hora de cancelación',
ADD COLUMN cancelled_by INT(11) NULL COMMENT 'ID del usuario que canceló',
ADD COLUMN cancel_reason TEXT NULL COMMENT 'Motivo detallado de la cancelación',
ADD COLUMN previous_status ENUM('PENDIENTE','CALCULADA','PROCESADA','PAGADA') NULL COMMENT 'Estado anterior antes de cancelar';

-- 3. Crear foreign key para cancelled_by
ALTER TABLE employee_terminations
ADD CONSTRAINT fk_termination_cancelled_by
FOREIGN KEY (cancelled_by) REFERENCES admin(id)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- 4. Crear índice para consultas de liquidaciones canceladas
CREATE INDEX idx_terminations_cancelled ON employee_terminations(status, cancelled_at);
CREATE INDEX idx_terminations_cancelled_by ON employee_terminations(cancelled_by, cancelled_at);

-- 5. Agregar nuevos tipos de acción al historial si no existen
INSERT IGNORE INTO liquidation_history (termination_id, action, description, user_id, ip_address, created_at)
VALUES (0, 'CANCELACION', 'Registro de ejemplo para tipo de acción CANCELACION', 1, '127.0.0.1', NOW());

-- Limpiar el registro de ejemplo
DELETE FROM liquidation_history WHERE termination_id = 0;

-- 6. Actualizar comentarios de tabla para documentación
ALTER TABLE employee_terminations
COMMENT = 'Gestión de terminaciones y liquidaciones con sistema de cancelación y auditoría completa';

-- =====================================================
-- VERIFICACIONES POST-MIGRACIÓN
-- =====================================================

-- Verificar estructura de la tabla
DESCRIBE employee_terminations;

-- Verificar que los nuevos campos están presentes
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'planilla_innova'
    AND TABLE_NAME = 'employee_terminations'
    AND COLUMN_NAME IN ('cancelled_at', 'cancelled_by', 'cancel_reason', 'previous_status')
ORDER BY ORDINAL_POSITION;

-- Verificar constraint de foreign key
SELECT
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'planilla_innova'
    AND TABLE_NAME = 'employee_terminations'
    AND CONSTRAINT_NAME = 'fk_termination_cancelled_by';

COMMIT;

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================
-- 1. La cancelación preserva todos los datos originales
-- 2. previous_status permite auditoría del estado anterior
-- 3. Los empleados pueden ser revertidos a ACTIVO si la liquidación
--    estaba en estado PENDIENTE o CALCULADA
-- 4. Las liquidaciones PROCESADAS o PAGADAS requieren proceso especial
-- 5. Todos los cambios quedan registrados en liquidation_history