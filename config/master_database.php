<?php

return [
    // Driver por defecto para Master Database - puede cambiar a 'pgsql' para migrar a PostgreSQL
    'default' => $_ENV['MASTER_DB_DRIVER'] ?? $_ENV['DB_CONNECTION'] ?? 'mysql',

    'connections' => [
        // Conexión MySQL (actual)
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['MASTER_DB_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['MASTER_DB_PORT'] ?? 3306),
            'database' => $_ENV['MASTER_DB_NAME'] ?? 'planilla_master',
            'username' => $_ENV['MASTER_DB_USER'] ?? 'root',
            'password' => $_ENV['MASTER_DB_PASS'] ?? '',
            'charset' => $_ENV['MASTER_DB_CHARSET'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],

        // Conexión PostgreSQL (nuevo)
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => $_ENV['MASTER_DB_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['MASTER_DB_PORT'] ?? 5432),
            'database' => $_ENV['MASTER_DB_NAME'] ?? 'planilla_master',
            'username' => $_ENV['MASTER_DB_USER'] ?? 'postgres',
            'password' => $_ENV['MASTER_DB_PASS'] ?? '',
            'charset' => $_ENV['MASTER_DB_CHARSET'] ?? 'utf8',
            'schema' => $_ENV['MASTER_DB_SCHEMA'] ?? 'public',
            'sslmode' => $_ENV['MASTER_DB_SSLMODE'] ?? 'prefer',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
];

