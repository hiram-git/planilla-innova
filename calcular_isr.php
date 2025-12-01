<?php
/**
 * 🧪 CALCULADORA ISR - Cálculo Detallado con Parámetro
 *
 * Uso: php calcular_isr.php [SALARIO_MENSUAL]
 * Ejemplo: php calcular_isr.php 3000
 *
 * Fecha: 29-Nov-2025
 */

require_once __DIR__ . '/vendor/autoload.php';

use NXP\MathExecutor;

// Verificar argumentos
if ($argc < 2) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "❌ ERROR: Falta el parámetro de salario\n";
    echo str_repeat("=", 80) . "\n\n";
    echo "📖 USO:\n";
    echo "   php calcular_isr.php [SALARIO_MENSUAL]\n\n";
    echo "💡 EJEMPLOS:\n";
    echo "   php calcular_isr.php 1200\n";
    echo "   php calcular_isr.php 3500\n";
    echo "   php calcular_isr.php 6000\n\n";
    echo str_repeat("=", 80) . "\n\n";
    exit(1);
}

// Obtener y validar el salario
$salario_input = $argv[1];

// Validar que sea un número válido
if (!is_numeric($salario_input) || $salario_input <= 0) {
    echo "\n❌ ERROR: El salario debe ser un número positivo válido.\n";
    echo "   Valor recibido: '$salario_input'\n\n";
    exit(1);
}

$SALARIO = (float)$salario_input;

// Iniciar cálculo
echo "\n" . str_repeat("=", 100) . "\n";
echo "💰 CALCULADORA ISR PANAMÁ - CÁLCULO DETALLADO LÍNEA POR LÍNEA\n";
echo "💵 Salario Mensual: $" . number_format($SALARIO, 2) . "\n";
echo str_repeat("=", 100) . "\n\n";

// Crear ejecutor de fórmulas
$executor = new MathExecutor();

// Agregar función SI
$executor->addFunction('SI', function ($condicion, $valorSiVerdadero, $valorSiFalso) {
    return $condicion ? $valorSiVerdadero : $valorSiFalso;
}, 3);

// Establecer variables iniciales
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
    echo sprintf("Línea %2d: %s = %s\n", $lineNumber, $variable, $formula);

    // Evaluar la fórmula
    try {
        $resultado = $executor->execute($formula);
        $variables[$variable] = $resultado;

        // Establecer la variable para las siguientes evaluaciones
        $executor->setVar($variable, $resultado);

        // Mostrar el resultado
        echo sprintf("          Resultado: %s\n", number_format($resultado, 2));

        // Explicación adicional para líneas importantes
        if ($variable === 'salario_anual') {
            echo sprintf("          📌 \$%s × 13 meses = \$%s\n",
                number_format($SALARIO, 2),
                number_format($resultado, 2));
        } elseif ($variable === 'saldo_gravable') {
            if ($resultado < 0) {
                echo "          ⚠️  Base gravable negativa: No se paga ISR\n";
            } else {
                echo sprintf("          📌 \$%s - \$11,000 = \$%s\n",
                    number_format($variables['neto_gravable'], 2),
                    number_format($resultado, 2));
            }
        } elseif ($variable === 'isr_anual') {
            echo sprintf("          📌 \$%s × 0.15 = \$%s\n",
                number_format(max(0, $variables['saldo_gravable']), 2),
                number_format($resultado, 2));
        } elseif ($variable === 'isr_mensual') {
            echo sprintf("          📌 \$%s ÷ 13 = \$%s\n",
                number_format($variables['isr_anual'], 2),
                number_format($resultado, 2));
        } elseif ($variable === 'isr_quincenal') {
            echo sprintf("          📌 \$%s ÷ 2 = \$%s (ISR base sin excedente)\n",
                number_format($variables['isr_mensual'], 2),
                number_format($resultado, 2));
        } elseif ($variable === 'saldo_excedente') {
            if ($resultado > 0) {
                echo "          ⚠️  SALARIO EXCEDE \$50,000: Se aplica tarifa adicional\n";
                echo sprintf("          📌 \$%s - \$50,000 = \$%s\n",
                    number_format($variables['salario_anual'], 2),
                    number_format($resultado, 2));
            } else {
                echo "          ✅ Salario NO excede \$50,000: No hay tarifa adicional\n";
            }
        } elseif ($variable === 'excendente_gravable') {
            if ($resultado > 0) {
                echo sprintf("          📌 \$%s × 0.25 = \$%s\n",
                    number_format($variables['saldo_excedente'], 2),
                    number_format($resultado, 2));
            }
        } elseif ($variable === 'exceso_adicional') {
            if ($resultado > 0) {
                echo sprintf("          📌 \$%s + \$5,850 = \$%s\n",
                    number_format($variables['excendente_gravable'], 2),
                    number_format($resultado, 2));
            }
        } elseif ($variable === 'exceso_anual') {
            if ($resultado > 0) {
                echo sprintf("          📌 \$%s ÷ 13 = \$%s\n",
                    number_format($variables['exceso_adicional'], 2),
                    number_format($resultado, 2));
            }
        } elseif ($variable === 'exceso_quincenal') {
            if ($resultado > 0) {
                echo sprintf("          📌 \$%s ÷ 2 = \$%s\n",
                    number_format($variables['exceso_anual'], 2),
                    number_format($resultado, 2));
            }
        } elseif ($variable === 'monto') {
            echo "\n";
            echo "          " . str_repeat("⭐", 35) . "\n";
            if ($variables['saldo_excedente'] > 0) {
                echo "          🎯 MONTO FINAL: exceso_quincenal (hay excedente)\n";
            } else {
                echo "          🎯 MONTO FINAL: isr_quincenal (sin excedente)\n";
            }
            echo sprintf("          💵 ISR A RETENER: \$%s\n", number_format($resultado, 2));
            echo "          " . str_repeat("⭐", 35) . "\n";
        }

    } catch (\Exception $e) {
        echo "          ❌ ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";
    $lineNumber++;
}

// Resumen final
echo "\n" . str_repeat("=", 100) . "\n";
echo "📊 RESUMEN DEL CÁLCULO\n";
echo str_repeat("=", 100) . "\n\n";

echo "💰 INGRESOS:\n";
echo sprintf("   %-45s %25s\n", "Salario mensual:", "$" . number_format($SALARIO, 2));
echo sprintf("   %-45s %25s\n", "Salario anual (13 meses):", "$" . number_format($variables['salario_anual'], 2));
echo "\n";

echo "📉 CÁLCULO BASE:\n";
echo sprintf("   %-45s %25s\n", "Deducción estándar:", "$11,000.00");
echo sprintf("   %-45s %25s\n", "Base gravable:", "$" . number_format(max(0, $variables['saldo_gravable']), 2));
echo sprintf("   %-45s %25s\n", "ISR anual (15%):", "$" . number_format($variables['isr_anual'], 2));
echo sprintf("   %-45s %25s\n", "ISR mensual base:", "$" . number_format($variables['isr_mensual'], 2));
echo sprintf("   %-45s %25s\n", "ISR quincenal base:", "$" . number_format($variables['isr_quincenal'], 2));
echo "\n";

if ($variables['saldo_excedente'] > 0) {
    echo "⚠️  TARIFA ADICIONAL (Salarios > \$50,000):\n";
    echo sprintf("   %-45s %25s\n", "Excedente sobre \$50,000:", "$" . number_format($variables['saldo_excedente'], 2));
    echo sprintf("   %-45s %25s\n", "Impuesto sobre excedente (25%):", "$" . number_format($variables['excendente_gravable'], 2));
    echo sprintf("   %-45s %25s\n", "Base imponible adicional:", "$" . number_format($variables['exceso_adicional'], 2));
    echo sprintf("   %-45s %25s\n", "ISR quincenal adicional:", "$" . number_format($variables['exceso_quincenal'], 2));
    echo "\n";
} else {
    echo "✅ SIN TARIFA ADICIONAL (Salario ≤ \$50,000)\n\n";
}

echo "✅ RETENCIÓN QUINCENAL:\n";
echo sprintf("   %-45s %25s\n", "ISR a retener:", "$" . number_format($variables['monto'], 2));
echo "\n";

echo "💵 SALARIO NETO QUINCENAL:\n";
echo sprintf("   %-45s %25s\n", "Salario bruto:", "$" . number_format($SALARIO / 2, 2));
echo sprintf("   %-45s %25s\n", "ISR:", "-$" . number_format($variables['monto'], 2));
echo sprintf("   %-45s %25s\n", "Salario neto:", "$" . number_format(($SALARIO / 2) - $variables['monto'], 2));
echo "\n";

// Cálculos adicionales
echo "📊 PROYECCIONES:\n";
echo sprintf("   %-45s %25s\n", "ISR mensual estimado:", "$" . number_format($variables['monto'] * 2, 2));
echo sprintf("   %-45s %25s\n", "ISR anual estimado:", "$" . number_format($variables['monto'] * 26, 2));
$porcentaje_retencion = (($variables['monto'] * 2) / $SALARIO) * 100;
echo sprintf("   %-45s %24s%%\n", "% Retención mensual:", number_format($porcentaje_retencion, 2));
echo "\n";

echo str_repeat("=", 100) . "\n";
echo "✅ CÁLCULO COMPLETADO\n";
echo str_repeat("=", 100) . "\n\n";
