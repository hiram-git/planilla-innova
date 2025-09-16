<?php
/**
 * Test complete payroll reopen workflow
 * 1. Close a payroll (generate acumulados)
 * 2. Reopen the payroll (rollback acumulados)
 * 3. Verify both operations work correctly
 */

// Cambiar al directorio raíz para cargar archivos correctamente
chdir('..');

// Cargar autoloader
require_once 'vendor/autoload.php';

// Inicializar sistema completo
use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\Payroll;

Bootstrap::init();

// Simular sesión de usuario administrador
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Test Admin';

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🧪 Complete Payroll Reopen Workflow Test</h1>";

$testPlanillaId = 35;

try {
    $db = Database::getInstance()->getConnection();
    $payrollModel = new Payroll();
    
    echo "<h2>📋 Step 1: Check Initial State</h2>";
    
    // Check planilla status
    $planillaInfo = $db->prepare("SELECT id, descripcion, estado, fecha_cierre, usuario_cierre, acumulados_generados, fecha_reapertura, usuario_reapertura, motivo_reapertura FROM planilla_cabecera WHERE id = ?");
    $planillaInfo->execute([$testPlanillaId]);
    $planilla = $planillaInfo->fetch();
    
    echo "<p><strong>Initial Status:</strong> {$planilla['estado']}</p>";
    echo "<p><strong>Acumulados Generated:</strong> " . ($planilla['acumulados_generados'] ? 'Yes' : 'No') . "</p>";
    
    // If not CERRADA with acumulados, close it first
    if ($planilla['estado'] !== 'CERRADA' || !$planilla['acumulados_generados']) {
        
        // First set to PROCESADA if needed
        if ($planilla['estado'] !== 'PROCESADA') {
            $db->prepare("UPDATE planilla_cabecera SET estado = 'PROCESADA' WHERE id = ?")->execute([$testPlanillaId]);
            echo "<p>✅ Set status to PROCESADA</p>";
        }
        
        echo "<h3>🔄 Closing planilla to generate acumulados...</h3>";
        
        $result = $payrollModel->closePayroll($testPlanillaId);
        
        if ($result) {
            echo "<p>✅ Planilla closed successfully</p>";
        } else {
            throw new Exception("Failed to close planilla");
        }
    }
    
    echo "<h2>📊 Step 2: Verify Closed State with Acumulados</h2>";
    
    // Re-check planilla status after closing
    $planillaInfo->execute([$testPlanillaId]);
    $closedPlanilla = $planillaInfo->fetch();
    
    echo "<ul>";
    echo "<li><strong>Status:</strong> {$closedPlanilla['estado']}</li>";
    echo "<li><strong>Closure Date:</strong> " . ($closedPlanilla['fecha_cierre'] ?? 'NULL') . "</li>";
    echo "<li><strong>Closed By:</strong> " . ($closedPlanilla['usuario_cierre'] ?? 'NULL') . "</li>";
    echo "<li><strong>Acumulados Generated:</strong> " . ($closedPlanilla['acumulados_generados'] ? '✅ Yes' : '❌ No') . "</li>";
    echo "</ul>";
    
    // Check acumulados records
    $detailedCount = $db->prepare("SELECT COUNT(*) FROM acumulados_por_empleado WHERE planilla_id = ?");
    $detailedCount->execute([$testPlanillaId]);
    $detailedRecords = $detailedCount->fetchColumn();
    
    $consolidatedCount = $db->prepare("SELECT COUNT(*) FROM acumulados_por_planilla WHERE planilla_id = ?");
    $consolidatedCount->execute([$testPlanillaId]);
    $consolidatedRecords = $consolidatedCount->fetchColumn();
    
    echo "<p><strong>Acumulados Records:</strong> $detailedRecords detailed + $consolidatedRecords consolidated</p>";
    
    if ($closedPlanilla['estado'] !== 'CERRADA' || !$closedPlanilla['acumulados_generados'] || $detailedRecords == 0) {
        throw new Exception("Planilla is not properly closed with acumulados");
    }
    
    echo "<h2>🔄 Step 3: Test Complete Reopen Process</h2>";
    
    // Simulate the complete reopen process from PayrollController
    require_once 'app/Controllers/PayrollController.php';
    
    $controller = new \App\Controllers\PayrollController();
    $reflection = new ReflectionClass($controller);
    
    // Access private rollbackAccumulatedData method
    $rollbackMethod = $reflection->getMethod('rollbackAccumulatedData');
    $rollbackMethod->setAccessible(true);
    
    echo "<h3>🗂️ Performing rollback of acumulados...</h3>";
    
    $db->beginTransaction();
    
    try {
        // 1. Perform rollback of acumulados
        $acumuladosAffected = $rollbackMethod->invoke($controller, $testPlanillaId);
        
        echo "<p>✅ Rollback completed: $acumuladosAffected records affected</p>";
        
        // 2. Change planilla status to PROCESADA
        $updateResult = $payrollModel->update($testPlanillaId, [
            'estado' => 'PROCESADA',
            'fecha_reapertura' => date('Y-m-d H:i:s'),
            'usuario_reapertura' => $_SESSION['admin_name'],
            'motivo_reapertura' => 'Test reopening'
        ]);
        
        if (!$updateResult) {
            throw new Exception('Failed to update planilla status');
        }
        
        echo "<p>✅ Planilla status changed to PROCESADA</p>";
        
        $db->commit();
        
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 15px 0;'>";
        echo "<h3>✅ REOPEN SUCCESSFUL!</h3>";
        echo "<p>Planilla has been successfully reopened with rollback of acumulados.</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
    echo "<h2>🔍 Step 4: Verify Final State After Reopen</h2>";
    
    // Check final planilla state
    $planillaInfo->execute([$testPlanillaId]);
    $finalPlanilla = $planillaInfo->fetch();
    
    echo "<h3>📊 Final Planilla State:</h3>";
    echo "<ul>";
    echo "<li><strong>Status:</strong> <strong>{$finalPlanilla['estado']}</strong></li>";
    echo "<li><strong>Closure Date:</strong> " . ($finalPlanilla['fecha_cierre'] ?? 'NULL') . "</li>";
    echo "<li><strong>Closed By:</strong> " . ($finalPlanilla['usuario_cierre'] ?? 'NULL') . "</li>";
    echo "<li><strong>Acumulados Generated:</strong> " . ($finalPlanilla['acumulados_generados'] ? '✅ Yes' : '❌ No') . "</li>";
    echo "<li><strong>Reopen Date:</strong> " . ($finalPlanilla['fecha_reapertura'] ?? 'NULL') . "</li>";
    echo "<li><strong>Reopened By:</strong> " . ($finalPlanilla['usuario_reapertura'] ?? 'NULL') . "</li>";
    echo "<li><strong>Reopen Reason:</strong> " . ($finalPlanilla['motivo_reapertura'] ?? 'NULL') . "</li>";
    echo "</ul>";
    
    // Check final acumulados
    $finalDetailedCount = $db->prepare("SELECT COUNT(*) FROM acumulados_por_empleado WHERE planilla_id = ?");
    $finalDetailedCount->execute([$testPlanillaId]);
    $finalDetailedRecords = $finalDetailedCount->fetchColumn();
    
    $finalConsolidatedCount = $db->prepare("SELECT COUNT(*) FROM acumulados_por_planilla WHERE planilla_id = ?");
    $finalConsolidatedCount->execute([$testPlanillaId]);
    $finalConsolidatedRecords = $finalConsolidatedCount->fetchColumn();
    
    echo "<h3>📈 Final Acumulados State:</h3>";
    echo "<ul>";
    echo "<li><strong>Detailed Records:</strong> $finalDetailedRecords (should be 0)</li>";
    echo "<li><strong>Consolidated Records:</strong> $finalConsolidatedRecords (should be 0)</li>";
    echo "</ul>";
    
    // Verify complete success
    $reopenSuccess = (
        $finalPlanilla['estado'] === 'PROCESADA' &&
        $finalPlanilla['fecha_cierre'] === null &&
        $finalPlanilla['usuario_cierre'] === null &&
        $finalPlanilla['acumulados_generados'] == 0 &&
        $finalPlanilla['fecha_reapertura'] !== null &&
        $finalPlanilla['usuario_reapertura'] !== null &&
        $finalDetailedRecords == 0 &&
        $finalConsolidatedRecords == 0
    );
    
    if ($reopenSuccess) {
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50;'>";
        echo "<h3>🎉 COMPLETE SUCCESS!</h3>";
        echo "<p><strong>The complete payroll reopen workflow is working perfectly:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Planilla status: CERRADA → PROCESADA</li>";
        echo "<li>✅ Closure fields reset to NULL</li>";
        echo "<li>✅ Acumulados flag reset to 0</li>";
        echo "<li>✅ All acumulados records removed</li>";
        echo "<li>✅ Reopen audit trail recorded</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div style='background: #f0f8ff; padding: 10px; border: 1px solid #007cba; margin: 10px 0;'>";
        echo "<p><strong>🎯 PROBLEM SOLVED!</strong></p>";
        echo "<p>The original error with 'empleado_id' column has been fixed. Users can now successfully reopen closed planillas from the web interface.</p>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107;'>";
        echo "<strong>⚠️ WARNING:</strong> Some aspects of the reopen process may not have completed successfully.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border: 1px solid #ff0000; margin: 15px 0;'>";
    echo "<h3>❌ WORKFLOW FAILED</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . " (Line: " . $e->getLine() . ")</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style>