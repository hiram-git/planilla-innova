<?php
/**
 * Página de prueba para verificar el dashboard
 */
session_start();

echo "<h1>Prueba de Dashboard</h1>";
echo "<p>Sesión admin: " . (isset($_SESSION['admin']) ? $_SESSION['admin'] : 'No definida') . "</p>";
echo "<p>Nombre admin: " . (isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'No definido') . "</p>";

if (isset($_SESSION['admin'])) {
    echo "<p><strong>Dashboard funcionando correctamente</strong></p>";
    echo '<p><a href="/admin/dashboard">Ir al Dashboard MVC</a></p>';
} else {
    echo "<p>No hay sesión activa</p>";
    echo '<p><a href="/admin/index.php">Ir al Login</a></p>';
}

// Variables de sesión mostradas arriba
?>