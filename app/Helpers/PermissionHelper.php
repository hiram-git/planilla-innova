<?php

namespace App\Helpers;

use App\Middleware\PermissionMiddleware;

/**
 * Helper de permisos para vistas
 * Funciones rápidas para validar permisos en templates
 */
class PermissionHelper 
{
    /**
     * Verificar si usuario puede acceder a ruta
     */
    public static function canAccess($route, $permissionType = 'read')
    {
        return PermissionMiddleware::checkRoutePermission($route, $permissionType);
    }

    /**
     * Verificar permiso de lectura para módulo
     */
    public static function canRead($module)
    {
        return PermissionMiddleware::canRead($module);
    }

    /**
     * Verificar permiso de escritura para módulo
     */
    public static function canWrite($module) 
    {
        return PermissionMiddleware::canWrite($module);
    }

    /**
     * Verificar permiso de eliminación para módulo
     */
    public static function canDelete($module)
    {
        return PermissionMiddleware::canDelete($module);
    }

    /**
     * Generar botones CRUD con permisos
     */
    public static function crudButtons($module, $recordId, $options = [])
    {
        $buttons = '';
        
        // Botón Ver (siempre visible si tiene read)
        if (self::canRead($module)) {
            $showUrl = $options['showUrl'] ?? "/panel/{$module}/{$recordId}";
            $buttons .= '<a href="' . $showUrl . '" class="btn btn-info btn-sm" title="Ver">
                            <i class="fas fa-eye"></i>
                         </a> ';
        }

        // Botón Editar
        if (self::canWrite($module)) {
            $editUrl = $options['editUrl'] ?? "/panel/{$module}/{$recordId}/edit";
            $buttons .= '<a href="' . $editUrl . '" class="btn btn-warning btn-sm" title="Editar">
                            <i class="fas fa-edit"></i>
                         </a> ';
        }

        // Botón Eliminar
        if (self::canDelete($module)) {
            $deleteClass = $options['deleteClass'] ?? 'btn-delete';
            $buttons .= '<button type="button" class="btn btn-danger btn-sm ' . $deleteClass . '" 
                                data-id="' . $recordId . '" title="Eliminar">
                            <i class="fas fa-trash"></i>
                         </button> ';
        }

        return trim($buttons);
    }

    /**
     * Mostrar botón crear si tiene permisos
     */
    public static function createButton($module, $options = [])
    {
        if (!self::canWrite($module)) {
            return '';
        }

        $url = $options['url'] ?? "/panel/{$module}/create";
        $text = $options['text'] ?? 'Crear';
        $class = $options['class'] ?? 'btn btn-primary';
        $icon = $options['icon'] ?? 'fas fa-plus';

        return '<a href="' . $url . '" class="' . $class . '">
                    <i class="' . $icon . '"></i> ' . $text . '
                </a>';
    }

    /**
     * Renderizar enlace de menú con permisos
     */
    public static function menuLink($route, $title, $icon = '', $options = [])
    {
        if (!self::canAccess($route, 'read')) {
            return '';
        }

        $class = $options['class'] ?? 'nav-link';
        $url = $options['url'] ?? '/' . $route;
        
        $iconHtml = $icon ? '<i class="' . $icon . '"></i> ' : '';
        
        return '<a href="' . $url . '" class="' . $class . '">
                    ' . $iconHtml . $title . '
                </a>';
    }

    /**
     * Verificar si usuario es super admin
     */
    public static function isSuperAdmin()
    {
        return PermissionMiddleware::isSuperAdmin();
    }

    /**
     * ✅ REFACTORIZADO: Obtener información completa del usuario actual
     */
    public static function getCurrentUser()
    {
        return PermissionMiddleware::getCurrentUser();
    }

    /**
     * Obtener rol actual del usuario
     */
    public static function getCurrentRole()
    {
        return $_SESSION['admin_role'] ?? 'guest';
    }

    /**
     * Obtener ID de rol actual
     */
    public static function getCurrentRoleId()
    {
        return $_SESSION['admin_role_id'] ?? null;
    }

    /**
     * ✅ NUEVO: Verificar si el usuario tiene un rol específico
     */
    public static function hasRole($roleName)
    {
        return PermissionMiddleware::hasRole($roleName);
    }

    /**
     * ✅ NUEVO: Verificar si el usuario tiene uno de varios roles
     */
    public static function hasAnyRole($roles)
    {
        return PermissionMiddleware::hasAnyRole($roles);
    }

    /**
     * Renderizar contenido solo si tiene permisos
     */
    public static function ifCan($permission, $content, $module = null)
    {
        $hasPermission = false;

        if ($module) {
            switch ($permission) {
                case 'read':
                    $hasPermission = self::canRead($module);
                    break;
                case 'write':
                    $hasPermission = self::canWrite($module);
                    break;
                case 'delete':
                    $hasPermission = self::canDelete($module);
                    break;
            }
        } else {
            // Asumir que $permission es una ruta
            $hasPermission = self::canAccess($permission);
        }

        return $hasPermission ? $content : '';
    }

    /**
     * Verificar múltiples permisos (OR lógico)
     */
    public static function canAny($permissions)
    {
        foreach ($permissions as $permission) {
            if (is_array($permission)) {
                // ['module' => 'users', 'permission' => 'read']
                $module = $permission['module'];
                $perm = $permission['permission'] ?? 'read';
                
                if (self::{"can" . ucfirst($perm)}($module)) {
                    return true;
                }
            } else {
                // Ruta directa
                if (self::canAccess($permission)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Verificar todos los permisos (AND lógico)
     */
    public static function canAll($permissions)
    {
        foreach ($permissions as $permission) {
            if (is_array($permission)) {
                $module = $permission['module'];
                $perm = $permission['permission'] ?? 'read';
                
                if (!self::{"can" . ucfirst($perm)}($module)) {
                    return false;
                }
            } else {
                if (!self::canAccess($permission)) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Generar array de módulos accesibles para select/dropdown
     */
    public static function getAccessibleModules($permissionType = 'read')
    {
        $modules = [
            'dashboard' => 'Dashboard',
            'employees' => 'Empleados', 
            'positions' => 'Posiciones',
            'concepts' => 'Conceptos',
            'payrolls' => 'Planillas',
            'creditors' => 'Acreedores',
            'users' => 'Usuarios',
            'roles' => 'Roles',
            'company' => 'Configuración'
        ];

        $accessible = [];
        foreach ($modules as $key => $name) {
            if (self::{"can" . ucfirst($permissionType)}($key)) {
                $accessible[$key] = $name;
            }
        }

        return $accessible;
    }
}