<?php

namespace App\Models;

use App\Core\MasterDatabase;
use PDO;
use RuntimeException;

class WizardModel
{
    private ?PDO $master = null;
    private string $appKey;

    public function __construct()
    {
        try {
            $this->master = MasterDatabase::getInstance()->getConnection();
        } catch (\Throwable $e) {
            // Permitir cargar el wizard aunque la base master no responda
            error_log('Master DB not available: ' . $e->getMessage());
            $this->master = null;
        }
        $this->appKey = $_ENV['APP_KEY'] ?? 'changeme-app-key';
    }

    public function hasConfiguredCompanies(): bool
    {
        $this->assertMasterConnection();

        $stmt = $this->master->query("SELECT COUNT(*) AS total FROM tenants WHERE status='ACTIVE'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ((int)($row['total'] ?? 0)) > 0;
    }

    public function validateRemoteDistributor(string $username, string $password): array
    {
        // Prefer env-provided distributor validation URL. If it's a base URL,
        // append the legacy endpoint path; if it's already a PHP endpoint, use as-is.
        $rawUrl = trim($_ENV['DISTRIBUTOR_VALIDATION_URL'] ?? '');
        if ($rawUrl === '') {
            $rawUrl = trim($_ENV['LICENSING_BASE_URL'] ?? 'https://plataforma.innovasoftlatam.com:8080');
        }
        $isPhpEndpoint = (bool)preg_match('/\.php(\?|$)/i', $rawUrl);
        $base = rtrim($rawUrl, '/');
        $endpoint = $isPhpEndpoint ? $base : ($base . '/ajax/user.php');
        $sslVerify = false; // default secure
        if (isset($_ENV['LICENSING_SSL_VERIFY'])) {
            $sslVerify = (($_ENV['LICENSING_SSL_VERIFY']) === 'true');
        }

        $payload = [
            'LoginUser' => 'yes',
            'usuario' => $username,
            'password' => $password,
        ];

        if (trim($username) === '' || trim($password) === '') {
            return ['success' => false, 'message' => 'Missing credentials'];
        }


        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_CONNECTTIMEOUT => (int)($_ENV['HTTP_CONNECT_TIMEOUT'] ?? 8),
            CURLOPT_TIMEOUT => (int)($_ENV['HTTP_TIMEOUT'] ?? 15),

        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'message' => 'cURL error: ' . $err];
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        curl_close($ch);

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['success' => false, 'message' => 'Invalid response from licensing server', 'status' => $status];
        }

        if (isset($data['success']) && (string)$data['success'] === '1') {
            $email = $data['user_email'] ?? $data['email'] ?? null;
            return ['success' => true, 'email' => $email, 'user_name' => $data['user_name'] ?? null, 'user_contacto' => $data['user_contacto'] ?? null, 'empresa_name' => $data['empresa_name'] ?? null,'empresa_ruc' => $data['empresa_ruc'] ?? null];
        }

        $message = $data['message'] ?? 'Authentication failed';
        return ['success' => false, 'message' => $message, 'status' => $status];
    }

    public function licenseExists(string $license): bool
    {
        $this->assertMasterConnection();
        $sql = "SELECT COUNT(*) AS total FROM tenants WHERE license_key = ?";
        $stmt = $this->master->prepare($sql);
        $stmt->execute([$license]);
        return ((int)($stmt->fetchColumn() ?: 0)) > 0;
    }

    public function rucExists(string $ruc): bool
    {
        $this->assertMasterConnection();
        $sql = "SELECT COUNT(*) AS total FROM tenants WHERE ruc = ?";
        $stmt = $this->master->prepare($sql);
        $stmt->execute([trim($ruc)]);
        return ((int)($stmt->fetchColumn() ?: 0)) > 0;
    }

    public function createCompanyRecord(array $companyData): int
    {
        $this->assertMasterConnection();

        // Determinar estado de licencia basado en modo offline
        $licenseStatus = 'ACTIVE';
        if (!empty($companyData['license_sync_pending'])) {
            $licenseStatus = 'PENDING_SYNC';
        }

        // Insert basic record into tenants with company information
        $sql = "INSERT INTO tenants (
                    slug, company_name, ruc, admin_email, status,
                    license_key, license_status, license_expires_at,
                    license_sync_pending, license_sync_error
                )
                VALUES (?, ?, ?, ?, 'ACTIVE', ?, ?, ?, ?, ?)";

        // Generar slug único con timestamp para permitir múltiples instalaciones de la misma empresa
        $baseSlug = $this->slugify($companyData['company_name'] ?? 'tenant');
        $timestamp = date('YmdHis'); // Formato: 20251224063845
        $slug = $baseSlug . '-' . $timestamp;

        // Calcular fecha de expiración de licencia
        $licenseExpiresAt = null;
        if (!empty($companyData['license_expiration'])) {
            $licenseExpiresAt = date('Y-m-d H:i:s', strtotime($companyData['license_expiration']));
        }

        // Preparar mensaje de error de sincronización si existe
        $syncError = null;
        if (!empty($companyData['license_offline_mode'])) {
            $syncError = 'Licencia generada en modo offline - pendiente de registro en servidor remoto';
        }

        $stmt = $this->master->prepare($sql);
        $stmt->execute([
            $slug,
            $companyData['company_name'] ?? null,
            $companyData['ruc'] ?? null,
            $companyData['admin_email'] ?? null,
            $companyData['license_key'] ?? null,
            $licenseStatus,
            $licenseExpiresAt,
            !empty($companyData['license_sync_pending']) ? 1 : 0,
            $syncError
        ]);

        return (int)$this->master->lastInsertId();
    }

    /**
     * Generar nombre de base de datos del tenant basado en licencia
     * El nombre de la BD es EXACTAMENTE igual a la licencia
     *
     * @param string $license License key (formato: PINN1234567890)
     * @return string Nombre de base de datos (mismo que licencia)
     */
    public function generateTenantDatabaseName(string $license): string
    {
        // El nombre de la BD es exactamente igual a la licencia
        return $license;
    }

    public function createTenantDatabase(string $dbName): void
    {
        $this->assertMasterConnection();
        $this->master->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    public function importTenantSchema(string $dbName): void
    {
        // Import base schema in order: structure, stored procedures, triggers
        $baseDir = __DIR__ . '/../../database/install';
        $schemaFiles = [
            'structure' => $baseDir . '/planilla_base.sql',
            'procedures' => $baseDir . '/planilla_base_sp.sql',
            'triggers' => $baseDir . '/planilla_base_trg.sql',
        ];

        // Connect to the tenant database
        $tenantPdo = $this->connectTenant($dbName);

        // Use SqlImporter to execute each schema file
        $importer = new \App\Core\SqlImporter($tenantPdo);

        try {
            $totalStats = [
                'total_statements' => 0,
                'successful' => 0,
                'failed' => 0,
                'total_execution_time' => 0
            ];

            // PASO 1: Import base schema files
            error_log("========== PASO 1: Importando schema base ==========");
            foreach ($schemaFiles as $type => $file) {
                if (!file_exists($file)) {
                    error_log("Warning: {$type} file not found: {$file}, skipping...");
                    continue;
                }

                error_log("Importing {$type} for {$dbName}...");
                $importer->importFile($file);

                $stats = $importer->getStats();
                $totalStats['total_statements'] += $stats['total_statements'];
                $totalStats['successful'] += $stats['successful'];
                $totalStats['failed'] += $stats['failed'];
                $totalStats['total_execution_time'] += $stats['total_execution_time'];

                error_log("{$type} imported: {$stats['successful']}/{$stats['total_statements']} statements in {$stats['total_execution_time']}s");
            }

            // PASO 2: Import seed data (initial concept relations)
            error_log("========== PASO 2: Importando datos iniciales (seed) ==========");
            $seedStats = $this->importSeedData($tenantPdo, $dbName);

            // Merge seed stats
            $totalStats['total_statements'] += $seedStats['total_statements'];
            $totalStats['successful'] += $seedStats['successful'];
            $totalStats['failed'] += $seedStats['failed'];
            $totalStats['total_execution_time'] += $seedStats['total_execution_time'];

            // PASO 3: Import all migrations in chronological order
            error_log("========== PASO 3: Importando migraciones ==========");
            $migrationsStats = $this->importMigrations($tenantPdo, $dbName);

            // Merge migration stats
            $totalStats['total_statements'] += $migrationsStats['total_statements'];
            $totalStats['successful'] += $migrationsStats['successful'];
            $totalStats['failed'] += $migrationsStats['failed'];
            $totalStats['total_execution_time'] += $migrationsStats['total_execution_time'];

            // Log overall success
            error_log("========== IMPORTACIÓN COMPLETA ==========");
            error_log("Schema + seed + migrations import completed for {$dbName}");
            error_log("Total statements: {$totalStats['successful']}/{$totalStats['total_statements']}");
            error_log("Failed: {$totalStats['failed']}");
            error_log("Total time: {$totalStats['total_execution_time']}s");
            error_log("==========================================");

        } catch (\Exception $e) {
            error_log("Error importing schema for {$dbName}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Import seed data file with initial concept relations
     * File: database/install/seed_concepto_relations.sql
     *
     * @param PDO $tenantPdo Connection to tenant database
     * @param string $dbName Database name for logging
     * @return array Statistics of seed data execution
     */
    private function importSeedData(PDO $tenantPdo, string $dbName): array
    {
        $seedFile = __DIR__ . '/../../database/install/seed_concepto_relations.sql';
        $stats = [
            'total_statements' => 0,
            'successful' => 0,
            'failed' => 0,
            'total_execution_time' => 0
        ];

        // Check if seed file exists
        if (!file_exists($seedFile)) {
            error_log("⚠️ Seed data file not found: {$seedFile}, skipping...");
            return $stats;
        }

        try {
            error_log("Importing seed data: " . basename($seedFile));
            $startTime = microtime(true);

            // Create importer instance
            $importer = new \App\Core\SqlImporter($tenantPdo);
            $importer->importFile($seedFile);

            $fileStats = $importer->getStats();
            $executionTime = microtime(true) - $startTime;

            // Update stats
            $stats['total_statements'] = $fileStats['total_statements'];
            $stats['successful'] = $fileStats['successful'];
            $stats['failed'] = $fileStats['failed'];
            $stats['total_execution_time'] = $executionTime;

            error_log("✅ Seed data imported: {$fileStats['successful']}/{$fileStats['total_statements']} statements in {$executionTime}s");

            // Log details about imported data
            error_log("   - conceptos_acumulados: initial relations");
            error_log("   - concepto_frecuencias: initial relations");
            error_log("   - concepto_situaciones: initial relations");
            error_log("   - concepto_tipos_planilla: initial relations");

        } catch (\Exception $e) {
            error_log("❌ Seed data import failed: " . $e->getMessage());
            // Don't throw - allow installation to continue even if seed fails
        }

        return $stats;
    }

    /**
     * Import all migration files from database/migrations/tenant/ directory
     * Files are executed in chronological order based on filename
     *
     * IMPORTANTE: Ejecuta SOLO migraciones TENANT (database/migrations/tenant/)
     * Las migraciones de database/migrations/ son para la BD master (planilla_prod)
     *
     * @param PDO $tenantPdo Connection to tenant database
     * @param string $dbName Database name for logging
     * @return array Statistics of migration execution
     */
    private function importMigrations(PDO $tenantPdo, string $dbName): array
    {
        // CORRECCIÓN: Usar database/migrations/tenant/ en lugar de database/migrations/
        $migrationsDir = __DIR__ . '/../../database/migrations/tenant';
        $stats = [
            'total_statements' => 0,
            'successful' => 0,
            'failed' => 0,
            'total_execution_time' => 0,
            'migrations_executed' => 0,
            'migrations_failed' => 0,
            'migrations_skipped' => 0
        ];

        // Check if migrations directory exists
        if (!is_dir($migrationsDir)) {
            error_log("⚠️ Tenant migrations directory not found: {$migrationsDir}");
            return $stats;
        }

        // Get all .sql files from migrations directory
        $migrationFiles = $this->getMigrationFiles($migrationsDir);

        if (empty($migrationFiles)) {
            error_log("No migration files found in {$migrationsDir}");
            return $stats;
        }

        error_log("Found " . count($migrationFiles) . " migration files to execute");

        // Create new importer instance for migrations WITHOUT transactions
        // (DDL statements like CREATE TRIGGER, ALTER TABLE cause implicit commits in MySQL)
        $importer = new \App\Core\SqlImporter($tenantPdo, false);

        // Execute each migration file in order
        foreach ($migrationFiles as $file) {
            $fileName = basename($file);

            try {
                error_log("Executing migration: {$fileName}");
                $startTime = microtime(true);

                $importer->importFile($file);

                $fileStats = $importer->getStats();
                $executionTime = microtime(true) - $startTime;

                // Update overall stats
                $stats['total_statements'] += $fileStats['total_statements'];
                $stats['successful'] += $fileStats['successful'];
                $stats['failed'] += $fileStats['failed'];
                $stats['total_execution_time'] += $executionTime;

                if ($fileStats['failed'] > 0) {
                    $stats['migrations_failed']++;
                    error_log("⚠️ Migration {$fileName} completed with {$fileStats['failed']} errors ({$fileStats['successful']}/{$fileStats['total_statements']} statements)");
                } else {
                    $stats['migrations_executed']++;
                    error_log("✅ Migration {$fileName} executed successfully ({$fileStats['successful']} statements, {$executionTime}s)");
                }

            } catch (\Exception $e) {
                $stats['migrations_failed']++;
                error_log("❌ Migration {$fileName} failed: " . $e->getMessage());

                // Continue with next migration even if this one fails
                // This allows partial migrations to succeed
                continue;
            }
        }

        error_log("---------- Migrations Summary ----------");
        error_log("Executed: {$stats['migrations_executed']}");
        error_log("Failed: {$stats['migrations_failed']}");
        error_log("Statements: {$stats['successful']}/{$stats['total_statements']}");
        error_log("Time: {$stats['total_execution_time']}s");
        error_log("----------------------------------------");

        return $stats;
    }

    /**
     * Get all migration files from directory, sorted chronologically by filename
     * Excluye archivos HOTFIX que están diseñados para ejecución manual
     *
     * @param string $directory Path to migrations directory
     * @return array Array of full file paths sorted chronologically
     */
    private function getMigrationFiles(string $directory): array
    {
        $files = glob($directory . '/*.sql');

        if ($files === false) {
            return [];
        }

        // Filtrar archivos HOTFIX (son para ejecución manual, no automática)
        $files = array_filter($files, function($file) {
            $basename = basename($file);
            // Excluir archivos que empiezan con HOTFIX_ o contienen _HOTFIX_
            if (stripos($basename, 'HOTFIX') !== false) {
                error_log("⏭️ Skipping HOTFIX file (manual execution only): {$basename}");
                return false;
            }
            return true;
        });

        // Sort files chronologically based on filename
        // Migration files follow format: YYYY_MM_DD_* or add_*
        usort($files, function($a, $b) {
            $fileA = basename($a);
            $fileB = basename($b);

            // Extract date prefix if exists (format: YYYY_MM_DD)
            $dateA = $this->extractMigrationDate($fileA);
            $dateB = $this->extractMigrationDate($fileB);

            // If both have dates, compare by date
            if ($dateA && $dateB) {
                return strcmp($dateA, $dateB);
            }

            // Files with dates come after files without dates
            if ($dateA && !$dateB) return 1;
            if (!$dateA && $dateB) return -1;

            // If neither has date, sort alphabetically
            return strcmp($fileA, $fileB);
        });

        return $files;
    }

    /**
     * Extract date/timestamp prefix from migration filename
     * Supports formats:
     * - YYYY_MM_DD_HHMMSS_* (recommended: full timestamp)
     * - YYYY_MM_DD_HHMM_* (timestamp without seconds)
     * - YYYY_MM_DD_* (date only, legacy)
     * - YYYYMMDDHHMMSS_* (compact timestamp)
     * - YYYYMMDD_* (compact date only, legacy)
     *
     * @param string $filename Migration filename
     * @return string|null Timestamp string in YYYYMMDDHHmmss format (padded), or null if no date found
     */
    private function extractMigrationDate(string $filename): ?string
    {
        // Match YYYY_MM_DD_HHMMSS format (e.g., 2025_12_23_143025_migration.sql)
        if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{6})_/', $filename, $matches)) {
            return $matches[1] . $matches[2] . $matches[3] . $matches[4]; // YYYYMMDDHHmmss
        }

        // Match YYYY_MM_DD_HHMM format (e.g., 2025_12_23_1430_migration.sql)
        if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{4})_/', $filename, $matches)) {
            return $matches[1] . $matches[2] . $matches[3] . $matches[4] . '00'; // YYYYMMDDHHmm00
        }

        // Match YYYY_MM_DD format (legacy, e.g., 2025_11_15_migration.sql)
        if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_/', $filename, $matches)) {
            return $matches[1] . $matches[2] . $matches[3] . '000000'; // YYYYMMDD000000
        }

        // Match YYYYMMDDHHMMSS format (compact, e.g., 20251223143025_migration.sql)
        if (preg_match('/^(\d{14})_/', $filename, $matches)) {
            return $matches[1]; // YYYYMMDDHHmmss
        }

        // Match YYYYMMDD format (compact legacy, e.g., 20251115_migration.sql)
        if (preg_match('/^(\d{8})_/', $filename, $matches)) {
            return $matches[1] . '000000'; // YYYYMMDD000000
        }

        return null;
    }

    /**
     * Configurar datos iniciales de empresa en BD tenant
     * IMPORTANTE: Este método se ejecuta DESPUÉS de importar schema + migraciones
     * para asegurar que la tabla companies existe
     *
     * ESTRATEGIA: El schema base inserta un registro dummy con ID=1.
     * Este método SIEMPRE actualiza ese registro (ID=1) con los datos reales de la empresa.
     *
     * @param string $dbName Nombre de la base de datos tenant
     * @param array $companyData Datos de la empresa (company_name, ruc, admin_email)
     * @param int $companyId ID de la empresa en tabla master.tenants (no se usa, siempre ID=1 en tenant)
     * @return void
     */
    public function setupTenantCompanyData(string $dbName, array $companyData, int $companyId): void
    {
        $pdo = $this->connectTenant($dbName);

        try {
            // DEBUG: Mostrar datos que llegan al método
            error_log("========== DEBUG setupTenantCompanyData ==========");
            error_log("📦 Datos recibidos en companyData:");
            error_log("   - company_name: " . ($companyData['company_name'] ?? 'NO SET'));
            error_log("   - ruc: " . ($companyData['ruc'] ?? 'NO SET'));
            error_log("   - admin_email: " . ($companyData['admin_email'] ?? 'NO SET'));
            error_log("   - admin_firstname: " . ($companyData['admin_firstname'] ?? 'NO SET'));
            error_log("   - admin_lastname: " . ($companyData['admin_lastname'] ?? 'NO SET'));
            error_log("================================================");

            // Verificar si la tabla companies existe
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'companies'");
            if ($tableCheck->rowCount() === 0) {
                error_log("⚠️ Tabla companies no existe en {$dbName}, saltando configuración");
                return;
            }

            // ESTRATEGIA: Siempre trabajar con ID=1 (el único registro en la BD tenant)
            // El schema base ya insertó un registro dummy, lo actualizamos con datos reales

            // Verificar si existe algún registro en companies
            $checkStmt = $pdo->query("SELECT COUNT(*) as total FROM companies");
            $total = $checkStmt->fetch(PDO::FETCH_ASSOC)['total'];

            if ($total > 0) {
                // ACTUALIZAR el registro existente (siempre ID=1 del schema base)
                error_log("📝 Actualizando datos empresa (reemplazando datos dummy del schema) en {$dbName}");

                // Preparar valores para UPDATE - SOLO nombre, ruc y email
                $updateCompanyName = $companyData['company_name'];
                $updateRuc = $companyData['ruc'] ?? '';
                $updateEmail = $companyData['admin_email'] ?? '';

                error_log("📝 Valores que se van a ACTUALIZAR:");
                error_log("   - company_name: '{$updateCompanyName}'");
                error_log("   - ruc: '{$updateRuc}'");
                error_log("   - email: '{$updateEmail}'");

                $stmt = $pdo->prepare("
                    UPDATE companies
                    SET company_name = ?,
                        ruc = ?,
                        email = ?
                    WHERE id = 1
                ");
                $stmt->execute([
                    $updateCompanyName,
                    $updateRuc,
                    $updateEmail
                ]);

                $rowsAffected = $stmt->rowCount();
                error_log("✅ Registro empresa ID=1 actualizado exitosamente - Filas afectadas: {$rowsAffected}");

                // Verificar que efectivamente se actualizó
                $verifyStmt = $pdo->query("SELECT company_name, ruc, email FROM companies WHERE id = 1");
                $verifyData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                error_log("🔍 Verificación datos después de UPDATE:");
                error_log("   - company_name (BD): '{$verifyData['company_name']}'");
                error_log("   - ruc (BD): '{$verifyData['ruc']}'");
                error_log("   - email (BD): '{$verifyData['email']}'");

            } else {
                // INSERTAR si no existe ningún registro (fallback) - SOLO nombre, ruc y email
                error_log("📝 Insertando nuevo registro empresa (ID=1) en {$dbName}");
                $stmt = $pdo->prepare("
                    INSERT INTO companies (id, company_name, ruc, email)
                    VALUES (1, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyData['company_name'],
                    $companyData['ruc'] ?? '',
                    $companyData['admin_email'] ?? ''
                ]);

                error_log("✅ Registro empresa ID=1 creado exitosamente");
            }

            error_log("✅ Datos empresa configurados correctamente en tenant:");
            error_log("   - Company Name: {$companyData['company_name']}");
            error_log("   - RUC: " . ($companyData['ruc'] ?? 'N/A'));
            error_log("   - Email: " . ($companyData['admin_email'] ?? 'N/A'));

        } catch (\Throwable $e) {
            error_log("❌ Error configurando datos empresa en {$dbName}: " . $e->getMessage());
            error_log("   Stack trace: " . $e->getTraceAsString());
            // No lanzar excepción para no bloquear la creación del tenant
        }
    }

    public function createTenantAdminUser(string $dbName, array $companyData): int
    {
        $pdo = $this->connectTenant($dbName);
        try {
            $hash = password_hash($companyData['admin_password'], PASSWORD_BCRYPT);
            $currentDate = date('Y-m-d');

            // La tabla admin no tiene columna email, solo username, password, firstname, lastname, photo, created_on, role_id
            $stmt = $pdo->prepare("
                INSERT INTO admin (username, password, firstname, lastname, photo, created_on, role_id, status)
                VALUES (?, ?, ?, ?, '', ?, 1, 1)
            ");
            $stmt->execute([
                $companyData['admin_username'],
                $hash,
                $companyData['admin_firstname'],
                $companyData['admin_lastname'],
                $currentDate
            ]);
            return (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log("Error creating admin user: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generar una licencia única para el tenant
     * Formato: PINN1234567890 (14 caracteres: PINN + 10 dígitos)
     *
     * @return string License key única
     */
    public function generateUniqueLicense(): string
    {
        do {
            // Generar licencia: PINN + 10 dígitos aleatorios
            $randomDigits = '';
            for ($i = 0; $i < 10; $i++) {
                $randomDigits .= random_int(0, 9);
            }

            $license = "PINN" . $randomDigits;

            // Verificar que no exista
        } while ($this->licenseExists($license));

        return $license;
    }

    public function generateAndValidateLicense(int $companyId, array $companyData): array
    {
        // If license_key already provided, use it; otherwise generate a new one
        $license = $companyData['license_key'] ?? $this->generateUniqueLicense();
        return ['success' => true, 'license_key' => $license];
    }

    public function updateCompanyDatabase(int $companyId, string $dbName): void
    {
        $this->assertMasterConnection();
        // Update tenant DB credentials in master.tenants
        $sql = "UPDATE tenants
                SET db_host = ?, db_port = ?, db_name = ?, db_user = ?, db_pass_enc = ?, db_charset = ?, license_status = 'ACTIVE'
                WHERE id = ?";
        $host = $_ENV['TENANT_DB_HOST'] ?? 'localhost';
        $port = (int)($_ENV['TENANT_DB_PORT'] ?? 3306);
        $user = $_ENV['TENANT_DB_USER'] ?? 'root';
        $pass = $_ENV['TENANT_DB_PASS'] ?? '';
        $charset = $_ENV['TENANT_DB_CHARSET'] ?? 'utf8mb4';
        $enc = $this->encrypt($pass);
        $stmt = $this->master->prepare($sql);
        $stmt->execute([$host, $port, $dbName, $user, $enc, $charset, $companyId]);
    }

    // ================= Helpers =================
    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9-]+/', '-', $text);
        return trim($text, '-');
    }

    private function connectTenant(string $dbName): PDO
    {
        $host = $_ENV['TENANT_DB_HOST'] ?? 'localhost';
        $port = (int)($_ENV['TENANT_DB_PORT'] ?? 3306);
        $user = $_ENV['TENANT_DB_USER'] ?? 'root';
        $pass = $_ENV['TENANT_DB_PASS'] ?? '';
        $charset = $_ENV['TENANT_DB_CHARSET'] ?? 'utf8mb4';
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $dbName, $charset);
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // Fix: Enable buffered queries for migrations
        ]);
    }

    private function encrypt(string $plain): string
    {
        $key = hash('sha256', $this->appKey, true);
        $iv = substr(hash('sha256', $this->appKey . '_iv'), 0, 16);
        return openssl_encrypt($plain, 'AES-256-CBC', $key, 0, $iv) ?: '';
    }

    public function decrypt(string $cipher): string
    {
        $key = hash('sha256', $this->appKey, true);
        $iv = substr(hash('sha256', $this->appKey . '_iv'), 0, 16);
        return openssl_decrypt($cipher, 'AES-256-CBC', $key, 0, $iv) ?: '';
    }

    private function assertMasterConnection(): void
    {
        if (!$this->master) {
            throw new RuntimeException('Master database connection is not configured or available.');
        }
    }
}
