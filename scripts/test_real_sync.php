#!/usr/bin/env php
<?php
/**
 * Test real synchronization with existing raw data
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Core\Database;
use App\Services\Attendance\AttendanceSyncService;

Bootstrap::init();

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║          TEST: Sincronización Real con Fix Aplicado      ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Check current state
$db = Database::getInstance()->getConnection();

echo "1️⃣  Estado antes de la sincronización:\n";
$stmt = $db->query("SELECT COUNT(*) as total FROM attendance_records");
$beforeCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "   - Registros en attendance_records: {$beforeCount}\n\n";

// Run sync
echo "2️⃣  Ejecutando sincronización completa...\n";
try {
    $syncService = new AttendanceSyncService();
    $stats = $syncService->syncAll();

    echo "   ✓ Sincronización completada\n\n";

    echo "3️⃣  Resultados de la sincronización:\n";
    echo "   - Obtenidos del API: {$stats['fetched']}\n";
    echo "   - Insertados: {$stats['inserted']}\n";
    echo "   - Actualizados: {$stats['updated']}\n";
    echo "   - Omitidos: {$stats['skipped']}\n";
    echo "   - Errores: {$stats['errors']}\n\n";

    if (!empty($syncService->getErrors())) {
        echo "   ⚠️  Errores encontrados:\n";
        foreach (array_slice($syncService->getErrors(), 0, 5) as $error) {
            echo "      - {$error}\n";
        }
        if (count($syncService->getErrors()) > 5) {
            echo "      ... y " . (count($syncService->getErrors()) - 5) . " más\n";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Check after state
echo "4️⃣  Estado después de la sincronización:\n";
$stmt = $db->query("SELECT COUNT(*) as total FROM attendance_records");
$afterCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "   - Registros en attendance_records: {$afterCount}\n";
echo "   - Nuevos registros creados: " . ($afterCount - $beforeCount) . "\n\n";

if ($afterCount > $beforeCount) {
    // Show sample records
    echo "5️⃣  Muestra de registros insertados:\n";
    $stmt = $db->query("
        SELECT r.id, r.employee_id, r.punch_date, r.punch_time, r.punch_type,
               CONCAT(e.firstname, ' ', e.lastname) as employee_name
        FROM attendance_records r
        INNER JOIN employees e ON r.employee_id = e.id
        ORDER BY r.id DESC
        LIMIT 5
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   - ID {$row['id']}: {$row['employee_name']} | {$row['punch_date']} {$row['punch_time']} | {$row['punch_type']}\n";
    }
    echo "\n";
}

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                      CONCLUSIÓN                           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

if ($stats['inserted'] > 0) {
    echo "✅ ¡FIX EXITOSO! Los registros ahora se insertan correctamente.\n\n";
    echo "Próximos pasos:\n";
    echo "  1. Procesar records → details desde /panel/attendance/sync\n";
    echo "  2. Verificar que los detalles se consoliden correctamente\n\n";
} elseif ($stats['skipped'] > 0 && $stats['errors'] === 0) {
    echo "⚠️  Los registros se omitieron (probablemente duplicados)\n\n";
    echo "Si quieres resincronizar:\n";
    echo "  - Ejecuta: scripts/reset_attendance_sync.sql\n";
    echo "  - Luego vuelve a sincronizar\n\n";
} else {
    echo "❌ Aún hay problemas con la sincronización.\n";
    echo "   Revisa los errores mostrados arriba.\n\n";
}
