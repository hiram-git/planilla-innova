<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Generador de Reportes Contables Excel para Asientos de Planilla
 * Extiende ExcelReportController para generar reportes contables con datos bancarios
 */
class PlanillaContableExcelGenerator extends ExcelReportController
{
    /**
     * Generar reporte contable Excel con tres hojas:
     * - Planilla (datos originales)
     * - Asiento de Planilla
     * - Asiento de Cuota Patronal
     */
    public function generateContableExcel($payrollId)
    {
        try {
            $this->requireAuth();

            if (!$payrollId) {
                $_SESSION['error'] = 'ID de planilla requerido';
                $this->redirect('/panel/reports');
                return;
            }

            // Obtener datos de la planilla CON información bancaria
            $planillaData = $this->getPayrollReportDataWithBankInfo($payrollId);

            if (!$planillaData) {
                error_log("PlanillaContableExcelGenerator: Planilla no encontrada para ID: $payrollId");
                $_SESSION['error'] = 'Planilla no encontrada';
                $this->redirect('/panel/reports');
                return;
            }

            error_log("PlanillaContableExcelGenerator: Planilla encontrada, empleados: " . count($planillaData['employees'] ?? []));

            // Obtener información de la empresa
            $companyInfo = $this->getCompanyInfo();

            // Generar Excel con tres hojas
            $this->generateContableExcelReport($planillaData, $companyInfo);

        } catch (\Exception $e) {
            error_log('Error generando Excel contable: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al generar el reporte contable Excel: ' . $e->getMessage();
            $this->redirect('/panel/reports');
        }
    }

    /**
     * Obtener datos de la planilla incluyendo información bancaria de empleados
     */
    private function getPayrollReportDataWithBankInfo($payrollId)
    {
        try {
            $reportModel = $this->model('Report');
            $db = $reportModel->getDatabase();
            $connection = $db->getConnection();

            // Información básica de la planilla
            $sql = "SELECT p.*,
                           p.fecha_desde as fecha_inicio,
                           p.fecha_hasta as fecha_fin,
                           tp.descripcion as tipo_descripcion
                   FROM planilla_cabecera p
                   LEFT JOIN tipos_planilla tp ON p.tipo_planilla_id = tp.id
                   WHERE p.id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$payrollId]);
            $payroll = $stmt->fetch();

            if (!$payroll) {
                error_log("PlanillaContableExcelGenerator: No se encontró planilla con ID: $payrollId");
                return null;
            }

            error_log("PlanillaContableExcelGenerator: Planilla encontrada: " . $payroll['descripcion']);

            // Obtener tipo de empresa
            $companySQL = "SELECT tipo_institucion FROM companies WHERE id = 1";
            $companyStmt = $connection->prepare($companySQL);
            $companyStmt->execute();
            $company = $companyStmt->fetch();
            $tipoEmpresa = $company['tipo_institucion'] ?? 'privada';

            // Empleados con conceptos y datos bancarios
            $sql = "SELECT
                        e.id AS employee_id,
                        e.employee_id AS employee_code,
                        e.firstname,
                        e.lastname,
                        e.document_id,
                        e.fecha_ingreso,
                        e.forma_pago,
                        e.banco,
                        e.numero_cuenta,
                        e.tipo_cuenta,
                        " . ($tipoEmpresa === 'publica'
                            ? "COALESCE(pos.sueldo, 0) as salary, pos.codigo as puesto_actual, f.nombre as funcion_name"
                            : "COALESCE(e.sueldo_individual, 0) as salary, c2.nombre as puesto_actual, f.nombre as funcion_name") . ",
                        pd.monto AS concepto_monto,
                        pd.unidad as reference_value,
                        c.concepto AS concepto_codigo,
                        c.descripcion AS concepto_descripcion,
                        c.tipo_concepto,
                        c.categoria_reporte,
                        c.orden_reporte,
                        pd.tipo
                    FROM
                        planilla_detalle pd
                        INNER JOIN employees e ON pd.employee_id = e.id
                        INNER JOIN concepto c ON pd.concepto_id = c.id
                        LEFT JOIN posiciones pos ON pos.id = e.position_id
                        LEFT JOIN cargos c2 ON c2.id = e.cargo_id
                        LEFT JOIN funciones f ON f.id = e.funcion_id
                    WHERE pd.planilla_cabecera_id = ?
                        AND c.incluir_reporte = 1
                    ORDER BY
                        e.lastname,
                        e.firstname,
                        c.orden_reporte ASC,
                        c.tipo_concepto,
                        c.concepto";

            $stmt = $connection->prepare($sql);
            $stmt->execute([$payrollId]);
            $conceptsData = $stmt->fetchAll();

            error_log("PlanillaContableExcelGenerator: Conceptos encontrados: " . count($conceptsData));

            // Organizar datos por empleado
            $employees = [];
            foreach ($conceptsData as $row) {
                $empKey = $row['employee_id'];

                if (!isset($employees[$empKey])) {
                    $employees[$empKey] = [
                        'id' => $row['employee_id'],
                        'employee_id' => $row['employee_id'],
                        'firstname' => $row['firstname'],
                        'lastname' => $row['lastname'],
                        'document_id' => $row['document_id'],
                        'fecha_ingreso' => $row['fecha_ingreso'],
                        'salary' => $row['salary'],
                        'puesto_actual' => $row['puesto_actual'],
                        'funcion_name' => $row['funcion_name'],
                        'reference_value' => $row['reference_value'] ?? 15,
                        // DATOS BANCARIOS
                        'forma_pago' => $row['forma_pago'] ?? 'EFECTIVO',
                        'banco' => $row['banco'] ?? '',
                        'numero_cuenta' => $row['numero_cuenta'] ?? '',
                        'tipo_cuenta' => $row['tipo_cuenta'] ?? '',
                        'concepts' => [],
                        'totals' => [
                            'ingresos' => 0,
                            'deducciones' => 0,
                            'seguro_social' => 0,
                            'seguro_educativo' => 0,
                            'impuesto_renta' => 0,
                            'otras_deducciones' => 0,
                            'neto' => 0
                        ]
                    ];
                }

                $monto = $row['concepto_monto'] ?? 0;

                $employees[$empKey]['concepts'][] = [
                    'codigo' => $row['concepto_codigo'],
                    'descripcion' => $row['concepto_descripcion'],
                    'tipo' => $row['tipo_concepto'],
                    'categoria' => $row['categoria_reporte'],
                    'monto' => $monto
                ];

                // Calcular totales
                if ($row['tipo_concepto'] == 'A') { // Asignación = Ingreso
                    $employees[$empKey]['totals']['ingresos'] += $monto;
                } elseif ($row['tipo_concepto'] == 'D') { // Deducción
                    $employees[$empKey]['totals']['deducciones'] += $monto;

                    // Categorizar usando el campo categoria_reporte
                    switch ($row['categoria_reporte']) {
                        case 'seguro_social':
                            $employees[$empKey]['totals']['seguro_social'] += $monto;
                            break;
                        case 'seguro_educativo':
                            $employees[$empKey]['totals']['seguro_educativo'] += $monto;
                            break;
                        case 'impuesto_renta':
                            $employees[$empKey]['totals']['impuesto_renta'] += $monto;
                            break;
                        case 'otras_deducciones':
                            $employees[$empKey]['totals']['otras_deducciones'] += $monto;
                            break;
                        default:
                            $employees[$empKey]['totals']['otras_deducciones'] += $monto;
                            break;
                    }
                }

                // Calcular neto
                $employees[$empKey]['totals']['neto'] =
                    $employees[$empKey]['totals']['ingresos'] - $employees[$empKey]['totals']['deducciones'];
            }

            // Si no hay empleados con conceptos, intentar obtener empleados básicos
            if (empty($employees)) {
                error_log("PlanillaContableExcelGenerator: No hay empleados con conceptos, intentando obtener empleados básicos");

                $basicEmployeeSql = "SELECT DISTINCT
                                        e.id AS employee_id,
                                        e.firstname,
                                        e.lastname,
                                        e.document_id,
                                        e.fecha_ingreso,
                                        e.forma_pago,
                                        e.banco,
                                        e.numero_cuenta,
                                        e.tipo_cuenta,
                                        COALESCE(e.sueldo_individual, 0) as salary
                                    FROM planilla_detalle pd
                                    INNER JOIN employees e ON pd.employee_id = e.id
                                    WHERE pd.planilla_cabecera_id = ?
                                    ORDER BY e.lastname, e.firstname";

                $basicStmt = $connection->prepare($basicEmployeeSql);
                $basicStmt->execute([$payrollId]);
                $basicEmployees = $basicStmt->fetchAll();

                error_log("PlanillaContableExcelGenerator: Empleados básicos encontrados: " . count($basicEmployees));

                foreach ($basicEmployees as $emp) {
                    $employees[$emp['employee_id']] = [
                        'id' => $emp['employee_id'],
                        'employee_id' => $emp['employee_id'],
                        'firstname' => $emp['firstname'],
                        'lastname' => $emp['lastname'],
                        'document_id' => $emp['document_id'],
                        'fecha_ingreso' => $emp['fecha_ingreso'],
                        'salary' => $emp['salary'],
                        'puesto_actual' => '',
                        'funcion_name' => '',
                        'reference_value' => 15,
                        // DATOS BANCARIOS
                        'forma_pago' => $emp['forma_pago'] ?? 'EFECTIVO',
                        'banco' => $emp['banco'] ?? '',
                        'numero_cuenta' => $emp['numero_cuenta'] ?? '',
                        'tipo_cuenta' => $emp['tipo_cuenta'] ?? '',
                        'concepts' => [],
                        'totals' => [
                            'ingresos' => 0,
                            'deducciones' => 0,
                            'seguro_social' => 0,
                            'seguro_educativo' => 0,
                            'impuesto_renta' => 0,
                            'otras_deducciones' => 0,
                            'neto' => 0
                        ]
                    ];
                }
            }

            error_log("PlanillaContableExcelGenerator: Total empleados a retornar: " . count($employees));

            return [
                'payroll' => $payroll,
                'employees' => array_values($employees)
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo datos contables: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generar archivo Excel contable con tres hojas
     */
    private function generateContableExcelReport($planillaData, $companyInfo)
    {
        $payroll = $planillaData['payroll'];
        $employees = $planillaData['employees'] ?? [];

        // Crear nueva instancia de Spreadsheet
        $spreadsheet = new Spreadsheet();

        // HOJA 1: Planilla (datos originales)
        $sheet0 = $spreadsheet->getActiveSheet();
        $sheet0->setTitle('Planilla');
        $this->buildExcelContent($sheet0, $payroll, $employees, $companyInfo);

        // HOJA 2: Asiento de Planilla
        $sheet1 = $spreadsheet->createSheet();
        $sheet1->setTitle('Asiento de Planilla');
        $this->buildAsientoPlanillaSheet($sheet1, $payroll, $employees, $companyInfo);

        // HOJA 3: Asiento de Cuota Patronal
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Asiento Cuota Patronal');
        $this->buildAsientoCuotaPatronalSheet($sheet2, $payroll, $employees, $companyInfo);

        // Configurar propiedades del documento
        $spreadsheet->getProperties()
            ->setCreator('Sistema de Planillas MVC')
            ->setTitle('Asientos Contables - ' . $payroll['descripcion'])
            ->setSubject('Asientos Contables de Planilla')
            ->setDescription('Asientos de Planilla y Cuota Patronal generados por Sistema MVC')
            ->setKeywords('planilla asientos contables panama')
            ->setCategory('Contabilidad');

        // Nombre del archivo
        $filename = 'Asientos_Contables_Planilla_' . $payroll['id'] . '_' . date('Y-m-d') . '.xlsx';

        // Limpiar buffer de salida
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Configurar headers para descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        // Crear writer y generar archivo
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Construir hoja de Asiento de Planilla (formato contable)
     */
    private function buildAsientoPlanillaSheet($sheet, $payroll, $employees, $companyInfo)
    {
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_inicio']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_fin']));

        $row = 1;

        // Header de la empresa
        $sheet->setCellValue('A' . $row, $companyInfo['company_name']);
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
        ]);
        $row++;

        // Subtítulo
        $sheet->setCellValue('A' . $row, 'ASIENTO DE PLANILLA - ' . strtoupper($payroll['descripcion']));
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '8FAADC']]
        ]);
        $row++;

        // Período
        $sheet->setCellValue('A' . $row, 'Período: ' . $fechaInicio . ' al ' . $fechaFin);
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E2F3']]
        ]);
        $row += 2; // Fila vacía

        // Headers de columnas
        $headers = [
            'A' => 'No.',
            'B' => 'NOMBRE DE CUENTA',
            'C' => 'DÉBITO',
            'D' => 'CRÉDITO'
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '70AD47']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
        }
        $row++;

        // Calcular totales de planilla
        $totalSueldos = 0;
        $totalAusencias = 0;
        $totalTardanzas = 0;
        $totalDeducciones = 0;

        foreach ($employees as $emp) {
            // Calcular sueldo quincenal para cada empleado
            $sueldoQuincenal = ($emp['salary'] ?? 0) / 2;

            // Extraer ausencias y tardanzas usando el helper del padre
            $ausencias = $this->getConceptAmountByType($emp, 'AUSENCIA', 'D');
            $tardanzas = $this->getConceptAmountByType($emp, 'TARDAN', 'D');

            // El salario es: sueldo quincenal - ausencias - tardanzas
            $salarioNeto = $sueldoQuincenal - $ausencias - $tardanzas;
            $totalSueldos += $salarioNeto;

            $totalAusencias += $ausencias;
            $totalTardanzas += $tardanzas;

            // Buscar otras deducciones (no ausencias ni tardanzas)
            foreach ($emp['concepts'] as $concept) {
                $codigo = strtoupper($concept['codigo'] ?? '');
                $descripcion = strtoupper($concept['descripcion'] ?? '');

                if ($concept['tipo'] == 'D') { // Deducciones
                    $monto = $concept['monto'] ?? 0;

                    // Solo sumar si NO es ausencia ni tardanza
                    if (strpos($codigo, 'AUSENCIA') === false && strpos($descripcion, 'AUSENCIA') === false &&
                        strpos($codigo, 'TARDAN') === false && strpos($descripcion, 'TARDAN') === false) {
                        $totalDeducciones += $monto;
                    }
                }
            }
        }

        // CRÉDITO: Salarios (ya neto de ausencias y tardanzas)
        $credito = $totalSueldos;

        // DÉBITO: Total de deducciones
        $debito = $totalDeducciones;

        $asiento = 1;

        // Línea 1: Gasto de Sueldos (DÉBITO - CRÉDITO)
        $sheet->setCellValue('A' . $row, $asiento++);
        $sheet->setCellValue('B' . $row, 'Gasto de Sueldos');
        $sheet->setCellValue('C' . $row, $credito); // DÉBITO
        $sheet->setCellValue('D' . $row, 0); // CRÉDITO
        $this->applyAccountingRowStyle($sheet, $row);
        $row++;

        // Línea 2: Deducciones (si hay)
        if ($debito > 0) {
            $sheet->setCellValue('A' . $row, $asiento++);
            $sheet->setCellValue('B' . $row, 'Deducciones por Pagar');
            $sheet->setCellValue('C' . $row, 0); // DÉBITO
            $sheet->setCellValue('D' . $row, $debito); // CRÉDITO
            $this->applyAccountingRowStyle($sheet, $row);
            $row++;
        }

        // Línea 3: Sueldos por Pagar
        $sueldosPorPagar = $credito - $debito;
        $sheet->setCellValue('A' . $row, $asiento++);
        $sheet->setCellValue('B' . $row, 'Sueldos por Pagar');
        $sheet->setCellValue('C' . $row, 0); // DÉBITO
        $sheet->setCellValue('D' . $row, $sueldosPorPagar); // CRÉDITO
        $this->applyAccountingRowStyle($sheet, $row);
        $row++;

        // Espacio
        $row++;

        // Totales
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('B' . $row, 'TOTALES');
        $sheet->setCellValue('C' . $row, $credito);
        $sheet->setCellValue('D' . $row, $credito); // Debe = Haber
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C5E0B4']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK]]
        ]);

        // Formato de moneda
        $currencySymbol = $companyInfo['currency_symbol'] ?? '$';
        $currencyFormat = '"' . $currencySymbol . '"#,##0.00';
        $sheet->getStyle('C6:D' . $row)->getNumberFormat()->setFormatCode($currencyFormat);

        // Ajustar anchos de columnas
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);

        // Congelar paneles
        $sheet->freezePane('A6');
    }

    /**
     * Aplicar estilo a fila de asiento contable
     */
    private function applyAccountingRowStyle($sheet, $row)
    {
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ]);

        // Alinear números a la derecha
        $sheet->getStyle('C' . $row . ':D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Formato numérico
        $sheet->getStyle('C' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    /**
     * Construir hoja de Asiento de Cuota Patronal (formato contable)
     */
    private function buildAsientoCuotaPatronalSheet($sheet, $payroll, $employees, $companyInfo)
    {
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_inicio']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_fin']));

        $row = 1;

        // Header de la empresa
        $sheet->setCellValue('A' . $row, $companyInfo['company_name']);
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
        ]);
        $row++;

        // Subtítulo
        $sheet->setCellValue('A' . $row, 'ASIENTO DE CUOTA PATRONAL - ' . strtoupper($payroll['descripcion']));
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']]
        ]);
        $row++;

        // Período
        $sheet->setCellValue('A' . $row, 'Período: ' . $fechaInicio . ' al ' . $fechaFin);
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E2F3']]
        ]);
        $row += 2; // Fila vacía

        // Headers de columnas
        $headers = [
            'A' => 'No.',
            'B' => 'NOMBRE DE CUENTA',
            'C' => 'DÉBITO',
            'D' => 'CRÉDITO'
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
        }
        $row++;

        // Calcular total de salarios
        $totalSalarios = 0;
        foreach ($employees as $emp) {
            $totalSalarios += $emp['salary'] ?? 0;
        }

        // Calcular cuotas patronales según legislación panameña
        $gastoSeguroSocial = $totalSalarios * 0.1225; // 12.25%
        $gastoSeguroEducativo = $totalSalarios * 0.015; // 1.5%
        $gastoRiesgosProfesionales = $totalSalarios * 0.021; // 2.1%

        // Crédito: Suma de todos los gastos (Caja de Seguro Social por Pagar)
        $cajaSeguroSocialPorPagar = $gastoSeguroSocial + $gastoSeguroEducativo + $gastoRiesgosProfesionales;

        $asiento = 1;

        // Línea 1: Gasto de Seguro Social (DÉBITO)
        $sheet->setCellValue('A' . $row, $asiento++);
        $sheet->setCellValue('B' . $row, 'Gasto de Seguro Social');
        $sheet->setCellValue('C' . $row, $gastoSeguroSocial); // DÉBITO
        $sheet->setCellValue('D' . $row, 0); // CRÉDITO
        $this->applyAccountingRowStyle($sheet, $row);
        $row++;

        // Línea 2: Gasto de Seguro Educativo (DÉBITO)
        $sheet->setCellValue('A' . $row, $asiento++);
        $sheet->setCellValue('B' . $row, 'Gasto de Seguro Educativo');
        $sheet->setCellValue('C' . $row, $gastoSeguroEducativo); // DÉBITO
        $sheet->setCellValue('D' . $row, 0); // CRÉDITO
        $this->applyAccountingRowStyle($sheet, $row);
        $row++;

        // Línea 3: Gasto de Riesgos Profesionales (DÉBITO)
        $sheet->setCellValue('A' . $row, $asiento++);
        $sheet->setCellValue('B' . $row, 'Gasto de Riesgos Profesionales');
        $sheet->setCellValue('C' . $row, $gastoRiesgosProfesionales); // DÉBITO
        $sheet->setCellValue('D' . $row, 0); // CRÉDITO
        $this->applyAccountingRowStyle($sheet, $row);
        $row++;

        // Línea 4: Caja de Seguro Social por Pagar (CRÉDITO)
        $sheet->setCellValue('A' . $row, $asiento++);
        $sheet->setCellValue('B' . $row, 'Caja de Seguro Social por Pagar');
        $sheet->setCellValue('C' . $row, 0); // DÉBITO
        $sheet->setCellValue('D' . $row, $cajaSeguroSocialPorPagar); // CRÉDITO
        $this->applyAccountingRowStyle($sheet, $row);
        $row++;

        // Espacio
        $row++;

        // Totales
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('B' . $row, 'TOTALES');
        $sheet->setCellValue('C' . $row, $cajaSeguroSocialPorPagar);
        $sheet->setCellValue('D' . $row, $cajaSeguroSocialPorPagar); // Debe = Haber
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFD966']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK]]
        ]);

        // Formato de moneda
        $currencySymbol = $companyInfo['currency_symbol'] ?? '$';
        $currencyFormat = '"' . $currencySymbol . '"#,##0.00';
        $sheet->getStyle('C6:D' . $row)->getNumberFormat()->setFormatCode($currencyFormat);

        // Nota legal (2 filas después de totales)
        $row += 2;
        $sheet->setCellValue('A' . $row, 'NOTA: Los porcentajes aplicados son según el Código de Trabajo de Panamá:');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'italic' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
        ]);
        $row++;

        $sheet->setCellValue('A' . $row, '• Seguro Social Patronal: 12.25% del salario base');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $row++;

        $sheet->setCellValue('A' . $row, '• Seguro Educativo Patronal: 1.5% del salario base');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $row++;

        $sheet->setCellValue('A' . $row, '• Riesgo Profesional: 2.1% del salario base');
        $sheet->mergeCells('A' . $row . ':D' . $row);

        // Ajustar anchos de columnas
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);

        // Congelar paneles
        $sheet->freezePane('A6');
    }
}
