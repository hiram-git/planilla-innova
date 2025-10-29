<?php
/**
 * Script para ejecutar sincronización de attendance
 * Fecha: 28 de Octubre, 2025
 */

// Cargar autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar .env
if (file_exists(__DIR__ . '/../../.env')) {
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();
    } else {
        // Fallback manual
        $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                putenv(trim($name) . '=' . trim($value));
                $_ENV[trim($name)] = trim($value);
                $_SERVER[trim($name)] = trim($value);
            }
        }
    }
}

// Override DB_NAME if needed
if (!getenv('DB_NAME') || getenv('DB_NAME') === 'planilla_innova') {
    putenv('DB_NAME=planilla_prod');
    $_ENV['DB_NAME'] = 'planilla_prod';
    $_SERVER['DB_NAME'] = 'planilla_prod';
}

echo "========================================\n";
echo "SINCRONIZACIÓN DE ATTENDANCE\n";
echo "========================================\n\n";

try {
    // Importar servicio de sincronización
    require_once __DIR__ . '/../../app/Services/Attendance/AttendanceSyncService.php';

    echo "🔄 Iniciando sincronización...\n\n";

    $syncService = new \App\Services\Attendance\AttendanceSyncService();

    // Ejecutar sincronización completa
    $stats = $syncService->syncAll();

    echo "\n========================================\n";
    echo "RESULTADO DE SINCRONIZACIÓN\n";
    echo "========================================\n";
    echo "Registros obtenidos: {$stats['fetched']}\n";
    echo "Insertados: {$stats['inserted']}\n";
    echo "Actualizados: {$stats['updated']}\n";
    echo "Omitidos: {$stats['skipped']}\n";
    echo "Errores: {$stats['errors']}\n";
    echo "========================================\n";

    if ($stats['errors'] > 0) {
        $errors = $syncService->getErrors();
        echo "\n⚠️ ERRORES ENCONTRADOS:\n";
        foreach (array_slice($errors, 0, 10) as $error) {
            echo "  - $error\n";
        }
        if (count($errors) > 10) {
            echo "  ... y " . (count($errors) - 10) . " errores más\n";
        }
    }

    echo "\n✅ Sincronización completada\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
