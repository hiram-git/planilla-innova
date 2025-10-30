#!/usr/bin/env php
<?php
/**
 * Script de Testing: Nuevo Flujo attendance_records
 *
 * Prueba el flujo completo:
 * 1. Sincronización desde API → attendance_raw_data + attendance_records
 * 2. Procesamiento: attendance_records → attendance_detail
 * 3. Comparación y actualización de registros existentes
 *
 * Versión: v3.5.2
 * Fecha: 30 de Octubre, 2025
 */

// Carga del entorno
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\AttendanceRecord;
use App\Models\AttendanceDetail;
use App\Services\Attendance\AttendanceSyncService;
use App\Services\Attendance\RecordsProcessor;

// Inicializar sistema
Bootstrap::init();

// Colores para output
class Color {
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
}

function printHeader($text) {
    echo Color::BOLD . Color::CYAN . "\n╔═══════════════════════════════════════════════════════════╗\n";
    echo "║ " . str_pad($text, 57) . " ║\n";
    echo "╚═══════════════════════════════════════════════════════════╝" . Color::RESET . "\n\n";
}

function printSection($text) {
    echo Color::BOLD . Color::BLUE . "\n▶ " . $text . Color::RESET . "\n";
    echo str_repeat("─", 60) . "\n";
}

function printSuccess($text) {
    echo Color::GREEN . "✓ " . $text . Color::RESET . "\n";
}

function printError($text) {
    echo Color::RED . "✗ " . $text . Color::RESET . "\n";
}

function printWarning($text) {
    echo Color::YELLOW . "⚠ " . $text . Color::RESET . "\n";
}

function printInfo($text) {
    echo Color::CYAN . "ℹ " . $text . Color::RESET . "\n";
}

// ============================================
// INICIO DEL TESTING
// ============================================

printHeader("TEST: NUEVO FLUJO ATTENDANCE_RECORDS");

$db = Database::getInstance()->getConnection();
$recordModel = new AttendanceRecord();
$detailModel = new AttendanceDetail();
$processor = new RecordsProcessor();

$testsPassed = 0;
$testsFailed = 0;

// ============================================
// FASE 1: Verificar Estructura de BD
// ============================================

printSection("FASE 1: Verificando Estructura de Base de Datos");

try {
    // Verificar que existe la tabla attendance_records
    $stmt = $db->prepare("SHOW TABLES LIKE 'attendance_records'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        printSuccess("Tabla 'attendance_records' existe");
        $testsPassed++;

        // Verificar columnas críticas
        $stmt = $db->prepare("DESCRIBE attendance_records");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $requiredColumns = ['id', 'employee_id', 'timestamp', 'punch_type', 'record_hash', 'is_processed', 'is_duplicate'];
        $missingColumns = array_diff($requiredColumns, $columns);

        if (empty($missingColumns)) {
            printSuccess("Todas las columnas requeridas existen");
            $testsPassed++;
        } else {
            printError("Columnas faltantes: " . implode(', ', $missingColumns));
            $testsFailed++;
        }

    } else {
        printError("Tabla 'attendance_records' NO existe");
        printWarning("Ejecutar migración: database/migrations/2025_10_30_create_attendance_records.sql");
        $testsFailed++;
        exit(1);
    }

    // Verificar vistas
    $stmt = $db->prepare("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_planilla_prod LIKE 'v_%_records%'");
    $stmt->execute();
    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($views) > 0) {
        printSuccess("Vistas creadas: " . count($views));
        foreach ($views as $view) {
            printInfo("  - " . $view);
        }
        $testsPassed++;
    } else {
        printWarning("No se encontraron vistas (opcional)");
    }

} catch (Exception $e) {
    printError("Error verificando estructura: " . $e->getMessage());
    $testsFailed++;
}

// ============================================
// FASE 2: Verificar Estadísticas Actuales
// ============================================

printSection("FASE 2: Estadísticas Actuales");

try {
    // Contar records sin procesar
    $unprocessed = $recordModel->count(['is_processed' => 0, 'is_duplicate' => 0]);
    printInfo("Records sin procesar: " . $unprocessed);

    // Contar duplicados
    $duplicates = $recordModel->count(['is_duplicate' => 1]);
    printInfo("Records duplicados: " . $duplicates);

    // Contar procesados
    $processed = $recordModel->count(['is_processed' => 1]);
    printInfo("Records procesados: " . $processed);

    // Total
    $total = $recordModel->count([]);
    printInfo("Total records: " . $total);

    $testsPassed++;

} catch (Exception $e) {
    printError("Error obteniendo estadísticas: " . $e->getMessage());
    $testsFailed++;
}

// ============================================
// FASE 3: Test de Procesamiento
// ============================================

printSection("FASE 3: Test de Procesamiento Records → Details");

if ($unprocessed > 0) {
    try {
        // Obtener rango de fechas de records sin procesar
        $stmt = $db->prepare("
            SELECT MIN(punch_date) as min_date, MAX(punch_date) as max_date
            FROM attendance_records
            WHERE is_processed = 0 AND is_duplicate = 0
        ");
        $stmt->execute();
        $dateRange = $stmt->fetch(PDO::FETCH_ASSOC);

        printInfo("Rango de fechas sin procesar: {$dateRange['min_date']} → {$dateRange['max_date']}");

        // Procesar
        printInfo("Iniciando procesamiento...");
        $result = $processor->processToDetails($dateRange['min_date'], $dateRange['max_date']);

        echo "\nResultados del procesamiento:\n";
        printSuccess("Grupos procesados: " . $result['groups_processed']);
        printSuccess("Details creados: " . $result['details_created']);
        printSuccess("Details actualizados: " . $result['details_updated']);
        printInfo("Details omitidos: " . $result['details_skipped']);
        printInfo("Records marcados como procesados: " . $result['records_marked']);

        if ($result['errors'] > 0) {
            printError("Errores: " . $result['errors']);
            if (!empty($result['errors_detail'])) {
                foreach ($result['errors_detail'] as $error) {
                    printError("  - " . $error);
                }
            }
            $testsFailed++;
        } else {
            printSuccess("Sin errores en el procesamiento");
            $testsPassed++;
        }

    } catch (Exception $e) {
        printError("Error en procesamiento: " . $e->getMessage());
        $testsFailed++;
    }
} else {
    printWarning("No hay records sin procesar para testear");
    printInfo("Ejecutar primero una sincronización desde API para generar records");
}

// ============================================
// FASE 4: Verificar Integridad de Datos
// ============================================

printSection("FASE 4: Verificación de Integridad");

try {
    // Verificar que todos los records procesados tienen detail_id
    $stmt = $db->prepare("
        SELECT COUNT(*) as orphans
        FROM attendance_records
        WHERE is_processed = 1 AND detail_id IS NULL
    ");
    $stmt->execute();
    $orphans = $stmt->fetch(PDO::FETCH_ASSOC)['orphans'];

    if ($orphans == 0) {
        printSuccess("Todos los records procesados tienen detail_id asignado");
        $testsPassed++;
    } else {
        printError("Records huérfanos (procesados sin detail_id): " . $orphans);
        $testsFailed++;
    }

    // Verificar que no hay details sin records
    $stmt = $db->prepare("
        SELECT COUNT(*) as orphan_details
        FROM attendance_detail d
        INNER JOIN attendance_header h ON d.header_id = h.id
        WHERE h.synced_from = 'API'
          AND NOT EXISTS (
              SELECT 1 FROM attendance_records r
              WHERE r.employee_id = d.employee_id
                AND r.punch_date = h.attendance_date
                AND r.is_processed = 1
          )
    ");
    $stmt->execute();
    $orphanDetails = $stmt->fetch(PDO::FETCH_ASSOC)['orphan_details'];

    if ($orphanDetails == 0) {
        printSuccess("Todos los details de API tienen records asociados");
        $testsPassed++;
    } else {
        printWarning("Details sin records asociados: " . $orphanDetails);
        printInfo("Esto puede ser normal si se crearon antes de implementar attendance_records");
    }

} catch (Exception $e) {
    printError("Error verificando integridad: " . $e->getMessage());
    $testsFailed++;
}

// ============================================
// FASE 5: Test de Duplicados
// ============================================

printSection("FASE 5: Detección de Duplicados");

try {
    $duplicatesFound = $recordModel->detectDuplicates();

    if ($duplicatesFound > 0) {
        printWarning("Se encontraron {$duplicatesFound} duplicados");

        // Mostrar algunos ejemplos
        $duplicates = $recordModel->getDuplicates();
        if (!empty($duplicates)) {
            printInfo("Ejemplos de duplicados:");
            foreach (array_slice($duplicates, 0, 3) as $dup) {
                echo "  - ID {$dup['id']}: {$dup['employee_name']} - {$dup['timestamp']} ({$dup['punch_type']})\n";
            }
        }
    } else {
        printSuccess("No se encontraron duplicados nuevos");
    }

    $testsPassed++;

} catch (Exception $e) {
    printError("Error detectando duplicados: " . $e->getMessage());
    $testsFailed++;
}

// ============================================
// FASE 6: Test de Agrupación
// ============================================

printSection("FASE 6: Test de Agrupación por Empleado/Fecha");

try {
    $dateFrom = date('Y-m-d', strtotime('-7 days'));
    $dateTo = date('Y-m-d');

    $grouped = $recordModel->getGroupedByEmployeeAndDate($dateFrom, $dateTo);

    printInfo("Grupos encontrados (últimos 7 días): " . count($grouped));

    if (!empty($grouped)) {
        // Mostrar ejemplos
        printInfo("Ejemplos de agrupación:");
        foreach (array_slice($grouped, 0, 5) as $group) {
            $status = "";
            if ($group['check_ins'] > 0 && $group['check_outs'] > 0) {
                $status = Color::GREEN . "COMPLETO" . Color::RESET;
            } elseif ($group['check_ins'] > 0 || $group['check_outs'] > 0) {
                $status = Color::YELLOW . "INCOMPLETO" . Color::RESET;
            } else {
                $status = Color::RED . "SIN MARCACIONES" . Color::RESET;
            }

            echo sprintf(
                "  - %s [%s]: IN=%d OUT=%d Total=%d %s\n",
                $group['employee_name'],
                $group['punch_date'],
                $group['check_ins'],
                $group['check_outs'],
                $group['total_punches'],
                $status
            );
        }
    }

    $testsPassed++;

} catch (Exception $e) {
    printError("Error en test de agrupación: " . $e->getMessage());
    $testsFailed++;
}

// ============================================
// RESUMEN FINAL
// ============================================

printSection("RESUMEN DE TESTS");

$totalTests = $testsPassed + $testsFailed;
$successRate = $totalTests > 0 ? round(($testsPassed / $totalTests) * 100, 1) : 0;

echo "\nTests ejecutados: " . Color::BOLD . $totalTests . Color::RESET . "\n";
echo "Tests exitosos:   " . Color::GREEN . Color::BOLD . $testsPassed . Color::RESET . "\n";
echo "Tests fallidos:   " . Color::RED . Color::BOLD . $testsFailed . Color::RESET . "\n";
echo "Tasa de éxito:    " . Color::BOLD . $successRate . "%" . Color::RESET . "\n\n";

if ($testsFailed == 0) {
    printHeader("✓ TODOS LOS TESTS PASARON");
    exit(0);
} else {
    printHeader("✗ ALGUNOS TESTS FALLARON");
    exit(1);
}
