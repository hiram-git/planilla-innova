<?php
/**
 * Script para probar la fórmula ISR usando el validador del sistema
 * 2025-09-29
 */

require_once 'app/Core/App.php';
require_once 'app/Models/Concept.php';
require_once 'app/Services/PlanillaConceptCalculator.php';

use App\Core\App;
use App\Models\Concept;
use App\Services\PlanillaConceptCalculator;

echo "=== PRUEBA DE FÓRMULA ISR - VALIDACIÓN SISTEMA ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Inicializar aplicación
$app = new App();

// Leer la fórmula desde el archivo
$formulaPath = 'pruebas/formula_isr_solo_texto_sin_punto_coma.txt';
if (!file_exists($formulaPath)) {
    die("ERROR: No se encuentra el archivo $formulaPath\n");
}

$formula = file_get_contents($formulaPath);
$formula = trim($formula);

echo "FÓRMULA A PROBAR:\n";
echo "================\n";
echo $formula . "\n\n";

// Verificar si termina con RETURN
if (!preg_match('/RETURN\s+\w+\s*$/i', $formula)) {
    echo "⚠️  ADVERTENCIA: La fórmula no termina con RETURN monto\n";
    echo "Agregando 'RETURN monto' al final...\n\n";
    $formula .= "\nRETURN monto";
}

try {
    // Crear instancia del modelo de conceptos
    $conceptModel = new Concept();

    echo "=== VALIDACIÓN USANDO MODEL CONCEPT ===\n";

    // Probar la validación básica de sintaxis
    $reflection = new ReflectionClass($conceptModel);
    $method = $reflection->getMethod('validateFormulaSyntax');
    $method->setAccessible(true);

    $syntaxResult = $method->invoke($conceptModel, $formula);
    echo "Validación de sintaxis: " . ($syntaxResult['valid'] ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "\n";
    echo "Mensaje: " . $syntaxResult['message'] . "\n\n";

    if (!$syntaxResult['valid']) {
        echo "ERROR: La fórmula no pasa la validación de sintaxis básica\n";
        exit(1);
    }

    // Probar la validación completa con empleado real
    echo "=== VALIDACIÓN COMPLETA CON EMPLEADO REAL ===\n";

    // Usar empleado EMP013 (ANTONIO J. JARAMILLO) que debe tener ISR
    $validationResult = $conceptModel->validateFormula($formula, 'EMP013');

    echo "Validación completa: " . ($validationResult['valid'] ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "\n";
    echo "Mensaje: " . $validationResult['message'] . "\n";

    if (isset($validationResult['test_result'])) {
        echo "Resultado de prueba: B/." . number_format($validationResult['test_result'], 2) . "\n";
    }

    if (isset($validationResult['variables_used'])) {
        echo "Variables utilizadas: " . implode(', ', $validationResult['variables_used']) . "\n";
    }

    echo "\n";

} catch (Exception $e) {
    echo "❌ ERROR EN VALIDACIÓN: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
}

// Probar directamente con PlanillaConceptCalculator
echo "=== PRUEBA DIRECTA CON CALCULATOR ===\n";

try {
    $calculator = new PlanillaConceptCalculator();

    // Configurar empleado EMP013 (ANTONIO J. JARAMILLO)
    $calculator->setVariablesColaborador('EMP013');

    // Mostrar variables del empleado
    echo "Variables del empleado EMP013:\n";
    $reflection = new ReflectionClass($calculator);
    $property = $reflection->getProperty('variables');
    $property->setAccessible(true);
    $variables = $property->getValue($calculator);

    echo "SALARIO: B/." . number_format($variables['SALARIO'] ?? 0, 2) . "\n";
    echo "GASTOS_REPRESENTACION: B/." . number_format($variables['GASTOS_REPRESENTACION'] ?? 0, 2) . "\n";
    echo "CLAVE_SS: " . ($variables['CLAVE_SS'] ?? 'N/A') . "\n\n";

    // Evaluar la fórmula
    echo "Evaluando fórmula...\n";
    $result = $calculator->evaluarFormula($formula);

    echo "Resultado ISR Quincenal: B/." . number_format($result, 2) . "\n\n";

    if ($result == 0) {
        echo "⚠️  PROBLEMA: La fórmula está retornando 0\n";
        echo "Vamos a debuggear paso a paso...\n\n";

        // Debuggear paso a paso
        $steps = [
            'SALARIO' => $variables['SALARIO'] ?? 0,
            'salario_anual' => ($variables['SALARIO'] ?? 0) * 13,
            'CLAVE_SS' => $variables['CLAVE_SS'] ?? '',
            'LEFT(CLAVE_SS, 1)' => substr($variables['CLAVE_SS'] ?? '', 0, 1),
            'deduc_pers' => substr($variables['CLAVE_SS'] ?? '', 0, 1) === 'E' ? 800 : 0,
        ];

        foreach ($steps as $step => $value) {
            echo "$step = $value\n";
        }

        $salario_anual = ($variables['SALARIO'] ?? 0) * 13;
        $deduc_pers = substr($variables['CLAVE_SS'] ?? '', 0, 1) === 'E' ? 800 : 0;
        $neto_gravable = $salario_anual - $deduc_pers;

        echo "\nCálculos intermedios:\n";
        echo "salario_anual = B/." . number_format($salario_anual, 2) . "\n";
        echo "deduc_pers = B/." . number_format($deduc_pers, 2) . "\n";
        echo "neto_gravable = B/." . number_format($neto_gravable, 2) . "\n";
        echo "¿neto_gravable > 11000? " . ($neto_gravable > 11000 ? 'SÍ' : 'NO') . "\n";

        if ($neto_gravable > 11000) {
            $saldo_gravable = $neto_gravable - 11000;
            $isr_anual = $saldo_gravable * 0.15;
            $isr_mensual = $isr_anual / 13;
            $isr_quincenal = $isr_mensual / 2;

            echo "saldo_gravable = B/." . number_format($saldo_gravable, 2) . "\n";
            echo "isr_anual = B/." . number_format($isr_anual, 2) . "\n";
            echo "isr_mensual = B/." . number_format($isr_mensual, 2) . "\n";
            echo "isr_quincenal = B/." . number_format($isr_quincenal, 2) . "\n";
        }
    }

} catch (Exception $e) {
    echo "❌ ERROR EN CALCULATOR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE PRUEBAS ===\n";