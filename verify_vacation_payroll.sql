-- Script para verificar que las planillas de vacaciones se crean correctamente
-- Ejecutar después de generar una planilla nueva

-- 1. Ver últimas planillas de vacaciones creadas
SELECT
    id,
    descripcion,
    frecuencia_id,
    estado,
    fecha,
    CASE
        WHEN frecuencia_id = 6 THEN '✅ CORRECTO'
        WHEN frecuencia_id = 11 THEN '❌ INCORRECTO (hardcoded)'
        ELSE '⚠️ OTRO VALOR'
    END as validacion
FROM planilla_cabecera
WHERE descripcion LIKE '%VACACION%'
ORDER BY id DESC
LIMIT 5;

-- 2. Ver frecuencias disponibles
SELECT id, codigo, nombre
FROM frecuencias
ORDER BY id;
