<?php 
session_start();

// Obtener la configuración de URL base desde variables de entorno
require_once __DIR__ . '/app/Core/Config.php';
\App\Core\Config::load();

$appUrl = \App\Core\Config::get('app.url', 'http://localhost');
$parsed = parse_url($appUrl);
$basePath = isset($parsed['path']) ? $parsed['path'] : '';

// Redirigir al sistema moderno usando la configuración
header('Location: ' . $basePath . '/', true, 301);
exit();

/* 
 * ARCHIVO LEGACY DEPRECADO
 * 
 * Este archivo ha sido reemplazado por el sistema MVC moderno.
 * Redirecciona automáticamente usando la configuración dinámica del .env
 * 
 * Para acceder directamente al formulario de marcaciones:
 * URL: {APP_URL}/
 * 
 * Para administración:
 * URL: {APP_URL}/panel
 * 
 * Fecha de deprecación: 27 de Agosto, 2025
 * Actualizado: Septiembre 2025 - URLs dinámicas
 */
?>