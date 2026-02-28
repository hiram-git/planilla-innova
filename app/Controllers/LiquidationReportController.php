<?php

namespace App\Controllers;

use App\Core\TenantStorage;

/**
 * Controlador de Reportes de Liquidación
 *
 * Clase especializada que hereda de LiquidationController para manejar
 * la generación de reportes en PDF y Excel de liquidaciones.
 *
 * Separada del controlador principal para mantener el código organizado
 * y enfocado en responsabilidades específicas.
 */
class LiquidationReportController extends LiquidationController
{
    /**
     * Vista previa de cálculo de liquidación
     */
    public function preview($termination_id)
    {
        try {
            $termination = $this->getTerminationData($termination_id);

            if (!$termination || $termination['status'] !== 'CALCULADA') {
                $this->setToastrMessage('error', 'Liquidación no encontrada o no calculada', 'Estado Inválido');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Obtener cálculos guardados con tipo de concepto
            $sql = "SELECT lc.*, c.tipo_concepto
                    FROM liquidation_calculations lc
                    LEFT JOIN concepto c ON lc.concept_id = c.id
                    WHERE lc.termination_id = ?
                    ORDER BY lc.concept_code";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$termination_id]);
            $calculations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $totals = $this->calculateTotals($calculations);
            $accumulatedConceptCodes = ['LIQ001', 'LIQ002', 'LIQ005', 'LIQ007'];
            $liquidationAccumulations = [];
            $accumulatedTypesByConcept = $this->getConceptAccumulatedTypes($accumulatedConceptCodes);
            foreach ($accumulatedConceptCodes as $conceptCode) {
                $types = $accumulatedTypesByConcept[$conceptCode] ?? ['SALARIO_BASE'];
                $liquidationAccumulations[$conceptCode] = $this->getLiquidationAccumulatedMonths(
                    (int)$termination['employee_table_id'],
                    $termination['termination_date'],
                    $types,
                    $conceptCode  // Pasar código de concepto para detectar LIQ007
                );
            }

            $this->render('admin/liquidation/preview', [
                'termination' => $termination,
                'calculations' => $calculations,
                'totals' => $totals,
                'liquidationAccumulations' => $liquidationAccumulations,
                'pageTitle' => 'Vista Previa de Liquidación'
            ]);

        } catch (\PDOException $e) {
            $this->setToastrMessage('error', 'Error al mostrar vista previa: ' . $e->getMessage(), 'Error de Vista');
            $this->redirect('/panel/liquidation');
        }
    }

    /**
     * Exportar planilla de liquidación a Excel
     */
    public function exportPayrollExcel($payroll_id)
    {
        try {
            // Obtener datos de la planilla
            $sql = "SELECT pc.*, tp.descripcion as tipo_planilla_nombre,
                           f.nombre as frecuencia_nombre
                    FROM planilla_cabecera pc
                    LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                    LEFT JOIN frecuencias f ON pc.frecuencia_id = f.id
                    WHERE pc.id = ? AND pc.frecuencia_id = ?";

            $liquidation_frequency_id = $this->getLiquidationFrequencyId();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payroll_id, $liquidation_frequency_id]);
            $payroll = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$payroll) {
                $this->setToastrMessage('error', 'Planilla de liquidación no encontrada', 'Error de Búsqueda');
                $this->redirect('/panel/liquidation/payrolls');
                return;
            }

            // Obtener detalles de la planilla
            $sql = "SELECT pd.*, c.concepto, c.descripcion as concepto_descripcion,
                           c.tipo_concepto, e.employee_id as cedula, e.document_id,
                           e.fecha_ingreso, e.sueldo_individual, org.descripcion as departamento,
                           cargos.nombre as position_name, et.termination_date,
                           et.termination_type, et.notice_period_days, et.reason as termination_reason
                    FROM planilla_cabecera pc
                    INNER JOIN planilla_detalle pd ON pd.planilla_cabecera_id = pc.id
                    INNER JOIN concepto c ON pd.concepto_id = c.id
                    INNER JOIN employees e ON pd.employee_id = e.id
                    LEFT JOIN organigrama org ON e.departamento_id = org.id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos ON p.id_cargo = cargos.id
                    LEFT JOIN employee_terminations et ON et.employee_id = e.id
                        AND pc.fecha_hasta = et.termination_date
                    WHERE pd.planilla_cabecera_id = ?
                    ORDER BY c.tipo_concepto, c.concepto";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payroll_id]);
            $details = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Obtener datos de la empresa para las firmas
            $sql_company = "SELECT legal_representative, jefe_recursos_humanos FROM companies LIMIT 1";
            $stmt_company = $this->db->prepare($sql_company);
            $stmt_company->execute();
            $company_data = $stmt_company->fetch(\PDO::FETCH_ASSOC);

            // Calcular totales
            $totals = [
                'total_asignaciones' => array_sum(array_column(array_filter($details, fn($d) => $d['tipo'] === 'A'), 'monto')),
                'total_deducciones' => array_sum(array_column(array_filter($details, fn($d) => $d['tipo'] === 'D'), 'monto'))
            ];
            $totals['total_neto'] = $totals['total_asignaciones'] - $totals['total_deducciones'];

            // Obtener información del empleado
            $employee_info = null;
            if (!empty($details)) {
                $first_detail = $details[0];

                // Calcular tiempo en la empresa
                $tiempo_empresa = 'N/A';
                if (!empty($first_detail['fecha_ingreso']) && !empty($first_detail['termination_date'])) {
                    $fecha_inicio = new \DateTime($first_detail['fecha_ingreso']);
                    $fecha_fin = new \DateTime($first_detail['termination_date']);
                    $intervalo = $fecha_inicio->diff($fecha_fin);

                    $tiempo_empresa = '';
                    if ($intervalo->y > 0) {
                        $tiempo_empresa .= $intervalo->y . ($intervalo->y == 1 ? ' año' : ' años');
                    }
                    if ($intervalo->m > 0) {
                        $tiempo_empresa .= ($tiempo_empresa ? ', ' : '') . $intervalo->m . ($intervalo->m == 1 ? ' mes' : ' meses');
                    }
                    if ($intervalo->d > 0) {
                        $tiempo_empresa .= ($tiempo_empresa ? ', ' : '') . $intervalo->d . ($intervalo->d == 1 ? ' día' : ' días');
                    }
                    if (empty($tiempo_empresa)) {
                        $tiempo_empresa = '0 días';
                    }
                }

                // Mapear tipo de terminación a texto legible
                $termination_types = [
                    'DESPIDO_CON_CAUSA' => 'Despido con Causa Justificada',
                    'DESPIDO_SIN_CAUSA' => 'Despido sin Causa Justificada',
                    'RENUNCIA' => 'Renuncia Voluntaria',
                    'MUTUO_ACUERDO' => 'Mutuo Acuerdo'
                ];
                $termination_type_text = isset($first_detail['termination_type']) && isset($termination_types[$first_detail['termination_type']])
                    ? $termination_types[$first_detail['termination_type']]
                    : 'N/A';

                $notice_period_days = $first_detail['notice_period_days'] ?? 0;
                $con_preaviso = $notice_period_days > 0 ? 'Sí' : 'No';

                $employee_info = [
                    'name' => $first_detail['firstname'] . ' ' . $first_detail['lastname'],
                    'cedula' => $first_detail['document_id'] ?? $first_detail['cedula'] ?? 'N/A',
                    'departamento' => $first_detail['departamento'] ?? 'N/A',
                    'position' => $first_detail['position_name'] ?? 'N/A',
                    'fecha_ingreso' => $first_detail['fecha_ingreso'] ?? 'N/A',
                    'fecha_terminacion' => $first_detail['termination_date'] ?? 'N/A',
                    'salario' => $first_detail['sueldo_individual'] ?? 0,
                    'tiempo_empresa' => $tiempo_empresa,
                    'motivo_liquidacion' => $termination_type_text,
                    'con_preaviso' => $con_preaviso,
                    'dias_preaviso' => $notice_period_days,
                    'razon_terminacion' => $first_detail['termination_reason'] ?? 'N/A'
                ];
            }

            // Crear Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Configurar hoja
            $sheet->setTitle('Liquidación');

            // ===== ENCABEZADO =====
            $sheet->mergeCells('A1:E1');
            $sheet->setCellValue('A1', 'INNOVA PLANILLA');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A2:E2');
            $sheet->setCellValue('A2', 'LIQUIDACIÓN LABORAL');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $periodo_texto = 'DEL ' . strtoupper(date('d \d\e F \d\e Y', strtotime($payroll['fecha_desde']))) .
                           ' HASTA EL ' . strtoupper(date('d \d\e F \d\e Y', strtotime($payroll['fecha_hasta'])));
            $sheet->mergeCells('A3:E3');
            $sheet->setCellValue('A3', $periodo_texto);
            $sheet->getStyle('A3')->getFont()->setSize(11);
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row = 5;

            // ===== INFORMACIÓN DEL EMPLEADO =====
            if ($employee_info) {
                $sheet->setCellValue('A' . $row, 'Nombre:');
                $sheet->setCellValue('B' . $row, $employee_info['name']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Cédula:');
                $sheet->setCellValue('B' . $row, $employee_info['cedula']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Cargo:');
                $sheet->setCellValue('B' . $row, $employee_info['position']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Departamento:');
                $sheet->setCellValue('B' . $row, $employee_info['departamento']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Salario:');
                $sheet->setCellValue('B' . $row, '$' . number_format($employee_info['salario'], 2));
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Fecha de Ingreso:');
                $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($employee_info['fecha_ingreso'])));
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Fecha Fin Contrato:');
                $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($employee_info['fecha_terminacion'])));
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Tiempo en Empresa:');
                $sheet->setCellValue('B' . $row, $employee_info['tiempo_empresa']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Motivo de Liquidación:');
                $sheet->setCellValue('B' . $row, $employee_info['motivo_liquidacion']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Con Preaviso:');
                $sheet->setCellValue('B' . $row, $employee_info['con_preaviso']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Días de Preaviso:');
                $sheet->setCellValue('B' . $row, $employee_info['dias_preaviso']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row += 2;
            }

            // ===== CONCEPTOS DE ASIGNACIÓN =====
            $sheet->setCellValue('A' . $row, 'ASIGNACIONES');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FF90EE90');
            $row++;

            $sheet->setCellValue('A' . $row, 'Código');
            $sheet->setCellValue('B' . $row, 'Concepto');
            $sheet->setCellValue('C' . $row, 'Descripción');
            $sheet->setCellValue('D' . $row, 'Monto');
            $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFE0E0E0');
            $row++;

            $asignaciones = array_filter($details, fn($d) => $d['tipo'] === 'A');
            foreach ($asignaciones as $asignacion) {
                $sheet->setCellValue('A' . $row, $asignacion['concepto']);
                $sheet->setCellValue('B' . $row, $asignacion['concepto']);
                $sheet->setCellValue('C' . $row, $asignacion['concepto_descripcion']);
                $sheet->setCellValue('D' . $row, $asignacion['monto']);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $row++;
            }

            $sheet->setCellValue('C' . $row, 'TOTAL ASIGNACIONES:');
            $sheet->setCellValue('D' . $row, $totals['total_asignaciones']);
            $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('C' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFCCFFCC');
            $row += 2;

            // ===== CONCEPTOS DE DEDUCCIÓN =====
            $sheet->setCellValue('A' . $row, 'DEDUCCIONES');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFFFCCCC');
            $row++;

            $sheet->setCellValue('A' . $row, 'Código');
            $sheet->setCellValue('B' . $row, 'Concepto');
            $sheet->setCellValue('C' . $row, 'Descripción');
            $sheet->setCellValue('D' . $row, 'Monto');
            $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFE0E0E0');
            $row++;

            $deducciones = array_filter($details, fn($d) => $d['tipo'] === 'D');
            foreach ($deducciones as $deduccion) {
                $sheet->setCellValue('A' . $row, $deduccion['concepto']);
                $sheet->setCellValue('B' . $row, $deduccion['concepto']);
                $sheet->setCellValue('C' . $row, $deduccion['concepto_descripcion']);
                $sheet->setCellValue('D' . $row, $deduccion['monto']);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $row++;
            }

            $sheet->setCellValue('C' . $row, 'TOTAL DEDUCCIONES:');
            $sheet->setCellValue('D' . $row, $totals['total_deducciones']);
            $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('C' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFFFDDDD');
            $row += 2;

            // ===== TOTAL NETO =====
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $sheet->setCellValue('A' . $row, 'TOTAL NETO A PAGAR:');
            $sheet->setCellValue('D' . $row, $totals['total_neto']);
            $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFCCCCFF');

            // ===== SECCIÓN DE FIRMAS =====
            $row += 4; // Espacio antes de firmas

            // Líneas para firmas
            $sheet->setCellValue('A' . $row, '____________________');
            $sheet->setCellValue('B' . $row, '____________________');
            $sheet->setCellValue('C' . $row, '____________________');
            $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;

            // Etiquetas de firmas
            $sheet->setCellValue('A' . $row, 'Autorizado por');
            $sheet->setCellValue('B' . $row, 'Elaborado por');
            $sheet->setCellValue('C' . $row, 'Recibido por');
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;

            // Nombres de quien firma
            $gerencia_name = $company_data['legal_representative'] ?? 'N/A';
            $rrhh_name = $company_data['jefe_recursos_humanos'] ?? 'N/A';
            $empleado_name = $employee_info ? $employee_info['name'] : 'N/A';
            $empleado_cedula = $employee_info ? $employee_info['cedula'] : 'N/A';

            $sheet->setCellValue('A' . $row, $gerencia_name);
            $sheet->setCellValue('B' . $row, $rrhh_name);
            $sheet->setCellValue('C' . $row, $empleado_name);
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setSize(9);
            $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;

            // Subtextos (cargo/cédula)
            $sheet->setCellValue('A' . $row, '(Gerencia)');
            $sheet->setCellValue('B' . $row, '(Recursos Humanos)');
            $sheet->setCellValue('C' . $row, 'Cédula: ' . $empleado_cedula);
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setSize(8);
            $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // ===== AJUSTAR ANCHOS DE COLUMNA =====
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(40);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);

            // ===== BORDES =====
            $lastRow = $row;
            $sheet->getStyle('A5:D' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            // Generar nombre de archivo
            $filename = 'Liquidacion_' . ($employee_info ? preg_replace('/[^A-Za-z0-9_]/', '_', $employee_info['name']) : 'Planilla') .
                       '_' . date('Y-m-d', strtotime($payroll['fecha'])) . '.xlsx';

            // Configurar headers para descarga
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (\PDOException $e) {
            error_log("Error generating Excel: " . $e->getMessage());
            $this->setToastrMessage('error', 'Error al generar Excel: ' . $e->getMessage(), 'Error de Exportación');
            $this->redirect('/panel/liquidation/payroll-detail/' . $payroll_id);
        } catch (\Exception $e) {
            error_log("Error generating Excel: " . $e->getMessage());
            $this->setToastrMessage('error', 'Error al generar Excel: ' . $e->getMessage(), 'Error de Exportación');
            $this->redirect('/panel/liquidation/payroll-detail/' . $payroll_id);
        }
    }

    /**
     * Exportar planilla de liquidación a PDF
     */
    public function exportPayrollPdf($payroll_id)
    {
        try {
            // Obtener datos de la planilla
            $sql = "SELECT pc.*, tp.descripcion as tipo_planilla_nombre,
                           f.nombre as frecuencia_nombre
                    FROM planilla_cabecera pc
                    LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                    LEFT JOIN frecuencias f ON pc.frecuencia_id = f.id
                    WHERE pc.id = ? AND pc.frecuencia_id = ?";

            $liquidation_frequency_id = $this->getLiquidationFrequencyId();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payroll_id, $liquidation_frequency_id]);
            $payroll = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$payroll) {
                $this->setToastrMessage('error', 'Planilla de liquidación no encontrada', 'Error de Búsqueda');
                $this->redirect('/panel/liquidation/payrolls');
                return;
            }

            // Obtener detalles de la planilla
           $sql = "SELECT pd.*, c.concepto, c.descripcion as concepto_descripcion,
                           c.tipo_concepto, e.employee_id as cedula, e.document_id,
                           e.fecha_ingreso, e.sueldo_individual, org.descripcion as departamento,
                           cargos.nombre as position_name,
                           et.termination_date,
                           et.termination_type, et.notice_period_days, et.reason as termination_reason
                    FROM planilla_cabecera pc
                    INNER JOIN planilla_detalle pd ON pd.planilla_cabecera_id = pc.id
                    INNER JOIN concepto c ON pd.concepto_id = c.id
                    INNER JOIN employees e ON pd.employee_id = e.id
                    LEFT JOIN organigrama org ON e.departamento_id = org.id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos ON cargos.id = e.cargo_id
                    LEFT JOIN employee_terminations et ON et.employee_id = e.id
                        AND pc.fecha_hasta = et.termination_date
                    WHERE pd.planilla_cabecera_id = ?
                    ORDER BY c.tipo_concepto, c.concepto";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payroll_id]);
            $details = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $accumulatedConceptCodes = ['LIQ001', 'LIQ002', 'LIQ005', 'LIQ007'];
            $liquidationAccumulations = [];
            if (!empty($details)) {
                $employeeId = (int)$details[0]['employee_id'];
                $endDate = $payroll['fecha_hasta'] ?? $payroll['fecha'] ?? date('Y-m-d');
                $accumulatedTypesByConcept = $this->getConceptAccumulatedTypes($accumulatedConceptCodes);
                foreach ($accumulatedConceptCodes as $conceptCode) {
                    $types = $accumulatedTypesByConcept[$conceptCode] ?? ['SALARIO_BASE'];
                    $liquidationAccumulations[$conceptCode] = $this->getLiquidationAccumulatedMonths(
                        $employeeId,
                        $endDate,
                        $types,
                        $conceptCode  // Pasar código de concepto para detectar LIQ007
                    );
                }
            }

            // Obtener datos de la empresa para las firmas y logos
            $sql_company = "SELECT legal_representative, jefe_recursos_humanos,
                                   company_name, logo_izquierdo_reportes, logo_derecho_reportes,
                                   logo_empresa
                            FROM companies LIMIT 1";
            $stmt_company = $this->db->prepare($sql_company);
            $stmt_company->execute();
            $company_data = $stmt_company->fetch(\PDO::FETCH_ASSOC);

            // Calcular totales
            $totals = [
                'total_asignaciones' => array_sum(array_column(array_filter($details, fn($d) => $d['tipo'] === 'A'), 'monto')),
                'total_deducciones' => array_sum(array_column(array_filter($details, fn($d) => $d['tipo'] === 'D'), 'monto'))
            ];
            $totals['total_neto'] = $totals['total_asignaciones'] - $totals['total_deducciones'];

            // Obtener información del empleado
            $employee_info = null;
            if (!empty($details)) {
                $first_detail = $details[0];

                // Calcular tiempo en la empresa
                $tiempo_empresa = 'N/A';
                if (!empty($first_detail['fecha_ingreso']) && !empty($first_detail['termination_date'])) {
                    $fecha_inicio = new \DateTime($first_detail['fecha_ingreso']);
                    $fecha_fin = new \DateTime($first_detail['termination_date']);
                    $intervalo = $fecha_inicio->diff($fecha_fin);

                    $tiempo_empresa = '';
                    if ($intervalo->y > 0) {
                        $tiempo_empresa .= $intervalo->y . ($intervalo->y == 1 ? ' año' : ' años');
                    }
                    if ($intervalo->m > 0) {
                        $tiempo_empresa .= ($tiempo_empresa ? ', ' : '') . $intervalo->m . ($intervalo->m == 1 ? ' mes' : ' meses');
                    }
                    if ($intervalo->d > 0) {
                        $tiempo_empresa .= ($tiempo_empresa ? ', ' : '') . $intervalo->d . ($intervalo->d == 1 ? ' día' : ' días');
                    }
                    if (empty($tiempo_empresa)) {
                        $tiempo_empresa = '0 días';
                    }
                }

                // Mapear tipo de terminación a texto legible
                $termination_types = [
                    'DESPIDO_CON_CAUSA' => 'Despido con Causa Justificada',
                    'DESPIDO_SIN_CAUSA' => 'Despido sin Causa Justificada',
                    'RENUNCIA' => 'Renuncia Voluntaria',
                    'MUTUO_ACUERDO' => 'Mutuo Acuerdo'
                ];
                $termination_type_text = isset($first_detail['termination_type']) && isset($termination_types[$first_detail['termination_type']])
                    ? $termination_types[$first_detail['termination_type']]
                    : 'N/A';

                $notice_period_days = $first_detail['notice_period_days'] ?? 0;
                $con_preaviso = $notice_period_days > 0 ? 'Sí' : 'No';

                $employee_info = [
                    'name' => $first_detail['firstname'] . ' ' . $first_detail['lastname'],
                    'firstname' => $first_detail['firstname'],
                    'lastname' => $first_detail['lastname'],
                    'cedula' => $first_detail['document_id'] ?? $first_detail['cedula'] ?? 'N/A',
                    'departamento' => $first_detail['departamento'] ?? 'N/A',
                    'position' => $first_detail['position_name'] ?? 'N/A',
                    'fecha_ingreso' => $first_detail['fecha_ingreso'] ?? 'N/A',
                    'fecha_terminacion' => $first_detail['termination_date'] ?? 'N/A',
                    'salario' => $first_detail['sueldo_individual'] ?? 0,
                    'tiempo_empresa' => $tiempo_empresa,
                    'motivo_liquidacion' => $termination_type_text,
                    'con_preaviso' => $con_preaviso,
                    'dias_preaviso' => $notice_period_days,
                    'razon_terminacion' => $first_detail['termination_reason'] ?? 'N/A'
                ];
            }

            // Crear PDF
            $pdf = new \TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Configuración del documento
            $pdf->SetCreator('Sistema de Planillas INNOVA');
            $pdf->SetAuthor($company_data['company_name'] ?? 'INNOVA PLANILLA');
            $pdf->SetTitle('Liquidación Laboral - ' . ($employee_info['name'] ?? 'N/A'));
            $pdf->SetSubject('Liquidación Laboral');

            // Desactivar header y footer automáticos
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Márgenes
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);

            // Agregar página
            $pdf->AddPage();

            // Insertar logos y nombre de empresa
            $this->insertLogosInPDF($pdf, $company_data);

            $pdf->Ln();
            // ===== ENCABEZADO =====

            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'LIQUIDACIÓN LABORAL', 0, 1, 'C');
            $pdf->Ln();

            // ===== INFORMACIÓN DEL EMPLEADO =====
            if ($employee_info) {
                // Columna izquierda
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Nombre:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['name'], 0, 1);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Cédula:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['cedula'], 0, 0);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Cargo:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['position'], 0, 1);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Departamento:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['departamento'], 0, 0);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Salario:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, '$' . number_format($employee_info['salario'], 2), 0, 1);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Fecha de Ingreso:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, date('d/m/Y', strtotime($employee_info['fecha_ingreso'])), 0, 0);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Fecha Fin Contrato:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, date('d/m/Y', strtotime($employee_info['fecha_terminacion'])), 0, 1);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Tiempo en Empresa:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['tiempo_empresa'], 0, 0);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Motivo de Liquidación:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['motivo_liquidacion'], 0, 1);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Con Preaviso:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['con_preaviso'], 0, 0);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Días de Preaviso:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['dias_preaviso'], 0, 1);

                $pdf->Ln(5);
            }

            // Calcular anchos de columna (ancho disponible: página - márgenes)
            $pageWidth = $pdf->getPageWidth();
            $margins = $pdf->getMargins();
            $availableWidth = $pageWidth - $margins['left'] - $margins['right'];

            // Distribución de columnas: 17% Código, 58% Descripción, 25% Monto
            $colCodigo = $availableWidth * 0.17;
            $colDescripcion = $availableWidth * 0.58;
            $colMonto = $availableWidth * 0.25;

            $conceptDetails = [];
            foreach ($details as $detail) {
                if (in_array($detail['concepto'], $accumulatedConceptCodes, true)) {
                    $conceptDetails[$detail['concepto']] = $detail;
                }
            }

            $xiiiCode = 'LIQ007';
            $vacCode = 'LIQ005';
            $xiiiDescription = $conceptDetails[$xiiiCode]['concepto_descripcion'] ?? 'XIII proporcional';
            $vacDescription = $conceptDetails[$vacCode]['concepto_descripcion'] ?? 'Vacaciones proporcionales';
            $accumulatedTotal = $liquidationAccumulations[$vacCode]['total']
                ?? $liquidationAccumulations[$xiiiCode]['total']
                ?? 0.0;
            $xiiiAmount = $conceptDetails[$xiiiCode]['monto']
                ?? ($accumulatedTotal > 0 ? ($accumulatedTotal / 12) : 0.0);
            $vacAmount = $conceptDetails[$vacCode]['monto']
                ?? ($accumulatedTotal > 0 ? ($accumulatedTotal / 11) : 0.0);

            $compactCodeWidth = $availableWidth * 0.12;
            $compactDescWidth = $availableWidth * 0.28;
            $compactAmountWidth = $availableWidth * 0.10;

            $pdf->SetFillColor(224, 224, 224);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($compactCodeWidth, 6, 'Codigo', 1, 0, 'C', true);
            $pdf->Cell($compactDescWidth, 6, 'Descripcion', 1, 0, 'C', true);
            $pdf->Cell($compactAmountWidth, 6, 'Monto', 1, 0, 'C', true);
            $pdf->Cell($compactCodeWidth, 6, 'Codigo', 1, 0, 'C', true);
            $pdf->Cell($compactDescWidth, 6, 'Descripcion', 1, 0, 'C', true);
            $pdf->Cell($compactAmountWidth, 6, 'Monto', 1, 1, 'C', true);

            $months = $liquidationAccumulations[$vacCode]['months']
                ?? $liquidationAccumulations[$xiiiCode]['months']
                ?? [];
            while (count($months) < 12) {
                $months[] = [
                    'label' => '',
                    'amount' => 0.0
                ];
            }

            $monthLabelWidth = $compactCodeWidth + $compactDescWidth;
            $monthAmountWidth = $compactAmountWidth;

            $pdf->SetFillColor(245, 245, 245);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell($monthLabelWidth, 5, 'Mes', 1, 0, 'L', true);
            $pdf->Cell($monthAmountWidth, 5, 'Acumulado', 1, 0, 'R', true);
            $pdf->Cell($monthLabelWidth, 5, 'Mes', 1, 0, 'L', true);
            $pdf->Cell($monthAmountWidth, 5, 'Acumulado', 1, 1, 'R', true);

            $pdf->SetFont('helvetica', '', 8);
            foreach ($months as $month) {
                $pdf->Cell($monthLabelWidth, 5, $month['label'], 1, 0, 'L');
                $pdf->Cell($monthAmountWidth, 5, '$' . number_format($month['amount'], 2), 1, 0, 'R');
                $pdf->Cell($monthLabelWidth, 5, $month['label'], 1, 0, 'L');
                $pdf->Cell($monthAmountWidth, 5, '$' . number_format($month['amount'], 2), 1, 1, 'R');
            }

            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell($compactCodeWidth, 6, $xiiiCode, 1, 0, 'L');
            $pdf->Cell($compactDescWidth, 6, $xiiiDescription, 1, 0, 'L');
            $pdf->Cell($compactAmountWidth, 6, '$' . number_format($xiiiAmount, 2), 1, 0, 'R');
            $pdf->Cell($compactCodeWidth, 6, $vacCode, 1, 0, 'L');
            $pdf->Cell($compactDescWidth, 6, $vacDescription, 1, 0, 'L');
            $pdf->Cell($compactAmountWidth, 6, '$' . number_format($vacAmount, 2), 1, 1, 'R');
            $pdf->Ln(3);

            $antCode = 'LIQ001';
            $indCode = 'LIQ002';
            $hasAntData = isset($conceptDetails[$antCode]) || !empty($liquidationAccumulations[$antCode]);
            $hasIndData = isset($conceptDetails[$indCode]) || !empty($liquidationAccumulations[$indCode]);
            if ($hasAntData || $hasIndData) {
                $antDescription = $conceptDetails[$antCode]['concepto_descripcion'] ?? 'Prima de antiguedad';
                $indDescription = $conceptDetails[$indCode]['concepto_descripcion'] ?? 'Indemnizacion';
                $antAmount = $conceptDetails[$antCode]['monto'] ?? 0.0;
                $indAmount = $conceptDetails[$indCode]['monto'] ?? 0.0;

                $pdf->SetFillColor(224, 224, 224);
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell($compactCodeWidth, 6, 'Codigo', 1, 0, 'C', true);
                $pdf->Cell($compactDescWidth, 6, 'Descripcion', 1, 0, 'C', true);
                $pdf->Cell($compactAmountWidth, 6, 'Monto', 1, 0, 'C', true);
                $pdf->Cell($compactCodeWidth, 6, 'Codigo', 1, 0, 'C', true);
                $pdf->Cell($compactDescWidth, 6, 'Descripcion', 1, 0, 'C', true);
                $pdf->Cell($compactAmountWidth, 6, 'Monto', 1, 1, 'C', true);

                $antMonths = $liquidationAccumulations[$antCode]['months'] ?? [];
                $indMonths = $liquidationAccumulations[$indCode]['months'] ?? [];
                while (count($antMonths) < 12) {
                    $antMonths[] = [
                        'label' => '',
                        'amount' => 0.0
                    ];
                }
                while (count($indMonths) < 12) {
                    $indMonths[] = [
                        'label' => '',
                        'amount' => 0.0
                    ];
                }

                $pdf->SetFillColor(245, 245, 245);
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->Cell($monthLabelWidth, 5, 'Mes', 1, 0, 'L', true);
                $pdf->Cell($monthAmountWidth, 5, 'Acumulado', 1, 0, 'R', true);
                $pdf->Cell($monthLabelWidth, 5, 'Mes', 1, 0, 'L', true);
                $pdf->Cell($monthAmountWidth, 5, 'Acumulado', 1, 1, 'R', true);

                $pdf->SetFont('helvetica', '', 8);
                for ($i = 0; $i < 12; $i++) {
                    $leftMonth = $antMonths[$i];
                    $rightMonth = $indMonths[$i];
                    $pdf->Cell($monthLabelWidth, 5, $leftMonth['label'], 1, 0, 'L');
                    $pdf->Cell($monthAmountWidth, 5, '$' . number_format($leftMonth['amount'], 2), 1, 0, 'R');
                    $pdf->Cell($monthLabelWidth, 5, $rightMonth['label'], 1, 0, 'L');
                    $pdf->Cell($monthAmountWidth, 5, '$' . number_format($rightMonth['amount'], 2), 1, 1, 'R');
                }

                $pdf->SetFont('helvetica', '', 9);
                $pdf->Cell($compactCodeWidth, 6, $antCode, 1, 0, 'L');
                $pdf->Cell($compactDescWidth, 6, $antDescription, 1, 0, 'L');
                $pdf->Cell($compactAmountWidth, 6, '$' . number_format($antAmount, 2), 1, 0, 'R');
                $pdf->Cell($compactCodeWidth, 6, $indCode, 1, 0, 'L');
                $pdf->Cell($compactDescWidth, 6, $indDescription, 1, 0, 'L');
                $pdf->Cell($compactAmountWidth, 6, '$' . number_format($indAmount, 2), 1, 1, 'R');
                $pdf->Ln(3);
            }

            // ===== ASIGNACIONES =====
            $pdf->SetFillColor(255, 140, 0); // Naranja intenso
            $pdf->SetTextColor(255, 255, 255); // Texto blanco
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 7, 'ASIGNACIONES', 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

            // Cabecera de tabla
            $pdf->SetFillColor(224, 224, 224); // Gris claro
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($colCodigo, 6, 'Código', 1, 0, 'C', true);
            $pdf->Cell($colDescripcion, 6, 'Descripción', 1, 0, 'C', true);
            $pdf->Cell($colMonto, 6, 'Monto', 1, 1, 'C', true);

            // Datos asignaciones
            $pdf->SetFont('helvetica', '', 9);
            $asignaciones = array_filter($details, fn($d) => $d['tipo'] === 'A');
            foreach ($asignaciones as $asignacion) {
                $pdf->Cell($colCodigo, 6, $asignacion['concepto'], 1, 0, 'L');
                $pdf->Cell($colDescripcion, 6, $asignacion['concepto_descripcion'], 1, 0, 'L');
                $pdf->Cell($colMonto, 6, '$' . number_format($asignacion['monto'], 2), 1, 1, 'R');
            }

            // Total asignaciones
            $pdf->SetFillColor(255, 180, 120); // Naranja claro
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($colCodigo + $colDescripcion, 6, 'TOTAL ASIGNACIONES:', 1, 0, 'R', true);
            $pdf->Cell($colMonto, 6, '$' . number_format($totals['total_asignaciones'], 2), 1, 1, 'R', true);
            $pdf->Ln(3);

            // ===== DEDUCCIONES =====
            $pdf->SetFillColor(255, 140, 0); // Naranja intenso (mismo que asignaciones)
            $pdf->SetTextColor(255, 255, 255); // Texto blanco
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 7, 'DEDUCCIONES', 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

            // Cabecera de tabla
            $pdf->SetFillColor(224, 224, 224); // Gris claro
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($colCodigo, 6, 'Código', 1, 0, 'C', true);
            $pdf->Cell($colDescripcion, 6, 'Descripción', 1, 0, 'C', true);
            $pdf->Cell($colMonto, 6, 'Monto', 1, 1, 'C', true);

            // Datos deducciones
            $pdf->SetFont('helvetica', '', 9);
            $deducciones = array_filter($details, fn($d) => $d['tipo'] === 'D');
            foreach ($deducciones as $deduccion) {
                $pdf->Cell($colCodigo, 6, $deduccion['concepto'], 1, 0, 'L');
                $pdf->Cell($colDescripcion, 6, $deduccion['concepto_descripcion'], 1, 0, 'L');
                $pdf->Cell($colMonto, 6, '$' . number_format($deduccion['monto'], 2), 1, 1, 'R');
            }

            // Total deducciones
            $pdf->SetFillColor(255, 180, 120); // Naranja claro (mismo que total asignaciones)
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($colCodigo + $colDescripcion, 6, 'TOTAL DEDUCCIONES:', 1, 0, 'R', true);
            $pdf->Cell($colMonto, 6, '$' . number_format($totals['total_deducciones'], 2), 1, 1, 'R', true);
            $pdf->Ln(5);

            // ===== TOTAL NETO =====
            $pdf->SetFillColor(25, 118, 210); // Azul profundo
            $pdf->SetTextColor(255, 255, 255); // Texto blanco
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell($colCodigo + $colDescripcion, 8, 'TOTAL NETO A PAGAR:', 1, 0, 'R', true);
            $pdf->Cell($colMonto, 8, '$' . number_format($totals['total_neto'], 2), 1, 1, 'R', true);
            $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

            // ===== SECCIÓN DE FIRMAS =====
            $pdf->Ln(15); // Espacio antes de firmas
            $availableWidth = ($pageWidth - $margins['left'] - $margins['right']) / 3;

            // Calcular ancho de cada columna de firma (3 columnas)
            $firmaWidth = $availableWidth; // Restar un poco para espacio entre columnas

            $pdf->SetFont('helvetica', '', 9);

            // Primera línea: espacios para firmar
            $pdf->Cell($firmaWidth, 5, '', 0, 0, 'C'); // Columna 1
            $pdf->Cell($firmaWidth, 5, '', 0, 0, 'C'); // Columna 2
            $pdf->Cell($firmaWidth, 5, '', 0, 1, 'C'); // Columna 3

            $pdf->Ln(10); // Espacio para la firma

            // Segunda línea: líneas de firma
            $pdf->SetFont('helvetica', '', 9);
            $lineaPadding = 1; // Padding interno para las líneas
            $pdf->Cell($firmaWidth - ($lineaPadding * 2), 0, '', 'T', 0, 'C'); // Línea firma 1
            $pdf->Cell($lineaPadding * 2, 5, '', 0, 0); // Espacio entre columnas

            $pdf->Cell($lineaPadding, 5, '', 0, 0); // Padding izquierdo
            $pdf->Cell($firmaWidth - ($lineaPadding * 2), 0, '', 'T', 0, 'C'); // Línea firma 2
            $pdf->Cell($lineaPadding * 2, 5, '', 0, 0); // Espacio entre columnas

            $pdf->Cell($lineaPadding, 5, '', 0, 0); // Padding izquierdo
            $pdf->Cell($firmaWidth - ($lineaPadding * 2), 0, '', 'T', 1, 'C'); // Línea firma 3

            $pdf->Ln(2);

            // Tercera línea: etiquetas de firmas
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($firmaWidth, 5, 'Autorizado por', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, 'Elaborado por', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, 'Recibido por', 0, 1, 'C');

            $pdf->Ln(2);

            // Cuarta línea: Nombres de quien firma
            $gerencia_name = $company_data['legal_representative'] ?? 'N/A';
            $rrhh_name = $company_data['jefe_recursos_humanos'] ?? 'N/A';
            $empleado_name = $employee_info ? $employee_info['name'] : 'N/A';

            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell($firmaWidth, 5, $gerencia_name, 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, $rrhh_name, 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, $employee_info['firstname'], 0, 1, 'C');

            $pdf->Ln(1);

            // Quinta línea: subtexto (cargo/cédula)
            $empleado_cedula = $employee_info ? $employee_info['cedula'] : 'N/A';

            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($firmaWidth, 5, '(Gerencia)', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, '(Recursos Humanos)', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, $employee_info['lastname'], 0, 1, 'C');

            $pdf->Ln(1);

            // Quinta línea: subtexto (cargo/cédula)
            $empleado_cedula = $employee_info ? $employee_info['cedula'] : 'N/A';

            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($firmaWidth, 5, '', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, '', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, 'Cedula: ' . $empleado_cedula, 0, 1, 'C');

            // Generar nombre de archivo
            $filename = 'Liquidacion_' . ($employee_info ? preg_replace('/[^A-Za-z0-9_]/', '_', $employee_info['name']) : 'Planilla') .
                       '_' . date('Y-m-d', strtotime($payroll['fecha'])) . '.pdf';

            // Salida del PDF
            $pdf->Output($filename, 'I');
            exit;

        } catch (\PDOException $e) {
            error_log("Error generating PDF: " . $e->getMessage());
            $this->setToastrMessage('error', 'Error al generar PDF: ' . $e->getMessage(), 'Error de Exportación');
            $this->redirect('/panel/liquidation/payroll-detail/' . $payroll_id);
        } catch (\Exception $e) {
            error_log("Error generating PDF: " . $e->getMessage());
            $this->setToastrMessage('error', 'Error al generar PDF: ' . $e->getMessage(), 'Error de Exportación');
            $this->redirect('/panel/liquidation/payroll-detail/' . $payroll_id);
        }
    }

    /**
     * Obtener acumulados mensuales para liquidación
     * Para LIQ007 (XIII mes): usa período trimestral correcto según legislación panameña
     * Para otros conceptos: usa 11 meses hacia atrás
     * Protected para permitir acceso desde métodos de reportes
     */
    protected function getLiquidationAccumulatedMonths(int $employeeId, string $terminationDate, $tipoAcumulado = 'SALARIO_BASE', string $conceptCode = ''): array
    {
        try {
            $fechaFin = new \DateTime($terminationDate);

            // Para LIQ007 (XIII mes), usar período trimestral correcto de Panamá
            if ($conceptCode === 'LIQ007') {
                $xiiiMesCalculator = new \App\Services\XIIIMesPeriodoTrimestralCalculator();
                $periodoInfo = $xiiiMesCalculator->determinarPeriodoTrimestral($terminationDate);
                $fechaInicio = new \DateTime($periodoInfo['fecha_inicio']);
                $fechaFin = new \DateTime($periodoInfo['fecha_fin']);

                error_log("LIQ007: Usando período trimestral XIII mes - Período {$periodoInfo['periodo']}: {$periodoInfo['fecha_inicio']} a {$periodoInfo['fecha_fin']}");
            } else {
                // Para otros conceptos, usar 11 meses hacia atrás (período de liquidación estándar)
                $fechaInicio = (clone $fechaFin)->modify('-11 months');
            }

            $tipos = is_array($tipoAcumulado) ? $tipoAcumulado : [$tipoAcumulado];
            $tipos = array_values(array_filter(array_map(static function ($value) {
                return trim((string)$value);
            }, $tipos), static fn($value) => $value !== ''));
            if (empty($tipos)) {
                $tipos = ['SALARIO_BASE'];
            }
            $placeholders = implode(',', array_fill(0, count($tipos), '?'));

            // LEFT JOIN para incluir acumulados importados (planilla_id = 0)
            // Lógica dual: planilla_id = 0 usa ano/mes, planilla_id != 0 usa fechas de planilla_cabecera
            $sql = "SELECT ape.ano, ape.mes, SUM(ape.monto) as total
                    FROM acumulados_por_empleado ape
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE ape.employee_id = ?
                    AND ape.tipo_acumulado IN ($placeholders)
                    AND (
                        (ape.planilla_id = 0 AND DATE(CONCAT(ape.ano, '-', LPAD(ape.mes, 2, '0'), '-01')) BETWEEN ? AND ?)
                        OR
                        (ape.planilla_id != 0 AND pc.fecha_hasta >= ? AND pc.fecha_desde <= ?)
                    )
                    GROUP BY ape.ano, ape.mes
                    ORDER BY ape.ano, ape.mes";

            $stmt = $this->db->prepare($sql);
            $fechaInicioStr = $fechaInicio->format('Y-m-d');
            $fechaFinStr = $fechaFin->format('Y-m-d');
            $stmt->execute(array_merge(
                [$employeeId],
                $tipos,
                [$fechaInicioStr, $fechaFinStr, $fechaInicioStr, $fechaFinStr]
            ));
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $byMonth = [];
            foreach ($rows as $row) {
                $key = sprintf('%04d-%02d', (int)$row['ano'], (int)$row['mes']);
                $byMonth[$key] = (float)$row['total'];
            }

            // Calcular meses a mostrar según el período
            $numMonths = $conceptCode === 'LIQ007' ? 4 : 12; // XIII mes: 4 meses del trimestre, otros: 12 meses
            $months = [];
            $total = 0.0;
            $cursor = (clone $fechaInicio)->modify('first day of this month');

            for ($i = 0; $i < $numMonths; $i++) {
                $key = $cursor->format('Y-m');
                $amount = (float)($byMonth[$key] ?? 0);
                $months[] = [
                    'label' => $cursor->format('m/Y'),
                    'amount' => $amount
                ];
                $total += $amount;
                $cursor->modify('+1 month');
            }

            return [
                'start' => $fechaInicio->format('Y-m-d'),
                'end' => $fechaFin->format('Y-m-d'),
                'months' => $months,
                'total' => $total,
                'concept' => $conceptCode,
                'periodo_info' => $conceptCode === 'LIQ007' ? $periodoInfo : null
            ];
        } catch (\Exception $e) {
            error_log("Error building liquidation accumulations: " . $e->getMessage());
        }

        // Fallback en caso de error
        $numMonths = $conceptCode === 'LIQ007' ? 4 : 12;
        $months = [];
        $cursor = new \DateTime(date('Y-m-01'));
        $cursor->modify($conceptCode === 'LIQ007' ? '-3 months' : '-11 months');

        for ($i = 0; $i < $numMonths; $i++) {
            $months[] = [
                'label' => $cursor->format('m/Y'),
                'amount' => 0.0
            ];
            $cursor->modify('+1 month');
        }

        return [
            'start' => null,
            'end' => null,
            'months' => $months,
            'total' => 0.0
        ];
    }

    /**
     * Obtener tipos de acumulados de conceptos según sus fórmulas
     * Protected para permitir acceso desde métodos de reportes
     */
    protected function getConceptAccumulatedTypes(array $conceptCodes): array
    {
        if (empty($conceptCodes)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($conceptCodes), '?'));
        $sql = "SELECT concepto, formula
                FROM concepto
                WHERE concepto IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($conceptCodes);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $typesByConcept = [];
        foreach ($rows as $row) {
            $concepto = $row['concepto'] ?? '';
            if ($concepto === '') {
                continue;
            }
            $types = $this->extractAccumulatedTypesFromFormula($row['formula'] ?? '');
            if (empty($types)) {
                $types = ['SALARIO_BASE'];
            }
            $typesByConcept[$concepto] = $types;
        }

        return $typesByConcept;
    }

    /**
     * Extraer tipos de acumulados de una fórmula
     * Protected para permitir acceso desde métodos relacionados
     */
    protected function extractAccumulatedTypesFromFormula(string $formula): array
    {
        if ($formula === '') {
            return [];
        }

        $types = [];
        $pattern = '/ACUMULADOS\\s*\\(\\s*(?:\"([^\"]+)\"|\\\'([^\\\']+)\\\'|([^,\\)]+))/i';
        if (preg_match_all($pattern, $formula, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $raw = $match[1] ?? ($match[2] ?? ($match[3] ?? ''));
                $raw = trim($raw, " \t\n\r\"'");
                if ($raw === '') {
                    continue;
                }
                foreach (explode(',', $raw) as $item) {
                    $item = trim($item);
                    if ($item !== '') {
                        $types[] = $item;
                    }
                }
            }
        }

        $types = array_values(array_unique($types));
        return $types;
    }

    /**
     * Insertar logos en el PDF alineados con el título
     * Protected para permitir acceso desde exportPayrollPdf
     */
    protected function insertLogosInPDF($pdf, $company_data)
    {
        $logoHeight = 12; // Altura del logo
        $pageWidth = $pdf->getPageWidth();
        $margin = 15; // Margen desde los bordes

        // Guardar posición actual
        $currentY = $pdf->GetY();

        // Logo izquierdo - alineado con el margen izquierdo
        if (!empty($company_data['logo_izquierdo_reportes'])) {
            $leftLogoPath = TenantStorage::getLogoFilesystemPath($company_data['logo_izquierdo_reportes']);
            if ($leftLogoPath) {
                $leftLogoWidth = 25;
                try {
                    $pdf->Image($leftLogoPath, $margin, $currentY, $leftLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                } catch (\Exception $e) {
                    error_log("Error cargando logo izquierdo: " . $e->getMessage());
                }
            }
        }

        // Logo derecho - alineado con el margen derecho
        if (!empty($company_data['logo_derecho_reportes'])) {
            $rightLogoPath = TenantStorage::getLogoFilesystemPath($company_data['logo_derecho_reportes']);
            if ($rightLogoPath) {
                $rightLogoWidth = 35;
                $rightX = $pageWidth - $margin - $rightLogoWidth;
                try {
                    $pdf->Image($rightLogoPath, $rightX, $currentY, $rightLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                } catch (\Exception $e) {
                    error_log("Error cargando logo derecho: " . $e->getMessage());
                }
            }
        }

        // Logo principal (centro) - solo si no hay logos laterales
        if (empty($company_data['logo_izquierdo_reportes']) &&
            empty($company_data['logo_derecho_reportes']) &&
            !empty($company_data['logo_empresa'])) {
            $mainLogoPath = TenantStorage::getLogoFilesystemPath($company_data['logo_empresa']);
            if ($mainLogoPath) {
                $mainLogoWidth = 40;
                $centerX = ($pageWidth - $mainLogoWidth) / 2;
                $pdf->Image($mainLogoPath, $centerX, $currentY, $mainLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
            }
        }

        // NOMBRE DE LA EMPRESA centrado a la misma altura de los logos
        if (!empty($company_data['company_name'])) {
            // Guardar la fuente actual
            $currentFont = $pdf->getFontFamily();
            $currentSize = $pdf->getFontSizePt();

            // Configurar fuente para el nombre de la empresa
            $pdf->SetFont('helvetica', 'B', 16);

            // Calcular posición centrada
            $companyNameWidth = $pdf->GetStringWidth($company_data['company_name']);
            $centerX = ($pageWidth - $companyNameWidth) / 2;

            // Posicionar el texto a la misma altura de los logos (centrado verticalmente)
            $textY = $currentY + ($logoHeight / 2) - 3; // Centrado vertical con pequeño ajuste

            // Escribir el nombre de la empresa
            $pdf->SetXY($centerX, $textY);
            $pdf->Cell($companyNameWidth, 0, $company_data['company_name'], 0, 0, 'C');

            // Restaurar fuente anterior
            $pdf->SetFont($currentFont, '', $currentSize);
        }

        // Reservar espacio después de los logos para que el contenido esté alineado
        if (!empty($company_data['logo_izquierdo_reportes']) ||
            !empty($company_data['logo_derecho_reportes']) ||
            !empty($company_data['logo_empresa'])) {
            $pdf->SetY(22); // Espacio después de logos
        }
    }
}
