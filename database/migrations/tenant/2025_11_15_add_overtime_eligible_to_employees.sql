-- =====================================================
-- Migración: Agregar campo elegibilidad horas extras a employees
-- Fecha: 15 de Noviembre, 2025
-- Propósito: Diferenciar empleados elegibles para horas extras vs empleados exentos
-- Impacto: Solo empleados con permite_horas_extras=1 generarán overtime PENDING
-- =====================================================

-- ========================================
-- PASO 1: Agregar campo permite_horas_extras
-- ========================================

ALTER TABLE employees
ADD COLUMN permite_horas_extras TINYINT(1) NOT NULL DEFAULT 1
    COMMENT 'Indica si el empleado es elegible para horas extras (1=Sí, 0=No/Exento)'
    AFTER marca_asistencia;

-- ========================================
-- PASO 2: Comentarios y uso
-- ========================================

/*
CAMPO: permite_horas_extras

VALORES:
- 1 (Sí): Empleado elegible para horas extras
  * Empleados por hora
  * Empleados no gerenciales
  * Posiciones operativas
  * DEFAULT para todos los empleados existentes

- 0 (No/Exento): Empleado NO elegible para horas extras
  * Gerentes y ejecutivos
  * Empleados con salario fijo mensual
  * Posiciones exentas según legislación

FLUJO:
1. Empleado trabaja más de 8 horas
2. AttendanceCalculator verifica permite_horas_extras
3. SI permite_horas_extras = 1:
   - overtime_status = 'PENDING' (requiere aprobación)
4. SI permite_horas_extras = 0:
   - overtime_status = 'NOT_APPLICABLE' (no genera horas extras)
   - Las horas extras NO se calculan ni se incluyen en planilla

CASOS DE USO:
- Gerentes: permite_horas_extras = 0
- Supervisores: permite_horas_extras = 1 (si aplica según política)
- Operativos: permite_horas_extras = 1
- Personal administrativo: permite_horas_extras = 1
*/

-- ========================================
-- PASO 3: Actualizar empleados específicos (opcional)
-- ========================================

-- Ejemplo: Marcar gerentes como NO elegibles para horas extras
-- Descomentar y ajustar según estructura organizacional

/*
UPDATE employees
SET permite_horas_extras = 0
WHERE position_id IN (
    -- NOTA: SELECT comentado para evitar error PDO "pending result sets"
    -- SELECT id FROM posiciones WHERE nombre LIKE '%Gerente%' OR nombre LIKE '%Director%'
-- );
*/

-- ========================================
-- PASO 4: Crear índice para consultas optimizadas
-- ========================================

CREATE INDEX idx_employees_overtime_eligible
ON employees(permite_horas_extras);

-- ========================================
-- VERIFICACIÓN
-- ========================================

-- Consulta para verificar que el campo se agregó correctamente
-- SELECT id, firstname, lastname, permite_horas_extras, marca_asistencia
-- FROM employees
-- WHERE activo = 1
-- LIMIT 10;

-- Estadísticas de empleados por elegibilidad horas extras
-- SELECT
--     permite_horas_extras,
--     COUNT(*) as total_empleados,
--     CASE
--         WHEN permite_horas_extras = 1 THEN 'Elegibles para horas extras'
--         ELSE 'Exentos (no generan horas extras)'
--     END as descripcion
-- FROM employees
-- WHERE activo = 1
-- GROUP BY permite_horas_extras;

-- ========================================
-- ROLLBACK (si es necesario)
-- ========================================

-- Para revertir esta migración:
/*
DROP INDEX idx_employees_overtime_eligible ON employees;
ALTER TABLE employees DROP COLUMN permite_horas_extras;
*/

-- ========================================
-- NOTAS DE IMPLEMENTACIÓN
-- ========================================

/*
INTEGRACIÓN CON AttendanceCalculator:

Modificar el método determineOvertimeStatus():

private function determineOvertimeStatus($overtimeHours, $employeeAllowsOvertime)
{
    // Si el empleado NO permite horas extras, marcar como NOT_APPLICABLE
    if (!$employeeAllowsOvertime) {
        return 'NOT_APPLICABLE';
    }

    // Si no hay horas extras, marcar como NO APLICABLE
    if ($overtimeHours == 0 || $overtimeHours === null) {
        return 'NOT_APPLICABLE';
    }

    // Si hay horas extras Y el empleado permite, marcar como PENDIENTE
    return 'PENDING';
}

VENTAJAS:
1. Cumplimiento legal: Respeta exenciones de horas extras
2. Flexibilidad: Configuración por empleado individual
3. Ahorro administrativo: Reduce aprobaciones innecesarias
4. Claridad: Distingue empleados elegibles vs exentos
5. Auditoría: Registro claro de quién puede/no puede generar OT

SIGUIENTE PASO:
- Modificar AttendanceCalculator.php para consultar este campo
- Agregar campo en formulario de empleados (UI)
*/
