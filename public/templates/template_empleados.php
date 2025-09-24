<?php
/**
 * Generador de Template Excel Estático para Empleados
 * Este script genera un archivo Excel físico que sirve como plantilla base
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

try {
    $spreadsheet = new Spreadsheet();

    // ==============================================
    // HOJA 1: TEMPLATE EMPLEADOS
    // ==============================================
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Empleados Template');

    // Headers con formato
    $headers = [
        'A1' => 'CÓDIGO EMPLEADO*',
        'B1' => 'NOMBRES*',
        'C1' => 'APELLIDOS*',
        'D1' => 'DIRECCIÓN',
        'E1' => 'FECHA NACIMIENTO*',
        'F1' => 'FECHA INGRESO*',
        'G1' => 'CONTACTO',
        'H1' => 'GÉNERO*',
        'I1' => 'POSICIÓN ID',
        'J1' => 'HORARIO ID*',
        'K1' => 'DOCUMENTO ID',
        'L1' => 'SEGURO SOCIAL',
        'M1' => 'SITUACIÓN ID*',
        'N1' => 'TIPO PLANILLA ID*',
        'O1' => 'CARGO ID',
        'P1' => 'FUNCIÓN ID',
        'Q1' => 'PARTIDA ID',
        'R1' => 'SUELDO INDIVIDUAL',
        'S1' => 'GASTOS REPRES.',
        'T1' => 'TIPO CONTRATO',
        'U1' => 'NÚMERO CONTRATO',
        'V1' => 'FECHA INICIO CONTRATO',
        'W1' => 'FECHA VENC. CONTRATO',
        'X1' => 'FORMA PAGO',
        'Y1' => 'BANCO',
        'Z1' => 'NÚMERO CUENTA',
        'AA1' => 'TIPO CUENTA'
    ];

    // Aplicar headers
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }

    // Estilo para headers
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '366092']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];
    $sheet->getStyle('A1:AA1')->applyFromArray($headerStyle);

    // Ajustar ancho de columnas
    $columnWidths = [
        'A' => 18, 'B' => 20, 'C' => 20, 'D' => 30, 'E' => 18, 'F' => 18,
        'G' => 15, 'H' => 12, 'I' => 15, 'J' => 15, 'K' => 18, 'L' => 18,
        'M' => 15, 'N' => 18, 'O' => 12, 'P' => 12, 'Q' => 12, 'R' => 18,
        'S' => 16, 'T' => 16, 'U' => 18, 'V' => 20, 'W' => 20, 'X' => 14,
        'Y' => 20, 'Z' => 18, 'AA' => 14
    ];

    foreach ($columnWidths as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }

    // Datos de ejemplo
    $examples = [
        // Ejemplo 1: Empleado empresa privada con ACH
        [
            'EMP001', 'Juan Carlos', 'Pérez González', 'Calle Principal #123, San Miguelito',
            '1985-03-15', '2023-01-15', '6677-8899', 'M', '1', '1',
            '8-123-456', 'SS123456', '1', '1', '1', '1', '1',
            '1500.00', '200.00', 'INDEFINIDO', 'CT-2023-001', '2023-01-15', '',
            'ACH', 'Banco Nacional de Panamá', '123456789', 'AHORROS'
        ],
        // Ejemplo 2: Empleada con contrato definido y cheque
        [
            'EMP002', 'María Elena', 'García López', 'Avenida Central #456, Pedregal',
            '1990-07-22', '2023-02-01', '6688-9900', 'F', '2', '1',
            '8-234-567', 'SS234567', '1', '2', '2', '2', '2',
            '1200.00', '150.00', 'DEFINIDO', 'CT-2023-002', '2023-02-01', '2024-02-01',
            'CHEQUE', 'Banco General', '987654321', 'CORRIENTE'
        ],
        // Ejemplo 3: Empleado efectivo (sin datos bancarios)
        [
            'EMP003', 'Roberto', 'Martínez Silva', 'Vía España #789, El Cangrejo',
            '1988-11-10', '2023-03-01', '6699-1122', 'M', '1', '2',
            '8-345-678', 'SS345678', '1', '1', '', '', '',
            '800.00', '100.00', 'INDEFINIDO', '', '', '',
            'EFECTIVO', '', '', ''
        ],
        // Ejemplo 4: Empleada por proyecto
        [
            'EMP004', 'Ana Sofía', 'Rodríguez Castro', 'Calle 50 #321, Bella Vista',
            '1992-05-18', '2023-04-01', '6655-3344', 'F', '3', '1',
            '8-456-789', 'SS456789', '1', '3', '3', '3', '3',
            '2000.00', '300.00', 'PROYECTO', 'CT-PROY-001', '2023-04-01', '2023-12-31',
            'ACH', 'Banco Industrial', '555666777', 'AHORROS'
        ],
        // Ejemplo 5: Empleado temporal
        [
            'EMP005', 'Carlos Eduardo', 'Vásquez Torres', 'Transistmica #654, Las Cumbres',
            '1987-09-25', '2023-05-01', '6633-5566', 'M', '2', '2',
            '8-567-890', 'SS567890', '1', '1', '1', '1', '1',
            '1000.00', '0.00', 'TEMPORAL', 'CT-TEMP-001', '2023-05-01', '2023-08-01',
            'CHEQUE', 'Banistmo', '888999000', 'CORRIENTE'
        ]
    ];

    // Aplicar ejemplos
    $row = 2;
    foreach ($examples as $example) {
        $col = 'A';
        foreach ($example as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }

    // Estilo para datos de ejemplo
    $dataStyle = [
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ];
    $sheet->getStyle('A2:AA6')->applyFromArray($dataStyle);

    // ==============================================
    // HOJA 2: REFERENCIAS
    // ==============================================
    $refSheet = $spreadsheet->createSheet();
    $refSheet->setTitle('Referencias');

    // Headers referencias
    $refSheet->setCellValue('A1', 'TABLA');
    $refSheet->setCellValue('B1', 'ID');
    $refSheet->setCellValue('C1', 'DESCRIPCIÓN');
    $refSheet->setCellValue('D1', 'OBSERVACIONES');

    $refHeaderStyle = [
        'font' => ['bold' => true, 'size' => 12],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $refSheet->getStyle('A1:D1')->applyFromArray($refHeaderStyle);

    // Datos de referencia de ejemplo
    $references = [
        ['POSICIÓN', '1', 'Gerente General', 'Nivel directivo'],
        ['POSICIÓN', '2', 'Supervisor', 'Nivel medio'],
        ['POSICIÓN', '3', 'Especialista', 'Nivel técnico'],
        ['', '', '', ''],
        ['HORARIO', '1', 'Tiempo Completo (8:00 AM - 5:00 PM)', '8 horas diarias'],
        ['HORARIO', '2', 'Medio Tiempo (8:00 AM - 12:00 PM)', '4 horas diarias'],
        ['', '', '', ''],
        ['SITUACIÓN', '1', 'Activo', 'Empleado trabajando normalmente'],
        ['SITUACIÓN', '2', 'Vacaciones', 'En período de descanso'],
        ['SITUACIÓN', '3', 'Licencia', 'Permiso temporal'],
        ['', '', '', ''],
        ['TIPO PLANILLA', '1', 'Quincenal', 'Pago cada 15 días'],
        ['TIPO PLANILLA', '2', 'Mensual', 'Pago cada mes'],
        ['TIPO PLANILLA', '3', 'Proyectos', 'Para empleados por proyecto'],
        ['', '', '', ''],
        ['CARGO', '1', 'Gerencia', 'Cargo directivo'],
        ['CARGO', '2', 'Supervisión', 'Cargo de supervisión'],
        ['CARGO', '3', 'Operativo', 'Cargo operativo'],
        ['', '', '', ''],
        ['FUNCIÓN', '1', 'Administración', 'Funciones administrativas'],
        ['FUNCIÓN', '2', 'Ventas', 'Funciones comerciales'],
        ['FUNCIÓN', '3', 'Operaciones', 'Funciones operativas'],
        ['', '', '', ''],
        ['PARTIDA', '1', 'Gastos Operativos', 'Partida principal'],
        ['PARTIDA', '2', 'Gastos Administrativos', 'Partida administrativa'],
        ['PARTIDA', '3', 'Proyectos Especiales', 'Partida para proyectos']
    ];

    $refRow = 2;
    foreach ($references as $ref) {
        $refSheet->setCellValue("A{$refRow}", $ref[0]);
        $refSheet->setCellValue("B{$refRow}", $ref[1]);
        $refSheet->setCellValue("C{$refRow}", $ref[2]);
        $refSheet->setCellValue("D{$refRow}", $ref[3]);
        $refRow++;
    }

    // Ajustar columnas referencias
    $refSheet->getColumnDimension('A')->setWidth(15);
    $refSheet->getColumnDimension('B')->setWidth(8);
    $refSheet->getColumnDimension('C')->setWidth(35);
    $refSheet->getColumnDimension('D')->setWidth(25);

    // ==============================================
    // HOJA 3: INSTRUCCIONES
    // ==============================================
    $instSheet = $spreadsheet->createSheet();
    $instSheet->setTitle('Instrucciones');

    $instructions = [
        ['INSTRUCCIONES PARA IMPORTAR EMPLEADOS - SISTEMA PLANILLAS INNOVA', '', '', ''],
        ['', '', '', ''],
        ['1. CAMPOS OBLIGATORIOS (marcados con *):', '', '', ''],
        ['   • CÓDIGO EMPLEADO*', 'Debe ser único', 'Ej: EMP001, RVP280963', ''],
        ['   • NOMBRES*', 'Nombre completo del empleado', 'Ej: Juan Carlos', ''],
        ['   • APELLIDOS*', 'Apellidos del empleado', 'Ej: Pérez González', ''],
        ['   • FECHA NACIMIENTO*', 'Formato YYYY-MM-DD', 'Ej: 1985-03-15', ''],
        ['   • FECHA INGRESO*', 'Formato YYYY-MM-DD', 'Ej: 2023-01-15', ''],
        ['   • GÉNERO*', 'M (Masculino) o F (Femenino)', 'Solo M o F', ''],
        ['   • HORARIO ID*', 'ID del horario (ver Referencias)', 'Ej: 1, 2, 3', ''],
        ['   • SITUACIÓN ID*', 'ID situación laboral (ver Referencias)', 'Ej: 1=Activo', ''],
        ['   • TIPO PLANILLA ID*', 'ID tipo planilla (ver Referencias)', 'Ej: 1=Quincenal', ''],
        ['', '', '', ''],
        ['2. CAMPOS CONDICIONALES:', '', '', ''],
        ['   • FECHA VENC. CONTRATO', 'Obligatorio para DEFINIDO, PROYECTO, TEMPORAL', '', ''],
        ['   • BANCO, NÚMERO CUENTA, TIPO CUENTA', 'Obligatorios para CHEQUE/ACH', '', ''],
        ['', '', '', ''],
        ['3. VALORES PERMITIDOS:', '', '', ''],
        ['   • GÉNERO:', 'M, F', '', ''],
        ['   • TIPO CONTRATO:', 'INDEFINIDO, DEFINIDO, PROYECTO, TEMPORAL', '', ''],
        ['   • FORMA PAGO:', 'EFECTIVO, CHEQUE, ACH', '', ''],
        ['   • TIPO CUENTA:', 'AHORROS, CORRIENTE', '', ''],
        ['', '', '', ''],
        ['4. FORMATO DE FECHAS:', 'YYYY-MM-DD', 'Ejemplo: 2023-12-31', ''],
        ['', '', '', ''],
        ['5. PASOS PARA IMPORTAR:', '', '', ''],
        ['   1. Eliminar las filas de ejemplo (filas 2-6)', '', '', ''],
        ['   2. Llenar sus datos empleados respetando formatos', '', '', ''],
        ['   3. Verificar IDs en hoja "Referencias"', '', '', ''],
        ['   4. Guardar archivo como .xlsx', '', '', ''],
        ['   5. Subir en el sistema: Panel > Empleados > Importar Excel', '', '', ''],
        ['', '', '', ''],
        ['6. VALIDACIONES DEL SISTEMA:', '', '', ''],
        ['   • Códigos empleado únicos', '', '', ''],
        ['   • Fechas válidas', '', '', ''],
        ['   • IDs existentes en base de datos', '', '', ''],
        ['   • Campos obligatorios completos', '', '', ''],
        ['   • Formatos correctos', '', '', ''],
        ['', '', '', ''],
        ['7. EN CASO DE ERRORES:', '', '', ''],
        ['   • El sistema mostrará errores específicos por fila', '', '', ''],
        ['   • Corregir y volver a intentar', '', '', ''],
        ['   • Contactar soporte si persisten problemas', '', '', ''],
        ['', '', '', ''],
        ['EJEMPLOS EN HOJA "Empleados Template" - ELIMINAR ANTES DE IMPORTAR', '', '', ''],
        ['', '', '', ''],
        ['© 2025 Sistema Planillas INNOVA v3.3.0', '', '', '']
    ];

    $instRow = 1;
    foreach ($instructions as $inst) {
        $instSheet->setCellValue("A{$instRow}", $inst[0]);
        $instSheet->setCellValue("B{$instRow}", $inst[1]);
        $instSheet->setCellValue("C{$instRow}", $inst[2]);
        $instSheet->setCellValue("D{$instRow}", $inst[3]);
        $instRow++;
    }

    // Formato especial para títulos
    $instSheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '366092']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);

    $instSheet->getStyle('A3')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0066CC']]
    ]);

    $instSheet->getStyle('A14')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0066CC']]
    ]);

    // Ajustar columnas instrucciones
    $instSheet->getColumnDimension('A')->setWidth(50);
    $instSheet->getColumnDimension('B')->setWidth(30);
    $instSheet->getColumnDimension('C')->setWidth(25);
    $instSheet->getColumnDimension('D')->setWidth(15);

    // Volver a la primera hoja
    $spreadsheet->setActiveSheetIndex(0);

    // Guardar archivo
    $writer = new Xlsx($spreadsheet);
    $filename = 'template_empleados_planilla_innova.xlsx';
    $filepath = $filename;

    $writer->save($filepath);

    echo "✅ Archivo Excel creado exitosamente: {$filename}\n";
    echo "📁 Ubicación: " . realpath($filepath) . "\n";
    echo "📊 El archivo contiene:\n";
    echo "   • Hoja 1: Template con 27 campos y 5 ejemplos\n";
    echo "   • Hoja 2: Referencias con IDs de ejemplo\n";
    echo "   • Hoja 3: Instrucciones detalladas\n";
    echo "\n🎯 Listo para usar como plantilla base!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>