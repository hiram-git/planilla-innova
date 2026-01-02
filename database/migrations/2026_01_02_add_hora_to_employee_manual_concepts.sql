-- =====================================================
-- Migración: Agregar campo hora a employee_manual_concepts
-- Fecha: 2026-01-02
-- Versión: 3.5.18
-- Descripción: Campo opcional para registrar horas trabajadas,
--              tardanzas en horas, etc.
-- =====================================================

ALTER TABLE employee_manual_concepts
ADD COLUMN hora DECIMAL(10,2) NULL
COMMENT 'Horas (opcional, para registro de horas trabajadas, tardanzas, etc.)'
AFTER unidad;

-- =====================================================
-- Fin de migración
-- =====================================================
