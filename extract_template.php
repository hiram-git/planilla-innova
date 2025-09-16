<?php
/**
 * Script para extraer la estructura de la plantilla Excel usando PhpSpreadsheet
 */

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

try {
    $templatePath = __DIR__ . '/assets/template/PRIMERA QUINCENA DE NOVIEMBRE.xlsx';
    
    if (!file_exists($templatePath)) {
        die("Error: No se encuentra el archivo de plantilla en: $templatePath\n");
    }
    
    echo "=== ANALIZANDO PLANTILLA EXCEL ===\n";
    echo "Archivo: $templatePath\n\n";
    
    // Cargar el archivo Excel
    $spreadsheet = IOFactory::load($templatePath);
    $worksheetNames = $spreadsheet->getSheetNames();
    
    echo "Hojas encontradas: " . implode(', ', $worksheetNames) . "\n\n";
    
    // Analizar cada hoja
    foreach ($worksheetNames as $sheetName) {
        $worksheet = $spreadsheet->getSheetByName($sheetName);
        
        echo "--- HOJA: $sheetName ---\n";
        
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        
        echo "Dimensiones: $highestRow filas x $highestColumn ($highestColumnIndex) columnas\n";
        
        // Extraer headers y primeras filas
        echo "\nPrimeras 15 filas de contenido:\n";
        
        for ($row = 1; $row <= min(15, $highestRow); $row++) {
            $rowData = [];
            for ($col = 1; $col <= min(15, $highestColumnIndex); $col++) {
                $cellCoordinate = Coordinate::stringFromColumnIndex($col) . $row;
                $cell = $worksheet->getCell($cellCoordinate, false);
                
                if ($cell && $cell->getValue() !== null) {
                    $value = $cell->getCalculatedValue();
                    if (is_string($value)) {
                        $value = trim($value);
                        if (strlen($value) > 50) {
                            $value = substr($value, 0, 47) . '...';
                        }
                    }
                    
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $rowData[] = "[$colLetter$row]: " . $value;
                }
            }
            
            if (!empty($rowData)) {
                echo "Fila $row: " . implode(' | ', $rowData) . "\n";
            }
        }
        
        // Analizar celdas combinadas
        $mergedCells = $worksheet->getMergeCells();
        if (!empty($mergedCells)) {
            echo "\nCeldas combinadas:\n";
            foreach ($mergedCells as $mergedRange) {
                $startCell = $worksheet->getCell(explode(':', $mergedRange)[0]);
                $value = $startCell->getCalculatedValue();
                echo "  - $mergedRange: '$value'\n";
            }
        }
        
        // Analizar estilos de las primeras filas
        echo "\nEstilos relevantes encontrados:\n";
        for ($row = 1; $row <= min(10, $highestRow); $row++) {
            for ($col = 1; $col <= min(10, $highestColumnIndex); $col++) {
                $cellCoordinate = Coordinate::stringFromColumnIndex($col) . $row;
                $cell = $worksheet->getCell($cellCoordinate, false);
                
                if ($cell && $cell->getValue() !== null) {
                    $style = $cell->getStyle();
                    $fill = $style->getFill();
                    $font = $style->getFont();
                    $borders = $style->getBorders();
                    
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    
                    // Solo mostrar si tiene estilo especial
                    if ($fill->getFillType() !== \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE ||
                        $font->getBold() ||
                        $font->getSize() !== 11 ||
                        $borders->getTop()->getBorderStyle() !== \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE) {
                        
                        $styleInfo = [];
                        
                        if ($font->getBold()) $styleInfo[] = "Bold";
                        if ($font->getSize() !== 11) $styleInfo[] = "Size:" . $font->getSize();
                        if ($fill->getFillType() !== \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE) {
                            $styleInfo[] = "Fill:" . $fill->getStartColor()->getRGB();
                        }
                        if ($borders->getTop()->getBorderStyle() !== \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE) {
                            $styleInfo[] = "Borders";
                        }
                        
                        if (!empty($styleInfo)) {
                            echo "  $colLetter$row: " . implode(', ', $styleInfo) . "\n";
                        }
                    }
                }
            }
        }
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>