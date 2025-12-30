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
use App\Models\EmployeePayrollSalary;
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
    private $employeePayrollSalaryModel;

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
        $this->employeePayrollSalaryModel = new EmployeePayrollSalary();
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
            'page_title' => 'Importar Empleados',
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

        // Limpiar cualquier output previo
        if (ob_get_level()) ob_end_clean();

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Empleados Template');

            // Configurar headers
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
            $sheet->getStyle('A1:AD1')->applyFromArray($headerStyle);

            // Agregar ejemplos de datos
            $exampleData = [
                ['EMP001', 'Juan Carlos', 'Pérez González', 'jperez@empresa.com', 'Calle Principal #123', '1985-03-15', '2023-01-15', '6677-8899', 'M', '1', '1', '8-123-456', 'SS123456', '1', '1', '1', '1', '1', '1500.00', '200.00', '1', '1', 'INDEFINIDO', 'CT-2023-001', '2023-01-15', '', 'ACH', 'Banco Nacional', '123456789', 'AHORROS'],
                ['EMP002', 'María Elena', 'García López', 'mgarcia@empresa.com', 'Avenida Central #456', '1990-07-22', '2023-02-01', '6688-9900', 'F', '2', '1', '8-234-567', 'SS234567', '1', '2', '2', '2', '2', '1200.00', '150.00', '1', '0', 'DEFINIDO', 'CT-2023-002', '2023-02-01', '2024-02-01', 'CHEQUE', 'Banco Industrial', '987654321', 'CORRIENTE']
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
            $this->redirect(\App\Core\UrlHelper::route('panel/employees/import'));
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
                $refSheet->setCellValue("C{$row}", $pos['codigo']);
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

        // Cargos
        $cargos = $this->cargoModel->all();
        if ($cargos) {
            foreach ($cargos as $cargo) {
                $refSheet->setCellValue("A{$row}", 'CARGO');
                $refSheet->setCellValue("B{$row}", $cargo['id']);
                $refSheet->setCellValue("C{$row}", $cargo['nombre']);
                $row++;
            }
        }

        // Funciones
        $funciones = $this->funcionModel->all();
        if ($funciones) {
            foreach ($funciones as $funcion) {
                $refSheet->setCellValue("A{$row}", 'FUNCIÓN');
                $refSheet->setCellValue("B{$row}", $funcion['id']);
                $refSheet->setCellValue("C{$row}", $funcion['nombre']);
                $row++;
            }
        }

        // Partidas
        $partidas = $this->partidaModel->all();
        if ($partidas) {
            foreach ($partidas as $partida) {
                $refSheet->setCellValue("A{$row}", 'PARTIDA');
                $refSheet->setCellValue("B{$row}", $partida['id']);
                // Mostrar código y nombre concatenados
                $partidaDesc = trim($partida['codigo'] ?? '') . ' - ' . trim($partida['nombre'] ?? '');
                $refSheet->setCellValue("C{$row}", $partidaDesc);
                $row++;
            }
        }

        // Ajustar ancho de columnas
        $refSheet->getColumnDimension('A')->setWidth(15);
        $refSheet->getColumnDimension('B')->setWidth(10);
        $refSheet->getColumnDimension('C')->setWidth(50);
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
            '   - EMAIL: Correo electrónico válido',
            '   - FECHA NACIMIENTO: Formato YYYY-MM-DD',
            '   - FECHA INGRESO: Formato YYYY-MM-DD',
            '   - GÉNERO: M (Masculino) o F (Femenino)',
            '   - HORARIO ID: ID del horario (ver hoja Referencias)',
            '   - SITUACIÓN ID: ID de situación laboral (ver hoja Referencias)',
            '   - TIPO PLANILLA ID: ID del tipo de planilla (ver hoja Referencias)',
            '',
            '2. CAMPOS OPCIONALES (con valores por defecto):',
            '   - MARCA ASISTENCIA: 1=Sí, 0=No (Por defecto: 1)',
            '   - PERMITE HORAS EXTRAS: 1=Sí, 0=No (Por defecto: 1)',
            '   - Si se dejan vacíos, el sistema asignará 1 (Sí) automáticamente',
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
            '   - La hoja contiene 7 tablas: Posiciones, Horarios, Situaciones,',
            '     Tipos Planilla, Cargos, Funciones y Partidas',
            '   - Para Partidas se muestra: CÓDIGO - NOMBRE (ej: 6.10.10.001. - Sueldos)',
            '   - Use los IDs de la columna B para llenar los campos correspondientes',
            '',
            '8. NOTAS IMPORTANTES:',
            '   - No modifique los headers (primera fila)',
            '   - Elimine las filas de ejemplo antes de importar',
            '   - Los campos numéricos deben ser números válidos',
            '   - El sistema validará duplicados de CÓDIGO EMPLEADO',
            '   - El email debe tener formato válido (ejemplo@dominio.com)',
            '   - Todos los empleados importados recibirán la foto por defecto',
            '   - Los IDs de referencia deben existir en la base de datos'
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
            $this->redirect(\App\Core\UrlHelper::route('panel/employees/import'));
        }

        // 🔍 DEBUG: Verificar conexión a BD correcta
        error_log("=== INICIO IMPORTACIÓN EXCEL ===");
        error_log("Tenant en sesión: " . ($_SESSION['tenant_db'] ?? 'NONE'));
        try {
            $currentDb = $this->db->query("SELECT DATABASE()")->fetchColumn();
            error_log("BD conectada: {$currentDb}");
        } catch (\Exception $e) {
            error_log("Error verificando BD: " . $e->getMessage());
        }

        // Iniciar output buffering para evitar headers sent
        ob_start();

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

                    // Saltar filas completamente vacías (sin código de empleado y sin nombres)
                    if ($this->isEmptyRow($data)) {
                        continue; // No contar como error, simplemente saltar
                    }

                    // Debug: Log raw data para análisis
                    error_log("Fila {$row} - Datos extraídos: " . print_r($data, true));

                    // Validar datos
                    $validation = $this->validateEmployeeData($data, $row);
                    if (!$validation['valid']) {
                        error_log("Fila {$row} - Errores validación: " . implode(', ', $validation['errors']));
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

                    // Limpiar valores null de foreign keys para evitar errores de constraint
                    $cleanData = $this->cleanForeignKeyNulls($data);
                    $cleanData['created_on'] = date('Y-m-d'); // Solo fecha, no hora (campo es DATE no DATETIME)

                    // Asignar foto por defecto
                    $cleanData['photo'] = 'images/facebook-profile-image.jpeg';

                    // 🔍 DEBUG: Log datos antes de create()
                    error_log("=== ANTES DE CREATE() - Fila {$row} ===");
                    error_log("Clean Data Keys: " . implode(', ', array_keys($cleanData)));
                    error_log("Clean Data JSON: " . json_encode($cleanData, JSON_UNESCAPED_UNICODE));

                    // Crear empleado y obtener su ID
                    try {
                        $employeeId = $this->employeeModel->create($cleanData);
                        error_log("=== DESPUÉS DE CREATE() - Fila {$row} ===");
                        error_log("Employee ID retornado: " . ($employeeId ?: 'NULL/FALSE'));
                    } catch (\Exception $createException) {
                        error_log("=== EXCEPCIÓN EN CREATE() - Fila {$row} ===");
                        error_log("Error: " . $createException->getMessage());
                        error_log("Trace: " . $createException->getTraceAsString());
                        throw $createException;
                    }

                    if (!$employeeId) {
                        error_log("=== FALLO EN CREATE() - Fila {$row} ===");
                        error_log("create() retornó: " . var_export($employeeId, true));
                        throw new \Exception('No se pudo crear el empleado - create() retornó ID vacío');
                    }

                    error_log("=== EMPLEADO CREADO EXITOSAMENTE - Fila {$row} ===");
                    error_log("Employee ID: {$employeeId}");

                    // Crear registro de salario por tipo de planilla
                    $salaryData = [
                        'employee_id' => $employeeId,
                        'tipo_planilla_id' => $data['tipo_planilla_id'],
                        'sueldo_base' => $data['sueldo_individual'] ?? 0,
                        'gastos_representacion' => $data['gastos_representacion'] ?? 0,
                        'fecha_inicio' => $data['fecha_ingreso'],
                        'fecha_fin' => null,
                        'is_active' => 1,
                        'notes' => 'Creado automáticamente desde importación Excel',
                        'created_by' => $_SESSION['admin'] ?? null
                    ];

                    $salaryResult = $this->employeePayrollSalaryModel->saveSalary($salaryData);

                    if (!$salaryResult) {
                        error_log("Warning: No se pudo crear salario para empleado {$employeeId}, tipo_planilla {$data['tipo_planilla_id']}");
                    }

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

        // Limpiar output buffer antes de redirigir
        ob_end_clean();
        $this->redirect(\App\Core\UrlHelper::route('panel/employees/import'));
    }

    /**
     * Extraer datos de una fila
     */
    private function extractRowData($worksheet, $row)
    {
        return [
            'employee_id' => $this->safeTrim($worksheet->getCell("A{$row}")->getCalculatedValue() ?? ''),
            'firstname' => $this->safeTrim($worksheet->getCell("B{$row}")->getCalculatedValue() ?? ''),
            'lastname' => $this->safeTrim($worksheet->getCell("C{$row}")->getCalculatedValue() ?? ''),
            'email' => $this->safeTrim($worksheet->getCell("D{$row}")->getCalculatedValue() ?? ''),
            'address' => $this->safeTrim($worksheet->getCell("E{$row}")->getCalculatedValue() ?? ''),
            'birthdate' => $this->formatDate($worksheet->getCell("F{$row}")->getCalculatedValue()),
            'fecha_ingreso' => $this->formatDate($worksheet->getCell("G{$row}")->getCalculatedValue()),
            'contact_info' => $this->safeTrim($worksheet->getCell("H{$row}")->getCalculatedValue() ?? ''),
            'gender' => strtoupper($this->safeTrim($worksheet->getCell("I{$row}")->getCalculatedValue() ?? '')),
            'position_id' => $this->formatNumber($worksheet->getCell("J{$row}")->getCalculatedValue()),
            'schedule_id' => $this->formatNumber($worksheet->getCell("K{$row}")->getCalculatedValue()),
            'document_id' => $this->safeTrim($worksheet->getCell("L{$row}")->getCalculatedValue() ?? ''),
            'clave_seguro_social' => $this->safeTrim($worksheet->getCell("M{$row}")->getCalculatedValue() ?? ''),
            'situacion_id' => $this->formatNumber($worksheet->getCell("N{$row}")->getCalculatedValue()),
            'tipo_planilla_id' => $this->formatNumber($worksheet->getCell("O{$row}")->getCalculatedValue()),
            'cargo_id' => $this->formatNumber($worksheet->getCell("P{$row}")->getCalculatedValue()),
            'funcion_id' => $this->formatNumber($worksheet->getCell("Q{$row}")->getCalculatedValue()),
            'partida_id' => $this->formatNumber($worksheet->getCell("R{$row}")->getCalculatedValue()),
            'sueldo_individual' => $this->formatDecimal($worksheet->getCell("S{$row}")->getCalculatedValue()),
            'gastos_representacion' => $this->formatDecimal($worksheet->getCell("T{$row}")->getCalculatedValue()),
            'marca_asistencia' => $this->formatBoolean($worksheet->getCell("U{$row}")->getCalculatedValue()),
            'permite_horas_extras' => $this->formatBoolean($worksheet->getCell("V{$row}")->getCalculatedValue()),
            'tipo_contrato' => strtoupper($this->safeTrim($worksheet->getCell("W{$row}")->getCalculatedValue() ?? '')),
            'numero_contrato' => $this->safeTrim($worksheet->getCell("X{$row}")->getCalculatedValue() ?? ''),
            'fecha_inicio_contrato' => $this->formatDate($worksheet->getCell("Y{$row}")->getCalculatedValue()),
            'fecha_vencimiento_contrato' => $this->formatDate($worksheet->getCell("Z{$row}")->getCalculatedValue()),
            'forma_pago' => strtoupper($this->safeTrim($worksheet->getCell("AA{$row}")->getCalculatedValue() ?? '')),
            'banco' => $this->safeTrim($worksheet->getCell("AB{$row}")->getCalculatedValue() ?? ''),
            'numero_cuenta' => $this->safeTrim($worksheet->getCell("AC{$row}")->getCalculatedValue() ?? ''),
            'tipo_cuenta' => strtoupper($this->safeTrim($worksheet->getCell("AD{$row}")->getCalculatedValue() ?? ''))
        ];
    }

    /**
     * Validar datos del empleado
     */
    private function validateEmployeeData($data, $row)
    {
        $errors = [];

        // Campos obligatorios
        if (empty($data['employee_id'])) $errors[] = 'Código empleado requerido (Columna A)';
        if (empty($data['firstname'])) $errors[] = 'Nombres requeridos (Columna B)';
        if (empty($data['lastname'])) $errors[] = 'Apellidos requeridos (Columna C)';
        if (empty($data['email'])) $errors[] = 'Email requerido (Columna D)';
        if (empty($data['birthdate'])) $errors[] = 'Fecha nacimiento requerida (Columna F) - Formato: YYYY-MM-DD o DD/MM/YYYY';
        if (empty($data['fecha_ingreso'])) $errors[] = 'Fecha ingreso requerida (Columna G) - Formato: YYYY-MM-DD o DD/MM/YYYY';
        if (empty($data['gender'])) $errors[] = 'Género requerido (Columna I) - Valores: M o F';
        if (empty($data['schedule_id'])) $errors[] = 'Horario ID requerido (Columna K) - Ver hoja Referencias';
        if (empty($data['situacion_id'])) $errors[] = 'Situación ID requerida (Columna N) - Ver hoja Referencias';
        if (empty($data['tipo_planilla_id'])) $errors[] = 'Tipo planilla ID requerido (Columna O) - Ver hoja Referencias';

        // Validar formato de email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido (Columna D)';
        }

        // Validar género (convertir a mayúsculas en extractRowData, aquí solo validar)
        if (!empty($data['gender']) && !in_array($data['gender'], ['M', 'F'])) {
            $errors[] = 'Género debe ser M o F (Columna I) - Valores permitidos: M, F';
        }

        // Validar tipo contrato
        if (!empty($data['tipo_contrato']) && !in_array($data['tipo_contrato'], ['INDEFINIDO', 'DEFINIDO', 'PROYECTO', 'TEMPORAL'])) {
            $errors[] = 'Tipo contrato inválido (Columna W) - Valores permitidos: INDEFINIDO, DEFINIDO, PROYECTO, TEMPORAL';
        }

        // Validar fecha vencimiento para contratos definidos
        if (in_array($data['tipo_contrato'], ['DEFINIDO', 'PROYECTO', 'TEMPORAL']) && empty($data['fecha_vencimiento_contrato'])) {
            $errors[] = 'Fecha vencimiento requerida para contratos DEFINIDO/PROYECTO/TEMPORAL (Columna Z)';
        }

        // Validar forma de pago
        if (!empty($data['forma_pago']) && !in_array($data['forma_pago'], ['EFECTIVO', 'CHEQUE', 'ACH'])) {
            $errors[] = 'Forma de pago inválida (Columna AA) - Valores permitidos: EFECTIVO, CHEQUE, ACH';
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

        // Validar foreign keys OBLIGATORIOS
        if (!empty($data['schedule_id'])) {
            $schedule = $this->scheduleModel->find($data['schedule_id']);
            if (!$schedule) {
                $errors[] = "Horario ID '{$data['schedule_id']}' no existe (Columna K) - Ver hoja Referencias para IDs válidos";
            }
        }

        if (!empty($data['situacion_id'])) {
            $situacion = $this->situacionModel->find($data['situacion_id']);
            if (!$situacion) {
                $errors[] = "Situación ID '{$data['situacion_id']}' no existe (Columna N) - Ver hoja Referencias para IDs válidos";
            }
        }

        if (!empty($data['tipo_planilla_id'])) {
            $tipoPlanilla = $this->tipoPlanillaModel->find($data['tipo_planilla_id']);
            if (!$tipoPlanilla) {
                $errors[] = "Tipo planilla ID '{$data['tipo_planilla_id']}' no existe (Columna O) - Ver hoja Referencias para IDs válidos";
            }
        }

        // Validar foreign keys OPCIONALES (solo si se proporcionan)
        if (!empty($data['position_id'])) {
            $position = $this->positionModel->find($data['position_id']);
            if (!$position) {
                $errors[] = "Posición ID '{$data['position_id']}' no existe (Columna J) - Ver hoja Referencias o deje vacío";
            }
        }

        if (!empty($data['cargo_id'])) {
            $cargo = $this->cargoModel->find($data['cargo_id']);
            if (!$cargo) {
                $errors[] = "Cargo ID '{$data['cargo_id']}' no existe (Columna P) - Ver hoja Referencias o deje vacío";
            }
        }

        if (!empty($data['funcion_id'])) {
            $funcion = $this->funcionModel->find($data['funcion_id']);
            if (!$funcion) {
                $errors[] = "Función ID '{$data['funcion_id']}' no existe (Columna Q) - Ver hoja Referencias o deje vacío";
            }
        }

        if (!empty($data['partida_id'])) {
            $partida = $this->partidaModel->find($data['partida_id']);
            if (!$partida) {
                $errors[] = "Partida ID '{$data['partida_id']}' no existe (Columna R) - Ver hoja Referencias o deje vacío";
            }
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
        if ($value === null || $value === '') return null;

        try {
            // Si es un número serial de Excel
            if (is_numeric($value)) {
                // Verificar que sea un número de fecha válido de Excel (mayor a 1)
                if ($value > 1) {
                    $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                    return $date->format('Y-m-d');
                }
                return null;
            }

            // Si es un string de fecha, intentar parsear diferentes formatos
            $value = trim($value);
            if (empty($value)) return null;

            // Intentar con formato directo Y-m-d
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $date = new \DateTime($value);
                return $date->format('Y-m-d');
            }

            // Intentar con formato d/m/Y
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                $date = \DateTime::createFromFormat('d/m/Y', $value);
                if ($date) return $date->format('Y-m-d');
            }

            // Intentar con formato m/d/Y
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                $date = \DateTime::createFromFormat('m/d/Y', $value);
                if ($date) return $date->format('Y-m-d');
            }

            // Último intento con DateTime genérico
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            // Log del error para debug
            error_log("Error formateando fecha '{$value}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Formatear número
     */
    private function formatNumber($value)
    {
        if ($value === null || $value === '' || $value === 0) return null;

        $value = trim($value);
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
     * Trim seguro que maneja valores null
     */
    private function safeTrim($value)
    {
        if ($value === null || $value === '') return '';
        return trim((string)$value);
    }

    /**
     * Formatear valor boolean
     */
    private function formatBoolean($value)
    {
        if ($value === null || $value === '') return 1; // Default: true

        // Convertir string a minúsculas para comparación
        $stringValue = strtolower(trim((string)$value));

        // Valores que se consideran false
        if (in_array($stringValue, ['0', 'no', 'false', 'n'])) {
            return 0;
        }

        // Valores que se consideran true
        if (in_array($stringValue, ['1', 'yes', 'si', 'sí', 'true', 's', 'y'])) {
            return 1;
        }

        // Por defecto, si es numérico mayor a 0, es true
        if (is_numeric($value)) {
            return $value > 0 ? 1 : 0;
        }

        // Default: true
        return 1;
    }

    /**
     * Limpiar foreign keys null para evitar errores de constraint
     */
    private function cleanForeignKeyNulls($data)
    {
        $foreignKeys = [
            'position_id',
            'schedule_id',
            'situacion_id',
            'tipo_planilla_id',
            'cargo_id',
            'funcion_id',
            'partida_id',
            'departamento_id'
        ];

        foreach ($foreignKeys as $key) {
            if (array_key_exists($key, $data) && ($data[$key] === null || $data[$key] === '')) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Verificar si una fila está completamente vacía
     * Una fila se considera vacía si no tiene código de empleado, nombres ni apellidos
     */
    private function isEmptyRow($data)
    {
        // Verificar los campos más críticos
        $isEmpty = empty($data['employee_id']) &&
                   empty($data['firstname']) &&
                   empty($data['lastname']);

        return $isEmpty;
    }

    /**
     * Verificar autenticación del usuario
     */
    protected function requireAuth()
    {
        \App\Middleware\AuthMiddleware::requireAuth();
    }
}