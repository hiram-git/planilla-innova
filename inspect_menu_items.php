<?php
require_once 'app/Core/TenantResolver.php';
require_once 'app/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->query("DESCRIBE menu_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($columns, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
