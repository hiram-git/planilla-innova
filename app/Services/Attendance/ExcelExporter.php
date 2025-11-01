<?php

namespace App\Services\Attendance;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

/**
 * ExcelExporter
 *
 * Servicio para exportar reportes de asistencias a Excel
 * Utiliza PHPOffice/PhpSpreadsheet para generar archivos .xlsx
 *
 * @package App\Services\Attendance
 * @version 1.0
 */
class ExcelExporter
{
    private $spreadsheet;
    private $sheet;

    /**
     * Exportar reporte de ausencias a Excel
     *
     * @param array $report Datos del reporte generado por ReportsGenerator
     * @param string $filename Nombre del archivo (sin extensión)
     * @return void Envía el archivo al navegador
     */
    public function exportAbsencesReport($report, $filename = 'Reporte_Ausencias')
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Ausencias');

        $period = $report['period'] ?? [];
        $summary = $report['summary'] ?? [];
        $byDepartment = $report['by_department'] ?? [];
        $topAbsentEmployees = $report['top_absent_employees'] ?? [];

        $row = 1;

        // Título principal
        $this->sheet->setCellValue('A' . $row, 'REPORTE DE AUSENCIAS');
        $this->sheet->mergeCells('A' . $row . ':H' . $row);
        $this->applyHeaderStyle('A' . $row . ':H' . $row);
        $row++;

        // Información del período
        $row++;
        $this->sheet->setCellValue('A' . $row, 'Período:');
        $this->sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($period['start_date'])) . ' - ' . date('d/m/Y', strtotime($period['end_date'])));
        $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        if ($period['tipo_planilla_id']) {
            $this->sheet->setCellValue('A' . $row, 'Tipo de Planilla:');
            $this->sheet->setCellValue('B' . $row, 'ID: ' . $period['tipo_planilla_id']);
            $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        $this->sheet->setCellValue('A' . $row, 'Fecha de Generación:');
        $this->sheet->setCellValue('B' . $row, date('d/m/Y H:i:s'));
        $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 2;
/*
        // Resumen estadístico
        $this->sheet->setCellValue('A' . $row, 'RESUMEN ESTADÍSTICO');
        $this->sheet->mergeCells('A' . $row . ':H' . $row);
        $this->applySubHeaderStyle('A' . $row . ':H' . $row);
        $row++;

        $summaryData = [
            ['Total Ausencias:', $summary['total_absences'] ?? 0],
            ['Justificadas:', $summary['justified'] ?? 0],
            ['Injustificadas:', $summary['unjustified'] ?? 0],
            ['Empleados Afectados:', $summary['affected_employees'] ?? 0],
            ['Departamentos Afectados:', $summary['departments_affected'] ?? 0]
        ];

        foreach ($summaryData as $data) {
            $this->sheet->setCellValue('A' . $row, $data[0]);
            $this->sheet->setCellValue('B' . $row, $data[1]);
            $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }
        $row += 2;

        // Top 10 empleados con más ausencias
        if (!empty($topAbsentEmployees)) {
            $this->sheet->setCellValue('A' . $row, 'TOP 10 EMPLEADOS CON MÁS AUSENCIAS');
            $this->sheet->mergeCells('A' . $row . ':H' . $row);
            $this->applySubHeaderStyle('A' . $row . ':H' . $row);
            $row++;

            // Encabezados
            $headers = ['#', 'ID Empleado', 'Cédula', 'Nombre Completo', 'Departamento', 'Cargo', 'Total Ausencias', 'Injustificadas'];
            $col = 'A';
            foreach ($headers as $header) {
                $this->sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $this->applyTableHeaderStyle('A' . $row . ':H' . $row);
            $row++;

            // Datos
            $index = 1;
            foreach ($topAbsentEmployees as $emp) {
                $this->sheet->setCellValue('A' . $row, $index++);
                $this->sheet->setCellValue('B' . $row, $emp['employee_code']);
                $this->sheet->setCellValue('C' . $row, $emp['cedula']);
                $this->sheet->setCellValue('D' . $row, $emp['full_name']);
                $this->sheet->setCellValue('E' . $row, $emp['departamento'] ?? 'N/A');
                $this->sheet->setCellValue('F' . $row, $emp['position_name'] ?? 'N/A');
                $this->sheet->setCellValue('G' . $row, $emp['total_absences']);
                $this->sheet->setCellValue('H' . $row, $emp['unjustified_count']);
                $this->applyTableRowStyle('A' . $row . ':H' . $row);
                $row++;
            }
            $row += 2;
        }*/

        // Ausencias por departamento
        foreach ($byDepartment as $deptName => $absences) {
            $this->sheet->setCellValue('A' . $row, 'DEPARTAMENTO: ' . $deptName);
            $this->sheet->mergeCells('A' . $row . ':G' . $row);
            $this->applyDepartmentHeaderStyle('A' . $row . ':G' . $row);
            $row++;

            // Encabezados
            $headers = ['ID', 'Cédula', 'Apellidos y Nombres', 'Cargo', 'Fecha', 'Tipo', 'Justificación'];
            $col = 'A';
            foreach ($headers as $header) {
                $this->sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $this->applyTableHeaderStyle('A' . $row . ':G' . $row);
            $row++;

            // Datos
            foreach ($absences as $absence) {
                $this->sheet->setCellValue('A' . $row, $absence['employee_code']);
                $this->sheet->setCellValue('B' . $row, $absence['cedula']);
                $this->sheet->setCellValue('C' . $row, $absence['full_name']);
                $this->sheet->setCellValue('D' . $row, $absence['position_name'] ?? 'N/A');
                $this->sheet->setCellValue('E' . $row, date('d/m/Y', strtotime($absence['absence_date'])));

                // Tipo de ausencia
                $typeText = match($absence['absence_type']) {
                    'JUSTIFIED' => 'Justificada',
                    'UNJUSTIFIED' => 'Injustificada',
                    'PENDING' => 'Pendiente',
                    default => $absence['absence_type']
                };
                $this->sheet->setCellValue('F' . $row, $typeText);

                // Justificación
                $justification = ($absence['justified'] && $absence['justification_type'])
                    ? $absence['justification_type']
                    : '-';
                $this->sheet->setCellValue('G' . $row, $justification);

                $this->applyTableRowStyle('A' . $row . ':G' . $row);
                $row++;
            }
            $row += 2;
        }

        // Ajustar anchos de columna
        $this->sheet->getColumnDimension('A')->setWidth(12);
        $this->sheet->getColumnDimension('B')->setWidth(15);
        $this->sheet->getColumnDimension('C')->setWidth(30);
        $this->sheet->getColumnDimension('D')->setWidth(25);
        $this->sheet->getColumnDimension('E')->setWidth(15);
        $this->sheet->getColumnDimension('F')->setWidth(18);
        $this->sheet->getColumnDimension('G')->setWidth(20);
        $this->sheet->getColumnDimension('H')->setWidth(18);

        // Descargar archivo
        $this->downloadExcel($filename);
    }

    /**
     * Exportar reporte de tardanzas a Excel
     *
     * @param array $report Datos del reporte generado por ReportsGenerator
     * @param string $filename Nombre del archivo (sin extensión)
     * @return void Envía el archivo al navegador
     */
    public function exportTardinessReport($report, $filename = 'Reporte_Tardanzas')
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Tardanzas');

        $period = $report['period'] ?? [];
        $summary = $report['summary'] ?? [];
        $byDepartment = $report['by_department'] ?? [];
        $topTardinessEmployees = $report['top_tardiness_employees'] ?? [];

        $row = 1;

        // Título principal
        $this->sheet->setCellValue('A' . $row, 'REPORTE DE TARDANZAS');
        $this->sheet->mergeCells('A' . $row . ':I' . $row);
        $this->applyHeaderStyle('A' . $row . ':I' . $row);
        $row++;

        // Información del período
        $row++;
        $this->sheet->setCellValue('A' . $row, 'Período:');
        $this->sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($period['start_date'])) . ' - ' . date('d/m/Y', strtotime($period['end_date'])));
        $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        if ($period['tipo_planilla_id']) {
            $this->sheet->setCellValue('A' . $row, 'Tipo de Planilla:');
            $this->sheet->setCellValue('B' . $row, 'ID: ' . $period['tipo_planilla_id']);
            $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        $this->sheet->setCellValue('A' . $row, 'Fecha de Generación:');
        $this->sheet->setCellValue('B' . $row, date('d/m/Y H:i:s'));
        $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 2;

        // Resumen estadístico
        $this->sheet->setCellValue('A' . $row, 'RESUMEN ESTADÍSTICO');
        $this->sheet->mergeCells('A' . $row . ':I' . $row);
        $this->applySubHeaderStyle('A' . $row . ':I' . $row);
        $row++;

        $summaryData = [
            ['Total Tardanzas:', $summary['total_tardiness'] ?? 0],
            ['Minutos Totales:', $summary['total_minutes'] ?? 0],
            ['Promedio Min/Tardanza:', number_format($summary['avg_minutes'] ?? 0, 1)],
            ['Empleados Afectados:', $summary['affected_employees'] ?? 0]
        ];

        foreach ($summaryData as $data) {
            $this->sheet->setCellValue('A' . $row, $data[0]);
            $this->sheet->setCellValue('B' . $row, $data[1]);
            $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }
        $row += 2;

        // Top 10 empleados con más tardanzas
        if (!empty($topTardinessEmployees)) {
            $this->sheet->setCellValue('A' . $row, 'TOP 10 EMPLEADOS CON MÁS TARDANZAS');
            $this->sheet->mergeCells('A' . $row . ':I' . $row);
            $this->applySubHeaderStyle('A' . $row . ':I' . $row);
            $row++;

            // Encabezados
            $headers = ['#', 'ID Empleado', 'Cédula', 'Nombre Completo', 'Departamento', 'Cargo', 'Total Tardanzas', 'Min. Totales', 'Promedio Min.'];
            $col = 'A';
            foreach ($headers as $header) {
                $this->sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $this->applyTableHeaderStyle('A' . $row . ':I' . $row);
            $row++;

            // Datos
            $index = 1;
            foreach ($topTardinessEmployees as $emp) {
                $this->sheet->setCellValue('A' . $row, $index++);
                $this->sheet->setCellValue('B' . $row, $emp['employee_code']);
                $this->sheet->setCellValue('C' . $row, $emp['cedula']);
                $this->sheet->setCellValue('D' . $row, $emp['full_name']);
                $this->sheet->setCellValue('E' . $row, $emp['departamento'] ?? 'N/A');
                $this->sheet->setCellValue('F' . $row, $emp['position_name'] ?? 'N/A');
                $this->sheet->setCellValue('G' . $row, $emp['total_tardiness']);
                $this->sheet->setCellValue('H' . $row, number_format($emp['total_minutes']));
                $this->sheet->setCellValue('I' . $row, number_format($emp['avg_minutes'], 1));
                $this->applyTableRowStyle('A' . $row . ':I' . $row);
                $row++;
            }
            $row += 2;
        }

        // Tardanzas por departamento
        foreach ($byDepartment as $deptName => $tardiness) {
            $this->sheet->setCellValue('A' . $row, 'DEPARTAMENTO: ' . $deptName);
            $this->sheet->mergeCells('A' . $row . ':I' . $row);
            $this->applyDepartmentHeaderStyle('A' . $row . ':I' . $row);
            $row++;

            // Encabezados
            $headers = ['ID', 'Cédula', 'Apellidos y Nombres', 'Cargo', 'Fecha', 'Hora Entrada', 'Hora Esperada', 'Min. Tarde', 'Gravedad'];
            $col = 'A';
            foreach ($headers as $header) {
                $this->sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $this->applyTableHeaderStyle('A' . $row . ':I' . $row);
            $row++;

            // Datos
            foreach ($tardiness as $t) {
                $this->sheet->setCellValue('A' . $row, $t['employee_code']);
                $this->sheet->setCellValue('B' . $row, $t['cedula']);
                $this->sheet->setCellValue('C' . $row, $t['full_name']);
                $this->sheet->setCellValue('D' . $row, $t['position_name'] ?? 'N/A');
                $this->sheet->setCellValue('E' . $row, date('d/m/Y', strtotime($t['tardiness_date'])));
                $this->sheet->setCellValue('F' . $row, date('H:i', strtotime($t['check_in_time'])));
                $this->sheet->setCellValue('G' . $row, date('H:i', strtotime($t['expected_time'])));
                $this->sheet->setCellValue('H' . $row, number_format($t['minutes_late']));

                // Gravedad
                $minutes = $t['minutes_late'];
                $severity = match(true) {
                    $minutes <= 5 => 'Leve',
                    $minutes <= 15 => 'Moderada',
                    $minutes <= 30 => 'Severa',
                    default => 'Crítica'
                };
                $this->sheet->setCellValue('I' . $row, $severity);

                $this->applyTableRowStyle('A' . $row . ':I' . $row);
                $row++;
            }
            $row += 2;
        }

        // Ajustar anchos de columna
        $this->sheet->getColumnDimension('A')->setWidth(12);
        $this->sheet->getColumnDimension('B')->setWidth(15);
        $this->sheet->getColumnDimension('C')->setWidth(30);
        $this->sheet->getColumnDimension('D')->setWidth(25);
        $this->sheet->getColumnDimension('E')->setWidth(15);
        $this->sheet->getColumnDimension('F')->setWidth(15);
        $this->sheet->getColumnDimension('G')->setWidth(15);
        $this->sheet->getColumnDimension('H')->setWidth(15);
        $this->sheet->getColumnDimension('I')->setWidth(15);

        // Descargar archivo
        $this->downloadExcel($filename);
    }

    /**
     * Exportar reporte combinado a Excel
     *
     * @param array $report Datos del reporte generado por ReportsGenerator
     * @param string $filename Nombre del archivo (sin extensión)
     * @return void Envía el archivo al navegador
     */
    public function exportCombinedReport($report, $filename = 'Reporte_Combinado')
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Desempeño General');

        $period = $report['period'] ?? [];
        $summary = $report['summary'] ?? [];
        $byDepartment = $report['by_department'] ?? [];
        $topPerformers = $report['top_performers'] ?? [];
        $bottomPerformers = $report['bottom_performers'] ?? [];

        $row = 1;

        // Título principal
        $this->sheet->setCellValue('A' . $row, 'REPORTE COMBINADO DE ASISTENCIAS');
        $this->sheet->mergeCells('A' . $row . ':J' . $row);
        $this->applyHeaderStyle('A' . $row . ':J' . $row);
        $row++;

        // Información del período
        $row++;
        $this->sheet->setCellValue('A' . $row, 'Período:');
        $this->sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($period['start_date'])) . ' - ' . date('d/m/Y', strtotime($period['end_date'])));
        $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        if ($period['tipo_planilla_id']) {
            $this->sheet->setCellValue('A' . $row, 'Tipo de Planilla:');
            $this->sheet->setCellValue('B' . $row, 'ID: ' . $period['tipo_planilla_id']);
            $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        $this->sheet->setCellValue('A' . $row, 'Fecha de Generación:');
        $this->sheet->setCellValue('B' . $row, date('d/m/Y H:i:s'));
        $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 2;

        // Resumen estadístico
        $this->sheet->setCellValue('A' . $row, 'RESUMEN GENERAL');
        $this->sheet->mergeCells('A' . $row . ':J' . $row);
        $this->applySubHeaderStyle('A' . $row . ':J' . $row);
        $row++;

        $summaryData = [
            ['Total Empleados:', $summary['total_employees'] ?? 0],
            ['Total Ausencias:', $summary['total_absences'] ?? 0],
            ['Total Tardanzas:', $summary['total_tardiness'] ?? 0],
            ['Minutos de Tardanza:', $summary['total_tardiness_minutes'] ?? 0],
            ['Score Promedio:', number_format($summary['avg_performance_score'] ?? 0, 1)]
        ];

        foreach ($summaryData as $data) {
            $this->sheet->setCellValue('A' . $row, $data[0]);
            $this->sheet->setCellValue('B' . $row, $data[1]);
            $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }
        $row += 2;

        // Top 10 mejores desempeños
        if (!empty($topPerformers)) {
            $this->sheet->setCellValue('A' . $row, 'TOP 10 MEJORES DESEMPEÑOS');
            $this->sheet->mergeCells('A' . $row . ':F' . $row);
            $this->applySubHeaderStyle('A' . $row . ':F' . $row, '28A745');
            $row++;

            // Encabezados
            $headers = ['#', 'ID', 'Nombre Completo', 'Departamento', 'Score', 'Evaluación'];
            $col = 'A';
            foreach ($headers as $header) {
                $this->sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $this->applyTableHeaderStyle('A' . $row . ':F' . $row);
            $row++;

            // Datos
            $index = 1;
            foreach ($topPerformers as $emp) {
                $this->sheet->setCellValue('A' . $row, $index++);
                $this->sheet->setCellValue('B' . $row, $emp['employee_code']);
                $this->sheet->setCellValue('C' . $row, $emp['full_name']);
                $this->sheet->setCellValue('D' . $row, $emp['departamento'] ?? 'N/A');
                $this->sheet->setCellValue('E' . $row, number_format($emp['performance_score'], 1));

                $score = $emp['performance_score'];
                $evaluation = match(true) {
                    $score >= 90 => 'Excelente',
                    $score >= 80 => 'Bueno',
                    $score >= 70 => 'Regular',
                    $score >= 60 => 'Necesita Mejorar',
                    default => 'Crítico'
                };
                $this->sheet->setCellValue('F' . $row, $evaluation);

                $this->applyTableRowStyle('A' . $row . ':F' . $row);
                $row++;
            }
            $row += 2;
        }

        // Top 10 necesitan atención
        if (!empty($bottomPerformers)) {
            $this->sheet->setCellValue('A' . $row, 'TOP 10 NECESITAN ATENCIÓN');
            $this->sheet->mergeCells('A' . $row . ':F' . $row);
            $this->applySubHeaderStyle('A' . $row . ':F' . $row, 'DC3545');
            $row++;

            // Encabezados
            $headers = ['#', 'ID', 'Nombre Completo', 'Departamento', 'Score', 'Evaluación'];
            $col = 'A';
            foreach ($headers as $header) {
                $this->sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $this->applyTableHeaderStyle('A' . $row . ':F' . $row);
            $row++;

            // Datos
            $index = 1;
            foreach ($bottomPerformers as $emp) {
                $this->sheet->setCellValue('A' . $row, $index++);
                $this->sheet->setCellValue('B' . $row, $emp['employee_code']);
                $this->sheet->setCellValue('C' . $row, $emp['full_name']);
                $this->sheet->setCellValue('D' . $row, $emp['departamento'] ?? 'N/A');
                $this->sheet->setCellValue('E' . $row, number_format($emp['performance_score'], 1));

                $score = $emp['performance_score'];
                $evaluation = match(true) {
                    $score >= 90 => 'Excelente',
                    $score >= 80 => 'Bueno',
                    $score >= 70 => 'Regular',
                    $score >= 60 => 'Necesita Mejorar',
                    default => 'Crítico'
                };
                $this->sheet->setCellValue('F' . $row, $evaluation);

                $this->applyTableRowStyle('A' . $row . ':F' . $row);
                $row++;
            }
            $row += 2;
        }

        // Desempeño por departamento
        foreach ($byDepartment as $deptName => $employees) {
            $this->sheet->setCellValue('A' . $row, 'DEPARTAMENTO: ' . $deptName);
            $this->sheet->mergeCells('A' . $row . ':J' . $row);
            $this->applyDepartmentHeaderStyle('A' . $row . ':J' . $row);
            $row++;

            // Encabezados
            $headers = ['ID', 'Cédula', 'Apellidos y Nombres', 'Cargo', 'Ausencias', 'Injustific.', 'Tardanzas', 'Min. Tarde', 'Score', 'Evaluación'];
            $col = 'A';
            foreach ($headers as $header) {
                $this->sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $this->applyTableHeaderStyle('A' . $row . ':J' . $row);
            $row++;

            // Datos
            foreach ($employees as $emp) {
                $this->sheet->setCellValue('A' . $row, $emp['employee_code']);
                $this->sheet->setCellValue('B' . $row, $emp['cedula']);
                $this->sheet->setCellValue('C' . $row, $emp['full_name']);
                $this->sheet->setCellValue('D' . $row, $emp['position_name'] ?? 'N/A');
                $this->sheet->setCellValue('E' . $row, $emp['total_absences']);
                $this->sheet->setCellValue('F' . $row, $emp['unjustified_absences']);
                $this->sheet->setCellValue('G' . $row, $emp['total_tardiness']);
                $this->sheet->setCellValue('H' . $row, number_format($emp['total_tardiness_minutes']));
                $this->sheet->setCellValue('I' . $row, number_format($emp['performance_score'], 1));

                $score = $emp['performance_score'];
                $evaluation = match(true) {
                    $score >= 90 => 'Excelente',
                    $score >= 80 => 'Bueno',
                    $score >= 70 => 'Regular',
                    $score >= 60 => 'Necesita Mejorar',
                    default => 'Crítico'
                };
                $this->sheet->setCellValue('J' . $row, $evaluation);

                $this->applyTableRowStyle('A' . $row . ':J' . $row);
                $row++;
            }
            $row += 2;
        }

        // Ajustar anchos de columna
        $this->sheet->getColumnDimension('A')->setWidth(12);
        $this->sheet->getColumnDimension('B')->setWidth(15);
        $this->sheet->getColumnDimension('C')->setWidth(30);
        $this->sheet->getColumnDimension('D')->setWidth(25);
        $this->sheet->getColumnDimension('E')->setWidth(12);
        $this->sheet->getColumnDimension('F')->setWidth(12);
        $this->sheet->getColumnDimension('G')->setWidth(12);
        $this->sheet->getColumnDimension('H')->setWidth(12);
        $this->sheet->getColumnDimension('I')->setWidth(12);
        $this->sheet->getColumnDimension('J')->setWidth(18);

        // Descargar archivo
        $this->downloadExcel($filename);
    }

    /**
     * Exportar reporte de marcaciones (punches) a Excel
     *
     * @param array $report Datos del reporte generado por ReportsGenerator
     * @param string $filename Nombre del archivo (sin extensión)
     * @return void Envía el archivo al navegador
     */
    public function exportPunchesReport($report, $filename = 'Reporte_Marcaciones')
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Marcaciones');

        $period = $report['period'] ?? [];
        $summary = $report['summary'] ?? [];
        $byDepartment = $report['by_department'] ?? [];
        $topTardinessEmployees = $report['top_tardiness_employees'] ?? [];

        $row = 1;

        // Título principal
        $this->sheet->setCellValue('A' . $row, 'REPORTE DE MARCACIONES');
        $this->sheet->mergeCells('A' . $row . ':J' . $row);
        $this->applyHeaderStyle('A' . $row . ':J' . $row);
        $row++;

        // Información del período
        $row++;
        $this->sheet->setCellValue('A' . $row, 'Período:');
        $this->sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($period['start_date'])) . ' - ' . date('d/m/Y', strtotime($period['end_date'])));
        $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        if ($period['tipo_planilla_id']) {
            $this->sheet->setCellValue('A' . $row, 'Tipo de Planilla:');
            $this->sheet->setCellValue('B' . $row, 'ID: ' . $period['tipo_planilla_id']);
            $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        $this->sheet->setCellValue('A' . $row, 'Fecha de Generación:');
        $this->sheet->setCellValue('B' . $row, date('d/m/Y H:i:s'));
        $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 2;

        // Resumen estadístico
        /*$this->sheet->setCellValue('A' . $row, 'RESUMEN ESTADÍSTICO');
        $this->sheet->mergeCells('A' . $row . ':J' . $row);
        $this->applySubHeaderStyle('A' . $row . ':J' . $row);
        $row++;

        $summaryData = [
            ['Total Marcaciones:', $summary['total_punches'] ?? 0],
            ['Marcaciones a Tiempo:', $summary['total_on_time'] ?? 0],
            ['Con Tardanza:', $summary['total_late'] ?? 0],
            ['Ausencias Detectadas:', $summary['total_absences'] ?? 0],
            ['Horas Totales Trabajadas:', number_format($summary['total_hours_worked'] ?? 0, 1) . 'h'],
            ['Horas Extras Total:', number_format($summary['total_overtime'] ?? 0, 1) . 'h'],
            ['Promedio Horas/Día:', number_format($summary['avg_hours_per_day'] ?? 0, 1) . 'h'],
            ['Minutos Tardanza Total:', number_format($summary['total_tardiness_minutes'] ?? 0) . ' min']
        ];

        foreach ($summaryData as $data) {
            $this->sheet->setCellValue('A' . $row, $data[0]);
            $this->sheet->setCellValue('B' . $row, $data[1]);
            $this->sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }
        $row += 2;

        // Top 10 empleados con más tardanzas
        if (!empty($topTardinessEmployees)) {
            $this->sheet->setCellValue('A' . $row, 'TOP 10 EMPLEADOS CON MÁS TARDANZAS');
            $this->sheet->mergeCells('A' . $row . ':I' . $row);
            $this->applySubHeaderStyle('A' . $row . ':I' . $row, 'FFC107');
            $row++;

            // Encabezados
            $headers = ['#', 'ID Empleado', 'Cédula', 'Nombre Completo', 'Departamento', 'Cargo', 'Días Tarde', 'Min. Totales', 'Horas Totales'];
            $col = 'A';
            foreach ($headers as $header) {
                $this->sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $this->applyTableHeaderStyle('A' . $row . ':I' . $row);
            $row++;

            // Datos
            $index = 1;
            foreach ($topTardinessEmployees as $emp) {
                $this->sheet->setCellValue('A' . $row, $index++);
                $this->sheet->setCellValue('B' . $row, $emp['employee_code']);
                $this->sheet->setCellValue('C' . $row, $emp['cedula']);
                $this->sheet->setCellValue('D' . $row, $emp['full_name']);
                $this->sheet->setCellValue('E' . $row, $emp['departamento'] ?? 'N/A');
                $this->sheet->setCellValue('F' . $row, $emp['position_name'] ?? 'N/A');
                $this->sheet->setCellValue('G' . $row, $emp['total_late']);
                $this->sheet->setCellValue('H' . $row, number_format($emp['total_tardiness_minutes']));
                $this->sheet->setCellValue('I' . $row, number_format($emp['total_hours'], 1));
                $this->applyTableRowStyle('A' . $row . ':I' . $row);
                $row++;
            }
            $row += 2;
        }*/

        // Marcaciones por departamento
        foreach ($byDepartment as $deptName => $punches) {
            $this->sheet->setCellValue('A' . $row, 'DEPARTAMENTO: ' . $deptName);
            $this->sheet->mergeCells('A' . $row . ':J' . $row);
            $this->applyDepartmentHeaderStyle('A' . $row . ':J' . $row);
            $row++;

            // Encabezados
            $headers = ['ID', 'Cédula', 'Apellidos y Nombres', 'Cargo', 'Fecha', 'Entrada', 'Salida', 'Horas', 'Tardanza', 'Estado'];
            $col = 'A';
            foreach ($headers as $header) {
                $this->sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $this->applyTableHeaderStyle('A' . $row . ':J' . $row);
            $row++;

            // Datos
            foreach ($punches as $p) {
                $this->sheet->setCellValue('A' . $row, $p['employee_code']);
                $this->sheet->setCellValue('B' . $row, $p['cedula']);
                $this->sheet->setCellValue('C' . $row, $p['full_name']);
                $this->sheet->setCellValue('D' . $row, $p['position_name'] ?? 'N/A');
                $this->sheet->setCellValue('E' . $row, date('d/m/Y', strtotime($p['date'])));

                // Entrada
                if ($p['is_absent']) {
                    $this->sheet->setCellValue('F' . $row, 'AUSENTE');
                } else {
                    $timeIn = $p['time_in'] ? date('H:i', strtotime($p['date'] . ' ' . $p['time_in'])) : '--:--';
                    if ($p['is_late'] && $p['tardiness_minutes'] > 0) {
                        $timeIn .= ' (' . $p['tardiness_minutes'] . ' min tarde)';
                    }
                    $this->sheet->setCellValue('F' . $row, $timeIn);
                }

                // Salida
                $this->sheet->setCellValue('G' . $row,
                    !$p['is_absent'] && $p['time_out'] ? date('H:i', strtotime($p['date'] . ' ' . $p['time_out'])) : '--:--'
                );

                // Horas
                if (!$p['is_absent']) {
                    $hoursText = number_format($p['total_hours'], 1) . 'h';
                    if ($p['overtime_hours'] > 0) {
                        $hoursText .= ' (+' . number_format($p['overtime_hours'], 1) . 'h extras)';
                    }
                    $this->sheet->setCellValue('H' . $row, $hoursText);
                } else {
                    $this->sheet->setCellValue('H' . $row, '--');
                }

                // Tardanza
                if ($p['is_absent']) {
                    $this->sheet->setCellValue('I' . $row, 'AUSENCIA');
                } elseif ($p['is_late'] && $p['tardiness_minutes'] > 0) {
                    $this->sheet->setCellValue('I' . $row, number_format($p['tardiness_minutes']) . ' min');
                } else {
                    $this->sheet->setCellValue('I' . $row, 'A tiempo');
                }

                // Estado
                $status = match(true) {
                    $p['is_absent'] => 'Ausente',
                    $p['is_late'] => 'Tarde',
                    default => 'Normal'
                };
                $this->sheet->setCellValue('J' . $row, $status);

                $this->applyTableRowStyle('A' . $row . ':J' . $row);
                $row++;
            }
            $row += 2;
        }

        // Ajustar anchos de columna
        $this->sheet->getColumnDimension('A')->setWidth(12);
        $this->sheet->getColumnDimension('B')->setWidth(15);
        $this->sheet->getColumnDimension('C')->setWidth(30);
        $this->sheet->getColumnDimension('D')->setWidth(25);
        $this->sheet->getColumnDimension('E')->setWidth(15);
        $this->sheet->getColumnDimension('F')->setWidth(20);
        $this->sheet->getColumnDimension('G')->setWidth(15);
        $this->sheet->getColumnDimension('H')->setWidth(20);
        $this->sheet->getColumnDimension('I')->setWidth(15);
        $this->sheet->getColumnDimension('J')->setWidth(15);

        // Descargar archivo
        $this->downloadExcel($filename);
    }

    // ==================== MÉTODOS DE ESTILOS ====================

    private function applyHeaderStyle($range)
    {
        $this->sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0056B3']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    private function applySubHeaderStyle($range, $color = '17A2B8')
    {
        $this->sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $color]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    private function applyDepartmentHeaderStyle($range)
    {
        $this->sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '6C757D']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    private function applyTableHeaderStyle($range)
    {
        $this->sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
    }

    private function applyTableRowStyle($range)
    {
        $this->sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DEE2E6']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    /**
     * Descargar archivo Excel al navegador
     *
     * @param string $filename Nombre del archivo sin extensión
     * @return void
     */
    private function downloadExcel($filename)
    {
        // Limpiar cualquier salida previa
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Headers para descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '_' . date('Y-m-d_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        // Generar y enviar archivo
        $writer = new Xlsx($this->spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
