<?php

return [
    // Conexión por defecto - puede cambiar a 'pgsql' para migrar a PostgreSQL
    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',

    'connections' => [
        // Conexión MySQL (actual)
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'planilla_prod',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
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
            'host' => $_ENV['PGSQL_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['PGSQL_PORT'] ?? '5432',
            'database' => $_ENV['PGSQL_DATABASE'] ?? $_ENV['DB_DATABASE'] ?? 'planilla_prod',
            'username' => $_ENV['PGSQL_USERNAME'] ?? $_ENV['DB_USERNAME'] ?? 'postgres',
            'password' => $_ENV['PGSQL_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8',
            'schema' => $_ENV['PGSQL_SCHEMA'] ?? 'public',
            'sslmode' => $_ENV['PGSQL_SSLMODE'] ?? 'prefer',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
];
