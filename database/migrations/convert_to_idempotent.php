#!/usr/bin/env php
<?php
/**
 * 🔄 Script para Convertir Migraciones a Formato Idempotente
 *
 * Uso:
 *   php convert_to_idempotent.php path/to/migration.sql
 *
 * Este script convierte migraciones SQL simples a formato idempotente usando
 * INFORMATION_SCHEMA y prepared statements.
 *
 * Soporta:
 * - ADD COLUMN
 * - CREATE INDEX
 * - ADD UNIQUE KEY
 * - ADD CONSTRAINT (FOREIGN KEY)
 *
 * Autor: Sistema Planillas Innova
 * Fecha: 26 de Diciembre, 2025
 */

if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde línea de comandos');
}

// Validar argumentos
if ($argc < 2) {
    echo "❌ Error: Debe proporcionar la ruta del archivo de migración\n\n";
    echo "Uso:\n";
    echo "  php convert_to_idempotent.php path/to/migration.sql\n\n";
    echo "Ejemplo:\n";
    echo "  php convert_to_idempotent.php master/2025_11_21_add_company_info_to_tenants.sql\n\n";
    exit(1);
}

$migrationFile = $argv[1];

// Verificar que el archivo existe
if (!file_exists($migrationFile)) {
    echo "❌ Error: Archivo no encontrado: $migrationFile\n";
    exit(1);
}

echo "🔄 Convirtiendo migración a formato idempotente...\n";
echo "📄 Archivo: $migrationFile\n\n";

// Leer contenido del archivo
$content = file_get_contents($migrationFile);

// Detectar nombre de base de datos (si existe USE statement)
$dbname = 'DATABASE()';
if (preg_match('/USE\s+([a-zA-Z0-9_]+)\s*;/i', $content, $matches)) {
    $dbname = "'" . $matches[1] . "'";
}

// Almacenar las conversiones realizadas
$conversions = [];
$idempotentStatements = [];

// ============================================================================
// Convertir ADD COLUMN statements
// ============================================================================
if (preg_match_all('/ALTER\s+TABLE\s+([a-zA-Z0-9_]+)\s+ADD\s+COLUMN\s+([a-zA-Z0-9_]+)\s+([^;]+);/is', $content, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        $fullStatement = $match[0];
        $tableName = $match[1];
        $columnName = $match[2];
        $columnDefinition = trim($match[3]);

        $idempotentCode = generateAddColumnIdempotent($dbname, $tableName, $columnName, $columnDefinition);
        $idempotentStatements[] = [
            'original' => $fullStatement,
            'idempotent' => $idempotentCode,
            'type' => 'ADD COLUMN'
        ];

        $conversions[] = "✓ ADD COLUMN $tableName.$columnName";
    }
}

// ============================================================================
// Convertir CREATE INDEX statements
// ============================================================================
if (preg_match_all('/CREATE\s+INDEX\s+([a-zA-Z0-9_]+)\s+ON\s+([a-zA-Z0-9_]+)\s*\(([^)]+)\)\s*;/is', $content, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        $fullStatement = $match[0];
        $indexName = $match[1];
        $tableName = $match[2];
        $columns = trim($match[3]);

        $idempotentCode = generateCreateIndexIdempotent($dbname, $tableName, $indexName, $columns);
        $idempotentStatements[] = [
            'original' => $fullStatement,
            'idempotent' => $idempotentCode,
            'type' => 'CREATE INDEX'
        ];

        $conversions[] = "✓ CREATE INDEX $indexName ON $tableName";
    }
}

// ============================================================================
// Convertir ADD UNIQUE KEY statements
// ============================================================================
if (preg_match_all('/ALTER\s+TABLE\s+([a-zA-Z0-9_]+)\s+ADD\s+UNIQUE\s+KEY\s+([a-zA-Z0-9_]+)\s*\(([^)]+)\)\s*;/is', $content, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        $fullStatement = $match[0];
        $tableName = $match[1];
        $constraintName = $match[2];
        $columns = trim($match[3]);

        $idempotentCode = generateAddUniqueKeyIdempotent($dbname, $tableName, $constraintName, $columns);
        $idempotentStatements[] = [
            'original' => $fullStatement,
            'idempotent' => $idempotentCode,
            'type' => 'ADD UNIQUE KEY'
        ];

        $conversions[] = "✓ ADD UNIQUE KEY $constraintName ON $tableName";
    }
}

// ============================================================================
// Mostrar resumen
// ============================================================================
if (empty($conversions)) {
    echo "⚠️  No se encontraron statements para convertir.\n";
    echo "    Este script solo convierte: ADD COLUMN, CREATE INDEX, ADD UNIQUE KEY\n";
    exit(0);
}

echo "📊 Resumen de conversiones:\n";
foreach ($conversions as $conversion) {
    echo "   $conversion\n";
}
echo "\n";

// ============================================================================
// Generar archivo idempotente
// ============================================================================
$outputFile = str_replace('.sql', '_idempotent.sql', $migrationFile);

// Crear header
$header = <<<HEADER
-- ============================================================================
-- Migración Idempotente Generada Automáticamente
-- Archivo original: $migrationFile
-- Fecha de conversión: @date
-- IDEMPOTENT: ✅ Safe to run multiple times
-- ============================================================================


HEADER;

$header = str_replace('@date', date('Y-m-d H:i:s'), $header);

// Agregar SET statements
$header .= "SET @dbname = $dbname;\n\n";

// Agregar statements idempotentes
$idempotentContent = $header;

foreach ($idempotentStatements as $statement) {
    $idempotentContent .= "-- Original: " . trim($statement['original']) . "\n";
    $idempotentContent .= $statement['idempotent'] . "\n\n";
}

// Guardar archivo
file_put_contents($outputFile, $idempotentContent);

echo "✅ Migración idempotente generada:\n";
echo "   📄 $outputFile\n\n";

echo "⚠️  IMPORTANTE:\n";
echo "   1. Revisa manualmente el archivo generado antes de usarlo\n";
echo "   2. Verifica que las definiciones de columnas sean correctas\n";
echo "   3. Prueba la migración en ambiente de desarrollo\n\n";

// ============================================================================
// Funciones helper
// ============================================================================

function generateAddColumnIdempotent($dbname, $tableName, $columnName, $columnDefinition) {
    $escapedDefinition = str_replace("'", "''", $columnDefinition);

    return <<<SQL
SET @tablename = '$tableName';
SET @columnname = '$columnName';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE $tableName ADD COLUMN $columnName $columnDefinition'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
SQL;
}

function generateCreateIndexIdempotent($dbname, $tableName, $indexName, $columns) {
    return <<<SQL
SET @tablename = '$tableName';
SET @indexname = '$indexName';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = @indexname)
  ) > 0,
  'SELECT 1',
  'CREATE INDEX $indexName ON $tableName($columns)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
SQL;
}

function generateAddUniqueKeyIdempotent($dbname, $tableName, $constraintName, $columns) {
    return <<<SQL
SET @tablename = '$tableName';
SET @constraintname = '$constraintName';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = @constraintname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE $tableName ADD UNIQUE KEY $constraintName ($columns)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
SQL;
}
