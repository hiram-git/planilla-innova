<?php
/**
 * Script simple para probar la fórmula ISR
 * 2025-09-29
 */

require_once 'app/Services/PlanillaConceptCalculator.php';

use App\Services\PlanillaConceptCalculator;

echo "=== PRUEBA SIMPLE FÓRMULA ISR ===\n";
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

// Verificar si termina con RETURN
if (!preg_match('/RETURN\s+\w+\s*$/i', $formula)) {
    echo "⚠️  ADVERTENCIA: La fórmula no termina con RETURN monto\n";
    echo "Agregando 'RETURN monto' al final...\n\n";
    $formula .= "\nRETURN monto";
}

echo "FÓRMULA CON RETURN:\n";
echo "==================\n";
echo $formula . "\n\n";

// Conectar a la base de datos para obtener datos de empleados
try {
    $pdo = new PDO("mysql:host=localhost;dbname=planilla_innova29092025", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener empleados con sus datos necesarios para ISR
    $sql = "SELECT
                id,
                firstname,
                lastname,
                sueldo_individual as salario,
                gastos_representacion,
                clave_seguro_social
            FROM employees
            WHERE situacion_id = 1
            ORDER BY id LIMIT 5";

    $stmt = $pdo->query($sql);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "EMPLEADOS A PROBAR:\n";
    echo "==================\n";
    foreach ($employees as $emp) {
        echo "ID: {$emp['id']} - {$emp['firstname']} {$emp['lastname']} - Salario: B/." . number_format($emp['salario'], 2) . " - Clave SS: {$emp['clave_seguro_social']}\n";
    }
    echo "\n";

} catch (PDOException $e) {
    die("ERROR de BD: " . $e->getMessage() . "\n");
}

// Probar con cada empleado
foreach ($employees as $emp) {
    echo "=== PROBANDO CON EMPLEADO: {$emp['firstname']} {$emp['lastname']} ===\n";

    try {
        $calculator = new PlanillaConceptCalculator();

        // Configurar variables manualmente
        $variables = [
            'SALARIO' => floatval($emp['salario']),
            'GASTOS_REPRESENTACION' => floatval($emp['gastos_representacion']),
            'CLAVE_SS' => $emp['clave_seguro_social']
        ];

        echo "Variables establecidas:\n";
        foreach ($variables as $var => $val) {
            echo "  $var = $val\n";
        }

        // Usar reflection para establecer las variables en el calculator
        $reflection = new ReflectionClass($calculator);
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        $property->setValue($calculator, array_merge($property->getValue($calculator), $variables));

        // Evaluar la fórmula
        $result = $calculator->evaluarFormula($formula);

        echo "Resultado ISR Quincenal: B/." . number_format($result, 2) . "\n";

        // Cálculo manual para verificar
        $salario_anual = $variables['SALARIO'] * 13;
        $clave_primera_letra = substr($variables['CLAVE_SS'], 0, 1);
        $deduc_pers = ($clave_primera_letra === 'E') ? 800 : 0;
        $neto_gravable = $salario_anual - $deduc_pers;

        echo "\nVerificación manual:\n";
        echo "  Salario anual: B/." . number_format($salario_anual, 2) . "\n";
        echo "  Primera letra clave SS: '$clave_primera_letra'\n";
        echo "  Deducción personal: B/." . number_format($deduc_pers, 2) . "\n";
        echo "  Neto gravable: B/." . number_format($neto_gravable, 2) . "\n";
        echo "  ¿Debe pagar ISR? " . ($neto_gravable > 11000 ? 'SÍ' : 'NO') . "\n";

        if ($neto_gravable > 11000) {
            $saldo_gravable = $neto_gravable - 11000;
            $isr_anual = $saldo_gravable * 0.15;
            $isr_mensual = $isr_anual / 13;
            $isr_quincenal_manual = $isr_mensual / 2;

            echo "  Saldo gravable: B/." . number_format($saldo_gravable, 2) . "\n";
            echo "  ISR anual: B/." . number_format($isr_anual, 2) . "\n";
            echo "  ISR quincenal manual: B/." . number_format($isr_quincenal_manual, 2) . "\n";

            if (abs($result - $isr_quincenal_manual) > 0.01) {
                echo "  ⚠️  DIFERENCIA entre fórmula y cálculo manual!\n";
            } else {
                echo "  ✅ Cálculo correcto\n";
            }
        }

        echo "\n" . str_repeat("-", 50) . "\n\n";

    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    }
}

echo "=== FIN DE PRUEBAS ===\n";