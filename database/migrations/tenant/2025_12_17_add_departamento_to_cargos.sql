-- ============================================================================
-- Migration: Agregar departamento_id a tabla cargos
-- Fecha: 2025-12-17
-- Descripción: Establece relación entre cargos y departamentos (organigrama)
-- Autor: Sistema de Planillas v3.5.14
-- ============================================================================

-- PASO 1: Verificar estructura actual de la tabla cargos
-- NOTA: SELECT comentado para evitar error PDO "pending result sets"
-- SELECT 'Estado actual de tabla cargos:' as info;
-- DESCRIBE cargos;

-- PASO 2: Verificar datos actuales (antes de la migración)
-- SELECT 'Datos actuales de cargos (6 registros):' as info;
-- SELECT id, codigo, nombre FROM cargos ORDER BY id;
-- 
-- PASO 3: Agregar columna departamento_id
-- SELECT 'PASO 3: Agregando columna departamento_id...' as info;
-- 
-- ALTER TABLE cargos
-- ADD COLUMN departamento_id INT NULL
-- COMMENT 'FK al departamento (organigrama) al que pertenece el cargo'
-- AFTER descripcion;

-- PASO 4: Crear índice para mejor performance
-- SELECT 'PASO 4: Creando índice en departamento_id...' as info;
-- 
-- ALTER TABLE cargos
-- ADD INDEX idx_departamento (departamento_id);

-- PASO 5: Agregar Foreign Key constraint
-- SELECT 'PASO 5: Agregando Foreign Key constraint...' as info;
-- 
-- ALTER TABLE cargos
-- ADD CONSTRAINT fk_cargos_departamento
    -- FOREIGN KEY (departamento_id)
    -- REFERENCES organigrama(id)
    -- ON DELETE SET NULL
    -- ON UPDATE CASCADE;

-- PASO 6: Actualizar comentario de tabla
-- SELECT 'PASO 6: Actualizando comentario de tabla...' as info;
-- 
-- ALTER TABLE cargos
-- COMMENT = 'Catálogo de cargos asociados a departamentos de la estructura organizacional';

-- PASO 7: Verificar estructura final
-- SELECT 'PASO 7: Verificando estructura final...' as info;
-- DESCRIBE cargos;

-- PASO 8: Verificar Foreign Keys creadas
-- SELECT 'PASO 8: Verificando Foreign Keys...' as info;
-- SELECT
    -- CONSTRAINT_NAME,
    -- TABLE_NAME,
    -- COLUMN_NAME,
    -- REFERENCED_TABLE_NAME,
    -- REFERENCED_COLUMN_NAME
-- FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
-- WHERE TABLE_NAME = 'cargos'
  -- AND CONSTRAINT_SCHEMA = DATABASE()
  -- AND REFERENCED_TABLE_NAME IS NOT NULL;

-- ============================================================================
-- NOTAS IMPORTANTES:
-- ============================================================================
-- 1. departamento_id es NULL por defecto (permite migración gradual)
-- 2. ON DELETE SET NULL: Si se elimina un departamento, el cargo queda sin departamento
-- 3. ON UPDATE CASCADE: Si cambia el ID del departamento, se actualiza automáticamente
-- 4. Se requiere asignar manualmente los departamentos a cada cargo existente
-- ============================================================================

-- DATOS DE MAPEO SUGERIDOS (ejecutar después de esta migración):
-- ============================================================================
-- UPDATE cargos SET departamento_id = 1 WHERE id = 6;  -- CEO → DIRECCIÓN GENERAL
-- UPDATE cargos SET departamento_id = 6 WHERE id = 1;  -- SALES MANAGER → VENTAS
-- UPDATE cargos SET departamento_id = 2 WHERE id IN (2,3);  -- HELP DESK → SOPORTE TÉCNICO
-- UPDATE cargos SET departamento_id = 8 WHERE id = 5;  -- ADMINISTRATIVE ASSISTANT → ADMINISTRACIÓN
-- UPDATE cargos SET departamento_id = 7 WHERE id = 7;  -- PROGRAMADOR SENIOR → DESARROLLO
-- ============================================================================

-- SELECT '✅ Migración completada exitosamente!' as resultado;
-- 