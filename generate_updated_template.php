<?php
/**
 * Script para generar template de importación actualizado (V3.5.9)
 * Con campos: EMAIL, MARCA_ASISTENCIA, PERMITE_HORAS_EXTRAS
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

echo "=== Generando Template Actualizado V3.5.9 ===\n\n";

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Empleados Template');

// Configurar headers (33 columnas A-AD)
$headers = [
    'A1' => ['value' => 'CÓDIGO EMPLEADO*', 'width' => 18],
    'B1' => ['value' => 'NOMBRES*', 'width' => 20],
    'C1' => ['value' => 'APELLIDOS*', 'width' => 20],
    'D1' => ['value' => 'EMAIL*', 'width' => 25],
    'E1' => ['value' => 'DIRECCIÓN', 'width' => 30],
    'F1' => ['value' => 'FECHA NACIMIENTO*', 'width' => 18],
    'G1' => ['value' => 'FECHA INGRESO*', 'width' => 18],
    'H1' => ['value' => 'CONTACTO', 'width' => 15],
    'I1' => ['value' => 'GÉNERO*', 'width' => 12],
    'J1' => ['value' => 'POSICIÓN ID (Ver Ref)', 'width' => 18],
    'K1' => ['value' => 'HORARIO ID* (Ver Ref)', 'width' => 18],
    'L1' => ['value' => 'DOCUMENTO ID', 'width' => 18],
    'M1' => ['value' => 'SEGURO SOCIAL', 'width' => 18],
    'N1' => ['value' => 'SITUACIÓN ID* (Ver Ref)', 'width' => 18],
    'O1' => ['value' => 'TIPO PLANILLA ID* (Ver Ref)', 'width' => 22],
    'P1' => ['value' => 'CARGO ID (Ver Ref)', 'width' => 15],
    'Q1' => ['value' => 'FUNCIÓN ID (Ver Ref)', 'width' => 17],
    'R1' => ['value' => 'PARTIDA ID (Ver Ref)', 'width' => 17],
    'S1' => ['value' => 'SUELDO INDIVIDUAL', 'width' => 18],
    'T1' => ['value' => 'GASTOS REPRES.', 'width' => 16],
    'U1' => ['value' => 'MARCA ASISTENCIA (1=SI, 0=NO)', 'width' => 22],
    'V1' => ['value' => 'PERMITE HORAS EXTRAS (1=SI, 0=NO)', 'width' => 26],
    'W1' => ['value' => 'TIPO CONTRATO', 'width' => 16],
    'X1' => ['value' => 'NÚMERO CONTRATO', 'width' => 18],
    'Y1' => ['value' => 'FECHA INICIO CONTRATO', 'width' => 20],
    'Z1' => ['value' => 'FECHA VENC. CONTRATO', 'width' => 20],
    'AA1' => ['value' => 'FORMA PAGO', 'width' => 14],
    'AB1' => ['value' => 'BANCO', 'width' => 20],
    'AC1' => ['value' => 'NÚMERO CUENTA', 'width' => 18],
    'AD1' => ['value' => 'TIPO CUENTA', 'width' => 14]
];

echo "Aplicando headers...\n";
foreach ($headers as $cell => $config) {
    $sheet->setCellValue($cell, $config['value']);
    $sheet->getColumnDimension(substr($cell, 0, -1))->setWidth($config['width']);
}

// Estilo para headers
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '366092']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:AD1')->applyFromArray($headerStyle);

echo "Conectando a BD para obtener IDs válidos...\n";

// Conectar a BD para obtener IDs dinámicamente
$dbConfig = require __DIR__ . '/config/database.php';

// Intentar conectar a BD master para ver si hay tenants
$masterDb = new PDO(
    "mysql:host={$dbConfig['connections']['mysql']['host']};dbname=planilla_master;charset=utf8mb4",
    $dbConfig['connections']['mysql']['username'],
    $dbConfig['connections']['mysql']['password']
);

// Buscar tenant activo más reciente
$tenantDb = $masterDb->query("SELECT db_name FROM tenants WHERE status = 'ACTIVE' ORDER BY id DESC LIMIT 1")->fetchColumn();

// Determinar qué BD usar
if ($tenantDb) {
    $targetDb = $tenantDb;
    echo "✅ Usando BD tenant: {$targetDb}\n";
} else {
    $targetDb = $dbConfig['connections']['mysql']['database'];
    echo "✅ Usando BD default: {$targetDb}\n";
}

// Conectar a la BD objetivo
$db = new PDO(
    "mysql:host={$dbConfig['connections']['mysql']['host']};dbname={$targetDb};charset=utf8mb4",
    $dbConfig['connections']['mysql']['username'],
    $dbConfig['connections']['mysql']['password']
);

// Obtener IDs válidos de las tablas
$position1 = $db->query("SELECT id FROM posiciones ORDER BY id LIMIT 1")->fetchColumn() ?: 1;
$schedule1 = $db->query("SELECT id FROM schedules ORDER BY id LIMIT 1")->fetchColumn() ?: 1;
$situacion1 = $db->query("SELECT id FROM situaciones WHERE nombre LIKE '%activ%' ORDER BY id LIMIT 1")->fetchColumn() ?: 1;
$tipoPlanilla1 = $db->query("SELECT id FROM tipos_planilla ORDER BY id LIMIT 1")->fetchColumn() ?: 1;
$cargo1 = $db->query("SELECT id FROM cargos ORDER BY id LIMIT 1")->fetchColumn() ?: 1;
$cargo2 = $db->query("SELECT id FROM cargos ORDER BY id LIMIT 1 OFFSET 1")->fetchColumn() ?: 1;
$funcion1 = $db->query("SELECT id FROM funciones ORDER BY id LIMIT 1")->fetchColumn() ?: 1;
$funcion2 = $db->query("SELECT id FROM funciones ORDER BY id LIMIT 1 OFFSET 1")->fetchColumn() ?: 1;
$partida1 = $db->query("SELECT id FROM partidas ORDER BY id LIMIT 1")->fetchColumn() ?: 1;
$partida2 = $db->query("SELECT id FROM partidas ORDER BY id LIMIT 1 OFFSET 1")->fetchColumn() ?: 1;

echo "IDs válidos obtenidos:\n";
echo "  - Posición: {$position1}\n";
echo "  - Horario: {$schedule1}\n";
echo "  - Situación: {$situacion1}\n";
echo "  - Tipo Planilla: {$tipoPlanilla1}\n";
echo "  - Cargo: {$cargo1}, {$cargo2}\n";
echo "  - Función: {$funcion1}, {$funcion2}\n";
echo "  - Partida: {$partida1}, {$partida2}\n";

echo "Agregando filas de ejemplo...\n";

// Agregar ejemplos de datos con EMAIL usando IDs válidos
$exampleData = [
    ['EMP001', 'Juan Carlos', 'Pérez González', 'jperez@empresa.com', 'Calle Principal #123', '1985-03-15', '2023-01-15', '6677-8899', 'M', $position1, $schedule1, '8-123-456', 'SS123456', $situacion1, $tipoPlanilla1, $cargo1, $funcion1, $partida1, '1500.00', '200.00', '1', '1', 'INDEFINIDO', 'CT-2023-001', '2023-01-15', '', 'ACH', 'Banco Nacional', '123456789', 'AHORROS'],
    ['EMP002', 'María Elena', 'García López', 'mgarcia@empresa.com', 'Avenida Central #456', '1990-07-22', '2023-02-01', '6688-9900', 'F', $position1, $schedule1, '8-234-567', 'SS234567', $situacion1, $tipoPlanilla1, $cargo2, $funcion2, $partida2, '1200.00', '150.00', '1', '0', 'DEFINIDO', 'CT-2023-002', '2023-02-01', '2024-02-01', 'CHEQUE', 'Banco Industrial', '987654321', 'CORRIENTE']
];

$row = 2;
foreach ($exampleData as $data) {
    $col = 'A';
    foreach ($data as $value) {
        $sheet->setCellValue($col . $row, $value);
        $col++;
    }
    $row++;
}

// =====================================
// HOJA DE REFERENCIAS
// =====================================
echo "Agregando hoja de Referencias...\n";

$refSheet = $spreadsheet->createSheet();
$refSheet->setTitle('Referencias');

// Headers
$refSheet->setCellValue('A1', 'TABLA');
$refSheet->setCellValue('B1', 'ID');
$refSheet->setCellValue('C1', 'DESCRIPCIÓN');

// Estilo para headers
$refHeaderStyle = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]
];
$refSheet->getStyle('A1:C1')->applyFromArray($refHeaderStyle);

$refRow = 2;

// POSICIONES (usar la misma conexión $db ya establecida)
echo "  - Posiciones...\n";
$positions = $db->query("SELECT id, codigo FROM posiciones ORDER BY codigo")->fetchAll(PDO::FETCH_ASSOC);
foreach ($positions as $pos) {
    $refSheet->setCellValue("A{$refRow}", 'POSICIÓN');
    $refSheet->setCellValue("B{$refRow}", $pos['id']);
    $refSheet->setCellValue("C{$refRow}", $pos['codigo']);
    $refRow++;
}

// HORARIOS
echo "  - Horarios...\n";
$schedules = $db->query("SELECT id, nombre FROM schedules ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
foreach ($schedules as $sch) {
    $refSheet->setCellValue("A{$refRow}", 'HORARIO');
    $refSheet->setCellValue("B{$refRow}", $sch['id']);
    $refSheet->setCellValue("C{$refRow}", $sch['nombre']);
    $refRow++;
}

// SITUACIONES
echo "  - Situaciones...\n";
$situaciones = $db->query("SELECT id, nombre FROM situaciones ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
foreach ($situaciones as $sit) {
    $refSheet->setCellValue("A{$refRow}", 'SITUACIÓN');
    $refSheet->setCellValue("B{$refRow}", $sit['id']);
    $refSheet->setCellValue("C{$refRow}", $sit['nombre']);
    $refRow++;
}

// TIPOS DE PLANILLA
echo "  - Tipos de Planilla...\n";
$tipos = $db->query("SELECT id, nombre FROM tipos_planilla ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
foreach ($tipos as $tipo) {
    $refSheet->setCellValue("A{$refRow}", 'TIPO PLANILLA');
    $refSheet->setCellValue("B{$refRow}", $tipo['id']);
    $refSheet->setCellValue("C{$refRow}", $tipo['nombre']);
    $refRow++;
}

// CARGOS
echo "  - Cargos...\n";
$cargos = $db->query("SELECT id, nombre FROM cargos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cargos as $cargo) {
    $refSheet->setCellValue("A{$refRow}", 'CARGO');
    $refSheet->setCellValue("B{$refRow}", $cargo['id']);
    $refSheet->setCellValue("C{$refRow}", $cargo['nombre']);
    $refRow++;
}

// FUNCIONES
echo "  - Funciones...\n";
$funciones = $db->query("SELECT id, nombre FROM funciones ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
foreach ($funciones as $funcion) {
    $refSheet->setCellValue("A{$refRow}", 'FUNCIÓN');
    $refSheet->setCellValue("B{$refRow}", $funcion['id']);
    $refSheet->setCellValue("C{$refRow}", $funcion['nombre']);
    $refRow++;
}

// PARTIDAS
echo "  - Partidas...\n";
$partidas = $db->query("SELECT id, codigo, nombre FROM partidas ORDER BY codigo")->fetchAll(PDO::FETCH_ASSOC);
foreach ($partidas as $partida) {
    $refSheet->setCellValue("A{$refRow}", 'PARTIDA');
    $refSheet->setCellValue("B{$refRow}", $partida['id']);
    $refSheet->setCellValue("C{$refRow}", $partida['codigo'] . ' - ' . $partida['nombre']);
    $refRow++;
}

// ORGANIGRAMA
echo "  - Organigrama...\n";
$organigramas = $db->query("SELECT id, descripcion FROM organigrama ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
foreach ($organigramas as $org) {
    $refSheet->setCellValue("A{$refRow}", 'ORGANIGRAMA');
    $refSheet->setCellValue("B{$refRow}", $org['id']);
    $refSheet->setCellValue("C{$refRow}", $org['descripcion']);
    $refRow++;
}

// Ajustar ancho de columnas
$refSheet->getColumnDimension('A')->setWidth(15);
$refSheet->getColumnDimension('B')->setWidth(10);
$refSheet->getColumnDimension('C')->setWidth(50);

// =====================================
// HOJA DE INSTRUCCIONES
// =====================================
echo "Agregando hoja de Instrucciones...\n";

$instSheet = $spreadsheet->createSheet();
$instSheet->setTitle('Instrucciones');

$instructions = [
    'INSTRUCCIONES PARA IMPORTAR EMPLEADOS',
    '',
    '1. CAMPOS OBLIGATORIOS (marcados con *):',
    '   - CÓDIGO EMPLEADO: Debe ser único',
    '   - NOMBRES: Nombre del empleado',
    '   - APELLIDOS: Apellidos del empleado',
    '   - EMAIL: Correo electrónico válido (ej: usuario@empresa.com)',
    '   - FECHA NACIMIENTO: Formato YYYY-MM-DD (ej: 1985-03-15)',
    '   - FECHA INGRESO: Formato YYYY-MM-DD (ej: 2023-01-15)',
    '   - GÉNERO: M (Masculino) o F (Femenino)',
    '   - HORARIO ID: ID del horario (ver hoja Referencias)',
    '   - SITUACIÓN ID: ID de situación laboral (ver hoja Referencias)',
    '   - TIPO PLANILLA ID: ID del tipo de planilla (ver hoja Referencias)',
    '',
    '2. CAMPOS OPCIONALES (con valores por defecto):',
    '   - MARCA ASISTENCIA: 1=Sí, 0=No (Por defecto: 1)',
    '   - PERMITE HORAS EXTRAS: 1=Sí, 0=No (Por defecto: 1)',
    '   - POSICIÓN ID: Ver hoja Referencias (opcional)',
    '   - CARGO ID: Ver hoja Referencias (opcional)',
    '   - FUNCIÓN ID: Ver hoja Referencias (opcional)',
    '   - PARTIDA ID: Ver hoja Referencias (opcional)',
    '   - Si se dejan vacíos, el sistema asignará valores por defecto',
    '',
    '3. CAMPOS CONDICIONALES:',
    '   - FECHA VENC. CONTRATO: Obligatorio para contratos DEFINIDO, PROYECTO, TEMPORAL',
    '   - BANCO, NÚMERO CUENTA, TIPO CUENTA: Obligatorios para forma de pago CHEQUE/ACH',
    '',
    '4. VALORES PERMITIDOS:',
    '   - GÉNERO: M, F',
    '   - MARCA ASISTENCIA: 1, 0, SI, NO, YES, NO',
    '   - PERMITE HORAS EXTRAS: 1, 0, SI, NO, YES, NO',
    '   - TIPO CONTRATO: INDEFINIDO, DEFINIDO, PROYECTO, TEMPORAL',
    '   - FORMA PAGO: EFECTIVO, CHEQUE, ACH',
    '   - TIPO CUENTA: AHORROS, CORRIENTE',
    '',
    '5. FORMATO DE FECHAS: YYYY-MM-DD (ejemplo: 2023-12-31)',
    '',
    '6. CAMPOS ASIGNADOS AUTOMÁTICAMENTE:',
    '   - FOTO: Se asigna automáticamente la imagen por defecto',
    '     Ruta: images/facebook-profile-image.jpeg',
    '   - SALARIO POR TIPO DE PLANILLA: Se crea automáticamente un registro de salario',
    '     con el Sueldo Individual y Gastos de Representación especificados',
    '     para el Tipo de Planilla asignado al empleado',
    '   - FECHA INICIO SALARIO: Se usa la Fecha de Ingreso del empleado',
    '   - Puede modificar estos datos posteriormente desde el sistema',
    '',
    '7. HOJA DE REFERENCIAS:',
    '   - Consulte la hoja "Referencias" para ver todos los IDs válidos',
    '   - La hoja contiene 8 tablas: Posiciones, Horarios, Situaciones,',
    '     Tipos Planilla, Cargos, Funciones, Partidas y Organigrama',
    '   - Use los IDs de la columna B para llenar los campos correspondientes',
    '',
    '8. NOTAS IMPORTANTES:',
    '   - No modifique los headers (primera fila)',
    '   - Elimine las filas de ejemplo antes de importar',
    '   - Los campos numéricos deben ser números válidos',
    '   - El sistema validará duplicados de CÓDIGO EMPLEADO',
    '   - El email debe tener formato válido (ejemplo@dominio.com)',
    '   - Todos los empleados importados recibirán la foto por defecto',
    '   - Los IDs de referencia deben existir en la base de datos',
    '',
    '9. PROCESO DE IMPORTACIÓN:',
    '   1. Complete los datos de sus empleados en la hoja "Empleados Template"',
    '   2. Asegúrese de que los IDs de referencia sean correctos',
    '   3. Verifique que los emails tengan formato válido',
    '   4. Guarde el archivo Excel',
    '   5. Suba el archivo desde el módulo de importación',
    '   6. El sistema validará y creará los registros automáticamente'
];

$instRow = 1;
foreach ($instructions as $instruction) {
    $instSheet->setCellValue("A{$instRow}", $instruction);
    $instRow++;
}

// Ajustar ancho de columna
$instSheet->getColumnDimension('A')->setWidth(80);

// Seleccionar la primera hoja
$spreadsheet->setActiveSheetIndex(0);

echo "Guardando archivo en public/template_empleados.xlsx...\n";
$writer = new Xlsx($spreadsheet);
$writer->save(__DIR__ . '/public/template_empleados.xlsx');

echo "✅ Template en /public guardado\n";

echo "Guardando archivo en raíz template_empleados.xlsx...\n";
$writer->save(__DIR__ . '/template_empleados.xlsx');

echo "✅ Template en raíz guardado\n";

echo "\n=== COMPLETADO ===\n";
echo "Se generaron 2 archivos actualizados:\n";
echo "1. public/template_empleados.xlsx\n";
echo "2. template_empleados.xlsx\n\n";
echo "Estructura: 33 columnas (A-AD)\n";
echo "Campos nuevos:\n";
echo "  - D: EMAIL* (obligatorio)\n";
echo "  - U: MARCA ASISTENCIA (1=SI, 0=NO)\n";
echo "  - V: PERMITE HORAS EXTRAS (1=SI, 0=NO)\n";
echo "\nAhora puedes usar estos templates para importar empleados.\n";
