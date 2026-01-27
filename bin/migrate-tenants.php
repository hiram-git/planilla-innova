#!/usr/bin/env php
<?php
/**
 * Tenant Migration CLI Script
 *
 * Script de línea de comandos para gestionar migraciones en bases de datos tenant.
 *
 * Comandos disponibles:
 *   migrate [--dry-run]  - Ejecutar migraciones pendientes en todos los tenants
 *   status [database]    - Ver estado de migraciones (todos o uno específico)
 *   rollback <database> [steps] - Revertir migraciones en un tenant
 *
 * Uso:
 *   php bin/migrate-tenants.php migrate
 *   php bin/migrate-tenants.php migrate --dry-run
 *   php bin/migrate-tenants.php status
 *   php bin/migrate-tenants.php status planilla_tenant_abc123
 *   php bin/migrate-tenants.php rollback planilla_tenant_abc123 1
 *
 * @package Sistema Planillas MVC
 * @version 1.0.0
 */

// Asegurar que se ejecuta desde CLI
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde línea de comandos.\n");
}

// Autoload
require __DIR__ . '/../vendor/autoload.php';

// Cargar configuración de base de datos master
$masterDbConfig = require __DIR__ . '/../config/master_database.php';

// Obtener el driver por defecto
$driver = $masterDbConfig['default'] ?? 'mysql';

// Obtener la configuración específica del driver
$masterConfig = $masterDbConfig['connections'][$driver] ?? $masterDbConfig['connections']['mysql'];

// Validar configuración
if (empty($masterConfig['host']) || empty($masterConfig['database'])) {
    die("Error: Configuración de master database incompleta en config/master_database.php\n");
}

try {
    // Conectar a master database con driver dinámico
    if ($driver === 'pgsql') {
        $dsn = "pgsql:host={$masterConfig['host']};port={$masterConfig['port']};dbname={$masterConfig['database']}";
        if (!empty($masterConfig['schema'])) {
            $dsn .= ";options=--search_path={$masterConfig['schema']}";
        }
    } else {
        // MySQL
        $dsn = "mysql:host={$masterConfig['host']};dbname={$masterConfig['database']};charset={$masterConfig['charset']}";
    }

    $masterDb = new PDO(
        $dsn,
        $masterConfig['username'],
        $masterConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {
    die("Error: No se pudo conectar a master database ({$driver}): " . $e->getMessage() . "\n");
}

// Crear instancia del sistema de migraciones
$migrationSystem = new \App\Core\TenantMigrationSystem($masterDb, $masterConfig);

// Parsear argumentos
$command = $argv[1] ?? null;

// Mostrar ayuda si no hay comando
if (!$command) {
    showHelp();
    exit(0);
}

// Ejecutar comando
try {
    switch ($command) {
        case 'migrate':
            // Verificar flag --dry-run
            $dryRun = in_array('--dry-run', $argv);

            if ($dryRun) {
                echo "🔍 DRY-RUN MODE: Preview only, no changes will be made\n";
            }

            $results = $migrationSystem->migrateAll($dryRun);

            // Exit code basado en resultados
            if (!empty($results['failed'])) {
                exit(1); // Error si alguno falló
            }

            exit(0);

        case 'status':
            // Verificar si se especificó un tenant
            $tenantDbName = $argv[2] ?? null;

            $migrationSystem->status($tenantDbName);
            exit(0);

        case 'rollback':
            // Verificar que se especificó el tenant
            $tenantDbName = $argv[2] ?? null;

            if (!$tenantDbName) {
                echo "❌ Error: Debe especificar el nombre de la base de datos tenant\n\n";
                echo "Uso: php bin/migrate-tenants.php rollback <database_name> [steps]\n";
                echo "Ejemplo: php bin/migrate-tenants.php rollback planilla_tenant_abc123 1\n\n";
                exit(1);
            }

            // Número de steps (default: 1)
            $steps = isset($argv[3]) ? (int)$argv[3] : 1;

            if ($steps < 1) {
                echo "❌ Error: El número de steps debe ser mayor a 0\n";
                exit(1);
            }

            $migrationSystem->rollback($tenantDbName, $steps);
            exit(0);

        case 'help':
        case '--help':
        case '-h':
            showHelp();
            exit(0);

        default:
            echo "❌ Error: Comando desconocido '{$command}'\n\n";
            showHelp();
            exit(1);
    }

} catch (Exception $e) {
    echo "\n❌ ERROR FATAL: {$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

/**
 * Mostrar ayuda del script
 */
function showHelp(): void
{
    echo <<<HELP

╔═══════════════════════════════════════════════════════════════╗
║         TENANT MIGRATION SYSTEM - Sistema Planillas MVC      ║
╚═══════════════════════════════════════════════════════════════╝

COMANDOS DISPONIBLES:

  migrate [--dry-run]
    Ejecuta todas las migraciones pendientes en todos los tenants activos.

    Flags:
      --dry-run    Preview de migraciones sin ejecutar (recomendado)

    Ejemplos:
      php bin/migrate-tenants.php migrate --dry-run
      php bin/migrate-tenants.php migrate

  status [database_name]
    Muestra el estado de migraciones de todos los tenants o uno específico.

    Ejemplos:
      php bin/migrate-tenants.php status
      php bin/migrate-tenants.php status planilla_tenant_abc123

  rollback <database_name> [steps]
    Revierte las últimas N migraciones de un tenant específico.

    Parámetros:
      database_name    Nombre de la base de datos tenant (requerido)
      steps            Número de migraciones a revertir (default: 1)

    Ejemplos:
      php bin/migrate-tenants.php rollback planilla_tenant_abc123
      php bin/migrate-tenants.php rollback planilla_tenant_abc123 2

  help
    Muestra esta ayuda.

═══════════════════════════════════════════════════════════════

NOTAS:

  • Las migraciones se leen desde: database/migrations/tenant/
  • Los logs de errores se guardan en: storage/logs/tenant_migrations_*.log
  • Cada migración debe tener un archivo .down.sql para rollback
  • Se recomienda siempre ejecutar --dry-run antes de migrate

FORMATO DE ARCHIVOS DE MIGRACIÓN:

  Archivo UP (ejecutar):
    database/migrations/tenant/2025_11_20_000001_add_column.sql

    -- version: 2025_11_20_000001
    -- description: Add overtime_eligible column to employees
    ALTER TABLE employees ADD COLUMN overtime_eligible TINYINT(1) DEFAULT 1;

  Archivo DOWN (rollback):
    database/migrations/tenant/2025_11_20_000001_add_column.down.sql

    ALTER TABLE employees DROP COLUMN overtime_eligible;

═══════════════════════════════════════════════════════════════


HELP;
}
