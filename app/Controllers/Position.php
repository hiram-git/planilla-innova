<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Middleware\AuthMiddleware;

class Position extends Controller
{
    public function __construct()
    {
        // AuthMiddleware se maneja a nivel de routing
    }

    public function index()
    {
        $position = $this->model('Posicion');
        
        $data = [
            'title' => 'Gestión de Posiciones',
            'page_title' => 'Posiciones',
            'positions' => $position->getAllWithRelations(),
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->render('admin/positions/index', $data);
    }

    public function create()
    {
        $cargo = $this->model('Cargo');
        $partida = $this->model('Partida');
        $funcion = $this->model('Funcion');
        $position = $this->model('Posicion');

        $data = [
            'title' => 'Agregar Posición',
            'page_title' => 'Agregar Posición',
            'cargos' => $cargo->getActive(),
            'partidas' => $partida->getActive(),
            'funciones' => $funcion->getActive(),
            'csrf_token' => AuthMiddleware::generateCSRF(),
            'suggested_code' => $position->getNextCode()
        ];

        $this->view('admin/positions/create', $data);
    }

    public function store()
    {
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Position::store() - Not POST method");
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Método no permitido']);
            }
            $this->redirect(\App\Core\UrlHelper::position());
        }

        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);
        $position = $this->model('Posicion');

        $errors = $position->validatePositionData($data);

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => implode(', ', array_values($errors))]);
            }
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect(\App\Core\UrlHelper::position('create'));
        }

        try {
            $position->create([
                'codigo' => $data['codigo'],
                'id_partida' => $data['partida'],
                'id_cargo' => $data['cargo'],
                'id_funcion' => $data['funcion'],
                'sueldo' => $data['sueldo']
            ]);
            
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Posición agregada exitosamente']);
            }
            $_SESSION['success'] = 'Posición agregada exitosamente';
            $this->redirect(\App\Core\UrlHelper::position());
        } catch (\Exception $e) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Error al agregar posición: ' . $e->getMessage()]);
            }
            $_SESSION['error'] = 'Error al agregar posición';
            $this->redirect(\App\Core\UrlHelper::position('create'));
        }
    }

    public function edit($id)
    {
        $position = $this->model('Posicion');
        $cargo = $this->model('Cargo');
        $partida = $this->model('Partida');
        $funcion = $this->model('Funcion');

        $positionData = $position->getPositionWithRelations($id);
        if (!$positionData) {
            $_SESSION['error'] = 'Posición no encontrada';
            $this->redirect(\App\Core\UrlHelper::position());
        }

        $data = [
            'title' => 'Editar Posición',
            'page_title' => 'Editar Posición',
            'position' => $positionData,
            'cargos' => $cargo->getActive(),
            'partidas' => $partida->getActive(),
            'funciones' => $funcion->getActive(),
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/positions/edit', $data);
    }

    public function update($id)
    {
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Método no permitido']);
            }
            $this->redirect(\App\Core\UrlHelper::position());
        }

        // CSRF validation with proper error handling
        try {
            if (!isset($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Token de seguridad requerido';
                $this->redirect(\App\Core\UrlHelper::position("edit/$id"));
                return;
            }
            
            if (!isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect(\App\Core\UrlHelper::position("edit/$id"));
                return;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error de validación de seguridad';
            $this->redirect(\App\Core\UrlHelper::position("edit/$id"));
            return;
        }

        $data = Security::sanitizeInput($_POST);
        $position = $this->model('Posicion');

        $positionData = $position->getPositionWithRelations($id);
        if (!$positionData) {
            $_SESSION['error'] = 'Posición no encontrada';
            $this->redirect(\App\Core\UrlHelper::position());
        }

        $errors = $position->validatePositionData($data);

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => implode(', ', array_values($errors))]);
            }
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect(\App\Core\UrlHelper::position("edit/$id"));
        }

        try {
            $updateData = [
                'codigo' => $data['edit_codigo'],
                'id_partida' => $data['edit_partida'],
                'id_cargo' => $data['edit_cargo'],
                'id_funcion' => $data['edit_funcion'],
                'sueldo' => $data['edit_sueldo']
            ];
            
            $position->update($id, $updateData);
            
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Posición actualizada exitosamente']);
            }
            $_SESSION['success'] = 'Posición actualizada exitosamente';
            $this->redirect(\App\Core\UrlHelper::position());
        } catch (\Exception $e) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar posición: ' . $e->getMessage()]);
            }
            $_SESSION['error'] = 'Error al actualizar posición: ' . $e->getMessage();
            $this->redirect(\App\Core\UrlHelper::position("edit/$id"));
        }
    }

    public function delete($id)
    {
        AuthMiddleware::requireAuth();
        
        $position = $this->model('Posicion');
        
        try {
            // Verificar si la posición está en uso
            if ($position->hasEmployees($id)) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'message' => 'No se puede eliminar la posición porque tiene empleados asignados']);
                }
                $_SESSION['error'] = 'No se puede eliminar la posición porque tiene empleados asignados';
            } else {
                $position->delete($id);
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => true, 'message' => 'Posición eliminada exitosamente']);
                }
                $_SESSION['success'] = 'Posición eliminada exitosamente';
            }
        } catch (\Exception $e) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar posición']);
            }
            $_SESSION['error'] = 'Error al eliminar posición';
        }

        $this->redirect(\App\Core\UrlHelper::position());
    }

    // API endpoints para AJAX
    public function getNextCode()
    {
        // Allow CORS for testing
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        try {
            $position = $this->model('Posicion');
            $nextCode = $position->getNextCode();
            $this->json(['code' => $nextCode]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Error al generar código'], 500);
        }
    }

    public function getRow()
    {
        // Allow CORS for testing
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        $id = $_POST['id'] ?? '';
        error_log("getRow called with id: " . $id);
        if (empty($id)) {
            $this->json(['error' => 'ID requerido'], 400);
        }

        $position = $this->model('Posicion');
        $positionData = $position->getPositionWithRelations($id);

        if ($positionData) {
            $this->json($positionData);
        } else {
            $this->json(['error' => 'Posición no encontrada'], 404);
        }
    }

    public function getOptions()
    {
        // Allow CORS for testing
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        $type = $_POST['type'] ?? '';
        $data = [];
        
        // Debug logging
        error_log("getOptions called with type: " . $type);

        switch ($type) {
            case 'partida':
                $partida = $this->model('Partida');
                $results = $partida->getActive();
                foreach ($results as $row) {
                    $data[] = ['id' => $row['id'], 'descripcion' => $row['nombre'] ?? $row['descripcion'] ?? 'Sin descripción'];
                }
                break;
                
            case 'cargo':
                $cargo = $this->model('Cargo');
                $results = $cargo->getActive();
                foreach ($results as $row) {
                    $data[] = ['id' => $row['id'], 'descripcion' => $row['nombre'] ?? $row['descripcion'] ?? 'Sin descripción'];
                }
                break;
                
            case 'funcion':
                $funcion = $this->model('Funcion');
                $results = $funcion->getActive();
                foreach ($results as $row) {
                    $data[] = ['id' => $row['id'], 'descripcion' => $row['nombre'] ?? $row['descripcion'] ?? 'Sin descripción'];
                }
                break;
                
            default:
                error_log("getOptions: Invalid type provided: " . $type);
                $this->json(['error' => 'Tipo no válido'], 400);
        }

        error_log("getOptions: Returning " . count($data) . " active items for type: " . $type);
        $this->json($data);
    }

    private function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }
}