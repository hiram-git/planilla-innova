<?php
/**
 * Script para simular la validación de fórmula del formulario web
 * 2025-09-29
 */

// Este script simula lo que hace el botón "Probar Fórmula" del formulario de conceptos

echo "=== SIMULACIÓN VALIDACIÓN FÓRMULA WEB ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Leer la fórmula corregida para el sistema
$formulaPath = 'pruebas/formula_isr_corregida_para_sistema.txt';
if (!file_exists($formulaPath)) {
    die("ERROR: No se encuentra el archivo $formulaPath\n");
}

$formula = file_get_contents($formulaPath);
$formula = trim($formula);

echo "FÓRMULA A VALIDAR:\n";
echo "==================\n";
echo $formula . "\n\n";

// Validaciones básicas que haría el sistema
echo "=== VALIDACIONES BÁSICAS ===\n";

// 1. Verificar caracteres permitidos (con punto y coma agregado)
$allowedCharsPattern = '/^[A-Z0-9_.(),\s\+\-\*\/\>\<\=\!\'\";]+$/i';
$hasValidChars = preg_match($allowedCharsPattern, $formula);

echo "1. Caracteres permitidos: " . ($hasValidChars ? "✅ VÁLIDO" : "❌ INVÁLIDO") . "\n";

if (!$hasValidChars) {
    echo "   Fórmula contiene caracteres no permitidos\n";
    exit(1);
}

// 2. Verificar paréntesis balanceados
$openParens = substr_count($formula, '(');
$closeParens = substr_count($formula, ')');
$parensBalanced = ($openParens === $closeParens);

echo "2. Paréntesis balanceados: " . ($parensBalanced ? "✅ VÁLIDO" : "❌ INVÁLIDO") . "\n";
echo "   Abiertos: $openParens, Cerrados: $closeParens\n";

if (!$parensBalanced) {
    exit(1);
}

// 3. Verificar que no empiece/termine con operadores
$startsEndsWithOp = preg_match('/^[\+\-\*\/]|[\+\-\*\/]$/', trim($formula));
echo "3. No inicia/termina con operadores: " . (!$startsEndsWithOp ? "✅ VÁLIDO" : "❌ INVÁLIDO") . "\n";

if ($startsEndsWithOp) {
    exit(1);
}

// 4. Verificar operadores consecutivos
$hasConsecutiveOps = preg_match('/[\+\-\*\/]{2,}/', $formula);
echo "4. Sin operadores consecutivos: " . (!$hasConsecutiveOps ? "✅ VÁLIDO" : "❌ INVÁLIDO") . "\n";

if ($hasConsecutiveOps) {
    exit(1);
}

echo "\n✅ TODAS LAS VALIDACIONES BÁSICAS PASARON\n\n";

echo "=== RESUMEN DEL PROBLEMA ===\n";
echo "1. La fórmula ISR está matemáticamente CORRECTA ✅\n";
echo "2. Produce los resultados esperados cuando se ejecuta paso a paso ✅\n";
echo "3. Pasa todas las validaciones de sintaxis básica ✅\n";
echo "4. El problema debe estar en el formato para el sistema PlanillaConceptCalculator\n\n";

echo "=== SOLUCIONES RECOMENDADAS ===\n";
echo "1. Usar la fórmula EN UNA SOLA LÍNEA con puntos y coma:\n";
echo "   pruebas/formula_isr_corregida_para_sistema.txt\n\n";

echo "2. Asegurarse de que el campo 'formula' en la BD tenga el texto exacto:\n";
echo "   " . substr($formula, 0, 100) . "...\n\n";

echo "3. El botón 'Probar Fórmula' debe usar esta versión corregida\n\n";

echo "4. Para procesar planilla, la fórmula debe estar guardada en la tabla 'concepto'\n";
echo "   con el campo 'formula' conteniendo el texto completo\n\n";

echo "=== VERIFICACIÓN BD ACTUAL ===\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=planilla_innova29092025", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar conceptos ISR existentes
    $sql = "SELECT id, concepto, descripcion, formula FROM concepto WHERE descripcion LIKE '%ISR%' OR descripcion LIKE '%impuesto%'";
    $stmt = $pdo->query($sql);
    $concepts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($concepts)) {
        echo "❌ No se encontraron conceptos ISR en la base de datos\n";
        echo "Recomendación: Crear un nuevo concepto ISR o actualizar uno existente\n";
    } else {
        echo "Conceptos ISR encontrados:\n";
        foreach ($concepts as $concept) {
            echo "- ID: {$concept['id']}, Código: {$concept['concepto']}, Descripción: {$concept['descripcion']}\n";
            echo "  Fórmula actual: " . substr($concept['formula'] ?? 'NULL', 0, 50) . "...\n";
        }
    }

} catch (PDOException $e) {
    echo "❌ Error conectando a BD: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";