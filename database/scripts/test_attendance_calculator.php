<?php

/**
 * Script de Testing - AttendanceCalculator
 *
 * Prueba la funcionalidad completa del calculador de asistencias:
 * - Cálculo de horas trabajadas
 * - Detección de tardanzas
 * - Cálculo de horas extras
 * - Score de puntualidad
 * - Asistencia perfecta
 * - Guardado en BD (attendance_calculations)
 *
 * Uso: php database/scripts/test_attendance_calculator.php
 *
 * @version 1.0.0
 * @date 2025-10-16
 */

// Bootstrap
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\Attendance\Calculators\AttendanceCalculator;
use App\Models\Attendance;
use App\Core\Database;

// Configurar entorno
if (file_exists(__DIR__ . '/../../.env') && class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
} elseif (file_exists(__DIR__ . '/../../.env')) {
    // Cargar manualmente si no hay Dotenv
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
        $_ENV[trim($name)] = trim($value);
        $_SERVER[trim($name)] = trim($value);
    }
}

echo "\n";
echo "========================================\n";
echo "  TESTING: AttendanceCalculator v1.0   \n";
echo "========================================\n\n";

// Inicializar calculadora
$calculator = new AttendanceCalculator();
$attendanceModel = new Attendance();
$db = Database::getInstance()->getConnection();

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

/**
 * Helper: Ejecutar test
 */
function runTest($testName, $testFunction) {
    global $totalTests, $passedTests, $failedTests;

    $totalTests++;
    echo "🧪 Test {$totalTests}: {$testName}... ";

    try {
        $result = $testFunction();

        if ($result === true) {
            echo "✅ PASS\n";
            $passedTests++;
        } else {
            echo "❌ FAIL: {$result}\n";
            $failedTests++;
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $failedTests++;
    }
}

/**
 * Helper: Crear marcación de prueba
 */
function createTestAttendance($timeIn, $timeOut, $scheduledIn = '08:00:00', $scheduledOut = '17:00:00') {
    return [
        'id' => 999999,  // ID ficticio para testing
        'employee_id' => 1,
        'date' => date('Y-m-d'),
        'time_in' => $timeIn,
        'time_out' => $timeOut,
        'num_hr' => 0,
        'status' => 1,
        'schedule_id' => 1,
        'scheduled_time_in' => $scheduledIn,
        'scheduled_time_out' => $scheduledOut,
    ];
}

echo "MÓDULO 1: CÁLCULOS BÁSICOS\n";
echo "───────────────────────────\n";

// Test 1: Jornada Normal (8 horas exactas)
runTest("Jornada normal 8h (08:00-17:00 con 1h almuerzo)", function() use ($calculator) {
    $attendance = createTestAttendance('08:00:00', '17:00:00');
    $calc = $calculator->calculate($attendance);

    // Debe calcular 8 horas (9h - 1h almuerzo)
    return ($calc['total_hours'] == 8 && $calc['regular_hours'] == 8 && $calc['overtime_hours'] == 0);
});

// Test 2: Horas Extras Normales
runTest("Horas extras (08:00-19:00 = 10h trabajadas)", function() use ($calculator) {
    $attendance = createTestAttendance('08:00:00', '19:00:00');
    $calc = $calculator->calculate($attendance);

    // 11h - 1h almuerzo = 10h total
    // 8h regulares + 2h extras
    return ($calc['total_hours'] == 10 && $calc['overtime_hours'] == 2);
});

// Test 3: Clasificación Horas Extras (25% y 50%)
runTest("Horas extras 25% y 50% (08:00-20:00 = 11h)", function() use ($calculator) {
    $attendance = createTestAttendance('08:00:00', '20:00:00');
    $calc = $calculator->calculate($attendance);

    // 12h - 1h almuerzo = 11h total
    // 8h regulares + 3h extras (25%) + 0h extras (50%)
    return ($calc['overtime_25_hours'] == 3 && $calc['overtime_50_hours'] == 0);
});

// Test 4: Más de 3 horas extras (activa 50%)
runTest("Horas extras >3h (08:00-22:00 = 13h trabajadas)", function() use ($calculator) {
    $attendance = createTestAttendance('08:00:00', '22:00:00');
    $calc = $calculator->calculate($attendance);

    // 14h - 1h almuerzo = 13h total
    // 8h regulares + 3h extras (25%) + 2h extras (50%)
    return ($calc['overtime_25_hours'] == 3 && $calc['overtime_50_hours'] == 2);
});

echo "\nMÓDULO 2: TARDANZAS Y PUNTUALIDAD\n";
echo "─────────────────────────────────\n";

// Test 5: Sin tardanza (puntual)
runTest("Entrada puntual (08:00 programado, 08:00 real)", function() use ($calculator) {
    $attendance = createTestAttendance('08:00:00', '17:00:00');
    $calc = $calculator->calculate($attendance);

    return ($calc['is_late'] == 0 && $calc['tardiness_minutes'] == 0);
});

// Test 6: Tardanza leve (dentro de tolerancia)
runTest("Tardanza 3 min (dentro de tolerancia de 5 min)", function() use ($calculator) {
    $attendance = createTestAttendance('08:03:00', '17:00:00');
    $calc = $calculator->calculate($attendance);

    // Con tolerancia de 5 min, no debe marcar como tarde
    return ($calc['is_late'] == 0);
});

// Test 7: Tardanza significativa
runTest("Tardanza 15 min (fuera de tolerancia)", function() use ($calculator) {
    $attendance = createTestAttendance('08:15:00', '17:00:00');
    $calc = $calculator->calculate($attendance);

    // 15 min - 5 min tolerancia = 10 min de tardanza
    return ($calc['is_late'] == 1 && $calc['tardiness_minutes'] == 10);
});

// Test 8: Score de puntualidad perfecto
runTest("Score puntualidad = 100 (asistencia perfecta)", function() use ($calculator) {
    $attendance = createTestAttendance('08:00:00', '17:00:00');
    $calc = $calculator->calculate($attendance);

    return ($calc['punctuality_score'] == 100);
});

// Test 9: Score puntualidad con tardanza
runTest("Score puntualidad reducido por tardanza", function() use ($calculator) {
    $attendance = createTestAttendance('08:30:00', '17:00:00'); // 30 min tarde
    $calc = $calculator->calculate($attendance);

    // Score debe ser menor a 100
    return ($calc['punctuality_score'] < 100 && $calc['punctuality_score'] > 0);
});

echo "\nMÓDULO 3: ASISTENCIA PERFECTA\n";
echo "─────────────────────────────\n";

// Test 10: Asistencia perfecta (sin tardanza ni salida anticipada)
runTest("Asistencia perfecta (puntual + horario completo)", function() use ($calculator) {
    $attendance = createTestAttendance('08:00:00', '17:00:00');
    $calc = $calculator->calculate($attendance);

    return ($calc['is_perfect_attendance'] == 1);
});

// Test 11: NO es perfecta (llegó tarde)
runTest("NO perfecta (llegó 10 min tarde)", function() use ($calculator) {
    $attendance = createTestAttendance('08:10:00', '17:00:00');
    $calc = $calculator->calculate($attendance);

    return ($calc['is_perfect_attendance'] == 0);
});

// Test 12: NO es perfecta (salió antes)
runTest("NO perfecta (salió 10 min antes)", function() use ($calculator) {
    $attendance = createTestAttendance('08:00:00', '16:50:00');
    $calc = $calculator->calculate($attendance);

    return ($calc['is_perfect_attendance'] == 0);
});

echo "\nMÓDULO 4: JORNADAS ESPECIALES\n";
echo "─────────────────────────────\n";

// Test 13: Media jornada (4 horas)
runTest("Media jornada 4h (sin descuento almuerzo)", function() use ($calculator) {
    $attendance = createTestAttendance('08:00:00', '12:00:00');
    $calc = $calculator->calculate($attendance);

    // 4h totales (sin descuento de almuerzo porque es < 6h)
    return ($calc['total_hours'] == 4);
});

// Test 14: Jornada larga sin salida registrada
runTest("Sin hora de salida (incomplete)", function() use ($calculator) {
    $attendance = createTestAttendance('08:00:00', null);
    $calc = $calculator->calculate($attendance);

    // No debe calcular horas si no hay salida
    return ($calc['total_hours'] == 0 && $calc['is_perfect_attendance'] == 0);
});

// Test 15: Jornada nocturna (6PM - 6AM)
runTest("Jornada nocturna parcial", function() use ($calculator) {
    $attendance = createTestAttendance('16:00:00', '22:00:00'); // 4PM - 10PM
    $calc = $calculator->calculate($attendance);

    // Debe detectar horas nocturnas después de 6PM
    return ($calc['night_hours'] > 0);
});

echo "\nMÓDULO 5: GUARDADO EN BASE DE DATOS\n";
echo "───────────────────────────────────\n";

// Obtener una marcación real de la BD para testing
$realAttendances = $db->query("SELECT * FROM attendance WHERE time_out IS NOT NULL ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($realAttendances) {
    // Test 16: Calcular marcación real
    runTest("Calcular marcación real desde BD", function() use ($calculator, $realAttendances) {
        $calc = $calculator->calculate($realAttendances);

        return (isset($calc['total_hours']) && isset($calc['punctuality_score']));
    });

    // Test 17: Guardar cálculo en BD
    runTest("Guardar cálculo en attendance_calculations", function() use ($calculator, $realAttendances) {
        $calc = $calculator->calculate($realAttendances);
        $calculationId = $calculator->saveCalculation($calc);

        return ($calculationId > 0);
    });

    // Test 18: Recuperar cálculo guardado
    runTest("Recuperar cálculo guardado desde BD", function() use ($calculator, $realAttendances) {
        $saved = $calculator->getCalculation($realAttendances['id']);

        return ($saved !== null && isset($saved['total_hours']));
    });

    // Test 19: Actualizar (recalcular) cálculo existente
    runTest("Recalcular (UPDATE) cálculo existente", function() use ($calculator, $realAttendances) {
        $calc = $calculator->calculate($realAttendances);
        $calculationId1 = $calculator->saveCalculation($calc);

        // Guardar nuevamente (debe hacer UPDATE, no INSERT)
        $calculationId2 = $calculator->saveCalculation($calc);

        return ($calculationId1 == $calculationId2);
    });

    // Test 20: Método calculateAndSave (all-in-one)
    runTest("Método calculateAndSave() all-in-one", function() use ($calculator, $realAttendances) {
        $result = $calculator->calculateAndSave($realAttendances);

        return (isset($result['calculation_id']) && $result['calculation_id'] > 0);
    });

} else {
    echo "⚠️  No hay marcaciones reales en BD para testing de guardado\n";
}

echo "\nMÓDULO 6: PROCESAMIENTO EN BATCH\n";
echo "────────────────────────────────\n";

// Test 21: Procesamiento bulk
runTest("Calcular múltiples asistencias en batch", function() use ($calculator) {
    $attendances = [
        createTestAttendance('08:00:00', '17:00:00'),
        createTestAttendance('08:10:00', '17:00:00'),
        createTestAttendance('08:30:00', '19:00:00'),
    ];

    $calculations = $calculator->calculateBulk($attendances);

    return (count($calculations) == 3);
});

// Test 22: Batch con guardado en BD (usando marcaciones reales)
$realBatch = $db->query("SELECT * FROM attendance WHERE time_out IS NOT NULL ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

if (count($realBatch) >= 3) {
    runTest("Batch calculateAndSaveBulk() con BD", function() use ($calculator, $realBatch) {
        $result = $calculator->calculateAndSaveBulk($realBatch, true);

        return (
            isset($result['stats']) &&
            $result['stats']['total_processed'] == 3 &&
            $result['stats']['saved'] >= 1
        );
    });
}

echo "\n";
echo "========================================\n";
echo "        RESUMEN DE RESULTADOS          \n";
echo "========================================\n";
echo "Total Tests:  {$totalTests}\n";
echo "Passed:       " . ($passedTests > 0 ? "✅ {$passedTests}" : "0") . "\n";
echo "Failed:       " . ($failedTests > 0 ? "❌ {$failedTests}" : "0") . "\n";

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
echo "Success Rate: {$successRate}%\n";

if ($failedTests == 0) {
    echo "\n🎉 ¡TODOS LOS TESTS PASARON! AttendanceCalculator funcionando correctamente.\n";
} else {
    echo "\n⚠️  Algunos tests fallaron. Revisar implementación.\n";
}

echo "\n";
echo "Configuración del calculador:\n";
$config = $calculator->getConfig();
foreach ($config as $key => $value) {
    echo "  - {$key}: {$value}\n";
}

echo "\n========================================\n\n";

// Retornar código de salida apropiado
exit($failedTests > 0 ? 1 : 0);
