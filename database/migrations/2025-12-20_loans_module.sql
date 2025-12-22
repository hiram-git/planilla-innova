-- Tabla principal de préstamos
CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    loan_type VARCHAR(100) NOT NULL,
    frequency ENUM('semanal', 'quincenal', 'mensual') NOT NULL,
    allow_december TINYINT(1) NOT NULL DEFAULT 1,
    total_amount DECIMAL(12,2) NOT NULL,
    installments_count INT NOT NULL,
    start_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_loans_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Detalle de cuotas del préstamo
CREATE TABLE IF NOT EXISTS loan_installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    installment_number INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('generada','pagada','cancelada') NOT NULL DEFAULT 'generada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_installments_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    INDEX idx_loan_installment (loan_id, installment_number)
);
