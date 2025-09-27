-- =====================================================
-- MIGRACIÓN: Eliminar campo redundante incluir_en_acumulado
-- =====================================================
-- Propósito: Simplificar lógica de acumulados eliminando campo redundante
-- Fecha: 2025-09-26
-- Razón: Si un concepto está seleccionado para un acumulado, automáticamente se incluye

-- PASO 1: Verificar estructura actual
SELECT
    'Estado inicial tabla conceptos_acumulados' as paso,
    COUNT(*) as total_registros,
    SUM(CASE WHEN incluir_en_acumulado = 1 THEN 1 ELSE 0 END) as incluidos,
    SUM(CASE WHEN incluir_en_acumulado = 0 THEN 1 ELSE 0 END) as excluidos
FROM conceptos_acumulados;

-- PASO 2: Mostrar registros que están marcados como NO incluir
SELECT
    'Registros que se eliminarán (incluir_en_acumulado = 0)' as info,
    ca.concepto_id,
    c.concepto,
    c.descripcion as concepto_descripcion,
    ta.codigo as tipo_acumulado_codigo,
    ta.descripcion as tipo_acumulado_descripcion
FROM conceptos_acumulados ca
INNER JOIN concepto c ON ca.concepto_id = c.id
INNER JOIN tipos_acumulados ta ON ca.tipo_acumulado_id = ta.id
WHERE ca.incluir_en_acumulado = 0;

-- PASO 3: Eliminar registros donde incluir_en_acumulado = 0
-- (Si no están incluidos, no deberían existir en la tabla)
DELETE FROM conceptos_acumulados WHERE incluir_en_acumulado = 0;

-- PASO 4: Eliminar el campo incluir_en_acumulado
ALTER TABLE conceptos_acumulados DROP COLUMN incluir_en_acumulado;

-- PASO 5: Verificación final
DESCRIBE conceptos_acumulados;

-- PASO 6: Mostrar estructura final y conteo
SELECT
    'Estado final tabla conceptos_acumulados' as paso,
    COUNT(*) as total_registros_finales
FROM conceptos_acumulados;

-- Mensaje final
SELECT
    'MIGRACIÓN COMPLETADA' as status,
    'Campo incluir_en_acumulado eliminado exitosamente' as message,
    'Lógica simplificada: si existe registro = se incluye en acumulado' as nueva_logica,
    NOW() as timestamp_completion;