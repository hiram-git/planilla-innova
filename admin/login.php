<?php
/**
 * Legacy Login Redirect
 * Redirige todas las peticiones de login al sistema MVC seguro
 */
session_start();

// Obtener la configuración de URL base desde variables de entorno
require_once __DIR__ . '/../app/Core/Config.php';
\App\Core\Config::load();

$appUrl = \App\Core\Config::get('app.url', 'http://localhost');
$parsed = parse_url($appUrl);
$basePath = isset($parsed['path']) ? $parsed['path'] : '';

// Redirigir al controlador MVC para procesamiento seguro del login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Redirigir POST al sistema MVC
    header('Location: ' . $basePath . '/admin/login', true, 307); // 307 mantiene el método POST
    exit();
} else {
    // Redirigir GET al login
    header('Location: ' . $basePath . '/admin');
    exit();
}
?>