-- ==================================================
-- ACTUALIZACIÓN TABLA SCHEDULES
-- Agregar campos requeridos por ReferenceModel
-- ==================================================

USE planilla_simple;

-- Verificar estructura actual
DESCRIBE schedules;

-- Agregar campos faltantes si no existen
ALTER TABLE schedules 
ADD COLUMN IF NOT EXISTS codigo VARCHAR(20) NOT NULL UNIQUE AFTER id,
ADD COLUMN IF NOT EXISTS nombre VARCHAR(100) NOT NULL AFTER codigo,
ADD COLUMN IF NOT EXISTS descripcion TEXT AFTER nombre,
ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1 AFTER descripcion;

-- Crear índice en código para optimizar búsquedas
CREATE INDEX IF NOT EXISTS idx_schedules_codigo ON schedules(codigo);
CREATE INDEX IF NOT EXISTS idx_schedules_activo ON schedules(activo);

-- Actualizar registros existentes si los hay (asignar códigos temporales)
UPDATE schedules 
SET 
    codigo = CONCAT('H', LPAD(id, 3, '0')),
    nombre = CONCAT('Horario ', id),
    descripcion = CONCAT('Horario de ', TIME_FORMAT(time_in, '%H:%i'), ' a ', TIME_FORMAT(time_out, '%H:%i')),
    activo = 1
WHERE codigo IS NULL OR codigo = '';

-- Verificar estructura final
DESCRIBE schedules;

-- Mostrar datos actualizados
SELECT * FROM schedules;