<?php

require_once './vendor/autoload.php';
require_once './app/Core/Config.php';
require_once './app/Core/Database.php';
require_once './app/Core/Model.php';
require_once './app/Models/BusinessCalendar.php';

use App\Core\Config;
use App\Models\BusinessCalendar;

Config::load();

echo "=== DIAGNÓSTICO: INICIALIZACIÓN CALENDARIO 2025 ===\n\n";

$calendar = new BusinessCalendar();

// 1. Verificar estado actual de la tabla
echo "1. ESTADO ACTUAL DE LA TABLA:\n";
$count = $calendar->db->find("SELECT COUNT(*) as total FROM business_calendar");
echo "   Total registros en tabla: {$count['total']}\n";

if ($count['total'] > 0) {
    $yearCount = $calendar->db->find("
        SELECT COUNT(*) as total
        FROM business_calendar
        WHERE year_value = 2025
    ");
    echo "   Registros para año 2025: {$yearCount['total']}\n";
}

// 2. Verificar conexión a base de datos
echo "\n2. VERIFICACIÓN CONEXIÓN BD:\n";
try {
    $testQuery = $calendar->db->find("SELECT DATABASE() as db_name");
    echo "   ✓ Conexión exitosa a: {$testQuery['db_name']}\n";
} catch (Exception $e) {
    echo "   ✗ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Ejecutar inicialización con saturday_half_day = true
echo "\n3. EJECUTANDO INICIALIZACIÓN (saturday_half_day = TRUE):\n";
echo "   Llamando a initializeYear(2025, true)...\n";

$result = $calendar->initializeYear(2025, true);

echo "\n4. RESULTADO DE LA INICIALIZACIÓN:\n";
echo "   Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";

if ($result['success']) {
    echo "   Insertados: {$result['inserted']}\n";
    echo "   Actualizados: {$result['updated']}\n";
    echo "   Saltados (ya existían): {$result['skipped']}\n";
    echo "   Total: {$result['total']}\n";
    echo "   Saturday Half Day: " . ($result['saturday_half_day'] ? 'YES' : 'NO') . "\n";
} else {
    echo "   ERROR: " . ($result['error'] ?? 'Error desconocido') . "\n";
}

// 4. Verificar registros creados
echo "\n5. VERIFICACIÓN POST-INICIALIZACIÓN:\n";
$afterCount = $calendar->db->find("
    SELECT COUNT(*) as total
    FROM business_calendar
    WHERE year_value = 2025
");
echo "   Registros 2025 después: {$afterCount['total']}\n";

// Contar por tipo
$byType = $calendar->db->findAll("
    SELECT day_type, status, COUNT(*) as total
    FROM business_calendar
    WHERE year_value = 2025
    GROUP BY day_type, status
    ORDER BY day_type, status
");

echo "\n   Distribución por tipo:\n";
foreach ($byType as $row) {
    echo "   - {$row['day_type']} / {$row['status']}: {$row['total']}\n";
}

// 5. Verificar sábados específicamente
echo "\n6. VERIFICACIÓN SÁBADOS:\n";
$saturdays = $calendar->db->findAll("
    SELECT day_type, status, COUNT(*) as total
    FROM business_calendar
    WHERE year_value = 2025
    AND day_of_week = 6
    GROUP BY day_type, status
");

echo "   Sábados encontrados:\n";
foreach ($saturdays as $row) {
    echo "   - {$row['day_type']} / {$row['status']}: {$row['total']}\n";
}

// Muestra de sábados
echo "\n   Muestra de 5 sábados:\n";
$sampleSaturdays = $calendar->db->findAll("
    SELECT date_value, day_type, status, description
    FROM business_calendar
    WHERE year_value = 2025
    AND day_of_week = 6
    ORDER BY date_value
    LIMIT 5
");

foreach ($sampleSaturdays as $sat) {
    echo "   - {$sat['date_value']}: {$sat['day_type']} / {$sat['status']} - {$sat['description']}\n";
}

echo "\n=== DIAGNÓSTICO COMPLETO ===\n";
