<?php

namespace App\Controllers;

use TCPDF;
use App\Core\TenantStorage;

/**
 * Generador de Reportes PDF para Asientos Contables
 * Genera PDF profesional de Asientos de Planilla y Cuota Patronal
 *
 * @extends PDFReportController
 * @version 1.0.0
 * @author Sistema de Planillas MVC
 */
class AsientosContablesPDFGenerator extends PDFReportController
{
    /**
     * Generar PDF de Asientos Contables
     * Incluye Asiento de Planilla y Asiento de Cuota Patronal
     *
     * @param int $payrollId ID de la planilla
     * @return void
     */
    public function generateAsientosContablesPDF($payrollId)
    {
        try {
            $this->requireAuth();

            if (!$payrollId) {
                $_SESSION['error'] = 'ID de planilla requerido';
                $this->redirect('/panel/reports');
                return;
            }

            // Obtener datos de la planilla usando el método heredado
            $planillaData = $this->getPayrollReportDataWithBankInfo($payrollId);

            if (!$planillaData) {
                error_log("AsientosContablesPDFGenerator: Planilla no encontrada para ID: $payrollId");
                $_SESSION['error'] = 'Planilla no encontrada';
                $this->redirect('/panel/reports');
                return;
            }

            error_log("AsientosContablesPDFGenerator: Planilla encontrada, empleados: " . count($planillaData['employees'] ?? []));

            // Obtener información de la empresa
            $companyInfo = $this->getCompanyInfo();

            // Generar PDF con ambos asientos
            $this->generateAsientosContablesReport($planillaData, $companyInfo);

        } catch (\Exception $e) {
            error_log('Error generando PDF de asientos contables: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $_SESSION['error'] = 'Error al generar el reporte PDF: ' . $e->getMessage();
            $this->redirect('/panel/reports');
        }
    }

    /**
     * Obtener datos de la planilla incluyendo información bancaria de empleados
     * Reutiliza la lógica de PlanillaContableExcelGenerator
     *
     * @param int $payrollId
     * @return array|null
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
                error_log("AsientosContablesPDFGenerator: No se encontró planilla con ID: $payrollId");
                return null;
            }

            error_log("AsientosContablesPDFGenerator: Planilla encontrada: " . $payroll['descripcion']);

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
                        pd.referencia_valor as reference_value,
                        c.concepto AS concepto_codigo,
                        c.descripcion AS concepto_descripcion,
                        c.tipo_concepto,
                        c.categoria_reporte
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
                        c.orden_reporte ASC";

            $stmt = $connection->prepare($sql);
            $stmt->execute([$payrollId]);
            $conceptsData = $stmt->fetchAll();

            error_log("AsientosContablesPDFGenerator: Conceptos encontrados: " . count($conceptsData));

            // Organizar datos por empleado
            $employees = [];
            foreach ($conceptsData as $row) {
                $empKey = $row['employee_id'];

                if (!isset($employees[$empKey])) {
                    $employees[$empKey] = [
                        'id' => $row['employee_id'],
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
                if ($row['tipo_concepto'] == 'A') {
                    $employees[$empKey]['totals']['ingresos'] += $monto;
                } elseif ($row['tipo_concepto'] == 'D') {
                    $employees[$empKey]['totals']['deducciones'] += $monto;
                }

                // Calcular neto
                $employees[$empKey]['totals']['neto'] =
                    $employees[$empKey]['totals']['ingresos'] - $employees[$empKey]['totals']['deducciones'];
            }

            error_log("AsientosContablesPDFGenerator: Total empleados a retornar: " . count($employees));

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
     * Generar reporte PDF completo con ambos asientos
     *
     * @param array $planillaData
     * @param array $companyInfo
     * @return void
     */
    private function generateAsientosContablesReport($planillaData, $companyInfo)
    {
        $payroll = $planillaData['payroll'];
        $employees = $planillaData['employees'] ?? [];

        // Crear instancia de TCPDF sin tamaño fijo (se configurará por página)
        $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);

        // Configurar documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor($companyInfo['company_name']);
        $pdf->SetTitle('Asientos Contables - ' . $payroll['descripcion']);
        $pdf->SetSubject('Asientos Contables de Planilla');

        // Configurar márgenes
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(true, 15);

        // Deshabilitar header y footer por defecto
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // PÁGINA 1: Planilla Completa (Extra Oficio Horizontal = Legal Landscape)
        // Legal: 216 x 356 mm (horizontal)
        $pdf->AddPage('L', 'LEGAL'); // 'L' = Landscape, 'LEGAL' = Extra Oficio
        $this->renderPlanillaCompletaPDF($pdf, $payroll, $employees, $companyInfo);

        // PÁGINA 2: Asiento de Planilla (Carta Vertical = Letter Portrait)
        // Letter: 216 x 279 mm (vertical)
        $pdf->AddPage('P', 'LETTER'); // 'P' = Portrait, 'LETTER' = Carta
        $this->renderAsientoPlanillaPDF($pdf, $payroll, $employees, $companyInfo);

        // PÁGINA 3: Asiento de Cuota Patronal (Carta Vertical = Letter Portrait)
        $pdf->AddPage('P', 'LETTER');
        $this->renderAsientoCuotaPatronalPDF($pdf, $payroll, $employees, $companyInfo);

        // Nombre del archivo
        $filename = 'Asientos_Contables_Planilla_' . $payroll['id'] . '_' . date('Y-m-d') . '.pdf';

        // Output del PDF
        $pdf->Output($filename, 'I');
        exit;
    }

    /**
     * Renderizar página de Asiento de Planilla
     *
     * @param TCPDF $pdf
     * @param array $payroll
     * @param array $employees
     * @param array $companyInfo
     * @return void
     */
    private function renderAsientoPlanillaPDF($pdf, $payroll, $employees, $companyInfo)
    {
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_inicio']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_fin']));
        $currencySymbol = $companyInfo['currency_symbol'] ?? '$';

        $y = 15;

        // Header de la empresa
        $pdf->SetFillColor(68, 114, 196); // Azul
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 10, $companyInfo['company_name'], 0, 1, 'C', true);
        $y += 10;

        // Subtítulo - usando MultiCell para texto largo
        $pdf->SetFillColor(143, 170, 220); // Azul claro
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(15, $y);

        // Calcular altura necesaria para el título
        $titulo = 'ASIENTO DE PLANILLA - ' . strtoupper($payroll['descripcion']);
        $pdf->MultiCell(180, 7, $titulo, 0, 'C', true, 1);
        $y = $pdf->GetY();

        // Período
        $pdf->SetFillColor(217, 226, 243); // Azul muy claro
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 7, 'Período: ' . $fechaInicio . ' al ' . $fechaFin, 0, 1, 'C', true);
        $y += 10;

        // Headers de tabla
        $pdf->SetFillColor(112, 173, 71); // Verde
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(15, $y);

        $pdf->Cell(15, 8, 'No.', 1, 0, 'C', true);
        $pdf->Cell(105, 8, 'NOMBRE DE CUENTA', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'DÉBITO', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'CRÉDITO', 1, 0, 'C', true);
        $y += 8;

        // Calcular totales
        $totalSueldos = 0;
        $totalDeducciones = 0;

        foreach ($employees as $emp) {
            $sueldoQuincenal = ($emp['salary'] ?? 0) / 2;

            // Buscar ausencias y tardanzas
            $ausencias = 0;
            $tardanzas = 0;

            foreach ($emp['concepts'] as $concept) {
                $codigo = strtoupper($concept['codigo'] ?? '');
                $descripcion = strtoupper($concept['descripcion'] ?? '');

                if ($concept['tipo'] == 'D') {
                    $monto = $concept['monto'] ?? 0;

                    if (strpos($codigo, 'AUSENCIA') !== false || strpos($descripcion, 'AUSENCIA') !== false) {
                        $ausencias += $monto;
                    } elseif (strpos($codigo, 'TARDAN') !== false || strpos($descripcion, 'TARDAN') !== false) {
                        $tardanzas += $monto;
                    } else {
                        $totalDeducciones += $monto;
                    }
                }
            }

            $salarioNeto = $sueldoQuincenal - $ausencias - $tardanzas;
            $totalSueldos += $salarioNeto;
        }

        $credito = $totalSueldos;
        $debito = $totalDeducciones;
        $sueldosPorPagar = $credito - $debito;

        // Resetear colores para datos
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 10);

        // Línea 1: Gasto de Sueldos
        $pdf->SetXY(15, $y);
        $pdf->Cell(15, 7, '1', 1, 0, 'C');
        $pdf->Cell(105, 7, 'Gasto de Sueldos', 1, 0, 'L');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format($credito, 2), 1, 0, 'R');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format(0, 2), 1, 0, 'R');
        $y += 7;

        // Línea 2: Deducciones (si hay)
        if ($debito > 0) {
            $pdf->SetXY(15, $y);
            $pdf->Cell(15, 7, '2', 1, 0, 'C');
            $pdf->Cell(105, 7, 'Deducciones por Pagar', 1, 0, 'L');
            $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format(0, 2), 1, 0, 'R');
            $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format($debito, 2), 1, 0, 'R');
            $y += 7;
        }

        // Línea 3: Sueldos por Pagar
        $pdf->SetXY(15, $y);
        $lineNo = ($debito > 0) ? '3' : '2';
        $pdf->Cell(15, 7, $lineNo, 1, 0, 'C');
        $pdf->Cell(105, 7, 'Sueldos por Pagar', 1, 0, 'L');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format(0, 2), 1, 0, 'R');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format($sueldosPorPagar, 2), 1, 0, 'R');
        $y += 10;

        // Totales
        $pdf->SetFillColor(197, 224, 180); // Verde claro
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(15, $y);
        $pdf->Cell(120, 8, 'TOTALES', 1, 0, 'R', true);
        $pdf->Cell(30, 8, $currencySymbol . ' ' . number_format($credito, 2), 1, 0, 'R', true);
        $pdf->Cell(30, 8, $currencySymbol . ' ' . number_format($credito, 2), 1, 0, 'R', true);
    }

    /**
     * Renderizar página de Asiento de Cuota Patronal
     *
     * @param TCPDF $pdf
     * @param array $payroll
     * @param array $employees
     * @param array $companyInfo
     * @return void
     */
    private function renderAsientoCuotaPatronalPDF($pdf, $payroll, $employees, $companyInfo)
    {
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_inicio']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_fin']));
        $currencySymbol = $companyInfo['currency_symbol'] ?? '$';

        $y = 15;

        // Header de la empresa
        $pdf->SetFillColor(68, 114, 196); // Azul
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 10, $companyInfo['company_name'], 0, 1, 'C', true);
        $y += 10;

        // Subtítulo - usando MultiCell para texto largo
        $pdf->SetFillColor(255, 192, 0); // Naranja/Amarillo
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(15, $y);

        // Calcular altura necesaria para el título
        $titulo = 'ASIENTO DE CUOTA PATRONAL - ' . strtoupper($payroll['descripcion']);
        $pdf->MultiCell(180, 7, $titulo, 0, 'C', true, 1);
        $y = $pdf->GetY();

        // Período
        $pdf->SetFillColor(217, 226, 243); // Azul muy claro
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 7, 'Período: ' . $fechaInicio . ' al ' . $fechaFin, 0, 1, 'C', true);
        $y += 10;

        // Headers de tabla
        $pdf->SetFillColor(255, 192, 0); // Naranja/Amarillo
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(15, $y);

        $pdf->Cell(15, 8, 'No.', 1, 0, 'C', true);
        $pdf->Cell(105, 8, 'NOMBRE DE CUENTA', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'DÉBITO', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'CRÉDITO', 1, 0, 'C', true);
        $y += 8;

        // Calcular total de salarios
        $totalSalarios = 0;
        foreach ($employees as $emp) {
            $totalSalarios += $emp['salary'] ?? 0;
        }

        // Calcular cuotas patronales según legislación panameña
        $gastoSeguroSocial = $totalSalarios * 0.1225; // 12.25%
        $gastoSeguroEducativo = $totalSalarios * 0.015; // 1.5%
        $gastoRiesgosProfesionales = $totalSalarios * 0.021; // 2.1%
        $cajaSeguroSocialPorPagar = $gastoSeguroSocial + $gastoSeguroEducativo + $gastoRiesgosProfesionales;

        // Resetear colores para datos
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 10);

        // Línea 1: Gasto de Seguro Social
        $pdf->SetXY(15, $y);
        $pdf->Cell(15, 7, '1', 1, 0, 'C');
        $pdf->Cell(105, 7, 'Gasto de Seguro Social', 1, 0, 'L');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format($gastoSeguroSocial, 2), 1, 0, 'R');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format(0, 2), 1, 0, 'R');
        $y += 7;

        // Línea 2: Gasto de Seguro Educativo
        $pdf->SetXY(15, $y);
        $pdf->Cell(15, 7, '2', 1, 0, 'C');
        $pdf->Cell(105, 7, 'Gasto de Seguro Educativo', 1, 0, 'L');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format($gastoSeguroEducativo, 2), 1, 0, 'R');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format(0, 2), 1, 0, 'R');
        $y += 7;

        // Línea 3: Gasto de Riesgos Profesionales
        $pdf->SetXY(15, $y);
        $pdf->Cell(15, 7, '3', 1, 0, 'C');
        $pdf->Cell(105, 7, 'Gasto de Riesgos Profesionales', 1, 0, 'L');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format($gastoRiesgosProfesionales, 2), 1, 0, 'R');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format(0, 2), 1, 0, 'R');
        $y += 7;

        // Línea 4: Caja de Seguro Social por Pagar
        $pdf->SetXY(15, $y);
        $pdf->Cell(15, 7, '4', 1, 0, 'C');
        $pdf->Cell(105, 7, 'Caja de Seguro Social por Pagar', 1, 0, 'L');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format(0, 2), 1, 0, 'R');
        $pdf->Cell(30, 7, $currencySymbol . ' ' . number_format($cajaSeguroSocialPorPagar, 2), 1, 0, 'R');
        $y += 10;

        // Totales
        $pdf->SetFillColor(255, 217, 102); // Amarillo claro
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(15, $y);
        $pdf->Cell(120, 8, 'TOTALES', 1, 0, 'R', true);
        $pdf->Cell(30, 8, $currencySymbol . ' ' . number_format($cajaSeguroSocialPorPagar, 2), 1, 0, 'R', true);
        $pdf->Cell(30, 8, $currencySymbol . ' ' . number_format($cajaSeguroSocialPorPagar, 2), 1, 0, 'R', true);
        $y += 12;

        // Nota legal
        $pdf->SetFont('helvetica', 'BI', 10);
        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 5, 'NOTA: Los porcentajes aplicados son según el Código de Trabajo de Panamá:', 0, 1, 'L');
        $y += 5;

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 5, '• Seguro Social Patronal: 12.25% del salario base', 0, 1, 'L');
        $y += 4;

        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 5, '• Seguro Educativo Patronal: 1.5% del salario base', 0, 1, 'L');
        $y += 4;

        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 5, '• Riesgo Profesional: 2.1% del salario base', 0, 1, 'L');
    }

    /**
     * Renderizar página de Planilla Completa (horizontal)
     * Incluye todos los datos de empleados y conceptos
     *
     * @param TCPDF $pdf
     * @param array $payroll
     * @param array $employees
     * @param array $companyInfo
     * @return void
     */
    private function renderPlanillaCompletaPDF($pdf, $payroll, $employees, $companyInfo)
    {
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_inicio']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_fin']));
        $currencySymbol = $companyInfo['currency_symbol'] ?? '$';

        // Configurar para orientación horizontal Legal (356mm ancho)
        $pdf->SetMargins(10, 10, 10);
        $y = 10;
        $anchoUtil = 336; // 356mm - 20mm márgenes (10+10)

        // Insertar logos y nombre de empresa
        $this->insertLogosInPDF($pdf, $companyInfo);
        $y = $pdf->GetY();

        // Título del reporte
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell($anchoUtil, 5, 'PLANILLA DE SUELDOS', 0, 1, 'C');
        $y = $pdf->GetY();

        // Descripción y período
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell($anchoUtil, 4, strtoupper($payroll['descripcion']), 0, 1, 'C');
        $y = $pdf->GetY();

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell($anchoUtil, 4, 'Período: ' . $fechaInicio . ' al ' . $fechaFin, 0, 1, 'C');
        $y = $pdf->GetY() + 3;

        // Headers de columnas - 18 columnas (sin Función, nombres unidos) - Optimizado para Legal
        // Total: 336mm disponibles (Legal horizontal 356mm - 20mm márgenes)
        $headers = [
            ['width' => 15, 'label' => 'Cédula'],
            ['width' => 50, 'label' => 'Nombre Completo'],
            ['width' => 19, 'label' => 'Puesto'],
            ['width' => 15, 'label' => 'Fecha Ing.'],
            ['width' => 15, 'label' => 'Sueldo'],
            ['width' => 18, 'label' => 'Forma Pago'],
            ['width' => 16, 'label' => 'Tipo Cta.'],
            ['width' => 19, 'label' => 'Núm. Cuenta'],
            ['width' => 14, 'label' => 'Días Lab.'],
            ['width' => 16, 'label' => 'Ausencias'],
            ['width' => 16, 'label' => 'Tardanzas'],
            ['width' => 19, 'label' => 'Tot. Ing.'],
            ['width' => 16, 'label' => 'Seg. Social'],
            ['width' => 16, 'label' => 'Seg. Educ.'],
            ['width' => 14, 'label' => 'ISR'],
            ['width' => 18, 'label' => 'Otras Ded.'],
            ['width' => 19, 'label' => 'Tot. Ded.'],
            ['width' => 19, 'label' => 'Salario Neto']
        ];

        $pdf->SetFillColor(220, 220, 220); // Gris claro (mismo color que planillaPdf)
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(10, $y);

        foreach ($headers as $header) {
            $pdf->Cell($header['width'], 6, $header['label'], 1, 0, 'C', true);
        }
        $y += 6;

        // Inicializar totales
        $totalesGenerales = [
            'dias_laborados' => 0,
            'ausencias' => 0,
            'tardanzas' => 0,
            'ingresos' => 0,
            'seguro_social' => 0,
            'seguro_educativo' => 0,
            'impuesto_renta' => 0,
            'otras_deducciones' => 0,
            'deducciones' => 0,
            'neto' => 0
        ];

        // Resetear colores para datos
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 6);

        // Datos de empleados
        foreach ($employees as $emp) {
            // Calcular totales del empleado
            $totales = $this->calculateEmployeeTotals($emp);

            // Acumular a totales generales
            foreach ($totales as $key => $value) {
                if (isset($totalesGenerales[$key])) {
                    $totalesGenerales[$key] += $value;
                }
            }

            $pdf->SetXY(10, $y);

            // Fila de datos - 18 columnas (optimizadas para Legal)
            $nombreCompleto = trim(($emp['firstname'] ?? '') . ' ' . ($emp['lastname'] ?? ''));

            $pdf->Cell(15, 5, $emp['document_id'] ?? 'N/A', 1, 0, 'C');
            $pdf->Cell(50, 5, substr($nombreCompleto, 0, 40), 1, 0, 'L');
            $pdf->Cell(19, 5, substr($emp['puesto_actual'] ?? 'N/A', 0, 24), 1, 0, 'L');
            $pdf->Cell(15, 5, !empty($emp['fecha_ingreso']) ? date('d/m/Y', strtotime($emp['fecha_ingreso'])) : 'N/A', 1, 0, 'C');
            $pdf->Cell(15, 5, number_format($emp['salary'] ?? 0, 2), 1, 0, 'R');
            $pdf->Cell(18, 5, substr($emp['forma_pago'] ?? 'EFECTIVO', 0, 12), 1, 0, 'C');
            $pdf->Cell(16, 5, substr($emp['tipo_cuenta'] ?? '', 0, 10), 1, 0, 'C');
            $pdf->Cell(19, 5, substr($emp['numero_cuenta'] ?? '', 0, 18), 1, 0, 'C');
            $pdf->Cell(14, 5, $totales['dias_laborados'], 1, 0, 'C');
            $pdf->Cell(16, 5, number_format($totales['ausencias'], 2), 1, 0, 'R');
            $pdf->Cell(16, 5, number_format($totales['tardanzas'], 2), 1, 0, 'R');
            $pdf->Cell(19, 5, number_format($totales['ingresos'], 2), 1, 0, 'R');
            $pdf->Cell(16, 5, number_format($totales['seguro_social'], 2), 1, 0, 'R');
            $pdf->Cell(16, 5, number_format($totales['seguro_educativo'], 2), 1, 0, 'R');
            $pdf->Cell(14, 5, number_format($totales['impuesto_renta'], 2), 1, 0, 'R');
            $pdf->Cell(18, 5, number_format($totales['otras_deducciones'], 2), 1, 0, 'R');
            $pdf->Cell(19, 5, number_format($totales['deducciones'], 2), 1, 0, 'R');
            $pdf->Cell(19, 5, number_format($totales['neto'], 2), 1, 0, 'R');

            $y += 5;

            // Si se acerca al final de la página, agregar nueva página Legal
            if ($y > 185) {
                $pdf->AddPage('L', 'LEGAL');
                $y = 10;

                // Repetir headers en nueva página
                $pdf->SetFillColor(220, 220, 220);
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetXY(10, $y);
                foreach ($headers as $header) {
                    $pdf->Cell($header['width'], 6, $header['label'], 1, 0, 'C', true);
                }
                $y += 6;
                $pdf->SetFont('helvetica', '', 7);
            }
        }

        // Fila de totales generales - 18 columnas (optimizadas para Legal)
        $pdf->SetFillColor(220, 220, 220); // Gris claro (mismo color que planillaPdf)
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(10, $y);

        // Merge: Cédula (24) + Nombre Completo (50) + Puesto (30) + Fecha Ing. (22) + Sueldo Base (20) + Forma Pago (18) + Tipo Cta. (16) + Núm. Cuenta (26) = 206
        $pdf->Cell(167, 6, 'TOTALES GENERALES', 1, 0, 'R', true);
        $pdf->Cell(14, 6, $totalesGenerales['dias_laborados'], 1, 0, 'C', true);
        $pdf->Cell(16, 6, $currencySymbol . number_format($totalesGenerales['ausencias'], 2), 1, 0, 'R', true);
        $pdf->Cell(16, 6, $currencySymbol . number_format($totalesGenerales['tardanzas'], 2), 1, 0, 'R', true);
        $pdf->Cell(19, 6, $currencySymbol . number_format($totalesGenerales['ingresos'], 2), 1, 0, 'R', true);
        $pdf->Cell(16, 6, $currencySymbol . number_format($totalesGenerales['seguro_social'], 2), 1, 0, 'R', true);
        $pdf->Cell(16, 6, $currencySymbol . number_format($totalesGenerales['seguro_educativo'], 2), 1, 0, 'R', true);
        $pdf->Cell(14, 6, $currencySymbol . number_format($totalesGenerales['impuesto_renta'], 2), 1, 0, 'R', true);
        $pdf->Cell(18, 6, $currencySymbol . number_format($totalesGenerales['otras_deducciones'], 2), 1, 0, 'R', true);
        $pdf->Cell(19, 6, $currencySymbol . number_format($totalesGenerales['deducciones'], 2), 1, 0, 'R', true);
        $pdf->Cell(19, 6, $currencySymbol . number_format($totalesGenerales['neto'], 2), 1, 0, 'R', true);

        // Agregar firmas de responsables
        $this->addSignatures($pdf, $companyInfo);

        // Restaurar márgenes normales para páginas siguientes
        $pdf->SetMargins(15, 15, 15);
    }

    /**
     * Calcular totales de un empleado para la planilla completa
     *
     * @param array $emp
     * @return array
     */
    private function calculateEmployeeTotals($emp)
    {
        // Los totales ya vienen calculados en la estructura del empleado
        $totals = $emp['totals'] ?? [];
        $diasLaborados = $emp['reference_value'] ?? 15; // Campo referencia como días laborados

        // Extraer ausencias y tardanzas de los conceptos
        $ausencias = 0;
        $tardanzas = 0;
        $seguroSocial = 0;
        $seguroEducativo = 0;
        $impuestoRenta = 0;
        $otrasDeducciones = 0;

        if (isset($emp['concepts']) && is_array($emp['concepts'])) {
            foreach ($emp['concepts'] as $concept) {
                $codigo = strtoupper($concept['codigo'] ?? '');
                $descripcion = strtoupper($concept['descripcion'] ?? '');
                $tipo = strtoupper($concept['tipo'] ?? '');
                $monto = $concept['monto'] ?? 0;

                if ($tipo === 'D') { // Deducciones
                    // Ausencias
                    if (strpos($codigo, 'AUSENCIA') !== false || strpos($descripcion, 'AUSENCIA') !== false) {
                        $ausencias += $monto;
                    }
                    // Tardanzas
                    elseif (strpos($codigo, 'TARDAN') !== false || strpos($descripcion, 'TARDAN') !== false) {
                        $tardanzas += $monto;
                    }
                    // Seguro Social
                    elseif ($concept['categoria'] === 'seguro_social') {
                        $seguroSocial += $monto;
                    }
                    // Seguro Educativo
                    elseif ($concept['categoria'] === 'seguro_educativo') {
                        $seguroEducativo += $monto;
                    }
                    // ISR
                    elseif ($concept['categoria'] === 'impuesto_renta') {
                        $impuestoRenta += $monto;
                    }
                    // Otras deducciones
                    else {
                        $otrasDeducciones += $monto;
                    }
                }
            }
        }

        return [
            'ingresos' => $totals['ingresos'] ?? 0,
            'deducciones' => $totals['deducciones'] ?? 0,
            'seguro_social' => $seguroSocial,
            'seguro_educativo' => $seguroEducativo,
            'impuesto_renta' => $impuestoRenta,
            'otras_deducciones' => $otrasDeducciones,
            'neto' => $totals['neto'] ?? 0,
            'dias_laborados' => $diasLaborados,
            'ausencias' => $ausencias,
            'tardanzas' => $tardanzas
        ];
    }

    /**
     * Insertar logos en el PDF alineados con el título
     * Reutilizado de PDFReportController
     */
    protected function insertLogosInPDF($pdf, $companyInfo)
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

        // Logo principal (centro) - solo si no hay logos laterales
        if (empty($companyInfo['logo_izquierdo_reportes']) && empty($companyInfo['logo_derecho_reportes']) && !empty($companyInfo['logo_empresa'])) {
            $mainLogoPath = TenantStorage::getLogoFilesystemPath($companyInfo['logo_empresa']);
            if ($mainLogoPath) {
                $mainLogoWidth = 40;
                $centerX = ($pageWidth - $mainLogoWidth) / 2;
                $pdf->Image($mainLogoPath, $centerX, $currentY, $mainLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
            }
        }

        // Nombre de la empresa centrado a la misma altura de los logos
        if (!empty($companyInfo['company_name'])) {
            $currentFont = $pdf->getFontFamily();
            $currentSize = $pdf->getFontSizePt();

            $pdf->SetFont('helvetica', 'B', 16);
            $companyNameWidth = $pdf->GetStringWidth($companyInfo['company_name']);
            $centerX = ($pageWidth - $companyNameWidth) / 2;
            $textY = $currentY + ($logoHeight / 2) - ($pdf->getFontSizePt() / 2) + 1;

            $pdf->SetXY($centerX, $textY);
            $pdf->Cell($companyNameWidth, 0, $companyInfo['company_name'], 0, 0, 'C');

            $pdf->SetFont($currentFont, '', $currentSize);
        }

        // Reservar espacio después de los logos
        if (!empty($companyInfo['logo_izquierdo_reportes']) || !empty($companyInfo['logo_derecho_reportes']) || !empty($companyInfo['logo_empresa'])) {
            $pdf->SetY(20);
        }
    }

    /**
     * Agregar firmas de responsables del reporte
     * Reutilizado de PDFReportController con ajustes para Legal landscape
     */
    protected function addSignatures($pdf, $companyInfo)
    {
        $pdf->Ln(8);

        // Determinar qué firmas mostrar
        $firmas = [];

        $firmas[] = [
            'nombre' => $companyInfo['elaborado_por'],
            'cargo' => $companyInfo['cargo_elaborador']
        ];

        $firmas[] = [
            'nombre' => $companyInfo['jefe_recursos_humanos'],
            'cargo' => $companyInfo['cargo_jefe_rrhh']
        ];

        // Firma director solo si tiene contenido
        if (!empty($companyInfo['firma_director_planilla']) && trim($companyInfo['firma_director_planilla']) !== '') {
            $firmas[] = [
                'nombre' => $companyInfo['firma_director_planilla'],
                'cargo' => $companyInfo['cargo_director_planilla']
            ];
        }

        $numFirmas = count($firmas);
        $colWidth = $numFirmas > 0 ? (316 / $numFirmas) : 105; // Ajustado para Legal landscape (336mm - 20mm)
        $sigHeight = 15;

        // Primera fila de firmas
        $pdf->SetFont('helvetica', '', 9);

        for ($i = 0; $i < $numFirmas; $i++) {
            $pdf->Cell($colWidth, $sigHeight, '', 'B', ($i == $numFirmas - 1) ? 1 : 0, 'C');
            if ($i < $numFirmas - 1) {
                $pdf->Cell(5, $sigHeight, '', 0, 0, 'C');
            }
        }

        // Nombres bajo las líneas de firma
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

        $pdf->Ln(5);

        // Segunda fila para firma del contador si existe
        if (!empty($companyInfo['firma_contador_planilla']) && trim($companyInfo['firma_contador_planilla']) !== '') {
            $pdf->SetFont('helvetica', '', 9);

            // Centrar la firma del contador
            $pdf->Cell(115, $sigHeight, '', 0, 0, 'C');
            $pdf->Cell($colWidth, $sigHeight, '', 'B', 1, 'C');

            // Nombre del contador
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell(115, 6, '', 0, 0, 'C');
            $pdf->Cell($colWidth, 6, strtoupper($companyInfo['firma_contador_planilla']), 0, 1, 'C');

            // Cargo del contador
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell(115, 5, '', 0, 0, 'C');
            $pdf->Cell($colWidth, 5, $companyInfo['cargo_contador_planilla'], 0, 1, 'C');
        }

        // Información adicional al final
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 4, 'Fecha de Generación: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
    }
}
