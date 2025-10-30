#!/usr/bin/env php
<?php
/**
 * Test punch type mapping from raw data
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Core\Database;

Bootstrap::init();

$db = Database::getInstance()->getConnection();

echo "\n=== Checking punch_type column constraints ===\n";
$stmt = $db->query("SHOW COLUMNS FROM attendance_records LIKE 'punch_type'");
$column = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Column Type: {$column['Type']}\n";
echo "Null: {$column['Null']}\n";
echo "Key: {$column['Key']}\n";
echo "Default: {$column['Default']}\n";
echo "Extra: {$column['Extra']}\n\n";

echo "=== Sample raw_data 'type' values ===\n";
$stmt = $db->query("
    SELECT DISTINCT JSON_EXTRACT(raw_json, '$.type') as type_value
    FROM attendance_raw_data
    LIMIT 10
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $typeValue = trim($row['type_value'], '"');
    $mappedValue = strtoupper($typeValue);
    echo "API Type: '{$typeValue}' → Mapped to: '{$mappedValue}'\n";
}

echo "\n=== Checking if ENUM constraint exists ===\n";
if (preg_match("/^enum\((.*)\)$/i", $column['Type'], $matches)) {
    echo "⚠️  punch_type is an ENUM column!\n";
    echo "Allowed values: {$matches[1]}\n\n";
    echo "This might be causing the problem if API types don't match ENUM values!\n";
} else {
    echo "✓ punch_type is NOT an ENUM, accepts any VARCHAR\n";
}
