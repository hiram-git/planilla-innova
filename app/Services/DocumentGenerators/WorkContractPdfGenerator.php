<?php

namespace App\Services\DocumentGenerators;

use TCPDF;
use App\Core\TenantStorage;

/**
 * Generador de Contrato de Trabajo Definido en PDF usando TCPDF
 *
 * Genera contratos completos con todas las cláusulas según legislación panameña
 */
class WorkContractPdfGenerator
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
     * Generar y descargar el PDF del contrato de trabajo
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
        $pdf->SetTitle('Contrato de Trabajo - ' . $this->getEmployeeFullName());
        $pdf->SetSubject('Contrato de Trabajo Definido');

        // Configuración de márgenes reducidos para aprovechar espacio
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        // Generar contenido
        $this->addTitle($pdf);
        $this->addParties($pdf);
        $this->addClauses($pdf);
        $this->addSignatures($pdf);

        // Nombre del archivo
        $filename = $this->generateFilename('contrato_trabajo');

        // Output del PDF (descarga inline en navegador)
        $pdf->Output($filename, 'I');
        exit;
    }

    /**
     * Agregar título del contrato
     */
    private function addTitle($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'CONTRATO DE TRABAJO DEFINIDO', 0, 1, 'C');
        $pdf->Ln(3);
    }

    /**
     * Agregar información de las partes (empleador y trabajador)
     */
    private function addParties($pdf)
    {
        $pdf->SetFont('helvetica', '', 9);

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
        $empleadoEstadoCivil = 'SOLTERO'; // Campo no existe en BD actual

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

        $pdf->MultiCell(0, 5, $text, 0, 'J', false, 1);
        $pdf->Ln(4);
    }

    /**
     * Agregar todas las cláusulas del contrato
     */
    private function addClauses($pdf)
    {
        $this->addClausePrimero($pdf);
        $this->addClauseSegundo($pdf);
        $this->addClauseTercero($pdf);
        $this->addClauseCuarto($pdf);
        $this->addClauseQuinto($pdf);
        $this->addClauseSexto($pdf);
        $this->addClauseSeptimo($pdf);
        $this->addClauseOctavo($pdf);
        $this->addClauseNoveno($pdf);
        $this->addClauseDecimo($pdf);
        $this->addClauseDecimoPrimero($pdf);
        $this->addClauseDecimoSegundo($pdf);
        $this->addClauseDecimoTercero($pdf);
        $this->addClauseDecimoCuarto($pdf);
    }

    /**
     * PRIMERO: Duración del contrato
     */
    private function addClausePrimero($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'PRIMERO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $duracionMeses = $this->getContractDurationMonths();
        $fechaInicio = $this->formatDateShort($this->employeeData['fecha_inicio_contrato'] ?? $this->employeeData['fecha_ingreso']);
        $fechaFin = $this->formatDateShort($this->employeeData['fecha_vencimiento_contrato'] ?? date('Y-m-d'));

        $text = sprintf(
            'EL EMPLEADOR, se compromete a darle empleo a EL TRABAJADOR, en la empresa antes mencionada o en cualquier otro establecimiento de su propiedad, con base en lo que dispone el artículo 197-A del CODIGO DE TRABAJO, por un término de %s MESES y regirá a partir del día %s hasta el %s. Sin embargo, el presente contrato puede ser prorrogado por período adicional previo acuerdo por las partes. No habrá desmejoramiento salarial.',
            $duracionMeses,
            $fechaInicio,
            $fechaFin
        );

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * SEGUNDO: Funciones del puesto
     */
    private function addClauseSegundo($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'SEGUNDO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $cargo = $this->employeeData['cargo_name'] ?? 'EMPLEADO';

        $text = sprintf(
            'EL TRABAJADOR, se desempeña principalmente como %s, aceptando que sus funciones básicas consisten en las tareas asignadas por su superior inmediato, así como aquellas tareas conexas o complementarias, relacionadas con las funciones para las cuales ha sido contratado.',
            strtoupper($cargo)
        );

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * TERCERO: Jornada y salario
     */
    private function addClauseTercero($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'TERCERO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $schedule = $this->getScheduleDescription();
        $salaryDescription = $this->getSalaryDescription();

        $text = sprintf(
            'La jornada de trabajo será de 8 horas de LUNES A SÁBADO con derecho a un día libre semanal de preferencia los días DOMINGO de cada semana; en total serán 48 horas de trabajo semanal. La rata de salario será de %s las que serán pagadas los días 15 y 30 de cada MES.',
            $salaryDescription
        );

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * CUARTO: Turno de trabajo
     */
    private function addClauseCuarto($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'CUARTO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $schedule = $this->getScheduleDescription();

        $text = sprintf(
            'El turno de las labores será de %s o de acuerdo a las necesidades y circunstancias de la empresa.',
            $schedule
        );

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * QUINTO: Prestaciones laborales
     */
    private function addClauseQuinto($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'QUINTO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $text = 'EL TRABAJADOR(A), tendrá derecho a las prestaciones y otras que le correspondan de acuerdo a las disposiciones laborales vigentes.';

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * SEXTO: Terminación del contrato
     */
    private function addClauseSexto($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'SEXTO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $text = 'Si durante la vigencia del presente contrato de trabajo si alguna de las partes quisiera darlo por terminado, puede hacerlo, acogiéndose a las disposiciones laborales vigentes.';

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * SEPTIMO: Período de prueba
     */
    private function addClauseSeptimo($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'SEPTIMO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $periodoPrueba = $this->employeeData['periodo_prueba_meses'] ?? 3;

        $text = sprintf(
            'El presente contrato tendrá un período de prueba de %s MESES a partir de la fecha de inicio del contrato. Por lo anterior, las partes consienten en que durante el período de prueba cualquiera de las partes podrá dar por terminada la relación de trabajo sin responsabilidad alguna, de conformidad a lo que establece el artículo 78 del Código de Trabajo.',
            $periodoPrueba
        );

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * OCTAVO: Confidencialidad
     */
    private function addClauseOctavo($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'OCTAVO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $text = 'Acepta EL TRABAJADOR guardar la mayor confidencialidad, sobre los asuntos de EL EMPLEADOR o de los clientes de éste, que lleguen a su conocimiento como resultado directo o indirecto de las labores que ejecuta para EL EMPLEADOR.';

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * NOVENO: Notificación de ausencias
     */
    private function addClauseNoveno($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'NOVENO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $text = 'Acepta EL TRABAJADOR, que cuando tenga la necesidad de faltar a sus labores, aunque sea con causa justificada, deberá notificarlo a EL EMPLEADOR, para que éste tome las medidas pertinentes del caso, con veinticuatro (24) horas de anticipación, salvo que, por la urgencia del caso, resulte imposible hacerlo.';

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * DÉCIMO: Justificación de ausencias
     */
    private function addClauseDecimo($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'DÉCIMO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $text = 'Acepta EL TRABAJADOR, que sus ausencias las deberá justificar el día en que se reincorpore a su trabajo, luego de haber estado ausente por cualquier causa. La justificación debe hacerse con documentos, en los casos que lo requieran, a más tardar luego de transcurridas veinticuatro (24) horas, contadas a partir de la fecha en que se reincorpore a su puesto de trabajo. EL TRABAJADOR debe presentar un certificado médico, para justificar las ausencias, motivadas por enfermedad común o riesgo profesional, el documento pertinente si resulta necesario para justificar las ausencias por otros motivos. Si los documentos no se entregan dentro del plazo antes mencionado, las ausencias se considerarán como injustificadas.';

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * DECIMOPRIMERO: Beneficiarios
     */
    private function addClauseDecimoPrimero($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'DECIMOPRIMERO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $beneficiarios = $this->employeeData['beneficiarios'] ?? 'NO ESPECIFICADO';

        $text = sprintf(
            'Declara el trabajador que viven y dependen de él las siguientes personas: %s y los mismos son sus primeros beneficiarios.',
            strtoupper($beneficiarios)
        );

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * DECIMOSEGUNDO: Devolución de uniformes y herramientas
     */
    private function addClauseDecimoSegundo($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'DECIMOSEGUNDO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $text = 'El trabajador se compromete a devolver los uniformes, útiles o herramientas proporcionados por la empresa al finalizar su relación laboral. En caso de no ser devueltos, la empresa tendrá derecho a descontar el valor del salario final del trabajador.';

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * DECIMOTERCERO: Contacto de emergencia
     */
    private function addClauseDecimoTercero($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'DECIMOTERCERO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $contactoEmergencia = $this->employeeData['contacto_emergencia'] ?? 'NO ESPECIFICADO';
        $telefonoEmergencia = $this->employeeData['telefono_emergencia'] ?? 'N/A';

        $text = sprintf(
            'Declara el trabajador que en caso de urgencia llamar a: %s, Teléfono: %s.',
            strtoupper($contactoEmergencia),
            $telefonoEmergencia
        );

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    /**
     * DECIMOCUARTO: Obligaciones del trabajador
     */
    private function addClauseDecimoCuarto($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'DECIMOCUARTO:', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);

        $text = 'EL TRABAJADOR se compromete a acatar las instrucciones impartidas de sus jefes inmediatos, y efectuará sus labores con la intensidad, cuidado, esmero y eficacia necesaria que exige el desempeño de sus labores. Y cumplirá con las obligaciones y prohibiciones que señalan los artículos 126 y 127 del Código de Trabajo reformado por la Ley 44 de 12 de agosto de 1995.';

        $pdf->MultiCell(0, 4.5, $text, 0, 'J', false, 1);
        $pdf->Ln(4);
    }

    /**
     * Agregar sección de firmas
     */
    private function addSignatures($pdf)
    {
        // Fecha de firma
        $pdf->SetFont('helvetica', '', 9);
        $fechaFirma = 'Para conformidad de las partes se firma el presente contrato a los ' .
                      date('d') . ' días del mes de ' .
                      $this->getMonthName(date('n')) . ' de ' . date('Y') . '.';

        $pdf->MultiCell(0, 4.5, $fechaFirma, 0, 'J', false, 1);
        $pdf->Ln(15);

        // Líneas de firma
        $pdf->SetFont('helvetica', '', 9);

        // Dos columnas: Empleador | Trabajador
        $colWidth = 85;

        // Líneas
        $pdf->Cell($colWidth, 0, '', 'B', 0, 'C');
        $pdf->Cell(10, 0, '', 0, 0, 'C'); // Espacio
        $pdf->Cell($colWidth, 0, '', 'B', 1, 'C');

        $pdf->Ln(2);

        // Labels
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidth, 5, 'EL EMPLEADOR', 0, 0, 'C');
        $pdf->Cell(10, 5, '', 0, 0, 'C');
        $pdf->Cell($colWidth, 5, 'EL TRABAJADOR', 0, 1, 'C');

        // Cédulas
        $pdf->SetFont('helvetica', '', 8);
        $representanteCedula = $this->companyInfo['legal_representative_id'] ?? 'N/A';
        $empleadoCedula = $this->employeeData['document_id'] ?? 'N/A';

        $pdf->Cell($colWidth, 5, 'Cédula: ' . $representanteCedula, 0, 0, 'C');
        $pdf->Cell(10, 5, '', 0, 0, 'C');
        $pdf->Cell($colWidth, 5, 'Cédula: ' . $empleadoCedula, 0, 1, 'C');

        // Nombres
        $representante = strtoupper($this->companyInfo['legal_representative'] ?? 'Representante Legal');
        $empleadoNombre = strtoupper($this->getEmployeeFullName());

        $pdf->Cell($colWidth, 5, $representante, 0, 0, 'C');
        $pdf->Cell(10, 5, '', 0, 0, 'C');
        $pdf->Cell($colWidth, 5, $empleadoNombre, 0, 1, 'C');
    }

    /**
     * Obtener nombre completo del empleado
     */
    private function getEmployeeFullName()
    {
        return trim($this->employeeData['firstname'] . ' ' . $this->employeeData['lastname']);
    }

    /**
     * Calcular edad a partir de fecha de nacimiento
     */
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

    /**
     * Obtener duración del contrato en meses
     */
    private function getContractDurationMonths()
    {
        $inicio = $this->employeeData['fecha_inicio_contrato'] ?? $this->employeeData['fecha_ingreso'];
        $fin = $this->employeeData['fecha_vencimiento_contrato'] ?? date('Y-m-d');

        if (empty($inicio) || empty($fin)) {
            return 6; // Default
        }

        $start = new \DateTime($inicio);
        $end = new \DateTime($fin);
        $interval = $start->diff($end);

        return $interval->y * 12 + $interval->m;
    }

    /**
     * Obtener descripción del horario
     */
    private function getScheduleDescription()
    {
        // Aquí podrías hacer un join con la tabla schedules si tienes los datos
        // Por ahora retornamos un valor estándar
        return '8:00 AM A 5:00 PM (NOTA: MEDIA HORA DE ALMUERZO)';
    }

    /**
     * Obtener descripción del salario
     */
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

    /**
     * Formatear fecha corta (DD/MES/YYYY)
     */
    private function formatDateShort($date)
    {
        if (empty($date)) {
            return 'N/A';
        }

        $timestamp = strtotime($date);
        return date('d', $timestamp) . '/' . strtoupper($this->getMonthName(date('n', $timestamp))) . '/' . date('Y', $timestamp);
    }

    /**
     * Obtener nombre del mes en español
     */
    private function getMonthName($month)
    {
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];

        return $meses[(int)$month] ?? 'N/A';
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
