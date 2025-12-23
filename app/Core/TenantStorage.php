<?php

namespace App\Core;

class TenantStorage
{
    public static function getPublicImageUrl(?string $path): string
    {
        if (!$path) {
            return '';
        }

        $clean = ltrim($path, '/');
        if (preg_match('#^https?://#i', $clean)) {
            return $clean;
        }

        if (strpos($clean, 'images/') === 0) {
            return UrlHelper::asset($clean);
        }

        if (strpos($clean, 'tenants/') === 0) {
            return UrlHelper::asset('images/' . $clean);
        }

        $tenantSubdir = self::getTenantSubdir();
        $tenantPath = __DIR__ . '/../../images/' . $tenantSubdir . '/' . $clean;
        if (is_file($tenantPath)) {
            return UrlHelper::asset('images/' . $tenantSubdir . '/' . $clean);
        }

        $legacyPath = __DIR__ . '/../../images/' . $clean;
        if (is_file($legacyPath)) {
            return UrlHelper::asset('images/' . $clean);
        }

        return UrlHelper::asset('images/' . $clean);
    }

    public static function getTenantKey(?int $tenantId = null, ?string $tenantDb = null): string
    {
        $key = null;

        if ($tenantId) {
            $key = (string)$tenantId;
        }

        if (!$key && TenantResolver::hasTenant()) {
            $info = TenantResolver::getTenantInfo();
            if ($info) {
                $key = $info['id'] ?? null;
                $tenantDb = $info['db_name'] ?? $tenantDb;
            }
        }

        if (!$key && isset($_SESSION['tenant_id'])) {
            $key = (string)$_SESSION['tenant_id'];
        }

        if (!$key && $tenantDb) {
            $key = $tenantDb;
        }

        if (!$key && isset($_SESSION['tenant_db'])) {
            $key = (string)$_SESSION['tenant_db'];
        }

        if (!$key) {
            $key = 'default';
        }

        return preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$key);
    }

    public static function getTenantSubdir(?int $tenantId = null, ?string $tenantDb = null): string
    {
        return 'tenants/' . self::getTenantKey($tenantId, $tenantDb);
    }

    public static function ensureDirectory(string $path): void
    {
        $mode = self::getStorageMode();
        if (!is_dir($path)) {
            if (!mkdir($path, $mode, true) && !is_dir($path)) {
                throw new \RuntimeException("No se pudo crear el directorio: {$path}");
            }
        }

        self::applyOwnership($path);
        self::applyPermissions($path, $mode);
    }

    public static function getImageDirectory(?int $tenantId = null, ?string $tenantDb = null): string
    {
        return __DIR__ . '/../../images/' . self::getTenantSubdir($tenantId, $tenantDb) . '/';
    }

    public static function getLogoDirectory(?int $tenantId = null, ?string $tenantDb = null): string
    {
        return __DIR__ . '/../../images/logos/' . self::getTenantSubdir($tenantId, $tenantDb) . '/';
    }

    public static function getLogDirectory(?int $tenantId = null, ?string $tenantDb = null): string
    {
        return __DIR__ . '/../../storage/tenants/' . self::getTenantKey($tenantId, $tenantDb) . '/logs/';
    }

    public static function initializeTenantStorage(int $tenantId, string $tenantDb): void
    {
        $tenantKey = self::getTenantKey($tenantId, $tenantDb);
        $tenantSubdir = self::getTenantSubdir($tenantId, $tenantDb);

        $paths = [
            __DIR__ . '/../../images/' . $tenantSubdir . '/',
            __DIR__ . '/../../images/logos/' . $tenantSubdir . '/',
            __DIR__ . '/../../storage/tenants/' . $tenantKey . '/logs/',
            __DIR__ . '/../../storage/tenants/' . $tenantKey . '/files/',
        ];

        foreach ($paths as $path) {
            self::ensureDirectory($path);
        }
    }

    private static function applyOwnership(string $path): void
    {
        if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
            return;
        }

        $owner = $_ENV['TENANT_STORAGE_OWNER'] ?? 'www-data';
        $group = $_ENV['TENANT_STORAGE_GROUP'] ?? 'www-data';

        if (function_exists('chown') && function_exists('chgrp')) {
            self::chownRecursive($path, $owner, $group);
            return;
        }

        if (function_exists('exec')) {
            $target = escapeshellarg($path);
            $ownerGroup = escapeshellarg($owner . ':' . $group);
            exec("chown -R {$ownerGroup} {$target} 2>/dev/null");
        }
    }

    private static function applyPermissions(string $path, int $mode): void
    {
        if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
            return;
        }

        if (function_exists('chmod')) {
            self::chmodRecursive($path, $mode);
            return;
        }

        if (function_exists('exec')) {
            $target = escapeshellarg($path);
            $modeString = escapeshellarg(decoct($mode));
            exec("chmod -R {$modeString} {$target} 2>/dev/null");
        }
    }

    private static function chmodRecursive(string $path, int $mode): void
    {
        @chmod($path, $mode);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            @chmod($item->getPathname(), $mode);
        }
    }

    private static function getStorageMode(): int
    {
        $mode = $_ENV['TENANT_STORAGE_MODE'] ?? '0777';
        $mode = trim((string)$mode);

        if ($mode === '') {
            return 0777;
        }

        $parsed = octdec($mode);
        if ($parsed <= 0) {
            return 0777;
        }

        return $parsed;
    }

    private static function chownRecursive(string $path, string $owner, string $group): void
    {
        @chown($path, $owner);
        @chgrp($path, $group);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            @chown($itemPath, $owner);
            @chgrp($itemPath, $group);
        }
    }
}
