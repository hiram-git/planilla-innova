<?php
// Test simple para tipos-planilla
require_once 'vendor/autoload.php';

session_start();

// Simular datos del formulario
$_POST = [
    'codigo' => 'TP01',
    'nombre' => 'Test Tipo',
    'descripcion' => 'Descripción de prueba',
    'activo' => '1'
];

// Simular REQUEST_METHOD
$_SERVER['REQUEST_METHOD'] = 'POST';

// Simular URL parsing
$_GET['url'] = 'admin/tipos-planilla';

try {
    $app = new App\Core\App();
    $app->run();
    echo "Success: App ran without errors!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}