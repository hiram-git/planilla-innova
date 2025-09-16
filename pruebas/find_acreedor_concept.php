<?php
// Find concept with ACREEDOR formula
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Concept;

try {
    echo "=== SEARCHING FOR ACREEDOR CONCEPTS ===\n\n";
    
    $conceptModel = new Concept();
    
    // Search for concepts with ACREEDOR in formula
    $sql = "SELECT * FROM concepto WHERE formula LIKE '%ACREEDOR%' ORDER BY id DESC";
    $stmt = $conceptModel->db->prepare($sql);
    $stmt->execute();
    $acreedorConcepts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    if (count($acreedorConcepts) > 0) {
        echo "Found " . count($acreedorConcepts) . " concept(s) with ACREEDOR formula:\n\n";
        
        foreach ($acreedorConcepts as $concept) {
            echo "Concept ID: {$concept['id']}\n";
            echo "- Code: " . ($concept['codigo'] ?? 'N/A') . "\n";
            echo "- Description: " . ($concept['descripcion'] ?? 'N/A') . "\n";
            echo "- Type: " . ($concept['tipo'] ?? 'N/A') . "\n";
            echo "- Formula: " . ($concept['formula'] ?? 'N/A') . "\n";
            echo "- Active: " . ($concept['activo'] ?? 'N/A') . "\n";
            echo "---\n\n";
        }
        
        // Check specifically for creditor ID 9
        $found = false;
        foreach ($acreedorConcepts as $concept) {
            if (strpos($concept['formula'] ?? '', 'ACREEDOR(EMPLEADO, 9)') !== false) {
                echo "✅ FOUND CONCEPT FOR CREDITOR ID 9!\n";
                echo "   Concept ID: {$concept['id']}\n";
                echo "   Description: {$concept['descripcion']}\n";
                echo "   Formula: {$concept['formula']}\n";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo "❌ No concept found specifically for creditor ID 9\n";
        }
        
    } else {
        echo "❌ No concepts found with ACREEDOR formula\n";
    }
    
    // Also search for concepts with description containing "BANCO INDUSTRIAL"
    echo "\nSearching for concepts with 'BANCO INDUSTRIAL' in description:\n";
    $sql = "SELECT * FROM concepto WHERE descripcion LIKE '%BANCO INDUSTRIAL%'";
    $stmt = $conceptModel->db->prepare($sql);
    $stmt->execute();
    $bankConcepts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    if (count($bankConcepts) > 0) {
        foreach ($bankConcepts as $concept) {
            echo "- ID: {$concept['id']}, Description: {$concept['descripcion']}, Formula: " . ($concept['formula'] ?? 'N/A') . "\n";
        }
    } else {
        echo "- No concepts found with 'BANCO INDUSTRIAL' in description\n";
    }
    
    // Check if there are any errors in the error log
    echo "\nChecking for recent error logs...\n";
    $logFile = 'C:\xampp82\php\logs\php_errors.log';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $recentLines = array_slice($lines, -10); // Last 10 lines
        foreach ($recentLines as $line) {
            if (strpos($line, 'Concept') !== false || strpos($line, 'concept') !== false) {
                echo "Log: " . trim($line) . "\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>