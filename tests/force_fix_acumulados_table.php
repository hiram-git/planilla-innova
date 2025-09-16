<?php
/**
 * SOLUCIÓN DE EMERGENCIA: Forzar recreación correcta de acumulados_por_planilla
 * Este script elimina cualquier tabla problemática y crea una nueva garantizada
 */
require_once 'app/Core/Database.php';
use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🚨 SOLUCIÓN DE EMERGENCIA: Forzar recreación de tabla</h1>";
echo "<p><strong>⚠️ ADVERTENCIA:</strong> Este script ELIMINARÁ la tabla acumulados_por_planilla existente y la recreará.</p>";

if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffc107; margin: 20px 0;'>";
    echo "<h3>🤔 ¿Estás seguro?</h3>";
    echo "<p>Este proceso:</p>";
    echo "<ul>";
    echo "<li>❌ Eliminará la tabla acumulados_por_planilla existente (si existe)</li>";
    echo "<li>💾 Intentará hacer backup de los datos existentes</li>";
    echo "<li>✅ Creará una nueva tabla con estructura correcta garantizada</li>";
    echo "</ul>";
    echo "<p><strong>¿Deseas continuar?</strong></p>";
    echo "<p>";
    echo "<a href='?confirm=yes' style='background: #dc3545; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🚨 SÍ, FORZAR RECREACIÓN</a>";
    echo "<a href='verify_acumulados_structure.php' style='background: #6c757d; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px;'>🔍 Mejor verificar primero</a>";
    echo "</p>";
    echo "</div>";
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>🔍 Paso 1: Verificar dependencias</h2>";
    
    // Verificar tipos_acumulados
    $tiposExists = $db->query("SHOW TABLES LIKE 'tipos_acumulados'")->fetch();
    if (!$tiposExists) {
        echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336;'>";
        echo "<h3>❌ ERROR CRÍTICO</h3>";
        echo "<p><strong>La tabla 'tipos_acumulados' no existe.</strong></p>";
        echo "<p>Debes crearla primero antes de proceder.</p>";
        echo "<p><a href='create_tipos_acumulados_table.php'>🔧 Crear tipos_acumulados PRIMERO</a></p>";
        echo "</div>";
        exit;
    } else {
        $tiposCount = $db->query("SELECT COUNT(*) FROM tipos_acumulados")->fetchColumn();
        echo "<p>✅ tipos_acumulados existe con $tiposCount tipos configurados</p>";
    }
    
    echo "<h2>💾 Paso 2: Backup de datos existentes (si existen)</h2>";
    
    $tableExists = $db->query("SHOW TABLES LIKE 'acumulados_por_planilla'")->fetch();
    
    if ($tableExists) {
        echo "<p>ℹ️ La tabla acumulados_por_planilla existe. Haciendo backup...</p>";
        
        try {
            // Contar registros
            $count = $db->query("SELECT COUNT(*) FROM acumulados_por_planilla")->fetchColumn();
            echo "<p>📊 Registros encontrados: $count</p>";
            
            if ($count > 0) {
                // Crear backup con timestamp
                $timestamp = date('Y_m_d_H_i_s');
                $backupTable = "acumulados_por_planilla_backup_$timestamp";
                
                $db->exec("CREATE TABLE $backupTable AS SELECT * FROM acumulados_por_planilla");
                echo "<p>✅ Backup creado: <strong>$backupTable</strong> ($count registros)</p>";
            } else {
                echo "<p>ℹ️ No hay datos para respaldar (tabla vacía)</p>";
            }
            
        } catch (Exception $e) {
            echo "<p>⚠️ Error en backup (continuando): " . $e->getMessage() . "</p>";
        }
        
        echo "<h3>🗑️ Eliminando tabla problemática...</h3>";
        $db->exec("DROP TABLE acumulados_por_planilla");
        echo "<p>✅ Tabla eliminada</p>";
    } else {
        echo "<p>ℹ️ No existe tabla previa para respaldar</p>";
    }
    
    echo "<h2>🔧 Paso 3: Crear tabla nueva (GARANTIZADA)</h2>";
    
    $createSQL = "
    CREATE TABLE acumulados_por_planilla (
        id INT AUTO_INCREMENT PRIMARY KEY,
        planilla_id INT NOT NULL COMMENT 'ID de la planilla que generó este acumulado',
        empleado_id INT NOT NULL COMMENT 'ID del empleado',
        concepto_id INT NOT NULL COMMENT 'ID del concepto origen',
        tipo_acumulado_id INT NOT NULL COMMENT 'Tipo de acumulado (XIII, vacaciones, etc)',
        monto_concepto DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto original del concepto',
        factor_acumulacion DECIMAL(8,4) NOT NULL DEFAULT 1.0000 COMMENT 'Factor aplicado',
        monto_acumulado DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto final acumulado',
        periodo_inicio DATE NOT NULL COMMENT 'Fecha inicio período',
        periodo_fin DATE NOT NULL COMMENT 'Fecha fin período',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Índices para consultas rápidas
        INDEX idx_planilla (planilla_id),
        INDEX idx_empleado (empleado_id),
        INDEX idx_tipo_acumulado (tipo_acumulado_id),
        INDEX idx_concepto (concepto_id),
        INDEX idx_planilla_empleado (planilla_id, empleado_id),
        INDEX idx_empleado_tipo (empleado_id, tipo_acumulado_id)
        
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
    COMMENT='Acumulados detallados por planilla - Legislación Panamá'";
    
    $db->exec($createSQL);
    echo "<p>✅ Tabla <strong>acumulados_por_planilla</strong> creada con éxito</p>";
    
    echo "<h2>🧪 Paso 4: Verificación completa</h2>";
    
    // Verificar estructura
    $columns = $db->query("DESCRIBE acumulados_por_planilla")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    $requiredColumns = ['id', 'planilla_id', 'empleado_id', 'concepto_id', 'tipo_acumulado_id'];
    
    echo "<h3>📋 Verificación de columnas críticas:</h3>";
    $allPresent = true;
    foreach ($requiredColumns as $required) {
        $present = in_array($required, $columnNames);
        $status = $present ? '✅' : '❌';
        $color = $present ? 'green' : 'red';
        echo "<p style='color: $color;'><strong>$status $required:</strong> " . ($present ? 'OK' : 'FALTANTE') . "</p>";
        if (!$present) $allPresent = false;
    }
    
    if (!$allPresent) {
        echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336;'>";
        echo "<h3>❌ ERROR CRÍTICO</h3>";
        echo "<p>La tabla no se creó correctamente. Contacta al desarrollador.</p>";
        echo "</div>";
        exit;
    }
    
    echo "<h3>🧪 Prueba de consulta crítica:</h3>";
    
    try {
        // Probar la consulta que estaba fallando
        $testStmt = $db->prepare("SELECT COUNT(*) FROM acumulados_por_planilla WHERE planilla_id = ?");
        $testStmt->execute([999999]);
        $count = $testStmt->fetchColumn();
        
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 15px 0;'>";
        echo "<h3>🎉 ¡ÉXITO TOTAL!</h3>";
        echo "<p><strong>La consulta problemática ahora funciona perfectamente.</strong></p>";
        echo "<p>Resultado de prueba: $count registros para planilla_id 999999</p>";
        echo "<p><strong>✅ El error 'Column not found: planilla_id' está RESUELTO.</strong></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336;'>";
        echo "<h3>❌ PRUEBA FALLIDA</h3>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
        exit;
    }
    
    echo "<h2>🎯 Paso 5: Resultado final</h2>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Columna</th><th>Tipo</th><th>Key</th><th>Comentario</th></tr>";
    
    foreach ($columns as $column) {
        $isKey = !empty($column['Key']);
        $keyStyle = $isKey ? 'background: #fff3cd;' : '';
        
        echo "<tr style='$keyStyle'>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td style='font-size: 11px;'>{$column['Comment']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>✅ PROCESO COMPLETADO</h2>";
    echo "<div style='background: #e8f5e8; padding: 20px; border: 1px solid #4caf50; margin: 20px 0;'>";
    echo "<h3>🎉 ¡PROBLEMA RESUELTO!</h3>";
    echo "<p><strong>La tabla acumulados_por_planilla está ahora correctamente configurada.</strong></p>";
    echo "<p>Total de columnas creadas: " . count($columns) . "</p>";
    echo "</div>";
    
    echo "<h3>🔗 Próximos pasos:</h3>";
    echo "<ol>";
    echo "<li>✅ <strong>Tabla creada correctamente</strong></li>";
    echo "<li>🧪 <a href='test_pending_records_deletion.php'>Probar funcionalidad de eliminación</a></li>";
    echo "<li>🎯 <a href='/panel/payrolls'>Ir al panel de planillas</a> y probar cambio a PENDIENTE</li>";
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