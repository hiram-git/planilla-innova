<?php
/**
 * Script para verificar errores de sincronización de marcaciones
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Core/Bootstrap.php';

use App\Core\Bootstrap;
use App\Core\Database;

// Inicializar sistema
Bootstrap::init();

$db = Database::getInstance();

echo "=== ANÁLISIS DE ERRORES SINCRONIZACIÓN MARCACIONES ===\n\n";

// Obtener último sync log
$lastSync = $db->find("SELECT * FROM attendance_sync_log ORDER BY id DESC LIMIT 1");

if (!$lastSync) {
    echo "❌ No se encontraron registros de sincronización\n";
    exit;
}

echo "📊 ÚLTIMO SYNC:\n";
echo "   ID: {$lastSync['id']}\n";
echo "   Tipo: {$lastSync['sync_type']}\n";
echo "   Fecha: {$lastSync['start_time']}\n";
echo "   Estado: {$lastSync['status']}\n\n";

echo "📈 ESTADÍSTICAS:\n";
echo "   ✅ Obtenidos: {$lastSync['records_fetched']}\n";
echo "   ✅ Insertados: {$lastSync['records_inserted']}\n";
echo "   ✅ Actualizados: {$lastSync['records_updated']}\n";
echo "   ⏭️  Omitidos: {$lastSync['records_skipped']}\n";
echo "   ❌ Errores: {$lastSync['errors_count']}\n\n";

if ($lastSync['errors_count'] > 0 && !empty($lastSync['error_details'])) {
    echo "🔍 DETALLES DE ERRORES:\n";
    echo str_repeat('-', 70) . "\n";

    $errors = json_decode($lastSync['error_details'], true);

    if (is_array($errors) && count($errors) > 0) {
        foreach ($errors as $index => $error) {
            echo ($index + 1) . ". " . $error . "\n";
        }
    } else {
        echo "   (No se pudieron decodificar los errores)\n";
        echo "   Raw: " . $lastSync['error_details'] . "\n";
    }
    echo str_repeat('-', 70) . "\n\n";
}

// Analizar registros raw más recientes
echo "📋 ÚLTIMOS 5 REGISTROS RAW:\n";
echo str_repeat('-', 70) . "\n";

$rawRecords = $db->findAll(
    "SELECT id, external_id, api_provider, entity_type, processed, received_at,
            JSON_EXTRACT(raw_json, '$.employee_email') as email,
            JSON_EXTRACT(raw_json, '$.timestamp') as timestamp
     FROM attendance_raw_data
     ORDER BY id DESC
     LIMIT 5"
);

foreach ($rawRecords as $record) {
    $email = trim($record['email'], '"');
    $timestamp = trim($record['timestamp'], '"');
    $processedIcon = $record['processed'] ? '✅' : '❌';

    echo "{$processedIcon} ID: {$record['id']} | Email: {$email} | Fecha: {$timestamp} | Procesado: " . ($record['processed'] ? 'SÍ' : 'NO') . "\n";
}

echo "\n" . str_repeat('-', 70) . "\n";

// Verificar emails que no existen en employees
echo "\n🔍 VERIFICANDO EMAILS NO ENCONTRADOS:\n";
echo str_repeat('-', 70) . "\n";

$sql = "SELECT DISTINCT JSON_EXTRACT(raw_json, '$.employee_email') as email
        FROM attendance_raw_data
        WHERE sync_batch_id = ?
        AND processed = 0";

$unmatchedEmails = $db->findAll($sql, [$lastSync['id']]);

if (count($unmatchedEmails) > 0) {
    echo "❌ Emails en marcaciones que NO existen en tabla employees:\n\n";

    foreach ($unmatchedEmails as $item) {
        $email = trim($item['email'], '"');

        // Verificar si existe en employees
        $exists = $db->find("SELECT id, firstname, lastname, email FROM employees WHERE email = ?", [$email]);

        if (!$exists) {
            echo "   ❌ {$email} - NO EXISTE\n";
        } else {
            echo "   ✅ {$email} - EXISTE (ID: {$exists['id']}, Nombre: {$exists['firstname']} {$exists['lastname']})\n";
        }
    }
} else {
    echo "✅ Todos los emails procesados existen en la tabla employees\n";
}

echo "\n" . str_repeat('=', 70) . "\n";
