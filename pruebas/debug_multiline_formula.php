<?php
/**
 * Script para debuggear el problema con fórmulas multi-línea
 * 2025-09-30
 */

// Fórmula multi-línea tal como está en la BD
$formulaMultilinea = "salario_anual = SALARIO*13
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

echo "=== DEBUG FÓRMULA MULTI-LÍNEA ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

echo "FÓRMULA ORIGINAL:\n";
echo "================\n";
echo $formulaMultilinea . "\n\n";

// Simular el procesamiento del dividirFormulaEnLineas
echo "=== SIMULACIÓN dividirFormulaEnLineas ===\n";

// Normalizar diferentes tipos de saltos de línea
$formulaNormalizada = str_replace(["\r\n", "\r"], "\n", $formulaMultilinea);

// Dividir en líneas
$lineas = explode("\n", $formulaNormalizada);
$lineas = array_filter(array_map('trim', $lineas), function($linea) {
    return !empty($linea);
});

echo "Líneas detectadas:\n";
foreach ($lineas as $i => $linea) {
    echo ($i + 1) . ". '$linea'\n";
}

echo "\n=== SIMULACIÓN evaluarFormulaSimple ===\n";

// Simular variables del empleado EMP013 (ANTONIO J. JARAMILLO)
$variablesGlobales = [
    'SALARIO' => 2525.00,
    'GASTOS_REPRESENTACION' => 2435.00,
    'CLAVE_SS' => 'E-02'
];

echo "Variables globales:\n";
foreach ($variablesGlobales as $var => $val) {
    echo "  $var = $val\n";
}
echo "\n";

// Simular procesamiento línea por línea
$variablesLocales = [];
$ultimoResultado = 0;

foreach ($lineas as $numeroLinea => $linea) {
    echo "Procesando línea " . ($numeroLinea + 1) . ": '$linea'\n";

    // Verificar si es una asignación
    if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/', $linea, $matches)) {
        $nombreVariable = $matches[1];
        $expresion = trim($matches[2]);

        echo "  -> Asignación detectada: $nombreVariable = $expresion\n";

        // Simular procesarExpresionConVariables
        $expresionProcesada = $expresion;

        // Reemplazar variables globales
        foreach ($variablesGlobales as $varGlobal => $valorGlobal) {
            $expresionProcesada = str_replace($varGlobal, $valorGlobal, $expresionProcesada);
        }

        // Reemplazar variables locales
        foreach ($variablesLocales as $varLocal => $valorLocal) {
            $expresionProcesada = str_replace($varLocal, $valorLocal, $expresionProcesada);
        }

        echo "  -> Expresión procesada: $expresionProcesada\n";

        // AQUÍ ESTÁ EL PROBLEMA: El sistema no puede evaluar funciones como SI() y LEFT()
        // Vamos a simular estas funciones para el debugging

        // Simular función SI()
        if (preg_match('/SI\s*\(\s*(.+?)\s*,\s*(.+?)\s*,\s*(.+?)\s*\)/', $expresionProcesada, $siMatches)) {
            $condicion = trim($siMatches[1]);
            $valorVerdadero = trim($siMatches[2]);
            $valorFalso = trim($siMatches[3]);

            echo "  -> Función SI detectada: condición='$condicion', verdadero='$valorVerdadero', falso='$valorFalso'\n";

            // Evaluar condición específica para LEFT(CLAVE_SS, 1) = "E"
            if (strpos($condicion, 'LEFT(') !== false && strpos($condicion, 'CLAVE_SS') !== false) {
                $primerCaracter = substr($variablesGlobales['CLAVE_SS'], 0, 1);
                $condicionReal = ($primerCaracter == 'E');
                $resultado = $condicionReal ? $valorVerdadero : $valorFalso;
                echo "  -> LEFT(CLAVE_SS, 1) = '$primerCaracter', condición es " . ($condicionReal ? 'verdadera' : 'falsa') . ", resultado = $resultado\n";
            } else {
                // Evaluar otras condiciones numéricas
                eval("\$condicionReal = $condicion;");
                $resultado = $condicionReal ? $valorVerdadero : $valorFalso;
                echo "  -> Condición '$condicion' es " . ($condicionReal ? 'verdadera' : 'falsa') . ", resultado = $resultado\n";
            }

            $variablesLocales[$nombreVariable] = floatval($resultado);
        } else {
            // Evaluar expresión matemática simple
            try {
                eval("\$resultado = $expresionProcesada;");
                $variablesLocales[$nombreVariable] = $resultado;
                echo "  -> Resultado matemático: $resultado\n";
            } catch (Exception $e) {
                echo "  -> ERROR evaluando expresión: " . $e->getMessage() . "\n";
                $variablesLocales[$nombreVariable] = 0;
            }
        }

        $ultimoResultado = $variablesLocales[$nombreVariable];
        echo "  -> Variable '$nombreVariable' = " . $variablesLocales[$nombreVariable] . "\n";

    } elseif (trim($linea) === 'RETURN monto') {
        echo "  -> RETURN detectado, valor final = " . ($variablesLocales['monto'] ?? 0) . "\n";
        return $variablesLocales['monto'] ?? 0;

    } else {
        echo "  -> Expresión final: $linea\n";
        // Procesar expresión final...
    }

    echo "\n";
}

echo "=== RESULTADO FINAL ===\n";
echo "ISR Quincenal: B/." . number_format($ultimoResultado, 2) . "\n";
echo "Variables finales:\n";
foreach ($variablesLocales as $var => $val) {
    echo "  $var = B/." . number_format($val, 2) . "\n";
}

echo "\n=== PROBLEMA IDENTIFICADO ===\n";
echo "El PlanillaConceptCalculator no puede evaluar las funciones SI() y LEFT()\n";
echo "en las expresiones matemáticas. Necesita implementación específica para estas funciones.\n";