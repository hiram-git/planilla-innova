<?php
/**
 * Script para reprocesar registros raw de attendance pendientes
 * Fecha: 28 de Octubre, 2025
 */

// Cargar autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar .env
if (file_exists(__DIR__ . '/../../.env')) {
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();
    }
}

echo "========================================\n";
echo "REPROCESAMIENTO DE ATTENDANCE RAW DATA\n";
echo "========================================\n\n";

try {
    // Importar servicio de sincronización
    require_once __DIR__ . '/../../app/Services/Attendance/AttendanceSyncService.php';

    $syncService = new \App\Services\Attendance\AttendanceSyncService();

    // Conectar a BD para obtener registros raw pendientes
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'planilla_prod';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ Conexión establecida a BD: $dbname\n\n";

    // Obtener registros raw no procesados
    $stmt = $pdo->query("
        SELECT id, raw_json
        FROM attendance_raw_data
        WHERE processed = 0
        ORDER BY received_at ASC
    ");

    $rawRecords = $stmt->fetchAll();
    $totalRecords = count($rawRecords);

    echo "📋 Total de registros pendientes: $totalRecords\n\n";

    if ($totalRecords == 0) {
        echo "✅ No hay registros pendientes para procesar.\n";
        exit(0);
    }

    echo "Procesando";

    $processed = 0;
    $errors = 0;
    $errorMessages = [];

    foreach ($rawRecords as $record) {
        try {
            $data = json_decode($record['raw_json'], true);

            if (!$data) {
                throw new Exception("JSON inválido en registro ID: " . $record['id']);
            }

            // Usar reflexión para acceder al método privado processAttendanceRecord
            $reflection = new ReflectionClass($syncService);
            $method = $reflection->getMethod('processAttendanceRecord');
            $method->setAccessible(true);

            // Procesar el registro
            $method->invoke($syncService, $data);

            $processed++;

            if ($processed % 10 == 0) {
                echo ".";
            }

        } catch (Exception $e) {
            $errors++;
            $errorMessages[] = "ID {$record['id']}: " . $e->getMessage();
        }
    }

    echo "\n\n";
    echo "========================================\n";
    echo "RESUMEN DE REPROCESAMIENTO\n";
    echo "========================================\n";
    echo "Total registros: $totalRecords\n";
    echo "Procesados: $processed\n";
    echo "Errores: $errors\n";

    if ($errors > 0) {
        echo "\n⚠️ ERRORES ENCONTRADOS:\n";
        foreach (array_slice($errorMessages, 0, 10) as $error) {
            echo "  - $error\n";
        }
        if ($errors > 10) {
            echo "  ... y " . ($errors - 10) . " errores más\n";
        }
    }

    // Obtener estadísticas del servicio
    $stats = $syncService->getStats();
    echo "\n📊 ESTADÍSTICAS:\n";
    echo "  - Insertados: {$stats['inserted']}\n";
    echo "  - Actualizados: {$stats['updated']}\n";
    echo "  - Omitidos: {$stats['skipped']}\n";
    echo "  - Errores: {$stats['errors']}\n";

    echo "\n✅ Reprocesamiento completado\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
