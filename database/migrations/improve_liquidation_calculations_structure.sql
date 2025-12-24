-- =====================================================
-- MIGRACIÓN: Mejora Estructura liquidation_calculations
-- =====================================================
-- Descripción: Agrega concept_id y mejora relaciones con tabla concepto
-- Autor: Claude Code Assistant
-- Fecha: 2025-09-23
-- Versión: 3.3.2

-- Usar la base de datos correcta

-- 1. Agregar campo concept_id para relación con tabla concepto
ALTER TABLE liquidation_calculations
ADD COLUMN concept_id INT(11) NULL AFTER termination_id;

-- 2. Crear foreign key constraint
ALTER TABLE liquidation_calculations
ADD CONSTRAINT fk_liquidation_calc_concept_id
FOREIGN KEY (concept_id) REFERENCES concepto(id)
ON DELETE RESTRICT
ON UPDATE CASCADE;

-- 3. Crear índice para optimizar consultas
CREATE INDEX idx_liquidation_calc_concept ON liquidation_calculations(concept_id);
CREATE INDEX idx_liquidation_calc_termination_concept ON liquidation_calculations(termination_id, concept_id);

-- 4. Migrar datos existentes (si existen)
-- Mapear concept_code existente a concept_id
UPDATE liquidation_calculations lc
INNER JOIN concepto c ON lc.concept_code = c.concepto
SET lc.concept_id = c.id
WHERE lc.concept_id IS NULL;

-- 5. Verificar que todos los registros tengan concept_id
SELECT
    COUNT(*) as total_records,
    COUNT(concept_id) as records_with_concept_id,
    COUNT(*) - COUNT(concept_id) as records_missing_concept_id
FROM liquidation_calculations;

-- 6. Agregar campo para marcar si necesita recálculo
ALTER TABLE employee_terminations
ADD COLUMN needs_recalculation TINYINT(1) DEFAULT 0;

-- 7. Crear índice para recálculos pendientes
CREATE INDEX idx_terminations_recalc ON employee_terminations(needs_recalculation, status);

-- =====================================================
-- VERIFICACIONES POST-MIGRACIÓN
-- =====================================================

-- Verificar estructura de liquidation_calculations
DESCRIBE liquidation_calculations;

-- Verificar foreign keys
SELECT
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'planilla_innova'
    AND TABLE_NAME = 'liquidation_calculations'
    AND CONSTRAINT_NAME LIKE 'fk_%';

-- Verificar datos migrados
SELECT
    lc.id,
    lc.concept_code,
    lc.concept_id,
    c.concepto as concept_from_table,
    c.descripcion
FROM liquidation_calculations lc
LEFT JOIN concepto c ON lc.concept_id = c.id
LIMIT 5;

COMMIT;

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================
-- 1. concept_id permite relacionar directamente con tabla concepto
-- 2. concept_code se mantiene por compatibilidad y auditoría
-- 3. needs_recalculation permite identificar liquidaciones que requieren recálculo
-- 4. Los foreign keys garantizan integridad referencial
-- 5. La migración preserva todos los datos existentes