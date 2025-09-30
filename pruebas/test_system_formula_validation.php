<?php
/**
 * Script para probar la fórmula ISR usando el sistema de validación real
 * 2025-09-29
 */

// Configurar directorio de trabajo y autoload
chdir(__DIR__);
require_once 'vendor/autoload.php';
require_once 'app/Core/Database.php';
require_once 'app/Core/Model.php';
require_once 'app/Models/Concept.php';
require_once 'app/Services/PlanillaConceptCalculator.php';

use App\Core\Database;
use App\Models\Concept;

echo "=== PRUEBA FÓRMULA ISR CON SISTEMA REAL ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Leer la fórmula corregida para el sistema
$formulaPath = 'pruebas/formula_isr_corregida_para_sistema.txt';
if (!file_exists($formulaPath)) {
    die("ERROR: No se encuentra el archivo $formulaPath\n");
}

$formula = file_get_contents($formulaPath);
$formula = trim($formula);

echo "FÓRMULA A PROBAR:\n";
echo "================\n";
echo $formula . "\n\n";

// Configurar base de datos
$config = [
    'host' => 'localhost',
    'dbname' => 'planilla_innova29092025',
    'username' => 'root',
    'password' => ''
];

try {
    // Establecer conexión usando el singleton de Database
    Database::getInstance()->setConfig($config);

    // Crear instancia del modelo
    $conceptModel = new Concept();

    echo "=== VALIDACIÓN USANDO SISTEMA REAL ===\n";

    // Probar validación con empleado ID 13 (ANTONIO J. JARAMILLO)
    $result = $conceptModel->validateFormula($formula, 13);

    echo "Resultado de validación:\n";
    echo "- Válida: " . ($result['valid'] ? "✅ SÍ" : "❌ NO") . "\n";
    echo "- Mensaje: " . $result['message'] . "\n";

    if (isset($result['test_result'])) {
        echo "- Resultado de prueba: B/." . number_format($result['test_result'], 2) . "\n";
    }

    if (isset($result['variables_used'])) {
        echo "- Variables utilizadas: " . implode(', ', $result['variables_used']) . "\n";
    }

    echo "\n";

    // Si la validación fue exitosa, probar con más empleados
    if ($result['valid']) {
        echo "=== PRUEBAS CON MÚLTIPLES EMPLEADOS ===\n";

        $employeeIds = [13, 12, 4, 6, 14];
        $expectedResults = [
            13 => 121.30,  // ANTONIO J. JARAMILLO
            12 => 93.17,   // OSCAR RUIZ VALERO
            4 => 48.17,    // ANTONIO RODARTE MENDEZ
            6 => 36.92,    // DOMINGO PASTOR CORDOBA ACOSTA
            14 => 35.50    // FRANCISCO PEREZ DELGADO
        ];

        foreach ($employeeIds as $empId) {
            $testResult = $conceptModel->validateFormula($formula, $empId);

            echo "Empleado ID $empId:\n";
            echo "- Resultado: B/." . number_format($testResult['test_result'] ?? 0, 2) . "\n";
            echo "- Esperado: B/." . number_format($expectedResults[$empId] ?? 0, 2) . "\n";

            $difference = abs(($testResult['test_result'] ?? 0) - ($expectedResults[$empId] ?? 0));
            echo "- Diferencia: B/." . number_format($difference, 2);

            if ($difference < 0.10) {
                echo " ✅\n";
            } else {
                echo " ❌\n";
            }
            echo "\n";
        }
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "=== FIN DE PRUEBAS ===\n";