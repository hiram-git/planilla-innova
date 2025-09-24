-- Migración: Agregar campos de logos a companies
-- Fecha: 2025-09-17
-- Descripción: Campos para almacenar logos de la empresa y reportes

-- Agregar campos de logos a companies
ALTER TABLE companies
ADD COLUMN logo_empresa VARCHAR(255) NULL
COMMENT 'Ruta del logo principal de la empresa',

ADD COLUMN logo_izquierdo_reportes VARCHAR(255) NULL
COMMENT 'Ruta del logo izquierdo para reportes PDF/Excel',

ADD COLUMN logo_derecho_reportes VARCHAR(255) NULL
COMMENT 'Ruta del logo derecho para reportes PDF/Excel';

-- Comentario actualizado en la tabla
ALTER TABLE companies
COMMENT = 'Tabla de configuración empresarial con logos para reportes';