<?php
/**
 * Prueba directa del dashboard MVC
 */
session_start();

// Simular que hay sesión activa
if (!isset($_SESSION['admin'])) {
    $_SESSION['admin'] = 1; // ID de admin de prueba
    $_SESSION['admin_name'] = 'Test Admin';
}

echo "<h1>Prueba del Dashboard MVC</h1>";
echo "<p>Intentando cargar el controlador Admin...</p>";

try {
    // Cargar autoloader
    require_once 'vendor/autoload.php';
    
    // Crear instancia del controlador Admin
    $adminController = new \App\Controllers\Admin();
    
    echo "<p>✅ Controlador Admin cargado correctamente</p>";
    
    // Intentar ejecutar el método dashboard
    echo "<p>Ejecutando método dashboard...</p>";
    
    ob_start(); // Capturar output
    $adminController->dashboard();
    $output = ob_get_clean();
    
    echo "<p>✅ Método dashboard ejecutado</p>";
    echo "<p>Output generado: " . strlen($output) . " caracteres</p>";
    
    if (strlen($output) > 0) {
        echo "<h2>Output del Dashboard:</h2>";
        echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 400px; overflow: auto;'>";
        echo htmlspecialchars($output);
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Trace: " . $e->getTraceAsString() . "</p>";
}

echo "<hr>";
echo '<p><a href="/admin/index.php">Volver al Login</a></p>';
echo '<p><a href="/test_dashboard.php">Volver a la Prueba</a></p>';
?>