#!/usr/bin/env php
<?php
/**
 * Script de Sincronización de Licencias Pendientes
 *
 * Este script sincroniza con el servidor remoto las licencias que fueron
 * generadas en modo offline cuando el servidor no estaba disponible.
 *
 * Uso:
 *   php scripts/sync_pending_licenses.php
 *   php scripts/sync_pending_licenses.php --verbose
 *   php scripts/sync_pending_licenses.php --license=PINN1234567890
 *
 * @version 1.0.0
 * @date 2025-11-24
 */

// Cargar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Services\LicenseGenerator;
use App\Core\MasterDatabase;

// Configuración
$verbose = in_array('--verbose', $argv) || in_array('-v', $argv);
$specificLicense = null;

// Buscar parámetro --license
foreach ($argv as $arg) {
    if (strpos($arg, '--license=') === 0) {
        $specificLicense = substr($arg, 10);
        break;
    }
}

// Color output helpers
function colorOutput($text, $color = 'default') {
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'default' => "\033[0m"
    ];

    return $colors[$color] . $text . $colors['default'];
}

function printHeader($text) {
    echo "\n" . str_repeat('=', 70) . "\n";
    echo "  " . colorOutput($text, 'blue') . "\n";
    echo str_repeat('=', 70) . "\n\n";
}

function printSuccess($text) {
    echo colorOutput("✓ ", 'green') . $text . "\n";
}

function printError($text) {
    echo colorOutput("✗ ", 'red') . $text . "\n";
}

function printWarning($text) {
    echo colorOutput("⚠ ", 'yellow') . $text . "\n";
}

function printInfo($text) {
    echo colorOutput("ℹ ", 'blue') . $text . "\n";
}

// Inicio del script
printHeader("Sincronización de Licencias Pendientes");

try {
    // Conectar a BD master
    $masterDb = MasterDatabase::getInstance()->getConnection();

    if (!$masterDb) {
        throw new Exception("No se pudo conectar a la base de datos master");
    }

    printSuccess("Conectado a base de datos master");

    // Construir query
    $query = "SELECT id, company_name, ruc, admin_email, license_key, license_expires_at,
                     license_sync_error, license_last_sync_attempt
              FROM tenants
              WHERE license_sync_pending = 1";

    $params = [];
    if ($specificLicense) {
        $query .= " AND license_key = ?";
        $params[] = $specificLicense;
        printInfo("Buscando licencia específica: {$specificLicense}");
    } else {
        printInfo("Buscando todas las licencias pendientes...");
    }

    // Ejecutar query
    $stmt = $masterDb->prepare($query);
    if ($params) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }

    $pendingTenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pendingTenants)) {
        printWarning("No hay licencias pendientes de sincronización");
        exit(0);
    }

    printInfo("Encontradas " . count($pendingTenants) . " licencia(s) pendiente(s)\n");

    // Inicializar generador de licencias
    $licenseGenerator = new LicenseGenerator();

    $stats = [
        'total' => count($pendingTenants),
        'success' => 0,
        'failed' => 0,
        'skipped' => 0
    ];

    // Procesar cada licencia pendiente
    foreach ($pendingTenants as $tenant) {
        echo str_repeat('-', 70) . "\n";
        echo "Procesando: " . colorOutput($tenant['company_name'], 'blue') . "\n";
        echo "Licencia: " . colorOutput($tenant['license_key'], 'yellow') . "\n";

        if ($verbose) {
            echo "RUC: {$tenant['ruc']}\n";
            echo "Email: {$tenant['admin_email']}\n";
            if ($tenant['license_last_sync_attempt']) {
                echo "Último intento: {$tenant['license_last_sync_attempt']}\n";
            }
            if ($tenant['license_sync_error']) {
                echo "Error previo: " . colorOutput($tenant['license_sync_error'], 'red') . "\n";
            }
        }

        // Preparar datos para registro
        $licenseData = [
            'license' => $tenant['license_key'],
            'ruc' => $tenant['ruc'],
            'company_name' => $tenant['company_name'],
            'buyer_name' => $tenant['company_name'], // Usar nombre empresa como comprador
            'email' => $tenant['admin_email'],
            'phone' => '',
            'country' => 'Panama',
            'expiration_date' => date('Y-m-d', strtotime($tenant['license_expires_at'])),
            'first_activation' => date('Y-m-d', strtotime($tenant['license_expires_at'] . ' -30 days')),
            'final_user' => $tenant['company_name']
        ];

        // Intentar registrar
        $result = $licenseGenerator->retryRegistration($licenseData);

        // Actualizar registro en BD
        $updateStmt = $masterDb->prepare("
            UPDATE tenants
            SET license_sync_pending = ?,
                license_sync_error = ?,
                license_last_sync_attempt = NOW(),
                license_status = ?
            WHERE id = ?
        ");

        if ($result['success']) {
            // Registro exitoso
            $updateStmt->execute([0, null, 'ACTIVE', $tenant['id']]);
            printSuccess("Sincronización exitosa");
            $stats['success']++;
        } else {
            // Registro fallido - guardar error
            $updateStmt->execute([1, $result['message'], 'PENDING_SYNC', $tenant['id']]);
            printError("Sincronización fallida: {$result['message']}");
            $stats['failed']++;
        }

        echo "\n";
    }

    // Resumen final
    printHeader("Resumen de Sincronización");

    echo "Total procesadas: " . colorOutput($stats['total'], 'blue') . "\n";
    echo "Exitosas:         " . colorOutput($stats['success'], 'green') . "\n";
    echo "Fallidas:         " . colorOutput($stats['failed'], 'red') . "\n";

    if ($stats['success'] > 0) {
        echo "\n";
        printSuccess("Se sincronizaron {$stats['success']} licencia(s) exitosamente");
    }

    if ($stats['failed'] > 0) {
        echo "\n";
        printWarning("Hay {$stats['failed']} licencia(s) que no se pudieron sincronizar");
        printInfo("Ejecute el script nuevamente cuando el servidor esté disponible");
    }

    echo "\n";

} catch (Exception $e) {
    printError("Error fatal: " . $e->getMessage());
    if ($verbose) {
        echo "\n" . $e->getTraceAsString() . "\n";
    }
    exit(1);
}
