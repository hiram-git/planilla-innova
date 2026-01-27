<?php

namespace App\Core;

use PDO;
use PDOException;

class MasterDatabase
{
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $masterConfig = require __DIR__ . '/../../config/master_database.php';

        // Obtener el driver por defecto
        $driver = $masterConfig['default'] ?? 'mysql';

        // Obtener la configuración específica del driver
        $config = $masterConfig['connections'][$driver] ?? $masterConfig['connections']['mysql'];

        try {
            // Construir DSN dinámicamente según el driver
            if ($driver === 'pgsql') {
                $dsn = sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s',
                    $config['host'],
                    $config['port'],
                    $config['database']
                );

                // Agregar schema si existe
                if (!empty($config['schema'])) {
                    $dsn .= ';options=--search_path=' . $config['schema'];
                }
            } else {
                // MySQL
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset']
                );
            }

            $this->connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            throw new \RuntimeException("Master DB connection failed ({$driver}): " . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}

