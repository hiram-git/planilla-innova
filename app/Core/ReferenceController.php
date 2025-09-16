<?php

namespace App\Core;

use App\Core\Controller;
use App\Core\Security;
use App\Middleware\AuthMiddleware;

/**
 * Controlador base abstracto para entidades de referencia
 * Implementa CRUD básico para tablas con estructura estándar: id, codigo, nombre, descripcion, activo
 */
abstract class ReferenceController extends Controller
{
    protected $modelName;
    protected $viewPath;
    protected $routeName;
    protected $singularName;
    protected $pluralName;
    
    public function __construct()
    {
        parent::__construct();
        AuthMiddleware::requireAuth();
        $this->initializeNames();
    }
    
    abstract protected function initializeNames();
    
    public function index()
    {
        $model = $this->model($this->modelName);
        
        $data = [
            'title' => 'Gestión de ' . $this->pluralName,
            'page_title' => $this->pluralName,
            'items' => $model->all(),
            'csrf_token' => AuthMiddleware::generateCSRF(),
            'singular_name' => $this->singularName,
            'plural_name' => $this->pluralName,
            'route_name' => $this->routeName
        ];

        $this->view("admin/{$this->viewPath}/index", $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Agregar ' . $this->singularName,
            'page_title' => 'Agregar ' . $this->singularName,
            'csrf_token' => AuthMiddleware::generateCSRF(),
            'singular_name' => $this->singularName,
            'plural_name' => $this->pluralName,
            'route_name' => $this->routeName
        ];

        // Generar código sugerido si el modelo tiene el método getNextCode
        $model = $this->model($this->modelName);
        if (method_exists($model, 'getNextCode')) {
            $data['suggested_code'] = $model->getNextCode();
        }

        $this->view("admin/{$this->viewPath}/create", $data);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("/panel/{$this->routeName}");
        }

        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);
        $model = $this->model($this->modelName);

        // Validación básica
        $errors = $model->validateReferenceData($data);

        // Validar unicidad de código
        if (isset($data['codigo']) && !$model->isCodigoUnique($data['codigo'])) {
            $errors['codigo'] = 'El código ya está registrado';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect("/panel/{$this->routeName}/create");
        }

        try {
            $result = $model->create($data);
            $_SESSION['success'] = $this->singularName . ' creado exitosamente';
            $this->redirect("/panel/{$this->routeName}");
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al crear ' . strtolower($this->singularName) . ': ' . $e->getMessage();
            $this->redirect("/panel/{$this->routeName}/create");
        }
    }

    public function edit($id)
    {
        $model = $this->model($this->modelName);
        $item = $model->find($id);
        
        if (!$item) {
            $_SESSION['error'] = $this->singularName . ' no encontrado';
            $this->redirect("/panel/{$this->routeName}");
        }

        $data = [
            'title' => 'Editar ' . $this->singularName,
            'page_title' => 'Editar ' . $this->singularName,
            'item' => $item,
            'csrf_token' => AuthMiddleware::generateCSRF(),
            'singular_name' => $this->singularName,
            'plural_name' => $this->pluralName,
            'route_name' => $this->routeName
        ];

        $this->view("admin/{$this->viewPath}/edit", $data);
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("/panel/{$this->routeName}");
        }

        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);
        $model = $this->model($this->modelName);

        $item = $model->find($id);
        if (!$item) {
            $_SESSION['error'] = $this->singularName . ' no encontrado';
            $this->redirect("/panel/{$this->routeName}");
        }

        // Validación
        $errors = $model->validateReferenceUpdateData($data);

        // Validar unicidad de código (excluyendo el actual)
        if (isset($data['edit_codigo']) && !$model->isCodigoUnique($data['edit_codigo'], $id)) {
            $errors['edit_codigo'] = 'El código ya está registrado';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect("/panel/{$this->routeName}/{$id}/edit");
        }

        try {
            $updateData = [
                'codigo' => $data['edit_codigo'],
                'nombre' => $data['edit_nombre'],
                'descripcion' => $data['edit_descripcion'] ?? '',
                'activo' => isset($data['edit_activo']) ? 1 : 0
            ];

            $model->update($id, $updateData);
            $_SESSION['success'] = $this->singularName . ' actualizado exitosamente';
            $this->redirect("/panel/{$this->routeName}");
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al actualizar ' . strtolower($this->singularName) . ': ' . $e->getMessage();
            $this->redirect("/panel/{$this->routeName}/{$id}/edit");
        }
    }

    public function delete($id)
    {
        AuthMiddleware::requireAuth();
        
        $model = $this->model($this->modelName);
        
        try {
            $item = $model->find($id);
            if (!$item) {
                $_SESSION['error'] = $this->singularName . ' no encontrado';
                $this->redirect("/panel/{$this->routeName}");
            }

            $model->delete($id);
            $_SESSION['success'] = $this->singularName . ' eliminado exitosamente';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al eliminar ' . strtolower($this->singularName);
        }

        $this->redirect("/panel/{$this->routeName}");
    }

    public function toggleStatus()
    {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id']) || !isset($input['status'])) {
                throw new \Exception('Datos incompletos');
            }

            $id = (int)$input['id'];
            $status = (int)$input['status'];

            if (!in_array($status, [0, 1])) {
                throw new \Exception('Status inválido');
            }

            $model = $this->model($this->modelName);
            
            if ($model->updateStatus($id, $status)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Status actualizado correctamente'
                ]);
            } else {
                throw new \Exception('Error al actualizar el status');
            }
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener opciones para selects (AJAX)
     */
    public function getOptions()
    {
        try {
            header('Content-Type: application/json');
            
            $model = $this->model($this->modelName);
            $options = $model->getActiveForSelect();
            
            echo json_encode($options);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

}