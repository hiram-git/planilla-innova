<?php

namespace App\Controllers;

/**
 * Controlador para el Reporte de Estimado Anual de Planillas
 * Extiende ReportController para acceso a métodos compartidos
 */
class AnnualPayrollEstimateController extends ReportController
{
    /**
     * Estimado anual de planillas (Vista HTML)
     * Proyecta el costo mensual de planillas basándose en la última planilla procesada
     * y lo proyecta a 12 meses
     */
    public function index()
    {
        try {
            $this->requireAuth();

            // Obtener tipo de planilla del filtro (si existe)
            $tipo_planilla_id = isset($_GET['tipo_planilla_id']) ? (int)$_GET['tipo_planilla_id'] : null;

            // Obtener datos para el reporte
            $reportData = $this->getEstimateData($tipo_planilla_id);

            if (!$reportData) {
                $_SESSION['error'] = 'No hay planillas procesadas para generar el estimado';
                $this->redirect('/panel/reports');
                return;
            }

            // Renderizar vista
            $this->view('admin/reports/estimado_planillas', $reportData);

        } catch (\Exception $e) {
            error_log("Error en AnnualPayrollEstimateController@index: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar estimado de planillas: ' . $e->getMessage();
            $this->redirect('/panel/reports');
        }
    }

    /**
     * Generar PDF del estimado anual de planillas
     */
    public function exportPdf()
    {
        try {
            $this->requireAuth();

            // Obtener tipo de planilla del filtro (si existe)
            $tipo_planilla_id = isset($_GET['tipo_planilla_id']) ? (int)$_GET['tipo_planilla_id'] : null;

            // Obtener datos para el reporte
            $reportData = $this->getEstimateData($tipo_planilla_id);

            if (!$reportData) {
                $_SESSION['error'] = 'No hay planillas procesadas para generar el estimado';
                $this->redirect('/panel/reports/estimado-anual-planillas');
                return;
            }

            // Generar PDF
            $this->generatePDF($reportData);

        } catch (\Exception $e) {
            error_log("Error en AnnualPayrollEstimateController@exportPdf: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar PDF de estimado: ' . $e->getMessage();
            $this->redirect('/panel/reports/estimado-anual-planillas');
        }
    }

    /**
     * Obtener datos comunes para el reporte (usado por index y exportPdf)
     */
    private function getEstimateData($tipo_planilla_id)
    {
        // Obtener tipos de planilla disponibles
        $sql = "SELECT id, nombre FROM tipos_planilla ORDER BY nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $tipos_planilla = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Cláusula WHERE base
        $where_clause = "pc.estado = 'PROCESADA'";
        $params = [];

        if ($tipo_planilla_id) {
            $where_clause .= " AND pc.tipo_planilla_id = ?";
            $params[] = $tipo_planilla_id;
        }

        // Obtener la última planilla procesada para usar como base
        $sql = "SELECT
                    pc.id,
                    pc.descripcion,
                    pc.fecha_desde,
                    pc.fecha_hasta,
                    tp.nombre as tipo_planilla_nombre,
                    f.nombre as frecuencia_nombre,
                    SUM(CASE WHEN c.tipo_concepto IN ('ASIGNACION', 'A') THEN pd.monto ELSE 0 END) as total_asignaciones,
                    SUM(CASE WHEN c.tipo_concepto IN ('DEDUCCION', 'D') THEN pd.monto ELSE 0 END) as total_deducciones,
                    COUNT(DISTINCT pd.employee_id) as total_empleados
                FROM planilla_cabecera pc
                INNER JOIN planilla_detalle pd ON pc.id = pd.planilla_cabecera_id
                INNER JOIN concepto c ON pd.concepto_id = c.id
                LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                LEFT JOIN frecuencias f ON pc.frecuencia_id = f.id
                WHERE $where_clause
                GROUP BY pc.id
                ORDER BY pc.fecha_hasta DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $ultima_planilla = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$ultima_planilla) {
            return null;
        }

        // Proyectar a 12 meses
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        $proyeccion_mensual = [];
        $total_anual_asignaciones = 0;
        $total_anual_deducciones = 0;
        $total_anual_neto = 0;

        foreach ($meses as $num_mes => $nombre_mes) {
            // Usar el costo de la última planilla como base
            // NOTA: Esto asume frecuencia mensual para simplificar la proyección como se solicitó.
            // Si la planilla es quincenal, se debería multiplicar por 2, semanal por 4.33, etc.
            // Por consistencia con el código original, mantenemos la lógica directa de proyección.
            
            $asignaciones_mes = $ultima_planilla['total_asignaciones'];
            $deducciones_mes = $ultima_planilla['total_deducciones'];
            $neto_mes = $asignaciones_mes - $deducciones_mes;

            $proyeccion_mensual[] = [
                'mes_numero' => $num_mes,
                'mes_nombre' => $nombre_mes,
                'asignaciones' => $asignaciones_mes,
                'deducciones' => $deducciones_mes,
                'neto' => $neto_mes,
                'empleados' => $ultima_planilla['total_empleados']
            ];

            $total_anual_asignaciones += $asignaciones_mes;
            $total_anual_deducciones += $deducciones_mes;
            $total_anual_neto += $neto_mes;
        }

        // Información de la empresa y firmas
        $companyInfo = $this->getCompanyInfo();
        $signatures = $this->companyModel->getSignaturesForReports();

        return [
            'title' => 'Estimado Anual de Planillas',
            'page_title' => 'Estimado Anual de Planillas',
            'breadcrumb' => [
                ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                ['name' => 'Reportes', 'url' => '/panel/reports'],
                ['name' => 'Estimado Anual Planillas', 'url' => '#']
            ],
            'proyeccion_mensual' => $proyeccion_mensual,
            'total_anual_asignaciones' => $total_anual_asignaciones,
            'total_anual_deducciones' => $total_anual_deducciones,
            'total_anual_neto' => $total_anual_neto,
            'ultima_planilla' => $ultima_planilla,
            'tipos_planilla' => $tipos_planilla,
            'tipo_planilla_id' => $tipo_planilla_id,
            'ano_estimado' => date('Y'),
            'fecha_estimado' => date('d/m/Y'),
            'companyInfo' => $companyInfo,
            'signatures' => $signatures
        ];
    }

    /**
     * Generar documento PDF usando TCPDF
     */
    private function generatePDF($data)
    {
        // Requerir TCPDF (asumiendo vendor/autoload ya cargado por App)
        // require_once 'vendor/autoload.php';

        // Crear instancia TCPDF (Portrait por defecto para listas verticales)
        $pdf = new \TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Desactivar header y footer automáticos
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Metadata
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor($data['companyInfo']['company_name']);
        $pdf->SetTitle('Estimado Anual de Planillas ' . $data['ano_estimado']);

        // Márgenes
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        // --- CONTENIDO PDF ---

        // 1. Header con logos
        $this->insertLogosInPDF($pdf, $data['companyInfo']);
        $pdf->Ln(5); // Espacio tras logos (ajustado en insertLogosInPDF también)

        // 2. Título del reporte
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 6, 'ESTIMADO PROYECTADO DE PLANILLAS ' . $data['ano_estimado'], 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 9);
        $fechaReporte = date('d/m/Y H:i');
        $pdf->Cell(0, 5, 'Generado: ' . $fechaReporte, 0, 1, 'C');
        $pdf->Ln(5);

        // 3. Información Base
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'INFORMACIÓN DE BASE DE PROYECCIÓN', 1, 1, 'L', true);
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(40, 6, 'Planilla Base:', 1, 0, 'L');
        $pdf->Cell(0, 6, $data['ultima_planilla']['descripcion'], 1, 1, 'L');
        
        $pdf->Cell(40, 6, 'Tipo de Planilla:', 1, 0, 'L');
        $pdf->Cell(50, 6, $data['ultima_planilla']['tipo_planilla_nombre'] ?? 'N/A', 1, 0, 'L');
        $pdf->Cell(40, 6, 'Empleados:', 1, 0, 'L');
        $pdf->Cell(0, 6, $data['ultima_planilla']['total_empleados'], 1, 1, 'L');
        $pdf->Ln(5);

        // 4. Tabla de Proyección Mensual
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'PROYECCIÓN MENSUAL', 0, 1, 'L');

        // Headers tabla
        $pdf->SetFillColor(52, 58, 64); // Dark gray
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);

        // Anchos: Mes(40), Empleados(25), Asignaciones(40), Deducciones(40), Neto(Resto)
        $w = [40, 25, 40, 40, 35]; // Total 180

        $pdf->Cell($w[0], 7, 'Mes', 1, 0, 'C', true);
        $pdf->Cell($w[1], 7, 'Empleados', 1, 0, 'C', true);
        $pdf->Cell($w[2], 7, 'Asignaciones', 1, 0, 'C', true);
        $pdf->Cell($w[3], 7, 'Deducciones', 1, 0, 'C', true);
        $pdf->Cell($w[4], 7, 'Neto', 1, 1, 'C', true);

        // Data tabla
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        $fill = false; // Alternar color filas

        foreach ($data['proyeccion_mensual'] as $mes) {
            $pdf->SetFillColor(245, 245, 245);
            
            $pdf->Cell($w[0], 6, $mes['mes_nombre'], 1, 0, 'L', $fill);
            $pdf->Cell($w[1], 6, $mes['empleados'], 1, 0, 'C', $fill);
            $pdf->Cell($w[2], 6, '$' . number_format($mes['asignaciones'], 2), 1, 0, 'R', $fill);
            $pdf->Cell($w[3], 6, '$' . number_format($mes['deducciones'], 2), 1, 0, 'R', $fill);
            $pdf->Cell($w[4], 6, '$' . number_format($mes['neto'], 2), 1, 1, 'R', $fill);
            
            $fill = !$fill;
        }

        // Totales Anuales
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 220); // Gris más oscuro para footer
        $pdf->Cell($w[0] + $w[1], 7, 'TOTAL ANUAL', 1, 0, 'R', true);
        $pdf->Cell($w[2], 7, '$' . number_format($data['total_anual_asignaciones'], 2), 1, 0, 'R', true);
        $pdf->Cell($w[3], 7, '$' . number_format($data['total_anual_deducciones'], 2), 1, 0, 'R', true);
        $pdf->Cell($w[4], 7, '$' . number_format($data['total_anual_neto'], 2), 1, 1, 'R', true);

        $pdf->Ln(5);

        // 5. Estadísticas / Resumen
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, '* Proyección basada en costos actuales constantes de la planilla base.', 0, 1, 'L');
        $pdf->Ln(15);

        // 6. Firmas
        // Reutilizamos toda la lógica de firmas del ReportController (o implementamos una similar)
        // Pero como ya existe en EstimateReportController, la podemos duplicar aquí o mover a ReportController.
        // Dado que AnnualPayrollEstimateController extiende ReportController, si no está ahí, lo implementamos aquí.
        // En ReportController.php original NO vi addEstimadoSignatures, estaba en EstimateReportController.
        // Copiaremos la lógica aquí para ser independientes.

        $this->addSignatures($pdf, $data['companyInfo'], $data['signatures']);

        // Output PDF
        $fileName = 'estimado_anual_planillas_' . date('Ymd_His') . '.pdf';
        $pdf->Output($fileName, 'I');
        exit;
    }

    /**
     * Agregar sección de firmas al PDF (adaptado de EstimateReportController)
     */
    private function addSignatures($pdf, $companyInfo, $signatures)
    {
        // Verificar espacio disponible en página, si no cabe, nueva página
        if ($pdf->GetY() > ($pdf->getPageHeight() - 50)) {
            $pdf->AddPage();
        }

        $pdf->Ln(10);

        $firmas = [];
        
        // Elaborado por
        $firmas[] = [
            'nombre' => $signatures['elaborado_por'] ?? $companyInfo['elaborado_por'] ?? '',
            'cargo' => $signatures['cargo_elaborador'] ?? $companyInfo['cargo_elaborador'] ?? 'Elaborado por'
        ];

        // Autorizado por
        $firmas[] = [
            'nombre' => $signatures['jefe_recursos_humanos'] ?? $companyInfo['jefe_recursos_humanos'] ?? '',
            'cargo' => $signatures['cargo_jefe_rrhh'] ?? $companyInfo['cargo_jefe_rrhh'] ?? 'Autorizado por'
        ];

        $numFirmas = count($firmas);
        $pageWidth = $pdf->getPageWidth() - 30; // Margins 15+15
        $colWidth = $pageWidth / $numFirmas;
        
        // Posición Y inicial de firmas
        $yInicio = $pdf->GetY();

        // Líneas
        for ($i = 0; $i < $numFirmas; $i++) {
            $x = 15 + ($i * $colWidth);
            // Centrar línea en la columna (ancho línea 60)
            $lineX = $x + (($colWidth - 60) / 2);
            
            $pdf->Line($lineX, $yInicio, $lineX + 60, $yInicio);
            
            $pdf->SetXY($x, $yInicio + 2);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($colWidth, 5, strtoupper($firmas[$i]['nombre']), 0, 1, 'C');
            
            $pdf->SetXY($x, $yInicio + 7);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($colWidth, 5, $firmas[$i]['cargo'], 0, 1, 'C');
        }
    }
}
