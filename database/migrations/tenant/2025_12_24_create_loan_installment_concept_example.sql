-- Migración: Crear concepto ejemplo para cuotas de préstamos
-- Fecha: 24-Dic-2025
-- Descripción: Concepto de deducción que usa la función CUOTASPRESTAMOS() para descontar cuotas

-- Nota: Este es un concepto de EJEMPLO
-- Debes personalizarlo según tus necesidades:
-- 1. Cambiar el ID del acreedor en la fórmula (actualmente 1)
-- 2. Ajustar tipos de planilla, frecuencias y situaciones según tu configuración
-- 3. Verificar que la cuenta contable sea la correcta

-- Insertar concepto ejemplo para cuotas de préstamos
-- Formula: CUOTASPRESTAMOS(FICHA, 1)
--   - FICHA: Identificador del empleado
--   - 1: ID del acreedor (ejemplo: puede ser un banco, cooperativa, etc.)
INSERT INTO concepto (
    concepto,
    descripcion,
    cuenta_contable,
    tipo_concepto,
    unidad,
    formula,
    valor_fijo,
    imprime_detalles,
    prorratea,
    modifica_valor,
    valor_referencia,
    monto_calculo,
    monto_cero,
    incluir_reporte,
    categoria_reporte,
    orden_reporte
) VALUES (
    'CUOTA_PREST_001',                          -- Código del concepto (personalizable)
    'Cuota Préstamo Banco XYZ',                 -- Descripción (personalizable)
    '2110-001',                                 -- Cuenta contable (personalizable)
    'D',                                        -- Tipo: Deducción
    'DINERO',                                   -- Unidad
    'CUOTASPRESTAMOS(FICHA, 1)',                -- Fórmula (cambiar el 1 por el ID de tu acreedor)
    NULL,                                       -- Sin valor fijo
    1,                                          -- Imprimir en detalles de planilla
    0,                                          -- No prorratear
    0,                                          -- No permite modificar valor
    0,                                          -- No usa valor de referencia
    1,                                          -- Usa monto de cálculo (fórmula)
    0,                                          -- No incluir si el monto es 0
    1,                                          -- Incluir en reporte
    'otras_deducciones',                        -- Categoría: otras deducciones
    100                                         -- Orden en el reporte
);

-- Obtener el ID del concepto recién creado
SET @concepto_id = LAST_INSERT_ID();

-- PASO IMPORTANTE: Configurar tipos de planilla para el concepto
-- Nota: Ajusta estos IDs según tus tipos de planilla
-- Por ejemplo: 1 = Planilla Regular, 2 = Planilla Quincenal, etc.

INSERT INTO concepto_tipos_planilla (concepto_id, tipo_planilla_id) VALUES
(@concepto_id, 1),  -- Tipo planilla 1 (ejemplo: Regular)
(@concepto_id, 2);  -- Tipo planilla 2 (ejemplo: Quincenal)

-- PASO IMPORTANTE: Configurar frecuencias para el concepto
-- Nota: Ajusta estos IDs según tus frecuencias
-- Por ejemplo: 1 = Mensual, 2 = Quincenal, 3 = Semanal, etc.

INSERT INTO concepto_frecuencias (concepto_id, frecuencia_id) VALUES
(@concepto_id, 1),  -- Frecuencia 1 (ejemplo: Mensual)
(@concepto_id, 2);  -- Frecuencia 2 (ejemplo: Quincenal)

-- PASO IMPORTANTE: Configurar situaciones para el concepto
-- Nota: Ajusta estos IDs según tus situaciones de empleados
-- Por ejemplo: 1 = Activo, 2 = En Licencia, etc.

INSERT INTO concepto_situaciones (concepto_id, situacion_id) VALUES
(@concepto_id, 1);  -- Situación 1 (ejemplo: Activo)

-- Mensaje de confirmación
-- NOTA: SELECT comentado para evitar error PDO "pending result sets"
-- SELECT CONCAT('Concepto creado exitosamente con ID: ', @concepto_id,
              -- '. IMPORTANTE: Verifica y ajusta el ID del acreedor en la fórmula') AS mensaje;

-- ============================================================================
-- INSTRUCCIONES PARA PERSONALIZAR:
-- ============================================================================
--
-- 1. IDENTIFICAR TU ACREEDOR:
--    Ejecuta: SELECT id, name FROM creditors ORDER BY id;
--    Anota el ID del acreedor que necesitas
--
-- 2. ACTUALIZAR LA FÓRMULA:
--    UPDATE concepto
--    SET formula = 'CUOTASPRESTAMOS(FICHA, TU_ACREEDOR_ID)'
--    WHERE id = @concepto_id;
--
-- 3. VERIFICAR TIPOS DE PLANILLA:
--    Ejecuta: SELECT id, descripcion FROM tipos_planilla WHERE activo = 1;
--    Ajusta los INSERT INTO concepto_tipos_planilla según tus necesidades
--
-- 4. VERIFICAR FRECUENCIAS:
--    Ejecuta: SELECT id, nombre FROM frecuencias ORDER BY id;
--    Ajusta los INSERT INTO concepto_frecuencias según tus necesidades
--
-- 5. VERIFICAR SITUACIONES:
--    Ejecuta: SELECT id, nombre FROM situaciones WHERE activo = 1;
--    Ajusta los INSERT INTO concepto_situaciones según tus necesidades
--
-- 6. CREAR CONCEPTOS ADICIONALES:
--    Puedes crear un concepto diferente para cada acreedor
--    Ejemplo: CUOTA_PREST_BANCO_A, CUOTA_PREST_BANCO_B, etc.
--
-- ============================================================================
-- EJEMPLO DE USO:
-- ============================================================================
--
-- Si tienes un acreedor con ID 5 (ejemplo: Banco Nacional)
-- La fórmula sería: CUOTASPRESTAMOS(FICHA, 5)
--
-- Esta fórmula buscará automáticamente la próxima cuota pendiente
-- del préstamo activo del empleado con ese acreedor.
--
-- Cuando se cierre la planilla, la cuota se marcará como pagada.
-- Cuando se reabra la planilla, la cuota volverá a pendiente.
-- Si se anula el préstamo, la cuota se marcará como anulada.
--
-- ============================================================================
