<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Creditor;
use App\Models\Deduction;
use App\Models\Concept;

/**
 * Controlador para gestión de acreedores
 */
class CreditorController extends Controller
{
    private $creditorModel;
    private $deductionModel;
    private $conceptModel;

    public function __construct()
    {
        parent::__construct();
        $this->creditorModel = new Creditor();
        $this->deductionModel = new Deduction();
        $this->conceptModel = new Concept();
    }

    /**
     * Listar acreedores
     */
    public function index()
    {
        try {
            $creditors = $this->creditorModel->getAllActive();
            $stats = $this->creditorModel->getStats();
            
            $data = [
                'title' => 'Gestión de Acreedores',
                'creditors' => $creditors,
                'stats' => $stats
            ];

            $this->render('admin/creditors/index', $data);
        } catch (\Exception $e) {
            error_log("Error in CreditorController::index: " . $e->getMessage());
            $this->redirect('/panel/dashboard');
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        try {
            $data = [
                'title' => 'Crear Acreedor',
                'tipos' => $this->creditorModel->getTiposAcreedor()
            ];

            $this->render('admin/creditors/create', $data);
        } catch (\Exception $e) {
            error_log("Error in CreditorController::create: " . $e->getMessage());
            $this->redirect('/panel/creditors');
        }
    }

    /**
     * Procesar creación de acreedor
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/creditors');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/panel/creditors/create');
                return;
            }

            $creditorData = [
                'description' => $_POST['description'] ?? '',
                'amount' => $_POST['amount'] ?? 0,
                'creditor_id' => $_POST['creditor_id'] ?? '',
                'employee_id' => $_POST['employee_id'] ?? '',
                'tipo' => $_POST['tipo'] ?? 'OTRO',
                'activo' => $_POST['estado'] ?? 1,
                'observaciones' => $_POST['observaciones'] ?? ''
            ];

            $result = $this->creditorModel->create($creditorData);

            if ($result['success']) {
                $creditorId = $result['id'];
                
                // Crear concepto automáticamente para el acreedor
                $conceptCreated = $this->createConceptForCreditor($creditorId, $creditorData['description']);
                
                if ($conceptCreated) {
                    $_SESSION['success'] = 'Acreedor y concepto creados exitosamente';
                } else {
                    $_SESSION['success'] = 'Acreedor creado exitosamente (concepto no pudo crearse)';
                }
                
                $this->redirect('/panel/creditors');
            } else {
                $_SESSION['error'] = $result['message'];
                $this->redirect('/panel/creditors/create');
            }

        } catch (\Exception $e) {
            error_log("Error in CreditorController::store: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear acreedor';
            $this->redirect('/panel/creditors/create');
        }
    }

    /**
     * Mostrar detalles de acreedor
     */
    public function show($id)
    {
        try {
            $creditor = $this->creditorModel->getWithDeductions($id);
            if (!$creditor) {
                $_SESSION['error'] = 'Acreedor no encontrado';
                $this->redirect('/panel/creditors');
                return;
            }

            $employees = $this->creditorModel->getEmployeesWithDeductions($id);

            $data = [
                'title' => 'Detalles de Acreedor',
                'creditor' => $creditor,
                'employees' => $employees,
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Acreedores', 'url' => '/panel/creditors'],
                    ['name' => $creditor['description'], 'url' => '']
                ]
            ];

            $this->render('admin/creditors/show', $data);
        } catch (\Exception $e) {
            error_log("Error in CreditorController::show: " . $e->getMessage());
            $this->redirect('/panel/creditors');
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        try {
            $creditor = $this->creditorModel->findById($id);
            if (!$creditor) {
                $_SESSION['error'] = 'Acreedor no encontrado';
                $this->redirect('/panel/creditors');
                return;
            }

            $data = [
                'title' => 'Editar Acreedor',
                'creditor' => $creditor,
                'tipos' => $this->creditorModel->getTiposAcreedor()
            ];

            $this->render('admin/creditors/edit', $data);
        } catch (\Exception $e) {
            error_log("Error in CreditorController::edit: " . $e->getMessage());
            $this->redirect('/panel/creditors');
        }
    }

    /**
     * Procesar actualización de acreedor
     */
    public function update($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/creditors');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect("/panel/creditors/{$id}/edit");
                return;
            }

            $creditorData = [
                'description' => $_POST['description'] ?? '',
                'amount' => $_POST['amount'] ?? 0,
                'creditor_id' => $_POST['creditor_id'] ?? '',
                'employee_id' => $_POST['employee_id'] ?? '',
                'tipo' => $_POST['tipo'] ?? '',
                'activo' => $_POST['estado'] ?? 1,
                'observaciones' => $_POST['observaciones'] ?? ''
            ];

            $result = $this->creditorModel->update($id, $creditorData);

            if ($result['success']) {
                $_SESSION['success'] = 'Acreedor actualizado exitosamente';
                $this->redirect('/panel/creditors');
            } else {
                $_SESSION['error'] = $result['message'];
                $this->redirect("/panel/creditors/{$id}/edit");
            }

        } catch (\Exception $e) {
            error_log("Error in CreditorController::update: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar acreedor';
            $this->redirect("/panel/creditors/{$id}/edit");
        }
    }

    /**
     * Eliminar acreedor
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/creditors');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/panel/creditors');
                return;
            }

            $result = $this->creditorModel->delete($id);

            if ($result['success']) {
                $_SESSION['success'] = 'Acreedor eliminado exitosamente';
            } else {
                $_SESSION['error'] = $result['message'];
            }

            $this->redirect('/panel/creditors');

        } catch (\Exception $e) {
            error_log("Error in CreditorController::delete: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar acreedor';
            $this->redirect('/panel/creditors');
        }
    }

    /**
     * Buscar acreedores (AJAX)
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

            $creditors = $this->creditorModel->search($term);
            echo json_encode($creditors);

        } catch (\Exception $e) {
            error_log("Error in CreditorController::search: " . $e->getMessage());
            echo json_encode([]);
        }
    }

    /**
     * Obtener opciones para select (AJAX)
     */
    public function getOptions()
    {
        try {
            header('Content-Type: application/json');
            
            $creditors = $this->creditorModel->getOptions();
            echo json_encode($creditors);

        } catch (\Exception $e) {
            error_log("Error in CreditorController::getOptions: " . $e->getMessage());
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
            
            $creditors = $this->creditorModel->getAllActive();
            
            $data = [];
            foreach ($creditors as $creditor) {
                $employeesCount = $creditor['empleados_asignados'] ?? 0;
                $totalAmount = $creditor['monto_total_asignado'] ?? 0;

                $employeesBadge = $employeesCount > 0 ? 
                    '<span class="badge badge-info">' . $employeesCount . ' empleados</span>' :
                    '<span class="badge badge-light">Sin asignaciones</span>';

                $amountFormatted = 'Q' . number_format($totalAmount, 2);

                $actions = '
                    <div class="btn-group" role="group">
                        <a href="/panel/creditors/' . $creditor['id'] . '" 
                           class="btn btn-info btn-sm" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/panel/creditors/' . $creditor['id'] . '/edit" 
                           class="btn btn-warning btn-sm" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" 
                                class="btn btn-danger btn-sm" 
                                onclick="confirmDelete(' . $creditor['id'] . ', \'' . htmlspecialchars($creditor['description']) . '\')"
                                title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>';

                $data[] = [
                    $creditor['id'],
                    htmlspecialchars($creditor['description']),
                    $creditor['creditor_id'] ?: 'N/A',
                    $employeesBadge,
                    $amountFormatted,
                    $actions
                ];
            }

            echo json_encode(['data' => $data]);

        } catch (\Exception $e) {
            error_log("Error in CreditorController::getData: " . $e->getMessage());
            echo json_encode(['data' => []]);
        }
    }

    /**
     * Dashboard/resumen de acreedores
     */
    public function dashboard()
    {
        try {
            $stats = $this->creditorModel->getStats();
            $recentCreditors = $this->creditorModel->getAllActive();
            $deductionStats = $this->deductionModel->getStats();

            $data = [
                'title' => 'Dashboard de Acreedores',
                'stats' => $stats,
                'deduction_stats' => $deductionStats,
                'recent_creditors' => array_slice($recentCreditors, 0, 10),
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Dashboard Acreedores', 'url' => '']
                ]
            ];

            $this->render('admin/creditors/dashboard', $data);
        } catch (\Exception $e) {
            error_log("Error in CreditorController::dashboard: " . $e->getMessage());
            $this->redirect('/panel/creditors');
        }
    }

    /**
     * Crear concepto automáticamente para el acreedor
     */
    private function createConceptForCreditor($creditorId, $creditorDescription)
    {
        try {
            // Generar código único para el concepto
            $conceptCode = $this->generateConceptCode($creditorDescription);
            
            // Crear descripción del concepto
            $conceptDescription = 'DEDUCCIÓN ' . strtoupper($creditorDescription);
            
            // Crear la fórmula con el ID del acreedor
            $formula = "ACREEDOR(EMPLEADO, $creditorId)";
            
            // Datos del concepto
            $conceptData = [
                'concepto' => $conceptCode,
                'descripcion' => $conceptDescription,
                'tipo_concepto' => 'D', // Corregir tipo para deducción
                'formula' => $formula,
                'valor_fijo' => null,
                'monto_cero' => 1, // Permitir montos cero para deducciones
                'monto_calculo' => 1, // Usar cálculo con fórmula
                'imprime_detalles' => 1 // Imprimir en detalles de planilla
            ];
            
            // Crear el concepto
            $conceptId = $this->conceptModel->create($conceptData);
            
            if ($conceptId) {
                // Crear relaciones por defecto para el concepto
                $this->createDefaultConceptRelations($conceptId);
                
                error_log("Concepto creado automáticamente para acreedor $creditorId: ID $conceptId");
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            error_log("Error creating concept for creditor $creditorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear relaciones por defecto para conceptos de acreedores
     */
    private function createDefaultConceptRelations($conceptId)
    {
        try {
            $db = $this->creditorModel->db;
            
            // 1. Relaciones con TIPOS DE PLANILLA
            // Por defecto, aplicar a tipos más comunes: Quincenal (1) y Mensual (2)
            $defaultPayrollTypes = [1, 2]; // Quincenal y Planilla cada mes
            
            foreach ($defaultPayrollTypes as $typeId) {
                $stmt = $db->prepare("INSERT IGNORE INTO concepto_tipos_planilla (concepto_id, tipo_planilla_id) VALUES (?, ?)");
                $stmt->execute([$conceptId, $typeId]);
            }
            
            // 2. Relaciones con FRECUENCIAS  
            // Por defecto, aplicar frecuencia "Se aplica en todas las planillas" (1)
            $defaultFrequencies = [1]; // Se aplica en todas las planillas
            
            foreach ($defaultFrequencies as $frequencyId) {
                $stmt = $db->prepare("INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id) VALUES (?, ?)");
                $stmt->execute([$conceptId, $frequencyId]);
            }
            
            // 3. Relaciones con SITUACIONES
            // Por defecto, aplicar solo a empleados activos (1)
            $defaultSituations = [1]; // Empleado activo
            
            foreach ($defaultSituations as $situationId) {
                $stmt = $db->prepare("INSERT IGNORE INTO concepto_situaciones (concepto_id, situacion_id) VALUES (?, ?)");
                $stmt->execute([$conceptId, $situationId]);
            }
            
            error_log("Relaciones por defecto creadas para concepto $conceptId");
            return true;
            
        } catch (\Exception $e) {
            error_log("Error creating default concept relations for concept $conceptId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar código único para el concepto
     */
    private function generateConceptCode($creditorDescription)
    {
        // Generar código basado en las primeras letras del nombre del acreedor
        $words = explode(' ', strtoupper($creditorDescription));
        $code = '';
        
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $code .= substr($word, 0, 2);
            }
        }
        
        // Limitar a 6 caracteres máximo
        $code = substr($code, 0, 6);
        
        // Verificar si el código ya existe y agregar número si es necesario
        $originalCode = $code;
        $counter = 1;
        
        while ($this->conceptModel->isCodeDuplicate($code)) {
            $code = $originalCode . sprintf('%02d', $counter);
            $counter++;
            
            // Evitar bucle infinito
            if ($counter > 99) {
                $code = 'ACR' . sprintf('%03d', rand(1, 999));
                break;
            }
        }
        
        return $code;
    }
}