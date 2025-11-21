<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;
use RuntimeException;

/**
 * TenantMigrationSystem - Sistema de migraciones multi-tenant
 *
 * Gestiona la ejecución de migraciones SQL en múltiples bases de datos tenant:
 * - Discovery automático de migraciones desde carpeta
 * - Tracking de versiones por tenant (tabla schema_migrations)
 * - Ejecución batch en todos los tenants activos
 * - Rollback granular con archivos .down.sql
 * - Dry-run mode para preview sin ejecutar
 * - Status command para auditorías
 * - Checksum validation para detectar cambios
 * - Logging detallado a archivo
 *
 * @package App\Core
 * @version 1.0.0
 * @author Sistema Planillas MVC
 */
class TenantMigrationSystem
{
    private PDO $masterDb;
    private array $config;
    private string $migrationsPath;
    private string $logPath;

    /**
     * Constructor
     *
     * @param PDO $masterDb Conexión a base de datos master (planilla_master)
     * @param array $config Configuración de base de datos [host, username, password]
     * @param string|null $migrationsPath Ruta a carpeta de migraciones (opcional)
     * @param string|null $logPath Ruta a carpeta de logs (opcional)
     */
    public function __construct(
        PDO $masterDb,
        array $config,
        ?string $migrationsPath = null,
        ?string $logPath = null
    ) {
        $this->masterDb = $masterDb;
        $this->config = $config;

        // Rutas por defecto
        $basePath = __DIR__ . '/../../';
        $this->migrationsPath = $migrationsPath ?? $basePath . 'database/migrations/tenant';
        $this->logPath = $logPath ?? $basePath . 'storage/logs';

        // Crear carpetas si no existen
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Ejecutar migraciones en todos los tenants activos
     *
     * @param bool $dryRun Si es true, solo muestra qué se ejecutaría sin hacerlo
     * @return array Resultados con arrays de success, failed, skipped
     */
    public function migrateAll(bool $dryRun = false): array
    {
        $startTime = microtime(true);

        $tenants = $this->getActiveTenants();
        $migrations = $this->discoverMigrations();

        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => []
        ];

        if (empty($tenants)) {
            echo "⚠️  No active tenants found\n";
            return $results;
        }

        if (empty($migrations)) {
            echo "⚠️  No migrations found in {$this->migrationsPath}\n";
            return $results;
        }

        echo "\n=== TENANT MIGRATION SYSTEM ===\n";
        echo "Tenants: " . count($tenants) . " active\n";
        echo "Migrations: " . count($migrations) . " discovered\n";
        echo "Mode: " . ($dryRun ? "DRY-RUN (preview only)" : "EXECUTE") . "\n\n";

        foreach ($tenants as $tenant) {
            echo "Processing tenant: {$tenant['company_name']} ({$tenant['database_name']})...\n";

            try {
                $tenantDb = $this->connectTenant($tenant['database_name']);
                $this->ensureMigrationsTable($tenantDb);

                $pending = $this->getPendingMigrations($tenantDb, $migrations);

                if (empty($pending)) {
                    $results['skipped'][] = $tenant['company_name'];
                    echo "  ⏭️  No pending migrations\n\n";
                    continue;
                }

                if ($dryRun) {
                    echo "  🔍 DRY-RUN: Would execute " . count($pending) . " migrations:\n";
                    foreach ($pending as $mig) {
                        echo "    - {$mig['version']}: {$mig['description']}\n";
                    }
                    echo "\n";
                    continue;
                }

                // Ejecutar migraciones pendientes
                $migrationCount = 0;
                foreach ($pending as $migration) {
                    $this->executeMigration($tenantDb, $migration);
                    $migrationCount++;
                }

                $results['success'][] = [
                    'tenant' => $tenant['company_name'],
                    'database' => $tenant['database_name'],
                    'migrations' => $migrationCount
                ];

                echo "  ✅ SUCCESS: {$migrationCount} migrations executed\n\n";

            } catch (Exception $e) {
                $results['failed'][] = [
                    'tenant' => $tenant['company_name'],
                    'database' => $tenant['database_name'],
                    'error' => $e->getMessage()
                ];

                echo "  ❌ FAILED: {$e->getMessage()}\n\n";

                // Log error a archivo
                $this->logError($tenant, $e);
            }
        }

        $totalTime = round(microtime(true) - $startTime, 2);

        echo "=== SUMMARY ===\n";
        echo "Success: " . count($results['success']) . " tenants\n";
        echo "Failed: " . count($results['failed']) . " tenants\n";
        echo "Skipped: " . count($results['skipped']) . " tenants (no pending migrations)\n";
        echo "Total time: {$totalTime}s\n";

        return $results;
    }

    /**
     * Ejecutar una migración en una base de datos tenant específica
     *
     * @param PDO $db Conexión a la base de datos tenant
     * @param array $migration Array con datos de la migración
     * @throws RuntimeException Si la migración falla
     */
    private function executeMigration(PDO $db, array $migration): void
    {
        $startTime = microtime(true);

        // Usar SqlImporter para ejecutar el archivo SQL
        $importer = new SqlImporter($db);

        $db->beginTransaction();

        try {
            // Importar archivo SQL
            $importer->importFile($migration['file']);

            $executionTime = (microtime(true) - $startTime) * 1000; // en milisegundos

            // Registrar en schema_migrations
            $stmt = $db->prepare("
                INSERT INTO schema_migrations
                (version, description, file_path, execution_time_ms, checksum)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $migration['version'],
                $migration['description'],
                $migration['file'],
                (int)$executionTime,
                $migration['checksum']
            ]);

            $db->commit();

            $stats = $importer->getStats();
            echo "    ✓ {$migration['version']}: {$migration['description']}\n";
            echo "      ({$stats['successful']} statements, " . round($executionTime, 2) . "ms)\n";

        } catch (Exception $e) {
            $db->rollback();

            $importerLog = $importer->getLog();
            $lastError = !empty($importerLog) ? end($importerLog) : null;

            throw new RuntimeException(
                "Migration {$migration['version']} failed:\n" .
                "  File: {$migration['file']}\n" .
                "  Error: " . $e->getMessage() . "\n" .
                ($lastError ? "  Last statement: " . ($lastError['statement'] ?? 'unknown') . "\n" : "")
            );
        }
    }

    /**
     * Descubrir todas las migraciones en la carpeta migrations/tenant/
     *
     * @return array Array de migraciones ordenadas por versión
     */
    private function discoverMigrations(): array
    {
        $migrations = [];
        $files = glob($this->migrationsPath . '/*.sql');

        if ($files === false) {
            return [];
        }

        // Excluir archivos .down.sql (son para rollback)
        $files = array_filter($files, fn($f) => !str_ends_with($f, '.down.sql'));

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if ($content === false) {
                continue;
            }

            // Parsear metadata desde comentarios en el archivo
            preg_match('/--\s*version:\s*(.+)/i', $content, $versionMatch);
            preg_match('/--\s*description:\s*(.+)/i', $content, $descriptionMatch);

            $version = isset($versionMatch[1]) ? trim($versionMatch[1]) : basename($file, '.sql');
            $description = isset($descriptionMatch[1]) ? trim($descriptionMatch[1]) : 'No description';

            $migrations[] = [
                'version' => $version,
                'description' => $description,
                'file' => $file,
                'checksum' => md5($content),
                'filename' => basename($file)
            ];
        }

        // Ordenar por version (alfanumérico)
        usort($migrations, fn($a, $b) => strcmp($a['version'], $b['version']));

        return $migrations;
    }

    /**
     * Obtener migraciones pendientes para un tenant específico
     *
     * @param PDO $db Conexión a la base de datos tenant
     * @param array $allMigrations Array de todas las migraciones disponibles
     * @return array Array de migraciones pendientes
     */
    private function getPendingMigrations(PDO $db, array $allMigrations): array
    {
        // Obtener migraciones ya ejecutadas
        $stmt = $db->query("SELECT version, checksum FROM schema_migrations");
        $executed = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return array_filter($allMigrations, function($migration) use ($executed) {
            // No ejecutada
            if (!isset($executed[$migration['version']])) {
                return true;
            }

            // Ejecutada pero checksum cambió (WARNING - no re-ejecutar)
            if ($executed[$migration['version']] !== $migration['checksum']) {
                echo "    ⚠️  WARNING: Migration {$migration['version']} checksum changed (not re-executing)\n";
                return false;
            }

            return false;
        });
    }

    /**
     * Asegurar que existe la tabla schema_migrations en el tenant
     *
     * @param PDO $db Conexión a la base de datos tenant
     */
    private function ensureMigrationsTable(PDO $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                version VARCHAR(255) NOT NULL UNIQUE,
                description VARCHAR(500),
                file_path VARCHAR(500),
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                execution_time_ms INT COMMENT 'Tiempo de ejecución en milisegundos',
                checksum VARCHAR(32) COMMENT 'MD5 hash del archivo de migración',
                INDEX idx_version (version),
                INDEX idx_executed_at (executed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Rollback de migraciones en un tenant específico
     *
     * @param string $tenantDbName Nombre de la base de datos tenant
     * @param int $steps Número de migraciones a revertir (default: 1)
     * @throws RuntimeException Si el rollback falla
     */
    public function rollback(string $tenantDbName, int $steps = 1): void
    {
        echo "\n=== ROLLBACK: {$tenantDbName} ===\n";
        echo "Steps to rollback: {$steps}\n\n";

        $db = $this->connectTenant($tenantDbName);
        $this->ensureMigrationsTable($db);

        // Obtener últimas N migraciones ejecutadas
        $stmt = $db->prepare("
            SELECT version, file_path, description
            FROM schema_migrations
            ORDER BY executed_at DESC
            LIMIT ?
        ");
        $stmt->execute([$steps]);
        $migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($migrations)) {
            echo "⚠️  No migrations to rollback\n";
            return;
        }

        foreach ($migrations as $migration) {
            $downFile = str_replace('.sql', '.down.sql', $migration['file_path']);

            if (!file_exists($downFile)) {
                echo "❌ Rollback file not found: {$downFile}\n";
                echo "   Skipping: {$migration['version']}\n\n";
                continue;
            }

            echo "Rolling back: {$migration['version']} - {$migration['description']}\n";

            $db->beginTransaction();

            try {
                $importer = new SqlImporter($db);
                $importer->importFile($downFile);

                // Eliminar registro de schema_migrations
                $stmt = $db->prepare("DELETE FROM schema_migrations WHERE version = ?");
                $stmt->execute([$migration['version']]);

                $db->commit();

                $stats = $importer->getStats();
                echo "  ✅ Rolled back successfully ({$stats['successful']} statements)\n\n";

            } catch (Exception $e) {
                $db->rollback();
                echo "  ❌ Rollback failed: {$e->getMessage()}\n\n";
                throw $e;
            }
        }

        echo "=== ROLLBACK COMPLETED ===\n";
    }

    /**
     * Mostrar estado de migraciones (todos los tenants o uno específico)
     *
     * @param string|null $tenantDbName Nombre de la base de datos tenant (opcional)
     */
    public function status(?string $tenantDbName = null): void
    {
        if ($tenantDbName) {
            $this->showTenantStatus($tenantDbName);
        } else {
            $this->showAllTenantsStatus();
        }
    }

    /**
     * Mostrar estado de todos los tenants
     */
    private function showAllTenantsStatus(): void
    {
        $tenants = $this->getActiveTenants();
        $allMigrations = $this->discoverMigrations();

        echo "\n=== MIGRATION STATUS (All Tenants) ===\n";
        echo "Total available migrations: " . count($allMigrations) . "\n\n";

        foreach ($tenants as $tenant) {
            try {
                $db = $this->connectTenant($tenant['database_name']);
                $this->ensureMigrationsTable($db);

                $executed = $db->query("SELECT COUNT(*) FROM schema_migrations")->fetchColumn();
                $pending = count($this->getPendingMigrations($db, $allMigrations));

                $status = $pending > 0 ? "⚠️  PENDING" : "✅ UP-TO-DATE";

                echo "{$status} | {$tenant['company_name']}\n";
                echo "  Database: {$tenant['database_name']}\n";
                echo "  Executed: {$executed} | Pending: {$pending}\n\n";

            } catch (Exception $e) {
                echo "❌ ERROR | {$tenant['company_name']}\n";
                echo "  Error: {$e->getMessage()}\n\n";
            }
        }
    }

    /**
     * Mostrar estado detallado de un tenant específico
     *
     * @param string $tenantDbName Nombre de la base de datos tenant
     */
    private function showTenantStatus(string $tenantDbName): void
    {
        echo "\n=== MIGRATION STATUS: {$tenantDbName} ===\n\n";

        try {
            $db = $this->connectTenant($tenantDbName);
            $this->ensureMigrationsTable($db);

            $migrations = $db->query("
                SELECT version, description, executed_at, execution_time_ms
                FROM schema_migrations
                ORDER BY executed_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);

            if (empty($migrations)) {
                echo "No migrations executed yet.\n";
                return;
            }

            foreach ($migrations as $mig) {
                echo "✓ {$mig['version']}: {$mig['description']}\n";
                echo "  Executed: {$mig['executed_at']} ({$mig['execution_time_ms']}ms)\n\n";
            }

            echo "Total: " . count($migrations) . " migrations executed\n";

        } catch (Exception $e) {
            echo "❌ Error: {$e->getMessage()}\n";
        }
    }

    /**
     * Log de errores a archivo
     *
     * @param array $tenant Datos del tenant
     * @param Exception $e Excepción ocurrida
     */
    private function logError(array $tenant, Exception $e): void
    {
        $logFile = $this->logPath . '/tenant_migrations_' . date('Y-m-d') . '.log';

        $logEntry = sprintf(
            "[%s] FAILED: %s (%s)\nError: %s\nTrace: %s\n\n",
            date('Y-m-d H:i:s'),
            $tenant['company_name'],
            $tenant['database_name'],
            $e->getMessage(),
            $e->getTraceAsString()
        );

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Obtener todos los tenants activos desde master DB
     *
     * @return array Array de tenants con id, database_name, company_name
     */
    private function getActiveTenants(): array
    {
        try {
            $stmt = $this->masterDb->query("
                SELECT id, database_name, company_name
                FROM tenants
                WHERE status = 'active'
                ORDER BY company_name
            ");

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Si la tabla tenants no existe, retornar array vacío
            return [];
        }
    }

    /**
     * Conectar a una base de datos tenant específica
     *
     * @param string $dbName Nombre de la base de datos tenant
     * @return PDO Conexión PDO a la base de datos tenant
     * @throws RuntimeException Si la conexión falla
     */
    private function connectTenant(string $dbName): PDO
    {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$dbName};charset=utf8mb4";
            $db = new PDO($dsn, $this->config['username'], $this->config['password']);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $db;

        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to connect to tenant database '{$dbName}': " . $e->getMessage()
            );
        }
    }
}
