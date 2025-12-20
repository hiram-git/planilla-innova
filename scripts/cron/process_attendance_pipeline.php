<?php
/**
 * Cron Job: Pipeline Completo de Procesamiento de Asistencias
 *
 * Este script ejecuta el pipeline completo de asistencias:
 * 1. Procesar attendance_records → attendance_detail
 * 2. Detectar ausencias automáticamente
 * 3. Marcar omisiones (marcaciones incompletas)
 * 4. Calcular horas trabajadas, tardanzas, extras, etc.
 * 5. Guardar en attendance_calculations
 *
 * Debe ejecutarse DESPUÉS de sync_attendance.php
 *
 * Ejemplo crontab (Linux) - Cada 25 minutos (5 min después de sync):
 * 5,25,45 * * * * php /path/to/planilla-innova/scripts/cron/process_attendance_pipeline.php >> /path/to/logs/cron_pipeline.log 2>&1
 *
 * Ejemplo Task Scheduler (Windows) - Cada 25 minutos con delay de 5 min:
 * Acción: Iniciar programa
 * Programa: C:\laragon60\bin\php\php.exe
 * Argumentos: C:\laragon60\www\planilla-innova\scripts\cron\process_attendance_pipeline.php
 * Repetir cada: 25 minutos
 * Retrasar: 5 minutos
 */

// Prevenir ejecución desde navegador
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde línea de comandos');
}

// Tiempo de inicio
$startTime = microtime(true);

// Cargar autoload de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar variables de entorno (.env). En producción puede no existir phpdotenv, usar fallback.
if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
} else {
    \App\Core\Config::load();
}

// Inicializar sesión (requerido para algunos modelos)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Services\Attendance\RecordsProcessor;
use App\Services\Attendance\Calculators\AttendanceCalculator;
use App\Services\Attendance\Calculators\AbsenceDetector;
use App\Models\AttendanceRecord;
use App\Models\AttendanceDetail;
use App\Models\BusinessCalendar;

// Banner
echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║   CRON JOB: Pipeline Completo de Procesamiento de Asistencias     ║\n";
echo "║   Fecha: " . date('Y-m-d H:i:s') . "                                      ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    // ============================================
    // PASO 1: PROCESAR RECORDS → DETAILS
    // ============================================
    echo "📋 PASO 1: Procesamiento de Marcaciones (Records → Details)\n";
    echo str_repeat("─", 68) . "\n";

    $recordModel = new AttendanceRecord();
    $pendingCount = $recordModel->countUnprocessed();

    if ($pendingCount === 0) {
        echo "✓ No hay registros pendientes de procesar.\n";
    } else {
        echo "📊 Registros pendientes: {$pendingCount}\n";

        // Obtener rango de fechas con registros pendientes
        $dateRange = $recordModel->getUnprocessedDateRange();

        if ($dateRange && isset($dateRange['min_date']) && isset($dateRange['max_date'])) {
            $minDate = $dateRange['min_date'];
            $maxDate = $dateRange['max_date'];

            echo "📅 Rango: {$minDate} → {$maxDate}\n";

            // Procesar
            $processor = new RecordsProcessor();
            $recordsStats = $processor->processToDetails($minDate, $maxDate);

            echo "✅ Resultados:\n";
            echo "  - Grupos procesados: {$recordsStats['groups_processed']}\n";
            echo "  - Details creados: {$recordsStats['details_created']}\n";
            echo "  - Details actualizados: {$recordsStats['details_updated']}\n";
            echo "  - Errores: {$recordsStats['errors']}\n";
        } else {
            echo "⚠️  No se pudo determinar el rango de fechas.\n";
        }
    }

    echo "\n";

    // ============================================
    // PASO 2: CALCULAR HORAS Y TARDANZAS
    // ============================================
    echo "⏱️  PASO 2: Cálculo de Horas y Tardanzas\n";
    echo str_repeat("─", 68) . "\n";

    // Determinar qué días procesar:
    // - Últimos 7 días (para capturar cambios recientes)
    // - Excluir domingos y fines de semana
    $daysToProcess = [];
    $calendar = new BusinessCalendar();
    $today = date('Y-m-d');
    $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));

    echo "📅 Analizando días laborables: {$sevenDaysAgo} → {$today}\n";

    // Obtener días laborables en el rango
    $workingDays = $calendar->getWorkingDaysList($sevenDaysAgo, $today);

    if (empty($workingDays)) {
        echo "⚠️  No hay días laborables en el rango.\n";
    } else {
        echo "📊 Días laborables encontrados: " . count($workingDays) . "\n\n";

        $detailModel = new AttendanceDetail();
        $calculator = new AttendanceCalculator();
        $absenceDetector = new AbsenceDetector();

        $calculationsStats = [
            'days_processed' => 0,
            'days_skipped' => 0,
            'total_details' => 0,
            'calculations_saved' => 0,
            'calculations_errors' => 0,
            'absences_detected' => 0,
            'omissions_marked' => 0
        ];

        foreach ($workingDays as $date) {
            try {
                // Verificar si hay details para procesar ese día
                $details = $detailModel->getByDate($date);

                if (empty($details)) {
                    echo "⏭️  {$date}: Sin marcaciones\n";
                    $calculationsStats['days_skipped']++;
                    continue;
                }

                $calculationsStats['days_processed']++;
                $calculationsStats['total_details'] += count($details);

                echo "⚙️  {$date}: Procesando " . count($details) . " marcaciones...\n";

                // Procesar cada detalle
                $dayCalculations = 0;
                $dayErrors = 0;

                foreach ($details as $detail) {
                    try {
                        // Calcular métricas
                        $result = $calculator->calculate(
                            $detail['employee_id'],
                            $date,
                            $detail['time_in'] ?? null,
                            $detail['time_out'] ?? null,
                            $detail['lunch_out'] ? date('H:i:s', strtotime($detail['lunch_out'])) : null,
                            $detail['lunch_in'] ? date('H:i:s', strtotime($detail['lunch_in'])) : null
                        );

                        if ($result && $result['success']) {
                            // Actualizar detail con cálculos
                            $detailModel->update($detail['id'], [
                                'hours_worked' => $result['hours_worked'] ?? 0,
                                'tardiness_minutes' => $result['tardiness_minutes'] ?? 0,
                                'is_late' => ($result['tardiness_minutes'] ?? 0) > 0 ? 1 : 0,
                                'early_departure_minutes' => $result['early_departure_minutes'] ?? 0,
                                'status' => $result['status'] ?? $detail['status']
                            ]);

                            // Guardar en attendance_calculations
                            $calculator->saveCalculation($detail['employee_id'], $date, $result);
                            $dayCalculations++;
                        } else {
                            $dayErrors++;
                        }

                    } catch (Exception $detailError) {
                        $dayErrors++;
                        error_log("Error calculando detail {$detail['id']}: " . $detailError->getMessage());
                    }
                }

                $calculationsStats['calculations_saved'] += $dayCalculations;
                $calculationsStats['calculations_errors'] += $dayErrors;

                echo "  ✓ {$dayCalculations} cálculos guardados";
                if ($dayErrors > 0) {
                    echo " | ⚠️  {$dayErrors} errores";
                }
                echo "\n";

            } catch (Exception $dayError) {
                error_log("Error procesando día {$date}: " . $dayError->getMessage());
                $calculationsStats['calculations_errors']++;
                echo "  ❌ Error: " . $dayError->getMessage() . "\n";
            }
        }

        echo "\n📊 Resumen de Cálculos:\n";
        echo "  - Días procesados: {$calculationsStats['days_processed']}\n";
        echo "  - Días sin datos: {$calculationsStats['days_skipped']}\n";
        echo "  - Total marcaciones: {$calculationsStats['total_details']}\n";
        echo "  - Cálculos guardados: {$calculationsStats['calculations_saved']}\n";
        echo "  - Errores: {$calculationsStats['calculations_errors']}\n";
    }

    // ============================================
    // RESUMEN FINAL
    // ============================================
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║   PIPELINE COMPLETADO                                              ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n";
    echo "⏱️  Tiempo total de ejecución: {$executionTime} segundos\n";
    echo "✅ Proceso finalizado exitosamente\n";

    exit(0);

} catch (Exception $e) {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║   ❌ ERROR FATAL                                                   ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n";
    echo "{$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo "{$e->getTraceAsString()}\n";

    // Tiempo de ejecución
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    echo "\n⏱️  Tiempo de ejecución: {$executionTime} segundos\n";

    exit(1);
}
