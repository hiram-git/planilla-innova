<?php

return [
    // Driver de base de datos: 'mysql' o 'pgsql'
    'driver' => $_ENV['MASTER_DB_DRIVER'] ?? $_ENV['DB_CONNECTION'] ?? 'mysql',

    'host' => $_ENV['MASTER_DB_HOST'] ?? 'localhost',
    'port' => (int)($_ENV['MASTER_DB_PORT'] ?? ($_ENV['MASTER_DB_DRIVER'] === 'pgsql' ? 5432 : 3306)),
    'database' => $_ENV['MASTER_DB_NAME'] ?? 'planilla_master',
    'username' => $_ENV['MASTER_DB_USER'] ?? ($_ENV['MASTER_DB_DRIVER'] === 'pgsql' ? 'postgres' : 'root'),
    'password' => $_ENV['MASTER_DB_PASS'] ?? '',
    'charset'  => $_ENV['MASTER_DB_CHARSET'] ?? ($_ENV['MASTER_DB_DRIVER'] === 'pgsql' ? 'utf8' : 'utf8mb4'),

    // PostgreSQL specific
    'schema' => $_ENV['MASTER_DB_SCHEMA'] ?? 'public',
    'sslmode' => $_ENV['MASTER_DB_SSLMODE'] ?? 'prefer',

    'options'  => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

