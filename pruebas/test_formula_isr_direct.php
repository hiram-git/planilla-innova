<?php
/**
 * Script directo para probar la fórmula ISR usando math-executor
 * 2025-09-29
 */

require_once 'vendor/autoload.php';

use NXP\MathExecutor;

echo "=== PRUEBA DIRECTA FÓRMULA ISR CON MATH-EXECUTOR ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Leer la fórmula desde el archivo
$formulaPath = 'pruebas/formula_isr_solo_texto_sin_punto_coma.txt';
if (!file_exists($formulaPath)) {
    die("ERROR: No se encuentra el archivo $formulaPath\n");
}

$formula = file_get_contents($formulaPath);
$formula = trim($formula);

echo "FÓRMULA ORIGINAL:\n";
echo "================\n";
echo $formula . "\n\n";

// Problema detectado: la fórmula está en formato multi-línea pero math-executor necesita formato específico
// Convertir a formato válido para math-executor

// Convertir cada línea en una asignación separada
$lines = explode("\n", $formula);
$lines = array_filter(array_map('trim', $lines)); // Remover líneas vacías

echo "LÍNEAS DE LA FÓRMULA:\n";
echo "====================\n";
foreach ($lines as $i => $line) {
    echo ($i + 1) . ". $line\n";
}
echo "\n";

// Conectar a la base de datos para obtener datos de empleados que deben tener ISR
try {
    $pdo = new PDO("mysql:host=localhost;dbname=planilla_innova29092025", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener empleados específicos que sabemos deben tener ISR
    $sql = "SELECT
                id,
                firstname,
                lastname,
                sueldo_individual as salario,
                gastos_representacion,
                clave_seguro_social
            FROM employees
            WHERE id IN (13, 12, 4, 6, 14)
            ORDER BY sueldo_individual DESC";

    $stmt = $pdo->query($sql);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "EMPLEADOS A PROBAR (que deben tener ISR):\n";
    echo "========================================\n";
    foreach ($employees as $emp) {
        echo "ID: {$emp['id']} - {$emp['firstname']} {$emp['lastname']} - Salario: B/." . number_format($emp['salario'], 2) . " - Clave SS: {$emp['clave_seguro_social']}\n";
    }
    echo "\n";

} catch (PDOException $e) {
    die("ERROR de BD: " . $e->getMessage() . "\n");
}

// Función para implementar SI (IF)
function SI($condition, $trueValue, $falseValue) {
    return $condition ? $trueValue : $falseValue;
}

// Función para implementar LEFT
function LEFT($string, $length) {
    return substr($string, 0, $length);
}

// Probar con cada empleado
foreach ($employees as $emp) {
    echo "=== PROBANDO CON EMPLEADO: {$emp['firstname']} {$emp['lastname']} ===\n";

    try {
        // Variables del empleado
        $SALARIO = floatval($emp['salario']);
        $GASTOS_REPRESENTACION = floatval($emp['gastos_representacion']);
        $CLAVE_SS = $emp['clave_seguro_social'];

        echo "Variables establecidas:\n";
        echo "  SALARIO = B/." . number_format($SALARIO, 2) . "\n";
        echo "  GASTOS_REPRESENTACION = B/." . number_format($GASTOS_REPRESENTACION, 2) . "\n";
        echo "  CLAVE_SS = '$CLAVE_SS'\n\n";

        // Ejecutar fórmula paso a paso para debugging
        echo "Ejecutando fórmula paso a paso:\n";

        // Paso 1: salario_anual = SALARIO*13
        $salario_anual = $SALARIO * 13;
        echo "1. salario_anual = $SALARIO * 13 = B/." . number_format($salario_anual, 2) . "\n";

        // Paso 2: gr_anual = GASTOS_REPRESENTACION*13
        $gr_anual = $GASTOS_REPRESENTACION * 13;
        echo "2. gr_anual = $GASTOS_REPRESENTACION * 13 = B/." . number_format($gr_anual, 2) . "\n";

        // Paso 3: deduc_pers = SI(LEFT(CLAVE_SS, 1) = "E", 800, 0)
        $primera_letra = LEFT($CLAVE_SS, 1);
        $deduc_pers = SI($primera_letra == "E", 800, 0);
        echo "3. LEFT(CLAVE_SS, 1) = '$primera_letra'\n";
        echo "3. deduc_pers = SI('$primera_letra' = 'E', 800, 0) = B/." . number_format($deduc_pers, 2) . "\n";

        // Paso 4: neto_gravable = salario_anual - deduc_pers
        $neto_gravable = $salario_anual - $deduc_pers;
        echo "4. neto_gravable = $salario_anual - $deduc_pers = B/." . number_format($neto_gravable, 2) . "\n";

        // Paso 5: saldo_gravable = SI(neto_gravable > 11000, neto_gravable - 11000, 0)
        $saldo_gravable = SI($neto_gravable > 11000, $neto_gravable - 11000, 0);
        echo "5. saldo_gravable = SI($neto_gravable > 11000, $neto_gravable - 11000, 0) = B/." . number_format($saldo_gravable, 2) . "\n";

        // Paso 6: isr_anual = saldo_gravable * 0.15
        $isr_anual = $saldo_gravable * 0.15;
        echo "6. isr_anual = $saldo_gravable * 0.15 = B/." . number_format($isr_anual, 2) . "\n";

        // Paso 7: isr_mensual = isr_anual/13
        $isr_mensual = $isr_anual / 13;
        echo "7. isr_mensual = $isr_anual / 13 = B/." . number_format($isr_mensual, 2) . "\n";

        // Paso 8: isr_quincenal = isr_mensual/2
        $isr_quincenal = $isr_mensual / 2;
        echo "8. isr_quincenal = $isr_mensual / 2 = B/." . number_format($isr_quincenal, 2) . "\n";

        // Pasos para tramo superior (> B/.50,000)
        echo "\nCalculando tramo superior (>B/.50,000):\n";

        // Paso 9: saldo_excedente = SI(salario_anual > 50000, salario_anual - 50000, 0)
        $saldo_excedente = SI($salario_anual > 50000, $salario_anual - 50000, 0);
        echo "9. saldo_excedente = SI($salario_anual > 50000, $salario_anual - 50000, 0) = B/." . number_format($saldo_excedente, 2) . "\n";

        // Paso 10: excedente_gravable = SI(saldo_excedente > 0, saldo_excedente * 0.25, 0)
        $excedente_gravable = SI($saldo_excedente > 0, $saldo_excedente * 0.25, 0);
        echo "10. excedente_gravable = SI($saldo_excedente > 0, $saldo_excedente * 0.25, 0) = B/." . number_format($excedente_gravable, 2) . "\n";

        // Paso 11: exceso_adicional = SI(excedente_gravable > 0, excedente_gravable + 5850, 0)
        $exceso_adicional = SI($excedente_gravable > 0, $excedente_gravable + 5850, 0);
        echo "11. exceso_adicional = SI($excedente_gravable > 0, $excedente_gravable + 5850, 0) = B/." . number_format($exceso_adicional, 2) . "\n";

        // Paso 12: exceso_anual = SI(exceso_adicional > 0, exceso_adicional/13, 0)
        $exceso_anual = SI($exceso_adicional > 0, $exceso_adicional / 13, 0);
        echo "12. exceso_anual = SI($exceso_adicional > 0, $exceso_adicional / 13, 0) = B/." . number_format($exceso_anual, 2) . "\n";

        // Paso 13: exceso_quincenal = SI(exceso_anual > 0, exceso_anual/2, 0)
        $exceso_quincenal = SI($exceso_anual > 0, $exceso_anual / 2, 0);
        echo "13. exceso_quincenal = SI($exceso_anual > 0, $exceso_anual / 2, 0) = B/." . number_format($exceso_quincenal, 2) . "\n";

        // Paso 14: monto = SI(exceso_quincenal > 0, exceso_quincenal, isr_quincenal)
        $monto = SI($exceso_quincenal > 0, $exceso_quincenal, $isr_quincenal);
        echo "14. monto = SI($exceso_quincenal > 0, $exceso_quincenal, $isr_quincenal) = B/." . number_format($monto, 2) . "\n";

        echo "\n✅ RESULTADO FINAL ISR QUINCENAL: B/." . number_format($monto, 2) . "\n";

        // Comparar con resultado esperado de los archivos de prueba anteriores
        $expectedResults = [
            13 => 121.30,  // ANTONIO J. JARAMILLO
            12 => 93.17,   // OSCAR RUIZ VALERO
            4 => 48.17,    // ANTONIO RODARTE MENDEZ
            6 => 36.92,    // DOMINGO PASTOR CORDOBA ACOSTA
            14 => 35.50    // FRANCISCO PEREZ DELGADO
        ];

        if (isset($expectedResults[$emp['id']])) {
            $expected = $expectedResults[$emp['id']];
            $difference = abs($monto - $expected);
            echo "Resultado esperado: B/." . number_format($expected, 2) . "\n";
            echo "Diferencia: B/." . number_format($difference, 2) . "\n";

            if ($difference < 0.10) {
                echo "✅ RESULTADO CORRECTO (diferencia < B/.0.10)\n";
            } else {
                echo "❌ RESULTADO INCORRECTO (diferencia significativa)\n";
            }
        }

        echo "\n" . str_repeat("-", 60) . "\n\n";

    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    }
}

echo "=== FIN DE PRUEBAS ===\n";