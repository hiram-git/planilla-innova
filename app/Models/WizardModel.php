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

        $slug = $this->slugify($companyData['company_name'] ?? ('tenant_' . substr(md5(uniqid()), 0, 6)));

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

            // Import each file in order
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

            // Log overall success
            error_log("Schema import completed for {$dbName}: {$totalStats['successful']}/{$totalStats['total_statements']} total statements in {$totalStats['total_execution_time']}s");

        } catch (\Exception $e) {
            error_log("Error importing schema for {$dbName}: " . $e->getMessage());
            throw $e;
        }
    }

    public function setupTenantCompanyData(string $dbName, array $companyData, int $companyId): void
    {
        $pdo = $this->connectTenant($dbName);
        try {
            $stmt = $pdo->prepare("INSERT INTO companies (id, company_name, ruc, admin_email) VALUES (?, ?, ?, ?)");
            $stmt->execute([$companyId, $companyData['company_name'], $companyData['ruc'] ?? null, $companyData['admin_email']]);
        } catch (\Throwable $e) {
            // Table may not exist yet; ignore in placeholder.
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
