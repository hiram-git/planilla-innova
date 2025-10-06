-- =====================================================
-- Migración: Soporte Múltiples Tipos de Planilla
-- Fecha: 2025-10-06 10:54
-- Versión: 3.3.20
-- Descripción: Convierte tipo_planilla_id de INT a VARCHAR(255)
--              para permitir múltiples tipos de planilla por empleado
--              separados por comas (ej: "1,3,5")
-- =====================================================

-- BACKUP: Crear tabla de respaldo
DROP TABLE IF EXISTS employees_tipo_planilla_backup_20251006;
CREATE TABLE employees_tipo_planilla_backup_20251006 AS
SELECT id, tipo_planilla_id
FROM employees;

-- Verificar backup
SELECT 'BACKUP CREADO' AS status, COUNT(*) AS total_registros
FROM employees_tipo_planilla_backup_20251006;

-- PASO 1: Eliminar foreign key constraint
SET @fk_name = (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'employees'
    AND COLUMN_NAME = 'tipo_planilla_id'
    AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

SET @sql = IF(@fk_name IS NOT NULL,
    CONCAT('ALTER TABLE employees DROP FOREIGN KEY ', @fk_name),
    'SELECT "No foreign key found" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'FOREIGN KEY ELIMINADA' AS status;

-- PASO 2: Modificar columna a VARCHAR(255)
ALTER TABLE employees
MODIFY COLUMN tipo_planilla_id VARCHAR(255) NULL
COMMENT 'IDs de tipos de planilla separados por comas (ej: 1,3,5)';

SELECT 'COLUMNA MODIFICADA A VARCHAR(255)' AS status;

-- PASO 3: Verificar integridad de datos
SELECT
    'VERIFICACION DE DATOS' AS status,
    (SELECT COUNT(*) FROM employees WHERE tipo_planilla_id IS NOT NULL) AS registros_actuales,
    (SELECT COUNT(*) FROM employees_tipo_planilla_backup_20251006 WHERE tipo_planilla_id IS NOT NULL) AS registros_backup,
    CASE
        WHEN (SELECT COUNT(*) FROM employees WHERE tipo_planilla_id IS NOT NULL) =
             (SELECT COUNT(*) FROM employees_tipo_planilla_backup_20251006 WHERE tipo_planilla_id IS NOT NULL)
        THEN 'OK - Datos íntegros'
        ELSE 'ERROR - Inconsistencia detectada'
    END AS resultado;

-- PASO 4: Mostrar distribución de datos
SELECT
    'DISTRIBUCION ACTUAL' AS info,
    tipo_planilla_id,
    COUNT(*) AS total_empleados
FROM employees
WHERE tipo_planilla_id IS NOT NULL
GROUP BY tipo_planilla_id
ORDER BY tipo_planilla_id;

-- =====================================================
-- ROLLBACK SCRIPT (ejecutar solo si es necesario)
-- =====================================================
/*
-- Para revertir la migración, ejecutar estos comandos:

-- 1. Restaurar datos desde backup
UPDATE employees e
INNER JOIN employees_tipo_planilla_backup_20251006 b ON e.id = b.id
SET e.tipo_planilla_id = b.tipo_planilla_id;

-- 2. Revertir columna a INT
ALTER TABLE employees
MODIFY COLUMN tipo_planilla_id INT(11) NULL;

-- 3. Recrear foreign key
ALTER TABLE employees
ADD CONSTRAINT fk_employees_tipo_planilla
FOREIGN KEY (tipo_planilla_id)
REFERENCES tipos_planilla(id)
ON DELETE SET NULL;

-- 4. Eliminar tabla de backup
DROP TABLE IF EXISTS employees_tipo_planilla_backup_20251006;

SELECT 'ROLLBACK COMPLETADO' AS status;
*/

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================
/*
1. Esta migración permite que un empleado pertenezca a múltiples tipos de planilla
2. Los valores se almacenan como VARCHAR separados por comas (ej: "1,3,5")
3. Las consultas deben usar FIND_IN_SET() para filtrar:
   WHERE FIND_IN_SET(?, e.tipo_planilla_id)
4. El frontend usa Select2 con multiple="multiple"
5. El backend convierte arrays a strings: implode(',', $array)
6. Se mantiene la tabla de backup para auditoría y rollback

IMPACTO EN CÓDIGO:
- Employee Model: getEmployeesByTipoPlanilla() usa FIND_IN_SET()
- EmployeeController: store() y update() usan implode()
- Vistas: create.php y edit.php usan Select2 múltiple
- Filtros: Dashboard y Acumulados actualizados para múltiples valores
*/

SELECT 'MIGRACION COMPLETADA EXITOSAMENTE' AS status;
