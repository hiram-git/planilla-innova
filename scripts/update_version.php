<?php
/**
 * Script para actualizar automáticamente la versión del sistema
 * Uso: php scripts/update_version.php [--from-roadmap|--from-claude]
 */

// Incluir autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/app/Helpers/VersionHelper.php';

use App\Helpers\VersionHelper;

function showUsage() {
    echo "📋 Script de Actualización de Versión del Sistema\n\n";
    echo "Uso:\n";
    echo "  php scripts/update_version.php [opciones]\n\n";
    echo "Opciones:\n";
    echo "  --from-roadmap    Actualizar desde documentation/ROADMAP.md\n";
    echo "  --from-claude     Actualizar desde CLAUDE.md\n";
    echo "  --current         Mostrar versión actual\n";
    echo "  --info            Mostrar información completa de versión\n";
    echo "  --check           Verificar si hay actualizaciones disponibles\n";
    echo "  --help            Mostrar esta ayuda\n\n";
}

function displayVersionInfo() {
    $versionInfo = VersionHelper::getFullVersionInfo();

    echo "📊 Información de Versión Actual:\n";
    echo "   Versión: " . $versionInfo['version'] . "\n";
    echo "   Codename: " . ($versionInfo['codename'] ?? 'N/A') . "\n";
    echo "   Build: " . ($versionInfo['build'] ?? 'N/A') . "\n";
    echo "   Entorno: " . ($versionInfo['environment'] ?? 'N/A') . "\n";
    echo "   Última actualización: " . ($versionInfo['last_updated'] ?? 'N/A') . "\n";

    if (!empty($versionInfo['changelog'])) {
        echo "\n📋 Historial de Versiones:\n";
        foreach ($versionInfo['changelog'] as $version => $description) {
            echo "   $version: $description\n";
        }
    }
    echo "\n";
}

function checkForUpdates() {
    $updateInfo = VersionHelper::checkForUpdates();

    if ($updateInfo['available']) {
        echo "🔄 Nueva versión disponible:\n";
        echo "   Actual: " . $updateInfo['current'] . "\n";
        echo "   Disponible: " . $updateInfo['latest'] . "\n\n";
        echo "Ejecute 'php scripts/update_version.php --from-roadmap' para actualizar.\n\n";
    } else {
        echo "✅ El sistema está actualizado a la última versión.\n\n";
    }
}

// Parsear argumentos
$options = getopt('', ['from-roadmap', 'from-claude', 'current', 'info', 'check', 'help']);

if (empty($options) || isset($options['help'])) {
    showUsage();
    exit(0);
}

echo "🚀 Script de Actualización de Versión - Sistema de Planillas MVC\n";
echo "================================================================\n\n";

// Mostrar versión actual
if (isset($options['current'])) {
    echo "📌 Versión actual: " . VersionHelper::getCurrentVersion() . "\n\n";
    exit(0);
}

// Mostrar información completa
if (isset($options['info'])) {
    displayVersionInfo();
    exit(0);
}

// Verificar actualizaciones
if (isset($options['check'])) {
    checkForUpdates();
    exit(0);
}

$currentVersion = VersionHelper::getCurrentVersion();
echo "📌 Versión actual: $currentVersion\n\n";

// Actualizar desde ROADMAP
if (isset($options['from-roadmap'])) {
    echo "🔍 Actualizando versión desde ROADMAP.md...\n";

    $newVersion = VersionHelper::updateFromRoadmap();

    if ($newVersion) {
        echo "✅ Versión actualizada exitosamente!\n";
        echo "   Anterior: $currentVersion\n";
        echo "   Nueva: $newVersion\n\n";

        $versionInfo = VersionHelper::getFullVersionInfo();
        if (!empty($versionInfo['codename'])) {
            echo "   Codename: " . $versionInfo['codename'] . "\n";
        }
        echo "   Build: " . $versionInfo['build'] . "\n\n";

        echo "🎯 La versión se ha actualizado en:\n";
        echo "   - Footer del sistema\n";
        echo "   - Menú lateral (sidebar)\n";
        echo "   - Archivo de configuración\n\n";
    } else {
        echo "❌ No se pudo extraer la versión del ROADMAP.md\n";
        echo "   Verifique que el archivo exista y contenga una línea como:\n";
        echo "   **Versión**: X.Y.Z\n\n";
        exit(1);
    }
}

// Actualizar desde CLAUDE.md
if (isset($options['from-claude'])) {
    echo "🔍 Actualizando versión desde CLAUDE.md...\n";

    $newVersion = VersionHelper::updateFromClaudeMd();

    if ($newVersion) {
        echo "✅ Versión actualizada exitosamente!\n";
        echo "   Anterior: $currentVersion\n";
        echo "   Nueva: $newVersion\n\n";

        $versionInfo = VersionHelper::getFullVersionInfo();
        if (!empty($versionInfo['codename'])) {
            echo "   Codename: " . $versionInfo['codename'] . "\n";
        }
        echo "   Build: " . $versionInfo['build'] . "\n\n";

        echo "🎯 La versión se ha actualizado en:\n";
        echo "   - Footer del sistema\n";
        echo "   - Menú lateral (sidebar)\n";
        echo "   - Archivo de configuración\n\n";
    } else {
        echo "❌ No se pudo extraer la versión del CLAUDE.md\n";
        echo "   Verifique que el archivo exista y contenga una línea como:\n";
        echo "   **Versión**: X.Y.Z - Descripción\n\n";
        exit(1);
    }
}

echo "✨ Proceso completado.\n";
echo "🌐 Recargue el navegador para ver los cambios en la interfaz.\n\n";