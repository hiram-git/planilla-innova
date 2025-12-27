<?php
/**
 * Script para comentar SELECT statements en archivos de migración
 *
 * Problema: Los SELECT de verificación en migraciones causan error:
 * "Cannot execute queries while there are pending result sets"
 *
 * Solución: Este script comenta automáticamente todos los SELECT
 * que no son parte de prepared statements o SET @variable.
 *
 * Uso: php fix_select_statements.php [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv);
$migrationsDir = __DIR__ . '/tenant';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║      FIX SELECT STATEMENTS - MIGRACIONES TENANT                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Modo: " . ($dryRun ? "🔍 DRY RUN (no modifica)" : "⚡ EJECUCIÓN REAL") . "\n";
echo "Directorio: {$migrationsDir}\n\n";

// Obtener todos los archivos .sql
$files = glob($migrationsDir . '/*.sql');
sort($files);

$stats = [
    'files_processed' => 0,
    'files_modified' => 0,
    'selects_commented' => 0,
    'lines_modified' => 0
];

foreach ($files as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);
    $originalContent = $content;
    $stats['files_processed']++;

    $selectsCommented = 0;

    // Patron para detectar SELECT statements que NO son parte de SET @variable
    // Busca SELECT al inicio de línea (opcionalmente precedido por espacios)
    $pattern = '/^(\s*)(SELECT\s+)/mi';

    // Verificar si hay SELECT statements para comentar
    if (preg_match($pattern, $content)) {
        $lines = explode("\n", $content);
        $modified = false;
        $inMultilineSelect = false;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmedLine = trim($line);

            // Detectar inicio de SELECT que NO es parte de SET @variable
            if (preg_match('/^\s*SELECT\s+/i', $line) &&
                !preg_match('/SET\s+@\w+\s*=\s*IF\s*\(/i', $lines[$i-1] ?? '') &&
                !preg_match('/^\s*--/', $line)) {

                // Verificar si es un SELECT dentro de un paréntesis (subquery en SET @var)
                $contextBefore = implode("\n", array_slice($lines, max(0, $i-5), 5));

                if (!preg_match('/SET\s+@\w+\s*=\s*IF\s*\([^)]*$/is', $contextBefore)) {
                    // Comentar la línea
                    $indent = '';
                    if (preg_match('/^(\s*)/', $line, $matches)) {
                        $indent = $matches[1];
                    }
                    $lines[$i] = $indent . '-- ' . ltrim($line);
                    $modified = true;
                    $selectsCommented++;
                    $inMultilineSelect = true;
                    $stats['lines_modified']++;
                }
            }
            // Si estamos en un SELECT multi-línea, comentar líneas siguientes hasta encontrar ;
            elseif ($inMultilineSelect) {
                if (!preg_match('/^\s*--/', $line)) {
                    $indent = '';
                    if (preg_match('/^(\s*)/', $line, $matches)) {
                        $indent = $matches[1];
                    }
                    $lines[$i] = $indent . '-- ' . ltrim($line);
                    $stats['lines_modified']++;
                }

                // Terminar cuando encontramos ;
                if (strpos($trimmedLine, ';') !== false) {
                    $inMultilineSelect = false;
                }
            }
        }

        if ($modified) {
            $content = implode("\n", $lines);

            // Agregar nota al inicio del bloque comentado
            $content = preg_replace(
                '/^(\s*)-- (SELECT\s+)/mi',
                "$1-- NOTA: SELECT comentado para evitar error PDO \"pending result sets\"\n$1-- $2",
                $content,
                1 // Solo la primera ocurrencia
            );
        }
    }

    // Si hubo cambios, guardar archivo
    if ($content !== $originalContent) {
        $stats['files_modified']++;
        $stats['selects_commented'] += $selectsCommented;

        echo "📝 {$filename}\n";
        echo "   ➜ {$selectsCommented} SELECT(s) comentados\n";

        if (!$dryRun) {
            file_put_contents($file, $content);
            echo "   ✅ Archivo actualizado\n";
        } else {
            echo "   🔍 [DRY RUN] No se modificó el archivo\n";
        }
        echo "\n";
    }
}

// Resumen
echo str_repeat("═", 64) . "\n";
echo "📊 RESUMEN\n";
echo str_repeat("═", 64) . "\n";
echo "Archivos procesados:     {$stats['files_processed']}\n";
echo "Archivos modificados:    {$stats['files_modified']}\n";
echo "SELECT comentados:       {$stats['selects_commented']}\n";
echo "Líneas modificadas:      {$stats['lines_modified']}\n";
echo str_repeat("═", 64) . "\n";

if ($dryRun) {
    echo "\n💡 Para aplicar cambios, ejecuta: php fix_select_statements.php\n\n";
} else {
    echo "\n✅ Proceso completado\n\n";
}
