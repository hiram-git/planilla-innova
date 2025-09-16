-- ==============================================
-- AGREGAR CAMPOS DE FIRMAS PARA REPORTES DE PLANILLA
-- Fecha: 15 Septiembre 2025
-- Descripción: Agrega campos para jefe de RRHH y elaborador de planilla
-- ==============================================

-- Agregar campos para firmas en reportes de planilla
ALTER TABLE `companies` 
ADD COLUMN `jefe_recursos_humanos` VARCHAR(255) NULL COMMENT 'Nombre del jefe de recursos humanos para firmas en reportes',
ADD COLUMN `cargo_jefe_rrhh` VARCHAR(255) NULL DEFAULT 'Jefe de Recursos Humanos' COMMENT 'Cargo del jefe de RRHH',
ADD COLUMN `elaborado_por` VARCHAR(255) NULL COMMENT 'Nombre de quien elabora la planilla para firmas en reportes',
ADD COLUMN `cargo_elaborador` VARCHAR(255) NULL DEFAULT 'Especialista en Nóminas' COMMENT 'Cargo de quien elabora la planilla';

-- Actualizar registro existente con valores por defecto (si existe una empresa)
UPDATE `companies` 
SET 
    `jefe_recursos_humanos` = 'Por definir',
    `cargo_jefe_rrhh` = 'Jefe de Recursos Humanos',
    `elaborado_por` = 'Por definir',
    `cargo_elaborador` = 'Especialista en Nóminas'
WHERE `jefe_recursos_humanos` IS NULL;

-- ==============================================
-- COMENTARIOS Y DOCUMENTACIÓN
-- ==============================================

/*
DOCUMENTACIÓN DE LOS NUEVOS CAMPOS:

1. CAMPOS AGREGADOS:
   - jefe_recursos_humanos: Nombre completo del jefe de RRHH
   - cargo_jefe_rrhh: Título/cargo del jefe de RRHH (personalizable)
   - elaborado_por: Nombre completo de quien elabora la planilla
   - cargo_elaborador: Título/cargo de quien elabora (personalizable)

2. USO EN REPORTES:
   - Estos campos aparecerán en los reportes PDF de planillas
   - Se mostrarán en la sección de firmas al final del reporte
   - Permiten personalizar los nombres según la empresa

3. CONFIGURACIÓN:
   - Se pueden editar desde el panel de administración
   - Los cargos tienen valores por defecto pero son personalizables
   - Si no se configuran, muestran "Por definir"

4. ESTRUCTURA DE FIRMAS EN REPORTES:
   - Elaborado por: [elaborado_por] - [cargo_elaborador]
   - Revisado por: [jefe_recursos_humanos] - [cargo_jefe_rrhh]
*/