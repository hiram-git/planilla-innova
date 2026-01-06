-- Query de verificación para diagnóstico de préstamos
-- Ejecutar en la base de datos: pinn49411848

-- 1. Verificar conceptos de préstamo con creditor_id
SELECT
    'Conceptos de préstamo' AS tipo,
    c.id,
    c.codigo,
    c.descripcion,
    c.creditor_id,
    c.tipo_concepto,
    cr.nombre AS nombre_acreedor
FROM concepto c
LEFT JOIN creditors cr ON c.creditor_id = cr.id
WHERE c.creditor_id IS NOT NULL
ORDER BY c.id;

-- 2. Verificar detalles de planilla con conceptos de préstamo
-- CAMBIAR 120 por el ID de tu planilla
SELECT
    'Detalles planilla con préstamos' AS tipo,
    pd.id,
    pd.employee_id,
    CONCAT(e.firstname, ' ', e.lastname) AS empleado,
    pd.concepto_id,
    c.codigo AS concepto_codigo,
    c.descripcion AS concepto_descripcion,
    c.creditor_id,
    pd.monto
FROM planilla_detalle pd
INNER JOIN concepto c ON c.id = pd.concepto_id
INNER JOIN employees e ON e.id = pd.employee_id
WHERE pd.planilla_cabecera_id = 120  -- CAMBIAR POR TU ID DE PLANILLA
AND c.creditor_id IS NOT NULL
AND c.tipo_concepto = 'D'
AND pd.monto > 0;

-- 3. Verificar cuotas pendientes
SELECT
    'Cuotas pendientes' AS tipo,
    li.id,
    li.loan_id,
    l.employee_id,
    CONCAT(e.firstname, ' ', e.lastname) AS empleado,
    l.creditor_id,
    cr.nombre AS acreedor,
    li.installment_number AS cuota_numero,
    li.amount AS monto,
    li.status,
    li.due_date,
    l.status AS prestamo_status
FROM loan_installments li
INNER JOIN loans l ON l.id = li.loan_id
INNER JOIN employees e ON e.id = l.employee_id
LEFT JOIN creditors cr ON cr.id = l.creditor_id
WHERE li.status = 'pendiente'
AND l.status = 'activo'
ORDER BY l.employee_id, li.due_date;

-- 4. Verificar planilla_id y paid_date de cuotas
SELECT
    'Estado de cuotas' AS tipo,
    li.id,
    li.loan_id,
    li.installment_number,
    li.status,
    li.planilla_id,
    li.paid_date,
    l.employee_id,
    CONCAT(e.firstname, ' ', e.lastname) AS empleado
FROM loan_installments li
INNER JOIN loans l ON l.id = li.loan_id
INNER JOIN employees e ON e.id = l.employee_id
ORDER BY l.employee_id, li.installment_number
LIMIT 20;

-- 5. Verificar si existe la columna planilla_id
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'pinn49411848'
AND TABLE_NAME = 'loan_installments'
AND COLUMN_NAME IN ('planilla_id', 'paid_date', 'status');
