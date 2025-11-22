<?php
/**
 * Script simple de diagnóstico de autenticación tenant
 * Uso: php test_tenant_simple.php
 */

// Configuración
$config = [
    'host' => 'localhost',
    'port' => 3306,
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
];

// Test data - MODIFICAR ESTOS VALORES
$tenantDbName = 'planilla_tenant_e0d35ff378';
$masterDbName = 'planilla_master';
$testUsername = 'pruebas3';
$testPassword = 'admin123'; // ⚠️ CAMBIAR por la contraseña que usaste en el wizard

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   TEST AUTENTICACIÓN TENANT - DIAGNÓSTICO            ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// ============ TEST 1: Verificar tenant en master ============
echo "📋 TEST 1: Verificar tenant en base master\n";
echo str_repeat("-", 60) . "\n";

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'], $config['port'], $masterDbName, $config['charset']);
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->prepare("SELECT id, company_name, ruc, db_name, status FROM tenants WHERE db_name = ?");
    $stmt->execute([$tenantDbName]);
    $tenant = $stmt->fetch();

    if ($tenant) {
        echo "✅ Tenant encontrado en master\n";
        echo "   ID: {$tenant['id']}\n";
        echo "   Empresa: {$tenant['company_name']}\n";
        echo "   RUC: {$tenant['ruc']}\n";
        echo "   Base de datos: {$tenant['db_name']}\n";
        echo "   Estado: {$tenant['status']}\n\n";

        if ($tenant['status'] !== 'ACTIVE') {
            echo "⚠️  ADVERTENCIA: El tenant no está ACTIVO\n\n";
        }
    } else {
        echo "❌ Tenant NO encontrado en master\n";
        echo "   Base buscada: {$tenantDbName}\n\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Error conectando a master database\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// ============ TEST 2: Verificar base de datos del tenant ============
echo "📋 TEST 2: Verificar base de datos del tenant\n";
echo str_repeat("-", 60) . "\n";

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'], $config['port'], $tenantDbName, $config['charset']);
    $tenantPdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ Conexión exitosa a: {$tenantDbName}\n\n";

} catch (Exception $e) {
    echo "❌ Error conectando a tenant database\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// ============ TEST 3: Listar usuarios disponibles ============
echo "📋 TEST 3: Usuarios disponibles en tenant\n";
echo str_repeat("-", 60) . "\n";

try {
    $stmt = $tenantPdo->query("SELECT id, username, firstname, lastname, role_id, status FROM admin ORDER BY id");
    $users = $stmt->fetchAll();

    if (count($users) === 0) {
        echo "❌ No hay usuarios en la tabla admin\n\n";
        exit(1);
    }

    echo "✅ Usuarios encontrados: " . count($users) . "\n\n";
    foreach ($users as $user) {
        $statusIcon = ($user['status'] ?? 1) == 1 ? '✅' : '❌';
        echo "   {$statusIcon} ID: {$user['id']} | Username: {$user['username']} | Nombre: {$user['firstname']} {$user['lastname']} | Role: {$user['role_id']}\n";
    }
    echo "\n";

} catch (Exception $e) {
    echo "❌ Error obteniendo usuarios\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// ============ TEST 4: Verificar usuario específico ============
echo "📋 TEST 4: Verificar usuario '{$testUsername}'\n";
echo str_repeat("-", 60) . "\n";

try {
    $stmt = $tenantPdo->prepare("SELECT id, username, password, firstname, lastname, role_id, status FROM admin WHERE username = ?");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "❌ Usuario '{$testUsername}' NO encontrado\n";
        echo "   Revisa el listado de usuarios arriba y usa uno de esos\n\n";
        exit(1);
    }

    echo "✅ Usuario encontrado\n";
    echo "   ID: {$user['id']}\n";
    echo "   Username: {$user['username']}\n";
    echo "   Nombre: {$user['firstname']} {$user['lastname']}\n";
    echo "   Role ID: {$user['role_id']}\n";
    echo "   Estado: " . (($user['status'] ?? 1) == 1 ? 'Activo' : 'Inactivo') . "\n";
    echo "   Hash password (inicio): " . substr($user['password'], 0, 20) . "...\n";
    echo "   Longitud hash: " . strlen($user['password']) . " caracteres\n\n";

    if (($user['status'] ?? 1) != 1) {
        echo "⚠️  ADVERTENCIA: El usuario no está activo\n\n";
    }

} catch (Exception $e) {
    echo "❌ Error buscando usuario\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// ============ TEST 5: Verificar contraseña ============
echo "📋 TEST 5: Verificar contraseña\n";
echo str_repeat("-", 60) . "\n";
echo "Probando contraseña: '{$testPassword}'\n";

if (password_verify($testPassword, $user['password'])) {
    echo "✅✅✅ CONTRASEÑA CORRECTA ✅✅✅\n\n";
} else {
    echo "❌ Contraseña INCORRECTA\n\n";

    echo "Probando contraseñas comunes...\n";
    $commonPasswords = [
        'admin',
        'admin123',
        'password',
        '123456',
        'pruebas3',
        'prueba3',
        'Pruebas3',
        'Admin123'
    ];

    $found = false;
    foreach ($commonPasswords as $pwd) {
        if (password_verify($pwd, $user['password'])) {
            echo "   ✅✅✅ CONTRASEÑA ENCONTRADA: '{$pwd}' ✅✅✅\n";
            $found = true;
            break;
        } else {
            echo "   ❌ '{$pwd}'\n";
        }
    }

    if (!$found) {
        echo "\n⚠️  Ninguna contraseña común funcionó.\n";
        echo "   La contraseña que usaste en el wizard es diferente.\n";
    }
    echo "\n";
    exit(1);
}

// ============ TEST 6: Resolver tenant por RUC ============
echo "📋 TEST 6: Resolver tenant por RUC desde master\n";
echo str_repeat("-", 60) . "\n";

try {
    $stmt = $pdo->prepare("
        SELECT id, company_name, ruc, db_name, status
        FROM tenants
        WHERE (ruc = ? OR license_key = ? OR id = ? OR slug = ?)
          AND status = 'ACTIVE'
        LIMIT 1
    ");
    $testCode = $tenant['ruc'];
    $stmt->execute([$testCode, $testCode, $testCode, $testCode]);
    $resolvedTenant = $stmt->fetch();

    if ($resolvedTenant) {
        echo "✅ Tenant resuelto por código '{$testCode}'\n";
        echo "   Empresa: {$resolvedTenant['company_name']}\n";
        echo "   Base de datos: {$resolvedTenant['db_name']}\n\n";
    } else {
        echo "❌ No se pudo resolver tenant por código '{$testCode}'\n\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Error resolviendo tenant\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// ============ RESUMEN FINAL ============
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║             ✅ TODOS LOS TESTS PASARON              ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

echo "📝 DATOS PARA LOGIN:\n";
echo str_repeat("=", 60) . "\n";
echo "URL: http://localhost/planilla-innova/panel/login\n";
echo "Usuario: {$testUsername}\n";
echo "Contraseña: {$testPassword}\n";
echo "Código de Empresa: {$tenant['ruc']}\n";
echo str_repeat("=", 60) . "\n";
