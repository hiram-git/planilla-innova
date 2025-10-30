#!/usr/bin/env php
<?php

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Core\Database;

Bootstrap::init();

$db = Database::getInstance()->getConnection();

// Check API config
echo "=== API Configuration ===\n";
$stmt = $db->query("SELECT id, api_provider, LENGTH(config_json) as json_length, LEFT(config_json, 100) as json_sample FROM attendance_api_config");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

// Check attendance_records count
echo "\n=== Attendance Records Count ===\n";
$stmt = $db->query("SELECT COUNT(*) as total FROM attendance_records");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total records: {$row['total']}\n";

// Check employees with email like API
echo "\n=== Employees Matching API Emails ===\n";
$emails = ['soporte4nms@gmail.com', 'dilsaquintana@gmail.com', 'soporte2@nmspanama.com'];
foreach ($emails as $email) {
    $stmt = $db->prepare("SELECT id, firstname, lastname, email FROM employees WHERE email = ?");
    $stmt->execute([$email]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($emp) {
        echo "✓ {$email} → ID {$emp['id']}: {$emp['firstname']} {$emp['lastname']}\n";
    } else {
        echo "✗ {$email} → NOT FOUND\n";
    }
}
