<?php
// Test script para verificar la ruta regenerate-employee
session_start();

// Simular datos POST
$_POST['employee_id'] = 7; // ID del empleado
$_POST['csrf_token'] = $_SESSION['csrf_token'] ?? 'test_token';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Simular URL
$_GET['url'] = 'panel/payrolls/22/regenerate-employee';

echo "=== TEST REGENERATE ROUTE ===\n\n";
echo "URL simulada: " . $_GET['url'] . "\n";
echo "Método HTTP: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Employee ID: " . $_POST['employee_id'] . "\n";
echo "CSRF Token: " . ($_POST['csrf_token'] ?? 'NO_TOKEN') . "\n\n";

// Incluir el index que manejará el routing
echo "Iniciando routing...\n\n";

try {
    require_once 'vendor/autoload.php';
    
    $app = new App\Core\App();
    $app->run();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>