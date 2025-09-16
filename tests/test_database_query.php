<?php
/**
 * Test database connection and query to debug the frecuencia column issue
 */
require_once 'app/Core/Database.php';
use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔍 Database Query Test - Frecuencia Column</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>Step 1: Test direct query to tipos_planilla</h2>";
    
    $sql = "SELECT id, codigo, nombre, descripcion, activo, frecuencia FROM tipos_planilla ORDER BY id";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Código</th><th>Nombre</th><th>Frecuencia</th></tr>";
    foreach ($tipos as $tipo) {
        echo "<tr>";
        echo "<td>{$tipo['id']}</td>";
        echo "<td>{$tipo['codigo']}</td>";
        echo "<td>{$tipo['nombre']}</td>";
        echo "<td><strong>{$tipo['frecuencia']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Step 2: Test JOIN query (exact same as PayrollAccumulationsProcessor)</h2>";
    
    $testPayrollId = 35;
    $joinSql = "SELECT 
                    pc.id, 
                    pc.descripcion, 
                    pc.fecha, 
                    pc.tipo_planilla_id,
                    tp.descripcion as tipo_planilla,
                    tp.frecuencia,
                    MONTH(pc.fecha) as mes,
                    YEAR(pc.fecha) as ano
                FROM planilla_cabecera pc
                INNER JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                WHERE pc.id = ?";
    
    echo "<p><strong>Testing query:</strong></p>";
    echo "<pre>" . htmlspecialchars($joinSql) . "</pre>";
    echo "<p><strong>With planilla ID:</strong> $testPayrollId</p>";
    
    $stmt = $db->prepare($joinSql);
    $stmt->execute([$testPayrollId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50;'>";
        echo "<h3>✅ SUCCESS - Query worked!</h3>";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
        
        echo "<h2>Step 3: Test PayrollAccumulationsProcessor directly</h2>";
        
        require_once 'app/Core/Model.php';
        require_once 'app/Models/PayrollAccumulationsProcessor.php';
        
        $processor = new App\Models\PayrollAccumulationsProcessor();
        
        // Use reflection to call the private method
        $reflection = new ReflectionClass($processor);
        $method = $reflection->getMethod('getPayrollInfo');
        $method->setAccessible(true);
        
        $payrollInfo = $method->invoke($processor, $testPayrollId);
        
        echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50;'>";
        echo "<h3>✅ PayrollAccumulationsProcessor::getPayrollInfo() worked!</h3>";
        echo "<pre>" . json_encode($payrollInfo, JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
        
    } else {
        echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff0000;'>";
        echo "<strong>❌ Error:</strong> No result from JOIN query";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "<br><strong>File:</strong> " . $e->getFile() . " (Line: " . $e->getLine() . ")";
    echo "<br><strong>Trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; font-size: 13px; }
th, td { padding: 8px 10px; text-align: left; border: 1px solid #ddd; }
th { background: #f9f9f9; }
pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>