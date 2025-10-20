<?php
/**
 * Script de prueba para sincronización de marcaciones por rango de fechas
 * Ejemplo: Solo sincronizar el último mes o mes actual
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Services\Attendance\AttendanceSyncService;

// Inicializar sistema
Bootstrap::init();

echo "=== SINCRONIZACIÓN DE MARCACIONES POR RANGO DE FECHAS ===\n\n";

// Definir rango de fechas
$startDate = '2025-10-01';  // Fecha inicio
$endDate = '2025-10-16';    // Fecha fin

// Alternativas útiles:
// $startDate = date('Y-m-d', strtotime('-30 days'));  // Últimos 30 días
// $endDate = date('Y-m-d');                            // Hoy

echo "📅 Rango de fechas: {$startDate} → {$endDate}\n\n";

try {
    echo "📡 Iniciando sincronización por rango...\n\n";

    $syncService = new AttendanceSyncService();

    // Ejecutar sincronización por rango de fechas
    $stats = $syncService->syncByDateRange($startDate, $endDate);

    echo "\n✅ SINCRONIZACIÓN COMPLETADA\n\n";
    echo "📊 ESTADÍSTICAS:\n";
    echo "   • Obtenidos:    {$stats['fetched']} (de la API)\n";
    echo "   • En rango:     " . ($stats['inserted'] + $stats['updated'] + $stats['skipped']) . " (filtrados)\n";
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

    // Calcular registros descartados (fuera del rango)
    $discarded = $stats['fetched'] - ($stats['inserted'] + $stats['updated'] + $stats['skipped'] + $stats['errors']);
    if ($discarded > 0) {
        echo "ℹ️ {$discarded} registros descartados (fuera del rango de fechas)\n\n";
    }

    if ($stats['inserted'] > 0 || $stats['updated'] > 0) {
        echo "✅ Sincronización exitosa con datos procesados\n";
    } elseif ($stats['errors'] > 0) {
        echo "⚠️ Sincronización con errores - revisar logs\n";
    } else {
        echo "ℹ️ No hay datos nuevos para sincronizar en el rango especificado\n";
    }

} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "\n💡 TIP: Modifica las variables \$startDate y \$endDate al inicio del script\n";
echo "   para sincronizar diferentes rangos de fechas.\n\n";
echo "Ejemplos útiles:\n";
echo "   • Último mes:    date('Y-m-d', strtotime('-1 month'))\n";
echo "   • Esta semana:   date('Y-m-d', strtotime('monday this week'))\n";
echo "   • Este mes:      date('Y-m-01') hasta date('Y-m-d')\n";
echo "   • Año actual:    date('Y-01-01') hasta date('Y-12-31')\n\n";
