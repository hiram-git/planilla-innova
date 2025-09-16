-- Script para agregar estado 'reabierta' a planillas
-- Ejecutar en la base de datos planilla_innova

USE planilla_innova;

-- Verificar estados actuales en la tabla
SELECT DISTINCT estado FROM planilla_cabecera;

-- Modificar la columna estado para incluir 'reabierta' si usa ENUM
-- Si la columna es VARCHAR, este ALTER no es necesario
ALTER TABLE planilla_cabecera 
MODIFY COLUMN estado ENUM('borrador', 'procesada', 'cerrada', 'reabierta') DEFAULT 'borrador';

-- Si la columna es VARCHAR en lugar de ENUM, usar:
-- ALTER TABLE planilla_cabecera 
-- MODIFY COLUMN estado VARCHAR(20) DEFAULT 'borrador';

-- Agregar columna para tracking de reaperturas (opcional pero recomendado)
ALTER TABLE planilla_cabecera 
ADD COLUMN fecha_reapertura TIMESTAMP NULL,
ADD COLUMN usuario_reapertura VARCHAR(100) NULL,
ADD COLUMN motivo_reapertura TEXT NULL;

-- Crear tabla de auditoría para cambios de estado de planillas
CREATE TABLE IF NOT EXISTS planilla_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planilla_id INT NOT NULL,
    estado_anterior VARCHAR(20) NOT NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    usuario VARCHAR(100) NOT NULL,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    motivo TEXT NULL,
    acumulados_afectados INT DEFAULT 0,
    FOREIGN KEY (planilla_id) REFERENCES planilla_cabecera(id) ON DELETE CASCADE,
    INDEX idx_planilla_fecha (planilla_id, fecha_cambio)
);

-- Mostrar estructura actualizada
DESCRIBE planilla_cabecera;

SELECT 'Estados de planilla actualizados exitosamente' as status;