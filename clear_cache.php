<?php
/**
 * Script para limpiar OPcache
 * ELIMINAR DESPUÉS DE USAR
 */

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>Limpiar Caché de PHP</h1>";

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color: green; font-size: 20px;'><strong>✅ OPcache limpiado exitosamente!</strong></p>";
        echo "<p>El caché de PHP ha sido limpiado. Ahora los archivos actualizados se usarán.</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Error al limpiar OPcache</strong></p>";
        echo "<p>Intenta reiniciar Apache manualmente.</p>";
    }

    echo "<hr>";
    echo "<h2>Estado de OPcache:</h2>";
    $status = opcache_get_status();
    echo "<pre>";
    echo "Enabled: " . ($status['opcache_enabled'] ? 'YES' : 'NO') . "\n";
    echo "Cache Full: " . ($status['cache_full'] ? 'YES' : 'NO') . "\n";
    echo "Restart Pending: " . ($status['restart_pending'] ? 'YES' : 'NO') . "\n";
    echo "Memory Usage: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB\n";
    echo "Number of Cached Scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
    echo "</pre>";
} else {
    echo "<p style='color: orange;'><strong>⚠️ OPcache no está habilitado</strong></p>";
    echo "<p>No es necesario limpiar caché. Los cambios deberían verse inmediatamente.</p>";
}

echo "<hr>";
echo "<p><a href='/planilla-innova/panel/vacation' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Volver a Vacaciones</a></p>";
echo "<p style='color: red;'><small><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo después de usarlo por seguridad.</small></p>";
?>
