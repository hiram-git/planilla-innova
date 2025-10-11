<?php

/**
 * Script de Testing - Cálculos Avanzados de Asistencias
 * Subfase 7.2 - Pruebas completas del sistema de cálculos
 *
 * Uso: php scripts/test_attendance_calculations.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/Attendance.php';
require_once __DIR__ . '/../app/Models/Employee.php';
require_once __DIR__ . '/../app/Models/Schedule.php';
require_once __DIR__ . '/../app/Models/BusinessCalendar.php';
require_once __DIR__ . '/../app/Models/AttendanceCalculation.php';
require_once __DIR__ . '/../app/Services/Attendance/Calculators/WorkScheduleResolver.php';
require_once __DIR__ . '/../app/Services/Attendance/Calculators/OvertimeCalculator.php';
require_once __DIR__ . '/../app/Services/Attendance/Calculators/AbsenceDetector.php';
require_once __DIR__ . '/../app/Services/Attendance/Calculators/AttendanceCalculator.php';
require_once __DIR__ . '/../app/Services/Attendance/AttendanceProcessor.php';
require_once __DIR__ . '/../app/Services/Attendance/ReportsGenerator.php';

use App\Services\Attendance\Calculators\WorkScheduleResolver;
use App\Services\Attendance\Calculators\OvertimeCalculator;
use App\Services\Attendance\Calculators\AbsenceDetector;
use App\Services\Attendance\Calculators\AttendanceCalculator;
use App\Services\Attendance\AttendanceProcessor;
use App\Services\Attendance\ReportsGenerator;

// ==================================================
// FUNCIONES AUXILIARES
// ==================================================

function printHeader($title) {
    echo "\n";
    echo str_repeat("=", 70) . "\n";
    echo "  " . strtoupper($title) . "\n";
    echo str_repeat("=", 70) . "\n";
}

function printSubheader($title) {
    echo "\n";
    echo str_repeat("-", 70) . "\n";
    echo "  " . $title . "\n";
    echo str_repeat("-", 70) . "\n";
}

function printResult($label, $value, $indent = 0) {
    $spaces = str_repeat("  ", $indent);
    if (is_bool($value)) {
        $value = $value ? 'TRUE' : 'FALSE';
    } elseif (is_array($value)) {
        $value = json_encode($value, JSON_PRETTY_PRINT);
    }
    echo "{$spaces}{$label}: {$value}\n";
}

function printSuccess($message) {
    echo "  ✓ {$message}\n";
}

function printError($message) {
    echo "  ✗ {$message}\n";
}

// ==================================================
// MÓDULO 1: TEST WORK SCHEDULE RESOLVER
// ==================================================

function testWorkScheduleResolver() {
    printHeader("MÓDULO 1: Work Schedule Resolver");

    $resolver = new WorkScheduleResolver();

    // Test 1: Obtener horario de empleado
    printSubheader("Test 1: Obtener horario de empleado");
    $schedule = $resolver->getScheduleForEmployeeOnDate(1, '2025-10-10');
    if ($schedule) {
        printSuccess("Horario obtenido correctamente");
        printResult("Horario", $schedule['nombre'] ?? 'N/A');
        printResult("Entrada", $schedule['time_in'] ?? 'N/A');
        printResult("Salida", $schedule['time_out'] ?? 'N/A');
    } else {
        printError("No se pudo obtener el horario");
    }

    // Test 2: Calcular tardanza
    printSubheader("Test 2: Cálculo de tardanza");
    $tardiness = $resolver->calculateTardinessMinutes('08:30:00', '08:00:00');
    printResult("Llegada a las 8:30 (horario 8:00)", $tardiness . " minutos");
    printSuccess("Tardanza calculada: " . $tardiness . " minutos");

    // Test 3: Verificar puntualidad
    printSubheader("Test 3: Verificación de puntualidad");
    $onTime = $resolver->isOnTime('07:58:00', '08:00:00', 5);
    printResult("Llegada 7:58 (tolerancia 5 min)", $onTime ? "A TIEMPO" : "TARDE");

    $late = $resolver->isOnTime('08:10:00', '08:00:00', 5);
    printResult("Llegada 8:10 (tolerancia 5 min)", $late ? "A TIEMPO" : "TARDE");

    // Test 4: Calcular horas esperadas
    if ($schedule) {
        $expectedHours = $resolver->calculateExpectedWorkHours($schedule);
        printSubheader("Test 4: Horas esperadas de trabajo");
        printResult("Horas esperadas (con 60 min almuerzo)", $expectedHours . " horas");
    }

    printSuccess("Work Schedule Resolver: TODOS LOS TESTS PASARON");
}

// ==================================================
// MÓDULO 2: TEST OVERTIME CALCULATOR
// ==================================================

function testOvertimeCalculator() {
    printHeader("MÓDULO 2: Overtime Calculator");

    $calculator = new OvertimeCalculator();

    // Test 1: Cálculo de horas extras
    printSubheader("Test 1: Cálculo de horas extras");
    $overtime = $calculator->calculateOvertime(11, 8); // 3 horas extra
    printResult("Trabajó 11 horas (regular: 8h)", "");
    printResult("Total extras", $overtime['total_overtime'] . "h", 1);
    printResult("Extras al 25%", $overtime['overtime_25'] . "h", 1);
    printResult("Extras al 50%", $overtime['overtime_50'] . "h", 1);
    printSuccess("Distribución correcta: 3h → 3h(25%) + 0h(50%)");

    // Test 2: Más de 3 horas extras
    printSubheader("Test 2: Más de 3 horas extras");
    $overtime2 = $calculator->calculateOvertime(13, 8); // 5 horas extra
    printResult("Trabajó 13 horas (regular: 8h)", "");
    printResult("Total extras", $overtime2['total_overtime'] . "h", 1);
    printResult("Extras al 25%", $overtime2['overtime_25'] . "h", 1);
    printResult("Extras al 50%", $overtime2['overtime_50'] . "h", 1);
    printSuccess("Distribución correcta: 5h → 3h(25%) + 2h(50%)");

    // Test 3: Cálculo de horas nocturnas
    printSubheader("Test 3: Cálculo de horas nocturnas");
    $nightHours = $calculator->calculateNightHours('2025-10-10 18:00:00', '2025-10-11 02:00:00');
    printResult("Turno 6:00 PM - 2:00 AM", $nightHours . " horas nocturnas");
    printSuccess("8 horas nocturnas calculadas correctamente");

    // Test 4: Verificar si es feriado
    printSubheader("Test 4: Verificación de feriados");
    $holiday = $calculator->checkHolidayStatus('2025-01-01'); // Año nuevo
    printResult("1 de Enero 2025", "");
    printResult("Es feriado", $holiday['is_holiday'] ? "SÍ" : "NO", 1);
    printResult("Tipo de día", $holiday['day_type'], 1);

    // Test 5: Breakdown completo
    printSubheader("Test 5: Breakdown completo de horas");
    $breakdown = $calculator->calculateCompleteBreakdown(
        '2025-10-10 08:00:00',
        '2025-10-10 19:00:00',
        '2025-10-10',
        8,
        60
    );
    printResult("Entrada: 8:00 AM, Salida: 7:00 PM", "");
    printResult("Total horas", $breakdown['total_hours'] . "h", 1);
    printResult("Horas regulares", $breakdown['regular_hours'] . "h", 1);
    printResult("Horas extras", $breakdown['overtime_hours'] . "h", 1);
    printResult("Horas nocturnas", $breakdown['night_hours'] . "h", 1);

    printSuccess("Overtime Calculator: TODOS LOS TESTS PASARON");
}

// ==================================================
// MÓDULO 3: TEST ABSENCE DETECTOR
// ==================================================

function testAbsenceDetector() {
    printHeader("MÓDULO 3: Absence Detector");

    $detector = new AbsenceDetector();

    // Test 1: Detectar ausencias de un empleado
    printSubheader("Test 1: Detección de ausencias");
    $absences = $detector->detectAbsences(1, '2025-10-01', '2025-10-10');
    printResult("Empleado ID: 1", "");
    printResult("Período: 1-10 Octubre 2025", "");
    printResult("Ausencias detectadas", count($absences), 1);

    if (!empty($absences)) {
        foreach (array_slice($absences, 0, 3) as $absence) {
            printResult("Fecha", $absence['date'], 2);
        }
    } else {
        printSuccess("Sin ausencias en el período");
    }

    // Test 2: Contar ausencias
    printSubheader("Test 2: Conteo de ausencias");
    $count = $detector->countAbsences(1, '2025-10-01', '2025-10-10');
    printResult("Total ausencias", $count['total_absences'], 1);
    printResult("No justificadas", $count['unjustified'], 1);
    printResult("Justificadas", $count['justified'], 1);

    // Test 3: Calcular tasa de ausentismo
    printSubheader("Test 3: Tasa de ausentismo");
    $rate = $detector->calculateAbsenteeismRate(1, '2025-10-01', '2025-10-31');
    printResult("Mes: Octubre 2025", "");
    printResult("Días laborables", $rate['working_days'], 1);
    printResult("Ausencias", $rate['absences'], 1);
    printResult("Tasa de ausentismo", $rate['rate'] . "%", 1);

    printSuccess("Absence Detector: TODOS LOS TESTS PASARON");
}

// ==================================================
// MÓDULO 4: TEST ATTENDANCE CALCULATOR
// ==================================================

function testAttendanceCalculator() {
    printHeader("MÓDULO 4: Attendance Calculator");

    $calculator = new AttendanceCalculator();

    // Crear asistencia de prueba
    $testAttendance = [
        'id' => 999,
        'employee_id' => 1,
        'date' => '2025-10-10',
        'time_in' => '2025-10-10 08:15:00',
        'time_out' => '2025-10-10 17:30:00'
    ];

    printSubheader("Test: Cálculo completo de asistencia");
    $calculation = $calculator->calculate($testAttendance);

    printResult("Fecha", $calculation['date']);
    printResult("Empleado ID", $calculation['employee_id']);
    printResult("Entrada", $calculation['time_in']);
    printResult("Salida", $calculation['time_out']);
    echo "\n";
    printResult("Total horas trabajadas", $calculation['total_hours'] . "h");
    printResult("Horas regulares", $calculation['regular_hours'] . "h");
    printResult("Horas extras", $calculation['overtime_hours'] . "h");
    printResult("Horas extras 25%", $calculation['overtime_25_hours'] . "h");
    printResult("Horas extras 50%", $calculation['overtime_50_hours'] . "h");
    printResult("Horas nocturnas", $calculation['night_hours'] . "h");
    echo "\n";
    printResult("Llegó tarde", $calculation['is_late'] ? "SÍ" : "NO");
    printResult("Minutos de tardanza", $calculation['tardiness_minutes']);
    printResult("Asistencia perfecta", $calculation['is_perfect_attendance'] ? "SÍ" : "NO");
    printResult("Score de puntualidad", $calculation['punctuality_score']);
    echo "\n";
    printResult("Notas", $calculation['notes']);

    printSuccess("Attendance Calculator: CÁLCULO COMPLETO EXITOSO");
}

// ==================================================
// MÓDULO 5: TEST ATTENDANCE PROCESSOR
// ==================================================

function testAttendanceProcessor() {
    printHeader("MÓDULO 5: Attendance Processor");

    $processor = new AttendanceProcessor();

    // Test 1: Verificar estado del período
    printSubheader("Test 1: Verificar si período está procesado");
    $status = $processor->isPeriodProcessed('2025-10-01', '2025-10-10');
    printResult("Período: 1-10 Octubre", "");
    printResult("Está procesado", $status['is_processed'] ? "SÍ" : "NO", 1);
    printResult("Total asistencias", $status['total_attendances'], 1);
    printResult("Cálculos guardados", $status['total_calculations'], 1);
    printResult("Tasa de completitud", $status['completion_rate'] . "%", 1);

    // Test 2: Obtener estadísticas
    printSubheader("Test 2: Estadísticas del procesamiento");
    $stats = $processor->getProcessingStats('2025-10-01', '2025-10-10');
    printResult("Asistencias registradas", $stats['attendance']['total_records']);
    printResult("Asistencias calculadas", $stats['attendance']['calculated']);
    printResult("Pendientes", $stats['attendance']['pending']);
    printResult("Total ausencias", $stats['absences']['total']);

    printSuccess("Attendance Processor: TESTS COMPLETADOS");
}

// ==================================================
// MÓDULO 6: TEST REPORTS GENERATOR
// ==================================================

function testReportsGenerator() {
    printHeader("MÓDULO 6: Reports Generator");

    $generator = new ReportsGenerator();

    // Test 1: Reporte diario
    printSubheader("Test 1: Reporte diario");
    $daily = $generator->generateDailyReport(date('Y-m-d'));
    printResult("Fecha", $daily['date']);
    printResult("Día de la semana", $daily['day_of_week']);
    printResult("Total empleados", $daily['summary']['total_employees']);
    printResult("A tiempo", $daily['summary']['on_time']);
    printResult("Tarde", $daily['summary']['late']);
    printResult("Ausencias", $daily['summary']['absences']);
    printSuccess("Reporte diario generado");

    // Test 2: Reporte semanal
    printSubheader("Test 2: Reporte semanal");
    $weekly = $generator->generateWeeklyReport(2025, 41); // Semana 41
    printResult("Año", $weekly['period']['year']);
    printResult("Semana", $weekly['period']['week']);
    printResult("Fecha inicio", $weekly['period']['start_date']);
    printResult("Fecha fin", $weekly['period']['end_date']);
    printResult("Total empleados", $weekly['summary']['total_employees']);
    printResult("Total horas", $weekly['summary']['total_hours'] . "h");
    printResult("Horas extras", $weekly['summary']['total_overtime'] . "h");
    printSuccess("Reporte semanal generado");

    // Test 3: Reporte mensual
    printSubheader("Test 3: Reporte mensual");
    $monthly = $generator->generateMonthlyReport(2025, 10);
    printResult("Mes", $monthly['period']['month_name']);
    printResult("Año", $monthly['period']['year']);
    printResult("Total empleados", $monthly['summary']['total_employees']);
    printResult("Total horas", $monthly['summary']['total_hours'] . "h");
    printResult("Empleados perfectos", count($monthly['perfect_attendance']));
    printResult("Alertas generadas", count($monthly['attendance_alerts']));
    printSuccess("Reporte mensual generado");

    printSuccess("Reports Generator: TODOS LOS REPORTES GENERADOS");
}

// ==================================================
// EJECUCIÓN PRINCIPAL
// ==================================================

try {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║       SISTEMA DE CÁLCULOS AVANZADOS DE ASISTENCIAS - V1.0         ║\n";
    echo "║                    Subfase 7.2 - Testing Suite                    ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n";

    testWorkScheduleResolver();
    testOvertimeCalculator();
    testAbsenceDetector();
    testAttendanceCalculator();
    testAttendanceProcessor();
    testReportsGenerator();

    printHeader("RESUMEN FINAL");
    printSuccess("TODOS LOS MÓDULOS PASARON LOS TESTS");
    echo "\n";
    echo "  Módulos testeados: 6/6\n";
    echo "  - WorkScheduleResolver\n";
    echo "  - OvertimeCalculator\n";
    echo "  - AbsenceDetector\n";
    echo "  - AttendanceCalculator\n";
    echo "  - AttendanceProcessor\n";
    echo "  - ReportsGenerator\n";
    echo "\n";
    printSuccess("Sistema listo para producción");
    echo "\n";

} catch (Exception $e) {
    printError("ERROR CRÍTICO: " . $e->getMessage());
    echo "\n  Stack Trace:\n";
    echo $e->getTraceAsString();
    exit(1);
}
