-- =====================================================
-- MIGRACIÓN FINAL: Limpiar y Completar Conceptos de Liquidación
-- =====================================================


-- Primero remover relaciones duplicadas y conflictivas
DELETE FROM concepto_frecuencias WHERE concepto_id IN (
    SELECT id FROM concepto WHERE concepto LIKE 'LIQ%' AND id NOT IN (21, 22, 23, 24, 25, 26, 27, 28)
);

-- Eliminar conceptos duplicados manteniendo solo los que están en uso
DELETE FROM concepto WHERE concepto LIKE 'LIQ%' AND id NOT IN (21, 22, 23, 24, 25, 26, 27, 28);

-- Actualizar conceptos existentes para que tengan las fórmulas correctas
UPDATE concepto SET
    descripcion = 'Prima de Antigüedad',
    formula = 'SUELDO_SEMANAL * ANOS_TRABAJADOS',
    tipo_concepto = 'ASIGNACION'
WHERE id = 21;

UPDATE concepto SET
    descripcion = 'Indemnización por Despido (6.54%)',
    formula = 'ACUMULADOS("SUELDO_REGULAR", FINPERIODO(-11,"MESES"), FINPERIODO) * 0.0654',
    tipo_concepto = 'ASIGNACION'
WHERE id = 22;

UPDATE concepto SET
    descripcion = 'Prima Antigüedad - Gastos Representación',
    formula = 'GASTOS_REPRESENTACION * ANOS_TRABAJADOS',
    tipo_concepto = 'ASIGNACION'
WHERE id = 23;

UPDATE concepto SET
    descripcion = 'Vacaciones Proporcionales',
    formula = 'SUELDO_DIARIO * DIAS_VACACIONES_PENDIENTES',
    tipo_concepto = 'ASIGNACION'
WHERE id = 24;

UPDATE concepto SET
    descripcion = 'XIII Mes Proporcional',
    formula = 'SUELDO_MENSUAL * MESES_TRABAJADOS_ANO_ACTUAL / 12',
    tipo_concepto = 'ASIGNACION'
WHERE id = 25;

UPDATE concepto SET
    descripcion = 'Preaviso',
    formula = 'SUELDO_DIARIO * DIAS_PREAVISO',
    tipo_concepto = 'ASIGNACION'
WHERE id = 26;

UPDATE concepto SET
    descripcion = 'Descuento Préstamos',
    formula = 'DESCUENTO_PRESTAMOS()',
    tipo_concepto = 'DEDUCCION'
WHERE id = 27;

UPDATE concepto SET
    descripcion = 'Descuento SS - Vacaciones',
    formula = 'CONCEPTO("LIQ005") * 0.0975',
    tipo_concepto = 'DEDUCCION'
WHERE id = 28;

-- Agregar el concepto LIQ009 si no existe con el ID correcto
INSERT IGNORE INTO concepto (concepto, descripcion, formula, tipo_concepto, categoria_reporte)
VALUES ('LIQ009', 'Descuento SS - XIII Mes', 'CONCEPTO("LIQ006") * 0.0975', 'DEDUCCION', 'liquidacion');

-- Asegurarse que todos los conceptos estén relacionados con la frecuencia de liquidación
INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id)
VALUES (21, 9), (22, 9), (23, 9), (24, 9), (25, 9), (26, 9), (27, 9), (28, 9);

-- Relacionar el concepto LIQ009 con la frecuencia de liquidación
INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id)
SELECT c.id, 9 FROM concepto c WHERE c.concepto = 'LIQ009' AND c.formula = 'CONCEPTO("LIQ006") * 0.0975';

-- Verificar resultado final
SELECT
    c.concepto,
    c.descripcion,
    c.formula,
    c.tipo_concepto
FROM concepto c
INNER JOIN concepto_frecuencias cf ON c.id = cf.concepto_id
WHERE cf.frecuencia_id = 9
ORDER BY c.concepto;

COMMIT;