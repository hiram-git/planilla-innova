<?php
/**
 * Debug: Verificar estructura de tablas para claves foráneas
 */
require_once 'app/Core/Database.php';
use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔍 Debug: Claves Foráneas para acumulados_por_planilla</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Tablas que necesitamos referenciar
    $referencedTables = [
        'planilla_cabecera' => 'id',
        'employees' => 'id', 
        'concepto' => 'id',
        'tipos_acumulados' => 'id'
    ];
    
    foreach ($referencedTables as $tableName => $expectedColumn) {
        echo "<h2>📊 Tabla: <strong>$tableName</strong></h2>";
        
        // Verificar si la tabla existe
        $tableExists = $db->query("SHOW TABLES LIKE '$tableName'")->fetch();
        
        if (!$tableExists) {
            echo "<div style='background: #ffebee; padding: 10px; border: 1px solid #f44336;'>";
            echo "<strong>❌ ERROR:</strong> La tabla <strong>$tableName</strong> NO EXISTE";
            echo "</div>";
            continue;
        }
        
        echo "<p>✅ Tabla existe</p>";
        
        // Verificar estructura
        $columns = $db->query("DESCRIBE $tableName")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $hasExpectedColumn = false;
        foreach ($columns as $column) {
            $isTarget = ($column['Field'] === $expectedColumn);
            $bgColor = $isTarget ? 'background: #e8f5e8;' : '';
            
            echo "<tr style='$bgColor'>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
            
            if ($column['Field'] === $expectedColumn) {
                $hasExpectedColumn = true;
            }
        }
        echo "</table>";
        
        if ($hasExpectedColumn) {
            echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50; margin: 10px 0;'>";
            echo "<strong>✅ CORRECTO:</strong> La columna <strong>$expectedColumn</strong> existe";
            echo "</div>";
        } else {
            echo "<div style='background: #ffebee; padding: 10px; border: 1px solid #f44336; margin: 10px 0;'>";
            echo "<strong>❌ ERROR:</strong> La columna <strong>$expectedColumn</strong> NO EXISTE";
            echo "</div>";
        }
        
        // Mostrar información del motor y charset
        $tableInfo = $db->query("SHOW CREATE TABLE $tableName")->fetch(PDO::FETCH_ASSOC);
        if ($tableInfo) {
            echo "<h4>🔧 Información de la tabla:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto; font-size: 12px;'>";
            echo htmlspecialchars($tableInfo['Create Table']);
            echo "</pre>";
        }
        
        echo "<hr>";
    }
    
    // Verificar si hay conflictos con nombres de columnas
    echo "<h2>🔍 Verificación de Conflictos</h2>";
    
    // Verificar tabla concepto vs concepts
    $conceptoExists = $db->query("SHOW TABLES LIKE 'concepto'")->fetch();
    $conceptsExists = $db->query("SHOW TABLES LIKE 'concepts'")->fetch();
    
    if ($conceptoExists && $conceptsExists) {
        echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107;'>";
        echo "<strong>⚠️ ADVERTENCIA:</strong> Existen ambas tablas 'concepto' y 'concepts'. Esto puede causar confusión.";
        echo "</div>";
    } elseif ($conceptsExists && !$conceptoExists) {
        echo "<div style='background: #d1ecf1; padding: 10px; border: 1px solid #bee5eb;'>";
        echo "<strong>ℹ️ INFO:</strong> Se usa la tabla 'concepts' en lugar de 'concepto'";
        echo "</div>";
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
th, td { padding: 6px 10px; text-align: left; border: 1px solid #ddd; }
th { background: #f9f9f9; }
pre { white-space: pre-wrap; word-wrap: break-word; }
</style>