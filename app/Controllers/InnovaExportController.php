<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Services\InnovaExportService;
use App\Models\Payroll;

class InnovaExportController extends Controller
{
    private $innovaExportService;
    private $payrollModel;

    public function __construct()
    {
        parent::__construct();
        $this->innovaExportService = new InnovaExportService();
        $this->payrollModel = new Payroll();
    }

    /**
     * Vista principal - Lista de planillas disponibles para exportar
     */
    public function index()
    {
        // Verificar autenticación
        $this->requireAuth();

        // Renderizar vista
        $data = [
            'title' => 'Exportación INNOVA',
            'page_title' => 'Exportación a ERP INNOVA'
        ];

        View::render('admin/innova_export/index', $data);
    }

    /**
     * Obtener datos de planillas para DataTable (AJAX)
     */
    public function getPayrollsData()
    {
        header('Content-Type: application/json');
        $this->requireAuth();

        try {
            // Obtener planillas procesadas o cerradas
            $sql = "SELECT
                        pc.id,
                        pc.descripcion,
                        pc.fecha,
                        pc.fecha_desde,
                        pc.fecha_hasta,
                        pc.estado,
                        tp.nombre as tipo_planilla,
                        f.nombre as frecuencia,
                        COUNT(DISTINCT pd.employee_id) as total_empleados,
                        SUM(CASE WHEN c.tipo_concepto = 'A' THEN pd.monto ELSE 0 END) as total_asignaciones,
                        SUM(CASE WHEN c.tipo_concepto = 'D' THEN pd.monto ELSE 0 END) as total_deducciones
                    FROM planilla_cabecera pc
                    LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                    LEFT JOIN frecuencias f ON pc.frecuencia_id = f.id
                    LEFT JOIN planilla_detalle pd ON pc.id = pd.planilla_cabecera_id
                    LEFT JOIN concepto c ON pd.concepto_id = c.id
                    WHERE pc.estado IN ('PROCESADA', 'CERRADA')
                    GROUP BY pc.id
                    ORDER BY pc.fecha DESC, pc.id DESC";

            $stmt = $this->db->query($sql);
            $payrolls = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Formatear datos para DataTable
            $data = [];
            foreach ($payrolls as $payroll) {
                $neto = $payroll['total_asignaciones'] - $payroll['total_deducciones'];

                $data[] = [
                    'id' => $payroll['id'],
                    'descripcion' => $payroll['descripcion'],
                    'tipo_planilla' => $payroll['tipo_planilla'] ?? 'N/A',
                    'frecuencia' => $payroll['frecuencia'] ?? 'N/A',
                    'fecha' => date('d/m/Y', strtotime($payroll['fecha'])),
                    'periodo' => date('d/m/Y', strtotime($payroll['fecha_desde'])) . ' - ' .
                                date('d/m/Y', strtotime($payroll['fecha_hasta'])),
                    'estado' => $payroll['estado'],
                    'total_empleados' => $payroll['total_empleados'],
                    'total_neto' => number_format($neto, 2),
                    'actions' => $this->buildActionButtons($payroll['id'])
                ];
            }

            echo json_encode(['data' => $data]);

        } catch (\Exception $e) {
            error_log("Error en getPayrollsData: " . $e->getMessage());
            echo json_encode([
                'error' => true,
                'message' => 'Error al cargar planillas'
            ]);
        }
    }

    /**
     * Exportar planilla a formato INNOVA
     */
    public function export($id)
    {
        $this->requireAuth();

        try {
            // Generar archivo
            $result = $this->innovaExportService->generateExportFile($id);

            if (!$result['success']) {
                $_SESSION['error_message'] = $result['message'];
                header('Location: ' . \App\Core\UrlHelper::route('/panel/innova-export'));
                exit;
            }

            // Descargar archivo
            $filePath = $this->innovaExportService->getFilePath($result['file_path']);

            if (!file_exists($filePath)) {
                $_SESSION['error_message'] = 'Archivo generado pero no encontrado';
                header('Location: ' . \App\Core\UrlHelper::route('/panel/innova-export'));
                exit;
            }

            // Headers para descarga
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: public');

            // Leer y enviar archivo
            readfile($filePath);
            exit;

        } catch (\Exception $e) {
            error_log("Error en export: " . $e->getMessage());
            $_SESSION['error_message'] = 'Error al exportar: ' . $e->getMessage();
            header('Location: ' . \App\Core\UrlHelper::route('/panel/innova-export'));
            exit;
        }
    }

    /**
     * Construir botones de acción para DataTable
     */
    private function buildActionButtons($payrollId)
    {
        $exportUrl = \App\Core\UrlHelper::route('/panel/innova-export/export/' . $payrollId);
        $detailsUrl = \App\Core\UrlHelper::route('/panel/planillas/view/' . $payrollId);

        return '
            <div class="btn-group btn-group-sm">
                <a href="' . $exportUrl . '"
                   class="btn btn-success btn-export"
                   data-id="' . $payrollId . '"
                   title="Exportar a INNOVA">
                    <i class="fa fa-file-export"></i> Exportar
                </a>
                <a href="' . $detailsUrl . '"
                   class="btn btn-info"
                   target="_blank"
                   title="Ver detalles">
                    <i class="fa fa-eye"></i>
                </a>
            </div>
        ';
    }
}
