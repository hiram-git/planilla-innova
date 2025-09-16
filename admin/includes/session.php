<?php
/**
 * Legacy Session Handler - Redirige al sistema MVC
 */
session_start();

// Obtener la configuración de URL base desde variables de entorno
require_once __DIR__ . '/../../app/Core/Config.php';
\App\Core\Config::load();

$appUrl = \App\Core\Config::get('app.url', 'http://localhost');
$parsed = parse_url($appUrl);
$basePath = isset($parsed['path']) ? $parsed['path'] : '';

// Si hay sesión activa, redirigir inmediatamente al dashboard MVC
if(isset($_SESSION['admin']) && trim($_SESSION['admin']) != ''){
	header('Location: ' . $basePath . '/panel/dashboard');
	exit();
}

// Si no hay sesión, redirigir al login MVC
header('Location: ' . $basePath . '/panel/login');
exit();
?>