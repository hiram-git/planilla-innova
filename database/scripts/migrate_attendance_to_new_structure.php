<?php
/**
 * Script de Migración: Attendance → Attendance Header/Detail
 * Fecha: 17 de Octubre, 2025
 * Descripción: Migra los datos existentes de la tabla `attendance`
 *              a la nueva estructura cabecera/detalle
 */

// Cargar autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar .env si existe
if (file_exists(__DIR__ . '/../../.env')) {
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();
    } else {
        // Fallback manual
        $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                putenv(trim($name) . '=' . trim($value));
                $_ENV[trim($name)] = trim($value);
                $_SERVER[trim($name)] = trim($value);
            }
        }
    }
}

// Configurar conexión PDO
try {
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'planilla_prod';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ Conexión establecida a BD: $dbname\n\n";

} catch (PDOException $e) {
    die("❌ Error conectando a BD: " . $e->getMessage() . "\n");
}

// ================================================
// PASO 1: Verificar tabla attendance original
// ================================================
echo "📋 PASO 1: Verificando tabla attendance...\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM attendance");
    $totalRecords = $stmt->fetch()['total'];
    echo "   Total registros en attendance: $totalRecords\n";

    if ($totalRecords == 0) {
        echo "⚠️  No hay registros para migrar. Proceso finalizado.\n";
        exit(0);
    }

} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

// ================================================
// PASO 2: Crear dispositivo por defecto
// ================================================
echo "\n📋 PASO 2: Creando dispositivo por defecto...\n";

try {
    // Verificar si ya existe
    $stmt = $pdo->query("SELECT id FROM attendance_devices WHERE device_code = 'LEGACY_MIGRATION' LIMIT 1");
    $device = $stmt->fetch();

    if (!$device) {
        $pdo->exec("
            INSERT INTO attendance_devices (device_code, device_name, device_type, location, is_active)
            VALUES ('LEGACY_MIGRATION', 'Datos Migrados del Sistema Anterior', 'MANUAL', 'Sistema Legacy', 1)
        ");
        $deviceId = $pdo->lastInsertId();
        echo "   ✅ Dispositivo creado con ID: $deviceId\n";
    } else {
        $deviceId = $device['id'];
        echo "   ℹ️  Dispositivo ya existe con ID: $deviceId\n";
    }

} catch (PDOException $e) {
    die("❌ Error creando dispositivo: " . $e->getMessage() . "\n");
}

// ================================================
// PASO 3: Agrupar marcaciones por fecha
// ================================================
echo "\n📋 PASO 3: Agrupando marcaciones por fecha...\n";

try {
    $stmt = $pdo->query("
        SELECT
            DATE(a.date) as attendance_date,
            COUNT(*) as total_records,
            COUNT(DISTINCT a.employee_id) as total_employees,
            SUM(CASE WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL THEN 1 ELSE 0 END) as total_on_time,
            0 as total_late,
            0 as total_absent
        FROM attendance a
        GROUP BY DATE(a.date)
        ORDER BY attendance_date ASC
    ");

    $dateGroups = $stmt->fetchAll();
    $totalDates = count($dateGroups);

    echo "   Total de fechas únicas: $totalDates\n";

} catch (PDOException $e) {
    die("❌ Error agrupando fechas: " . $e->getMessage() . "\n");
}

// ================================================
// PASO 4: Crear registros de cabecera
// ================================================
echo "\n📋 PASO 4: Creando registros de cabecera...\n";

$headersCreated = 0;
$headersSkipped = 0;

try {
    $pdo->beginTransaction();

    $insertHeaderStmt = $pdo->prepare("
        INSERT INTO attendance_header (
            attendance_date, device_id, total_records, total_employees,
            total_on_time, total_late, total_absent,
            is_processed, sync_batch_id, synced_from
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'LEGACY_MIGRATION', 'MANUAL')
        ON DUPLICATE KEY UPDATE
            total_records = VALUES(total_records),
            total_employees = VALUES(total_employees)
    ");

    foreach ($dateGroups as $group) {
        $insertHeaderStmt->execute([
            $group['attendance_date'],
            $deviceId,
            $group['total_records'],
            $group['total_employees'],
            $group['total_on_time'],
            $group['total_late'],
            $group['total_absent']
        ]);

        if ($insertHeaderStmt->rowCount() > 0) {
            $headersCreated++;
        } else {
            $headersSkipped++;
        }
    }

    $pdo->commit();
    echo "   ✅ Cabeceras creadas: $headersCreated\n";
    if ($headersSkipped > 0) {
        echo "   ℹ️  Cabeceras omitidas (duplicadas): $headersSkipped\n";
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    die("❌ Error creando cabeceras: " . $e->getMessage() . "\n");
}

// ================================================
// PASO 5: Migrar detalles de marcaciones
// ================================================
echo "\n📋 PASO 5: Migrando detalles de marcaciones...\n";

$detailsCreated = 0;
$detailsSkipped = 0;
$detailsErrors = 0;

try {
    // Obtener todas las marcaciones
    $stmt = $pdo->query("
        SELECT
            a.id,
            a.date as attendance_date,
            a.employee_id,
            a.time_in,
            a.time_out,
            a.status,
            a.num_hr,
            NOW() as created_at
        FROM attendance a
        ORDER BY a.date ASC, a.employee_id ASC
    ");

    $attendanceRecords = $stmt->fetchAll();
    $totalToMigrate = count($attendanceRecords);

    echo "   Total de marcaciones a migrar: $totalToMigrate\n";
    echo "   Procesando";

    $pdo->beginTransaction();

    // Preparar statement de inserción
    $insertDetailStmt = $pdo->prepare("
        INSERT INTO attendance_detail (
            header_id, employee_id,
            time_in, time_out, hours_worked,
            device_id, external_id,
            status, created_at
        )
        SELECT
            h.id,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        FROM attendance_header h
        WHERE h.attendance_date = ?
          AND h.device_id = ?
        LIMIT 1
        ON DUPLICATE KEY UPDATE
            time_in = VALUES(time_in),
            time_out = VALUES(time_out),
            status = VALUES(status)
    ");

    $counter = 0;
    foreach ($attendanceRecords as $record) {
        try {
            // Mapear status (1 = PRESENT, otros = ABSENT)
            $statusText = ($record['status'] == 1) ? 'PRESENT' : 'ABSENT';

            $insertDetailStmt->execute([
                $record['employee_id'],
                $record['time_in'],
                $record['time_out'],
                $record['num_hr'] ?: 0,
                $deviceId,
                'LEGACY_' . $record['id'],
                $statusText,
                $record['created_at'],
                $record['attendance_date'],
                $deviceId
            ]);

            if ($insertDetailStmt->rowCount() > 0) {
                $detailsCreated++;
            } else {
                $detailsSkipped++;
            }

        } catch (PDOException $e) {
            $detailsErrors++;
            error_log("Error migrando detalle ID {$record['id']}: " . $e->getMessage());
        }

        $counter++;
        if ($counter % 100 == 0) {
            echo ".";
        }
    }

    $pdo->commit();
    echo "\n   ✅ Detalles creados: $detailsCreated\n";

    if ($detailsSkipped > 0) {
        echo "   ℹ️  Detalles omitidos (duplicados): $detailsSkipped\n";
    }

    if ($detailsErrors > 0) {
        echo "   ⚠️  Detalles con error: $detailsErrors\n";
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("❌ Error migrando detalles: " . $e->getMessage() . "\n");
}

// ================================================
// PASO 6: Verificar migración
// ================================================
echo "\n📋 PASO 6: Verificando migración...\n";

try {
    // Contar registros migrados
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM attendance_header");
    $totalHeaders = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM attendance_detail");
    $totalDetails = $stmt->fetch()['total'];

    echo "   Total cabeceras: $totalHeaders\n";
    echo "   Total detalles: $totalDetails\n";

    // Comparar con original
    echo "\n   Comparación:\n";
    echo "   - Registros originales: $totalRecords\n";
    echo "   - Registros migrados: $totalDetails\n";

    if ($totalDetails >= $totalRecords) {
        echo "   ✅ Migración exitosa\n";
    } else {
        echo "   ⚠️  Hay diferencias en la migración\n";
    }

} catch (PDOException $e) {
    echo "❌ Error en verificación: " . $e->getMessage() . "\n";
}

// ================================================
// RESUMEN FINAL
// ================================================
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESUMEN DE MIGRACIÓN\n";
echo str_repeat("=", 60) . "\n";
echo "Registros originales:     $totalRecords\n";
echo "Fechas únicas:            $totalDates\n";
echo "Cabeceras creadas:        $headersCreated\n";
echo "Detalles creados:         $detailsCreated\n";
echo "Detalles omitidos:        $detailsSkipped\n";
echo "Errores:                  $detailsErrors\n";
echo str_repeat("=", 60) . "\n";
echo "\n✅ Migración completada exitosamente\n";
echo "\nℹ️  IMPORTANTE: La tabla `attendance` original NO ha sido eliminada.\n";
echo "   Puedes mantenerla como respaldo o eliminarla manualmente si\n";
echo "   estás seguro de que la migración es correcta.\n\n";
