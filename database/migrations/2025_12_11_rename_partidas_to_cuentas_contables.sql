-- Migración: Renombrar tabla partidas a cuentas_contables
-- Fecha: 2025-12-11
-- Descripción: Cambio de nombre para reflejar mejor su propósito
--              Las "partidas" ahora se llaman "cuentas contables"
--              Se asignan a conceptos de planilla para clasificación contable

-- Renombrar tabla
RENAME TABLE partidas TO cuentas_contables;

-- Actualizar comentario de la tabla
ALTER TABLE cuentas_contables
  COMMENT='Cuentas contables para clasificación de conceptos de planilla';

-- Actualizar comentarios de columnas (opcional, para mejor documentación)
ALTER TABLE cuentas_contables
  MODIFY COLUMN codigo VARCHAR(20) NOT NULL UNIQUE COMMENT 'Código único de la cuenta contable (ej: 6.10.10.001)',
  MODIFY COLUMN nombre VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo de la cuenta contable',
  MODIFY COLUMN descripcion VARCHAR(500) NULL COMMENT 'Descripción detallada de la cuenta',
  MODIFY COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Estado: 1=Activo, 0=Inactivo';

-- Renombrar foreign keys en otras tablas que referencian "partidas"
-- NOTA: Verificar y actualizar en tablas dependientes según sea necesario

-- Renombrar índices para reflejar nuevo nombre (opcional)
-- MySQL maneja esto automáticamente al renombrar la tabla

-- Si existe columna "partida" en alguna tabla, renombrarla a "cuenta_contable"
-- ALTER TABLE nombre_tabla CHANGE partida cuenta_contable VARCHAR(255);
