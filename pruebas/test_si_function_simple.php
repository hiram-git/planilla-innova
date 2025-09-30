<?php
/**
 * Test simple para debuggear función SI()
 */

echo "=== TEST FUNCIÓN SI() SIMPLE ===\n\n";

// Test 1: Función SI() simple sin LEFT()
$expresion1 = 'SI(2525 > 1000, 800, 0)';
echo "Test 1: $expresion1\n";

// Simulamos el procesamiento
if (preg_match('/SI\(([^()]*(?:\([^()]*\)[^()]*)*)\)/', $expresion1, $matches)) {
    echo "Contenido SI(): " . $matches[1] . "\n";

    // Dividir parámetros manualmente para test
    $contenido = $matches[1];
    $parametros = explode(',', $contenido);

    if (count($parametros) === 3) {
        $condicion = trim($parametros[0]);
        $verdadero = trim($parametros[1]);
        $falso = trim($parametros[2]);

        echo "Condición: '$condicion'\n";
        echo "Verdadero: '$verdadero'\n";
        echo "Falso: '$falso'\n";

        // Evaluar condición
        if (preg_match('/([0-9.]+)\s*([><=]+)\s*([0-9.]+)/', $condicion, $condMatches)) {
            $valor1 = (float)$condMatches[1];
            $operador = $condMatches[2];
            $valor2 = (float)$condMatches[3];

            echo "Comparando: $valor1 $operador $valor2\n";

            $resultado = false;
            switch ($operador) {
                case '>': $resultado = $valor1 > $valor2; break;
                case '<': $resultado = $valor1 < $valor2; break;
                case '>=': $resultado = $valor1 >= $valor2; break;
                case '<=': $resultado = $valor1 <= $valor2; break;
                case '==': case '=': $resultado = $valor1 == $valor2; break;
            }

            echo "Resultado condición: " . ($resultado ? 'VERDADERO' : 'FALSO') . "\n";
            echo "Valor final: " . ($resultado ? $verdadero : $falso) . "\n";
        }
    }
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 2: Función SI() con LEFT()
$expresion2 = 'SI(LEFT(CLAVE_SS, 1) = "E", 800, 0)';
echo "Test 2: $expresion2\n";

// Primero procesar LEFT()
$variables = ['CLAVE_SS' => 'E-02'];
echo "Variables: CLAVE_SS = E-02\n";

$expresionProcesada = $expresion2;

// Procesar LEFT()
$expresionProcesada = preg_replace_callback('/LEFT\(([^,]+),\s*(\d+)\)/', function($matches) use ($variables) {
    $texto = trim($matches[1]);
    $longitud = (int)trim($matches[2]);

    echo "Procesando LEFT($texto, $longitud)\n";

    if (isset($variables[$texto])) {
        $textoResuelto = $variables[$texto];
    } else {
        $textoResuelto = $texto;
    }

    $resultado = '"' . substr($textoResuelto, 0, $longitud) . '"';
    echo "LEFT($texto, $longitud) -> $resultado\n";
    return $resultado;
}, $expresionProcesada);

echo "Después de LEFT(): $expresionProcesada\n";

// Ahora procesar SI()
if (preg_match('/SI\(([^()]*(?:\([^()]*\)[^()]*)*)\)/', $expresionProcesada, $matches)) {
    echo "Contenido SI(): " . $matches[1] . "\n";

    // Dividir parámetros respetando comillas
    $contenido = $matches[1];
    $parametros = [];
    $parametroActual = '';
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
        } elseif ($char === ',' && !$enComillas) {
            $parametros[] = $parametroActual;
            $parametroActual = '';
        } else {
            $parametroActual .= $char;
        }
    }

    if ($parametroActual !== '') {
        $parametros[] = $parametroActual;
    }

    echo "Parámetros divididos:\n";
    foreach ($parametros as $i => $param) {
        echo "  $i: '" . trim($param) . "'\n";
    }

    if (count($parametros) === 3) {
        $condicion = trim($parametros[0]);
        $verdadero = trim($parametros[1]);
        $falso = trim($parametros[2]);

        echo "Evaluando condición: '$condicion'\n";

        // Evaluar comparación de strings
        if (preg_match('/["\']([^"\']*)["\']\s*=\s*["\']([^"\']*)["\']/', $condicion, $condMatches)) {
            $valor1 = $condMatches[1];
            $valor2 = $condMatches[2];

            echo "Comparando strings: '$valor1' = '$valor2'\n";
            $resultado = $valor1 === $valor2;
            echo "Resultado: " . ($resultado ? 'VERDADERO' : 'FALSO') . "\n";
            echo "Valor final: " . ($resultado ? $verdadero : $falso) . "\n";
        }
    }
}

echo "\n=== FIN TEST ===\n";