<?php

namespace App\Controllers;

use App\Core\Controller;
use TCPDF;

class ReportController extends Controller
{
    public $companyModel;
    
    public function __construct()
    {
        parent::__construct(); // Inicializar conexión DB desde clase base
        // Los modelos se cargan dinámicamente cuando se necesitan
        $this->companyModel = $this->model('Company');
    }
    
    /**
     * Obtener símbolo de moneda configurado
     */
    public function getCurrencySymbol()
    {
        return $this->companyModel->getCurrencySymbol();
    }
    
    /**
     * Formatear monto con símbolo de moneda
     */
    public function formatCurrency($amount, $decimals = 2)
    {
        return $this->getCurrencySymbol() . ' ' . number_format($amount, $decimals);
    }
    
    /**
     * Vista principal de reportes
     */
    public function index()
    {
        $this->requireAuth();
        
        // Obtener planillas disponibles para reportes
        $reportModel = $this->model('Report');
        $payrolls = $reportModel->getPayrollsForReports();
        
        $data = [
            'title' => 'Reportes del Sistema',
            'page_title' => 'Centro de Reportes',
            'payrolls' => $payrolls
        ];
        
        $this->view('reports/index', $data);
    }
    
    /**
     * Vista de exportaciones
     */
    public function exports()
    {
        $this->requireAuth();
        
        $data = [
            'title' => 'Reportes de Exportación',
            'page_title' => 'Exportar Datos',
            'breadcrumb' => [
                ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                ['name' => 'Reportes', 'url' => '/panel/reports'],
                ['name' => 'Exportaciones', 'url' => '#']
            ]
        ];
        
        $this->view('admin/reports/exports', $data);
    }
    
    /**
     * Generar reporte de planilla en PDF
     */
    public function planillaPdf($payrollId)
    {
        try {
            $this->requireAuth();

            // Debug: Log el inicio del método
            error_log("planillaPdf: Iniciando generación PDF para planilla ID: $payrollId");
            
            if (!$payrollId) {
                $_SESSION['error'] = 'ID de planilla requerido';
                $this->redirect('/panel/reports');
                return;
            }
            
            // Delegar al controlador especializado de PDF
            $pdfController = new \App\Controllers\PDFReportController();
            error_log("planillaPdf: Instancia PDF creada, llamando generatePayrollPDF");
            $pdfController->generatePayrollPDF($payrollId);
            error_log("planillaPdf: generatePayrollPDF completado");
            
        } catch (\Exception $e) {
            error_log('Error en planillaPdf: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $_SESSION['error'] = 'Error al generar el reporte PDF: ' . $e->getMessage();
            $this->redirect('/panel/reports');
        }
    }

    /**
     * Generar todos los comprobantes de pago de una planilla
     */
    public function comprobantesPlanilla($payrollId)
    {
        try {
            //error_log("=== Iniciando comprobantesPlanilla con ID: $payrollId ===");

            $this->requireAuth();

            if (!$payrollId) {
                error_log("Error: ID de planilla requerido");
                $_SESSION['error'] = 'ID de planilla requerido';
                $this->redirect('/panel/reports');
            }

            //error_log("Obteniendo datos de empleados para planilla $payrollId");

            // Obtener datos de todos los empleados de la planilla
            $planillaData = $this->getAllEmployeesPayrollData($payrollId);

            if (!$planillaData) {
                error_log("Error: No se pudieron obtener datos de la planilla $payrollId");
                $_SESSION['error'] = 'Error al obtener datos de la planilla';
                $this->redirect('/panel/reports');
            }

            if (empty($planillaData['employees'])) {
                error_log("Error: No hay empleados en la planilla $payrollId");
                $_SESSION['error'] = 'No hay empleados en esta planilla';
                $this->redirect('/panel/reports');
            }

            error_log("Empleados encontrados: " . count($planillaData['employees']));

            // Generar PDF con todos los comprobantes
            $this->generateAllPaySlipsPDF($planillaData);

        } catch (\Exception $e) {
            error_log("Error en ReportController@comprobantesPlanilla: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = 'Error al generar los comprobantes: ' . $e->getMessage();
            $this->redirect('/panel/reports');
        }
    }

    /**
     * Generar comprobante individual de un empleado en orientación horizontal
     * Para visualización en pantalla y envío por correo electrónico
     *
     * @param int $payrollId ID de la planilla
     * @param int $employeeId ID del empleado
     * @param string $output Modo de salida: 'I' = inline (navegador), 'S' = string (para email), 'F' = archivo
     * @param string $dest Ruta destino si output='F'
     * @return mixed PDF output según parámetro $output
     */
    public function comprobanteIndividual($payrollId, $employeeId, $output = 'I', $dest = '')
    {
        try {
            $this->requireAuth();

            if (!$payrollId || !$employeeId) {
                throw new \Exception('ID de planilla y empleado requeridos');
            }

            // Obtener datos del empleado específico
            $employeeData = $this->getEmployeePayrollData($payrollId, $employeeId);

            if (!$employeeData) {
                throw new \Exception('No se encontraron datos del empleado en esta planilla');
            }

            // Generar PDF horizontal
            return $this->generateIndividualPaySlipPDF($employeeData, $output, $dest);

        } catch (\Exception $e) {
            error_log("Error en ReportController@comprobanteIndividual: " . $e->getMessage());

            if ($output === 'I') {
                $_SESSION['error'] = 'Error al generar el comprobante: ' . $e->getMessage();
                $this->redirect('/panel/reports');
            } else {
                throw $e; // Re-lanzar para manejo en email
            }
        }
    }

    /**
     * Generar comprobantes horizontales para todos los empleados de una planilla
     * Un comprobante por página en orientación horizontal
     *
     * @param int $payrollId ID de la planilla
     */
    public function comprobantesPlanillaHorizontal($payrollId)
    {
        try {
            $this->requireAuth();

            if (!$payrollId) {
                throw new \Exception('ID de planilla requerido');
            }

            // Obtener todos los empleados de la planilla
            $allEmployeesData = $this->getAllEmployeesPayrollData($payrollId);

            if (!$allEmployeesData || empty($allEmployeesData['employees'])) {
                $_SESSION['error'] = 'No se encontraron empleados en esta planilla';
                $this->redirect('/panel/payrolls');
                return;
            }

            // Generar PDF con todos los comprobantes
            $this->generateAllPayslipsHorizontalPDF($allEmployeesData);

        } catch (\Exception $e) {
            error_log("Error en ReportController@comprobantesPlanillaHorizontal: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar comprobantes: ' . $e->getMessage();
            $this->redirect('/panel/payrolls');
        }
    }

    /**
     * Generar PDF con todos los comprobantes horizontales (uno por página)
     *
     * @param array $allEmployeesData Datos de todos los empleados
     */
    public function generateAllPayslipsHorizontalPDF($allEmployeesData)
    {
        $payroll = $allEmployeesData['payroll'];
        $employees = $allEmployeesData['employees'];

        // Crear instancia TCPDF en orientación HORIZONTAL (Landscape)
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Configuración del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor('Sistema de Planillas');
        $pdf->SetTitle('Comprobantes de Pago - ' . $payroll['descripcion']);
        $pdf->SetSubject('Comprobantes de Pago');

        // Desactivar header y footer automáticos (quita línea superior)
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Márgenes reducidos para orientación horizontal
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Obtener información de la empresa para logos
        $companyInfo = $this->getCompanyInfo();

        // Obtener firmas de la empresa
        $companySignatures = $this->companyModel->getSignaturesForReports();

        // Generar una página por cada empleado
        foreach ($employees as $index => $employeeData) {
            // Agregar nueva página para cada empleado
            $pdf->AddPage();

            // Datos del empleado
            $employee = $employeeData['employee'];

            // Insertar logos en cada página
            $this->insertLogosInPDF($pdf, $companyInfo);
            $pdf->Ln();

            // Header del comprobante
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 8, 'COMPROBANTE DE PAGO', 0, 1, 'C');

            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 6, $payroll['descripcion'], 0, 1, 'C');

            $pdf->SetFont('helvetica', '', 9);
            $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_desde']));
            $fechaFin = date('d/m/Y', strtotime($payroll['fecha_hasta']));
            $pdf->Cell(0, 5, 'Período: ' . $fechaInicio . ' al ' . $fechaFin, 0, 1, 'C');

            $pdf->Ln(8);

            // === SECCIÓN: DATOS DEL EMPLEADO ===
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetFillColor(255, 140, 0); // Naranja intenso (mismo que liquidaciones)
            $pdf->SetTextColor(255, 255, 255); // Texto blanco
            $pdf->Cell(0, 7, 'DATOS DEL EMPLEADO', 0, 1, 'L', true);
            $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

            $pdf->SetFont('helvetica', '', 9);

            // Fila 1: Nombre y Cédula
            $pdf->Cell(25, 5, 'Nombre:', 0, 0, 'L');
            $pdf->Cell(113, 5, $employee['firstname'] . ' ' . $employee['lastname'], 0, 0, 'L');
            $pdf->Cell(25, 5, 'Cédula:', 0, 0, 'L');
            $pdf->Cell(0, 5, $employee['document_id'] ?? 'N/A', 0, 1, 'L');

            // Fila 2: Cargo/Posición y Salario Base
            $etiquetaPuesto = $employee['etiqueta_puesto'] ?? 'Cargo';
            $pdf->Cell(25, 5, $etiquetaPuesto . ':', 0, 0, 'L');
            $pdf->Cell(113, 5, $employee['puesto_actual'] ?? 'N/A', 0, 0, 'L');
            $pdf->Cell(25, 5, 'Salario Base:', 0, 0, 'L');
            $pdf->Cell(0, 5, '$' . number_format($employee['salario_base'], 2), 0, 1, 'L');

            $pdf->Ln(6);

            // === LAYOUT HORIZONTAL: INGRESOS Y DEDUCCIONES LADO A LADO ===

            // Calcular anchos de columnas
            $margins = $pdf->getMargins();
            $leftMargin = $margins['left'];
            $rightMargin = $margins['right'];
            $pageWidth = $pdf->getPageWidth() - $leftMargin - $rightMargin;
            $colWidth = ($pageWidth - 10) / 2; // 10 = espacio entre columnas

            // Guardar posición Y inicial
            $startY = $pdf->GetY();
            $leftX = $leftMargin;
            $rightX = $leftX + $colWidth + 10;

            // === COLUMNA IZQUIERDA: INGRESOS ===
            $pdf->SetXY($leftX, $startY);

            if (!empty($employeeData['ingresos'])) {
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->SetFillColor(255, 140, 0); // Naranja intenso
                $pdf->SetTextColor(255, 255, 255); // Texto blanco
                $pdf->Cell($colWidth, 6, 'INGRESOS', 1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

                $pdf->SetX($leftX);
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(224, 224, 224); // Gris claro (mismo que liquidaciones)
                $colCodigo = 25;
                $colDescripcion = $colWidth - $colCodigo - 30;
                $colMonto = 30;

                $pdf->Cell($colCodigo, 5, 'Código', 1, 0, 'C', true);
                $pdf->Cell($colDescripcion, 5, 'Descripción', 1, 0, 'C', true);
                $pdf->Cell($colMonto, 5, 'Monto', 1, 1, 'C', true);

                $pdf->SetFont('helvetica', '', 8);
                foreach ($employeeData['ingresos'] as $ingreso) {
                    $pdf->SetX($leftX);
                    $pdf->Cell($colCodigo, 5, $ingreso['codigo'], 1, 0, 'C');
                    $pdf->Cell($colDescripcion, 5, substr($ingreso['descripcion'], 0, 40), 1, 0, 'L');
                    $pdf->Cell($colMonto, 5, '$' . number_format($ingreso['monto'], 2), 1, 1, 'R');
                }

                $pdf->SetX($leftX);
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(255, 180, 120); // Naranja claro (mismo que liquidaciones)
                $pdf->Cell($colCodigo + $colDescripcion, 6, 'TOTAL INGRESOS:', 1, 0, 'R', true);
                $pdf->Cell($colMonto, 6, '$' . number_format($employeeData['total_ingresos'], 2), 1, 1, 'R', true);
            }

            $leftEndY = $pdf->GetY();

            // === COLUMNA DERECHA: DEDUCCIONES ===
            $pdf->SetXY($rightX, $startY);

            if (!empty($employeeData['deducciones'])) {
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->SetFillColor(255, 140, 0); // Naranja intenso (mismo que ingresos)
                $pdf->SetTextColor(255, 255, 255); // Texto blanco
                $pdf->Cell($colWidth, 6, 'DEDUCCIONES', 1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

                $pdf->SetX($rightX);
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(224, 224, 224); // Gris claro
                $colCodigo = 25;
                $colDescripcion = $colWidth - $colCodigo - 30;
                $colMonto = 30;

                $pdf->Cell($colCodigo, 5, 'Código', 1, 0, 'C', true);
                $pdf->Cell($colDescripcion, 5, 'Descripción', 1, 0, 'C', true);
                $pdf->Cell($colMonto, 5, 'Monto', 1, 1, 'C', true);

                $pdf->SetFont('helvetica', '', 8);
                foreach ($employeeData['deducciones'] as $deduccion) {
                    $pdf->SetX($rightX);
                    $pdf->Cell($colCodigo, 5, $deduccion['codigo'], 1, 0, 'C');
                    $pdf->Cell($colDescripcion, 5, substr($deduccion['descripcion'], 0, 40), 1, 0, 'L');
                    $pdf->Cell($colMonto, 5, '$' . number_format($deduccion['monto'], 2), 1, 1, 'R');
                }

                $pdf->SetX($rightX);
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(255, 180, 120); // Naranja claro (mismo que total ingresos)
                $pdf->Cell($colCodigo + $colDescripcion, 6, 'TOTAL DEDUCCIONES:', 1, 0, 'R', true);
                $pdf->Cell($colMonto, 6, '$' . number_format($employeeData['total_deducciones'], 2), 1, 1, 'R', true);
            }

            $rightEndY = $pdf->GetY();

            // Posicionar después de la tabla más alta
            $maxY = max($leftEndY, $rightEndY);
            $pdf->SetY($maxY + 8);

            // === RESUMEN FINAL: SALARIO NETO ===
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetFillColor(25, 118, 210); // Azul profundo (mismo que liquidaciones)
            $pdf->SetTextColor(255, 255, 255); // Texto blanco
            $pdf->Cell(0, 10, 'SALARIO NETO A PAGAR: $' . number_format($employeeData['neto'], 2), 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

            $pdf->Ln(10);

            // === FIRMAS ===
            $pdf->SetFont('helvetica', '', 8);

            // Tres columnas para las firmas (Elaborado por, Autorizado por, Recibido por)
            $colWidth = ($pageWidth - $leftMargin - $rightMargin) / 3;

            // Líneas para firmas
            $pdf->Cell($colWidth, 3, '', 0, 0, 'C');
            $pdf->Cell($colWidth, 3, '', 0, 0, 'C');
            $pdf->Cell($colWidth, 3, '', 0, 1, 'C');

            $pdf->Cell($colWidth, 3, '______________________', 0, 0, 'C');
            $pdf->Cell($colWidth, 3, '______________________', 0, 0, 'C');
            $pdf->Cell($colWidth, 3, '______________________', 0, 1, 'C');

            // Nombres
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell($colWidth, 4, $companySignatures['elaborado_por'] ?: 'Por definir', 0, 0, 'C');
            $pdf->Cell($colWidth, 4, $companySignatures['jefe_recursos_humanos'] ?: 'Por definir', 0, 0, 'C');
            $pdf->Cell($colWidth, 4, $employee['firstname'] . ' ' . $employee['lastname'], 0, 1, 'C');

            // Cargos
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell($colWidth, 3, $companySignatures['cargo_elaborador'] ?: 'Elaborado por', 0, 0, 'C');
            $pdf->Cell($colWidth, 3, $companySignatures['cargo_jefe_rrhh'] ?: 'Autorizado por', 0, 0, 'C');
            $pdf->Cell($colWidth, 3, 'Recibido por (Colaborador)', 0, 1, 'C');

            $pdf->Ln(3);

            /*// Nota informativa
            $pdf->SetFont('helvetica', 'I', 7);
            $pdf->Cell(0, 4, 'Este comprobante es un documento informativo generado por el Sistema de Planillas.', 0, 1, 'C');*/
        }

        // Output del PDF
        $filename = 'comprobantes_horizontal_' . $payroll['id'] . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
    }

    /**
     * Generar PDF de comprobante individual horizontal
     * Layout optimizado para mostrar en una sola página horizontal
     *
     * @param array $data Datos del empleado y planilla
     * @param string $output 'I' = inline, 'S' = string, 'F' = file
     * @param string $dest Ruta destino para output='F'
     * @return mixed PDF según parámetro output
     */
    public function generateIndividualPaySlipPDF($data, $output = 'I', $dest = '')
    {
        $employee = $data['employee'];
        $payroll = $data['payroll'];

        // Crear instancia TCPDF en orientación HORIZONTAL (Landscape)
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Configuración del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor('Sistema de Planillas');
        $pdf->SetTitle('Comprobante de Pago - ' . $employee['firstname'] . ' ' . $employee['lastname']);

        // Desactivar header y footer automáticos (quita línea superior)
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Márgenes reducidos para aprovechar espacio horizontal
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        // Obtener información de la empresa para logos
        $companyInfo = $this->getCompanyInfo();

        // Insertar logos en la cabecera
        $this->insertLogosInPDF($pdf, $companyInfo);
        $pdf->Ln(); // Salto de línea después de logos para evitar superposición

        // Header del comprobante
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 8, 'COMPROBANTE DE PAGO', 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, $payroll['descripcion'], 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 9);
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_desde']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_hasta']));
        $pdf->Cell(0, 5, 'Período: ' . $fechaInicio . ' al ' . $fechaFin, 0, 1, 'C');

        $pdf->Ln(8);

        // === SECCIÓN: DATOS DEL EMPLEADO ===
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(255, 140, 0); // Naranja intenso (mismo que liquidaciones)
        $pdf->SetTextColor(255, 255, 255); // Texto blanco
        $pdf->Cell(0, 7, 'DATOS DEL EMPLEADO', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

        $pdf->SetFont('helvetica', '', 9);

        // Fila 1: Nombre y Cédula
        $pdf->Cell(40, 5, 'Nombre:', 0, 0, 'L');
        $pdf->Cell(90, 5, $employee['firstname'] . ' ' . $employee['lastname'], 0, 0, 'L');
        $pdf->Cell(40, 5, 'Cédula:', 0, 0, 'L');
        $pdf->Cell(0, 5, $employee['document_id'] ?? 'N/A', 0, 1, 'L');

        // Fila 2: Cargo/Posición y Salario Base
        $etiquetaPuesto = $employee['etiqueta_puesto'] ?? 'Cargo';
        $pdf->Cell(40, 5, $etiquetaPuesto . ':', 0, 0, 'L');
        $pdf->Cell(90, 5, $employee['puesto_actual'] ?? 'N/A', 0, 0, 'L');
        $pdf->Cell(40, 5, 'Salario Base:', 0, 0, 'L');
        $pdf->Cell(0, 5, '$' . number_format($employee['salario_base'], 2), 0, 1, 'L');

        $pdf->Ln(6);

        // === LAYOUT HORIZONTAL: INGRESOS Y DEDUCCIONES LADO A LADO ===

        // Calcular anchos de columnas para distribución horizontal
        $margins = $pdf->getMargins();
        $leftMargin = $margins['left'];
        $rightMargin = $margins['right'];
        $pageWidth = $pdf->getPageWidth() - $leftMargin - $rightMargin;
        $colWidth = ($pageWidth - 10) / 2; // 10 = espacio entre columnas

        // Guardar posición Y inicial para alinear ambas tablas
        $startY = $pdf->GetY();
        $leftX = $leftMargin;
        $rightX = $leftX + $colWidth + 10;

        // === COLUMNA IZQUIERDA: INGRESOS ===
        $pdf->SetXY($leftX, $startY);

        if (!empty($data['ingresos'])) {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetFillColor(255, 140, 0); // Naranja intenso
            $pdf->SetTextColor(255, 255, 255); // Texto blanco
            $pdf->Cell($colWidth, 6, 'INGRESOS', 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

            $pdf->SetX($leftX);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(224, 224, 224); // Gris claro
            $colCodigo = 25;
            $colDescripcion = $colWidth - $colCodigo - 30;
            $colMonto = 30;

            $pdf->Cell($colCodigo, 5, 'Código', 1, 0, 'C', true);
            $pdf->Cell($colDescripcion, 5, 'Descripción', 1, 0, 'C', true);
            $pdf->Cell($colMonto, 5, 'Monto', 1, 1, 'C', true);

            $pdf->SetFont('helvetica', '', 8);
            foreach ($data['ingresos'] as $ingreso) {
                $pdf->SetX($leftX);
                $pdf->Cell($colCodigo, 5, $ingreso['codigo'], 1, 0, 'C');
                $pdf->Cell($colDescripcion, 5, substr($ingreso['descripcion'], 0, 40), 1, 0, 'L');
                $pdf->Cell($colMonto, 5, '$' . number_format($ingreso['monto'], 2), 1, 1, 'R');
            }

            $pdf->SetX($leftX);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(255, 180, 120); // Naranja claro
            $pdf->Cell($colCodigo + $colDescripcion, 6, 'TOTAL INGRESOS:', 1, 0, 'R', true);
            $pdf->Cell($colMonto, 6, '$' . number_format($data['total_ingresos'], 2), 1, 1, 'R', true);
        }

        // Guardar altura máxima alcanzada por ingresos
        $leftEndY = $pdf->GetY();

        // === COLUMNA DERECHA: DEDUCCIONES ===
        $pdf->SetXY($rightX, $startY);

        if (!empty($data['deducciones'])) {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetFillColor(255, 140, 0); // Naranja intenso (mismo que ingresos)
            $pdf->SetTextColor(255, 255, 255); // Texto blanco
            $pdf->Cell($colWidth, 6, 'DEDUCCIONES', 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

            $pdf->SetX($rightX);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(224, 224, 224); // Gris claro
            $colCodigo = 25;
            $colDescripcion = $colWidth - $colCodigo - 30;
            $colMonto = 30;

            $pdf->Cell($colCodigo, 5, 'Código', 1, 0, 'C', true);
            $pdf->Cell($colDescripcion, 5, 'Descripción', 1, 0, 'C', true);
            $pdf->Cell($colMonto, 5, 'Monto', 1, 1, 'C', true);

            $pdf->SetFont('helvetica', '', 8);
            foreach ($data['deducciones'] as $deduccion) {
                $pdf->SetX($rightX);
                $pdf->Cell($colCodigo, 5, $deduccion['codigo'], 1, 0, 'C');
                $pdf->Cell($colDescripcion, 5, substr($deduccion['descripcion'], 0, 40), 1, 0, 'L');
                $pdf->Cell($colMonto, 5, '$' . number_format($deduccion['monto'], 2), 1, 1, 'R');
            }

            $pdf->SetX($rightX);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(255, 180, 120); // Naranja claro (mismo que total ingresos)
            $pdf->Cell($colCodigo + $colDescripcion, 6, 'TOTAL DEDUCCIONES:', 1, 0, 'R', true);
            $pdf->Cell($colMonto, 6, '$' . number_format($data['total_deducciones'], 2), 1, 1, 'R', true);
        }

        // Guardar altura máxima alcanzada por deducciones
        $rightEndY = $pdf->GetY();

        // Posicionar después de la tabla más alta
        $maxY = max($leftEndY, $rightEndY);
        $pdf->SetY($maxY + 8);

        // === RESUMEN FINAL: SALARIO NETO ===
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(25, 118, 210); // Azul profundo (mismo que liquidaciones)
        $pdf->SetTextColor(255, 255, 255); // Texto blanco
        $pdf->Cell(0, 10, 'SALARIO NETO A PAGAR: $' . number_format($data['neto'], 2), 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

        $pdf->Ln(5);

        // Nota informativa
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 5, 'Este comprobante es un documento informativo generado por el sistema de planillas.', 0, 1, 'C');

        // Output del PDF según parámetro
        $filename = 'comprobante_' . $employee['id'] . '_' . $payroll['id'] . '_' . date('Y-m-d') . '.pdf';

        if ($output === 'S') {
            // String output para adjuntar en email
            return $pdf->Output($filename, 'S');
        } elseif ($output === 'F') {
            // File output para guardar en servidor
            return $pdf->Output($dest . $filename, 'F');
        } else {
            // Inline output para navegador (default)
            $pdf->Output($filename, 'I');
            exit;
        }
    }

    /**
     * Enviar comprobante individual por correo electrónico
     *
     * @param int $payrollId ID de la planilla
     * @param int $employeeId ID del empleado
     * @param string $emailTo Email destino (opcional, usa email del empleado si no se proporciona)
     * @return array ['success' => bool, 'message' => string]
     */
    public function enviarComprobanteEmail($payrollId, $employeeId, $emailTo = null)
    {
        try {
            $this->requireAuth();

            if (!$payrollId || !$employeeId) {
                return ['success' => false, 'message' => 'ID de planilla y empleado requeridos'];
            }

            // Obtener datos del empleado
            $employeeData = $this->getEmployeePayrollData($payrollId, $employeeId);

            if (!$employeeData) {
                return ['success' => false, 'message' => 'No se encontraron datos del empleado en esta planilla'];
            }

            $employee = $employeeData['employee'];
            $payroll = $employeeData['payroll'];

            // Determinar email destino
            $destinatario = $emailTo ?? $employee['email'] ?? null;

            if (!$destinatario) {
                return ['success' => false, 'message' => 'El empleado no tiene email registrado'];
            }

            // Validar formato de email
            if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email inválido: ' . $destinatario];
            }

            // Generar PDF como string para adjuntar
            $pdfContent = $this->generateIndividualPaySlipPDF($employeeData, 'S');

            // Obtener configuración de correo desde BD (con fallback a .env)
            $companyModel = $this->model('Company');
            $mailConfig = $companyModel->getMailConfig();

            // Configurar PHPMailer
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host       = $mailConfig['host'];
            $mail->SMTPAuth   = !empty($mailConfig['username']); // Solo autenticar si hay username
            $mail->Username   = $mailConfig['username'];
            $mail->Password   = $mailConfig['password'];
            $mail->SMTPSecure = $mailConfig['encryption'];
            $mail->Port       = $mailConfig['port'];
            $mail->CharSet    = 'UTF-8';

            // Remitente
            $mail->setFrom(
                $mailConfig['from_address'],
                $mailConfig['from_name']
            );

            // Destinatario
            $mail->addAddress($destinatario, $employee['firstname'] . ' ' . $employee['lastname']);

            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = 'Comprobante de Pago - ' . $payroll['descripcion'];

            $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_desde']));
            $fechaFin = date('d/m/Y', strtotime($payroll['fecha_hasta']));

            $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .header { background-color: #f4f4f4; padding: 20px; text-align: center; }
                        .content { padding: 20px; }
                        .footer { background-color: #f4f4f4; padding: 10px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='header'>
                        <h2>Comprobante de Pago</h2>
                    </div>
                    <div class='content'>
                        <p>Estimado/a <strong>{$employee['firstname']} {$employee['lastname']}</strong>,</p>
                        <p>Adjunto a este correo encontrará su comprobante de pago correspondiente a:</p>
                        <ul>
                            <li><strong>Planilla:</strong> {$payroll['descripcion']}</li>
                            <li><strong>Período:</strong> {$fechaInicio} al {$fechaFin}</li>
                            <li><strong>Salario Neto:</strong> \$" . number_format($employeeData['neto'], 2) . "</li>
                        </ul>
                        <p>Si tiene alguna consulta sobre este comprobante, por favor contacte al departamento de Recursos Humanos.</p>
                        <p>Saludos cordiales,<br>Departamento de Recursos Humanos</p>
                    </div>
                    <div class='footer'>
                        <p>Este es un correo automático generado por el Sistema de Planillas. Por favor no responda a este mensaje.</p>
                    </div>
                </body>
                </html>
            ";

            $mail->AltBody = "Estimado/a {$employee['firstname']} {$employee['lastname']},\n\n"
                . "Adjunto a este correo encontrará su comprobante de pago correspondiente a:\n"
                . "Planilla: {$payroll['descripcion']}\n"
                . "Período: {$fechaInicio} al {$fechaFin}\n"
                . "Salario Neto: \$" . number_format($employeeData['neto'], 2) . "\n\n"
                . "Si tiene alguna consulta sobre este comprobante, por favor contacte al departamento de Recursos Humanos.\n\n"
                . "Saludos cordiales,\nDepartamento de Recursos Humanos";

            // Adjuntar PDF
            $filename = 'comprobante_' . $employee['id'] . '_' . $payroll['id'] . '_' . date('Y-m-d') . '.pdf';
            $mail->addStringAttachment($pdfContent, $filename, 'base64', 'application/pdf');

            // Enviar email
            $mail->send();

            return [
                'success' => true,
                'message' => 'Comprobante enviado exitosamente a ' . $destinatario
            ];

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Error PHPMailer en enviarComprobanteEmail: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al enviar email: ' . $mail->ErrorInfo
            ];
        } catch (\Exception $e) {
            error_log("Error en ReportController@enviarComprobanteEmail: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar el envío: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Endpoint AJAX para enviar comprobante por email
     * Recibe: payroll_id, employee_id, email_to (opcional)
     */
    public function enviarComprobanteEmailAjax()
    {
        try {
            $this->requireAuth();

            // Verificar que sea una petición AJAX
            if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Petición inválida']);
                return;
            }

            // Obtener parámetros
            $payrollId = $_POST['payroll_id'] ?? null;
            $employeeId = $_POST['employee_id'] ?? null;
            $emailTo = $_POST['email_to'] ?? null;

            if (!$payrollId || !$employeeId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Parámetros requeridos: payroll_id, employee_id']);
                return;
            }

            // Llamar al método de envío
            $result = $this->enviarComprobanteEmail($payrollId, $employeeId, $emailTo);

            header('Content-Type: application/json');
            echo json_encode($result);

        } catch (\Exception $e) {
            error_log("Error en ReportController@enviarComprobanteEmailAjax: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generar reporte de acreedores
     */
    public function reporteAcreedores($payrollId = null)
    {
        try {
            $this->requireAuth();
            
            // Obtener datos de acreedores
            $acreedoresData = $this->getAcreedoresReportData($payrollId);
            
            if (!$acreedoresData) {
                $_SESSION['error'] = 'No hay datos de acreedores disponibles';
                $this->redirect('/panel/reports');
            }
            
            // Generar PDF de acreedores
            $this->generateAcreedoresPDF($acreedoresData);
            
        } catch (\Exception $e) {
            error_log("Error en ReportController@reporteAcreedores: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar el reporte de acreedores';
            $this->redirect('/panel/reports');
        }
    }
    
    /**
     * Obtener datos completos de la planilla para el reporte
     */
    public function getPayrollReportData($payrollId)
    {
        try {
            $reportModel = $this->model('Report');
            $db = $reportModel->getDatabase();
            $connection = $db->getConnection();
            
            // Información básica de la planilla (legacy)
            $sql = "SELECT p.*, 
                           p.fecha as fecha_inicio,
                           p.fecha as fecha_fin,
                           tp.descripcion as tipo_descripcion
                   FROM planilla_cabecera p
                   LEFT JOIN tipos_planilla tp ON p.tipo_planilla_id = tp.id
                   WHERE p.id = ?"; 
            $stmt = $connection->prepare($sql);
            $stmt->execute([$payrollId]);
            $payroll = $stmt->fetch();
            
            if (!$payroll) {
                return null;
            }
            
            // Obtener tipo de empresa primero
            $companySQL = "SELECT tipo_institucion FROM companies WHERE id = 1";
            $companyStmt = $connection->prepare($companySQL);
            $companyStmt->execute();
            $company = $companyStmt->fetch();
            $tipoEmpresa = $company['tipo_institucion'] ?? 'privada';
            
            // Empleados de la planilla con sus conceptos calculados
            // Solo incluir conceptos que estén marcados para incluir en reportes
            // Usar salario apropiado según tipo de empresa
            $sql = "SELECT
                        e.id AS employee_id,
                        e.employee_id AS employee_code,
                        e.firstname,
                        e.lastname,
                        e.document_id as cedula,
                        " . ($tipoEmpresa === 'publica' 
                            ? "COALESCE(pos.sueldo, 0) as salary" 
                            : "COALESCE(e.sueldo_individual, 0) as salary") . ",
                        pd.monto AS concepto_monto,
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
            
            // Organizar datos por empleado
            $employees = [];
            foreach ($conceptsData as $row) {
                $empKey = $row['employee_id'];
                
                if (!isset($employees[$empKey])) {
                    $employees[$empKey] = [
                        'employee_id' => $row['employee_id'],
                        'firstname' => $row['firstname'],
                        'lastname' => $row['lastname'],
                        'cedula' => $row['cedula'],
                        'salary' => $row['salary'],
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
                
                // Calcular totales usando la parametrización
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
                            // Si es 'otro' o no está categorizado, va a otras deducciones
                            $employees[$empKey]['totals']['otras_deducciones'] += $monto;
                            break;
                    }
                }
                
                // Calcular neto
                $employees[$empKey]['totals']['neto'] = 
                    $employees[$empKey]['totals']['ingresos'] - $employees[$empKey]['totals']['deducciones'];
            }
            
            return [
                'payroll' => $payroll,
                'employees' => array_values($employees)
            ];
            
        } catch (\Exception $e) {
            error_log("Error obteniendo datos del reporte: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener datos específicos de un empleado para comprobante
     */
    public function getEmployeePayrollData($payrollId, $employeeId)
    {
        try {
            $reportModel = $this->model('Report');
            $db = $reportModel->getDatabase();
            $connection = $db->getConnection();
            
            // Información de la planilla
            $sql = "SELECT p.*, tp.descripcion as tipo_descripcion
                   FROM planilla_cabecera p
                   LEFT JOIN tipos_planilla tp ON p.tipo_planilla_id = tp.id
                   WHERE p.id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$payrollId]);
            $payroll = $stmt->fetch();
            
            if (!$payroll) return null;
            
            // Obtener tipo de empresa para determinar campos a mostrar
            $companySQL = "SELECT tipo_institucion FROM companies WHERE id = 1";
            $companyStmt = $connection->prepare($companySQL);
            $companyStmt->execute();
            $company = $companyStmt->fetch();
            $tipoEmpresa = $company['tipo_institucion'] ?? 'privada';
            
            // Información del empleado con campos según tipo de empresa
            $sql = "SELECT e.*,
                           pos.codigo as position_code,
                           pos.sueldo as position_salary,
                           c.nombre as cargo_name,
                           f.nombre as funcion_name,
                           pt.nombre as partida_name
                   FROM employees e
                   LEFT JOIN posiciones pos ON e.position_id = pos.id
                   LEFT JOIN cargos c ON e.cargo_id = c.id
                   LEFT JOIN funciones f ON e.funcion_id = f.id
                   LEFT JOIN cuentas_contables pt ON e.partida_id = pt.id
                   WHERE e.id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch();
            
            if (!$employee) return null;
            
            // Agregar información de tipo de empresa y salario correcto
            $employee['tipo_empresa'] = $tipoEmpresa;
            
            if ($tipoEmpresa === 'publica') {
                $employee['salario_base'] = $employee['position_salary'];
                $employee['puesto_actual'] = $employee['position_code'];
                $employee['etiqueta_puesto'] = 'Posición';
            } else {
                $employee['salario_base'] = $employee['sueldo_individual'];
                $employee['puesto_actual'] = $employee['cargo_name'];
                $employee['etiqueta_puesto'] = 'Cargo';
            }
            
            // Conceptos del empleado en esta planilla
            $sql = "SELECT pd.*, c.concepto, c.descripcion, c.tipo_concepto, c.categoria_reporte
                   FROM planilla_detalle pd
                   INNER JOIN concepto c ON pd.concepto_id = c.id
                   WHERE pd.planilla_cabecera_id = ? AND pd.employee_id = ?
                   ORDER BY c.tipo_concepto, c.orden_reporte, c.concepto";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$payrollId, $employeeId]);
            $concepts = $stmt->fetchAll();
            
            // Organizar conceptos por tipo
            $ingresos = [];
            $deducciones = [];
            $aptronales = [];
            $totalIngresos = 0;
            $totalDeducciones = 0;
            $totalPatronales = 0;
            
            foreach ($concepts as $concept) {
                $monto = $concept['monto'] ?? 0;
                $conceptData = [
                    'codigo' => $concept['concepto'],
                    'descripcion' => $concept['descripcion'],
                    'monto' => $monto
                ];
                
                if ($concept['tipo_concepto'] == 'A') {
                    $ingresos[] = $conceptData;
                    $totalIngresos += $monto;
                } elseif ($concept['tipo_concepto'] == 'D') {
                    $deducciones[] = $conceptData;
                    $totalDeducciones += $monto;
                } elseif ($concept['tipo_concepto'] == 'P') {
                    $aptronales[] = $conceptData;
                    $totalPatronales += $monto;
                }
                
            }
            
            return [
                'payroll' => $payroll,
                'employee' => $employee,
                'ingresos' => $ingresos,
                'deducciones' => $deducciones,
                'aptronales' => $aptronales,
                'total_ingresos' => $totalIngresos,
                'total_deducciones' => $totalDeducciones,
                'total_aptronales' => $totalPatronales,
                'neto' => $totalIngresos - $totalDeducciones
            ];
            
        } catch (\Exception $e) {
            error_log("Error obteniendo datos del empleado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener datos de todos los empleados de una planilla para comprobantes
     */
    public function getAllEmployeesPayrollData($payrollId)
    {
        try {
            error_log("=== getAllEmployeesPayrollData para planilla $payrollId ===");
            
            $reportModel = $this->model('Report');
            $db = $reportModel->getDatabase();
            $connection = $db->getConnection();
            
            // Información de la planilla
            $sql = "SELECT p.*, 
                           p.fecha as fecha_inicio,
                           p.fecha as fecha_fin,
                           tp.descripcion as tipo_descripcion
                   FROM planilla_cabecera p
                   LEFT JOIN tipos_planilla tp ON p.tipo_planilla_id = tp.id
                   WHERE p.id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$payrollId]);
            $payroll = $stmt->fetch();
            
            if (!$payroll) {
                error_log("No se encontró la planilla con ID: $payrollId");
                return null;
            }
            
            error_log("Planilla encontrada: " . $payroll['descripcion']);
            
            // Obtener todos los empleados de la planilla ordenados alfabéticamente
            $sql = "SELECT DISTINCT 
                        e.id,
                        e.employee_id,
                        e.firstname,
                        e.lastname,
                        e.document_id,
                        pos.codigo as position_name,
                        pos.sueldo
                    FROM planilla_detalle pd
                    INNER JOIN employees e ON pd.employee_id = e.id
                    LEFT JOIN posiciones pos ON e.position_id = pos.id
                    WHERE pd.planilla_cabecera_id = ?
                    ORDER BY e.lastname, e.firstname";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute([$payrollId]);
            $employees = $stmt->fetchAll();
            
            error_log("Empleados básicos encontrados: " . count($employees));
            
            // Para cada empleado, obtener sus conceptos
            $employeesData = [];
            foreach ($employees as $employee) {
                error_log("Procesando empleado: " . $employee['firstname'] . ' ' . $employee['lastname']);
                
                $employeeData = $this->getEmployeePayrollData($payrollId, $employee['id']);
                if ($employeeData) {
                    $employeesData[] = $employeeData;
                    error_log("Empleado agregado: " . $employee['firstname'] . ' ' . $employee['lastname']);
                } else {
                    error_log("No se pudieron obtener datos para empleado: " . $employee['firstname'] . ' ' . $employee['lastname']);
                }
            }
            
            error_log("Total empleados con datos completos: " . count($employeesData));
            
            return [
                'payroll' => $payroll,
                'employees' => $employeesData
            ];
            
        } catch (\Exception $e) {
            error_log("Error obteniendo datos de todos los empleados: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Obtener datos de acreedores para reporte
     */
    public function getAcreedoresReportData($payrollId = null)
    {
        try {
            $reportModel = $this->model('Report');
            $db = $reportModel->getDatabase();
            $connection = $db->getConnection();
            
            // Base query para acreedores
            $whereClause = "";
            $params = [];
            
            if ($payrollId) {
                // Información de la planilla específica con campos fecha correctos
                $sql = "SELECT p.*, 
                               p.fecha as fecha_inicio,
                               p.fecha as fecha_fin,
                               tp.descripcion as tipo_descripcion
                       FROM planilla_cabecera p
                       LEFT JOIN tipos_planilla tp ON p.tipo_planilla_id = tp.id
                       WHERE p.id = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$payrollId]);
                $payrollInfo = $stmt->fetch();
                
                // Debug: verificar si la consulta devuelve datos
                if (!$payrollInfo) {
                    error_log("No se encontró información de planilla para ID: $payrollId");
                } else {
                    error_log("Planilla encontrada: " . json_encode($payrollInfo));
                }
                
                // Para planilla específica, obtener solo deducciones de esa planilla
                $whereClause = "AND pd.planilla_cabecera_id = ?";
                $params[] = $payrollId;
            } else {
                $payrollInfo = null;
            }
            
            // Simplificar la consulta - obtener directamente datos de acreedores y deducciones
            error_log("Obteniendo datos de acreedores con whereClause: $whereClause");
            
            if ($payrollId) {
                // Para planilla específica, obtener acreedores que tienen empleados en esa planilla
                $sql = "SELECT 
                            cr.id as acreedor_id,
                            cr.description as acreedor_nombre,
                            cr.creditor_id as acreedor_codigo,
                            'N/A' as acreedor_ruc,
                            'N/A' as acreedor_telefono,
                            'N/A' as acreedor_direccion,
                            'DEDUCCION' as concepto,
                            cr.description as concepto_descripcion,
                            'otras_deducciones' as categoria_reporte,
                            SUM(d.amount) as total_monto,
                            COUNT(DISTINCT d.employee_id) as total_empleados,
                            GROUP_CONCAT(DISTINCT CONCAT(e.firstname, ' ', e.lastname) SEPARATOR ', ') as empleados_nombres
                        FROM creditors cr
                        INNER JOIN deductions d ON cr.id = d.creditor_id
                        INNER JOIN employees e ON d.employee_id = e.employee_id
                        WHERE EXISTS (
                            SELECT 1 FROM planilla_detalle pd 
                            WHERE pd.employee_id = e.id 
                            AND pd.planilla_cabecera_id = ?
                        )
                        GROUP BY cr.id
                        ORDER BY cr.description";
                
                $stmt = $connection->prepare($sql);
                $stmt->execute([$payrollId]);
                $acreedoresData = $stmt->fetchAll();
            } else {
                // Para reporte general, obtener todos los acreedores
                $sql = "SELECT 
                            cr.id as acreedor_id,
                            cr.description as acreedor_nombre,
                            cr.creditor_id as acreedor_codigo,
                            'N/A' as acreedor_ruc,
                            'N/A' as acreedor_telefono,
                            'N/A' as acreedor_direccion,
                            'DEDUCCION' as concepto,
                            cr.description as concepto_descripcion,
                            'otras_deducciones' as categoria_reporte,
                            SUM(d.amount) as total_monto,
                            COUNT(DISTINCT d.employee_id) as total_empleados,
                            GROUP_CONCAT(DISTINCT CONCAT(e.firstname, ' ', e.lastname) SEPARATOR ', ') as empleados_nombres
                        FROM creditors cr
                        INNER JOIN deductions d ON cr.id = d.creditor_id
                        INNER JOIN employees e ON d.employee_id = e.employee_id
                        GROUP BY cr.id
                        ORDER BY cr.description";
                
                $stmt = $connection->prepare($sql);
                $stmt->execute();
                $acreedoresData = $stmt->fetchAll();
            }
            
            error_log("Datos de acreedores encontrados: " . count($acreedoresData));
            
            return [
                'payroll' => $payrollInfo,
                'acreedores' => $acreedoresData,
                'fecha_generacion' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            error_log("Error obteniendo datos de acreedores: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generar PDF del reporte de planilla
     */
    public function generatePayrollPDF($data)
    {

        // Crear una clase personalizada para manejar header/footer
        $pdf = new class('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false) extends TCPDF {
            public $payrollData;
            
            public function Header() {
                if (!$this->payrollData) return;
                
                // Logo o título principal
                $this->SetFont('helvetica', 'B', 14);
                $this->Cell(0, 8, 'REPORTE DE PLANILLA', 0, 1, 'C');
                
                $this->Ln(2);
                
                // Información de la planilla
                $this->SetFont('helvetica', 'B', 11);
                $this->Cell(0, 6, $this->payrollData['descripcion'], 0, 1, 'C');
                
                $this->SetFont('helvetica', '', 9);
                $fechaInicio = date('d/m/Y', strtotime($this->payrollData['fecha_inicio']));
                $fechaFin = date('d/m/Y', strtotime($this->payrollData['fecha_fin']));
                $this->Cell(0, 5, 'Período: ' . $fechaInicio . ' al ' . $fechaFin, 0, 1, 'C');
                
                $tipo = $this->payrollData['tipo_descripcion'] ?? 'N/A';
                $this->Cell(0, 5, 'Tipo: ' . $tipo . ' | Generado: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
                
                $this->Ln(3);
                
                // Headers de la tabla
                $this->SetFont('helvetica', 'B', 8);
                $this->SetFillColor(200, 200, 200);
                
                // Ajustar anchos para orientación horizontal (297mm disponible aprox)
                $headers = [
                    ['Nombre', 50],
                    ['Cédula', 20], 
                    ['Sueldo', 20],
                    ['T.Ingresos', 20],
                    ['T.Deducciones', 30],
                    ['S.Social', 20],
                    ['S.Educativo', 20],
                    ['Imp.Renta', 20],
                    ['Otras Ded.', 20],
                    ['Neto', 20]
                ];
                
                foreach ($headers as $header) {
                    $this->Cell($header[1], 8, $header[0], 1, 0, 'C', true);
                }
                $this->Ln();
                
                // Espacio adicional después de los headers para evitar superposición
                $this->Ln(2);
            }
            
            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
            }
        };
        
        // Información del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor('Sistema de Planillas');
        $pdf->SetTitle('Reporte de Planilla - ' . $data['payroll']['descripcion']);
        $pdf->SetSubject('Reporte de Planilla');
        
        // Configurar página para landscape
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(25, 42, 10); // Margen superior mayor para evitar superposición
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 25);
        
        // Asignar datos para el header
        $pdf->payrollData = $data['payroll'];
        
        // Agregar página
        $pdf->AddPage();
        
        // Configurar fuente
        $pdf->SetFont('helvetica', '', 8);
        
        // Tabla de empleados (el header se maneja automáticamente)
        $this->addEmployeeTable($pdf, $data['employees']);
        
        // Totales generales
        $this->addGeneralTotals($pdf, $data['employees']);
        
        // Output del PDF
        $filename = 'planilla_' . $data['payroll']['id'] . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I'); // I = inline browser, D = download
        exit;
    }
    
    
    /**
     * Agregar tabla de empleados
     */
    public function addEmployeeTable($pdf, $employees)
    {
        // Anchos de columna para orienación horizontal
        $colWidths = [ 50, 20, 20, 20, 30, 20, 20, 20, 20, 20];
        
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
            $pdf->Cell($colWidths[1], 6, $emp['cedula'] ?? 'N/A', 1, 0, 'C');
            $pdf->Cell($colWidths[2], 6, '$ ' . number_format($emp['salary'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[3], 6, '$ ' . number_format($emp['totals']['ingresos'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[4], 6, '$ ' . number_format($emp['totals']['deducciones'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[5], 6, '$ ' . number_format($emp['totals']['seguro_social'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[6], 6, '$ ' . number_format($emp['totals']['seguro_educativo'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[7], 6, '$ ' . number_format($emp['totals']['impuesto_renta'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[8], 6, '$ ' . number_format($emp['totals']['otras_deducciones'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[9], 6, '$ ' . number_format($emp['totals']['neto'], 2), 1, 0, 'R');
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
        $pdf->Cell($colWidths[2], 7, '$ ' . number_format($totalGeneral['sueldo'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[3], 7, '$ ' . number_format($totalGeneral['ingresos'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[4], 7, '$ ' . number_format($totalGeneral['deducciones'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[5], 7, '$ ' . number_format($totalGeneral['seguro_social'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[6], 7, '$ ' . number_format($totalGeneral['seguro_educativo'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[7], 7, '$ ' . number_format($totalGeneral['impuesto_renta'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[8], 7, '$ ' . number_format($totalGeneral['otras_deducciones'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[9], 7, '$ ' . number_format($totalGeneral['neto'], 2), 1, 0, 'R', true);
        $pdf->Ln();
    }
    
    /**
     * Agregar resumen de totales generales
     */
    public function addGeneralTotals($pdf, $employees)
    {
        $pdf->Ln(10);
        
        // Estadísticas generales
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'RESUMEN EJECUTIVO', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Total de Empleados: ' . count($employees), 0, 1, 'L');
        
        $totalIngresos = array_sum(array_column(array_column($employees, 'totals'), 'ingresos'));
        $totalDeducciones = array_sum(array_column(array_column($employees, 'totals'), 'deducciones'));
        $totalNeto = $totalIngresos - $totalDeducciones;
        
        $pdf->Cell(0, 6, 'Total de Ingresos: ' . $this->formatCurrency($totalIngresos), 0, 1, 'L');
        $pdf->Cell(0, 6, 'Total de Deducciones: ' . $this->formatCurrency($totalDeducciones), 0, 1, 'L');
        $pdf->Cell(0, 6, 'Neto a Pagar: ' . $this->formatCurrency($totalNeto), 0, 1, 'L');
        
        $promedioNeto = count($employees) > 0 ? $totalNeto / count($employees) : 0;
        $pdf->Cell(0, 6, 'Promedio Neto por Empleado: ' . $this->formatCurrency($promedioNeto), 0, 1, 'L');
    }

    /**
     * Generar PDF del comprobante de pago individual
     */
    public function generatePaySlipPDF($data)
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
        
        $pdf->Cell(50, 6, $employee['etiqueta_puesto'] . ':', 0, 0, 'L');
        $pdf->Cell(0, 6, $employee['puesto_actual'] ?? 'N/A', 0, 1, 'L');
        
        // Mostrar campos adicionales para empresas privadas si están disponibles
        if ($employee['tipo_empresa'] === 'privada') {
            if (!empty($employee['funcion_name'])) {
                $pdf->Cell(50, 6, 'Función:', 0, 0, 'L');
                $pdf->Cell(0, 6, $employee['funcion_name'], 0, 1, 'L');
            }
            if (!empty($employee['partida_name'])) {
                $pdf->Cell(50, 6, 'Partida:', 0, 0, 'L');
                $pdf->Cell(0, 6, $employee['partida_name'], 0, 1, 'L');
            }
        }
        
        $pdf->Cell(50, 6, 'Sueldo Base:', 0, 0, 'L');
        $pdf->Cell(0, 6, $this->formatCurrency($employee['salario_base']), 0, 1, 'L');
        
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
                $pdf->Cell(30, 5, $this->formatCurrency($ingreso['monto']), 1, 1, 'R');
            }
            
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(130, 6, 'TOTAL INGRESOS:', 1, 0, 'R', true);
            $pdf->Cell(30, 6, $this->formatCurrency($data['total_ingresos']), 1, 1, 'R', true);
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
                $pdf->Cell(30, 5, $this->formatCurrency($deduccion['monto']), 1, 1, 'R');
            }
            
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(130, 6, 'TOTAL DEDUCCIONES:', 1, 0, 'R', true);
            $pdf->Cell(30, 6, $this->formatCurrency($data['total_deducciones']), 1, 1, 'R', true);
        }
        
        $pdf->Ln(8);
        
        // Resumen final
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(220, 220, 255);
        $pdf->Cell(130, 10, 'NETO A PAGAR:', 1, 0, 'R', true);
        $pdf->Cell(30, 10, $this->formatCurrency($data['neto']), 1, 1, 'R', true);
        
        $pdf->Ln(10);
        
        // Footer
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 5, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        
        // Output
        $filename = 'comprobante_' . $employee['employee_id'] . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
    }

    /**
     * Generar PDF con todos los comprobantes de pago de una planilla
     * Ahora genera 2 comprobantes por página: empleado y empleador
     */
    public function generateAllPaySlipsPDF($data)
    {
        $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuración del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor('Sistema de Planillas');
        $pdf->SetTitle('Comprobantes de Pago - ' . $data['payroll']['descripcion']);
        
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(FALSE); // Deshabilitamos para control manual
        
        $payroll = $data['payroll'];
        $empleados = $data['employees'];
        
        // Obtener datos de la empresa para firmas
        $companySignatures = $this->companyModel->getSignaturesForReports();
        
        foreach ($empleados as $index => $employeeData) {
            // Nueva página para cada empleado (2 comprobantes)
            $pdf->AddPage();
            
            $employee = $employeeData['employee'];
            
            // ======== COMPROBANTE DEL EMPLEADO (Parte Superior) ========
            $this->generateSingleVoucher($pdf, $payroll, $employee, $employeeData, 'EMPLEADO', 15, $companySignatures);
            
            // Línea divisoria
            $pdf->SetY(148);
            $pdf->SetLineWidth(0.5);
            $pdf->Line(15, 148, 195, 148);
            
            // Texto "TIJERA" o línea punteada
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->SetY(145);
            $pdf->Cell(0, 5, ' - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -', 0, 1, 'C');
            
            // ======== COMPROBANTE DEL EMPLEADOR (Parte Inferior) ========
            $this->generateSingleVoucher($pdf, $payroll, $employee, $employeeData, 'EMPLEADOR', 155, $companySignatures);
        }
        
        // Output
        $filename = 'comprobantes_planilla_' . $payroll['id'] . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
    }

    /**
     * Generar un comprobante individual en una posición específica de la página
     * @param TCPDF $pdf - Objeto PDF
     * @param array $payroll - Datos de la planilla
     * @param array $employee - Datos del empleado
     * @param array $employeeData - Datos completos del empleado con conceptos
     * @param string $type - 'EMPLEADO' o 'EMPLEADOR'
     * @param int $startY - Posición Y donde empezar el comprobante
     * @param array $signatures - Firmas de la empresa
     */
    public function generateSingleVoucher($pdf, $payroll, $employee, $employeeData, $type, $startY, $signatures)
    {
        // Establecer posición inicial
        $pdf->SetY($startY);
        $currentY = $startY;
        
        // Header del comprobante con tipo
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'COMPROBANTE DE PAGO - ' . $type, 0, 1, 'C');
        $currentY += 6;
        
        $pdf->SetY($currentY);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, $payroll['descripcion'], 0, 1, 'C');
        $currentY += 5;
        
        $pdf->SetY($currentY);
        $pdf->SetFont('helvetica', '', 8);
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_desde']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_hasta']));
        $pdf->Cell(0, 4, 'Período: ' . $fechaInicio . ' al ' . $fechaFin, 0, 1, 'C');
        $currentY += 6;
        
        // Información del empleado (compacta)
        $pdf->SetY($currentY);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 5, 'DATOS DEL EMPLEADO', 0, 1, 'L', true);
        $currentY += 5;
        
        $pdf->SetY($currentY);
        $pdf->SetFont('helvetica', '', 8);
        
        // Información en dos columnas
        $pdf->Cell(15, 3, 'Nombre:', 0, 0, 'L');
        $pdf->Cell(80, 3, $employee['firstname'] . ' ' . $employee['lastname'], 0, 0, 'L');
        $pdf->Cell(15, 3, 'Cédula:', 0, 0, 'L');
        $pdf->Cell(70, 3, $employee['document_id'] ?? 'N/A', 0, 0, 'L');
        $currentY += 4;
        
        $pdf->SetY($currentY);
        $pdf->Cell(15, 3, $employee['etiqueta_puesto'] . ':', 0, 0, 'L');
        $pdf->Cell(80, 3, $employee['puesto_actual'] ?? 'N/A', 0, 0, 'L');
        $pdf->Cell(15, 3, 'Código:', 0, 0, 'L');
        $pdf->Cell(70, 3, $employee['employee_id'] ?? 'N/A', 0, 1, 'L');
        $currentY += 5;
        
        // Conceptos en formato tabla compacta
        $pdf->SetY($currentY);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(220, 220, 220);
        
        // Headers
        $pdf->Cell(70, 4, 'INGRESOS', 1, 0, 'C', true);
        $pdf->Cell(20, 4, 'Monto', 1, 0, 'C', true);
        $pdf->Cell(70, 4, 'DEDUCCIONES', 1, 0, 'C', true);
        $pdf->Cell(20, 4, 'Monto', 1, 1, 'C', true);
        $currentY += 4;
        
        // Preparar datos para mostrar en paralelo
        $ingresos = $employeeData['ingresos'] ?? [];
        $deducciones = $employeeData['deducciones'] ?? [];
        $maxRows = max(count($ingresos), count($deducciones), 1);
        
        $pdf->SetFont('helvetica', '', 7);
        
        for ($i = 0; $i < $maxRows && $currentY < 300; $i++) { // Límite de altura
            $pdf->SetY($currentY);
            
            // Ingreso
            if (isset($ingresos[$i])) {
                $ing = $ingresos[$i];
                $descripcion = strlen($ing['descripcion']) > 22 ? substr($ing['descripcion'], 0, 19) . '...' : $ing['descripcion'];
                $pdf->Cell(70, 4, $descripcion, 1, 0, 'L');
                $pdf->Cell(20, 4, $this->formatCurrency($ing['monto']), 1, 0, 'R');
            } else {
                $pdf->Cell(70, 4, '', 1, 0, 'L');
                $pdf->Cell(20, 4, '', 1, 0, 'R');
            }
            
            // Deducción
            if (isset($deducciones[$i])) {
                $ded = $deducciones[$i];
                $descripcion = strlen($ded['descripcion']) > 22 ? substr($ded['descripcion'], 0, 19) . '...' : $ded['descripcion'];
                $pdf->Cell(70, 4, $descripcion, 1, 0, 'L');
                $pdf->Cell(20, 4, $this->formatCurrency($ded['monto']), 1, 1, 'R');
            } else {
                $pdf->Cell(70, 4, '', 1, 0, 'L');
                $pdf->Cell(20, 4, '', 1, 1, 'R');
            }
            
            $currentY += 4;
        }
        
        // Totales
        $pdf->SetY($currentY);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(70, 4, 'TOTAL INGRESOS:', 1, 0, 'R', true);
        $pdf->Cell(20, 4, $this->formatCurrency($employeeData['total_ingresos']), 1, 0, 'R', true);
        $pdf->Cell(70, 4, 'TOTAL DEDUCCIONES:', 1, 0, 'R', true);
        $pdf->Cell(20, 4, $this->formatCurrency($employeeData['total_deducciones']), 1, 1, 'R', true);
        $currentY += 4;
        
        // Neto a pagar
        $pdf->SetY($currentY);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(220, 220, 255);
        $pdf->Cell(140, 6, 'NETO A PAGAR:', 1, 0, 'R', true);
        $pdf->Cell(40, 6, $this->formatCurrency($employeeData['neto']), 1, 1, 'R', true);
        $currentY += 15;
        
        // Firmas (solo para comprobante del empleador)
        //if ($type === 'EMPLEADOR') {
            $pdf->SetY($currentY);
            $pdf->SetFont('helvetica', '', 7);
            
            // Firmas en dos columnas
            $pdf->Cell(90, 3, '', 0, 0, 'C'); // Espacio para firma
            $pdf->Cell(90, 3, '', 0, 1, 'C'); // Espacio para firma
            $pdf->Cell(90, 3, '____________________', 0, 0, 'C');
            $pdf->Cell(90, 3, '____________________', 0, 1, 'C');
            $pdf->Cell(90, 3, $signatures['elaborado_por'] ?: 'Por definir', 0, 0, 'C');
            $pdf->Cell(90, 3, $signatures['jefe_recursos_humanos'] ?: 'Por definir', 0, 1, 'C');
            $pdf->Cell(90, 3, $signatures['cargo_elaborador'] ?: 'Especialista en Nóminas', 0, 0, 'C');
            $pdf->Cell(90, 3, $signatures['cargo_jefe_rrhh'] ?: 'Jefe de Recursos Humanos', 0, 1, 'C');
        //}
        
        // Footer con fecha
        $pdf->SetY($currentY + 60);
        $pdf->SetFont('helvetica', 'I', 6);
        $pdf->Cell(0, 3, 'Generado: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
    }

    /**
     * Generar PDF del reporte de acreedores
     */
    public function generateAcreedoresPDF($data)
    {
        $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuración del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor('Sistema de Planillas');
        
        if ($data['payroll']) {
            $pdf->SetTitle('Reporte de Acreedores - ' . $data['payroll']['descripcion']);
        } else {
            $pdf->SetTitle('Reporte General de Acreedores');
        }
        
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'REPORTE DE ACREEDORES', 0, 1, 'C');
        
        if ($data['payroll'] && isset($data['payroll']['descripcion'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, $data['payroll']['descripcion'], 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 10);
            
            // Validar fechas antes de usarlas
            if (isset($data['payroll']['fecha_inicio']) && $data['payroll']['fecha_inicio']) {
                $fechaInicio = date('d/m/Y', strtotime($data['payroll']['fecha_inicio']));
            } else {
                $fechaInicio = 'N/A';
            }
            
            if (isset($data['payroll']['fecha_fin']) && $data['payroll']['fecha_fin']) {
                $fechaFin = date('d/m/Y', strtotime($data['payroll']['fecha_fin']));
            } else {
                $fechaFin = 'N/A';
            }
            
            $pdf->Cell(0, 6, 'Período: ' . $fechaInicio . ' al ' . $fechaFin, 0, 1, 'C');
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, 'Reporte General de Todos los Acreedores', 0, 1, 'C');
        }
        
        $pdf->Cell(0, 6, 'Generado: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(10);
        
        if (empty($data['acreedores'])) {
            $pdf->SetFont('helvetica', 'I', 12);
            $pdf->Cell(0, 10, 'No hay datos de acreedores disponibles', 0, 1, 'C');
        } else {
            // Agrupar por acreedor
            $acreedoresByName = [];
            $totalGeneral = 0;
            
            foreach ($data['acreedores'] as $item) {
                $nombre = $item['acreedor_nombre'];
                if (!isset($acreedoresByName[$nombre])) {
                    $acreedoresByName[$nombre] = [
                        'info' => $item,
                        'conceptos' => [],
                        'total' => 0
                    ];
                }
                
                $acreedoresByName[$nombre]['conceptos'][] = $item;
                $acreedoresByName[$nombre]['total'] += $item['total_monto'];
                $totalGeneral += $item['total_monto'];
            }
            
            // Generar reporte por acreedor
            foreach ($acreedoresByName as $nombreAcreedor => $acreedor) {
                $info = $acreedor['info'];
                
                // Información del acreedor
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->SetFillColor(230, 230, 230);
                $pdf->Cell(0, 8, strtoupper($nombreAcreedor), 0, 1, 'L', true);
                
                $pdf->SetFont('helvetica', '', 9);
                if ($info['acreedor_codigo'] && $info['acreedor_codigo'] != 'N/A') {
                    $pdf->Cell(40, 5, 'Código:', 0, 0, 'L');
                    $pdf->Cell(60, 5, $info['acreedor_codigo'], 0, 0, 'L');
                }
                if ($info['acreedor_ruc'] && $info['acreedor_ruc'] != 'N/A') {
                    $pdf->Cell(40, 5, 'RUC:', 0, 0, 'L');
                    $pdf->Cell(0, 5, $info['acreedor_ruc'], 0, 1, 'L');
                } else {
                    $pdf->Ln();
                }
                
                if ($info['acreedor_telefono'] && $info['acreedor_telefono'] != 'N/A') {
                    $pdf->Cell(40, 5, 'Teléfono:', 0, 0, 'L');
                    $pdf->Cell(0, 5, $info['acreedor_telefono'], 0, 1, 'L');
                }
                
                if ($info['acreedor_direccion'] && $info['acreedor_direccion'] != 'N/A') {
                    $pdf->Cell(40, 5, 'Dirección:', 0, 0, 'L');
                    $pdf->Cell(0, 5, $info['acreedor_direccion'], 0, 1, 'L');
                }
                
                $pdf->Ln(3);
                
                // Tabla de conceptos
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(25, 6, 'Código', 1, 0, 'C', true);
                $pdf->Cell(80, 6, 'Concepto', 1, 0, 'C', true);
                $pdf->Cell(30, 6, 'Empleados', 1, 0, 'C', true);
                $pdf->Cell(30, 6, 'Total', 1, 1, 'C', true);
                
                $pdf->SetFont('helvetica', '', 8);
                $totalAcreedor = 0;
                
                foreach ($acreedor['conceptos'] as $concepto) {
                    $pdf->Cell(25, 5, $concepto['concepto'], 1, 0, 'C');
                    $pdf->Cell(80, 5, substr($concepto['concepto_descripcion'], 0, 35), 1, 0, 'L');
                    $pdf->Cell(30, 5, $concepto['total_empleados'], 1, 0, 'C');
                    $pdf->Cell(30, 5, $this->formatCurrency($concepto['total_monto']), 1, 1, 'R');
                    $totalAcreedor += $concepto['total_monto'];
                }
                
                // Total del acreedor
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell(135, 6, 'TOTAL ' . strtoupper($nombreAcreedor) . ':', 1, 0, 'R', true);
                $pdf->Cell(30, 6, $this->formatCurrency($totalAcreedor), 1, 1, 'R', true);
                
                $pdf->Ln(8);
            }
            
            // Resumen general
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetFillColor(220, 220, 255);
            $pdf->Cell(135, 10, 'TOTAL GENERAL A PAGAR:', 1, 0, 'R', true);
            $pdf->Cell(30, 10, $this->formatCurrency($totalGeneral), 1, 1, 'R', true);
            
            $pdf->Ln(5);
            
            // Estadísticas
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'ESTADÍSTICAS:', 0, 1, 'L');
            
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 5, 'Total de Acreedores: ' . count($acreedoresByName), 0, 1, 'L');
            $pdf->Cell(0, 5, 'Total de Conceptos: ' . count($data['acreedores']), 0, 1, 'L');
            
            $totalEmpleados = array_sum(array_column($data['acreedores'], 'total_empleados'));
            $pdf->Cell(0, 5, 'Total de Empleados Afectados: ' . $totalEmpleados, 0, 1, 'L');
        }
        
        // Output
        $filename = 'reporte_acreedores_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
    }
    
    /**
     * Exportar empleados a CSV
     */
    public function exportEmployees()
    {
        try {
            $this->requireAuth();
            
            $employeeModel = $this->model('Employee');
            $employees = $employeeModel->getAllWithDetails();

            $filename = 'empleados_' . date('Y-m-d_H-i-s') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $output = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($output, [
                'ID', 'Código Empleado', 'Nombres', 'Apellidos', 'Documento ID', 'Contacto', 
                'Dirección', 'Posición', 'Cargo', 'Horario', 'Fecha Nacimiento', 'Género',
                'Fecha Ingreso', 'Estado'
            ]);

            // Data
            foreach ($employees as $employee) {
                fputcsv($output, [
                    $employee['id'],
                    $employee['employee_id'],
                    $employee['firstname'],
                    $employee['lastname'],
                    $employee['document_id'] ?? 'N/A',
                    $employee['contact_info'] ?? 'N/A',
                    $employee['address'] ?? 'N/A',
                    $employee['position_name'] ?? 'N/A',
                    $employee['cargo_name'] ?? 'N/A',
                    ($employee['time_in'] ?? '') . ' - ' . ($employee['time_out'] ?? ''),
                    $employee['birthdate'] ?? 'N/A',
                    $employee['gender'] ?? 'N/A',
                    $employee['created_on'] ?? 'N/A',
                    isset($employee['active']) && $employee['active'] ? 'Activo' : 'Inactivo'
                ]);
            }

            fclose($output);
            exit;

        } catch (\Exception $e) {
            error_log("Error exportando empleados: " . $e->getMessage());
            $_SESSION['error'] = 'Error al exportar empleados';
            $this->redirect('/panel/reports/exports');
        }
    }

    /**
     * Exportar acreedores a CSV
     */
    public function exportCreditors()
    {
        try {
            $this->requireAuth();
            
            $creditorModel = $this->model('Creditor');
            $creditors = $creditorModel->all();

            $filename = 'acreedores_' . date('Y-m-d_H-i-s') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $output = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($output, [
                'ID', 'Descripción', 'Monto', 'Código Acreedor', 'Employee ID', 'Tipo', 'Estado', 'Observaciones'
            ]);

            // Data
            foreach ($creditors as $creditor) {
                fputcsv($output, [
                    $creditor['id'],
                    $creditor['description'] ?? 'N/A',
                    $creditor['amount'] ?? '0',
                    $creditor['creditor_id'] ?? 'N/A',
                    $creditor['employee_id'] ?? 'N/A',
                    $creditor['tipo'] ?? 'N/A',
                    isset($creditor['activo']) && $creditor['activo'] ? 'Activo' : 'Inactivo',
                    $creditor['observaciones'] ?? 'N/A'
                ]);
            }

            fclose($output);
            exit;

        } catch (\Exception $e) {
            error_log("Error exportando acreedores: " . $e->getMessage());
            $_SESSION['error'] = 'Error al exportar acreedores';
            $this->redirect('/panel/reports/exports');
        }
    }

    /**
     * Exportar conceptos a CSV
     */
    public function exportConcepts()
    {
        try {
            $this->requireAuth();
            
            $conceptModel = $this->model('Concept');
            $concepts = $conceptModel->all();

            $filename = 'conceptos_' . date('Y-m-d_H-i-s') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $output = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($output, [
                'ID', 'Concepto', 'Descripción', 'Cuenta Contable', 'Tipo Concepto', 'Unidad', 'Fórmula', 'Valor Fijo',
                'Imprime Detalles', 'Prorratea', 'Modifica Valor', 'Valor Referencia', 
                'Monto Cálculo', 'Monto Cero', 'Incluir Reporte', 'Categoría Reporte', 'Orden Reporte'
            ]);

            // Data
            foreach ($concepts as $concept) {
                fputcsv($output, [
                    $concept['id'],
                    $concept['concepto'] ?? 'N/A',
                    $concept['descripcion'] ?? 'N/A',
                    $concept['cuenta_contable'] ?? 'N/A',
                    $concept['tipo_concepto'] ?? 'N/A',
                    $concept['unidad'] ?? 'N/A',
                    $concept['formula'] ?? 'N/A',
                    $concept['valor_fijo'] ?? 'N/A',
                    isset($concept['imprime_detalles']) && $concept['imprime_detalles'] ? 'Sí' : 'No',
                    isset($concept['prorratea']) && $concept['prorratea'] ? 'Sí' : 'No',
                    isset($concept['modifica_valor']) && $concept['modifica_valor'] ? 'Sí' : 'No',
                    isset($concept['valor_referencia']) && $concept['valor_referencia'] ? 'Sí' : 'No',
                    isset($concept['monto_calculo']) && $concept['monto_calculo'] ? 'Sí' : 'No',
                    isset($concept['monto_cero']) && $concept['monto_cero'] ? 'Sí' : 'No',
                    isset($concept['incluir_reporte']) && $concept['incluir_reporte'] ? 'Sí' : 'No',
                    $concept['categoria_reporte'] ?? 'N/A',
                    $concept['orden_reporte'] ?? 0
                ]);
            }

            fclose($output);
            exit;

        } catch (\Exception $e) {
            error_log("Error exportando conceptos: " . $e->getMessage());
            $_SESSION['error'] = 'Error al exportar conceptos';
            $this->redirect('/panel/reports/exports');
        }
    }

    /**
     * Generar reporte de planilla en Excel para Panamá
     */
    public function planillaExcelPanama($payrollId)
    {
        // Delegar al controlador especializado de Excel
        $excelController = new \App\Controllers\ExcelReportController();
        $excelController->generatePayrollExcel($payrollId);
    }

    /**
     * Generar Informe 03 - Reporte gubernamental de planillas
     */
    public function informe03($payrollId = null)
    {
        // Delegar al controlador especializado de Excel
        $excelController = new \App\Controllers\ExcelReportController();
        $excelController->generateInforme03($payrollId);
    }

    /**
     * Generar Asientos Contables - Asiento de Planilla y Cuota Patronal
     * Con información bancaria de empleados (Excel con 3 hojas)
     */
    public function asientosContables($payrollId)
    {
        // Delegar al generador especializado de asientos contables Excel
        $contableGenerator = new \App\Controllers\PlanillaContableExcelGenerator();
        $contableGenerator->generateContableExcel($payrollId);
    }

    /**
     * Generar PDF de Asientos Contables - Asiento de Planilla y Cuota Patronal
     * Delega a clase especializada para mantener ReportController limpio
     */
    public function asientosContablesPdf($payrollId)
    {
        // Delegar al generador especializado de asientos contables PDF
        $pdfGenerator = new \App\Controllers\AsientosContablesPDFGenerator();
        $pdfGenerator->generateAsientosContablesPDF($payrollId);
    }

    /**
     * Generar Excel de Asientos Contables - Asiento de Planilla y Cuota Patronal
     * Delega a clase especializada para mantener ReportController limpio
     */
    public function asientosContablesExcel($payrollId)
    {
        // Delegar al generador especializado de asientos contables Excel
        $excelGenerator = new \App\Controllers\AsientosContablesExcelGenerator();
        $excelGenerator->generateAsientosContablesExcel($payrollId);
    }

    /**
     * Generar Excel profesional para Panamá con múltiples hojas
     */
    public function generateExcelPanama($planillaData, $companyInfo, $signatures)
    {
        $payroll = $planillaData['payroll'];
        $employees = $planillaData['employees'];
        
        // Configurar headers para Excel
        $filename = 'Planilla_Panama_' . $payroll['id'] . '_' . date('Y-m-d') . '.xls';
        
        // Limpiar buffer de salida previo
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . str_replace('.xls', '.xlsx', $filename) . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        
        // Iniciar buffer de salida
        ob_start();
        
        // Generar contenido Excel usando HTML/XML que Excel puede interpretar
        $content = $this->generateCustomExcelContent($payroll, $employees, $companyInfo, $signatures);
        
        // Validar XML en modo debug
        if (getenv('APP_ENV') === 'development') {
            if (!$this->validateExcelXML($content)) {
                error_log("Warning: El XML generado para Excel puede tener problemas de formato");
            }
        }
        
        // Limpiar buffer
        ob_end_clean();
        
        // Output del contenido
        echo $content;
        exit;
    }

    /**
     * Generar contenido XML/HTML para Excel según plantilla específica
     */
    public function generateExcelXMLContent($payroll, $employees, $companyInfo, $signatures)
    {
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_inicio']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_fin']));
        $currencySymbol = $this->getCurrencySymbol();
        
        // Calcular totales generales
        $totales = $this->calculatePanamaPayrollTotals($employees);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        // Estilos
        $xml .= $this->getExcelStyles();
        
        // HOJA 1: RESUMEN EJECUTIVO
        $xml .= '<Worksheet ss:Name="Resumen Ejecutivo">' . "\n";
        $xml .= '<Table>' . "\n";
        
        // Header de la empresa
        $xml .= '<Row ss:StyleID="HeaderCompany">' . "\n";
        $xml .= '<Cell ss:MergeAcross="6"><Data ss:Type="String">' . $this->escapeXmlData($companyInfo['company_name']) . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row ss:StyleID="SubHeader">' . "\n";
        $xml .= '<Cell ss:MergeAcross="6"><Data ss:Type="String">PLANILLA DE SUELDOS - REPÚBLICA DE PANAMÁ</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row ss:StyleID="SubHeader">' . "\n";
        $xml .= '<Cell ss:MergeAcross="6"><Data ss:Type="String">' . $this->escapeXmlData($payroll['descripcion']) . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row ss:StyleID="Info">' . "\n";
        $xml .= '<Cell ss:MergeAcross="6"><Data ss:Type="String">Período: ' . $fechaInicio . ' al ' . $fechaFin . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row><Cell></Cell></Row>' . "\n"; // Fila vacía
        
        // Resumen de totales por categoría
        $xml .= '<Row ss:StyleID="TableHeader">' . "\n";
        $xml .= '<Cell><Data ss:Type="String">CONCEPTO</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">EMPLEADOS</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">TOTAL BALBOAS</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">PROMEDIO</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">% DEL TOTAL</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        // Datos del resumen
        foreach ($totales['resumen_conceptos'] as $concepto => $data) {
            $xml .= '<Row ss:StyleID="TableData">' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($concepto) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . $data['empleados'] . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . number_format($data['total'], 2) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . number_format($data['promedio'], 2) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . number_format($data['porcentaje'], 1) . '%</Data></Cell>' . "\n";
            $xml .= '</Row>' . "\n";
        }
        
        // Totales generales
        $xml .= '<Row><Cell></Cell></Row>' . "\n";
        $xml .= '<Row ss:StyleID="TableTotal">' . "\n";
        $xml .= '<Cell><Data ss:Type="String">TOTAL INGRESOS:</Data></Cell>' . "\n";
        $xml .= '<Cell ss:MergeAcross="1"><Data ss:Type="String">' . $currencySymbol . ' ' . number_format($totales['total_ingresos'], 2) . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row ss:StyleID="TableTotal">' . "\n";
        $xml .= '<Cell><Data ss:Type="String">TOTAL DEDUCCIONES:</Data></Cell>' . "\n";
        $xml .= '<Cell ss:MergeAcross="1"><Data ss:Type="String">' . $currencySymbol . ' ' . number_format($totales['total_deducciones'], 2) . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row ss:StyleID="TableTotal">' . "\n";
        $xml .= '<Cell><Data ss:Type="String">NETO A PAGAR:</Data></Cell>' . "\n";
        $xml .= '<Cell ss:MergeAcross="1"><Data ss:Type="String">' . $currencySymbol . ' ' . number_format($totales['neto_total'], 2) . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        
        // HOJA 2: DETALLE POR EMPLEADO
        $xml .= '<Worksheet ss:Name="Detalle por Empleado">' . "\n";
        $xml .= '<Table>' . "\n";
        
        // Headers específicos para Panamá
        $xml .= '<Row ss:StyleID="TableHeader">' . "\n";
        $xml .= '<Cell><Data ss:Type="String">CÉDULA</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">APELLIDOS Y NOMBRES</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">CARGO/POSICIÓN</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">SALARIO BASE</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">INGRESOS TOTALES</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">SEGURO SOCIAL</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">SEGURO EDUCATIVO</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">IMPUESTO S/RENTA</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">OTRAS DEDUCCIONES</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">TOTAL DEDUCCIONES</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">SALARIO NETO</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        // Datos de empleados
        foreach ($employees as $emp) {
            $xml .= '<Row ss:StyleID="TableData">' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($emp['cedula'] ?? 'N/A') . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($emp['lastname'] . ', ' . $emp['firstname']) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($emp['employee']['puesto_actual'] ?? 'N/A') . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . number_format($emp['salary'], 2) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . number_format($emp['totals']['ingresos'], 2) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . number_format($emp['totals']['seguro_social'], 2) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . number_format($emp['totals']['seguro_educativo'], 2) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . number_format($emp['totals']['impuesto_renta'], 2) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . number_format($emp['totals']['otras_deducciones'], 2) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . number_format($emp['totals']['deducciones'], 2) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="Number">' . number_format($emp['totals']['neto'], 2) . '</Data></Cell>' . "\n";
            $xml .= '</Row>' . "\n";
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        
        // HOJA 3: DETALLE DE CONCEPTOS
        $xml .= '<Worksheet ss:Name="Detalle de Conceptos">' . "\n";
        $xml .= '<Table>' . "\n";
        
        $xml .= '<Row ss:StyleID="TableHeader">' . "\n";
        $xml .= '<Cell><Data ss:Type="String">EMPLEADO</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">CÉDULA</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">CÓDIGO CONCEPTO</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">DESCRIPCIÓN</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">TIPO</Data></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">MONTO</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        // Detalle de todos los conceptos por empleado
        foreach ($employees as $emp) {
            foreach ($emp['concepts'] as $concept) {
                $tipoTexto = $concept['tipo'] == 'A' ? 'INGRESO' : 'DEDUCCIÓN';
                
                $xml .= '<Row ss:StyleID="TableData">' . "\n";
                $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($emp['firstname'] . ' ' . $emp['lastname']) . '</Data></Cell>' . "\n";
                $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($emp['cedula'] ?? 'N/A') . '</Data></Cell>' . "\n";
                $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($concept['codigo']) . '</Data></Cell>' . "\n";
                $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($concept['descripcion']) . '</Data></Cell>' . "\n";
                $xml .= '<Cell><Data ss:Type="String">' . $tipoTexto . '</Data></Cell>' . "\n";
                $xml .= '<Cell><Data ss:Type="Number">' . number_format($concept['monto'], 2) . '</Data></Cell>' . "\n";
                $xml .= '</Row>' . "\n";
            }
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        
        // HOJA 4: FIRMAS Y CERTIFICACIÓN
        $xml .= '<Worksheet ss:Name="Certificación">' . "\n";
        $xml .= '<Table>' . "\n";
        
        $xml .= '<Row ss:StyleID="HeaderCompany">' . "\n";
        $xml .= '<Cell ss:MergeAcross="3"><Data ss:Type="String">CERTIFICACIÓN DE PLANILLA</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row><Cell></Cell></Row>' . "\n";
        $xml .= '<Row><Cell></Cell></Row>' . "\n";
        
        $xml .= '<Row ss:StyleID="Info">' . "\n";
        $xml .= '<Cell ss:MergeAcross="3"><Data ss:Type="String">Por medio de la presente certificamos que la planilla adjunta</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row ss:StyleID="Info">' . "\n";
        $xml .= '<Cell ss:MergeAcross="3"><Data ss:Type="String">corresponde al período ' . $fechaInicio . ' al ' . $fechaFin . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row ss:StyleID="Info">' . "\n";
        $xml .= '<Cell ss:MergeAcross="3"><Data ss:Type="String">y que los montos reflejados son correctos.</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row><Cell></Cell></Row>' . "\n";
        $xml .= '<Row><Cell></Cell></Row>' . "\n";
        $xml .= '<Row><Cell></Cell></Row>' . "\n";
        
        // Firmas
        $xml .= '<Row ss:StyleID="Info">' . "\n";
        $xml .= '<Cell><Data ss:Type="String">ELABORADO POR:</Data></Cell>' . "\n";
        $xml .= '<Cell></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">REVISADO POR:</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row><Cell></Cell></Row>' . "\n";
        $xml .= '<Row><Cell></Cell></Row>' . "\n";
        
        $xml .= '<Row ss:StyleID="Info">' . "\n";
        $xml .= '<Cell><Data ss:Type="String">____________________</Data></Cell>' . "\n";
        $xml .= '<Cell></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">____________________</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row ss:StyleID="Info">' . "\n";
        $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($signatures['elaborado_por'] ?: 'Por definir') . '</Data></Cell>' . "\n";
        $xml .= '<Cell></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($signatures['jefe_recursos_humanos'] ?: 'Por definir') . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row ss:StyleID="Info">' . "\n";
        $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($signatures['cargo_elaborador'] ?: 'Especialista en Nóminas') . '</Data></Cell>' . "\n";
        $xml .= '<Cell></Cell>' . "\n";
        $xml .= '<Cell><Data ss:Type="String">' . $this->escapeXmlData($signatures['cargo_jefe_rrhh'] ?: 'Jefe de Recursos Humanos') . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '<Row><Cell></Cell></Row>' . "\n";
        $xml .= '<Row ss:StyleID="Info">' . "\n";
        $xml .= '<Cell ss:MergeAcross="3"><Data ss:Type="String">Fecha de generación: ' . date('d/m/Y H:i:s') . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        
        $xml .= '</Workbook>';
        
        return $xml;
    }

    /**
     * Generar Excel personalizado según plantilla específica (HTML format)
     */
    public function generateCustomExcelContent($payroll, $employees, $companyInfo, $signatures)
    {
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_inicio']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_fin']));
        
        // Agrupar empleados por nivel organizativo
        $employeesByLevel = $this->groupEmployeesByLevel($employees);
        
        // Generar HTML que Excel reconocerá como XLSX
        $html = '<!DOCTYPE html>';
        $html .= '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="ProgId" content="Excel.Sheet">';
        $html .= '<meta name="Generator" content="Microsoft Excel 15">';
        $html .= '<!--[if !mso]><style>v\:* {behavior:url(#default#VML);}</style><![endif]-->';
        $html .= $this->getCustomExcelHTMLStyles();
        $html .= '</head>';
        $html .= '<body>';
        
        // Tabla principal
        $html .= '<table border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%">';
        
        // Header de la empresa
        $html .= '<tr class="company-header">';
        $html .= '<td colspan="24" style="text-align:center;font-weight:bold;font-size:16px;background-color:#4472C4;color:white;padding:10px;">';
        $html .= htmlspecialchars($companyInfo['company_name']);
        $html .= '</td>';
        $html .= '</tr>';
        
        $html .= '<tr class="subtitle">';
        $html .= '<td colspan="24" style="text-align:center;font-weight:bold;font-size:14px;background-color:#8FAADC;padding:8px;">';
        $html .= 'PLANILLA DE SUELDOS - ' . strtoupper($payroll['descripcion']);
        $html .= '</td>';
        $html .= '</tr>';
        
        $html .= '<tr class="period-info">';
        $html .= '<td colspan="24" style="text-align:center;font-weight:bold;font-size:11px;background-color:#D9E2F3;padding:6px;">';
        $html .= 'Período: ' . $fechaInicio . ' al ' . $fechaFin;
        $html .= '</td>';
        $html .= '</tr>';
        
        // Fila vacía
        $html .= '<tr><td colspan="24" style="height:10px;"></td></tr>';
        
        // Headers de columnas
        $html .= '<tr class="column-headers">';
        $headers = [
            'No.', 'COLABORADOR', 'FECHA DE ENTRADA', 'CEDULA', 'PUESTO', 
            'SALARIO MENSUAL', 'GASTO MENSUAL REP.', 'I.S.R. CLAVE', 'SALARIO QUINCENAL', 
            'Días Laborados', 'HORAS', 'Incapac.', 'G.REP. QUINC.', 'Salario Retroact.', 
            'Desc. Por Ausencia', 'SALARIO BRUTO', 'Bonificación QUINCENAL', 'SEG. SOC.', 
            'S.S. G.REP.', 'SEG. EDU.', 'I.S.R.', 'DESC. G.REP.', 'DESC. VARIOS', 'SALARIO NETO'
        ];
        
        foreach ($headers as $header) {
            $html .= '<td style="text-align:center;font-weight:bold;font-size:9px;background-color:#70AD47;color:white;padding:4px;border:1px solid #000;white-space:nowrap;">';
            $html .= htmlspecialchars($header);
            $html .= '</td>';
        }
        $html .= '</tr>';
        
        // Generar datos por nivel organizativo
        $numeroEmpleado = 1;
        $totalesGenerales = $this->initializeTotals();
        
        foreach ($employeesByLevel as $nivel => $employeesInLevel) {
            // Header del nivel organizativo
            $html .= '<tr class="level-header">';
            $html .= '<td colspan="24" style="font-weight:bold;font-size:12px;background-color:#FFC000;padding:6px;border:1px solid #000;">';
            $html .= strtoupper($nivel);
            $html .= '</td>';
            $html .= '</tr>';
            
            $totalesNivel = $this->initializeTotals();
            
            // Empleados del nivel
            foreach ($employeesInLevel as $emp) {
                $html .= $this->generateEmployeeRowHTML($emp, $numeroEmpleado, $totalesNivel, $totalesGenerales);
                $numeroEmpleado++;
            }
            
            // Subtotal del nivel
            $html .= '<tr class="subtotal">';
            $html .= '<td colspan="5" style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;padding:4px;">SUBTOTAL ' . strtoupper($nivel) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['salario_mensual'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['gasto_rep_mensual'], 2) . '</td>';
            $html .= '<td style="background-color:#E2EFDA;border:1px solid #000;"></td>'; // I.S.R. CLAVE
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['salario_quincenal'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:center;background-color:#E2EFDA;border:1px solid #000;">' . $totalesNivel['dias_laborados'] . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['horas'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['incapacidades'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['gasto_rep_quinc'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['salario_retroactivo'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['desc_ausencias'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['salario_bruto'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['bonificacion'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['seguro_social'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['ss_gasto_rep'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['seguro_educativo'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['isr'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['desc_gasto_rep'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['desc_varios'], 2) . '</td>';
            $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totalesNivel['salario_neto'], 2) . '</td>';
            $html .= '</tr>';
            
            // Fila vacía entre niveles
            $html .= '<tr><td colspan="24" style="height:5px;"></td></tr>';
        }
        
        // Total general
        $html .= '<tr class="grand-total">';
        $html .= '<td colspan="5" style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;padding:4px;">TOTAL GENERAL</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['salario_mensual'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['gasto_rep_mensual'], 2) . '</td>';
        $html .= '<td style="background-color:#C5E0B4;border:2px solid #000;"></td>'; // I.S.R. CLAVE
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['salario_quincenal'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:center;background-color:#C5E0B4;border:2px solid #000;">' . $totalesGenerales['dias_laborados'] . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['horas'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['incapacidades'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['gasto_rep_quinc'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['salario_retroactivo'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['desc_ausencias'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['salario_bruto'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['bonificacion'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['seguro_social'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['ss_gasto_rep'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['seguro_educativo'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['isr'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['desc_gasto_rep'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['desc_varios'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totalesGenerales['salario_neto'], 2) . '</td>';
        $html .= '</tr>';
        
        // Firmas
        $html .= '<tr><td colspan="24" style="height:20px;"></td></tr>';
        
        $html .= '<tr>';
        $html .= '<td colspan="8" style="text-align:center;font-weight:bold;border-top:1px solid #000;padding:10px;">Elaborado por:</td>';
        $html .= '<td colspan="8" style="text-align:center;font-weight:bold;border-top:1px solid #000;padding:10px;">Revisado por:</td>';
        $html .= '<td colspan="8" style="text-align:center;font-weight:bold;border-top:1px solid #000;padding:10px;">Autorizado por:</td>';
        $html .= '</tr>';
        
        $html .= '<tr><td colspan="24" style="height:10px;"></td></tr>';
        
        $html .= '<tr>';
        $html .= '<td colspan="8" style="text-align:center;font-weight:bold;padding:5px;">' . htmlspecialchars($signatures['elaborado_por'] ?: 'Por definir') . '</td>';
        $html .= '<td colspan="8" style="text-align:center;font-weight:bold;padding:5px;">' . htmlspecialchars($signatures['jefe_recursos_humanos'] ?: 'Por definir') . '</td>';
        $html .= '<td colspan="8" style="text-align:center;font-weight:bold;padding:5px;">Gerente General</td>';
        $html .= '</tr>';
        
        $html .= '<tr>';
        $html .= '<td colspan="8" style="text-align:center;padding:5px;">' . htmlspecialchars($signatures['cargo_elaborador'] ?: 'Especialista en Nóminas') . '</td>';
        $html .= '<td colspan="8" style="text-align:center;padding:5px;">' . htmlspecialchars($signatures['cargo_jefe_rrhh'] ?: 'Jefe de Recursos Humanos') . '</td>';
        $html .= '<td colspan="8" style="text-align:center;padding:5px;">Administración</td>';
        $html .= '</tr>';
        
        $html .= '</table>';
        $html .= '</body>';
        $html .= '</html>';
        
        return $html;
    }

    /**
     * Obtener estilos CSS para Excel HTML
     */
    public function getCustomExcelHTMLStyles()
    {
        return '
        <style>
            table { 
                border-collapse: collapse; 
                width: 100%; 
                font-family: Arial, sans-serif; 
            }
            .company-header td { 
                text-align: center; 
                font-weight: bold; 
                font-size: 16px; 
                background-color: #4472C4; 
                color: white; 
                padding: 10px; 
            }
            .subtitle td { 
                text-align: center; 
                font-weight: bold; 
                font-size: 14px; 
                background-color: #8FAADC; 
                padding: 8px; 
            }
            .period-info td { 
                text-align: center; 
                font-weight: bold; 
                font-size: 11px; 
                background-color: #D9E2F3; 
                padding: 6px; 
            }
            .column-headers td { 
                text-align: center; 
                font-weight: bold; 
                font-size: 9px; 
                background-color: #70AD47; 
                color: white; 
                padding: 4px; 
                border: 1px solid #000; 
                white-space: nowrap; 
            }
            .level-header td { 
                font-weight: bold; 
                font-size: 12px; 
                background-color: #FFC000; 
                padding: 6px; 
                border: 1px solid #000; 
            }
            .subtotal td { 
                font-weight: bold; 
                background-color: #E2EFDA; 
                border: 1px solid #000; 
                padding: 4px; 
            }
            .grand-total td { 
                font-weight: bold; 
                background-color: #C5E0B4; 
                border: 2px solid #000; 
                padding: 4px; 
            }
            .employee-row td { 
                font-size: 9px; 
                border: 1px solid #000; 
                padding: 2px; 
            }
            .number-cell { 
                text-align: center; 
            }
            .currency-cell { 
                text-align: right; 
            }
            .text-cell { 
                text-align: left; 
            }
        </style>';
    }

    /**
     * Obtener estilos específicos para la plantilla Excel personalizada (XML)
     */
    public function getCustomExcelStyles()
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
            
            <Style ss:ID="SubTitle">
                <Font ss:FontName="Arial" ss:Size="14" ss:Bold="1"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Interior ss:Color="#8FAADC" ss:Pattern="Solid"/>
            </Style>
            
            <Style ss:ID="PeriodInfo">
                <Font ss:FontName="Arial" ss:Size="11" ss:Bold="1"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Interior ss:Color="#D9E2F3" ss:Pattern="Solid"/>
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
            
            <Style ss:ID="LevelHeader">
                <Font ss:FontName="Arial" ss:Size="12" ss:Bold="1"/>
                <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
                <Interior ss:Color="#FFC000" ss:Pattern="Solid"/>
                <Borders>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <Style ss:ID="EmployeeRow">
                <Font ss:FontName="Arial" ss:Size="9"/>
                <Alignment ss:Vertical="Center"/>
                <Borders>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <Style ss:ID="EmployeeRowNumber">
                <Font ss:FontName="Arial" ss:Size="9"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Borders>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <Style ss:ID="CurrencyCell">
                <Font ss:FontName="Arial" ss:Size="9"/>
                <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
                <NumberFormat ss:Format="&quot;$&quot;#,##0.00"/>
                <Borders>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <Style ss:ID="SubTotal">
                <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1"/>
                <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
                <Interior ss:Color="#E2EFDA" ss:Pattern="Solid"/>
                <NumberFormat ss:Format="&quot;$&quot;#,##0.00"/>
                <Borders>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
                </Borders>
            </Style>
            
            <Style ss:ID="GrandTotal">
                <Font ss:FontName="Arial" ss:Size="11" ss:Bold="1"/>
                <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
                <Interior ss:Color="#C5E0B4" ss:Pattern="Solid"/>
                <NumberFormat ss:Format="&quot;$&quot;#,##0.00"/>
                <Borders>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
                </Borders>
            </Style>
            
            <Style ss:ID="Signature">
                <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Borders>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
        </Styles>';
    }

    /**
     * Agrupar empleados por nivel organizativo
     */
    public function groupEmployeesByLevel($employees)
    {
        $grouped = [];
        
        foreach ($employees as $emp) {
            // Determinar el nivel organizativo basado en el puesto o función
            $nivel = 'GENERAL'; // Nivel por defecto
            
            if (!empty($emp['funcion_name'])) {
                $nivel = strtoupper($emp['funcion_name']);
            } elseif (!empty($emp['puesto_actual'])) {
                $nivel = strtoupper($emp['puesto_actual']);
            } elseif (!empty($emp['position_name'])) {
                $nivel = strtoupper($emp['position_name']);
            }
            
            if (!isset($grouped[$nivel])) {
                $grouped[$nivel] = [];
            }
            
            $grouped[$nivel][] = $emp;
        }
        
        // Ordenar niveles alfabéticamente
        ksort($grouped);
        
        return $grouped;
    }

    /**
     * Inicializar array de totales
     */
    public function initializeTotals()
    {
        return [
            'salario_mensual' => 0,
            'gasto_rep_mensual' => 0,
            'salario_quincenal' => 0,
            'dias_laborados' => 0,
            'horas' => 0,
            'incapacidades' => 0,
            'gasto_rep_quinc' => 0,
            'salario_retroactivo' => 0,
            'desc_ausencias' => 0,
            'salario_bruto' => 0,
            'bonificacion' => 0,
            'seguro_social' => 0,
            'ss_gasto_rep' => 0,
            'seguro_educativo' => 0,
            'isr' => 0,
            'desc_gasto_rep' => 0,
            'desc_varios' => 0,
            'salario_neto' => 0
        ];
    }

    /**
     * Generar fila de empleado en formato HTML
     */
    public function generateEmployeeRowHTML($emp, $numeroEmpleado, &$totalesNivel, &$totalesGenerales)
    {
        // Calcular valores específicos para la plantilla
        $salarioMensual = $emp['salary'] ?? 0;
        $gastoRepMensual = $this->getConceptAmount($emp, 'GASTO_REPRESENTACION') * 2; // Quincenal a mensual
        $salarioQuincenal = $salarioMensual / 2;
        $diasLaborados = $emp['reference_value'] ?? 15; // Campo referencia como días laborados
        $horas = $this->getConceptAmount($emp, 'HORAS_EXTRAS');
        $incapacidades = $this->getConceptAmount($emp, 'INCAPACIDAD');
        $gastoRepQuinc = $this->getConceptAmount($emp, 'GASTO_REPRESENTACION');
        $salarioRetroactivo = $this->getConceptAmount($emp, 'RETROACTIVO');
        $descAusencias = $this->getConceptAmount($emp, 'AUSENCIAS', 'deduccion');
        $salarioBruto = $emp['totals']['ingresos'] ?? 0;
        $bonificacion = $this->getConceptAmount($emp, 'BONIFICACION');
        $seguroSocial = $emp['totals']['seguro_social'] ?? 0;
        $ssGastoRep = $gastoRepQuinc * 0.0975; // 9.75% sobre gasto de representación
        $seguroEducativo = $emp['totals']['seguro_educativo'] ?? 0;
        $isr = $emp['totals']['impuesto_renta'] ?? 0;
        $descGastoRep = $this->getConceptAmount($emp, 'DESC_GASTO_REP', 'deduccion');
        $descVarios = $this->getConceptAmount($emp, 'OTROS', 'deduccion');
        $salarioNeto = $emp['totals']['neto'] ?? 0;
        
        // Acumular totales (reutilizar la función existente)
        $this->addToTotals($totalesNivel, [
            'salario_mensual' => $salarioMensual,
            'gasto_rep_mensual' => $gastoRepMensual,
            'salario_quincenal' => $salarioQuincenal,
            'dias_laborados' => $diasLaborados,
            'horas' => $horas,
            'incapacidades' => $incapacidades,
            'gasto_rep_quinc' => $gastoRepQuinc,
            'salario_retroactivo' => $salarioRetroactivo,
            'desc_ausencias' => $descAusencias,
            'salario_bruto' => $salarioBruto,
            'bonificacion' => $bonificacion,
            'seguro_social' => $seguroSocial,
            'ss_gasto_rep' => $ssGastoRep,
            'seguro_educativo' => $seguroEducativo,
            'isr' => $isr,
            'desc_gasto_rep' => $descGastoRep,
            'desc_varios' => $descVarios,
            'salario_neto' => $salarioNeto
        ]);
        
        $this->addToTotals($totalesGenerales, [
            'salario_mensual' => $salarioMensual,
            'gasto_rep_mensual' => $gastoRepMensual,
            'salario_quincenal' => $salarioQuincenal,
            'dias_laborados' => $diasLaborados,
            'horas' => $horas,
            'incapacidades' => $incapacidades,
            'gasto_rep_quinc' => $gastoRepQuinc,
            'salario_retroactivo' => $salarioRetroactivo,
            'desc_ausencias' => $descAusencias,
            'salario_bruto' => $salarioBruto,
            'bonificacion' => $bonificacion,
            'seguro_social' => $seguroSocial,
            'ss_gasto_rep' => $ssGastoRep,
            'seguro_educativo' => $seguroEducativo,
            'isr' => $isr,
            'desc_gasto_rep' => $descGastoRep,
            'desc_varios' => $descVarios,
            'salario_neto' => $salarioNeto
        ]);
        
        // Generar fila HTML
        $html = '<tr class="employee-row">';
        $html .= '<td class="number-cell">' . $numeroEmpleado . '</td>';
        $html .= '<td class="text-cell">' . htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']) . '</td>';
        $html .= '<td class="text-cell">' . htmlspecialchars(date('d/m/Y', strtotime($emp['fecha_ingreso'] ?? 'now'))) . '</td>';
        $html .= '<td class="text-cell">' . htmlspecialchars($emp['document_id'] ?? 'N/A') . '</td>';
        $html .= '<td class="text-cell">' . htmlspecialchars($emp['puesto_actual'] ?? $emp['position_name'] ?? 'N/A') . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($salarioMensual, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($gastoRepMensual, 2) . '</td>';
        $html .= '<td class="text-cell">' . htmlspecialchars($emp['isr_clave'] ?? '') . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($salarioQuincenal, 2) . '</td>';
        $html .= '<td class="number-cell">' . $diasLaborados . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($horas, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($incapacidades, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($gastoRepQuinc, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($salarioRetroactivo, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($descAusencias, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($salarioBruto, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($bonificacion, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($seguroSocial, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($ssGastoRep, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($seguroEducativo, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($isr, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($descGastoRep, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($descVarios, 2) . '</td>';
        $html .= '<td class="currency-cell">$' . number_format($salarioNeto, 2) . '</td>';
        $html .= '</tr>';
        
        return $html;
    }

    /**
     * Generar fila de empleado personalizada (XML)
     */
    public function generateEmployeeRowCustom($emp, $numeroEmpleado, &$totalesNivel, &$totalesGenerales)
    {
        // Calcular valores específicos para la plantilla
        $salarioMensual = $emp['salary'] ?? 0;
        $gastoRepMensual = $this->getConceptAmount($emp, 'GASTO_REPRESENTACION') * 2; // Quincenal a mensual
        $salarioQuincenal = $salarioMensual / 2;
        $diasLaborados = $emp['reference_value'] ?? 15; // Campo referencia como días laborados
        $horas = $this->getConceptAmount($emp, 'HORAS_EXTRAS');
        $incapacidades = $this->getConceptAmount($emp, 'INCAPACIDAD');
        $gastoRepQuinc = $this->getConceptAmount($emp, 'GASTO_REPRESENTACION');
        $salarioRetroactivo = $this->getConceptAmount($emp, 'RETROACTIVO');
        $descAusencias = $this->getConceptAmount($emp, 'AUSENCIAS', 'deduccion');
        $salarioBruto = $emp['totals']['ingresos'] ?? 0;
        $bonificacion = $this->getConceptAmount($emp, 'BONIFICACION');
        $seguroSocial = $emp['totals']['seguro_social'] ?? 0;
        $ssGastoRep = $gastoRepQuinc * 0.0975; // 9.75% sobre gasto de representación
        $seguroEducativo = $emp['totals']['seguro_educativo'] ?? 0;
        $isr = $emp['totals']['impuesto_renta'] ?? 0;
        $descGastoRep = $this->getConceptAmount($emp, 'DESC_GASTO_REP', 'deduccion');
        $descVarios = $this->getConceptAmount($emp, 'OTROS', 'deduccion');
        $salarioNeto = $emp['totals']['neto'] ?? 0;
        
        // Acumular totales
        $this->addToTotals($totalesNivel, [
            'salario_mensual' => $salarioMensual,
            'gasto_rep_mensual' => $gastoRepMensual,
            'salario_quincenal' => $salarioQuincenal,
            'dias_laborados' => $diasLaborados,
            'horas' => $horas,
            'incapacidades' => $incapacidades,
            'gasto_rep_quinc' => $gastoRepQuinc,
            'salario_retroactivo' => $salarioRetroactivo,
            'desc_ausencias' => $descAusencias,
            'salario_bruto' => $salarioBruto,
            'bonificacion' => $bonificacion,
            'seguro_social' => $seguroSocial,
            'ss_gasto_rep' => $ssGastoRep,
            'seguro_educativo' => $seguroEducativo,
            'isr' => $isr,
            'desc_gasto_rep' => $descGastoRep,
            'desc_varios' => $descVarios,
            'salario_neto' => $salarioNeto
        ]);
        
        $this->addToTotals($totalesGenerales, [
            'salario_mensual' => $salarioMensual,
            'gasto_rep_mensual' => $gastoRepMensual,
            'salario_quincenal' => $salarioQuincenal,
            'dias_laborados' => $diasLaborados,
            'horas' => $horas,
            'incapacidades' => $incapacidades,
            'gasto_rep_quinc' => $gastoRepQuinc,
            'salario_retroactivo' => $salarioRetroactivo,
            'desc_ausencias' => $descAusencias,
            'salario_bruto' => $salarioBruto,
            'bonificacion' => $bonificacion,
            'seguro_social' => $seguroSocial,
            'ss_gasto_rep' => $ssGastoRep,
            'seguro_educativo' => $seguroEducativo,
            'isr' => $isr,
            'desc_gasto_rep' => $descGastoRep,
            'desc_varios' => $descVarios,
            'salario_neto' => $salarioNeto
        ]);
        
        // Generar fila XML
        $xml = '<Row>' . "\n";
        $xml .= '<Cell ss:StyleID="EmployeeRowNumber"><Data ss:Type="Number">' . $numeroEmpleado . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="EmployeeRow"><Data ss:Type="String">' . $this->escapeXmlData($emp['firstname'] . ' ' . $emp['lastname']) . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="EmployeeRow"><Data ss:Type="String">' . $this->escapeXmlData(date('d/m/Y', strtotime($emp['fecha_ingreso'] ?? 'now'))) . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="EmployeeRow"><Data ss:Type="String">' . $this->escapeXmlData($emp['document_id'] ?? 'N/A') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="EmployeeRow"><Data ss:Type="String">' . $this->escapeXmlData($emp['puesto_actual'] ?? $emp['position_name'] ?? 'N/A') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($salarioMensual, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($gastoRepMensual, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="EmployeeRow"><Data ss:Type="String">' . $this->escapeXmlData($emp['isr_clave'] ?? '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($salarioQuincenal, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="EmployeeRowNumber"><Data ss:Type="Number">' . $diasLaborados . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($horas, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($incapacidades, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($gastoRepQuinc, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($salarioRetroactivo, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($descAusencias, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($salarioBruto, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($bonificacion, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($seguroSocial, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($ssGastoRep, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($seguroEducativo, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($isr, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($descGastoRep, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($descVarios, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '<Cell ss:StyleID="CurrencyCell"><Data ss:Type="Number">' . number_format($salarioNeto, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '</Row>' . "\n";
        
        return $xml;
    }

    /**
     * Obtener monto de un concepto específico
     */
    public function getConceptAmount($employee, $conceptCode, $type = 'ingreso')
    {
        if (!isset($employee['concepts']) || !is_array($employee['concepts'])) {
            return 0;
        }
        
        foreach ($employee['concepts'] as $concept) {
            if (($concept['tipo'] === $type || strtolower($concept['tipo']) === $type) && 
                (strpos(strtoupper($concept['codigo'] ?? ''), $conceptCode) !== false ||
                 strpos(strtoupper($concept['descripcion'] ?? ''), $conceptCode) !== false)) {
                return $concept['monto'] ?? 0;
            }
        }
        
        return 0;
    }

    /**
     * Agregar valores a los totales
     */
    public function addToTotals(&$totales, $values)
    {
        foreach ($values as $key => $value) {
            if (isset($totales[$key])) {
                $totales[$key] += $value;
            }
        }
    }

    /**
     * Calcular totales específicos para Panamá
     */
    public function calculatePanamaPayrollTotals($employees)
    {
        $totales = [
            'total_empleados' => count($employees),
            'total_ingresos' => 0,
            'total_deducciones' => 0,
            'total_seguro_social' => 0,
            'total_seguro_educativo' => 0,
            'total_impuesto_renta' => 0,
            'total_otras_deducciones' => 0,
            'neto_total' => 0,
            'resumen_conceptos' => []
        ];
        
        $conceptos = [];
        
        foreach ($employees as $emp) {
            $totales['total_ingresos'] += $emp['totals']['ingresos'];
            $totales['total_deducciones'] += $emp['totals']['deducciones'];
            $totales['total_seguro_social'] += $emp['totals']['seguro_social'];
            $totales['total_seguro_educativo'] += $emp['totals']['seguro_educativo'];
            $totales['total_impuesto_renta'] += $emp['totals']['impuesto_renta'];
            $totales['total_otras_deducciones'] += $emp['totals']['otras_deducciones'];
            $totales['neto_total'] += $emp['totals']['neto'];
            
            // Agrupar conceptos
            foreach ($emp['concepts'] as $concept) {
                $key = $concept['descripcion'];
                if (!isset($conceptos[$key])) {
                    $conceptos[$key] = [
                        'total' => 0,
                        'empleados' => 0,
                        'tipo' => $concept['tipo']
                    ];
                }
                $conceptos[$key]['total'] += $concept['monto'];
                $conceptos[$key]['empleados']++;
            }
        }
        
        // Procesar resumen de conceptos
        foreach ($conceptos as $descripcion => $data) {
            $totales['resumen_conceptos'][$descripcion] = [
                'total' => $data['total'],
                'empleados' => $data['empleados'],
                'promedio' => $data['empleados'] > 0 ? $data['total'] / $data['empleados'] : 0,
                'porcentaje' => $totales['total_ingresos'] > 0 ? ($data['total'] / $totales['total_ingresos']) * 100 : 0,
                'tipo' => $data['tipo']
            ];
        }
        
        return $totales;
    }

    /**
     * Escapar datos para XML de Excel
     */
    public function escapeXmlData($data)
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

    /**
     * Validar XML para Excel
     */
    public function validateExcelXML($xml)
    {
        // Verificar que el XML básico esté bien formado
        $doc = new \DOMDocument();
        $doc->recover = true;
        
        // Suprimir errores y warnings de DOMDocument
        libxml_use_internal_errors(true);
        
        $valid = $doc->loadXML($xml);
        
        if (!$valid) {
            $errors = libxml_get_errors();
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = "Línea {$error->line}: {$error->message}";
            }
            error_log("Errores XML Excel: " . implode('; ', $errorMessages));
            libxml_clear_errors();
        }
        
        libxml_use_internal_errors(false);
        
        return $valid;
    }

    /**
     * Generar estilos CSS para Excel
     */
    public function getExcelStyles()
    {
        return '<Styles>
            <Style ss:ID="HeaderCompany">
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Size="16" ss:Bold="1" ss:Color="#FFFFFF"/>
                <Interior ss:Color="#4F81BD" ss:Pattern="Solid"/>
            </Style>
            <Style ss:ID="SubHeader">
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Size="12" ss:Bold="1"/>
                <Interior ss:Color="#DCE6F1" ss:Pattern="Solid"/>
            </Style>
            <Style ss:ID="Info">
                <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Size="10"/>
            </Style>
            <Style ss:ID="TableHeader">
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/>
                <Interior ss:Color="#4F81BD" ss:Pattern="Solid"/>
                <Borders>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            <Style ss:ID="TableData">
                <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Size="9"/>
                <Borders>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            <Style ss:ID="TableTotal">
                <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1"/>
                <Interior ss:Color="#FFC000" ss:Pattern="Solid"/>
                <Borders>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
                </Borders>
            </Style>
        </Styles>' . "\n";
    }

    /**
     * Generar reporte PDF de acumulados por empleado
     */
    public function acumuladosEmpleadoPdf($empleadoId)
    {
        try {
            $this->requireAuth();
            
            if (!$empleadoId) {
                $_SESSION['error'] = 'ID de empleado requerido';
                $this->redirect('/panel/acumulados');
            }
            
            // Obtener datos del empleado
            $employeeModel = $this->model('Employee');
            $employee = $employeeModel->getEmployeeWithFullDetails($empleadoId);
            
            if (!$employee) {
                $_SESSION['error'] = 'Empleado no encontrado';
                $this->redirect('/panel/acumulados');
            }
            
            $year = $_GET['year'] ?? date('Y');
            
            // Obtener acumulados del empleado
            $acumulados = $this->getAcumuladosForEmployeeReport($empleadoId, $year);
            
            // Obtener información de la empresa
            $companyInfo = $this->companyModel->getCompanyForReports();
            $signatures = $this->companyModel->getSignaturesForReports();
            
            // Generar PDF
            $this->generateAcumuladosEmpleadoPDF($employee, $acumulados, $year, $companyInfo, $signatures);
            
        } catch (\Exception $e) {
            error_log("Error en ReportController@acumuladosEmpleadoPdf: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar el reporte de acumulados: ' . $e->getMessage();
            $this->redirect('/panel/acumulados');
        }
    }

    /**
     * Generar reporte PDF de acumulados por tipo
     */
    public function acumuladosTipoPdf($tipoId)
    {
        try {
            $this->requireAuth();
            
            if (!$tipoId) {
                $_SESSION['error'] = 'ID de tipo de acumulado requerido';
                $this->redirect('/panel/acumulados');
            }
            
            // Obtener información del tipo de acumulado
            $tipoAcumuladoModel = $this->model('TipoAcumulado');
            $tipoAcumulado = $tipoAcumuladoModel->getById($tipoId);
            
            if (!$tipoAcumulado) {
                $_SESSION['error'] = 'Tipo de acumulado no encontrado';
                $this->redirect('/panel/acumulados');
            }
            
            $year = $_GET['year'] ?? date('Y');
            
            // Obtener acumulados por tipo
            $acumulados = $this->getAcumuladosForTipoReport($tipoId, $year);
            
            // Obtener información de la empresa
            $companyInfo = $this->companyModel->getCompanyForReports();
            $signatures = $this->companyModel->getSignaturesForReports();
            
            // Generar PDF
            $this->generateAcumuladosTipoPDF($tipoAcumulado, $acumulados, $year, $companyInfo, $signatures);
            
        } catch (\Exception $e) {
            error_log("Error en ReportController@acumuladosTipoPdf: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar el reporte de acumulados por tipo: ' . $e->getMessage();
            $this->redirect('/panel/acumulados');
        }
    }

    /**
     * Generar reporte PDF general de acumulados
     */
    public function acumuladosGeneralPdf()
    {
        try {
            $this->requireAuth();
            
            $year = $_GET['year'] ?? date('Y');
            $tipoConcepto = $_GET['tipo_concepto'] ?? '';
            
            // Obtener todos los acumulados del año
            $acumulados = $this->getAcumuladosForGeneralReport($year, $tipoConcepto);
            
            // Obtener información de la empresa
            $companyInfo = $this->companyModel->getCompanyForReports();
            $signatures = $this->companyModel->getSignaturesForReports();
            
            // Generar PDF
            $this->generateAcumuladosGeneralPDF($acumulados, $year, $tipoConcepto, $companyInfo, $signatures);
            
        } catch (\Exception $e) {
            error_log("Error en ReportController@acumuladosGeneralPdf: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar el reporte general de acumulados: ' . $e->getMessage();
            $this->redirect('/panel/acumulados');
        }
    }

    /**
     * Obtener datos de acumulados para reporte de empleado
     */
    public function getAcumuladosForEmployeeReport($empleadoId, $year)
    {
        try {
            $sql = "SELECT 
                        c.id as concepto_id,
                        c.descripcion as concepto_descripcion,
                        c.tipo_concepto,
                        c.codigo as concepto_codigo,
                        ape.tipo_concepto as ape_tipo_concepto,
                        SUM(ape.monto) as total_acumulado,
                        COUNT(ape.planilla_id) as total_planillas,
                        ape.frecuencia,
                        MIN(ape.created_at) as fecha_primer_calculo,
                        MAX(ape.created_at) as fecha_ultimo_calculo,
                        GROUP_CONCAT(
                            DISTINCT CONCAT(
                                pc.descripcion, ' (', 
                                DATE_FORMAT(pc.fecha_inicio, '%d/%m/%Y'), ' - ',
                                DATE_FORMAT(pc.fecha_fin, '%d/%m/%Y'), ')'
                            ) 
                            ORDER BY pc.fecha_inicio 
                            SEPARATOR '; '
                        ) as planillas_incluidas
                    FROM acumulados_por_empleado ape
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE ape.employee_id = ? AND ape.ano = ?
                    GROUP BY c.id, c.descripcion, c.tipo_concepto, c.codigo, ape.tipo_concepto, ape.frecuencia
                    ORDER BY c.tipo_concepto, c.descripcion";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$empleadoId, $year]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados para reporte de empleado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener datos de acumulados para reporte por tipo
     */
    public function getAcumuladosForTipoReport($tipoId, $year)
    {
        try {
            // Primero obtener los conceptos asociados a este tipo
            $sql = "SELECT DISTINCT concepto_id FROM concepto_acumulado WHERE tipo_acumulado_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tipoId]);
            $conceptoIds = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'concepto_id');
            
            if (empty($conceptoIds)) {
                return [];
            }
            
            $placeholders = str_repeat('?,', count($conceptoIds) - 1) . '?';
            $params = array_merge($conceptoIds, [$year]);
            
            $sql = "SELECT 
                        e.id as employee_id,
                        e.document_id,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_empleado,
                        p.description as position,
                        c.id as concepto_id,
                        c.descripcion as concepto_descripcion,
                        c.tipo_concepto,
                        c.codigo as concepto_codigo,
                        SUM(ape.monto) as total_acumulado,
                        COUNT(ape.planilla_id) as total_planillas,
                        ape.frecuencia,
                        MAX(ape.created_at) as fecha_ultimo_calculo
                    FROM acumulados_por_empleado ape
                    INNER JOIN employees e ON ape.employee_id = e.id
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN positions p ON e.position_id = p.id
                    WHERE ape.concepto_id IN ($placeholders) AND ape.ano = ?
                    GROUP BY e.id, c.id, ape.frecuencia
                    ORDER BY e.lastname, e.firstname, c.descripcion";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados para reporte por tipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener datos de acumulados para reporte general
     */
    public function getAcumuladosForGeneralReport($year, $tipoConcepto = '')
    {
        try {
            $whereConditions = ["ape.ano = ?"];
            $params = [$year];
            
            if (!empty($tipoConcepto)) {
                $whereConditions[] = "c.tipo_concepto = ?";
                $params[] = $tipoConcepto;
            }
            
            $whereClause = implode(" AND ", $whereConditions);
            
            $sql = "SELECT 
                        e.id as employee_id,
                        e.document_id,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_empleado,
                        p.description as position,
                        c.id as concepto_id,
                        c.descripcion as concepto_descripcion,
                        c.tipo_concepto,
                        c.codigo as concepto_codigo,
                        SUM(ape.monto) as total_acumulado,
                        COUNT(DISTINCT ape.planilla_id) as total_planillas,
                        ape.frecuencia,
                        MAX(ape.created_at) as fecha_ultimo_calculo
                    FROM acumulados_por_empleado ape
                    INNER JOIN employees e ON ape.employee_id = e.id
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN positions p ON e.position_id = p.id
                    WHERE {$whereClause}
                    GROUP BY e.id, c.id, ape.frecuencia
                    ORDER BY c.tipo_concepto, c.descripcion, e.lastname, e.firstname";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados para reporte general: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generar PDF de acumulados por empleado
     */
    public function generateAcumuladosEmpleadoPDF($employee, $acumulados, $year, $companyInfo, $signatures)
    {
        $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuración del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor($companyInfo['company_name']);
        $pdf->SetTitle('Reporte de Acumulados - ' . $employee['firstname'] . ' ' . $employee['lastname'] . ' - Año ' . $year);
        
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->AddPage();
        
        // Header del reporte
        $this->addCompanyHeaderToPDF($pdf, $companyInfo);
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'REPORTE DE ACUMULADOS POR EMPLEADO', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'AÑO ' . $year, 0, 1, 'C');
        
        $pdf->Ln(10);
        
        // Información del empleado
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 8, 'DATOS DEL EMPLEADO', 0, 1, 'L', true);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Cédula:', 0, 0, 'L');
        $pdf->Cell(0, 6, $employee['document_id'] ?? 'N/A', 0, 1, 'L');
        
        $pdf->Cell(40, 6, 'Nombre:', 0, 0, 'L');
        $pdf->Cell(0, 6, $employee['firstname'] . ' ' . $employee['lastname'], 0, 1, 'L');
        
        if (!empty($employee['position_description'])) {
            $pdf->Cell(40, 6, 'Posición:', 0, 0, 'L');
            $pdf->Cell(0, 6, $employee['position_description'], 0, 1, 'L');
        }
        
        $pdf->Cell(40, 6, 'Salario Base:', 0, 0, 'L');
        $pdf->Cell(0, 6, $this->formatCurrency($employee['salario_base'] ?? 0), 0, 1, 'L');
        
        $pdf->Ln(8);
        
        // Tabla de acumulados
        if (!empty($acumulados)) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetFillColor(220, 220, 255);
            $pdf->Cell(0, 8, 'DETALLE DE ACUMULADOS', 0, 1, 'L', true);
            
            // Headers de tabla
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(25, 6, 'Código', 1, 0, 'C', true);
            $pdf->Cell(80, 6, 'Concepto', 1, 0, 'C', true);
            $pdf->Cell(25, 6, 'Tipo', 1, 0, 'C', true);
            $pdf->Cell(30, 6, 'Total Acum.', 1, 0, 'C', true);
            $pdf->Cell(20, 6, 'Planillas', 1, 1, 'C', true);
            
            // Datos de acumulados
            $pdf->SetFont('helvetica', '', 8);
            $totalGeneral = 0;
            $tipoActual = '';
            $totalTipo = 0;
            
            foreach ($acumulados as $acum) {
                // Subtotal por tipo
                if ($tipoActual != '' && $tipoActual != $acum['tipo_concepto']) {
                    $pdf->SetFont('helvetica', 'B', 8);
                    $pdf->SetFillColor(255, 255, 200);
                    $pdf->Cell(130, 5, 'SUBTOTAL ' . strtoupper($tipoActual) . ':', 1, 0, 'R', true);
                    $pdf->Cell(30, 5, $this->formatCurrency($totalTipo), 1, 0, 'R', true);
                    $pdf->Cell(20, 5, '', 1, 1, 'C', true);
                    $totalTipo = 0;
                    $pdf->SetFont('helvetica', '', 8);
                    $pdf->SetFillColor(255, 255, 255);
                }
                
                $tipoActual = $acum['tipo_concepto'];
                
                $pdf->Cell(25, 5, $acum['concepto_codigo'] ?? 'N/A', 1, 0, 'C');
                $pdf->Cell(80, 5, $acum['concepto_descripcion'], 1, 0, 'L');
                $pdf->Cell(25, 5, $acum['tipo_concepto'], 1, 0, 'C');
                $pdf->Cell(30, 5, $this->formatCurrency($acum['total_acumulado']), 1, 0, 'R');
                $pdf->Cell(20, 5, $acum['total_planillas'], 1, 1, 'C');
                
                $totalTipo += $acum['total_acumulado'];
                $totalGeneral += $acum['total_acumulado'];
            }
            
            // Último subtotal
            if (!empty($acumulados)) {
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(255, 255, 200);
                $pdf->Cell(130, 5, 'SUBTOTAL ' . strtoupper($tipoActual) . ':', 1, 0, 'R', true);
                $pdf->Cell(30, 5, $this->formatCurrency($totalTipo), 1, 0, 'R', true);
                $pdf->Cell(20, 5, '', 1, 1, 'C', true);
            }
            
            // Total general
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(200, 255, 200);
            $pdf->Cell(130, 6, 'TOTAL GENERAL ACUMULADO:', 1, 0, 'R', true);
            $pdf->Cell(30, 6, $this->formatCurrency($totalGeneral), 1, 0, 'R', true);
            $pdf->Cell(20, 6, '', 1, 1, 'C', true);
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No hay acumulados registrados para este empleado en el año ' . $year, 0, 1, 'C');
        }
        
        // Firmas
        $this->addSignaturesToPDF($pdf, $signatures);
        
        // Output
        $filename = 'Acumulados_' . $employee['document_id'] . '_' . $year . '_' . date('Ymd_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Generar PDF de acumulados por tipo
     */
    public function generateAcumuladosTipoPDF($tipoAcumulado, $acumulados, $year, $companyInfo, $signatures)
    {
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); // Landscape
        
        // Configuración del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor($companyInfo['company_name']);
        $pdf->SetTitle('Reporte de Acumulados por Tipo - ' . $tipoAcumulado['descripcion'] . ' - Año ' . $year);
        
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->AddPage();
        
        // Header del reporte
        $this->addCompanyHeaderToPDF($pdf, $companyInfo);
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'REPORTE DE ACUMULADOS POR TIPO', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, strtoupper($tipoAcumulado['descripcion']) . ' - AÑO ' . $year, 0, 1, 'C');
        
        $pdf->Ln(8);
        
        // Tabla de acumulados
        if (!empty($acumulados)) {
            // Headers de tabla
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(25, 6, 'Cédula', 1, 0, 'C', true);
            $pdf->Cell(70, 6, 'Empleado', 1, 0, 'C', true);
            $pdf->Cell(60, 6, 'Posición', 1, 0, 'C', true);
            $pdf->Cell(60, 6, 'Concepto', 1, 0, 'C', true);
            $pdf->Cell(25, 6, 'Tipo', 1, 0, 'C', true);
            $pdf->Cell(30, 6, 'Total Acum.', 1, 0, 'C', true);
            $pdf->Cell(20, 6, 'Planillas', 1, 1, 'C', true);
            
            // Datos de acumulados
            $pdf->SetFont('helvetica', '', 8);
            $totalGeneral = 0;
            
            foreach ($acumulados as $acum) {
                $pdf->Cell(25, 5, $acum['document_id'] ?? 'N/A', 1, 0, 'C');
                $pdf->Cell(70, 5, $acum['nombre_empleado'], 1, 0, 'L');
                $pdf->Cell(60, 5, $acum['position'] ?? 'N/A', 1, 0, 'L');
                $pdf->Cell(60, 5, $acum['concepto_descripcion'], 1, 0, 'L');
                $pdf->Cell(25, 5, $acum['tipo_concepto'], 1, 0, 'C');
                $pdf->Cell(30, 5, $this->formatCurrency($acum['total_acumulado']), 1, 0, 'R');
                $pdf->Cell(20, 5, $acum['total_planillas'], 1, 1, 'C');
                
                $totalGeneral += $acum['total_acumulado'];
            }
            
            // Total general
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(200, 255, 200);
            $pdf->Cell(240, 6, 'TOTAL GENERAL:', 1, 0, 'R', true);
            $pdf->Cell(30, 6, $this->formatCurrency($totalGeneral), 1, 0, 'R', true);
            $pdf->Cell(20, 6, '', 1, 1, 'C', true);
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No hay acumulados registrados para este tipo en el año ' . $year, 0, 1, 'C');
        }
        
        // Firmas
        $this->addSignaturesToPDF($pdf, $signatures);
        
        // Output
        $filename = 'Acumulados_Tipo_' . str_replace(' ', '_', $tipoAcumulado['codigo']) . '_' . $year . '_' . date('Ymd_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Generar PDF general de acumulados
     */
    public function generateAcumuladosGeneralPDF($acumulados, $year, $tipoConcepto, $companyInfo, $signatures)
    {
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); // Landscape
        
        // Configuración del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor($companyInfo['company_name']);
        $title = 'Reporte General de Acumulados - Año ' . $year;
        if (!empty($tipoConcepto)) {
            $title .= ' - ' . strtoupper($tipoConcepto);
        }
        $pdf->SetTitle($title);
        
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->AddPage();
        
        // Header del reporte
        $this->addCompanyHeaderToPDF($pdf, $companyInfo);
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'REPORTE GENERAL DE ACUMULADOS', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 12);
        $subtitle = 'AÑO ' . $year;
        if (!empty($tipoConcepto)) {
            $subtitle .= ' - ' . strtoupper($tipoConcepto);
        }
        $pdf->Cell(0, 8, $subtitle, 0, 1, 'C');
        
        $pdf->Ln(8);
        
        // Tabla de acumulados
        if (!empty($acumulados)) {
            // Headers de tabla
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(25, 6, 'Cédula', 1, 0, 'C', true);
            $pdf->Cell(65, 6, 'Empleado', 1, 0, 'C', true);
            $pdf->Cell(55, 6, 'Posición', 1, 0, 'C', true);
            $pdf->Cell(55, 6, 'Concepto', 1, 0, 'C', true);
            $pdf->Cell(25, 6, 'Tipo', 1, 0, 'C', true);
            $pdf->Cell(30, 6, 'Total Acum.', 1, 0, 'C', true);
            $pdf->Cell(20, 6, 'Planillas', 1, 1, 'C', true);
            
            // Datos de acumulados agrupados por tipo
            $pdf->SetFont('helvetica', '', 8);
            $totalGeneral = 0;
            $tipoActual = '';
            $totalTipo = 0;
            
            foreach ($acumulados as $acum) {
                // Subtotal por tipo
                if ($tipoActual != '' && $tipoActual != $acum['tipo_concepto']) {
                    $pdf->SetFont('helvetica', 'B', 8);
                    $pdf->SetFillColor(255, 255, 200);
                    $pdf->Cell(225, 5, 'SUBTOTAL ' . strtoupper($tipoActual) . ':', 1, 0, 'R', true);
                    $pdf->Cell(30, 5, $this->formatCurrency($totalTipo), 1, 0, 'R', true);
                    $pdf->Cell(20, 5, '', 1, 1, 'C', true);
                    $totalTipo = 0;
                    $pdf->SetFont('helvetica', '', 8);
                    $pdf->SetFillColor(255, 255, 255);
                }
                
                $tipoActual = $acum['tipo_concepto'];
                
                $pdf->Cell(25, 5, $acum['document_id'] ?? 'N/A', 1, 0, 'C');
                $pdf->Cell(65, 5, $acum['nombre_empleado'], 1, 0, 'L');
                $pdf->Cell(55, 5, $acum['position'] ?? 'N/A', 1, 0, 'L');
                $pdf->Cell(55, 5, $acum['concepto_descripcion'], 1, 0, 'L');
                $pdf->Cell(25, 5, $acum['tipo_concepto'], 1, 0, 'C');
                $pdf->Cell(30, 5, $this->formatCurrency($acum['total_acumulado']), 1, 0, 'R');
                $pdf->Cell(20, 5, $acum['total_planillas'], 1, 1, 'C');
                
                $totalTipo += $acum['total_acumulado'];
                $totalGeneral += $acum['total_acumulado'];
            }
            
            // Último subtotal
            if (!empty($acumulados)) {
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(255, 255, 200);
                $pdf->Cell(225, 5, 'SUBTOTAL ' . strtoupper($tipoActual) . ':', 1, 0, 'R', true);
                $pdf->Cell(30, 5, $this->formatCurrency($totalTipo), 1, 0, 'R', true);
                $pdf->Cell(20, 5, '', 1, 1, 'C', true);
            }
            
            // Total general
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(200, 255, 200);
            $pdf->Cell(225, 6, 'TOTAL GENERAL ACUMULADOS:', 1, 0, 'R', true);
            $pdf->Cell(30, 6, $this->formatCurrency($totalGeneral), 1, 0, 'R', true);
            $pdf->Cell(20, 6, '', 1, 1, 'C', true);
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No hay acumulados registrados para los criterios especificados', 0, 1, 'C');
        }
        
        // Firmas
        $this->addSignaturesToPDF($pdf, $signatures);
        
        // Output
        $filename = 'Acumulados_General_' . $year;
        if (!empty($tipoConcepto)) {
            $filename .= '_' . str_replace(' ', '_', $tipoConcepto);
        }
        $filename .= '_' . date('Ymd_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Agregar header de empresa al PDF
     */
    public function addCompanyHeaderToPDF($pdf, $companyInfo)
    {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, strtoupper($companyInfo['company_name']), 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        if (!empty($companyInfo['ruc'])) {
            $pdf->Cell(0, 6, 'RUC: ' . $companyInfo['ruc'], 0, 1, 'C');
        }
        if (!empty($companyInfo['address'])) {
            $pdf->Cell(0, 6, $companyInfo['address'], 0, 1, 'C');
        }
        
        $pdf->Ln(5);
    }

    /**
     * Agregar firmas al PDF
     */
    public function addSignaturesToPDF($pdf, $signatures)
    {
        $pdf->Ln(15);
        
        // Fecha de generación
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
        
        $pdf->Ln(10);
        
        // Firmas
        $pdf->SetFont('helvetica', '', 10);
        
        // Elaborado por
        $pdf->Cell(90, 6, 'Elaborado por:', 0, 0, 'L');
        $pdf->Cell(90, 6, 'Revisado por:', 0, 1, 'L');
        
        $pdf->Ln(15);
        
        $pdf->Cell(90, 6, '_____________________________', 0, 0, 'C');
        $pdf->Cell(90, 6, '_____________________________', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(90, 5, $signatures['elaborado_por'], 0, 0, 'C');
        $pdf->Cell(90, 5, $signatures['jefe_recursos_humanos'], 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(90, 4, $signatures['cargo_elaborador'], 0, 0, 'C');
        $pdf->Cell(90, 4, $signatures['cargo_jefe_rrhh'], 0, 1, 'C');
    }
    
    protected function requireAuth()
    {
        if (!isset($_SESSION['admin'])) {
            $this->redirect('/admin');
        }
    }

    /**
     * Obtener información de la empresa desde la BD
     */
    public function getCompanyInfo()
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
                'firma_director_planilla' => (!empty($company['firma_director_planilla']) && trim($company['firma_director_planilla']) !== '')
                    ? $company['firma_director_planilla']
                    : ($company['legal_representative'] ?? ''),
                'cargo_director_planilla' => (!empty($company['cargo_director_planilla']) && trim($company['cargo_director_planilla']) !== '')
                    ? $company['cargo_director_planilla']
                    : '',
                'firma_contador_planilla' => (!empty($company['firma_contador_planilla']) && trim($company['firma_contador_planilla']) !== '')
                    ? $company['firma_contador_planilla']
                    : '',
                'cargo_contador_planilla' => (!empty($company['cargo_contador_planilla']) && trim($company['cargo_contador_planilla']) !== '')
                    ? $company['cargo_contador_planilla']
                    : '',
                'logo_empresa' => $company['logo_empresa'] ?? '',
                'logo_izquierdo_reportes' => $company['logo_izquierdo_reportes'] ?? '',
                'logo_derecho_reportes' => $company['logo_derecho_reportes'] ?? ''
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
                'firma_director_planilla' => '',
                'cargo_director_planilla' => '',
                'firma_contador_planilla' => '',
                'cargo_contador_planilla' => '',
                'logo_empresa' => '',
                'logo_izquierdo_reportes' => '',
                'logo_derecho_reportes' => ''
            ];
        }
    }

    /**
     * Insertar logos en el PDF alineados con el título
     */
    public function insertLogosInPDF($pdf, $companyInfo)
    {
        $logoPath = __DIR__ . '/../../images/logos/';
        $logoHeight = 10;
        $pageWidth = $pdf->getPageWidth();
        $margin = 10;

        // Guardar posición actual
        $currentY = $pdf->GetY();

        // Logo izquierdo
        if (!empty($companyInfo['logo_izquierdo_reportes'])) {
            $leftLogoPath = $logoPath . $companyInfo['logo_izquierdo_reportes'];
            if (file_exists($leftLogoPath)) {
                $leftLogoWidth = 30; // Mismo tamaño que el logo derecho
                try {
                    $pdf->Image($leftLogoPath, $margin, $currentY, $leftLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                } catch (\Exception $e) {
                    error_log("Error cargando logo izquierdo: " . $e->getMessage());
                }
            }
        }

        // Logo derecho
        if (!empty($companyInfo['logo_derecho_reportes'])) {
            $rightLogoPath = $logoPath . $companyInfo['logo_derecho_reportes'];
            if (file_exists($rightLogoPath)) {
                $rightLogoWidth = 30;
                $rightX = $pageWidth - $margin - $rightLogoWidth;
                try {
                    $pdf->Image($rightLogoPath, $rightX, $currentY, $rightLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                } catch (\Exception $e) {
                    error_log("Error cargando logo derecho: " . $e->getMessage());
                }
            }
        }

        // Logo principal (centro) - solo si no hay logos laterales
        if (empty($companyInfo['logo_izquierdo_reportes']) && empty($companyInfo['logo_derecho_reportes']) && !empty($companyInfo['logo_empresa'])) {
            $mainLogoPath = $logoPath . $companyInfo['logo_empresa'];
            if (file_exists($mainLogoPath)) {
                $mainLogoWidth = 40;
                $centerX = ($pageWidth - $mainLogoWidth) / 2;
                $pdf->Image($mainLogoPath, $centerX, $currentY, $mainLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
            }
        }

        // Nombre de la empresa centrado
        if (!empty($companyInfo['company_name'])) {
            $currentFont = $pdf->getFontFamily();
            $currentSize = $pdf->getFontSizePt();

            $pdf->SetFont('helvetica', 'B', 16);
            $companyNameWidth = $pdf->GetStringWidth($companyInfo['company_name']);
            $centerX = ($pageWidth - $companyNameWidth) / 2;
            $textY = $currentY + ($logoHeight / 2) - 3;

            $pdf->SetXY($centerX, $textY);
            $pdf->Cell($companyNameWidth, 0, $companyInfo['company_name'], 0, 0, 'C');

            $pdf->SetFont($currentFont, '', $currentSize);
        }

        // Reservar espacio después de los logos
        if (!empty($companyInfo['logo_izquierdo_reportes']) || !empty($companyInfo['logo_derecho_reportes']) || !empty($companyInfo['logo_empresa'])) {
            $pdf->SetY(20);
        }
    }
}