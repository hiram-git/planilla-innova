<?php
// Verify creation results
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Creditor;
use App\Models\Concept;

try {
    echo "=== VERIFYING CREDITOR AND CONCEPT CREATION ===\n\n";
    
    $creditorModel = new Creditor();
    $conceptModel = new Concept();
    
    // Get latest creditor
    $sql = "SELECT * FROM creditors ORDER BY id DESC LIMIT 1";
    $stmt = $creditorModel->db->prepare($sql);
    $stmt->execute();
    $latestCreditor = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if ($latestCreditor) {
        echo "Latest creditor:\n";
        echo "- ID: {$latestCreditor['id']}\n";
        echo "- Description: {$latestCreditor['description']}\n";
        echo "- Type: " . ($latestCreditor['tipo'] ?? 'N/A') . "\n";
        echo "- Active: " . ($latestCreditor['activo'] ?? 'N/A') . "\n\n";
        
        // Check if it's our test creditor
        if (strpos($latestCreditor['description'], 'BANCO INDUSTRIAL TEST') !== false) {
            echo "✅ Test creditor found!\n\n";
            
            // Get latest concept
            $sql = "SELECT * FROM concepto ORDER BY id DESC LIMIT 1";
            $stmt = $conceptModel->db->prepare($sql);
            $stmt->execute();
            $latestConcept = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($latestConcept) {
                echo "Latest concept:\n";
                echo "- ID: {$latestConcept['id']}\n";
                echo "- Code: {$latestConcept['codigo']}\n";
                echo "- Description: {$latestConcept['descripcion']}\n";
                echo "- Type: {$latestConcept['tipo']}\n";
                echo "- Formula: {$latestConcept['formula']}\n";
                echo "- Active: {$latestConcept['activo']}\n\n";
                
                // Verify the formula
                $expectedFormula = "ACREEDOR(EMPLEADO, {$latestCreditor['id']})";
                if ($latestConcept['formula'] === $expectedFormula) {
                    echo "✅ AUTOMATIC CREATION SUCCESSFUL!\n";
                    echo "   Formula matches: {$latestConcept['formula']}\n";
                    echo "   Concept type is: {$latestConcept['tipo']}\n";
                } else {
                    echo "⚠️  CONCEPT CREATED BUT FORMULA MIGHT BE DIFFERENT\n";
                    echo "   Expected: $expectedFormula\n";
                    echo "   Got: {$latestConcept['formula']}\n";
                }
                
                // Check if concept description relates to creditor
                if (strpos($latestConcept['descripcion'], 'BANCO INDUSTRIAL') !== false) {
                    echo "✅ Concept description relates to creditor\n";
                }
                
            } else {
                echo "❌ No concept found\n";
            }
            
        } else {
            echo "ℹ️  Latest creditor is not our test creditor\n";
        }
    } else {
        echo "❌ No creditor found\n";
    }
    
    // Check total counts
    $sql = "SELECT COUNT(*) as total FROM creditors";
    $stmt = $creditorModel->db->prepare($sql);
    $stmt->execute();
    $totalCreditors = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
    
    $sql = "SELECT COUNT(*) as total FROM concepto";
    $stmt = $conceptModel->db->prepare($sql);
    $stmt->execute();
    $totalConcepts = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
    
    echo "\nCurrent totals:\n";
    echo "- Creditors: $totalCreditors\n";
    echo "- Concepts: $totalConcepts\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>