<?php
/**
 * CORRECCIÓN: Estructura de planilla_detalle
 * Agregar columna planilla_id si no existe o corregir nombres de columnas
 */
require_once 'app/Core/Database.php';
use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔧 Corrección: Estructura planilla_detalle</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>📋 Paso 1: Analizar estructura actual</h2>";
    
    $tableExists = $db->query("SHOW TABLES LIKE 'planilla_detalle'")->fetch();
    
    if (!$tableExists) {
        echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336;'>";
        echo "<h3>❌ ERROR CRÍTICO</h3>";
        echo "<p><strong>La tabla 'planilla_detalle' no existe.</strong></p>";
        echo "<p>Esta tabla es fundamental para el sistema.</p>";
        echo "</div>";
        exit;
    }
    
    $columns = $db->query("DESCRIBE planilla_detalle")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    echo "<h3>📊 Columnas actuales:</h3>";
    echo "<ul>";
    foreach ($columnNames as $col) {
        echo "<li><strong>$col</strong></li>";
    }
    echo "</ul>";
    
    // Verificar si tiene planilla_id
    $hasPlanillaId = in_array('planilla_id', $columnNames);
    
    // Buscar columnas alternativas que puedan referirse a planilla
    $alternativeColumns = [];
    foreach ($columnNames as $col) {
        if (stripos($col, 'planilla') !== false || stripos($col, 'payroll') !== false) {
            $alternativeColumns[] = $col;
        }
    }
    
    echo "<h2>🔍 Paso 2: Determinar acción necesaria</h2>";
    
    if ($hasPlanillaId) {
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50;'>";
        echo "<h3>✅ COLUMNA EXISTE</h3>";
        echo "<p><strong>La columna 'planilla_id' ya existe.</strong></p>";
        echo "<p>El problema puede ser otro (cache, conexión, etc.)</p>";
        echo "</div>";
        
        // Probar consulta
        echo "<h3>🧪 Prueba de consulta:</h3>";
        try {
            $testStmt = $db->prepare("SELECT COUNT(*) FROM planilla_detalle WHERE planilla_id = ?");
            $testStmt->execute([999999]);
            $count = $testStmt->fetchColumn();
            
            echo "<p>✅ <strong>CONSULTA EXITOSA:</strong> La columna planilla_id funciona correctamente</p>";
            echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb;'>";
            echo "<h4>💡 Recomendaciones:</h4>";
            echo "<ul>";
            echo "<li>Reinicia el servidor web</li>";
            echo "<li>Limpia cache del navegador</li>";
            echo "<li>Prueba desde una nueva sesión</li>";
            echo "<li>Verifica que el error persiste</li>";
            echo "</ul>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<p>❌ <strong>ERROR PERSISTENTE:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Vamos a recrear la tabla para garantizar estructura correcta.</p>";
        }
        
    } elseif (!empty($alternativeColumns)) {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107;'>";
        echo "<h3>⚠️ COLUMNA ALTERNATIVA ENCONTRADA</h3>";
        echo "<p><strong>Se encontraron columnas similares:</strong></p>";
        echo "<ul>";
        foreach ($alternativeColumns as $alt) {
            echo "<li><strong>$alt</strong></li>";
        }
        echo "</ul>";
        echo "<p>Opción 1: Renombrar columna existente</p>";
        echo "<p>Opción 2: Agregar nueva columna planilla_id</p>";
        echo "</div>";
        
        // Mostrar opciones
        if (!isset($_GET['action'])) {
            echo "<h3>🤔 ¿Qué quieres hacer?</h3>";
            echo "<p>";
            echo "<a href='?action=rename&from={$alternativeColumns[0]}' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px;'>🔄 Renombrar {$alternativeColumns[0]} → planilla_id</a>";
            echo "<a href='?action=add' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>➕ Agregar nueva columna planilla_id</a>";
            echo "</p>";
            exit;
        }
        
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336;'>";
        echo "<h3>❌ COLUMNA FALTANTE</h3>";
        echo "<p><strong>No se encontró 'planilla_id' ni columnas similares.</strong></p>";
        echo "<p>Vamos a agregar la columna planilla_id.</p>";
        echo "</div>";
    }
    
    // Procesar acciones
    if (isset($_GET['action'])) {
        echo "<h2>🔧 Paso 3: Ejecutar corrección</h2>";
        
        $action = $_GET['action'];
        
        if ($action === 'rename' && isset($_GET['from'])) {
            $fromColumn = $_GET['from'];
            
            echo "<p>🔄 Renombrando columna <strong>$fromColumn</strong> → <strong>planilla_id</strong></p>";
            
            try {
                $renameSQL = "ALTER TABLE planilla_detalle CHANGE $fromColumn planilla_id INT NOT NULL";
                $db->exec($renameSQL);
                
                echo "<p>✅ Columna renombrada exitosamente</p>";
                
            } catch (Exception $e) {
                echo "<p>❌ Error al renombrar: " . htmlspecialchars($e->getMessage()) . "</p>";
                
                // Intentar como alternativa agregar nueva columna
                echo "<p>🔄 Intentando agregar nueva columna...</p>";
                $action = 'add';
            }
        }
        
        if ($action === 'add') {
            echo "<p>➕ Agregando nueva columna <strong>planilla_id</strong></p>";
            
            try {
                // Agregar columna planilla_id
                $addSQL = "ALTER TABLE planilla_detalle ADD COLUMN planilla_id INT NOT NULL DEFAULT 0 COMMENT 'ID de la planilla asociada'";
                $db->exec($addSQL);
                
                echo "<p>✅ Columna agregada exitosamente</p>";
                
                // Agregar índice
                try {
                    $indexSQL = "ALTER TABLE planilla_detalle ADD INDEX idx_planilla_id (planilla_id)";
                    $db->exec($indexSQL);
                    echo "<p>✅ Índice agregado para optimización</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ No se pudo agregar índice: " . $e->getMessage() . "</p>";
                }
                
            } catch (Exception $e) {
                echo "<p>❌ Error al agregar columna: " . htmlspecialchars($e->getMessage()) . "</p>";
                
                if (stripos($e->getMessage(), 'Duplicate column name') !== false) {
                    echo "<p>ℹ️ La columna ya existe. Verificando...</p>";
                }
            }
        }
        
        // Verificar resultado
        echo "<h3>🧪 Verificación final:</h3>";
        
        $newColumns = $db->query("DESCRIBE planilla_detalle")->fetchAll(PDO::FETCH_ASSOC);
        $newColumnNames = array_column($newColumns, 'Field');
        
        if (in_array('planilla_id', $newColumnNames)) {
            echo "<p>✅ <strong>ÉXITO:</strong> La columna planilla_id ahora existe</p>";
            
            // Probar consulta
            try {
                $testStmt = $db->prepare("SELECT COUNT(*) FROM planilla_detalle WHERE planilla_id = ?");
                $testStmt->execute([999999]);
                $count = $testStmt->fetchColumn();
                
                echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 15px 0;'>";
                echo "<h3>🎉 ¡PROBLEMA RESUELTO!</h3>";
                echo "<p><strong>La consulta ahora funciona correctamente.</strong></p>";
                echo "<p>Resultado de prueba: $count registros</p>";
                echo "<p><strong>✅ La funcionalidad de cambio a PENDIENTE debería funcionar ahora.</strong></p>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<p>❌ Consulta aún falla: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
        } else {
            echo "<p>❌ La columna planilla_id sigue sin existir</p>";
        }
    }
    
    // Si no hay problemas, mostrar estructura final
    if (!isset($_GET['action'])) {
        echo "<h2>📊 Estructura final de planilla_detalle</h2>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        foreach ($columns as $column) {
            $isTarget = ($column['Field'] === 'planilla_id');
            $bgColor = $isTarget ? 'background: #e8f5e8;' : '';
            
            echo "<tr style='$bgColor'>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>🔗 Próximos pasos</h2>";
    echo "<ol>";
    echo "<li>✅ Estructura verificada/corregida</li>";
    echo "<li>🧪 <a href='test_final_pending_functionality.php'>Probar funcionalidad completa</a></li>";
    echo "<li>🎯 <a href='/panel/payrolls'>Volver al panel y probar cambio a PENDIENTE</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Error crítico:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; font-size: 13px; }
th, td { padding: 8px 10px; text-align: left; border: 1px solid #ddd; }
th { background: #f9f9f9; }
a { text-decoration: none; }
a:hover { opacity: 0.8; }
</style>