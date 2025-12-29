<?php
/**
 * Multi-Tenant Migration Runner - Planilla Innova
 *
 * Ejecuta migraciones automáticamente en TODOS los tenants registrados
 * en la base de datos tenant_master.
 *
 * Características:
 * - Detecta automáticamente todos los tenants ACTIVOS
 * - Ejecuta migraciones /tenant para cada tenant
 * - Logging detallado por tenant
 * - Manejo robusto de errores (continúa si un tenant falla)
 * - Estadísticas consolidadas al final
 *
 * Uso:
 *   php migrate_all_tenants.php [--dry-run] [--tenant=slug]
 *
 * Opciones:
 *   --dry-run         No ejecuta, solo muestra qué haría
 *   --tenant=slug     Ejecuta solo para un tenant específico
 *   --status          Muestra status de migraciones por tenant
 *
 * @version 1.0.0
 * @date 2025-12-27
 */

class MultiTenantMigrationRunner
{
    private $masterPdo;
    private $dryRun = false;
    private $targetTenant = null;
    private $migrationsPath;
    private $stats = [
        'total_tenants' => 0,
        'successful_tenants' => 0,
        'failed_tenants' => 0,
        'skipped_tenants' => 0,
        'total_migrations' => 0,
        'tenant_results' => []
    ];

    public function __construct($dryRun = false, $targetTenant = null)
    {
        $this->dryRun = $dryRun;
        $this->targetTenant = $targetTenant;
        $this->migrationsPath = __DIR__ . '/tenant';
        $this->loadEnvironment();
        $this->connectMaster();
    }

    /**
     * Cargar variables de entorno desde .env
     */
    private function loadEnvironment()
    {
        $envFile = __DIR__ . '/../../.env';

        if (!file_exists($envFile)) {
            echo "⚠️  Archivo .env no encontrado, usando valores por defecto\n";
            return;
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
    }

    /**
     * Conectar a la base de datos master
     */
    private function connectMaster()
    {
        try {
            $masterConfig = require __DIR__ . '/../../config/master_database.php';

            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $masterConfig['host'],
                $masterConfig['port'],
                $masterConfig['database'],
                $masterConfig['charset']
            );

            $this->masterPdo = new PDO(
                $dsn,
                $masterConfig['username'],
                $masterConfig['password'],
                $masterConfig['options']
            );

            echo "✅ Conectado a base de datos master: {$masterConfig['database']}\n";
        } catch (PDOException $e) {
            die("❌ Error conectando a BD master: " . $e->getMessage() . "\n");
        }
    }

    /**
     * Obtener lista de tenants activos
     */
    private function getActiveTenants()
    {
        try {
            $sql = "SELECT id, slug, db_host, db_port, db_name, db_user, db_pass_enc, db_charset, status
                    FROM tenants
                    WHERE status = 'ACTIVE'";

            if ($this->targetTenant) {
                $sql .= " AND slug = :slug";
                $stmt = $this->masterPdo->prepare($sql);
                $stmt->execute(['slug' => $this->targetTenant]);
            } else {
                $stmt = $this->masterPdo->query($sql);
            }

            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($tenants) && $this->targetTenant) {
                throw new Exception("Tenant '{$this->targetTenant}' no encontrado o no está ACTIVO");
            }

            return $tenants;

        } catch (PDOException $e) {
            die("❌ Error obteniendo tenants: " . $e->getMessage() . "\n");
        }
    }

    /**
     * Conectar a la base de datos de un tenant específico
     */
    private function connectToTenant($tenant)
    {
        try {
            // Desencriptar password si está encriptado
            $password = $this->decryptPassword($tenant['db_pass_enc']);

            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $tenant['db_host'] ?: 'localhost',
                $tenant['db_port'] ?: 3306,
                $tenant['db_name'],
                $tenant['db_charset'] ?: 'utf8mb4'
            );

            $pdo = new PDO(
                $dsn,
                $tenant['db_user'],
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // Fix: evitar error "Cannot execute queries while there are pending result sets"
                ]
            );

            return $pdo;

        } catch (PDOException $e) {
            throw new Exception("Error conectando a tenant {$tenant['slug']}: " . $e->getMessage());
        }
    }

    /**
     * Desencriptar password del tenant usando el mismo método que WizardModel
     * Utiliza AES-256-CBC con APP_KEY como clave
     */
    private function decryptPassword($encryptedPassword)
    {
        // Si no está encriptado (NULL o vacío), retornar vacío
        if (empty($encryptedPassword)) {
            return '';
        }

        // Usar el mismo método de desencriptación que WizardModel
        $appKey = $_ENV['APP_KEY'] ?? 'changeme-app-key';
        $key = hash('sha256', $appKey, true);
        $iv = substr(hash('sha256', $appKey . '_iv'), 0, 16);

        $decrypted = openssl_decrypt($encryptedPassword, 'AES-256-CBC', $key, 0, $iv);

        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Crear tabla migrations_history si no existe
     */
    private function ensureMigrationsTable($pdo)
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
            $pdo->exec($sql);
        }
    }

    /**
     * Obtener migraciones ya ejecutadas
     */
    private function getExecutedMigrations($pdo)
    {
        try {
            $stmt = $pdo->query("
                SELECT filename
                FROM migrations_history
                WHERE status IN ('success', 'skipped')
            ");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            // Si la tabla no existe o tiene estructura antigua
            return [];
        }
    }

    /**
     * Obtener archivos de migración
     */
    private function getMigrationFiles()
    {
        $files = glob($this->migrationsPath . "/*.sql");
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
     * Maneja correctamente string literals y comentarios
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
                        $i++; // Skip next char
                        continue;
                    }
                    if ($char === '/' && $nextChar === '*') {
                        $inComment = true;
                        $commentType = 'block';
                        $i++; // Skip next char
                        continue;
                    }
                }

                // Detectar fin de comentario
                if ($inComment) {
                    if ($commentType === 'block' && $char === '*' && $nextChar === '/') {
                        $inComment = false;
                        $commentType = '';
                        $i++; // Skip next char
                        continue;
                    }
                    if ($commentType === 'line') {
                        // Comentario de línea termina al final de la línea
                        break;
                    }
                    continue; // No agregar caracteres de comentarios
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

            // Agregar newline si no estamos en comentario
            if (!$inComment) {
                $buffer .= "\n";
            }

            // Reset comentario de línea al final de la línea
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
     * Ejecutar migración en un tenant
     */
    private function executeMigration($pdo, $migration, $tenantSlug)
    {
        $startTime = microtime(true);
        $status = 'success';
        $errorMessage = null;

        try {
            $sql = file_get_contents($migration['path']);

            if (!$this->dryRun) {
                // Dividir SQL en statements individuales
                $statements = $this->splitSqlStatements($sql);

                // Ejecutar cada statement por separado usando prepared statements para evitar buffer issues
                foreach ($statements as $index => $statement) {
                    $trimmedStatement = trim($statement);
                    if (!empty($trimmedStatement)) {
                        try {
                            // Usar query() en lugar de exec() para evitar errores de buffering
                            // query() libera automáticamente el buffer después de ejecutar
                            $result = $pdo->query($trimmedStatement);
                            if ($result) {
                                $result->closeCursor(); // Liberar resultado inmediatamente
                            }
                        } catch (PDOException $e) {
                            throw new PDOException(
                                "Error en statement #" . ($index + 1) . ": " . $e->getMessage(),
                                (int)$e->getCode()
                            );
                        }
                    }
                }
            }

            $executionTime = (int)((microtime(true) - $startTime) * 1000);

            // Registrar en migrations_history del tenant
            if (!$this->dryRun) {
                $this->recordMigration($pdo, $migration, $executionTime, $status, $errorMessage);
            }

            echo "      ✅ {$migration['filename']} ({$executionTime}ms)\n";
            return true;

        } catch (PDOException $e) {
            $executionTime = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();
            $status = 'failed';

            // Registrar migración fallida
            if (!$this->dryRun) {
                try {
                    $this->recordMigration($pdo, $migration, $executionTime, $status, $errorMessage);
                } catch (Exception $recordError) {
                    // Ignorar errores al registrar
                }
            }

            echo "      ❌ {$migration['filename']} - ERROR: {$errorMessage}\n";
            return false;
        }
    }

    /**
     * Registrar migración ejecutada
     */
    private function recordMigration($pdo, $migration, $executionTime, $status, $errorMessage)
    {
        try {
            $batch = $this->getNextBatch($pdo);
            $executedBy = getenv('USER') ?: getenv('USERNAME') ?: 'cli-script';

            $stmt = $pdo->prepare("
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
                $stmt = $pdo->prepare("
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
    private function getNextBatch($pdo)
    {
        try {
            $stmt = $pdo->query("SELECT MAX(batch) as max_batch FROM migrations_history");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['max_batch'] ?? 0) + 1;
        } catch (PDOException $e) {
            return 1;
        }
    }

    /**
     * Procesar migraciones para un tenant
     */
    private function processTenant($tenant)
    {
        $tenantSlug = $tenant['slug'];
        $tenantDb = $tenant['db_name'];

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🏢 TENANT: {$tenantSlug} (DB: {$tenantDb})\n";
        echo str_repeat("=", 80) . "\n";

        try {
            // Conectar al tenant
            $pdo = $this->connectToTenant($tenant);
            echo "   ✅ Conectado exitosamente\n";

            // Asegurar tabla migrations_history
            $this->ensureMigrationsTable($pdo);

            // Obtener migraciones ejecutadas y pendientes
            $executedMigrations = $this->getExecutedMigrations($pdo);
            $allMigrations = $this->getMigrationFiles();

            $pendingMigrations = array_filter($allMigrations, function($migration) use ($executedMigrations) {
                return !in_array($migration['filename'], $executedMigrations);
            });

            $totalMigrations = count($allMigrations);
            $executedCount = count($executedMigrations);
            $pendingCount = count($pendingMigrations);

            echo "\n   📊 Estado:\n";
            echo "      Total: {$totalMigrations} | Ejecutadas: ✅ {$executedCount} | Pendientes: ⏳ {$pendingCount}\n\n";

            if (empty($pendingMigrations)) {
                echo "   ✅ No hay migraciones pendientes\n";
                $this->stats['skipped_tenants']++;
                $this->stats['tenant_results'][$tenantSlug] = [
                    'status' => 'skipped',
                    'migrations_run' => 0,
                    'migrations_failed' => 0
                ];
                return;
            }

            // Ejecutar migraciones pendientes
            echo "   🔄 Ejecutando {$pendingCount} migraciones:\n\n";

            $successCount = 0;
            $failCount = 0;

            foreach ($pendingMigrations as $migration) {
                $result = $this->executeMigration($pdo, $migration, $tenantSlug);
                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }

            $this->stats['total_migrations'] += $successCount;

            if ($failCount === 0) {
                echo "\n   ✅ Tenant completado exitosamente ({$successCount} migraciones)\n";
                $this->stats['successful_tenants']++;
                $this->stats['tenant_results'][$tenantSlug] = [
                    'status' => 'success',
                    'migrations_run' => $successCount,
                    'migrations_failed' => 0
                ];
            } else {
                echo "\n   ⚠️  Tenant completado con errores ({$successCount} exitosas, {$failCount} fallidas)\n";
                $this->stats['failed_tenants']++;
                $this->stats['tenant_results'][$tenantSlug] = [
                    'status' => 'partial',
                    'migrations_run' => $successCount,
                    'migrations_failed' => $failCount
                ];
            }

        } catch (Exception $e) {
            echo "   ❌ ERROR: {$e->getMessage()}\n";
            $this->stats['failed_tenants']++;
            $this->stats['tenant_results'][$tenantSlug] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ejecutar migraciones en todos los tenants
     */
    public function run()
    {
        echo "\n╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║          MULTI-TENANT MIGRATION RUNNER - PLANILLA INNOVA                 ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";

        echo "\nModo: " . ($this->dryRun ? "🔍 DRY RUN (no ejecuta)" : "⚡ EJECUCIÓN REAL") . "\n";
        echo "Directorio migraciones: {$this->migrationsPath}\n";

        // Obtener tenants
        $tenants = $this->getActiveTenants();
        $this->stats['total_tenants'] = count($tenants);

        if ($this->targetTenant) {
            echo "Filtro: Solo tenant '{$this->targetTenant}'\n";
        }

        echo "\n📋 Tenants encontrados: " . count($tenants) . "\n";

        if (empty($tenants)) {
            echo "\n⚠️  No hay tenants activos para procesar\n";
            return;
        }

        // Procesar cada tenant
        foreach ($tenants as $tenant) {
            $this->processTenant($tenant);
        }

        // Mostrar resumen final
        $this->showSummary();
    }

    /**
     * Mostrar resumen final
     */
    private function showSummary()
    {
        echo "\n\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                           RESUMEN FINAL                                   ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n\n";

        echo "📊 ESTADÍSTICAS GENERALES:\n";
        echo "   Tenants procesados:        {$this->stats['total_tenants']}\n";
        echo "   ✅ Exitosos:               {$this->stats['successful_tenants']}\n";
        echo "   ❌ Con errores:            {$this->stats['failed_tenants']}\n";
        echo "   ⏭️  Omitidos (sin cambios): {$this->stats['skipped_tenants']}\n";
        echo "   📝 Total migraciones:      {$this->stats['total_migrations']}\n\n";

        // Detalles por tenant
        if (!empty($this->stats['tenant_results'])) {
            echo "📋 DETALLE POR TENANT:\n";
            echo str_repeat("─", 80) . "\n";
            printf("%-30s %-15s %-15s %s\n", "TENANT", "STATUS", "EJECUTADAS", "FALLIDAS");
            echo str_repeat("─", 80) . "\n";

            foreach ($this->stats['tenant_results'] as $slug => $result) {
                $statusIcon = $this->getStatusIcon($result['status']);
                $executed = $result['migrations_run'] ?? '-';
                $failed = $result['migrations_failed'] ?? '-';

                printf("%-30s %-15s %-15s %s\n", $slug, $statusIcon, $executed, $failed);

                if (isset($result['error'])) {
                    echo "   ⚠️  " . substr($result['error'], 0, 70) . "\n";
                }
            }
            echo str_repeat("─", 80) . "\n";
        }

        echo "\n✅ Proceso completado\n\n";
    }

    /**
     * Obtener ícono de status
     */
    private function getStatusIcon($status)
    {
        $icons = [
            'success' => '✅ Exitoso',
            'partial' => '⚠️  Parcial',
            'error' => '❌ Error',
            'skipped' => '⏭️  Omitido'
        ];
        return $icons[$status] ?? '❓ Desconocido';
    }

    /**
     * Mostrar status de migraciones por tenant
     */
    public function showStatus()
    {
        echo "\n╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║            STATUS MIGRACIONES MULTI-TENANT - PLANILLA INNOVA             ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n\n";

        $tenants = $this->getActiveTenants();

        foreach ($tenants as $tenant) {
            $tenantSlug = $tenant['slug'];
            $tenantDb = $tenant['db_name'];

            echo str_repeat("─", 80) . "\n";
            echo "🏢 {$tenantSlug} (DB: {$tenantDb})\n";
            echo str_repeat("─", 80) . "\n";

            try {
                $pdo = $this->connectToTenant($tenant);
                $executedMigrations = $this->getExecutedMigrations($pdo);
                $allMigrations = $this->getMigrationFiles();

                $pendingMigrations = array_filter($allMigrations, function($migration) use ($executedMigrations) {
                    return !in_array($migration['filename'], $executedMigrations);
                });

                $total = count($allMigrations);
                $executed = count($executedMigrations);
                $pending = count($pendingMigrations);

                echo "   Total: {$total} | Ejecutadas: ✅ {$executed} | Pendientes: ⏳ {$pending}\n";

                if ($pending > 0) {
                    echo "\n   Migraciones pendientes:\n";
                    foreach ($pendingMigrations as $migration) {
                        echo "      ⏳ {$migration['filename']}\n";
                    }
                }

                echo "\n";

            } catch (Exception $e) {
                echo "   ❌ Error: {$e->getMessage()}\n\n";
            }
        }

        echo str_repeat("─", 80) . "\n";
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// EJECUCIÓN DEL SCRIPT
// ═══════════════════════════════════════════════════════════════════════════

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    // Parsear argumentos
    $options = getopt('', ['dry-run', 'tenant:', 'status', 'help']);

    if (isset($options['help'])) {
        echo "\nMulti-Tenant Migration Runner - Planilla Innova\n\n";
        echo "Uso: php migrate_all_tenants.php [opciones]\n\n";
        echo "Opciones:\n";
        echo "  --dry-run          No ejecuta migraciones, solo muestra qué haría\n";
        echo "  --tenant=SLUG      Ejecuta migraciones solo para el tenant especificado\n";
        echo "  --status           Muestra el status de migraciones por cada tenant\n";
        echo "  --help             Muestra esta ayuda\n\n";
        echo "Ejemplos:\n";
        echo "  php migrate_all_tenants.php\n";
        echo "  php migrate_all_tenants.php --dry-run\n";
        echo "  php migrate_all_tenants.php --tenant=innova\n";
        echo "  php migrate_all_tenants.php --status\n\n";
        exit(0);
    }

    $dryRun = isset($options['dry-run']);
    $targetTenant = $options['tenant'] ?? null;
    $showStatus = isset($options['status']);

    try {
        $runner = new MultiTenantMigrationRunner($dryRun, $targetTenant);

        if ($showStatus) {
            $runner->showStatus();
        } else {
            $runner->run();
        }

    } catch (Exception $e) {
        echo "\n❌ ERROR FATAL: {$e->getMessage()}\n\n";
        exit(1);
    }
}
