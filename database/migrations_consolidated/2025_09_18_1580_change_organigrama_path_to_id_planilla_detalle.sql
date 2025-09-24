-- Migración: Cambiar organigrama_path por organigrama_id en planilla_detalle
-- Fecha: 2025-09-18
-- Propósito: Guardar ID del organigrama en lugar del path para mantener integridad referencial

-- Agregar nueva columna organigrama_id
ALTER TABLE planilla_detalle
ADD COLUMN organigrama_id INT(11) NULL
AFTER tipo;

-- Agregar foreign key constraint
ALTER TABLE planilla_detalle
ADD CONSTRAINT fk_planilla_detalle_organigrama
FOREIGN KEY (organigrama_id) REFERENCES organigrama(id)
ON DELETE SET NULL ON UPDATE CASCADE;

-- Migrar datos existentes si es necesario
-- UPDATE planilla_detalle pd
-- JOIN organigrama o ON pd.organigrama_path = o.path
-- SET pd.organigrama_id = o.id
-- WHERE pd.organigrama_path IS NOT NULL;

-- Eliminar columna antigua después de verificar que todo funciona
-- ALTER TABLE planilla_detalle DROP COLUMN organigrama_path;

-- Crear índice para mejor performance
CREATE INDEX idx_planilla_detalle_organigrama ON planilla_detalle(organigrama_id);