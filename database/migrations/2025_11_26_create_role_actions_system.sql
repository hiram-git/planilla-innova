-- ============================================================================
-- Migración: Sistema de Permisos Granulares por Acción
-- Fecha: 2025-11-26
-- Descripción: Implementa permisos a nivel de acción (action-level permissions)
--              para operaciones específicas más allá de CRUD básico.
-- Tipo: TENANT (Aplicar a todas las bases de datos tenant)
-- ============================================================================

-- 1. Crear tabla role_actions para permisos granulares
CREATE TABLE IF NOT EXISTS role_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL COMMENT 'ID del rol',
    action_code VARCHAR(100) NOT NULL COMMENT 'Código único de la acción (ej: payroll.generate)',
    allowed TINYINT(1) DEFAULT 1 COMMENT 'Permiso concedido (1) o denegado (0)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    UNIQUE KEY unique_role_action (role_id, action_code),
    INDEX idx_role_id (role_id),
    INDEX idx_action_code (action_code),
    INDEX idx_allowed (allowed),

    -- Foreign Key
    CONSTRAINT fk_role_actions_role FOREIGN KEY (role_id)
        REFERENCES roles(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Permisos granulares por acción';

-- 2. Crear tabla action_definitions (catálogo de acciones del sistema)
CREATE TABLE IF NOT EXISTS action_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_code VARCHAR(100) NOT NULL UNIQUE COMMENT 'Código único (ej: payroll.generate)',
    module VARCHAR(50) NOT NULL COMMENT 'Módulo al que pertenece (payrolls, employees, etc)',
    name VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo de la acción',
    description VARCHAR(255) DEFAULT NULL COMMENT 'Descripción detallada',
    category ENUM('create', 'read', 'update', 'delete', 'execute', 'approve', 'export', 'import', 'other') DEFAULT 'other',
    is_system TINYINT(1) DEFAULT 0 COMMENT 'Acción del sistema (no editable)',
    status TINYINT(1) DEFAULT 1 COMMENT '1=activa, 0=inactiva',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    INDEX idx_module (module),
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Definiciones de acciones disponibles en el sistema';

-- 3. Insertar acciones del sistema para módulo Planillas
INSERT INTO action_definitions (action_code, module, name, description, category, is_system) VALUES
-- PLANILLAS REGULARES
('payroll.generate', 'payrolls', 'Generar Planilla Regular', 'Generar nueva planilla de pago ordinaria', 'execute', 1),
('payroll.regenerate', 'payrolls', 'Regenerar Planilla', 'Reprocesar/recalcular planilla existente', 'execute', 1),
('payroll.approve', 'payrolls', 'Aprobar Planilla', 'Aprobar planilla para pago', 'approve', 1),
('payroll.reverse', 'payrolls', 'Revertir Planilla', 'Anular/revertir planilla aprobada', 'delete', 1),
('payroll.export_pdf', 'payrolls', 'Exportar PDF Planilla', 'Generar reporte PDF de planilla', 'export', 1),
('payroll.export_excel', 'payrolls', 'Exportar Excel Planilla', 'Generar archivo Excel de planilla', 'export', 1),
('payroll.view_details', 'payrolls', 'Ver Detalles Planilla', 'Consultar detalles completos de planilla', 'read', 1),
('payroll.edit_concepts', 'payrolls', 'Editar Conceptos', 'Modificar conceptos de planilla existente', 'update', 1),

-- PLANILLAS DE VACACIONES
('payroll.vacation.generate', 'payrolls', 'Generar Planilla Vacaciones', 'Generar planilla de pago de vacaciones', 'execute', 1),
('payroll.vacation.approve', 'payrolls', 'Aprobar Planilla Vacaciones', 'Aprobar planilla de vacaciones para pago', 'approve', 1),
('payroll.vacation.export_pdf', 'payrolls', 'Exportar PDF Vacaciones', 'Generar reporte PDF planilla vacaciones', 'export', 1),
('payroll.vacation.reverse', 'payrolls', 'Revertir Planilla Vacaciones', 'Anular planilla de vacaciones', 'delete', 1),

-- LIQUIDACIONES
('payroll.liquidation.generate', 'payrolls', 'Generar Liquidación', 'Generar planilla de liquidación de prestaciones', 'execute', 1),
('payroll.liquidation.approve', 'payrolls', 'Aprobar Liquidación', 'Aprobar liquidación para pago final', 'approve', 1),
('payroll.liquidation.export_pdf', 'payrolls', 'Exportar PDF Liquidación', 'Generar comprobante PDF liquidación', 'export', 1),
('payroll.liquidation.reverse', 'payrolls', 'Revertir Liquidación', 'Anular liquidación generada', 'delete', 1),

-- XIII MES
('payroll.xiii.generate', 'payrolls', 'Generar XIII Mes', 'Generar planilla de décimo tercer mes', 'execute', 1),
('payroll.xiii.approve', 'payrolls', 'Aprobar XIII Mes', 'Aprobar planilla XIII mes para pago', 'approve', 1),
('payroll.xiii.export_pdf', 'payrolls', 'Exportar PDF XIII Mes', 'Generar reporte PDF XIII mes', 'export', 1),

-- OTRAS ACCIONES PLANILLAS
('payroll.view_salaries', 'payrolls', 'Ver Salarios Empleados', 'Consultar información salarial de empleados', 'read', 1),
('payroll.process_batch', 'payrolls', 'Procesar Lote', 'Procesar múltiples planillas simultáneamente', 'execute', 1),
('payroll.email_receipts', 'payrolls', 'Enviar Comprobantes Email', 'Enviar comprobantes de pago por correo', 'execute', 1),

-- EMPLEADOS
('employee.create', 'employees', 'Crear Empleado', 'Registrar nuevo empleado en el sistema', 'create', 1),
('employee.terminate', 'employees', 'Terminar Contrato', 'Finalizar relación laboral de empleado', 'update', 1),
('employee.edit_salary', 'employees', 'Editar Salario', 'Modificar información salarial de empleado', 'update', 1),
('employee.view_confidential', 'employees', 'Ver Datos Confidenciales', 'Acceder a información sensible del empleado', 'read', 1),
('employee.export_all', 'employees', 'Exportar Todos', 'Exportar lista completa de empleados', 'export', 1),
('employee.import_bulk', 'employees', 'Importar Masivo', 'Importar empleados desde archivo Excel', 'import', 1),

-- VACACIONES
('vacation.request.create', 'vacation', 'Crear Solicitud Vacaciones', 'Crear nueva solicitud de vacaciones', 'create', 1),
('vacation.request.approve', 'vacation', 'Aprobar Solicitud', 'Aprobar solicitud de vacaciones', 'approve', 1),
('vacation.request.reject', 'vacation', 'Rechazar Solicitud', 'Rechazar solicitud de vacaciones', 'approve', 1),
('vacation.balance.adjust', 'vacation', 'Ajustar Saldo', 'Ajustar manualmente saldo de vacaciones', 'update', 1),

-- ASISTENCIAS
('attendance.process_day', 'attendance', 'Procesar Día Asistencias', 'Procesar y calcular asistencias del día', 'execute', 1),
('attendance.approve_overtime', 'attendance', 'Aprobar Horas Extras', 'Aprobar horas extras trabajadas', 'approve', 1),
('attendance.justify_absence', 'attendance', 'Justificar Ausencia', 'Marcar ausencia como justificada', 'approve', 1),
('attendance.export_report', 'attendance', 'Exportar Reporte', 'Exportar reporte de asistencias', 'export', 1),

-- REPORTES
('report.view_executive', 'reports', 'Ver Reportes Ejecutivos', 'Acceder a reportes ejecutivos y gerenciales', 'read', 1),
('report.export_confidential', 'reports', 'Exportar Datos Confidenciales', 'Exportar reportes con información sensible', 'export', 1),

-- CONFIGURACIÓN
('config.edit_company', 'company', 'Editar Datos Empresa', 'Modificar información de la empresa', 'update', 1),
('config.manage_concepts', 'concepts', 'Gestionar Conceptos', 'Crear/editar conceptos de planilla', 'update', 1),
('config.manage_roles', 'roles', 'Gestionar Roles', 'Administrar roles y permisos del sistema', 'update', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    category = VALUES(category);

-- 4. Asignar TODAS las acciones al rol Super Admin (ID = 1)
INSERT INTO role_actions (role_id, action_code, allowed)
-- NOTA: SELECT comentado para evitar error PDO "pending result sets"
-- SELECT 1, action_code, 1
-- FROM action_definitions
-- WHERE status = 1
-- ON DUPLICATE KEY UPDATE allowed = 1;

-- 5. Vista útil: permisos de acción por rol
CREATE OR REPLACE VIEW v_role_actions_summary AS
-- SELECT
    -- r.id AS role_id,
    -- r.name AS role_name,
    -- ad.module,
    -- ad.category,
    -- COUNT(*) AS total_actions,
    -- SUM(CASE WHEN ra.allowed = 1 THEN 1 ELSE 0 END) AS allowed_actions,
    -- SUM(CASE WHEN ra.allowed = 0 THEN 1 ELSE 0 END) AS denied_actions
-- FROM roles r
-- LEFT JOIN role_actions ra ON r.id = ra.role_id
-- LEFT JOIN action_definitions ad ON ra.action_code = ad.action_code
-- WHERE ad.status = 1
-- GROUP BY r.id, r.name, ad.module, ad.category
-- ORDER BY r.id, ad.module, ad.category;

-- 6. Vista útil: acciones disponibles por módulo
CREATE OR REPLACE VIEW v_actions_by_module AS
-- SELECT
    -- module,
    -- category,
    -- COUNT(*) AS total_actions,
    -- GROUP_CONCAT(name ORDER BY name SEPARATOR ', ') AS actions
-- FROM action_definitions
-- WHERE status = 1
-- GROUP BY module, category
-- ORDER BY module, category;

-- ============================================================================
-- Verificación
-- ============================================================================
-- SELECT * FROM action_definitions WHERE module = 'payrolls' ORDER BY action_code;
-- SELECT * FROM role_actions WHERE role_id = 1;
-- SELECT * FROM v_role_actions_summary WHERE role_id = 1;
-- SELECT * FROM v_actions_by_module;

-- ============================================================================
-- Notas de Implementación:
--
-- NOMENCLATURA DE ACCIONES:
-- - Formato: {module}.{submodule}.{action} o {module}.{action}
-- - Ejemplos:
--   * payroll.generate (acción general)
--   * payroll.vacation.generate (acción específica de submódulo)
--   * employee.edit_salary (acción específica)
--
-- CATEGORÍAS:
-- - create: Crear nuevos registros
-- - read: Consultar información (puede incluir datos sensibles)
-- - update: Modificar registros existentes
-- - delete: Eliminar/anular registros
-- - execute: Ejecutar procesos/operaciones
-- - approve: Aprobar/autorizar operaciones
-- - export: Exportar datos (PDF, Excel, etc)
-- - import: Importar datos masivos
-- - other: Otras acciones no clasificadas
--
-- USO EN CÓDIGO PHP:
-- - PermissionMiddleware::canDoAction('payroll.generate')
-- - PermissionMiddleware::canDoAction('payroll.vacation.approve')
-- - PermissionMiddleware::requireAction('employee.edit_salary')
--
-- JERARQUÍA DE PERMISOS:
-- 1. Super Admin (role_id = 1): TODAS las acciones permitidas
-- 2. Permisos de módulo (role_permissions): Acceso básico CRUD
-- 3. Permisos de acción (role_actions): Operaciones específicas granulares
--
-- EXTENSIBILIDAD:
-- - Agregar nuevas acciones: INSERT INTO action_definitions
-- - No requiere cambios de esquema
-- - Mantiene compatibilidad con sistema existente
-- ============================================================================
