#!/usr/bin/env php
<?php

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Core\Database;

Bootstrap::init();

$db = Database::getInstance()->getConnection();

// Check raw_data
echo "=== Attendance Raw Data ===\n";
$stmt = $db->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN processed = 1 THEN 1 ELSE 0 END) as processed,
        SUM(CASE WHEN processed = 0 THEN 1 ELSE 0 END) as unprocessed
    FROM attendance_raw_data
");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total raw records: {$row['total']}\n";
echo "Processed: {$row['processed']}\n";
echo "Unprocessed: {$row['unprocessed']}\n";

// Check recent raw_data
echo "\n=== Last 3 Raw Data Records ===\n";
$stmt = $db->query("
    SELECT id, api_provider, processed, received_at, LEFT(raw_json, 100) as json_sample
    FROM attendance_raw_data
    ORDER BY id DESC
    LIMIT 3
");
while ($row = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
    print_r($row);
}
