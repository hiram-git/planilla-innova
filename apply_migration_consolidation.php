<?php
/**
 * Script para aplicar la consolidación de migraciones
 * CUIDADO: Este script reemplaza el sistema de migraciones actual
 */

class MigrationConsolidationApplier
{
    private $backupDir = 'database/migrations_backup_' . date('Y_m_d_H_i');
    private $consolidatedDir = 'database/migrations_consolidated';
    private $migrationsDir = 'database/migrations';

    public function apply()
    {
        echo "=== APLICAR CONSOLIDACIÓN DE MIGRACIONES ===\n\n";

        echo "⚠️  ADVERTENCIA: Este proceso reemplazará el sistema actual de migraciones.\n";
        echo "📁 Backup será creado en: {$this->backupDir}\n\n";

        $this->confirmAction();
        $this->createBackup();
        $this->applyConsolidation();
        $this->cleanupOldDirectories();
        $this->runMigrationStatus();

        echo "\n✅ CONSOLIDACIÓN APLICADA EXITOSAMENTE\n\n";
        $this->printNextSteps();
    }

    private function confirmAction()
    {
        echo "¿Continuar con la consolidación? (escriba 'SI' para confirmar): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);

        if (trim(strtoupper($line)) !== 'SI') {
            echo "❌ Operación cancelada por el usuario\n";
            exit(1);
        }
    }

    private function createBackup()
    {
        echo "📦 Creando backup del sistema actual...\n";

        // Crear directorio backup
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        // Backup de database/migrations actual
        if (is_dir($this->migrationsDir)) {
            $this->copyDirectory($this->migrationsDir, $this->backupDir . '/migrations');
        }

        // Backup de databases/
        if (is_dir('databases')) {
            $this->copyDirectory('databases', $this->backupDir . '/databases_legacy');
        }

        // Backup de archivos raíz database/
        $rootFiles = glob('database/*.sql') ?: [];
        foreach ($rootFiles as $file) {
            copy($file, $this->backupDir . '/' . basename($file));
        }

        echo "   ✅ Backup creado en: {$this->backupDir}\n";
    }

    private function copyDirectory($src, $dst)
    {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    private function applyConsolidation()
    {
        echo "🔄 Aplicando consolidación...\n";

        // Limpiar directorio migrations actual (conservar migration_runner.php)
        $this->cleanMigrationsDirectory();

        // Copiar migraciones consolidadas
        $consolidatedFiles = glob($this->consolidatedDir . '/*.sql') ?: [];

        foreach ($consolidatedFiles as $file) {
            $destFile = $this->migrationsDir . '/' . basename($file);
            if (copy($file, $destFile)) {
                echo "   ✅ " . basename($file) . "\n";
            } else {
                echo "   ❌ Error copiando: " . basename($file) . "\n";
            }
        }

        echo "   📋 Migraciones consolidadas: " . count($consolidatedFiles) . "\n";
    }

    private function cleanMigrationsDirectory()
    {
        $files = glob($this->migrationsDir . '/*.sql') ?: [];

        foreach ($files as $file) {
            unlink($file);
        }

        echo "   🧹 Directorio migrations limpio\n";
    }

    private function cleanupOldDirectories()
    {
        echo "🧹 Limpieza de directorios legacy...\n";

        // Renombrar databases/ a databases_legacy/
        if (is_dir('databases') && !is_dir('databases_legacy')) {
            rename('databases', 'databases_legacy');
            echo "   📁 databases/ → databases_legacy/\n";
        }

        // Remover consolidation temp directory
        if (is_dir($this->consolidatedDir)) {
            $this->removeDirectory($this->consolidatedDir);
            echo "   🗑️  Removido: {$this->consolidatedDir}\n";
        }
    }

    private function removeDirectory($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    private function runMigrationStatus()
    {
        echo "📊 Estado del sistema de migraciones:\n\n";

        // Ejecutar migration_runner para ver status
        if (file_exists($this->migrationsDir . '/migration_runner.php')) {
            $output = shell_exec("php {$this->migrationsDir}/migration_runner.php --status 2>&1");
            echo $output . "\n";
        }
    }

    private function printNextSteps()
    {
        echo "📋 PRÓXIMOS PASOS:\n\n";

        echo "1. **Verificar status**:\n";
        echo "   php database/migrations/migration_runner.php --status\n\n";

        echo "2. **Dry run (recomendado)**:\n";
        echo "   php database/migrations/migration_runner.php --dry-run\n\n";

        echo "3. **Ejecutar migraciones** (cuando estés listo):\n";
        echo "   php database/migrations/migration_runner.php\n\n";

        echo "4. **Documentación**:\n";
        echo "   - Ver: database/MIGRATION_GUIDE.md\n";
        echo "   - Backup en: {$this->backupDir}\n\n";

        echo "5. **Estructura final**:\n";
        echo "   database/\n";
        echo "   ├── migrations/           ← 50 migraciones consolidadas ordenadas\n";
        echo "   ├── MIGRATION_GUIDE.md    ← Guía completa\n";
        echo "   └── backups/              ← Respaldos BD\n";
        echo "   databases_legacy/         ← Archivos legacy (backup)\n\n";

        echo "⚠️  IMPORTANTE:\n";
        echo "- Todas las migraciones están ordenadas cronológicamente\n";
        echo "- Sistema detecta automáticamente cuáles ejecutar\n";
        echo "- Siempre usar --dry-run primero en producción\n";
    }
}

// Ejecutar si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $applier = new MigrationConsolidationApplier();
    $applier->apply();
}
?>