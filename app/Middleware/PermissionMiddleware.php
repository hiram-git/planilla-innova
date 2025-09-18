<?php

namespace App\Middleware;

use App\Core\Database;
use PDO;

/**
 * Middleware de permisos granulares
 * Valida permisos por ruta usando BD role_permissions
 */
class PermissionMiddleware
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Verificar si el usuario tiene permiso para la ruta actual
     */
    public static function checkRoutePermission($route, $permissionType = 'read')
    {
        $middleware = new self();
        return $middleware->hasPermission($route, $permissionType);
    }

    /**
     * Middleware principal - bloquea acceso sin permisos
     */
    public static function requirePermission($route, $permissionType = 'read')
    {
        // ✅ MANEJO ESPECIAL: logout siempre permitido con mensaje apropiado
        if ($route === 'panel/logout') {
            return;
        }
        
        if (!self::checkRoutePermission($route, $permissionType)) {
            // ✅ CORREGIDO: Cerrar sesión y redirigir a login para evitar bucles
            self::logoutAndRedirect();
        }
    }

    /**
     * Cerrar sesión y redirigir a login
     */
    private static function logoutAndRedirect($message = null)
    {
        // Determinar mensaje apropiado según el tipo de error
        if ($message === null) {
            if (isset($_SESSION['auth_error_type']) && $_SESSION['auth_error_type'] === 'session_expired') {
                $message = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.';
            } else {
                $message = 'No tienes permisos para acceder a esta sección';
            }
        }
        // Si es una petición AJAX, devolver JSON en lugar de redirect
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message,
                'redirect' => url('panel/login')
            ]);
            exit();
        }
        
        // Limpiar variables de error antes de destruir sesión
        unset($_SESSION['auth_error_type']);

        // Limpiar sesión
        session_unset();
        session_destroy();

        // Iniciar nueva sesión para el mensaje de error
        session_start();
        $_SESSION['error'] = $message;
        
        // Redirigir a login
        http_response_code(403);
        header('Location: ' . url('admin'));
        exit();
    }

    /**
     * Verificar permiso específico
     */
    private function hasPermission($route, $permissionType = 'read')
    {
        // Si no hay sesión, es un problema de autenticación, no de permisos
        if (!isset($_SESSION['admin']) || !isset($_SESSION['admin_role_id'])) {
            // Marcar que es problema de sesión expirada
            $_SESSION['auth_error_type'] = 'session_expired';
            return false;
        }

        // ✅ SUPER ADMIN BYPASS: Si es super admin, permitir TODO
        if (self::isSuperAdmin()) {
            return true;
        }

        // ✅ EXCEPCIÓN: Rutas siempre accesibles si está logueado
        if ($route === 'panel/dashboard') {
            return true;
        }
        
        // ✅ EXCEPCIÓN ESPECIAL: logout - permitir pero con mensaje correcto
        if ($route === 'panel/logout') {
            return true;
        }

        $roleId = $_SESSION['admin_role_id'];
        $menuId = $this->getMenuIdForRoute($route);

        if (!$menuId) {
            // Si la ruta no está mapeada, permitir por defecto (rutas públicas)
            return true;
        }

        return $this->checkRolePermission($roleId, $menuId, $permissionType);
    }

    /**
     * Obtener menu_id para una ruta específica
     */
    private function getMenuIdForRoute($route)
    {
        try {
            // Normalizar ruta
            $route = trim($route, '/');
            
            // Buscar ruta exacta primero
            $sql = "SELECT menu_id FROM route_permissions WHERE route = ? AND permission_type = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$route, 'read']); // Usar 'read' como base
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return $result['menu_id'];
            }

            // Buscar ruta con wildcard (e.g., panel/users/*/edit)
            $sql = "SELECT menu_id FROM route_permissions WHERE ? REGEXP REPLACE(REPLACE(route, '*', '[0-9]+'), '/', '\\\\/')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$route]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return $result['menu_id'];
            }

            // Fallback: extraer módulo base de la ruta
            return $this->getMenuIdByRoutePattern($route);

        } catch (\Exception $e) {
            error_log("Error getting menu_id for route $route: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener menu_id por patrón de ruta
     */
    private function getMenuIdByRoutePattern($route)
    {
        $routeMapping = [
            'dashboard' => 1,
            'company' => 2, 
            'positions' => 3,
            'partidas' => 4,
            'organigrama' => 5,
            'cargos' => 6,
            'funciones' => 7,
            'employees' => 8,
            'overtime' => 9,
            'schedules' => 10,
            'attendance' => 11,
            'creditors' => 12,
            'deductions' => 12,
            'payrolls' => 13,
            'concepts' => 14,
            'tipos-planilla' => 15,
            'users' => 16,
            'roles' => 17,
            'acumulados' => 18,      // Módulo de acumulados
            'tipos-acumulados' => 19  // Administración de tipos de acumulados
        ];

        // Extraer el módulo base de la ruta (panel/employees/123/edit -> employees)
        if (preg_match('/^panel\/([^\/]+)/', $route, $matches)) {
            $module = $matches[1];
            return $routeMapping[$module] ?? null;
        }

        return null;
    }

    /**
     * Verificar permiso de rol en BD
     */
    private function checkRolePermission($roleId, $menuId, $permissionType)
    {
        try {
            $column = $permissionType . '_perm';
            $sql = "SELECT {$column} FROM role_permissions WHERE role_id = ? AND menu_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId, $menuId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (bool)$result[$column] : false;
        } catch (\Exception $e) {
            error_log("Error checking permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper: Verificar si usuario puede leer módulo
     */
    public static function canRead($module)
    {
        return self::checkModulePermission($module, 'read');
    }

    /**
     * Helper: Verificar si usuario puede escribir en módulo
     */
    public static function canWrite($module)
    {
        return self::checkModulePermission($module, 'write');
    }

    /**
     * Helper: Verificar si usuario puede eliminar en módulo
     */
    public static function canDelete($module)
    {
        return self::checkModulePermission($module, 'delete');
    }

    /**
     * Verificar permiso por módulo (para helpers)
     */
    private static function checkModulePermission($module, $permissionType)
    {
        $route = "panel/$module";
        return self::checkRoutePermission($route, $permissionType);
    }

    /**
     * Obtener todos los permisos de usuario actual
     */
    public static function getUserPermissions()
    {
        if (!isset($_SESSION['admin_role_id'])) {
            return [];
        }

        $middleware = new self();
        return $middleware->loadUserPermissions($_SESSION['admin_role_id']);
    }

    /**
     * Cargar permisos de usuario desde BD
     */
    private function loadUserPermissions($roleId)
    {
        try {
            $sql = "SELECT m.name, m.url, rp.read_perm, rp.write_perm, rp.delete_perm
                    FROM role_permissions rp
                    JOIN menu_items m ON rp.menu_id = m.id  
                    WHERE rp.role_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId]);
            
            $permissions = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permissions[$row['url']] = [
                    'name' => $row['name'],
                    'read' => (bool)$row['read_perm'],
                    'write' => (bool)$row['write_perm'], 
                    'delete' => (bool)$row['delete_perm']
                ];
            }
            
            return $permissions;
        } catch (\Exception $e) {
            error_log("Error loading user permissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ✅ REFACTORIZADO: Verificar si usuario es super admin usando sesión mejorada
     */
    public static function isSuperAdmin()
    {
        // Prioridad 1: Variable específica en sesión (más eficiente)
        if (isset($_SESSION['is_super_admin'])) {
            return $_SESSION['is_super_admin'];
        }
        
        // Fallback: Verificar role_id tradicional
        return isset($_SESSION['admin_role_id']) && $_SESSION['admin_role_id'] == 1;
    }

    /**
     * Bypass de permisos para super admin
     */
    public static function requirePermissionWithAdminBypass($route, $permissionType = 'read')
    {
        // ✅ MANEJO ESPECIAL: logout siempre permitido
        if ($route === 'panel/logout') {
            return true;
        }
        
        // Super admin bypass
        if (self::isSuperAdmin()) {
            return true;
        }

        // Verificar permiso normal
        if (!self::checkRoutePermission($route, $permissionType)) {
            self::logoutAndRedirect();
        }
        
        return true;
    }

    /**
     * ✅ NUEVO: Obtener información del usuario actual
     */
    public static function getCurrentUser()
    {
        return [
            'id' => $_SESSION['admin'] ?? null,
            'name' => $_SESSION['admin_name'] ?? '',
            'username' => $_SESSION['admin_username'] ?? '',
            'email' => $_SESSION['admin_email'] ?? '',
            'role' => $_SESSION['admin_role'] ?? '',
            'role_id' => $_SESSION['admin_role_id'] ?? null,
            'role_description' => $_SESSION['admin_role_description'] ?? '',
            'is_super_admin' => $_SESSION['is_super_admin'] ?? false,
            'login_time' => $_SESSION['admin_login_time'] ?? ''
        ];
    }

    /**
     * ✅ NUEVO: Verificar si el usuario actual tiene un rol específico
     */
    public static function hasRole($roleName)
    {
        return isset($_SESSION['admin_role']) && 
               strtolower($_SESSION['admin_role']) === strtolower($roleName);
    }

    /**
     * ✅ NUEVO: Verificar si el usuario actual tiene uno de varios roles
     */
    public static function hasAnyRole($roles)
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        $userRole = strtolower($_SESSION['admin_role'] ?? '');
        
        foreach ($roles as $role) {
            if ($userRole === strtolower($role)) {
                return true;
            }
        }
        
        return false;
    }
}