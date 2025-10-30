#!/usr/bin/env php
<?php
/**
 * Test completo del flujo de sincronización
 * Diagnóstica por qué los registros se omiten
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Core\Database;
use App\Services\Attendance\Base44ApiClient;
use App\Services\Attendance\AttendanceSyncService;
use App\Models\AttendanceRecord;
use App\Models\AttendanceApiConfig;

Bootstrap::init();

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║          TEST COMPLETO: Flujo de Sincronización           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$db = Database::getInstance()->getConnection();

// 1. Verificar configuración
echo "1️⃣  Verificando configuración API...\n";
$configModel = new AttendanceApiConfig();
$config = $configModel->getActiveConfig();

if (!$config) {
    echo "   ❌ No hay configuración activa\n";
    exit(1);
}

echo "   ✓ Config ID: {$config['id']}\n";
echo "   ✓ Provider: {$config['api_provider']}\n";
echo "   ✓ Sync enabled: " . ($config['sync_enabled'] ? 'SÍ' : 'NO') . "\n\n";

// 2. Obtener datos del API
echo "2️⃣  Obteniendo datos del API Base44...\n";
$apiClient = new Base44ApiClient(
    $config['api_key'],
    $config['app_id'],
    $config['api_url']
);

try {
    $attendances = $apiClient->getAttendances();
    $totalFetched = count($attendances);
    echo "   ✓ Registros obtenidos: {$totalFetched}\n\n";

    if ($totalFetched === 0) {
        echo "   ❌ No hay registros en el API\n";
        exit(0);
    }

    // Mostrar primer registro
    echo "   📄 Primer registro (estructura):\n";
    $firstRecord = $attendances[0];
    echo "   " . json_encode($firstRecord, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

} catch (Exception $e) {
    echo "   ❌ Error obteniendo datos: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Verificar empleados en BD
echo "3️⃣  Verificando empleados en base de datos...\n";
$recordModel = new AttendanceRecord();
$employeesFound = 0;
$employeesNotFound = 0;
$employeesMissing = [];

foreach ($attendances as $record) {
    $email = $record['employee_email'] ?? null;

    if (!$email) {
        $employeesNotFound++;
        continue;
    }

    $stmt = $db->prepare("SELECT id, firstname, lastname, email FROM employees WHERE email = ?");
    $stmt->execute([$email]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($employee) {
        $employeesFound++;
    } else {
        $employeesNotFound++;
        if (count($employeesMissing) < 5) {
            $employeesMissing[] = [
                'email' => $email,
                'name' => $record['employee_name'] ?? 'N/A'
            ];
        }
    }
}

echo "   ✓ Empleados encontrados: {$employeesFound}\n";
echo "   ❌ Empleados NO encontrados: {$employeesNotFound}\n";

if (!empty($employeesMissing)) {
    echo "\n   📋 Primeros empleados faltantes:\n";
    foreach ($employeesMissing as $missing) {
        echo "      - {$missing['name']} ({$missing['email']})\n";
    }
}
echo "\n";

// 4. Verificar estado de attendance_records
echo "4️⃣  Estado actual de attendance_records...\n";
$stmt = $db->query("
    SELECT
        COUNT(*) as total,
        COUNT(DISTINCT record_hash) as unique_hashes,
        SUM(CASE WHEN is_processed = 1 THEN 1 ELSE 0 END) as procesados,
        SUM(CASE WHEN is_processed = 0 THEN 1 ELSE 0 END) as sin_procesar
    FROM attendance_records
");
$recordsStats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "   📊 Total registros: {$recordsStats['total']}\n";
echo "   📊 Hashes únicos: {$recordsStats['unique_hashes']}\n";
echo "   📊 Procesados: {$recordsStats['procesados']}\n";
echo "   📊 Sin procesar: {$recordsStats['sin_procesar']}\n\n";

// 5. Simular inserción de primer registro
echo "5️⃣  Simulando inserción del primer registro...\n";
$firstRecord = $attendances[0];
$email = $firstRecord['employee_email'] ?? null;

if (!$email) {
    echo "   ❌ El primer registro no tiene email\n";
    exit(1);
}

$stmt = $db->prepare("SELECT id FROM employees WHERE email = ?");
$stmt->execute([$email]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    echo "   ❌ Empleado no encontrado: {$email}\n";
    echo "   💡 RAZÓN DE OMISIÓN: EMPLEADO_NO_EXISTE\n\n";

    // Buscar empleados similares
    $name = $firstRecord['employee_name'] ?? '';
    echo "   🔍 Buscando empleados con nombre similar a: {$name}\n";
    $stmt = $db->prepare("SELECT id, firstname, lastname, email FROM employees WHERE CONCAT(firstname, ' ', lastname) LIKE ? LIMIT 5");
    $stmt->execute(["%{$name}%"]);
    $similar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($similar)) {
        echo "   📋 Empleados similares encontrados:\n";
        foreach ($similar as $emp) {
            echo "      - {$emp['firstname']} {$emp['lastname']} ({$emp['email']})\n";
        }
    }
    exit(1);
}

echo "   ✓ Empleado encontrado: ID {$employee['id']}\n";

// Normalizar timestamp
$timestamp = $firstRecord['timestamp'] ?? $firstRecord['actual_timestamp'] ?? $firstRecord['registered_timestamp'] ?? null;

if (!$timestamp) {
    echo "   ❌ El registro no tiene timestamp\n";
    echo "   💡 RAZÓN DE OMISIÓN: SIN_TIMESTAMP\n";
    exit(1);
}

echo "   ✓ Timestamp: {$timestamp}\n";

$punchType = strtoupper($firstRecord['type'] ?? 'CHECK_IN');
echo "   ✓ Tipo: {$punchType}\n";

// Calcular hash
$recordData = [
    'employee_id' => $employee['id'],
    'timestamp' => $timestamp,
    'punch_type' => $punchType
];

$hash = $recordModel->calculateHash($recordData);
echo "   ✓ Hash: " . substr($hash, 0, 20) . "...\n";

// Verificar si existe
$exists = $recordModel->existsByHash($hash);

if ($exists) {
    echo "   ❌ El registro YA EXISTE en attendance_records\n";
    echo "   💡 RAZÓN DE OMISIÓN: DUPLICADO\n";

    $stmt = $db->prepare("SELECT id, created_at, is_processed FROM attendance_records WHERE record_hash = ?");
    $stmt->execute([$hash]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "   📄 Registro existente:\n";
    echo "      - ID: {$existing['id']}\n";
    echo "      - Creado: {$existing['created_at']}\n";
    echo "      - Procesado: " . ($existing['is_processed'] ? 'SÍ' : 'NO') . "\n";
} else {
    echo "   ✓ El registro NO existe - PUEDE INSERTARSE\n";
}

echo "\n";

// 6. Conclusión y recomendaciones
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                      CONCLUSIÓN                           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

if ($employeesNotFound > 0) {
    echo "⚠️  PROBLEMA PRINCIPAL: {$employeesNotFound} empleados NO existen en la BD\n\n";
    echo "💡 SOLUCIONES:\n";
    echo "   1. Verificar emails de empleados en la tabla 'employees'\n";
    echo "   2. Actualizar emails para que coincidan con Base44\n";
    echo "   3. Crear empleados faltantes\n\n";

    echo "🔧 SQL para verificar emails:\n";
    echo "   SELECT id, firstname, lastname, email FROM employees WHERE situacion_id = 1;\n\n";
}

if ($recordsStats['total'] > 0 && $exists) {
    echo "⚠️  Los registros se omiten porque YA EXISTEN\n\n";
    echo "💡 OPCIONES:\n";
    echo "   1. Si quieres resincronizar:\n";
    echo "      DELETE FROM attendance_records;\n\n";
    echo "   2. Si los registros están correctos:\n";
    echo "      Procesa records → details desde /panel/attendance\n\n";
}

if ($employeesFound > 0 && !$exists) {
    echo "✅ Todo está listo para sincronizar\n";
    echo "   Los registros se insertarán correctamente\n\n";
}

echo "🚀 Para ejecutar sincronización real:\n";
echo "   - Desde UI: /panel/attendance/sync\n";
echo "   - O ejecuta: php scripts/sync_attendance.php\n\n";
