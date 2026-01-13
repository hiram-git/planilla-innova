<?php

namespace App\Services\DocumentGenerators;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Generador de Contrato de Trabajo Definido en Word (DOCX) usando PhpWord
 *
 * Genera contratos completos editables con todas las cláusulas según legislación panameña
 */
class WorkContractWordGenerator
{
    private $companyInfo;
    private $employeeData;
    private $phpWord;
    private $section;

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
     * Generar y descargar el documento Word del contrato
     *
     * @return void (outputs DOCX directly)
     */
    public function generate()
    {
        // Crear instancia de PhpWord
        $this->phpWord = new PhpWord();

        // Configurar propiedades del documento
        $properties = $this->phpWord->getDocInfo();
        $properties->setCreator('Sistema de Planillas MVC');
        $properties->setCompany($this->companyInfo['company_name']);
        $properties->setTitle('Contrato de Trabajo - ' . $this->getEmployeeFullName());
        $properties->setSubject('Contrato de Trabajo Definido');

        // Crear sección (página) con márgenes reducidos
        $this->section = $this->phpWord->addSection([
            'marginLeft' => Converter::cmToTwip(2),
            'marginRight' => Converter::cmToTwip(2),
            'marginTop' => Converter::cmToTwip(2),
            'marginBottom' => Converter::cmToTwip(2)
        ]);

        // Generar contenido
        $this->addTitle();
        $this->addParties();
        $this->addClauses();
        $this->addSignatures();

        // Nombre del archivo
        $filename = $this->generateFilename('contrato_trabajo');

        // Guardar y descargar
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * Agregar título del contrato
     */
    private function addTitle()
    {
        $this->section->addText(
            'CONTRATO DE TRABAJO DEFINIDO',
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 200]
        );
    }

    /**
     * Agregar información de las partes
     */
    private function addParties()
    {
        // Datos del empleador
        $representante = $this->companyInfo['legal_representative'] ?? 'Representante Legal';
        $representanteCedula = $this->companyInfo['legal_representative_id'] ?? 'N/A';
        $companyRuc = $this->companyInfo['ruc'] ?? 'N/A';
        $companyAddress = $this->companyInfo['address'] ?? 'Dirección Empresa';

        // Datos del trabajador
        $empleadoNombre = strtoupper($this->getEmployeeFullName());
        $empleadoCedula = $this->employeeData['document_id'] ?? 'N/A';
        $empleadoSeguro = $this->employeeData['clave_seguro_social'] ?? 'N/A';
        $empleadoDireccion = $this->employeeData['address'] ?? 'N/A';
        $empleadoEdad = $this->calculateAge($this->employeeData['birthdate']);
        $empleadoSexo = strtoupper(substr($this->employeeData['gender'] ?? 'M', 0, 1));
        $empleadoEstadoCivil = 'SOLTERO';

        $text = sprintf(
            'Entre los suscritos a saber %s, Mayor de edad portador(a) de la cédula de identidad personal No. %s con domicilio en: %s, en representación de la sociedad: %s del negocio denominado %s inscrita en el Registro Público, con RUC: %s, ubicado en %s, quien en adelante se denominará EL EMPLEADOR, y el(la) Sr.(a) %s, Edad: %s, Sexo: %s, Estado civil: %s, con cédula de identidad personal # %s, Seguro social: %s, con residencia en: %s, quien en adelante se denominará EL TRABAJADOR(A), convienen en celebrar un CONTRATO DE TRABAJO, en los siguientes términos:',
            strtoupper($representante),
            $representanteCedula,
            strtoupper($companyAddress),
            strtoupper($this->companyInfo['company_name']),
            strtoupper($this->companyInfo['company_name']),
            $companyRuc,
            strtoupper($companyAddress),
            $empleadoNombre,
            $empleadoEdad,
            $empleadoSexo,
            $empleadoEstadoCivil,
            $empleadoCedula,
            $empleadoSeguro,
            strtoupper($empleadoDireccion)
        );

        $this->section->addText(
            $text,
            ['size' => 10],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 200]
        );
    }

    /**
     * Agregar todas las cláusulas del contrato
     */
    private function addClauses()
    {
        $this->addClause('PRIMERO:', $this->getClausePrimeroText());
        $this->addClause('SEGUNDO:', $this->getClauseSegundoText());
        $this->addClause('TERCERO:', $this->getClauseTerceroText());
        $this->addClause('CUARTO:', $this->getClauseCuartoText());
        $this->addClause('QUINTO:', 'EL TRABAJADOR(A), tendrá derecho a las prestaciones y otras que le correspondan de acuerdo a las disposiciones laborales vigentes.');
        $this->addClause('SEXTO:', 'Si durante la vigencia del presente contrato de trabajo si alguna de las partes quisiera darlo por terminado, puede hacerlo, acogiéndose a las disposiciones laborales vigentes.');
        $this->addClause('SEPTIMO:', $this->getClauseSeptimoText());
        $this->addClause('OCTAVO:', 'Acepta EL TRABAJADOR guardar la mayor confidencialidad, sobre los asuntos de EL EMPLEADOR o de los clientes de éste, que lleguen a su conocimiento como resultado directo o indirecto de las labores que ejecuta para EL EMPLEADOR.');
        $this->addClause('NOVENO:', 'Acepta EL TRABAJADOR, que cuando tenga la necesidad de faltar a sus labores, aunque sea con causa justificada, deberá notificarlo a EL EMPLEADOR, para que éste tome las medidas pertinentes del caso, con veinticuatro (24) horas de anticipación, salvo que, por la urgencia del caso, resulte imposible hacerlo.');
        $this->addClause('DÉCIMO:', 'Acepta EL TRABAJADOR, que sus ausencias las deberá justificar el día en que se reincorpore a su trabajo, luego de haber estado ausente por cualquier causa. La justificación debe hacerse con documentos, en los casos que lo requieran, a más tardar luego de transcurridas veinticuatro (24) horas, contadas a partir de la fecha en que se reincorpore a su puesto de trabajo. EL TRABAJADOR debe presentar un certificado médico, para justificar las ausencias, motivadas por enfermedad común o riesgo profesional, el documento pertinente si resulta necesario para justificar las ausencias por otros motivos. Si los documentos no se entregan dentro del plazo antes mencionado, las ausencias se considerarán como injustificadas.');
        $this->addClause('DECIMOPRIMERO:', $this->getClauseDecimoPrimeroText());
        $this->addClause('DECIMOSEGUNDO:', 'El trabajador se compromete a devolver los uniformes, útiles o herramientas proporcionados por la empresa al finalizar su relación laboral. En caso de no ser devueltos, la empresa tendrá derecho a descontar el valor del salario final del trabajador.');
        $this->addClause('DECIMOTERCERO:', $this->getClauseDecimoTerceroText());
        $this->addClause('DECIMOCUARTO:', 'EL TRABAJADOR se compromete a acatar las instrucciones impartidas de sus jefes inmediatos, y efectuará sus labores con la intensidad, cuidado, esmero y eficacia necesaria que exige el desempeño de sus labores. Y cumplirá con las obligaciones y prohibiciones que señalan los artículos 126 y 127 del Código de Trabajo reformado por la Ley 44 de 12 de agosto de 1995.');
    }

    /**
     * Agregar una cláusula al documento
     */
    private function addClause($title, $text)
    {
        $this->section->addText(
            $title,
            ['bold' => true, 'size' => 10],
            ['spaceAfter' => 80]
        );

        $this->section->addText(
            $text,
            ['size' => 10],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 150]
        );
    }

    /**
     * Texto de cláusula PRIMERO
     */
    private function getClausePrimeroText()
    {
        $duracionMeses = $this->getContractDurationMonths();
        $fechaInicio = $this->formatDateShort($this->employeeData['fecha_inicio_contrato'] ?? $this->employeeData['fecha_ingreso']);
        $fechaFin = $this->formatDateShort($this->employeeData['fecha_vencimiento_contrato'] ?? date('Y-m-d'));

        return sprintf(
            'EL EMPLEADOR, se compromete a darle empleo a EL TRABAJADOR, en la empresa antes mencionada o en cualquier otro establecimiento de su propiedad, con base en lo que dispone el artículo 197-A del CODIGO DE TRABAJO, por un término de %s MESES y regirá a partir del día %s hasta el %s. Sin embargo, el presente contrato puede ser prorrogado por período adicional previo acuerdo por las partes. No habrá desmejoramiento salarial.',
            $duracionMeses,
            $fechaInicio,
            $fechaFin
        );
    }

    /**
     * Texto de cláusula SEGUNDO
     */
    private function getClauseSegundoText()
    {
        $cargo = $this->employeeData['cargo_name'] ?? 'EMPLEADO';

        return sprintf(
            'EL TRABAJADOR, se desempeña principalmente como %s, aceptando que sus funciones básicas consisten en las tareas asignadas por su superior inmediato, así como aquellas tareas conexas o complementarias, relacionadas con las funciones para las cuales ha sido contratado.',
            strtoupper($cargo)
        );
    }

    /**
     * Texto de cláusula TERCERO
     */
    private function getClauseTerceroText()
    {
        $salaryDescription = $this->getSalaryDescription();

        return sprintf(
            'La jornada de trabajo será de 8 horas de LUNES A SÁBADO con derecho a un día libre semanal de preferencia los días DOMINGO de cada semana; en total serán 48 horas de trabajo semanal. La rata de salario será de %s las que serán pagadas los días 15 y 30 de cada MES.',
            $salaryDescription
        );
    }

    /**
     * Texto de cláusula CUARTO
     */
    private function getClauseCuartoText()
    {
        $schedule = $this->getScheduleDescription();

        return sprintf(
            'El turno de las labores será de %s o de acuerdo a las necesidades y circunstancias de la empresa.',
            $schedule
        );
    }

    /**
     * Texto de cláusula SEPTIMO
     */
    private function getClauseSeptimoText()
    {
        $periodoPrueba = $this->employeeData['periodo_prueba_meses'] ?? 3;

        return sprintf(
            'El presente contrato tendrá un período de prueba de %s MESES a partir de la fecha de inicio del contrato. Por lo anterior, las partes consienten en que durante el período de prueba cualquiera de las partes podrá dar por terminada la relación de trabajo sin responsabilidad alguna, de conformidad a lo que establece el artículo 78 del Código de Trabajo.',
            $periodoPrueba
        );
    }

    /**
     * Texto de cláusula DECIMOPRIMERO
     */
    private function getClauseDecimoPrimeroText()
    {
        $beneficiarios = $this->employeeData['beneficiarios'] ?? 'NO ESPECIFICADO';

        return sprintf(
            'Declara el trabajador que viven y dependen de él las siguientes personas: %s y los mismos son sus primeros beneficiarios.',
            strtoupper($beneficiarios)
        );
    }

    /**
     * Texto de cláusula DECIMOTERCERO
     */
    private function getClauseDecimoTerceroText()
    {
        $contactoEmergencia = $this->employeeData['contacto_emergencia'] ?? 'NO ESPECIFICADO';
        $telefonoEmergencia = $this->employeeData['telefono_emergencia'] ?? 'N/A';

        return sprintf(
            'Declara el trabajador que en caso de urgencia llamar a: %s, Teléfono: %s.',
            strtoupper($contactoEmergencia),
            $telefonoEmergencia
        );
    }

    /**
     * Agregar sección de firmas
     */
    private function addSignatures()
    {
        // Fecha de firma
        $fechaFirma = 'Para conformidad de las partes se firma el presente contrato a los ' .
                      date('d') . ' días del mes de ' .
                      $this->getMonthName(date('n')) . ' de ' . date('Y') . '.';

        $this->section->addText(
            $fechaFirma,
            ['size' => 10],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 400]
        );

        // Tabla de firmas (2 columnas)
        $table = $this->section->addTable(['alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER]);
        $table->addRow();

        // Celda Empleador
        $cellEmpleador = $table->addCell(Converter::cmToTwip(7.5));
        $cellEmpleador->addText('', [], ['borderTopSize' => 6, 'borderTopColor' => '000000']);
        $cellEmpleador->addTextBreak(1);
        $cellEmpleador->addText('EL EMPLEADOR', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        $representanteCedula = $this->companyInfo['legal_representative_id'] ?? 'N/A';
        $cellEmpleador->addText('Cédula: ' . $representanteCedula, ['size' => 9], ['alignment' => Jc::CENTER]);
        $representante = strtoupper($this->companyInfo['legal_representative'] ?? 'Representante Legal');
        $cellEmpleador->addText($representante, ['size' => 9], ['alignment' => Jc::CENTER]);

        // Celda Trabajador
        $cellTrabajador = $table->addCell(Converter::cmToTwip(7.5));
        $cellTrabajador->addText('', [], ['borderTopSize' => 6, 'borderTopColor' => '000000']);
        $cellTrabajador->addTextBreak(1);
        $cellTrabajador->addText('EL TRABAJADOR', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        $empleadoCedula = $this->employeeData['document_id'] ?? 'N/A';
        $cellTrabajador->addText('Cédula: ' . $empleadoCedula, ['size' => 9], ['alignment' => Jc::CENTER]);
        $empleadoNombre = strtoupper($this->getEmployeeFullName());
        $cellTrabajador->addText($empleadoNombre, ['size' => 9], ['alignment' => Jc::CENTER]);
    }

    /**
     * Utilidades y helpers
     */

    private function getEmployeeFullName()
    {
        return trim($this->employeeData['firstname'] . ' ' . $this->employeeData['lastname']);
    }

    private function calculateAge($birthdate)
    {
        if (empty($birthdate)) {
            return 'N/A';
        }

        $birth = new \DateTime($birthdate);
        $today = new \DateTime();
        $age = $birth->diff($today)->y;

        return $age;
    }

    private function getContractDurationMonths()
    {
        $inicio = $this->employeeData['fecha_inicio_contrato'] ?? $this->employeeData['fecha_ingreso'];
        $fin = $this->employeeData['fecha_vencimiento_contrato'] ?? date('Y-m-d');

        if (empty($inicio) || empty($fin)) {
            return 6;
        }

        $start = new \DateTime($inicio);
        $end = new \DateTime($fin);
        $interval = $start->diff($end);

        return $interval->y * 12 + $interval->m;
    }

    private function getScheduleDescription()
    {
        return '8:00 AM A 5:00 PM (NOTA: MEDIA HORA DE ALMUERZO)';
    }

    private function getSalaryDescription()
    {
        $currency = $this->companyInfo['currency_symbol'] ?? 'B/.';

        if (!empty($this->employeeData['tarifa_hora']) && $this->employeeData['tarifa_hora'] > 0) {
            return $currency . number_format($this->employeeData['tarifa_hora'], 2) . ' X HORA';
        }

        if (!empty($this->employeeData['sueldo_individual']) && $this->employeeData['sueldo_individual'] > 0) {
            return $currency . number_format($this->employeeData['sueldo_individual'], 2) . ' MENSUAL';
        }

        return 'SEGÚN CONTRATO';
    }

    private function formatDateShort($date)
    {
        if (empty($date)) {
            return 'N/A';
        }

        $timestamp = strtotime($date);
        return date('d', $timestamp) . '/' . strtoupper($this->getMonthName(date('n', $timestamp))) . '/' . date('Y', $timestamp);
    }

    private function getMonthName($month)
    {
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];

        return $meses[(int)$month] ?? 'N/A';
    }

    private function generateFilename($prefix)
    {
        $employeeName = str_replace(' ', '_', $this->getEmployeeFullName());
        $employeeName = preg_replace('/[^A-Za-z0-9_]/', '', $employeeName);
        $date = date('Y-m-d');

        return "{$prefix}_{$employeeName}_{$date}.docx";
    }
}
