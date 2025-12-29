<?php
/**
 * Main Database Migration Runner - Planilla Innova
 *
 * Ejecuta migraciones tenant en la base de datos principal (planilla_prod)
 * usando las credenciales del archivo .env
 *
 * Este script es independiente del sistema multi-tenant y procesa
 * únicamente la base de datos configurada en DB_DATABASE (.env)
 *
 * Uso:
 *   php migrate_main_database.php [--dry-run]
 *
 * Opciones:
 *   --dry-run    No ejecuta, solo muestra qué haría
 *
 * @version 1.0.0
 * @date 2025-12-29
 */

class MainDatabaseMigrationRunner
{
    private $pdo;
    private $dryRun = false;
    private $migrationsPath;
    private $dbConfig = [];

    public function __construct($dryRun = false)
    {
        $this->dryRun = $dryRun;
        $this->migrationsPath = __DIR__ . '/tenant';
        $this->loadEnvironment();
        $this->connect();
    }

    /**
     * Cargar variables de entorno desde .env
     */
    private function loadEnvironment()
    {
        $envFile = __DIR__ . '/../../.env';

        if (!file_exists($envFile)) {
            die("❌ Archivo .env no encontrado en: $envFile\n");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parsear línea KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remover comillas si existen
                $value = trim($value, '"\'');

                // Establecer en $_ENV si no existe
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                }
            }
        }

        // Cargar configuración de base de datos
        $this->dbConfig = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'database' => $_ENV['DB_DATABASE'] ?? 'planilla_prod',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
        ];

        echo "✅ Configuración cargada desde .env:\n";
        echo "   Host: {$this->dbConfig['host']}:{$this->dbConfig['port']}\n";
        echo "   Database: {$this->dbConfig['database']}\n";
        echo "   Username: {$this->dbConfig['username']}\n";
    }

    /**
     * Conectar a la base de datos principal
     */
    private function connect()
    {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $this->dbConfig['host'],
                $this->dbConfig['port'],
                $this->dbConfig['database'],
                $this->dbConfig['charset']
            );

            $this->pdo = new PDO(
                $dsn,
                $this->dbConfig['username'],
                $this->dbConfig['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ]
            );

            echo "✅ Conectado a base de datos: {$this->dbConfig['database']}\n\n";
        } catch (PDOException $e) {
            die("❌ Error conectando a base de datos: " . $e->getMessage() . "\n");
        }
    }

    /**
     * Crear tabla migrations_history si no existe
     */
    private function ensureMigrationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            version VARCHAR(20) NULL,
            checksum VARCHAR(32) NULL,
            batch INT NULL,
            execution_time_ms INT NULL,
            status ENUM('success', 'failed', 'skipped', 'rolled_back') DEFAULT 'success',
            error_message TEXT NULL,
            executed_by VARCHAR(100) NULL,
            migration_type ENUM('master', 'tenant', 'seed', 'default') DEFAULT 'tenant'
        ) ENGINE=InnoDB";

        if (!$this->dryRun) {
            $this->pdo->exec($sql);
            echo "✅ Tabla migrations_history verificada\n";
        }
    }

    /**
     * Obtener migraciones ya ejecutadas
     */
    private function getExecutedMigrations()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT filename
                FROM migrations_history
                WHERE status IN ('success', 'skipped')
            ");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            // Si la tabla no existe
            return [];
        }
    }

    /**
     * Obtener archivos de migración
     */
    private function getMigrationFiles()
    {
        $files = glob($this->migrationsPath . "/*.sql");

        if ($files === false || empty($files)) {
            echo "⚠️  No se encontraron archivos de migración en: {$this->migrationsPath}\n";
            return [];
        }

        sort($files);

        return array_map(function($file) {
            return [
                'path' => $file,
                'filename' => basename($file),
                'checksum' => md5_file($file)
            ];
        }, $files);
    }

    /**
     * Dividir contenido SQL en statements individuales
     */
    private function splitSqlStatements($sql)
    {
        $statements = [];
        $buffer = '';
        $inString = false;
        $stringChar = '';
        $inComment = false;
        $commentType = '';

        $lines = explode("\n", $sql);

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Ignorar líneas vacías
            if (empty($trimmed)) {
                continue;
            }

            // Ignorar comentarios de línea completa
            if (substr($trimmed, 0, 2) === '--' || substr($trimmed, 0, 1) === '#') {
                continue;
            }

            // Procesar línea caracter por caracter
            $len = strlen($line);
            for ($i = 0; $i < $len; $i++) {
                $char = $line[$i];
                $nextChar = ($i + 1 < $len) ? $line[$i + 1] : '';

                // Detectar inicio/fin de string literals
                if (!$inComment && ($char === '"' || $char === "'")) {
                    if (!$inString) {
                        $inString = true;
                        $stringChar = $char;
                    } elseif ($char === $stringChar && ($i === 0 || $line[$i - 1] !== '\\')) {
                        $inString = false;
                        $stringChar = '';
                    }
                }

                // Detectar inicio de comentario
                if (!$inString && !$inComment) {
                    if ($char === '-' && $nextChar === '-') {
                        $inComment = true;
                        $commentType = 'line';
                        $i++;
                        continue;
                    }
                    if ($char === '/' && $nextChar === '*') {
                        $inComment = true;
                        $commentType = 'block';
                        $i++;
                        continue;
                    }
                }

                // Detectar fin de comentario
                if ($inComment) {
                    if ($commentType === 'block' && $char === '*' && $nextChar === '/') {
                        $inComment = false;
                        $commentType = '';
                        $i++;
                        continue;
                    }
                    if ($commentType === 'line') {
                        break;
                    }
                    continue;
                }

                // Detectar fin de statement (;)
                if (!$inString && !$inComment && $char === ';') {
                    $buffer .= $char;
                    $statement = trim($buffer);
                    if (!empty($statement)) {
                        $statements[] = $statement;
                    }
                    $buffer = '';
                    continue;
                }

                $buffer .= $char;
            }

            if (!$inComment) {
                $buffer .= "\n";
            }

            if ($inComment && $commentType === 'line') {
                $inComment = false;
                $commentType = '';
            }
        }

        // Agregar último statement si existe
        $lastStatement = trim($buffer);
        if (!empty($lastStatement) && $lastStatement !== ';') {
            $statements[] = $lastStatement;
        }

        return $statements;
    }

    /**
     * Ejecutar migración
     */
    private function executeMigration($migration)
    {
        $startTime = microtime(true);
        $status = 'success';
        $errorMessage = null;

        try {
            $sql = file_get_contents($migration['path']);

            if (!$this->dryRun) {
                // Dividir SQL en statements individuales
                $statements = $this->splitSqlStatements($sql);

                echo "   Ejecutando {$migration['filename']} (" . count($statements) . " statements)...\n";

                // Ejecutar cada statement por separado
                foreach ($statements as $index => $statement) {
                    $trimmedStatement = trim($statement);
                    if (!empty($trimmedStatement)) {
                        try {
                            $result = $this->pdo->query($trimmedStatement);
                            if ($result) {
                                $result->closeCursor();
                            }
                        } catch (PDOException $e) {
                            throw new PDOException(
                                "Error en statement #" . ($index + 1) . ": " . $e->getMessage(),
                                (int)$e->getCode()
                            );
                        }
                    }
                }
            } else {
                echo "   [DRY-RUN] {$migration['filename']}\n";
            }

            $executionTime = (int)((microtime(true) - $startTime) * 1000);

            // Registrar en migrations_history
            if (!$this->dryRun) {
                $this->recordMigration($migration, $executionTime, $status, $errorMessage);
            }

            echo "      ✅ Completada ({$executionTime}ms)\n";
            return true;

        } catch (PDOException $e) {
            $executionTime = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();
            $status = 'failed';

            if (!$this->dryRun) {
                try {
                    $this->recordMigration($migration, $executionTime, $status, $errorMessage);
                } catch (Exception $recordError) {
                    // Ignorar errores al registrar
                }
            }

            echo "      ❌ ERROR: {$errorMessage}\n";
            return false;
        }
    }

    /**
     * Registrar migración ejecutada
     */
    private function recordMigration($migration, $executionTime, $status, $errorMessage)
    {
        try {
            $batch = $this->getNextBatch();
            $executedBy = getenv('USER') ?: getenv('USERNAME') ?: 'cli-script';

            $stmt = $this->pdo->prepare("
                INSERT INTO migrations_history
                (filename, checksum, batch, execution_time_ms, status, error_message, executed_by, migration_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'tenant')
            ");

            $stmt->execute([
                $migration['filename'],
                $migration['checksum'],
                $batch,
                $executionTime,
                $status,
                $errorMessage,
                substr($executedBy, 0, 100)
            ]);
        } catch (PDOException $e) {
            // Fallback para tabla antigua
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO migrations_history (filename, checksum)
                    VALUES (?, ?)
                ");
                $stmt->execute([$migration['filename'], $migration['checksum']]);
            } catch (PDOException $e2) {
                // Ignorar error de registro
            }
        }
    }

    /**
     * Obtener siguiente número de batch
     */
    private function getNextBatch()
    {
        try {
            $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM migrations_history");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['max_batch'] ?? 0) + 1;
        } catch (PDOException $e) {
            return 1;
        }
    }

    /**
     * Ejecutar migraciones
     */
    public function run()
    {
        echo "\n╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║          MAIN DATABASE MIGRATION RUNNER - PLANILLA INNOVA                ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n\n";

        echo "Modo: " . ($this->dryRun ? "🔍 DRY RUN (no ejecuta)" : "⚡ EJECUCIÓN REAL") . "\n";
        echo "Directorio migraciones: {$this->migrationsPath}\n\n";

        // Asegurar tabla migrations_history
        $this->ensureMigrationsTable();

        // Obtener migraciones ejecutadas y pendientes
        $executedMigrations = $this->getExecutedMigrations();
        $allMigrations = $this->getMigrationFiles();

        $pendingMigrations = array_filter($allMigrations, function($migration) use ($executedMigrations) {
            return !in_array($migration['filename'], $executedMigrations);
        });

        $totalMigrations = count($allMigrations);
        $executedCount = count($executedMigrations);
        $pendingCount = count($pendingMigrations);

        echo "📊 Estado:\n";
        echo "   Total: {$totalMigrations} | Ejecutadas: ✅ {$executedCount} | Pendientes: ⏳ {$pendingCount}\n\n";

        if (empty($pendingMigrations)) {
            echo "✅ No hay migraciones pendientes\n\n";
            return;
        }

        // Ejecutar migraciones pendientes
        echo "🔄 Ejecutando {$pendingCount} migraciones:\n\n";

        $successCount = 0;
        $failCount = 0;

        foreach ($pendingMigrations as $migration) {
            $result = $this->executeMigration($migration);
            if ($result) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        // Mostrar resumen
        echo "\n" . str_repeat("═", 80) . "\n";
        echo "📊 RESUMEN:\n";
        echo "   ✅ Exitosas: {$successCount}\n";
        echo "   ❌ Fallidas: {$failCount}\n";
        echo str_repeat("═", 80) . "\n\n";

        if ($failCount === 0) {
            echo "✅ Todas las migraciones completadas exitosamente\n\n";
        } else {
            echo "⚠️  Algunas migraciones fallaron. Revisa los errores arriba.\n\n";
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// EJECUCIÓN DEL SCRIPT
// ═══════════════════════════════════════════════════════════════════════════

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    // Parsear argumentos
    $options = getopt('', ['dry-run', 'help']);

    if (isset($options['help'])) {
        echo "\nMain Database Migration Runner - Planilla Innova\n\n";
        echo "Ejecuta migraciones tenant en la base de datos principal\n";
        echo "configurada en el archivo .env (DB_DATABASE)\n\n";
        echo "Uso: php migrate_main_database.php [opciones]\n\n";
        echo "Opciones:\n";
        echo "  --dry-run    No ejecuta migraciones, solo muestra qué haría\n";
        echo "  --help       Muestra esta ayuda\n\n";
        echo "Ejemplos:\n";
        echo "  php migrate_main_database.php\n";
        echo "  php migrate_main_database.php --dry-run\n\n";
        exit(0);
    }

    $dryRun = isset($options['dry-run']);

    try {
        $runner = new MainDatabaseMigrationRunner($dryRun);
        $runner->run();

    } catch (Exception $e) {
        echo "\n❌ ERROR FATAL: {$e->getMessage()}\n\n";
        exit(1);
    }
}
