#!/usr/bin/env php
<?php
/**
 * Test that punch_type mapping is working correctly
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Services\Attendance\AttendanceSyncService;

Bootstrap::init();

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║        TEST: Mapeo de Tipos de Marcación Corregido       ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Test the mapping using reflection to access private method
$syncService = new AttendanceSyncService();
$reflection = new ReflectionClass($syncService);
$mapMethod = $reflection->getMethod('mapPunchType');
$mapMethod->setAccessible(true);

$testCases = [
    'entrada' => 'CHECK_IN',
    'salida' => 'CHECK_OUT',
    'entrada_almuerzo' => 'CHECK_IN',
    'salida_almuerzo' => 'CHECK_OUT',
    'ENTRADA' => 'CHECK_IN',
    'SALIDA' => 'CHECK_OUT',
    'check_in' => 'CHECK_IN',
    'check_out' => 'CHECK_OUT',
    'in' => 'CHECK_IN',
    'out' => 'CHECK_OUT',
    'unknown_type' => 'CHECK_IN'  // default
];

echo "Probando mapeo de tipos:\n\n";
$allPass = true;

foreach ($testCases as $input => $expected) {
    $result = $mapMethod->invoke($syncService, $input);
    $status = $result === $expected ? '✓' : '✗';

    if ($result !== $expected) {
        $allPass = false;
    }

    echo "  {$status} '{$input}' → '{$result}' " . ($result === $expected ? '' : "(esperado: '{$expected}')") . "\n";
}

echo "\n";

if ($allPass) {
    echo "✅ Todos los casos de prueba pasaron!\n";
    echo "   El mapeo está funcionando correctamente.\n\n";
} else {
    echo "❌ Algunos casos fallaron!\n";
    echo "   Revisa la implementación del mapeo.\n\n";
}

echo "Ahora puedes ejecutar una sincronización real desde:\n";
echo "  - UI: /panel/attendance/sync\n";
echo "  - CLI: php scripts/sync_attendance.php\n\n";
