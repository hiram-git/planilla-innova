<?php

/**
 * Script de Pruebas Independientes - Sin Conexión BD
 * Valida la lógica de los calculadores de forma standalone
 *
 * Uso: php scripts/test_calculators_standalone.php
 */

// ==================================================
// SIMULACIÓN DE CLASES (Sin DB)
// ==================================================

class MockWorkScheduleResolver
{
    public function calculateTardinessMinutes($actualTimeIn, $scheduledTimeIn)
    {
        $actual = new DateTime($actualTimeIn);
        $scheduled = new DateTime($scheduledTimeIn);

        if ($actual <= $scheduled) {
            return 0;
        }

        $interval = $scheduled->diff($actual);
        return ($interval->h * 60) + $interval->i;
    }

    public function isOnTime($actualTimeIn, $scheduledTimeIn, $toleranceMinutes = 5)
    {
        $actual = new DateTime($actualTimeIn);
        $scheduled = new DateTime($scheduledTimeIn);
        $scheduled->modify("+{$toleranceMinutes} minutes");

        return $actual <= $scheduled;
    }

    public function calculateEarlyDepartureMinutes($actualTimeOut, $scheduledTimeOut)
    {
        if (empty($actualTimeOut) || empty($scheduledTimeOut)) {
            return 0;
        }

        $actual = new DateTime($actualTimeOut);
        $scheduled = new DateTime($scheduledTimeOut);

        if ($actual >= $scheduled) {
            return 0;
        }

        $interval = $actual->diff($scheduled);
        return ($interval->h * 60) + $interval->i;
    }

    public function calculateExpectedWorkHours($timeIn, $timeOut, $lunchMinutes = 60)
    {
        $start = new DateTime($timeIn);
        $end = new DateTime($timeOut);

        if ($end < $start) {
            $end->modify('+1 day');
        }

        $interval = $start->diff($end);
        $totalMinutes = ($interval->h * 60) + $interval->i;
        $workMinutes = $totalMinutes - $lunchMinutes;

        return round($workMinutes / 60, 2);
    }
}

class MockOvertimeCalculator
{
    const REGULAR_DAILY_HOURS = 8;
    const OVERTIME_25_LIMIT = 3;
    const NIGHT_SHIFT_START = 18;
    const NIGHT_SHIFT_END = 6;

    public function calculateOvertime($totalHours, $regularHours = 8)
    {
        $overtimeHours = max(0, $totalHours - $regularHours);
        $overtime25 = min($overtimeHours, self::OVERTIME_25_LIMIT);
        $overtime50 = max(0, $overtimeHours - self::OVERTIME_25_LIMIT);

        return [
            'total_overtime' => round($overtimeHours, 2),
            'overtime_25' => round($overtime25, 2),
            'overtime_50' => round($overtime50, 2)
        ];
    }

    public function calculateNightHours($timeIn, $timeOut)
    {
        if (empty($timeIn) || empty($timeOut)) {
            return 0;
        }

        $start = new DateTime($timeIn);
        $end = new DateTime($timeOut);

        if ($end < $start) {
            $end->modify('+1 day');
        }

        $nightHours = 0;
        $current = clone $start;

        while ($current < $end) {
            $hour = (int)$current->format('H');

            if ($hour >= self::NIGHT_SHIFT_START || $hour < self::NIGHT_SHIFT_END) {
                $nextHour = (clone $current)->modify('+1 hour')->setTime((int)$current->format('H') + 1, 0, 0);

                if ($nextHour > $end) {
                    $nextHour = $end;
                }

                $interval = $current->diff($nextHour);
                $minutes = ($interval->h * 60) + $interval->i + ($interval->s / 60);
                $nightHours += $minutes / 60;
            }

            $current->modify('+1 hour')->setTime((int)$current->format('H'), 0, 0);
        }

        return round($nightHours, 2);
    }

    public function calculateTotalHours($timeIn, $timeOut, $lunchMinutes = 60)
    {
        $start = new DateTime($timeIn);
        $end = new DateTime($timeOut);

        if ($end < $start) {
            $end->modify('+1 day');
        }

        $interval = $start->diff($end);
        $totalMinutes = ($interval->h * 60) + $interval->i;

        if ($totalMinutes > 240) {
            $totalMinutes -= $lunchMinutes;
        }

        return round($totalMinutes / 60, 2);
    }
}

// ==================================================
// FUNCIONES DE TESTING
// ==================================================

function printHeader($title) {
    echo "\n";
    echo str_repeat("=", 70) . "\n";
    echo "  " . strtoupper($title) . "\n";
    echo str_repeat("=", 70) . "\n";
}

function printTest($testName, $expected, $actual, &$passed, &$failed) {
    $status = ($expected == $actual) ? "✓ PASS" : "✗ FAIL";
    $color = ($expected == $actual) ? "" : "";

    echo sprintf("  %-50s %s\n", $testName, $status);

    if ($expected != $actual) {
        echo "    Expected: {$expected}\n";
        echo "    Actual:   {$actual}\n";
        $failed++;
    } else {
        $passed++;
    }
}

function printSubheader($title) {
    echo "\n";
    echo str_repeat("-", 70) . "\n";
    echo "  " . $title . "\n";
    echo str_repeat("-", 70) . "\n";
}

// ==================================================
// TESTS: WORK SCHEDULE RESOLVER
// ==================================================

function testWorkScheduleResolver(&$totalPassed, &$totalFailed) {
    printHeader("TEST SUITE 1: WorkScheduleResolver");

    $resolver = new MockWorkScheduleResolver();
    $passed = 0;
    $failed = 0;

    printSubheader("Cálculo de Tardanzas");

    // Test 1: Sin tardanza
    $result = $resolver->calculateTardinessMinutes('08:00:00', '08:00:00');
    printTest("Llegada exacta a las 8:00", 0, $result, $passed, $failed);

    // Test 2: 15 minutos tarde
    $result = $resolver->calculateTardinessMinutes('08:15:00', '08:00:00');
    printTest("Llegada a las 8:15 (horario 8:00)", 15, $result, $passed, $failed);

    // Test 3: 1 hora tarde
    $result = $resolver->calculateTardinessMinutes('09:00:00', '08:00:00');
    printTest("Llegada a las 9:00 (horario 8:00)", 60, $result, $passed, $failed);

    // Test 4: Llegó temprano
    $result = $resolver->calculateTardinessMinutes('07:45:00', '08:00:00');
    printTest("Llegada temprano (7:45)", 0, $result, $passed, $failed);

    printSubheader("Verificación de Puntualidad (tolerancia 5 min)");

    // Test 5: A tiempo con tolerancia
    $result = $resolver->isOnTime('08:03:00', '08:00:00', 5);
    printTest("Llegada 8:03 (tolerancia 5 min)", true, $result, $passed, $failed);

    // Test 6: Tarde aún con tolerancia
    $result = $resolver->isOnTime('08:10:00', '08:00:00', 5);
    printTest("Llegada 8:10 (tolerancia 5 min)", false, $result, $passed, $failed);

    printSubheader("Cálculo de Salidas Anticipadas");

    // Test 7: Salida a tiempo
    $result = $resolver->calculateEarlyDepartureMinutes('17:00:00', '17:00:00');
    printTest("Salida exacta a las 5:00 PM", 0, $result, $passed, $failed);

    // Test 8: Salida 30 min antes
    $result = $resolver->calculateEarlyDepartureMinutes('16:30:00', '17:00:00');
    printTest("Salida a las 4:30 PM (horario 5:00 PM)", 30, $result, $passed, $failed);

    printSubheader("Cálculo de Horas Esperadas");

    // Test 9: Jornada completa 8:00-17:00 con 1h almuerzo
    $result = $resolver->calculateExpectedWorkHours('08:00:00', '17:00:00', 60);
    printTest("Jornada 8:00-17:00 (1h almuerzo)", 8.0, $result, $passed, $failed);

    // Test 10: Jornada completa 9:00-18:00 con 1h almuerzo
    $result = $resolver->calculateExpectedWorkHours('09:00:00', '18:00:00', 60);
    printTest("Jornada 9:00-18:00 (1h almuerzo)", 8.0, $result, $passed, $failed);

    $totalPassed += $passed;
    $totalFailed += $failed;

    echo "\n  Subtotal: {$passed} passed, {$failed} failed\n";
}

// ==================================================
// TESTS: OVERTIME CALCULATOR
// ==================================================

function testOvertimeCalculator(&$totalPassed, &$totalFailed) {
    printHeader("TEST SUITE 2: OvertimeCalculator");

    $calculator = new MockOvertimeCalculator();
    $passed = 0;
    $failed = 0;

    printSubheader("Cálculo de Horas Extras (Legislación Panameña)");

    // Test 1: Sin horas extras
    $result = $calculator->calculateOvertime(8, 8);
    printTest("8 horas trabajadas (sin extras)", 0, $result['total_overtime'], $passed, $failed);

    // Test 2: 2 horas extras (dentro del límite 25%)
    $result = $calculator->calculateOvertime(10, 8);
    printTest("10 horas - Total extras", 2.0, $result['total_overtime'], $passed, $failed);
    printTest("10 horas - Extras al 25%", 2.0, $result['overtime_25'], $passed, $failed);
    printTest("10 horas - Extras al 50%", 0.0, $result['overtime_50'], $passed, $failed);

    // Test 3: Exactamente 3 horas extras (límite)
    $result = $calculator->calculateOvertime(11, 8);
    printTest("11 horas - Total extras", 3.0, $result['total_overtime'], $passed, $failed);
    printTest("11 horas - Extras al 25%", 3.0, $result['overtime_25'], $passed, $failed);
    printTest("11 horas - Extras al 50%", 0.0, $result['overtime_50'], $passed, $failed);

    // Test 4: 5 horas extras (3h al 25% + 2h al 50%)
    $result = $calculator->calculateOvertime(13, 8);
    printTest("13 horas - Total extras", 5.0, $result['total_overtime'], $passed, $failed);
    printTest("13 horas - Extras al 25%", 3.0, $result['overtime_25'], $passed, $failed);
    printTest("13 horas - Extras al 50%", 2.0, $result['overtime_50'], $passed, $failed);

    printSubheader("Cálculo de Horas Nocturnas (6PM-6AM)");

    // Test 5: Turno completo nocturno 6PM-2AM
    $result = $calculator->calculateNightHours('2025-10-10 18:00:00', '2025-10-11 02:00:00');
    printTest("Turno 6:00 PM - 2:00 AM", 8.0, $result, $passed, $failed);

    // Test 6: Turno diurno sin horas nocturnas
    $result = $calculator->calculateNightHours('2025-10-10 08:00:00', '2025-10-10 17:00:00');
    printTest("Turno 8:00 AM - 5:00 PM (sin nocturnas)", 0.0, $result, $passed, $failed);

    // Test 7: Turno mixto (termina en noche)
    $result = $calculator->calculateNightHours('2025-10-10 14:00:00', '2025-10-10 22:00:00');
    printTest("Turno 2:00 PM - 10:00 PM (4h nocturnas)", 4.0, $result, $passed, $failed);

    printSubheader("Cálculo de Horas Totales Trabajadas");

    // Test 8: Jornada completa con almuerzo
    $result = $calculator->calculateTotalHours('2025-10-10 08:00:00', '2025-10-10 17:00:00', 60);
    printTest("8:00 AM - 5:00 PM (1h almuerzo)", 8.0, $result, $passed, $failed);

    // Test 9: Jornada extendida
    $result = $calculator->calculateTotalHours('2025-10-10 08:00:00', '2025-10-10 19:00:00', 60);
    printTest("8:00 AM - 7:00 PM (1h almuerzo)", 10.0, $result, $passed, $failed);

    $totalPassed += $passed;
    $totalFailed += $failed;

    echo "\n  Subtotal: {$passed} passed, {$failed} failed\n";
}

// ==================================================
// TESTS: CASOS DE USO REALES
// ==================================================

function testRealWorldScenarios(&$totalPassed, &$totalFailed) {
    printHeader("TEST SUITE 3: Casos de Uso Reales");

    $resolver = new MockWorkScheduleResolver();
    $calculator = new MockOvertimeCalculator();
    $passed = 0;
    $failed = 0;

    printSubheader("Escenario 1: Empleado Puntual (Día Normal)");

    $timeIn = '2025-10-10 08:00:00';
    $timeOut = '2025-10-10 17:00:00';

    $tardiness = $resolver->calculateTardinessMinutes($timeIn, '08:00:00');
    $totalHours = $calculator->calculateTotalHours($timeIn, $timeOut, 60);
    $overtime = $calculator->calculateOvertime($totalHours, 8);

    printTest("Sin tardanza", 0, $tardiness, $passed, $failed);
    printTest("8 horas trabajadas", 8.0, $totalHours, $passed, $failed);
    printTest("Sin horas extras", 0, $overtime['total_overtime'], $passed, $failed);

    printSubheader("Escenario 2: Empleado con Tardanza");

    $timeIn = '2025-10-10 08:25:00';
    $timeOut = '2025-10-10 17:00:00';

    $tardiness = $resolver->calculateTardinessMinutes($timeIn, '08:00:00');
    printTest("25 minutos de tardanza", 25, $tardiness, $passed, $failed);

    printSubheader("Escenario 3: Día con Horas Extras");

    $timeIn = '2025-10-10 08:00:00';
    $timeOut = '2025-10-10 20:00:00'; // Salió a las 8 PM

    $totalHours = $calculator->calculateTotalHours($timeIn, $timeOut, 60);
    $overtime = $calculator->calculateOvertime($totalHours, 8);
    $nightHours = $calculator->calculateNightHours($timeIn, $timeOut);

    printTest("11 horas trabajadas", 11.0, $totalHours, $passed, $failed);
    printTest("3 horas extras totales", 3.0, $overtime['total_overtime'], $passed, $failed);
    printTest("3 horas extras al 25%", 3.0, $overtime['overtime_25'], $passed, $failed);
    printTest("2 horas nocturnas (6PM-8PM)", 2.0, $nightHours, $passed, $failed);

    printSubheader("Escenario 4: Turno Nocturno Completo");

    $timeIn = '2025-10-10 22:00:00';  // 10 PM
    $timeOut = '2025-10-11 06:00:00'; // 6 AM

    $totalHours = $calculator->calculateTotalHours($timeIn, $timeOut, 0); // Sin almuerzo
    $nightHours = $calculator->calculateNightHours($timeIn, $timeOut);

    printTest("8 horas trabajadas", 8.0, $totalHours, $passed, $failed);
    printTest("8 horas nocturnas (100%)", 8.0, $nightHours, $passed, $failed);

    printSubheader("Escenario 5: Salida Anticipada");

    $timeOut = '2025-10-10 15:30:00';
    $earlyDeparture = $resolver->calculateEarlyDepartureMinutes($timeOut, '17:00:00');

    printTest("90 minutos salida anticipada", 90, $earlyDeparture, $passed, $failed);

    $totalPassed += $passed;
    $totalFailed += $failed;

    echo "\n  Subtotal: {$passed} passed, {$failed} failed\n";
}

// ==================================================
// EJECUCIÓN PRINCIPAL
// ==================================================

try {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║    TESTS STANDALONE - CALCULADORES DE ASISTENCIAS V1.0            ║\n";
    echo "║                  Sin Conexión a Base de Datos                      ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n";

    $totalPassed = 0;
    $totalFailed = 0;

    testWorkScheduleResolver($totalPassed, $totalFailed);
    testOvertimeCalculator($totalPassed, $totalFailed);
    testRealWorldScenarios($totalPassed, $totalFailed);

    printHeader("RESUMEN FINAL");

    $total = $totalPassed + $totalFailed;
    $percentage = $total > 0 ? round(($totalPassed / $total) * 100, 2) : 0;

    echo "\n";
    echo "  Total Tests:    {$total}\n";
    echo "  ✓ Passed:       {$totalPassed}\n";
    echo "  ✗ Failed:       {$totalFailed}\n";
    echo "  Success Rate:   {$percentage}%\n";
    echo "\n";

    if ($totalFailed == 0) {
        echo "  🎉 TODOS LOS TESTS PASARON EXITOSAMENTE!\n";
        echo "  ✅ Sistema listo para integración con BD\n";
        exit(0);
    } else {
        echo "  ⚠️  ALGUNOS TESTS FALLARON\n";
        echo "  Por favor revisa los errores arriba\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\n✗ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
    exit(1);
}
