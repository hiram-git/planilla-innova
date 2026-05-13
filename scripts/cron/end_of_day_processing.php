<?php
/**
 * Cron Job: Procesamiento de Fin de Día - Detección de Ausencias y Tardanzas
 *
 * Este script debe ejecutarse UNA VEZ AL DÍA al final de la jornada laboral
 * para consolidar asistencias, detectar ausencias y calcular métricas finales.
 *
 * Ejemplo crontab (Linux) - Ejecutar a las 9 PM de Lunes a Viernes:
 * 0 21 * * 1-5 php /var/www/html/planilla/scripts/cron/end_of_day_processing.php >> /var/log/planilla/cron_eod.log 2>&1
 *
 * Funcionalidad:
 * 1. Consolida registros del día (attendance_records → attendance_detail)
 * 2. Detecta empleados SIN marcación (ausencias)
 * 3. Marca marcaciones incompletas (omisiones)
 * 4. Calcula métricas (horas, tardanzas, horas extras, etc.)
 * 5. Actualiza estadísticas del día
 */

// Prevenir ejecución desde navegador
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde línea de comandos');
}

// Tiempo de inicio
$startTime = microtime(true);

// Cargar autoload de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar variables de entorno (.env)
if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
} else {
    \App\Core\Config::load();
}

// Fallback adicional: Si las variables críticas no están en $_ENV, establecer defaults
$requiredEnvVars = [
    'DB_HOST' => 'localhost',
    'DB_DATABASE' => 'planilla_prod',  // Tu .env usa DB_DATABASE
    'DB_USERNAME' => 'root',            // Tu .env usa DB_USERNAME
    'DB_PASSWORD' => '',
    'DB_PORT' => '3306'
];

foreach ($requiredEnvVars as $key => $default) {
    if (!isset($_ENV[$key]) || empty($_ENV[$key])) {
        $value = getenv($key);
        if ($value === false) {
            $_ENV[$key] = $default;
            putenv("{$key}={$default}");
        } else {
            $_ENV[$key] = $value;
        }
    }
}

// Crear alias para compatibilidad con código que espera DB_NAME y DB_USER
if (!isset($_ENV['DB_NAME']) && isset($_ENV['DB_DATABASE'])) {
    $_ENV['DB_NAME'] = $_ENV['DB_DATABASE'];
    putenv("DB_NAME={$_ENV['DB_DATABASE']}");
}
if (!isset($_ENV['DB_USER']) && isset($_ENV['DB_USERNAME'])) {
    $_ENV['DB_USER'] = $_ENV['DB_USERNAME'];
    putenv("DB_USER={$_ENV['DB_USERNAME']}");
}

// Inicializar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Core\MasterDatabase;
use App\Core\TenantResolver;
use App\Core\Database;
use App\Models\AttendanceHeader;
use App\Models\AttendanceDetail;
use App\Models\AttendanceApiConfig;
use App\Models\Employee;
use App\Models\BusinessCalendar;
use App\Models\EmployeeDailySchedule;
use App\Services\Attendance\RecordsProcessor;
use App\Services\Attendance\AttendanceCalculator;

// Banner
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   CRON JOB: Procesamiento de Fin de Día - Asistencias         ║\n";
echo "║   Fecha: " . date('Y-m-d H:i:s') . "                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

/**
 * Cambiar de tenant
 */
function switchTenant(string $dbName): void
{
    TenantResolver::clear();
    Database::resetInstance();
    $_SESSION['tenant_db'] = $dbName;
}

/**
 * Obtener lista de tenants activos
 */
function getActiveTenants(): array
{
    try {
        $master = MasterDatabase::getInstance()->getConnection();
        $stmt = $master->query("SELECT db_name FROM tenants WHERE status = 'ACTIVE'");
        $tenants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $tenants ?: [];
    } catch (\Exception $e) {
        echo "⚠️  No se pudieron obtener tenants: {$e->getMessage()}\n";
        return [];
    }
}

/**
 * Procesar día completo para un tenant
 */
function processEndOfDay(string $tenantDb, string $date): array
{
    $stats = [
        'records_processed' => 0,
        'details_created' => 0,
        'absences_detected' => 0,
        'omissions_marked' => 0,
        'calculations_saved' => 0,
        'errors' => 0
    ];

    try {
        switchTenant($tenantDb);

        echo "\n══════════════════════════════════════════════════════\n";
        echo "▶️  Tenant: {$tenantDb} | Fecha: {$date}\n";
        echo "══════════════════════════════════════════════════════\n";

        // Validar si la sincronización está habilitada
        $configModel = new AttendanceApiConfig();
        $config = $configModel->getActiveConfig();

        if (!$config) {
            echo "⚠️  No hay configuración activa de API. Saltando procesamiento...\n";
            return $stats;
        }

        if (!$config['sync_enabled']) {
            echo "⚠️  Sincronización automática deshabilitada. Saltando procesamiento...\n";
            return $stats;
        }

        echo "✓ Sincronización habilitada: Procesando fin de día...\n";

        // Cargar info del calendario empresarial y overrides personales.
        // Importante: NO se hace early-return en domingos/no-laborables,
        // porque un empleado puede tener override personal en employee_daily_schedules
        // que le asigne jornada ese día (p.ej. turno rotativo).
        $calendar = new BusinessCalendar();
        $dayInfo = $calendar->getDayInfo($date);

        $dailyScheduleModel = new EmployeeDailySchedule();
        $employeesWithPersonalOverride = $dailyScheduleModel->getEmployeeIdsWithOverrideForDate($date);

        if ($dayInfo && $dayInfo['day_type'] === 'FERIADO') {
            echo "🎉 Día {$date} es feriado ({$dayInfo['description']}). ";
            if ($dayInfo['is_paid_holiday'] == 1) {
                echo "Feriado PAGADO - se procesará automáticamente.\n";
            } elseif (empty($employeesWithPersonalOverride)) {
                echo "Feriado NO pagado - saltando.\n";
                return $stats;
            } else {
                echo "Feriado NO pagado, pero hay empleados con override personal. Procesando solo a ellos.\n";
            }
        }

        // PASO 1: Consolidar registros → detalles
        echo "\n📋 PASO 1: Consolidando registros del día...\n";
        $processor = new RecordsProcessor();
        $processStats = $processor->processDay($date);

        $stats['records_processed'] = $processStats['records_processed'] ?? 0;
        $stats['details_created'] = $processStats['details_updated'] ?? 0;

        echo "  ✓ Registros procesados: {$stats['records_processed']}\n";
        echo "  ✓ Detalles creados/actualizados: {$stats['details_created']}\n";

        // PASO 2: Obtener header del día (o crearlo)
        $headerModel = new AttendanceHeader();
        $header = $headerModel->getByDate($date);

        if (!$header) {
            $headerId = $headerModel->create([
                'attendance_date' => $date,
                'total_records' => 0,
                'total_employees' => 0,
                'total_on_time' => 0,
                'total_late' => 0,
                'total_absent' => 0,
                'is_processed' => 0
            ]);
            $header = $headerModel->getById($headerId);
            echo "  ✓ Header creado para fecha {$date}\n";
        }

        // PASO 3: Detectar ausencias (empleados sin marcación)
        echo "\n👥 PASO 2: Detectando ausencias...\n";

        $employeeModel = new Employee();
        $detailModel = new AttendanceDetail();

        // Obtener empleados activos que marcan asistencia
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT e.id, e.employee_id, e.firstname, e.lastname, e.schedule_id
                FROM employees e
                LEFT JOIN situaciones sit ON e.situacion_id = sit.id
                WHERE (e.situacion_id = 1 OR sit.descripcion LIKE '%activ%' OR sit.descripcion LIKE '%ACTIV%' OR e.situacion_id IS NULL)
                  AND COALESCE(e.marca_asistencia, 0) = 1";
        $stmt = $db->query($sql);
        $activeEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "  📊 Empleados activos con marca_asistencia=1: " . count($activeEmployees) . "\n";

        // Obtener empleados que YA tienen marcación
        $existingDetails = $detailModel->getByHeader($header['id']);
        $employeesWithAttendance = [];
        foreach ($existingDetails as $detail) {
            $employeesWithAttendance[$detail['employee_id']] = true;
        }

        // Por cada empleado sin marcación:
        //   1) Limpia ABSENT auto-generado previo (si quedó basura porque el
        //      calendario empresarial no estaba inicializado al sincronizar).
        //   2) Crea ABSENT sólo si shouldMarkAbsence lo permite (override personal
        //      o día LABORAL en business_calendar).
        $absencesCreated = 0;
        foreach ($activeEmployees as $employee) {
            if (isset($employeesWithAttendance[$employee['id']])) {
                continue;
            }

            $hasOverride = in_array((int)$employee['id'], $employeesWithPersonalOverride, true);

            // (1) Limpieza idempotente
            $detailModel->deleteAutoAbsenceByEmployeeAndHeader($header['id'], $employee['id']);

            // (2) Aplicar regla
            if (!BusinessCalendar::shouldMarkAbsence($dayInfo ?: null, $date, $hasOverride)) {
                continue;
            }

            // Resolver schedule_id (override personal si aplica, sino el base)
            $scheduleId = $employee['schedule_id'] ?? null;
            if ($hasOverride) {
                $overrideRecord = $dailyScheduleModel->getForDate((int)$employee['id'], $date);
                if ($overrideRecord) {
                    $scheduleId = $overrideRecord['schedule_id'];
                }
            }

            $absenceData = [
                'header_id' => $header['id'],
                'employee_id' => $employee['id'],
                'schedule_id' => $scheduleId,
                'time_in' => null,
                'time_out' => null,
                'status' => 'ABSENT',
                'is_late' => 0,
                'tardiness_minutes' => 0,
                'hours_worked' => 0,
                'notes' => 'Ausencia detectada automáticamente - Sin marcación'
            ];

            $detailId = $detailModel->create($absenceData);

            if ($detailId) {
                $absencesCreated++;
            }
        }

        $stats['absences_detected'] = $absencesCreated;
        echo "  ✓ Ausencias detectadas: {$absencesCreated}\n";

        // PASO 4: Marcar omisiones (marcaciones incompletas)
        echo "\n⚠️  PASO 3: Marcando omisiones...\n";

        $allDetails = $detailModel->getByHeader($header['id']);
        $omissionsMarked = 0;

        foreach ($allDetails as $detail) {
            $hasTimeIn = !empty($detail['time_in']);
            $hasTimeOut = !empty($detail['time_out']);

            // Solo marcar como omisión si tiene UNA marcación pero no la otra
            if (($hasTimeIn && !$hasTimeOut) || (!$hasTimeIn && $hasTimeOut)) {
                $detailModel->update($detail['id'], [
                    'status' => 'INCOMPLETE',
                    'notes' => 'Omisión de marcación: ' . ($hasTimeIn ? 'Falta salida' : 'Falta entrada')
                ]);
                $omissionsMarked++;
            }
        }

        $stats['omissions_marked'] = $omissionsMarked;
        echo "  ✓ Omisiones marcadas: {$omissionsMarked}\n";

        // PASO 5: Calcular métricas para marcaciones completas
        echo "\n🧮 PASO 4: Calculando métricas...\n";

        $calculator = new AttendanceCalculator();
        $completeAttendances = [];

        foreach ($allDetails as $detail) {
            // Saltar feriados pagados (ya tienen cálculo manual)
            $isFeriado = (strpos($detail['notes'] ?? '', 'Feriado Pagado') !== false);
            if ($isFeriado) {
                continue;
            }

            // Solo calcular si tiene entrada Y salida
            if (!empty($detail['time_in']) && !empty($detail['time_out'])) {
                $completeAttendances[] = [
                    'id' => $detail['id'],
                    'employee_id' => $detail['employee_id'],
                    'date' => $date,
                    'time_in' => $detail['time_in'],
                    'time_out' => $detail['time_out'],
                    'lunch_out' => $detail['lunch_out'] ?? null,
                    'lunch_in' => $detail['lunch_in'] ?? null
                ];
            }
        }

        if (!empty($completeAttendances)) {
            $calcResult = $calculator->calculateAndSaveBulk($completeAttendances, true);
            $stats['calculations_saved'] = $calcResult['stats']['saved'] ?? 0;

            echo "  ✓ Cálculos guardados: {$stats['calculations_saved']}\n";

            // Actualizar estados en attendance_detail
            foreach ($calcResult['calculations'] as $calculation) {
                $newStatus = 'PRESENT';
                if ($calculation['is_absent'] == 1) {
                    $newStatus = 'ABSENT';
                } elseif (empty($calculation['time_out'])) {
                    $newStatus = 'INCOMPLETE';
                }

                $detailModel->update($calculation['attendance_detail_id'], [
                    'status' => $newStatus,
                    'hours_worked' => $calculation['total_hours'],
                    'is_late' => $calculation['is_late'],
                    'tardiness_minutes' => $calculation['tardiness_minutes']
                ]);
            }
        }

        // PASO 6: Actualizar estadísticas del header
        echo "\n📊 PASO 5: Actualizando estadísticas del día...\n";

        $finalDetails = $detailModel->getByHeader($header['id']);
        $totalOnTime = 0;
        $totalLate = 0;
        $totalAbsent = 0;
        $totalIncomplete = 0;

        foreach ($finalDetails as $detail) {
            switch ($detail['status']) {
                case 'PRESENT':
                    if ($detail['is_late']) {
                        $totalLate++;
                    } else {
                        $totalOnTime++;
                    }
                    break;
                case 'LATE':
                    $totalLate++;
                    break;
                case 'ABSENT':
                    $totalAbsent++;
                    break;
                case 'INCOMPLETE':
                    $totalIncomplete++;
                    break;
            }
        }

        $headerModel->update($header['id'], [
            'total_records' => count($finalDetails),
            'total_employees' => count($finalDetails),
            'total_on_time' => $totalOnTime,
            'total_late' => $totalLate,
            'total_absent' => $totalAbsent,
            'total_incomplete' => $totalIncomplete,
            'is_processed' => 1,
            'processed_at' => date('Y-m-d H:i:s')
        ]);

        echo "  ✓ Total empleados: " . count($finalDetails) . "\n";
        echo "  ✓ A tiempo: {$totalOnTime}\n";
        echo "  ✓ Tarde: {$totalLate}\n";
        echo "  ✓ Ausentes: {$totalAbsent}\n";
        echo "  ✓ Incompletos: {$totalIncomplete}\n";

        echo "\n✅ Procesamiento de fin de día completado exitosamente\n";

    } catch (Exception $e) {
        $stats['errors']++;
        echo "\n❌ ERROR en tenant {$tenantDb}:\n";
        echo "  {$e->getMessage()}\n";
        echo "\n  Stack trace:\n";
        echo "  {$e->getTraceAsString()}\n";
    }

    return $stats;
}

// ========================================
// EJECUCIÓN PRINCIPAL
// ========================================

$tenants = getActiveTenants();
if (empty($tenants)) {
    $tenants = [$_ENV['DB_NAME'] ?? 'planilla_prod'];
}
$tenants[] = 'planilla_prod';
$tenants = array_values(array_unique(array_filter($tenants)));

// Fecha a procesar (hoy o la fecha pasada como argumento)
$dateToProcess = $argv[1] ?? date('Y-m-d');

echo "📅 Fecha a procesar: {$dateToProcess}\n";
echo "🏢 Tenants a procesar: " . count($tenants) . "\n\n";

$overallStats = [
    'tenants_processed' => 0,
    'total_records' => 0,
    'total_absences' => 0,
    'total_calculations' => 0,
    'total_errors' => 0
];

foreach ($tenants as $tenantDb) {
    if (empty($tenantDb)) {
        continue;
    }

    $stats = processEndOfDay($tenantDb, $dateToProcess);

    $overallStats['tenants_processed']++;
    $overallStats['total_records'] += $stats['records_processed'];
    $overallStats['total_absences'] += $stats['absences_detected'];
    $overallStats['total_calculations'] += $stats['calculations_saved'];
    $overallStats['total_errors'] += $stats['errors'];
}

// Resumen final
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    RESUMEN FINAL                               ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";
echo "║  Tenants procesados:    " . str_pad($overallStats['tenants_processed'], 10) . "                        ║\n";
echo "║  Total registros:       " . str_pad($overallStats['total_records'], 10) . "                        ║\n";
echo "║  Total ausencias:       " . str_pad($overallStats['total_absences'], 10) . "                        ║\n";
echo "║  Total cálculos:        " . str_pad($overallStats['total_calculations'], 10) . "                        ║\n";
echo "║  Total errores:         " . str_pad($overallStats['total_errors'], 10) . "                        ║\n";
echo "║  Tiempo ejecución:      " . str_pad($executionTime . " seg", 10) . "                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$exitCode = $overallStats['total_errors'] > 0 ? 1 : 0;
echo ($exitCode === 0 ? "✅" : "❌") . " Finalizado con código: {$exitCode}\n\n";

exit($exitCode);
