<?php
/**
 * Sidebar Component - AdminLTE Multilevel Style
 * ✅ REFACTORIZADO CON ESTRUCTURA MULTILEVEL NATIVA ADMINLTE
 * ✅ PERSISTENCIA AUTOMÁTICA DE ESTADO CON data-widget="treeview"
 */

use App\Helpers\PermissionHelper;

if (!class_exists('SidebarComponent')) {
class SidebarComponent
{
    private $currentRoute;
    private $userRole;

    public function __construct()
    {
        $this->currentRoute = $this->getCurrentRoute();
        $this->userRole = $_SESSION['admin_role'] ?? 'guest';
    }

    private function getCurrentRoute()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);

        // Obtener el base path del sistema (ej: /planilla-innova)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);

        // Si el script está en el root, basePath será '/' o '.'
        if ($basePath === '/' || $basePath === '.') {
            $basePath = '';
        }

        // Remover el base path del path actual
        if (!empty($basePath) && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        return trim($path, '/');
    }

    /**
     * Verificar si es una empresa con posiciones
     */
    private function isPublicInstitution()
    {
        try {
            $companyModel = new \App\Models\Company();
            return $companyModel->isEmpresaConPosiciones();
        } catch (\Exception $e) {
            error_log("Error checking company type in sidebar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si una ruta está activa
     */
    private function isActive($route)
    {
        return strpos($this->currentRoute, $route) === 0;
    }

    /**
     * Verificar si tiene permiso
     */
    private function hasPermission($permissions)
    {
        if (empty($permissions)) {
            return true;
        }

        if (PermissionHelper::isSuperAdmin()) {
            return true;
        }

        if (is_array($permissions) && isset($permissions[0]) && is_string($permissions[0])) {
            foreach ($permissions as $permission) {
                if (PermissionHelper::canAccess($permission, 'read')) {
                    return true;
                }
            }
            return false;
        }

        if (is_array($permissions)) {
            return PermissionHelper::hasAnyRole($permissions);
        }

        if (is_string($permissions)) {
            return PermissionHelper::hasRole($permissions);
        }

        return false;
    }

    public function render()
    {
        $isPublic = $this->isPublicInstitution();

        return '
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="' . \App\Core\UrlHelper::panel('dashboard') . '" class="brand-link">
                <img src="' . url('dist/img/AdminLTELogo.png') . '" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">Innova Planilla</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a href="' . \App\Core\UrlHelper::panel('dashboard') . '" class="nav-link ' . ($this->isActive('panel/dashboard') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <!-- GESTIÓN DE PERSONAL -->
                        <li class="nav-header">GESTIÓN DE PERSONAL</li>

                        <!-- Empleados -->
                        <li class="nav-item ' . ($this->isActive('panel/employees') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/employees') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    Empleados
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::employee() . '" class="nav-link ' . ($this->isActive('panel/employees') && !$this->isActive('panel/employees/terminated') && !$this->isActive('panel/employees/create') && !$this->isActive('panel/employees/import') ? 'active' : '') . '">
                                        <i class="fas fa-list nav-icon"></i>
                                        <p>Lista de Empleados</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/employees/terminated') . '" class="nav-link ' . ($this->isActive('panel/employees/terminated') ? 'active' : '') . '">
                                        <i class="fas fa-user-times nav-icon"></i>
                                        <p>Empleados Dados de Baja</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::employee('create') . '" class="nav-link ' . ($this->isActive('panel/employees/create') ? 'active' : '') . '">
                                        <i class="fas fa-user-plus nav-icon"></i>
                                        <p>Nuevo Empleado</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::employee('import') . '" class="nav-link ' . ($this->isActive('panel/employees/import') ? 'active' : '') . '">
                                        <i class="fas fa-file-excel nav-icon"></i>
                                        <p>Importar desde Excel</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Estructura Organizacional -->
                        <li class="nav-item ' . ($this->isActive('panel/positions') || $this->isActive('panel/cargos') || $this->isActive('panel/partidas') || $this->isActive('panel/funciones') || $this->isActive('panel/organizational') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/positions') || $this->isActive('panel/cargos') || $this->isActive('panel/partidas') || $this->isActive('panel/funciones') || $this->isActive('panel/organizational') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-sitemap"></i>
                                <p>
                                    Estructura Organizacional
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">' .
                            ($isPublic ? '
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::position() . '" class="nav-link ' . ($this->isActive('panel/positions') ? 'active' : '') . '">
                                        <i class="fas fa-briefcase nav-icon"></i>
                                        <p>Posiciones</p>
                                    </a>
                                </li>' : '') . '
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::cargo() . '" class="nav-link ' . ($this->isActive('panel/cargos') ? 'active' : '') . '">
                                        <i class="fas fa-user-tie nav-icon"></i>
                                        <p>Cargos</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::partida() . '" class="nav-link ' . ($this->isActive('panel/partidas') ? 'active' : '') . '">
                                        <i class="fas fa-coins nav-icon"></i>
                                        <p>Partidas</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::funcion() . '" class="nav-link ' . ($this->isActive('panel/funciones') ? 'active' : '') . '">
                                        <i class="fas fa-tasks nav-icon"></i>
                                        <p>Funciones</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::url('panel/organizational') . '" class="nav-link ' . ($this->isActive('panel/organizational') ? 'active' : '') . '">
                                        <i class="fas fa-project-diagram nav-icon"></i>
                                        <p>Organigrama</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Horarios -->
                        <li class="nav-item">
                            <a href="' . \App\Core\UrlHelper::schedule() . '" class="nav-link ' . ($this->isActive('panel/schedules') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>Horarios</p>
                            </a>
                        </li>

                        <!-- CONTROL DE ASISTENCIA -->
                        <li class="nav-header">CONTROL DE ASISTENCIA</li>

                        <!-- Asistencia -->
                        <li class="nav-item ' . ($this->isActive('panel/attendance') || $this->isActive('timeclock') || $this->isActive('panel/attendance-api-config') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/attendance') || $this->isActive('timeclock') || $this->isActive('panel/attendance-api-config') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-clock"></i>
                                <p>
                                    Asistencia
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('/panel/attendance/devices') . '" class="nav-link ' . ($this->isActive('panel/attendance/devices') ? 'active' : '') . '">
                                        <i class="fas fa-desktop nav-icon"></i>
                                        <p>Dispositivos</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('/panel/attendance/sync') . '" class="nav-link ' . ($this->isActive('panel/attendance/sync') ? 'active' : '') . '">
                                        <i class="fas fa-sync-alt nav-icon"></i>
                                        <p>Sincronizar</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('/panel/attendance/sync-history') . '" class="nav-link ' . ($this->isActive('panel/attendance/sync-history') ? 'active' : '') . '">
                                        <i class="fas fa-history nav-icon"></i>
                                        <p>Historial Sync</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('/panel/attendance') . '" class="nav-link ' . ($this->isActive('panel/attendance') && !$this->isActive('panel/attendance/reports') && !$this->isActive('panel/attendance/sync') && !$this->isActive('panel/attendance/devices') && !$this->isActive('panel/attendance/detail') ? 'active' : '') . '">
                                        <i class="fas fa-calendar-check nav-icon"></i>
                                        <p>Marcaciones por Día</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::attendance('reports') . '" class="nav-link ' . ($this->isActive('panel/attendance/reports') ? 'active' : '') . '">
                                        <i class="fas fa-chart-bar nav-icon"></i>
                                        <p>Reportes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('/panel/attendance-api-config') . '" class="nav-link ' . ($this->isActive('panel/attendance-api-config') ? 'active' : '') . '">
                                        <i class="fas fa-plug nav-icon"></i>
                                        <p>Configuración API</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::timeclock() . '" class="nav-link ' . ($this->isActive('timeclock') ? 'active' : '') . '" target="_blank">
                                        <i class="fas fa-stopwatch nav-icon"></i>
                                        <p>Sistema de Marcaciones</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        

                        <!-- REPORTES -->
                        <li class="nav-header">REPORTES</li>

                        <!-- Reportes -->
                        <li class="nav-item ' . ($this->isActive('panel/reports') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/reports') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>
                                    Reportes
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::url('panel/reports') . '" class="nav-link ' . ($this->isActive('panel/reports') && !$this->isActive('panel/reports/exports') ? 'active' : '') . '">
                                        <i class="fas fa-file-pdf nav-icon"></i>
                                        <p>Centro de Reportes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::url('panel/reports/exports') . '" class="nav-link ' . ($this->isActive('panel/reports/exports') ? 'active' : '') . '">
                                        <i class="fas fa-download nav-icon"></i>
                                        <p>Exportar Datos</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- NÓMINA Y PLANILLAS -->
                        <li class="nav-header">NÓMINA Y PLANILLAS</li>

                        <!-- Gestión de Planillas -->
                        <li class="nav-item ' . ($this->isActive('panel/payrolls') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/payrolls') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                <p>
                                    Gestión de Planillas
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::payroll() . '" class="nav-link ' . ($this->isActive('panel/payrolls') && !$this->isActive('panel/payrolls/create') ? 'active' : '') . '">
                                        <i class="fas fa-list nav-icon"></i>
                                        <p>Planillas generadas</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::payroll('create') . '" class="nav-link ' . ($this->isActive('panel/payrolls/create') ? 'active' : '') . '">
                                        <i class="fas fa-plus-circle nav-icon"></i>
                                        <p>Nueva Planilla</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Conceptos y Fórmulas -->
                        <li class="nav-item ' . ($this->isActive('panel/concepts') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/concepts') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-calculator"></i>
                                <p>
                                    Conceptos y Fórmulas
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::concept() . '" class="nav-link ' . ($this->isActive('panel/concepts') && !$this->isActive('panel/concepts/create') ? 'active' : '') . '">
                                        <i class="fas fa-list-ul nav-icon"></i>
                                        <p>Gestionar Conceptos</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::concept('create') . '" class="nav-link ' . ($this->isActive('panel/concepts/create') ? 'active' : '') . '">
                                        <i class="fas fa-plus nav-icon"></i>
                                        <p>Nuevo Concepto</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Configuración de Conceptos -->
                        <li class="nav-item ' . ($this->isActive('panel/tipos-planilla') || $this->isActive('panel/frecuencias') || $this->isActive('panel/situaciones') || $this->isActive('panel/tipos-acumulados') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/tipos-planilla') || $this->isActive('panel/frecuencias') || $this->isActive('panel/situaciones') || $this->isActive('panel/tipos-acumulados') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>
                                    Configuración de Conceptos
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/tipos-planilla') . '" class="nav-link ' . ($this->isActive('panel/tipos-planilla') ? 'active' : '') . '">
                                        <i class="fas fa-clipboard-list nav-icon"></i>
                                        <p>Tipos de Planilla</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/frecuencias') . '" class="nav-link ' . ($this->isActive('panel/frecuencias') ? 'active' : '') . '">
                                        <i class="fas fa-calendar-check nav-icon"></i>
                                        <p>Frecuencias</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/situaciones') . '" class="nav-link ' . ($this->isActive('panel/situaciones') ? 'active' : '') . '">
                                        <i class="fas fa-user-tag nav-icon"></i>
                                        <p>Situaciones</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/tipos-acumulados') . '" class="nav-link ' . ($this->isActive('panel/tipos-acumulados') ? 'active' : '') . '">
                                        <i class="fas fa-coins nav-icon"></i>
                                        <p>Tipos de Acumulados</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Acumulados -->
                        <li class="nav-item ' . ($this->isActive('panel/acumulados') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/acumulados') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-coins"></i>
                                <p>
                                    Acumulados
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <!--<li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/acumulados') . '" class="nav-link ' . ($this->isActive('panel/acumulados') && !$this->isActive('panel/acumulados/byType') && !$this->isActive('panel/acumulados/byEmployee') && !$this->isActive('panel/acumulados/byConcepto') ? 'active' : '') . '">
                                        <i class="fas fa-tachometer-alt nav-icon"></i>
                                        <p>Dashboard Acumulados</p>
                                    </a>
                                </li>-->
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/acumulados/byEmployee') . '" class="nav-link ' . ($this->isActive('panel/acumulados/byEmployee') ? 'active' : '') . '">
                                        <i class="fas fa-user nav-icon"></i>
                                        <p>Por Empleado</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/acumulados/byConcepto') . '" class="nav-link ' . ($this->isActive('panel/acumulados/byConcepto') ? 'active' : '') . '">
                                        <i class="fas fa-tags nav-icon"></i>
                                        <p>Por Concepto</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Liquidaciones -->
                        <li class="nav-item ' . ($this->isActive('panel/liquidation') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/liquidation') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-handshake"></i>
                                <p>
                                    Liquidaciones
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/liquidation') . '" class="nav-link ' . ($this->isActive('panel/liquidation') && !$this->isActive('panel/liquidation/payrolls') ? 'active' : '') . '">
                                        <i class="fas fa-list nav-icon"></i>
                                        <p>Gestionar Liquidaciones</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/liquidation/payrolls') . '" class="nav-link ' . ($this->isActive('panel/liquidation/payrolls') ? 'active' : '') . '">
                                        <i class="fas fa-file-invoice nav-icon"></i>
                                        <p>Planillas de Liquidación</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Vacaciones -->
                        <li class="nav-item ' . ($this->isActive('panel/vacation') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/vacation') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-umbrella-beach"></i>
                                <p>
                                    Vacaciones
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/vacation') . '" class="nav-link ' . ($this->isActive('panel/vacation') && !$this->isActive('panel/vacation/calendar') && !$this->isActive('panel/vacation/reports') ? 'active' : '') . '">
                                        <i class="fas fa-list nav-icon"></i>
                                        <p>Gestión de Vacaciones</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/vacation/calendar') . '" class="nav-link ' . ($this->isActive('panel/vacation/calendar') ? 'active' : '') . '">
                                        <i class="fas fa-calendar-alt nav-icon"></i>
                                        <p>Calendario de Vacaciones</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/vacation/reports') . '" class="nav-link ' . ($this->isActive('panel/vacation/reports') ? 'active' : '') . '">
                                        <i class="fas fa-chart-bar nav-icon"></i>
                                        <p>Reportes de Vacaciones</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Acreedores y Deducciones -->
                        <li class="nav-item ' . ($this->isActive('panel/creditors') || $this->isActive('panel/deductions') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/creditors') || $this->isActive('panel/deductions') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-hand-holding-usd"></i>
                                <p>
                                    Acreedores y Deducciones
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/creditors') . '" class="nav-link ' . ($this->isActive('panel/creditors') && !$this->isActive('panel/creditors/create') ? 'active' : '') . '">
                                        <i class="fas fa-building nav-icon"></i>
                                        <p>Gestionar Acreedores</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/creditors/create') . '" class="nav-link ' . ($this->isActive('panel/creditors/create') ? 'active' : '') . '">
                                        <i class="fas fa-plus-circle nav-icon"></i>
                                        <p>Nuevo Acreedor</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/deductions') . '" class="nav-link ' . ($this->isActive('panel/deductions') && !$this->isActive('panel/deductions/create') && !$this->isActive('panel/deductions/mass-assign') ? 'active' : '') . '">
                                        <i class="fas fa-minus-circle nav-icon"></i>
                                        <p>Deducciones por Empleado</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/deductions/create') . '" class="nav-link ' . ($this->isActive('panel/deductions/create') ? 'active' : '') . '">
                                        <i class="fas fa-plus nav-icon"></i>
                                        <p>Nueva Deducción</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/deductions/mass-assign') . '" class="nav-link ' . ($this->isActive('panel/deductions/mass-assign') ? 'active' : '') . '">
                                        <i class="fas fa-users-cog nav-icon"></i>
                                        <p>Asignación Masiva</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- CONFIGURACIÓN -->
                        <li class="nav-header">CONFIGURACIÓN</li>

                        <!-- Administración -->
                        <li class="nav-item ' . ($this->isActive('panel/users') || $this->isActive('panel/roles') || $this->isActive('panel/business-calendar') ? 'menu-open' : '') . '">
                            <a href="#" class="nav-link ' . ($this->isActive('panel/users') || $this->isActive('panel/roles') || $this->isActive('panel/business-calendar') ? 'active' : '') . '">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>
                                    Administración
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::url('panel/company') . '" class="nav-link ' . ($this->isActive('panel/company') ? 'active' : '') . '">
                                        <i class="fas fa-building nav-icon"></i>
                                        <p>Configuración de Empresa</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/users') . '" class="nav-link ' . ($this->isActive('panel/users') ? 'active' : '') . '">
                                        <i class="fas fa-users-cog nav-icon"></i>
                                        <p>Usuarios</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/roles') . '" class="nav-link ' . ($this->isActive('panel/roles') ? 'active' : '') . '">
                                        <i class="fas fa-key nav-icon"></i>
                                        <p>Roles y Permisos</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="' . \App\Core\UrlHelper::route('panel/business-calendar') . '" class="nav-link ' . ($this->isActive('panel/business-calendar') ? 'active' : '') . '">
                                        <i class="fas fa-calendar-check nav-icon"></i>
                                        <p>Calendario Empresarial</p>
                                    </a>
                                </li>
                            </ul>
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

        .nav-sidebar .nav-link {
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .nav-sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .nav-sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.2) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .nav-sidebar .nav-link.disabled {
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

        /* AdminLTE Treeview animations */
        .nav-treeview {
            animation-name: fadeIn;
            animation-duration: 0.3s;
        }

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
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
?>
