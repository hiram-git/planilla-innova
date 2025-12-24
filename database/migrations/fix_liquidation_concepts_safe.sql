-- =====================================================
-- MIGRACIÓN: Corregir y Completar Conceptos de Liquidación
-- =====================================================


-- Primero limpiar duplicados
DELETE c1 FROM concepto c1
INNER JOIN concepto c2
WHERE c1.id > c2.id AND c1.concepto = c2.concepto AND c1.concepto LIKE 'LIQ%';

-- Agregar conceptos faltantes si no existen
INSERT IGNORE INTO concepto (concepto, descripcion, formula, tipo_concepto, categoria_reporte)
VALUES
('LIQ003', 'Prima Antigüedad - Gastos Representación', 'GASTOS_REPRESENTACION * ANOS_TRABAJADOS', 'ASIGNACION', 'liquidacion'),
('LIQ004', 'Indemnización por Despido (6.54%)', 'ACUMULADOS(\"SUELDO_REGULAR\", FINPERIODO(-11,\"MESES\"), FINPERIODO) * 0.0654', 'ASIGNACION', 'liquidacion'),
('LIQ005', 'Vacaciones Proporcionales', 'SUELDO_DIARIO * DIAS_VACACIONES_PENDIENTES', 'ASIGNACION', 'liquidacion'),
('LIQ006', 'XIII Mes Proporcional', 'SUELDO_MENSUAL * MESES_TRABAJADOS_ANO_ACTUAL / 12', 'ASIGNACION', 'liquidacion'),
('LIQ008', 'Descuento SS - Vacaciones', 'CONCEPTO(\"LIQ005\") * 0.0975', 'DEDUCCION', 'liquidacion'),
('LIQ009', 'Descuento SS - XIII Mes', 'CONCEPTO(\"LIQ006\") * 0.0975', 'DEDUCCION', 'liquidacion'),
('LIQ010', 'Preaviso', 'SUELDO_DIARIO * DIAS_PREAVISO', 'ASIGNACION', 'liquidacion');

-- Limpiar relaciones duplicadas
DELETE cf1 FROM concepto_frecuencias cf1
INNER JOIN concepto_frecuencias cf2
WHERE cf1.id > cf2.id AND cf1.concepto_id = cf2.concepto_id AND cf1.frecuencia_id = cf2.frecuencia_id;

-- Relacionar conceptos con frecuencia de liquidación (uno por uno)
INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id)
SELECT c.id, 9 FROM concepto c WHERE c.concepto = 'LIQ003';

INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id)
SELECT c.id, 9 FROM concepto c WHERE c.concepto = 'LIQ004';

INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id)
SELECT c.id, 9 FROM concepto c WHERE c.concepto = 'LIQ005';

INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id)
SELECT c.id, 9 FROM concepto c WHERE c.concepto = 'LIQ006';

INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id)
SELECT c.id, 9 FROM concepto c WHERE c.concepto = 'LIQ008';

INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id)
SELECT c.id, 9 FROM concepto c WHERE c.concepto = 'LIQ009';

INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id)
SELECT c.id, 9 FROM concepto c WHERE c.concepto = 'LIQ010';

-- Verificar resultado final
SELECT
    c.concepto,
    c.descripcion,
    c.formula,
    c.tipo_concepto
FROM concepto c
INNER JOIN concepto_frecuencias cf ON c.id = cf.concepto_id
WHERE cf.frecuencia_id = 9
ORDER BY c.tipo_concepto, c.concepto;

COMMIT;