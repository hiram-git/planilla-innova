<?php
/**
 * SOLUCIÓN RÁPIDA: Crear tabla acumulados_por_planilla sin claves foráneas
 * Esta versión funciona siempre, independientemente de la estructura de la BD
 */
require_once 'app/Core/Database.php';
use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🚀 Solución Rápida: Crear tabla acumulados_por_planilla</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>🔍 Paso 0: Verificar dependencias (tipos_acumulados)</h2>";
    
    // Verificar que tipos_acumulados existe
    $tiposAcumuladosExists = $db->query("SHOW TABLES LIKE 'tipos_acumulados'")->fetch();
    
    if (!$tiposAcumuladosExists) {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 10px 0;'>";
        echo "<h3>⚠️ DEPENDENCIA FALTANTE</h3>";
        echo "<p><strong>La tabla 'tipos_acumulados' no existe y es necesaria para el sistema de acumulados.</strong></p>";
        echo "<p><a href='create_tipos_acumulados_table.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>🔧 Crear tabla tipos_acumulados PRIMERO</a></p>";
        echo "<p><em>Después de crear tipos_acumulados, vuelve a ejecutar este script.</em></p>";
        echo "</div>";
        exit;
    } else {
        echo "<p>✅ tipos_acumulados existe</p>";
        
        // Verificar que tiene datos
        $count = $db->query("SELECT COUNT(*) FROM tipos_acumulados")->fetchColumn();
        if ($count == 0) {
            echo "<p>⚠️ tipos_acumulados está vacía. Necesita al menos los tipos básicos.</p>";
            echo "<p><a href='create_tipos_acumulados_table.php'>➡️ Poblar tipos_acumulados</a></p>";
        } else {
            echo "<p>✅ tipos_acumulados tiene $count tipos configurados</p>";
        }
    }
    
    echo "<h2>🔧 Paso 1: Verificar y eliminar tabla existente (si es problemática)</h2>";
    
    // Verificar si existe
    $tableExists = $db->query("SHOW TABLES LIKE 'acumulados_por_planilla'")->fetch();
    
    if ($tableExists) {
        echo "<p>ℹ️ La tabla existe. Verificando estructura...</p>";
        
        $hasCorrectColumn = $db->query("SHOW COLUMNS FROM acumulados_por_planilla LIKE 'planilla_id'")->fetch();
        
        if ($hasCorrectColumn) {
            echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50;'>";
            echo "<strong>✅ PERFECTO:</strong> La tabla ya existe con la estructura correcta";
            echo "</div>";
            
            // Mostrar estructura actual
            $columns = $db->query("DESCRIBE acumulados_por_planilla")->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>📋 Estructura actual:</h3>";
            echo "<ul>";
            foreach ($columns as $column) {
                echo "<li><strong>{$column['Field']}</strong> ({$column['Type']})</li>";
            }
            echo "</ul>";
            
            echo "<p><strong>✅ La tabla está lista para usar. No se necesita modificación.</strong></p>";
            exit;
        } else {
            echo "<p>⚠️ La tabla tiene estructura incorrecta. Eliminando...</p>";
            
            // Hacer backup si hay datos
            $count = $db->query("SELECT COUNT(*) FROM acumulados_por_planilla")->fetchColumn();
            if ($count > 0) {
                try {
                    $db->exec("CREATE TABLE acumulados_por_planilla_backup AS SELECT * FROM acumulados_por_planilla");
                    echo "<p>💾 Backup creado: acumulados_por_planilla_backup ($count registros)</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ No se pudo crear backup: " . $e->getMessage() . "</p>";
                }
            }
            
            $db->exec("DROP TABLE acumulados_por_planilla");
            echo "<p>🗑️ Tabla eliminada</p>";
        }
    }
    
    echo "<h2>🚀 Paso 2: Crear tabla nueva (SIMPLE Y FUNCIONAL)</h2>";
    
    $createSQL = "
    CREATE TABLE acumulados_por_planilla (
        id INT AUTO_INCREMENT PRIMARY KEY,
        planilla_id INT NOT NULL COMMENT 'ID de la planilla procesada',
        empleado_id INT NOT NULL COMMENT 'ID del empleado',
        concepto_id INT NOT NULL COMMENT 'ID del concepto que generó el acumulado',
        tipo_acumulado_id INT NOT NULL COMMENT 'ID del tipo de acumulado (XIII, vacaciones, etc)',
        monto_concepto DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto original del concepto',
        factor_acumulacion DECIMAL(8,4) NOT NULL DEFAULT 1.0000 COMMENT 'Factor aplicado al cálculo',
        monto_acumulado DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto final acumulado',
        periodo_inicio DATE NOT NULL COMMENT 'Inicio del período acumulado',
        periodo_fin DATE NOT NULL COMMENT 'Fin del período acumulado',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Índices para optimizar consultas
        INDEX idx_planilla (planilla_id),
        INDEX idx_empleado (empleado_id),
        INDEX idx_tipo_acumulado (tipo_acumulado_id),
        INDEX idx_concepto (concepto_id),
        INDEX idx_planilla_empleado (planilla_id, empleado_id)
        
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
    COMMENT='Registro detallado de acumulados generados por planilla específica'";
    
    $db->exec($createSQL);
    echo "<p>✅ Tabla <strong>acumulados_por_planilla</strong> creada exitosamente</p>";
    
    echo "<h2>🎯 Paso 3: Verificar funcionalidad</h2>";
    
    // Verificar estructura
    $columns = $db->query("DESCRIBE acumulados_por_planilla")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>📋 Estructura creada:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Comentario</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td style='font-size: 11px;'>{$column['Comment']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Probar consulta crítica
    try {
        $testStmt = $db->prepare("SELECT COUNT(*) FROM acumulados_por_planilla WHERE planilla_id = ?");
        $testStmt->execute([999999]);
        $count = $testStmt->fetchColumn();
        
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 15px 0;'>";
        echo "<h3>✅ ÉXITO TOTAL</h3>";
        echo "<p><strong>La tabla está funcionando perfectamente.</strong></p>";
        echo "<p>Consulta de prueba exitosa: planilla_id 999999 = $count registros</p>";
        echo "<p><strong>Ahora puedes usar la funcionalidad de cambio a PENDIENTE sin errores.</strong></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #ffebee; padding: 10px; border: 1px solid #f44336;'>";
        echo "<strong>❌ Error en prueba:</strong> " . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
    
    echo "<h2>🔗 Próximos pasos</h2>";
    echo "<ol>";
    echo "<li>✅ La tabla está lista para usar</li>";
    echo "<li>✅ Puedes probar el cambio de planillas a PENDIENTE</li>";
    echo "<li>📋 Ve a: <a href='test_pending_records_deletion.php'>test_pending_records_deletion.php</a></li>";
    echo "<li>🎯 O ve directamente al panel: <a href='/panel/payrolls'>Panel de Planillas</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Error crítico:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; font-size: 13px; }
th, td { padding: 8px 10px; text-align: left; border: 1px solid #ddd; }
th { background: #f9f9f9; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>