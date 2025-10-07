<?php
/**
 * Script para llenar automáticamente el calendario empresarial del año 2025
 *
 * Este script:
 * - Mantiene los feriados nacionales ya existentes
 * - Completa todos los días laborables (Lunes-Viernes)
 * - Completa todos los fines de semana (Sábado-Domingo)
 *
 * Uso: php database/scripts/fill_business_calendar_2025.php
 */

// Cargar variables de entorno desde .env
$rootPath = dirname(dirname(__DIR__));
$envFile = $rootPath . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, "\"'");
        }
    }
}

// Configuración de BD
$host = $_ENV['DB_HOST'] ?? 'localhost';
$database = $_ENV['DB_DATABASE'] ?? 'planilla_innova29092025';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    // Conectar a la base de datos
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "============================================\n";
    echo "LLENADO AUTOMÁTICO CALENDARIO 2025\n";
    echo "============================================\n\n";

    $year = 2025;
    $startDate = new DateTime("$year-01-01");
    $endDate = new DateTime("$year-12-31");

    // Obtener todos los días que ya existen en la BD para este año
    $stmt = $pdo->prepare("SELECT date_value FROM business_calendar WHERE year_value = ?");
    $stmt->execute([$year]);
    $existingDates = array_column($stmt->fetchAll(), 'date_value');
    $existingDatesSet = array_flip($existingDates);

    echo "Fechas existentes en BD: " . count($existingDates) . "\n";
    echo "Procesando año $year...\n\n";

    // Preparar statement para inserción
    $insertStmt = $pdo->prepare("
        INSERT INTO business_calendar
        (date_value, year_value, month_value, day_of_week, day_type, status, description, is_weekend)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $diasSemana = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday'
    ];

    $diasSemanaEs = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo'
    ];

    $inserted = 0;
    $skipped = 0;

    // Iterar cada día del año
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        $dateStr = $currentDate->format('Y-m-d');

        // Si ya existe, saltarlo
        if (isset($existingDatesSet[$dateStr])) {
            $skipped++;
            $currentDate->modify('+1 day');
            continue;
        }

        // Determinar tipo de día
        $dayOfWeek = (int)$currentDate->format('N'); // 1=Lunes, 7=Domingo
        $isWeekend = ($dayOfWeek >= 6);

        $dayType = $isWeekend ? 'NO_LABORAL' : 'LABORAL';
        $description = $isWeekend
            ? $diasSemanaEs[$dayOfWeek]
            : "Día laboral - {$diasSemana[$dayOfWeek]}";

        // Insertar
        $insertStmt->execute([
            $dateStr,                              // date_value
            (int)$currentDate->format('Y'),        // year_value
            (int)$currentDate->format('m'),        // month_value
            $dayOfWeek,                            // day_of_week
            $dayType,                              // day_type
            'NORMAL',                              // status
            $description,                          // description
            $isWeekend ? 1 : 0                     // is_weekend
        ]);

        $inserted++;
        $currentDate->modify('+1 day');
    }

    echo "✅ Proceso completado:\n";
    echo "   - Días insertados: $inserted\n";
    echo "   - Días omitidos (ya existían): $skipped\n\n";

    // Mostrar estadísticas finales
    $statsStmt = $pdo->prepare("
        SELECT day_type, COUNT(*) as total
        FROM business_calendar
        WHERE year_value = ?
        GROUP BY day_type
        ORDER BY day_type
    ");
    $statsStmt->execute([$year]);
    $stats = $statsStmt->fetchAll();

    echo "📊 Estadísticas finales del calendario $year:\n";
    foreach ($stats as $stat) {
        echo "   - {$stat['day_type']}: {$stat['total']} días\n";
    }

    // Total general
    $totalStmt = $pdo->prepare("SELECT COUNT(*) as total FROM business_calendar WHERE year_value = ?");
    $totalStmt->execute([$year]);
    $total = $totalStmt->fetch()['total'];
    echo "\n   TOTAL: $total días\n";

    // Verificar integridad (debe ser 365 o 366)
    $expectedDays = (date('L', strtotime("$year-01-01"))) ? 366 : 365;
    if ($total == $expectedDays) {
        echo "   ✅ Calendario completo ($expectedDays días)\n";
    } else {
        echo "   ⚠️  Advertencia: se esperaban $expectedDays días pero hay $total\n";
    }

    echo "\n============================================\n";
    echo "✅ LLENADO COMPLETADO EXITOSAMENTE\n";
    echo "============================================\n";

} catch (PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
