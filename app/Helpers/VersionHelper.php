<?php

namespace App\Helpers;

class VersionHelper
{
    private static $versionConfig = null;

    /**
     * Obtener la versión actual del sistema
     */
    public static function getCurrentVersion()
    {
        $config = self::getVersionConfig();
        return $config['version'] ?? '1.0.0';
    }

    /**
     * Obtener el codename de la versión
     */
    public static function getCodename()
    {
        $config = self::getVersionConfig();
        return $config['codename'] ?? '';
    }

    /**
     * Obtener información completa de la versión
     */
    public static function getFullVersionInfo()
    {
        return self::getVersionConfig();
    }

    /**
     * Obtener versión con codename
     */
    public static function getVersionWithCodename()
    {
        $version = self::getCurrentVersion();
        $codename = self::getCodename();

        return $codename ? "$version - $codename" : $version;
    }

    /**
     * Cargar configuración de versión
     */
    private static function getVersionConfig()
    {
        if (self::$versionConfig === null) {
            $configPath = dirname(dirname(__DIR__)) . '/config/version.php';

            if (file_exists($configPath)) {
                self::$versionConfig = require $configPath;
            } else {
                self::$versionConfig = [
                    'version' => '1.0.0',
                    'codename' => 'Base System',
                    'build' => date('Y-m-d'),
                    'environment' => 'development'
                ];
            }
        }

        return self::$versionConfig;
    }

    /**
     * Actualizar versión desde ROADMAP.md
     */
    public static function updateFromRoadmap()
    {
        $roadmapPath = dirname(dirname(__DIR__)) . '/documentation/ROADMAP.md';

        if (!file_exists($roadmapPath)) {
            return false;
        }

        $content = file_get_contents($roadmapPath);

        // Extraer versión del ROADMAP usando diferentes patrones
        $patterns = [
            '/\*\*Versión\*\*:\s*([0-9.]+(?:\s*-[^*]+)?)/i',
            '/Versión.*?([0-9.]+)/i',
            '/V([0-9.]+)/i'
        ];

        $version = null;
        $codename = '';

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $fullMatch = trim($matches[1]);

                // Separar versión y codename si están juntos
                if (preg_match('/([0-9.]+)\s*-\s*(.+)/', $fullMatch, $parts)) {
                    $version = $parts[1];
                    $codename = trim($parts[2]);
                } else {
                    $version = $fullMatch;
                }
                break;
            }
        }

        // Extraer codename adicional si no se encontró
        if ($version && !$codename) {
            if (preg_match('/Sistema\s+([^*\n]+)/i', $content, $matches)) {
                $codename = trim($matches[1]);
            }
        }

        if ($version) {
            return self::updateVersionFile($version, $codename);
        }

        return false;
    }

    /**
     * Actualizar versión desde CLAUDE.md
     */
    public static function updateFromClaudeMd()
    {
        $claudePath = dirname(dirname(__DIR__)) . '/CLAUDE.md';

        if (!file_exists($claudePath)) {
            return false;
        }

        $content = file_get_contents($claudePath);

        // Buscar versión en CLAUDE.md
        if (preg_match('/\*\*Versión\*\*:\s*([0-9.]+)\s*-\s*([^*\n]+)/i', $content, $matches)) {
            $version = trim($matches[1]);
            $codename = trim($matches[2]);

            return self::updateVersionFile($version, $codename);
        }

        return false;
    }

    /**
     * Actualizar archivo de versión
     */
    private static function updateVersionFile($version, $codename = '')
    {
        $currentConfig = self::getVersionConfig();

        $newConfig = [
            'version' => $version,
            'codename' => $codename ?: $currentConfig['codename'],
            'build' => date('Y-m-d'),
            'environment' => $currentConfig['environment'] ?? 'production',
            'last_updated' => date('Y-m-d H:i:s'),
            'changelog' => $currentConfig['changelog'] ?? []
        ];

        // Agregar entrada al changelog si es una nueva versión
        if ($version !== $currentConfig['version']) {
            $newConfig['changelog'][$version] = $codename ?: "Versión $version";
        }

        $configPath = dirname(dirname(__DIR__)) . '/config/version.php';
        $content = "<?php\n\nreturn " . var_export($newConfig, true) . ";\n";

        $result = file_put_contents($configPath, $content);

        if ($result !== false) {
            // Limpiar cache
            self::$versionConfig = null;
            return $version;
        }

        return false;
    }

    /**
     * Obtener último cambio del changelog
     */
    public static function getLatestChange()
    {
        $config = self::getVersionConfig();
        $changelog = $config['changelog'] ?? [];

        if (empty($changelog)) {
            return null;
        }

        $latestVersion = array_keys($changelog)[0];
        return [
            'version' => $latestVersion,
            'description' => $changelog[$latestVersion]
        ];
    }

    /**
     * Verificar si hay una nueva versión disponible
     */
    public static function checkForUpdates()
    {
        $currentVersion = self::getCurrentVersion();
        $roadmapVersion = self::extractVersionFromRoadmap();

        if ($roadmapVersion && version_compare($roadmapVersion, $currentVersion, '>')) {
            return [
                'available' => true,
                'current' => $currentVersion,
                'latest' => $roadmapVersion
            ];
        }

        return ['available' => false];
    }

    /**
     * Extraer versión del ROADMAP sin actualizar
     */
    private static function extractVersionFromRoadmap()
    {
        $roadmapPath = dirname(dirname(__DIR__)) . '/documentation/ROADMAP.md';

        if (!file_exists($roadmapPath)) {
            return null;
        }

        $content = file_get_contents($roadmapPath);

        if (preg_match('/\*\*Versión\*\*:\s*([0-9.]+)/i', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }
}