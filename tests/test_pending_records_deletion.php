<?php
/**
 * Prueba para verificar eliminación de registros al cambiar a PENDIENTE
 * Ejecutar desde navegador: /planilla-claude-v2/test_pending_records_deletion.php
 */

require_once 'app/Core/Database.php';
use App\Core\Database;

// Configurar headers y errores
header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

echo "<h1>🔍 Prueba: Eliminación de Registros al Cambiar a PENDIENTE</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar dependencias del sistema de acumulados Y planilla_detalle
    $needsFix = false;
    $fixMessage = "";
    $fixLink = "";
    
    // 1. Verificar tipos_acumulados
    $tiposAcumuladosExists = $db->query("SHOW TABLES LIKE 'tipos_acumulados'")->fetch();
    if (!$tiposAcumuladosExists) {
        $needsFix = true;
        $fixMessage = "La tabla 'tipos_acumulados' NO EXISTE y es requerida para el sistema de acumulados.";
        $fixLink = "create_tipos_acumulados_table.php";
    } else {
        // 2. Verificar acumulados_por_planilla
        $tableExists = $db->query("SHOW TABLES LIKE 'acumulados_por_planilla'")->fetch();
        if ($tableExists) {
            $hasCorrectColumn = $db->query("SHOW COLUMNS FROM acumulados_por_planilla LIKE 'planilla_id'")->fetch();
            if (!$hasCorrectColumn) {
                $needsFix = true;
                $fixMessage = "La tabla 'acumulados_por_planilla' existe pero no tiene la columna 'planilla_id' requerida.";
                $fixLink = "create_simple_acumulados_table.php";
            }
        } else {
            $needsFix = true;
            $fixMessage = "La tabla 'acumulados_por_planilla' no existe.";
            $fixLink = "create_simple_acumulados_table.php";
        }
        
        // 3. Verificar planilla_detalle (CRÍTICO)
        if (!$needsFix) {
            $detalleExists = $db->query("SHOW TABLES LIKE 'planilla_detalle'")->fetch();
            if ($detalleExists) {
                $hasDetallePlanillaId = $db->query("SHOW COLUMNS FROM planilla_detalle LIKE 'planilla_id'")->fetch();
                if (!$hasDetallePlanillaId) {
                    $needsFix = true;
                    $fixMessage = "La tabla 'planilla_detalle' NO tiene la columna 'planilla_id' requerida para eliminar registros.";
                    $fixLink = "debug_planilla_detalle_structure.php";
                }
            } else {
                $needsFix = true;
                $fixMessage = "La tabla 'planilla_detalle' no existe (tabla crítica del sistema).";
                $fixLink = "debug_planilla_detalle_structure.php";
            }
        }
    }

    if ($needsFix) {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>⚠️ CORRECCIÓN NECESARIA</h3>";
        echo "<p><strong>$fixMessage</strong></p>";
        echo "<h4>🔧 Opciones de solución:</h4>";
        echo "<p>";
        echo "<a href='$fixLink' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px;'>🔧 Solución Automática</a>";
        echo "<a href='verify_acumulados_structure.php' target='_blank' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px;'>🔍 Verificar Estado</a>";
        echo "<a href='force_fix_acumulados_table.php' target='_blank' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>🚨 Solución de Emergencia</a>";
        echo "</p>";
        echo "<p><em>Si el problema persiste después de usar la solución automática, usa la <strong>Solución de Emergencia</strong>.</em></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>✅ SISTEMA LISTO</h3>";
        echo "<p><strong>Todas las tablas necesarias están correctamente configuradas:</strong></p>";
        echo "<ul>";
        echo "<li>✅ tipos_acumulados - Existe y configurada</li>";
        echo "<li>✅ acumulados_por_planilla - Existe con estructura correcta</li>";
        echo "<li>✅ planilla_detalle - Tiene columna planilla_id requerida</li>";
        echo "</ul>";
        echo "<p>La funcionalidad de cambio a PENDIENTE funcionará correctamente.</p>";
        echo "</div>";
    }
    
    echo "<h2>📋 Estado Actual de Planillas</h2>";
    
    // Mostrar planillas existentes
    $stmt = $db->prepare("
        SELECT 
            id, 
            descripcion, 
            estado, 
            fecha,
            (SELECT COUNT(*) FROM planilla_detalle WHERE planilla_id = pc.id) as total_detalles,
            (SELECT COUNT(*) FROM acumulados_por_planilla WHERE planilla_id = pc.id) as total_acumulados
        FROM planilla_cabecera pc 
        WHERE estado IN ('PROCESADA', 'CERRADA')
        ORDER BY id DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $planillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($planillas)) {
        echo "<p>❌ No hay planillas PROCESADAS o CERRADAS para probar.</p>";
        exit;
    }
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>
            <th>ID</th>
            <th>Descripción</th>
            <th>Estado</th>
            <th>Fecha</th>
            <th>Detalles</th>
            <th>Acumulados</th>
            <th>Acción</th>
          </tr>";
    
    foreach ($planillas as $planilla) {
        echo "<tr>";
        echo "<td>{$planilla['id']}</td>";
        echo "<td>{$planilla['descripcion']}</td>";
        echo "<td><strong>{$planilla['estado']}</strong></td>";
        echo "<td>{$planilla['fecha']}</td>";
        echo "<td style='text-align: center;'>{$planilla['total_detalles']}</td>";
        echo "<td style='text-align: center;'>{$planilla['total_acumulados']}</td>";
        
        if ($planilla['estado'] === 'PROCESADA') {
            echo "<td><button onclick=\"testDeleteRecords({$planilla['id']})\">🧪 Probar Eliminación</button></td>";
        } else {
            echo "<td>-</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    if (isset($_GET['test_planilla_id'])) {
        $planillaId = intval($_GET['test_planilla_id']);
        
        echo "<h2>🧪 Simulación de Eliminación para Planilla ID: $planillaId</h2>";
        
        // Mostrar registros antes de la eliminación (simulación)
        echo "<h3>📊 Registros ANTES de cambiar a PENDIENTE:</h3>";
        
        // Contar detalles
        $detallesStmt = $db->prepare("SELECT COUNT(*) as total FROM planilla_detalle WHERE planilla_id = ?");
        $detallesStmt->execute([$planillaId]);
        $detallesCount = $detallesStmt->fetchColumn();
        
        // Contar acumulados
        $acumuladosStmt = $db->prepare("SELECT COUNT(*) as total FROM acumulados_por_planilla WHERE planilla_id = ?");
        $acumuladosStmt->execute([$planillaId]);
        $acumuladosCount = $acumuladosStmt->fetchColumn();
        
        echo "<ul>";
        echo "<li><strong>Detalles de planilla:</strong> $detallesCount registros</li>";
        echo "<li><strong>Acumulados por planilla:</strong> $acumuladosCount registros</li>";
        echo "</ul>";
        
        if ($detallesCount > 0 || $acumuladosCount > 0) {
            echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4CAF50; margin: 10px 0;'>";
            echo "<strong>✅ PLANILLA VÁLIDA PARA PRUEBA</strong><br>";
            echo "Esta planilla tiene registros que serían eliminados al cambiar a PENDIENTE.";
            echo "</div>";
            
            echo "<p><strong>⚠️ IMPORTANTE:</strong> Para probar realmente la funcionalidad:</p>";
            echo "<ol>";
            echo "<li>Ve a la lista de planillas: <a href='/panel/payrolls' target='_blank'>Panel de Planillas</a></li>";
            echo "<li>Busca la planilla ID $planillaId</li>";
            echo "<li>Haz clic en 'Marcar como Pendiente'</li>";
            echo "<li>Los $detallesCount detalles y $acumuladosCount acumulados serán eliminados automáticamente</li>";
            echo "</ol>";
        } else {
            echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; margin: 10px 0;'>";
            echo "<strong>⚠️ PLANILLA SIN REGISTROS</strong><br>";
            echo "Esta planilla no tiene registros asociados para eliminar.";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<script>
function testDeleteRecords(planillaId) {
    if (confirm('¿Mostrar detalles de simulación para planilla ' + planillaId + '?')) {
        window.location.href = '?test_planilla_id=' + planillaId;
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 20px 0; }
th, td { padding: 8px 12px; text-align: left; }
button { padding: 5px 10px; background: #007cba; color: white; border: none; cursor: pointer; border-radius: 3px; }
button:hover { background: #005a87; }
</style>

<hr>
<h2>📝 Documentación del Cambio</h2>
<p><strong>Funcionalidad implementada:</strong> Al cambiar una planilla de estado PROCESADA a PENDIENTE, se eliminan automáticamente:</p>
<ul>
<li>❌ <strong>Todos los detalles de planilla</strong> (tabla: planilla_detalle)</li>
<li>❌ <strong>Todos los acumulados por planilla</strong> (tabla: acumulados_por_planilla)</li>
<li>❌ <strong>Registros consolidados opcionales</strong> (tabla: planillas_acumulados_consolidados)</li>
</ul>
<p><strong>Motivo:</strong> La planilla queda completamente limpia para ser reprocesada desde cero.</p>
<p><strong>Métodos modificados:</strong></p>
<ul>
<li><code>PayrollController::toPending()</code></li>
<li><code>PayrollController::markPending()</code></li>
<li><code>PayrollController::deleteAllPayrollRecords()</code> (nuevo método privado)</li>
</ul>