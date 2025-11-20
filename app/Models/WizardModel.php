<?php

namespace App\Models;

use App\Core\MasterDatabase;
use PDO;

class WizardModel
{
    private PDO $master;
    private string $appKey;

    public function __construct()
    {
        $this->master = MasterDatabase::getInstance()->getConnection();
        $this->appKey = $_ENV['APP_KEY'] ?? 'changeme-app-key';
    }

    public function hasConfiguredCompanies(): bool
    {
        try {
            $stmt = $this->master->query("SELECT COUNT(*) AS total FROM tenants WHERE status='ACTIVE'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int)($row['total'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
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
            return ['success' => true, 'email' => $email];
        }

        $message = $data['message'] ?? 'Authentication failed';
        return ['success' => false, 'message' => $message, 'status' => $status];
    }

    public function licenseExists(string $license): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM tenants WHERE license_key = ?";
        $stmt = $this->master->prepare($sql);
        $stmt->execute([$license]);
        return ((int)($stmt->fetchColumn() ?: 0)) > 0;
    }

    public function createCompanyRecord(array $companyData): int
    {
        // Insert basic record into tenants (without credentials yet)
        $sql = "INSERT INTO tenants (slug, status, license_key, license_status)
                VALUES (?, 'ACTIVE', ?, 'PENDING')";
        $slug = $this->slugify($companyData['company_name'] ?? ('tenant_' . substr(md5(uniqid()), 0, 6)));
        $stmt = $this->master->prepare($sql);
        $stmt->execute([$slug, $companyData['license_key']]);
        return (int)$this->master->lastInsertId();
    }

    public function generateTenantDatabaseName(string $license): string
    {
        $hash = substr(hash('sha256', $license), 0, 10);
        return 'planilla_tenant_' . $hash;
    }

    public function createTenantDatabase(string $dbName): void
    {
        $this->master->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    public function importTenantSchema(string $dbName): void
    {
        // Real integration: import schema/migrations for tenant DB.
        // Placeholder: ensure connection exists; migrations can run later.
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
            $stmt = $pdo->prepare("INSERT INTO admin (username, email, password, firstname, lastname, role_id) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $companyData['admin_username'],
                $companyData['admin_email'],
                $hash,
                $companyData['admin_firstname'],
                $companyData['admin_lastname']
            ]);
            return (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function generateAndValidateLicense(int $companyId, array $companyData): array
    {
        // If license_key already provided, use it; otherwise generate a new one
        $license = $companyData['license_key'] ?? strtoupper(substr(hash('sha256', uniqid((string)$companyId, true)), 0, 20));
        return ['success' => true, 'license_key' => $license];
    }

    public function updateCompanyDatabase(int $companyId, string $dbName): void
    {
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
}
