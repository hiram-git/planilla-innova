<?php
/**
 * Ejecutar migración individual en la base de datos de tenant
 */

require_once __DIR__ . '/../../../config/database.php';

$migrationFile = __DIR__ . '/2025_12_30_add_organigrama_id_to_employees.sql';

try {
    $db = getDBConnection();
    $sql = file_get_contents($migrationFile);

    echo "Ejecutando migración: " . basename($migrationFile) . "\n";
    echo "Base de datos conectada exitosamente\n";

    // Ejecutar el SQL completo
    $db->exec($sql);

    echo "✅ Migración ejecutada exitosamente\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
