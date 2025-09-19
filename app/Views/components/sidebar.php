<?php
/**
 * Sidebar Component
 * Componente reutilizable para la barra lateral de navegación
 * ✅ INTEGRADO CON SISTEMA DE PERMISOS GRANULARES
 */

use App\Helpers\PermissionHelper;

if (!class_exists('SidebarComponent')) {
class SidebarComponent 
{
    private $menuItems;
    private $currentRoute;
    private $userRole;
    
    public function __construct() 
    {
        $this->currentRoute = $this->getCurrentRoute();
        $this->userRole = $_SESSION['admin_role'] ?? 'guest';
        $this->initializeMenuItems();
    }
    
    /**
     * Verificar si es una institución pública
     */
    private function isPublicInstitution() 
    {
        try {
            $companyModel = new \App\Models\Company();
            return $companyModel->isEmpresaPublica();
        } catch (\Exception $e) {
            error_log("Error checking company type in sidebar: " . $e->getMessage());
            return false; // Default to private (don't show structures)
        }
    }
    
    private function getCurrentRoute() 
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);
        return trim($path, '/');
    }
    
    private function initializeMenuItems() 
    {
        $this->menuItems = [
            [
                'type' => 'single',
                'title' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => \App\Core\UrlHelper::panel('dashboard'),
                'route' => 'panel/dashboard',
                'badge' => null,
                'permissions' => ['panel/dashboard']
            ],
            [
                'type' => 'divider',
                'title' => 'GESTIÓN DE PERSONAL'
            ],
            [
                'type' => 'dropdown',
                'title' => 'Empleados',
                'icon' => 'fas fa-users',
                'route' => 'panel/employees',
                'permissions' => ['panel/employees'],
                'children' => [
                    [
                        'title' => 'Lista de Empleados',
                        'icon' => 'fas fa-list',
                        'url' => \App\Core\UrlHelper::employee(),
                        'route' => 'panel/employees'
                    ],
                    [
                        'title' => 'Nuevo Empleado',
                        'icon' => 'fas fa-user-plus',
                        'url' => \App\Core\UrlHelper::employee('create'),
                        'route' => 'panel/employees/create'
                    ]
                ]
            ]
        ];
        
        // Agregar módulo de Estructura Organizacional con lógica condicional
        $structureChildren = [];
        
        // Para empresa pública: agregar Posiciones
        if ($this->isPublicInstitution()) {
            $structureChildren[] = [
                'title' => 'Posiciones',
                'icon' => 'fas fa-briefcase',
                'url' => \App\Core\UrlHelper::position(),
                'route' => 'panel/positions'
            ];
        }
        
        // Siempre agregar Cargos, Partidas y Funciones (tanto pública como privada)
        $structureChildren[] = [
            'title' => 'Cargos',
            'icon' => 'fas fa-user-tie',
            'url' => \App\Core\UrlHelper::cargo(),
            'route' => 'panel/cargos'
        ];
        $structureChildren[] = [
            'title' => 'Partidas',
            'icon' => 'fas fa-coins',
            'url' => \App\Core\UrlHelper::partida(),
            'route' => 'panel/partidas'
        ];
        $structureChildren[] = [
            'title' => 'Funciones',
            'icon' => 'fas fa-tasks',
            'url' => \App\Core\UrlHelper::funcion(),
            'route' => 'panel/funciones'
        ];
        
        // Agregar el módulo de organigrama jerárquico
        $structureChildren[] = [
            'title' => 'Organigrama',
            'icon' => 'fas fa-project-diagram',
            'url' => \App\Core\UrlHelper::url('panel/organizational'),
            'route' => 'panel/organizational'
        ];
        
        // Agregar el módulo completo
        $this->menuItems[] = [
            'type' => 'dropdown',
            'title' => 'Estructura Organizacional', 
            'icon' => 'fas fa-sitemap',
            'route' => 'structure',
            'permissions' => [],
            'children' => $structureChildren
        ];
        
        // Continuar con otros elementos del menú
        $additionalMenuItems = [
            [
                'type' => 'single',
                'title' => 'Horarios',
                'icon' => 'fas fa-calendar-alt',
                'url' => \App\Core\UrlHelper::schedule(),
                'route' => 'panel/schedules',
                'permissions' => ['panel/schedules']
            ],
            [
                'type' => 'divider',
                'title' => 'CONTROL DE ASISTENCIA'
            ],
            [
                'type' => 'dropdown',
                'title' => 'Asistencia',
                'icon' => 'fas fa-clock',
                'route' => 'panel/attendance',
                'permissions' => ['panel/attendance'],
                'children' => [
                    [
                        'title' => 'Registros de Asistencia',
                        'icon' => 'fas fa-list-ul',
                        'url' => \App\Core\UrlHelper::attendance(),
                        'route' => 'panel/attendance'
                    ],
                    [
                        'title' => 'Reportes',
                        'icon' => 'fas fa-chart-bar',
                        'url' => \App\Core\UrlHelper::attendance('reports'),
                        'route' => 'panel/attendance/reports'
                    ],
                    [
                        'title' => 'Sistema de Marcaciones',
                        'icon' => 'fas fa-stopwatch',
                        'url' => \App\Core\UrlHelper::timeclock(),
                        'route' => 'timeclock',
                        'target' => '_blank'
                    ]
                ]
            ],
            [
                'type' => 'divider',
                'title' => 'NÓMINA Y PLANILLAS'
            ],
            [
                'type' => 'dropdown',
                'title' => 'Gestión de Planillas',
                'icon' => 'fas fa-file-invoice-dollar',
                'route' => 'panel/payrolls',
                'permissions' => ['panel/payrolls'],
                'children' => [
                    [
                        'title' => 'Lista de Planillas',
                        'icon' => 'fas fa-list',
                        'url' => \App\Core\UrlHelper::payroll(),
                        'route' => 'panel/payrolls'
                    ],
                    [
                        'title' => 'Nueva Planilla',
                        'icon' => 'fas fa-plus-circle',
                        'url' => \App\Core\UrlHelper::payroll('create'),
                        'route' => 'panel/payrolls/create'
                    ],
                    [
                        'type' => 'divider'
                    ],
                    [
                        'title' => 'Procesar Planillas',
                        'icon' => 'fas fa-play-circle',
                        'url' => \App\Core\UrlHelper::payroll(),
                        'route' => 'panel/payrolls',
                        'description' => 'Procesamiento de nómina'
                    ],
                    [
                        'title' => 'Reportes de Nómina',
                        'icon' => 'fas fa-chart-line',
                        'url' => \App\Core\UrlHelper::payroll('reports'),
                        'route' => 'panel/payrolls/reports'
                    ]
                ]
            ],
            [
                'type' => 'dropdown',
                'title' => 'Conceptos y Fórmulas',
                'icon' => 'fas fa-calculator',
                'route' => 'panel/concepts',
                'permissions' => ['panel/concepts'],
                'children' => [
                    [
                        'title' => 'Gestionar Conceptos',
                        'icon' => 'fas fa-list-ul',
                        'url' => \App\Core\UrlHelper::concept(),
                        'route' => 'panel/concepts'
                    ],
                    [
                        'title' => 'Nuevo Concepto',
                        'icon' => 'fas fa-plus',
                        'url' => \App\Core\UrlHelper::concept('create'),
                        'route' => 'panel/concepts/create'
                    ],
                    [
                        'type' => 'divider'
                    ],
                    [
                        'title' => 'Editor de Fórmulas',
                        'icon' => 'fas fa-code',
                        'url' => \App\Core\UrlHelper::concept('create'),
                        'route' => 'panel/concepts/create',
                        'description' => 'Crear fórmulas de cálculo'
                    ],
                    [
                        'title' => 'Probar Conceptos',
                        'icon' => 'fas fa-flask',
                        'url' => \App\Core\UrlHelper::concept(),
                        'route' => 'panel/concepts',
                        'description' => 'Validar fórmulas'
                    ]
                ]
            ],
            [
                'type' => 'dropdown',
                'title' => 'Configuración de Conceptos',
                'icon' => 'fas fa-cogs',
                'route' => 'config',
                'permissions' => [],
                'children' => [
                    [
                        'title' => 'Tipos de Planilla',
                        'icon' => 'fas fa-clipboard-list',
                        'url' => \App\Core\UrlHelper::route('panel/tipos-planilla'),
                        'route' => 'panel/tipos-planilla'
                    ],
                    [
                        'title' => 'Frecuencias',
                        'icon' => 'fas fa-calendar-check',
                        'url' => \App\Core\UrlHelper::route('panel/frecuencias'),
                        'route' => 'panel/frecuencias'
                    ],
                    [
                        'title' => 'Situaciones',
                        'icon' => 'fas fa-user-tag',
                        'url' => \App\Core\UrlHelper::route('panel/situaciones'),
                        'route' => 'panel/situaciones'
                    ],
                    [
                        'type' => 'divider'
                    ],
                    [
                        'title' => 'Tipos de Acumulados',
                        'icon' => 'fas fa-piggy-bank',
                        'url' => \App\Core\UrlHelper::route('panel/tipos-acumulados'),
                        'route' => 'panel/tipos-acumulados',
                        'description' => 'Aguinaldo, Bono 14, Vacaciones'
                    ]
                ]
            ],
            [
                'type' => 'dropdown',
                'title' => 'Acumulados',
                'icon' => 'fas fa-coins',
                'route' => 'panel/acumulados',
                'permissions' => ['panel/acumulados'],
                'children' => [
                    [
                        'title' => 'Dashboard Acumulados',
                        'icon' => 'fas fa-tachometer-alt',
                        'url' => \App\Core\UrlHelper::route('panel/acumulados'),
                        'route' => 'panel/acumulados',
                        'description' => 'Vista general de acumulados'
                    ],
                    [
                        'type' => 'divider'
                    ],
                    [
                        'title' => 'Por Empleado',
                        'icon' => 'fas fa-user',
                        'url' => \App\Core\UrlHelper::route('panel/acumulados/byEmployee'),
                        'route' => 'panel/acumulados/byEmployee',
                        'description' => 'Acumulados por empleado específico'
                    ],
                    [
                        'title' => 'Por Tipo de Acumulado',
                        'icon' => 'fas fa-tags',
                        'url' => \App\Core\UrlHelper::route('panel/acumulados/byType'),
                        'route' => 'panel/acumulados/byType',
                        'description' => 'XIII Mes, Prima de Antigüedad, etc.'
                    ],
                    [
                        'title' => 'Por Planilla',
                        'icon' => 'fas fa-file-invoice',
                        'url' => \App\Core\UrlHelper::route('panel/payrolls'),
                        'route' => 'panel/payrolls',
                        'description' => 'Acumulados específicos por planilla'
                    ],
                    [
                        'type' => 'divider'
                    ],
                    [
                        'title' => 'XIII Mes',
                        'icon' => 'fas fa-gift',
                        'url' => \App\Core\UrlHelper::route('panel/acumulados/byType?tipo=XIII_MES'),
                        'route' => 'panel/acumulados/byType',
                        'description' => 'Décimo Tercer Mes (Aguinaldo)'
                    ],
                    [
                        'title' => 'Prima de Antigüedad',
                        'icon' => 'fas fa-award',
                        'url' => \App\Core\UrlHelper::route('panel/acumulados/byType?tipo=PRIMA_ANTIGUEDAD'),
                        'route' => 'panel/acumulados/byType',
                        'description' => 'Prima por años de servicio'
                    ],
                    [
                        'title' => 'Vacaciones',
                        'icon' => 'fas fa-umbrella-beach',
                        'url' => \App\Core\UrlHelper::route('panel/acumulados/byType?tipo=VACACIONES'),
                        'route' => 'panel/acumulados/byType',
                        'description' => 'Acumulado de vacaciones'
                    ],
                    [
                        'type' => 'divider'
                    ],
                    [
                        'title' => 'Exportar Datos',
                        'icon' => 'fas fa-download',
                        'url' => \App\Core\UrlHelper::route('panel/acumulados/export'),
                        'route' => 'panel/acumulados/export',
                        'target' => '_blank',
                        'description' => 'Descargar reportes en CSV/Excel'
                    ]
                ]
            ],
            [
                'type' => 'dropdown',
                'title' => 'Acreedores y Deducciones',
                'icon' => 'fas fa-hand-holding-usd',
                'route' => 'panel/creditors', 
                'permissions' => ['panel/creditors'],
                'children' => [
                    [
                        'title' => 'Gestionar Acreedores',
                        'icon' => 'fas fa-building',
                        'url' => \App\Core\UrlHelper::route('panel/creditors'),
                        'route' => 'panel/creditors'
                    ],
                    [
                        'title' => 'Nuevo Acreedor',
                        'icon' => 'fas fa-plus-circle',
                        'url' => \App\Core\UrlHelper::route('panel/creditors/create'),
                        'route' => 'panel/creditors/create'
                    ],
                    [
                        'type' => 'divider'
                    ],
                    [
                        'title' => 'Deducciones por Empleado',
                        'icon' => 'fas fa-minus-circle',
                        'url' => \App\Core\UrlHelper::route('panel/deductions'),
                        'route' => 'panel/deductions'
                    ],
                    [
                        'title' => 'Nueva Deducción',
                        'icon' => 'fas fa-plus',
                        'url' => \App\Core\UrlHelper::route('panel/deductions/create'),
                        'route' => 'panel/deductions/create'
                    ],
                    [
                        'title' => 'Asignación Masiva',
                        'icon' => 'fas fa-users-cog',
                        'url' => \App\Core\UrlHelper::route('panel/deductions/mass-assign'),
                        'route' => 'panel/deductions/mass-assign',
                        'description' => 'Asignar deducciones múltiples'
                    ]
                ]
            ],
            [
                'type' => 'divider',
                'title' => 'CONFIGURACIÓN'
            ],
            [
                'type' => 'dropdown',
                'title' => 'Administración',
                'icon' => 'fas fa-cog',
                'route' => 'admin',
                'permissions' => [],
                'children' => [
                    [
                        'title' => 'Usuarios',
                        'icon' => 'fas fa-users-cog',
                        'url' => \App\Core\UrlHelper::route('panel/users'),
                        'route' => 'panel/users'
                    ],
                    [
                        'title' => 'Roles y Permisos',
                        'icon' => 'fas fa-key',
                        'url' => \App\Core\UrlHelper::route('panel/roles'),
                        'route' => 'panel/roles'
                    ]
                ]
            ],
            [
                'type' => 'divider',
                'title' => 'REPORTES'
            ],
            [
                'type' => 'dropdown',
                'title' => 'Reportes',
                'icon' => 'fas fa-chart-line',
                'route' => 'panel/reports',
                'permissions' => ['panel/reports'],
                'children' => [
                    [
                        'title' => 'Centro de Reportes',
                        'icon' => 'fas fa-file-pdf',
                        'url' => \App\Core\UrlHelper::url('panel/reports'),
                        'route' => 'panel/reports'
                    ],
                    [
                        'title' => 'Exportar Datos',
                        'icon' => 'fas fa-download',
                        'url' => \App\Core\UrlHelper::url('panel/reports/exports'),
                        'route' => 'panel/reports/exports'
                    ]
                ]
            ],
            [
                'type' => 'divider',
                'title' => 'CONFIGURACIÓN'
            ],
            [
                'type' => 'single',
                'title' => 'Configuración de Empresa',
                'icon' => 'fas fa-building',
                'url' => \App\Core\UrlHelper::url('panel/company'),
                'route' => 'panel/company',
                'permissions' => ['panel/company']
            ]
        ];
        
        // Unir los elementos adicionales al menú principal
        $this->menuItems = array_merge($this->menuItems, $additionalMenuItems);
    }
    
    private function hasPermission($permissions) 
    {
        // ✅ REFACTORIZADO: Sistema de permisos más flexible y claro
        
        // 1. Si no hay permisos definidos, mostrar siempre (elementos públicos)
        if (empty($permissions)) {
            return true;
        }
        
        // 2. Super admin ve TODO sin restricciones
        if (PermissionHelper::isSuperAdmin()) {
            return true;
        }
        
        // 3. Si permissions es array con rutas, verificar acceso granular por BD
        if (is_array($permissions) && isset($permissions[0]) && is_string($permissions[0])) {
            foreach ($permissions as $permission) {
                if (PermissionHelper::canAccess($permission, 'read')) {
                    return true;
                }
            }
            return false;
        }
        
        // 4. Verificación por roles específicos (backward compatibility)
        if (is_array($permissions)) {
            return PermissionHelper::hasAnyRole($permissions);
        }
        
        // 5. Verificación simple por string
        if (is_string($permissions)) {
            return PermissionHelper::hasRole($permissions);
        }
        
        return false;
    }
    
    private function isActive($route) 
    {
        return strpos($this->currentRoute, $route) === 0;
    }
    
    private function renderMenuItem($item) 
    {
        // ✅ SUPER ADMIN: Ve todo sin restricciones
        if (PermissionHelper::isSuperAdmin()) {
            // No verificar permisos, proceder directamente
        } elseif (isset($item['permissions']) && !$this->hasPermission($item['permissions'])) {
            return '';
        }
        
        $html = '';
        
        switch ($item['type']) {
            case 'divider':
                $html = '<li class="nav-header">' . strtoupper($item['title']) . '</li>';
                break;
                
            case 'single':
                $active = isset($item['route']) && $this->isActive($item['route']) ? 'active' : '';
                $disabled = isset($item['disabled']) && $item['disabled'] ? 'disabled' : '';
                $target = isset($item['target']) ? 'target="' . $item['target'] . '"' : '';
                
                $badge = '';
                if (isset($item['badge'])) {
                    $badge = '<span class="right badge ' . $item['badge']['class'] . '">' . $item['badge']['text'] . '</span>';
                }
                
                $html = '
                <li class="nav-item">
                    <a href="' . ($item['url'] ?? '#') . '" class="nav-link ' . $active . ' ' . $disabled . '" ' . $target . '>
                        <i class="nav-icon ' . $item['icon'] . '"></i>
                        <p>' . $item['title'] . $badge . '</p>
                    </a>
                </li>';
                break;
                
            case 'dropdown':
                $active = isset($item['route']) && $this->isActive($item['route']) ? 'menu-open' : '';
                $activeLink = $active ? 'active' : '';
                
                $badge = '';
                if (isset($item['badge'])) {
                    $badge = '<span class="right badge ' . $item['badge']['class'] . '">' . $item['badge']['text'] . '</span>';
                }
                
                $html = '
                <li class="nav-item has-treeview ' . $active . '">
                    <a href="#" class="nav-link ' . $activeLink . '">
                        <i class="nav-icon ' . $item['icon'] . '"></i>
                        <p>
                            ' . $item['title'] . '
                            <i class="right fas fa-angle-left"></i>
                            ' . $badge . '
                        </p>
                    </a>
                    <ul class="nav nav-treeview">';
                
                foreach ($item['children'] as $child) {
                    // Manejar dividers en dropdowns
                    if (isset($child['type']) && $child['type'] === 'divider') {
                        $html .= '<li class="nav-item"><hr class="dropdown-divider"></li>';
                        continue;
                    }
                    
                    // Verificar que el child tenga title antes de procesarlo
                    if (!isset($child['title'])) {
                        continue;
                    }
                    
                    // ✅ REFACTORIZADO: Lógica simplificada para elementos hijos
                    
                    // Super admin ve todo - no necesita validaciones
                    if (PermissionHelper::isSuperAdmin()) {
                        // Continuar sin validaciones
                    } 
                    // Validar permisos específicos del elemento hijo
                    elseif (isset($child['permissions']) && !$this->hasPermission($child['permissions'])) {
                        continue;
                    }
                    // Validar acceso por ruta (solo para usuarios no-admin)
                    elseif (isset($child['route']) && !PermissionHelper::canAccess($child['route'], 'read')) {
                        continue;
                    }
                    
                    $childActive = isset($child['route']) && $this->isActive($child['route']) ? 'active' : '';
                    $childDisabled = isset($child['disabled']) && $child['disabled'] ? 'disabled' : '';
                    $childTarget = isset($child['target']) ? 'target="' . $child['target'] . '"' : '';
                    
                    $html .= '
                        <li class="nav-item">
                            <a href="' . ($child['url'] ?? '#') . '" class="nav-link ' . $childActive . ' ' . $childDisabled . '" ' . $childTarget . '>
                                <i class="' . ($child['icon'] ?? 'fas fa-circle') . ' nav-icon"></i>
                                <p>' . $child['title'] . '</p>
                            </a>
                        </li>';
                }
                
                $html .= '
                    </ul>
                </li>';
                break;
        }
        
        return $html;
    }
    
    public function render() 
    {
        $menuHtml = '';
        foreach ($this->menuItems as $item) {
            $menuHtml .= $this->renderMenuItem($item);
        }
        
        return '
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="' . \App\Core\UrlHelper::panel('dashboard') . '" class="brand-link">
                <img src="' . url('dist/img/AdminLTELogo.png') . '" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">Innova Planilla</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- ✅ REFACTORIZADO: Panel de usuario con información de rol 
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="' . url('dist/img/avatar.png') . '" class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="#" class="d-block">' . htmlspecialchars($_SESSION['admin_name'] ?? 'Usuario') . '</a>
                        <small class="text-muted d-block">
                            <i class="fas fa-user-tag"></i> ' . htmlspecialchars($_SESSION['admin_role'] ?? 'Sin rol') . '
                        </small>
                        <small class="text-muted">
                            <i class="fas fa-circle text-success"></i> En línea
                        </small>
                    </div>
                </div>-->

                <!-- Sidebar Menu -->
                <nav class="mt-0">
                    <ul class="nav nav-pills nav-sidebar flex-column nav-compact" data-widget="treeview" role="menu" data-accordion="false">
                        ' . $menuHtml . '
                        
                        <!-- System Info -->
                        <li class="nav-header">INFORMACIÓN DEL SISTEMA</li>
                        <li class="nav-item">
                            <a href="#" class="nav-link disabled">
                                <i class="nav-icon fas fa-info-circle"></i>
                                <p>
                                    <?php
                                    use App\Helpers\VersionHelper;
                                    ?>
                                    Versión <?= VersionHelper::getCurrentVersion() ?>
                                    <span class="right badge badge-success">MVC</span>
                                </p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>';
    }
    
    public function getStyles() 
    {
        return '
        <style>
        .sidebar {
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.2) transparent;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255,255,255,0.2);
            border-radius: 3px;
        }
        
        .nav-compact .nav-item {
            margin-bottom: 2px;
        }
        
        .nav-compact .nav-link {
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .nav-compact .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateX(3px);
        }
        
        .nav-compact .nav-link.active {
            background-color: rgba(255,255,255,0.2) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .nav-compact .nav-link.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .nav-header {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            color: rgba(255,255,255,0.6);
        }
        
        .brand-link {
            transition: background-color 0.3s ease;
        }
        
        .brand-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .user-panel .info a:hover {
            color: #fff !important;
        }
        
        @media (max-width: 768px) {
            .nav-header {
                font-size: 0.6rem;
            }
        }
        </style>';
    }
}
} // End of if (!class_exists('SidebarComponent'))

// Crear el componente para que esté disponible en el layout
$sidebar = new SidebarComponent();
// El layout se encargará de renderizar usando $sidebarHtml
?>