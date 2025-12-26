<?php
/**
 * Script de prueba para verificar configuración de cron jobs
 *
 * Ejecutar manualmente para verificar que todo esté configurado correctamente:
 * php /var/www/html/planilla/scripts/cron/test_cron_setup.php
 */

// Prevenir ejecución desde navegador
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde línea de comandos');
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   TEST: Verificación de Configuración Cron Jobs               ║\n";
echo "║   Fecha: " . date('Y-m-d H:i:s') . "                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$errors = [];
$warnings = [];
$success = [];

// ============================================================================
// TEST 1: Verificar directorio de trabajo
// ============================================================================
echo "TEST 1: Verificando directorio de trabajo...\n";
$cwd = getcwd();
echo "  📁 Directorio actual: {$cwd}\n";

$expectedDirs = [
    'app',
    'config',
    'scripts',
    'vendor'
];

foreach ($expectedDirs as $dir) {
    if (is_dir($cwd . '/' . $dir)) {
        $success[] = "Directorio '{$dir}' encontrado";
        echo "  ✓ {$dir}/ existe\n";
    } else {
        $errors[] = "Directorio '{$dir}' NO encontrado. Asegúrate de ejecutar el script desde el root del proyecto";
        echo "  ❌ {$dir}/ NO existe\n";
    }
}

// ============================================================================
// TEST 2: Verificar autoload de Composer
// ============================================================================
echo "\nTEST 2: Verificando autoload de Composer...\n";
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    $success[] = "Autoload de Composer cargado correctamente";
    echo "  ✓ vendor/autoload.php existe\n";
} else {
    $errors[] = "vendor/autoload.php NO encontrado. Ejecutar: composer install";
    echo "  ❌ vendor/autoload.php NO existe\n";
    die("  🛑 No se puede continuar sin autoload. Ejecutar: composer install\n\n");
}

// ============================================================================
// TEST 3: Verificar archivos de configuración
// ============================================================================
echo "\nTEST 3: Verificando archivos de configuración...\n";
$configFiles = [
    'config/database.php',
    'config/app.php',
    'config/master_database.php',
    '.env'
];

foreach ($configFiles as $file) {
    $fullPath = __DIR__ . '/../../' . $file;
    if (file_exists($fullPath)) {
        $success[] = "Archivo '{$file}' encontrado";
        echo "  ✓ {$file} existe\n";
    } else {
        if ($file === '.env') {
            $warnings[] = "Archivo .env NO encontrado. Se usarán variables de entorno del sistema";
            echo "  ⚠️  {$file} NO existe (se usarán variables del sistema)\n";
        } else {
            $errors[] = "Archivo '{$file}' NO encontrado";
            echo "  ❌ {$file} NO existe\n";
        }
    }
}

// ============================================================================
// TEST 4: Cargar variables de entorno
// ============================================================================
echo "\nTEST 4: Cargando variables de entorno...\n";
if (class_exists(\Dotenv\Dotenv::class)) {
    try {
        \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
        $success[] = "Variables de entorno cargadas con Dotenv";
        echo "  ✓ Dotenv cargado\n";
    } catch (Exception $e) {
        $warnings[] = "No se pudo cargar .env: " . $e->getMessage();
        echo "  ⚠️  Error cargando .env: {$e->getMessage()}\n";
    }
} else {
    \App\Core\Config::load();
    $success[] = "Variables de entorno cargadas con Config::load()";
    echo "  ✓ Config::load() ejecutado\n";
}

// Verificar variables críticas
$envVars = [
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'DB_PASSWORD'
];

foreach ($envVars as $var) {
    $value = $_ENV[$var] ?? getenv($var);
    if ($value !== false && !empty($value)) {
        $masked = ($var === 'DB_PASSWORD') ? str_repeat('*', strlen($value)) : $value;
        echo "  ✓ {$var} = {$masked}\n";
    } else {
        $errors[] = "Variable de entorno '{$var}' no está definida";
        echo "  ❌ {$var} NO definida\n";
    }
}

// ============================================================================
// TEST 5: Verificar conexión a base de datos master
// ============================================================================
echo "\nTEST 5: Verificando conexión a base de datos MASTER...\n";
try {
    $master = \App\Core\MasterDatabase::getInstance()->getConnection();
    $stmt = $master->query("SELECT COUNT(*) as count FROM tenants");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $success[] = "Conexión a planilla_master exitosa. Tenants encontrados: {$result['count']}";
    echo "  ✓ Conexión a planilla_master exitosa\n";
    echo "  ✓ Tenants encontrados: {$result['count']}\n";
} catch (Exception $e) {
    $warnings[] = "No se pudo conectar a planilla_master: " . $e->getMessage();
    echo "  ⚠️  Error conectando a planilla_master: {$e->getMessage()}\n";
    echo "  ℹ️  Esto es normal si no usas multitenancy\n";
}

// ============================================================================
// TEST 6: Verificar conexión a base de datos default
// ============================================================================
echo "\nTEST 6: Verificando conexión a base de datos DEFAULT...\n";

// Inicializar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurar tenant default
$_SESSION['tenant_db'] = $_ENV['DB_NAME'] ?? 'planilla_prod';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $success[] = "Conexión a BD default exitosa. Empleados encontrados: {$result['count']}";
    echo "  ✓ Conexión a BD default exitosa\n";
    echo "  ✓ Empleados encontrados: {$result['count']}\n";
} catch (Exception $e) {
    $errors[] = "No se pudo conectar a BD default: " . $e->getMessage();
    echo "  ❌ Error conectando a BD default: {$e->getMessage()}\n";
}

// ============================================================================
// TEST 7: Verificar clases de servicios
// ============================================================================
echo "\nTEST 7: Verificando clases de servicios de asistencias...\n";
$services = [
    'App\\Services\\Attendance\\AttendanceSyncService',
    'App\\Services\\Attendance\\RecordsProcessor',
    'App\\Services\\Attendance\\AttendanceCalculator',
    'App\\Services\\Attendance\\AbsenceDetector'
];

foreach ($services as $service) {
    if (class_exists($service)) {
        $success[] = "Clase '{$service}' existe";
        echo "  ✓ " . basename(str_replace('\\', '/', $service)) . " disponible\n";
    } else {
        $errors[] = "Clase '{$service}' NO existe";
        echo "  ❌ " . basename(str_replace('\\', '/', $service)) . " NO disponible\n";
    }
}

// ============================================================================
// TEST 8: Verificar modelos
// ============================================================================
echo "\nTEST 8: Verificando modelos de asistencias...\n";
$models = [
    'App\\Models\\AttendanceHeader',
    'App\\Models\\AttendanceDetail',
    'App\\Models\\AttendanceApiConfig',
    'App\\Models\\Employee',
    'App\\Models\\BusinessCalendar'
];

foreach ($models as $model) {
    if (class_exists($model)) {
        $success[] = "Modelo '{$model}' existe";
        echo "  ✓ " . basename(str_replace('\\', '/', $model)) . " disponible\n";
    } else {
        $errors[] = "Modelo '{$model}' NO existe";
        echo "  ❌ " . basename(str_replace('\\', '/', $model)) . " NO disponible\n";
    }
}

// ============================================================================
// TEST 9: Verificar permisos de logs
// ============================================================================
echo "\nTEST 9: Verificando directorio de logs...\n";
$logDir = '/var/log/planilla';
if (is_dir($logDir)) {
    echo "  ✓ {$logDir} existe\n";

    if (is_writable($logDir)) {
        $success[] = "Directorio de logs es escribible";
        echo "  ✓ {$logDir} es escribible\n";
    } else {
        $warnings[] = "Directorio de logs NO es escribible. Ejecutar: sudo chown www-data:www-data {$logDir}";
        echo "  ⚠️  {$logDir} NO es escribible\n";
    }
} else {
    $warnings[] = "Directorio de logs NO existe. Ejecutar: sudo mkdir -p {$logDir} && sudo chown www-data:www-data {$logDir}";
    echo "  ⚠️  {$logDir} NO existe\n";
}

// ============================================================================
// TEST 10: Verificar scripts cron
// ============================================================================
echo "\nTEST 10: Verificando scripts de cron...\n";
$cronScripts = [
    'scripts/cron/sync_attendance.php',
    'scripts/cron/end_of_day_processing.php',
    'scripts/cron/process_attendance_pipeline.php'
];

foreach ($cronScripts as $script) {
    $fullPath = __DIR__ . '/../../' . $script;
    if (file_exists($fullPath)) {
        if (is_executable($fullPath)) {
            $success[] = "Script '{$script}' existe y es ejecutable";
            echo "  ✓ {$script} (ejecutable)\n";
        } else {
            $warnings[] = "Script '{$script}' existe pero NO es ejecutable. Ejecutar: chmod +x {$fullPath}";
            echo "  ⚠️  {$script} (NO ejecutable)\n";
        }
    } else {
        $errors[] = "Script '{$script}' NO existe";
        echo "  ❌ {$script} NO existe\n";
    }
}

// ============================================================================
// RESUMEN FINAL
// ============================================================================
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                      RESUMEN DE PRUEBAS                        ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";
echo "║  ✅ Éxitos:      " . str_pad(count($success), 3) . "                                         ║\n";
echo "║  ⚠️  Advertencias: " . str_pad(count($warnings), 3) . "                                         ║\n";
echo "║  ❌ Errores:     " . str_pad(count($errors), 3) . "                                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

if (!empty($warnings)) {
    echo "\n⚠️  ADVERTENCIAS:\n";
    foreach ($warnings as $i => $warning) {
        echo "  " . ($i + 1) . ". {$warning}\n";
    }
}

if (!empty($errors)) {
    echo "\n❌ ERRORES CRÍTICOS:\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". {$error}\n";
    }
    echo "\n🛑 Se encontraron errores críticos. Corregir antes de usar cron jobs.\n";
    exit(1);
} else {
    echo "\n✅ TODAS LAS PRUEBAS PASARON EXITOSAMENTE\n";
    echo "✅ El sistema está listo para ejecutar cron jobs\n";
    echo "\n📋 Próximos pasos:\n";
    echo "  1. Configurar crontab con: crontab -e\n";
    echo "  2. Monitorear logs en: /var/log/planilla/\n";
    echo "  3. Ejecutar manualmente un script para probar:\n";
    echo "     php /var/www/html/planilla/scripts/cron/sync_attendance.php\n";
    exit(0);
}
