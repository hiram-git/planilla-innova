-- Migración: Agregar campo creditor_id a tabla concepto
-- Fecha: 29-Dic-2025
-- Descripción: Relación directa entre concepto de préstamos y acreedores

-- 1. Agregar columna creditor_id a tabla concepto
ALTER TABLE `concepto`
ADD COLUMN `creditor_id` INT NULL AFTER `formula`,
ADD KEY `idx_creditor_id` (`creditor_id`);

-- 2. Agregar foreign key (si existe tabla creditors)
ALTER TABLE `concepto`
ADD CONSTRAINT `fk_concepto_creditor`
FOREIGN KEY (`creditor_id`) REFERENCES `creditors`(`id`)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Comentarios:
-- creditor_id permite:
--   1. Evitar duplicación de concepto al crear préstamos
--   2. Búsqueda directa sin parsear fórmulas CUOTASPRESTAMOS()
--   3. Asignación precisa de cuotas en cierre/reapertura planillas
--   4. Mejor rendimiento en queries
