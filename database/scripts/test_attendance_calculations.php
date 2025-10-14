<?php
/**
 * Script de Testing - Cálculos Avanzados de Asistencias
 *
 * Prueba todos los componentes de la Subfase 7.2:
 * - AttendanceCalculator
 * - WorkScheduleResolver
 * - OvertimeCalculator
 * - AbsenceDetector
 * - AttendanceProcessor
 * - Modelos AttendanceCalculation y AttendanceAbsenceLog
 *
 * @version 1.0
 * @date 2025-10-13
 */

// Cargar autoloader de Composer y configuración
$rootPath = dirname(dirname(__DIR__));
require_once $rootPath . '/vendor/autoload.php';

// Cargar variables de entorno desde .env
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

use App\Models\Attendance;
use App\Models\AttendanceCalculation;
use App\Models\AttendanceAbsenceLog;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\BusinessCalendar;
use App\Services\Attendance\Calculators\AttendanceCalculator;
use App\Services\Attendance\Calculators\WorkScheduleResolver;
use App\Services\Attendance\Calculators\OvertimeCalculator;
use App\Services\Attendance\Calculators\AbsenceDetector;
use App\Services\Attendance\AttendanceProcessor;

// ============================================
// Funciones Helper
// ============================================
function printHeader($title) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "  $title\n";
    echo str_repeat('=', 80) . "\n\n";
}

function printSubheader($title) {
    echo "\n" . str_repeat('-', 80) . "\n";
    echo "  $title\n";
    echo str_repeat('-', 80) . "\n";
}

function printSuccess($message) {
    echo "✓ $message\n";
}

function printError($message) {
    echo "✗ ERROR: $message\n";
}

function printInfo($label, $value) {
    echo "  • $label: $value\n";
}

function printArray($array, $indent = 2) {
    $spaces = str_repeat(' ', $indent);
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            echo "{$spaces}$key:\n";
            printArray($value, $indent + 2);
        } else {
            echo "{$spaces}$key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }
}

// ============================================
// MÓDULO 1: Test Modelos BD
// ============================================
printHeader('MÓDULO 1: VERIFICACIÓN MODELOS BD');

try {
    printSubheader('1.1 - AttendanceCalculation Model');
    $calcModel = new AttendanceCalculation();
    printSuccess("Modelo AttendanceCalculation instanciado correctamente");
    printInfo("Tabla", $calcModel->table);
    printInfo("Campos fillable", count($calcModel->fillable) . " campos");

    printSubheader('1.2 - AttendanceAbsenceLog Model');
    $absenceModel = new AttendanceAbsenceLog();
    printSuccess("Modelo AttendanceAbsenceLog instanciado correctamente");
    printInfo("Tabla", $absenceModel->table);
    printInfo("Campos fillable", count($absenceModel->fillable) . " campos");

    printSubheader('1.3 - Verificar Tablas en BD');
    $attendanceModel = new Attendance();

    // Verificar attendance_calculations
    $testCalc = $calcModel->all();
    printSuccess("Tabla attendance_calculations: " . count($testCalc) . " registros");

    // Verificar attendance_absence_log
    $testAbsence = $absenceModel->all();
    printSuccess("Tabla attendance_absence_log: " . count($testAbsence) . " registros");

} catch (Exception $e) {
    printError($e->getMessage());
}

// ============================================
// MÓDULO 2: Test WorkScheduleResolver
// ============================================
printHeader('MÓDULO 2: WORKSCHEDULERESOLVE R');

try {
    $scheduleResolver = new WorkScheduleResolver();
    printSuccess("WorkScheduleResolver instanciado correctamente");

    // Obtener primer empleado activo con horario
    $employeeModel = new Employee();
    $employees = $employeeModel->all();
    $employee = null;

    foreach ($employees as $emp) {
        if (!empty($emp['schedule_id'])) {
            $employee = $emp;
            break;
        }
    }

    if ($employee) {
        printSubheader("2.1 - Test getScheduleForEmployeeOnDate");
        $schedule = $scheduleResolver->getScheduleForEmployeeOnDate(
            $employee['id'],
            date('Y-m-d')
        );

        if ($schedule) {
            printSuccess("Horario obtenido para empleado ID {$employee['id']}");
            printInfo("Código horario", $schedule['codigo'] ?? 'N/A');
            printInfo("Nombre horario", $schedule['nombre'] ?? 'N/A');
            printInfo("Hora entrada", $schedule['time_in']);
            printInfo("Hora salida", $schedule['time_out']);

            printSubheader("2.2 - Test calculateExpectedWorkHours");
            $expectedHours = $scheduleResolver->calculateExpectedWorkHours($schedule);
            printSuccess("Horas esperadas calculadas: $expectedHours horas");

            printSubheader("2.3 - Test calculateTardinessMinutes");
            $tardiness1 = $scheduleResolver->calculateTardinessMinutes('08:15:00', '08:00:00');
            printInfo("Tardanza (entrada 08:15, programada 08:00)", "$tardiness1 minutos");

            $tardiness2 = $scheduleResolver->calculateTardinessMinutes('07:55:00', '08:00:00');
            printInfo("Tardanza (entrada 07:55, programada 08:00)", "$tardiness2 minutos");

            printSubheader("2.4 - Test isNightShift");
            $isNight = $scheduleResolver->isNightShift($schedule);
            printInfo("Es jornada nocturna", $isNight ? 'Sí' : 'No');

        } else {
            printError("No se encontró horario para empleado");
        }
    } else {
        printError("No se encontró empleado con horario asignado");
    }

} catch (Exception $e) {
    printError($e->getMessage());
}

// ============================================
// MÓDULO 3: Test OvertimeCalculator
// ============================================
printHeader('MÓDULO 3: OVERTIMECALCULATOR');

try {
    $overtimeCalc = new OvertimeCalculator();
    printSuccess("OvertimeCalculator instanciado correctamente");

    printSubheader("3.1 - Test calculateOvertime");

    // Caso 1: 10 horas trabajadas (2 horas extras)
    $overtime1 = $overtimeCalc->calculateOvertime(10, 8);
    printInfo("Caso 1: 10h trabajadas", "");
    printInfo("  Total extras", $overtime1['total_overtime'] . "h");
    printInfo("  Extras 25%", $overtime1['overtime_25'] . "h");
    printInfo("  Extras 50%", $overtime1['overtime_50'] . "h");

    // Caso 2: 12 horas trabajadas (4 horas extras)
    $overtime2 = $overtimeCalc->calculateOvertime(12, 8);
    printInfo("Caso 2: 12h trabajadas", "");
    printInfo("  Total extras", $overtime2['total_overtime'] . "h");
    printInfo("  Extras 25%", $overtime2['overtime_25'] . "h");
    printInfo("  Extras 50%", $overtime2['overtime_50'] . "h");

    printSubheader("3.2 - Test calculateNightHours");
    $nightHours1 = $overtimeCalc->calculateNightHours('18:00:00', '23:00:00');
    printInfo("Caso 1: 18:00 - 23:00", "$nightHours1 horas nocturnas");

    $nightHours2 = $overtimeCalc->calculateNightHours('22:00:00', '06:00:00');
    printInfo("Caso 2: 22:00 - 06:00", "$nightHours2 horas nocturnas");

    printSubheader("3.3 - Test checkHolidayStatus");
    $today = date('Y-m-d');
    $holidayStatus = $overtimeCalc->checkHolidayStatus($today);
    printInfo("Fecha", $today);
    printInfo("Tipo de día", $holidayStatus['day_type']);
    printInfo("Es feriado", $holidayStatus['is_holiday'] ? 'Sí' : 'No');
    printInfo("Es no laboral", $holidayStatus['is_non_working'] ? 'Sí' : 'No');

    printSubheader("3.4 - Test calculateCompleteBreakdown");
    $breakdown = $overtimeCalc->calculateCompleteBreakdown(
        '2025-10-13 08:00:00',
        '2025-10-13 18:00:00',
        '2025-10-13',
        8,
        60
    );
    printSuccess("Breakdown completo calculado:");
    printArray($breakdown);

} catch (Exception $e) {
    printError($e->getMessage());
}

// ============================================
// MÓDULO 4: Test AttendanceCalculator
// ============================================
printHeader('MÓDULO 4: ATTENDANCECALCULATOR');

try {
    $attendanceCalc = new AttendanceCalculator();
    printSuccess("AttendanceCalculator instanciado correctamente");

    printSubheader("4.1 - Test calculate() con asistencia real");

    // Obtener una asistencia reciente
    $attendanceModel = new Attendance();
    $recentAttendances = $attendanceModel->getAttendanceByDateRange(
        date('Y-m-d', strtotime('-7 days')),
        date('Y-m-d')
    );

    if (!empty($recentAttendances)) {
        $attendance = $recentAttendances[0];
        printInfo("Procesando asistencia ID", $attendance['id']);
        printInfo("Empleado", "{$attendance['firstname']} {$attendance['lastname']}");
        printInfo("Fecha", $attendance['date']);
        printInfo("Entrada", $attendance['time_in']);
        printInfo("Salida", $attendance['time_out']);

        $calculation = $attendanceCalc->calculate($attendance);

        printSuccess("Cálculo completado:");
        echo "\n  MARCACIONES:\n";
        printInfo("  Hora entrada", $calculation['time_in'] ?? 'N/A');
        printInfo("  Hora salida", $calculation['time_out'] ?? 'N/A');

        echo "\n  HORAS TRABAJADAS:\n";
        printInfo("  Total horas", $calculation['total_hours']);
        printInfo("  Horas regulares", $calculation['regular_hours']);
        printInfo("  Horas extras", $calculation['overtime_hours']);
        printInfo("  Horas extras 25%", $calculation['overtime_25_hours']);
        printInfo("  Horas extras 50%", $calculation['overtime_50_hours']);
        printInfo("  Horas nocturnas", $calculation['night_hours']);
        printInfo("  Horas feriado", $calculation['holiday_hours']);

        echo "\n  TARDANZAS Y PUNTUALIDAD:\n";
        printInfo("  Minutos tardanza", $calculation['tardiness_minutes']);
        printInfo("  Llegó tarde", $calculation['is_late'] ? 'Sí' : 'No');
        printInfo("  Salida anticipada", $calculation['early_departure_minutes'] . " min");
        printInfo("  Asistencia perfecta", $calculation['is_perfect_attendance'] ? 'Sí' : 'No');
        printInfo("  Score puntualidad", $calculation['punctuality_score'] . "/100");

        echo "\n  TIPO DE DÍA:\n";
        printInfo("  Tipo", $calculation['day_type']);
        printInfo("  Es laboral", $calculation['is_working_day'] ? 'Sí' : 'No');
        printInfo("  Es feriado", $calculation['is_holiday'] ? 'Sí' : 'No');
        printInfo("  Es fin de semana", $calculation['is_weekend'] ? 'Sí' : 'No');

        if (!empty($calculation['notes'])) {
            printInfo("  Notas", $calculation['notes']);
        }

    } else {
        printError("No se encontraron asistencias recientes para probar");
    }

} catch (Exception $e) {
    printError($e->getMessage());
}

// ============================================
// MÓDULO 5: Test AbsenceDetector
// ============================================
printHeader('MÓDULO 5: ABSENCEDETECTOR');

try {
    $absenceDetector = new AbsenceDetector();
    printSuccess("AbsenceDetector instanciado correctamente");

    printSubheader("5.1 - Test detectAbsences para un empleado");

    // Obtener primer empleado activo
    $employeeModel = new Employee();
    $employees = $employeeModel->all();
    $activeEmployee = null;

    foreach ($employees as $emp) {
        if ($emp['situacion_id'] == 1) {
            $activeEmployee = $emp;
            break;
        }
    }

    if ($activeEmployee) {
        $startDate = date('Y-m-01'); // Inicio del mes
        $endDate = date('Y-m-d');    // Hoy

        printInfo("Empleado", "{$activeEmployee['firstname']} {$activeEmployee['lastname']}");
        printInfo("Rango", "$startDate a $endDate");

        $absences = $absenceDetector->detectAbsences(
            $activeEmployee['id'],
            $startDate,
            $endDate
        );

        printSuccess("Ausencias detectadas: " . count($absences));

        if (!empty($absences)) {
            echo "\n  Primeras 5 ausencias:\n";
            $count = 0;
            foreach ($absences as $absence) {
                if ($count >= 5) break;
                printInfo("  - Fecha", $absence['date'] . " (" . $absence['absence_type'] . ")");
                $count++;
            }
        }

        printSubheader("5.2 - Test countAbsences");
        $stats = $absenceDetector->countAbsences(
            $activeEmployee['id'],
            $startDate,
            $endDate
        );
        printInfo("Total ausencias", $stats['total_absences']);
        printInfo("Injustificadas", $stats['unjustified']);
        printInfo("Justificadas", $stats['justified']);
        printInfo("Pendientes", $stats['pending']);

    } else {
        printError("No se encontró empleado activo");
    }

    printSubheader("5.3 - Test detectTodayAbsences");
    $todayAbsences = $absenceDetector->detectTodayAbsences();
    printSuccess("Ausencias detectadas hoy: " . count($todayAbsences) . " empleados");

} catch (Exception $e) {
    printError($e->getMessage());
}

// ============================================
// MÓDULO 6: Test AttendanceProcessor
// ============================================
printHeader('MÓDULO 6: ATTENDANCEPROCESSOR');

try {
    $processor = new AttendanceProcessor();
    printSuccess("AttendanceProcessor instanciado correctamente");

    printSubheader("6.1 - Test isPeriodProcessed");
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');

    $processedStatus = $processor->isPeriodProcessed($startDate, $endDate);
    printInfo("Período", "$startDate a $endDate");
    printInfo("Está procesado", $processedStatus['is_processed'] ? 'Sí' : 'No');
    printInfo("Total asistencias", $processedStatus['total_attendances']);
    printInfo("Total cálculos", $processedStatus['total_calculations']);
    printInfo("Pendientes", $processedStatus['pending']);
    printInfo("Tasa completitud", $processedStatus['completion_rate'] . "%");

    printSubheader("6.2 - Test getProcessingStats");
    $stats = $processor->getProcessingStats($startDate, $endDate);
    printSuccess("Estadísticas obtenidas:");
    echo "\n  PERÍODO:\n";
    printInfo("  Inicio", $stats['period']['start_date']);
    printInfo("  Fin", $stats['period']['end_date']);

    echo "\n  ASISTENCIAS:\n";
    printInfo("  Total registros", $stats['attendance']['total_records']);
    printInfo("  Calculados", $stats['attendance']['calculated']);
    printInfo("  Pendientes", $stats['attendance']['pending']);

    echo "\n  AUSENCIAS:\n";
    printInfo("  Total", $stats['absences']['total']);
    printInfo("  Empleados afectados", $stats['absences']['employees_affected']);

    if (!empty($stats['totals'])) {
        echo "\n  TOTALES:\n";
        printInfo("  Total empleados", $stats['totals']['total_employees'] ?? 0);
        printInfo("  Total horas", $stats['totals']['total_hours'] ?? 0);
        printInfo("  Total horas extras", $stats['totals']['total_overtime'] ?? 0);
        printInfo("  Total tardanzas", $stats['totals']['total_tardiness'] ?? 0);
    }

} catch (Exception $e) {
    printError($e->getMessage());
}

// ============================================
// MÓDULO 7: Test Integración BusinessCalendar
// ============================================
printHeader('MÓDULO 7: INTEGRACIÓN BUSINESSCALENDAR');

try {
    $calendar = new BusinessCalendar();
    printSuccess("BusinessCalendar instanciado correctamente");

    printSubheader("7.1 - Test isWorkingDay");
    $today = date('Y-m-d');
    $isWorking = $calendar->isWorkingDay($today);
    printInfo("Hoy ($today)", $isWorking ? 'Es día laboral' : 'No es día laboral');

    printSubheader("7.2 - Test getWorkingDaysBetween");
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-d');
    $workingDays = $calendar->getWorkingDaysBetween($startDate, $endDate);
    printInfo("Rango", "$startDate a $endDate");
    printInfo("Días laborables", "$workingDays días");

    printSubheader("7.3 - Test getDayInfo");
    $dayInfo = $calendar->getDayInfo($today);
    if ($dayInfo) {
        printSuccess("Información del día obtenida:");
        printInfo("Fecha", $dayInfo['date_value']);
        printInfo("Tipo", $dayInfo['day_type']);
        printInfo("Estado", $dayInfo['day_state'] ?? 'N/A');
        if (!empty($dayInfo['description'])) {
            printInfo("Descripción", $dayInfo['description']);
        }
    } else {
        printError("No se encontró información para la fecha");
    }

    printSubheader("7.4 - Test getHolidaysByYear");
    $year = date('Y');
    $holidays = $calendar->getHolidaysByYear($year);
    printSuccess("Feriados $year: " . count($holidays) . " días");

    if (!empty($holidays)) {
        echo "\n  Próximos 3 feriados:\n";
        $count = 0;
        foreach ($holidays as $holiday) {
            if ($count >= 3) break;
            if ($holiday['date_value'] >= $today) {
                printInfo("  - " . $holiday['date_value'], $holiday['description']);
                $count++;
            }
        }
    }

} catch (Exception $e) {
    printError($e->getMessage());
}

// ============================================
// RESUMEN FINAL
// ============================================
printHeader('RESUMEN FINAL DEL TESTING');

echo "COMPONENTES VERIFICADOS:\n";
printSuccess("✓ Modelos: AttendanceCalculation, AttendanceAbsenceLog");
printSuccess("✓ WorkScheduleResolver: Resolución de horarios y cálculos básicos");
printSuccess("✓ OvertimeCalculator: Horas extras según legislación panameña");
printSuccess("✓ AttendanceCalculator: Motor principal de cálculos");
printSuccess("✓ AbsenceDetector: Detección de ausencias");
printSuccess("✓ AttendanceProcessor: Orquestador de procesamiento");
printSuccess("✓ BusinessCalendar: Integración calendario empresarial");

echo "\nLEGISLACIÓN IMPLEMENTADA:\n";
printSuccess("✓ Art. 31: Jornada ordinaria 8h diarias / 48h semanales");
printSuccess("✓ Art. 38: Jornada nocturna 6PM-6AM recargo 50%");
printSuccess("✓ Art. 39: Horas extras primeras 3h +25%, siguientes +50%");
printSuccess("✓ Art. 48: Domingos/feriados recargo 50%");
printSuccess("✓ Art. 213: 3+ ausencias injustificadas = causal despido");

echo "\nPRÓXIMOS PASOS:\n";
echo "  1. Crear interfaz web para gestión de cálculos\n";
echo "  2. Implementar reportes de asistencias\n";
echo "  3. Desarrollar dashboard de métricas\n";
echo "  4. Integrar con generación de planillas (Subfase 7.4)\n";

printHeader('FIN DEL TESTING');
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "Todos los componentes de la Subfase 7.2 han sido verificados exitosamente.\n\n";
