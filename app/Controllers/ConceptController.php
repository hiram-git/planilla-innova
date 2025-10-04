<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Concept;
use App\Models\Employee;
use App\Core\Security;
use App\Services\PlanillaConceptCalculator;

class ConceptController extends Controller
{
    private $conceptModel;
    private $employeeModel;

    public function __construct()
    {
        parent::__construct();
        $this->conceptModel = new Concept();
        $this->employeeModel = new Employee();
    }

    /**
     * Mostrar lista de conceptos
     */
    public function index()
    {
        try {
            $concepts = $this->conceptModel->getWithUsageStats();
            
            $this->render('admin/concepts/index', [
                'concepts' => $concepts,
                'page_title' => 'Conceptos de Nómina',
                'csrf_token' => Security::generateToken()
            ]);
        } catch (\Exception $e) {
            error_log("Error en ConceptController@index: " . $e->getMessage());
            $this->redirect('/panel/dashboard?error=Error cargando conceptos');
        }
    }

    /**
     * Mostrar formulario para crear nuevo concepto
     */
    public function create()
    {
        try {
            // Cargar datos para los selects
            $tipoPlanillaModel = new \App\Models\TipoPlanilla();
            $frecuenciaModel = new \App\Models\Frecuencia();
            $situacionModel = new \App\Models\Situacion();

            $this->render('admin/concepts/create', [
                'page_title' => 'Nuevo Concepto',
                'csrf_token' => Security::generateToken(),
                'tipos_planilla' => $tipoPlanillaModel->getActive(),
                'frecuencias' => $frecuenciaModel->getActive(),
                'situaciones' => $situacionModel->getActive()
            ]);
        } catch (\Exception $e) {
            error_log("Error en ConceptController@create: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el formulario de creación';
            $this->redirect('/panel/concepts');
        }
    }

    /**
     * Procesar creación de nuevo concepto
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

            // Obtener todos los datos del formulario
            $descripcion = trim($_POST['descripcion'] ?? '');
            $concepto = trim($_POST['concepto'] ?? '');
            $cuenta_contable = trim($_POST['cuenta_contable'] ?? '');
            $tipo_concepto = $_POST['tipo_concepto'] ?? '';
            $unidad = $_POST['unidad'] ?? '';
            $formula = trim($_POST['formula'] ?? '');
            $valor_fijo = isset($_POST['valor_fijo']) ? floatval($_POST['valor_fijo']) : null;
            
            // Checkboxes de configuración
            $imprime_detalles = isset($_POST['imprime_detalles']) ? 1 : 0;
            $prorratea = isset($_POST['prorratea']) ? 1 : 0;
            $modifica_valor = isset($_POST['modifica_valor']) ? 1 : 0;
            $valor_referencia = isset($_POST['valor_referencia']) ? 1 : 0;
            $monto_calculo = isset($_POST['monto_calculo']) ? 1 : 0;
            $monto_cero = isset($_POST['monto_cero']) ? 1 : 0;
            
            // Arrays de configuración (ahora son IDs de las tablas de referencia)
            $tipos_planilla_ids = $_POST['tipos_planilla'] ?? [];
            $frecuencias_ids = $_POST['frecuencias'] ?? [];
            $situaciones_ids = $_POST['situaciones'] ?? [];
            
            // Configuración de acumulados
            $acumulados_data = $_POST['acumulados'] ?? [];

            // Validaciones básicas (las demás están en frontend)
            if (empty($descripcion)) {
                throw new \Exception('La descripción es obligatoria');
            }

            if (empty($tipo_concepto)) {
                throw new \Exception('El tipo de concepto es obligatorio');
            }

            // Validar tipo de concepto
            if (!in_array($tipo_concepto, ['A', 'D', 'C'])) {
                throw new \Exception('Tipo de concepto inválido');
            }

            // Validar fórmula si se proporciona
            if (!empty($formula)) {
                $validation = $this->validateFormula($formula);
                if (!$validation['valid']) {
                    throw new \Exception('Fórmula inválida: ' . $validation['message']);
                }
            }

            // Crear concepto con todos los campos y relaciones
            $conceptData = [
                'concepto' => $concepto,
                'descripcion' => $descripcion,
                'cuenta_contable' => $cuenta_contable,
                'tipo_concepto' => $tipo_concepto,
                'unidad' => $unidad,
                'formula' => $formula ?: null,
                'valor_fijo' => $valor_fijo,
                'imprime_detalles' => $imprime_detalles,
                'prorratea' => $prorratea,
                'modifica_valor' => $modifica_valor,
                'valor_referencia' => $valor_referencia,
                'monto_calculo' => $monto_calculo,
                'monto_cero' => $monto_cero,
                // Relaciones
                'tipos_planilla_ids' => $tipos_planilla_ids,
                'frecuencias_ids' => $frecuencias_ids,
                'situaciones_ids' => $situaciones_ids
            ];

            $conceptId = $this->conceptModel->createWithRelations($conceptData);

            if ($conceptId) {
                // Guardar acumulados asociados
                if (!empty($acumulados_data)) {
                    $this->saveConceptAcumulados($conceptId, $acumulados_data);
                }
                
                $_SESSION['success'] = 'Concepto creado exitosamente';
                $this->redirect('/panel/concepts');
            } else {
                throw new \Exception('Error al crear el concepto');
            }

        } catch (\Exception $e) {
            error_log("Error en ConceptController@store: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/panel/concepts/create');
        }
    }

    /**
     * Mostrar detalle de concepto específico
     */
    public function show($id)
    {
        try {
            // Cargar concepto con sus relaciones
            $concept = $this->conceptModel->findWithRelations($id);
            if (!$concept) {
                throw new \Exception('Concepto no encontrado');
            }

            // Obtener estadísticas de uso
            $usageStats = $this->conceptModel->getWithUsageStats();
            $conceptStats = null;
            foreach ($usageStats as $stat) {
                if ($stat['id'] == $id) {
                    $conceptStats = $stat;
                    break;
                }
            }

            $this->render('admin/concepts/show', [
                'concept' => $concept,
                'stats' => $conceptStats,
                'page_title' => 'Detalle del Concepto: ' . $concept['descripcion'],
                'csrf_token' => Security::generateToken()
            ]);

        } catch (\Exception $e) {
            error_log("Error en ConceptController@show: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/panel/concepts');
        }
    }

    /**
     * Mostrar formulario para editar concepto
     */
    public function edit($id)
    {
        try {
            // Cargar concepto con sus relaciones
            $concept = $this->conceptModel->findWithRelations($id);
            if (!$concept) {
                throw new \Exception('Concepto no encontrado');
            }

            // Cargar opciones para selects
            $tiposPlanillaModel = new \App\Models\TipoPlanilla();
            $frecuenciasModel = new \App\Models\Frecuencia();
            $situacionesModel = new \App\Models\Situacion();

            $this->render('admin/concepts/edit', [
                'concept' => $concept,
                'page_title' => 'Editar Concepto',
                'csrf_token' => Security::generateToken(),
                'tipos_planilla' => $tiposPlanillaModel->getActive(),
                'frecuencias' => $frecuenciasModel->getActive(),
                'situaciones' => $situacionesModel->getActive()
            ]);

        } catch (\Exception $e) {
            error_log("Error en ConceptController@edit: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/panel/concepts');
        }
    }

    /**
     * Procesar actualización de concepto
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

            $concept = $this->conceptModel->find($id);
            if (!$concept) {
                throw new \Exception('Concepto no encontrado');
            }

            // Validar y preparar datos
            $concepto = trim($_POST['concepto'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $cuenta_contable = trim($_POST['cuenta_contable'] ?? '');
            $tipo_concepto = $_POST['tipo_concepto'] ?? '';
            $unidad = $_POST['unidad'] ?? '';
            $formula = trim($_POST['formula'] ?? '');
            
            // Procesar checkboxes
            $imprime_detalles = isset($_POST['imprime_detalles']) ? 1 : 0;
            $prorratea = isset($_POST['prorratea']) ? 1 : 0;
            $modifica_valor = isset($_POST['modifica_valor']) ? 1 : 0;
            $valor_referencia = isset($_POST['valor_referencia']) ? 1 : 0;
            $monto_calculo = isset($_POST['monto_calculo']) ? 1 : 0;
            $monto_cero = isset($_POST['monto_cero']) ? 1 : 0;
            
            // Procesar arrays de checkboxes múltiples
            $tipos_planilla_ids = isset($_POST['tipos_planilla']) && is_array($_POST['tipos_planilla']) 
                ? $_POST['tipos_planilla'] : [];
            $frecuencias_ids = isset($_POST['frecuencias']) && is_array($_POST['frecuencias']) 
                ? $_POST['frecuencias'] : [];
            $situaciones_ids = isset($_POST['situaciones']) && is_array($_POST['situaciones']) 
                ? $_POST['situaciones'] : [];

            // Configuración de acumulados
            $acumulados_data = $_POST['acumulados'] ?? [];


            // Obtener valor_fijo si se proporciona
            $valor_fijo = !empty($_POST['valor_fijo']) ? floatval($_POST['valor_fijo']) : null;

            if (empty($descripcion) || empty($tipo_concepto)) {
                throw new \Exception('La descripción y el tipo de concepto son obligatorios');
            }

            if (!in_array($tipo_concepto, ['A', 'D', 'C'])) {
                throw new \Exception('Tipo de concepto inválido');
            }

            // Validar fórmula si se proporciona
            if (!empty($formula)) {
                $validation = $this->validateFormula($formula);
                if (!$validation['valid']) {
                    throw new \Exception('Fórmula inválida: ' . $validation['message']);
                }
            }

            // Datos completos del concepto con relaciones
            $updateData = [
                'concepto' => $concepto,
                'descripcion' => $descripcion,
                'cuenta_contable' => $cuenta_contable,
                'tipo_concepto' => $tipo_concepto,
                'unidad' => $unidad,
                'formula' => $formula ?: null,
                'valor_fijo' => $valor_fijo,
                'imprime_detalles' => $imprime_detalles,
                'prorratea' => $prorratea,
                'modifica_valor' => $modifica_valor,
                'valor_referencia' => $valor_referencia,
                'monto_calculo' => $monto_calculo,
                'monto_cero' => $monto_cero,
                // Incluir relaciones
                'tipos_planilla_ids' => $tipos_planilla_ids,
                'frecuencias_ids' => $frecuencias_ids,
                'situaciones_ids' => $situaciones_ids
            ];

            // Actualizar concepto con relaciones usando el método especializado
            $result = $this->conceptModel->updateWithRelations($id, $updateData);

            if ($result) {
                // Actualizar acumulados asociados
                if (!empty($acumulados_data)) {
                    $this->saveConceptAcumulados($id, $acumulados_data);
                }
                
                $_SESSION['success'] = 'Concepto actualizado exitosamente';
                $this->redirect('/panel/concepts/' . $id);
            } else {
                throw new \Exception('Error al actualizar el concepto - verificar campos requeridos');
            }

        } catch (\Exception $e) {
            error_log("Error en ConceptController@update: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/panel/concepts/' . $id . '/edit');
        }
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleActive($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $result = $this->conceptModel->toggleActive($id);

            if ($result) {
                $_SESSION['success'] = 'Estado del concepto actualizado';
            } else {
                throw new \Exception('Error al cambiar el estado del concepto');
            }

        } catch (\Exception $e) {
            error_log("Error en ConceptController@toggleActive: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/panel/concepts');
    }

    /**
     * Eliminar concepto
     */
    public function delete($id)
    {
        try {
            // Verificar autenticación
            if (!isset($_SESSION['admin'])) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Debe iniciar sesión para realizar esta acción',
                        'redirect' => url('/panel/login')
                    ]);
                    exit;
                }
                header('Location: ' . url('/panel/login'));
                exit;
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $concept = $this->conceptModel->find($id);
            if (!$concept) {
                throw new \Exception('Concepto no encontrado');
            }

            // Verificar si puede ser eliminado
            if (!$this->conceptModel->canDelete($id)) {
                throw new \Exception('No se puede eliminar el concepto porque ya ha sido usado en planillas procesadas o cerradas. Las referencias en liquidaciones y acumulados se limpian automáticamente.');
            }

            $result = $this->conceptModel->delete($id);

            if ($result) {
                $successMessage = 'Concepto eliminado exitosamente. Se limpiaron automáticamente las referencias en liquidaciones y acumulados.';

                // Si es una petición AJAX, devolver JSON
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => $successMessage
                    ]);
                    exit;
                }

                $_SESSION['success'] = $successMessage;
            } else {
                throw new \Exception('Error al eliminar el concepto');
            }

        } catch (\Exception $e) {
            error_log("Error en ConceptController@delete: " . $e->getMessage());
            
            // Si es una petición AJAX, devolver error JSON
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }
            
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/panel/concepts');
    }

    /**
     * Verificar si la petición es AJAX
     */
    private function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Guardar acumulados asociados a un concepto
     */
    private function saveConceptAcumulados($conceptId, $acumuladosData)
    {
        try {
            // Primero eliminar acumulados existentes (para update)
            $this->db->prepare("DELETE FROM conceptos_acumulados WHERE concepto_id = ?")->execute([$conceptId]);
            
            // Insertar nuevos acumulados
            $stmt = $this->db->prepare("
                INSERT INTO conceptos_acumulados
                (concepto_id, tipo_acumulado_id, factor_acumulacion, observaciones)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($acumuladosData as $tipoAcumuladoId => $config) {
                // Solo procesar si está marcado para incluir
                if (isset($config['incluir']) && $config['incluir'] == 1) {
                    $factor = isset($config['factor']) ? floatval($config['factor']) / 100 : 1.0; // Convertir % a decimal
                    $observaciones = trim($config['observaciones'] ?? '');

                    $stmt->execute([
                        $conceptId,
                        $tipoAcumuladoId,
                        $factor,
                        $observaciones
                    ]);
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Error saving concept acumulados: " . $e->getMessage());
            throw new \Exception("Error al guardar configuración de acumulados: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener acumulados de un concepto (AJAX endpoint)
     * GET /panel/concepts/{id}/acumulados
     */
    public function getAcumulados($conceptId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT ca.*, ta.codigo, ta.descripcion as tipo_descripcion
                FROM conceptos_acumulados ca
                INNER JOIN tipos_acumulados ta ON ca.tipo_acumulado_id = ta.id
                WHERE ca.concepto_id = ?
                ORDER BY ta.codigo
            ");
            $stmt->execute([$conceptId]);
            $acumulados = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($acumulados);
            
        } catch (\Exception $e) {
            error_log("Error getting concept acumulados: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener acumulados del concepto']);
        }
    }

    /**
     * API: Validar fórmula en tiempo real
     */
    public function validateFormulaAjax()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            $formula = trim($_POST['formula'] ?? '');
            $employeeId = intval($_POST['employee_id'] ?? 0);

            if (empty($formula)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'valid' => true,
                    'message' => 'Fórmula vacía - se usará como valor fijo',
                    'result' => null
                ]);
                exit;
            }

            $validation = $this->validateFormula($formula, $employeeId);
            
            header('Content-Type: application/json');
            echo json_encode($validation);

        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'valid' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * API: Probar fórmula con empleado específico
     */
    public function testFormula()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            $formula = trim($_POST['formula'] ?? '');
            $employeeId = intval($_POST['employee_id'] ?? 0);

            if (empty($formula)) {
                throw new \Exception('Fórmula requerida');
            }

            if (!$employeeId) {
                throw new \Exception('ID de empleado requerido');
            }

            // Verificar que el empleado existe
            $employee = $this->employeeModel->find($employeeId);
            if (!$employee) {
                throw new \Exception('Empleado no encontrado');
            }

            // Probar la fórmula
            $calculator = new PlanillaConceptCalculator();
            $calculator->setVariablesColaborador($employeeId);
            
            // Evaluar la fórmula directamente
            $result = $calculator->evaluarFormula($formula);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'result' => $result,
                'employee' => $employee['firstname'] . ' ' . $employee['lastname'],
                'variables' => $calculator->getVariablesColaborador()
            ]);

        } catch (\Exception $e) {
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

            $concept = $this->conceptModel->find($id);
            if (!$concept) {
                throw new \Exception('Concepto no encontrado');
            }

            header('Content-Type: application/json');
            echo json_encode($concept);

        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Buscar conceptos
     */
    public function search()
    {
        try {
            $term = trim($_GET['term'] ?? '');
            if (empty($term)) {
                $concepts = $this->conceptModel->getAll();
            } else {
                $concepts = $this->conceptModel->search($term);
            }

            header('Content-Type: application/json');
            echo json_encode($concepts);

        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Duplicar concepto
     */
    public function duplicate($id)
    {
        // Clean any existing output buffer to prevent parsererror
        if (ob_get_level()) {
            ob_clean();
        }

        try {
            // Log the start of the duplicate operation
            error_log("ConceptController@duplicate started for ID: $id");
            error_log("Request method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log("GET request to duplicate route - redirecting to concepts list");
                // Si es una petición GET (acceso directo), redirigir a la lista de conceptos
                $_SESSION['error'] = 'Acceso directo no permitido. Use la interfaz de duplicación.';
                $this->redirect('/panel/concepts');
                return;
            }


            // Validar token CSRF
            $token = $_POST['csrf_token'] ?? '';
            $sessionToken = $_SESSION['csrf_token'] ?? 'NOT_SET';
            error_log("CSRF token received: '$token'");
            error_log("CSRF token in session: '$sessionToken'");
            error_log("Session ID: " . session_id());

            if (!Security::validateToken($token)) {
                error_log("CSRF validation failed for token: '$token'");
                throw new \Exception('Token de seguridad inválido');
            } else {
                error_log("CSRF validation successful");
            }

            $newDescription = trim($_POST['new_description'] ?? '');
            if (empty($newDescription)) {
                throw new \Exception('Nueva descripción requerida');
            }

            error_log("Attempting to duplicate concept ID $id with description: $newDescription");

            // Check if conceptModel exists
            if (!$this->conceptModel) {
                throw new \Exception('Modelo de concepto no inicializado');
            }

            // Log the call to duplicate
            error_log("Calling conceptModel->duplicate($id, '$newDescription')");
            $newId = $this->conceptModel->duplicate($id, $newDescription);
            error_log("Duplicate operation result: " . ($newId ? "Success (ID: $newId)" : "Failed"));

            if ($newId) {
                $_SESSION['success'] = 'Concepto duplicado exitosamente';

                // Si es una petición AJAX, devolver JSON
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

                    // Ensure no output has been sent
                    if (headers_sent($file, $line)) {
                        error_log("Headers already sent at $file:$line");
                        throw new \Exception('Headers ya enviados, no se puede devolver JSON');
                    }

                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(200);

                    $response = [
                        'success' => true,
                        'message' => 'Concepto duplicado exitosamente',
                        'redirect' => '/panel/concepts/' . $newId . '/edit',
                        'newId' => $newId
                    ];

                    error_log("Sending success JSON response: " . json_encode($response));
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $this->redirect('/panel/concepts/' . $newId . '/edit');
            } else {
                throw new \Exception('Error al duplicar el concepto');
            }

        } catch (\Throwable $e) {
            error_log("Error en ConceptController@duplicate: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Si es una petición AJAX, devolver JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

                // Ensure no output has been sent
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(400);
                }

                $response = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_type' => get_class($e)
                ];

                error_log("Sending error JSON response: " . json_encode($response));
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }

            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/panel/concepts/' . $id);
        }
    }

    /**
     * Validar fórmula usando la calculadora
     */
    private function validateFormula($formula, $employeeId = null)
    {
        try {
            if (empty($formula)) {
                return ['valid' => true, 'message' => 'Fórmula vacía - se usará como valor fijo'];
            }

            // Instanciar la calculadora
            $calculator = new PlanillaConceptCalculator();
            
            // Si se proporciona un empleado, usar sus datos para validar
            if ($employeeId) {
                $calculator->setVariablesColaborador($employeeId);
            } else {
                // Usar un empleado de ejemplo para la validación
                $empleados = $this->employeeModel->all();
                if (!empty($empleados)) {
                    $calculator->setVariablesColaborador($empleados[0]['id']);
                }
            }

            // Validar la fórmula intentando evaluarla
            $conceptoTest = 'VALIDACION_FORMULA';
            
            // Crear un concepto temporal en la calculadora para validar
            $conceptos = $calculator->getConceptos();
            $conceptos[$conceptoTest] = ['id' => 999, 'formula' => $formula];
            
            // Intentar evaluar la fórmula
            $resultado = $calculator->evaluarFormula($conceptoTest);
            
            if (is_numeric($resultado)) {
                return [
                    'valid' => true, 
                    'message' => 'Fórmula válida - Resultado ejemplo: ' . number_format($resultado, 2),
                    'result' => $resultado
                ];
            } else {
                return [
                    'valid' => false,
                    'message' => 'La fórmula no produce un resultado numérico válido'
                ];
            }

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error en la fórmula: ' . $e->getMessage()
            ];
        }
    }
}