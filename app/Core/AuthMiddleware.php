<?php

namespace App\Core;

use App\Models\User;

/**
 * Middleware de autenticación y autorización
 */
class AuthMiddleware
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public static function requireAuth()
    {
        if (!self::isAuthenticated()) {
            $_SESSION['error'] = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.';
            header('Location: /login');
            exit();
        }
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public static function isAuthenticated()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Obtener usuario actual
     */
    public static function getCurrentUser()
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return $_SESSION['user_data'] ?? null;
    }

    /**
     * Obtener ID del usuario actual
     */
    public static function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Obtener rol del usuario actual
     */
    public static function getCurrentUserRole()
    {
        $user = self::getCurrentUser();
        return $user['role_name'] ?? null;
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public static function hasPermission($menuId, $permissionType = 'read')
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        $permissions = $_SESSION['user_permissions'] ?? [];
        
        if (!isset($permissions[$menuId])) {
            return false;
        }

        return $permissions[$menuId][$permissionType] ?? false;
    }

    /**
     * Requerir permiso específico
     */
    public static function requirePermission($menuId, $permissionType = 'read')
    {
        // Primero verificar si está autenticado
        if (!self::isAuthenticated()) {
            $_SESSION['error'] = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.';
            header('Location: /login');
            exit();
        }

        // Luego verificar permisos
        if (!self::hasPermission($menuId, $permissionType)) {
            $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
            header('Location: /panel/dashboard');
            exit();
        }
    }

    /**
     * Verificar si es administrador
     */
    public static function isAdmin()
    {
        $role = self::getCurrentUserRole();
        return $role && (
            stripos($role, 'admin') !== false || 
            stripos($role, 'administrador') !== false
        );
    }

    /**
     * Requerir permisos de administrador
     */
    public static function requireAdmin()
    {
        if (!self::isAdmin()) {
            $_SESSION['error'] = 'Acceso denegado. Se requieren permisos de administrador';
            header('Location: /panel/dashboard');
            exit();
        }
    }

    /**
     * Iniciar sesión de usuario
     */
    public function login($username, $password)
    {
        try {
            $result = $this->userModel->authenticate($username, $password);
            
            if ($result['success']) {
                $user = $result['user'];
                
                // Guardar datos de sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_data'] = $user;
                $_SESSION['user_role_id'] = $user['role_id'];
                
                // Obtener y guardar permisos
                $permissions = $this->userModel->getUserPermissions($user['id']);
                $_SESSION['user_permissions'] = $permissions;
                $_SESSION['success'] = 'Sesión iniciada con éxito';

                // Log de acceso exitoso
                error_log("Login exitoso: Usuario {$user['username']} (ID: {$user['id']})");
                
                return ['success' => true, 'user' => $user];
            }

            // Log de intento fallido
            error_log("Intento de login fallido: $username");
            return $result;

        } catch (\Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en el sistema de autenticación'];
        }
    }

    /**
     * Cerrar sesión
     */
    public static function logout()
    {
        $userId = self::getCurrentUserId();
        $username = self::getCurrentUser()['username'] ?? 'unknown';
        
        // Limpiar sesión
        session_unset();
        session_destroy();
        
        // Log de logout
        error_log("Logout: Usuario $username (ID: $userId)");
        
        // Iniciar nueva sesión limpia
        session_start();
        
        return true;
    }

    /**
     * Actualizar permisos en sesión
     */
    public function refreshUserPermissions($userId = null)
    {
        try {
            $userId = $userId ?: self::getCurrentUserId();
            if (!$userId) {
                return false;
            }

            $permissions = $this->userModel->getUserPermissions($userId);
            $_SESSION['user_permissions'] = $permissions;
            
            return true;
        } catch (\Exception $e) {
            error_log("Error refreshing permissions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si la sesión es válida
     */
    public static function validateSession()
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        // Verificar timeout de sesión (opcional)
        $timeout = 8 * 60 * 60; // 8 horas
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > $timeout) {
            
            self::logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * Obtener permisos formateados para vista
     */
    public static function getPermissionsForView()
    {
        if (!self::isAuthenticated()) {
            return [];
        }

        $permissions = $_SESSION['user_permissions'] ?? [];
        $formatted = [];
        
        foreach ($permissions as $menuId => $perms) {
            $formatted[$menuId] = [
                'read' => $perms['read'] ?? false,
                'write' => $perms['write'] ?? false,
                'delete' => $perms['delete'] ?? false,
                'full' => ($perms['read'] ?? false) && 
                         ($perms['write'] ?? false) && 
                         ($perms['delete'] ?? false)
            ];
        }
        
        return $formatted;
    }

    /**
     * Middleware para rutas específicas
     */
    public static function checkRouteAccess($route)
    {
        self::requireAuth();

        // Mapeo de rutas a permisos
        $routePermissions = [
            '/panel/users' => [16, 'read'],
            '/panel/users/create' => [16, 'write'],
            '/panel/users/edit' => [16, 'write'],
            '/panel/users/delete' => [16, 'delete'],
            '/panel/roles' => [17, 'read'],
            '/panel/roles/create' => [17, 'write'],
            '/panel/roles/edit' => [17, 'write'],
            '/panel/roles/delete' => [17, 'delete'],
            '/panel/employees' => [8, 'read'],
            '/panel/employees/create' => [8, 'write'],
            '/panel/employees/edit' => [8, 'write'],
            '/panel/employees/delete' => [8, 'delete'],
            '/panel/payrolls' => [13, 'read'],
            '/panel/payrolls/create' => [13, 'write'],
            '/panel/payrolls/edit' => [13, 'write'],
            '/panel/payrolls/delete' => [13, 'delete'],
            '/panel/concepts' => [14, 'read'],
            '/panel/concepts/create' => [14, 'write'],
            '/panel/concepts/edit' => [14, 'write'],
            '/panel/concepts/delete' => [14, 'delete'],
        ];

        // Verificar si la ruta requiere permisos específicos
        foreach ($routePermissions as $pattern => $permission) {
            if (strpos($route, $pattern) === 0) {
                self::requirePermission($permission[0], $permission[1]);
                break;
            }
        }
    }

    /**
     * Generar menú basado en permisos
     */
    public static function getAuthorizedMenu()
    {
        if (!self::isAuthenticated()) {
            return [];
        }

        $permissions = $_SESSION['user_permissions'] ?? [];
        $menuItems = [];

        // Dashboard (siempre visible)
        $menuItems[] = [
            'name' => 'Dashboard',
            'url' => '/panel/dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'active' => true
        ];

        // Empleados
        if (isset($permissions[8]) && $permissions[8]['read']) {
            $menuItems[] = [
                'name' => 'Empleados',
                'icon' => 'fas fa-users',
                'submenu' => [
                    ['name' => 'Listado', 'url' => '/panel/employees', 'icon' => 'fas fa-list'],
                    ['name' => 'Posiciones', 'url' => '/panel/positions', 'icon' => 'fas fa-sitemap'],
                    ['name' => 'Cargos', 'url' => '/panel/cargos', 'icon' => 'fas fa-user-tie'],
                    ['name' => 'Horarios', 'url' => '/panel/schedules', 'icon' => 'fas fa-calendar-alt'],
                ]
            ];
        }

        // Planillas
        if (isset($permissions[13]) && $permissions[13]['read']) {
            $submenu = [
                ['name' => 'Planillas', 'url' => '/panel/payrolls', 'icon' => 'fas fa-file-invoice-dollar']
            ];
            
            if (isset($permissions[14]) && $permissions[14]['read']) {
                $submenu[] = ['name' => 'Conceptos', 'url' => '/panel/concepts', 'icon' => 'fas fa-calculator'];
            }
            
            $menuItems[] = [
                'name' => 'Nómina',
                'icon' => 'fas fa-money-check-alt',
                'submenu' => $submenu
            ];
        }

        // Administración (solo admins)
        if (self::isAdmin()) {
            $adminSubmenu = [];
            
            if (isset($permissions[16]) && $permissions[16]['read']) {
                $adminSubmenu[] = ['name' => 'Usuarios', 'url' => '/panel/users', 'icon' => 'fas fa-user-cog'];
            }
            
            if (isset($permissions[17]) && $permissions[17]['read']) {
                $adminSubmenu[] = ['name' => 'Roles', 'url' => '/panel/roles', 'icon' => 'fas fa-user-shield'];
            }

            if (!empty($adminSubmenu)) {
                $menuItems[] = [
                    'name' => 'Administración',
                    'icon' => 'fas fa-cogs',
                    'submenu' => $adminSubmenu
                ];
            }
        }

        return $menuItems;
    }
}