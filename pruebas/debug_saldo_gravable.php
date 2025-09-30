<?php
/**
 * Debug específico para saldo_gravable
 */

echo "=== DEBUG SALDO_GRAVABLE ===\n\n";

// Simular el estado en ese punto
$variablesLocales = [
    'salario_anual' => 32825,
    'gr_anual' => 31655,
    'deduc_pers' => 800,
    'neto_gravable' => 32025
];

$expresion = 'SI(neto_gravable > 11000, neto_gravable - 11000, 0)';

echo "Expresión original: $expresion\n";
echo "Variables locales disponibles:\n";
foreach ($variablesLocales as $var => $val) {
    echo "  $var = $val\n";
}
echo "\n";

// Paso 1: Reemplazar variables locales
$expresionConVariables = $expresion;
foreach ($variablesLocales as $varNombre => $varValor) {
    $expresionConVariables = preg_replace('/\b' . preg_quote($varNombre, '/') . '\b/', (string)$varValor, $expresionConVariables);
}

echo "Después de reemplazar variables: $expresionConVariables\n\n";

// Paso 2: Procesar función SI()
if (preg_match('/SI\(([^()]*(?:\([^()]*\)[^()]*)*)\)/', $expresionConVariables, $matches)) {
    $contenido = $matches[1];
    echo "Contenido de SI(): $contenido\n";

    // Dividir parámetros
    $parametros = [];
    $parametroActual = '';
    $nivelParentesis = 0;
    $enComillas = false;
    $caracterComilla = '';

    for ($i = 0; $i < strlen($contenido); $i++) {
        $char = $contenido[$i];

        if (($char === '"' || $char === "'") && !$enComillas) {
            $enComillas = true;
            $caracterComilla = $char;
            $parametroActual .= $char;
        } elseif ($char === $caracterComilla && $enComillas) {
            $enComillas = false;
            $caracterComilla = '';
            $parametroActual .= $char;
        } elseif ($enComillas) {
            $parametroActual .= $char;
        } elseif ($char === '(') {
            $nivelParentesis++;
            $parametroActual .= $char;
        } elseif ($char === ')') {
            $nivelParentesis--;
            $parametroActual .= $char;
        } elseif ($char === ',' && $nivelParentesis === 0) {
            $parametros[] = $parametroActual;
            $parametroActual = '';
        } else {
            $parametroActual .= $char;
        }
    }

    if ($parametroActual !== '') {
        $parametros[] = $parametroActual;
    }

    echo "Parámetros detectados:\n";
    foreach ($parametros as $i => $param) {
        echo "  $i: '" . trim($param) . "'\n";
    }

    if (count($parametros) === 3) {
        $condicion = trim($parametros[0]);
        $valorVerdadero = trim($parametros[1]);
        $valorFalso = trim($parametros[2]);

        echo "\nEvaluando condición: '$condicion'\n";

        // Evaluar condición numérica
        if (preg_match('/([0-9.]+)\s*([><=]+)\s*([0-9.]+)/', $condicion, $condMatches)) {
            $valor1 = (float)$condMatches[1];
            $operador = $condMatches[2];
            $valor2 = (float)$condMatches[3];

            echo "Comparación numérica: $valor1 $operador $valor2\n";

            $resultado = false;
            switch ($operador) {
                case '>': $resultado = $valor1 > $valor2; break;
                case '<': $resultado = $valor1 < $valor2; break;
                case '>=': $resultado = $valor1 >= $valor2; break;
                case '<=': $resultado = $valor1 <= $valor2; break;
                case '==': case '=': $resultado = $valor1 == $valor2; break;
            }

            echo "Resultado condición: " . ($resultado ? 'VERDADERO' : 'FALSO') . "\n";
            echo "Valor retornado: " . ($resultado ? $valorVerdadero : $valorFalso) . "\n";

            // El resultado final debería ser
            $valorFinal = $resultado ? $valorVerdadero : $valorFalso;
            echo "\nValor final esperado: $valorFinal\n";

            // Si el valor verdadero tiene una expresión, evaluarla
            if ($resultado && strpos($valorVerdadero, '-') !== false) {
                echo "Evaluando expresión verdadera: $valorVerdadero\n";

                // Reemplazar variables en la expresión verdadera
                $expresionVerdadera = $valorVerdadero;
                foreach ($variablesLocales as $varNombre => $varValor) {
                    $expresionVerdadera = preg_replace('/\b' . preg_quote($varNombre, '/') . '\b/', (string)$varValor, $expresionVerdadera);
                }

                echo "Expresión verdadera con variables: $expresionVerdadera\n";

                try {
                    $resultadoFinal = eval("return $expresionVerdadera;");
                    echo "Resultado final evaluado: $resultadoFinal\n";
                } catch (Exception $e) {
                    echo "ERROR evaluando expresión: " . $e->getMessage() . "\n";
                }
            }
        } else {
            echo "❌ La condición no coincide con el patrón numérico\n";
            echo "Patrón esperado: /([0-9.]+)\\s*([><=]+)\\s*([0-9.]+)/\n";
        }
    } else {
        echo "❌ Se esperaban 3 parámetros, se encontraron: " . count($parametros) . "\n";
    }
} else {
    echo "❌ No se pudo extraer el contenido de la función SI()\n";
}

echo "\n=== FIN DEBUG ===\n";