-- Migración: Actualizar sistema de estados de préstamos y cuotas
-- Fecha: 24-Dic-2025
-- Descripción: Agregar columna status a loans y actualizar estados de loan_installments

-- 1. Agregar columna status a tabla loans
ALTER TABLE `loans`
ADD COLUMN `status` ENUM('activo', 'completado', 'anulado') NOT NULL DEFAULT 'activo'
AFTER `start_date`;

-- 2. Actualizar estados de cuotas: renombrar 'generada' -> 'pendiente' y agregar 'anulada'
ALTER TABLE `loan_installments`
MODIFY COLUMN `status` ENUM('pendiente', 'pagada', 'anulada') NOT NULL DEFAULT 'pendiente';

-- 3. Actualizar cuotas existentes con status 'generada' (si las hay) a 'pendiente'
-- Nota: MySQL automáticamente convierte 'generada' a 'pendiente' con el ALTER TABLE anterior

-- 4. Crear índice para optimizar consultas de cuotas pendientes
CREATE INDEX idx_status_loan ON loan_installments(status, loan_id);

-- 5. Crear índice para optimizar consultas de préstamos por empleado y acreedor
CREATE INDEX idx_employee_creditor_status ON loans(employee_id, creditor_id, status);

-- 6. Agregar columna para tracking de planilla (opcional, para auditoría)
ALTER TABLE `loan_installments`
ADD COLUMN `planilla_id` INT NULL
AFTER `status`,
ADD COLUMN `paid_date` DATE NULL
AFTER `planilla_id`;

-- 7. Foreign key para planilla_id (si existe la tabla planilla_cabecera)
ALTER TABLE `loan_installments`
ADD CONSTRAINT `fk_installment_planilla`
FOREIGN KEY (`planilla_id`) REFERENCES `planilla_cabecera`(`id`)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Comentarios sobre los nuevos estados:
-- loans.status:
--   - 'activo': Préstamo en curso, generando cuotas
--   - 'completado': Todas las cuotas pagadas
--   - 'anulado': Préstamo cancelado (cuotas también anuladas)

-- loan_installments.status:
--   - 'pendiente': Cuota generada, esperando pago (antes 'generada')
--   - 'pagada': Cuota procesada en planilla y marcada como pagada
--   - 'anulada': Cuota cancelada (por anulación de préstamo o corrección)
