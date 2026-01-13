/*
 * Migración: Corregir AUTO_INCREMENT de planilla_cabecera
 *
 * Fecha: 12-Enero-2026
 * Versión: 3.5.18
 *
 * Problema: La tabla planilla_cabecera tenía AUTO_INCREMENT = 100 en el script de instalación,
 *           causando que las planillas empiecen con IDs como 100, 126, etc. en lugar de 1.
 *
 * Solución:
 * - Si la tabla está vacía: Resetear AUTO_INCREMENT a 1
 * - Si tiene registros: Ajustar AUTO_INCREMENT al siguiente ID disponible (max_id + 1)
 *
 * IMPORTANTE: Esta migración es segura y no elimina datos.
 */

SET @max_id = (SELECT COALESCE(MAX(id), 0) FROM planilla_cabecera);
SET @next_id = @max_id + 1;

-- Si la tabla está vacía, resetear a 1
-- Si tiene datos, ajustar al siguiente ID disponible
SET @sql = CONCAT('ALTER TABLE planilla_cabecera AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificación
SELECT
    'planilla_cabecera' AS tabla,
    @max_id AS max_id_actual,
    @next_id AS nuevo_autoincrement,
    CASE
        WHEN @max_id = 0 THEN 'Tabla vacía - AUTO_INCREMENT reseteado a 1'
        ELSE CONCAT('Tabla con datos - AUTO_INCREMENT ajustado a ', @next_id)
    END AS resultado
FROM DUAL;
