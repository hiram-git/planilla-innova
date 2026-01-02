-- =====================================================
-- Migración: Sistema de Conceptos Manuales por Empleado
-- Fecha: 2026-01-02
-- Versión: 3.5.18
-- Descripción: Permite asignar conceptos (ausencias, tardanzas, bonos, etc.)
--              manualmente a empleados sin necesidad de sincronización de marcaciones
-- =====================================================

-- Crear tabla de conceptos manuales por empleado
CREATE TABLE IF NOT EXISTS employee_manual_concepts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Relaciones
    employee_id INT NOT NULL COMMENT 'ID interno del empleado (FK a employees.id)',
    concepto_id INT NOT NULL COMMENT 'ID del concepto a aplicar (FK a concepto.id)',

    -- Filtros de aplicación
    tipo_planilla_id INT NULL COMMENT 'Si es NULL, aplica a todos los tipos de planilla',
    frecuencia_id INT NULL COMMENT 'Si es NULL, aplica a todas las frecuencias',

    -- Valores de cálculo
    unidad DECIMAL(10,2) DEFAULT 1.00 COMMENT 'Unidad para el cálculo (días, horas, etc.)',
    monto_fijo DECIMAL(10,2) NULL COMMENT 'Si se especifica, ignora fórmula y usa este monto',
    observaciones TEXT COMMENT 'Notas sobre la asignación (motivo, referencia, etc.)',

    -- Periodo de aplicación
    fecha_inicio DATE NOT NULL COMMENT 'Fecha desde la cual aplica',
    fecha_fin DATE NULL COMMENT 'Fecha hasta la cual aplica (NULL = indefinido)',
    aplica_una_vez TINYINT(1) DEFAULT 0 COMMENT '1 = Solo se aplica una vez y se marca como APLICADO',

    -- Control de estado
    estado ENUM('ACTIVO', 'APLICADO', 'CANCELADO') DEFAULT 'ACTIVO' COMMENT 'ACTIVO = Pendiente aplicación, APLICADO = Ya procesado, CANCELADO = Anulado',
    planilla_id INT NULL COMMENT 'ID de la planilla donde se aplicó (cuando aplica_una_vez = 1)',
    fecha_aplicacion DATETIME NULL COMMENT 'Fecha/hora en que se aplicó',

    -- Auditoría
    created_by INT UNSIGNED NULL COMMENT 'Usuario que creó el registro',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    INDEX idx_employee (employee_id),
    INDEX idx_concepto (concepto_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_inicio (fecha_inicio),
    INDEX idx_fecha_fin (fecha_fin),
    INDEX idx_tipo_planilla (tipo_planilla_id),
    INDEX idx_frecuencia (frecuencia_id),

    -- Foreign Keys
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (concepto_id) REFERENCES concepto(id) ON DELETE RESTRICT,
    FOREIGN KEY (tipo_planilla_id) REFERENCES tipos_planilla(id) ON DELETE SET NULL,
    FOREIGN KEY (frecuencia_id) REFERENCES frecuencias(id) ON DELETE SET NULL,
    FOREIGN KEY (planilla_id) REFERENCES planilla_cabecera(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Asignaciones manuales de conceptos a empleados específicos';

-- =====================================================
-- Permisos para el nuevo módulo
-- =====================================================
-- NOTA: Los permisos y menús se configurarán manualmente en el sidebar
-- ya que este sistema no usa las tablas menus/permissions

/*
-- Insertar nuevo menú en la tabla menus
INSERT INTO menus (nombre, url, icono, orden, parent_id, activo)
VALUES ('Conceptos Manuales', '/panel/employee-manual-concepts', 'fas fa-user-edit', 8,
        (SELECT id FROM (SELECT id FROM menus WHERE nombre = 'Gestión de Planillas' LIMIT 1) AS tmp),
        1)
ON DUPLICATE KEY UPDATE nombre = nombre;

-- Obtener el ID del menú recién creado
SET @menu_id = LAST_INSERT_ID();

-- Crear permisos para el módulo
INSERT INTO permissions (name, description) VALUES
    ('employee_manual_concepts.read', 'Ver conceptos manuales de empleados'),
    ('employee_manual_concepts.create', 'Crear conceptos manuales de empleados'),
    ('employee_manual_concepts.update', 'Editar conceptos manuales de empleados'),
    ('employee_manual_concepts.delete', 'Eliminar conceptos manuales de empleados'),
    ('employee_manual_concepts.cancel', 'Cancelar conceptos manuales de empleados')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Asignar permisos al rol de Administrador (role_id = 1)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions
WHERE name LIKE 'employee_manual_concepts.%'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Asignar permisos al rol de Recursos Humanos (role_id = 2, si existe)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions
WHERE name LIKE 'employee_manual_concepts.%' AND EXISTS (SELECT 1 FROM roles WHERE id = 2)
ON DUPLICATE KEY UPDATE role_id = role_id;
*/

-- =====================================================
-- Datos de ejemplo (opcional - comentado por defecto)
-- =====================================================

/*
-- Ejemplo 1: Descuento por ausencia injustificada (1 día, una vez)
INSERT INTO employee_manual_concepts
(employee_id, concepto_id, unidad, fecha_inicio, aplica_una_vez, observaciones, created_by)
VALUES
('EMP001', (SELECT id FROM concepto WHERE concepto = 'AUSENCIAS' LIMIT 1),
 1.00, '2026-01-15', 1, 'Ausencia injustificada el 15/01/2026', 1);

-- Ejemplo 2: Descuento por tardanzas acumuladas (2 horas, una vez)
INSERT INTO employee_manual_concepts
(employee_id, concepto_id, unidad, fecha_inicio, fecha_fin, aplica_una_vez, observaciones, created_by)
VALUES
('EMP001', (SELECT id FROM concepto WHERE concepto = 'TARDANZAS' LIMIT 1),
 2.00, '2026-01-01', '2026-01-31', 1, 'Tardanzas acumuladas del mes de enero', 1);

-- Ejemplo 3: Bono por desempeño (monto fijo, recurrente por 3 meses)
INSERT INTO employee_manual_concepts
(employee_id, concepto_id, monto_fijo, fecha_inicio, fecha_fin, observaciones, created_by)
VALUES
('EMP002', (SELECT id FROM concepto WHERE tipo_concepto = 'I' LIMIT 1),
 200.00, '2026-01-01', '2026-03-31', 'Bono trimestral por desempeño excepcional', 1);
*/

-- =====================================================
-- Fin de migración
-- =====================================================
