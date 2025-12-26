<?php
/**
 * Sistema de Migración de Base de Datos - Planilla Innova
 *
 * Ejecuta migraciones en orden cronológico usando nomenclatura:
 * YYYY_MM_DD_HHII_nombre_descriptivo.sql
 *
 * Uso: php migration_runner.php [--dry-run] [--version=3.3.3]
 */

class MigrationRunner
{
    private $pdo;
    private $dryRun = false;
    private $migrationsPath;
    private $executedMigrations = [];
    private $databaseName;
    private $connectionLabel = 'default';

    public function __construct($dryRun = false)
    {
        $this->dryRun = $dryRun;
        $this->migrationsPath = __DIR__;
        $this->connectDatabase();
        $this->createMigrationsTable();
        $this->loadExecutedMigrations();
    }

    private function connectDatabase()
    {
        $masterConfigPath = __DIR__ . '/../../config/master_database.php';
        if (file_exists($masterConfigPath)) {
            $masterConfig = require $masterConfigPath;
            $pdo = $this->createPdo($masterConfig);
            if ($pdo) {
                $this->pdo = $pdo;
                $this->databaseName = $masterConfig['database'] ?? 'tenant_master';
                $this->connectionLabel = 'tenant_master';
                $this->migrationsPath = __DIR__ . '/master';
                return;
            }
        }

        try {
            $config = require __DIR__ . '/../../config/database.php';
            $connection = $config['connections']['mysql'] ?? [];
            $dbConfig = [
                'host' => $connection['host'] ?? 'localhost',
                'port' => (int)($connection['port'] ?? 3306),
                'database' => $connection['database'] ?? ($_ENV['DB_DATABASE'] ?? ($_ENV['DB_NAME'] ?? 'planilla_prod')),
                'username' => $connection['username'] ?? 'root',
                'password' => $connection['password'] ?? '',
                'charset' => $connection['charset'] ?? 'utf8mb4',
                'options' => $connection['options'] ?? [],
            ];

            $this->pdo = $this->createPdo($dbConfig);
            if (!$this->pdo) {
                throw new PDOException('Unable to connect using app database config.');
            }

            $this->databaseName = $dbConfig['database'] ?? 'planilla_prod';
            if ($this->databaseName === 'planilla_prod') {
                $this->connectionLabel = 'planilla_prod';
                $this->migrationsPath = __DIR__;
            } else {
                $this->migrationsPath = __DIR__ . '/tenant';
            }
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error conectando BD: " . $e->getMessage() . "\n");
        }
    }

    private function createPdo(array $config)
    {
        try {
            $host = $config['host'] ?? 'localhost';
            $port = (int)($config['port'] ?? 3306);
            $database = $config['database'] ?? 'planilla_prod';
            $charset = $config['charset'] ?? 'utf8mb4';
            $username = $config['username'] ?? 'root';
            $password = $config['password'] ?? '';
            $options = $config['options'] ?? [];

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            return null;
        }
    }

    private function createMigrationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            version VARCHAR(20) NULL,
            checksum VARCHAR(32) NULL
        ) ENGINE=InnoDB";

        if (!$this->dryRun) {
            $this->pdo->exec($sql);
        }
        echo "✅ Tabla migrations_history verificada\n";
    }

    private function loadExecutedMigrations()
    {
        try {
            $stmt = $this->pdo->query("SELECT filename FROM migrations_history");
            $this->executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->executedMigrations = [];
        }
    }

    public function run($targetVersion = null)
    {
        echo "=== SISTEMA MIGRACIÓN PLANILLA INNOVA ===\n";
        echo "Modo: " . ($this->dryRun ? "DRY RUN (no ejecuta)" : "EJECUCIÓN REAL") . "\n";
        if ($this->databaseName) {
            echo "Base de datos: {$this->databaseName} ({$this->connectionLabel})\n";
        }
        echo "Directorio: {$this->migrationsPath}\n\n";

        $migrations = $this->getMigrationFiles();
        $pendingMigrations = $this->filterPendingMigrations($migrations, $targetVersion);

        echo "📋 Migraciones encontradas: " . count($migrations) . "\n";
        echo "⏳ Migraciones pendientes: " . count($pendingMigrations) . "\n";
        echo "✅ Migraciones ejecutadas: " . count($this->executedMigrations) . "\n\n";

        if (empty($pendingMigrations)) {
            echo "✅ No hay migraciones pendientes\n";
            return;
        }

        foreach ($pendingMigrations as $migration) {
            $this->executeMigration($migration);
        }

        echo "\n✅ Proceso de migración completado\n";
    }

    private function getMigrationFiles()
    {
        $files = glob($this->migrationsPath . "/*.sql");

        // Ordenar por nombre de archivo (que incluye fecha)
        sort($files);

        return array_map(function($file) {
            return [
                'path' => $file,
                'filename' => basename($file),
                'date' => $this->extractDateFromFilename(basename($file)),
                'version' => $this->extractVersionFromFilename(basename($file))
            ];
        }, $files);
    }

    private function extractDateFromFilename($filename)
    {
        // Buscar patrón YYYY_MM_DD_HHII al inicio del nombre
        if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{4})/', $filename, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' .
                   substr($matches[4], 0, 2) . ':' . substr($matches[4], 2, 2);
        }

        // Fallback: usar fecha de archivo (para migraciones legacy)
        return date('Y-m-d H:i', filemtime($this->migrationsPath . '/' . $filename));
    }

    private function extractVersionFromFilename($filename)
    {
        // Buscar patrón v3.X.X en el nombre
        if (preg_match('/v(\d+\.\d+\.\d+)/', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function filterPendingMigrations($migrations, $targetVersion = null)
    {
        return array_filter($migrations, function($migration) use ($targetVersion) {
            // Skip si ya está ejecutada
            if (in_array($migration['filename'], $this->executedMigrations)) {
                return false;
            }

            // Filtrar por versión si se especifica
            if ($targetVersion && $migration['version'] &&
                version_compare($migration['version'], $targetVersion, '>')) {
                return false;
            }

            return true;
        });
    }

    private function executeMigration($migration)
    {
        echo "🔄 Ejecutando: {$migration['filename']}\n";
        echo "   Fecha: {$migration['date']}\n";

        if ($migration['version']) {
            echo "   Versión: {$migration['version']}\n";
        }

        try {
            $sql = file_get_contents($migration['path']);
            $checksum = md5($sql);

            if (!$this->dryRun) {
                // Ejecutar SQL
                try {
                    $this->pdo->exec($sql);
                } catch (PDOException $e) {
                    if ($this->shouldIgnoreMigrationError($e)) {
                        echo "   ! Warning: " . $e->getMessage() . " (ignored)\n";
                    } else {
                        throw $e;
                    }
                }

                // Registrar migracion
                $stmt = $this->pdo->prepare(
                    "INSERT INTO migrations_history (filename, version, checksum) VALUES (?, ?, ?)"
                );
                $stmt->execute([$migration['filename'], $migration['version'], $checksum]);
            }

            echo "   ✅ Completada exitosamente\n\n";

        } catch (PDOException $e) {
            echo "   ❌ ERROR: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    private function shouldIgnoreMigrationError(PDOException $e): bool
    {
        $errorInfo = $e->errorInfo ?? [];
        $code = $errorInfo[1] ?? null;
        if ($code === 1061) {
            return true;
        }

        return strpos($e->getMessage(), 'Duplicate key name') !== false;
    }

    public function status()
    {
        echo "=== STATUS MIGRACIONES ===\n\n";

        $migrations = $this->getMigrationFiles();

        foreach ($migrations as $migration) {
            $status = in_array($migration['filename'], $this->executedMigrations) ?
                      "✅ Ejecutada" : "⏳ Pendiente";

            echo sprintf("%-50s %s %s\n",
                $migration['filename'],
                $migration['date'],
                $status
            );
        }
    }
}

// Ejecutar si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $options = getopt('', ['dry-run', 'version:', 'status']);

    $dryRun = isset($options['dry-run']);
    $version = $options['version'] ?? null;
    $showStatus = isset($options['status']);

    $runner = new MigrationRunner($dryRun);

    if ($showStatus) {
        $runner->status();
    } else {
        $runner->run($version);
    }
}
?>
