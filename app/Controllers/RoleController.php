<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Role;

/**
 * Controlador para gestión de roles y permisos
 */
class RoleController extends Controller
{
    private $roleModel;

    public function __construct()
    {
        parent::__construct();
        $this->roleModel = new Role();
    }

    /**
     * Listar roles
     */
    public function index()
    {
        try {
            $roles = $this->roleModel->getRolesWithUserCount();
            
            $data = [
                'title' => 'Gestión de Roles',
                'roles' => $roles,
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Roles', 'url' => '']
                ]
            ];

            $this->render('admin/roles/index', $data);
        } catch (\Exception $e) {
            error_log("Error in RoleController::index: " . $e->getMessage());
            $this->redirect('/panel/dashboard');
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        try {
            $modules = $this->roleModel->getSystemModules();
            
            $data = [
                'title' => 'Crear Rol',
                'modules' => $modules,
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Roles', 'url' => '/panel/roles'],
                    ['name' => 'Crear', 'url' => '']
                ]
            ];

            $this->render('admin/roles/create', $data);
        } catch (\Exception $e) {
            error_log("Error in RoleController::create: " . $e->getMessage());
            $this->redirect('/panel/roles');
        }
    }

    /**
     * Procesar creación de rol
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/roles');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/panel/roles/create');
                return;
            }

            // Procesar permisos
            $permissions = [];
            if (isset($_POST['permissions'])) {
                foreach ($_POST['permissions'] as $menuId => $perms) {
                    $permissions[$menuId] = [
                        'read' => isset($perms['read']),
                        'write' => isset($perms['write']),
                        'delete' => isset($perms['delete'])
                    ];
                }
            }

            $roleData = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'status' => isset($_POST['status']) ? 1 : 0,
                'permissions' => $permissions
            ];

            $result = $this->roleModel->create($roleData);

            if ($result['success']) {
                $_SESSION['success'] = 'Rol creado exitosamente';
                $this->redirect('/panel/roles');
            } else {
                $_SESSION['error'] = $result['message'];
                $this->redirect('/panel/roles/create');
            }

        } catch (\Exception $e) {
            error_log("Error in RoleController::store: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear rol';
            $this->redirect('/panel/roles/create');
        }
    }

    /**
     * Mostrar detalles de rol
     */
    public function show($id)
    {
        try {
            $role = $this->roleModel->getRoleWithPermissions($id);
            if (!$role) {
                $_SESSION['error'] = 'Rol no encontrado';
                $this->redirect('/panel/roles');
                return;
            }

            $modules = $this->roleModel->getSystemModules();

            $data = [
                'title' => 'Detalles de Rol',
                'role' => $role,
                'modules' => $modules,
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Roles', 'url' => '/panel/roles'],
                    ['name' => $role['name'], 'url' => '']
                ]
            ];

            $this->render('admin/roles/show', $data);
        } catch (\Exception $e) {
            error_log("Error in RoleController::show: " . $e->getMessage());
            $this->redirect('/panel/roles');
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        try {
            $role = $this->roleModel->getRoleWithPermissions($id);
            if (!$role) {
                $_SESSION['error'] = 'Rol no encontrado';
                $this->redirect('/panel/roles');
                return;
            }

            $modules = $this->roleModel->getSystemModules();
            
            $data = [
                'title' => 'Editar Rol',
                'role' => $role,
                'modules' => $modules,
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Roles', 'url' => '/panel/roles'],
                    ['name' => 'Editar', 'url' => '']
                ]
            ];

            $this->render('admin/roles/edit', $data);
        } catch (\Exception $e) {
            error_log("Error in RoleController::edit: " . $e->getMessage());
            $this->redirect('/panel/roles');
        }
    }

    /**
     * Procesar actualización de rol
     */
    public function update($id)
    {
        try {
            error_log("RoleController::update called with ID: $id");
            error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
            error_log("POST data: " . json_encode($_POST));
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log("Invalid request method, redirecting");
                $this->redirect('/panel/roles');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                error_log("Invalid CSRF token");
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect("/panel/roles/{$id}/edit");
                return;
            }

            // Procesar permisos
            $permissions = [];
            if (isset($_POST['permissions'])) {
                foreach ($_POST['permissions'] as $menuId => $perms) {
                    $permissions[$menuId] = [
                        'read' => isset($perms['read']),
                        'write' => isset($perms['write']),
                        'delete' => isset($perms['delete'])
                    ];
                }
            }

            $roleData = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'status' => isset($_POST['status']) ? 1 : 0,
                'permissions' => $permissions
            ];

            $result = $this->roleModel->update($id, $roleData);

            if ($result['success']) {
                $_SESSION['success'] = 'Rol actualizado exitosamente';
                $this->redirect('/panel/roles');
            } else {
                $_SESSION['error'] = $result['message'];
                $this->redirect("/panel/roles/{$id}/edit");
            }

        } catch (\Exception $e) {
            error_log("Error in RoleController::update: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar rol';
            $this->redirect("/panel/roles/{$id}/edit");
        }
    }

    /**
     * Cambiar estado de rol (AJAX)
     */
    public function toggleStatus()
    {
        try {
            header('Content-Type: application/json');

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $status = $input['status'] ?? null;

            if (!$id || $status === null) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                return;
            }

            $result = $this->roleModel->changeStatus($id, $status);
            echo json_encode($result);

        } catch (\Exception $e) {
            error_log("Error in RoleController::toggleStatus: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error en servidor']);
        }
    }

    /**
     * Eliminar rol
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/roles');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/panel/roles');
                return;
            }

            $result = $this->roleModel->delete($id);

            if ($result['success']) {
                $_SESSION['success'] = 'Rol eliminado exitosamente';
            } else {
                $_SESSION['error'] = $result['message'];
            }

            $this->redirect('/panel/roles');

        } catch (\Exception $e) {
            error_log("Error in RoleController::delete: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar rol';
            $this->redirect('/panel/roles');
        }
    }

    /**
     * Clonar rol
     */
    public function clone($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/roles');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/panel/roles');
                return;
            }

            $newName = $_POST['new_name'] ?? '';
            $newDescription = $_POST['new_description'] ?? '';

            if (empty($newName)) {
                $_SESSION['error'] = 'El nombre del nuevo rol es requerido';
                $this->redirect('/panel/roles');
                return;
            }

            $result = $this->roleModel->cloneRole($id, $newName, $newDescription);

            if ($result['success']) {
                $_SESSION['success'] = 'Rol clonado exitosamente';
            } else {
                $_SESSION['error'] = $result['message'];
            }

            $this->redirect('/panel/roles');

        } catch (\Exception $e) {
            error_log("Error in RoleController::clone: " . $e->getMessage());
            $_SESSION['error'] = 'Error al clonar rol';
            $this->redirect('/panel/roles');
        }
    }

    /**
     * Obtener datos para DataTables (AJAX)
     */
    public function getData()
    {
        try {
            header('Content-Type: application/json');
            
            $roles = $this->roleModel->getRolesWithUserCount();
            
            $data = [];
            foreach ($roles as $role) {
                $statusBadge = $role['status'] ? 
                    '<span class="badge badge-success">Activo</span>' : 
                    '<span class="badge badge-secondary">Inactivo</span>';

                $usersBadge = $role['user_count'] > 0 ? 
                    '<span class="badge badge-info">' . $role['active_user_count'] . '/' . $role['user_count'] . ' usuarios</span>' :
                    '<span class="badge badge-light">Sin usuarios</span>';

                $actions = '
                    <div class="btn-group" role="group">
                        <a href="/panel/roles/' . $role['id'] . '" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/panel/roles/' . $role['id'] . '/edit" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-secondary btn-sm" 
                                onclick="confirmClone(' . $role['id'] . ', \'' . htmlspecialchars($role['name']) . '\')">
                            <i class="fas fa-copy"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" 
                                onclick="confirmDelete(' . $role['id'] . ')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>';

                $data[] = [
                    $role['id'],
                    $role['name'],
                    $role['description'] ?? '',
                    $usersBadge,
                    $role['created_at'],
                    $statusBadge,
                    $actions
                ];
            }

            echo json_encode(['data' => $data]);

        } catch (\Exception $e) {
            error_log("Error in RoleController::getData: " . $e->getMessage());
            echo json_encode(['data' => []]);
        }
    }

    /**
     * Importar/Exportar configuración de permisos
     */
    public function exportConfig($id)
    {
        try {
            $role = $this->roleModel->getRoleWithPermissions($id);
            if (!$role) {
                $_SESSION['error'] = 'Rol no encontrado';
                $this->redirect('/panel/roles');
                return;
            }

            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="role-' . $role['name'] . '-permissions.json"');
            
            $config = [
                'role' => [
                    'name' => $role['name'],
                    'description' => $role['description']
                ],
                'permissions' => $role['permissions'],
                'exported_at' => date('Y-m-d H:i:s')
            ];

            echo json_encode($config, JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            error_log("Error in RoleController::exportConfig: " . $e->getMessage());
            $_SESSION['error'] = 'Error al exportar configuración';
            $this->redirect('/panel/roles');
        }
    }
}