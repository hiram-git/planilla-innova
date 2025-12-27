-- Agregar campo tipo_planilla_id a acumulados_por_empleado (si no existe)
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'acumulados_por_empleado'
      AND column_name = 'tipo_planilla_id'
);

SET @sql := IF(@col_exists = 0,
    'ALTER TABLE acumulados_por_empleado ADD COLUMN tipo_planilla_id INT NULL AFTER planilla_id',
    'SELECT \"tipo_planilla_id ya existe\"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear índice si no existe
SET @idx_exists := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'acumulados_por_empleado'
      AND index_name = 'idx_tipo_planilla_id'
);

SET @sql_idx := IF(@idx_exists = 0,
    'CREATE INDEX idx_tipo_planilla_id ON acumulados_por_empleado(tipo_planilla_id)',
    'SELECT \"idx_tipo_planilla_id ya existe\"'
);
PREPARE stmt2 FROM @sql_idx;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
