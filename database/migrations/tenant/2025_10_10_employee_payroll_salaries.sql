-- =====================================================
-- MIGRACIÓN: Múltiples Salarios por Tipo de Planilla
-- Fecha: 10 de Octubre, 2025
-- Descripción: Sistema de salarios diferenciados por tipo de planilla
-- =====================================================

-- =====================================================
-- Tabla: employee_payroll_salaries
-- Almacena salarios específicos de cada empleado por tipo de planilla
-- =====================================================
CREATE TABLE IF NOT EXISTS employee_payroll_salaries (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Referencias
    employee_id INT NOT NULL COMMENT 'FK a tabla employees',
    tipo_planilla_id INT NOT NULL COMMENT 'FK a tabla tipos_planilla',

    -- Salarios base
    sueldo_base DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Salario base para este tipo de planilla',
    gastos_representacion DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Gastos de representación específicos',

    -- Vigencia (permite histórico de salarios)
    fecha_inicio DATE NOT NULL COMMENT 'Fecha de inicio de vigencia',
    fecha_fin DATE NULL COMMENT 'Fecha de fin de vigencia (NULL = indefinido)',

    -- Estado
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Indica si el registro está activo',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL COMMENT 'Usuario que creó el registro',
    updated_by INT NULL COMMENT 'Usuario que actualizó el registro',

    -- Notas
    notes TEXT COMMENT 'Observaciones sobre el salario',

    -- Índices y constraints
    UNIQUE KEY unique_employee_payroll_active (employee_id, tipo_planilla_id, is_active, fecha_fin),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_planilla_id) REFERENCES tipos_planilla(id) ON DELETE RESTRICT,

    INDEX idx_employee (employee_id),
    INDEX idx_tipo_planilla (tipo_planilla_id),
    INDEX idx_active (is_active),
    INDEX idx_vigencia (fecha_inicio, fecha_fin),
    INDEX idx_employee_tipo (employee_id, tipo_planilla_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Salarios de empleados diferenciados por tipo de planilla';

-- =====================================================
-- Migración de Datos Existentes
-- Transfiere salarios actuales de employees a employee_payroll_salaries
-- =====================================================

-- Crear tabla temporal para descomponer tipo_planilla_id con comas
DROP TEMPORARY TABLE IF EXISTS temp_employee_planillas;
CREATE TEMPORARY TABLE temp_employee_planillas (
    employee_id INT,
    tipo_planilla_id INT,
    sueldo_individual DECIMAL(10,2),
    gastos_representacion DECIMAL(10,2),
    fecha_ingreso DATE
);

-- Insertar empleados con sus tipos de planilla descompuestos
-- Maneja hasta 10 tipos de planilla por empleado (ajustable si necesario)
INSERT INTO temp_employee_planillas (employee_id, tipo_planilla_id, sueldo_individual, gastos_representacion, fecha_ingreso)
SELECT
    e.id,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(e.tipo_planilla_id, ',', numbers.n), ',', -1) AS UNSIGNED) as tipo_id,
    COALESCE(e.sueldo_individual, p.sueldo, 0) as sueldo_individual,
    COALESCE(e.gastos_representacion, 0) as gastos_representacion,
    COALESCE(e.fecha_ingreso, e.created_on, CURDATE()) as fecha_ingreso
FROM employees e
LEFT JOIN posiciones p ON e.position_id = p.id
CROSS JOIN (
    SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
) numbers
WHERE e.tipo_planilla_id IS NOT NULL
  AND e.tipo_planilla_id != ''
  AND CHAR_LENGTH(e.tipo_planilla_id) - CHAR_LENGTH(REPLACE(e.tipo_planilla_id, ',', '')) >= numbers.n - 1
  AND SUBSTRING_INDEX(SUBSTRING_INDEX(e.tipo_planilla_id, ',', numbers.n), ',', -1) != ''
  AND (e.sueldo_individual IS NOT NULL OR p.sueldo IS NOT NULL);

-- Insertar en la tabla definitiva desde la tabla temporal
INSERT INTO employee_payroll_salaries
    (employee_id, tipo_planilla_id, sueldo_base, gastos_representacion, fecha_inicio, is_active, notes)
SELECT
    tep.employee_id,
    tep.tipo_planilla_id,
    tep.sueldo_individual,
    tep.gastos_representacion,
    tep.fecha_ingreso,
    1,
    'Migrado automáticamente desde tabla employees'
FROM temp_employee_planillas tep
WHERE tep.tipo_planilla_id IS NOT NULL
  AND tep.tipo_planilla_id > 0
  AND tep.sueldo_individual > 0
ON DUPLICATE KEY UPDATE
    sueldo_base = VALUES(sueldo_base),
    gastos_representacion = VALUES(gastos_representacion),
    updated_at = CURRENT_TIMESTAMP;

-- Limpiar tabla temporal
DROP TEMPORARY TABLE IF EXISTS temp_employee_planillas;

-- =====================================================
-- Verificación de Migración
-- =====================================================
SELECT
    'Migración completada' as mensaje,
    COUNT(*) as total_registros_creados,
    COUNT(DISTINCT employee_id) as empleados_migrados,
    COUNT(DISTINCT tipo_planilla_id) as tipos_planilla_distintos
FROM employee_payroll_salaries
WHERE notes LIKE '%Migrado automáticamente%';

-- =====================================================
-- Mostrar resumen por tipo de planilla
-- =====================================================
SELECT
    tp.id,
    tp.descripcion as tipo_planilla,
    COUNT(eps.id) as empleados_con_salario,
    ROUND(AVG(eps.sueldo_base), 2) as salario_promedio,
    ROUND(MIN(eps.sueldo_base), 2) as salario_minimo,
    ROUND(MAX(eps.sueldo_base), 2) as salario_maximo
FROM tipos_planilla tp
LEFT JOIN employee_payroll_salaries eps ON tp.id = eps.tipo_planilla_id AND eps.is_active = 1
GROUP BY tp.id, tp.descripcion
ORDER BY tp.descripcion;

-- =====================================================
-- Verificar empleados sin salarios configurados
-- =====================================================
SELECT
    e.id,
    e.employee_id,
    CONCAT(e.firstname, ' ', e.lastname) as nombre_completo,
    e.tipo_planilla_id as tipos_asignados,
    COUNT(eps.id) as salarios_configurados
FROM employees e
LEFT JOIN employee_payroll_salaries eps ON e.id = eps.employee_id AND eps.is_active = 1
WHERE e.tipo_planilla_id IS NOT NULL
  AND e.tipo_planilla_id != ''
  AND e.situacion_id = 1
GROUP BY e.id
HAVING salarios_configurados = 0
LIMIT 10;
