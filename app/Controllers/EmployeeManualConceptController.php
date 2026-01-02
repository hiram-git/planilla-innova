<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\EmployeeManualConcept;
use App\Models\Employee;
use App\Models\Concept;
use App\Models\TipoPlanilla;
use App\Models\Frecuencia;

/**
 * EmployeeManualConceptController
 *
 * Controlador para gestión de conceptos manuales asignados a empleados.
 * Permite a RR.HH. asignar ausencias, tardanzas, bonos, etc. sin sincronización automática.
 *
 * @package App\Controllers
 * @version 3.5.18
 */
class EmployeeManualConceptController extends Controller
{
    private $manualConceptModel;
    private $employeeModel;
    private $conceptModel;
    private $tipoPlanillaModel;
    private $frecuenciaModel;

    public function __construct()
    {
        parent::__construct();
        $this->manualConceptModel = new EmployeeManualConcept();
        $this->employeeModel = new Employee();
        $this->conceptModel = new Concept();
        $this->tipoPlanillaModel = new TipoPlanilla();
        $this->frecuenciaModel = new Frecuencia();
    }

    /**
     * Listar todos los conceptos manuales
     */
    public function index()
    {
        try {
            // Obtener filtros
            $filters = [
                'employee_id' => $_GET['employee_id'] ?? null,
                'concepto_id' => $_GET['concepto_id'] ?? null,
                'estado' => $_GET['estado'] ?? null,
                'tipo_planilla_id' => $_GET['tipo_planilla_id'] ?? null
            ];

            $manualConcepts = $this->manualConceptModel->getAllWithDetails($filters);
            $stats = $this->manualConceptModel->getStats();

            // Datos para filtros
            $employees = $this->employeeModel->getAllEmployees();
            $concepts = $this->conceptModel->getAllActive();
            $tiposPlanilla = $this->tipoPlanillaModel->getActiveForSelect();

            $this->render('admin/employee-manual-concepts/index', [
                'manual_concepts' => $manualConcepts,
                'stats' => $stats,
                'employees' => $employees,
                'concepts' => $concepts,
                'tipos_planilla' => $tiposPlanilla,
                'filters' => $filters,
                'page_title' => 'Conceptos Manuales de Empleados',
                'csrf_token' => Security::generateToken()
            ]);

        } catch (\Exception $e) {
            error_log("Error en EmployeeManualConceptController@index: " . $e->getMessage());
            $this->redirect('/panel/dashboard?error=' . urlencode('Error cargando conceptos manuales'));
        }
    }

    /**
     * Mostrar formulario para crear concepto manual
     */
    public function create()
    {
        try {
            $employeeId = $_GET['employee_id'] ?? null;
            $employee = null;

            if ($employeeId) {
                $employee = $this->employeeModel->find($employeeId);
            }

            // Obtener todos los empleados activos para el Select2
            $employees = $this->employeeModel->getActiveEmployees();

            // Obtener solo conceptos de tipo Asignación (A) y Deducción (D)
            $concepts = $this->conceptModel->getManualAssignmentConcepts();
            $tiposPlanilla = $this->tipoPlanillaModel->getActiveForSelect();
            $frecuencias = $this->frecuenciaModel->getActiveForSelect();

            $this->render('admin/employee-manual-concepts/create', [
                'employee' => $employee,
                'employees' => $employees,
                'concepts' => $concepts,
                'tipos_planilla' => $tiposPlanilla,
                'frecuencias' => $frecuencias,
                'page_title' => 'Asignar Concepto Manual',
                'csrf_token' => Security::generateToken()
            ]);

        } catch (\Exception $e) {
            error_log("Error en EmployeeManualConceptController@create: " . $e->getMessage());
            $this->redirect('/panel/employee-manual-concepts');
        }
    }

    /**
     * Procesar creación de concepto manual
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/employee-manual-concepts');
                return;
            }

            // Validar CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/panel/employee-manual-concepts/create');
                return;
            }

            $data = [
                'employee_id' => $_POST['employee_id'] ?? null,
                'concepto_id' => $_POST['concepto_id'] ?? null,
                'tipo_planilla_id' => !empty($_POST['tipo_planilla_id']) ? $_POST['tipo_planilla_id'] : null,
                'frecuencia_id' => !empty($_POST['frecuencia_id']) ? $_POST['frecuencia_id'] : null,
                'unidad' => !empty($_POST['unidad']) ? $_POST['unidad'] : 1.00,
                'hora' => !empty($_POST['hora']) ? $_POST['hora'] : null,
                'monto_fijo' => !empty($_POST['monto_fijo']) ? $_POST['monto_fijo'] : null,
                'observaciones' => $_POST['observaciones'] ?? null,
                'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
                'fecha_fin' => !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null,
                'aplica_una_vez' => isset($_POST['aplica_una_vez']) ? 1 : 0,
                'created_by' => $_SESSION['admin_id'] ?? null
            ];

            // Validar
            $validation = $this->manualConceptModel->validate($data);
            if (!$validation['valid']) {
                $_SESSION['error'] = implode('<br>', $validation['errors']);
                $_SESSION['form_data'] = $data;
                $this->redirect('/panel/employee-manual-concepts/create');
                return;
            }

            $id = $this->manualConceptModel->create($data);

            if ($id) {
                $_SESSION['success'] = 'Concepto manual asignado exitosamente';
                $this->redirect('/panel/employee-manual-concepts');
            } else {
                $_SESSION['error'] = 'Error al crear concepto manual';
                $_SESSION['form_data'] = $data;
                $this->redirect('/panel/employee-manual-concepts/create');
            }

        } catch (\Exception $e) {
            error_log("Error en EmployeeManualConceptController@store: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar concepto manual';
            $this->redirect('/panel/employee-manual-concepts/create');
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        try {
            $manualConcept = $this->manualConceptModel->find($id);

            if (!$manualConcept) {
                $_SESSION['error'] = 'Concepto manual no encontrado';
                $this->redirect('/panel/employee-manual-concepts');
                return;
            }

            if (!$this->manualConceptModel->canEdit($id)) {
                $_SESSION['error'] = 'Este concepto manual no puede ser editado';
                $this->redirect('/panel/employee-manual-concepts');
                return;
            }

            $employee = $this->employeeModel->find($manualConcept['employee_id']);
            // Obtener solo conceptos de tipo Asignación (A) y Deducción (D)
            $concepts = $this->conceptModel->getManualAssignmentConcepts();
            $tiposPlanilla = $this->tipoPlanillaModel->getActiveForSelect();
            $frecuencias = $this->frecuenciaModel->getActiveForSelect();

            $this->render('admin/employee-manual-concepts/edit', [
                'manual_concept' => $manualConcept,
                'employee' => $employee,
                'concepts' => $concepts,
                'tipos_planilla' => $tiposPlanilla,
                'frecuencias' => $frecuencias,
                'page_title' => 'Editar Concepto Manual',
                'csrf_token' => Security::generateToken()
            ]);

        } catch (\Exception $e) {
            error_log("Error en EmployeeManualConceptController@edit: " . $e->getMessage());
            $this->redirect('/panel/employee-manual-concepts');
        }
    }

    /**
     * Procesar actualización
     */
    public function update($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/employee-manual-concepts');
                return;
            }

            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect("/panel/employee-manual-concepts/{$id}/edit");
                return;
            }

            if (!$this->manualConceptModel->canEdit($id)) {
                $_SESSION['error'] = 'Este concepto manual no puede ser editado';
                $this->redirect('/panel/employee-manual-concepts');
                return;
            }

            $data = [
                'tipo_planilla_id' => !empty($_POST['tipo_planilla_id']) ? $_POST['tipo_planilla_id'] : null,
                'frecuencia_id' => !empty($_POST['frecuencia_id']) ? $_POST['frecuencia_id'] : null,
                'unidad' => !empty($_POST['unidad']) ? $_POST['unidad'] : 1.00,
                'hora' => !empty($_POST['hora']) ? $_POST['hora'] : null,
                'monto_fijo' => !empty($_POST['monto_fijo']) ? $_POST['monto_fijo'] : null,
                'observaciones' => $_POST['observaciones'] ?? null,
                'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
                'fecha_fin' => !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null,
                'aplica_una_vez' => isset($_POST['aplica_una_vez']) ? 1 : 0
            ];

            $validation = $this->manualConceptModel->validate($data, $id);
            if (!$validation['valid']) {
                $_SESSION['error'] = implode('<br>', $validation['errors']);
                $this->redirect("/panel/employee-manual-concepts/{$id}/edit");
                return;
            }

            $result = $this->manualConceptModel->update($id, $data);

            if ($result) {
                $_SESSION['success'] = 'Concepto manual actualizado exitosamente';
                $this->redirect('/panel/employee-manual-concepts');
            } else {
                $_SESSION['error'] = 'Error al actualizar concepto manual';
                $this->redirect("/panel/employee-manual-concepts/{$id}/edit");
            }

        } catch (\Exception $e) {
            error_log("Error en EmployeeManualConceptController@update: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar concepto manual';
            $this->redirect("/panel/employee-manual-concepts/{$id}/edit");
        }
    }

    /**
     * Eliminar concepto manual
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                return;
            }

            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Token inválido']);
                return;
            }

            if (!$this->manualConceptModel->canDelete($id)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Este concepto no puede ser eliminado']);
                return;
            }

            $result = $this->manualConceptModel->delete($id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Concepto eliminado exitosamente' : 'Error al eliminar'
            ]);

        } catch (\Exception $e) {
            error_log("Error en EmployeeManualConceptController@delete: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error interno']);
        }
    }

    /**
     * Cancelar concepto manual
     */
    public function cancel($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                return;
            }

            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Token inválido']);
                return;
            }

            $result = $this->manualConceptModel->cancel($id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Concepto cancelado exitosamente' : 'Error al cancelar'
            ]);

        } catch (\Exception $e) {
            error_log("Error en EmployeeManualConceptController@cancel: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error interno']);
        }
    }

    /**
     * Reactivar concepto manual
     */
    public function reactivate($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                return;
            }

            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Token inválido']);
                return;
            }

            $result = $this->manualConceptModel->reactivate($id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Concepto reactivado exitosamente' : 'Error al reactivar'
            ]);

        } catch (\Exception $e) {
            error_log("Error en EmployeeManualConceptController@reactivate: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error interno']);
        }
    }

    /**
     * Buscar empleados para Select2 (AJAX)
     */
    public function searchEmployees()
    {
        try {
            header('Content-Type: application/json');

            $search = $_GET['search'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $sql = "SELECT
                        e.id,
                        CONCAT(e.firstname, ' ', e.lastname, ' (', e.employee_id, ')') as text,
                        e.employee_id as code
                    FROM employees e
                    WHERE e.estado_laboral = 'ACTIVO'
                    AND (e.firstname LIKE ?
                         OR e.lastname LIKE ?
                         OR e.employee_id LIKE ?
                         OR CONCAT(e.firstname, ' ', e.lastname) LIKE ?)
                    ORDER BY e.firstname, e.lastname
                    LIMIT {$limit} OFFSET {$offset}";

            $searchParam = '%' . $search . '%';
            $stmt = $this->employeeModel->db->prepare($sql);
            $stmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
            $employees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Contar total
            $countSql = "SELECT COUNT(*) as total FROM employees e
                        WHERE e.estado_laboral = 'ACTIVO'
                        AND (e.firstname LIKE ? OR e.lastname LIKE ? OR e.employee_id LIKE ?)";
            $countStmt = $this->employeeModel->db->prepare($countSql);
            $countStmt->execute([$searchParam, $searchParam, $searchParam]);
            $total = $countStmt->fetch(\PDO::FETCH_ASSOC)['total'];

            echo json_encode([
                'results' => $employees,
                'pagination' => ['more' => ($offset + $limit) < $total]
            ]);

        } catch (\Exception $e) {
            error_log("Error en searchEmployees: " . $e->getMessage());
            echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
        }
    }

    /**
     * Obtener datos para DataTables (AJAX)
     */
    public function data()
    {
        try {
            header('Content-Type: application/json');

            $filters = [
                'estado' => $_GET['estado'] ?? null,
                'employee_id' => $_GET['employee_id'] ?? null
            ];

            $manualConcepts = $this->manualConceptModel->getAllWithDetails($filters);

            $data = [];
            foreach ($manualConcepts as $mc) {
                $employeeName = $mc['firstname'] . ' ' . $mc['lastname'];
                $badge = $this->getEstadoBadge($mc['estado']);
                $tipoBadge = $this->getTipoConceptoBadge($mc['tipo_concepto']);

                $actions = $this->buildActions($mc);

                $data[] = [
                    $mc['id'],
                    $employeeName,
                    $mc['emp_code'],
                    $mc['concepto_descripcion'],
                    $tipoBadge,
                    $mc['unidad'] ?? '-',
                    $mc['monto_fijo'] ? currency_symbol() . number_format($mc['monto_fijo'], 2) : '-',
                    date('d/m/Y', strtotime($mc['fecha_inicio'])),
                    $mc['fecha_fin'] ? date('d/m/Y', strtotime($mc['fecha_fin'])) : 'Indefinido',
                    $mc['aplica_una_vez'] ? 'Sí' : 'No',
                    $badge,
                    $actions
                ];
            }

            echo json_encode(['data' => $data]);

        } catch (\Exception $e) {
            error_log("Error en getData: " . $e->getMessage());
            echo json_encode(['data' => []]);
        }
    }

    /**
     * Generar badge de estado
     */
    private function getEstadoBadge($estado)
    {
        $badges = [
            'ACTIVO' => '<span class="badge badge-success">Activo</span>',
            'APLICADO' => '<span class="badge badge-info">Aplicado</span>',
            'CANCELADO' => '<span class="badge badge-secondary">Cancelado</span>'
        ];

        return $badges[$estado] ?? '<span class="badge badge-warning">Desconocido</span>';
    }

    /**
     * Generar badge de tipo de concepto
     */
    private function getTipoConceptoBadge($tipo)
    {
        $badges = [
            'I' => '<span class="badge badge-success">Ingreso</span>',
            'D' => '<span class="badge badge-danger">Deducción</span>',
            'P' => '<span class="badge badge-info">Patronal</span>'
        ];

        return $badges[$tipo] ?? '<span class="badge badge-secondary">-</span>';
    }

    /**
     * Construir botones de acciones
     */
    private function buildActions($mc)
    {
        $actions = '<div class="btn-group" role="group">';

        if ($mc['estado'] === 'ACTIVO') {
            $editUrl = \App\Core\UrlHelper::route('panel/employee-manual-concepts/' . $mc['id'] . '/edit');
            $actions .= '<a href="' . $editUrl . '"
                            class="btn btn-sm btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                         </a>';
            $actions .= '<button type="button"
                            class="btn btn-sm btn-secondary btn-cancel"
                            data-id="' . $mc['id'] . '" title="Cancelar">
                            <i class="fas fa-ban"></i>
                         </button>';
        }

        if ($mc['estado'] === 'CANCELADO') {
            $actions .= '<button type="button"
                            class="btn btn-sm btn-success btn-reactivate"
                            data-id="' . $mc['id'] . '" title="Reactivar">
                            <i class="fas fa-redo"></i>
                         </button>';
        }

        if ($this->manualConceptModel->canDelete($mc['id'])) {
            $actions .= '<button type="button"
                            class="btn btn-sm btn-danger btn-delete"
                            data-id="' . $mc['id'] . '" title="Eliminar">
                            <i class="fas fa-trash"></i>
                         </button>';
        }

        $actions .= '</div>';

        return $actions;
    }
}
