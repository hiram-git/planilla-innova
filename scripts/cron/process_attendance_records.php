<?php
/**
 * Cron Job: Procesamiento Automático de Marcaciones
 *
 * Este script consolida attendance_records → attendance_detail
 * Debe ejecutarse DESPUÉS de sync_attendance.php
 *
 * Funciones:
 * - Agrupar marcaciones por empleado y fecha
 * - Clasificar entrada/salida/almuerzo según horarios
 * - Crear/actualizar attendance_detail
 * - Marcar records como procesados
 *
 * Ejemplo crontab (Linux) - Cada 20 minutos:
 * *20 * * * * php /path/to/planilla-innova/scripts/cron/process_attendance_records.php >> /path/to/logs/cron_process_records.log 2>&1
 *
 * Ejemplo Task Scheduler (Windows) - Cada 20 minutos:
 * Acción: Iniciar programa
 * Programa: C:\laragon60\bin\php\php.exe
 * Argumentos: C:\laragon60\www\planilla-innova\scripts\cron\process_attendance_records.php
 * Repetir cada: 20 minutos
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

// Fallback adicional: Si las variables críticas no están en $_ENV, establecer defaults
$requiredEnvVars = [
    'DB_HOST' => 'localhost',
    'DB_DATABASE' => 'planilla_prod',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '',
    'DB_PORT' => '3306'
];

foreach ($requiredEnvVars as $key => $default) {
    if (!isset($_ENV[$key]) || empty($_ENV[$key])) {
        // Intentar obtener de getenv()
        $value = getenv($key);
        if ($value === false) {
            // Si no existe, usar default
            $_ENV[$key] = $default;
            putenv("{$key}={$default}");
        } else {
            $_ENV[$key] = $value;
        }
    }
}

// Crear alias para compatibilidad con código que espera DB_NAME y DB_USER
if (!isset($_ENV['DB_NAME']) && isset($_ENV['DB_DATABASE'])) {
    $_ENV['DB_NAME'] = $_ENV['DB_DATABASE'];
    putenv("DB_NAME={$_ENV['DB_DATABASE']}");
}
if (!isset($_ENV['DB_USER']) && isset($_ENV['DB_USERNAME'])) {
    $_ENV['DB_USER'] = $_ENV['DB_USERNAME'];
    putenv("DB_USER={$_ENV['DB_USERNAME']}");
}

// Inicializar sesión (requerido para algunos modelos)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Core\MasterDatabase;
use App\Core\TenantResolver;
use App\Core\Database;
use App\Services\Attendance\RecordsProcessor;
use App\Models\AttendanceRecord;
use App\Models\AttendanceApiConfig;

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

// Banner
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   CRON JOB: Procesamiento de Marcaciones                   ║\n";
echo "║   Fecha: " . date('Y-m-d H:i:s') . "                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$tenants = getActiveTenants();
if (empty($tenants)) {
    $tenants = [$_ENV['DB_NAME'] ?? 'planilla_prod'];
}
// incluir planilla_prod aunque no esté en tenants y limpiar duplicados/nulos
$tenants[] = 'planilla_prod';
$tenants = array_values(array_unique(array_filter($tenants)));

$overallExit = 0;
$processedTenants = 0;

foreach ($tenants as $tenantDb) {
    if (empty($tenantDb)) {
        echo "⚠️  Tenant con db_name vacío; se omite.\n";
        continue;
    }

    $processedTenants++;
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  Tenant: {$tenantDb}                                       ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";

    try {
        switchTenant($tenantDb);

        // ============================================
        // VALIDAR SI LA SINCRONIZACIÓN ESTÁ HABILITADA
        // ============================================
        $configModel = new AttendanceApiConfig();
        $config = $configModel->getActiveConfig();

        if (!$config) {
            echo "⚠️  No hay configuración activa de API. Saltando procesamiento...\n\n";
            continue;
        }

        echo "✓ Configuración encontrada:\n";
        echo "  - Sincronización habilitada: " . ($config['sync_enabled'] ? 'Sí' : 'No') . "\n\n";

        // Verificar si está habilitada
        if (!$config['sync_enabled']) {
            echo "⚠️  Sincronización automática deshabilitada. Saltando procesamiento...\n\n";
            continue;
        }

        // 1. Verificar si hay registros pendientes de procesar
        $recordModel = new AttendanceRecord();
        $pendingCount = $recordModel->countUnprocessed();

        if ($pendingCount === 0) {
            echo "✓ No hay registros pendientes de procesar.\n";
            echo "⏭️  Saltando tenant...\n\n";
            continue;
        }

        echo "📊 Registros pendientes: {$pendingCount}\n\n";

        // 2. Obtener rango de fechas con registros pendientes
        $dateRange = $recordModel->getUnprocessedDateRange();

        if (!$dateRange || !isset($dateRange['min_date']) || !isset($dateRange['max_date'])) {
            echo "⚠️  No se pudo determinar el rango de fechas. Saltando tenant...\n\n";
            continue;
        }

        $minDate = $dateRange['min_date'];
        $maxDate = $dateRange['max_date'];

        echo "📅 Rango de fechas a procesar:\n";
        echo "  - Desde: {$minDate}\n";
        echo "  - Hasta: {$maxDate}\n\n";

        // 3. Iniciar procesamiento
        echo "🚀 Iniciando procesamiento...\n\n";
        $processor = new RecordsProcessor();
        $stats = $processor->processToDetails($minDate, $maxDate);

        // 4. Mostrar resultados
        echo "✅ Procesamiento completado:\n";
        echo "  - Registros procesados: {$stats['total_records']}\n";
        echo "  - Grupos procesados: {$stats['groups_processed']}\n";
        echo "  - Details creados: {$stats['details_created']}\n";
        echo "  - Details actualizados: {$stats['details_updated']}\n";
        echo "  - Details omitidos: {$stats['details_skipped']}\n";
        echo "  - Records marcados: {$stats['records_marked']}\n";
        echo "  - Errores: {$stats['errors']}\n\n";

        // 5. Mostrar errores si existen
        if ($stats['errors'] > 0 && !empty($stats['errors_detail'])) {
            $overallExit = 1;
            echo "⚠️  Detalles de errores:\n";
            foreach ($stats['errors_detail'] as $error) {
                echo "  - {$error}\n";
            }
            echo "\n";
        }

    } catch (Exception $e) {
        $overallExit = 1;
        echo "\n❌ ERROR en tenant {$tenantDb}:\n";
        echo "  {$e->getMessage()}\n";
        echo "\n  Stack trace:\n";
        echo "  {$e->getTraceAsString()}\n\n";
    }
}

// Resumen final
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   PROCESAMIENTO COMPLETADO                                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "⏱️  Tiempo total de ejecución: {$executionTime} segundos ({$processedTenants} tenants)\n";
echo ($overallExit === 0 ? "✓" : "⚠️") . " Proceso finalizado con código {$overallExit}\n\n";

exit($overallExit);
