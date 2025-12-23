<?php
/**
 * Cron Job: Sincronización Automática de Asistencias desde API
 *
 * Este script debe ejecutarse periódicamente (recomendado cada 15 minutos)
 * mediante el crontab del sistema o el programador de tareas de Windows
 *
 * Ejemplo crontab (Linux):
 * *\/15 * * * * php /path/to/planilla-innova/scripts/cron/sync_attendance.php >> /path/to/logs/cron_attendance.log 2>&1
 *
 * Ejemplo Task Scheduler (Windows):
 * Acción: Iniciar programa
 * Programa: C:\xampp82\php\php.exe
 * Argumentos: C:\xampp82\htdocs\planilla-innova\scripts\cron\sync_attendance.php
 * Repetir cada: 15 minutos
 */

// Prevenir ejecución desde navegador
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde línea de comandos');
}

// Tiempo de inicio
$startTime = microtime(true);

// Cargar autoload de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar variables de entorno (.env). En producción puede no existir phpdotenv, usar fallback.
if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
} else {
    // Fallback: usar el loader interno de Config para popular $_ENV/putenv
    \App\Core\Config::load();
}

// Inicializar sesión (requerido para algunos modelos)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Core\MasterDatabase;
use App\Core\TenantResolver;
use App\Core\Database;
use App\Models\AttendanceApiConfig;
use App\Services\Attendance\AttendanceSyncService;

// Banner
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   CRON JOB: Sincronización de Asistencias desde API       ║\n";
echo "║   Fecha: " . date('Y-m-d H:i:s') . "                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

/**
 * Cambiar de tenant reseteando la conexión global
 */
function switchTenant(string $dbName): void
{
    TenantResolver::clear();
    Database::resetInstance();
    $_SESSION['tenant_db'] = $dbName;
}

/**
 * Obtener lista de tenants activos desde planilla_master
 */
function getActiveTenants(): array
{
    try {
        $master = MasterDatabase::getInstance()->getConnection();
        $stmt = $master->query("SELECT db_name FROM tenants WHERE status = 'ACTIVE'");
        $tenants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $tenants ?: [];
    } catch (\Exception $e) {
        echo "⚠️  No se pudieron obtener tenants desde planilla_master: {$e->getMessage()}\n";
        return [];
    }
}

$tenants = getActiveTenants();
if (empty($tenants)) {
    // Fallback: usar base por defecto del .env
    $tenants = [$_ENV['DB_NAME'] ?? 'planilla_prod'];
}
$tenants[] = 'planilla_prod';
$tenants = array_values(array_unique(array_filter($tenants))); // limpiar nulos/vacíos y duplicados

$overallExit = 0;
$processedTenants = 0;

foreach ($tenants as $tenantDb) {
    if (empty($tenantDb)) {
        echo "⚠️  Tenant con db_name vacío; se omite.\n";
        continue;
    }
    $processedTenants++;
    echo "\n══════════════════════════════════════════════════════\n";
    echo "▶️  Tenant: {$tenantDb}\n";
    echo "══════════════════════════════════════════════════════\n";

    try {
        switchTenant($tenantDb);

        // Obtener configuración activa
        $configModel = new AttendanceApiConfig();
        $config = $configModel->getActiveConfig();

        if (!$config) {
            echo "⚠️  Tenant {$tenantDb}: No hay configuración activa de API. Saltando...\n";
            continue;
        }

        echo "✓ Configuración encontrada:\n";
        echo "  - Proveedor: {$config['api_provider']}\n";
        echo "  - Sincronización habilitada: " . ($config['sync_enabled'] ? 'Sí' : 'No') . "\n";
        echo "  - Intervalo: {$config['sync_interval_minutes']} minutos\n";
        echo "\n";

        // Verificar si está habilitada
        if (!$config['sync_enabled']) {
            echo "⚠️  Tenant {$tenantDb}: Sincronización automática deshabilitada. Saltando...\n";
            continue;
        }

        // Verificar si debe ejecutarse
        if (!$configModel->shouldSync($config['id'])) {
            $minutesUntilNext = $configModel->getMinutesUntilNextSync($config['id']);
            echo "⏰ Tenant {$tenantDb}: Aún no es tiempo de sincronizar. Próxima ejecución en {$minutesUntilNext} minutos.\n";
            continue;
        }

        echo "🚀 Iniciando sincronización...\n\n";

        // Iniciar sincronización
        $syncService = new AttendanceSyncService($config['id']);
        $stats = $syncService->syncSince(); // Sincronizar solo registros nuevos

        // Mostrar resultados
        echo "✅ Sincronización completada para {$tenantDb}:\n";
        echo "  - Registros obtenidos: {$stats['fetched']}\n";
        echo "  - Registros insertados: {$stats['inserted']}\n";
        echo "  - Registros actualizados: {$stats['updated']}\n";
        echo "  - Registros omitidos: {$stats['skipped']}\n";
        echo "  - Errores: {$stats['errors']}\n";

        // Mostrar errores si existen
        if ($stats['errors'] > 0) {
            $overallExit = 1;
            $errors = $syncService->getErrors();
            echo "\n⚠️  Detalles de errores:\n";
            foreach ($errors as $error) {
                echo "  - {$error}\n";
            }
        }

    } catch (Exception $e) {
        $overallExit = 1;
        echo "\n❌ ERROR en tenant {$tenantDb}:\n";
        echo "  {$e->getMessage()}\n";
        echo "\n  Stack trace:\n";
        echo "  {$e->getTraceAsString()}\n";
    }
}

// Tiempo de ejecución total
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);
echo "\n⏱️  Tiempo de ejecución total: {$executionTime} segundos ({$processedTenants} tenants)\n";
echo "\n" . ($overallExit === 0 ? "✓" : "✗") . " Finalizado con código: {$overallExit}\n";

exit($overallExit);
