<?php
/**
 * Debug: Verificar estructura de tablas de acumulados
 */
require_once 'app/Core/Database.php';
use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔍 Debug: Estructura de Tablas de Acumulados</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Verificar qué tablas de acumulados existen
    echo "<h2>📋 Tablas de Acumulados Existentes</h2>";
    $tables = $db->query("SHOW TABLES LIKE '%acumulados%'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p>❌ No se encontraron tablas de acumulados.</p>";
        exit;
    }
    
    foreach ($tables as $table) {
        echo "<h3>📊 Tabla: <strong>$table</strong></h3>";
        
        // Describir estructura
        $columns = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Contar registros
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<p><strong>Total registros:</strong> $count</p>";
        
        // Mostrar algunos registros de ejemplo si existen
        if ($count > 0 && $count <= 10) {
            echo "<h4>🔍 Registros de ejemplo:</h4>";
            $samples = $db->query("SELECT * FROM $table LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($samples)) {
                echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
                echo "<tr style='background: #e8f4fd;'>";
                foreach (array_keys($samples[0]) as $header) {
                    echo "<th>$header</th>";
                }
                echo "</tr>";
                
                foreach ($samples as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        echo "<hr>";
    }
    
    // 2. Verificar también planilla_detalle
    echo "<h2>📋 Estructura tabla planilla_detalle</h2>";
    $columns = $db->query("DESCRIBE planilla_detalle")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $count = $db->query("SELECT COUNT(*) FROM planilla_detalle")->fetchColumn();
    echo "<p><strong>Total registros:</strong> $count</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 6px 10px; text-align: left; border: 1px solid #ddd; }
th { background: #f9f9f9; }
</style>