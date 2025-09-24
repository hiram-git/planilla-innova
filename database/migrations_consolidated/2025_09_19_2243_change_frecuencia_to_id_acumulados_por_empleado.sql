-- Migración: Cambiar campo frecuencia de ENUM a INT en tabla acumulados_por_empleado
-- Fecha: 2025-09-19
-- Propósito: Almacenar el ID de frecuencia en lugar del valor ENUM para mejor integridad referencial

-- Paso 1: Agregar nueva columna frecuencia_id
ALTER TABLE acumulados_por_empleado
ADD COLUMN frecuencia_id INT NULL
COMMENT 'ID de la frecuencia desde tabla frecuencias'
AFTER frecuencia;

-- Paso 2: Migrar datos existentes del ENUM al ID
-- Mapear valores ENUM a IDs de frecuencias
UPDATE acumulados_por_empleado ape
INNER JOIN frecuencias f ON (
    (ape.frecuencia = 'QUINCENAL' AND f.nombre = 'QUINCENAL') OR
    (ape.frecuencia = 'MENSUAL' AND f.nombre = 'MENSUAL') OR
    (ape.frecuencia = 'ANUAL' AND f.nombre = 'ANUAL') OR
    (ape.frecuencia = 'ESPECIAL' AND f.nombre = 'ESPECIAL')
)
SET ape.frecuencia_id = f.id
WHERE ape.frecuencia_id IS NULL;

-- Paso 3: Hacer la nueva columna NOT NULL después de migrar datos
ALTER TABLE acumulados_por_empleado
MODIFY COLUMN frecuencia_id INT NOT NULL;

-- Paso 4: Agregar foreign key constraint
ALTER TABLE acumulados_por_empleado
ADD CONSTRAINT fk_acumulados_frecuencia
FOREIGN KEY (frecuencia_id) REFERENCES frecuencias(id)
ON DELETE RESTRICT
ON UPDATE CASCADE;

-- Paso 5: Crear índice para optimizar consultas
CREATE INDEX idx_frecuencia_id ON acumulados_por_empleado(frecuencia_id);

-- Paso 6: Eliminar la columna ENUM antigua
ALTER TABLE acumulados_por_empleado
DROP COLUMN frecuencia;

-- Paso 7: Renombrar la nueva columna para mantener el nombre original
ALTER TABLE acumulados_por_empleado
CHANGE COLUMN frecuencia_id frecuencia INT NOT NULL
COMMENT 'ID de la frecuencia desde tabla frecuencias';