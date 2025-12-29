-- ============================================================================
-- Migration: Employee Files (Records + Attachments)
-- Date: 2025-12-30
-- Description: Creates employee_files and employee_file_attachments tables
-- Type: TENANT (apply to all tenant databases)
-- ============================================================================

CREATE TABLE IF NOT EXISTS employee_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    type_id INT NOT NULL,
    subtype_id INT NOT NULL,
    document_date DATE NOT NULL,
    document_number VARCHAR(120) NULL,
    observations TEXT NULL,
    extra_fields JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_files_employee (employee_id),
    INDEX idx_employee_files_type (type_id),
    INDEX idx_employee_files_subtype (subtype_id),
    INDEX idx_employee_files_document_date (document_date),
    CONSTRAINT fk_employee_files_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_files_type
        FOREIGN KEY (type_id) REFERENCES employee_file_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_employee_files_subtype
        FOREIGN KEY (subtype_id) REFERENCES employee_file_subtypes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Employee file records';

CREATE TABLE IF NOT EXISTS employee_file_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_file_id INT NOT NULL,
    label VARCHAR(120) NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_file_attachments_file (employee_file_id),
    CONSTRAINT fk_employee_file_attachments_file
        FOREIGN KEY (employee_file_id) REFERENCES employee_files(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Employee file attachments';
