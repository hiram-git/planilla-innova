-- ============================================
-- MIGRACIÓN: Agregar campo clave_seguro_social
-- Fecha: 2025-01-13
-- Descripción: Agregar campo para la clave única del empleado en el seguro social
-- ============================================

-- Agregar columna clave_seguro_social a la tabla employees
ALTER TABLE employees 
ADD COLUMN clave_seguro_social VARCHAR(20) NULL 
AFTER document_id;

-- Verificar que la columna se haya agregado correctamente
-- DESCRIBE employees;

-- Comentario sobre el campo
ALTER TABLE employees 
MODIFY COLUMN clave_seguro_social VARCHAR(20) NULL 
COMMENT 'Clave única del empleado en el sistema de seguro social';

-- ============================================
-- NOTAS:
-- - Campo opcional (NULL)
-- - Longitud VARCHAR(20) para formatos como "12-34-567890"
-- - Posicionado después del campo document_id para mantener lógica de documentos
-- - Se debe actualizar los formularios de creación, edición y vista de empleados
-- - Se debe agregar al array fillable del modelo Employee.php
-- ============================================