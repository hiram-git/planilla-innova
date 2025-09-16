<?php
$password = "123456"; // La contraseña que quieres hashear
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

echo $hash; // Esto generará una cadena similar a la que proporcionaste
?>
