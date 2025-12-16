#!/usr/bin/env php
<?php
/**
 * Script de Debug: Por qué se omiten los registros en sincronización
 *
 * Ejecutar: php scripts/debug_sync_skipped.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Core\Database;
use App\Services\Attendance\ApiClient;
use App\Models\AttendanceRecord;
use App\Models\AttendanceApiConfig;

Bootstrap::init();

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║ DEBUG: Por qué se omiten registros en sincronización     ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// 1. Obtener configuración
$configModel = new AttendanceApiConfig();
$config = $configModel->getActiveConfig();

if (!$config) {
    echo "❌ No hay configuración activa\n";
    exit(1);
}

echo "✓ Configuración encontrada: {$config['api_provider']}\n\n";

// 2. Obtener datos del API
$apiClient = new ApiClient(
    $config['api_key'],
    $config['app_id'],
    $config['api_url']
);

echo "📡 Obteniendo registros del API...\n";
$attendances = $apiClient->getAttendances();
$totalFetched = count($attendances);

echo "✓ Total obtenidos: {$totalFetched}\n\n";

if (empty($attendances)) {
    echo "❌ No hay registros para analizar\n";
    exit(0);
}

// 3. Analizar primeros 5 registros
$recordModel = new AttendanceRecord();
$db = Database::getInstance()->getConnection();

echo "🔍 Analizando primeros 5 registros:\n";
echo str_repeat("─", 60) . "\n\n";

foreach (array_slice($attendances, 0, 5) as $index => $record) {
    $num = $index + 1;
    echo "📝 Registro #{$num}:\n";
    echo "  - Email: " . ($record['employee_email'] ?? 'N/A') . "\n";
    echo "  - Timestamp: " . ($record['timestamp'] ?? $record['actual_timestamp'] ?? $record['registered_timestamp'] ?? 'N/A') . "\n";
    echo "  - Type: " . ($record['type'] ?? 'N/A') . "\n";

    // Buscar empleado
    $stmt = $db->prepare("SELECT id, firstname, lastname FROM employees WHERE email = ?");
    $stmt->execute([$record['employee_email'] ?? '']);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo "  ❌ Empleado NO encontrado en BD\n";
        echo "     Razón de omisión: EMPLEADO_NO_EXISTE\n\n";
        continue;
    }

    echo "  ✓ Empleado: {$employee['firstname']} {$employee['lastname']} (ID: {$employee['id']})\n";

    // Calcular hash
    $timestamp = $record['timestamp'] ?? $record['actual_timestamp'] ?? $record['registered_timestamp'];
    $punchType = strtoupper($record['type'] ?? 'CHECK_IN');

    $recordData = [
        'employee_id' => $employee['id'],
        'timestamp' => $timestamp,
        'punch_type' => $punchType
    ];

    $hash = $recordModel->calculateHash($recordData);
    echo "  📊 Hash calculado: " . substr($hash, 0, 16) . "...\n";

    // Verificar si existe
    $exists = $recordModel->existsByHash($hash);

    if ($exists) {
        // Buscar el registro existente
        $stmt = $db->prepare("SELECT id, created_at, is_processed, is_duplicate FROM attendance_records WHERE record_hash = ?");
        $stmt->execute([$hash]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "  ❌ YA EXISTE en attendance_records\n";
        echo "     - ID existente: {$existing['id']}\n";
        echo "     - Creado: {$existing['created_at']}\n";
        echo "     - Procesado: " . ($existing['is_processed'] ? 'SÍ' : 'NO') . "\n";
        echo "     - Duplicado: " . ($existing['is_duplicate'] ? 'SÍ' : 'NO') . "\n";
        echo "     Razón de omisión: REGISTRO_DUPLICADO\n\n";
    } else {
        echo "  ✓ NO existe - SE INSERTARÍA\n\n";
    }
}

// 4. Estadísticas generales
echo str_repeat("─", 60) . "\n";
echo "📊 Estadísticas de attendance_records:\n";

$stmt = $db->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_processed = 1 THEN 1 ELSE 0 END) as procesados,
        SUM(CASE WHEN is_processed = 0 THEN 1 ELSE 0 END) as sin_procesar,
        SUM(CASE WHEN is_duplicate = 1 THEN 1 ELSE 0 END) as duplicados,
        COUNT(DISTINCT record_hash) as hashes_unicos
    FROM attendance_records
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "  - Total registros: {$stats['total']}\n";
echo "  - Procesados: {$stats['procesados']}\n";
echo "  - Sin procesar: {$stats['sin_procesar']}\n";
echo "  - Duplicados: {$stats['duplicados']}\n";
echo "  - Hashes únicos: {$stats['hashes_unicos']}\n\n";

// 5. Recomendación
echo "💡 Recomendaciones:\n";
if ($stats['total'] > 0) {
    echo "  - Los registros se omiten porque YA EXISTEN en attendance_records\n";
    echo "  - Opciones:\n";
    echo "    1. Si quieres resincronizar desde cero:\n";
    echo "       Ejecuta: scripts/reset_attendance_sync.sql\n";
    echo "    2. Si solo quieres limpiar duplicados:\n";
    echo "       Ejecuta: scripts/clean_duplicate_records_only.sql\n";
    echo "    3. Si los registros están correctos:\n";
    echo "       Procesa los records a details:\n";
    echo "       Desde UI: /panel/attendance/sync → Pestaña 'Procesamiento'\n";
} else {
    echo "  - La tabla attendance_records está vacía\n";
    echo "  - Los registros deberían insertarse sin problemas\n";
    echo "  - Si aún se omiten, revisa los logs en storage/logs/\n";
}

echo "\n";
