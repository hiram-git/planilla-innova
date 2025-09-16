<?php
/**
 * Script de prueba para generar Excel y verificar errores XML
 */

require_once 'vendor/autoload.php';

// Simular datos de prueba
$planillaData = [
    'payroll' => [
        'id' => 1,
        'descripcion' => 'PLANILLA PRUEBA',
        'fecha_inicio' => '2025-09-01',
        'fecha_fin' => '2025-09-15'
    ],
    'employees' => [
        [
            'id' => 1,
            'firstname' => 'Juan',
            'lastname' => 'Pérez',
            'document_id' => '8-123-456',
            'salary' => 1500.00,
            'fecha_ingreso' => '2024-01-15',
            'puesto_actual' => 'Administrador',
            'funcion_name' => 'Administración',
            'reference_value' => 15,
            'totals' => [
                'ingresos' => 750.00,
                'deducciones' => 187.50,
                'seguro_social' => 71.25,
                'seguro_educativo' => 9.38,
                'impuesto_renta' => 0.00,
                'otras_deducciones' => 106.87,
                'neto' => 562.50
            ],
            'concepts' => [
                [
                    'codigo' => 'SUELDO',
                    'descripcion' => 'Sueldo Base',
                    'tipo' => 'ingreso',
                    'monto' => 750.00
                ],
                [
                    'codigo' => 'SS',
                    'descripcion' => 'Seguro Social',
                    'tipo' => 'deduccion',
                    'monto' => 71.25
                ]
            ]
        ]
    ]
];

$companyInfo = [
    'company_name' => 'EMPRESA DE PRUEBA S.A.',
    'ruc' => '123456789-1-DV'
];

$signatures = [
    'elaborado_por' => 'Sistema Automático',
    'jefe_recursos_humanos' => 'Director RRHH',
    'cargo_elaborador' => 'Especialista en Nóminas',
    'cargo_jefe_rrhh' => 'Jefe de Recursos Humanos'
];

// Crear instancia del ReportController para usar sus métodos
class TestReportController {
    
    private function escapeXmlData($data)
    {
        if (is_null($data)) {
            return '';
        }
        
        // Convertir a string y escapar caracteres XML
        $data = (string) $data;
        
        // Escape básico para XML
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_XML1, 'UTF-8');
        
        // Remover caracteres de control que pueden causar problemas
        $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
        
        return $data;
    }
    
    private function getCustomExcelStyles()
    {
        return '
        <Styles>
            <Style ss:ID="Default" ss:Name="Normal">
                <Alignment ss:Vertical="Bottom"/>
                <Borders/>
                <Font ss:FontName="Arial" ss:Size="10"/>
                <Interior/>
                <NumberFormat/>
                <Protection/>
            </Style>
            
            <Style ss:ID="CompanyHeader">
                <Font ss:FontName="Arial" ss:Size="16" ss:Bold="1" ss:Color="#FFFFFF"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Interior ss:Color="#4472C4" ss:Pattern="Solid"/>
            </Style>
            
            <Style ss:ID="ColumnHeaders">
                <Font ss:FontName="Arial" ss:Size="9" ss:Bold="1" ss:Color="#FFFFFF"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
                <Interior ss:Color="#70AD47" ss:Pattern="Solid"/>
                <Borders>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
        </Styles>';
    }
    
    public function generateTestExcel($planillaData, $companyInfo, $signatures)
    {
        $payroll = $planillaData['payroll'];
        $employees = $planillaData['employees'];
        
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_inicio']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_fin']));
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        $xml .= $this->getCustomExcelStyles();
        
        $xml .= '<Worksheet ss:Name="Planilla Quincenal">' . "\n";
        $xml .= '<Table>' . "\n";
        
        // Header de la empresa
        $xml .= '<Row ss:StyleID="CompanyHeader">' . "\n";
        $xml .= '<Cell ss:MergeAcross="23"><Data ss:Type="String">' . $this->escapeXmlData($companyInfo['company_name']) . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        // Headers de columnas según la plantilla
        $xml .= '<Row ss:StyleID="ColumnHeaders">' . "\n";
        $xml .= '<Cell><Data ss:Type="String">No.</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">COLABORADOR</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">SALARIO NETO</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        // Datos de empleado
        foreach ($employees as $index => $emp) {
            $xml .= '<Row>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . ($index + 1) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($emp['firstname'] . ' ' . $emp['lastname']) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . number_format($emp['totals']['neto'], 2, '.', '') . '</Data></Cell>' . "\n";
            $xml .= '</Row>' . "\n";
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>';
        
        return $xml;
    }
}

// Generar el Excel de prueba
$testController = new TestReportController();
$xmlContent = $testController->generateTestExcel($planillaData, $companyInfo, $signatures);

// Validar el XML
$dom = new DOMDocument();
$dom->formatOutput = true;

// Intentar cargar el XML
libxml_use_internal_errors(true);
$loaded = $dom->loadXML($xmlContent);

if ($loaded) {
    echo "✅ XML válido - Sin errores de estructura\n";
    
    // Guardar archivo de prueba
    $filename = 'planilla_test_' . date('Y-m-d_H-i-s') . '.xls';
    file_put_contents($filename, $xmlContent);
    echo "📄 Archivo generado: $filename\n";
    
} else {
    echo "❌ Errores en XML:\n";
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
        echo "Error: " . $error->message . "\n";
    }
}

echo "\n=== ANALIZANDO ESTRUCTURA XML ===\n";
echo "Longitud del contenido: " . strlen($xmlContent) . " caracteres\n";

// Buscar elementos Font duplicados
$fontMatches = [];
preg_match_all('/<Font[^>]*>/', $xmlContent, $fontMatches);
echo "Elementos Font encontrados: " . count($fontMatches[0]) . "\n";

// Verificar estilos
preg_match_all('/<Style[^>]*ss:ID="([^"]*)"[^>]*>(.*?)<\/Style>/s', $xmlContent, $styleMatches);
echo "Estilos definidos: " . count($styleMatches[1]) . "\n";

for ($i = 0; $i < count($styleMatches[1]); $i++) {
    $styleId = $styleMatches[1][$i];
    $styleContent = $styleMatches[2][$i];
    $fontCount = substr_count($styleContent, '<Font');
    
    if ($fontCount > 1) {
        echo "⚠️  Estilo '$styleId' tiene $fontCount elementos Font (debería ser 1)\n";
    } else {
        echo "✅ Estilo '$styleId' correcto ($fontCount elemento Font)\n";
    }
}

echo "\n=== TEST COMPLETADO ===\n";
?>