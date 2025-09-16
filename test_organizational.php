<?php
// Script de prueba para debug del módulo organizacional
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Organizational Module</h1>\n";

// Cargar configuración
require_once 'config/app.php';

// Cargar autoloader simple
spl_autoload_register(function($class) {
    $class = str_replace('\\', '/', $class);
    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

try {
    echo "<h2>1. Testing Database Connection</h2>\n";
    $db = App\Core\Database::getInstance();
    echo "✅ Database connection successful<br>\n";
    
    echo "<h2>2. Testing Organizational Model</h2>\n";
    $model = new App\Models\Organizational();
    echo "✅ Model instantiated<br>\n";
    
    echo "<h2>3. Testing getOrganizationalFlat()</h2>\n";
    $flat = $model->getOrganizationalFlat();
    echo "✅ Got " . count($flat) . " records<br>\n";
    echo "<pre>" . print_r($flat, true) . "</pre>\n";
    
    echo "<h2>4. Testing create() method</h2>\n";
    $testData = [
        'descripcion' => 'Test Element ' . date('Y-m-d H:i:s'),
        'id_padre' => null
    ];
    echo "Creating test element with data: <pre>" . print_r($testData, true) . "</pre>\n";
    
    $result = $model->create($testData);
    echo "✅ Create result: " . print_r($result, true) . "<br>\n";
    
    echo "<h2>5. Verifying creation</h2>\n";
    $newFlat = $model->getOrganizationalFlat();
    echo "✅ Now have " . count($newFlat) . " records<br>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>