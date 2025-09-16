<?php
/**
 * Test script for planilla closure functionality
 * Verifies the PayrollAccumulationsProcessor transaction fix
 */
require_once 'app/Core/Database.php';
require_once 'app/Core/Model.php';
require_once 'app/Models/Payroll.php';
require_once 'app/Models/PayrollAccumulationsProcessor.php';

use App\Core\Database;
use App\Models\Payroll;
use App\Models\PayrollAccumulationsProcessor;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🧪 Test Planilla Closure - Transaction Fix</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>📋 Step 1: Check Available PENDIENTE Planillas</h2>";
    
    $sql = "SELECT id, descripcion, estado, fecha, acumulados_generados, 
                   (SELECT COUNT(*) FROM planilla_detalle WHERE planilla_cabecera_id = planilla_cabecera.id) as detail_count
            FROM planilla_cabecera 
            WHERE estado = 'PENDIENTE' 
            ORDER BY fecha DESC 
            LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $pendientePlanillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pendientePlanillas)) {
        echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107;'>";
        echo "<strong>⚠️ WARNING:</strong> No PENDIENTE planillas found for testing";
        echo "</div>";
        
        // Check if there are any planillas at all
        $totalCount = $db->query("SELECT COUNT(*) FROM planilla_cabecera")->fetchColumn();
        echo "<p>Total planillas in database: <strong>$totalCount</strong></p>";
        
        if ($totalCount > 0) {
            // Show some recent planillas regardless of status
            $recentSql = "SELECT id, descripcion, estado, fecha, acumulados_generados FROM planilla_cabecera ORDER BY fecha DESC LIMIT 5";
            $recentPlanillas = $db->query($recentSql)->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Recent planillas (any status):</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Description</th><th>Status</th><th>Date</th><th>Acumulados Generated</th></tr>";
            
            foreach ($recentPlanillas as $planilla) {
                echo "<tr>";
                echo "<td>{$planilla['id']}</td>";
                echo "<td>{$planilla['descripcion']}</td>";
                echo "<td><strong>{$planilla['estado']}</strong></td>";
                echo "<td>{$planilla['fecha']}</td>";
                echo "<td>" . ($planilla['acumulados_generados'] ? '✅ Yes' : '❌ No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<p><strong>💡 SUGGESTION:</strong> Create a test planilla or mark an existing one as PENDIENTE to test closure functionality.</p>";
        exit;
    }
    
    echo "<p>Found <strong>" . count($pendientePlanillas) . "</strong> PENDIENTE planillas available for testing:</p>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Description</th><th>Date</th><th>Details</th><th>Acumulados</th><th>Action</th></tr>";
    
    foreach ($pendientePlanillas as $planilla) {
        echo "<tr>";
        echo "<td>{$planilla['id']}</td>";
        echo "<td>{$planilla['descripcion']}</td>";
        echo "<td>{$planilla['fecha']}</td>";
        echo "<td>{$planilla['detail_count']} records</td>";
        echo "<td>" . ($planilla['acumulados_generados'] ? '✅ Generated' : '❌ Not Generated') . "</td>";
        echo "<td><a href='?test_planilla_id={$planilla['id']}'>🧪 Test Closure</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // If test_planilla_id is provided, test closure
    if (isset($_GET['test_planilla_id'])) {
        $testPlanillaId = (int) $_GET['test_planilla_id'];
        
        echo "<h2>🔬 Step 2: Testing Closure for Planilla ID: $testPlanillaId</h2>";
        
        // Verify planilla exists and is PENDIENTE
        $planillaCheck = $db->prepare("SELECT id, descripcion, estado FROM planilla_cabecera WHERE id = ? AND estado = 'PENDIENTE'");
        $planillaCheck->execute([$testPlanillaId]);
        $planilla = $planillaCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$planilla) {
            echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff0000;'>";
            echo "<strong>❌ Error:</strong> Planilla $testPlanillaId not found or not in PENDIENTE status";
            echo "</div>";
            exit;
        }
        
        echo "<p>Testing planilla: <strong>{$planilla['descripcion']}</strong></p>";
        
        try {
            // Initialize models
            $payrollModel = new Payroll();
            
            echo "<h3>🔄 Attempting to close planilla...</h3>";
            
            // Attempt to close the planilla
            $result = $payrollModel->closePayroll($testPlanillaId);
            
            echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 15px 0;'>";
            echo "<h3>✅ SUCCESS - Planilla Closed Successfully!</h3>";
            echo "<p><strong>Transaction fix is working correctly.</strong></p>";
            echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
            echo "</div>";
            
            // Verify the results
            echo "<h3>🔍 Step 3: Verifying Results</h3>";
            
            // Check planilla status
            $statusCheck = $db->prepare("SELECT estado, fecha_cierre, usuario_cierre, acumulados_generados FROM planilla_cabecera WHERE id = ?");
            $statusCheck->execute([$testPlanillaId]);
            $updatedPlanilla = $statusCheck->fetch(PDO::FETCH_ASSOC);
            
            echo "<h4>📊 Planilla Status After Closure:</h4>";
            echo "<ul>";
            echo "<li><strong>Status:</strong> {$updatedPlanilla['estado']}</li>";
            echo "<li><strong>Closure Date:</strong> {$updatedPlanilla['fecha_cierre']}</li>";
            echo "<li><strong>Closed By:</strong> {$updatedPlanilla['usuario_cierre']}</li>";
            echo "<li><strong>Acumulados Generated:</strong> " . ($updatedPlanilla['acumulados_generados'] ? '✅ Yes' : '❌ No') . "</li>";
            echo "</ul>";
            
            // Check acumulados_por_empleado
            $detailedCount = $db->prepare("SELECT COUNT(*) FROM acumulados_por_empleado WHERE planilla_id = ?");
            $detailedCount->execute([$testPlanillaId]);
            $detailedRecords = $detailedCount->fetchColumn();
            
            // Check acumulados_por_planilla
            $consolidatedCount = $db->prepare("SELECT COUNT(*) FROM acumulados_por_planilla WHERE planilla_id = ?");
            $consolidatedCount->execute([$testPlanillaId]);
            $consolidatedRecords = $consolidatedCount->fetchColumn();
            
            echo "<h4>📈 Acumulados Records Created:</h4>";
            echo "<ul>";
            echo "<li><strong>Detailed Records (acumulados_por_empleado):</strong> $detailedRecords</li>";
            echo "<li><strong>Consolidated Records (acumulados_por_planilla):</strong> $consolidatedRecords</li>";
            echo "</ul>";
            
            if ($detailedRecords > 0 && $consolidatedRecords > 0) {
                echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50;'>";
                echo "<strong>✅ PERFECT:</strong> Both acumulados tables were populated successfully!";
                echo "</div>";
            } else {
                echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107;'>";
                echo "<strong>⚠️ WARNING:</strong> Some acumulados tables were not populated. Check if planilla has valid detail records.";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div style='color: red; background: #ffe6e6; padding: 15px; border: 1px solid #ff0000; margin: 15px 0;'>";
            echo "<h3>❌ CLOSURE FAILED</h3>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>This indicates the transaction fix may need additional work.</strong></p>";
            echo "</div>";
            
            // Show the full error trace for debugging
            echo "<details>";
            echo "<summary>🔍 Click to see full error details</summary>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</details>";
        }
    }
    
    echo "<h2>📝 Test Summary</h2>";
    echo "<p>This test verifies that the PayrollAccumulationsProcessor transaction fix is working correctly.</p>";
    echo "<p><strong>Expected Result:</strong> Planilla should close without 'No active transaction' errors and acumulados should be generated.</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Database Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; font-size: 13px; }
th, td { padding: 8px 10px; text-align: left; border: 1px solid #ddd; }
th { background: #f9f9f9; }
a { color: #007cba; text-decoration: none; padding: 5px 10px; background: #f0f8ff; border-radius: 3px; }
a:hover { background: #e0f0ff; text-decoration: underline; }
details { margin: 10px 0; }
pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>