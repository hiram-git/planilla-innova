<?php
/**
 * Script Manual: Corregir AUTO_INCREMENT de planilla_cabecera
 *
 * Este script corrige el problema de AUTO_INCREMENT en la tabla planilla_cabecera
 * que estaba configurado en 100 en lugar de 1.
 *
 * Uso:
 * php scripts/fix_planilla_autoincrement.php
 *
 * SEGURO: No elimina datos, solo ajusta el AUTO_INCREMENT
 */

// Prevenir ejecución desde navegador
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde línea de comandos');
}

// Cargar autoload de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
} else {
    \App\Core\Config::load();
}

// Inicializar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Core\MasterDatabase;
use App\Core\TenantResolver;
use App\Core\Database;

// Banner
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   CORRECCIÓN AUTO_INCREMENT DE PLANILLA_CABECERA          ║\n";
echo "║   Fecha: " . date('Y-m-d H:i:s') . "                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

/**
 * Cambiar de tenant
 */
function switchTenant(string $dbName): void
{
    TenantResolver::clear();
    Database::resetInstance();
    $_SESSION['tenant_db'] = $dbName;
}

/**
 * Obtener tenants activos
 */
function getActiveTenants(): array
{
    try {
        $master = MasterDatabase::getInstance()->getConnection();
        $stmt = $master->query("SELECT db_name FROM tenants WHERE status = 'ACTIVE'");
        $tenants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $tenants ?: [];
    } catch (\Exception $e) {
        echo "⚠️  No se pudieron obtener tenants: {$e->getMessage()}\n";
        return [];
    }
}

/**
 * Corregir AUTO_INCREMENT de planilla_cabecera
 */
function fixAutoIncrement(PDO $db, string $tenantName): array
{
    try {
        // Obtener MAX ID actual
        $stmt = $db->query("SELECT COALESCE(MAX(id), 0) AS max_id FROM planilla_cabecera");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $maxId = (int)$result['max_id'];
        $nextId = $maxId + 1;

        // Ajustar AUTO_INCREMENT
        $sql = "ALTER TABLE planilla_cabecera AUTO_INCREMENT = {$nextId}";
        $db->exec($sql);

        return [
            'success' => true,
            'max_id' => $maxId,
            'new_autoincrement' => $nextId,
            'message' => $maxId === 0
                ? 'Tabla vacía - AUTO_INCREMENT reseteado a 1'
                : "Tabla con datos - AUTO_INCREMENT ajustado a {$nextId}"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Obtener lista de tenants
$tenants = getActiveTenants();
if (empty($tenants)) {
    $tenants = [$_ENV['DB_NAME'] ?? 'planilla_prod'];
}
$tenants[] = 'planilla_prod';
$tenants = array_values(array_unique(array_filter($tenants)));

$totalFixed = 0;
$totalErrors = 0;

foreach ($tenants as $tenantDb) {
    if (empty($tenantDb)) {
        continue;
    }

    echo "\n══════════════════════════════════════════════════════\n";
    echo "▶️  Tenant: {$tenantDb}\n";
    echo "══════════════════════════════════════════════════════\n";

    try {
        switchTenant($tenantDb);
        $db = Database::getInstance()->getConnection();

        $result = fixAutoIncrement($db, $tenantDb);

        if ($result['success']) {
            echo "✅ Corrección exitosa:\n";
            echo "  - MAX ID actual: {$result['max_id']}\n";
            echo "  - Nuevo AUTO_INCREMENT: {$result['new_autoincrement']}\n";
            echo "  - Resultado: {$result['message']}\n";
            $totalFixed++;
        } else {
            echo "❌ Error: {$result['error']}\n";
            $totalErrors++;
        }

    } catch (Exception $e) {
        echo "❌ ERROR: {$e->getMessage()}\n";
        $totalErrors++;
    }
}

// Resumen final
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║   CORRECCIÓN COMPLETADA                                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "✓ Tenants corregidos: {$totalFixed}\n";
echo "✗ Errores: {$totalErrors}\n\n";

exit($totalErrors > 0 ? 1 : 0);
