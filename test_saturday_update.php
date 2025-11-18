<?php

require_once './vendor/autoload.php';
require_once './app/Core/Config.php';
require_once './app/Core/Database.php';
require_once './app/Core/Model.php';
require_once './app/Models/BusinessCalendar.php';

use App\Core\Config;
use App\Models\BusinessCalendar;

Config::load();

echo "=== TESTING SATURDAY UPDATE FOR 2025 ===\n\n";

$calendar = new BusinessCalendar();

// 1. Check current status
echo "1. BEFORE UPDATE:\n";
$before = $calendar->db->findAll("
    SELECT day_type, status, COUNT(*) as total
    FROM business_calendar
    WHERE year_value = 2025 AND day_of_week = 6
    GROUP BY day_type, status
");
foreach ($before as $row) {
    echo "   {$row['day_type']} / {$row['status']}: {$row['total']}\n";
}

// 2. Execute initializeYear WITH saturday_half_day = true
echo "\n2. Executing initializeYear(2025, true)...\n";
$result = $calendar->initializeYear(2025, true);

echo "   Result:\n";
echo "   - Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
echo "   - Inserted: {$result['inserted']}\n";
echo "   - Updated: {$result['updated']}\n";
echo "   - Total: {$result['total']}\n";

// 3. Check after update
echo "\n3. AFTER UPDATE:\n";
$after = $calendar->db->findAll("
    SELECT day_type, status, COUNT(*) as total
    FROM business_calendar
    WHERE year_value = 2025 AND day_of_week = 6
    GROUP BY day_type, status
");
foreach ($after as $row) {
    echo "   {$row['day_type']} / {$row['status']}: {$row['total']}\n";
}

// 4. Sample Saturday
echo "\n4. Sample Saturday:\n";
$sample = $calendar->db->find("
    SELECT date_value, day_type, status, description, is_weekend
    FROM business_calendar
    WHERE year_value = 2025 AND day_of_week = 6
    AND day_type = 'LABORAL' AND status = 'MEDIO_DIA'
    LIMIT 1
");
if ($sample) {
    echo "   Date: {$sample['date_value']}\n";
    echo "   Type: {$sample['day_type']}\n";
    echo "   Status: {$sample['status']}\n";
    echo "   Description: {$sample['description']}\n";
    echo "   Is Weekend: {$sample['is_weekend']}\n";
} else {
    echo "   NO Saturday with LABORAL/MEDIO_DIA found!\n";
}

echo "\n=== TEST COMPLETE ===\n";
