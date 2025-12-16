-- Migración: Agregar campo partida_presupuestaria_id a employees y posiciones
-- Fecha: 2025-12-11
-- Descripción: Agrega referencia a partidas presupuestarias en empleados y posiciones
--              También renombra campos de partidas (ahora cuentas contables) por consistencia

-- ==================================
-- TABLA EMPLOYEES
-- ==================================

-- Agregar campo partida_presupuestaria_id a employees
ALTER TABLE employees
  ADD COLUMN partida_presupuestaria_id INT NULL AFTER partida_id;

-- Renombrar partida_id a cuenta_contable_id por consistencia (DEPRECATED - mantener por compatibilidad)
-- NOTA: No renombramos para mantener retrocompatibilidad con código existente
-- Si se decide renombrar en el futuro:
-- ALTER TABLE employees CHANGE partida_id cuenta_contable_id INT NULL;

-- Agregar índice y foreign key
ALTER TABLE employees
  ADD INDEX idx_employees_partida_presupuestaria (partida_presupuestaria_id);

ALTER TABLE employees
  ADD CONSTRAINT fk_employees_partida_presupuestaria
    FOREIGN KEY (partida_presupuestaria_id)
    REFERENCES partidas_presupuestarias(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- ==================================
-- TABLA POSICIONES
-- ==================================

-- Agregar campo partida_presupuestaria_id a posiciones
ALTER TABLE posiciones
  ADD COLUMN partida_presupuestaria_id INT NULL AFTER id_partida;

-- Renombrar id_partida a cuenta_contable_id por consistencia (DEPRECATED - mantener por compatibilidad)
-- NOTA: No renombramos para mantener retrocompatibilidad
-- Si se decide renombrar en el futuro:
-- ALTER TABLE posiciones CHANGE id_partida cuenta_contable_id INT NULL;

-- Agregar índice y foreign key
ALTER TABLE posiciones
  ADD INDEX idx_posiciones_partida_presupuestaria (partida_presupuestaria_id);

ALTER TABLE posiciones
  ADD CONSTRAINT fk_posiciones_partida_presupuestaria
    FOREIGN KEY (partida_presupuestaria_id)
    REFERENCES partidas_presupuestarias(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- ==================================
-- NOTAS DE IMPLEMENTACIÓN
-- ==================================
-- 1. Los campos partida_id e id_partida se mantienen por compatibilidad
-- 2. En el código, se usarán como "cuenta_contable_id" conceptualmente
-- 3. Los nuevos campos partida_presupuestaria_id son independientes
-- 4. Un empleado/posición puede tener AMBOS: cuenta contable + partida presupuestaria
