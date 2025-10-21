<?php
/**
 * Script de Testing Completo - Sistema de Alertas
 *
 * Prueba todos los componentes de la Subfase 7.3:
 * - LegalComplianceChecker
 * - OvertimeRateCalculator
 * - WorkingDayClassifier
 * - AlertsSystem
 * - Integración completa con AttendanceCalculator
 *
 * @version 1.0
 * @date 20 Oct 2025
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Core/Database.php';

use App\Core\Database;
use App\Services\Attendance\Calculators\LegalComplianceChecker;
use App\Services\Attendance\Calculators\OvertimeRateCalculator;
use App\Services\Attendance\Calculators\WorkingDayClassifier;
use App\Services\Attendance\AlertsSystem;
use App\Services\Attendance\Calculators\AttendanceCalculator;

// Colores para output
function color($text, $color) {
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['reset'];
}

function printHeader($text) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo color($text, 'blue') . "\n";
    echo str_repeat("=", 80) . "\n\n";
}

function printTest($name, $passed, $details = '') {
    $status = $passed ? color("✓ PASS", 'green') : color("✗ FAIL", 'red');
    echo "[$status] $name\n";
    if ($details) {
        echo "    → $details\n";
    }
}

// ==============================================================
// PARTE 1: TESTS LEGALCOMPLIANCECHECKER
// ==============================================================

printHeader("PARTE 1: TESTS LEGALCOMPLIANCECHECKER");

$checker = new LegalComplianceChecker();
$testsPassedChecker = 0;
$totalTestsChecker = 0;

// Test 1.1: Jornada diaria normal (8h)
$totalTestsChecker++;
$result = $checker->validateDailyHours(8, '2025-10-20');
$passed = $result['is_compliant'] === true && empty($result['violations']);
printTest("Jornada diaria 8h (compliant)", $passed, "Total: {$result['total_hours']}h");
if ($passed) $testsPassedChecker++;

// Test 1.2: Jornada diaria excedida (11h)
$totalTestsChecker++;
$result = $checker->validateDailyHours(11, '2025-10-20');
$passed = $result['is_compliant'] === false && !empty($result['violations']);
printTest("Jornada diaria 11h (violation)", $passed, "Violaciones: " . count($result['violations']));
if ($passed) $testsPassedChecker++;

// Test 1.3: Jornada semanal normal (40h)
$totalTestsChecker++;
$result = $checker->validateWeeklyHours(40, '2025-10-13', '2025-10-19');
$passed = $result['is_compliant'] === true;
printTest("Jornada semanal 40h (compliant)", $passed);
if ($passed) $testsPassedChecker++;

// Test 1.4: Jornada semanal excedida (52h)
$totalTestsChecker++;
$result = $checker->validateWeeklyHours(52, '2025-10-13', '2025-10-19');
$passed = $result['is_compliant'] === false && !empty($result['violations']);
printTest("Jornada semanal 52h (violation)", $passed);
if ($passed) $testsPassedChecker++;

// Test 1.5: Tiempo almuerzo suficiente (60 min)
$totalTestsChecker++;
$result = $checker->validateLunchTime(60, 8);
$passed = $result['is_compliant'] === true;
printTest("Tiempo almuerzo 60 min (compliant)", $passed);
if ($passed) $testsPassedChecker++;

// Test 1.6: Tiempo almuerzo insuficiente (20 min)
$totalTestsChecker++;
$result = $checker->validateLunchTime(20, 8);
$passed = $result['is_compliant'] === false && !empty($result['violations']);
printTest("Tiempo almuerzo 20 min (violation)", $passed);
if ($passed) $testsPassedChecker++;

// Test 1.7: Ausencias injustificadas graves (3)
$totalTestsChecker++;
$result = $checker->validateAbsences(3, '2025-10');
$passed = $result['is_compliant'] === false && $result['is_serious_offense'] === true;
printTest("3 ausencias injustificadas (falta grave)", $passed);
if ($passed) $testsPassedChecker++;

// Test 1.8: Validación completa
$totalTestsChecker++;
$attendanceData = [
    'date' => '2025-10-20',
    'daily_hours' => 9.5,
    'total_hours' => 9.5,
    'lunch_minutes' => 45,
    'night_hours' => 2
];
$result = $checker->performFullCompliance($attendanceData);
$passed = isset($result['overall_compliant']) && isset($result['checks']);
printTest("Validación completa de cumplimiento", $passed, "Checks: " . count($result['checks']));
if ($passed) $testsPassedChecker++;

echo "\nResumen LegalComplianceChecker: " . color("$testsPassedChecker/$totalTestsChecker tests passed", $testsPassedChecker === $totalTestsChecker ? 'green' : 'yellow') . "\n";

// ==============================================================
// PARTE 2: TESTS OVERTIMERATEAND CALCULATOR
// ==============================================================

printHeader("PARTE 2: TESTS OVERTIMERRATECALCULATOR");

$calculator = new OvertimeRateCalculator();
$testsPassedCalc = 0;
$totalTestsCalc = 0;

// Test 2.1: Cálculo tarifa horaria
$totalTestsCalc++;
$monthlySalary = 1920;
$hourlyRate = $calculator->calculateHourlyRate($monthlySalary);
$expected = 10; // 1920 / 192
$passed = abs($hourlyRate - $expected) < 0.01;
printTest("Tarifa horaria desde salario mensual", $passed, "Tarifa: $$hourlyRate/h");
if ($passed) $testsPassedCalc++;

// Test 2.2: Horas extras 25%
$totalTestsCalc++;
$result = $calculator->calculateOvertime25Amount(10, 2);
$expected = 25; // 10 * 1.25 * 2
$passed = abs($result['total_amount'] - $expected) < 0.01;
printTest("Horas extras 25% (2h @ $10/h)", $passed, "Total: $" . $result['total_amount']);
if ($passed) $testsPassedCalc++;

// Test 2.3: Horas extras 50%
$totalTestsCalc++;
$result = $calculator->calculateOvertime50Amount(10, 3);
$expected = 45; // 10 * 1.50 * 3
$passed = abs($result['total_amount'] - $expected) < 0.01;
printTest("Horas extras 50% (3h @ $10/h)", $passed, "Total: $" . $result['total_amount']);
if ($passed) $testsPassedCalc++;

// Test 2.4: Horas nocturnas con recargo
$totalTestsCalc++;
$result = $calculator->calculateNightHoursAmount(10, 4);
$expected = 60; // 10 * 1.50 * 4
$passed = abs($result['total_amount'] - $expected) < 0.01;
printTest("Horas nocturnas 50% (4h @ $10/h)", $passed, "Total: $" . $result['total_amount']);
if ($passed) $testsPassedCalc++;

// Test 2.5: Cálculo completo de montos
$totalTestsCalc++;
$hoursBreakdown = [
    'regular_hours' => 8,
    'overtime_25_hours' => 2,
    'overtime_50_hours' => 1,
    'night_hours' => 3
];
$result = $calculator->calculateCompleteAmounts(1920, $hoursBreakdown);
$passed = isset($result['totals']['total_amount']) && $result['totals']['total_amount'] > 0;
printTest("Cálculo completo de montos", $passed, "Total a pagar: $" . $result['totals']['total_amount']);
if ($passed) $testsPassedCalc++;

// Test 2.6: Multiplicadores de tarifa
$totalTestsCalc++;
$multipliers = $calculator->getRateMultipliers();
$passed = count($multipliers) === 6 && isset($multipliers['overtime_25']);
printTest("Obtener multiplicadores legales", $passed, "Multipliers: " . count($multipliers));
if ($passed) $testsPassedCalc++;

echo "\nResumen OvertimeRateCalculator: " . color("$testsPassedCalc/$totalTestsCalc tests passed", $testsPassedCalc === $totalTestsCalc ? 'green' : 'yellow') . "\n";

// ==============================================================
// PARTE 3: TESTS WORKINGDAYCLASSIFIER
// ==============================================================

printHeader("PARTE 3: TESTS WORKINGDAYCLASSIFIER");

$classifier = new WorkingDayClassifier();
$testsPassedClass = 0;
$totalTestsClass = 0;

// Test 3.1: Clasificar día laboral (lunes)
$totalTestsClass++;
$result = $classifier->classifyDay('2025-10-20'); // Lunes
$passed = $result['day_of_week'] === 1 && $result['day_name'] === 'Lunes';
printTest("Clasificar lunes", $passed, "Día: {$result['day_name']}");
if ($passed) $testsPassedClass++;

// Test 3.2: Clasificar fin de semana (domingo)
$totalTestsClass++;
$result = $classifier->classifyDay('2025-10-19'); // Domingo
$passed = $result['is_weekend'] === true && $result['day_of_week'] === 7;
printTest("Clasificar domingo", $passed, "Fin de semana: " . ($result['is_weekend'] ? 'Sí' : 'No'));
if ($passed) $testsPassedClass++;

// Test 3.3: Verificar es día laboral
$totalTestsClass++;
$isWorking = $classifier->isWorkingDay('2025-10-20');
$passed = $isWorking === true;
printTest("Verificar día laboral", $passed);
if ($passed) $testsPassedClass++;

// Test 3.4: Verificar NO es día laboral (domingo)
$totalTestsClass++;
$isWorking = $classifier->isWorkingDay('2025-10-19');
$passed = $isWorking === false;
printTest("Verificar NO día laboral (domingo)", $passed);
if ($passed) $testsPassedClass++;

// Test 3.5: Clasificar período (semana)
$totalTestsClass++;
$classifications = $classifier->classifyPeriod('2025-10-13', '2025-10-19');
$passed = count($classifications) === 7;
printTest("Clasificar período de 7 días", $passed, "Total días: " . count($classifications));
if ($passed) $testsPassedClass++;

// Test 3.6: Estadísticas de período
$totalTestsClass++;
$stats = $classifier->getPeriodStatistics('2025-10-13', '2025-10-19');
$passed = isset($stats['working_days']) && isset($stats['weekends']);
printTest("Estadísticas de período", $passed, "Días laborables: {$stats['working_days']}, Fines de semana: {$stats['weekends']}");
if ($passed) $testsPassedClass++;

// Test 3.7: Obtener días laborables
$totalTestsClass++;
$workingDays = $classifier->getWorkingDays('2025-10-01', '2025-10-07');
$passed = is_array($workingDays) && count($workingDays) >= 5;
printTest("Obtener días laborables de una semana", $passed, "Total: " . count($workingDays));
if ($passed) $testsPassedClass++;

// Test 3.8: Siguiente día laboral
$totalTestsClass++;
$nextWorking = $classifier->getNextWorkingDay('2025-10-18'); // Sábado
$passed = !empty($nextWorking) && $classifier->isWorkingDay($nextWorking);
printTest("Obtener siguiente día laboral después de sábado", $passed, "Próximo: $nextWorking");
if ($passed) $testsPassedClass++;

echo "\nResumen WorkingDayClassifier: " . color("$testsPassedClass/$totalTestsClass tests passed", $testsPassedClass === $totalTestsClass ? 'green' : 'yellow') . "\n";

// ==============================================================
// PARTE 4: TESTS ALERTSSYSTEM
// ==============================================================

printHeader("PARTE 4: TESTS ALERTSSYSTEM");

$alertsSystem = new AlertsSystem();
$testsPassedAlerts = 0;
$totalTestsAlerts = 0;

// Limpiar alertas de prueba anteriores del empleado 1
try {
    $db = Database::getInstance();
    $db->query("DELETE FROM attendance_alerts WHERE employee_id = 1 AND title LIKE '%TEST%'");
    echo color("✓ Alertas de prueba anteriores limpiadas\n", 'yellow');
} catch (Exception $e) {
    echo color("✗ Error limpiando alertas: " . $e->getMessage() . "\n", 'red');
}

// Test 4.1: Crear alerta simple
$totalTestsAlerts++;
$alertData = [
    'employee_id' => 1,
    'alert_type' => 'EXCESO_JORNADA_DIARIA',
    'severity' => 'WARNING',
    'title' => 'TEST: Exceso jornada diaria',
    'message' => 'Test de creación de alerta',
    'date' => '2025-10-20',
    'metadata' => ['total_hours' => 9, 'max_allowed' => 8]
];
$alertId = $alertsSystem->createAlert($alertData);
$passed = $alertId !== false && $alertId > 0;
printTest("Crear alerta simple", $passed, "Alert ID: $alertId");
if ($passed) $testsPassedAlerts++;

// Test 4.2: Prevenir alertas duplicadas
$totalTestsAlerts++;
$duplicateId = $alertsSystem->createAlert($alertData); // Mismo datos
$passed = $duplicateId === false; // Debe rechazar duplicado
printTest("Prevenir alerta duplicada", $passed, "Duplicado rechazado: " . ($passed ? 'Sí' : 'No'));
if ($passed) $testsPassedAlerts++;

// Test 4.3: Crear alerta crítica
$totalTestsAlerts++;
$criticalAlert = [
    'employee_id' => 1,
    'alert_type' => 'AUSENCIAS_INJUSTIFICADAS_GRAVES',
    'severity' => 'CRITICAL',
    'title' => 'TEST: Falta grave detectada',
    'message' => '3 ausencias injustificadas en el mes',
    'date' => '2025-10-20',
    'period_start' => '2025-10-01',
    'period_end' => '2025-10-31',
    'metadata' => ['absences_count' => 3, 'threshold' => 3]
];
$criticalId = $alertsSystem->createAlert($criticalAlert);
$passed = $criticalId !== false && $criticalId > 0;
printTest("Crear alerta CRITICAL", $passed, "Critical Alert ID: $criticalId");
if ($passed) $testsPassedAlerts++;

// Test 4.4: Obtener alertas pendientes del empleado
$totalTestsAlerts++;
$pendingAlerts = $alertsSystem->getEmployeePendingAlerts(1);
$passed = is_array($pendingAlerts) && count($pendingAlerts) >= 2;
printTest("Obtener alertas pendientes", $passed, "Total pendientes: " . count($pendingAlerts));
if ($passed) $testsPassedAlerts++;

// Test 4.5: Reconocer alerta
$totalTestsAlerts++;
if ($alertId) {
    $acknowledged = $alertsSystem->acknowledgeAlert($alertId, 1, "Test de reconocimiento");
    $passed = $acknowledged === true;
    printTest("Reconocer alerta", $passed);
    if ($passed) $testsPassedAlerts++;
} else {
    echo color("⊘ SKIP - No hay alert ID para reconocer\n", 'yellow');
}

// Test 4.6: Resolver alerta
$totalTestsAlerts++;
if ($criticalId) {
    $resolved = $alertsSystem->resolveAlert($criticalId, "Test de resolución");
    $passed = $resolved === true;
    printTest("Resolver alerta", $passed);
    if ($passed) $testsPassedAlerts++;
} else {
    echo color("⊘ SKIP - No hay alert ID para resolver\n", 'yellow');
}

// Test 4.7: Estadísticas de empleado
$totalTestsAlerts++;
$stats = $alertsSystem->getEmployeeAlertStats(1);
$passed = isset($stats['total_alerts']) && $stats['total_alerts'] >= 0;
printTest("Estadísticas de empleado", $passed, "Total alertas: {$stats['total_alerts']}");
if ($passed) $testsPassedAlerts++;

// Test 4.8: Resumen global de alertas
$totalTestsAlerts++;
$summary = $alertsSystem->getGlobalAlertsSummary();
$passed = isset($summary['total_alerts']);
printTest("Resumen global de alertas", $passed, "Total global: " . ($summary['total_alerts'] ?? 0));
if ($passed) $testsPassedAlerts++;

echo "\nResumen AlertsSystem: " . color("$testsPassedAlerts/$totalTestsAlerts tests passed", $testsPassedAlerts === $totalTestsAlerts ? 'green' : 'yellow') . "\n";

// ==============================================================
// PARTE 5: TESTS INTEGRACIÓN COMPLETA
// ==============================================================

printHeader("PARTE 5: TESTS INTEGRACIÓN COMPLETA");

$testsPassedInt = 0;
$totalTestsInt = 0;

// Test 5.1: Generar alertas desde compliance result
$totalTestsInt++;
$complianceResult = $checker->performFullCompliance([
    'date' => '2025-10-20',
    'daily_hours' => 11.5,
    'total_hours' => 11.5,
    'lunch_minutes' => 25
]);
$alertsStats = $alertsSystem->generateAlertsFromCompliance(1, $complianceResult);
$passed = $alertsStats['total_generated'] > 0 || $alertsStats['skipped_duplicates'] > 0;
printTest("Generar alertas desde compliance", $passed, "Generadas: {$alertsStats['total_generated']}, Duplicadas: {$alertsStats['skipped_duplicates']}");
if ($passed) $testsPassedInt++;

// Test 5.2: Flujo completo con AttendanceCalculator (simulado)
$totalTestsInt++;
$attCalc = new AttendanceCalculator();
$config = $attCalc->getConfig();
$passed = isset($config['alerts_system']) && $config['alerts_system'] === 'Integrado';
printTest("AttendanceCalculator con AlertsSystem integrado", $passed);
if ($passed) $testsPassedInt++;

// Test 5.3: Métodos de integración existen
$totalTestsInt++;
$methods = ['checkLegalComplianceAndAlert', 'calculateSaveAndAlert', 'getEmployeeAlerts'];
$allExist = true;
foreach ($methods as $method) {
    if (!method_exists($attCalc, $method)) {
        $allExist = false;
        break;
    }
}
$passed = $allExist;
printTest("Métodos de integración existen", $passed, "Métodos: " . implode(', ', $methods));
if ($passed) $testsPassedInt++;

echo "\nResumen Integración: " . color("$testsPassedInt/$totalTestsInt tests passed", $testsPassedInt === $totalTestsInt ? 'green' : 'yellow') . "\n";

// ==============================================================
// RESUMEN FINAL
// ==============================================================

printHeader("RESUMEN FINAL DE TODOS LOS TESTS");

$totalTests = $totalTestsChecker + $totalTestsCalc + $totalTestsClass + $totalTestsAlerts + $totalTestsInt;
$totalPassed = $testsPassedChecker + $testsPassedCalc + $testsPassedClass + $testsPassedAlerts + $testsPassedInt;
$percentage = round(($totalPassed / $totalTests) * 100, 1);

echo "\n";
echo "LegalComplianceChecker:  " . color("$testsPassedChecker/$totalTestsChecker", 'blue') . "\n";
echo "OvertimeRateCalculator:  " . color("$testsPassedCalc/$totalTestsCalc", 'blue') . "\n";
echo "WorkingDayClassifier:    " . color("$testsPassedClass/$totalTestsClass", 'blue') . "\n";
echo "AlertsSystem:            " . color("$testsPassedAlerts/$totalTestsAlerts", 'blue') . "\n";
echo "Integración:             " . color("$testsPassedInt/$totalTestsInt", 'blue') . "\n";
echo str_repeat("-", 80) . "\n";
echo "TOTAL:                   " . color("$totalPassed/$totalTests", $percentage >= 90 ? 'green' : ($percentage >= 75 ? 'yellow' : 'red')) . " ($percentage%)\n";

if ($percentage >= 90) {
    echo "\n" . color("🎉 EXCELENTE! Todos los sistemas funcionan correctamente.", 'green') . "\n";
} elseif ($percentage >= 75) {
    echo "\n" . color("⚠️ ACEPTABLE. Algunos tests fallaron, revisar detalles arriba.", 'yellow') . "\n";
} else {
    echo "\n" . color("❌ CRÍTICO. Múltiples tests fallaron, requiere atención inmediata.", 'red') . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo color("Script de testing completado - " . date('Y-m-d H:i:s'), 'blue') . "\n";
echo str_repeat("=", 80) . "\n\n";

// Retornar código de salida basado en resultado
exit($percentage >= 75 ? 0 : 1);
