<?php
/**
 * Navbar Component
 * Componente reutilizable para la barra de navegación principal
 */

use App\Core\UrlHelper;

if (!class_exists('NavbarComponent')) {
class NavbarComponent 
{
    private $currentUser;
    private $userRole;
    private $avatarUrl;
    
    public function __construct() 
    {
        // Inicializar datos del usuario desde la sesión
        $this->initUserData();
    }
    
    private function initUserData()
    {
        // Obtener datos del usuario de la sesión
        $this->currentUser = $_SESSION['admin_name'] ?? 'Administrador';
        $this->userRole = $_SESSION['admin_role'] ?? 'Administrador del Sistema';
        
        // Generar URL del avatar usando UrlHelper para consistencia
        $this->avatarUrl = UrlHelper::asset('dist/img/avatar.png');
    }
    
    public function getUserInfo()
    {
        return [
            'name' => $this->currentUser,
            'role' => $this->userRole,
            'username' => $_SESSION['admin_username'] ?? '',
            'email' => $_SESSION['admin_email'] ?? '',
            'login_time' => $_SESSION['admin_login_time'] ?? '',
            'avatar_url' => $this->avatarUrl
        ];
    }
    
    public function render() 
    {
        return '
        <nav class="main-header navbar navbar-expand navbar-white navbar-light elevation-1">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button" title="Alternar menú">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="' . \App\Core\UrlHelper::panel('dashboard') . '" class="nav-link">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="' . \App\Core\UrlHelper::timeclock() . '" class="nav-link" target="_blank">
                        <i class="fas fa-clock"></i> Marcaciones
                    </a>
                </li>
            </ul>

            <!-- Center navbar -->
            <div class="navbar-nav mx-auto">
                <div class="nav-item">
                    <span class="navbar-text">
                        <i class="fas fa-calendar-alt"></i>
                        <span id="currentDateTime">' . date('d/m/Y H:i:s') . '</span>
                    </span>
                </div>
            </div>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- Payroll Type Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#" title="Tipo de Planilla">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span class="d-none d-md-inline ml-1">Planilla: </span>
                        <span id="selectedPayrollType" class="badge badge-primary">Seleccionar</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item-text">Seleccionar Tipo de Planilla</span>
                        <div class="dropdown-divider"></div>
                        <div id="payrollTypeOptions">
                            <!-- Opciones cargadas dinámicamente -->
                        </div>
                    </div>
                </li>

                <!-- Quick Actions Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#" title="Acciones rápidas">
                        <i class="fas fa-plus-circle"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item-text">Acciones Rápidas</span>
                        <div class="dropdown-divider"></div>
                        <a href="' . \App\Core\UrlHelper::employee('create') . '" class="dropdown-item">
                            <i class="fas fa-user-plus mr-2"></i> Nuevo Empleado
                        </a>
                        <a href="' . \App\Core\UrlHelper::position('create') . '" class="dropdown-item">
                            <i class="fas fa-briefcase mr-2"></i> Nueva Posición
                        </a>
                        <a href="' . \App\Core\UrlHelper::schedule('create') . '" class="dropdown-item">
                            <i class="fas fa-calendar-plus mr-2"></i> Nuevo Horario
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="' . \App\Core\UrlHelper::attendance() . '" class="dropdown-item">
                            <i class="fas fa-chart-line mr-2"></i> Ver Asistencia
                        </a>
                    </div>
                </li>

                <!-- User Dropdown -->
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                        <img src="' . $this->avatarUrl . '" class="user-image img-circle elevation-2" alt="User Image">
                        <span class="d-none d-md-inline">' . htmlspecialchars($this->currentUser) . '</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <!-- User image -->
                        <li class="user-header bg-primary">
                            <img src="' . $this->avatarUrl . '" class="img-circle elevation-2" alt="User Image">
                            <p>
                                ' . htmlspecialchars($this->currentUser) . '
                                <small>' . htmlspecialchars($this->userRole) . '</small>
                            </p>
                        </li>
                        
                        <!-- Menu Body
                        <li class="user-body">
                            <div class="row">
                                <div class="col-4 text-center">
                                    <a href="' . \App\Core\UrlHelper::employee() . '">Empleados</a>
                                </div>
                                <div class="col-4 text-center">
                                    <a href="' . \App\Core\UrlHelper::attendance() . '">Asistencia</a>
                                </div>
                                <div class="col-4 text-center">
                                    <a href="' . \App\Core\UrlHelper::position() . '">Estructura</a>
                                </div>
                            </div>
                        </li> -->
                        
                        <!-- Menu Footer-->
                        <li class="user-footer">
                            <a href="#" class="btn btn-default btn-flat">Perfil</a>
                            <a href="' . \App\Core\UrlHelper::panel('logout') . '" class="btn btn-default btn-flat float-right">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>';
    }
    
    public function getScripts() 
    {
        return '
        <script>
        // Actualizar fecha y hora en tiempo real
        function updateDateTime() {
            const now = new Date();
            const options = { 
                year: "numeric", 
                month: "2-digit", 
                day: "2-digit",
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
                hour12: false
            };
            document.getElementById("currentDateTime").textContent = now.toLocaleDateString("es-ES", options);
        }
        
        // Actualizar cada segundo
        setInterval(updateDateTime, 1000);
        updateDateTime();
        
        // Cargar tipos de planilla al inicializar
        loadPayrollTypes();
        
        // Función para cargar tipos de planilla
        function loadPayrollTypes() {
            fetch("' . \App\Core\UrlHelper::url('api/payroll-types') . '")
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById("payrollTypeOptions");
                    if (data.success && data.data.length > 0) {
                        container.innerHTML = data.data.map(type => 
                            `<a href="#" class="dropdown-item payroll-type-option" data-id="${type.id}" data-name="${type.descripcion}">
                                <i class="fas fa-file-invoice mr-2"></i> ${type.descripcion}
                            </a>`
                        ).join("");
                        
                        // Agregar event listeners a las opciones
                        document.querySelectorAll(".payroll-type-option").forEach(option => {
                            option.addEventListener("click", function(e) {
                                e.preventDefault();
                                selectPayrollType(this.dataset.id, this.dataset.name);
                            });
                        });
                        
                        // Seleccionar el primero por defecto si no hay selección
                        if (!sessionStorage.getItem("selectedPayrollType")) {
                            selectPayrollType(data.data[0].id, data.data[0].descripcion);
                        } else {
                            // Restaurar selección previa
                            const stored = JSON.parse(sessionStorage.getItem("selectedPayrollType"));
                            document.getElementById("selectedPayrollType").textContent = stored.name;
                        }
                    } else {
                        container.innerHTML = "<span class=\"dropdown-item-text text-muted\">No hay tipos disponibles</span>";
                    }
                })
                .catch(error => {
                    console.error("Error cargando tipos de planilla:", error);
                    document.getElementById("payrollTypeOptions").innerHTML = 
                        "<span class=\"dropdown-item-text text-danger\">Error cargando tipos</span>";
                });
        }
        
        // Función para seleccionar tipo de planilla
        function selectPayrollType(id, name) {
            // Actualizar UI
            document.getElementById("selectedPayrollType").textContent = name;
            
            // Guardar en sessionStorage para persistencia
            sessionStorage.setItem("selectedPayrollType", JSON.stringify({id: id, name: name}));
            
            // Disparar evento personalizado para notificar a otras partes de la aplicación
            window.dispatchEvent(new CustomEvent("payrollTypeChanged", { 
                detail: { id: id, name: name } 
            }));
        }
        
        // Función global para obtener el tipo de planilla seleccionado
        window.getSelectedPayrollType = function() {
            const stored = sessionStorage.getItem("selectedPayrollType");
            return stored ? JSON.parse(stored) : null;
        }
        </script>';
    }
    
    public function getStyles() 
    {
        return '
        <style>
        .navbar-text {
            font-weight: 500;
            color: #495057 !important;
        }
        
        .user-image {
            width: 25px;
            height: 25px;
        }
        
        .navbar-badge {
            font-size: 0.6rem;
            font-weight: bold;
        }
        
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .user-header {
            background: linear-gradient(135deg, #007bff, #0056b3) !important;
        }
        
        @media (max-width: 576px) {
            .navbar-text span {
                display: none;
            }
        }
        </style>';
    }
}
} // End of if (!class_exists('NavbarComponent'))

// Renderizar el componente
$navbar = new NavbarComponent();
echo $navbar->render();
?>