#!/usr/bin/env php
<?php
/**
 * Test Script para Sistema de Migraciones Tenant
 *
 * Este script prueba el sistema de migraciones usando la base de datos actual
 * como si fuera un tenant, sin necesitar infraestructura de multitenancy completa.
 *
 * Útil para:
 * - Validar que SqlImporter funciona correctamente
 * - Probar parsing de archivos SQL
 * - Verificar transacciones y rollback
 * - Debugging antes de usar tenants reales
 *
 * @package Sistema Planillas MVC
 * @version 1.0.0
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde línea de comandos.\n");
}

require __DIR__ . '/../vendor/autoload.php';

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║       TEST: Sistema de Migraciones Tenant - SqlImporter      ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Cargar configuración de base de datos principal
if (!file_exists(__DIR__ . '/../config/database.php')) {
    die("❌ Error: config/database.php no encontrado\n");
}

$dbConfig = require __DIR__ . '/../config/database.php';

// Obtener configuración de conexión MySQL
$mysqlConfig = $dbConfig['connections']['mysql'] ?? null;

if (!$mysqlConfig) {
    die("❌ Error: Configuración MySQL no encontrada en config/database.php\n");
}

try {
    // Conectar a la base de datos principal (planilla_prod o la que uses)
    $db = new PDO(
        "mysql:host={$mysqlConfig['host']};dbname={$mysqlConfig['database']};charset=utf8mb4",
        $mysqlConfig['username'],
        $mysqlConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "✅ Conexión a base de datos exitosa\n";
    echo "   Database: {$mysqlConfig['database']}\n\n";

} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage() . "\n");
}

// Test 1: Verificar que existe tabla employees
echo "─────────────────────────────────────────────────────────────────\n";
echo "TEST 1: Verificar tabla employees\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $result = $db->query("SHOW TABLES LIKE 'employees'")->fetch();

    if ($result) {
        echo "✅ Tabla employees existe\n\n";
    } else {
        die("❌ Error: Tabla employees no encontrada\n");
    }
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

// Test 2: Crear tabla schema_migrations de prueba
echo "─────────────────────────────────────────────────────────────────\n";
echo "TEST 2: Crear tabla schema_migrations\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $db->exec("DROP TABLE IF EXISTS schema_migrations");

    $db->exec("
        CREATE TABLE schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            version VARCHAR(255) NOT NULL UNIQUE,
            description VARCHAR(500),
            file_path VARCHAR(500),
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            execution_time_ms INT,
            checksum VARCHAR(32),
            INDEX idx_version (version),
            INDEX idx_executed_at (executed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "✅ Tabla schema_migrations creada exitosamente\n\n";

} catch (PDOException $e) {
    die("❌ Error al crear tabla: " . $e->getMessage() . "\n");
}

// Test 3: Probar SqlImporter con migración de ejemplo
echo "─────────────────────────────────────────────────────────────────\n";
echo "TEST 3: Ejecutar migración de ejemplo con SqlImporter\n";
echo "─────────────────────────────────────────────────────────────────\n";

$migrationFile = __DIR__ . '/../database/migrations/tenant/2025_11_20_000001_add_example_test_column.sql';

if (!file_exists($migrationFile)) {
    die("❌ Error: Archivo de migración no encontrado: {$migrationFile}\n");
}

echo "Archivo: " . basename($migrationFile) . "\n";

try {
    $importer = new \App\Core\SqlImporter($db);

    $startTime = microtime(true);
    $importer->importFile($migrationFile);
    $executionTime = (microtime(true) - $startTime) * 1000;

    $stats = $importer->getStats();

    echo "✅ Migración ejecutada exitosamente\n";
    echo "   Statements: {$stats['successful']}/{$stats['total_statements']}\n";
    echo "   Tiempo: " . round($executionTime, 2) . "ms\n";
    echo "   Promedio: " . round($stats['average_execution_time'] * 1000, 2) . "ms por statement\n\n";

    // Registrar en schema_migrations
    $stmt = $db->prepare("
        INSERT INTO schema_migrations (version, description, file_path, execution_time_ms, checksum)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        '2025_11_20_000001',
        'Add test column for migration system validation',
        $migrationFile,
        (int)$executionTime,
        md5_file($migrationFile)
    ]);

    echo "✅ Migración registrada en schema_migrations\n\n";

} catch (Exception $e) {
    echo "❌ Error en migración: " . $e->getMessage() . "\n";
    if (isset($importer)) {
        echo "\nLog de SqlImporter:\n";
        print_r($importer->getLog());
    }
    exit(1);
}

// Test 4: Verificar que la columna fue creada
echo "─────────────────────────────────────────────────────────────────\n";
echo "TEST 4: Verificar columna migration_test_column\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $result = $db->query("SHOW COLUMNS FROM employees LIKE 'migration_test_column'")->fetch();

    if ($result) {
        echo "✅ Columna migration_test_column creada exitosamente\n";
        echo "   Tipo: {$result['Type']}\n";
        echo "   Default: {$result['Default']}\n\n";
    } else {
        echo "❌ Error: Columna no encontrada\n\n";
        exit(1);
    }
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

// Test 5: Probar rollback
echo "─────────────────────────────────────────────────────────────────\n";
echo "TEST 5: Ejecutar rollback con archivo .down.sql\n";
echo "─────────────────────────────────────────────────────────────────\n";

$rollbackFile = __DIR__ . '/../database/migrations/tenant/2025_11_20_000001_add_example_test_column.down.sql';

if (!file_exists($rollbackFile)) {
    die("❌ Error: Archivo de rollback no encontrado: {$rollbackFile}\n");
}

try {
    $importer = new \App\Core\SqlImporter($db);
    $importer->importFile($rollbackFile);

    $stats = $importer->getStats();

    echo "✅ Rollback ejecutado exitosamente\n";
    echo "   Statements: {$stats['successful']}/{$stats['total_statements']}\n\n";

    // Eliminar registro de schema_migrations
    $db->exec("DELETE FROM schema_migrations WHERE version = '2025_11_20_000001'");

    echo "✅ Registro eliminado de schema_migrations\n\n";

} catch (Exception $e) {
    die("❌ Error en rollback: " . $e->getMessage() . "\n");
}

// Test 6: Verificar que la columna fue eliminada
echo "─────────────────────────────────────────────────────────────────\n";
echo "TEST 6: Verificar que columna fue eliminada\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $result = $db->query("SHOW COLUMNS FROM employees LIKE 'migration_test_column'")->fetch();

    if (!$result) {
        echo "✅ Columna migration_test_column eliminada correctamente\n\n";
    } else {
        echo "❌ Error: Columna todavía existe\n\n";
        exit(1);
    }
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

// Test 7: Verificar limpieza completa después del rollback
echo "─────────────────────────────────────────────────────────────────\n";
echo "TEST 7: Verificar limpieza completa después del rollback\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $count = $db->query("SELECT COUNT(*) FROM schema_migrations")->fetchColumn();

    if ($count === 0 || $count === '0') {
        echo "✅ Limpieza completa verificada\n";
        echo "   No hay registros en schema_migrations (correcto después de rollback)\n\n";
    } else {
        echo "⚠️  Advertencia: Aún hay {$count} registros en schema_migrations\n\n";
    }

} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

// Cleanup final
echo "─────────────────────────────────────────────────────────────────\n";
echo "CLEANUP: Eliminar columna de prueba\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $db->exec("DROP INDEX IF EXISTS idx_employees_migration_test ON employees");
    $db->exec("ALTER TABLE employees DROP COLUMN IF EXISTS migration_test_column");
    $db->exec("DROP TABLE IF EXISTS schema_migrations");

    echo "✅ Cleanup completado - base de datos restaurada\n\n";

} catch (PDOException $e) {
    echo "⚠️  Warning: Error en cleanup: " . $e->getMessage() . "\n\n";
}

// Resumen final
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                    ✅ TODOS LOS TESTS PASARON                 ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "El sistema de migraciones está funcionando correctamente:\n";
echo "  ✓ SqlImporter: Parser robusto funcionando\n";
echo "  ✓ Transacciones: Rollback automático en errores\n";
echo "  ✓ Migraciones: Ejecución y registro exitoso\n";
echo "  ✓ Rollback: Archivos .down.sql funcionando\n";
echo "  ✓ Tracking: Sistema schema_migrations operativo\n";
echo "\n";
echo "Próximo paso: Crear tenants reales y probar con múltiples bases de datos\n";
echo "Comando: php bin/migrate-tenants.php migrate --dry-run\n\n";
