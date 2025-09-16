<?php
/*
 * ARCHIVO LEGACY DEPRECADO - REDIRECCIONAMIENTO
 * 
 * Este archivo ha sido reemplazado por el sistema MVC moderno.
 * Redirecciona a la nueva API de marcaciones usando configuración dinámica.
 * 
 * Fecha de deprecación: 27 de Agosto, 2025
 * Actualizado: Septiembre 2025 - URLs dinámicas
 */

// Obtener configuración dinámica
require_once __DIR__ . '/app/Core/Config.php';
\App\Core\Config::load();

$appUrl = \App\Core\Config::get('app.url', 'http://localhost');
$parsed = parse_url($appUrl);
$basePath = isset($parsed['path']) ? $parsed['path'] : '';

// Redirigir llamadas AJAX al nuevo endpoint
if (isset($_POST['employee'])) {
    // Redirigir POST al nuevo sistema
    $newUrl = $basePath . '/timeclock/punch';
    
    // Redirigir con método POST preservando los datos
    echo '<script>
        fetch("' . $newUrl . '", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "' . http_build_query($_POST) . '"
        })
        .then(response => response.json())
        .then(data => {
            parent.postMessage(data, "*");
        })
        .catch(error => {
            parent.postMessage({error: true, message: "Error de conexión"}, "*");
        });
    </script>';
    exit;
}

// Para requests GET, redirigir a la página principal
header('Location: ' . $basePath . '/', true, 301);
exit;
?>