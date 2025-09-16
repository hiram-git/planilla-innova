<?php
/**
 * TEST: Nueva Arquitectura de Acumulados
 * 
 * Prueba el sistema dual de acumulados:
 * 1. acumulados_por_empleado (detallado por transacción)
 * 2. acumulados_por_planilla (consolidado optimizado)
 */

require_once '../app/Core/Database.php';
require_once '../app/Models/PayrollAccumulationsProcessor.php';

use App\Core\Database;
use App\Models\PayrollAccumulationsProcessor;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🧪 TEST: Nueva Arquitectura de Acumulados</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>📋 Paso 1: Verificar estructura de tablas</h2>";
    
    // Verificar tablas necesarias
    $tables = [
        'acumulados_por_empleado' => 'Registros detallados por transacción',
        'acumulados_por_planilla' => 'Consolidado optimizado para reportes',
        'planilla_detalle' => 'Detalles de planillas (fuente de datos)',
        'planilla_cabecera' => 'Cabeceras de planillas'
    ];
    
    $tablesStatus = [];
    foreach ($tables as $table => $description) {
        $exists = $db->query("SHOW TABLES LIKE '$table'")->fetch();
        $status = $exists ? '✅' : '❌';
        $color = $exists ? 'green' : 'red';
        echo "<p style='color: $color;'><strong>$status $table:</strong> $description</p>";
        $tablesStatus[$table] = $exists ? true : false;
    }
    
    // Si alguna tabla no existe, mostrar instrucciones
    if (in_array(false, $tablesStatus)) {
        echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336; margin: 15px 0;'>";
        echo "<h3>⚠️ TABLAS FALTANTES</h3>";
        echo "<p><strong>Para continuar, ejecuta primero:</strong></p>";
        echo "<p><code>mysql -u root -p planilla_innova < databases/migration_new_acumulados_structure.sql</code></p>";
        echo "<p>O desde phpMyAdmin: Importar > databases/migration_new_acumulados_structure.sql</p>";
        echo "</div>";
        exit;
    }
    
    echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50; margin: 10px 0;'>";
    echo "<strong>✅ Todas las tablas requeridas están presentes</strong>";
    echo "</div>";
    
    echo "<h2>🔍 Paso 2: Verificar estructura de campos</h2>";
    
    // Verificar estructura de acumulados_por_empleado
    echo "<h3>📊 acumulados_por_empleado:</h3>";
    $columns1 = $db->query("DESCRIBE acumulados_por_empleado")->fetchAll(PDO::FETCH_ASSOC);
    echo "<ul>";
    foreach ($columns1 as $col) {
        echo "<li><strong>{$col['Field']}</strong> ({$col['Type']}) - {$col['Comment']}</li>";
    }
    echo "</ul>";
    
    // Verificar estructura de acumulados_por_planilla
    echo "<h3>📊 acumulados_por_planilla:</h3>";
    $columns2 = $db->query("DESCRIBE acumulados_por_planilla")->fetchAll(PDO::FETCH_ASSOC);
    echo "<ul>";
    foreach ($columns2 as $col) {
        if (!empty($col['Comment'])) {
            echo "<li><strong>{$col['Field']}</strong> ({$col['Type']}) - {$col['Comment']}</li>";
        }
    }
    echo "</ul>";
    
    echo "<h2>🧪 Paso 3: Probar procesador de acumulados</h2>";
    
    // Buscar planilla PROCESADA para probar
    $stmt = $db->prepare("
        SELECT 
            pc.id, 
            pc.descripcion, 
            pc.estado, 
            pc.fecha,
            (SELECT COUNT(*) FROM planilla_detalle WHERE planilla_cabecera_id = pc.id) as total_detalles
        FROM planilla_cabecera pc 
        WHERE pc.estado = 'PROCESADA'
        ORDER BY pc.id DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $testPayroll = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testPayroll) {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107;'>";
        echo "<h4>⚠️ No hay planillas PROCESADAS</h4>";
        echo "<p>Para probar el procesador, necesitas una planilla en estado PROCESADA con detalles.</p>";
        echo "</div>";
    } else {
        echo "<h4>🎯 Planilla de prueba encontrada:</h4>";
        echo "<p><strong>ID:</strong> {$testPayroll['id']}</p>";
        echo "<p><strong>Descripción:</strong> {$testPayroll['descripcion']}</p>";
        echo "<p><strong>Estado:</strong> {$testPayroll['estado']}</p>";
        echo "<p><strong>Detalles:</strong> {$testPayroll['total_detalles']}</p>";
        
        if (isset($_GET['test_process']) && $_GET['test_process'] == $testPayroll['id']) {
            echo "<h3>🚀 Ejecutando procesamiento de prueba...</h3>";
            
            try {
                $processor = new PayrollAccumulationsProcessor();
                $results = $processor->processPayrollAccumulations($testPayroll['id']);
                
                echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 15px 0;'>";
                echo "<h4>✅ PROCESAMIENTO EXITOSO</h4>";
                echo "<ul>";
                echo "<li><strong>Registros detallados creados:</strong> {$results['detailed_records']}</li>";
                echo "<li><strong>Empleados consolidados:</strong> {$results['consolidated_records']}</li>";
                echo "<li><strong>Total empleados:</strong> {$results['total_employees']}</li>";
                echo "<li><strong>Tiempo de procesamiento:</strong> " . number_format($results['processing_time'], 4) . " segundos</li>";
                echo "</ul>";
                echo "</div>";
                
                // Mostrar muestras de los registros creados
                echo "<h4>📊 Muestra de registros detallados (acumulados_por_empleado):</h4>";
                $sampleDetailed = $db->prepare("
                    SELECT 
                        ape.employee_id,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_empleado,
                        c.descripcion as concepto,
                        ape.monto,
                        ape.tipo_concepto,
                        ape.mes,
                        ape.ano,
                        ape.frecuencia
                    FROM acumulados_por_empleado ape
                    INNER JOIN employees e ON ape.employee_id = e.id
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    WHERE ape.planilla_id = ?
                    ORDER BY ape.employee_id, ape.tipo_concepto, ape.monto DESC
                    LIMIT 10
                ");
                $sampleDetailed->execute([$testPayroll['id']]);
                $detailedSamples = $sampleDetailed->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($detailedSamples)) {
                    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
                    echo "<tr style='background: #f0f0f0;'>";
                    echo "<th>Empleado</th><th>Concepto</th><th>Monto</th><th>Tipo</th><th>Período</th>";
                    echo "</tr>";
                    
                    foreach ($detailedSamples as $sample) {
                        $color = $sample['tipo_concepto'] === 'ASIGNACION' ? '#e8f5e8' : '#ffe8e8';
                        echo "<tr style='background: $color;'>";
                        echo "<td>{$sample['nombre_empleado']}</td>";
                        echo "<td>{$sample['concepto']}</td>";
                        echo "<td>$" . number_format($sample['monto'], 2) . "</td>";
                        echo "<td>{$sample['tipo_concepto']}</td>";
                        echo "<td>{$sample['mes']}/{$sample['ano']} ({$sample['frecuencia']})</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
                // Mostrar muestras de registros consolidados
                echo "<h4>📊 Muestra de registros consolidados (acumulados_por_planilla):</h4>";
                $sampleConsolidated = $db->prepare("
                    SELECT 
                        ap.employee_id,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_empleado,
                        ap.sueldos,
                        ap.gastos_representacion,
                        ap.otras_asignaciones,
                        ap.total_asignaciones,
                        ap.seguro_social,
                        ap.seguro_educativo,
                        ap.impuesto_renta,
                        ap.otras_deducciones,
                        ap.total_deducciones,
                        ap.total_neto
                    FROM acumulados_por_planilla ap
                    INNER JOIN employees e ON ap.employee_id = e.id
                    WHERE ap.planilla_id = ?
                    ORDER BY ap.total_neto DESC
                    LIMIT 5
                ");
                $sampleConsolidated->execute([$testPayroll['id']]);
                $consolidatedSamples = $sampleConsolidated->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($consolidatedSamples)) {
                    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 11px;'>";
                    echo "<tr style='background: #f0f0f0;'>";
                    echo "<th>Empleado</th><th>Sueldos</th><th>G.Rep.</th><th>Otras Asig.</th><th>Total Asig.</th>";
                    echo "<th>SS</th><th>SE</th><th>ISR</th><th>Otras Ded.</th><th>Total Ded.</th><th>Neto</th>";
                    echo "</tr>";
                    
                    foreach ($consolidatedSamples as $sample) {
                        echo "<tr>";
                        echo "<td>{$sample['nombre_empleado']}</td>";
                        echo "<td>$" . number_format($sample['sueldos'], 2) . "</td>";
                        echo "<td>$" . number_format($sample['gastos_representacion'], 2) . "</td>";
                        echo "<td>$" . number_format($sample['otras_asignaciones'], 2) . "</td>";
                        echo "<td><strong>$" . number_format($sample['total_asignaciones'], 2) . "</strong></td>";
                        echo "<td>$" . number_format($sample['seguro_social'], 2) . "</td>";
                        echo "<td>$" . number_format($sample['seguro_educativo'], 2) . "</td>";
                        echo "<td>$" . number_format($sample['impuesto_renta'], 2) . "</td>";
                        echo "<td>$" . number_format($sample['otras_deducciones'], 2) . "</td>";
                        echo "<td><strong>$" . number_format($sample['total_deducciones'], 2) . "</strong></td>";
                        echo "<td style='background: #e8f5e8;'><strong>$" . number_format($sample['total_neto'], 2) . "</strong></td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
            } catch (Exception $e) {
                echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336;'>";
                echo "<h4>❌ Error en el procesamiento</h4>";
                echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        } else {
            echo "<p><a href='?test_process={$testPayroll['id']}' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>🧪 Probar Procesamiento</a></p>";
        }
    }
    
    echo "<h2>📊 Paso 4: Estadísticas del sistema</h2>";
    
    // Estadísticas generales
    $stats = [];
    $stats['planillas_total'] = $db->query("SELECT COUNT(*) FROM planilla_cabecera")->fetchColumn();
    $stats['planillas_procesadas'] = $db->query("SELECT COUNT(*) FROM planilla_cabecera WHERE estado = 'PROCESADA'")->fetchColumn();
    $stats['acumulados_detallados'] = $db->query("SELECT COUNT(*) FROM acumulados_por_empleado")->fetchColumn();
    $stats['acumulados_consolidados'] = $db->query("SELECT COUNT(*) FROM acumulados_por_planilla")->fetchColumn();
    $stats['empleados_con_acumulados'] = $db->query("SELECT COUNT(DISTINCT employee_id) FROM acumulados_por_planilla")->fetchColumn();
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0;'>";
    
    foreach ($stats as $label => $value) {
        $labelFormatted = ucfirst(str_replace('_', ' ', $label));
        echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; text-align: center;'>";
        echo "<h4 style='margin: 0; color: #495057; font-size: 12px;'>$labelFormatted</h4>";
        echo "<p style='margin: 5px 0 0 0; font-size: 20px; font-weight: bold; color: #007cba;'>$value</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    echo "<h2>🎯 Conclusiones</h2>";
    
    echo "<div style='background: #e8f5e8; padding: 20px; border: 1px solid #4caf50; margin: 20px 0;'>";
    echo "<h3>✅ Nueva Arquitectura Implementada</h3>";
    echo "<h4>📋 Tablas del Sistema:</h4>";
    echo "<ul>";
    echo "<li><strong>acumulados_por_empleado:</strong> Registro detallado por cada transacción de planilla</li>";
    echo "<li><strong>acumulados_por_planilla:</strong> Consolidado optimizado con campos específicos</li>";
    echo "<li><strong>vista_acumulados_anuales:</strong> Vista para reportes anuales sin queries complejos</li>";
    echo "</ul>";
    
    echo "<h4>🚀 Beneficios:</h4>";
    echo "<ul>";
    echo "<li><strong>Performance:</strong> Reportes sin SELECT complejos a planilla_detalle</li>";
    echo "<li><strong>Auditoría:</strong> Tracking completo por transacción</li>";
    echo "<li><strong>Flexibilidad:</strong> Campos específicos para cada tipo de concepto</li>";
    echo "<li><strong>Legislación:</strong> Separación clara de deducciones legales vs otras</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Error en el test:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; font-size: 13px; }
th, td { padding: 6px 8px; text-align: left; border: 1px solid #ddd; }
th { background: #f9f9f9; }
a { text-decoration: none; }
a:hover { opacity: 0.8; }
h3 { color: #333; margin-top: 25px; }
h4 { color: #555; margin-top: 20px; }
</style>