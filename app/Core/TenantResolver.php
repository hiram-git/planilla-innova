<?php

namespace App\Core;

use PDO;

/**
 * TenantResolver - Sistema de resolución de tenant para multitenancy
 *
 * Detecta el tenant actual desde:
 * - Parámetro URL ?tenant=
 * - Sesión (tenant persistente)
 *
 * Y cambia dinámicamente la conexión de base de datos
 */
class TenantResolver
{
    private static ?string $currentTenant = null;
    private static ?array $tenantInfo = null;

    /**
     * Resolver tenant desde request y establecer contexto
     *
     * @return bool True si hay tenant activo
     */
    public static function resolve(): bool
    {
        // 1. Verificar parámetro URL ?tenant=
        if (isset($_GET['tenant']) && !empty($_GET['tenant'])) {
            $tenantDbName = trim($_GET['tenant']);

            // Validar y cargar tenant
            if (self::loadTenant($tenantDbName)) {
                $_SESSION['tenant_db'] = $tenantDbName;
                self::$currentTenant = $tenantDbName;
                return true;
            }
        }

        // 2. Verificar sesión existente
        if (isset($_SESSION['tenant_db']) && !empty($_SESSION['tenant_db'])) {
            $tenantDbName = $_SESSION['tenant_db'];

            if (self::loadTenant($tenantDbName)) {
                self::$currentTenant = $tenantDbName;
                return true;
            }
        }

        // 3. No hay tenant - modo single database (planilla_prod)
        return false;
    }

    /**
     * Resolver tenant por código de empresa (PRIORIDAD: license_key)
     * Útil para login basado en formulario
     *
     * @param string $code Código de empresa (license_key preferido, RUC, ID, slug)
     * @return bool True si el tenant fue encontrado y cargado
     */
    public static function resolveByCompanyCode(string $code): bool
    {
        if (empty($code)) {
            return false;
        }

        try {
            $master = MasterDatabase::getInstance()->getConnection();

            // Buscar tenant PRIORIZANDO license_key
            // ORDER BY: license_key match primero, luego RUC, ID, slug
            $sql = "SELECT id, company_name, ruc, license_key, db_name, status,license_expires_at,
                       db_host, db_port, db_user, db_pass_enc, db_charset
                FROM tenants
                WHERE (license_key = ? OR ruc = ? OR id = ? OR slug = ?)
                  AND status = 'ACTIVE'
                ORDER BY
                    CASE
                        WHEN license_key = ? THEN 1
                        WHEN ruc = ? THEN 2
                        WHEN id = ? THEN 3
                        ELSE 4
                    END
                LIMIT 1
            ";
            $stmt = $master->prepare($sql);
            $stmt->execute([$code, $code, $code, $code, $code, $code, $code]);
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tenant) {
                error_log("Tenant not found with code: {$code}");
                return false;
            }

            // Cargar tenant encontrado
            self::$tenantInfo = $tenant;
            self::$currentTenant = $tenant['db_name'];

            // Guardar en sesión para persistencia
            $_SESSION['tenant_db'] = $tenant['db_name'];

            $licenseInfo = !empty($tenant['license_key']) ? " | License: {$tenant['license_key']}" : "";
            error_log("Tenant resolved by code '{$code}': {$tenant['company_name']} (DB: {$tenant['db_name']}{$licenseInfo})");
            return true;

        } catch (\Exception $e) {
            error_log("Error resolving tenant by code: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cargar información del tenant desde master database
     *
     * @param string $dbName Nombre de la base de datos tenant
     * @return bool True si el tenant existe y está activo
     */
    private static function loadTenant(string $dbName): bool
    {
        try {
            // ✅ FIX: Si el dbName es la base de datos por defecto, no validar como tenant
            $defaultDb = $_ENV['DB_NAME'] ?? 'planilla_prod';
            if ($dbName === $defaultDb) {
                error_log("Skipping tenant validation for default database: {$dbName}");
                return false; // No es un tenant, es la BD por defecto
            }

            $master = MasterDatabase::getInstance()->getConnection();

            $stmt = $master->prepare("
                SELECT id, company_name, ruc, db_name, status,license_expires_at, license_key,
                       db_host, db_port, db_user, db_pass_enc, db_charset
                FROM tenants
                WHERE db_name = ? AND status = 'ACTIVE'
            ");
            $stmt->execute([$dbName]);
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tenant) {
                error_log("Tenant not found or inactive: {$dbName}");
                return false;
            }

            self::$tenantInfo = $tenant;
            return true;

        } catch (\Exception $e) {
            error_log("Error loading tenant: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener nombre de BD del tenant actual
     *
     * @return string|null
     */
    public static function getCurrentTenant(): ?string
    {
        return self::$currentTenant;
    }

    /**
     * Obtener información completa del tenant actual
     *
     * @return array|null
     */
    public static function getTenantInfo(): ?array
    {
        return self::$tenantInfo;
    }

    /**
     * Verificar si hay un tenant activo
     *
     * @return bool
     */
    public static function hasTenant(): bool
    {
        return self::$currentTenant !== null;
    }

    /**
     * Limpiar tenant de la sesión (logout)
     */
    public static function clear(): void
    {
        unset($_SESSION['tenant_db']);
        self::$currentTenant = null;
        self::$tenantInfo = null;
    }

    /**
     * Obtener nombre de base de datos a usar
     *
     * @return string Nombre de la BD (tenant o default)
     */
    public static function getDatabaseName(): string
    {
        if (self::hasTenant()) {
            return self::$currentTenant;
        }

        // Fallback a base de datos por defecto
        return $_ENV['DB_NAME'] ?? 'planilla_prod';
    }

    /**
     * Obtener configuración de conexión para el tenant actual
     *
     * @return array Configuración de conexión PDO
     */
    public static function getDatabaseConfig(): array
    {
        $config = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['DB_PORT'] ?? 3306),
            'database' => self::getDatabaseName(),
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        ];

        // Si hay tenant con credenciales específicas, usarlas
        if (self::$tenantInfo && !empty(self::$tenantInfo['db_host'])) {
            $config['host'] = self::$tenantInfo['db_host'];
            $config['port'] = (int)self::$tenantInfo['db_port'];
            $config['database'] = self::$tenantInfo['db_name'];
            $config['username'] = self::$tenantInfo['db_user'];

            // Descifrar password si está encriptado
            if (!empty(self::$tenantInfo['db_pass_enc'])) {
                $wizardModel = new \App\Models\WizardModel();
                $config['password'] = $wizardModel->decrypt(self::$tenantInfo['db_pass_enc']);
            }

            if (!empty(self::$tenantInfo['db_charset'])) {
                $config['charset'] = self::$tenantInfo['db_charset'];
            }
        }

        return $config;
    }
}
