<?php
/**
 * Debug paso a paso del procesamiento de la fórmula
 */

echo "=== DEBUG PASO A PASO ===\n";

$formulaISR = "salario_anual = SALARIO*13
gr_anual = GASTOS_REPRESENTACION*13
deduc_pers = SI(LEFT(CLAVE_SS, 1) = \"E\", 800, 0)
neto_gravable = salario_anual - deduc_pers
saldo_gravable = SI(neto_gravable > 11000, neto_gravable - 11000, 0)
isr_anual = saldo_gravable * 0.15
isr_mensual = isr_anual/13
isr_quincenal = isr_mensual/2
saldo_excedente = SI(salario_anual > 50000, salario_anual - 50000, 0)
excedente_gravable = SI(saldo_excedente > 0, saldo_excedente * 0.25, 0)
exceso_adicional = SI(excedente_gravable > 0, excedente_gravable + 5850, 0)
exceso_anual = SI(exceso_adicional > 0, exceso_adicional/13, 0)
exceso_quincenal = SI(exceso_anual > 0, exceso_anual/2, 0)
monto = SI(exceso_quincenal > 0, exceso_quincenal, isr_quincenal)
RETURN monto";

// Variables de empleado
$variables = [
    'SALARIO' => 2525.00,
    'GASTOS_REPRESENTACION' => 2435.00,
    'CLAVE_SS' => 'E-02'
];

echo "Variables globales:\n";
foreach ($variables as $var => $val) {
    echo "  $var = $val\n";
}
echo "\n";

// Dividir en líneas
$formula = str_replace(["\r\n", "\r"], "\n", $formulaISR);
$lineas = explode("\n", $formula);
$lineas = array_filter(array_map('trim', $lineas), function($linea) {
    return !empty($linea);
});

echo "Líneas de la fórmula:\n";
foreach ($lineas as $i => $linea) {
    echo ($i + 1) . ". $linea\n";
}
echo "\n";

// Procesar línea por línea
$variablesLocales = [];

foreach ($lineas as $numeroLinea => $linea) {
    echo "=== LÍNEA " . ($numeroLinea + 1) . ": $linea ===\n";

    if ($linea === 'RETURN monto') {
        echo "RETURN detectado. Valor final: " . ($variablesLocales['monto'] ?? 'NO DEFINIDO') . "\n";
        break;
    }

    if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/', $linea, $matches)) {
        $nombreVariable = $matches[1];
        $expresion = trim($matches[2]);

        echo "Variable: $nombreVariable\n";
        echo "Expresión original: $expresion\n";

        // Simular procesamiento de LEFT()
        if (strpos($expresion, 'LEFT(') !== false) {
            echo "Detectada función LEFT()\n";
            $expresion = preg_replace_callback('/LEFT\(([^,]+),\s*(\d+)\)/', function($matches) use ($variables) {
                $texto = trim($matches[1]);
                $longitud = (int)trim($matches[2]);

                if (isset($variables[$texto])) {
                    $textoResuelto = $variables[$texto];
                } else {
                    $textoResuelto = $texto;
                }

                $textoResuelto = trim($textoResuelto, '"\'');
                $resultado = '"' . substr($textoResuelto, 0, $longitud) . '"';
                echo "  LEFT($texto, $longitud) -> LEFT($textoResuelto, $longitud) -> $resultado\n";
                return $resultado;
            }, $expresion);
        }

        // Simular procesamiento de SI()
        if (strpos($expresion, 'SI(') !== false) {
            echo "Detectada función SI()\n";
            // Para simplificar, vamos a evaluar manualmente la primera función SI()
            if ($linea === 'deduc_pers = SI(LEFT(CLAVE_SS, 1) = "E", 800, 0)') {
                // Después de procesar LEFT: deduc_pers = SI("E" = "E", 800, 0)
                echo "  Después de LEFT(): SI(\"E\" = \"E\", 800, 0)\n";
                echo "  Condición: \"E\" = \"E\" -> VERDADERO\n";
                echo "  Resultado: 800\n";
                $expresion = '800';
            } elseif (preg_match('/SI\(([^,]+)\s*>\s*([^,]+),([^,]+),([^)]+)\)/', $expresion, $siMatches)) {
                $valor1 = trim($siMatches[1]);
                $valor2 = trim($siMatches[2]);
                $verdadero = trim($siMatches[3]);
                $falso = trim($siMatches[4]);

                // Reemplazar variables
                foreach (array_merge($variables, $variablesLocales) as $var => $val) {
                    $valor1 = str_replace($var, $val, $valor1);
                    $valor2 = str_replace($var, $val, $valor2);
                }

                $condicion = (float)$valor1 > (float)$valor2;
                $resultado = $condicion ? $verdadero : $falso;

                echo "  Condición: $valor1 > $valor2 -> " . ($condicion ? 'VERDADERO' : 'FALSO') . "\n";
                echo "  Resultado: $resultado\n";
                $expresion = $resultado;
            }
        }

        // Reemplazar variables globales
        foreach ($variables as $var => $val) {
            $expresion = str_replace($var, $val, $expresion);
        }

        // Reemplazar variables locales
        foreach ($variablesLocales as $var => $val) {
            $expresion = str_replace($var, $val, $expresion);
        }

        echo "Expresión procesada: $expresion\n";

        // Evaluar expresión matemática
        try {
            $resultado = eval("return $expresion;");
            $variablesLocales[$nombreVariable] = $resultado;
            echo "Resultado: $resultado\n";
        } catch (Exception $e) {
            echo "ERROR evaluando: " . $e->getMessage() . "\n";
            $variablesLocales[$nombreVariable] = 0;
        }

        echo "\n";
    }
}

echo "=== VARIABLES FINALES ===\n";
foreach ($variablesLocales as $var => $val) {
    echo "$var = " . number_format($val, 2) . "\n";
}

echo "\nISR Quincenal final: B/." . number_format($variablesLocales['monto'] ?? 0, 2) . "\n";