<?php
/**
 * Fix: Crear o verificar tabla acumulados_por_planilla
 */
require_once 'app/Core/Database.php';
use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔧 Fix: Estructura tabla acumulados_por_planilla</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>📋 Paso 1: Verificar si existe la tabla</h2>";
    
    // Verificar si la tabla existe
    $tableExists = $db->query("SHOW TABLES LIKE 'acumulados_por_planilla'")->fetch();
    
    if ($tableExists) {
        echo "<p>✅ La tabla <strong>acumulados_por_planilla</strong> existe</p>";
        
        // Verificar estructura
        echo "<h3>🔍 Estructura actual:</h3>";
        $columns = $db->query("DESCRIBE acumulados_por_planilla")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
        
        $hasCorrectColumns = false;
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "</tr>";
            
            if ($column['Field'] === 'planilla_id') {
                $hasCorrectColumns = true;
            }
        }
        echo "</table>";
        
        if (!$hasCorrectColumns) {
            echo "<div style='background: #ffebee; padding: 10px; border: 1px solid #f44336; margin: 10px 0;'>";
            echo "<strong>❌ ERROR:</strong> La tabla existe pero no tiene la columna 'planilla_id'<br>";
            echo "La tabla necesita ser recreada con la estructura correcta.";
            echo "</div>";
            
            echo "<h3>🔄 Recreando tabla con estructura correcta...</h3>";
            
            // Hacer backup si hay datos
            $count = $db->query("SELECT COUNT(*) FROM acumulados_por_planilla")->fetchColumn();
            if ($count > 0) {
                echo "<p>⚠️ La tabla tiene $count registros. Creando backup...</p>";
                $db->exec("CREATE TABLE acumulados_por_planilla_backup AS SELECT * FROM acumulados_por_planilla");
                echo "<p>✅ Backup creado en 'acumulados_por_planilla_backup'</p>";
            }
            
            // Eliminar tabla incorrecta
            $db->exec("DROP TABLE acumulados_por_planilla");
            echo "<p>🗑️ Tabla antigua eliminada</p>";
            
            $tableExists = false; // Forzar recreación
        } else {
            echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50; margin: 10px 0;'>";
            echo "<strong>✅ CORRECTO:</strong> La tabla tiene la estructura adecuada";
            echo "</div>";
        }
        
    } else {
        echo "<p>❌ La tabla <strong>acumulados_por_planilla</strong> NO existe</p>";
    }
    
    // Crear tabla si no existe o fue eliminada
    if (!$tableExists) {
        echo "<h2>🔧 Paso 2: Crear tabla acumulados_por_planilla</h2>";
        
        // Crear tabla SIN claves foráneas inicialmente para evitar errores 150
        $createTableSQL = "
        CREATE TABLE acumulados_por_planilla (
            id INT AUTO_INCREMENT PRIMARY KEY,
            planilla_id INT NOT NULL,
            empleado_id INT NOT NULL,
            concepto_id INT NOT NULL,
            tipo_acumulado_id INT NOT NULL,
            monto_concepto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            factor_acumulacion DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
            monto_acumulado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            periodo_inicio DATE NOT NULL,
            periodo_fin DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_planilla (planilla_id),
            INDEX idx_empleado (empleado_id),
            INDEX idx_tipo_acumulado (tipo_acumulado_id),
            INDEX idx_concepto (concepto_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $db->exec($createTableSQL);
        echo "<p>✅ Tabla <strong>acumulados_por_planilla</strong> creada exitosamente (sin claves foráneas)</p>";
        
        // Intentar agregar claves foráneas una por una
        echo "<h3>🔗 Intentando agregar claves foráneas:</h3>";
        
        // Detectar nombre correcto de tabla concepto/concepts
        $conceptTable = 'concepto';
        $conceptoExists = $db->query("SHOW TABLES LIKE 'concepto'")->fetch();
        $conceptsExists = $db->query("SHOW TABLES LIKE 'concepts'")->fetch();
        
        if ($conceptsExists && !$conceptoExists) {
            $conceptTable = 'concepts';
            echo "<p>ℹ️ Detectado: usando tabla 'concepts' en lugar de 'concepto'</p>";
        }
        
        $foreignKeys = [
            'planilla_id' => ['table' => 'planilla_cabecera', 'column' => 'id'],
            'empleado_id' => ['table' => 'employees', 'column' => 'id'],
            'concepto_id' => ['table' => $conceptTable, 'column' => 'id'],
            'tipo_acumulado_id' => ['table' => 'tipos_acumulados', 'column' => 'id']
        ];
        
        foreach ($foreignKeys as $fkColumn => $reference) {
            try {
                // Verificar que la tabla de referencia existe
                $refTableExists = $db->query("SHOW TABLES LIKE '{$reference['table']}'")->fetch();
                
                if ($refTableExists) {
                    $alterSQL = "ALTER TABLE acumulados_por_planilla 
                                ADD CONSTRAINT fk_acumulados_{$fkColumn} 
                                FOREIGN KEY ({$fkColumn}) REFERENCES {$reference['table']}({$reference['column']}) 
                                ON DELETE CASCADE ON UPDATE CASCADE";
                    
                    $db->exec($alterSQL);
                    echo "<p>✅ FK agregada: {$fkColumn} → {$reference['table']}.{$reference['column']}</p>";
                } else {
                    echo "<p>⚠️ FK omitida: {$fkColumn} → {$reference['table']} (tabla no existe)</p>";
                }
                
            } catch (Exception $fkError) {
                echo "<p>❌ FK fallida: {$fkColumn} → {$reference['table']} (" . $fkError->getMessage() . ")</p>";
            }
        }
        
        // Verificar la creación final
        $newColumns = $db->query("DESCRIBE acumulados_por_planilla")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>✅ Estructura final:</h3>";
        echo "<ul>";
        foreach ($newColumns as $column) {
            echo "<li><strong>{$column['Field']}</strong> ({$column['Type']})</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>🎯 Paso 3: Probar funcionalidad</h2>";
    
    // Probar que las consultas funcionan
    try {
        $testStmt = $db->prepare("SELECT COUNT(*) FROM acumulados_por_planilla WHERE planilla_id = ?");
        $testStmt->execute([999999]); // ID que no existe
        $count = $testStmt->fetchColumn();
        
        echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50; margin: 10px 0;'>";
        echo "<strong>✅ SUCCESS:</strong> La consulta con planilla_id funciona correctamente<br>";
        echo "Registros encontrados para planilla_id 999999: $count (debería ser 0)";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #ffebee; padding: 10px; border: 1px solid #f44336; margin: 10px 0;'>";
        echo "<strong>❌ ERROR:</strong> " . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
    
    echo "<h2>✅ Proceso completado</h2>";
    echo "<p><strong>Siguiente paso:</strong> Probar la funcionalidad de cambio a PENDIENTE en el sistema.</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Error crítico:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
th { background: #f9f9f9; }
</style>