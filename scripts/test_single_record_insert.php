#!/usr/bin/env php
<?php
/**
 * Test inserting a single record to diagnose why sync is failing
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\AttendanceRecord;

Bootstrap::init();

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║           TEST: Inserción Individual de Registro         ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$db = Database::getInstance()->getConnection();
$recordModel = new AttendanceRecord();

// Test data (known good employee)
$testData = [
    'raw_data_id' => 1,
    'external_id' => 'test_' . time(),
    'employee_id' => 2,  // KATHY GONZALEZ (we know this exists)
    'timestamp' => '2025-10-30 10:00:00',
    'punch_date' => '2025-10-30',
    'punch_time' => '10:00:00',
    'punch_type' => 'CHECK_IN',
    'device_id' => null,
    'device_serial' => null,
    'source' => 'API',
    'metadata' => json_encode(['test' => true]),
    'notes' => 'Test record'
];

// Calculate hash
$testData['record_hash'] = $recordModel->calculateHash($testData);

echo "1️⃣  Test Data Prepared:\n";
echo "   - Employee ID: {$testData['employee_id']}\n";
echo "   - Timestamp: {$testData['timestamp']}\n";
echo "   - Punch Type: {$testData['punch_type']}\n";
echo "   - Hash: " . substr($testData['record_hash'], 0, 20) . "...\n\n";

// Check if employee exists
echo "2️⃣  Verifying Employee Exists:\n";
$empStmt = $db->prepare("SELECT id, firstname, lastname FROM employees WHERE id = ?");
$empStmt->execute([2]);
$employee = $empStmt->fetch(PDO::FETCH_ASSOC);

if ($employee) {
    echo "   ✓ Employee found: {$employee['firstname']} {$employee['lastname']}\n\n";
} else {
    echo "   ✗ Employee NOT found!\n";
    exit(1);
}

// Check if hash already exists
echo "3️⃣  Checking for Duplicates:\n";
$exists = $recordModel->existsByHash($testData['record_hash']);
if ($exists) {
    echo "   ⚠️  Hash already exists - will be duplicate\n\n";
} else {
    echo "   ✓ Hash is unique - can insert\n\n";
}

// Try to insert
echo "4️⃣  Attempting Insert:\n";

try {
    $recordId = $recordModel->create($testData);

    if ($recordId) {
        echo "   ✅ SUCCESS! Record created with ID: {$recordId}\n";

        // Verify it was inserted
        $verifyStmt = $db->prepare("SELECT * FROM attendance_records WHERE id = ?");
        $verifyStmt->execute([$recordId]);
        $inserted = $verifyStmt->fetch(PDO::FETCH_ASSOC);

        if ($inserted) {
            echo "   ✓ Verified in database:\n";
            echo "      - ID: {$inserted['id']}\n";
            echo "      - Employee: {$inserted['employee_id']}\n";
            echo "      - Timestamp: {$inserted['timestamp']}\n";
            echo "      - Punch Type: {$inserted['punch_type']}\n";
            echo "      - Hash: " . substr($inserted['record_hash'], 0, 20) . "...\n";
        }

        // Clean up
        echo "\n   🧹 Cleaning up test record...\n";
        $deleteStmt = $db->prepare("DELETE FROM attendance_records WHERE id = ?");
        $deleteStmt->execute([$recordId]);
        echo "   ✓ Test record removed\n";

    } else {
        echo "   ❌ FAILED! create() returned false\n";
        echo "   💡 This is why sync is failing!\n";
    }

} catch (Exception $e) {
    echo "   ❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║                      RESULTADO                            ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";

if ($recordId) {
    echo "\n✅ La inserción FUNCIONA correctamente\n";
    echo "   El problema debe estar en otra parte del flujo de sincronización\n\n";
} else {
    echo "\n❌ La inserción FALLA\n";
    echo "   Este es el motivo por el que todos los registros se omiten\n";
    echo "   Revisa los errores de base de datos o permisos\n\n";
}
