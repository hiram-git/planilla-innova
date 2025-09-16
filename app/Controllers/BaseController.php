<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ActivityLogger;
use App\Middleware\PermissionMiddleware;
use App\Helpers\PermissionHelper;

/**
 * Controlador base con middleware de permisos integrado
 * Otros controladores pueden heredar de este para tener validación automática
 */
class BaseController extends Controller
{
    protected $module = ''; // Definir en cada controlador hijo
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    /**
     * Verificar permiso antes de ejecutar acción
     */
    protected function requirePermission($action = 'read')
    {
        if (empty($this->module)) {
            return; // Si no está definido el módulo, no validar
        }

        $route = "panel/{$this->module}";
        
        // Mapear acción a tipo de permiso
        $permissionType = $this->mapActionToPermission($action);
        
        PermissionMiddleware::requirePermissionWithAdminBypass($route, $permissionType);
    }

    /**
     * Mapear acción de controlador a tipo de permiso
     */
    private function mapActionToPermission($action)
    {
        $writeActions = ['create', 'store', 'edit', 'update', 'toggle', 'activate', 'deactivate'];
        $deleteActions = ['delete', 'destroy', 'remove'];

        if (in_array($action, $deleteActions)) {
            return 'delete';
        } elseif (in_array($action, $writeActions)) {
            return 'write';
        } else {
            return 'read';
        }
    }

    /**
     * Override del index para incluir validación
     */
    public function index()
    {
        $this->requirePermission('index');
        parent::index();
    }

    /**
     * Override del create para incluir validación
     */
    public function create()
    {
        $this->requirePermission('create');
        parent::create();
    }

    /**
     * Override del store para incluir validación y logging
     */
    public function store()
    {
        $this->requirePermission('store');
        
        $result = parent::store();
        
        // Log creation if successful
        if ($result !== false && !empty($this->module)) {
            $recordId = $result ?? 'unknown';
            $description = "Nuevo registro creado en módulo '{$this->module}'";
            ActivityLogger::logCreate($this->module, $recordId, $_POST, $description);
        }
        
        return $result;
    }

    /**
     * Override del edit para incluir validación
     */
    public function edit($id = null)
    {
        $this->requirePermission('edit');
        parent::edit($id);
    }

    /**
     * Override del update para incluir validación y logging
     */
    public function update($id = null)
    {
        $this->requirePermission('update');
        
        // Get old data before update for logging
        $oldData = [];
        if ($id && !empty($this->module)) {
            try {
                // Try to get old data if model exists
                $modelClass = "\\App\\Models\\" . ucfirst($this->module);
                if (class_exists($modelClass)) {
                    $model = new $modelClass();
                    if (method_exists($model, 'find')) {
                        $oldRecord = $model->find($id);
                        $oldData = $oldRecord ? (array)$oldRecord : [];
                    }
                }
            } catch (\Exception $e) {
                // Continue without old data if error
            }
        }
        
        $result = parent::update($id);
        
        // Log update if successful
        if ($result !== false && !empty($this->module)) {
            $description = "Registro actualizado en módulo '{$this->module}' con ID {$id}";
            ActivityLogger::logUpdate($this->module, $id, $oldData, $_POST, $description);
        }
        
        return $result;
    }

    /**
     * Override del delete para incluir validación y logging
     */
    public function delete($id = null)
    {
        $this->requirePermission('delete');
        
        // Get data before deletion for logging
        $deletedData = [];
        if ($id && !empty($this->module)) {
            try {
                // Try to get data before deletion if model exists
                $modelClass = "\\App\\Models\\" . ucfirst($this->module);
                if (class_exists($modelClass)) {
                    $model = new $modelClass();
                    if (method_exists($model, 'find')) {
                        $record = $model->find($id);
                        $deletedData = $record ? (array)$record : [];
                    }
                }
            } catch (\Exception $e) {
                // Continue without data if error
            }
        }
        
        $result = parent::delete($id);
        
        // Log deletion if successful
        if ($result !== false && !empty($this->module)) {
            $description = "Registro eliminado del módulo '{$this->module}' con ID {$id}";
            ActivityLogger::logDelete($this->module, $id, $deletedData, $description);
        }
        
        return $result;
    }

    /**
     * Helper: Verificar si usuario puede ejecutar acción
     */
    protected function canPerform($action)
    {
        if (empty($this->module)) {
            return true;
        }

        $permissionType = $this->mapActionToPermission($action);
        
        switch ($permissionType) {
            case 'write':
                return PermissionHelper::canWrite($this->module);
            case 'delete':
                return PermissionHelper::canDelete($this->module);
            default:
                return PermissionHelper::canRead($this->module);
        }
    }

    /**
     * Helper: Generar botones CRUD con permisos
     */
    protected function generateCrudButtons($recordId, $options = [])
    {
        if (empty($this->module)) {
            return '';
        }

        return PermissionHelper::crudButtons($this->module, $recordId, $options);
    }

    /**
     * Helper: Datos para vistas con información de permisos
     */
    protected function getViewDataWithPermissions($additionalData = [])
    {
        $permissionData = [];
        
        if (!empty($this->module)) {
            $permissionData = [
                'canRead' => PermissionHelper::canRead($this->module),
                'canWrite' => PermissionHelper::canWrite($this->module),
                'canDelete' => PermissionHelper::canDelete($this->module),
                'isSuperAdmin' => PermissionHelper::isSuperAdmin()
            ];
        }

        return array_merge($additionalData, $permissionData);
    }

    /**
     * Respuesta JSON para AJAX con validación de permisos
     */
    protected function jsonResponse($data, $action = 'read')
    {
        header('Content-Type: application/json');

        // Verificar permisos para la acción
        if (!$this->canPerform($action)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ]);
            return;
        }

        echo json_encode($data);
    }
}