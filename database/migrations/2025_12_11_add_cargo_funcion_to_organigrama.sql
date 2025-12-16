-- Migración: Agregar campos cargo_id y funcion_id a tabla organigrama
-- Fecha: 2025-12-11
-- Descripción: Permite asignar cargo y función a cada elemento del organigrama
--              Los empleados heredan estos valores del organigrama seleccionado

-- Agregar columnas cargo_id y funcion_id a organigrama
ALTER TABLE organigrama
  ADD COLUMN cargo_id INT NULL AFTER nivel,
  ADD COLUMN funcion_id INT NULL AFTER cargo_id;

-- Agregar índices para mejorar rendimiento
ALTER TABLE organigrama
  ADD INDEX idx_organigrama_cargo (cargo_id),
  ADD INDEX idx_organigrama_funcion (funcion_id);

-- Agregar foreign keys con restricción CASCADE (si se elimina cargo/función, se pone NULL)
ALTER TABLE organigrama
  ADD CONSTRAINT fk_organigrama_cargo
    FOREIGN KEY (cargo_id) REFERENCES cargos(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT fk_organigrama_funcion
    FOREIGN KEY (funcion_id) REFERENCES funciones(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- Comentarios en columnas
ALTER TABLE organigrama
  MODIFY COLUMN cargo_id INT NULL COMMENT 'Cargo asignado a este elemento del organigrama',
  MODIFY COLUMN funcion_id INT NULL COMMENT 'Función asignada a este elemento del organigrama';
