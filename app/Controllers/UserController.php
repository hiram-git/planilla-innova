<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\User;
use App\Models\Role;

/**
 * Controlador para gestión de usuarios
 */
class UserController extends Controller
{
    private $userModel;
    private $roleModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->roleModel = new Role();
    }

    /**
     * Listar usuarios
     */
    public function index()
    {
        try {
            $users = $this->userModel->getAllWithRoles();
            
            $data = [
                'title' => 'Gestión de Usuarios',
                'users' => $users,
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Usuarios', 'url' => '']
                ]
            ];

            $this->render('admin/users/index', $data);
        } catch (\Exception $e) {
            error_log("Error in UserController::index: " . $e->getMessage());
            $this->redirect('/panel/dashboard');
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        try {
            $roles = $this->roleModel->getAllActive();
            
            $data = [
                'title' => 'Crear Usuario',
                'roles' => $roles,
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Usuarios', 'url' => '/panel/users'],
                    ['name' => 'Crear', 'url' => '']
                ]
            ];

            $this->render('admin/users/create', $data);
        } catch (\Exception $e) {
            error_log("Error in UserController::create: " . $e->getMessage());
            $this->redirect('/panel/users');
        }
    }

    /**
     * Procesar creación de usuario
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/users');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/panel/users/create');
                return;
            }

            // Procesar datos
            $userData = [
                'username' => $_POST['username'] ?? '',
                'password' => $_POST['password'] ?? '',
                'firstname' => $_POST['firstname'] ?? '',
                'lastname' => $_POST['lastname'] ?? '',
                'role_id' => $_POST['role_id'] ?? '',
                'status' => isset($_POST['status']) ? 1 : 0,
                'photo' => '' // Por ahora vacío
            ];

            $result = $this->userModel->create($userData);

            if ($result['success']) {
                $_SESSION['success'] = 'Usuario creado exitosamente';
                $this->redirect('/panel/users');
            } else {
                $_SESSION['error'] = $result['message'];
                $this->redirect('/panel/users/create');
            }

        } catch (\Exception $e) {
            error_log("Error in UserController::store: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear usuario';
            $this->redirect('/panel/users/create');
        }
    }

    /**
     * Mostrar detalles de usuario
     */
    public function show($id)
    {
        try {
            $user = $this->userModel->findById($id);
            if (!$user) {
                $_SESSION['error'] = 'Usuario no encontrado';
                $this->redirect('/panel/users');
                return;
            }

            // Obtener rol y permisos
            $role = $this->roleModel->getRoleWithPermissions($user['role_id']);
            $permissions = $this->userModel->getUserPermissions($id);

            $data = [
                'title' => 'Detalles de Usuario',
                'user' => $user,
                'role' => $role,
                'permissions' => $permissions,
                'modules' => $this->roleModel->getSystemModules(),
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Usuarios', 'url' => '/panel/users'],
                    ['name' => $user['firstname'] . ' ' . $user['lastname'], 'url' => '']
                ]
            ];

            $this->render('admin/users/show', $data);
        } catch (\Exception $e) {
            error_log("Error in UserController::show: " . $e->getMessage());
            $this->redirect('/panel/users');
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        try {
            $user = $this->userModel->findById($id);
            if (!$user) {
                $_SESSION['error'] = 'Usuario no encontrado';
                $this->redirect('/panel/users');
                return;
            }

            $roles = $this->roleModel->getAllActive();
            
            $data = [
                'title' => 'Editar Usuario',
                'user' => $user,
                'roles' => $roles,
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Usuarios', 'url' => '/panel/users'],
                    ['name' => 'Editar', 'url' => '']
                ]
            ];

            $this->render('admin/users/edit', $data);
        } catch (\Exception $e) {
            error_log("Error in UserController::edit: " . $e->getMessage());
            $this->redirect('/panel/users');
        }
    }

    /**
     * Procesar actualización de usuario
     */
    public function update($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/users');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect("/panel/users/{$id}/edit");
                return;
            }

            // Procesar datos
            $userData = [
                'username' => $_POST['username'] ?? '',
                'firstname' => $_POST['firstname'] ?? '',
                'lastname' => $_POST['lastname'] ?? '',
                'role_id' => $_POST['role_id'] ?? '',
                'status' => isset($_POST['status']) ? 1 : 0
            ];

            // Agregar contraseña solo si se proporciona
            if (!empty($_POST['password'])) {
                $userData['password'] = $_POST['password'];
            }

            $result = $this->userModel->update($id, $userData);

            if ($result['success']) {
                $_SESSION['success'] = 'Usuario actualizado exitosamente';
                $this->redirect('/panel/users');
            } else {
                $_SESSION['error'] = $result['message'];
                $this->redirect("/panel/users/{$id}/edit");
            }

        } catch (\Exception $e) {
            error_log("Error in UserController::update: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar usuario';
            $this->redirect("/panel/users/{$id}/edit");
        }
    }

    /**
     * Cambiar estado de usuario (AJAX)
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

            $result = $this->userModel->changeStatus($id, $status);
            echo json_encode($result);

        } catch (\Exception $e) {
            error_log("Error in UserController::toggleStatus: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error en servidor']);
        }
    }

    /**
     * Eliminar usuario
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/users');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/panel/users');
                return;
            }

            $result = $this->userModel->delete($id);

            if ($result['success']) {
                $_SESSION['success'] = 'Usuario eliminado exitosamente';
            } else {
                $_SESSION['error'] = $result['message'];
            }

            $this->redirect('/panel/users');

        } catch (\Exception $e) {
            error_log("Error in UserController::delete: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar usuario';
            $this->redirect('/panel/users');
        }
    }

    /**
     * Resetear contraseña
     */
    public function resetPassword($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/panel/users');
                return;
            }

            // Validar CSRF token
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                $this->redirect('/panel/users');
                return;
            }

            $newPassword = $_POST['new_password'] ?? '';
            if (empty($newPassword)) {
                $_SESSION['error'] = 'La nueva contraseña es requerida';
                $this->redirect("/panel/users/{$id}/edit");
                return;
            }

            $result = $this->userModel->update($id, ['password' => $newPassword]);

            if ($result['success']) {
                $_SESSION['success'] = 'Contraseña actualizada exitosamente';
            } else {
                $_SESSION['error'] = $result['message'];
            }

            $this->redirect("/panel/users/{$id}/edit");

        } catch (\Exception $e) {
            error_log("Error in UserController::resetPassword: " . $e->getMessage());
            $_SESSION['error'] = 'Error al resetear contraseña';
            $this->redirect("/panel/users/{$id}/edit");
        }
    }

    /**
     * Obtener datos para DataTables (AJAX)
     */
    public function getData()
    {
        try {
            header('Content-Type: application/json');
            
            $users = $this->userModel->getAllWithRoles();
            
            $data = [];
            foreach ($users as $user) {
                $statusBadge = $user['status'] ? 
                    '<span class="badge badge-success">Activo</span>' : 
                    '<span class="badge badge-secondary">Inactivo</span>';

                $actions = '
                    <div class="btn-group" role="group">
                        <a href="/panel/users/' . $user['id'] . '" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/panel/users/' . $user['id'] . '/edit" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-danger btn-sm" 
                                onclick="confirmDelete(' . $user['id'] . ')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>';

                $data[] = [
                    $user['id'],
                    $user['username'],
                    $user['firstname'] . ' ' . $user['lastname'],
                    $user['role_name'] ?? 'Sin rol',
                    $user['created_on'],
                    $statusBadge,
                    $actions
                ];
            }

            echo json_encode(['data' => $data]);

        } catch (\Exception $e) {
            error_log("Error in UserController::getData: " . $e->getMessage());
            echo json_encode(['data' => []]);
        }
    }
}