<?php
/**
 * Script para corregir errores CORS de DataTables en todas las vistas
 * Reemplaza URLs de CDN por configuración local
 */

$files = [
    'app/Views/admin/acumulados/by_concept.php',
    'app/Views/admin/acumulados/by_employee.php',
    'app/Views/admin/acumulados/by_type.php',
    'app/Views/admin/acumulados/by-type.php',
    'app/Views/admin/acumulados/index.php',
    'app/Views/admin/reports/payroll_selector.php',
];

$searchPattern = '"url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"';
$searchPattern2 = 'url: \'//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json\'';
$searchPattern3 = '"url": urls.datatables_spanish || "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"';

$replacement = 'DATATABLES_SPANISH';

$scriptInclude = '<script src="<?php echo url(\'assets/javascript/datatables-spanish.js\', false); ?>"></script>';

$filesModified = 0;
$errors = [];

foreach ($files as $file) {
    if (!file_exists($file)) {
        $errors[] = "Archivo no encontrado: $file";
        continue;
    }

    $content = file_get_contents($file);
    $originalContent = $content;

    // Reemplazar patrones
    $content = str_replace(
        [
            '"url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"',
            'url: \'//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json\'',
            '"url": urls.datatables_spanish || "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"'
        ],
        $replacement,
        $content
    );

    // Agregar script include si no existe
    if (strpos($content, 'datatables-spanish.js') === false) {
        // Buscar último </section> o primer <script>
        if (preg_match('/<\/section>\s*\n/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1] + strlen($matches[0][0]);
            $content = substr_replace($content, "\n$scriptInclude\n", $pos, 0);
        }
    }

    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $filesModified++;
        echo "✅ Actualizado: $file\n";
    } else {
        echo "⏭️  Sin cambios: $file\n";
    }
}

echo "\n📊 Resumen:\n";
echo "  - Archivos procesados: " . count($files) . "\n";
echo "  - Archivos modificados: $filesModified\n";
echo "  - Errores: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\n❌ Errores encontrados:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n✅ Proceso completado!\n";
