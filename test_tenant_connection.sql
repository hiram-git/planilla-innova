-- ========================================
-- TEST TENANT CONNECTION
-- Verificar que Controller.php conecta a BD tenant correcta
-- ========================================

-- 1. Ver licencias disponibles
SELECT
    id,
    company_name,
    ruc,
    license_key,
    db_name,
    status
FROM planilla_master.tenants
WHERE status = 'ACTIVE'
ORDER BY id DESC;

-- 2. Verificar que existe la BD del tenant
SHOW DATABASES LIKE 'PINN%';

-- 3. Ver empleados en BD tenant (ejemplo con PINN0000000004)
USE PINN0000000004;
SELECT COUNT(*) as total_empleados FROM employees;
SELECT id, firstname, lastname, situacion_id FROM employees LIMIT 5;

-- 4. Ver solicitudes de vacaciones en BD tenant
SELECT COUNT(*) as total_solicitudes FROM vacation_requests;
SELECT id, employee_id, status, start_date, end_date FROM vacation_requests LIMIT 5;

-- 5. Ver liquidaciones en BD tenant
SELECT COUNT(*) as total_liquidaciones FROM employee_terminations;

-- ========================================
-- PRUEBA DESPUÉS DE LA CORRECCIÓN
-- ========================================
-- 1. Login con licencia: PINN0000000004
-- 2. Ir a: http://localhost/planilla-innova/panel/vacation
-- 3. Ejecutar en consola PHP (o verificar logs):
--    error_log("Current DB: " . $this->db->query("SELECT DATABASE()")->fetchColumn());
--    Debe retornar: PINN0000000004
--
-- 4. Verificar que los datos mostrados coinciden con:
USE PINN0000000004;
SELECT e.id, e.firstname, e.lastname,
       (SELECT COUNT(*) FROM vacation_requests WHERE employee_id = e.id) as solicitudes
FROM employees e
WHERE situacion_id = 1
LIMIT 10;

-- ========================================
-- VERIFICACIÓN DE DATOS CORRECTOS
-- ========================================
-- Si la corrección funciona correctamente:
-- ✅ VacationController debe mostrar SOLO empleados de PINN0000000004
-- ✅ LiquidationController debe mostrar SOLO empleados de PINN0000000004
-- ❌ NO debe mezclar datos de planilla_prod

-- Comparar con BD incorrecta (planilla_prod):
USE planilla_prod;
SELECT COUNT(*) as empleados_prod FROM employees WHERE situacion_id = 1;

-- Si VacationController muestra la cantidad de empleados_prod,
-- entonces la corrección NO funcionó.
