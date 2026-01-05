<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Employee;
use App\Models\Concept;
use App\Models\TipoAcumulado;
use App\Models\Frecuencia;
use App\Models\TipoPlanilla;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AccumulatedImportController extends Controller
{
    private $employeeModel;
    private $conceptModel;
    private $tipoAcumuladoModel;
    private $frecuenciaModel;
    private $tipoPlanillaModel;

    public function __construct()
    {
        parent::__construct();
        $this->employeeModel = new Employee();
        $this->conceptModel = new Concept();
        $this->tipoAcumuladoModel = new TipoAcumulado();
        $this->frecuenciaModel = new Frecuencia();
        $this->tipoPlanillaModel = new TipoPlanilla();
    }

    /**
     * Mostrar formulario principal de importación
     */
    public function index()
    {
        $this->requireAuth();

        // Contadores para resumen rápido
        $totalEmployees = $this->employeeModel->count();
        $totalAcumulados = 0;
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM acumulados_por_empleado");
            $totalAcumulados = (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            error_log("Error contando acumulados: " . $e->getMessage());
        }

        $this->render('admin/accumulated/import', [
            'title' => 'Importar Acumulados',
            'page_title' => 'Importar Acumulados desde Excel',
            'breadcrumb' => 'Acumulados > Importar Excel',
            'totalEmployees' => $totalEmployees,
            'totalAcumulados' => $totalAcumulados
        ]);
    }

    /**
     * Descargar plantilla dinámica
     */
    public function downloadTemplate()
    {
        $this->requireAuth();

        if (ob_get_length()) {
            ob_end_clean();
        }

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Importar Acumulados');

            $headers = [
                'A1' => ['value' => 'CÓDIGO EMPLEADO*', 'width' => 18],
                'B1' => ['value' => 'CÓDIGO CONCEPTO*', 'width' => 18],
                'C1' => ['value' => 'MONTO*', 'width' => 14],
                'D1' => ['value' => 'MES* (1-12)', 'width' => 12],
                'E1' => ['value' => 'AÑO* (YYYY)', 'width' => 12],
                'F1' => ['value' => 'FRECUENCIA* (ID/CÓDIGO)', 'width' => 24],
                'G1' => ['value' => 'PLANILLA ID (opcional)', 'width' => 18],
                'H1' => ['value' => 'TIPO PLANILLA (ID/CODIGO)', 'width' => 24],
                'I1' => ['value' => 'TIPO ACUMULADO (opcional)', 'width' => 26],
            ];

            foreach ($headers as $cell => $config) {
                $sheet->setCellValue($cell, $config['value']);
                $sheet->getColumnDimension(substr($cell, 0, -1))->setWidth($config['width']);
            }

            // Forzar texto en columnas que requieren ceros a la izquierda
            $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            $sheet->getStyle('H:H')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            $sheet->getStyle('I:I')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

            $sheet->getStyle('A1:H1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            // Ejemplos
            $examples = [
                ['EMP001', 'SALARIO', 850.00, 1, date('Y'), 'quincenal', 0, 'MENSUAL', 'XIII_MES'],
                ['EMP002', 'ISR', -50.25, 1, date('Y'), 2, '', 'MENSUAL', 'DEDUCCION'],
                ['EMP003', '01', 1200.00, 2, date('Y'), 'mensual', 0, 'MENSUAL', 'CONCEPTO'],
            ];

            $row = 2;
            foreach ($examples as $example) {
                $col = 'A';
                foreach ($example as $value) {
                    $cell = $col . $row;
                    if (in_array($col, ['B', 'H', 'I'])) {
                        $sheet->setCellValueExplicit($cell, (string)$value, DataType::TYPE_STRING);
                    } else {
                        $sheet->setCellValue($cell, $value);
                    }
                    $col++;
                }
                $row++;
            }

            $this->addReferenceSheet($spreadsheet);
            $this->addInstructionsSheet($spreadsheet);
            $spreadsheet->setActiveSheetIndex(0);

            $filename = 'template_acumulados_' . date('Y-m-d_H-i-s') . '.xlsx';
            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al generar la plantilla: ' . $e->getMessage();
            $this->redirect(\App\Core\UrlHelper::route('panel/accumulated/import'));
        }
    }

    /**
     * Procesar importación
     */
    public function import()
    {
        $this->requireAuth();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::route('panel/accumulated/import'));
        }

        \App\Middleware\AuthMiddleware::validateCSRF();

        ob_start();

        try {
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception('No se pudo cargar el archivo Excel');
            }

            $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            if ($highestRow < 2) {
                throw new \Exception('El archivo no contiene datos para importar');
            }

            // Precargar catálogos para validar rápido
            [$employeesByCode, $employeesByDocument] = $this->getEmployeesIndexed();
            $conceptsByCode = $this->getConceptsIndexed();
            $frecuenciasByCode = $this->getFrecuenciasIndexed();
            $tiposAcumuladosByCode = $this->getTiposAcumuladosIndexed();
            $tiposPlanillaByCode = $this->getTiposPlanillaIndexed();

            $insertStmt = $this->db->prepare("
                INSERT INTO acumulados_por_empleado
                (employee_id, concepto_id, planilla_id, tipo_planilla_id, monto, mes, ano, frecuencia, tipo_concepto, tipo_acumulado, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $existsStmt = $this->db->prepare("
                SELECT COUNT(*) FROM acumulados_por_empleado 
                WHERE employee_id = ? AND concepto_id = ? AND ano = ? AND mes = ? AND planilla_id = ? AND ((tipo_planilla_id = ?) OR (tipo_planilla_id IS NULL AND ? IS NULL))
                AND ((tipo_acumulado = ?) OR (tipo_acumulado IS NULL AND ? IS NULL))
            ");

            $errors = [];
            $imported = 0;
            $skipped = 0;

            for ($row = 2; $row <= $highestRow; $row++) {
                $data = $this->extractRow($sheet, $row);

                if ($this->isEmptyRow($data)) {
                    continue;
                }

                $validation = $this->validateRow($data, $row, $employeesByCode, $employeesByDocument, $conceptsByCode, $frecuenciasByCode, $tiposAcumuladosByCode, $tiposPlanillaByCode);
                if (!$validation['valid']) {
                    $errors[] = $validation['message'];
                    $skipped++;
                    continue;
                }

                // Resolver empleado por código o cédula
                $employeeLookupCode = strtoupper($data['employee_code']);
                $employee = $employeesByCode[$employeeLookupCode] ?? null;

                if (!$employee && isset($employeesByDocument[$employeeLookupCode])) {
                    $employee = $employeesByDocument[$employeeLookupCode];
                }
                $concept = $conceptsByCode[strtoupper($data['concept_code'])];
                $frecuenciaId = $validation['frecuencia_id'];
                $tipoPlanillaId = $validation['tipo_planilla_id'] ?? null;
                $tipoAcumulado = $validation['tipo_acumulado'] ?: strtoupper($data['concept_code']);
                // Acumulados importados siempre tienen planilla_id = 0 (no asociados a planilla)
                $planillaId = !empty($data['planilla_id']) ? (int)$data['planilla_id'] : 0;

                // Evitar duplicados
                $existsStmt->execute([
                    $employee['id'],
                    $concept['id'],
                    $data['year'],
                    $data['month'],
                    $planillaId,
                    $tipoPlanillaId,
                    $tipoPlanillaId,
                    $tipoAcumulado,
                    $tipoAcumulado
                ]);

                if ($existsStmt->fetchColumn() > 0) {
                    $errors[] = "Fila {$row}: ya existe un acumulado para empleado {$data['employee_code']}, concepto {$data['concept_code']} en {$data['month']}/{$data['year']} (planilla {$planillaId}).";
                    $skipped++;
                    continue;
                }

                $tipoConcepto = $this->mapTipoConcepto($concept['tipo_concepto']);

                $insertStmt->execute([
                    $employee['id'],
                    $concept['id'],
                    $planillaId,
                    $tipoPlanillaId,
                    $data['amount'],
                    $data['month'],
                    $data['year'],
                    $frecuenciaId,
                    $tipoConcepto,
                    $tipoAcumulado
                ]);

                $imported++;
            }

            $message = "Importación completada: {$imported} registros creados";
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

        ob_end_clean();
        $this->redirect(\App\Core\UrlHelper::route('panel/accumulated/import'));
    }

    /**
     * Extraer una fila del Excel
     */
    private function extractRow($sheet, $row)
    {
        return [
            // Conservar ceros a la izquierda para el código de empleado
            'employee_code' => $this->safeTrim($sheet->getCell("A{$row}")->getFormattedValue()),
            // Usar getFormattedValue para conservar ceros a la izquierda
            'concept_code' => $this->safeTrim($sheet->getCell("B{$row}")->getFormattedValue()),
            'amount' => $this->formatDecimal($sheet->getCell("C{$row}")->getCalculatedValue()),
            'month' => $this->formatNumber($sheet->getCell("D{$row}")->getCalculatedValue()),
            'year' => $this->formatNumber($sheet->getCell("E{$row}")->getCalculatedValue()),
            'frecuencia' => $this->safeTrim($sheet->getCell("F{$row}")->getCalculatedValue()),
            'planilla_id' => $this->formatNumber($sheet->getCell("G{$row}")->getCalculatedValue()),
            // Conservar ceros a la izquierda en tipo planilla (ID o código)
            'tipo_planilla' => $this->safeTrim($sheet->getCell("H{$row}")->getFormattedValue()),
            // Conservar ceros a la izquierda en tipo acumulado
            'tipo_acumulado' => strtoupper($this->safeTrim($sheet->getCell("I{$row}")->getFormattedValue())),
        ];
    }

    /**
     * Validar datos de una fila
     */
    private function validateRow(array $data, int $row, array $employeesByCode, array $employeesByDocument, array $conceptsByCode, array $frecuenciasByCode, array $tiposAcumuladosByCode, array $tiposPlanillaByCode)
    {
        $errors = [];

        if (empty($data['employee_code'])) {
            $errors[] = 'Código de empleado requerido (Columna A)';
        }

        if (empty($data['concept_code'])) {
            $errors[] = 'Código de concepto requerido (Columna B)';
        }

        if ($data['amount'] === null || $data['amount'] === '') {
            $errors[] = 'Monto requerido (Columna C)';
        }

        if (empty($data['month']) || $data['month'] < 1 || $data['month'] > 12) {
            $errors[] = 'Mes inválido (Columna D)';
        }

        if (empty($data['year']) || $data['year'] < 2000 || $data['year'] > 2100) {
            $errors[] = 'Año inválido (Columna E)';
        }

        if (empty($data['frecuencia'])) {
            $errors[] = 'Frecuencia requerida (Columna F)';
        }

        // Validar existencia de empleado y concepto
        $employeeCode = strtoupper($data['employee_code']);
        $conceptCode = strtoupper($data['concept_code']);

        $employeeExists = isset($employeesByCode[$employeeCode]) ||
                          isset($employeesByCode['DOC:' . $employeeCode]) ||
                          isset($employeesByDocument[$employeeCode]);

        if (!$employeeExists) {
            $errors[] = "Empleado con código/cédula {$data['employee_code']} no existe (Columna A)";
        }

        if (!isset($conceptsByCode[$conceptCode])) {
            $errors[] = "Concepto con código {$data['concept_code']} no existe (Columna B)";
        }

        // Resolver frecuencia (por ID o código)
        $frecuenciaId = null;
        if (!empty($data['frecuencia'])) {
            if (is_numeric($data['frecuencia'])) {
                $frecuenciaId = (int)$data['frecuencia'];
                if (!isset($frecuenciasByCode[$frecuenciaId])) {
                    $errors[] = "Frecuencia ID {$data['frecuencia']} no existe (Columna F)";
                }
            } else {
                $freqKey = strtolower($data['frecuencia']);
                if (isset($frecuenciasByCode[$freqKey])) {
                    $frecuenciaId = $frecuenciasByCode[$freqKey]['id'];
                } else {
                    $errors[] = "Frecuencia código {$data['frecuencia']} no existe (Columna F)";
                }
            }
        }

        // Validar tipo planilla (opcional)
        $tipoPlanillaId = null;
        if (!empty($data['tipo_planilla'])) {
            if (is_numeric($data['tipo_planilla'])) {
                $tipoPlanillaId = (int)$data['tipo_planilla'];
                if (!isset($tiposPlanillaByCode[$tipoPlanillaId])) {
                    $errors[] = "Tipo planilla ID {$data['tipo_planilla']} no existe (Columna H)";
                }
            } else {
                $tipoPlanillaKey = strtolower($data['tipo_planilla']);
                if (isset($tiposPlanillaByCode[$tipoPlanillaKey])) {
                    $tipoPlanillaId = $tiposPlanillaByCode[$tipoPlanillaKey]['id'];
                } else {
                    $errors[] = "Tipo planilla código {$data['tipo_planilla']} no existe (Columna H)";
                }
            }
        }

        // Validar tipo acumulado (opcional)
        $tipoAcumulado = null;
        if (!empty($data['tipo_acumulado'])) {
            $tipoAcumuladoUpper = strtoupper($data['tipo_acumulado']);

            // Si es "CONCEPTO" o "POR CONCEPTO", usar el código del concepto
            if ($tipoAcumuladoUpper === 'CONCEPTO' || $tipoAcumuladoUpper === 'POR CONCEPTO') {
                $tipoAcumulado = $conceptCode; // Usar código del concepto (ej: "01")
            }
            // Si existe en el catálogo de tipos acumulados
            elseif (isset($tiposAcumuladosByCode[$data['tipo_acumulado']])) {
                $tipoAcumulado = $data['tipo_acumulado'];
            }
            // Si es igual al código del concepto (permiso para usar directamente el código)
            elseif ($conceptCode === $data['tipo_acumulado']) {
                $tipoAcumulado = $data['tipo_acumulado'];
            }
            else {
                $errors[] = "Tipo acumulado '{$data['tipo_acumulado']}' no existe en catálogos (Columna I). Use 'CONCEPTO' para acumular por código del concepto.";
            }
        }

        // Validar planilla_id numérico si se envía
        if (!empty($data['planilla_id']) && !is_numeric($data['planilla_id'])) {
            $errors[] = 'Planilla ID debe ser numérico (Columna G)';
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'message' => "Fila {$row}: " . implode('; ', $errors)
            ];
        }

        return [
            'valid' => true,
            'frecuencia_id' => $frecuenciaId ?? null,
            'tipo_acumulado' => $tipoAcumulado,
            'tipo_planilla_id' => $tipoPlanillaId
        ];
    }

    /**
     * Helpers de formato
     */
    private function safeTrim($value)
    {
        if ($value === null) return '';
        return trim((string)$value);
    }

    private function formatDecimal($value)
    {
        if ($value === null || $value === '') return null;
        return is_numeric($value) ? (float)$value : null;
    }

    private function formatNumber($value)
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) {
            return (int)$value;
        }
        return null;
    }

    private function mapTipoConcepto($tipo)
    {
        $tipo = strtoupper(trim((string)$tipo));
        switch ($tipo) {
            case 'A':
            case 'ASIGNACION':
            case 'ASIGNACIÓN':
                return 'ASIGNACION';
            case 'P':
            case 'PATRONAL':
                return 'PATRONAL';
            default:
                return 'DEDUCCION';
        }
    }

    private function isEmptyRow($data)
    {
        return empty($data['employee_code']) &&
               empty($data['concept_code']) &&
               ($data['amount'] === null || $data['amount'] === '');
    }

    /**
     * Precargar catálogos para referencia
     */
    private function getEmployeesIndexed()
    {
        $employees = $this->employeeModel->getAllEmployees() ?? [];
        $byCode = [];
        $byDocument = [];
        foreach ($employees as $employee) {
            $code = strtoupper($employee['employee_id'] ?? '');
            $doc = strtoupper($employee['document_id'] ?? '');
            if ($code) {
                $byCode[$code] = $employee;
            }
            if ($doc) {
                $byDocument[$doc] = $employee;
                // Prefijo DOC: para lookup rápido en validación
                $byCode['DOC:' . $doc] = $employee;
            }
        }
        return [$byCode, $byDocument];
    }

    private function getConceptsIndexed()
    {
        $concepts = $this->conceptModel->all() ?? [];
        $indexed = [];
        foreach ($concepts as $concept) {
            $code = strtoupper($concept['concepto'] ?? '');
            if ($code) {
                $indexed[$code] = $concept;
            }
        }
        return $indexed;
    }

    private function getFrecuenciasIndexed()
    {
        $frecuencias = $this->frecuenciaModel->getActiveForSelect() ?? [];
        $indexed = [];
        foreach ($frecuencias as $freq) {
            $indexed[$freq['id']] = $freq;
            $indexed[strtolower($freq['codigo'])] = $freq;
        }
        return $indexed;
    }

    private function getTiposPlanillaIndexed()
    {
        $tipos = $this->tipoPlanillaModel->getAllSorted() ?? [];
        $indexed = [];
        foreach ($tipos as $tipo) {
            $indexed[$tipo['id']] = $tipo;
            $indexed[strtolower($tipo['codigo'])] = $tipo;
        }
        return $indexed;
    }

    private function getTiposAcumuladosIndexed()
    {
        $tipos = $this->tipoAcumuladoModel->getActivos() ?? [];
        $indexed = [];
        foreach ($tipos as $tipo) {
            $indexed[strtoupper($tipo['codigo'])] = $tipo;
        }
        return $indexed;
    }

    /**
     * Hoja de referencias (empleados, conceptos, frecuencias, tipos)
     */
    private function addReferenceSheet(Spreadsheet $spreadsheet)
    {
        $ref = $spreadsheet->createSheet();
        $ref->setTitle('Referencias');

        $row = 1;
        $ref->setCellValue("A{$row}", 'EMPLEADOS');
        $ref->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $ref->setCellValue("A{$row}", 'CÓDIGO');
        $ref->setCellValue("B{$row}", 'NOMBRE');
        $ref->setCellValue("C{$row}", 'CÉDULA');
        $row++;

        foreach ($this->employeeModel->getAllEmployees() as $emp) {
            $ref->setCellValue("A{$row}", $emp['employee_id']);
            $ref->setCellValue("B{$row}", trim(($emp['firstname'] ?? '') . ' ' . ($emp['lastname'] ?? '')));
            $ref->setCellValue("C{$row}", $emp['document_id'] ?? '');
            $row++;
        }

        $row += 2;
        $ref->setCellValue("A{$row}", 'CONCEPTOS');
        $ref->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $ref->setCellValue("A{$row}", 'CÓDIGO');
        $ref->setCellValue("B{$row}", 'DESCRIPCIÓN');
        $ref->setCellValue("C{$row}", 'TIPO');
        $row++;

        foreach ($this->conceptModel->all() as $concept) {
            $ref->setCellValue("A{$row}", $concept['concepto'] ?? '');
            $ref->setCellValue("B{$row}", $concept['descripcion'] ?? '');
            $ref->setCellValue("C{$row}", $this->mapTipoConcepto($concept['tipo_concepto'] ?? ''));
            $row++;
        }

        $row += 2;
        $ref->setCellValue("A{$row}", 'FRECUENCIAS');
        $ref->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $ref->setCellValue("A{$row}", 'ID');
        $ref->setCellValue("B{$row}", 'CÓDIGO');
        $ref->setCellValue("C{$row}", 'NOMBRE');
        $row++;

        foreach ($this->frecuenciaModel->getActiveForSelect() as $freq) {
            $ref->setCellValue("A{$row}", $freq['id']);
            $ref->setCellValue("B{$row}", $freq['codigo']);
            $ref->setCellValue("C{$row}", $freq['nombre']);
            $row++;
        }

        $row += 2;
        $ref->setCellValue("A{$row}", 'TIPOS ACUMULADOS');
        $ref->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $ref->setCellValue("A{$row}", 'CÓDIGO');
        $ref->setCellValue("B{$row}", 'DESCRIPCIÓN');
        $row++;

        foreach ($this->tipoAcumuladoModel->getActivos() as $tipo) {
            $ref->setCellValue("A{$row}", $tipo['codigo']);
            $ref->setCellValue("B{$row}", $tipo['descripcion']);
            $row++;
        }

        $row += 2;
        $ref->setCellValue("A{$row}", 'TIPOS PLANILLA');
        $ref->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $ref->setCellValue("A{$row}", 'ID');
        $ref->setCellValue("B{$row}", 'CÓDIGO');
        $ref->setCellValue("C{$row}", 'NOMBRE');
        $row++;

        // ReferenceModel no expone getAll(); usamos getAllSorted para catálogo completo
        foreach ($this->tipoPlanillaModel->getAllSorted() as $tp) {
            $ref->setCellValue("A{$row}", $tp['id']);
            $ref->setCellValue("B{$row}", $tp['codigo']);
            $ref->setCellValue("C{$row}", $tp['nombre']);
            $row++;
        }

        $ref->getColumnDimension('A')->setWidth(18);
        $ref->getColumnDimension('B')->setWidth(40);
        $ref->getColumnDimension('C')->setWidth(20);
    }

    /**
     * Hoja de instrucciones
     */
    private function addInstructionsSheet(Spreadsheet $spreadsheet)
    {
        $inst = $spreadsheet->createSheet();
        $inst->setTitle('Instrucciones');

        $lines = [
            'INSTRUCCIONES PARA IMPORTAR ACUMULADOS',
            '',
            'Campos obligatorios: Código empleado, Código concepto, Monto, Mes, Año, Frecuencia.',
            'Frecuencia acepta ID (1=quincenal, 2=mensual, etc.) o el código (quincenal, mensual, semanal, XIII, LIQUIDACION, VACACIONES).',
            'Planilla ID es opcional. Si no aplica, dejar vacío y se guardará como 0.',
            '',
            'TIPO ACUMULADO (Columna I - opcional):',
            '- Use códigos de tipos_acumulados (ej: XIII_MES, DEDUCCION, SALARIO_BASE, etc.)',
            '- Use "CONCEPTO" o "POR CONCEPTO" para acumular automáticamente por el código del concepto',
            '  Ejemplo: Si concepto es "01", escribir "CONCEPTO" guardará "01" como tipo acumulado',
            '- Puede escribir directamente el código del concepto si coincide',
            '',
            'Tipo de concepto se calcula automáticamente desde el concepto.',
            'Mes debe estar entre 1 y 12. Año en formato YYYY.',
            'El sistema evita duplicados por empleado + concepto + mes + año + planilla + tipo acumulado.',
            '',
            'Antes de importar:',
            '- Descargue esta plantilla dinámica para obtener los catálogos actualizados.',
            '- Elimine filas de ejemplo.',
            '- Revise los IDs de frecuencia y los códigos de conceptos/tipos.',
            '- Haga respaldo de la base de datos.'
        ];

        $row = 1;
        foreach ($lines as $line) {
            $inst->setCellValue("A{$row}", $line);
            $row++;
        }

        $inst->getColumnDimension('A')->setWidth(90);
    }

    /**
     * Verificar autenticación
     */
    protected function requireAuth()
    {
        \App\Middleware\AuthMiddleware::requireAuth();
    }
}
