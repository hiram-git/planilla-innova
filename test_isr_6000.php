<?php
/**
 * 🧪 PRUEBA ISR - Sueldo $6,000 - Cálculo Línea por Línea
 *
 * Objetivo: Mostrar el proceso completo de cálculo del ISR para un empleado
 * con salario de $6,000 mensuales (salario anual > $50,000)
 *
 * Fecha: 29-Nov-2025
 */

require_once __DIR__ . '/vendor/autoload.php';

use NXP\MathExecutor;

echo "\n" . str_repeat("=", 100) . "\n";
echo "🧪 PRUEBA ISR - CÁLCULO DETALLADO LÍNEA POR LÍNEA\n";
echo "💰 Salario: $6,000.00 mensuales\n";
echo str_repeat("=", 100) . "\n\n";

// Crear ejecutor de fórmulas
$executor = new MathExecutor();

// Agregar función SI
$executor->addFunction('SI', function ($condicion, $valorSiVerdadero, $valorSiFalso) {
    return $condicion ? $valorSiVerdadero : $valorSiFalso;
}, 3);

// Establecer variables iniciales
$SALARIO = 6000;
$GASTOS_REPRESENTACION = 0;

$executor->setVar('SALARIO', $SALARIO);
$executor->setVar('GASTOS_REPRESENTACION', $GASTOS_REPRESENTACION);

echo "📋 VARIABLES INICIALES\n";
echo str_repeat("-", 100) . "\n";
echo sprintf("%-30s = %s\n", "SALARIO", number_format($SALARIO, 2));
echo sprintf("%-30s = %s\n", "GASTOS_REPRESENTACION", number_format($GASTOS_REPRESENTACION, 2));
echo "\n";

// Definir las líneas de la fórmula
$formulas = [
    'salario_anual' => 'SALARIO*13',
    'gr_anual' => 'GASTOS_REPRESENTACION*13',
    'neto_gravable' => 'salario_anual',
    'saldo_gravable' => 'neto_gravable-11000',
    'isr_anual' => 'saldo_gravable * 0.15',
    'isr_mensual' => 'isr_anual/13',
    'isr_quincenal' => 'isr_mensual/2',
    'saldo_excedente' => 'SI(salario_anual>50000, salario_anual-50000, 0)',
    'excendente_gravable' => 'SI(saldo_excedente>0, saldo_excedente*0.25, 0)',
    'exceso_adicional' => 'SI(excendente_gravable>0, excendente_gravable+5850, 0)',
    'exceso_anual' => 'SI(exceso_adicional>0, exceso_adicional/13, 0)',
    'exceso_quincenal' => 'SI(exceso_anual>0, exceso_anual/2, 0)',
    'monto' => 'SI(saldo_excedente>0, exceso_quincenal, isr_quincenal)'
];

echo "🔢 EVALUACIÓN LÍNEA POR LÍNEA\n";
echo str_repeat("=", 100) . "\n\n";

$lineNumber = 1;
$variables = [];

foreach ($formulas as $variable => $formula) {
    echo sprintf("Línea %2d: %s\n", $lineNumber, str_pad($variable . " = " . $formula, 80));
    echo str_repeat("-", 100) . "\n";

    // Evaluar la fórmula
    try {
        $resultado = $executor->execute($formula);
        $variables[$variable] = $resultado;

        // Establecer la variable para las siguientes evaluaciones
        $executor->setVar($variable, $resultado);

        // Mostrar el resultado
        echo "   Resultado: " . number_format($resultado, 2) . "\n";

        // Explicación adicional para líneas importantes
        if ($variable === 'salario_anual') {
            echo "   📌 Salario anual: \$6,000 × 13 meses = \$" . number_format($resultado, 2) . "\n";
        } elseif ($variable === 'saldo_gravable') {
            echo "   📌 Base gravable después de deducción estándar: \$" . number_format($variables['neto_gravable'], 2) . " - \$11,000 = \$" . number_format($resultado, 2) . "\n";
        } elseif ($variable === 'isr_anual') {
            echo "   📌 ISR anual (15%): \$" . number_format($variables['saldo_gravable'], 2) . " × 0.15 = \$" . number_format($resultado, 2) . "\n";
        } elseif ($variable === 'isr_quincenal') {
            echo "   📌 ISR quincenal (base): \$" . number_format($variables['isr_mensual'], 2) . " ÷ 2 = \$" . number_format($resultado, 2) . "\n";
        } elseif ($variable === 'saldo_excedente') {
            if ($resultado > 0) {
                echo "   ⚠️  SALARIO EXCEDE \$50,000: Se aplica tarifa adicional\n";
                echo "   📌 Excedente: \$" . number_format($variables['salario_anual'], 2) . " - \$50,000 = \$" . number_format($resultado, 2) . "\n";
            } else {
                echo "   ✅ Salario NO excede \$50,000: No se aplica tarifa adicional\n";
            }
        } elseif ($variable === 'excendente_gravable') {
            if ($resultado > 0) {
                echo "   📌 Impuesto sobre excedente (25%): \$" . number_format($variables['saldo_excedente'], 2) . " × 0.25 = \$" . number_format($resultado, 2) . "\n";
            }
        } elseif ($variable === 'exceso_adicional') {
            if ($resultado > 0) {
                echo "   📌 Base imponible excedente: \$" . number_format($variables['excendente_gravable'], 2) . " + \$5,850 = \$" . number_format($resultado, 2) . "\n";
            }
        } elseif ($variable === 'exceso_anual') {
            if ($resultado > 0) {
                echo "   📌 ISR anual por excedente: \$" . number_format($variables['exceso_adicional'], 2) . " ÷ 13 = \$" . number_format($resultado, 2) . "\n";
            }
        } elseif ($variable === 'exceso_quincenal') {
            if ($resultado > 0) {
                echo "   📌 ISR quincenal por excedente: \$" . number_format($variables['exceso_anual'], 2) . " ÷ 2 = \$" . number_format($resultado, 2) . "\n";
            }
        } elseif ($variable === 'monto') {
            echo "\n";
            echo "   " . str_repeat("⭐", 40) . "\n";
            if ($variables['saldo_excedente'] > 0) {
                echo "   🎯 MONTO FINAL: Se usa exceso_quincenal porque hay excedente\n";
                echo "   💵 ISR a retener: \$" . number_format($resultado, 2) . "\n";
            } else {
                echo "   🎯 MONTO FINAL: Se usa isr_quincenal (sin excedente)\n";
                echo "   💵 ISR a retener: \$" . number_format($resultado, 2) . "\n";
            }
            echo "   " . str_repeat("⭐", 40) . "\n";
        }

    } catch (\Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";
    $lineNumber++;
}

// Resumen final
echo "\n" . str_repeat("=", 100) . "\n";
echo "📊 RESUMEN DEL CÁLCULO\n";
echo str_repeat("=", 100) . "\n\n";

echo "💰 INGRESOS:\n";
echo sprintf("   %-40s %20s\n", "Salario mensual:", "$" . number_format($SALARIO, 2));
echo sprintf("   %-40s %20s\n", "Salario anual (13 meses):", "$" . number_format($variables['salario_anual'], 2));
echo "\n";

echo "📉 DEDUCCIÓN ESTÁNDAR:\n";
echo sprintf("   %-40s %20s\n", "Monto exento:", "$11,000.00");
echo sprintf("   %-40s %20s\n", "Base gravable:", "$" . number_format($variables['saldo_gravable'], 2));
echo "\n";

echo "🧮 ISR BASE (15%):\n";
echo sprintf("   %-40s %20s\n", "ISR anual:", "$" . number_format($variables['isr_anual'], 2));
echo sprintf("   %-40s %20s\n", "ISR mensual:", "$" . number_format($variables['isr_mensual'], 2));
echo sprintf("   %-40s %20s\n", "ISR quincenal:", "$" . number_format($variables['isr_quincenal'], 2));
echo "\n";

if ($variables['saldo_excedente'] > 0) {
    echo "⚠️  TARIFA ADICIONAL (Salarios > \$50,000):\n";
    echo sprintf("   %-40s %20s\n", "Excedente sobre \$50,000:", "$" . number_format($variables['saldo_excedente'], 2));
    echo sprintf("   %-40s %20s\n", "Impuesto sobre excedente (25%):", "$" . number_format($variables['excendente_gravable'], 2));
    echo sprintf("   %-40s %20s\n", "Base imponible adicional:", "$" . number_format($variables['exceso_adicional'], 2));
    echo sprintf("   %-40s %20s\n", "ISR anual adicional:", "$" . number_format($variables['exceso_anual'], 2));
    echo sprintf("   %-40s %20s\n", "ISR quincenal adicional:", "$" . number_format($variables['exceso_quincenal'], 2));
    echo "\n";
}

echo "✅ RETENCIÓN FINAL:\n";
echo sprintf("   %-40s %20s\n", "ISR quincenal a retener:", "$" . number_format($variables['monto'], 2));
echo sprintf("   %-40s %20s\n", "ISR mensual estimado:", "$" . number_format($variables['monto'] * 2, 2));
echo sprintf("   %-40s %20s\n", "ISR anual estimado:", "$" . number_format($variables['monto'] * 26, 2));
echo "\n";

echo "💵 SALARIO NETO:\n";
echo sprintf("   %-40s %20s\n", "Salario quincenal bruto:", "$" . number_format($SALARIO / 2, 2));
echo sprintf("   %-40s %20s\n", "ISR quincenal:", "-$" . number_format($variables['monto'], 2));
echo sprintf("   %-40s %20s\n", "Salario quincenal neto:", "$" . number_format(($SALARIO / 2) - $variables['monto'], 2));
echo "\n";

echo str_repeat("=", 100) . "\n";
echo "✅ PRUEBA COMPLETADA\n";
echo str_repeat("=", 100) . "\n\n";
