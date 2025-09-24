<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Schedule;
use App\Models\Situacion;
use App\Models\TipoPlanilla;
use App\Models\Cargo;
use App\Models\Funcion;
use App\Models\Partida;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class EmployeeImportController extends Controller
{
    private $employeeModel;
    private $positionModel;
    private $scheduleModel;
    private $situacionModel;
    private $tipoPlanillaModel;
    private $cargoModel;
    private $funcionModel;
    private $partidaModel;

    public function __construct()
    {
        parent::__construct();
        $this->employeeModel = new Employee();
        $this->positionModel = new Position();
        $this->scheduleModel = new Schedule();
        $this->situacionModel = new Situacion();
        $this->tipoPlanillaModel = new TipoPlanilla();
        $this->cargoModel = new Cargo();
        $this->funcionModel = new Funcion();
        $this->partidaModel = new Partida();
    }

    /**
     * Mostrar página principal de importación
     */
    public function index()
    {
        $this->requireAuth();

        // Obtener contador de empleados actuales
        $employeeCount = $this->employeeModel->count();

        $this->render('admin/employees/import', [
            'title' => 'Importar Empleados desde Excel',
            'breadcrumb' => 'Empleados > Importar Excel',
            'employeeCount' => $employeeCount
        ]);
    }

    /**
     * Generar template Excel para importación
     */
    public function downloadTemplate()
    {
        $this->requireAuth();

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Empleados Template');

            // Configurar headers
            $headers = [
                'A1' => ['value' => 'CÓDIGO EMPLEADO*', 'width' => 18],
                'B1' => ['value' => 'NOMBRES*', 'width' => 20],
                'C1' => ['value' => 'APELLIDOS*', 'width' => 20],
                'D1' => ['value' => 'DIRECCIÓN', 'width' => 30],
                'E1' => ['value' => 'FECHA NACIMIENTO*', 'width' => 18],
                'F1' => ['value' => 'FECHA INGRESO*', 'width' => 18],
                'G1' => ['value' => 'CONTACTO', 'width' => 15],
                'H1' => ['value' => 'GÉNERO*', 'width' => 12],
                'I1' => ['value' => 'POSICIÓN ID', 'width' => 15],
                'J1' => ['value' => 'HORARIO ID*', 'width' => 15],
                'K1' => ['value' => 'DOCUMENTO ID', 'width' => 18],
                'L1' => ['value' => 'SEGURO SOCIAL', 'width' => 18],
                'M1' => ['value' => 'SITUACIÓN ID*', 'width' => 15],
                'N1' => ['value' => 'TIPO PLANILLA ID*', 'width' => 18],
                'O1' => ['value' => 'CARGO ID', 'width' => 12],
                'P1' => ['value' => 'FUNCIÓN ID', 'width' => 12],
                'Q1' => ['value' => 'PARTIDA ID', 'width' => 12],
                'R1' => ['value' => 'SUELDO INDIVIDUAL', 'width' => 18],
                'S1' => ['value' => 'GASTOS REPRES.', 'width' => 16],
                'T1' => ['value' => 'TIPO CONTRATO', 'width' => 16],
                'U1' => ['value' => 'NÚMERO CONTRATO', 'width' => 18],
                'V1' => ['value' => 'FECHA INICIO CONTRATO', 'width' => 20],
                'W1' => ['value' => 'FECHA VENC. CONTRATO', 'width' => 20],
                'X1' => ['value' => 'FORMA PAGO', 'width' => 14],
                'Y1' => ['value' => 'BANCO', 'width' => 20],
                'Z1' => ['value' => 'NÚMERO CUENTA', 'width' => 18],
                'AA1' => ['value' => 'TIPO CUENTA', 'width' => 14]
            ];

            // Aplicar headers y estilos
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
            $sheet->getStyle('A1:AA1')->applyFromArray($headerStyle);

            // Agregar ejemplos de datos
            $exampleData = [
                ['EMP001', 'Juan Carlos', 'Pérez González', 'Calle Principal #123', '1985-03-15', '2023-01-15', '6677-8899', 'M', '1', '1', '8-123-456', 'SS123456', '1', '1', '1', '1', '1', '1500.00', '200.00', 'INDEFINIDO', 'CT-2023-001', '2023-01-15', '', 'ACH', 'Banco Nacional', '123456789', 'AHORROS'],
                ['EMP002', 'María Elena', 'García López', 'Avenida Central #456', '1990-07-22', '2023-02-01', '6688-9900', 'F', '2', '1', '8-234-567', 'SS234567', '1', '2', '2', '2', '2', '1200.00', '150.00', 'DEFINIDO', 'CT-2023-002', '2023-02-01', '2024-02-01', 'CHEQUE', 'Banco Industrial', '987654321', 'CORRIENTE']
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

            // Agregar hoja de referencia
            $this->addReferenceSheet($spreadsheet);

            // Agregar hoja de instrucciones
            $this->addInstructionsSheet($spreadsheet);

            // Seleccionar la primera hoja
            $spreadsheet->setActiveSheetIndex(0);

            // Generar y descargar archivo
            $filename = 'template_empleados_' . date('Y-m-d_H-i-s') . '.xlsx';
            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al generar template: ' . $e->getMessage();
            $this->redirect('/admin/employees/import');
        }
    }

    /**
     * Agregar hoja de referencias
     */
    private function addReferenceSheet($spreadsheet)
    {
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Referencias');

        // Headers
        $refSheet->setCellValue('A1', 'TABLA');
        $refSheet->setCellValue('B1', 'ID');
        $refSheet->setCellValue('C1', 'DESCRIPCIÓN');

        // Estilo para headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]
        ];
        $refSheet->getStyle('A1:C1')->applyFromArray($headerStyle);

        $row = 2;

        // Posiciones
        $positions = $this->positionModel->all();
        if ($positions) {
            foreach ($positions as $pos) {
                $refSheet->setCellValue("A{$row}", 'POSICIÓN');
                $refSheet->setCellValue("B{$row}", $pos['id']);
                $refSheet->setCellValue("C{$row}", $pos['description']);
                $row++;
            }
        }

        // Horarios
        $schedules = $this->scheduleModel->all();
        if ($schedules) {
            foreach ($schedules as $sch) {
                $refSheet->setCellValue("A{$row}", 'HORARIO');
                $refSheet->setCellValue("B{$row}", $sch['id']);
                $refSheet->setCellValue("C{$row}", $sch['nombre']);
                $row++;
            }
        }

        // Situaciones
        $situaciones = $this->situacionModel->all();
        if ($situaciones) {
            foreach ($situaciones as $sit) {
                $refSheet->setCellValue("A{$row}", 'SITUACIÓN');
                $refSheet->setCellValue("B{$row}", $sit['id']);
                $refSheet->setCellValue("C{$row}", $sit['nombre']);
                $row++;
            }
        }

        // Tipos de Planilla
        $tipos = $this->tipoPlanillaModel->all();
        if ($tipos) {
            foreach ($tipos as $tipo) {
                $refSheet->setCellValue("A{$row}", 'TIPO PLANILLA');
                $refSheet->setCellValue("B{$row}", $tipo['id']);
                $refSheet->setCellValue("C{$row}", $tipo['nombre']);
                $row++;
            }
        }

        // Ajustar ancho de columnas
        $refSheet->getColumnDimension('A')->setWidth(15);
        $refSheet->getColumnDimension('B')->setWidth(10);
        $refSheet->getColumnDimension('C')->setWidth(40);
    }

    /**
     * Agregar hoja de instrucciones
     */
    private function addInstructionsSheet($spreadsheet)
    {
        $instSheet = $spreadsheet->createSheet();
        $instSheet->setTitle('Instrucciones');

        $instructions = [
            'INSTRUCCIONES PARA IMPORTAR EMPLEADOS',
            '',
            '1. CAMPOS OBLIGATORIOS (marcados con *):',
            '   - CÓDIGO EMPLEADO: Debe ser único',
            '   - NOMBRES: Nombre del empleado',
            '   - APELLIDOS: Apellidos del empleado',
            '   - FECHA NACIMIENTO: Formato YYYY-MM-DD',
            '   - FECHA INGRESO: Formato YYYY-MM-DD',
            '   - GÉNERO: M (Masculino) o F (Femenino)',
            '   - HORARIO ID: ID del horario (ver hoja Referencias)',
            '   - SITUACIÓN ID: ID de situación laboral (ver hoja Referencias)',
            '   - TIPO PLANILLA ID: ID del tipo de planilla (ver hoja Referencias)',
            '',
            '2. CAMPOS CONDICIONALES:',
            '   - FECHA VENC. CONTRATO: Obligatorio para contratos DEFINIDO, PROYECTO, TEMPORAL',
            '   - BANCO, NÚMERO CUENTA, TIPO CUENTA: Obligatorios para forma de pago CHEQUE/ACH',
            '',
            '3. VALORES PERMITIDOS:',
            '   - GÉNERO: M, F',
            '   - TIPO CONTRATO: INDEFINIDO, DEFINIDO, PROYECTO, TEMPORAL',
            '   - FORMA PAGO: EFECTIVO, CHEQUE, ACH',
            '   - TIPO CUENTA: AHORROS, CORRIENTE',
            '',
            '4. FORMATO DE FECHAS: YYYY-MM-DD (ejemplo: 2023-12-31)',
            '',
            '5. NOTAS IMPORTANTES:',
            '   - No modifique los headers (primera fila)',
            '   - Elimine las filas de ejemplo antes de importar',
            '   - Consulte la hoja "Referencias" para IDs válidos',
            '   - Los campos numéricos deben ser números válidos',
            '   - El sistema validará duplicados de CÓDIGO EMPLEADO'
        ];

        $row = 1;
        foreach ($instructions as $instruction) {
            $instSheet->setCellValue("A{$row}", $instruction);
            $row++;
        }

        // Ajustar ancho de columna
        $instSheet->getColumnDimension('A')->setWidth(80);
    }

    /**
     * Procesar importación de archivo Excel
     */
    public function import()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/panel/employees/import');
        }

        try {
            // Verificar si se subió archivo
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception('No se pudo cargar el archivo Excel');
            }

            $inputFileName = $_FILES['excel_file']['tmp_name'];

            // Cargar archivo Excel
            $spreadsheet = IOFactory::load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();

            if ($highestRow < 2) {
                throw new \Exception('El archivo no contiene datos para importar');
            }

            $errors = [];
            $imported = 0;
            $skipped = 0;

            // Procesar cada fila (empezar desde la fila 2, saltando headers)
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    $data = $this->extractRowData($worksheet, $row);

                    // Validar datos
                    $validation = $this->validateEmployeeData($data, $row);
                    if (!$validation['valid']) {
                        $errors[] = "Fila {$row}: " . implode(', ', $validation['errors']);
                        $skipped++;
                        continue;
                    }

                    // Verificar si el empleado ya existe
                    $existing = $this->employeeModel->findByEmployeeId($data['employee_id']);
                    if ($existing) {
                        $errors[] = "Fila {$row}: El código de empleado '{$data['employee_id']}' ya existe";
                        $skipped++;
                        continue;
                    }

                    // Crear empleado
                    $data['created_on'] = date('Y-m-d H:i:s');
                    $this->employeeModel->create($data);
                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Fila {$row}: " . $e->getMessage();
                    $skipped++;
                }
            }

            // Preparar mensaje de resultado
            $message = "Importación completada: {$imported} empleados importados";
            if ($skipped > 0) {
                $message .= ", {$skipped} filas omitidas";
            }

            $_SESSION['success'] = $message;
            if (!empty($errors)) {
                $_SESSION['import_errors'] = $errors;
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error en la importación: ' . $e->getMessage();
        }

        $this->redirect('/panel/employees/import');
    }

    /**
     * Extraer datos de una fila
     */
    private function extractRowData($worksheet, $row)
    {
        return [
            'employee_id' => trim($worksheet->getCell("A{$row}")->getCalculatedValue()),
            'firstname' => trim($worksheet->getCell("B{$row}")->getCalculatedValue()),
            'lastname' => trim($worksheet->getCell("C{$row}")->getCalculatedValue()),
            'address' => trim($worksheet->getCell("D{$row}")->getCalculatedValue()),
            'birthdate' => $this->formatDate($worksheet->getCell("E{$row}")->getCalculatedValue()),
            'fecha_ingreso' => $this->formatDate($worksheet->getCell("F{$row}")->getCalculatedValue()),
            'contact_info' => trim($worksheet->getCell("G{$row}")->getCalculatedValue()),
            'gender' => strtoupper(trim($worksheet->getCell("H{$row}")->getCalculatedValue())),
            'position_id' => $this->formatNumber($worksheet->getCell("I{$row}")->getCalculatedValue()),
            'schedule_id' => $this->formatNumber($worksheet->getCell("J{$row}")->getCalculatedValue()),
            'document_id' => trim($worksheet->getCell("K{$row}")->getCalculatedValue()),
            'clave_seguro_social' => trim($worksheet->getCell("L{$row}")->getCalculatedValue()),
            'situacion_id' => $this->formatNumber($worksheet->getCell("M{$row}")->getCalculatedValue()),
            'tipo_planilla_id' => $this->formatNumber($worksheet->getCell("N{$row}")->getCalculatedValue()),
            'cargo_id' => $this->formatNumber($worksheet->getCell("O{$row}")->getCalculatedValue()),
            'funcion_id' => $this->formatNumber($worksheet->getCell("P{$row}")->getCalculatedValue()),
            'partida_id' => $this->formatNumber($worksheet->getCell("Q{$row}")->getCalculatedValue()),
            'sueldo_individual' => $this->formatDecimal($worksheet->getCell("R{$row}")->getCalculatedValue()),
            'gastos_representacion' => $this->formatDecimal($worksheet->getCell("S{$row}")->getCalculatedValue()),
            'tipo_contrato' => strtoupper(trim($worksheet->getCell("T{$row}")->getCalculatedValue())),
            'numero_contrato' => trim($worksheet->getCell("U{$row}")->getCalculatedValue()),
            'fecha_inicio_contrato' => $this->formatDate($worksheet->getCell("V{$row}")->getCalculatedValue()),
            'fecha_vencimiento_contrato' => $this->formatDate($worksheet->getCell("W{$row}")->getCalculatedValue()),
            'forma_pago' => strtoupper(trim($worksheet->getCell("X{$row}")->getCalculatedValue())),
            'banco' => trim($worksheet->getCell("Y{$row}")->getCalculatedValue()),
            'numero_cuenta' => trim($worksheet->getCell("Z{$row}")->getCalculatedValue()),
            'tipo_cuenta' => strtoupper(trim($worksheet->getCell("AA{$row}")->getCalculatedValue()))
        ];
    }

    /**
     * Validar datos del empleado
     */
    private function validateEmployeeData($data, $row)
    {
        $errors = [];

        // Campos obligatorios
        if (empty($data['employee_id'])) $errors[] = 'Código empleado requerido';
        if (empty($data['firstname'])) $errors[] = 'Nombres requeridos';
        if (empty($data['lastname'])) $errors[] = 'Apellidos requeridos';
        if (empty($data['birthdate'])) $errors[] = 'Fecha nacimiento requerida';
        if (empty($data['fecha_ingreso'])) $errors[] = 'Fecha ingreso requerida';
        if (empty($data['gender'])) $errors[] = 'Género requerido';
        if (empty($data['schedule_id'])) $errors[] = 'Horario ID requerido';
        if (empty($data['situacion_id'])) $errors[] = 'Situación ID requerida';
        if (empty($data['tipo_planilla_id'])) $errors[] = 'Tipo planilla ID requerido';

        // Validar género
        if (!empty($data['gender']) && !in_array($data['gender'], ['M', 'F'])) {
            $errors[] = 'Género debe ser M o F';
        }

        // Validar tipo contrato
        if (!empty($data['tipo_contrato']) && !in_array($data['tipo_contrato'], ['INDEFINIDO', 'DEFINIDO', 'PROYECTO', 'TEMPORAL'])) {
            $errors[] = 'Tipo contrato inválido';
        }

        // Validar fecha vencimiento para contratos definidos
        if (in_array($data['tipo_contrato'], ['DEFINIDO', 'PROYECTO', 'TEMPORAL']) && empty($data['fecha_vencimiento_contrato'])) {
            $errors[] = 'Fecha vencimiento requerida para este tipo de contrato';
        }

        // Validar forma de pago
        if (!empty($data['forma_pago']) && !in_array($data['forma_pago'], ['EFECTIVO', 'CHEQUE', 'ACH'])) {
            $errors[] = 'Forma de pago inválida';
        }

        // Validar datos bancarios para CHEQUE/ACH
        if (in_array($data['forma_pago'], ['CHEQUE', 'ACH'])) {
            if (empty($data['banco'])) $errors[] = 'Banco requerido para esta forma de pago';
            if (empty($data['numero_cuenta'])) $errors[] = 'Número cuenta requerido para esta forma de pago';
            if (empty($data['tipo_cuenta'])) $errors[] = 'Tipo cuenta requerido para esta forma de pago';
        }

        // Validar tipo cuenta
        if (!empty($data['tipo_cuenta']) && !in_array($data['tipo_cuenta'], ['AHORROS', 'CORRIENTE'])) {
            $errors[] = 'Tipo cuenta debe ser AHORROS o CORRIENTE';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Formatear fecha desde Excel
     */
    private function formatDate($value)
    {
        if (empty($value)) return null;

        try {
            // Si es un número serial de Excel
            if (is_numeric($value)) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            }

            // Si es un string de fecha
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Formatear número
     */
    private function formatNumber($value)
    {
        if (empty($value)) return null;
        return is_numeric($value) ? (int)$value : null;
    }

    /**
     * Formatear decimal
     */
    private function formatDecimal($value)
    {
        if (empty($value)) return null;
        return is_numeric($value) ? (float)$value : null;
    }

    /**
     * Verificar autenticación del usuario
     */
    protected function requireAuth()
    {
        \App\Middleware\AuthMiddleware::requireAuth();
    }
}