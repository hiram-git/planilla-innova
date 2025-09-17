-- Migración: Agregar campo organigrama_id a employees
-- Fecha: 2025-09-17
-- Descripción: Campo para relacionar empleado con elemento del organigrama

-- Agregar campo organigrama_id a employees
ALTER TABLE employees
ADD COLUMN organigrama_id INT(11) NULL
COMMENT 'ID del elemento organizacional al que pertenece el empleado';

-- Crear foreign key con organigrama
ALTER TABLE employees
ADD CONSTRAINT fk_employees_organigrama
FOREIGN KEY (organigrama_id) REFERENCES organigrama(id)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Crear índice para optimizar consultas
CREATE INDEX idx_employees_organigrama ON employees(organigrama_id);

-- Comentario actualizado en la tabla
ALTER TABLE employees
COMMENT = 'Tabla de empleados con relación al organigrama empresarial';