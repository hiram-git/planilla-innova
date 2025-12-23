-- Agregar acreedor a prestamos
ALTER TABLE loans
    ADD COLUMN creditor_id INT NULL AFTER employee_id;

ALTER TABLE loans
    ADD CONSTRAINT fk_loans_creditor
        FOREIGN KEY (creditor_id) REFERENCES creditors(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE;

CREATE INDEX idx_loans_creditor_id ON loans (creditor_id);
