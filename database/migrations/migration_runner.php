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
            // Solo cargar migraciones exitosas (no las fallidas ni rolled_back)
            $stmt = $this->pdo->query("
                SELECT filename
                FROM migrations_history
                WHERE status = 'success' OR status = 'skipped'
            ");
            $this->executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            // Si no existe la columna status (tabla antigua), usar query simple
            try {
                $stmt = $this->pdo->query("SELECT filename FROM migrations_history");
                $this->executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (PDOException $e2) {
                $this->executedMigrations = [];
            }
        }
    }

    /**
     * Obtener el siguiente número de batch
     */
    private function getNextBatch()
    {
        try {
            $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM migrations_history");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['max_batch'] ?? 0) + 1;
        } catch (PDOException $e) {
            // Si no existe la columna batch (tabla antigua), retornar 1
            return 1;
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

        $startTime = microtime(true);
        $status = 'success';
        $errorMessage = null;
        $batch = $this->getNextBatch();
        $migrationType = $this->getMigrationType();
        $executedBy = $this->getExecutedBy();

        try {
            $sql = file_get_contents($migration['path']);
            $checksum = md5($sql);

            if (!$this->dryRun) {
                // Ejecutar SQL
                try {
                    $this->pdo->exec($sql);
                } catch (PDOException $e) {
                    if ($this->shouldIgnoreMigrationError($e)) {
                        echo "   ⚠️  Warning: " . $e->getMessage() . " (skipped)\n";
                        $status = 'skipped';
                        $errorMessage = $e->getMessage();
                    } else {
                        throw $e;
                    }
                }

                $executionTime = (int)((microtime(true) - $startTime) * 1000);

                // Registrar migración con todos los detalles
                $this->recordMigration(
                    $migration['filename'],
                    $migration['version'],
                    $checksum,
                    $batch,
                    $executionTime,
                    $status,
                    $errorMessage,
                    $executedBy,
                    $migrationType
                );
            }

            if ($status === 'success') {
                echo "   ✅ Completada exitosamente";
            } else {
                echo "   ⏭️  Omitida (ya existente)";
            }

            if (!$this->dryRun) {
                $executionTime = (int)((microtime(true) - $startTime) * 1000);
                echo " ({$executionTime}ms)";
            }

            echo "\n\n";

        } catch (PDOException $e) {
            $executionTime = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            echo "   ❌ ERROR: {$errorMessage}\n";

            // Registrar migración fallida (NO detiene el proceso)
            if (!$this->dryRun) {
                try {
                    $this->recordMigration(
                        $migration['filename'],
                        $migration['version'],
                        md5(file_get_contents($migration['path'])),
                        $batch,
                        $executionTime,
                        'failed',
                        $errorMessage,
                        $executedBy,
                        $migrationType
                    );
                } catch (PDOException $recordError) {
                    echo "   ⚠️  No se pudo registrar el error: {$recordError->getMessage()}\n";
                }
            }

            // NO lanzar excepción - continuar con siguiente migración
            echo "   ⏭️  Continuando con siguiente migración...\n\n";
        }
    }

    /**
     * Registrar migración en la tabla migrations_history
     */
    private function recordMigration($filename, $version, $checksum, $batch, $executionTime, $status, $errorMessage, $executedBy, $migrationType)
    {
        try {
            // Intentar con campos nuevos (tabla mejorada)
            $stmt = $this->pdo->prepare("
                INSERT INTO migrations_history
                (filename, version, checksum, batch, execution_time_ms, status, error_message, executed_by, migration_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $filename,
                $version,
                $checksum,
                $batch,
                $executionTime,
                $status,
                $errorMessage,
                $executedBy,
                $migrationType
            ]);
        } catch (PDOException $e) {
            // Fallback: tabla antigua sin campos nuevos
            $stmt = $this->pdo->prepare("
                INSERT INTO migrations_history (filename, version, checksum)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$filename, $version, $checksum]);
        }
    }

    /**
     * Determinar el tipo de migración según el path
     */
    private function getMigrationType()
    {
        if (strpos($this->migrationsPath, '/master') !== false) {
            return 'master';
        } elseif (strpos($this->migrationsPath, '/tenant') !== false) {
            return 'tenant';
        } elseif (strpos($this->migrationsPath, '/seed') !== false) {
            return 'seed';
        }
        return 'default';
    }

    /**
     * Obtener usuario que ejecuta la migración
     */
    private function getExecutedBy()
    {
        // Obtener usuario del sistema operativo
        $user = getenv('USER') ?: getenv('USERNAME') ?: 'unknown';

        // Si está en servidor web, agregar prefijo
        if (php_sapi_name() !== 'cli') {
            $user = 'www-data:' . $user;
        }

        return substr($user, 0, 100);
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
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║            STATUS MIGRACIONES - PLANILLA INNOVA                ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";

        echo "Base de datos: {$this->databaseName} ({$this->connectionLabel})\n";
        echo "Directorio: {$this->migrationsPath}\n\n";

        $migrations = $this->getMigrationFiles();
        $executedDetails = $this->getExecutedMigrationsDetails();

        // Estadísticas
        $total = count($migrations);
        $executed = count($this->executedMigrations);
        $pending = $total - $executed;

        echo "📊 ESTADÍSTICAS:\n";
        echo "   Total: {$total} | Ejecutadas: ✅ {$executed} | Pendientes: ⏳ {$pending}\n\n";

        // Tabla de migraciones
        echo "📋 DETALLE DE MIGRACIONES:\n";
        echo str_repeat("─", 140) . "\n";
        printf("%-50s %-20s %-12s %-10s %-15s %s\n",
            "ARCHIVO",
            "FECHA",
            "STATUS",
            "BATCH",
            "TIEMPO (ms)",
            "EJECUTADO POR"
        );
        echo str_repeat("─", 140) . "\n";

        foreach ($migrations as $migration) {
            $filename = $migration['filename'];

            if (isset($executedDetails[$filename])) {
                $detail = $executedDetails[$filename];
                $statusIcon = $this->getStatusIcon($detail['status']);
                $batch = $detail['batch'] ?? '-';
                $execTime = $detail['execution_time_ms'] ?? '-';
                $executedBy = $detail['executed_by'] ?? '-';

                printf("%-50s %-20s %-12s %-10s %-15s %s\n",
                    substr($filename, 0, 50),
                    $migration['date'],
                    $statusIcon,
                    $batch,
                    $execTime,
                    substr($executedBy, 0, 20)
                );

                // Mostrar error si existe
                if ($detail['status'] === 'failed' && !empty($detail['error_message'])) {
                    echo "   ❌ Error: " . substr($detail['error_message'], 0, 100) . "\n";
                }
            } else {
                printf("%-50s %-20s %-12s %-10s %-15s %s\n",
                    substr($filename, 0, 50),
                    $migration['date'],
                    "⏳ Pendiente",
                    "-",
                    "-",
                    "-"
                );
            }
        }

        echo str_repeat("─", 140) . "\n\n";

        // Mostrar migraciones fallidas si existen
        $this->showFailedMigrations($executedDetails);
    }

    /**
     * Obtener detalles de migraciones ejecutadas
     */
    private function getExecutedMigrationsDetails()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT filename, batch, execution_time_ms, status, error_message, executed_by, executed_at
                FROM migrations_history
                ORDER BY id ASC
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $details = [];
            foreach ($results as $row) {
                $details[$row['filename']] = $row;
            }
            return $details;

        } catch (PDOException $e) {
            // Fallback: tabla antigua sin campos nuevos
            try {
                $stmt = $this->pdo->query("
                    SELECT filename, executed_at
                    FROM migrations_history
                    ORDER BY id ASC
                ");
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $details = [];
                foreach ($results as $row) {
                    $details[$row['filename']] = [
                        'filename' => $row['filename'],
                        'executed_at' => $row['executed_at'],
                        'status' => 'success',
                        'batch' => null,
                        'execution_time_ms' => null,
                        'error_message' => null,
                        'executed_by' => null
                    ];
                }
                return $details;

            } catch (PDOException $e2) {
                return [];
            }
        }
    }

    /**
     * Obtener icono de status
     */
    private function getStatusIcon($status)
    {
        $icons = [
            'success' => '✅ Exitosa',
            'failed' => '❌ Fallida',
            'skipped' => '⏭️  Omitida',
            'rolled_back' => '↩️  Rollback'
        ];
        return $icons[$status] ?? '❓ Desconocido';
    }

    /**
     * Mostrar migraciones fallidas
     */
    private function showFailedMigrations($executedDetails)
    {
        $failed = array_filter($executedDetails, function($detail) {
            return $detail['status'] === 'failed';
        });

        if (!empty($failed)) {
            echo "⚠️  MIGRACIONES FALLIDAS:\n\n";

            foreach ($failed as $filename => $detail) {
                echo "   ❌ {$filename}\n";
                echo "      Fecha: {$detail['executed_at']}\n";
                echo "      Batch: {$detail['batch']}\n";
                if (!empty($detail['error_message'])) {
                    echo "      Error: {$detail['error_message']}\n";
                }
                echo "\n";
            }

            echo "💡 Para re-ejecutar migraciones fallidas, corrige el error y ejecuta nuevamente el runner.\n\n";
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
