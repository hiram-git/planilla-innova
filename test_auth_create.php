<?php
// Script para probar creación con autenticación simulada
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar sesión
session_start();

// Simular autenticación (temporal para testing)
$_SESSION['admin'] = [
    'id' => 1,
    'username' => 'admin',
    'role' => 'admin'
];

echo "<h1>Testing Organizational Create with Auth</h1>\n";

// Simular POST data
$_POST = [
    'descripcion' => 'Test Element with Auth ' . date('H:i:s'),
    'id_padre' => ''
];

$_SERVER['REQUEST_METHOD'] = 'POST';

// Cargar configuración
require_once 'config/app.php';

// Cargar autoloader
spl_autoload_register(function($class) {
    $class = str_replace('\\', '/', $class);
    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

try {
    echo "<h2>1. Instantiating Controller</h2>\n";
    $controller = new App\Controllers\OrganizationalController();
    echo "✅ Controller instantiated<br>\n";
    
    echo "<h2>2. Calling create() method</h2>\n";
    $controller->create();
    echo "✅ Create method called (check for redirects)<br>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

// Mostrar mensajes de sesión
if (isset($_SESSION['success'])) {
    echo "<div style='color: green;'>SUCCESS: " . $_SESSION['success'] . "</div>\n";
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo "<div style='color: red;'>ERROR: " . $_SESSION['error'] . "</div>\n";
    unset($_SESSION['error']);
}

echo "<h2>3. Checking logs</h2>\n";
echo "Check PHP error log for detailed debugging information.<br>\n";
?>