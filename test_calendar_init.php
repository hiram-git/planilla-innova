<?php

require_once './vendor/autoload.php';
require_once './app/Core/Config.php';
require_once './app/Core/Database.php';
require_once './app/Core/Model.php';
require_once './app/Models/BusinessCalendar.php';

use App\Core\Config;
use App\Models\BusinessCalendar;

// Load configuration (includes environment variables)
Config::load();

echo "=== TESTING SATURDAY UPDATE FUNCTIONALITY ===\n\n";

$calendar = new BusinessCalendar();

// TEST: Actualizar sábados existentes
echo "TEST: Update existing Saturdays to half-day\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// 1. Limpiar y crear 2028 SIN sábados medio día
echo "1. Creating 2028 WITHOUT saturday half-day...\n";
$calendar->db->query("DELETE FROM business_calendar WHERE year_value = 2028");
$result1 = $calendar->initializeYear(2028, false);
echo "   Result: Inserted {$result1['inserted']} days\n";

$saturdays1 = $calendar->db->findAll("
    SELECT COUNT(*) as total, day_type, status
    FROM business_calendar
    WHERE year_value = 2028 AND day_of_week = 6
    GROUP BY day_type, status
");
echo "   Saturdays BEFORE update:\n";
foreach ($saturdays1 as $row) {
    echo "     - {$row['day_type']} / {$row['status']}: {$row['total']}\n";
}

// Verificar sample ANTES
$sampleBefore = $calendar->db->find("
    SELECT date_value, day_type, status, description, is_weekend
    FROM business_calendar
    WHERE year_value = 2028 AND day_of_week = 6
    LIMIT 1
");
echo "\n   Sample Saturday BEFORE update:\n";
echo "     Date: {$sampleBefore['date_value']}\n";
echo "     Type: {$sampleBefore['day_type']}\n";
echo "     Status: {$sampleBefore['status']}\n";
echo "     Is Weekend: {$sampleBefore['is_weekend']}\n";
echo "     Description: {$sampleBefore['description']}\n";

// 2. Ahora ejecutar con checkbox marcado (debería ACTUALIZAR sábados)
echo "\n2. Re-initializing 2028 WITH saturday half-day (should UPDATE)...\n";
$result2 = $calendar->initializeYear(2028, true);
echo "   Result:\n";
echo "     - Inserted: {$result2['inserted']} new days\n";
echo "     - Updated: {$result2['updated']} Saturdays\n";
echo "     - Total: {$result2['total']} days\n";

$saturdays2 = $calendar->db->findAll("
    SELECT COUNT(*) as total, day_type, status
    FROM business_calendar
    WHERE year_value = 2028 AND day_of_week = 6
    GROUP BY day_type, status
");
echo "\n   Saturdays AFTER update:\n";
foreach ($saturdays2 as $row) {
    echo "     - {$row['day_type']} / {$row['status']}: {$row['total']}\n";
}

// Verificar sample DESPUÉS
$sampleAfter = $calendar->db->find("
    SELECT date_value, day_type, status, description, is_weekend
    FROM business_calendar
    WHERE year_value = 2028 AND day_of_week = 6
    LIMIT 1
");
echo "\n   Sample Saturday AFTER update:\n";
echo "     Date: {$sampleAfter['date_value']}\n";
echo "     Type: {$sampleAfter['day_type']}\n";
echo "     Status: {$sampleAfter['status']}\n";
echo "     Is Weekend: {$sampleAfter['is_weekend']}\n";
echo "     Description: {$sampleAfter['description']}\n";

// Verificar total de días laborables
$laborables = $calendar->db->find("
    SELECT COUNT(*) as total
    FROM business_calendar
    WHERE year_value = 2028 AND day_type = 'LABORAL'
");
echo "\n3. Summary for 2028:\n";
echo "   - Total LABORAL days: {$laborables['total']}\n";
echo "   - Expected: 300 (260 weekdays + 52 Saturdays with half-day)\n";

$statusBreakdown = $calendar->db->findAll("
    SELECT status, COUNT(*) as total
    FROM business_calendar
    WHERE year_value = 2028 AND day_type = 'LABORAL'
    GROUP BY status
");
echo "   - LABORAL breakdown:\n";
foreach ($statusBreakdown as $row) {
    echo "       * {$row['status']}: {$row['total']}\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo $result2['updated'] == 52 ? "✅ SUCCESS: 52 Saturdays updated!\n" : "❌ FAILED: Expected 52 updates\n";
