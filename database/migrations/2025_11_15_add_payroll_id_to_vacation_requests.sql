-- Migración: Agregar campo payroll_id a vacation_requests
-- Fecha: 15 de Noviembre, 2025
-- Propósito: Rastrear si ya se generó una planilla para una solicitud de vacaciones

-- Agregar campo payroll_id
ALTER TABLE vacation_requests
ADD COLUMN payroll_id INT NULL AFTER compensation_amount,
ADD INDEX idx_payroll_id (payroll_id),
ADD CONSTRAINT fk_vacation_requests_payroll
    FOREIGN KEY (payroll_id)
    REFERENCES planilla_cabecera(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- Comentarios de columna
ALTER TABLE vacation_requests
MODIFY COLUMN payroll_id INT NULL COMMENT 'ID de la planilla generada para esta solicitud';
