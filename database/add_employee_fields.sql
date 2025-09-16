-- =====================================================
-- Agregar campos situacion_id y tipo_planilla_id a empleados
-- Sistema de Planillas MVC
-- Fecha: Agosto 2025
-- =====================================================

USE `planilla-simple`;

-- Agregar columnas a la tabla employees
ALTER TABLE `employees` 
ADD COLUMN `situacion_id` int DEFAULT NULL AFTER `schedule_id`,
ADD COLUMN `tipo_planilla_id` int DEFAULT NULL AFTER `situacion_id`;

-- Agregar índices para mejor performance
ALTER TABLE `employees` 
ADD KEY `fk_employees_situacion` (`situacion_id`),
ADD KEY `fk_employees_tipo_planilla` (`tipo_planilla_id`);

-- Agregar foreign keys
ALTER TABLE `employees` 
ADD CONSTRAINT `fk_employees_situacion` FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `fk_employees_tipo_planilla` FOREIGN KEY (`tipo_planilla_id`) REFERENCES `tipos_planilla` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Actualizar empleados existentes con valores por defecto
-- Asignar situación "Activo" (ID=1) a todos los empleados activos
UPDATE `employees` SET `situacion_id` = 1 WHERE `active` = 1 AND `situacion_id` IS NULL;

-- Asignar tipo de planilla "Ordinaria" (ID=1) a todos los empleados
UPDATE `employees` SET `tipo_planilla_id` = 1 WHERE `tipo_planilla_id` IS NULL;

-- =====================================================
-- Actualizar vista de empleados completos
-- =====================================================

DROP VIEW IF EXISTS `view_employees_full`;

CREATE VIEW `view_employees_full` AS
SELECT 
    e.id,
    e.employee_id,
    e.firstname,
    e.lastname,
    CONCAT(e.firstname, ' ', e.lastname) as full_name,
    e.document_id,
    e.birthdate,
    e.gender,
    e.address,
    e.contact_info,
    e.fecha_ingreso,
    p.descripcion as position_name,
    p.sueldo as position_salary,
    c.descripcion as cargo_name,
    part.partida as partida_code,
    part.descripcion as partida_name,
    f.descripcion as funcion_name,
    s.name as schedule_name,
    sit.descripcion as situacion_name,
    sit.codigo as situacion_codigo,
    tp.descripcion as tipo_planilla_name,
    tp.codigo as tipo_planilla_codigo,
    CONCAT(TIME_FORMAT(s.time_in, '%H:%i'), ' - ', TIME_FORMAT(s.time_out, '%H:%i')) as horario,
    e.active,
    e.created_on
FROM employees e
LEFT JOIN position p ON e.position_id = p.id
LEFT JOIN cargos c ON e.cargo_id = c.id
LEFT JOIN partidas part ON e.partida_id = part.id
LEFT JOIN funciones f ON e.funcion_id = f.id
LEFT JOIN schedules s ON e.schedule_id = s.id
LEFT JOIN situaciones sit ON e.situacion_id = sit.id
LEFT JOIN tipos_planilla tp ON e.tipo_planilla_id = tp.id;

-- =====================================================
-- Script completado exitosamente
-- =====================================================
SELECT 'Campos situacion_id y tipo_planilla_id agregados exitosamente a la tabla employees' as mensaje;