-- ============================================================================
-- Migration: Employee Files Catalogs (Types + Subtypes) + Permission Module
-- Date: 2025-12-29
-- Description: Creates employee file type/subtype catalogs and adds menu module
-- Type: TENANT (apply to all tenant databases)
-- ============================================================================

-- 1. Create employee file types table
CREATE TABLE IF NOT EXISTS employee_file_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_employee_file_types_name (name),
    INDEX idx_employee_file_types_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catalog of employee file types';

-- 2. Create employee file subtypes table
CREATE TABLE IF NOT EXISTS employee_file_subtypes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_employee_file_subtypes (type_id, name),
    INDEX idx_employee_file_subtypes_status (status),
    INDEX idx_employee_file_subtypes_type (type_id),
    CONSTRAINT fk_employee_file_subtypes_type
        FOREIGN KEY (type_id) REFERENCES employee_file_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catalog of employee file subtypes';

-- 3. Insert employee file types (idempotent)
INSERT INTO employee_file_types (name, status)
VALUES
    ('Estudios Academicos', 1),
    ('Capacitación', 1),
    ('Permisos', 1),
    ('Amonestaciones', 1),
    ('Movimiento de Personal', 1),
    ('Evaluación de Desempeño', 1),
    ('Vacaciones', 1),
    ('Tiempo Compensatorio', 1),
    ('Documento', 1),
    ('Experiencia', 1),
    ('Licencias con Sueldo', 1),
    ('Licencias sin Sueldo', 1),
    ('Licencias Especiales', 1)
ON DUPLICATE KEY UPDATE
    status = VALUES(status);

-- 4. Insert employee file subtypes (idempotent)
-- Type: Estudios Academicos
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'Diplomado', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'Técnico', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'Maestría', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'Bachiller', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'Ingeniería', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'En Derecho Administrativo', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'Primaria', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'Licenciatura', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'Doctorado', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'Profesorado', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'Post Grado', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'No Especificado', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Estudios Academicos'), 'Primer Ciclo', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Capacitación
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Capacitación'), 'Curso', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Capacitación'), 'Charla', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Capacitación'), 'Taller', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Capacitación'), 'Jornada', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Capacitación'), 'Seminario', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Permisos
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Permisos'), 'Otros', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Permisos'), 'Enfermedad', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Permisos'), 'Eventos Academicos', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Permisos'), 'Misión Oficial', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Permisos'), 'Nacimiento', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Permisos'), 'Duelo', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Permisos'), 'Otros asuntos personales', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Permisos'), 'Representación Gremial', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Permisos'), 'Cita Médica', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Permisos'), 'Matrimonio', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Amonestaciones
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Amonestaciones'), 'Escrita', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Amonestaciones'), 'Verbal', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Movimiento de Personal
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Movimiento de Personal'), 'Asignación', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Movimiento de Personal'), 'Designación', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Movimiento de Personal'), 'Traslado', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Movimiento de Personal'), 'Prestamo Interinstitucional', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Evaluación de Desempeño
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Evaluación de Desempeño'), 'Bueno', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Evaluación de Desempeño'), 'No Satisfactorio', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Evaluación de Desempeño'), 'Excelente', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Evaluación de Desempeño'), 'Regular', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Vacaciones
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Vacaciones'), 'Accion de Personal (AUMENTA)', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Vacaciones'), 'Inicializacion Período', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Vacaciones'), 'Resuelto Normal', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Vacaciones'), 'Accion de Personal (DISMINUYE)', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Vacaciones'), 'Migración Período', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Vacaciones'), 'Resuelto Especial', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Tiempo Compensatorio
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Tiempo Compensatorio'), 'Aumenta', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Tiempo Compensatorio'), 'Disminuye', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Documento
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Documento'), 'Licencia', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Documento'), 'Cedula/RUC', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Experiencia
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Experiencia'), 'Trabajo Realizado', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Experiencia'), 'Labor Realizada', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Licencias con Sueldo
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias con Sueldo'), 'Representación de la Institución, Estado o Pa', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias con Sueldo'), 'Estudios', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias con Sueldo'), 'Representación de la asociación de servidor', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias con Sueldo'), 'Capacitación', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias con Sueldo'), 'RAZONES EXTRAORDINARIAS', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Licencias sin Sueldo
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias sin Sueldo'), 'Asumir cargo de elección popular', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias sin Sueldo'), 'Asuntos Personales', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias sin Sueldo'), 'Asumir cargo de libre nobramiento y remoción', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias sin Sueldo'), 'Estudiar', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Type: Licencias Especiales
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias Especiales'), 'Enfermedad Profesional', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias Especiales'), 'Enfermedad/Incapacidad superior quince días', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias Especiales'), 'Riesgos Profesionales', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias Especiales'), 'Gravidez', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- 5. Add menu item for Employee Files module
INSERT INTO menu_items (id, name, url)
VALUES (26, 'Employee Files', 'employee-files')
ON DUPLICATE KEY UPDATE
    name = 'Employee Files',
    url = 'employee-files';
