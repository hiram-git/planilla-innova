<?php

namespace App\Services\DocumentGenerators;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use App\Core\TenantStorage;

/**
 * Generador de Carta de Trabajo en Word (DOCX) usando PhpWord
 *
 * Genera certificaciones de empleo en formato editable de Word
 */
class WorkCertificateWordGenerator
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
     * Generar y descargar el documento Word de la carta de trabajo
     *
     * @return void (outputs DOCX directly)
     */
    public function generate()
    {
        // Crear instancia de PhpWord
        $phpWord = new PhpWord();

        // Configurar propiedades del documento
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('Sistema de Planillas MVC');
        $properties->setCompany($this->companyInfo['company_name']);
        $properties->setTitle('Carta de Trabajo - ' . $this->getEmployeeFullName());
        $properties->setSubject('Certificación de Empleo');

        // Crear sección (página)
        $section = $phpWord->addSection([
            'marginLeft' => Converter::cmToTwip(2.5),
            'marginRight' => Converter::cmToTwip(2.5),
            'marginTop' => Converter::cmToTwip(2.5),
            'marginBottom' => Converter::cmToTwip(2.5)
        ]);

        // Generar contenido
        $this->addHeader($section, $phpWord);
        $this->addBody($section, $phpWord);
        $this->addFooter($section, $phpWord);

        // Nombre del archivo
        $filename = $this->generateFilename('carta_trabajo');

        // Guardar y descargar
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * Agregar encabezado con logo y datos de empresa
     */
    private function addHeader($section, $phpWord)
    {
        // Intentar agregar logo si existe
        $this->insertLogo($section);

        // Nombre de la empresa (centrado, negrita)
        $section->addText(
            strtoupper($this->companyInfo['company_name']),
            ['bold' => true, 'size' => 14],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        // RUC de la empresa
        $section->addText(
            'RUC ' . $this->companyInfo['ruc'],
            ['size' => 10],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        // Dirección
        $section->addText(
            strtoupper($this->companyInfo['address']),
            ['size' => 10],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        // Teléfono (si existe)
        if (!empty($this->companyInfo['phone'])) {
            $section->addText(
                'TEL: ' . $this->companyInfo['phone'],
                ['size' => 10],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
            );
        }

        // Espacio después del encabezado
        $section->addTextBreak(2);
    }

    /**
     * Agregar cuerpo de la carta
     */
    private function addBody($section, $phpWord)
    {
        // Fecha de emisión (alineada a la derecha)
        $fechaEmision = 'Panamá, ' . $this->formatDate(date('Y-m-d'));
        $section->addText(
            $fechaEmision,
            ['size' => 11],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::END]
        );

        $section->addTextBreak(1);

        // Destinatario
        $section->addText('Sres,', ['size' => 11]);
        $section->addText('A quien corresponda.', ['size' => 11]);

        $section->addTextBreak(1);

        // Cuerpo del texto (justificado)
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

        $section->addText(
            $parrafo,
            ['size' => 11],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH, 'spaceAfter' => 200]
        );

        $section->addTextBreak(1);

        // Despedida
        $section->addText('Sin más que agregar.', ['size' => 11]);
    }

    /**
     * Agregar pie con firma del representante legal
     */
    private function addFooter($section, $phpWord)
    {
        $section->addTextBreak(3);

        // Línea de firma (usando una tabla de 1 celda con borde superior)
        $table = $section->addTable(['alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER]);
        $table->addRow();
        $cell = $table->addCell(Converter::cmToTwip(8), [
            'borderTopSize' => 6,
            'borderTopColor' => '000000',
            'borderBottomSize' => 0,
            'borderLeftSize' => 0,
            'borderRightSize' => 0
        ]);

        $section->addTextBreak(1);

        // Nombre del representante legal
        $representante = $this->companyInfo['legal_representative'] ?? 'Representante Legal';
        $section->addText(
            strtoupper($representante),
            ['bold' => true, 'size' => 11],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        // Cédula del representante
        $representanteCedula = $this->companyInfo['legal_representative_id'] ?? '';
        if (!empty($representanteCedula)) {
            $section->addText(
                'Cédula: ' . $representanteCedula,
                ['size' => 10],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
            );
        }
    }

    /**
     * Insertar logo de la empresa
     */
    private function insertLogo($section)
    {
        $logoPath = null;

        // Priorizar logo principal de empresa
        if (!empty($this->companyInfo['logo_empresa'])) {
            $logoPath = TenantStorage::getLogoFilesystemPath($this->companyInfo['logo_empresa']);
        }

        if ($logoPath && file_exists($logoPath)) {
            try {
                $section->addImage(
                    $logoPath,
                    [
                        'width' => Converter::cmToPixel(4),
                        'height' => Converter::cmToPixel(2),
                        'positioning' => \PhpOffice\PhpWord\Style\Image::POSITION_RELATIVE,
                        'posHorizontal' => \PhpOffice\PhpWord\Style\Image::POSITION_HORIZONTAL_CENTER,
                        'posHorizontalRel' => 'page',
                        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                    ]
                );
                $section->addTextBreak(1);
            } catch (\Exception $e) {
                error_log("Error cargando logo en carta de trabajo Word: " . $e->getMessage());
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
     * Obtener fecha de finalización de empleo
     */
    private function getEmploymentEndDate()
    {
        if (!empty($this->employeeData['fecha_terminacion'])) {
            return $this->formatDate($this->employeeData['fecha_terminacion']);
        }

        return 'la actualidad';
    }

    /**
     * Obtener descripción del salario del empleado
     */
    private function getEmployeeSalaryDescription()
    {
        $currency = $this->companyInfo['currency_symbol'] ?? 'B/.';

        if (!empty($this->employeeData['tarifa_hora']) && $this->employeeData['tarifa_hora'] > 0) {
            return number_format($this->employeeData['tarifa_hora'], 2) . ' x hora';
        }

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

        return "{$prefix}_{$employeeName}_{$date}.docx";
    }
}
