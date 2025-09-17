<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollConcept;
use App\Models\Concept;
use App\Models\Employee;
use App\Core\Security;
use App\Core\PayrollValidationRules;

class PayrollController extends Controller
{
    private $payrollModel;
    private $payrollDetailModel;
    private $payrollConceptModel;
    private $conceptModel;
    private $employeeModel;
    private $validationRules;

    public function __construct()
    {
        parent::__construct();
        $this->payrollModel = new Payroll();
        $this->payrollDetailModel = new PayrollDetail();
        $this->payrollConceptModel = new PayrollConcept();
        $this->conceptModel = new Concept();
        $this->employeeModel = new Employee();
        $this->validationRules = new PayrollValidationRules();
    }

    /**
     * Mostrar lista de planillas
     */
    public function index()
    {
        try {
            // Obtener filtro de tipo de planilla si existe
            $tipoPlanillaId = $_GET['tipo_planilla_id'] ?? null;
            
            // Si no hay filtro en URL, intentar obtener del sessionStorage via JavaScript
            $payrolls = $this->payrollModel->getAllWithStats($tipoPlanillaId);
            
            // Obtener tipos de planilla para mostrar información del filtro
            $tipoPlanillaModel = new \App\Models\TipoPlanilla();
            $tiposPlanilla = $tipoPlanillaModel->getActiveForSelect();
            
            $this->render('admin/payroll/index', [
                'payrolls' => $payrolls,
                'tipos_planilla' => $tiposPlanilla,
                'tipo_planilla_filtro' => $tipoPlanillaId,
                'page_title' => 'Planillas',
                'csrf_token' => Security::generateToken()
            ]);
        } catch (\Exception $e) {
            error_log("Error en PayrollController@index: " . $e->getMessage());
            $this->redirect('/panel/dashboard?error=Error cargando planillas');
        }
    }

    /**
     * Mostrar formulario para crear nueva planilla
     */
    public function create()
    {
        try {
            // Cargar tipos de planilla activos
            $tipoPlanillaModel = new \App\Models\TipoPlanilla();
            $tiposPlanilla = $tipoPlanillaModel->getActiveForSelect();
            
            // Cargar frecuencias activas
            $frecuenciaModel = new \App\Models\Frecuencia();
            $frecuencias = $frecuenciaModel->getActiveForSelect();
            
            $this->render('admin/payroll/create', [
                'page_title' => 'Nueva Planilla',
                'csrf_token' => Security::generateToken(),
                'frecuencias' => $frecuencias
            ]);
        } catch (\Exception $e) {
            error_log("Error cargando datos para crear planilla: " . $e->getMessage());
            $this->render('admin/payroll/create', [
                'page_title' => 'Nueva Planilla',
                'csrf_token' => Security::generateToken(),
                'frecuencias' => []
            ]);
        }
    }

    /**
     * Procesar creación de nueva planilla
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }
            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            // Validar datos requeridos
            $descripcion = trim($_POST['descripcion'] ?? '');
            $tipo_planilla_id = intval($_POST['tipo_planilla_id'] ?? 0);
            $fecha = $_POST['fecha'] ?? '';
            $periodo_inicio = $_POST['periodo_inicio'] ?? '';
            $periodo_fin = $_POST['periodo_fin'] ?? '';
            
            // Obtener frecuencia_id del select - se envía directamente el ID desde la tabla
            $frecuencia_id = intval($_POST['frecuencia_id'] ?? 0);

            if (empty($descripcion) || !$tipo_planilla_id || empty($fecha) || empty($periodo_inicio) || empty($periodo_fin)) {
                throw new \Exception('Todos los campos obligatorios deben ser completados');
            }

            // Validar que el tipo de planilla existe y está activo
            $tipoPlanillaModel = new \App\Models\TipoPlanilla();
            $tipoPlanilla = $tipoPlanillaModel->find($tipo_planilla_id);
            if (!$tipoPlanilla || !$tipoPlanilla['activo']) {
                throw new \Exception('El tipo de planilla seleccionado no es válido');
            }

            // Validar fechas (solo si ambos valores no están vacíos)
            if (!empty($periodo_inicio) && !empty($periodo_fin)) {
                if (strtotime($periodo_inicio) >= strtotime($periodo_fin)) {
                    throw new \Exception('La fecha de inicio debe ser anterior a la fecha fin');
                }
            }

            // Crear planilla
            $payrollData = [
                'descripcion' => $descripcion,
                'tipo_planilla_id' => $tipo_planilla_id,
                'frecuencia_id' => $frecuencia_id,
                'fecha' => $fecha,
                'periodo_inicio' => $periodo_inicio,
                'periodo_fin' => $periodo_fin,
                'usuario_creacion' => $_SESSION['admin_id'] ?? null
            ];

            $payrollId = $this->payrollModel->create($payrollData);

            if ($payrollId) {
                $_SESSION['success'] = 'Planilla creada exitosamente';
                $this->redirect('/panel/payrolls/' . $payrollId);
            } else {
                throw new \Exception('Error al crear la planilla');
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@store: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/panel/payrolls/create');
        }
    }

    /**
     * Mostrar detalle de planilla específica
     */
    public function show($id)
    {
        try {
            $payroll = $this->payrollModel->findWithType($id);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            $details = $this->payrollModel->getPayrollDetails($id);
            $stats = $this->payrollModel->getPayrollStats($id);

            $this->render('admin/payroll/show', [
                'payroll' => $payroll,
                'employees' => $details,
                'stats' => $stats,
                'page_title' => 'Detalle de Planilla: ' . $payroll['descripcion'],
                'csrf_token' => Security::generateToken()
            ]);

        } catch (\Exception $e) {
            error_log("Error en PayrollController@show: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/panel/payrolls');
        }
    }

    /**
     * Mostrar formulario para editar planilla
     */
    public function edit($id)
    {
        try {
            $payroll = $this->payrollModel->findWithType($id);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            if (!$this->payrollModel->canEdit($id)) {
                throw new \Exception('Esta planilla no puede ser editada en su estado actual');
            }

            // Cargar tipos de planilla activos
            $tipoPlanillaModel = new \App\Models\TipoPlanilla();
            $tiposPlanilla = $tipoPlanillaModel->getActiveForSelect();
            
            // Cargar frecuencias activas (consistente con create)
            $frecuenciaModel = new \App\Models\Frecuencia();
            $frecuencias = $frecuenciaModel->getActiveForSelect();

            $this->render('admin/payroll/edit', [
                'payroll' => $payroll,
                'page_title' => 'Editar Planilla',
                'csrf_token' => Security::generateToken(),
                'frecuencias' => $frecuencias
            ]);

        } catch (\Exception $e) {
            error_log("Error en PayrollController@edit: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/panel/payrolls');
        }
    }

    /**
     * Procesar actualización de planilla
     */
    public function update($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            if (!$this->payrollModel->canEdit($id)) {
                throw new \Exception('Esta planilla no puede ser editada en su estado actual');
            }

            
            // Validar y preparar datos (usar mismos nombres que store para consistencia)
            $tipo_planilla_id = intval($_POST['tipo_planilla_id'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');
            $fecha = $_POST['fecha'] ?? '';
            $periodo_inicio = $_POST['periodo_inicio'] ?? '';
            $periodo_fin = $_POST['periodo_fin'] ?? '';
            
            // Obtener frecuencia_id del select - consistente con store
            $frecuencia_id = intval($_POST['frecuencia_id'] ?? 0);
            
            
            $updateData = [
                'descripcion' => $descripcion,
                'tipo_planilla_id' => $tipo_planilla_id,
                'frecuencia_id' => $frecuencia_id,
                'fecha' => $fecha,
                'fecha_desde' => $periodo_inicio,
                'fecha_hasta' => $periodo_fin,
                'estado' => $_POST['estado'] ?? $payroll['estado']
            ];

            if (empty($descripcion) || !$tipo_planilla_id || empty($fecha) || 
                empty($periodo_inicio) || empty($periodo_fin)) {
                throw new \Exception('Todos los campos obligatorios deben ser completados');
            }

            // Validar que el tipo de planilla existe y está activo
            $tipoPlanillaModel = new \App\Models\TipoPlanilla();
            $tipoPlanilla = $tipoPlanillaModel->find($tipo_planilla_id);
            if (!$tipoPlanilla || !$tipoPlanilla['activo']) {
                throw new \Exception('El tipo de planilla seleccionado no es válido');
            }

            // Validar fechas (solo si ambos valores no están vacíos)
            if (!empty($periodo_inicio) && !empty($periodo_fin)) {
                if (strtotime($periodo_inicio) >= strtotime($periodo_fin)) {
                    throw new \Exception('La fecha de inicio debe ser anterior a la fecha fin');
                }
            }

            $result = $this->payrollModel->update($id, $updateData);

            if ($result) {
                $_SESSION['success'] = 'Planilla actualizada exitosamente';
                $this->redirect('/panel/payrolls/' . $id);
            } else {
                throw new \Exception('Error al actualizar la planilla');
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@update: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/panel/payrolls/' . $id . '/edit');
        }
    }

    /**
     * Procesar planilla (generar detalles para empleados)
     */
    public function process($id, $tipoPlanillaId = null)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            // Validar tipo de planilla requerido
            if (!$tipoPlanillaId) {
                throw new \Exception('Tipo de planilla requerido para procesar');
            }

            // Validar que el tipo de planilla existe y está activo
            $tipoPlanillaModel = new \App\Models\TipoPlanilla();
            $tipoPlanilla = $tipoPlanillaModel->find($tipoPlanillaId);
            if (!$tipoPlanilla || !$tipoPlanilla['activo']) {
                throw new \Exception('El tipo de planilla seleccionado no es válido');
            }

            if (!$this->payrollModel->canProcess($id)) {
                throw new \Exception('Esta planilla no puede ser procesada');
            }

            // Actualizar la planilla con el tipo seleccionado si no lo tiene
            $payroll = $this->payrollModel->find($id);
            if (!$payroll['tipo_planilla_id'] || $payroll['tipo_planilla_id'] != $tipoPlanillaId) {
                $this->payrollModel->update($id, ['tipo_planilla_id' => $tipoPlanillaId]);
            }
            
            // CRÍTICO: Liberar la sesión ANTES del procesamiento para permitir requests concurrentes
            session_write_close();
            
            // Enviar respuesta inmediata al cliente antes del procesamiento
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Procesamiento iniciado',
                'payroll_id' => $id,
                'tipo_planilla_id' => $tipoPlanillaId
            ]);
            
            // Forzar el envío de la respuesta al cliente
            if (ob_get_level()) ob_end_flush();
            flush();
            
            // Ahora procesar en background sin bloquear más requests
            $userId = $_SESSION['admin_id'] ?? null;
            $result = $this->payrollModel->processPayroll($id, $userId, $tipoPlanillaId);


        } catch (\Exception $e) {
            error_log("Error en PayrollController@process: " . $e->getMessage());
            
            // Si aún no se envió respuesta, enviar error
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
        
        // No hacer redirect - ya se envió respuesta JSON
        exit;
    }

    /**
     * Cerrar planilla (cambiar estado a CERRADA)
     */
    public function close($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $payroll = $this->payrollModel->find($id);
            if (!$payroll || $payroll['estado'] !== 'PROCESADA') {
                throw new \Exception('Solo se pueden cerrar planillas procesadas');
            }

            $result = $this->payrollModel->closePayroll($id);

            if ($result) {
                $_SESSION['success'] = 'Planilla cerrada exitosamente';
            } else {
                throw new \Exception('Error al cerrar la planilla');
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@close: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/panel/payrolls/' . $id);
    }

    /**
     * Anular planilla
     */
    public function cancel($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $result = $this->payrollModel->cancelPayroll($id);

            if ($result) {
                $_SESSION['success'] = 'Planilla anulada exitosamente';
            } else {
                throw new \Exception('Error al anular la planilla');
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@cancel: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/panel/payrolls/' . $id);
    }

    /**
     * Reprocesar planilla (limpiar y volver a procesar)
     */
    public function reprocess($id, $tipoPlanillaId = null)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            $receivedToken = $_POST['csrf_token'] ?? '';
            
            if (!Security::validateToken($receivedToken)) {
                throw new \Exception('Token de seguridad inválido');
            }

            // Verificar que la planilla existe y está en estado PROCESADA
            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            if ($payroll['estado'] !== 'PROCESADA') {
                throw new \Exception('Solo se pueden reprocesar planillas en estado PROCESADA');
            }

            // Si se proporciona tipoPlanillaId, validar y actualizar
            if ($tipoPlanillaId) {
                // Validar que el tipo de planilla existe y está activo
                $tipoPlanillaModel = new \App\Models\TipoPlanilla();
                $tipoPlanilla = $tipoPlanillaModel->find($tipoPlanillaId);
                if (!$tipoPlanilla || !$tipoPlanilla['activo']) {
                    throw new \Exception('El tipo de planilla seleccionado no es válido');
                }
                
                // Actualizar la planilla con el tipo seleccionado si es diferente
                if ($payroll['tipo_planilla_id'] != $tipoPlanillaId) {
                    $this->payrollModel->update($id, ['tipo_planilla_id' => $tipoPlanillaId]);
                }
            }
            
            // CRÍTICO: Liberar la sesión ANTES del reprocesamiento para permitir requests concurrentes
            session_write_close();
            
            // Enviar respuesta inmediata al cliente antes del procesamiento
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Reprocesamiento iniciado',
                'payroll_id' => $id
            ]);
            
            // Forzar el envío de la respuesta al cliente
            if (ob_get_level()) ob_end_flush();
            flush();
            
            // Ahora procesar en background sin bloquear más requests
            $userId = $_SESSION['admin_id'] ?? null;
            $result = $this->payrollModel->reprocessPayroll($id, $userId, $tipoPlanillaId);


        } catch (\Exception $e) {
            error_log("Error en PayrollController@reprocess: " . $e->getMessage());
            
            // Si aún no se envió respuesta, enviar error
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
        
        // No hacer redirect - ya se envió respuesta JSON
        exit;
    }

    /**
     * Mostrar detalle completo de un empleado en la planilla con conceptos
     */
    public function showDetail($payrollId, $employeeId)
    {
        try {
            // Obtener información de la planilla
            $payroll = $this->payrollModel->find($payrollId);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            // Obtener detalle del empleado con información adicional usando payrollId + employeeId
            $detail = $this->payrollDetailModel->getDetailByPayrollAndEmployee($payrollId, $employeeId);
            
            if (!$detail) {
                throw new \Exception('Detalle de empleado no encontrado');
            }

            // Los conceptos ya están incluidos en $detail['conceptos']
            $concepts = $detail['conceptos'] ?? [];
            
            // Separar conceptos por tipo para mejor presentación
            $incomes = [];
            $deductions = [];
            $totalIncomes = 0;
            $totalDeductions = 0;
            
            foreach ($concepts as $concept) {
                // Mapear los tipos de concepto - parece que 'A' significa algo diferente
                $tipoConcepto = $concept['concepto_tipo'];
                
                // Si el tipo es 'A', necesitamos determinar si es ingreso o deducción
                // Por ahora, asumiré que los conceptos de sueldo son ingresos
                if ($tipoConcepto === 'A' || $tipoConcepto === 'INGRESO' || 
                    (stripos($concept['descripcion'], 'sueldo') !== false)) {
                    $incomes[] = $concept;
                    $totalIncomes += $concept['monto'];
                } else {
                    $deductions[] = $concept;
                    $totalDeductions += $concept['monto'];
                }
            }

            $this->render('admin/payroll/show_detail', [
                'payroll' => $payroll,
                'detail' => $detail,
                'concepts' => $concepts,
                'incomes' => $incomes,
                'deductions' => $deductions,
                'totalIncomes' => $totalIncomes,
                'totalDeductions' => $totalDeductions,
                'netSalary' => $totalIncomes - $totalDeductions,
                'page_title' => 'Detalle de Empleado - ' . ($detail['employee_name'] ?? 'N/A'),
                'csrf_token' => Security::generateToken()
            ]);

        } catch (\Exception $e) {
            error_log("Error en PayrollController@showDetail: " . $e->getMessage());
            $this->redirect('/panel/payrolls/' . $payrollId . '?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Exportar planilla a Excel
     */
    public function export($id)
    {
        try {
            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            $details = $this->payrollDetailModel->getPayrollSummaryForExport($id);
            
            // Configurar headers para descarga
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="planilla_' . $id . '_' . date('Y-m-d') . '.xls"');
            header('Cache-Control: max-age=0');

            // Generar contenido HTML para Excel
            echo $this->generateExcelContent($payroll, $details);
            exit;

        } catch (\Exception $e) {
            error_log("Error en PayrollController@export: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/panel/payrolls/' . $id);
        }
    }

    /**
     * Generar contenido HTML para Excel
     */
    private function generateExcelContent($payroll, $details)
    {
        $html = '<html><body>';
        $html .= '<h1>Planilla: ' . htmlspecialchars($payroll['descripcion']) . '</h1>';
        $html .= '<p>Fecha: ' . date('d/m/Y', strtotime($payroll['fecha'])) . '</p>';
        $html .= '<p>Período: ' . date('d/m/Y', strtotime($payroll['periodo_inicio'])) . ' al ' . date('d/m/Y', strtotime($payroll['periodo_fin'])) . '</p>';
        $html .= '<br>';
        
        $html .= '<table border="1">';
        $html .= '<tr>';
        $html .= '<th>Código</th>';
        $html .= '<th>Nombre Completo</th>';
        $html .= '<th>Posición</th>';
        $html .= '<th>Salario Base</th>';
        $html .= '<th>Horas Trabajadas</th>';
        $html .= '<th>Total Ingresos</th>';
        $html .= '<th>Total Deducciones</th>';
        $html .= '<th>Salario Neto</th>';
        $html .= '</tr>';

        $totalIngresos = 0;
        $totalDeducciones = 0;
        $totalNeto = 0;

        foreach ($details as $detail) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($detail['codigo']) . '</td>';
            $html .= '<td>' . htmlspecialchars($detail['nombre_completo']) . '</td>';
            $html .= '<td>' . htmlspecialchars($detail['posicion'] ?? '') . '</td>';
            $html .= '<td>' . number_format($detail['salario_base'], 2) . '</td>';
            $html .= '<td>' . number_format($detail['horas_trabajadas'], 2) . '</td>';
            $html .= '<td>' . number_format($detail['total_ingresos'], 2) . '</td>';
            $html .= '<td>' . number_format($detail['total_deducciones'], 2) . '</td>';
            $html .= '<td>' . number_format($detail['salario_neto'], 2) . '</td>';
            $html .= '</tr>';

            $totalIngresos += $detail['total_ingresos'];
            $totalDeducciones += $detail['total_deducciones'];
            $totalNeto += $detail['salario_neto'];
        }

        // Fila de totales
        $html .= '<tr style="font-weight: bold;">';
        $html .= '<td colspan="5">TOTALES</td>';
        $html .= '<td>' . number_format($totalIngresos, 2) . '</td>';
        $html .= '<td>' . number_format($totalDeducciones, 2) . '</td>';
        $html .= '<td>' . number_format($totalNeto, 2) . '</td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * API: Obtener datos para AJAX
     */
    public function getRow()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception('ID inválido');
            }

            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            header('Content-Type: application/json');
            echo json_encode($payroll);

        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Obtener estadísticas de planilla
     */
    public function getStats($id)
    {
        try {
            $stats = $this->payrollModel->getPayrollStats($id);
            
            header('Content-Type: application/json');
            echo json_encode($stats ?: []);

        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Mostrar vista de edición detallada de planilla
     */
    public function editDetails($id)
    {
        try {
            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            if (!$this->payrollModel->canEdit($id)) {
                throw new \Exception('Esta planilla no puede ser editada en su estado actual');
            }

            // Obtener empleados y sus detalles
            $details = $this->payrollDetailModel->getByPayrollId($id);
            if (count($details) > 0) {
            } else {
            }
            
            // Obtener todos los conceptos activos
            $concepts = $this->conceptModel->getActiveConceptsForPayroll();
            
            // Obtener matriz empleado-concepto
            $employeeConceptMatrix = [];
            foreach ($details as $detail) {
                $employeeConceptMatrix[$detail['employee_id']] = [
                    'detail_id' => $detail['id'],
                    'employee_name' => $detail['employee_name'],
                    'employee_code' => $detail['employee_code'],
                    'concepts' => $this->payrollConceptModel->getByDetailId($detail['id']),
                    'manual_edits' => $this->payrollDetailModel->getManualEditsSummary($detail['id'])
                ];
            }

            $this->render('admin/payroll/edit-details', [
                'payroll' => $payroll,
                'details' => $details,
                'concepts' => $concepts,
                'employeeConceptMatrix' => $employeeConceptMatrix,
                'page_title' => 'Editar Detalles: ' . $payroll['descripcion'],
                'csrf_token' => Security::generateToken()
            ]);

        } catch (\Exception $e) {
            error_log("Error en PayrollController@editDetails: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/panel/payrolls/' . $id);
        }
    }

    /**
     * Actualizar valor específico empleado-concepto
     */
    public function updateConceptValue()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $detailId = intval($_POST['detail_id'] ?? 0);
            $conceptId = intval($_POST['concept_id'] ?? 0);
            $newValue = floatval($_POST['value'] ?? 0);
            $payrollId = intval($_POST['payroll_id'] ?? 0);

            if (!$detailId || !$conceptId) {
                throw new \Exception('Parámetros inválidos');
            }

            // Validar que la planilla puede ser editada
            if (!$this->payrollModel->canEdit($payrollId)) {
                throw new \Exception('Esta planilla no puede ser editada en su estado actual');
            }

            // Obtener empleado del detalle
            $detail = $this->payrollDetailModel->find($detailId);
            if (!$detail) {
                throw new \Exception('Detalle de planilla no encontrado');
            }

            // Validar rango de valores básico
            $validation = $this->payrollDetailModel->validateValueRange($conceptId, $newValue);
            if (!$validation['valid']) {
                throw new \Exception($validation['message']);
            }

            // Validaciones avanzadas de reglas de negocio
            $businessValidation = $this->validationRules->validateConceptForEmployee(
                $conceptId, 
                $detail['employee_id'], 
                $payrollId, 
                $newValue
            );

            if (!$businessValidation['valid']) {
                throw new \Exception('Validación de reglas de negocio: ' . $businessValidation['message']);
            }

            // Actualizar valor usando el nuevo método
            $result = $this->payrollDetailModel->updateValue($detailId, $newValue, true);

            if ($result) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Valor actualizado correctamente',
                    'new_value' => $newValue
                ]);
            } else {
                throw new \Exception('Error al actualizar el valor');
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@updateConceptValue: " . $e->getMessage());
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Agregar concepto específico a empleado
     */
    public function addEmployeeConcept()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $detailId = intval($_POST['detail_id'] ?? 0);
            $conceptId = intval($_POST['concept_id'] ?? 0);
            $initialValue = isset($_POST['initial_value']) ? floatval($_POST['initial_value']) : null;
            $payrollId = intval($_POST['payroll_id'] ?? 0);

            if (!$detailId || !$conceptId) {
                throw new \Exception('Parámetros inválidos');
            }

            // Validar que la planilla puede ser editada
            if (!$this->payrollModel->canEdit($payrollId)) {
                throw new \Exception('Esta planilla no puede ser editada en su estado actual');
            }

            // Validar rango si se proporciona valor inicial
            if ($initialValue !== null) {
                $validation = $this->payrollDetailModel->validateValueRange($conceptId, $initialValue);
                if (!$validation['valid']) {
                    throw new \Exception($validation['message']);
                }
            }

            // Agregar concepto
            $result = $this->payrollDetailModel->addConceptToEmployee($detailId, $conceptId, $initialValue);

            if ($result) {
                // Obtener el nuevo valor calculado o aplicado
                $concepts = $this->payrollConceptModel->getByDetailId($detailId);
                $appliedValue = 0;
                foreach ($concepts as $concept) {
                    if ($concept['concepto_id'] == $conceptId) {
                        $appliedValue = $concept['monto'];
                        break;
                    }
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Concepto agregado correctamente',
                    'applied_value' => $appliedValue
                ]);
            } else {
                throw new \Exception('Error al agregar el concepto');
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@addEmployeeConcept: " . $e->getMessage());
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Remover concepto específico de empleado
     */
    public function removeEmployeeConcept()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $detailId = intval($_POST['detail_id'] ?? 0);
            $conceptId = intval($_POST['concept_id'] ?? 0);
            $payrollId = intval($_POST['payroll_id'] ?? 0);

            if (!$detailId || !$conceptId) {
                throw new \Exception('Parámetros inválidos');
            }

            // Validar que la planilla puede ser editada
            if (!$this->payrollModel->canEdit($payrollId)) {
                throw new \Exception('Esta planilla no puede ser editada en su estado actual');
            }

            // Remover concepto
            $result = $this->payrollDetailModel->removeConceptFromEmployee($detailId, $conceptId);

            if ($result) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Concepto removido correctamente'
                ]);
            } else {
                throw new \Exception('Error al remover el concepto');
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@removeEmployeeConcept: " . $e->getMessage());
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Recalcular empleado específico
     */
    public function recalculateEmployee()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $detailId = intval($_POST['detail_id'] ?? 0);
            $payrollId = intval($_POST['payroll_id'] ?? 0);

            if (!$detailId) {
                throw new \Exception('Parámetros inválidos');
            }

            // Validar que la planilla puede ser editada
            if (!$this->payrollModel->canEdit($payrollId)) {
                throw new \Exception('Esta planilla no puede ser editada en su estado actual');
            }

            // Recalcular concepto específico
            $result = $this->payrollDetailModel->recalculateValue($detailId);

            if ($result) {
                // Obtener nuevos totales
                $detail = $this->payrollDetailModel->find($detailId);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Empleado recalculado correctamente',
                    'new_totals' => [
                        'total_ingresos' => $detail['total_ingresos'],
                        'total_deducciones' => $detail['total_deducciones'],
                        'salario_neto' => $detail['salario_neto']
                    ]
                ]);
            } else {
                throw new \Exception('Error al recalcular el empleado');
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@recalculateEmployee: " . $e->getMessage());
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Restaurar valor calculado de un concepto
     */
    public function restoreCalculatedValue()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $detailId = intval($_POST['detail_id'] ?? 0);
            $conceptId = intval($_POST['concept_id'] ?? 0);
            $payrollId = intval($_POST['payroll_id'] ?? 0);

            if (!$detailId || !$conceptId) {
                throw new \Exception('Parámetros inválidos');
            }

            // Validar que la planilla puede ser editada
            if (!$this->payrollModel->canEdit($payrollId)) {
                throw new \Exception('Esta planilla no puede ser editada en su estado actual');
            }

            // Obtener employeeId del detalle
            $detail = $this->payrollDetailModel->find($detailId);
            if (!$detail) {
                throw new \Exception('Detalle no encontrado');
            }

            // Restaurar valor calculado
            $result = $this->payrollDetailModel->restoreCalculatedValue($detailId, $conceptId, $detail['employee_id']);

            if ($result) {
                // Obtener nuevo valor calculado
                $concepts = $this->payrollConceptModel->getByDetailId($detailId);
                $calculatedValue = 0;
                foreach ($concepts as $concept) {
                    if ($concept['concepto_id'] == $conceptId) {
                        $calculatedValue = $concept['monto'];
                        break;
                    }
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Valor restaurado correctamente',
                    'calculated_value' => $calculatedValue
                ]);
            } else {
                throw new \Exception('Error al restaurar el valor calculado');
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@restoreCalculatedValue: " . $e->getMessage());
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Obtener resumen de ediciones manuales
     */
    public function getManualEditsSummary()
    {
        try {
            $detailId = intval($_GET['detail_id'] ?? 0);
            
            if (!$detailId) {
                throw new \Exception('ID de detalle requerido');
            }

            $summary = $this->payrollDetailModel->getManualEditsSummary($detailId);
            
            header('Content-Type: application/json');
            echo json_encode($summary ?: []);

        } catch (\Exception $e) {
            error_log("Error en PayrollController@getManualEditsSummary: " . $e->getMessage());
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Eliminar planilla (solo si está en estado PENDIENTE)
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            if ($payroll['estado'] !== 'PENDIENTE') {
                throw new \Exception('Solo se pueden eliminar planillas en estado PENDIENTE');
            }

            $result = $this->payrollModel->delete($id);

            if ($result) {
                $_SESSION['success'] = 'Planilla eliminada exitosamente';
            } else {
                throw new \Exception('Error al eliminar la planilla');
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@delete: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/panel/payrolls');
    }

    public function progress($id)
    {
        // Liberar la sesión para permitir requests concurrentes
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        try {
            // Verificar que la planilla existe
            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                $this->json(['error' => 'Planilla no encontrada'], 404);
                return;
            }
            
            // Usar la instancia de Database
            $db = $this->payrollModel->db;
            
            // Obtener información de la planilla para filtrar empleados
            $payrollInfo = $this->payrollModel->findWithType($id);
            
            // Contar empleados totales que corresponden al tipo de planilla
            // Sin filtro situacion_id para coincidir con la lógica de processPayroll
            $totalEmployees = $db->find("
                SELECT COUNT(*) as total 
                FROM employees e 
                WHERE e.tipo_planilla_id = ?
                  AND e.situacion_id IN (
                      SELECT DISTINCT cs.situacion_id 
                      FROM concepto_situaciones cs
                  )", 
                [$payrollInfo['tipo_planilla_id']])['total'];
            
            // Contar empleados ya procesados en esta planilla
            $processedEmployees = $db->find("
                SELECT COUNT(DISTINCT employee_id) as processed 
                FROM planilla_detalle 
                WHERE planilla_cabecera_id = ?
            ", [$id])['processed'] ?? 0;
            
            // Contar conceptos calculados
            $conceptsCalculated = $db->find("
                SELECT COUNT(*) as concepts 
                FROM planilla_detalle 
                WHERE planilla_cabecera_id = ?
            ", [$id])['concepts'] ?? 0;
            
            // Obtener último registro para determinar fase (solo si hay empleados procesados)
            $lastRecord = null;
            if ($processedEmployees > 0) {
                $lastRecord = $db->find("
                    SELECT e.firstname, e.lastname 
                    FROM planilla_detalle pd 
                    LEFT JOIN employees e ON pd.employee_id = e.id 
                    WHERE pd.planilla_cabecera_id = ? 
                    ORDER BY pd.id DESC 
                    LIMIT 1
                ", [$id]);
            }
            
            // Determinar fase actual y percentage basado en el estado de la planilla
            $phase = 'Iniciando procesamiento...';
            $percentage = 0;
            
            if ($payroll['estado'] === 'PROCESADA') {
                // Para planillas ya completadas, mostrar 100%
                $phase = 'Procesamiento completado exitosamente';
                $percentage = 100;
                $processedEmployees = $processedEmployees ?: $totalEmployees; // Mostrar todos como procesados
            } elseif ($payroll['estado'] === 'PENDIENTE') {
                // Para planillas en proceso
                if ($processedEmployees > 0 && $processedEmployees < $totalEmployees) {
                    $currentEmployee = $lastRecord ? $lastRecord['firstname'] . ' ' . $lastRecord['lastname'] : '';
                    $phase = "Procesando empleado: " . $currentEmployee . " (" . $processedEmployees . "/" . $totalEmployees . ")";
                    $percentage = $totalEmployees > 0 ? round(($processedEmployees / $totalEmployees) * 100, 1) : 0;
                } elseif ($processedEmployees >= $totalEmployees) {
                    $phase = 'Finalizando procesamiento...';
                    $percentage = 95; // Casi completo, pero aún no marcado como PROCESADA
                } else {
                    $phase = 'Iniciando procesamiento...';
                    $percentage = 0;
                }
            }
            
            $progress = [
                'total' => $totalEmployees,
                'processed' => $processedEmployees,
                'concepts_calculated' => $conceptsCalculated,
                'phase' => $phase,
                'status' => $payroll['estado'] === 'PROCESADA' ? 'completed' : 'processing',
                'percentage' => $percentage
            ];
            
            
            $this->json($progress);
            
        } catch (\Exception $e) {
            error_log("Error in progress endpoint: " . $e->getMessage());
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Regenerar planilla para un empleado específico
     */
    public function regenerateEmployee($payrollId)
    {
        // Limpiar cualquier output buffer antes de enviar JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Headers para respuesta JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Asegurar que la sesión esté iniciada para validar CSRF
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $employeeId = intval($_POST['employee_id'] ?? 0);
            if (!$employeeId || !$payrollId) {
                throw new \Exception('Parámetros inválidos: payrollId=' . $payrollId . ', employeeId=' . $employeeId);
            }

            // Liberar sesión después de validaciones para evitar bloqueos
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            // Verificar que la planilla existe
            $payroll = $this->payrollModel->find($payrollId);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada con ID: ' . $payrollId);
            }

            // Verificar que la planilla está procesada
            if ($payroll['estado'] !== 'PROCESADA') {
                throw new \Exception('Solo se pueden regenerar empleados en planillas procesadas. Estado actual: ' . $payroll['estado']);
            }

            // Verificar que el empleado existe
            $employee = $this->employeeModel->find($employeeId);
            if (!$employee) {
                throw new \Exception('Empleado no encontrado con ID: ' . $employeeId);
            }

            // Ejecutar la regeneración
            $result = $this->processEmployeeRegeneration($payrollId, $employeeId);

            // Respuesta exitosa
            echo json_encode([
                'success' => true,
                'message' => 'Empleado regenerado exitosamente',
                'employee_name' => $employee['firstname'] . ' ' . $employee['lastname'],
                'concepts_count' => $result['concepts_applied'],
                'total_ingresos' => $result['total_ingresos'],
                'total_deducciones' => $result['total_deducciones'],
                'salario_neto' => $result['salario_neto']
            ]);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            error_log("Error en PayrollController@regenerateEmployee: " . $errorMessage);
            
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $errorMessage,
                'debug_info' => [
                    'payroll_id' => $payrollId,
                    'employee_id' => $_POST['employee_id'] ?? 'not_provided',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
                ]
            ]);
        }
        exit;
    }

    /**
     * Procesar regeneración de empleado
     */
    private function processEmployeeRegeneration($payrollId, $employeeId)
    {
        try {
            $db = $this->payrollModel->db;
            $db->beginTransaction();

            // 1. Eliminar el detalle del empleado
            $deleteDetailQuery = "
                DELETE FROM planilla_detalle 
                WHERE planilla_cabecera_id = ? AND employee_id = ?
            ";
            $db->query($deleteDetailQuery, [$payrollId, $employeeId]);

            // 2. Obtener información del empleado para regeneración
            $employee = $this->employeeModel->find($employeeId);
            if (!$employee) {
                throw new \Exception('Empleado no encontrado durante regeneración');
            }

            // 3. Los conceptos se crearán directamente en planilla_detalle, no hay detalle separado

            // 4. Obtener conceptos con sus condiciones (igual que en procesamiento normal)
            $conceptsQuery = "
                SELECT id, concepto, descripcion, tipo_concepto as tipo, 
                       tipos_planilla, frecuencias, situaciones,
                       formula, valor_fijo, monto_calculo, monto_cero, imprime_detalles 
                FROM concepto 
                WHERE imprime_detalles = 1
            ";
            
            $stmt = $db->prepare($conceptsQuery);
            $stmt->execute();
            $concepts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // 5. Obtener información de la planilla para validaciones
            $payroll = $this->payrollModel->findWithType($payrollId);
            if (!$payroll) {
                throw new \Exception('Información de planilla no encontrada');
            }

            $conceptsApplied = 0;
            $totalIngresos = 0;
            $totalDeducciones = 0;
            
            // Inicializar calculadora (igual que en procesamiento normal)
            if (!class_exists('\App\Services\PlanillaConceptCalculator')) {
                require_once __DIR__ . '/../Services/PlanillaConceptCalculator.php';
            }
            $calculadora = new \App\Services\PlanillaConceptCalculator();
            
            // Situación del empleado (activo = 1)
            $employeeSituacion = 1;

            // 6. Calcular y aplicar cada concepto con validaciones
            foreach ($concepts as $concept) {
                // CRÍTICO: Validar condicionales del concepto (igual que en procesamiento normal)
                if (!$this->payrollModel->validateConceptConditions($concept, $payroll, $employeeSituacion)) {
                    // Concepto omitido - no cumple condiciones
                    continue; // Saltar este concepto - no aplica para este empleado/planilla
                }
                try {
                    $amount = 0;
                    
                    // Establecer variables del colaborador en la calculadora
                    $calculadora->setVariablesColaborador($employeeId);
                    
                    // Calcular monto según la configuración del concepto (igual que procesamiento normal)
                    if (!empty($concept['valor_fijo']) && $concept['valor_fijo'] > 0) {
                        $amount = floatval($concept['valor_fijo']);
                    } elseif ($concept['monto_calculo'] == 1 && !empty($concept['formula'])) {
                        try {
                            $amount = $calculadora->evaluarFormula($concept['formula']);
                            if ($amount < 0) $amount = 0; // No permitir montos negativos
                        } catch (\Exception $e) {
                            error_log("Error evaluando fórmula para concepto {$concept['concepto']}: " . $e->getMessage());
                            $amount = 0;
                        }
                    } else {
                        $amount = 0;
                    }

                    // Calcular valor de referencia según la unidad del concepto
                    $referenciaValor = $this->calculateReferenceValue($concept, $employee);

                    // Insertar en planilla_detalle si hay monto o si el concepto permite monto cero
                    if ($amount > 0 || ($concept['monto_cero'] == 1 )) {
                        $insertConceptQuery = "
                            INSERT INTO planilla_detalle (
                                planilla_cabecera_id,
                                employee_id,
                                concepto_id,
                                monto,
                                tipo,
                                firstname,
                                lastname,
                                position_id,
                                schedule_id,
                                referencia_valor,
                                fecha_transaccion
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ";
                        
                        $conceptType = $concept['tipo'] ?? 'A';
                        
                        // Insertando concepto
                        
                        try {
                            $stmt = $db->prepare($insertConceptQuery);
                            $result = $stmt->execute([
                                $payrollId,
                                $employeeId,
                                $concept['id'],
                                $amount,
                                $conceptType,
                                $employee['firstname'],
                                $employee['lastname'],
                                $employee['position_id'],
                                $employee['schedule_id'],
                                $referenciaValor
                            ]);
                            
                            if ($result) {
                                $conceptsApplied++;
                                
                                // Sumar a totales según tipo
                                if ($concept['tipo'] === 'A') {
                                    $totalIngresos += $amount;
                                } else {
                                    $totalDeducciones += $amount;
                                }
                                
                            } else {
                                $errorInfo = $stmt->errorInfo();
                            }
                        } catch (\PDOException $e) {
                            error_log("Error PDO calculando concepto {$concept['id']} para empleado $employeeId: " . $e->getMessage());
                        } catch (\Exception $e) {
                            error_log("Error calculando concepto {$concept['id']} para empleado $employeeId: " . $e->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Error general calculando concepto {$concept['id']} para empleado $employeeId: " . $e->getMessage());
                }
            }

            // 7. Las deducciones se calculan automáticamente a través de conceptos con fórmulas ACREEDOR()
            // No es necesario agregar deducciones manualmente aquí

            // 6. Los totales se calculan automáticamente ya que los conceptos están en planilla_detalle
            $salarioNeto = $totalIngresos - $totalDeducciones;

            $db->commit();

            // Retornar resultados
            return [
                'concepts_applied' => $conceptsApplied,
                'total_ingresos' => $totalIngresos,
                'total_deducciones' => $totalDeducciones,
                'salario_neto' => $salarioNeto
            ];

        } catch (\Exception $e) {
            error_log("Excepción en regeneración - haciendo rollback: " . $e->getMessage());
            $db->rollback();
            throw $e;
        }
    }

    /**
     * API: Obtener información de empleados de una planilla para reportes
     */
    public function employeeInfo($id)
    {
        try {
            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Planilla no encontrada']);
                exit;
            }

            // Obtener empleados de esta planilla
            $db = $this->payrollModel->getDatabase();
            $connection = $db->getConnection();

            $sql = "SELECT DISTINCT 
                        e.id,
                        e.employee_id,
                        e.firstname,
                        e.lastname,
                        e.document_id,
                        pos.descripcion as position_name
                    FROM planilla_detalle pd
                    INNER JOIN employees e ON pd.employee_id = e.id
                    LEFT JOIN posiciones pos ON e.position_id = pos.id
                    WHERE pd.planilla_cabecera_id = ?
                    ORDER BY e.lastname, e.firstname";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute([$id]);
            $employees = $stmt->fetchAll();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'employees' => $employees
            ]);

        } catch (\Exception $e) {
            error_log("Error en employeeInfo: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener los empleados'
            ]);
        }
        exit;
    }

    /**
     * API: Endpoint AJAX para DataTables server-side processing de empleados en planilla
     */
    public function getEmployeesData($id)
    {
        try {
            // IMPORTANTE: Configurar headers para respuesta JSON
            if (!headers_sent()) {
                header('Content-Type: application/json');
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            }
            
            
            // Validar token CSRF si está presente
            $csrfToken = $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if ($csrfToken && !\App\Core\Security::validateToken($csrfToken)) {
                echo json_encode([
                    'draw' => 0,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Token de seguridad inválido'
                ]);
                exit;
            }
            
            
            // Verificar que la planilla existe
            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                echo json_encode([
                    'draw' => 0,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Planilla no encontrada'
                ]);
                exit;
            }

            // Parámetros DataTables
            $draw = intval($_GET['draw'] ?? 1);
            $start = intval($_GET['start'] ?? 0);
            $length = intval($_GET['length'] ?? 25);
            $search = $_GET['search']['value'] ?? '';
            $order = $_GET['order'] ?? [];

            // Construir query base - corregir nombre de columna
            $baseQuery = "FROM (
                SELECT 
                    e.id as employee_id,
                    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                    e.employee_id as employee_code,
                    COALESCE(pos.codigo, 'Sin posición') as position_name,
                    SUM(CASE WHEN pd.tipo = 'A' THEN pd.monto ELSE 0 END) as total_ingresos,
                    SUM(CASE WHEN pd.tipo != 'A' THEN pd.monto ELSE 0 END) as total_deducciones,
                    (SUM(CASE WHEN pd.tipo = 'A' THEN pd.monto ELSE 0 END) - 
                     SUM(CASE WHEN pd.tipo != 'A' THEN pd.monto ELSE 0 END)) as salario_neto
                FROM planilla_detalle pd
                INNER JOIN employees e ON pd.employee_id = e.id
                LEFT JOIN posiciones pos ON e.position_id = pos.id
                WHERE pd.planilla_cabecera_id = ?
                GROUP BY e.id, e.firstname, e.lastname, e.employee_id, pos.codigo
            ) t";

            $params = [$id];

            // Aplicar filtro de búsqueda
            $whereClause = "";
            if (!empty($search)) {
                $whereClause = " WHERE (t.employee_name LIKE ? OR t.employee_code LIKE ? OR t.position_name LIKE ?)";
                $searchParam = '%' . $search . '%';
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
            }

            // Contar registros totales
            $totalQuery = "SELECT COUNT(*) as total " . $baseQuery;
            $stmt = $this->payrollModel->db->prepare($totalQuery);
            $stmt->execute([$id]);
            $totalRecords = $stmt->fetch()['total'];
            
            // Contar registros filtrados
            $filteredQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
            $stmt = $this->payrollModel->db->prepare($filteredQuery);
            $stmt->execute($params);
            $filteredRecords = $stmt->fetch()['total'];
            
            // Aplicar ordenamiento
            $orderClause = "";
            if (!empty($order)) {
                $orderColumns = ['employee_name', 'position_name', 'total_ingresos', 'total_deducciones', 'salario_neto'];
                $orderBy = [];
                foreach ($order as $orderItem) {
                    $columnIndex = intval($orderItem['column']);
                    $direction = $orderItem['dir'] === 'desc' ? 'DESC' : 'ASC';
                    if ($columnIndex < count($orderColumns)) {
                        $orderBy[] = "t.{$orderColumns[$columnIndex]} {$direction}";
                    }
                }
                if (!empty($orderBy)) {
                    $orderClause = " ORDER BY " . implode(', ', $orderBy);
                }
            }
            
            if (empty($orderClause)) {
                $orderClause = " ORDER BY t.employee_name ASC";
            }

            // Query final con paginación
            $dataQuery = "SELECT 
                t.employee_id,
                t.employee_name,
                t.employee_code,
                t.position_name,
                t.total_ingresos,
                t.total_deducciones,
                t.salario_neto
                " . $baseQuery . $whereClause . $orderClause . " LIMIT ? OFFSET ?";

            $params[] = $length;
            $params[] = $start;

            $stmt = $this->payrollModel->db->prepare($dataQuery);
            $stmt->execute($params);
            $employees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Formatear datos para DataTables - usar array indexado
            $data = [];
            foreach ($employees as $employee) {
                $data[] = [
                    0 => $employee['employee_name'],
                    1 => $employee['position_name'] ?: 'Sin posición',
                    2 => currency_symbol() . number_format($employee['total_ingresos'], 2),
                    3 => currency_symbol() . number_format($employee['total_deducciones'], 2),
                    4 => currency_symbol() . number_format($employee['salario_neto'], 2),
                    5 => $this->generateEmployeeActions($id, $employee['employee_id'])
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            error_log("Error en getEmployeesData: " . $e->getMessage());
            echo json_encode([
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Generar botones de acciones para cada empleado
     */
    private function generateEmployeeActions($payrollId, $employeeId)
    {
        $actions = '<div class="btn-group" role="group">';
        
        // Ver detalle
        $actions .= '<a href="' . \App\Core\Config::get('app.url') . '/panel/payrolls/' . $payrollId . '/employee/' . $employeeId . '" 
                       class="btn btn-info btn-sm" title="Ver detalle">
                       <i class="fas fa-eye"></i>
                    </a>';
        
        // Regenerar empleado
        $actions .= '<button type="button" 
                            class="btn btn-warning btn-sm" 
                            onclick="regenerateEmployee(' . $employeeId . ')"
                            title="Regenerar empleado">
                        <i class="fas fa-sync-alt"></i>
                    </button>';
        
        $actions .= '</div>';
        
        return $actions;
    }

    /**
     * Cambiar estado de planilla a PENDIENTE
     */
    public function toPending($id)
    {
        // Asegurar que no hay transacciones pendientes
        try {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
                error_log("Rollback de transacción pendiente en toPending()");
            }
        } catch (\Exception $e) {
            error_log("Error limpiando transacción pendiente: " . $e->getMessage());
        }

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            // Verificar que el estado actual permita el cambio
            if ($payroll['estado'] !== 'PROCESADA') {
                throw new \Exception('Solo se puede cambiar a PENDIENTE una planilla PROCESADA');
            }

            try {
                // Eliminar todos los registros asociados a la planilla (sin transacción envolvente)
                $this->deleteAllPayrollRecords($id);
                
                // Transacción corta solo para el update principal
                $this->db->beginTransaction();
                
                // Cambiar el estado a PENDIENTE
                $result = $this->payrollModel->update($id, ['estado' => 'PENDIENTE']);

                if (!$result) {
                    throw new \Exception('No se pudo actualizar el estado de la planilla');
                }

                // Confirmar transacción rápidamente
                $this->db->commit();
                
                // Registrar en auditoría fuera de la transacción principal
                try {
                    $this->registerAuditTrail($id, 'PROCESADA', 'PENDIENTE', 'Planilla marcada como pendiente para reprocesamiento', 0);
                } catch (\Exception $auditError) {
                    error_log("Error en auditoría (no crítico): " . $auditError->getMessage());
                }
                
                // Enviar respuesta JSON para AJAX
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Planilla marcada como PENDIENTE y todos los registros asociados han sido eliminados. La planilla está lista para ser reprocesada.',
                    'new_status' => 'PENDIENTE'
                ]);
                return;
                
            } catch (\Exception $e) {
                // Asegurar rollback si estamos en transacción
                try {
                    if ($this->db->inTransaction()) {
                        $this->db->rollback();
                    }
                } catch (\Exception $rollbackError) {
                    error_log("Error en rollback: " . $rollbackError->getMessage());
                }
                throw new \Exception('Error al cambiar planilla a PENDIENTE: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@toPending: " . $e->getMessage());
            
            // Enviar respuesta JSON de error
            if (!headers_sent()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            return;
        }
    }

    /**
     * Abrir planilla cerrada (CERRADA → PROCESADA) con rollback de acumulados
     */
    public function reopen($id)
    {
        // Asegurar que no hay transacciones pendientes
        try {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
                error_log("Rollback de transacción pendiente en reopen()");
            }
        } catch (\Exception $e) {
            error_log("Error limpiando transacción pendiente: " . $e->getMessage());
        }

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            // Verificar que la planilla esté cerrada
            if ($payroll['estado'] !== 'CERRADA' && $payroll['estado'] !== 'cerrada') {
                throw new \Exception('Solo se pueden reabrir planillas cerradas');
            }

            // Fix: usar el campo correcto que envía el JavaScript
            $motivo = trim($_POST['reason'] ?? $_POST['motivo_reapertura'] ?? 'Apertura de planilla solicitada');
            
            // OPTIMIZACIÓN: Hacer operaciones simples sin transacción larga
            try {
                // 1. Primero hacer rollback de acumulados (sin transacción envolvente)
                $acumuladosAfectados = $this->rollbackAccumulatedData($id);
                
                // 2. Transacción corta solo para el update principal
                $this->db->beginTransaction();
                
                // Cambiar estado de la planilla a 'PROCESADA'
                $updateData = [
                    'estado' => 'PROCESADA'
                ];
                
                // Solo agregar campos si existen en la tabla
                if ($this->hasClosureFields()) {
                    $updateData['fecha_reapertura'] = date('Y-m-d H:i:s');
                    $updateData['usuario_reapertura'] = $_SESSION['admin_name'] ?? 'Sistema';
                    $updateData['motivo_reapertura'] = $motivo;
                }
                
                $updateResult = $this->payrollModel->update($id, $updateData);

                if (!$updateResult) {
                    throw new \Exception('No se pudo actualizar el estado de la planilla');
                }

                // Confirmar transacción rápidamente
                $this->db->commit();
                
                // 3. Registrar en auditoría fuera de la transacción principal
                try {
                    $this->registerAuditTrail($id, 'CERRADA', 'PROCESADA', $motivo, $acumuladosAfectados);
                } catch (\Exception $auditError) {
                    error_log("Error en auditoría (no crítico): " . $auditError->getMessage());
                }
                
                // Configurar mensaje de éxito y redireccionar (para formularios del listado)
                $mensaje = "Planilla abierta exitosamente. Estado cambió a PROCESADA";
                if ($acumuladosAfectados > 0) {
                    $mensaje .= " y se realizó rollback de {$acumuladosAfectados} registros de acumulados.";
                } else {
                    $mensaje .= ".";
                }
                $_SESSION['success'] = $mensaje;
                $this->redirect('/panel/payrolls/' . $id);
                
            } catch (\Exception $e) {
                // Asegurar rollback si estamos en transacción
                try {
                    if ($this->db->inTransaction()) {
                        $this->db->rollback();
                    }
                } catch (\Exception $rollbackError) {
                    error_log("Error en rollback: " . $rollbackError->getMessage());
                }
                throw new \Exception('Error al abrir la planilla: ' . $e->getMessage());
            }
            
        } catch (\Exception $e) {
            error_log("Error en PayrollController@reopen: " . $e->getMessage());
            
            // Enviar respuesta JSON de error
            if (!headers_sent()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            return;
        }
    }

    /**
     * Marcar planilla como PENDIENTE (desde estado PROCESADA)
     */
    public function markPending($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            // Verificar que la planilla existe y está en estado PROCESADA
            $payroll = $this->payrollModel->find($id);
            if (!$payroll) {
                throw new \Exception('Planilla no encontrada');
            }

            if ($payroll['estado'] !== 'PROCESADA') {
                throw new \Exception('Solo se puede cambiar a PENDIENTE una planilla PROCESADA');
            }

            // Obtener motivo del formulario
            $motivo = trim($_POST['motivo'] ?? 'Planilla marcada como pendiente sin motivo especificado');
            if (empty($motivo)) {
                throw new \Exception('El motivo es obligatorio para marcar la planilla como pendiente');
            }

            // Iniciar transacción
            $this->db->beginTransaction();
            
            try {
                // Eliminar todos los registros asociados a la planilla
                $this->deleteAllPayrollRecords($id);
                
                // Cambiar el estado a PENDIENTE
                $result = $this->payrollModel->update($id, ['estado' => 'PENDIENTE']);

                if ($result) {
                    // Registrar en auditoría
                    $this->registerAuditTrail($id, 'PROCESADA', 'PENDIENTE', $motivo, 0);
                    
                    $this->db->commit();
                    $_SESSION['success'] = 'Planilla marcada como PENDIENTE y todos los registros asociados han sido eliminados. La planilla está lista para ser reprocesada.';
                } else {
                    $this->db->rollback();
                    throw new \Exception('No se pudo actualizar el estado de la planilla');
                }
            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            error_log("Error en PayrollController@markPending: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/panel/payrolls');
    }

    /**
     * Realizar rollback de datos acumulados para una planilla específica
     */
    private function rollbackAccumulatedData($payrollId)
    {
        $acumuladosAfectados = 0;
        
        try {
            // Verificar qué tablas de acumulados existen antes de intentar eliminar
            $tablesExist = $this->checkAccumulationTablesExist();
            
            $detailedRecords = 0;
            $consolidatedRecords = 0;
            
            // 1. Eliminar registros de acumulados por empleado si la tabla existe
            if ($tablesExist['acumulados_por_empleado']) {
                try {
                    $countAndDeleteDetailed = $this->db->prepare("
                        DELETE FROM acumulados_por_empleado 
                        WHERE planilla_id = ?
                    ");
                    $countAndDeleteDetailed->execute([$payrollId]);
                    $detailedRecords = $countAndDeleteDetailed->rowCount();
                } catch (\Exception $e) {
                    error_log("Error eliminando de acumulados_por_empleado: " . $e->getMessage());
                }
            }
            
            // 2. Eliminar registros de acumulados consolidados si la tabla existe
            if ($tablesExist['acumulados_por_planilla']) {
                try {
                    $countAndDeleteConsolidated = $this->db->prepare("
                        DELETE FROM acumulados_por_planilla 
                        WHERE planilla_id = ?
                    ");
                    $countAndDeleteConsolidated->execute([$payrollId]);
                    $consolidatedRecords = $countAndDeleteConsolidated->rowCount();
                } catch (\Exception $e) {
                    error_log("Error eliminando de acumulados_por_planilla: " . $e->getMessage());
                }
            }
            
            // 3. Verificar si planilla_cabecera tiene campos de cierre antes de resetear
            if ($this->hasClosureFields()) {
                try {
                    $resetClosureStmt = $this->db->prepare("
                        UPDATE planilla_cabecera 
                        SET fecha_cierre = NULL,
                            usuario_cierre = NULL,
                            acumulados_generados = 0
                        WHERE id = ?
                    ");
                    $resetClosureStmt->execute([$payrollId]);
                } catch (\Exception $e) {
                    error_log("Error reseteando campos de cierre: " . $e->getMessage());
                }
            }
            
            $acumuladosAfectados = $detailedRecords + $consolidatedRecords;
            
            if ($acumuladosAfectados > 0) {
                error_log("Rollback de acumulados completado: {$detailedRecords} registros detallados + {$consolidatedRecords} registros consolidados eliminados para planilla {$payrollId}");
            } else {
                error_log("Rollback completado - no se encontraron registros de acumulados para eliminar en planilla {$payrollId}");
            }
            
        } catch (\Exception $e) {
            error_log("Error en rollback de acumulados: " . $e->getMessage());
            // No lanzar excepción aquí para no interrumpir el proceso de reapertura
            error_log("Continuando con reapertura sin rollback de acumulados...");
        }
        
        return $acumuladosAfectados;
    }

    /**
     * Verificar si las tablas de acumulados existen
     */
    private function checkAccumulationTablesExist()
    {
        $result = [
            'acumulados_por_empleado' => false,
            'acumulados_por_planilla' => false
        ];
        
        try {
            // Verificar acumulados_por_empleado
            $stmt1 = $this->db->prepare("SHOW TABLES LIKE 'acumulados_por_empleado'");
            $stmt1->execute();
            $result['acumulados_por_empleado'] = $stmt1->rowCount() > 0;
            
            // Verificar acumulados_por_planilla
            $stmt2 = $this->db->prepare("SHOW TABLES LIKE 'acumulados_por_planilla'");
            $stmt2->execute();
            $result['acumulados_por_planilla'] = $stmt2->rowCount() > 0;
            
        } catch (\Exception $e) {
            error_log("Error verificando tablas de acumulados: " . $e->getMessage());
        }
        
        return $result;
    }

    /**
     * Verificar si planilla_cabecera tiene campos de cierre
     */
    private function hasClosureFields()
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM planilla_cabecera LIKE 'fecha_cierre'");
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            error_log("Error verificando campos de cierre: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar cambio de estado en auditoría
     */
    private function registerAuditTrail($planillaId, $estadoAnterior, $estadoNuevo, $motivo, $acumuladosAfectados = 0)
    {
        try {
            // Verificar si la tabla de auditoría existe
            $checkTable = $this->db->prepare("SHOW TABLES LIKE 'planilla_auditoria'");
            $checkTable->execute();
            
            if ($checkTable->rowCount() == 0) {
                error_log("Tabla planilla_auditoria no existe - saltando auditoría");
                return;
            }
            
            $auditStmt = $this->db->prepare("
                INSERT INTO planilla_auditoria 
                (planilla_id, estado_anterior, estado_nuevo, usuario, motivo, acumulados_afectados)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $auditStmt->execute([
                $planillaId,
                $estadoAnterior,
                $estadoNuevo,
                $_SESSION['admin_name'] ?? 'Sistema',
                $motivo,
                $acumuladosAfectados
            ]);
            
        } catch (\Exception $e) {
            error_log("Error registrando auditoría: " . $e->getMessage());
            // No lanzar excepción aquí para no interrumpir el proceso principal
        }
    }

    /**
     * Eliminar todos los registros asociados a una planilla
     * Se ejecuta cuando se cambia el estado a PENDIENTE
     * 
     * @param int $payrollId ID de la planilla
     * @throws \Exception Si hay errores en la eliminación
     */
    private function deleteAllPayrollRecords($payrollId)
    {
        try {
            $deletedRecords = [];
            
            // 1. Eliminar detalles de planilla (empleados y conceptos) - CRÍTICO
            try {
                $deleteDetallesStmt = $this->db->prepare("DELETE FROM planilla_detalle WHERE planilla_cabecera_id = ?");
                $detallesDeleted = $deleteDetallesStmt->execute([$payrollId]);
                $detallesCount = $deleteDetallesStmt->rowCount();
                $deletedRecords['detalles'] = $detallesCount;
                
                if (!$detallesDeleted) {
                    throw new \Exception('Error crítico al eliminar detalles de planilla');
                }
            } catch (\Exception $e) {
                error_log("Error crítico eliminando planilla_detalle: " . $e->getMessage());
                throw new \Exception('No se pudieron eliminar los detalles de la planilla: ' . $e->getMessage());
            }
            
            // 2. Eliminar acumulados usando el nuevo procesador
            try {
                // Cargar el procesador de acumulados
                require_once __DIR__ . '/../Models/PayrollAccumulationsProcessor.php';
                $accumulationsProcessor = new \App\Models\PayrollAccumulationsProcessor();
                
                // Eliminar tanto acumulados detallados como consolidados
                $accumulationResults = $accumulationsProcessor->deletePayrollAccumulations($payrollId);
                $deletedRecords['acumulados'] = $accumulationResults['total_deleted'];
                $deletedRecords['acumulados_detalle'] = $accumulationResults['detailed_deleted'];
                $deletedRecords['acumulados_consolidado'] = $accumulationResults['consolidated_deleted'];
                
            } catch (\Exception $e) {
                error_log("Error eliminando acumulados con procesador: " . $e->getMessage());
                $deletedRecords['acumulados'] = 'Error: ' . $e->getMessage();
                
                // Fallback al método anterior si el procesador falla
                try {
                    $deleteAcumuladosStmt = $this->db->prepare("DELETE FROM acumulados_por_planilla WHERE planilla_id = ?");
                    $acumuladosDeleted = $deleteAcumuladosStmt->execute([$payrollId]);
                    $acumuladosCount = $deleteAcumuladosStmt->rowCount();
                    $deletedRecords['acumulados_fallback'] = $acumuladosCount;
                } catch (\Exception $fallbackError) {
                    error_log("Error en fallback de eliminación de acumulados: " . $fallbackError->getMessage());
                }
            }
            
            // 3. Eliminar registros de consolidados si existen (tabla opcional)
            try {
                $deleteConsolidadosStmt = $this->db->prepare("DELETE FROM planillas_acumulados_consolidados WHERE planilla_id = ?");
                $deleteConsolidadosStmt->execute([$payrollId]);
                $consolidadosCount = $deleteConsolidadosStmt->rowCount();
                $deletedRecords['consolidados'] = $consolidadosCount;
            } catch (\Exception $e) {
                error_log("Tabla planillas_acumulados_consolidados no existe o error al eliminar: " . $e->getMessage());
                $deletedRecords['consolidados'] = 'No disponible';
            }
            
            // 4. Registrar en log resumen de eliminación
            $summary = "Planilla ID $payrollId eliminada - ";
            $summary .= "Detalles: {$deletedRecords['detalles']}, ";
            $summary .= "Acumulados: {$deletedRecords['acumulados']}, ";
            $summary .= "Consolidados: {$deletedRecords['consolidados']}";
            error_log($summary);
            
            // Verificar que al menos los detalles se eliminaron (crítico)
            if (!isset($deletedRecords['detalles']) || $deletedRecords['detalles'] === 0) {
                error_log("Advertencia: No se encontraron detalles para eliminar en planilla $payrollId");
            }
            
        } catch (\Exception $e) {
            error_log("Error en deleteAllPayrollRecords para planilla $payrollId: " . $e->getMessage());
            throw new \Exception('Error al eliminar registros de la planilla: ' . $e->getMessage());
        }
    }

    /**
     * Calcular valor de referencia según la unidad del concepto
     */
    private function calculateReferenceValue($concept, $employee)
    {
        $unidad = $concept['unidad'] ?? 'monto';

        switch (strtolower($unidad)) {
            case 'dias':
            case 'día':
            case 'días':
                // Para conceptos en días, usar días del período (típicamente 15 o 30)
                return 15; // Valor por defecto para quincena

            case 'horas':
            case 'hora':
                // Para conceptos en horas, calcular horas laborables estándar
                return 120; // 8 horas * 15 días para quincena

            case 'porcentaje':
            case '%':
                // Para porcentajes, usar 100 como base
                return 100;

            case 'monto':
            case 'cantidad':
            default:
                // Para montos fijos, usar 1 como multiplicador
                return 1;
        }
    }
}