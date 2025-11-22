<?php
/**
 * Script de prueba para verificar autenticación de tenant
 * Uso: php test_tenant_auth.php
 */

// Configuración manual (sin .env por simplicidad)
$config = [
    'host' => 'localhost',
    'port' => 3306,
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
];

// Test data
$tenantDbName = 'planilla_tenant_e0d35ff378';
$testUsername = 'pruebas3';
$testPassword = 'admin123'; // Cambiar por la contraseña que usaste en el wizard

echo "=== TEST TENANT AUTHENTICATION ===\n\n";

// 1. Verificar conexión a tenant database
try {
    $host = $config['host'];
    $port = $config['port'];
    $user = $config['user'];
    $pass = $config['pass'];
    $charset = $config['charset'];

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $tenantDbName, $charset);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "✅ Conexión a tenant database: OK\n";
    echo "   Database: {$tenantDbName}\n\n";

} catch (Exception $e) {
    echo "❌ Error conectando a tenant database: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Buscar usuario
try {
    $stmt = $pdo->prepare("SELECT id, username, password, firstname, lastname, role_id FROM admin WHERE username = ?");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "❌ Usuario '{$testUsername}' no encontrado\n";
        echo "\nUsuarios disponibles:\n";
        $stmt = $pdo->query("SELECT username, firstname, lastname FROM admin");
        foreach ($stmt->fetchAll() as $u) {
            echo "   - {$u['username']} ({$u['firstname']} {$u['lastname']})\n";
        }
        exit(1);
    }

    echo "✅ Usuario encontrado: {$user['username']}\n";
    echo "   ID: {$user['id']}\n";
    echo "   Nombre: {$user['firstname']} {$user['lastname']}\n";
    echo "   Role ID: {$user['role_id']}\n\n";

} catch (Exception $e) {
    echo "❌ Error buscando usuario: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Verificar contraseña
echo "Verificando contraseña: '{$testPassword}'\n";
if (password_verify($testPassword, $user['password'])) {
    echo "✅ Contraseña correcta\n\n";
} else {
    echo "❌ Contraseña incorrecta\n";
    echo "   Hash en BD: " . substr($user['password'], 0, 30) . "...\n\n";

    echo "Probando contraseñas comunes:\n";
    $commonPasswords = ['admin', 'admin123', 'password', '123456', 'pruebas3'];
    foreach ($commonPasswords as $pwd) {
        if (password_verify($pwd, $user['password'])) {
            echo "   ✅ Contraseña encontrada: '{$pwd}'\n";
            break;
        } else {
            echo "   ❌ No es: '{$pwd}'\n";
        }
    }
    exit(1);
}

// 4. Test TenantResolver
echo "=== TEST TENANT RESOLVER ===\n\n";

// Simular sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Test por RUC
$testRuc = '098765678890';
echo "Resolviendo por RUC: {$testRuc}\n";

try {
    $resolved = \App\Core\TenantResolver::resolveByCompanyCode($testRuc);

    if ($resolved) {
        echo "✅ Tenant resuelto correctamente\n";
        $info = \App\Core\TenantResolver::getTenantInfo();
        echo "   Empresa: {$info['company_name']}\n";
        echo "   Base de datos: {$info['db_name']}\n\n";
    } else {
        echo "❌ No se pudo resolver el tenant\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Error resolviendo tenant: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Test conexión completa
echo "=== TEST FLUJO COMPLETO ===\n\n";

// Reset Database instance para usar tenant
\App\Core\Database::resetInstance();
$db = \App\Core\Database::getInstance();

echo "✅ Database instance reseteada\n";
echo "   Conectada a: " . \App\Core\TenantResolver::getDatabaseName() . "\n\n";

// Intentar autenticar
$adminModel = new \App\Models\Admin();
$authenticated = $adminModel->authenticate($testUsername, $testPassword);

if ($authenticated) {
    echo "✅✅✅ AUTENTICACIÓN EXITOSA ✅✅✅\n";
    echo "   Usuario: {$authenticated['username']}\n";
    echo "   Nombre: {$authenticated['firstname']} {$authenticated['lastname']}\n";
    echo "   Role: " . ($authenticated['role_name'] ?? 'N/A') . "\n";
} else {
    echo "❌ Autenticación falló\n";
    exit(1);
}

echo "\n=== TODOS LOS TESTS PASARON ===\n";
