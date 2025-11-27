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
            // Redirigir a página de acceso con información del módulo
            self::logoutAndRedirect(null, $route);
        }
    }

    /**
     * Manejar acceso denegado - redirigir a página de acceso o login
     */
    private static function logoutAndRedirect($message = null, $route = null)
    {
        // Verificar si es problema de sesión expirada
        $isSessionExpired = isset($_SESSION['auth_error_type']) && $_SESSION['auth_error_type'] === 'session_expired';

        // Determinar mensaje apropiado según el tipo de error
        if ($message === null) {
            if ($isSessionExpired) {
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
                'redirect' => $isSessionExpired ? url('panel/login') : url('panel/access')
            ]);
            exit();
        }

        // Si la sesión expiró, cerrar sesión y redirigir a login
        if ($isSessionExpired) {
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

        // Si es problema de permisos (no sesión), mantener sesión y redirigir a página de acceso
        // Guardar información del módulo solicitado
        if ($route) {
            $_SESSION['access_denied_module'] = $route;
            // Intentar obtener nombre legible del módulo
            $_SESSION['access_denied_module_name'] = ucfirst(str_replace('-', ' ', $route));
        }

        $_SESSION['warning'] = $message;

        // Redirigir a página de acceso
        http_response_code(403);
        header('Location: ' . url('panel/access'));
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
        if ($route === 'panel/dashboard' || $route === 'panel/access') {
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
            // Normalizar ruta: remover 'panel/' al inicio si existe
            $route = trim($route, '/');
            $route = str_replace('panel/', '', $route);

            // Buscar directamente en menu_items por URL
            $sql = "SELECT id FROM menu_items WHERE url = ? AND status = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$route]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return $result['id'];
            }

            // Si no se encuentra, retornar null
            return null;
        } catch (\Exception $e) {
            error_log("Error getting menu ID for route: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar permiso de rol para un menú
     */
    private function checkRolePermission($roleId, $menuId, $permissionType = 'read')
    {
        try {
            // Mapear tipo de permiso a columna
            $column = $permissionType . '_perm';

            $sql = "SELECT $column FROM role_permissions WHERE role_id = ? AND menu_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId, $menuId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? (bool)$result[$column] : false;
        } catch (\Exception $e) {
            error_log("Error checking role permission: " . $e->getMessage());
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
            self::logoutAndRedirect(null, $route);
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

    // ============================================================================
    // ACTION-LEVEL PERMISSIONS (Granular Permissions System)
    // ============================================================================

    /**
     * ✅ NUEVO: Verificar si usuario puede ejecutar una acción específica
     *
     * @param string $actionCode Código de acción (ej: 'payroll.generate', 'payroll.vacation.approve')
     * @return bool true si tiene permiso, false si no
     *
     * Ejemplos de uso:
     * - PermissionMiddleware::canDoAction('payroll.generate')
     * - PermissionMiddleware::canDoAction('payroll.vacation.approve')
     * - PermissionMiddleware::canDoAction('employee.edit_salary')
     */
    public static function canDoAction($actionCode)
    {
        // Super admin bypass
        if (self::isSuperAdmin()) {
            return true;
        }

        // Si no hay sesión, denegar
        if (!isset($_SESSION['admin_role_id'])) {
            return false;
        }

        $middleware = new self();
        return $middleware->checkActionPermission($_SESSION['admin_role_id'], $actionCode);
    }

    /**
     * ✅ NUEVO: Requerir permiso de acción (bloquea si no tiene permiso)
     *
     * @param string $actionCode Código de acción requerida
     * @param string $customMessage Mensaje personalizado de error (opcional)
     * @throws void Redirige a login si no tiene permiso
     *
     * Ejemplo de uso en controlador:
     * ```php
     * PermissionMiddleware::requireAction('payroll.generate', 'No tienes permiso para generar planillas');
     * ```
     */
    public static function requireAction($actionCode, $customMessage = null)
    {
        if (!self::canDoAction($actionCode)) {
            $message = $customMessage ?? "No tienes permiso para ejecutar esta acción: {$actionCode}";
            self::logoutAndRedirect($message, $actionCode);
        }
    }

    /**
     * ✅ NUEVO: Verificar si usuario puede ejecutar alguna de varias acciones
     *
     * @param array $actionCodes Array de códigos de acción
     * @return bool true si tiene permiso para AL MENOS UNA acción
     *
     * Ejemplo de uso:
     * ```php
     * if (PermissionMiddleware::canDoAnyAction(['payroll.generate', 'payroll.regenerate'])) {
     *     // Mostrar botón de generar planilla
     * }
     * ```
     */
    public static function canDoAnyAction($actionCodes)
    {
        if (!is_array($actionCodes)) {
            $actionCodes = [$actionCodes];
        }

        foreach ($actionCodes as $action) {
            if (self::canDoAction($action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ✅ NUEVO: Verificar si usuario puede ejecutar TODAS las acciones especificadas
     *
     * @param array $actionCodes Array de códigos de acción
     * @return bool true si tiene permiso para TODAS las acciones
     *
     * Ejemplo de uso:
     * ```php
     * if (PermissionMiddleware::canDoAllActions(['payroll.generate', 'payroll.approve'])) {
     *     // Permitir flujo completo
     * }
     * ```
     */
    public static function canDoAllActions($actionCodes)
    {
        if (!is_array($actionCodes)) {
            $actionCodes = [$actionCodes];
        }

        foreach ($actionCodes as $action) {
            if (!self::canDoAction($action)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verificar permiso de acción en BD
     *
     * @param int $roleId ID del rol
     * @param string $actionCode Código de la acción
     * @return bool
     */
    private function checkActionPermission($roleId, $actionCode)
    {
        try {
            // Verificar en tabla role_actions
            $sql = "SELECT allowed
                    FROM role_actions
                    WHERE role_id = ? AND action_code = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId, $actionCode]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return (bool)$result['allowed'];
            }

            // Si no existe registro explícito, denegar por defecto
            // (Seguridad: whitelist approach)
            return false;

        } catch (\Exception $e) {
            error_log("Error checking action permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ NUEVO: Obtener todas las acciones permitidas para el usuario actual
     *
     * @param string|null $module Filtrar por módulo específico (opcional)
     * @return array Array de códigos de acción permitidos
     *
     * Ejemplo de uso:
     * ```php
     * $actions = PermissionMiddleware::getAllowedActions();
     * $payrollActions = PermissionMiddleware::getAllowedActions('payrolls');
     * ```
     */
    public static function getAllowedActions($module = null)
    {
        if (!isset($_SESSION['admin_role_id'])) {
            return [];
        }

        // Super admin tiene todas las acciones
        if (self::isSuperAdmin()) {
            $middleware = new self();
            return $middleware->getAllSystemActions($module);
        }

        $middleware = new self();
        return $middleware->loadAllowedActions($_SESSION['admin_role_id'], $module);
    }

    /**
     * Cargar acciones permitidas desde BD
     *
     * @param int $roleId
     * @param string|null $module
     * @return array
     */
    private function loadAllowedActions($roleId, $module = null)
    {
        try {
            $sql = "SELECT ra.action_code, ad.name, ad.module, ad.category
                    FROM role_actions ra
                    JOIN action_definitions ad ON ra.action_code = ad.action_code
                    WHERE ra.role_id = ? AND ra.allowed = 1 AND ad.status = 1";

            $params = [$roleId];

            if ($module) {
                $sql .= " AND ad.module = ?";
                $params[] = $module;
            }

            $sql .= " ORDER BY ad.module, ad.category, ad.name";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            error_log("Error loading allowed actions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todas las acciones del sistema (para super admin)
     *
     * @param string|null $module
     * @return array
     */
    private function getAllSystemActions($module = null)
    {
        try {
            $sql = "SELECT action_code, name, module, category
                    FROM action_definitions
                    WHERE status = 1";

            $params = [];

            if ($module) {
                $sql .= " AND module = ?";
                $params[] = $module;
            }

            $sql .= " ORDER BY module, category, name";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            error_log("Error loading system actions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ✅ NUEVO: Obtener acciones agrupadas por módulo
     *
     * @return array Array asociativo [module => [actions]]
     *
     * Ejemplo de uso:
     * ```php
     * $groupedActions = PermissionMiddleware::getActionsByModule();
     * foreach ($groupedActions as $module => $actions) {
     *     echo "$module: " . count($actions) . " acciones\n";
     * }
     * ```
     */
    public static function getActionsByModule()
    {
        $actions = self::getAllowedActions();
        $grouped = [];

        foreach ($actions as $action) {
            $module = $action['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $action;
        }

        return $grouped;
    }

    /**
     * ✅ NUEVO: Verificar permisos múltiples de manera eficiente
     *
     * @param array $actionCodes Array de códigos de acción a verificar
     * @return array Array asociativo [action_code => bool]
     *
     * Útil para verificar múltiples permisos de una sola vez (1 query)
     *
     * Ejemplo de uso:
     * ```php
     * $permissions = PermissionMiddleware::checkMultipleActions([
     *     'payroll.generate',
     *     'payroll.approve',
     *     'payroll.export_pdf'
     * ]);
     *
     * if ($permissions['payroll.generate']) {
     *     // Mostrar botón generar
     * }
     * ```
     */
    public static function checkMultipleActions($actionCodes)
    {
        // Super admin tiene todas
        if (self::isSuperAdmin()) {
            $result = [];
            foreach ($actionCodes as $code) {
                $result[$code] = true;
            }
            return $result;
        }

        if (!isset($_SESSION['admin_role_id'])) {
            $result = [];
            foreach ($actionCodes as $code) {
                $result[$code] = false;
            }
            return $result;
        }

        $middleware = new self();
        return $middleware->batchCheckActions($_SESSION['admin_role_id'], $actionCodes);
    }

    /**
     * Verificar múltiples acciones en una sola query
     *
     * @param int $roleId
     * @param array $actionCodes
     * @return array
     */
    private function batchCheckActions($roleId, $actionCodes)
    {
        try {
            // Inicializar resultado (por defecto todo false)
            $result = [];
            foreach ($actionCodes as $code) {
                $result[$code] = false;
            }

            if (empty($actionCodes)) {
                return $result;
            }

            // Preparar placeholders para IN clause
            $placeholders = str_repeat('?,', count($actionCodes) - 1) . '?';

            $sql = "SELECT action_code, allowed
                    FROM role_actions
                    WHERE role_id = ? AND action_code IN ($placeholders)";

            $params = array_merge([$roleId], $actionCodes);

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[$row['action_code']] = (bool)$row['allowed'];
            }

            return $result;

        } catch (\Exception $e) {
            error_log("Error batch checking actions: " . $e->getMessage());
            // En caso de error, devolver todo false (seguro)
            $result = [];
            foreach ($actionCodes as $code) {
                $result[$code] = false;
            }
            return $result;
        }
    }
}