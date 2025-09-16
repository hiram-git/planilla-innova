-- =====================================================
-- Migración: Agregar Jerarquía Organizacional (Simple)
-- Fecha: Septiembre 2025
-- Descripción: Agregar estructura padre-hijo compatible
-- =====================================================

-- Agregar campos de jerarquía a la tabla funciones
ALTER TABLE `funciones` 
ADD COLUMN `parent_id` int DEFAULT NULL,
ADD COLUMN `nivel_jerarquico` int DEFAULT 1,
ADD COLUMN `orden_organizacional` int DEFAULT 0;

-- Agregar índices
ALTER TABLE `funciones` 
ADD INDEX `idx_parent_id` (`parent_id`),
ADD INDEX `idx_nivel_jerarquico` (`nivel_jerarquico`);

-- Agregar foreign key
ALTER TABLE `funciones` 
ADD CONSTRAINT `fk_funciones_parent` FOREIGN KEY (`parent_id`) REFERENCES `funciones` (`id`) ON DELETE SET NULL;

-- Agregar campos de jerarquía a la tabla cargos
ALTER TABLE `cargos` 
ADD COLUMN `parent_id` int DEFAULT NULL,
ADD COLUMN `nivel_jerarquico` int DEFAULT 1,
ADD COLUMN `orden_organizacional` int DEFAULT 0;

-- Agregar índices
ALTER TABLE `cargos` 
ADD INDEX `idx_cargos_parent` (`parent_id`),
ADD INDEX `idx_cargos_nivel` (`nivel_jerarquico`);

-- Agregar foreign key
ALTER TABLE `cargos` 
ADD CONSTRAINT `fk_cargos_parent` FOREIGN KEY (`parent_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL;

-- =====================================================
-- Actualizar datos existentes con jerarquía básica
-- =====================================================

-- Actualizar funciones existentes con valores por defecto
UPDATE `funciones` SET 
    `nivel_jerarquico` = 1,
    `orden_organizacional` = id;

-- Actualizar cargos existentes con valores por defecto
UPDATE `cargos` SET 
    `nivel_jerarquico` = 1,
    `orden_organizacional` = id;