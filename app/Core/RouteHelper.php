<?php

namespace App\Core;

/**
 * Helper para gestión de rutas y navegación
 * Proporciona funciones para generar rutas, breadcrumbs y detectar rutas activas
 */
class RouteHelper
{
    private static $routes = [
        'panel/dashboard' => [
            'title' => 'Dashboard',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard']
            ]
        ],
        // Mantener compatibilidad con admin
        'admin/dashboard' => [
            'title' => 'Dashboard',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard']
            ]
        ],
        // Rutas con panel/
        'panel/employees' => [
            'title' => 'Empleados',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Empleados', 'url' => 'panel/employees']
            ]
        ],
        'panel/positions' => [
            'title' => 'Posiciones',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Posiciones', 'url' => 'panel/positions']
            ]
        ],
        'panel/cargos' => [
            'title' => 'Cargos',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Cargos', 'url' => 'panel/cargos']
            ]
        ],
        'panel/partidas' => [
            'title' => 'Partidas',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Partidas', 'url' => 'panel/partidas']
            ]
        ],
        'panel/funciones' => [
            'title' => 'Funciones',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Funciones', 'url' => 'panel/funciones']
            ]
        ],
        'panel/schedules' => [
            'title' => 'Horarios',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Horarios', 'url' => 'panel/schedules']
            ]
        ],
        'panel/creditors' => [
            'title' => 'Acreedores',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Acreedores', 'url' => 'panel/creditors']
            ]
        ],
        'panel/payrolls' => [
            'title' => 'Planillas',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Planillas', 'url' => 'panel/payrolls']
            ]
        ],
        'panel/concepts' => [
            'title' => 'Conceptos',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Planilla', 'url' => null],
                ['title' => 'Conceptos', 'url' => 'panel/concepts']
            ]
        ],
        // Mantener compatibilidad con admin
        'admin/employees' => [
            'title' => 'Empleados',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Empleados', 'url' => 'panel/employees']
            ]
        ],
        'admin/employees/create' => [
            'title' => 'Nuevo Empleado',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Empleados', 'url' => 'panel/employees'],
                ['title' => 'Nuevo Empleado', 'url' => null]
            ]
        ],
        'admin/employees/edit' => [
            'title' => 'Editar Empleado',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Empleados', 'url' => 'panel/employees'],
                ['title' => 'Editar Empleado', 'url' => null]
            ]
        ],
        // Mantener compatibilidad con rutas singulares
        'admin/employee' => [
            'title' => 'Empleados',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Empleados', 'url' => 'panel/employees']
            ]
        ],
        'admin/employee/create' => [
            'title' => 'Nuevo Empleado',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Empleados', 'url' => 'panel/employees'],
                ['title' => 'Nuevo Empleado', 'url' => null]
            ]
        ],
        'admin/positions' => [
            'title' => 'Posiciones',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Posiciones', 'url' => 'panel/positions']
            ]
        ],
        'admin/cargos' => [
            'title' => 'Cargos',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Cargos', 'url' => 'panel/cargos']
            ]
        ],
        'admin/partidas' => [
            'title' => 'Partidas',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Partidas', 'url' => 'panel/partidas']
            ]
        ],
        'admin/funciones' => [
            'title' => 'Funciones',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Funciones', 'url' => 'panel/funciones']
            ]
        ],
        'admin/schedules' => [
            'title' => 'Horarios',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Horarios', 'url' => 'panel/schedules']
            ]
        ],
        'admin/attendance' => [
            'title' => 'Asistencia',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Asistencia', 'url' => 'panel/attendance']
            ]
        ],
        'admin/attendance/reports' => [
            'title' => 'Reportes de Asistencia',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Asistencia', 'url' => 'panel/attendance'],
                ['title' => 'Reportes', 'url' => 'panel/attendance/reports']
            ]
        ],
        // Mantener compatibilidad con rutas singulares y viejas
        'position' => [
            'title' => 'Posiciones',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Posiciones', 'url' => 'panel/positions']
            ]
        ],
        'cargo' => [
            'title' => 'Cargos',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Cargos', 'url' => 'panel/cargos']
            ]
        ],
        'partida' => [
            'title' => 'Partidas',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Partidas', 'url' => 'panel/partidas']
            ]
        ],
        'funcion' => [
            'title' => 'Funciones',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Estructura', 'url' => null],
                ['title' => 'Funciones', 'url' => 'panel/funciones']
            ]
        ],
        'schedule' => [
            'title' => 'Horarios',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Horarios', 'url' => 'panel/schedules']
            ]
        ],
        'employee' => [
            'title' => 'Empleados',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Empleados', 'url' => 'panel/employees']
            ]
        ],
        'payroll' => [
            'title' => 'Planillas',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Planillas', 'url' => 'panel/payrolls']
            ]
        ],
        'concept' => [
            'title' => 'Conceptos',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Planilla', 'url' => null],
                ['title' => 'Conceptos', 'url' => 'panel/concepts']
            ]
        ],
        'attendance' => [
            'title' => 'Asistencia',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Asistencia', 'url' => 'panel/attendance']
            ]
        ],
        'attendance/reports' => [
            'title' => 'Reportes de Asistencia',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Asistencia', 'url' => 'panel/attendance'],
                ['title' => 'Reportes', 'url' => 'panel/attendance/reports']
            ]
        ],
        'panel/deductions' => [
            'title' => 'Deducciones',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Deducciones', 'url' => 'panel/deductions']
            ]
        ],
        'panel/deductions/create' => [
            'title' => 'Nueva Deducción',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Deducciones', 'url' => 'panel/deductions'],
                ['title' => 'Nueva Deducción', 'url' => null]
            ]
        ],
        'panel/deductions/edit' => [
            'title' => 'Editar Deducción',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Deducciones', 'url' => 'panel/deductions'],
                ['title' => 'Editar Deducción', 'url' => null]
            ]
        ],
        'timeclock' => [
            'title' => 'Sistema de Marcaciones',
            'breadcrumbs' => [
                ['title' => 'Marcaciones', 'url' => 'timeclock']
            ]
        ],
        'panel/acumulados' => [
            'title' => 'Acumulados',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Acumulados', 'url' => 'panel/acumulados']
            ]
        ],
        'panel/acumulados/allEmployees' => [
            'title' => 'Acumulados por Empleados',
            'breadcrumbs' => [
                ['title' => 'Inicio', 'url' => 'panel/dashboard'],
                ['title' => 'Acumulados', 'url' => 'panel/acumulados'],
                ['title' => 'Por Empleados', 'url' => 'panel/acumulados/allEmployees']
            ]
        ]
    ];

    /**
     * Obtiene la ruta actual basada en la URL
     */
    public static function getCurrentRoute()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);
        $segments = array_filter(explode('/', $path));
        
        // Remover el directorio base si existe
        $baseDir = basename(dirname(dirname(__DIR__)));
        if (!empty($segments) && $segments[array_key_first($segments)] === $baseDir) {
            array_shift($segments);
        }
        
        return implode('/', $segments);
    }

    /**
     * Verifica si una ruta está activa
     */
    public static function isRouteActive($route)
    {
        $currentRoute = self::getCurrentRoute();
        return strpos($currentRoute, $route) === 0;
    }

    /**
     * Genera breadcrumbs para la ruta actual
     */
    public static function getBreadcrumbs($route = null)
    {
        if ($route === null) {
            $route = self::getCurrentRoute();
        }

        // Coincidencia exacta
        if (isset(self::$routes[$route])) {
            return self::$routes[$route]['breadcrumbs'];
        }

        // Manejo de rutas dinámicas (ej: panel/deductions/17/edit)
        $routeParts = explode('/', $route);
        
        // Para rutas de edición con ID dinámico
        if (count($routeParts) >= 4 && $routeParts[3] === 'edit') {
            $baseRoute = $routeParts[0] . '/' . $routeParts[1] . '/edit';
            if (isset(self::$routes[$baseRoute])) {
                return self::$routes[$baseRoute]['breadcrumbs'];
            }
        }
        
        // Para rutas de creación
        if (count($routeParts) >= 3 && $routeParts[2] === 'create') {
            $baseRoute = $routeParts[0] . '/' . $routeParts[1] . '/create';
            if (isset(self::$routes[$baseRoute])) {
                return self::$routes[$baseRoute]['breadcrumbs'];
            }
        }
        
        // Para rutas base (ej: panel/deductions)
        if (count($routeParts) >= 2) {
            $baseRoute = $routeParts[0] . '/' . $routeParts[1];
            if (isset(self::$routes[$baseRoute])) {
                return self::$routes[$baseRoute]['breadcrumbs'];
            }
        }

        // Breadcrumb por defecto
        return [
            ['title' => 'Inicio', 'url' => 'panel/dashboard']
        ];
    }

    /**
     * Renderiza los breadcrumbs como HTML
     */
    public static function renderBreadcrumbs($route = null)
    {
        $breadcrumbs = self::getBreadcrumbs($route);
        $html = '';

        foreach ($breadcrumbs as $index => $breadcrumb) {
            $isLast = ($index === count($breadcrumbs) - 1);
            
            if ($isLast || $breadcrumb['url'] === null) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">' . 
                         htmlspecialchars($breadcrumb['title']) . '</li>';
            } else {
                // Usar UrlHelper para generar la URL correcta
                $url = \App\Core\UrlHelper::route($breadcrumb['url']);
                $html .= '<li class="breadcrumb-item">' .
                         '<a href="' . htmlspecialchars($url) . '">' .
                         htmlspecialchars($breadcrumb['title']) . '</a></li>';
            }
        }

        return $html;
    }

    /**
     * Obtiene el título de página para la ruta actual
     */
    public static function getPageTitle($route = null)
    {
        if ($route === null) {
            $route = self::getCurrentRoute();
        }

        if (isset(self::$routes[$route])) {
            return self::$routes[$route]['title'];
        }

        // Título por defecto basado en la ruta
        $segments = explode('/', $route);
        return ucfirst(end($segments));
    }

    /**
     * Genera URL relativa basada en la ruta
     */
    public static function url($route)
    {
        // Usar UrlHelper para generar URLs consistentes
        return \App\Core\UrlHelper::route($route);
    }

    /**
     * Verifica si el usuario tiene permisos para una ruta
     */
    public static function hasPermission($route, $userRole = null)
    {
        if ($userRole === null) {
            $userRole = $_SESSION['admin_role'] ?? 'admin';
        }

        // Configuración de permisos por ruta
        $permissions = [
            'panel/dashboard' => ['admin', 'manager', 'operator'],
            'panel/employees' => ['admin', 'manager'],
            'panel/positions' => ['admin'],
            'panel/cargos' => ['admin'],
            'panel/partidas' => ['admin'],
            'panel/funciones' => ['admin'],
            'panel/tipos-planilla' => ['admin'],
            'panel/frecuencias' => ['admin'],
            'panel/situaciones' => ['admin'],
            'panel/schedules' => ['admin', 'manager'],
            'panel/attendance' => ['admin', 'manager', 'operator'],
            // Módulo de Acumulados
            'panel/acumulados' => ['admin', 'manager', 'operator'],
            'panel/acumulados/index' => ['admin', 'manager', 'operator'],
            'panel/acumulados/byEmployee' => ['admin', 'manager', 'operator'],
            'panel/acumulados/byType' => ['admin', 'manager', 'operator'],
            'panel/acumulados/allEmployees' => ['admin', 'manager', 'operator'],
            'panel/acumulados/export' => ['admin', 'manager'],
            // Administración de Tipos de Acumulados (solo admin)
            'panel/tipos-acumulados' => ['admin'],
            'panel/tipos-acumulados/create' => ['admin'],
            'panel/tipos-acumulados/edit' => ['admin'],
            'panel/tipos-acumulados/delete' => ['admin'],
            // Mantener compatibilidad con rutas singulares
            'panel/employee' => ['admin', 'manager'],
            'employee' => ['admin', 'manager'],
            'position' => ['admin'],
            'cargo' => ['admin'],
            'partida' => ['admin'],
            'funcion' => ['admin'],
            'schedule' => ['admin', 'manager'],
            'attendance' => ['admin', 'manager', 'operator'],
            'timeclock' => ['admin', 'manager', 'operator']
        ];

        if (isset($permissions[$route])) {
            return in_array($userRole, $permissions[$route]);
        }

        // Por defecto, solo admin tiene acceso
        return $userRole === 'admin';
    }

    /**
     * Registra una nueva ruta
     */
    public static function registerRoute($route, $config)
    {
        self::$routes[$route] = $config;
    }

    /**
     * Obtiene todas las rutas registradas
     */
    public static function getAllRoutes()
    {
        return self::$routes;
    }
}