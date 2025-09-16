<?php
// Test directo para verificar acumulados
require_once 'app/Core/Database.php';

try {
    $db = App\Core\Database::getInstance();
    
    echo "<h1>Test de Acumulados</h1>";
    
    // Verificar si existen las tablas
    $tables = ['tipos_acumulados', 'empleados_acumulados_historicos', 'planillas_acumulados_consolidados', 'conceptos_acumulados'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "<p>✅ Tabla '$table' existe con {$result['count']} registros</p>";
        } catch (Exception $e) {
            echo "<p>❌ Tabla '$table' no existe: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar empleados
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM employees");
        $result = $stmt->fetch();
        echo "<p>✅ Tabla 'employees' tiene {$result['count']} empleados</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error con tabla employees: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<h2>Test del Controlador AcumuladoController</h2>";
    
    // Probar el controlador
    try {
        $controller = new App\Controllers\AcumuladoController();
        echo "<p>✅ AcumuladoController instanciado correctamente</p>";
        
        // Probar obtener empleados
        $reflection = new ReflectionClass($controller);
        $employeeModel = $reflection->getProperty('employeeModel');
        $employeeModel->setAccessible(true);
        $empModel = $employeeModel->getValue($controller);
        
        $employees = $empModel->getActiveEmployees();
        echo "<p>✅ Se obtuvieron " . count($employees) . " empleados activos</p>";
        
        // Probar tipos acumulados
        $tipoModel = $reflection->getProperty('tipoAcumuladoModel');
        $tipoModel->setAccessible(true);
        $tipModel = $tipoModel->getValue($controller);
        
        $tipos = $tipModel->getActivos();
        echo "<p>✅ Se obtuvieron " . count($tipos) . " tipos de acumulados activos</p>";
        
        foreach ($tipos as $tipo) {
            echo "<li>{$tipo['codigo']} - {$tipo['descripcion']}</li>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error en AcumuladoController: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error de conexión a BD: " . $e->getMessage() . "</p>";
}
?>