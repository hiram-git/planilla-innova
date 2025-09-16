<?php
// Check concept table name
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Concept;

try {
    echo "Checking concept table structure...\n";
    
    $conceptModel = new Concept();
    echo "Table name: " . $conceptModel->table . "\n";
    
    // Try to get table structure
    $sql = "SHOW TABLES LIKE '%concepto%'";
    $stmt = $conceptModel->db->prepare($sql);
    $stmt->execute();
    $tables = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    echo "Tables containing 'concepto':\n";
    foreach ($tables as $table) {
        echo "- " . implode(', ', $table) . "\n";
    }
    
    // Check if the model table exists
    $sql = "SHOW TABLES LIKE '{$conceptModel->table}'";
    $stmt = $conceptModel->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "\nTable '{$conceptModel->table}' exists!\n";
        
        // Get sample count
        $sql = "SELECT COUNT(*) as total FROM {$conceptModel->table}";
        $stmt = $conceptModel->db->prepare($sql);
        $stmt->execute();
        $count = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
        echo "Total records: $count\n";
        
    } else {
        echo "\nTable '{$conceptModel->table}' does not exist!\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>