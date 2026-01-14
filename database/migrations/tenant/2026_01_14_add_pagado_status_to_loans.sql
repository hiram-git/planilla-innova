-- Agregar estado 'pagado' a la tabla loans para marcar préstamos completamente pagados
-- Fecha: 2026-01-14
-- Descripción: Permite actualizar automáticamente el status del préstamo cuando se pagan todas las cuotas

ALTER TABLE loans
    MODIFY COLUMN status ENUM('activo', 'pagado', 'anulado') NOT NULL DEFAULT 'activo';
