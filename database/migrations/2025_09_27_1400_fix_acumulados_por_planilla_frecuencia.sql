-- =====================================================
-- MIGRACIÓN: Fix tabla acumulados_por_planilla frecuencia
-- FECHA: 2025-09-27 14:00
-- VERSIÓN: 3.3.9
-- DESCRIPCIÓN: Cambiar campo 'frecuencia' ENUM por 'frecuencia_id' INT
--              para resolver error con planillas XIII Mes
-- =====================================================

-- PASO 1: Agregar nueva columna frecuencia_id
ALTER TABLE acumulados_por_planilla
ADD COLUMN frecuencia_id INT(11) AFTER ano;

-- PASO 2: Crear índice para la nueva columna
ALTER TABLE acumulados_por_planilla
ADD INDEX idx_frecuencia_id (frecuencia_id);

-- PASO 3: Migrar datos existentes (mapear ENUM a IDs)
UPDATE acumulados_por_planilla
SET frecuencia_id = CASE
    WHEN frecuencia = 'QUINCENAL' THEN 1
    WHEN frecuencia = 'MENSUAL' THEN 2
    WHEN frecuencia = 'ANUAL' THEN 6
    WHEN frecuencia = 'ESPECIAL' THEN 7
    ELSE NULL
END;

-- PASO 4: Verificar que no hay datos NULL (todos los registros deben tener frecuencia_id)
-- Esta consulta debe retornar 0
-- NOTA: SELECT comentado para evitar error PDO "pending result sets"
-- SELECT COUNT(*) as registros_sin_frecuencia_id
-- FROM acumulados_por_planilla
-- WHERE frecuencia_id IS NULL;

-- PASO 5: Hacer la nueva columna NOT NULL después de migrar datos
ALTER TABLE acumulados_por_planilla
MODIFY COLUMN frecuencia_id INT(11) NOT NULL;

-- PASO 6: Eliminar la columna antigua frecuencia ENUM
ALTER TABLE acumulados_por_planilla
DROP COLUMN frecuencia;

-- PASO 7: Agregar foreign key constraint
ALTER TABLE acumulados_por_planilla
ADD CONSTRAINT fk_acumulados_planilla_frecuencia
FOREIGN KEY (frecuencia_id) REFERENCES frecuencias(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- PASO 8: Verificación final - mostrar estructura nueva
DESCRIBE acumulados_por_planilla;

-- PASO 9: Verificar que los datos se migraron correctamente
SELECT
    frecuencia_id,
    f.codigo as frecuencia_codigo,
    f.descripcion as frecuencia_descripcion,
    COUNT(*) as total_registros
FROM acumulados_por_planilla app
LEFT JOIN frecuencias f ON app.frecuencia_id = f.id
GROUP BY frecuencia_id, f.codigo, f.descripcion
ORDER BY frecuencia_id;