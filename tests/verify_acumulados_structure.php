<?php
/**
 * VERIFICACIÓN COMPLETA: Estado real de la tabla acumulados_por_planilla
 */
require_once 'app/Core/Database.php';
use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔍 Verificación Completa: acumulados_por_planilla</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>📋 Paso 1: Verificar existencia de tabla</h2>";
    
    $tableExists = $db->query("SHOW TABLES LIKE 'acumulados_por_planilla'")->fetch();
    
    if (!$tableExists) {
        echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336;'>";
        echo "<h3>❌ PROBLEMA ENCONTRADO</h3>";
        echo "<p><strong>La tabla 'acumulados_por_planilla' NO EXISTE.</strong></p>";
        echo "<p>Esto explica el error de columna no encontrada.</p>";
        echo "</div>";
        
        echo "<h3>🔧 Solución inmediata:</h3>";
        echo "<p><a href='create_simple_acumulados_table.php' style='background: #28a745; color: white; padding: 15px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>▶️ CREAR TABLA AHORA</a></p>";
        exit;
    }
    
    echo "<p>✅ La tabla acumulados_por_planilla existe</p>";
    
    echo "<h2>📊 Paso 2: Verificar estructura completa</h2>";
    
    $columns = $db->query("DESCRIBE acumulados_por_planilla")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasRequiredColumns = [
        'id' => false,
        'planilla_id' => false,
        'empleado_id' => false,
        'concepto_id' => false,
        'tipo_acumulado_id' => false
    ];
    
    foreach ($columns as $column) {
        $fieldName = $column['Field'];
        $isRequired = isset($hasRequiredColumns[$fieldName]);
        $bgColor = $isRequired ? 'background: #e8f5e8;' : '';
        
        if ($isRequired) {
            $hasRequiredColumns[$fieldName] = true;
        }
        
        echo "<tr style='$bgColor'>";
        echo "<td><strong>$fieldName</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🔍 Verificación de columnas críticas:</h3>";
    
    $allRequiredPresent = true;
    foreach ($hasRequiredColumns as $column => $present) {
        $status = $present ? '✅' : '❌';
        $color = $present ? 'green' : 'red';
        echo "<p style='color: $color;'><strong>$status $column:</strong> " . ($present ? 'PRESENTE' : 'FALTANTE') . "</p>";
        
        if (!$present) {
            $allRequiredPresent = false;
        }
    }
    
    if (!$allRequiredPresent) {
        echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336; margin: 15px 0;'>";
        echo "<h3>❌ ESTRUCTURA INCORRECTA</h3>";
        echo "<p><strong>La tabla existe pero le faltan columnas críticas.</strong></p>";
        echo "<p>Esto explica el error 'Column not found: planilla_id'</p>";
        echo "</div>";
        
        echo "<h3>🔧 Soluciones disponibles:</h3>";
        echo "<ol>";
        echo "<li><strong>Recrear tabla:</strong> <a href='create_simple_acumulados_table.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>🔄 Recrear Tabla</a></li>";
        echo "<li><strong>Diagnóstico FK:</strong> <a href='debug_foreign_keys.php'>🔍 Ver Diagnóstico Completo</a></li>";
        echo "</ol>";
        
    } else {
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 15px 0;'>";
        echo "<h3>✅ ESTRUCTURA CORRECTA</h3>";
        echo "<p><strong>Todas las columnas críticas están presentes.</strong></p>";
        echo "</div>";
        
        echo "<h2>🧪 Paso 3: Probar consulta problemática</h2>";
        
        try {
            $testStmt = $db->prepare("SELECT COUNT(*) FROM acumulados_por_planilla WHERE planilla_id = ?");
            $testStmt->execute([999999]);
            $count = $testStmt->fetchColumn();
            
            echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50;'>";
            echo "<strong>✅ PRUEBA EXITOSA:</strong> La consulta funciona correctamente<br>";
            echo "Resultado: $count registros para planilla_id 999999";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='background: #ffebee; padding: 10px; border: 1px solid #f44336;'>";
            echo "<strong>❌ PRUEBA FALLIDA:</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
            
            echo "<h4>🔧 El problema persiste. Opciones:</h4>";
            echo "<ol>";
            echo "<li><a href='create_simple_acumulados_table.php'>🔄 Forzar recreación de tabla</a></li>";
            echo "<li>Verificar que no hay conflictos de cache de BD</li>";
            echo "</ol>";
        }
    }
    
    echo "<h2>📊 Paso 4: Información adicional</h2>";
    
    // Mostrar información de la tabla
    try {
        $tableInfo = $db->query("SHOW CREATE TABLE acumulados_por_planilla")->fetch(PDO::FETCH_ASSOC);
        if ($tableInfo) {
            echo "<h4>📋 Definición completa de la tabla:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto; font-size: 11px; border: 1px solid #ddd;'>";
            echo htmlspecialchars($tableInfo['Create Table']);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p>⚠️ No se pudo obtener la definición completa: " . $e->getMessage() . "</p>";
    }
    
    // Contar registros
    try {
        $count = $db->query("SELECT COUNT(*) FROM acumulados_por_planilla")->fetchColumn();
        echo "<p><strong>Total de registros en la tabla:</strong> $count</p>";
    } catch (Exception $e) {
        echo "<p>⚠️ No se pudo contar registros: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Error en verificación:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; font-size: 13px; }
th, td { padding: 8px 10px; text-align: left; border: 1px solid #ddd; }
th { background: #f9f9f9; }
pre { white-space: pre-wrap; word-wrap: break-word; }
a { text-decoration: none; }
a:hover { opacity: 0.8; }
</style>