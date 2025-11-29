<?php
/**
 * 🧪 SCRIPT DE PRUEBA - Fórmula ISR Multilínea
 *
 * Objetivo: Reproducir y diagnosticar el error en la fórmula del concepto ISR (ID 4)
 * Error reportado: Variable o concepto 'isr_quincenal' no encontrado
 *
 * Fecha: 28-Nov-2025
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use App\Services\PlanillaConceptCalculator;

echo "\n" . str_repeat("=", 80) . "\n";
echo "🧪 PRUEBA DE FÓRMULA ISR - Concepto ID 4\n";
echo str_repeat("=", 80) . "\n\n";

try {
    // Conectar a la base de datos
    $db = Database::getInstance()->getConnection();

    // 1. OBTENER LA FÓRMULA DE LA BASE DE DATOS
    echo "📋 PASO 1: Obtener fórmula desde BD\n";
    echo str_repeat("-", 80) . "\n";

    $stmt = $db->prepare("SELECT id, concepto, descripcion, formula FROM concepto WHERE id = 4");
    $stmt->execute();
    $conceptoISR = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conceptoISR) {
        throw new Exception("❌ Concepto ISR (ID 4) no encontrado en la base de datos");
    }

    echo "✅ Concepto encontrado:\n";
    echo "   - ID: {$conceptoISR['id']}\n";
    echo "   - Código: {$conceptoISR['concepto']}\n";
    echo "   - Descripción: {$conceptoISR['descripcion']}\n";
    echo "   - Longitud fórmula: " . strlen($conceptoISR['formula']) . " caracteres\n\n";

    echo "📝 Fórmula completa:\n";
    echo str_repeat("-", 80) . "\n";
    echo $conceptoISR['formula'];
    echo "\n" . str_repeat("-", 80) . "\n\n";

    // 2. ANALIZAR LÍNEAS DE LA FÓRMULA
    echo "🔍 PASO 2: Analizar líneas de la fórmula\n";
    echo str_repeat("-", 80) . "\n";

    $lineas = explode("\n", $conceptoISR['formula']);
    $lineas = array_filter(array_map('trim', $lineas), function($l) { return !empty($l); });

    echo "Total líneas: " . count($lineas) . "\n\n";

    foreach ($lineas as $idx => $linea) {
        $num = $idx + 1;
        echo sprintf("%2d. %s\n", $num, $linea);

        // Analizar si es asignación
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/', $linea, $matches)) {
            $variable = $matches[1];
            $expresion = $matches[2];
            echo "    └─ Asignación: \$variable = '$variable', \$expresion = '$expresion'\n";

            // DETECTAR ERROR: Variable se referencia a sí misma
            if (strpos($expresion, $variable) !== false) {
                echo "    ⚠️  ADVERTENCIA: La variable '$variable' se referencia a sí misma en la expresión!\n";
            }
        }
        echo "\n";
    }

    // 3. OBTENER DATOS DEL EMPLEADO
    echo "👤 PASO 3: Obtener datos del empleado SRI431260\n";
    echo str_repeat("-", 80) . "\n";

    $stmt = $db->prepare("SELECT id, employee_id, firstname, lastname FROM employees WHERE employee_id = ?");
    $stmt->execute(['SRI431260']);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        throw new Exception("❌ Empleado SRI431260 no encontrado");
    }

    echo "✅ Empleado encontrado:\n";
    echo "   - ID: {$empleado['id']}\n";
    echo "   - Cédula: {$empleado['employee_id']}\n";
    echo "   - Nombre: {$empleado['firstname']} {$empleado['lastname']}\n\n";

    // 4. CONFIGURAR CALCULADORA E INTENTAR EVALUAR
    echo "🧮 PASO 4: Intentar evaluar fórmula con PlanillaConceptCalculator\n";
    echo str_repeat("-", 80) . "\n";

    $calculator = new PlanillaConceptCalculator();

    // Establecer variables del empleado con tipo de planilla
    $tipo_planilla_id = 1; // Tipo de planilla para esta prueba
    echo "📌 Usando tipo_planilla_id = $tipo_planilla_id\n\n";

    $calculator->setVariablesColaborador($empleado['id'], $tipo_planilla_id);

    // Establecer fechas de planilla (ejemplo)
    $calculator->establecerFechasPlanilla('2025-11-01', '2025-11-15');

    echo "✅ Variables establecidas:\n";
    $vars = $calculator->getVariablesColaborador();
    foreach ($vars as $nombre => $valor) {
        echo "   - $nombre = $valor\n";
    }
    echo "\n";

    echo "⏳ Evaluando fórmula...\n\n";

    // Intentar evaluar (esto debería generar el error)
    try {
        $resultado = $calculator->evaluarFormula($conceptoISR['formula']);
        echo "✅ Resultado: $resultado\n";
    } catch (\Exception $e) {
        echo "❌ ERROR CAPTURADO:\n";
        echo "   Tipo: " . get_class($e) . "\n";
        echo "   Mensaje: " . $e->getMessage() . "\n\n";

        // 5. ANÁLISIS DEL ERROR
        echo "🔬 PASO 5: Análisis del error\n";
        echo str_repeat("-", 80) . "\n";

        if (strpos($e->getMessage(), 'isr_quincenal') !== false) {
            echo "✅ Error confirmado: La variable 'isr_quincenal' no está definida cuando se intenta usar\n\n";

            echo "🔍 Diagnóstico:\n";
            echo "   Línea problemática #7:\n";
            echo "   > isr_quincenal = isr_quincenal/2\n\n";

            echo "   El problema: La variable 'isr_quincenal' está intentando definirse\n";
            echo "   en términos de sí misma, lo cual es imposible en la primera evaluación.\n\n";

            echo "💡 Solución propuesta:\n";
            echo "   Cambiar línea #7 de:\n";
            echo "   ❌ isr_quincenal = isr_quincenal/2\n\n";
            echo "   A:\n";
            echo "   ✅ isr_quincenal = isr_mensual/2\n\n";

            echo "   Esto sería lógico porque:\n";
            echo "   - isr_mensual (línea #6) ya está definido como: isr_anual/13\n";
            echo "   - isr_quincenal debería ser la mitad del mensual: isr_mensual/2\n";
        }
    }

    echo "\n" . str_repeat("=", 80) . "\n";
    echo "✅ PRUEBA COMPLETADA\n";
    echo str_repeat("=", 80) . "\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR FATAL:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}
