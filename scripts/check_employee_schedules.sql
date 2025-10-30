-- Script para verificar horarios de empleados

-- 1. Ver cuántos empleados tienen schedule_id asignado
SELECT
    COUNT(*) as total_empleados,
    SUM(CASE WHEN schedule_id IS NOT NULL THEN 1 ELSE 0 END) as con_horario,
    SUM(CASE WHEN schedule_id IS NULL THEN 1 ELSE 0 END) as sin_horario
FROM employees
WHERE situacion_id = 1;

-- 2. Ver detalles de empleados sin horario
SELECT
    id,
    firstname,
    lastname,
    email,
    schedule_id
FROM employees
WHERE situacion_id = 1
  AND schedule_id IS NULL
LIMIT 10;

-- 3. Ver horarios disponibles en la tabla schedules
SELECT * FROM schedules LIMIT 10;

-- 4. Ver registros de attendance_detail sin horario programado
SELECT
    d.id,
    d.employee_id,
    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
    d.schedule_id,
    d.scheduled_time_in,
    d.scheduled_time_out,
    d.time_in,
    d.time_out,
    h.attendance_date
FROM attendance_detail d
INNER JOIN employees e ON d.employee_id = e.id
INNER JOIN attendance_header h ON d.header_id = h.id
WHERE (d.scheduled_time_in IS NULL OR d.scheduled_time_out IS NULL)
  AND h.attendance_date = '2025-10-29'
LIMIT 10;
