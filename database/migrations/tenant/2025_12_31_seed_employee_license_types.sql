-- ============================================================================
-- Migration: Seed missing license types and subtypes for employee files
-- Date: 2025-12-31
-- Description: Ensures license-related types/subtypes exist for existing tenants
-- Type: TENANT (apply to all tenant databases)
-- ============================================================================

-- 1. Ensure license types exist (idempotent)
INSERT INTO employee_file_types (name, status)
VALUES
    ('Licencias con Sueldo', 1),
    ('Licencias sin Sueldo', 1),
    ('Licencias Especiales', 1)
ON DUPLICATE KEY UPDATE
    status = VALUES(status);

-- 2. Ensure subtypes for Licencias con Sueldo
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias con Sueldo'), 'Representación de la Institución, Estado o País', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias con Sueldo'), 'Estudios', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias con Sueldo'), 'Representación de la asociación de servidor', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias con Sueldo'), 'Capacitación', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias con Sueldo'), 'RAZONES EXTRAORDINARIAS', 1)
ON DUPLICATE KEY UPDATE
    status = VALUES(status);

-- 3. Ensure subtypes for Licencias sin Sueldo
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias sin Sueldo'), 'Asumir cargo de elección popular', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias sin Sueldo'), 'Asuntos Personales', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias sin Sueldo'), 'Asumir cargo de libre nobramiento y remoción', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias sin Sueldo'), 'Estudiar', 1)
ON DUPLICATE KEY UPDATE
    status = VALUES(status);

-- 4. Ensure subtypes for Licencias Especiales
INSERT INTO employee_file_subtypes (type_id, name, status)
VALUES
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias Especiales'), 'Enfermedad Profesional', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias Especiales'), 'Enfermedad/Incapacidad superior quince días', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias Especiales'), 'Riesgos Profesionales', 1),
    ((SELECT id FROM employee_file_types WHERE name = 'Licencias Especiales'), 'Gravidez', 1)
ON DUPLICATE KEY UPDATE
    status = VALUES(status);
