<?php
/**
 * Procesador de Login Seguro
 * Maneja autenticación con medidas de seguridad
 */
session_start();

// Cargar variables de entorno
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value, '"\'');
    }
}

// Rate limiting simple
$ip = $_SERVER['REMOTE_ADDR'];
$key = 'rate_limit_' . $ip;

if (!isset($_SESSION[$key])) {
    $_SESSION[$key] = [
        'attempts' => 0,
        'last_attempt' => time()
    ];
}

$data = $_SESSION[$key];

// Reset después de 5 minutos
if (time() - $data['last_attempt'] > 300) {
    $_SESSION[$key] = [
        'attempts' => 1,
        'last_attempt' => time()
    ];
} else {
    // Limitar a 5 intentos por 5 minutos
    if ($data['attempts'] >= 5) {
        $_SESSION['error'] = 'Demasiados intentos. Intente más tarde.';
        header('Location: index.php');
        exit();
    }
    $_SESSION[$key]['attempts']++;
    $_SESSION[$key]['last_attempt'] = time();
}

// Validar CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Token de seguridad inválido';
    header('Location: index.php');
    exit();
}

// Validar datos de entrada
if (!isset($_POST['login']) || empty($_POST['username']) || empty($_POST['password'])) {
    $_SESSION['error'] = 'Usuario y contraseña requeridos';
    header('Location: index.php');
    exit();
}

// Sanitizar entrada
$username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
$password = $_POST['password'];

// Conectar a la base de datos
include 'includes/conn.php';

try {
    // Usar prepared statements para prevenir SQL injection
    $sql = "SELECT * FROM admin WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows < 1) {
        $_SESSION['error'] = 'Credenciales no encontradas';
        
        // Log del intento fallido
        error_log("Login fallido - Usuario no encontrado: $username - IP: $ip");
    } else {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Login exitoso
            $_SESSION['admin'] = $user['id'];
            $_SESSION['admin_name'] = trim($user['firstname'] . ' ' . $user['lastname']);
            
            // Reset rate limiting en login exitoso
            unset($_SESSION[$key]);
            
            // Log del login exitoso
            error_log("Login exitoso - Usuario: $username - ID: {$user['id']} - IP: $ip");
            
            // Regenerar session ID para prevenir session fixation
            session_regenerate_id(true);
            
            // Redirigir al dashboard MVC usando configuración dinámica
            $appUrl = \App\Core\Config::get('app.url', 'http://localhost');
            $parsed = parse_url($appUrl);
            $basePath = isset($parsed['path']) ? $parsed['path'] : '';
            
            header('Location: ' . $basePath . '/admin/dashboard');
            exit();
        } else {
            $_SESSION['error'] = 'Credenciales incorrectas';
            
            // Log del intento fallido
            error_log("Login fallido - Contraseña incorrecta: $username - IP: $ip");
        }
    }
    
    $stmt->close();
} catch (Exception $e) {
    $_SESSION['error'] = 'Error del sistema. Intente más tarde.';
    error_log("Error en login_process.php: " . $e->getMessage());
}

$conn->close();

// Redirigir de vuelta al login en caso de error
header('Location: index.php');
exit();
?>