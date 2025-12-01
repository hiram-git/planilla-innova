<?php
/**
 * Script de diagnóstico temporal para verificar frecuencia de Vacaciones
 * ELIMINAR DESPUÉS DE USAR
 */

require_once __DIR__ . '/app/Core/Database.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();

    // Verificar frecuencias disponibles
    echo "<h2>Frecuencias en la base de datos:</h2>";
    $stmt = $db->query("SELECT id, codigo, nombre FROM frecuencias ORDER BY id");
    $frecuencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Código</th><th>Nombre</th></tr>";
    foreach ($frecuencias as $f) {
        echo "<tr><td>{$f['id']}</td><td>{$f['codigo']}</td><td>{$f['nombre']}</td></tr>";
    }
    echo "</table>";

    echo "<br><hr><br>";

    // Probar la consulta dinámica con UPPER()
    echo "<h2>Prueba de consulta dinámica (UPPER):</h2>";
    $sql_frecuencia = "SELECT id FROM frecuencias WHERE UPPER(codigo) = 'VACACIONES' OR UPPER(nombre) = 'VACACIONES' LIMIT 1";
    echo "<p><strong>SQL:</strong> " . htmlspecialchars($sql_frecuencia) . "</p>";

    $stmt_frecuencia = $db->prepare($sql_frecuencia);
    $stmt_frecuencia->execute();
    $frecuencia = $stmt_frecuencia->fetch(PDO::FETCH_ASSOC);

    if ($frecuencia) {
        echo "<p style='color: green;'><strong>✅ Frecuencia encontrada:</strong> ID = {$frecuencia['id']}</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Frecuencia NO encontrada</strong></p>";
    }

    echo "<br><hr><br>";

    // Verificar planillas de vacaciones existentes
    echo "<h2>Planillas de Vacaciones existentes:</h2>";
    $stmt = $db->query("SELECT id, descripcion, frecuencia_id, estado, fecha
                        FROM planilla_cabecera
                        WHERE descripcion LIKE '%vacacion%'
                        ORDER BY id DESC LIMIT 10");
    $planillas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($planillas) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Descripción</th><th>Frecuencia ID</th><th>Estado</th><th>Fecha</th></tr>";
        foreach ($planillas as $p) {
            $color = $p['frecuencia_id'] == 6 ? 'lightgreen' : 'lightcoral';
            echo "<tr style='background-color: $color;'>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['descripcion']}</td>";
            echo "<td><strong>{$p['frecuencia_id']}</strong></td>";
            echo "<td>{$p['estado']}</td>";
            echo "<td>{$p['fecha']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><small>Verde = frecuencia_id correcto (6), Rojo = frecuencia_id incorrecto</small></p>";
    } else {
        echo "<p>No hay planillas de vacaciones.</p>";
    }

    echo "<br><hr><br>";
    echo "<p><strong>OPcache Status:</strong></p>";
    if (function_exists('opcache_get_status')) {
        $status = opcache_get_status();
        echo "<pre>";
        echo "Enabled: " . ($status['opcache_enabled'] ? 'YES' : 'NO') . "\n";
        echo "Cache Full: " . ($status['cache_full'] ? 'YES' : 'NO') . "\n";
        echo "Restart Pending: " . ($status['restart_pending'] ? 'YES' : 'NO') . "\n";
        echo "</pre>";

        echo "<form method='post'>";
        echo "<button type='submit' name='clear_opcache' style='padding: 10px 20px; background: #d9534f; color: white; border: none; cursor: pointer;'>🔄 Limpiar OPcache</button>";
        echo "</form>";

        if (isset($_POST['clear_opcache'])) {
            if (opcache_reset()) {
                echo "<p style='color: green;'><strong>✅ OPcache limpiado exitosamente!</strong></p>";
                echo "<script>setTimeout(function(){ location.reload(); }, 1000);</script>";
            } else {
                echo "<p style='color: red;'><strong>❌ Error al limpiar OPcache</strong></p>";
            }
        }
    } else {
        echo "<p>OPcache no está disponible</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
