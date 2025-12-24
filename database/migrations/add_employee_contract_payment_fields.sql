-- Migration: Agregar campos contrato, forma de pago y datos bancarios
-- Fecha: 2025-09-20
-- Descripción: Agregar campos para gestión contratos, forma de pago y datos bancarios


-- Agregar campos para gestión de contratos
ALTER TABLE employees
ADD COLUMN tipo_contrato ENUM('INDEFINIDO', 'DEFINIDO', 'PROYECTO', 'TEMPORAL') DEFAULT 'INDEFINIDO' AFTER gastos_representacion,
ADD COLUMN fecha_inicio_contrato DATE NULL AFTER tipo_contrato,
ADD COLUMN fecha_vencimiento_contrato DATE NULL AFTER fecha_inicio_contrato,
ADD COLUMN numero_contrato VARCHAR(50) NULL AFTER fecha_vencimiento_contrato;

-- Agregar campos para forma de pago
ALTER TABLE employees
ADD COLUMN forma_pago ENUM('EFECTIVO', 'CHEQUE', 'ACH') DEFAULT 'EFECTIVO' AFTER numero_contrato,
ADD COLUMN banco VARCHAR(100) NULL AFTER forma_pago,
ADD COLUMN numero_cuenta VARCHAR(50) NULL AFTER banco,
ADD COLUMN tipo_cuenta ENUM('AHORROS', 'CORRIENTE') NULL AFTER numero_cuenta;

-- Agregar comentarios para documentación
ALTER TABLE employees
MODIFY COLUMN tipo_contrato ENUM('INDEFINIDO', 'DEFINIDO', 'PROYECTO', 'TEMPORAL') DEFAULT 'INDEFINIDO'
COMMENT 'Tipo de contrato laboral del empleado',

MODIFY COLUMN fecha_inicio_contrato DATE NULL
COMMENT 'Fecha de inicio del contrato actual',

MODIFY COLUMN fecha_vencimiento_contrato DATE NULL
COMMENT 'Fecha de vencimiento del contrato (solo para contratos definidos)',

MODIFY COLUMN numero_contrato VARCHAR(50) NULL
COMMENT 'Número identificador del contrato',

MODIFY COLUMN forma_pago ENUM('EFECTIVO', 'CHEQUE', 'ACH') DEFAULT 'EFECTIVO'
COMMENT 'Forma de pago del salario',

MODIFY COLUMN banco VARCHAR(100) NULL
COMMENT 'Nombre del banco (requerido para CHEQUE y ACH)',

MODIFY COLUMN numero_cuenta VARCHAR(50) NULL
COMMENT 'Número de cuenta bancaria (requerido para CHEQUE y ACH)',

MODIFY COLUMN tipo_cuenta ENUM('AHORROS', 'CORRIENTE') NULL
COMMENT 'Tipo de cuenta bancaria';

-- Crear índices para mejor rendimiento
CREATE INDEX IF NOT EXISTS idx_employees_tipo_contrato ON employees(tipo_contrato);
CREATE INDEX IF NOT EXISTS idx_employees_forma_pago ON employees(forma_pago);
CREATE INDEX IF NOT EXISTS idx_employees_banco ON employees(banco);
CREATE INDEX IF NOT EXISTS idx_employees_fecha_vencimiento ON employees(fecha_vencimiento_contrato);