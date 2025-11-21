-- =====================================================
-- Migración: Asignar Licencias a Tenants Existentes
-- Fecha: 2025-11-21
-- Versión: 3.5.8
-- Descripción: Genera y asigna licencias únicas a tenants
--              que fueron creados antes del sistema de licencias
-- =====================================================

USE planilla_master;

-- Verificar tenants sin licencia
SELECT
    id,
    company_name,
    ruc,
    license_key,
    db_name
FROM tenants
WHERE license_key IS NULL OR license_key = '';

-- =====================================================
-- IMPORTANTE: Ejecutar manualmente una a una
-- Reemplazar XXXX-XXXX-XXXX con licencias únicas
-- =====================================================

-- Ejemplo de asignación manual:
-- UPDATE tenants SET license_key = 'INNO-A1B2-C3D4-E5F6' WHERE id = 1;
-- UPDATE tenants SET license_key = 'INNO-F6E5-D4C3-B2A1' WHERE id = 2;
-- UPDATE tenants SET license_key = 'INNO-9876-5432-1098' WHERE id = 3;

-- =====================================================
-- Script Automático (OPCIÓN A): Generar licencias aleatorias
-- ⚠️ ADVERTENCIA: Solo usar para tenants de prueba
-- =====================================================

-- Tenant ID 6: Empresa Test
UPDATE tenants
SET license_key = 'INNO-TEST-0001-EF79'
WHERE id = 6 AND (license_key IS NULL OR license_key = '');

-- Tenant ID 7: empresa prueba
UPDATE tenants
SET license_key = 'INNO-TEST-0002-C775'
WHERE id = 7 AND (license_key IS NULL OR license_key = '');

-- Tenant ID 8: prueba2
UPDATE tenants
SET license_key = 'INNO-TEST-0003-500B'
WHERE id = 8 AND (license_key IS NULL OR license_key = '');

-- Tenant ID 9: prueba3
UPDATE tenants
SET license_key = 'INNO-TEST-0004-E0D3'
WHERE id = 9 AND (license_key IS NULL OR license_key = '');

-- =====================================================
-- Verificar resultado
-- =====================================================

SELECT
    id,
    company_name,
    license_key,
    db_name,
    status,
    license_status
FROM tenants
ORDER BY id;

-- =====================================================
-- Verificar unicidad de licencias
-- =====================================================

SELECT
    license_key,
    COUNT(*) as count
FROM tenants
WHERE license_key IS NOT NULL
GROUP BY license_key
HAVING count > 1;

-- Si el query anterior devuelve resultados, hay licencias duplicadas
-- Corregir manualmente con licencias únicas

-- =====================================================
-- NOTA: Para tenants de producción
-- =====================================================

-- Usar el siguiente PHP script para generar licencias únicas:
-- php -r "echo 'INNO-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)) . PHP_EOL;"

-- Ejecutar múltiples veces y asignar manualmente a cada tenant
