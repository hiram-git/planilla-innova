-- ============================================================================
-- Migration: Agregar cargo_id a tabla funciones
-- Fecha: 2025-12-17
-- Descripción: Establece relación OPCIONAL entre funciones y cargos
-- Autor: Sistema de Planillas v3.5.14
-- ============================================================================

-- PASO 1: Verificar estructura actual de la tabla funciones
SELECT 'Estado actual de tabla funciones:' as info;
DESCRIBE funciones;

-- PASO 2: Verificar datos actuales (antes de la migración)
SELECT 'Datos actuales de funciones (5 registros):' as info;
SELECT id, codigo, nombre FROM funciones ORDER BY id;

-- PASO 3: Agregar columna cargo_id (NULL permitido - funciones opcionales)
SELECT 'PASO 3: Agregando columna cargo_id...' as info;

ALTER TABLE funciones
ADD COLUMN cargo_id INT NULL
COMMENT 'FK opcional al cargo. NULL = función genérica aplicable a cualquier cargo'
AFTER descripcion;

-- PASO 4: Crear índice para mejor performance
SELECT 'PASO 4: Creando índice en cargo_id...' as info;

ALTER TABLE funciones
ADD INDEX idx_cargo (cargo_id);

-- PASO 5: Agregar Foreign Key constraint
SELECT 'PASO 5: Agregando Foreign Key constraint...' as info;

ALTER TABLE funciones
ADD CONSTRAINT fk_funciones_cargo
    FOREIGN KEY (cargo_id)
    REFERENCES cargos(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- PASO 6: Actualizar comentario de tabla
SELECT 'PASO 6: Actualizando comentario de tabla...' as info;

ALTER TABLE funciones
COMMENT = 'Funciones opcionales asociadas a cargos. cargo_id NULL = función genérica';

-- PASO 7: Verificar estructura final
SELECT 'PASO 7: Verificando estructura final...' as info;
DESCRIBE funciones;

-- PASO 8: Verificar Foreign Keys creadas
SELECT 'PASO 8: Verificando Foreign Keys...' as info;
SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'funciones'
  AND CONSTRAINT_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- ============================================================================
-- NOTAS IMPORTANTES:
-- ============================================================================
-- 1. cargo_id es NULL por defecto (funciones genéricas)
-- 2. Funciones con cargo_id NULL están disponibles para TODOS los cargos
-- 3. Funciones con cargo_id específico solo se muestran para ese cargo
-- 4. ON DELETE SET NULL: Si se elimina un cargo, la función queda genérica
-- 5. ON UPDATE CASCADE: Si cambia el ID del cargo, se actualiza automáticamente
-- ============================================================================

-- DATOS DE MAPEO SUGERIDOS (ejecutar después de esta migración):
-- ============================================================================
-- OPCIÓN A: Dejar todas como genéricas (NULL) - más flexible
-- -- No hacer nada, todas las funciones quedan disponibles para cualquier cargo
--
-- OPCIÓN B: Asignar funciones específicas a cargos
-- UPDATE funciones SET cargo_id = 1 WHERE id = 1;  -- GERENTE DE VENTAS → SALES MANAGER
-- UPDATE funciones SET cargo_id = 2 WHERE id = 2;  -- SOPORTE TECNICO → HELP DESK SUPPORT
-- UPDATE funciones SET cargo_id = 5 WHERE id = 3;  -- ASISTENTE ADM → ADMINISTRATIVE ASSISTANT
-- UPDATE funciones SET cargo_id = 6 WHERE id = 4;  -- CEO → CEO
-- UPDATE funciones SET cargo_id = 7 WHERE id = 5;  -- PROGRAMADOR → PROGRAMADOR SENIOR
-- ============================================================================

SELECT '✅ Migración completada exitosamente!' as resultado;
SELECT 'ℹ️  Todas las funciones quedan como genéricas (cargo_id = NULL) por defecto' as nota;
