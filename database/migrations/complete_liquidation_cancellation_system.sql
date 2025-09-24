-- =====================================================
-- COMPLETAR: Sistema de Cancelación de Liquidaciones
-- =====================================================
-- Solo agregar foreign key e índices faltantes
-- Fecha: 2025-09-23

USE planilla_innova;

-- 1. Crear foreign key para cancelled_by (si no existe)
ALTER TABLE employee_terminations
ADD CONSTRAINT fk_termination_cancelled_by
FOREIGN KEY (cancelled_by) REFERENCES admin(id)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- 2. Crear índices para optimizar consultas
CREATE INDEX idx_terminations_cancelled ON employee_terminations(status, cancelled_at);
CREATE INDEX idx_terminations_cancelled_by ON employee_terminations(cancelled_by, cancelled_at);

-- 3. Verificar estructura final
DESCRIBE employee_terminations;

COMMIT;