<?php
/**
 * CREAR TABLA FALTANTE: tipos_acumulados
 * Esta tabla es necesaria para el sistema de acumulados
 */
require_once 'app/Core/Database.php';
use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔧 Crear tabla tipos_acumulados (FALTANTE)</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>📋 Paso 1: Verificar si existe la tabla</h2>";
    
    $tableExists = $db->query("SHOW TABLES LIKE 'tipos_acumulados'")->fetch();
    
    if ($tableExists) {
        echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50;'>";
        echo "<strong>✅ INFO:</strong> La tabla tipos_acumulados ya existe";
        echo "</div>";
        
        // Mostrar estructura y datos
        $columns = $db->query("DESCRIBE tipos_acumulados")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>📊 Estructura actual:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li><strong>{$column['Field']}</strong> ({$column['Type']})</li>";
        }
        echo "</ul>";
        
        $count = $db->query("SELECT COUNT(*) FROM tipos_acumulados")->fetchColumn();
        echo "<p><strong>Registros existentes:</strong> $count</p>";
        
        if ($count > 0) {
            $tipos = $db->query("SELECT * FROM tipos_acumulados ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            echo "<h4>🗂️ Tipos configurados:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Código</th><th>Descripción</th><th>Activo</th></tr>";
            foreach ($tipos as $tipo) {
                $activo = $tipo['activo'] ? '✅ Sí' : '❌ No';
                echo "<tr>";
                echo "<td>{$tipo['id']}</td>";
                echo "<td><strong>{$tipo['codigo']}</strong></td>";
                echo "<td>{$tipo['descripcion']}</td>";
                echo "<td>$activo</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<p><strong>✅ La tabla está lista. Ahora puedes crear acumulados_por_planilla.</strong></p>";
        echo "<p><a href='create_simple_acumulados_table.php'>➡️ Crear tabla acumulados_por_planilla</a></p>";
        exit;
    }
    
    echo "<p>❌ La tabla tipos_acumulados NO existe. Creándola...</p>";
    
    echo "<h2>🔧 Paso 2: Crear tabla tipos_acumulados</h2>";
    
    $createSQL = "
    CREATE TABLE tipos_acumulados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código único del tipo (XIII_MES, VACACIONES, etc)',
        descripcion VARCHAR(200) NOT NULL COMMENT 'Descripción del tipo de acumulado',
        periodicidad ENUM('ANUAL', 'MENSUAL', 'ESPECIAL') NOT NULL DEFAULT 'ANUAL' COMMENT 'Frecuencia del acumulado',
        reinicia_automaticamente TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Si se reinicia cada período',
        activo TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Si está activo para usar',
        observaciones TEXT NULL COMMENT 'Notas adicionales',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_codigo (codigo),
        INDEX idx_activo (activo)
        
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
    COMMENT='Tipos de acumulados para nómina (XIII mes, vacaciones, prima antigüedad, etc)'";
    
    $db->exec($createSQL);
    echo "<p>✅ Tabla <strong>tipos_acumulados</strong> creada exitosamente</p>";
    
    echo "<h2>📋 Paso 3: Insertar tipos básicos de acumulados panameños</h2>";
    
    $tiposBasicos = [
        ['XIII_MES', 'Décimo Tercer Mes (Aguinaldo)', 'ANUAL', 1, 1],
        ['VACACIONES', 'Acumulado de Vacaciones', 'ANUAL', 1, 1],
        ['PRIMA_ANTIGUEDAD', 'Prima de Antigüedad', 'ANUAL', 0, 1],
        ['INDEMNIZACION', 'Indemnización por Despido', 'ESPECIAL', 0, 1],
        ['GASTO_REPRES', 'Gasto de Representación', 'MENSUAL', 1, 1]
    ];
    
    $insertSQL = "INSERT INTO tipos_acumulados (codigo, descripcion, periodicidad, reinicia_automaticamente, activo) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($insertSQL);
    
    foreach ($tiposBasicos as $tipo) {
        $stmt->execute($tipo);
        echo "<p>✅ Insertado: <strong>{$tipo[0]}</strong> - {$tipo[1]}</p>";
    }
    
    echo "<h2>🎯 Paso 4: Verificar resultado final</h2>";
    
    // Verificar estructura
    $columns = $db->query("DESCRIBE tipos_acumulados")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>📊 Estructura creada:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar datos insertados
    $tipos = $db->query("SELECT * FROM tipos_acumulados ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>🗂️ Tipos de acumulados creados:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Código</th><th>Descripción</th><th>Periodicidad</th><th>Activo</th></tr>";
    
    foreach ($tipos as $tipo) {
        $activo = $tipo['activo'] ? '✅ Sí' : '❌ No';
        echo "<tr>";
        echo "<td>{$tipo['id']}</td>";
        echo "<td><strong>{$tipo['codigo']}</strong></td>";
        echo "<td>{$tipo['descripcion']}</td>";
        echo "<td>{$tipo['periodicidad']}</td>";
        echo "<td>$activo</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 15px 0;'>";
    echo "<h3>✅ ÉXITO TOTAL</h3>";
    echo "<p><strong>La tabla tipos_acumulados está creada y poblada con los tipos básicos de acumulados panameños.</strong></p>";
    echo "<p>Total de tipos creados: " . count($tipos) . "</p>";
    echo "</div>";
    
    echo "<h2>🔗 Próximos pasos</h2>";
    echo "<ol>";
    echo "<li>✅ tipos_acumulados creada y poblada</li>";
    echo "<li>➡️ <a href='create_simple_acumulados_table.php'><strong>Crear tabla acumulados_por_planilla</strong></a></li>";
    echo "<li>🧪 <a href='test_pending_records_deletion.php'>Probar funcionalidad de eliminación</a></li>";
    echo "<li>🎯 <a href='/panel/payrolls'>Ir al panel de planillas</a></li>";
    echo "</ol>";
    
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
a { color: #007cba; text-decoration: none; padding: 5px 10px; background: #f0f8ff; border-radius: 3px; }
a:hover { background: #e0f0ff; text-decoration: underline; }
</style>