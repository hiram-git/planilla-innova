<?php
session_start();

// Cargar autoloader
require_once 'vendor/autoload.php';

// Inicializar la aplicación
use App\Core\App;

$app = new App();
$app->run();
?>