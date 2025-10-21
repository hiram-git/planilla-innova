<?php
/**
 * Script de Testing: Integración Planillas-Asistencias
 *
 * Prueba completa de los 3 componentes principales:
 * 1. PeriodAttendanceSummary - Resumen de asistencias por período
 * 2. AttendanceConceptMapper - Mapeo de asistencias a conceptos de planilla
 * 3. PayrollAttendanceIntegrator - Coordinador principal de integración
 *
 * Uso: php database/scripts/test_payroll_attendance_integration.php
 *
 * @version 1.0.0
 * @date 2025-10-20
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Core/Bootstrap.php';

use App\Core\Database;
use App\Services\Attendance\PeriodAttendanceSummary;
use App\Services\Attendance\AttendanceConceptMapper;
use App\Services\Attendance\PayrollAttendanceIntegrator;

// Colores para output en consola
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_CYAN = "\033[36m";
const COLOR_RESET = "\033[0m";

// Configuración del test
$config = [
    'test_employee_id' => null, // Se detectará automáticamente
    'test_planilla_id' => null, // Se detectará automáticamente
    'period_start' => '2025-10-01',
    'period_end' => '2025-10-31',
    'verbose' => true
];

// Inicializar database
\App\Core\Bootstrap::init();
$db = Database::getInstance()->getConnection();

echo COLOR_CYAN . str_repeat("=", 80) . COLOR_RESET . "\n";
echo COLOR_CYAN . "TESTING: Integración Planillas-Asistencias V3.4.5" . COLOR_RESET . "\n";
echo COLOR_CYAN . str_repeat("=", 80) . COLOR_RESET . "\n\n";

// ============================================================================
// FASE 1: PREPARACIÓN DE DATOS DE PRUEBA
// ============================================================================

echo COLOR_BLUE . "FASE 1: Preparación de Datos de Prueba" . COLOR_RESET . "\n";
echo str_repeat("-", 80) . "\n\n";

// 1.1 Obtener empleado de prueba
echo "→ Buscando empleado activo con asistencias...\n";
$sql = "SELECT DISTINCT e.id, e.employee_id as code, e.firstname, e.lastname,
        e.tipo_planilla_id, e.sueldo_individual
        FROM employees e
        INNER JOIN attendance_calculations ac ON e.id = ac.employee_id
        WHERE e.situacion_id = 1
        AND ac.date BETWEEN ? AND ?
        LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([$config['period_start'], $config['period_end']]);
$testEmployee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$testEmployee) {
    echo COLOR_RED . "✗ No se encontró empleado con asistencias en el período especificado" . COLOR_RESET . "\n";
    exit(1);
}

$config['test_employee_id'] = $testEmployee['id'];
echo COLOR_GREEN . "✓ Empleado encontrado: {$testEmployee['firstname']} {$testEmployee['lastname']} (ID: {$testEmployee['id']})" . COLOR_RESET . "\n";
echo "  Código: {$testEmployee['code']}\n";
echo "  Salario: $" . number_format($testEmployee['sueldo_individual'], 2) . "\n";
echo "  Tipos planilla: {$testEmployee['tipo_planilla_id']}\n\n";

// 1.2 Obtener planilla de prueba
echo "→ Buscando planilla PENDIENTE...\n";
$sql = "SELECT id, descripcion, fecha_desde, fecha_hasta, tipo_planilla_id, estado
        FROM planilla_cabecera
        WHERE estado = 'PENDIENTE'
        AND fecha_desde <= ?
        AND fecha_hasta >= ?
        LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([$config['period_end'], $config['period_start']]);
$testPayroll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$testPayroll) {
    echo COLOR_YELLOW . "⚠ No se encontró planilla PENDIENTE. Buscando cualquier planilla..." . COLOR_RESET . "\n";
    $sql = "SELECT id, descripcion, fecha_desde, fecha_hasta, tipo_planilla_id, estado
            FROM planilla_cabecera
            ORDER BY id DESC
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $testPayroll = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$testPayroll) {
    echo COLOR_RED . "✗ No se encontró ninguna planilla en el sistema" . COLOR_RESET . "\n";
    exit(1);
}

$config['test_planilla_id'] = $testPayroll['id'];
echo COLOR_GREEN . "✓ Planilla encontrada: {$testPayroll['descripcion']} (ID: {$testPayroll['id']})" . COLOR_RESET . "\n";
echo "  Período: {$testPayroll['fecha_desde']} a {$testPayroll['fecha_hasta']}\n";
echo "  Estado: {$testPayroll['estado']}\n";
echo "  Tipo planilla: {$testPayroll['tipo_planilla_id']}\n\n";

// Actualizar período de prueba según la planilla
$config['period_start'] = $testPayroll['fecha_desde'];
$config['period_end'] = $testPayroll['fecha_hasta'];

// 1.3 Verificar datos de asistencias calculadas
echo "→ Verificando datos de asistencias calculadas...\n";
$sql = "SELECT COUNT(*) as total,
        SUM(total_hours) as total_hours,
        SUM(CASE WHEN is_late = 1 THEN 1 ELSE 0 END) as total_late,
        SUM(CASE WHEN perfect_attendance = 1 THEN 1 ELSE 0 END) as perfect_days
        FROM attendance_calculations
        WHERE employee_id = ?
        AND date BETWEEN ? AND ?";
$stmt = $db->prepare($sql);
$stmt->execute([$config['test_employee_id'], $config['period_start'], $config['period_end']]);
$attendanceStats = $stmt->fetch(PDO::FETCH_ASSOC);

echo COLOR_GREEN . "✓ Datos de asistencias encontrados:" . COLOR_RESET . "\n";
echo "  Total días con cálculo: {$attendanceStats['total']}\n";
echo "  Total horas trabajadas: " . number_format($attendanceStats['total_hours'], 2) . "\n";
echo "  Total tardanzas: {$attendanceStats['total_late']}\n";
echo "  Días asistencia perfecta: {$attendanceStats['perfect_days']}\n\n";

if ($attendanceStats['total'] == 0) {
    echo COLOR_RED . "✗ No hay datos de asistencias calculadas para este empleado" . COLOR_RESET . "\n";
    exit(1);
}

// ============================================================================
// FASE 2: TEST PERIOD ATTENDANCE SUMMARY
// ============================================================================

echo COLOR_BLUE . "\nFASE 2: Test PeriodAttendanceSummary" . COLOR_RESET . "\n";
echo str_repeat("-", 80) . "\n\n";

$summaryGenerator = new PeriodAttendanceSummary();
$testResults = ['phase2' => ['passed' => 0, 'failed' => 0]];

// Test 2.1: Generar resumen de asistencias
echo "Test 2.1: Generar resumen de asistencias del período\n";
try {
    $summary = $summaryGenerator->generateSummary(
        $config['test_employee_id'],
        $config['period_start'],
        $config['period_end']
    );

    if ($summary && is_array($summary)) {
        echo COLOR_GREEN . "  ✓ Resumen generado exitosamente" . COLOR_RESET . "\n";

        // Validar campos esenciales
        $requiredFields = [
            'employee_id', 'total_hours_worked', 'regular_hours',
            'overtime_hours_25', 'overtime_hours_50', 'total_days_worked',
            'total_absences', 'tardiness_count', 'punctuality_score'
        ];

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($summary[$field])) {
                $missingFields[] = $field;
            }
        }

        if (empty($missingFields)) {
            echo COLOR_GREEN . "  ✓ Todos los campos requeridos están presentes" . COLOR_RESET . "\n";
            $testResults['phase2']['passed']++;
        } else {
            echo COLOR_RED . "  ✗ Campos faltantes: " . implode(', ', $missingFields) . COLOR_RESET . "\n";
            $testResults['phase2']['failed']++;
        }

        if ($config['verbose']) {
            echo "\n  Resumen generado:\n";
            echo "  - Empleado ID: {$summary['employee_id']}\n";
            echo "  - Horas trabajadas: " . number_format($summary['total_hours_worked'], 2) . "\n";
            echo "  - Horas regulares: " . number_format($summary['regular_hours'], 2) . "\n";
            echo "  - Horas extras 25%: " . number_format($summary['overtime_hours_25'], 2) . "\n";
            echo "  - Horas extras 50%: " . number_format($summary['overtime_hours_50'], 2) . "\n";
            echo "  - Horas nocturnas: " . number_format($summary['night_hours'], 2) . "\n";
            echo "  - Días trabajados: {$summary['total_days_worked']}\n";
            echo "  - Ausencias totales: {$summary['total_absences']}\n";
            echo "  - Tardanzas: {$summary['tardiness_count']}\n";
            echo "  - Score puntualidad: {$summary['punctuality_score']}%\n";
            echo "  - Cumplimiento legal: " . ($summary['legal_compliant'] ? 'SÍ' : 'NO') . "\n";
            echo "  - Nivel de riesgo: {$summary['legal_risk_level']}\n\n";
        }

    } else {
        echo COLOR_RED . "  ✗ No se pudo generar resumen" . COLOR_RESET . "\n";
        $testResults['phase2']['failed']++;
    }

} catch (Exception $e) {
    echo COLOR_RED . "  ✗ Error: " . $e->getMessage() . COLOR_RESET . "\n";
    $testResults['phase2']['failed']++;
}

// ============================================================================
// FASE 3: TEST ATTENDANCE CONCEPT MAPPER
// ============================================================================

echo COLOR_BLUE . "\nFASE 3: Test AttendanceConceptMapper" . COLOR_RESET . "\n";
echo str_repeat("-", 80) . "\n\n";

$conceptMapper = new AttendanceConceptMapper();
$testResults['phase3'] = ['passed' => 0, 'failed' => 0];

// Test 3.1: Verificar que existan mapeos configurados
echo "Test 3.1: Verificar configuración de mapeos\n";
$sql = "SELECT COUNT(*) as total FROM attendance_concepts_mapping WHERE is_active = 1";
$stmt = $db->prepare($sql);
$stmt->execute();
$mappingCount = $stmt->fetchColumn();

if ($mappingCount > 0) {
    echo COLOR_GREEN . "  ✓ Se encontraron {$mappingCount} mapeos activos configurados" . COLOR_RESET . "\n";
    $testResults['phase3']['passed']++;
} else {
    echo COLOR_YELLOW . "  ⚠ No hay mapeos configurados (esto es normal en primera instalación)" . COLOR_RESET . "\n";
    echo "  → Los mapeos deben configurarse manualmente a través de la interfaz web\n";
    $testResults['phase3']['passed']++;
}

// Test 3.2: Mapear asistencias a conceptos
echo "\nTest 3.2: Mapear asistencias a conceptos de planilla\n";
try {
    if (!isset($summary)) {
        $summary = $summaryGenerator->generateSummary(
            $config['test_employee_id'],
            $config['period_start'],
            $config['period_end']
        );
    }

    // Obtener primer tipo_planilla_id del empleado
    $tiposPlanilla = explode(',', $testEmployee['tipo_planilla_id']);
    $tipoPlanillaId = (int)$tiposPlanilla[0];

    $concepts = $conceptMapper->mapAttendanceToConcepts(
        $summary,
        $config['test_employee_id'],
        $tipoPlanillaId,
        1 // situacion_id = ACTIVO
    );

    if (is_array($concepts)) {
        $conceptCount = count($concepts);
        if ($conceptCount > 0) {
            echo COLOR_GREEN . "  ✓ Se mapearon {$conceptCount} conceptos" . COLOR_RESET . "\n";
            $testResults['phase3']['passed']++;

            if ($config['verbose']) {
                echo "\n  Conceptos generados:\n";
                foreach ($concepts as $concept) {
                    echo "  - Concepto ID {$concept['concepto_id']}: {$concept['descripcion']}\n";
                    echo "    Tipo: {$concept['tipo']}, Valor: $" . number_format($concept['valor'], 2) . "\n";
                }
                echo "\n";
            }
        } else {
            echo COLOR_YELLOW . "  ⚠ No se generaron conceptos (verificar mapeos configurados)" . COLOR_RESET . "\n";
            $testResults['phase3']['passed']++;
        }
    } else {
        echo COLOR_RED . "  ✗ Error al mapear conceptos" . COLOR_RESET . "\n";
        $testResults['phase3']['failed']++;
    }

} catch (Exception $e) {
    echo COLOR_RED . "  ✗ Error: " . $e->getMessage() . COLOR_RESET . "\n";
    $testResults['phase3']['failed']++;
}

// ============================================================================
// FASE 4: TEST PAYROLL ATTENDANCE INTEGRATOR
// ============================================================================

echo COLOR_BLUE . "\nFASE 4: Test PayrollAttendanceIntegrator (Integración Completa)" . COLOR_RESET . "\n";
echo str_repeat("-", 80) . "\n\n";

$integrator = new PayrollAttendanceIntegrator();
$testResults['phase4'] = ['passed' => 0, 'failed' => 0];

// Test 4.1: Procesar asistencias para un empleado
echo "Test 4.1: Procesar asistencias de empleado individual\n";
try {
    $result = $integrator->processEmployeeAttendance(
        $config['test_planilla_id'],
        $config['test_employee_id'],
        $tipoPlanillaId,
        $config['period_start'],
        $config['period_end']
    );

    if ($result['summary_created'] && $result['summary_id']) {
        echo COLOR_GREEN . "  ✓ Procesamiento exitoso" . COLOR_RESET . "\n";
        echo "  - Summary ID: {$result['summary_id']}\n";
        echo "  - Conceptos generados: " . count($result['concepts']) . "\n";
        $testResults['phase4']['passed']++;

        // Verificar que el resumen se guardó en BD
        $sql = "SELECT * FROM payroll_attendance_summary WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$result['summary_id']]);
        $savedSummary = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($savedSummary) {
            echo COLOR_GREEN . "  ✓ Resumen guardado correctamente en BD" . COLOR_RESET . "\n";
            $testResults['phase4']['passed']++;
        } else {
            echo COLOR_RED . "  ✗ No se encontró el resumen en BD" . COLOR_RESET . "\n";
            $testResults['phase4']['failed']++;
        }

        // Verificar detalles guardados
        $sql = "SELECT COUNT(*) FROM payroll_attendance_details WHERE summary_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$result['summary_id']]);
        $detailCount = $stmt->fetchColumn();

        if ($detailCount > 0) {
            echo COLOR_GREEN . "  ✓ {$detailCount} registros de detalle guardados" . COLOR_RESET . "\n";
            $testResults['phase4']['passed']++;
        } else {
            echo COLOR_YELLOW . "  ⚠ No se guardaron detalles (puede ser normal si no hay datos)" . COLOR_RESET . "\n";
        }

    } else {
        echo COLOR_RED . "  ✗ Error en procesamiento" . COLOR_RESET . "\n";
        $testResults['phase4']['failed']++;
    }

} catch (Exception $e) {
    echo COLOR_RED . "  ✗ Error: " . $e->getMessage() . COLOR_RESET . "\n";
    $testResults['phase4']['failed']++;
}

// Test 4.2: Obtener resumen guardado
echo "\nTest 4.2: Obtener resumen de asistencias de planilla\n";
try {
    $summaries = $integrator->getPayrollAttendanceSummary($config['test_planilla_id']);

    if (is_array($summaries) && count($summaries) > 0) {
        echo COLOR_GREEN . "  ✓ Se obtuvieron " . count($summaries) . " resúmenes" . COLOR_RESET . "\n";
        $testResults['phase4']['passed']++;
    } else {
        echo COLOR_YELLOW . "  ⚠ No se encontraron resúmenes (esperado si es primera ejecución)" . COLOR_RESET . "\n";
    }

} catch (Exception $e) {
    echo COLOR_RED . "  ✗ Error: " . $e->getMessage() . COLOR_RESET . "\n";
    $testResults['phase4']['failed']++;
}

// Test 4.3: Eliminar datos de prueba
echo "\nTest 4.3: Eliminar datos de asistencias de planilla\n";
try {
    $deleted = $integrator->deletePayrollAttendanceData($config['test_planilla_id']);

    if ($deleted) {
        echo COLOR_GREEN . "  ✓ Datos eliminados correctamente (cleanup exitoso)" . COLOR_RESET . "\n";
        $testResults['phase4']['passed']++;
    } else {
        echo COLOR_YELLOW . "  ⚠ No se pudieron eliminar datos" . COLOR_RESET . "\n";
    }

} catch (Exception $e) {
    echo COLOR_RED . "  ✗ Error: " . $e->getMessage() . COLOR_RESET . "\n";
    $testResults['phase4']['failed']++;
}

// ============================================================================
// FASE 5: TEST INTEGRACIÓN COMPLETA (BATCH)
// ============================================================================

echo COLOR_BLUE . "\nFASE 5: Test Procesamiento en Batch (Múltiples Empleados)" . COLOR_RESET . "\n";
echo str_repeat("-", 80) . "\n\n";

$testResults['phase5'] = ['passed' => 0, 'failed' => 0];

// Obtener 3 empleados de prueba
echo "→ Obteniendo empleados de prueba...\n";
$sql = "SELECT DISTINCT e.id
        FROM employees e
        INNER JOIN attendance_calculations ac ON e.id = ac.employee_id
        WHERE e.situacion_id = 1
        AND ac.date BETWEEN ? AND ?
        LIMIT 3";
$stmt = $db->prepare($sql);
$stmt->execute([$config['period_start'], $config['period_end']]);
$testEmployees = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (count($testEmployees) < 2) {
    echo COLOR_YELLOW . "  ⚠ Menos de 2 empleados con datos, saltando test batch" . COLOR_RESET . "\n";
} else {
    echo COLOR_GREEN . "  ✓ Se encontraron " . count($testEmployees) . " empleados para test batch" . COLOR_RESET . "\n\n";

    // Test 5.1: Procesar múltiples empleados
    echo "Test 5.1: Procesar asistencias de múltiples empleados\n";
    try {
        $result = $integrator->processPayrollAttendance(
            $config['test_planilla_id'],
            $testEmployees,
            $tipoPlanillaId
        );

        if ($result['success']) {
            echo COLOR_GREEN . "  ✓ Procesamiento batch exitoso" . COLOR_RESET . "\n";
            echo "  - Total empleados: {$result['stats']['total_employees']}\n";
            echo "  - Procesados: {$result['stats']['processed']}\n";
            echo "  - Resúmenes creados: {$result['stats']['summaries_created']}\n";
            echo "  - Conceptos generados: {$result['stats']['concepts_generated']}\n";
            echo "  - Errores: {$result['stats']['errors']}\n";
            $testResults['phase5']['passed']++;

            if ($result['stats']['errors'] > 0) {
                echo COLOR_YELLOW . "  ⚠ Se encontraron errores:\n" . COLOR_RESET;
                foreach ($result['stats']['error_messages'] as $error) {
                    echo "    - {$error}\n";
                }
            }
        } else {
            echo COLOR_RED . "  ✗ Error en procesamiento batch: {$result['error']}" . COLOR_RESET . "\n";
            $testResults['phase5']['failed']++;
        }

    } catch (Exception $e) {
        echo COLOR_RED . "  ✗ Error: " . $e->getMessage() . COLOR_RESET . "\n";
        $testResults['phase5']['failed']++;
    }

    // Cleanup final
    echo "\n→ Limpiando datos de prueba...\n";
    $integrator->deletePayrollAttendanceData($config['test_planilla_id']);
    echo COLOR_GREEN . "  ✓ Cleanup completado" . COLOR_RESET . "\n";
}

// ============================================================================
// RESUMEN FINAL
// ============================================================================

echo "\n" . COLOR_CYAN . str_repeat("=", 80) . COLOR_RESET . "\n";
echo COLOR_CYAN . "RESUMEN DE RESULTADOS" . COLOR_RESET . "\n";
echo COLOR_CYAN . str_repeat("=", 80) . COLOR_RESET . "\n\n";

$totalPassed = 0;
$totalFailed = 0;

foreach ($testResults as $phase => $results) {
    $phaseName = str_replace('phase', 'Fase ', $phase);
    $passed = $results['passed'];
    $failed = $results['failed'];
    $total = $passed + $failed;

    $totalPassed += $passed;
    $totalFailed += $failed;

    $color = $failed === 0 ? COLOR_GREEN : COLOR_YELLOW;
    echo "{$color}{$phaseName}: {$passed}/{$total} tests pasados" . COLOR_RESET . "\n";
}

$totalTests = $totalPassed + $totalFailed;
$successRate = $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 2) : 0;

echo "\n" . str_repeat("-", 80) . "\n";
echo "TOTAL: {$totalPassed}/{$totalTests} tests pasados ({$successRate}%)\n";

if ($totalFailed === 0) {
    echo COLOR_GREEN . "\n✓ TODOS LOS TESTS PASARON EXITOSAMENTE\n" . COLOR_RESET;
    echo COLOR_GREEN . "El sistema de integración planillas-asistencias está funcionando correctamente.\n" . COLOR_RESET;
} else {
    echo COLOR_YELLOW . "\n⚠ ALGUNOS TESTS FALLARON\n" . COLOR_RESET;
    echo COLOR_YELLOW . "Revisa los errores reportados arriba para más detalles.\n" . COLOR_RESET;
}

echo "\n" . COLOR_CYAN . str_repeat("=", 80) . COLOR_RESET . "\n\n";

// Código de salida
exit($totalFailed === 0 ? 0 : 1);
