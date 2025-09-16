-- ==============================================
-- CORRECCIÓN ENUM CAMPO ESTADO EN PLANILLA_CABECERA
-- Fecha: 15 Septiembre 2025
-- Descripción: Corrige el ENUM del campo estado para incluir todos los valores necesarios
-- ==============================================

-- Corregir el ENUM del campo estado en planilla_cabecera
-- Esto soluciona el error al crear planillas cuando el estado no está en los valores permitidos
ALTER TABLE `planilla_cabecera` 
CHANGE `estado` `estado` ENUM('PENDIENTE','PROCESADA','CERRADA') 
CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci 
NULL DEFAULT 'PENDIENTE';

-- Verificar que todos los registros existentes tengan un estado válido
-- Si hay registros con estados no válidos, los actualizamos a PENDIENTE
UPDATE `planilla_cabecera` 
SET `estado` = 'PENDIENTE' 
WHERE `estado` NOT IN ('PENDIENTE', 'PROCESADA', 'CERRADA');

-- ==============================================
-- COMENTARIOS Y DOCUMENTACIÓN
-- ==============================================

/*
DOCUMENTACIÓN DE LA CORRECCIÓN:

1. PROBLEMA IDENTIFICADO:
   - El ENUM del campo estado en planilla_cabecera no incluía todos los valores necesarios
   - Esto causaba errores al crear nuevas planillas
   - Error reportado: "Error al crear la planilla"

2. SOLUCIÓN IMPLEMENTADA:
   - Se actualiza el ENUM para incluir los tres estados válidos: PENDIENTE, PROCESADA, CERRADA
   - Se mantiene PENDIENTE como valor por defecto
   - Se actualiza cualquier registro con estado inválido a PENDIENTE

3. ESTADOS DEL SISTEMA:
   - PENDIENTE: Planilla creada pero no procesada
   - PROCESADA: Planilla procesada con empleados y conceptos calculados
   - CERRADA: Planilla finalizada, genera acumulados y no permite modificaciones

4. COMPATIBILIDAD:
   - Esta migración es compatible con versiones anteriores
   - No afecta datos existentes válidos
   - Corrige datos inconsistentes automáticamente
*/