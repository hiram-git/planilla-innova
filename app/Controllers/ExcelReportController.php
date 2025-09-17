<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Payroll;
use App\Models\Employee;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Controlador específico para reportes Excel
 */
class ExcelReportController extends Controller
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
     * Generar reporte Excel para planilla específica
     */
    public function generatePayrollExcel($payrollId)
    {
        try {
            $this->requireAuth();
            
            if (!$payrollId) {
                $_SESSION['error'] = 'ID de planilla requerido';
                $this->redirect('/panel/reports');
                return;
            }
            
            // Obtener datos de la planilla usando el mismo método que ReportController
            $planillaData = $this->getPayrollReportData($payrollId);
            
            if (!$planillaData) {
                error_log("ExcelReportController: Planilla no encontrada para ID: $payrollId");
                $_SESSION['error'] = 'Planilla no encontrada';
                $this->redirect('/panel/reports');
                return;
            }

            error_log("ExcelReportController: Planilla encontrada, empleados: " . count($planillaData['employees'] ?? []));

            // Obtener información de la empresa
            $companyInfo = $this->getCompanyInfo();
            
            // Obtener firmas
            $signatures = $this->getSignatures();
            
            // Generar Excel
            $this->generateExcelReport($planillaData, $companyInfo, $signatures);
            
        } catch (\Exception $e) {
            error_log('Error generando Excel: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al generar el reporte Excel: ' . $e->getMessage();
            $this->redirect('/panel/reports');
        }
    }

    /**
     * Generar el archivo Excel usando PhpSpreadsheet
     */
    private function generateExcelReport($planillaData, $companyInfo, $signatures)
    {
        $payroll = $planillaData['payroll'];
        $employees = $planillaData['employees'] ?? [];

        // Crear nueva instancia de Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar propiedades del documento
        $spreadsheet->getProperties()
            ->setCreator('Sistema de Planillas MVC')
            ->setTitle('Planilla de Sueldos - ' . $payroll['descripcion'])
            ->setSubject('Reporte de Planilla')
            ->setDescription('Planilla generada por Sistema MVC')
            ->setKeywords('planilla sueldos panama')
            ->setCategory('Recursos Humanos');

        // Configurar el worksheet
        $sheet->setTitle('Planilla');

        // Generar contenido
        $this->buildExcelContent($sheet, $payroll, $employees, $companyInfo);

        // Nombre del archivo
        $filename = 'Planilla_Panama_' . $payroll['id'] . '_' . date('Y-m-d') . '.xlsx';

        // Limpiar buffer de salida
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Configurar headers para descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        // Crear writer y generar archivo
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Generar contenido HTML para Excel
     */
    private function generateExcelContent($payroll, $employees, $companyInfo, $signatures)
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
        $html .= $this->getExcelStyles();
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
                $html .= $this->generateEmployeeRow($emp, $numeroEmpleado, $totalesNivel, $totalesGenerales);
                $numeroEmpleado++;
            }
            
            // Subtotal del nivel
            $html .= $this->generateSubtotalRow($nivel, $totalesNivel);
            
            // Fila vacía entre niveles
            $html .= '<tr><td colspan="24" style="height:5px;"></td></tr>';
        }
        
        // Total general
        $html .= $this->generateGrandTotalRow($totalesGenerales);
        
        // Firmas
        $html .= $this->generateSignatureSection($signatures);
        
        $html .= '</table>';
        $html .= '</body>';
        $html .= '</html>';
        
        return $html;
    }

    /**
     * Obtener estilos CSS para Excel
     */
    private function getExcelStyles()
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
     * Agrupar empleados por nivel organizativo
     */
    private function groupEmployeesByLevel($employees)
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
    private function initializeTotals()
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
     * Generar fila de empleado
     */
    private function generateEmployeeRow($emp, $numeroEmpleado, &$totalesNivel, &$totalesGenerales)
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
     * Generar fila de subtotal
     */
    private function generateSubtotalRow($nivel, $totales)
    {
        $html = '<tr class="subtotal">';
        $html .= '<td colspan="5" style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;padding:4px;">SUBTOTAL ' . strtoupper($nivel) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['salario_mensual'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['gasto_rep_mensual'], 2) . '</td>';
        $html .= '<td style="background-color:#E2EFDA;border:1px solid #000;"></td>'; // I.S.R. CLAVE
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['salario_quincenal'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:center;background-color:#E2EFDA;border:1px solid #000;">' . $totales['dias_laborados'] . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['horas'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['incapacidades'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['gasto_rep_quinc'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['salario_retroactivo'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['desc_ausencias'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['salario_bruto'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['bonificacion'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['seguro_social'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['ss_gasto_rep'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['seguro_educativo'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['isr'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['desc_gasto_rep'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['desc_varios'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#E2EFDA;border:1px solid #000;">$' . number_format($totales['salario_neto'], 2) . '</td>';
        $html .= '</tr>';
        
        return $html;
    }

    /**
     * Generar fila de total general
     */
    private function generateGrandTotalRow($totales)
    {
        $html = '<tr class="grand-total">';
        $html .= '<td colspan="5" style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;padding:4px;">TOTAL GENERAL</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['salario_mensual'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['gasto_rep_mensual'], 2) . '</td>';
        $html .= '<td style="background-color:#C5E0B4;border:2px solid #000;"></td>'; // I.S.R. CLAVE
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['salario_quincenal'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:center;background-color:#C5E0B4;border:2px solid #000;">' . $totales['dias_laborados'] . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['horas'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['incapacidades'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['gasto_rep_quinc'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['salario_retroactivo'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['desc_ausencias'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['salario_bruto'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['bonificacion'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['seguro_social'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['ss_gasto_rep'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['seguro_educativo'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['isr'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['desc_gasto_rep'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['desc_varios'], 2) . '</td>';
        $html .= '<td style="font-weight:bold;text-align:right;background-color:#C5E0B4;border:2px solid #000;">$' . number_format($totales['salario_neto'], 2) . '</td>';
        $html .= '</tr>';
        
        return $html;
    }

    /**
     * Generar sección de firmas
     */
    private function generateSignatureSection($signatures)
    {
        $html = '<tr><td colspan="24" style="height:20px;"></td></tr>';
        
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
        
        return $html;
    }

    /**
     * Obtener monto de un concepto específico
     */
    private function getConceptAmount($employee, $conceptCode, $type = 'ingreso')
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
    private function addToTotals(&$totales, $values)
    {
        foreach ($values as $key => $value) {
            if (isset($totales[$key])) {
                $totales[$key] += $value;
            }
        }
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
                error_log("ExcelReportController: No se encontró planilla con ID: $payrollId en planilla_cabecera");
                return null;
            }

            error_log("ExcelReportController: Planilla encontrada: " . $payroll['descripcion']);
            
            // Obtener tipo de empresa primero
            $companySQL = "SELECT tipo_institucion FROM companies WHERE id = 1";
            $companyStmt = $connection->prepare($companySQL);
            $companyStmt->execute();
            $company = $companyStmt->fetch();
            $tipoEmpresa = $company['tipo_institucion'] ?? 'privada';
            
            // Empleados de la planilla con sus conceptos calculados
            $sql = "SELECT
                        e.id AS employee_id,
                        e.employee_id AS employee_code,
                        e.firstname,
                        e.lastname,
                        e.document_id as cedula,
                        e.document_id,
                        e.fecha_ingreso,
                        " . ($tipoEmpresa === 'publica'
                            ? "COALESCE(pos.sueldo, 0) as salary, pos.codigo as puesto_actual, f.nombre as funcion_name"
                            : "COALESCE(e.sueldo_individual, 0) as salary, c2.nombre as puesto_actual, f.nombre as funcion_name") . ",
                        pd.monto AS concepto_monto,
                        pd.referencia_valor as reference_value,
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
                        LEFT JOIN cargos c2 ON c2.id = e.cargo_id
                        LEFT JOIN funciones f ON f.id = e.funcion_id
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

            error_log("ExcelReportController: Conceptos encontrados: " . count($conceptsData));

            // Organizar datos por empleado
            $employees = [];
            foreach ($conceptsData as $row) {
                $empKey = $row['employee_id'];
                
                if (!isset($employees[$empKey])) {
                    $employees[$empKey] = [
                        'id' => $row['employee_id'],
                        'employee_id' => $row['employee_id'],
                        'firstname' => $row['firstname'],
                        'lastname' => $row['lastname'],
                        'document_id' => $row['document_id'],
                        'fecha_ingreso' => $row['fecha_ingreso'],
                        'salary' => $row['salary'],
                        'puesto_actual' => $row['puesto_actual'],
                        'funcion_name' => $row['funcion_name'],
                        'reference_value' => $row['reference_value'] ?? 15,
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
                
                // Calcular totales
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
                            $employees[$empKey]['totals']['otras_deducciones'] += $monto;
                            break;
                    }
                }
                
                // Calcular neto
                $employees[$empKey]['totals']['neto'] = 
                    $employees[$empKey]['totals']['ingresos'] - $employees[$empKey]['totals']['deducciones'];
            }
            
            // Si no hay empleados con conceptos, intentar obtener empleados básicos sin conceptos
            if (empty($employees)) {
                error_log("ExcelReportController: No hay empleados con conceptos, intentando obtener empleados básicos");

                $basicEmployeeSql = "SELECT DISTINCT
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

                $basicStmt = $connection->prepare($basicEmployeeSql);
                $basicStmt->execute([$payrollId]);
                $basicEmployees = $basicStmt->fetchAll();

                error_log("ExcelReportController: Empleados básicos encontrados: " . count($basicEmployees));

                foreach ($basicEmployees as $emp) {
                    $employees[$emp['employee_id']] = [
                        'id' => $emp['employee_id'],
                        'employee_id' => $emp['employee_id'],
                        'firstname' => $emp['firstname'],
                        'lastname' => $emp['lastname'],
                        'document_id' => $emp['document_id'],
                        'fecha_ingreso' => $emp['fecha_ingreso'],
                        'salary' => $emp['salary'],
                        'puesto_actual' => '',
                        'funcion_name' => '',
                        'reference_value' => 15,
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
            }

            error_log("ExcelReportController: Total empleados a retornar: " . count($employees));

            return [
                'payroll' => $payroll,
                'employees' => array_values($employees)
            ];
            
        } catch (\Exception $e) {
            error_log("Error obteniendo datos del reporte Excel: " . $e->getMessage());
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
                'company_name' => $company['nombre'] ?? 'EMPRESA EJEMPLO S.A.',
                'ruc' => $company['ruc'] ?? '1234567890-1-DV',
                'address' => $company['direccion'] ?? 'Dirección Empresa'
            ];
        } catch (\Exception $e) {
            return [
                'company_name' => 'EMPRESA EJEMPLO S.A.',
                'ruc' => '1234567890-1-DV',
                'address' => 'Dirección Empresa'
            ];
        }
    }

    /**
     * Obtener firmas para el reporte
     */
    private function getSignatures()
    {
        return [
            'elaborado_por' => $_SESSION['signature_elaborado_por'] ?? 'Sistema Automático',
            'jefe_recursos_humanos' => $_SESSION['signature_jefe_rrhh'] ?? 'Director RRHH',
            'cargo_elaborador' => $_SESSION['cargo_elaborador'] ?? 'Especialista en Nóminas',
            'cargo_jefe_rrhh' => $_SESSION['cargo_jefe_rrhh'] ?? 'Jefe de Recursos Humanos'
        ];
    }

    /**
     * Construir contenido del Excel usando PhpSpreadsheet
     */
    private function buildExcelContent($sheet, $payroll, $employees, $companyInfo)
    {
        $fechaInicio = date('d/m/Y', strtotime($payroll['fecha_inicio']));
        $fechaFin = date('d/m/Y', strtotime($payroll['fecha_fin']));

        $row = 1;

        // Header de la empresa
        $sheet->setCellValue('A' . $row, $companyInfo['company_name']);
        $sheet->mergeCells('A' . $row . ':O' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
        ]);
        $row++;

        // Subtítulo
        $sheet->setCellValue('A' . $row, 'PLANILLA DE SUELDOS - ' . strtoupper($payroll['descripcion']));
        $sheet->mergeCells('A' . $row . ':O' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '8FAADC']]
        ]);
        $row++;

        // Período
        $sheet->setCellValue('A' . $row, 'Período: ' . $fechaInicio . ' al ' . $fechaFin);
        $sheet->mergeCells('A' . $row . ':O' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E2F3']]
        ]);
        $row += 2; // Fila vacía

        // Headers de columnas
        $headers = [
            'A' => 'Cédula',
            'B' => 'Apellidos',
            'C' => 'Nombres',
            'D' => 'Puesto',
            'E' => 'Función',
            'F' => 'Fecha Ingreso',
            'G' => 'Sueldo Base',
            'H' => 'Total Ingresos',
            'I' => 'Seguro Social',
            'J' => 'Seguro Educativo',
            'K' => 'ISR',
            'L' => 'Otras Deducciones',
            'M' => 'Total Deducciones',
            'N' => 'Salario Neto',
            'O' => 'Días Laborados'
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
        }
        $row++;

        // Datos de empleados
        $totalesGenerales = $this->initializePayrollTotals();

        foreach ($employees as $emp) {
            $totales = $this->calculatePayrollTotals($emp);
            $this->accumulatePayrollTotals($totalesGenerales, $totales);

            $sheet->setCellValue('A' . $row, $emp['document_id'] ?? 'N/A');
            $sheet->setCellValue('B' . $row, $emp['lastname'] ?? '');
            $sheet->setCellValue('C' . $row, $emp['firstname'] ?? '');
            $sheet->setCellValue('D' . $row, $emp['puesto_actual'] ?? 'N/A');
            $sheet->setCellValue('E' . $row, $emp['funcion_name'] ?? 'N/A');
            $sheet->setCellValue('F' . $row, !empty($emp['fecha_ingreso']) ? date('d/m/Y', strtotime($emp['fecha_ingreso'])) : 'N/A');
            $sheet->setCellValue('G' . $row, $emp['salary'] ?? 0);
            $sheet->setCellValue('H' . $row, $totales['ingresos']);
            $sheet->setCellValue('I' . $row, $totales['seguro_social']);
            $sheet->setCellValue('J' . $row, $totales['seguro_educativo']);
            $sheet->setCellValue('K' . $row, $totales['impuesto_renta']);
            $sheet->setCellValue('L' . $row, $totales['otras_deducciones']);
            $sheet->setCellValue('M' . $row, $totales['deducciones']);
            $sheet->setCellValue('N' . $row, $totales['neto']);
            $sheet->setCellValue('O' . $row, $totales['dias_laborados']);

            // Aplicar formatos
            $sheet->getStyle('A' . $row . ':O' . $row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            // Formato moneda para columnas numéricas
            $sheet->getStyle('G' . $row . ':N' . $row)->getNumberFormat()
                  ->setFormatCode('$#,##0.00');

            $row++;
        }

        // Fila de totales
        $sheet->setCellValue('A' . $row, 'TOTALES GENERALES');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->setCellValue('H' . $row, $totalesGenerales['ingresos']);
        $sheet->setCellValue('I' . $row, $totalesGenerales['seguro_social']);
        $sheet->setCellValue('J' . $row, $totalesGenerales['seguro_educativo']);
        $sheet->setCellValue('K' . $row, $totalesGenerales['impuesto_renta']);
        $sheet->setCellValue('L' . $row, $totalesGenerales['otras_deducciones']);
        $sheet->setCellValue('M' . $row, $totalesGenerales['deducciones']);
        $sheet->setCellValue('N' . $row, $totalesGenerales['neto']);
        $sheet->setCellValue('O' . $row, $totalesGenerales['dias_laborados']);

        // Estilo para totales
        $sheet->getStyle('A' . $row . ':O' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C5E0B4']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK]]
        ]);

        $sheet->getStyle('H' . $row . ':N' . $row)->getNumberFormat()
              ->setFormatCode('$#,##0.00');

        // Ajustar anchos de columnas
        $this->autoSizeColumns($sheet);

        // Congelar paneles en headers
        $sheet->freezePane('A6');
    }

    /**
     * Inicializar totales para planilla
     */
    private function initializePayrollTotals()
    {
        return [
            'ingresos' => 0,
            'deducciones' => 0,
            'seguro_social' => 0,
            'seguro_educativo' => 0,
            'impuesto_renta' => 0,
            'otras_deducciones' => 0,
            'neto' => 0,
            'dias_laborados' => 0
        ];
    }

    /**
     * Calcular totales de un empleado
     */
    private function calculatePayrollTotals($emp)
    {
        // Los totales ya vienen calculados en la estructura del empleado
        $totals = $emp['totals'] ?? [];
        $diasLaborados = $emp['reference_value'] ?? 15; // Campo referencia como días laborados

        return [
            'ingresos' => $totals['ingresos'] ?? 0,
            'deducciones' => $totals['deducciones'] ?? 0,
            'seguro_social' => $totals['seguro_social'] ?? 0,
            'seguro_educativo' => $totals['seguro_educativo'] ?? 0,
            'impuesto_renta' => $totals['impuesto_renta'] ?? 0,
            'otras_deducciones' => $totals['otras_deducciones'] ?? 0,
            'neto' => $totals['neto'] ?? 0,
            'dias_laborados' => $diasLaborados,
        ];
    }

    /**
     * Acumular totales
     */
    private function accumulatePayrollTotals(&$totalesGenerales, $totales)
    {
        foreach ($totales as $key => $value) {
            $totalesGenerales[$key] += $value;
        }
    }

    /**
     * Ajustar automáticamente el ancho de las columnas
     */
    private function autoSizeColumns($sheet)
    {
        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
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