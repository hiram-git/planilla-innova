<?php
/**
 * Script de Instalación de Base de Datos Limpia
 * Sistema de Planillas MVC
 * 
 * Este script instala una base de datos completamente limpia del sistema
 * con datos iniciales necesarios para el funcionamiento básico.
 */

// Configuración de errores para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función para mostrar mensajes con colores
function showMessage($message, $type = 'info') {
    $colors = [
        'info' => '36',      // Cyan
        'success' => '32',   // Green
        'warning' => '33',   // Yellow
        'error' => '31'      // Red
    ];
    
    $color = $colors[$type] ?? '37';
    echo "\033[{$color}m{$message}\033[0m\n";
}

function showStep($step) {
    showMessage("🔄 {$step}", 'info');
}

function showSuccess($message) {
    showMessage("✅ {$message}", 'success');
}

function showError($message) {
    showMessage("❌ {$message}", 'error');
}

function showWarning($message) {
    showMessage("⚠️  {$message}", 'warning');
}

// Header
showMessage("", 'info');
showMessage("╔══════════════════════════════════════════════════════════════╗", 'info');
showMessage("║              🗃️  INSTALADOR BASE DE DATOS LIMPIA              ║", 'info');
showMessage("║                 Sistema de Planillas MVC v2.1                ║", 'info');
showMessage("╚══════════════════════════════════════════════════════════════╝", 'info');
showMessage("", 'info');

try {
    // 1. Verificar archivo .env
    showStep("Verificando configuración del sistema...");
    
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) {
        throw new Exception("Archivo .env no encontrado. Ejecute la instalación del sistema primero.");
    }
    
    // Cargar configuración .env
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, '"');
        $_ENV[$key] = $value;
    }
    
    showSuccess("Configuración cargada exitosamente");
    
    // 2. Configurar conexión a la base de datos
    showStep("Conectando a la base de datos...");
    
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_DATABASE'] ?? 'planilla';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    // Conectar sin especificar base de datos para crearla
    $dsn = "mysql:host={$host};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    showSuccess("Conexión establecida exitosamente");
    
    // 3. Crear base de datos si no existe
    showStep("Creando base de datos '{$dbname}' si no existe...");
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");
    showSuccess("Base de datos seleccionada");
    
    // 4. Verificar esquema SQL
    showStep("Cargando esquema de la base de datos...");
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Archivo schema.sql no encontrado en el directorio database/");
    }
    
    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        throw new Exception("No se pudo leer el archivo schema.sql");
    }
    
    showSuccess("Esquema cargado exitosamente");
    
    // 5. Ejecutar esquema SQL
    showStep("Instalando estructura de la base de datos...");
    
    // Dividir el schema en statements individuales
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) { return !empty($stmt) && !preg_match('/^\s*--/', $stmt); }
    );
    
    $tablesCreated = 0;
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
            if (stripos($statement, 'CREATE TABLE') !== false) {
                $tablesCreated++;
            }
        }
    }
    
    showSuccess("Estructura instalada: {$tablesCreated} tablas creadas");
    
    // 6. Insertar datos iniciales
    showStep("Insertando datos iniciales del sistema...");
    
    // Insertar roles por defecto
    $pdo->exec("
        INSERT IGNORE INTO roles (id, name, description, created_at) VALUES
        (1, 'Super Admin', 'Administrador con acceso completo al sistema', NOW()),
        (2, 'Usuario', 'Usuario estándar con permisos limitados', NOW()),
        (3, 'Solo Lectura', 'Usuario con permisos únicamente de consulta', NOW())
    ");
    
    // Insertar usuario administrador por defecto
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("
        INSERT IGNORE INTO users (id, username, email, password, role_id, active, created_at) VALUES
        (1, 'admin', 'admin@planilla-simple.com', '{$adminPassword}', 1, 1, NOW())
    ");
    
    // Insertar configuración básica de la empresa
    $companyName = $_ENV['APP_NAME'] ?? 'Sistema de Planillas MVC';
    $pdo->exec("
        INSERT IGNORE INTO companies (id, name, nit, address, phone, email, currency_symbol, currency_code, active, created_at) VALUES
        (1, '{$companyName}', '0000000000', 'Dirección de la empresa', '000-0000', 'contacto@empresa.com', 'Q', 'GTQ', 1, NOW())
    ");
    
    // Insertar tipos de conceptos básicos
    $pdo->exec("
        INSERT IGNORE INTO concept_types (id, name, description, created_at) VALUES
        (1, 'Salario', 'Salario base del empleado', NOW()),
        (2, 'Horas Extra', 'Pago por horas adicionales trabajadas', NOW()),
        (3, 'Bono', 'Bonificaciones y incentivos', NOW()),
        (4, 'Deducción', 'Descuentos aplicados al salario', NOW())
    ");
    
    // Insertar conceptos básicos
    $pdo->exec("
        INSERT IGNORE INTO concepts (id, code, name, concept_type_id, is_active, created_at) VALUES
        (1, 'SAL_BASE', 'Salario Base', 1, 1, NOW()),
        (2, 'HRS_EXTRA', 'Horas Extras', 2, 1, NOW()),
        (3, 'BONO_PROD', 'Bono de Productividad', 3, 1, NOW()),
        (4, 'DED_SS', 'Deducción Seguro Social', 4, 1, NOW()),
        (5, 'DED_IR', 'Deducción Impuesto sobre la Renta', 4, 1, NOW())
    ");
    
    // Insertar situaciones laborales
    $pdo->exec("
        INSERT IGNORE INTO employment_situations (id, name, description, is_active, created_at) VALUES
        (1, 'Activo', 'Empleado activo en la empresa', 1, NOW()),
        (2, 'Suspendido', 'Empleado temporalmente suspendido', 1, NOW()),
        (3, 'Vacaciones', 'Empleado en período de vacaciones', 1, NOW()),
        (4, 'Incapacidad', 'Empleado con incapacidad médica', 1, NOW()),
        (5, 'Retirado', 'Ex-empleado retirado de la empresa', 1, NOW())
    ");
    
    // Insertar frecuencias de pago
    $pdo->exec("
        INSERT IGNORE INTO payment_frequencies (id, name, description, days, created_at) VALUES
        (1, 'Quincenal', 'Pago cada 15 días', 15, NOW()),
        (2, 'Mensual', 'Pago mensual', 30, NOW()),
        (3, 'Semanal', 'Pago semanal', 7, NOW())
    ");
    
    // Insertar permisos básicos para el sistema
    $permissions = [
        'panel.dashboard.view' => 'Ver dashboard principal',
        'panel.employees.view' => 'Ver listado de empleados',
        'panel.employees.create' => 'Crear nuevos empleados',
        'panel.employees.edit' => 'Editar empleados existentes',
        'panel.employees.delete' => 'Eliminar empleados',
        'panel.payrolls.view' => 'Ver planillas',
        'panel.payrolls.create' => 'Crear planillas',
        'panel.payrolls.process' => 'Procesar planillas',
        'panel.payrolls.reprocess' => 'Reprocesar planillas',
        'panel.creditors.view' => 'Ver acreedores',
        'panel.creditors.create' => 'Crear acreedores',
        'panel.creditors.edit' => 'Editar acreedores',
        'panel.creditors.delete' => 'Eliminar acreedores',
        'panel.deductions.view' => 'Ver deducciones',
        'panel.deductions.create' => 'Crear deducciones',
        'panel.deductions.edit' => 'Editar deducciones',
        'panel.deductions.delete' => 'Eliminar deducciones',
        'panel.concepts.view' => 'Ver conceptos',
        'panel.concepts.create' => 'Crear conceptos',
        'panel.concepts.edit' => 'Editar conceptos',
        'panel.concepts.delete' => 'Eliminar conceptos',
        'panel.reports.view' => 'Ver reportes',
        'panel.reports.export' => 'Exportar reportes',
        'panel.config.view' => 'Ver configuración',
        'panel.config.edit' => 'Modificar configuración',
        'panel.users.view' => 'Ver usuarios',
        'panel.users.create' => 'Crear usuarios',
        'panel.users.edit' => 'Editar usuarios',
        'panel.users.delete' => 'Eliminar usuarios'
    ];
    
    foreach ($permissions as $permission => $description) {
        $pdo->prepare("INSERT IGNORE INTO permissions (permission, description, created_at) VALUES (?, ?, NOW())")
            ->execute([$permission, $description]);
    }
    
    // Asignar todos los permisos al Super Admin
    $pdo->exec("
        INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
        SELECT 1, id, NOW() FROM permissions
    ");
    
    showSuccess("Datos iniciales insertados exitosamente");
    
    // 7. Crear directorios necesarios
    showStep("Creando directorios del sistema...");
    
    $directories = [
        __DIR__ . '/../storage/uploads',
        __DIR__ . '/../storage/reports',
        __DIR__ . '/../storage/cache',
        __DIR__ . '/../logs/system',
        __DIR__ . '/../logs/php',
        __DIR__ . '/../backups'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    showSuccess("Directorios del sistema creados");
    
    // 8. Verificar instalación
    showStep("Verificando instalación...");
    
    $tablesCheck = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $expectedTables = ['users', 'roles', 'employees', 'payrolls', 'concepts', 'companies'];
    
    $missingTables = array_diff($expectedTables, $tablesCheck);
    if (!empty($missingTables)) {
        throw new Exception("Faltan tablas importantes: " . implode(', ', $missingTables));
    }
    
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        throw new Exception("No se creó el usuario administrador");
    }
    
    showSuccess("Verificación completada exitosamente");
    
    // Instalación completada
    showMessage("", 'success');
    showMessage("╔══════════════════════════════════════════════════════════════╗", 'success');
    showMessage("║              ✅ INSTALACIÓN COMPLETADA                        ║", 'success');
    showMessage("╚══════════════════════════════════════════════════════════════╝", 'success');
    showMessage("", 'success');
    
    showSuccess("🎉 ¡La base de datos se instaló exitosamente!");
    showMessage("", 'info');
    showMessage("📋 INFORMACIÓN DE ACCESO:", 'warning');
    showMessage("• Usuario por defecto: admin", 'info');
    showMessage("• Contraseña por defecto: admin123", 'info');
    showMessage("• Base de datos: {$dbname}", 'info');
    showMessage("• Tablas creadas: " . count($tablesCheck), 'info');
    showMessage("", 'info');
    showMessage("🆕 NOVEDADES VERSIÓN 2.1:", 'success');
    showMessage("• Filtrado por tipo de planilla mejorado", 'info');
    showMessage("• Validación de empleados antes de procesamiento", 'info');
    showMessage("• Gestión de acreedores con validaciones de seguridad", 'info');
    showMessage("• Corrección de errores de JavaScript en formularios", 'info');
    showMessage("", 'info');
    showMessage("⚠️  IMPORTANTE:", 'error');
    showMessage("• Cambie la contraseña por defecto inmediatamente", 'warning');
    showMessage("• Configure los datos de la empresa en el panel", 'warning');
    showMessage("• Revise los permisos de usuarios según sus necesidades", 'warning');
    showMessage("• Verifique la configuración de moneda en configuración empresa", 'warning');
    showMessage("", 'info');
    
} catch (Exception $e) {
    showError("Error durante la instalación: " . $e->getMessage());
    showMessage("", 'warning');
    showMessage("🔧 PASOS PARA RESOLUCIÓN:", 'warning');
    showMessage("1. Verifique que el archivo .env existe y tiene la configuración correcta", 'info');
    showMessage("2. Asegúrese de que MySQL/MariaDB está ejecutándose", 'info');
    showMessage("3. Verifique las credenciales de la base de datos", 'info');
    showMessage("4. Confirme que el usuario de BD tiene permisos para crear databases", 'info');
    showMessage("5. Ejecute el script nuevamente", 'info');
    showMessage("", 'info');
    exit(1);
}

showMessage("🚀 Sistema listo para usar!", 'success');
showMessage("", 'info');
?>