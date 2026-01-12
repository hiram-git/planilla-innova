<?php
/**
 * Script Manual: Sincronización de Asistencias por Rango de Fechas
 *
 * Permite sincronizar marcaciones desde la API externa para un rango de fechas específico
 *
 * Uso:
 * php manual_sync_range.php [fecha_inicio] [fecha_fin]
 *
 * Ejemplos:
 * php manual_sync_range.php 2026-01-07 2026-01-12  # Rango específico
 * php manual_sync_range.php 5                       # Últimos 5 días
 * php manual_sync_range.php                         # Últimos 7 días (default)
 */

// Prevenir ejecución desde navegador
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde línea de comandos');
}

// Tiempo de inicio
$startTime = microtime(true);

// Cargar autoload de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar variables de entorno
if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
} else {
    \App\Core\Config::load();
}

// Inicializar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Core\MasterDatabase;
use App\Core\TenantResolver;
use App\Core\Database;
use App\Models\AttendanceApiConfig;
use App\Services\Attendance\AttendanceSyncService;
use App\Services\Attendance\RecordsProcessor;

// Banner
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   SINCRONIZACIÓN MANUAL DE ASISTENCIAS POR RANGO          ║\n";
echo "║   Fecha: " . date('Y-m-d H:i:s') . "                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Procesar argumentos
$argc = $GLOBALS['argc'] ?? 1;
$argv = $GLOBALS['argv'] ?? [];

if ($argc === 2 && is_numeric($argv[1])) {
    // Formato: php script.php 5 (últimos N días)
    $days = (int)$argv[1];
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    $endDate = date('Y-m-d');
} elseif ($argc === 3) {
    // Formato: php script.php 2026-01-07 2026-01-12
    $startDate = $argv[1];
    $endDate = $argv[2];
} else {
    // Default: últimos 7 días
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');
}

// Validar fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    echo "❌ Error: Formato de fecha inválido. Use YYYY-MM-DD\n";
    exit(1);
}

if (strtotime($startDate) > strtotime($endDate)) {
    echo "❌ Error: La fecha de inicio no puede ser mayor a la fecha final\n";
    exit(1);
}

echo "📅 Rango de sincronización: {$startDate} → {$endDate}\n\n";

/**
 * Cambiar de tenant
 */
function switchTenant(string $dbName): void
{
    TenantResolver::clear();
    Database::resetInstance();
    $_SESSION['tenant_db'] = $dbName;
}

/**
 * Obtener tenants activos
 */
function getActiveTenants(): array
{
    try {
        $master = MasterDatabase::getInstance()->getConnection();
        $stmt = $master->query("SELECT db_name FROM tenants WHERE status = 'ACTIVE'");
        $tenants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $tenants ?: [];
    } catch (\Exception $e) {
        echo "⚠️  No se pudieron obtener tenants: {$e->getMessage()}\n";
        return [];
    }
}

$tenants = getActiveTenants();
if (empty($tenants)) {
    $tenants = [$_ENV['DB_NAME'] ?? 'planilla_prod'];
}
$tenants[] = 'planilla_prod';
$tenants = array_values(array_unique(array_filter($tenants)));

$overallExit = 0;
$processedTenants = 0;

foreach ($tenants as $tenantDb) {
    if (empty($tenantDb)) {
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
            echo "⚠️  No hay configuración activa de API. Saltando...\n";
            continue;
        }

        echo "✓ Configuración encontrada: {$config['api_provider']}\n\n";

        // Sincronizar rango completo
        echo "🚀 Iniciando sincronización para rango: {$startDate} → {$endDate}\n\n";

        $syncService = new AttendanceSyncService($config['id']);
        $stats = $syncService->syncByDateRange($startDate, $endDate);

        echo "✅ Sincronización completada:\n";
        echo "  - Registros obtenidos: {$stats['fetched']}\n";
        echo "  - Registros insertados: {$stats['inserted']}\n";
        echo "  - Registros actualizados: {$stats['updated']}\n";
        echo "  - Registros omitidos: {$stats['skipped']}\n";
        echo "  - Errores: {$stats['errors']}\n";

        if ($stats['errors'] > 0) {
            $overallExit = 1;
            $errors = $syncService->getErrors();
            echo "\n⚠️  Detalles de errores:\n";
            foreach ($errors as $error) {
                echo "  - {$error}\n";
            }
        }

        // Procesar a attendance_detail
        echo "\n🔄 Procesando marcaciones a attendance_detail...\n";

        $processor = new RecordsProcessor();
        $processStats = $processor->processToDetails($startDate, $endDate);

        echo "✅ Procesamiento completado:\n";
        echo "  - Grupos procesados: {$processStats['groups_processed']}\n";
        echo "  - Detalles creados: {$processStats['details_created']}\n";
        echo "  - Detalles actualizados: {$processStats['details_updated']}\n";
        echo "  - Errores: {$processStats['errors']}\n";

        if ($processStats['errors'] > 0) {
            $overallExit = 1;
        }

    } catch (Exception $e) {
        $overallExit = 1;
        echo "\n❌ ERROR en tenant {$tenantDb}:\n";
        echo "  {$e->getMessage()}\n";
    }
}

// Resumen final
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║   SINCRONIZACIÓN COMPLETADA                                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "⏱️  Tiempo total: {$executionTime} segundos ({$processedTenants} tenants)\n";
echo ($overallExit === 0 ? "✓" : "✗") . " Finalizado con código: {$overallExit}\n\n";

exit($overallExit);
