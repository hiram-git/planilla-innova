<?php
/**
 * Script de prueba para inicializar año 2026
 * Usa el método BusinessCalendar->initializeYear()
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Core/Database.php';
require_once __DIR__ . '/../../app/Core/Model.php';
require_once __DIR__ . '/../../app/Models/BusinessCalendar.php';

// Cargar variables de entorno
$rootPath = dirname(dirname(__DIR__));
$envFile = $rootPath . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, "\"'");
        }
    }
}

echo "============================================\n";
echo "TEST: Inicializar Año 2026\n";
echo "============================================\n\n";

try {
    // Instanciar modelo
    $businessCalendar = new \App\Models\BusinessCalendar();

    echo "Inicializando año 2026...\n";
    $result = $businessCalendar->initializeYear(2026);

    if ($result['success']) {
        echo "\n✅ Inicialización exitosa:\n";
        echo "   - Días insertados: {$result['inserted']}\n";
        echo "   - Días omitidos (ya existían): {$result['skipped']}\n";
        echo "   - Total días en 2026: {$result['total']}\n";

        // Verificar estadísticas
        $stats = $businessCalendar->getCalendarStats(2026);
        echo "\n📊 Estadísticas 2026:\n";
        foreach ($stats as $stat) {
            echo "   - {$stat['day_type']}: {$stat['count']} días\n";
        }
    } else {
        echo "\n❌ Error: " . ($result['error'] ?? 'Error desconocido') . "\n";
    }

} catch (Exception $e) {
    echo "\n❌ Excepción: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n============================================\n";
