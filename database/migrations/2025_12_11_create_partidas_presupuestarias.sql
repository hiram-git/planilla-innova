-- Migración: Crear tabla partidas_presupuestarias
-- Fecha: 2025-12-11
-- Descripción: Nueva tabla para gestionar partidas presupuestarias
--              Separada de cuentas contables (antes "partidas")
--              Se asignan a empleados y posiciones

CREATE TABLE IF NOT EXISTS partidas_presupuestarias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE COMMENT 'Código único de la partida presupuestaria (ej: 1.01.01.001)',
  nombre VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo de la partida',
  descripcion VARCHAR(500) NULL COMMENT 'Descripción detallada de la partida presupuestaria',
  activo TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Estado: 1=Activo, 0=Inactivo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_partidas_presupuestarias_codigo (codigo),
  INDEX idx_partidas_presupuestarias_activo (activo),
  INDEX idx_partidas_presupuestarias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Partidas presupuestarias para asignación a empleados y posiciones';

-- Insertar datos de ejemplo (opcional)
INSERT INTO partidas_presupuestarias (codigo, nombre, descripcion, activo) VALUES
  ('1.01.01.001', 'Remuneraciones Permanentes', 'Sueldos y salarios del personal permanente', 1),
  ('1.01.01.002', 'Remuneraciones Transitorias', 'Salarios del personal temporal y contratado', 1),
  ('1.01.02.001', 'Décimo Tercer Mes', 'Pago de décimo tercer mes (XIII mes)', 1),
  ('1.01.02.002', 'Vacaciones', 'Pago de vacaciones del personal', 1),
  ('1.01.03.001', 'Aportes Patronales CSS', 'Cuota patronal de la Caja de Seguro Social', 1),
  ('1.01.03.002', 'Seguro Educativo', 'Aporte patronal al Seguro Educativo', 1),
  ('1.01.04.001', 'Horas Extras', 'Pago de horas extras laboradas', 1),
  ('1.01.05.001', 'Prima de Antigüedad', 'Prima por años de servicio', 1)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
