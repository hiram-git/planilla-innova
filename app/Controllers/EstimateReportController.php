<?php

namespace App\Controllers;

use App\Core\TenantStorage;

/**
 * Controlador de Reportes de Estimados Anuales
 * Extiende ReportController para mantener acceso a métodos compartidos
 */
class EstimateReportController extends ReportController
{
    /**
     * Estimado anual de liquidaciones
     * Calcula el monto estimado de liquidación para todos los empleados activos
     * sin que estén dados de baja
     */
    public function estimadoAnualLiquidaciones()
    {
        try {
            $this->requireAuth();

            // Obtener todos los empleados activos (no terminados)
            $sql = "SELECT
                        e.id,
                        e.employee_id,
                        e.document_id,
                        e.firstname,
                        e.lastname,
                        e.fecha_ingreso,
                        e.sueldo_individual,
                        e.tipo_planilla_id,
                        IFNULL(c.nombre, 'Sin Cargo') as position_name,
                        DATEDIFF(CURDATE(), e.fecha_ingreso) / 365 as years_worked
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos c ON p.id_cargo = c.id
                    WHERE e.id NOT IN (
                        SELECT employee_id FROM employee_terminations WHERE status IN ('CALCULADA', 'PROCESADA')
                    )
                    AND e.fecha_ingreso IS NOT NULL
                    ORDER BY e.lastname, e.firstname";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $employees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($employees)) {
                $_SESSION['error'] = 'No hay empleados activos para calcular estimado';
                $this->redirect('/panel/reports');
                return;
            }

            // Obtener calculadora de liquidación (está en Services, no en Models)
            $calculatorModel = new \App\Services\PlanillaConceptCalculator();

            // Obtener frecuencia de liquidación dinámicamente
            $liquidationController = new \App\Controllers\LiquidationController();
            $liquidation_frequency_id = $liquidationController->getLiquidationFrequencyId();

            if (!$liquidation_frequency_id) {
                $_SESSION['error'] = 'No se pudo obtener la frecuencia de liquidación';
                $this->redirect('/panel/reports');
                return;
            }

            // Array para almacenar cálculos de todos los empleados
            $estimates = [];
            $total_general_asignaciones = 0;
            $total_general_deducciones = 0;
            $total_general_neto = 0;

            foreach ($employees as $employee) {
                // Simular variables de liquidación para el empleado
                // Usar fecha actual como fecha de terminación hipotética
                $today = date('Y-m-d');

                // Configurar calculadora con variables del empleado
                // Nota: No existe termination_id, así que pasamos NULL o 0
                $calculatorModel->setVariablesColaborador(
                    $employee['id'],
                    $employee['tipo_planilla_id'] ? explode(',', $employee['tipo_planilla_id'])[0] : 1,
                    $today,
                    $today
                );

                // Establecer variables específicas de liquidación manualmente
                $fecha_ingreso = new \DateTime($employee['fecha_ingreso']);
                $fecha_simulada_terminacion = new \DateTime($today);
                $total_years = $fecha_ingreso->diff($fecha_simulada_terminacion)->y;
                $total_months = $fecha_ingreso->diff($fecha_simulada_terminacion)->m;
                $total_days = $fecha_ingreso->diff($fecha_simulada_terminacion)->d;

                // Calcular total de días trabajados
                $dias_trabajados = $fecha_ingreso->diff($fecha_simulada_terminacion)->days;

                // Establecer solo variables numéricas necesarias para liquidación
                $calculatorModel->setVariable('ANOS_TRABAJADOS', (float)$total_years);
                $calculatorModel->setVariable('MESES_TRABAJADOS', (float)$total_months);
                $calculatorModel->setVariable('DIAS_TRABAJADOS', (float)$dias_trabajados);
                $calculatorModel->setVariable('DIAS_PREAVISO', 0); // Estimado sin preaviso

                // Establecer salarios calculados para liquidaciones
                $calculatorModel->setVariable('SUELDO_SEMANAL', (float)($employee['sueldo_individual'] / 4.33));
                $calculatorModel->setVariable('SUELDO_MENSUAL', (float)$employee['sueldo_individual']);
                $calculatorModel->setVariable('SUELDO_DIARIO', (float)($employee['sueldo_individual'] / 30));

                // Obtener TODOS los conceptos y luego validar con validateConceptConditions
                $sql = "SELECT id, concepto, descripcion, tipo_concepto, formula, valor_fijo, monto_calculo, monto_cero
                        FROM concepto
                        ORDER BY tipo_concepto, concepto";

                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $allConcepts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // Simular datos de planilla para validación
                $employee_tipo_planilla_id = !empty($employee['tipo_planilla_id'])
                    ? explode(',', $employee['tipo_planilla_id'])[0]
                    : null;

                $payrollSimulation = [
                    'tipo_planilla_id' => $employee_tipo_planilla_id,
                    'frecuencia_id' => $liquidation_frequency_id
                ];

                // Obtener modelo de Payroll para usar validateConceptConditions
                $payrollModel = new \App\Models\Payroll();

                // Filtrar conceptos usando validateConceptConditions
                $concepts = [];
                foreach ($allConcepts as $concept) {
                    // Para estimado de liquidaciones, NO validar situación (son empleados activos simulando baja)
                    // Pasar validateSituacion = false para omitir validación de situación
                    if ($payrollModel->validateConceptConditions($concept, $payrollSimulation, 1, false)) {
                        $concepts[] = $concept;
                    }
                }

                $employee_calculations = [];
                $total_asignaciones = 0;
                $total_deducciones = 0;

                foreach ($concepts as $concept) {
                    try {
                        // Calcular monto según fórmula
                        $amount = $calculatorModel->evaluarFormula($concept['formula']);

                        if ($concept['tipo_concepto'] === 'ASIGNACION' || $concept['tipo_concepto'] === 'A') {
                            $total_asignaciones += $amount;
                        } elseif ($concept['tipo_concepto'] === 'DEDUCCION' || $concept['tipo_concepto'] === 'D') {
                            $total_deducciones += $amount;
                        }

                        $employee_calculations[] = [
                            'concept_code' => $concept['concepto'],
                            'concept_description' => $concept['descripcion'],
                            'amount' => $amount,
                            'type' => $concept['tipo_concepto']
                        ];
                    } catch (\Exception $e) {
                        error_log("Error calculando concepto {$concept['concepto']} para empleado {$employee['id']}: " . $e->getMessage());
                        // Continuar con el siguiente concepto
                    }
                }

                $neto = $total_asignaciones - $total_deducciones;

                $estimates[] = [
                    'employee' => $employee,
                    'calculations' => $employee_calculations,
                    'total_asignaciones' => $total_asignaciones,
                    'total_deducciones' => $total_deducciones,
                    'neto' => $neto
                ];

                $total_general_asignaciones += $total_asignaciones;
                $total_general_deducciones += $total_deducciones;
                $total_general_neto += $neto;
            }

            // Preparar datos para la vista
            $companyInfo = $this->getCompanyInfo();
            $signatures = $this->companyModel->getSignaturesForReports();

            $data = [
                'title' => 'Estimado Anual de Liquidaciones',
                'page_title' => 'Estimado Anual de Liquidaciones',
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Reportes', 'url' => '/panel/reports'],
                    ['name' => 'Estimado Anual Liquidaciones', 'url' => '#']
                ],
                'estimates' => $estimates,
                'total_general_asignaciones' => $total_general_asignaciones,
                'total_general_deducciones' => $total_general_deducciones,
                'total_general_neto' => $total_general_neto,
                'total_employees' => count($employees),
                'fecha_estimado' => date('d/m/Y'),
                'companyInfo' => $companyInfo,
                'signatures' => $signatures
            ];

            $this->view('admin/reports/estimado_liquidaciones', $data);

        } catch (\Exception $e) {
            error_log("Error en estimadoAnualLiquidaciones: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = 'Error al generar estimado de liquidaciones: ' . $e->getMessage();
            $this->redirect('/panel/reports');
        }
    }

    /**
     * Generar PDF del estimado anual de liquidaciones
     * Similar al reporte de planilla pero para estimado de liquidaciones
     */
    public function estimadoAnualLiquidacionesPdf()
    {
        try {
            $this->requireAuth();

            // Reutilizar la lógica de cálculo del estimado
            // Obtener todos los empleados activos (no terminados)
            $sql = "SELECT
                        e.id,
                        e.employee_id,
                        e.document_id,
                        e.firstname,
                        e.lastname,
                        e.fecha_ingreso,
                        e.sueldo_individual,
                        e.tipo_planilla_id,
                        IFNULL(c.nombre, 'Sin Cargo') as position_name,
                        DATEDIFF(CURDATE(), e.fecha_ingreso) / 365 as years_worked
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos c ON p.id_cargo = c.id
                    WHERE e.id NOT IN (
                        SELECT employee_id FROM employee_terminations WHERE status IN ('CALCULADA', 'PROCESADA')
                    )
                    AND e.fecha_ingreso IS NOT NULL
                    ORDER BY e.lastname, e.firstname";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $employees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($employees)) {
                $_SESSION['error'] = 'No hay empleados activos para calcular estimado';
                $this->redirect('/panel/reports/estimado-anual-liquidaciones');
                return;
            }

            // Obtener calculadora de liquidación
            $calculatorModel = new \App\Services\PlanillaConceptCalculator();

            // Obtener frecuencia de liquidación dinámicamente
            $liquidationController = new \App\Controllers\LiquidationController();
            $liquidation_frequency_id = $liquidationController->getLiquidationFrequencyId();

            if (!$liquidation_frequency_id) {
                $_SESSION['error'] = 'No se pudo obtener la frecuencia de liquidación';
                $this->redirect('/panel/reports/estimado-anual-liquidaciones');
                return;
            }

            // Array para almacenar cálculos de todos los empleados
            $estimates = [];
            $total_general_asignaciones = 0;
            $total_general_deducciones = 0;
            $total_general_neto = 0;

            foreach ($employees as $employee) {
                // Simular variables de liquidación para el empleado
                $today = date('Y-m-d');

                // Configurar calculadora con variables del empleado
                $calculatorModel->setVariablesColaborador(
                    $employee['id'],
                    $employee['tipo_planilla_id'] ? explode(',', $employee['tipo_planilla_id'])[0] : 1,
                    $today,
                    $today
                );

                // Establecer variables específicas de liquidación manualmente
                $fecha_ingreso = new \DateTime($employee['fecha_ingreso']);
                $fecha_simulada_terminacion = new \DateTime($today);
                $total_years = $fecha_ingreso->diff($fecha_simulada_terminacion)->y;
                $total_months = $fecha_ingreso->diff($fecha_simulada_terminacion)->m;
                $dias_trabajados = $fecha_ingreso->diff($fecha_simulada_terminacion)->days;

                // Establecer solo variables numéricas necesarias para liquidación
                $calculatorModel->setVariable('ANOS_TRABAJADOS', (float)$total_years);
                $calculatorModel->setVariable('MESES_TRABAJADOS', (float)$total_months);
                $calculatorModel->setVariable('DIAS_TRABAJADOS', (float)$dias_trabajados);
                $calculatorModel->setVariable('DIAS_PREAVISO', 0);

                // Establecer salarios calculados para liquidaciones
                $calculatorModel->setVariable('SUELDO_SEMANAL', (float)($employee['sueldo_individual'] / 4.33));
                $calculatorModel->setVariable('SUELDO_MENSUAL', (float)$employee['sueldo_individual']);
                $calculatorModel->setVariable('SUELDO_DIARIO', (float)($employee['sueldo_individual'] / 30));

                // Obtener TODOS los conceptos y luego validar con validateConceptConditions
                $sql = "SELECT id, concepto, descripcion, tipo_concepto, formula, valor_fijo, monto_calculo, monto_cero
                        FROM concepto
                        ORDER BY tipo_concepto, concepto";

                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $allConcepts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // Simular datos de planilla para validación
                $employee_tipo_planilla_id = !empty($employee['tipo_planilla_id'])
                    ? explode(',', $employee['tipo_planilla_id'])[0]
                    : null;

                $payrollSimulation = [
                    'tipo_planilla_id' => $employee_tipo_planilla_id,
                    'frecuencia_id' => $liquidation_frequency_id
                ];

                // Obtener modelo de Payroll para usar validateConceptConditions
                $payrollModel = new \App\Models\Payroll();

                // Filtrar conceptos usando validateConceptConditions
                $concepts = [];
                foreach ($allConcepts as $concept) {
                    // Para estimado de liquidaciones, NO validar situación (son empleados activos simulando baja)
                    // Pasar validateSituacion = false para omitir validación de situación
                    if ($payrollModel->validateConceptConditions($concept, $payrollSimulation, 1, false)) {
                        $concepts[] = $concept;
                    }
                }

                $employee_calculations = [];
                $total_asignaciones = 0;
                $total_deducciones = 0;

                foreach ($concepts as $concept) {
                    try {
                        // Calcular monto según fórmula
                        $amount = $calculatorModel->evaluarFormula($concept['formula']);

                        if ($concept['tipo_concepto'] === 'ASIGNACION' || $concept['tipo_concepto'] === 'A') {
                            $total_asignaciones += $amount;
                        } elseif ($concept['tipo_concepto'] === 'DEDUCCION' || $concept['tipo_concepto'] === 'D') {
                            $total_deducciones += $amount;
                        }

                        $employee_calculations[] = [
                            'concept_code' => $concept['concepto'],
                            'concept_description' => $concept['descripcion'],
                            'amount' => $amount,
                            'type' => $concept['tipo_concepto']
                        ];
                    } catch (\Exception $e) {
                        error_log("Error calculando concepto {$concept['concepto']} para empleado {$employee['id']}: " . $e->getMessage());
                    }
                }

                $neto = $total_asignaciones - $total_deducciones;

                $estimates[] = [
                    'employee' => $employee,
                    'calculations' => $employee_calculations,
                    'total_asignaciones' => $total_asignaciones,
                    'total_deducciones' => $total_deducciones,
                    'neto' => $neto
                ];

                $total_general_asignaciones += $total_asignaciones;
                $total_general_deducciones += $total_deducciones;
                $total_general_neto += $neto;
            }

            // Obtener información de la empresa
            $companyInfo = $this->getCompanyInfo();

            // Preparar datos para el PDF
            $estimateData = [
                'estimates' => $estimates,
                'total_general_asignaciones' => $total_general_asignaciones,
                'total_general_deducciones' => $total_general_deducciones,
                'total_general_neto' => $total_general_neto,
                'total_employees' => count($employees),
                'fecha_estimado' => date('d/m/Y')
            ];

            // Generar PDF usando PDFReportController
            $this->generateEstimadoLiquidacionesPDF($estimateData, $companyInfo);

        } catch (\Exception $e) {
            error_log("Error en estimadoAnualLiquidacionesPdf: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = 'Error al generar PDF de estimado de liquidaciones: ' . $e->getMessage();
            $this->redirect('/panel/reports/estimado-anual-liquidaciones');
        }
    }

    /**
     * Generar el documento PDF del estimado de liquidaciones
     * Usando la clase PDFReportController como base
     */
    protected function generateEstimadoLiquidacionesPDF($estimateData, $companyInfo)
    {
        // Requerir TCPDF
        require_once 'vendor/autoload.php';

        // Crear instancia de TCPDF en orientación horizontal
        $pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Desactivar header y footer por defecto
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Configuración del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor($companyInfo['company_name']);
        $pdf->SetTitle('Estimado Anual de Liquidaciones');
        $pdf->SetSubject('Reporte de Estimado de Liquidaciones');

        // Configuración de la página
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->AddPage();

        // Header del estimado
        $this->addEstimadoPDFHeader($pdf, $companyInfo, $estimateData);

        // Tabla de empleados con estimados
        $this->addEstimadoTable($pdf, $estimateData['estimates']);

        // Firmas de responsables (reutilizando método de PDFReportController)
        $this->addEstimadoSignatures($pdf, $companyInfo);

        // Output del PDF
        $filename = 'estimado_liquidaciones_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I'); // I = inline browser
        exit;
    }

    /**
     * Agregar header del PDF de estimado
     */
    protected function addEstimadoPDFHeader($pdf, $companyInfo, $estimateData)
    {
        // Insertar logos y nombre de empresa (reutilizando método)
        $this->insertLogosInPDF($pdf, $companyInfo);

        // Título del reporte
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 5, 'ESTIMADO ANUAL DE LIQUIDACIONES', 0, 1, 'C');

        // Información del estimado
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 4, 'Fecha del Estimado: ' . $estimateData['fecha_estimado'] . ' | Empleados Incluidos: ' . $estimateData['total_employees'], 0, 1, 'C');

        $pdf->Ln(3);

        // Headers de la tabla
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(255, 193, 7); // Fondo amarillo/naranja para destacar que es estimado

        // Anchos de columna para orientación horizontal
        $colWidths = [60, 25, 25, 20, 30, 30, 30];

        $pdf->Cell($colWidths[0], 6, 'Empleado', 1, 0, 'C', true);
        $pdf->Cell($colWidths[1], 6, 'Cédula', 1, 0, 'C', true);
        $pdf->Cell($colWidths[2], 6, 'Cargo', 1, 0, 'C', true);
        $pdf->Cell($colWidths[3], 6, 'Años', 1, 0, 'C', true);
        $pdf->Cell($colWidths[4], 6, 'Asignaciones', 1, 0, 'C', true);
        $pdf->Cell($colWidths[5], 6, 'Deducciones', 1, 0, 'C', true);
        $pdf->Cell($colWidths[6], 6, 'Neto Estimado', 1, 1, 'C', true);
    }

    /**
     * Agregar tabla de estimados de empleados
     */
    protected function addEstimadoTable($pdf, $estimates)
    {
        // Anchos de columna
        $colWidths = [60, 25, 25, 20, 30, 30, 30];

        // Datos de empleados
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetFillColor(255, 255, 255);

        $totalGeneral = [
            'asignaciones' => 0,
            'deducciones' => 0,
            'neto' => 0
        ];

        foreach ($estimates as $estimate) {
            $employee = $estimate['employee'];
            $years_worked = number_format($employee['years_worked'], 1);

            // Nombre completo (truncado si es muy largo)
            $nombreCompleto = $employee['lastname'] . ', ' . $employee['firstname'];
            if (strlen($nombreCompleto) > 40) {
                $nombreCompleto = substr($nombreCompleto, 0, 37) . '...';
            }

            // Cargo truncado
            $cargo = $employee['position_name'];
            if (strlen($cargo) > 20) {
                $cargo = substr($cargo, 0, 17) . '...';
            }

            $pdf->Cell($colWidths[0], 6, $nombreCompleto, 1, 0, 'L');
            $pdf->Cell($colWidths[1], 6, $employee['document_id'] ?? 'N/A', 1, 0, 'C');
            $pdf->Cell($colWidths[2], 6, $cargo, 1, 0, 'L');
            $pdf->Cell($colWidths[3], 6, $years_worked, 1, 0, 'C');
            $pdf->Cell($colWidths[4], 6, '$' . number_format($estimate['total_asignaciones'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[5], 6, '$' . number_format($estimate['total_deducciones'], 2), 1, 0, 'R');
            $pdf->Cell($colWidths[6], 6, '$' . number_format($estimate['neto'], 2), 1, 0, 'R');
            $pdf->Ln();

            // Acumular totales
            $totalGeneral['asignaciones'] += $estimate['total_asignaciones'];
            $totalGeneral['deducciones'] += $estimate['total_deducciones'];
            $totalGeneral['neto'] += $estimate['neto'];
        }

        // Fila de totales
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(255, 193, 7); // Mismo color del header
        $pdf->Cell($colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3], 7, 'TOTAL GENERAL ESTIMADO', 1, 0, 'C', true);
        $pdf->Cell($colWidths[4], 7, '$' . number_format($totalGeneral['asignaciones'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[5], 7, '$' . number_format($totalGeneral['deducciones'], 2), 1, 0, 'R', true);
        $pdf->Cell($colWidths[6], 7, '$' . number_format($totalGeneral['neto'], 2), 1, 0, 'R', true);
        $pdf->Ln();
    }

    /**
     * Agregar firmas de responsables (reutilizando estructura de PDFReportController)
     */
    protected function addEstimadoSignatures($pdf, $companyInfo)
    {
        $pdf->Ln(8);

        // Determinar firmas
        $firmas = [];

        $firmas[] = [
            'nombre' => $companyInfo['elaborado_por'],
            'cargo' => $companyInfo['cargo_elaborador']
        ];

        $firmas[] = [
            'nombre' => $companyInfo['jefe_recursos_humanos'],
            'cargo' => $companyInfo['cargo_jefe_rrhh']
        ];

        if (!empty($companyInfo['firma_director_planilla']) && trim($companyInfo['firma_director_planilla']) !== '') {
            $firmas[] = [
                'nombre' => $companyInfo['firma_director_planilla'],
                'cargo' => $companyInfo['cargo_director_planilla']
            ];
        }

        $numFirmas = count($firmas);
        $colWidth = $numFirmas > 0 ? (270 / $numFirmas) : 90;
        $sigHeight = 15;

        // Primera fila de firmas
        $pdf->SetFont('helvetica', '', 9);

        for ($i = 0; $i < $numFirmas; $i++) {
            $pdf->Cell($colWidth, $sigHeight, '', 'B', ($i == $numFirmas - 1) ? 1 : 0, 'C');
            if ($i < $numFirmas - 1) {
                $pdf->Cell(5, $sigHeight, '', 0, 0, 'C');
            }
        }

        // Nombres bajo las líneas
        $pdf->SetFont('helvetica', 'B', 8);
        for ($i = 0; $i < $numFirmas; $i++) {
            $pdf->Cell($colWidth, 6, strtoupper($firmas[$i]['nombre']), 0, ($i == $numFirmas - 1) ? 1 : 0, 'C');
            if ($i < $numFirmas - 1) {
                $pdf->Cell(5, 6, '', 0, 0, 'C');
            }
        }

        // Cargos bajo los nombres
        $pdf->SetFont('helvetica', '', 7);
        for ($i = 0; $i < $numFirmas; $i++) {
            $pdf->Cell($colWidth, 5, $firmas[$i]['cargo'], 0, ($i == $numFirmas - 1) ? 1 : 0, 'C');
            if ($i < $numFirmas - 1) {
                $pdf->Cell(5, 5, '', 0, 0, 'C');
            }
        }

        $pdf->Ln(3);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 4, 'Fecha de Generación: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
    }

    /**
     * Insertar logos en el PDF (reutilizado de PDFReportController)
     */
    public function insertLogosInPDF($pdf, $companyInfo)
    {
        $logoHeight = 10;
        $pageWidth = $pdf->getPageWidth();
        $margin = 10;

        $currentY = $pdf->GetY();

        // Logo izquierdo
        if (!empty($companyInfo['logo_izquierdo_reportes'])) {
            $leftLogoPath = TenantStorage::getLogoFilesystemPath($companyInfo['logo_izquierdo_reportes']);
            if ($leftLogoPath) {
                $leftLogoWidth = 20;
                try {
                    $pdf->Image($leftLogoPath, $margin, $currentY, $leftLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                } catch (\Exception $e) {
                    error_log("Error cargando logo izquierdo: " . $e->getMessage());
                }
            }
        }

        // Logo derecho
        if (!empty($companyInfo['logo_derecho_reportes'])) {
            $rightLogoPath = TenantStorage::getLogoFilesystemPath($companyInfo['logo_derecho_reportes']);
            if ($rightLogoPath) {
                $rightLogoWidth = 30;
                $rightX = $pageWidth - $margin - $rightLogoWidth;
                try {
                    $pdf->Image($rightLogoPath, $rightX, $currentY, $rightLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                } catch (\Exception $e) {
                    error_log("Error cargando logo derecho: " . $e->getMessage());
                }
            }
        }

        // Logo principal centro
        if (empty($companyInfo['logo_izquierdo_reportes']) && empty($companyInfo['logo_derecho_reportes']) && !empty($companyInfo['logo_empresa'])) {
            $mainLogoPath = TenantStorage::getLogoFilesystemPath($companyInfo['logo_empresa']);
            if ($mainLogoPath) {
                $mainLogoWidth = 40;
                $centerX = ($pageWidth - $mainLogoWidth) / 2;
                $pdf->Image($mainLogoPath, $centerX, $currentY, $mainLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
            }
        }

        // Nombre de empresa centrado
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

        // Reservar espacio después de logos
        if (!empty($companyInfo['logo_izquierdo_reportes']) || !empty($companyInfo['logo_derecho_reportes']) || !empty($companyInfo['logo_empresa'])) {
            $pdf->SetY(20);
        }
    }


}
