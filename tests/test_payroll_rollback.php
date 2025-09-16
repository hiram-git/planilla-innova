<?php
/**
 * Test payroll rollback functionality (reopening closed payrolls)
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
echo "<h1>🧪 Test Payroll Rollback Functionality</h1>";

$testPlanillaId = 35; // Esta planilla debería estar CERRADA con acumulados

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>📋 Step 1: Check Initial State (Should be CERRADA)</h2>";
    
    // Check planilla status
    $planillaInfo = $db->prepare("SELECT id, descripcion, estado, fecha_cierre, usuario_cierre, acumulados_generados FROM planilla_cabecera WHERE id = ?");
    $planillaInfo->execute([$testPlanillaId]);
    $planilla = $planillaInfo->fetch();
    
    if (!$planilla) {
        echo "<p>❌ Planilla $testPlanillaId not found</p>";
        exit;
    }
    
    echo "<h3>Current Planilla State:</h3>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> {$planilla['id']}</li>";
    echo "<li><strong>Description:</strong> {$planilla['descripcion']}</li>";
    echo "<li><strong>Status:</strong> <strong>{$planilla['estado']}</strong></li>";
    echo "<li><strong>Closure Date:</strong> " . ($planilla['fecha_cierre'] ?? 'NULL') . "</li>";
    echo "<li><strong>Closed By:</strong> " . ($planilla['usuario_cierre'] ?? 'NULL') . "</li>";
    echo "<li><strong>Acumulados Generated:</strong> " . ($planilla['acumulados_generados'] ? '✅ Yes' : '❌ No') . "</li>";
    echo "</ul>";
    
    // Check existing acumulados
    $detailedCount = $db->prepare("SELECT COUNT(*) FROM acumulados_por_empleado WHERE planilla_id = ?");
    $detailedCount->execute([$testPlanillaId]);
    $detailedRecords = $detailedCount->fetchColumn();
    
    $consolidatedCount = $db->prepare("SELECT COUNT(*) FROM acumulados_por_planilla WHERE planilla_id = ?");
    $consolidatedCount->execute([$testPlanillaId]);
    $consolidatedRecords = $consolidatedCount->fetchColumn();
    
    echo "<p><strong>Current acumulados records:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Detailed (acumulados_por_empleado):</strong> $detailedRecords</li>";
    echo "<li><strong>Consolidated (acumulados_por_planilla):</strong> $consolidatedRecords</li>";
    echo "</ul>";
    
    if ($planilla['estado'] !== 'CERRADA') {
        echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107;'>";
        echo "<strong>⚠️ WARNING:</strong> Planilla is not in CERRADA status. Cannot test rollback.";
        echo "<p>Current status: <strong>{$planilla['estado']}</strong></p>";
        echo "</div>";
        exit;
    }
    
    if ($detailedRecords == 0 && $consolidatedRecords == 0) {
        echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107;'>";
        echo "<strong>⚠️ WARNING:</strong> No acumulados records found. Nothing to rollback.";
        echo "</div>";
        exit;
    }
    
    echo "<h2>🔄 Step 2: Testing Rollback Functionality</h2>";
    
    // Create a reflection to access the private rollbackAccumulatedData method
    require_once 'app/Controllers/PayrollController.php';
    
    $controller = new \App\Controllers\PayrollController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('rollbackAccumulatedData');
    $method->setAccessible(true);
    
    echo "<p>Calling <strong>PayrollController->rollbackAccumulatedData($testPlanillaId)</strong>...</p>";
    
    // Test the rollback method directly
    $affectedRecords = $method->invoke($controller, $testPlanillaId);
    
    echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 15px 0;'>";
    echo "<h3>✅ SUCCESS - Rollback Completed!</h3>";
    echo "<p><strong>Records affected:</strong> $affectedRecords</p>";
    echo "</div>";
    
    echo "<h2>🔍 Step 3: Verifying Rollback Results</h2>";
    
    // Check updated planilla status
    $planillaInfo->execute([$testPlanillaId]);
    $updatedPlanilla = $planillaInfo->fetch();
    
    echo "<h3>📊 Final Planilla State:</h3>";
    echo "<ul>";
    echo "<li><strong>Status:</strong> <strong>{$updatedPlanilla['estado']}</strong> (should still be CERRADA - rollback only affects acumulados)</li>";
    echo "<li><strong>Closure Date:</strong> " . ($updatedPlanilla['fecha_cierre'] ?? 'NULL') . " (should be NULL after rollback)</li>";
    echo "<li><strong>Closed By:</strong> " . ($updatedPlanilla['usuario_cierre'] ?? 'NULL') . " (should be NULL after rollback)</li>";
    echo "<li><strong>Acumulados Generated:</strong> " . ($updatedPlanilla['acumulados_generados'] ? '✅ Yes' : '❌ No') . " (should be No after rollback)</li>";
    echo "</ul>";
    
    // Check remaining acumulados
    $newDetailedCount = $db->prepare("SELECT COUNT(*) FROM acumulados_por_empleado WHERE planilla_id = ?");
    $newDetailedCount->execute([$testPlanillaId]);
    $newDetailedRecords = $newDetailedCount->fetchColumn();
    
    $newConsolidatedCount = $db->prepare("SELECT COUNT(*) FROM acumulados_por_planilla WHERE planilla_id = ?");
    $newConsolidatedCount->execute([$testPlanillaId]);
    $newConsolidatedRecords = $newConsolidatedCount->fetchColumn();
    
    echo "<h3>📈 Acumulados After Rollback:</h3>";
    echo "<ul>";
    echo "<li><strong>Detailed Records (acumulados_por_empleado):</strong> $newDetailedRecords (should be 0)</li>";
    echo "<li><strong>Consolidated Records (acumulados_por_planilla):</strong> $newConsolidatedRecords (should be 0)</li>";
    echo "</ul>";
    
    // Verify success
    $rollbackSuccess = (
        $updatedPlanilla['fecha_cierre'] === null &&
        $updatedPlanilla['usuario_cierre'] === null &&
        $updatedPlanilla['acumulados_generados'] == 0 &&
        $newDetailedRecords == 0 &&
        $newConsolidatedRecords == 0
    );
    
    if ($rollbackSuccess) {
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50;'>";
        echo "<h3>🎉 PERFECT! Rollback Complete Success</h3>";
        echo "<ul>";
        echo "<li>✅ Closure date reset to NULL</li>";
        echo "<li>✅ Closure user reset to NULL</li>";
        echo "<li>✅ Acumulados flag reset to 0</li>";
        echo "<li>✅ All detailed acumulados records removed</li>";
        echo "<li>✅ All consolidated acumulados records removed</li>";
        echo "</ul>";
        echo "<p><strong>The rollback functionality is working perfectly!</strong></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107;'>";
        echo "<strong>⚠️ WARNING:</strong> Some aspects of rollback may not have completed successfully.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border: 1px solid #ff0000; margin: 15px 0;'>";
    echo "<h3>❌ ROLLBACK FAILED</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . " (Line: " . $e->getLine() . ")</p>";
    echo "</div>";
    
    echo "<details>";
    echo "<summary>🔍 Click to see full error details</summary>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</details>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
details { margin: 10px 0; }
</style>