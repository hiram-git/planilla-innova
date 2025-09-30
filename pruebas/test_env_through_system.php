<?php
/**
 * Test .env loading through the complete system (with Bootstrap)
 */

// Cargar autoloader
require_once 'vendor/autoload.php';

// Inicializar Bootstrap manualmente
use App\Core\Bootstrap;
use App\Core\Config;
use App\Core\Database;

Bootstrap::init();

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔍 System .env Test (Through Bootstrap)</h1>";

echo "<h2>Step 1: Check $_ENV after Bootstrap</h2>";
echo "<ul>";
echo "<li><strong>DB_HOST:</strong> " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "</li>";
echo "<li><strong>DB_DATABASE:</strong> " . ($_ENV['DB_DATABASE'] ?? 'NOT SET') . "</li>";
echo "<li><strong>DB_USERNAME:</strong> " . ($_ENV['DB_USERNAME'] ?? 'NOT SET') . "</li>";
echo "<li><strong>DB_PASSWORD:</strong> " . (isset($_ENV['DB_PASSWORD']) ? '[SET]' : 'NOT SET') . "</li>";
echo "</ul>";

echo "<h2>Step 2: Check Config class values</h2>";
$dbConfig = Config::get('database.connections.mysql');
echo "<ul>";
echo "<li><strong>Config DB_HOST:</strong> " . $dbConfig['host'] . "</li>";
echo "<li><strong>Config DB_DATABASE:</strong> " . $dbConfig['database'] . "</li>";
echo "<li><strong>Config DB_USERNAME:</strong> " . $dbConfig['username'] . "</li>";
echo "</ul>";

echo "<h2>Step 3: Test Database Connection</h2>";
try {
    $db = Database::getInstance()->getConnection();
    
    // Get current database name
    $result = $db->query("SELECT DATABASE() as current_db")->fetch();
    echo "<h3>✅ Connected to database: <strong>{$result['current_db']}</strong></h3>";
    
    // Check if it's the expected database from .env
    $expectedDb = $_ENV['DB_DATABASE'] ?? 'Unknown';
    if ($result['current_db'] === $expectedDb) {
        echo "<p>✅ Database matches .env configuration!</p>";
    } else {
        echo "<p>❌ Database mismatch!</p>";
        echo "<p>Expected from .env: <strong>$expectedDb</strong></p>";
        echo "<p>Actually connected to: <strong>{$result['current_db']}</strong></p>";
    }
    
    // Check acumulados tables in current database
    $tables = $db->query("SHOW TABLES LIKE '%acumulados%'")->fetchAll();
    
    if (!empty($tables)) {
        echo "<p>✅ Acumulados tables found in current database:</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "<li>$tableName</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ No acumulados tables in current database</p>";
        echo "<p>Need to create acumulados tables in <strong>{$result['current_db']}</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff0000;'>";
    echo "<strong>❌ Database Connection Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
</style>