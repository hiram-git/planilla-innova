<?php

return [
    'host' => $_ENV['MASTER_DB_HOST'] ?? 'localhost',
    'port' => (int)($_ENV['MASTER_DB_PORT'] ?? 3306),
    'database' => $_ENV['MASTER_DB_NAME'] ?? 'planilla_master',
    'username' => $_ENV['MASTER_DB_USER'] ?? 'root',
    'password' => $_ENV['MASTER_DB_PASS'] ?? '',
    'charset'  => $_ENV['MASTER_DB_CHARSET'] ?? 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

