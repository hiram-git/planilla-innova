-- Agregar estado a prestamos y permitir anulado en cuotas
ALTER TABLE loans
    ADD COLUMN status ENUM('activo','anulado') NOT NULL DEFAULT 'activo' AFTER start_date;

ALTER TABLE loan_installments
    MODIFY COLUMN status ENUM('generada','pagada','cancelada','anulado') NOT NULL DEFAULT 'generada';
