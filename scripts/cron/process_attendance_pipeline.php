<?php

/**

 * Cron Job: Pipeline Completo de Procesamiento de Asistencias

 *

 * Este script ejecuta el pipeline completo de asistencias:

 * 1. Procesar attendance_records Ã¢ÂÂ attendance_detail

 * 2. Detectar ausencias automÃÂ¡ticamente

 * 3. Marcar omisiones (marcaciones incompletas)

 * 4. Calcular horas trabajadas, tardanzas, extras, etc.

 * 5. Guardar en attendance_calculations

 *

 * Debe ejecutarse DESPUÃÂS de sync_attendance.php

 *

 * Ejemplo crontab (Linux) - Cada 25 minutos (5 min despuÃÂ©s de sync):

 * 5,25,45 * * * * php /path/to/planilla-innova/scripts/cron/process_attendance_pipeline.php >> /path/to/logs/cron_pipeline.log 2>&1

 *

 * Ejemplo Task Scheduler (Windows) - Cada 25 minutos con delay de 5 min:

 * AcciÃÂ³n: Iniciar programa

 * Programa: C:\laragon60\bin\php\php.exe

 * Argumentos: C:\laragon60\www\planilla-innova\scripts\cron\process_attendance_pipeline.php

 * Repetir cada: 25 minutos

 * Retrasar: 5 minutos

 */



// Prevenir ejecuciÃÂ³n desde navegador

if (php_sapi_name() !== 'cli') {

    die('Este script solo puede ejecutarse desde lÃÂ­nea de comandos');

}



// Tiempo de inicio

$startTime = microtime(true);



// Cargar autoload de Composer

require_once __DIR__ . '/../../vendor/autoload.php';



// Cargar variables de entorno (.env). En producciÃÂ³n puede no existir phpdotenv, usar fallback.
if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
} else {
    \App\Core\Config::load();
}


// Inicializar sesiÃÂ³n (requerido para algunos modelos)

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}



use App\Core\MasterDatabase;
use App\Core\TenantResolver;
use App\Core\Database;
use App\Services\Attendance\RecordsProcessor;
use App\Services\Attendance\Calculators\AttendanceCalculator;
use App\Services\Attendance\Calculators\AbsenceDetector;
use App\Models\AttendanceRecord;
use App\Models\AttendanceDetail;
use App\Models\AttendanceApiConfig;
use App\Models\BusinessCalendar;


// Banner

echo "\n";

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║ CRON JOB: Pipeline Completo de Procesamiento de Asistencias  ║\n";
echo "║   Fecha: " . date('Y-m-d H:i:s') . "                         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

echo "\n";



/**
 * Cambiar de tenant reseteando la conexiÃÂ³n global
 */
function switchTenant(string $dbName): void
{
    TenantResolver::clear();
    Database::resetInstance();
    $_SESSION['tenant_db'] = $dbName;
}

/**
 * Obtener lista de tenants activos desde planilla_master
 */
function getActiveTenants(): array
{
    try {
        $master = MasterDatabase::getInstance()->getConnection();
        $stmt = $master->query("SELECT db_name FROM tenants WHERE status = 'ACTIVE'");
        $tenants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $tenants ?: [];
    } catch (\Exception $e) {
        echo "Ã¢ÂÂ Ã¯Â¸Â  No se pudieron obtener tenants desde planilla_master: {$e->getMessage()}\n";
        return [];
    }
}

$tenants = getActiveTenants();
if (empty($tenants)) {
    $tenants = [$_ENV['DB_NAME'] ?? 'planilla_prod'];
}
// incluir planilla_prod aunque no estÃ© en tenants y limpiar duplicados/nulos
$tenants[] = 'planilla_prod';
$tenants = array_values(array_unique(array_filter($tenants)));

$overallExit = 0;
$processedTenants = 0;

foreach ($tenants as $tenantDb) {
    if (empty($tenantDb)) {
        echo "Ã¢ÂÂ Ã¯Â¸Â  Tenant con db_name vacÃÂ­o; se omite.\n";
        continue;
    }
    $processedTenants++;
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  Tenant: {$tenantDb}                                         ║\n";                                     
    echo "╚══════════════════════════════════════════════════════════════╝\n";

    try {
        switchTenant($tenantDb);

        // ============================================
        // VALIDAR SI LA SINCRONIZACIÓN ESTÁ HABILITADA
        // ============================================
        $configModel = new AttendanceApiConfig();
        $config = $configModel->getActiveConfig();

        if (!$config) {
            echo "⚠️  No hay configuración activa de API. Saltando procesamiento...\n\n";
            continue;
        }

        echo "✓ Configuración encontrada:\n";
        echo "  - Sincronización habilitada: " . ($config['sync_enabled'] ? 'Sí' : 'No') . "\n\n";

        // Verificar si está habilitada
        if (!$config['sync_enabled']) {
            echo "⚠️  Sincronización automática deshabilitada. Saltando procesamiento...\n\n";
            continue;
        }

        // ============================================
        // PASO 1: PROCESAR RECORDS Ã¢ÂÂ DETAILS
        // ============================================
        echo "Ã°ÂÂÂ PASO 1: Procesamiento de Marcaciones (Records Ã¢ÂÂ Details)\n";
        echo str_repeat("Ã¢ÂÂ", 68) . "\n";

        $recordModel = new AttendanceRecord();
        $pendingCount = $recordModel->countUnprocessed() ?? 0;

        if ($pendingCount === 0) {
            echo "Ã¢ÂÂ No hay registros pendientes de procesar.\n";
        } else {
            echo "Ã°ÂÂÂ Registros pendientes: {$pendingCount}\n";

            // Obtener rango de fechas con registros pendientes (fallback si el metodo no existe)
            if (method_exists($recordModel, 'getUnprocessedDateRange')) {
                $dateRange = $recordModel->getUnprocessedDateRange();
            } else {
                $conn = Database::getInstance()->getConnection();
                $stmtRange = $conn->prepare("SELECT MIN(punch_date) AS min_date, MAX(punch_date) AS max_date FROM attendance_records WHERE is_duplicate = 0 AND (is_processed = 0 OR is_processed IS NULL)");
                $stmtRange->execute();
                $dateRange = $stmtRange->fetch(\PDO::FETCH_ASSOC);
            }

            if ($dateRange && isset($dateRange['min_date']) && isset($dateRange['max_date'])) {
                $minDate = $dateRange['min_date'];
                $maxDate = $dateRange['max_date'];

                echo "Ã°ÂÂÂ
 Rango: {$minDate} Ã¢ÂÂ {$maxDate}\n";

                // Procesar
                $processor = new RecordsProcessor();
                $recordsStats = $processor->processToDetails($minDate, $maxDate);

                echo "Ã¢ÂÂ
 Resultados:\n";
                echo "  - Grupos procesados: {$recordsStats['groups_processed']}\n";
                echo "  - Details creados: {$recordsStats['details_created']}\n";
                echo "  - Details actualizados: {$recordsStats['details_updated']}\n";
                echo "  - Errores: {$recordsStats['errors']}\n";
            } else {
                echo "Ã¢ÂÂ Ã¯Â¸Â  No se pudo determinar el rango de fechas.\n";
            }
        }

        echo "\n";

        // ============================================
        // PASO 2: CALCULAR HORAS Y TARDANZAS
        // ============================================
        echo "Ã¢ÂÂ±Ã¯Â¸Â  PASO 2: CÃÂ¡lculo de Horas y Tardanzas\n";
        echo str_repeat("Ã¢ÂÂ", 68) . "\n";

        $calendar = new BusinessCalendar();
        $today = date('Y-m-d');
        $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));

        echo "Ã°ÂÂÂ
 Analizando dÃÂ­as laborables: {$sevenDaysAgo} Ã¢ÂÂ {$today}\n";

        // Obtener dÃÂ­as laborables en el rango
        $workingDays = $calendar->getWorkingDaysList($sevenDaysAgo, $today);

        if (empty($workingDays)) {
            echo "Ã¢ÂÂ Ã¯Â¸Â  No hay dÃÂ­as laborables en el rango.\n";
        } else {
            echo "Ã°ÂÂÂ DÃÂ­as laborables encontrados: " . count($workingDays) . "\n\n";

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
                    // Verificar si hay details para procesar ese dÃÂ­a
                    $details = $detailModel->getByDate($date);

                    if (empty($details)) {
                        echo "Ã¢ÂÂ­Ã¯Â¸Â  {$date}: Sin marcaciones\n";
                        $calculationsStats['days_skipped']++;
                        continue;
                    }

                    $calculationsStats['days_processed']++;
                    $calculationsStats['total_details'] += count($details);

                    echo "Ã¢ÂÂÃ¯Â¸Â  {$date}: Procesando " . count($details) . " marcaciones...\n";

                    // Procesar cada detalle
                    $dayCalculations = 0;
                    $dayErrors = 0;

                    foreach ($details as $detail) {
                        try {
                            // Calcular mÃÂ©tricas
                            $result = $calculator->calculate(
                                $detail['employee_id'],
                                $date,
                                $detail['time_in'] ?? null,
                                $detail['time_out'] ?? null,
                                $detail['lunch_out'] ? date('H:i:s', strtotime($detail['lunch_out'])) : null,
                                $detail['lunch_in'] ? date('H:i:s', strtotime($detail['lunch_in'])) : null
                            );

                            if ($result && $result['success']) {
                                // Actualizar detail con cÃÂ¡lculos
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

                    echo "  Ã¢ÂÂ {$dayCalculations} cÃÂ¡lculos guardados";
                    if ($dayErrors > 0) {
                        echo " | Ã¢ÂÂ Ã¯Â¸Â  {$dayErrors} errores";
                    }
                    echo "\n";

                } catch (Exception $dayError) {
                    error_log("Error procesando dÃÂ­a {$date}: " . $dayError->getMessage());
                    $calculationsStats['calculations_errors']++;
                    echo "  Ã¢ÂÂ Error: " . $dayError->getMessage() . "\n";
                }
            }

            echo "\nÃ°ÂÂÂ Resumen de CÃÂ¡lculos:\n";
            echo "  - DÃÂ­as procesados: {$calculationsStats['days_processed']}\n";
            echo "  - DÃÂ­as sin datos: {$calculationsStats['days_skipped']}\n";
            echo "  - Total marcaciones: {$calculationsStats['total_details']}\n";
            echo "  - CÃÂ¡lculos guardados: {$calculationsStats['calculations_saved']}\n";
            echo "  - Errores: {$calculationsStats['calculations_errors']}\n";
        }

    } catch (Exception $e) {
        $overallExit = 1;
        echo "\nÃ¢ÂÂ ERROR en tenant {$tenantDb}:\n";
        echo "  {$e->getMessage()}\n";
        echo "\n  Stack trace:\n";
        echo "  {$e->getTraceAsString()}\n";
    }
}

// ============================================
// RESUMEN FINAL
// ============================================
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "\n";
echo "╔═════════════════════════════════════════════════════════╗\n";
echo "║   PIPELINE COMPLETADO                                   ║\n";
echo "╚═════════════════════════════════════════════════════════╝\n";
echo "⏱️  Tiempo total de ejecución: {$executionTime} segundos ({$processedTenants} tenants)\n";
echo ($overallExit === 0 ? "✓" : "⚠️") . " Proceso finalizado con código {$overallExit}\n";

exit($overallExit);


