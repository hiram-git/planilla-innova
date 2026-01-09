<?php

namespace App\Services\DocumentGenerators;

use TCPDF;
use App\Core\TenantStorage;

/**
 * Generador de Carta de Trabajo en PDF usando TCPDF
 *
 * Genera certificaciones de empleo con datos del empleado y empresa
 */
class WorkCertificatePdfGenerator
{
    private $companyInfo;
    private $employeeData;

    /**
     * Constructor
     *
     * @param array $companyInfo Información de la empresa desde BD
     * @param array $employeeData Información completa del empleado
     */
    public function __construct($companyInfo, $employeeData)
    {
        $this->companyInfo = $companyInfo;
        $this->employeeData = $employeeData;
    }

    /**
     * Generar y descargar el PDF de la carta de trabajo
     *
     * @return void (outputs PDF directly)
     */
    public function generate()
    {
        // Crear instancia de TCPDF en orientación vertical (Portrait)
        $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Desactivar header y footer por defecto
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Configuración del documento
        $pdf->SetCreator('Sistema de Planillas MVC');
        $pdf->SetAuthor($this->companyInfo['company_name']);
        $pdf->SetTitle('Carta de Trabajo - ' . $this->getEmployeeFullName());
        $pdf->SetSubject('Certificación de Empleo');

        // Configuración de márgenes
        $pdf->SetMargins(25, 25, 25);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();

        // Generar contenido
        $this->addHeader($pdf);
        $this->addBody($pdf);
        $this->addFooter($pdf);

        // Nombre del archivo
        $filename = $this->generateFilename('carta_trabajo');

        // Output del PDF (descarga inline en navegador)
        $pdf->Output($filename, 'I');
        exit;
    }

    /**
     * Agregar encabezado con logo y datos de empresa
     */
    private function addHeader($pdf)
    {
        // Insertar logo si existe (centrado o lateral según configuración)
        $this->insertLogo($pdf);

        // Nombre de la empresa (centrado, negrita)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, strtoupper($this->companyInfo['company_name']), 0, 1, 'C');

        // RUC de la empresa
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'RUC ' . $this->companyInfo['ruc'], 0, 1, 'C');

        // Dirección
        $pdf->Cell(0, 5, strtoupper($this->companyInfo['address']), 0, 1, 'C');

        // Teléfono (si existe)
        if (!empty($this->companyInfo['phone'])) {
            $pdf->Cell(0, 5, 'TEL: ' . $this->companyInfo['phone'], 0, 1, 'C');
        }

        $pdf->Ln(10); // Espacio después del encabezado
    }

    /**
     * Agregar cuerpo de la carta
     */
    private function addBody($pdf)
    {
        // Fecha de emisión (alineada a la derecha)
        $pdf->SetFont('helvetica', '', 11);
        $fechaEmision = 'Panamá, ' . $this->formatDate(date('Y-m-d'));
        $pdf->Cell(0, 6, $fechaEmision, 0, 1, 'R');

        $pdf->Ln(8);

        // Destinatario
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, 'Sres,', 0, 1, 'L');
        $pdf->Cell(0, 6, 'A quien corresponda.', 0, 1, 'L');

        $pdf->Ln(8);

        // Cuerpo del texto
        $pdf->SetFont('helvetica', '', 11);

        // Párrafo principal con texto justificado
        $representante = $this->companyInfo['legal_representative'] ?? 'Representante Legal';
        $representanteCedula = $this->companyInfo['legal_representative_id'] ?? 'N/A';

        $parrafo = sprintf(
            'Por medio de la presente Yo, %s con cédula de identidad personal: %s representante legal de la empresa %s con Ruc %s. Hago constar que el(la) Sr(a). %s, laboró dentro de nuestra empresa desde %s hasta %s devengando un salario de %s. Durante este período, ha demostrado ser una persona responsable, puntual y honesta.',
            strtoupper($representante),
            $representanteCedula,
            strtoupper($this->companyInfo['company_name']),
            $this->companyInfo['ruc'],
            strtoupper($this->getEmployeeFullName()),
            $this->formatDate($this->employeeData['fecha_ingreso']),
            $this->getEmploymentEndDate(),
            $this->getEmployeeSalaryDescription()
        );

        // MultiCell para texto justificado con saltos automáticos
        $pdf->MultiCell(0, 6, $parrafo, 0, 'J', false, 1);

        $pdf->Ln(10);

        // Despedida
        $pdf->Cell(0, 6, 'Sin más que agregar.', 0, 1, 'L');
    }

    /**
     * Agregar pie con firma del representante legal
     */
    private function addFooter($pdf)
    {
        $pdf->Ln(20); // Espacio para firma

        // Línea de firma
        $pdf->Cell(0, 0, '', 'T', 1, 'C');

        $pdf->Ln(2);

        // Nombre del representante legal
        $pdf->SetFont('helvetica', 'B', 11);
        $representante = $this->companyInfo['legal_representative'] ?? 'Representante Legal';
        $pdf->Cell(0, 6, strtoupper($representante), 0, 1, 'C');

        // Cédula del representante
        $pdf->SetFont('helvetica', '', 10);
        $representanteCedula = $this->companyInfo['legal_representative_id'] ?? '';
        if (!empty($representanteCedula)) {
            $pdf->Cell(0, 5, 'Cédula: ' . $representanteCedula, 0, 1, 'C');
        }
    }

    /**
     * Insertar logo de la empresa
     */
    private function insertLogo($pdf)
    {
        $logoPath = null;

        // Priorizar logo principal de empresa
        if (!empty($this->companyInfo['logo_empresa'])) {
            $logoPath = TenantStorage::getLogoFilesystemPath($this->companyInfo['logo_empresa']);
        }

        if ($logoPath && file_exists($logoPath)) {
            $logoWidth = 35;
            $pageWidth = $pdf->getPageWidth();
            $centerX = ($pageWidth - $logoWidth) / 2;
            $currentY = $pdf->GetY();

            try {
                $pdf->Image($logoPath, $centerX, $currentY, $logoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                // Ajustar posición Y después del logo
                $pdf->SetY($currentY + 15);
            } catch (\Exception $e) {
                error_log("Error cargando logo en carta de trabajo: " . $e->getMessage());
            }
        }
    }

    /**
     * Obtener nombre completo del empleado
     */
    private function getEmployeeFullName()
    {
        return trim($this->employeeData['firstname'] . ' ' . $this->employeeData['lastname']);
    }

    /**
     * Obtener fecha de finalización de empleo (fecha actual si está activo)
     */
    private function getEmploymentEndDate()
    {
        // Si hay fecha de terminación, usarla
        if (!empty($this->employeeData['fecha_terminacion'])) {
            return $this->formatDate($this->employeeData['fecha_terminacion']);
        }

        // Si está activo, mostrar "la actualidad" o fecha actual
        return 'la actualidad';
    }

    /**
     * Obtener descripción del salario del empleado
     */
    private function getEmployeeSalaryDescription()
    {
        $currency = $this->companyInfo['currency_symbol'] ?? 'B/.';

        // Si tiene tarifa por hora
        if (!empty($this->employeeData['tarifa_hora']) && $this->employeeData['tarifa_hora'] > 0) {
            return number_format($this->employeeData['tarifa_hora'], 2) . ' x hora';
        }

        // Si tiene sueldo mensual
        if (!empty($this->employeeData['sueldo_individual']) && $this->employeeData['sueldo_individual'] > 0) {
            return $currency . number_format($this->employeeData['sueldo_individual'], 2) . ' mensual';
        }

        return 'salario según contrato';
    }

    /**
     * Formatear fecha a texto en español (dd de mes de yyyy)
     */
    private function formatDate($date)
    {
        if (empty($date)) {
            return 'N/A';
        }

        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];

        $timestamp = strtotime($date);
        $dia = date('d', $timestamp);
        $mes = $meses[(int)date('n', $timestamp)];
        $anio = date('Y', $timestamp);

        return "{$dia} de {$mes} de {$anio}";
    }

    /**
     * Generar nombre del archivo para descarga
     */
    private function generateFilename($prefix)
    {
        $employeeName = str_replace(' ', '_', $this->getEmployeeFullName());
        $employeeName = preg_replace('/[^A-Za-z0-9_]/', '', $employeeName);
        $date = date('Y-m-d');

        return "{$prefix}_{$employeeName}_{$date}.pdf";
    }
}
