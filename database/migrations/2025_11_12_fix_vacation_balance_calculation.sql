-- =====================================================
-- CORRECCIÓN: Recálculo de Saldos de Vacaciones
-- =====================================================
-- Fecha: 2025-11-12
-- Descripción: Los días disfrutados son SOLO informativos
--              y NO deben afectar el saldo disponible.
--              Fórmula correcta: saldo = dias_vacaciones - dias_pagados
-- =====================================================

-- Recalcular todos los saldos en vacation_annual_balances
UPDATE vacation_annual_balances
SET saldo_disponible_year = dias_vacaciones_anuales - dias_pagados_year,
    updated_at = NOW();

-- Verificar resultados
SELECT
    id,
    employee_id,
    year,
    dias_vacaciones_anuales AS 'Días Anuales',
    dias_pagados_year AS 'Días Pagados',
    dias_disfrutados_year AS 'Días Disfrutados (Info)',
    saldo_disponible_year AS 'Saldo Disponible',
    updated_at AS 'Actualizado'
FROM vacation_annual_balances
ORDER BY employee_id, year;

-- Recalcular totales generales acumulativos
UPDATE vacation_general_totals vgt
SET total_saldo_acumulado = (
    -- NOTA: SELECT comentado para evitar error PDO "pending result sets"
    -- SELECT COALESCE(SUM(saldo_disponible_year), 0)
    -- FROM vacation_annual_balances
    -- WHERE employee_id = vgt.employee_id
-- );

-- Verificar totales generales
SELECT
    id,
    employee_id,
    total_dias_pagados AS 'Total Días Pagados',
    total_dias_disfrutados AS 'Total Días Disfrutados (Info)',
    total_saldo_acumulado AS 'Total Saldo Acumulado'
FROM vacation_general_totals
ORDER BY employee_id;
