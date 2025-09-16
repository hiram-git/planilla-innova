<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Deduction;
use App\Models\Creditor;
use App\Models\Employee;

/**
 * Controlador para gestión de deducciones
 */
class DeductionController extends Controller
{
    private $deductionModel;
    private $creditorModel;
    private $employeeModel;

    public function __construct()
    {
        parent::__construct();
        $this->deductionModel = new Deduction();
        $this->creditorModel = new Creditor();
        $this->employeeModel = new Employee();
    }

    /**
     * Listar deducciones
     */
    public function index()
    {
        try {
            $deductions = $this->deductionModel->getAllWithDetails();
            $stats = $this->deductionModel->getStats();
            $creditors = $this->creditorModel->getActive(); // Para el filtro
            
            // Obtener configuración de empresa para símbolo de moneda
            $companyModel = $this->model('Company');
            $companyConfig = $companyModel->getCompanyConfig();
            $currencySymbol = $companyConfig['currency_symbol'] ?? 'Q';
            
            $data = [
                'title' => 'Gestión de Deducciones',
                'deductions' => $deductions,
                'stats' => $stats,
                'creditors' => $creditors,
                'currency_symbol' => $currencySymbol
            ];

            $this->render('admin/deductions/index', $data);
        } catch (\Exception $e) {
            error_log("Error in DeductionController::index: " . $e->getMessage());
            $this->redirect('/panel/dashboard');
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        try {
            $creditors = $this->creditorModel->getOptions();
            // No cargar empleados aquí - se cargarán dinámicamente con Select2
            
            $data = [
                'title' => 'Crear Deducción',
                'creditors' => $creditors,
                'csrf_token' => Security::generateToken(),
                'frecuencias' => $this->deductionModel::FRECUENCIAS
            ];

            $this->render('admin/deductions/create', $data);
        } catch (\Exception $e) {
            error_log("Error in DeductionController::create: " . $e->getMessage());
            $this->redirect('/panel/deductions');
        }
    }

    /**
     * Procesar creación de deducción
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/deductions');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/panel/deductions/create');
                return;
            }

            $deductionData = [
                'employee_id' => $_POST['employee_id'] ?? '',
                'creditor_id' => $_POST['creditor_id'] ?? '',
                'description' => $_POST['description'] ?? '',
                'amount' => $_POST['amount'] ?? 0
            ];

            $result = $this->deductionModel->create($deductionData);

            if ($result['success']) {
                $_SESSION['success'] = 'Deducción creada exitosamente';
                $this->redirect('/panel/deductions');
            } else {
                $_SESSION['error'] = $result['message'];
                $this->redirect('/panel/deductions/create');
            }

        } catch (\Exception $e) {
            error_log("Error in DeductionController::store: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear deducción';
            $this->redirect('/panel/deductions/create');
        }
    }

    /**
     * Mostrar detalles de deducción
     */
    public function show($id)
    {
        try {
            $deduction = $this->deductionModel->findById($id);
            if (!$deduction) {
                $_SESSION['error'] = 'Deducción no encontrada';
                $this->redirect('/panel/deductions');
                return;
            }

            // Obtener información completa
            $deductionDetails = $this->deductionModel->getAllWithDetails();
            $deductionDetail = array_filter($deductionDetails, function($d) use ($id) {
                return $d['id'] == $id;
            });
            $deductionDetail = reset($deductionDetail);

            $data = [
                'title' => 'Detalles de Deducción',
                'deduction' => $deductionDetail ?: $deduction
            ];

            $this->render('admin/deductions/show', $data);
        } catch (\Exception $e) {
            error_log("Error in DeductionController::show: " . $e->getMessage());
            $this->redirect('/panel/deductions');
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        try {
            $deduction = $this->deductionModel->findById($id);
            if (!$deduction) {
                $_SESSION['error'] = 'Deducción no encontrada';
                $this->redirect('/panel/deductions');
                return;
            }

            // Obtener información del empleado asociado
            $employeeSql = "SELECT e.employee_id, e.firstname, e.lastname 
                           FROM employees e 
                           WHERE e.employee_id = ?";
            $employeeStmt = $this->employeeModel->db->prepare($employeeSql);
            $employeeStmt->execute([$deduction['employee_id']]);
            $employee = $employeeStmt->fetch(\PDO::FETCH_ASSOC);
            
            // Agregar información del empleado a la deducción
            if ($employee) {
                $deduction['employee_name'] = $employee['firstname'] . ' ' . $employee['lastname'];
                $deduction['employee_code'] = $employee['employee_id'];
            }

            $creditors = $this->creditorModel->getOptions();
            // No cargar empleados aquí - se cargarán dinámicamente con Select2

            // Obtener restricciones de edición
            $editRestrictions = $this->deductionModel->getEditRestrictions($id);
            
            // Forzar restricciones: empleado y acreedor NUNCA se pueden editar
            $editRestrictions['canEditEmployee'] = false;
            $editRestrictions['canEditCreditor'] = false;

            // Obtener configuración de empresa para símbolo de moneda
            $companyModel = $this->model('Company');
            $companyConfig = $companyModel->getCompanyConfig();
            $currencySymbol = $companyConfig['currency_symbol'] ?? 'Q';

            $data = [
                'title' => 'Editar Deducción',
                'deduction' => $deduction,
                'creditors' => $creditors,
                'csrf_token' => Security::generateToken(),
                'frecuencias' => $this->deductionModel::FRECUENCIAS,
                'editRestrictions' => $editRestrictions,
                'currency_symbol' => $currencySymbol
            ];

            $this->render('admin/deductions/edit', $data);
        } catch (\Exception $e) {
            error_log("Error in DeductionController::edit: " . $e->getMessage());
            $this->redirect('/panel/deductions');
        }
    }

    /**
     * Procesar actualización de deducción
     */
    public function update($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/deductions');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect("/panel/deductions/{$id}/edit");
                return;
            }

            $deductionData = [
                'employee_id' => $_POST['employee_id'] ?? '',
                'creditor_id' => $_POST['creditor_id'] ?? '',
                'description' => $_POST['description'] ?? '',
                'amount' => $_POST['amount'] ?? 0
            ];

            $result = $this->deductionModel->update($id, $deductionData);

            if ($result['success']) {
                $_SESSION['success'] = 'Deducción actualizada exitosamente';
                $this->redirect('/panel/deductions');
            } else {
                $_SESSION['error'] = $result['message'];
                $this->redirect("/panel/deductions/{$id}/edit");
            }

        } catch (\Exception $e) {
            error_log("Error in DeductionController::update: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar deducción';
            $this->redirect("/panel/deductions/{$id}/edit");
        }
    }

    /**
     * Eliminar deducción
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                // Si es petición AJAX, responder JSON
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                    return;
                }
                $this->redirect('/panel/deductions');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                // Si es petición AJAX, responder JSON
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
                    return;
                }
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/panel/deductions');
                return;
            }

            $result = $this->deductionModel->delete($id);

            // Si es petición AJAX, responder JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode($result);
                return;
            }

            // Si no es AJAX, usar redirect tradicional
            if ($result['success']) {
                $_SESSION['success'] = 'Deducción eliminada exitosamente';
            } else {
                $_SESSION['error'] = $result['message'];
            }

            $this->redirect('/panel/deductions');

        } catch (\Exception $e) {
            error_log("Error in DeductionController::delete: " . $e->getMessage());
            
            // Si es petición AJAX, responder JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error al eliminar deducción']);
                return;
            }
            
            $_SESSION['error'] = 'Error al eliminar deducción';
            $this->redirect('/panel/deductions');
        }
    }

    /**
     * Obtener deducciones por empleado (AJAX)
     */
    public function getByEmployee()
    {
        try {
            header('Content-Type: application/json');

            $employeeId = $_GET['employee_id'] ?? '';
            if (empty($employeeId)) {
                echo json_encode([]);
                return;
            }

            $deductions = $this->deductionModel->getByEmployee($employeeId);
            echo json_encode($deductions);

        } catch (\Exception $e) {
            error_log("Error in DeductionController::getByEmployee: " . $e->getMessage());
            echo json_encode([]);
        }
    }

    /**
     * Obtener deducciones por acreedor (AJAX)
     */
    public function getByCreditor()
    {
        try {
            header('Content-Type: application/json');

            $creditorId = $_GET['creditor_id'] ?? '';
            if (empty($creditorId)) {
                echo json_encode([]);
                return;
            }

            $deductions = $this->deductionModel->getByCreditor($creditorId);
            echo json_encode($deductions);

        } catch (\Exception $e) {
            error_log("Error in DeductionController::getByCreditor: " . $e->getMessage());
            echo json_encode([]);
        }
    }

    /**
     * Buscar deducciones (AJAX)
     */
    public function search()
    {
        try {
            header('Content-Type: application/json');

            $term = $_GET['term'] ?? '';
            if (strlen($term) < 2) {
                echo json_encode([]);
                return;
            }

            $deductions = $this->deductionModel->search($term);
            echo json_encode($deductions);

        } catch (\Exception $e) {
            error_log("Error in DeductionController::search: " . $e->getMessage());
            echo json_encode([]);
        }
    }

    /**
     * Obtener datos para DataTables (AJAX)
     */
    public function getData()
    {
        try {
            header('Content-Type: application/json');
            
            $deductions = $this->deductionModel->getAllWithDetails();
            
            $data = [];
            foreach ($deductions as $deduction) {
                $employeeName = $deduction['firstname'] . ' ' . $deduction['lastname'];
                $creditorName = $deduction['creditor_name'] ?: 'Sin acreedor';
                $amount = 'Q' . number_format($deduction['amount'], 2);

                $actions = '
                    <div class="btn-group" role="group">
                        <a href="/panel/deductions/' . $deduction['id'] . '" 
                           class="btn btn-info btn-sm" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/panel/deductions/' . $deduction['id'] . '/edit" 
                           class="btn btn-warning btn-sm" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" 
                                class="btn btn-danger btn-sm" 
                                onclick="confirmDelete(' . $deduction['id'] . ', \'' . htmlspecialchars($employeeName) . '\')"
                                title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>';

                $data[] = [
                    $deduction['id'],
                    $employeeName,
                    $deduction['emp_code'] ?? 'N/A',
                    $creditorName,
                    $deduction['description'] ?: 'Sin descripción',
                    $amount,
                    $actions
                ];
            }

            echo json_encode(['data' => $data]);

        } catch (\Exception $e) {
            error_log("Error in DeductionController::getData: " . $e->getMessage());
            echo json_encode(['data' => []]);
        }
    }

    /**
     * Resumen/dashboard de deducciones
     */
    public function dashboard()
    {
        try {
            $stats = $this->deductionModel->getStats();
            $recentDeductions = array_slice($this->deductionModel->getAllWithDetails(), 0, 10);

            $data = [
                'title' => 'Dashboard de Deducciones',
                'stats' => $stats,
                'recent_deductions' => $recentDeductions
            ];

            $this->render('admin/deductions/dashboard', $data);
        } catch (\Exception $e) {
            error_log("Error in DeductionController::dashboard: " . $e->getMessage());
            $this->redirect('/panel/deductions');
        }
    }

    /**
     * Obtener información del empleado para AJAX
     */
    public function employeeInfo()
    {
        try {
            header('Content-Type: application/json');

            $employeeId = $_GET['employee_id'] ?? '';
            if (empty($employeeId)) {
                echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
                return;
            }

            // Obtener información básica del empleado
            $sql = "SELECT 
                        e.id, e.employee_id, e.firstname, e.lastname,
                        p.codigo as position_name
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    WHERE e.employee_id = ?";
            
            $stmt = $this->employeeModel->db->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($employee) {
                // Formatear datos básicos para el frontend
                $response = [
                    'success' => true,
                    'data' => [
                        'name' => $employee['firstname'] . ' ' . $employee['lastname'],
                        'code' => $employee['employee_id'],
                        'position' => $employee['position_name'] ?: 'Sin puesto'
                    ]
                ];
                
                echo json_encode($response);
            } else {
                echo json_encode(['success' => false, 'message' => 'Empleado no encontrado']);
            }

        } catch (\Exception $e) {
            error_log("Error in DeductionController::employeeInfo: " . $e->getMessage());
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
            $limit = 20; // Mostrar 20 resultados por página
            $offset = ($page - 1) * $limit;

            // Búsqueda en empleados
            $sql = "SELECT 
                        e.employee_id as id, 
                        CONCAT(e.firstname, ' ', e.lastname, ' (', e.employee_id, ')') as text,
                        e.firstname, 
                        e.lastname,
                        e.employee_id as code,
                        p.codigo as position_name
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    WHERE (e.firstname LIKE ? 
                         OR e.lastname LIKE ? 
                         OR e.employee_id LIKE ?
                         OR CONCAT(e.firstname, ' ', e.lastname) LIKE ?)";

            // Contar total para paginación
            $countSql = "SELECT COUNT(*) as total 
                        FROM employees e
                        WHERE (e.firstname LIKE ? 
                             OR e.lastname LIKE ? 
                             OR e.employee_id LIKE ?
                             OR CONCAT(e.firstname, ' ', e.lastname) LIKE ?)";

            $searchParam = '%' . $search . '%';
            
            // Ejecutar conteo
            $countStmt = $this->employeeModel->db->prepare($countSql);
            $countStmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
            $totalCount = $countStmt->fetch(\PDO::FETCH_ASSOC)['total'];

            // Ejecutar búsqueda con límite
            $sql .= " ORDER BY e.firstname, e.lastname LIMIT $limit OFFSET $offset";
            $stmt = $this->employeeModel->db->prepare($sql);
            $stmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
            $employees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Formato para Select2
            $results = [
                'results' => $employees,
                'pagination' => [
                    'more' => ($offset + $limit) < $totalCount
                ]
            ];

            echo json_encode($results);

        } catch (\Exception $e) {
            error_log("Error in DeductionController::searchEmployees: " . $e->getMessage());
            echo json_encode([
                'results' => [],
                'pagination' => ['more' => false]
            ]);
        }
    }

    /**
     * Verificar deducción duplicada para AJAX
     */
    public function checkDuplicate()
    {
        try {
            header('Content-Type: application/json');

            $employeeId = $_GET['employee_id'] ?? '';
            $creditorId = $_GET['creditor_id'] ?? '';
            
            if (empty($employeeId) || empty($creditorId)) {
                echo json_encode(['exists' => false]);
                return;
            }

            // Verificar si existe la combinación
            $sql = "SELECT COUNT(*) as count FROM deductions WHERE employee_id = ? AND creditor_id = ?";
            $stmt = $this->deductionModel->db->prepare($sql);
            $stmt->execute([$employeeId, $creditorId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            echo json_encode(['exists' => $result['count'] > 0]);

        } catch (\Exception $e) {
            error_log("Error in DeductionController::checkDuplicate: " . $e->getMessage());
            echo json_encode(['exists' => false]);
        }
    }

    /**
     * Copia masiva de deducciones (para aplicar la misma deducción a múltiples empleados)
     */
    public function massAssign()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validar CSRF token
                if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                    $_SESSION['error'] = 'Token de seguridad inválido';
                    $this->redirect('/panel/deductions');
                    return;
                }

                $creditorId = $_POST['creditor_id'] ?? '';
                $employeeIds = $_POST['employee_ids'] ?? [];
                $amount = $_POST['amount'] ?? 0;
                $description = $_POST['description'] ?? '';

                if (empty($creditorId) || empty($employeeIds) || !is_numeric($amount)) {
                    $_SESSION['error'] = 'Datos incompletos para asignación masiva';
                    $this->redirect('/panel/deductions');
                    return;
                }

                $success = 0;
                $errors = [];

                foreach ($employeeIds as $employeeId) {
                    $deductionData = [
                        'employee_id' => $employeeId,
                        'creditor_id' => $creditorId,
                        'description' => $description,
                        'amount' => $amount
                    ];

                    $result = $this->deductionModel->create($deductionData);
                    if ($result['success']) {
                        $success++;
                    } else {
                        $errors[] = "Error para empleado $employeeId: " . $result['message'];
                    }
                }

                if ($success > 0) {
                    $_SESSION['success'] = "Se crearon $success deducciones exitosamente";
                }
                if (!empty($errors)) {
                    $_SESSION['error'] = implode('<br>', $errors);
                }

                $this->redirect('/panel/deductions');
                return;
            }

            // Mostrar formulario de asignación masiva
            $creditors = $this->creditorModel->getOptions();
            $employees = $this->employeeModel->getOptions();

            $data = [
                'title' => 'Asignación Masiva de Deducciones',
                'creditors' => $creditors,
                'employees' => $employees
            ];

            $this->render('admin/deductions/mass_assign', $data);

        } catch (\Exception $e) {
            error_log("Error in DeductionController::massAssign: " . $e->getMessage());
            $_SESSION['error'] = 'Error en asignación masiva';
            $this->redirect('/panel/deductions');
        }
    }
}