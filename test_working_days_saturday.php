<?php

require_once './vendor/autoload.php';
require_once './app/Core/Config.php';
require_once './app/Core/Database.php';
require_once './app/Core/Model.php';
require_once './app/Models/BusinessCalendar.php';

use App\Core\Config;
use App\Models\BusinessCalendar;

Config::load();

echo "=== TESTING WORKING DAYS LIST WITH SATURDAY HALF-DAY ===\n\n";

$calendar = new BusinessCalendar();

// Test con una semana que incluye sábado
$startDate = '2025-11-03'; // Lunes
$endDate = '2025-11-09';   // Domingo

echo "1. Date range: {$startDate} to {$endDate}\n";
echo "   (Monday to Sunday - includes Saturday 2025-11-08)\n\n";

// Verificar el estado del sábado 08
echo "2. Checking Saturday 2025-11-08 status in database:\n";
$saturday = $calendar->db->find("
    SELECT date_value, day_of_week, day_type, status, description
    FROM business_calendar
    WHERE date_value = '2025-11-08'
");

if ($saturday) {
    echo "   Date: {$saturday['date_value']}\n";
    echo "   Day of Week: {$saturday['day_of_week']} (6 = Saturday)\n";
    echo "   Day Type: {$saturday['day_type']}\n";
    echo "   Status: {$saturday['status']}\n";
    echo "   Description: {$saturday['description']}\n";
} else {
    echo "   NOT FOUND in database!\n";
}

// Obtener lista de días laborables
echo "\n3. Getting working days list from BusinessCalendar:\n";
$workingDays = $calendar->getWorkingDaysList($startDate, $endDate);

echo "   Working days found: " . count($workingDays) . "\n";
foreach ($workingDays as $date) {
    $dayOfWeek = date('N', strtotime($date));
    $dayName = ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][$dayOfWeek];
    $highlight = ($dayOfWeek == 6) ? ' ← SATURDAY (MEDIO_DIA)' : '';
    echo "   - {$date} ({$dayName}){$highlight}\n";
}

// Verificar si el sábado está incluido
echo "\n4. Is Saturday included?\n";
$saturdayIncluded = in_array('2025-11-08', $workingDays);
echo "   " . ($saturdayIncluded ? '✅ YES' : '❌ NO') . "\n";

// Conteo esperado
echo "\n5. Expected count:\n";
echo "   - Monday to Friday: 5 days\n";
echo "   - Saturday (MEDIO_DIA): 1 day\n";
echo "   - Total: 6 days\n";
echo "   - Actual: " . count($workingDays) . " days\n";
echo "   - Match: " . (count($workingDays) == 6 ? '✅ YES' : '❌ NO') . "\n";

echo "\n=== TEST COMPLETE ===\n";
