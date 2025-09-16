<?php
// Test automatic concept creation when creating creditor
require_once __DIR__ . '/vendor/autoload.php';

use App\Controllers\CreditorController;
use App\Core\Security;
use App\Models\Creditor;
use App\Models\Concept;

try {
    echo "=== TESTING AUTOMATIC CONCEPT CREATION FOR CREDITOR ===\n\n";
    
    // Get initial counts
    $creditorModel = new Creditor();
    $conceptModel = new Concept();
    
    $sql = "SELECT COUNT(*) as total FROM creditors";
    $stmt = $creditorModel->db->prepare($sql);
    $stmt->execute();
    $initialCreditors = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
    
    $sql = "SELECT COUNT(*) as total FROM concepto";
    $stmt = $conceptModel->db->prepare($sql);
    $stmt->execute();
    $initialConcepts = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
    
    echo "1. Initial state:\n";
    echo "   - Creditors: $initialCreditors\n";
    echo "   - Concepts: $initialConcepts\n\n";
    
    // Simulate POST data for creating a creditor
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf_token' => Security::generateToken(),
        'description' => 'BANCO INDUSTRIAL TEST',
        'tipo' => 'FINANCIERO',
        'estado' => '1',
        'observaciones' => 'Acreedor de prueba para test automático'
    ];
    
    echo "2. Creating creditor with data:\n";
    echo "   - Description: {$_POST['description']}\n";
    echo "   - Type: {$_POST['tipo']}\n\n";
    
    // Create creditor using controller
    $controller = new CreditorController();
    
    ob_start();
    try {
        $controller->store();
        $output = ob_get_clean();
        echo "3. Creditor creation executed successfully\n";
    } catch (Exception $e) {
        ob_end_clean();
        echo "3. Creditor creation failed: " . $e->getMessage() . "\n";
        exit;
    }
    
    // Check new counts
    $stmt = $creditorModel->db->prepare("SELECT COUNT(*) as total FROM creditors");
    $stmt->execute();
    $finalCreditors = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conceptModel->db->prepare("SELECT COUNT(*) as total FROM concepto");
    $stmt->execute();
    $finalConcepts = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
    
    echo "4. Final state:\n";
    echo "   - Creditors: $finalCreditors (+" . ($finalCreditors - $initialCreditors) . ")\n";
    echo "   - Concepts: $finalConcepts (+" . ($finalConcepts - $initialConcepts) . ")\n\n";
    
    // Get the latest creditor and concept
    if ($finalCreditors > $initialCreditors) {
        $sql = "SELECT * FROM creditors ORDER BY id DESC LIMIT 1";
        $stmt = $creditorModel->db->prepare($sql);
        $stmt->execute();
        $latestCreditor = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        echo "5. Latest creditor created:\n";
        echo "   - ID: {$latestCreditor['id']}\n";
        echo "   - Description: {$latestCreditor['description']}\n";
        echo "   - Type: {$latestCreditor['tipo']}\n\n";
        
        if ($finalConcepts > $initialConcepts) {
            $sql = "SELECT * FROM concepto ORDER BY id DESC LIMIT 1";
            $stmt = $conceptModel->db->prepare($sql);
            $stmt->execute();
            $latestConcept = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            echo "6. Latest concept created:\n";
            echo "   - ID: {$latestConcept['id']}\n";
            echo "   - Code: {$latestConcept['codigo']}\n";
            echo "   - Description: {$latestConcept['descripcion']}\n";
            echo "   - Type: {$latestConcept['tipo']}\n";
            echo "   - Formula: {$latestConcept['formula']}\n\n";
            
            // Verify the formula contains the creditor ID
            $expectedFormula = "ACREEDOR(EMPLEADO, {$latestCreditor['id']})";
            if ($latestConcept['formula'] === $expectedFormula) {
                echo "✅ AUTOMATIC CONCEPT CREATION SUCCESSFUL!\n";
                echo "   Formula is correct: {$latestConcept['formula']}\n";
            } else {
                echo "❌ FORMULA MISMATCH!\n";
                echo "   Expected: $expectedFormula\n";
                echo "   Got: {$latestConcept['formula']}\n";
            }
            
        } else {
            echo "❌ CONCEPT NOT CREATED\n";
            echo "   Expected concept creation but none found\n";
        }
        
    } else {
        echo "❌ CREDITOR NOT CREATED\n";
        echo "   Expected creditor creation but none found\n";
    }
    
    // Check session messages
    if (isset($_SESSION['success'])) {
        echo "\n7. Success message: {$_SESSION['success']}\n";
    }
    if (isset($_SESSION['error'])) {
        echo "\n7. Error message: {$_SESSION['error']}\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>