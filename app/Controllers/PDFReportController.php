<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Payroll;
use App\Models\Employee;

require_once 'vendor/autoload.php';
use TCPDF;

/**
 * Controlador específico para reportes PDF
 */
class PDFReportController extends Controller
{
    private $payrollModel;
    private $employeeModel;

    public function __construct()
    {
        parent::__construct();
        $this->payrollModel = new Payroll();
        $this->employeeModel = new Employee();
    }

    /**
     * Generar reporte PDF para planilla específica
     */
    public function generatePayrollPDF($payrollId)
    {
        try {
            error_log("PDFReportController: Iniciando generatePayrollPDF con ID: $payrollId");
            
            // La autenticación ya se valida en ReportController
            if (!$payrollId) {
                throw new \Exception('ID de planilla requerido');
            }
            
            error_log("PDFReportController: Obteniendo datos de la planilla");
            // Obtener datos de la planilla usando el mismo método que ExcelReportController
            $planillaData = $this->getPayrollReportData($payrollId);
            
            if (!$planillaData) {
                error_log("PDFReportController: Planilla no encontrada para ID: $payrollId");
                throw new \Exception('Planilla no encontrada');
            }

            error_log("PDFReportController: Datos obtenidos, empleados encontrados: " . count($planillaData['employees'] ?? []));
            
            // Obtener información de la empresa
            $companyInfo = $this->getCompanyInfo();
            error_log("PDFReportController: Información de empresa obtenida");
            
            // Generar PDF
            error_log("PDFReportController: Iniciando generación del PDF");
            $this->generatePDFReport($planillaData, $companyInfo);
            error_log("PDFReportController: PDF generado exitosamente");
            
        } catch (\Exception $e) {
            error_log('PDFReportController Error: ' . $e->getMessage());
            error_log('PDFReportController Stack trace: ' . $e->getTraceAsString());
            throw $e; // Re-lanzar para que ReportController lo maneje
        }
    }

    /**
     * Generar comprobante de pago individual PDF
     */
    public function generatePaySlipPDF($payrollId, $employeeId)
    {
        try {
            // Verificar permisos
            $this->checkPermission('reports_pdf');
            
            // Obtener datos específicos del empleado en la planilla
            $data = $this->payrollModel->getEmployeePayrollData($payrollId, $employeeId);
            
            if (empty($data)) {
                throw new \Exception('Datos no encontrados');
            }

            // Generar PDF del comprobante
            $this->generatePaySlipPDFDocument($data);
            
        } catch (\Exception $e) {
            error_log('Error generando comprobante PDF: ' . $e->getMessage());
            $this->redirect('/panel/planillas?error=' . urlencode('Error generando comprobante PDF'));
        }
    }

    /**
     * Generar el documento PDF de la planilla
     */
    private function generatePDFReport($planillaData, $companyInfo)
    {
        $payroll = $planillaData['payroll'];
        $employees = $planillaData['employees'] ?? [];
        
        // Crear instancia de TCPDF
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuración del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor($companyInfo['company_name']);
        $pdf->SetTitle('Planilla de Sueldos - ' . $payroll['descripcion']);
        $pdf->SetSubject('Reporte de Planilla');
        
        // Configuración de la página
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();
        
        // Header de la empresa
        $this->addPDFHeader($pdf, $companyInfo, $payroll);
        
        // Tabla de empleados
        $this->addEmployeeTable($pdf, $employees);
        
        // Firmas de responsables
        $this->addSignatures($pdf, $companyInfo);
        
        // Output del PDF
        $filename = 'planilla_' . $payroll['id'] . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I'); // I = inline browser, D = download
        exit;
    }

    /**
     * Agregar header del PDF
     */
    private function addPDFHeader($pdf, $companyInfo, $payroll)
    {
        // Título de la empresa
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $companyInfo['company_name'], 0, 1, 'C');
        
        // Título del reporte
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'PLANILLA DE SUELDOS', 0, 1, 'C');
        
        // Descripción y período
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, strtoupper($payroll['descripcion']), 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_inicio']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_fin']));
        $pdf->Cell(0, 6, 'Período: ' . $fechaInicio . ' al ' . $fechaFin, 0, 1, 'C');
        
        $pdf->Ln(5);
        
        // Headers de la tabla
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(220, 220, 220);
        
        // Anchos de columna para orientación horizontal
        $colWidths = [50, 20, 20, 20, 30, 20, 20, 20, 20, 20];
        
        $pdf->Cell($colWidths[0], 6, 'Empleado', 1, 0, 'C', true);
        $pdf->Cell($colWidths[1], 6, 'Cédula', 1, 0, 'C', true);
        $pdf->Cell($colWidths[2], 6, 'Sueldo', 1, 0, 'C', true);
        $pdf->Cell($colWidths[3], 6, 'Ingresos', 1, 0, 'C', true);
        $pdf->Cell($colWidths[4], 6, 'Deducciones', 1, 0, 'C', true);
        $pdf->Cell($colWidths[5], 6, 'Seg. Social', 1, 0, 'C', true);
        $pdf->Cell($colWidths[6], 6, 'Seg. Edu.', 1, 0, 'C', true);
        $pdf->Cell($colWidths[7], 6, 'ISR', 1, 0, 'C', true);
        $pdf->Cell($colWidths[8], 6, 'Otras Ded.', 1, 0, 'C', true);
        $pdf->Cell($colWidths[9], 6, 'Neto', 1, 1, 'C', true);
    }

    /**
     * Agregar tabla de empleados
     */
    private function addEmployeeTable($pdf, $employees)
    {
        // Anchos de columna para orientación horizontal
        $colWidths = [50, 20, 20, 20, 30, 20, 20, 20, 20, 20];
        
        // Datos de empleados
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetFillColor(255, 255, 255);
        
        $totalGeneral = [
            'sueldo' => 0,
            'ingresos' => 0,
            'deducciones' => 0,
            'seguro_social' => 0,
            'seguro_educativo' => 0,
            'impuesto_renta' => 0,
            'otras_deducciones' => 0,
            'neto' => 0
        ];
        
        foreach ($employees as $emp) {
            // Nombre completo (truncado si es muy largo)
            $nombreCompleto = $emp['lastname'] . ', ' . $emp['firstname'];
            if (strlen($nombreCompleto) > 30) {
                $nombreCompleto = substr($nombreCompleto, 0, 27) . '...';
            }
            
            $pdf->Cell($colWidths[0], 6, $nombreCompleto, 1, 0, 'L');
            $pdf->Cell($colWidths[1], 6, $emp['document_id'] ?? 'N/A', 1, 0, 'C');
            $pdf->Cell($colWidths[2], 6, '$' . number_format($emp['salary'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[3], 6, '$' . number_format($emp['totals']['ingresos'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[4], 6, '$' . number_format($emp['totals']['deducciones'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[5], 6, '$' . number_format($emp['totals']['seguro_social'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[6], 6, '$' . number_format($emp['totals']['seguro_educativo'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[7], 6, '$' . number_format($emp['totals']['impuesto_renta'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[8], 6, '$' . number_format($emp['totals']['otras_deducciones'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[9], 6, '$' . number_format($emp['totals']['neto'], 2), 1, 0, 'R');
            $pdf->Ln();
            
            // Acumular totales
            $totalGeneral['sueldo'] += $emp['salary'];
            $totalGeneral['ingresos'] += $emp['totals']['ingresos'];
            $totalGeneral['deducciones'] += $emp['totals']['deducciones'];
            $totalGeneral['seguro_social'] += $emp['totals']['seguro_social'];
            $totalGeneral['seguro_educativo'] += $emp['totals']['seguro_educativo'];
            $totalGeneral['impuesto_renta'] += $emp['totals']['impuesto_renta'];
            $totalGeneral['otras_deducciones'] += $emp['totals']['otras_deducciones'];
            $totalGeneral['neto'] += $emp['totals']['neto'];
        }
        
        // Fila de totales
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell($colWidths[0], 7, 'TOTALES', 1, 0, 'C', true);
        $pdf->Cell($colWidths[1], 7, '', 1, 0, 'C', true);
        $pdf->Cell($colWidths[2], 7, '$' . number_format($totalGeneral['sueldo'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[3], 7, '$' . number_format($totalGeneral['ingresos'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[4], 7, '$' . number_format($totalGeneral['deducciones'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[5], 7, '$' . number_format($totalGeneral['seguro_social'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[6], 7, '$' . number_format($totalGeneral['seguro_educativo'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[7], 7, '$' . number_format($totalGeneral['impuesto_renta'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[8], 7, '$' . number_format($totalGeneral['otras_deducciones'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[9], 7, '$' . number_format($totalGeneral['neto'], 2), 1, 0, 'R', true);
        $pdf->Ln();
    }

    /**
     * Agregar firmas de responsables del reporte
     */
    private function addSignatures($pdf, $companyInfo)
    {
        $pdf->Ln(15);
        
        // Espacio para firmas - 3 columnas
        $colWidth = 90; // Ancho de cada columna para 3 firmas
        $sigHeight = 25; // Altura del área de firma
        
        // Primera fila de firmas
        $pdf->SetFont('helvetica', '', 9);
        
        // Elaborado por
        $pdf->Cell($colWidth, $sigHeight, '', 'B', 0, 'C'); // Línea superior para firma
        $pdf->Cell(5, $sigHeight, '', 0, 0, 'C'); // Espaciado
        
        // Revisado por (Jefe de RRHH)
        $pdf->Cell($colWidth, $sigHeight, '', 'B', 0, 'C'); // Línea superior para firma
        $pdf->Cell(5, $sigHeight, '', 0, 0, 'C'); // Espaciado
        
        // Aprobado por (Director)
        $pdf->Cell($colWidth, $sigHeight, '', 'B', 1, 'C'); // Línea superior para firma
        
        // Nombres bajo las líneas de firma
        $pdf->SetFont('helvetica', 'B', 8);
        
        // Elaborado por
        $pdf->Cell($colWidth, 6, strtoupper($companyInfo['elaborado_por']), 0, 0, 'C');
        $pdf->Cell(5, 6, '', 0, 0, 'C');
        
        // Revisado por
        $pdf->Cell($colWidth, 6, strtoupper($companyInfo['jefe_recursos_humanos']), 0, 0, 'C');
        $pdf->Cell(5, 6, '', 0, 0, 'C');
        
        // Aprobado por
        $pdf->Cell($colWidth, 6, strtoupper($companyInfo['firma_director_planilla']), 0, 1, 'C');
        
        // Cargos bajo los nombres
        $pdf->SetFont('helvetica', '', 7);
        
        // Cargo elaborador
        $pdf->Cell($colWidth, 5, $companyInfo['cargo_elaborador'], 0, 0, 'C');
        $pdf->Cell(5, 5, '', 0, 0, 'C');
        
        // Cargo RRHH
        $pdf->Cell($colWidth, 5, $companyInfo['cargo_jefe_rrhh'], 0, 0, 'C');
        $pdf->Cell(5, 5, '', 0, 0, 'C');
        
        // Cargo Director
        $pdf->Cell($colWidth, 5, $companyInfo['cargo_director_planilla'], 0, 1, 'C');
        
        $pdf->Ln(8);
        
        // Segunda fila para firma del contador si existe
        if (!empty($companyInfo['firma_contador_planilla'])) {
            $pdf->SetFont('helvetica', '', 9);
            
            // Centrar la firma del contador
            $pdf->Cell(95, $sigHeight, '', 0, 0, 'C'); // Espacio para centrar
            $pdf->Cell($colWidth, $sigHeight, '', 'B', 1, 'C'); // Línea para firma del contador
            
            // Nombre del contador
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell(95, 6, '', 0, 0, 'C');
            $pdf->Cell($colWidth, 6, strtoupper($companyInfo['firma_contador_planilla']), 0, 1, 'C');
            
            // Cargo del contador
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell(95, 5, '', 0, 0, 'C');
            $pdf->Cell($colWidth, 5, $companyInfo['cargo_contador_planilla'], 0, 1, 'C');
        }
        
        // Información adicional al final
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 4, 'Fecha de Generación: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
        $pdf->Cell(0, 4, 'Generado por: Sistema de Planillas MVC', 0, 1, 'R');
    }

    /**
     * Generar PDF del comprobante de pago individual
     */
    private function generatePaySlipPDFDocument($data)
    {
        $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuración del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor('Sistema de Planillas');
        $pdf->SetTitle('Comprobante de Pago - ' . $data['employee']['firstname'] . ' ' . $data['employee']['lastname']);
        
        $pdf->SetMargins(20, 30, 20);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();
        
        $employee = $data['employee'];
        $payroll = $data['payroll'];
        
        // Header del comprobante
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'COMPROBANTE DE PAGO', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, $payroll['descripcion'], 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_inicio']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_fin']));
        $pdf->Cell(0, 6, 'Período: ' . $fechaInicio . ' al ' . $fechaFin, 0, 1, 'C');
        
        $pdf->Ln(10);
        
        // Información del empleado
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 8, 'DATOS DEL EMPLEADO', 0, 1, 'L', true);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(50, 6, 'Nombre:', 0, 0, 'L');
        $pdf->Cell(0, 6, $employee['firstname'] . ' ' . $employee['lastname'], 0, 1, 'L');
        
        $pdf->Cell(50, 6, 'Cédula:', 0, 0, 'L');
        $pdf->Cell(0, 6, $employee['document_id'] ?? 'N/A', 0, 1, 'L');
        
        $pdf->Cell(50, 6, 'Puesto:', 0, 0, 'L');
        $pdf->Cell(0, 6, $employee['puesto_actual'] ?? $employee['position_name'] ?? 'N/A', 0, 1, 'L');
        
        $pdf->Cell(50, 6, 'Sueldo Base:', 0, 0, 'L');
        $pdf->Cell(0, 6, '$' . number_format($employee['salary'], 2), 0, 1, 'L');
        
        $pdf->Ln(8);
        
        // Ingresos
        if (!empty($data['ingresos'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetFillColor(220, 255, 220);
            $pdf->Cell(0, 8, 'INGRESOS', 0, 1, 'L', true);
            
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(30, 6, 'Código', 1, 0, 'C', true);
            $pdf->Cell(100, 6, 'Descripción', 1, 0, 'C', true);
            $pdf->Cell(30, 6, 'Monto', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 9);
            foreach ($data['ingresos'] as $ingreso) {
                $pdf->Cell(30, 5, $ingreso['codigo'], 1, 0, 'C');
                $pdf->Cell(100, 5, $ingreso['descripcion'], 1, 0, 'L');
                $pdf->Cell(30, 5, '$' . number_format($ingreso['monto'], 2), 1, 1, 'R');
            }
            
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(130, 6, 'TOTAL INGRESOS:', 1, 0, 'R', true);
            $pdf->Cell(30, 6, '$' . number_format($data['total_ingresos'], 2), 1, 1, 'R', true);
        }
        
        $pdf->Ln(5);
        
        // Deducciones
        if (!empty($data['deducciones'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetFillColor(255, 220, 220);
            $pdf->Cell(0, 8, 'DEDUCCIONES', 0, 1, 'L', true);
            
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(30, 6, 'Código', 1, 0, 'C', true);
            $pdf->Cell(100, 6, 'Descripción', 1, 0, 'C', true);
            $pdf->Cell(30, 6, 'Monto', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 9);
            foreach ($data['deducciones'] as $deduccion) {
                $pdf->Cell(30, 5, $deduccion['codigo'], 1, 0, 'C');
                $pdf->Cell(100, 5, $deduccion['descripcion'], 1, 0, 'L');
                $pdf->Cell(30, 5, '$' . number_format($deduccion['monto'], 2), 1, 1, 'R');
            }
            
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(130, 6, 'TOTAL DEDUCCIONES:', 1, 0, 'R', true);
            $pdf->Cell(30, 6, '$' . number_format($data['total_deducciones'], 2), 1, 1, 'R', true);
        }
        
        $pdf->Ln(10);
        
        // Resumen final
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(0, 10, 'SALARIO NETO A PAGAR: $' . number_format($data['salario_neto'], 2), 1, 1, 'C', true);
        
        // Output del PDF
        $filename = 'comprobante_' . $employee['id'] . '_' . $payroll['id'] . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
    }

    /**
     * Obtener datos de la planilla para reportes
     */
    private function getPayrollReportData($payrollId)
    {
        try {
            $reportModel = $this->model('Report');
            $db = $reportModel->getDatabase();
            $connection = $db->getConnection();
            
            error_log("PDFReportController: Obteniendo datos básicos de planilla ID: $payrollId");
            
            // Información básica de la planilla (simplificada)
            $sql = "SELECT p.*, 
                           p.fecha as fecha_inicio,
                           p.fecha as fecha_fin
                   FROM planilla_cabecera p
                   WHERE p.id = ?"; 
            $stmt = $connection->prepare($sql);
            $stmt->execute([$payrollId]);
            $payroll = $stmt->fetch();
            
            if (!$payroll) {
                error_log("PDFReportController: No se encontró planilla con ID: $payrollId");
                // Verificar qué planillas existen
                $checkSql = "SELECT id, descripcion FROM planilla_cabecera ORDER BY id DESC LIMIT 5";
                $checkStmt = $connection->prepare($checkSql);
                $checkStmt->execute();
                $existing = $checkStmt->fetchAll();
                error_log("PDFReportController: Planillas disponibles: " . json_encode($existing));
                return null;
            }
            
            error_log("PDFReportController: Planilla encontrada: " . $payroll['descripcion']);
            
            error_log("PDFReportController: Obteniendo empleados de la planilla");
            
            // Consulta simplificada - primero obtener empleados únicos
            $employeeSql = "SELECT DISTINCT
                                e.id AS employee_id,
                                e.firstname,
                                e.lastname,
                                e.document_id,
                                e.fecha_ingreso,
                                COALESCE(e.sueldo_individual, 0) as salary
                            FROM planilla_detalle pd
                            INNER JOIN employees e ON pd.employee_id = e.id
                            WHERE pd.planilla_cabecera_id = ?
                            ORDER BY e.lastname, e.firstname";
            
            $stmt = $connection->prepare($employeeSql);
            $stmt->execute([$payrollId]);
            $employeesData = $stmt->fetchAll();
            
            error_log("PDFReportController: Empleados encontrados: " . count($employeesData));
            
            if (empty($employeesData)) {
                error_log("PDFReportController: No se encontraron empleados para la planilla ID: $payrollId");
                // Verificar si hay datos en planilla_detalle
                $detailSql = "SELECT COUNT(*) as count FROM planilla_detalle WHERE planilla_cabecera_id = ?";
                $detailStmt = $connection->prepare($detailSql);
                $detailStmt->execute([$payrollId]);
                $detailCount = $detailStmt->fetch();
                error_log("PDFReportController: Registros en planilla_detalle: " . $detailCount['count']);
                return null;
            }
            
            // Ahora obtener conceptos para cada empleado
            $conceptsSql = "SELECT
                                pd.employee_id,
                                pd.monto AS concepto_monto,
                                c.concepto AS concepto_codigo,
                                c.descripcion AS concepto_descripcion,
                                c.tipo_concepto,
                                c.categoria_reporte
                            FROM planilla_detalle pd
                            INNER JOIN concepto c ON pd.concepto_id = c.id
                            WHERE pd.planilla_cabecera_id = ? 
                                AND c.incluir_reporte = 1
                            ORDER BY pd.employee_id, c.tipo_concepto, c.concepto";
            
            $stmt = $connection->prepare($conceptsSql);
            $stmt->execute([$payrollId]);
            $conceptsData = $stmt->fetchAll();
            
            error_log("PDFReportController: Conceptos encontrados: " . count($conceptsData));
            
            // Organizar datos por empleado
            $employees = [];
            
            // Primero crear estructura básica de empleados
            foreach ($employeesData as $emp) {
                $employees[$emp['employee_id']] = [
                    'id' => $emp['employee_id'],
                    'employee_id' => $emp['employee_id'],
                    'firstname' => $emp['firstname'],
                    'lastname' => $emp['lastname'],
                    'document_id' => $emp['document_id'],
                    'fecha_ingreso' => $emp['fecha_ingreso'],
                    'salary' => $emp['salary'],
                    'puesto_actual' => 'N/A',
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
            
            // Luego agregar conceptos
            foreach ($conceptsData as $row) {
                $empKey = $row['employee_id'];
                
                if (!isset($employees[$empKey])) {
                    error_log("PDFReportController: Empleado ID $empKey no encontrado en lista de empleados");
                    continue;
                }
                
                $monto = floatval($row['concepto_monto'] ?? 0);
                
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
                    
                    // Categorizar deducciones
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
                        default:
                            $employees[$empKey]['totals']['otras_deducciones'] += $monto;
                            break;
                    }
                }
                
                // Calcular neto
                $employees[$empKey]['totals']['neto'] = 
                    $employees[$empKey]['totals']['ingresos'] - $employees[$empKey]['totals']['deducciones'];
            }
            
            error_log("PDFReportController: Empleados procesados: " . count($employees));
            
            return [
                'payroll' => $payroll,
                'employees' => array_values($employees)
            ];
            
        } catch (\Exception $e) {
            error_log("Error obteniendo datos del reporte PDF: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener información de la empresa
     */
    private function getCompanyInfo()
    {
        try {
            $reportModel = $this->model('Report');
            $db = $reportModel->getDatabase();
            $connection = $db->getConnection();
            
            $sql = "SELECT * FROM companies WHERE id = 1";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $company = $stmt->fetch();
            
            return [
                'company_name' => $company['company_name'] ?? 'EMPRESA EJEMPLO S.A.',
                'ruc' => $company['ruc'] ?? '1234567890-1-DV',
                'address' => $company['address'] ?? 'Dirección Empresa',
                'legal_representative' => $company['legal_representative'] ?? 'Representante Legal',
                'jefe_recursos_humanos' => $company['jefe_recursos_humanos'] ?? 'Jefe de RRHH',
                'cargo_jefe_rrhh' => $company['cargo_jefe_rrhh'] ?? 'Jefe de Recursos Humanos',
                'elaborado_por' => $company['elaborado_por'] ?? 'Especialista en Nóminas',
                'cargo_elaborador' => $company['cargo_elaborador'] ?? 'Especialista en Nóminas',
                'firma_director_planilla' => $company['firma_director_planilla'] ?? $company['legal_representative'] ?? 'Director General',
                'cargo_director_planilla' => $company['cargo_director_planilla'] ?? 'Director General',
                'firma_contador_planilla' => $company['firma_contador_planilla'] ?? 'Contador General',
                'cargo_contador_planilla' => $company['cargo_contador_planilla'] ?? 'Contador General'
            ];
        } catch (\Exception $e) {
            return [
                'company_name' => 'EMPRESA EJEMPLO S.A.',
                'ruc' => '1234567890-1-DV',
                'address' => 'Dirección Empresa',
                'legal_representative' => 'Representante Legal',
                'jefe_recursos_humanos' => 'Jefe de RRHH',
                'cargo_jefe_rrhh' => 'Jefe de Recursos Humanos',
                'elaborado_por' => 'Especialista en Nóminas',
                'cargo_elaborador' => 'Especialista en Nóminas',
                'firma_director_planilla' => 'Director General',
                'cargo_director_planilla' => 'Director General',
                'firma_contador_planilla' => 'Contador General',
                'cargo_contador_planilla' => 'Contador General'
            ];
        }
    }

    /**
     * Verificar autenticación
     */
    private function requireAuth()
    {
        if (!isset($_SESSION['admin'])) {
            $this->redirect('/admin');
        }
    }

    /**
     * Verificar permisos
     */
    private function checkPermission($permission)
    {
        if (!isset($_SESSION['permissions']) || !in_array($permission, $_SESSION['permissions'])) {
            throw new \Exception('No tiene permisos para acceder a esta funcionalidad');
        }
    }
}