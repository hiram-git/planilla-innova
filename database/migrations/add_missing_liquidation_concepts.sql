-- =====================================================
-- MIGRACIÓN: Agregar Conceptos Faltantes de Liquidación
-- =====================================================
-- Descripción: Agrega conceptos de liquidación según legislación panameña
-- Autor: Claude Code Assistant
-- Fecha: 2025-09-23
-- Versión: 3.3.3


-- 1. Prima de Antigüedad para Gastos de Representación
INSERT INTO concepto (concepto, descripcion, formula, tipo_concepto, categoria_reporte)
VALUES ('LIQ003', 'Prima Antigüedad - Gastos Representación', 'GASTOS_REPRESENTACION * ANOS_TRABAJADOS', 'ASIGNACION', 'liquidacion');

-- 2. Indemnización basada en últimos 11 meses (6.54%)
INSERT INTO concepto (concepto, descripcion, formula, tipo_concepto, categoria_reporte)
VALUES ('LIQ004', 'Indemnización por Despido (6.54%)', 'ACUMULADOS(\'SUELDO_REGULAR\', FINPERIODO(-11,\'MESES\'), FINPERIODO) * 0.0654', 'ASIGNACION', 'liquidacion');

-- 3. Vacaciones Proporcionales
INSERT INTO concepto (concepto, descripcion, formula, tipo_concepto, categoria_reporte)
VALUES ('LIQ005', 'Vacaciones Proporcionales', 'VACACIONES_PROPORCIONALES(FECHA_INGRESO, FECHA_TERMINACION, SUELDO_DIARIO)', 'ASIGNACION', 'liquidacion');

-- 4. Décimo Tercer Mes Proporcional
INSERT INTO concepto (concepto, descripcion, formula, tipo_concepto, categoria_reporte)
VALUES ('LIQ006', 'XIII Mes Proporcional', 'XIII_MES_PROPORCIONAL(FECHA_INGRESO, FECHA_TERMINACION, SUELDO_MENSUAL)', 'ASIGNACION', 'liquidacion');

-- 5. Descuento Seguro Social - Vacaciones
INSERT INTO concepto (concepto, descripcion, formula, tipo_concepto, categoria_reporte)
VALUES ('LIQ008', 'Descuento SS - Vacaciones', 'CONCEPTO(\'LIQ005\') * 0.0975', 'DEDUCCION', 'liquidacion');

-- 6. Descuento Seguro Social - XIII Mes
INSERT INTO concepto (concepto, descripcion, formula, tipo_concepto, categoria_reporte)
VALUES ('LIQ009', 'Descuento SS - XIII Mes', 'CONCEPTO(\'LIQ006\') * 0.0975', 'DEDUCCION', 'liquidacion');

-- 7. Preaviso (si aplica)
INSERT INTO concepto (concepto, descripcion, formula, tipo_concepto, categoria_reporte)
VALUES ('LIQ010', 'Preaviso', 'PREAVISO_LIQUIDACION(TIPO_TERMINACION, DIAS_PREAVISO, SUELDO_DIARIO)', 'ASIGNACION', 'liquidacion');

-- Relacionar todos los conceptos con la frecuencia de liquidación (ID: 9)
INSERT INTO concepto_frecuencias (concepto_id, frecuencia_id)
VALUES
((SELECT id FROM concepto WHERE concepto = 'LIQ003'), 9),
((SELECT id FROM concepto WHERE concepto = 'LIQ004'), 9),
((SELECT id FROM concepto WHERE concepto = 'LIQ005'), 9),
((SELECT id FROM concepto WHERE concepto = 'LIQ006'), 9),
((SELECT id FROM concepto WHERE concepto = 'LIQ008'), 9),
((SELECT id FROM concepto WHERE concepto = 'LIQ009'), 9),
((SELECT id FROM concepto WHERE concepto = 'LIQ010'), 9);

-- Verificar los conceptos agregados
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