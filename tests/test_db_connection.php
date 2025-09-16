<?php
/**
 * Test database connection to verify which database we're connected to
 */
require_once 'app/Core/Database.php';
use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔍 Database Connection Test</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Get current database name
    $result = $db->query("SELECT DATABASE() as current_db")->fetch();
    echo "<h2>Current Database: <strong>{$result['current_db']}</strong></h2>";
    
    // Check if tipos_planilla table exists
    $tableExists = $db->query("SHOW TABLES LIKE 'tipos_planilla'")->fetch();
    
    if ($tableExists) {
        echo "<p>✅ tipos_planilla table exists</p>";
        
        // Get table structure
        $columns = $db->query("DESCRIBE tipos_planilla")->fetchAll();
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        $hasFrequencia = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'frecuencia') {
                $hasFrequencia = true;
            }
            echo "<tr>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($hasFrequencia) {
            echo "<p>✅ frecuencia column exists!</p>";
        } else {
            echo "<p>❌ frecuencia column is MISSING!</p>";
            echo "<p><strong>Need to add frecuencia column to the correct database.</strong></p>";
        }
        
    } else {
        echo "<p>❌ tipos_planilla table does not exist in this database</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; font-size: 13px; }
th, td { padding: 8px 10px; text-align: left; border: 1px solid #ddd; }
th { background: #f9f9f9; }
</style>