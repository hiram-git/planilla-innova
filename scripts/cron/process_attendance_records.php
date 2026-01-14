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

use App\Services\Attendance\RecordsProcessor;
use App\Models\AttendanceRecord;

// Banner
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   CRON JOB: Procesamiento de Marcaciones                   ║\n";
echo "║   Fecha: " . date('Y-m-d H:i:s') . "                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    // 1. Verificar si hay registros pendientes de procesar
    $recordModel = new AttendanceRecord();
    $pendingCount = $recordModel->countUnprocessed();

    if ($pendingCount === 0) {
        echo "✓ No hay registros pendientes de procesar.\n";
        echo "⏭️  Saliendo...\n";
        exit(0);
    }

    echo "📊 Registros pendientes: {$pendingCount}\n\n";

    // 2. Obtener rango de fechas con registros pendientes
    $dateRange = $recordModel->getUnprocessedDateRange();

    if (!$dateRange || !isset($dateRange['min_date']) || !isset($dateRange['max_date'])) {
        echo "⚠️  No se pudo determinar el rango de fechas. Saliendo...\n";
        exit(0);
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
    echo "  - Errores: {$stats['errors']}\n";

    // 5. Mostrar errores si existen
    if ($stats['errors'] > 0 && !empty($stats['errors_detail'])) {
        echo "\n⚠️  Detalles de errores:\n";
        foreach ($stats['errors_detail'] as $error) {
            echo "  - {$error}\n";
        }
    }

    // 6. Tiempo de ejecución
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    echo "\n⏱️  Tiempo de ejecución: {$executionTime} segundos\n";

    // 7. Código de salida
    $exitCode = ($stats['errors'] > 0) ? 1 : 0;
    echo "\n" . ($exitCode === 0 ? "✓" : "✗") . " Finalizado con código: {$exitCode}\n";

    exit($exitCode);

} catch (Exception $e) {
    echo "\n❌ ERROR FATAL:\n";
    echo "  {$e->getMessage()}\n";
    echo "\n  Stack trace:\n";
    echo "  {$e->getTraceAsString()}\n";

    // Tiempo de ejecución
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    echo "\n⏱️  Tiempo de ejecución: {$executionTime} segundos\n";

    exit(1);
}
