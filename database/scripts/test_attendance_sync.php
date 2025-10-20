<?php
/**
 * Script de prueba para sincronización de marcaciones
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Services\Attendance\AttendanceSyncService;

// Inicializar sistema
Bootstrap::init();

echo "=== PRUEBA DE SINCRONIZACIÓN DE MARCACIONES ===\n\n";

try {
    echo "📡 Iniciando sincronización completa...\n\n";

    $syncService = new AttendanceSyncService();

    // Ejecutar sincronización completa
    $stats = $syncService->syncAll();

    echo "\n✅ SINCRONIZACIÓN COMPLETADA\n\n";
    echo "📊 ESTADÍSTICAS:\n";
    echo "   • Obtenidos:    {$stats['fetched']}\n";
    echo "   • Insertados:   {$stats['inserted']}\n";
    echo "   • Actualizados: {$stats['updated']}\n";
    echo "   • Omitidos:     {$stats['skipped']}\n";
    echo "   • Errores:      {$stats['errors']}\n\n";

    if ($stats['errors'] > 0) {
        echo "❌ ERRORES ENCONTRADOS:\n";
        echo str_repeat('-', 70) . "\n";
        $errors = $syncService->getErrors();
        foreach ($errors as $index => $error) {
            echo ($index + 1) . ". " . $error . "\n";
        }
        echo str_repeat('-', 70) . "\n\n";
    }

    if ($stats['inserted'] > 0 || $stats['updated'] > 0) {
        echo "✅ Sincronización exitosa con datos procesados\n";
    } elseif ($stats['errors'] > 0) {
        echo "⚠️ Sincronización con errores - revisar logs\n";
    } else {
        echo "ℹ️ No hay datos nuevos para sincronizar\n";
    }

} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n" . str_repeat('=', 70) . "\n";
