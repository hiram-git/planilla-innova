<?php
/**
 * DEBUG CRÍTICO: Verificar estructura real de planilla_detalle
 */
require_once 'app/Core/Database.php';
use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔍 DEBUG CRÍTICO: Estructura planilla_detalle</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>📋 Paso 1: Verificar existencia de tabla planilla_detalle</h2>";
    
    $tableExists = $db->query("SHOW TABLES LIKE 'planilla_detalle'")->fetch();
    
    if (!$tableExists) {
        echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336;'>";
        echo "<h3>❌ ERROR CRÍTICO</h3>";
        echo "<p><strong>La tabla 'planilla_detalle' NO EXISTE.</strong></p>";
        echo "<p>Esta es una tabla fundamental del sistema de planillas.</p>";
        echo "</div>";
        exit;
    }
    
    echo "<p>✅ La tabla planilla_detalle existe</p>";
    
    echo "<h2>📊 Paso 2: Estructura completa de planilla_detalle</h2>";
    
    $columns = $db->query("DESCRIBE planilla_detalle")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasPlanillaId = false;
    $alternativeColumns = [];
    
    foreach ($columns as $column) {
        $fieldName = $column['Field'];
        
        // Verificar si tiene planilla_id
        if ($fieldName === 'planilla_id') {
            $hasPlanillaId = true;
            $bgColor = 'background: #e8f5e8;';
        } else {
            $bgColor = '';
            
            // Buscar columnas que puedan ser alternativas
            if (stripos($fieldName, 'planilla') !== false || stripos($fieldName, 'payroll') !== false) {
                $alternativeColumns[] = $fieldName;
                $bgColor = 'background: #fff3cd;';
            }
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
    
    echo "<h2>🔍 Paso 3: Análisis del problema</h2>";
    
    if ($hasPlanillaId) {
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50;'>";
        echo "<h3>✅ COLUMNA ENCONTRADA</h3>";
        echo "<p><strong>La columna 'planilla_id' SÍ existe en planilla_detalle.</strong></p>";
        echo "<p>El error puede ser por cache de BD o problema de conexión.</p>";
        echo "</div>";
        
        echo "<h3>🧪 Prueba de consulta:</h3>";
        try {
            $testStmt = $db->prepare("SELECT COUNT(*) FROM planilla_detalle WHERE planilla_id = ?");
            $testStmt->execute([999999]);
            $count = $testStmt->fetchColumn();
            
            echo "<p>✅ <strong>CONSULTA EXITOSA:</strong> $count registros encontrados para planilla_id 999999</p>";
            echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb;'>";
            echo "<h4>🤔 Posibles causas del error original:</h4>";
            echo "<ul>";
            echo "<li>Cache de consultas de BD</li>";
            echo "<li>Conexión diferente en el contexto web vs CLI</li>";
            echo "<li>Problema temporal de la BD</li>";
            echo "</ul>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<p>❌ <strong>CONSULTA FALLIDA:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336;'>";
        echo "<h3>❌ PROBLEMA CONFIRMADO</h3>";
        echo "<p><strong>La columna 'planilla_id' NO EXISTE en planilla_detalle.</strong></p>";
        echo "<p>Esto confirma el error que estás viendo.</p>";
        echo "</div>";
        
        if (!empty($alternativeColumns)) {
            echo "<h3>🔍 Columnas alternativas encontradas:</h3>";
            echo "<ul>";
            foreach ($alternativeColumns as $alt) {
                echo "<li><strong>$alt</strong> - Posible alternativa</li>";
            }
            echo "</ul>";
        }
    }
    
    echo "<h2>📊 Paso 4: Información adicional</h2>";
    
    // Mostrar definición completa de la tabla
    try {
        $tableInfo = $db->query("SHOW CREATE TABLE planilla_detalle")->fetch(PDO::FETCH_ASSOC);
        if ($tableInfo) {
            echo "<h4>📋 Definición completa de la tabla:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto; font-size: 11px; border: 1px solid #ddd;'>";
            echo htmlspecialchars($tableInfo['Create Table']);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p>⚠️ No se pudo obtener la definición: " . $e->getMessage() . "</p>";
    }
    
    // Contar registros
    try {
        $count = $db->query("SELECT COUNT(*) FROM planilla_detalle")->fetchColumn();
        echo "<p><strong>Total de registros:</strong> $count</p>";
        
        if ($count > 0) {
            echo "<h4>🔍 Muestra de registros (primeros 3):</h4>";
            $samples = $db->query("SELECT * FROM planilla_detalle LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($samples)) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
                echo "<tr style='background: #f0f0f0;'>";
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
        
    } catch (Exception $e) {
        echo "<p>⚠️ Error al contar registros: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🔧 Paso 5: Soluciones posibles</h2>";
    
    if (!$hasPlanillaId) {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107;'>";
        echo "<h3>🛠️ CORRECCIONES NECESARIAS</h3>";
        
        if (!empty($alternativeColumns)) {
            echo "<p><strong>Opción 1: Usar columna alternativa</strong></p>";
            echo "<p>Modificar el código para usar: <strong>" . $alternativeColumns[0] . "</strong></p>";
            
            echo "<p><strong>Opción 2: Agregar columna planilla_id</strong></p>";
            echo "<p>Ejecutar ALTER TABLE para agregar la columna faltante</p>";
        } else {
            echo "<p><strong>Agregar columna planilla_id a la tabla</strong></p>";
        }
        
        echo "</div>";
        
        echo "<h4>🔧 Scripts de corrección:</h4>";
        echo "<p><a href='fix_planilla_detalle_structure.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>🔧 Corregir planilla_detalle</a></p>";
    } else {
        echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb;'>";
        echo "<h3>🔄 POSIBLES SOLUCIONES</h3>";
        echo "<ul>";
        echo "<li>Reiniciar servidor web (Apache/Nginx)</li>";
        echo "<li>Limpiar cache de BD</li>";
        echo "<li>Verificar que todas las conexiones usan la misma BD</li>";
        echo "<li>Probar desde una nueva sesión del navegador</li>";
        echo "</ul>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Error en diagnóstico:</strong> " . htmlspecialchars($e->getMessage());
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