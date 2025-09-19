<?php
/**
 * Layout Principal del Área Administrativa
 * Utiliza componentes reutilizables para navbar y sidebar
 */

// Incluir componentes
$navbarComponent = null;
$sidebarComponent = null;

// Capturar estilos de componentes
ob_start();
include __DIR__ . '/../components/navbar.php';
$navbarStyles = isset($navbar) ? $navbar->getStyles() : '';
$navbarScripts = isset($navbar) ? $navbar->getScripts() : '';
$navbarHtml = ob_get_clean();

// Incluir el componente sidebar
include __DIR__ . '/../components/sidebar.php';
$sidebarStyles = isset($sidebar) ? $sidebar->getStyles() : '';
$sidebarHtml = isset($sidebar) ? $sidebar->render() : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Planilla y Control de Asistencia">
    <meta name="author" content="Innova Planilla">
    <meta name="robots" content="noindex, nofollow">
    <?php if (isset($_SESSION['csrf_token'])): ?>
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
    <?php endif; ?>
    
    <title><?= htmlspecialchars($title ?? 'Administración - Innova Planilla') ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= url('dist/img/favicon.ico') ?>">
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="<?= url('plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= url('dist/css/adminlte.min.css') ?>">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="<?= url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') ?>">
    
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= url('plugins/select2/css/select2.min.css') ?>">
    <link rel="stylesheet" href="<?= url('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') ?>">
    
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Custom Component Styles -->
    <?= $navbarStyles ?>
    <?= $sidebarStyles ?>
    
    <!-- Page Specific Styles -->
    <?= $styles ?? '' ?>
    
    <!-- Global Styles -->
    <style>
    :root {
        --primary-color: #007bff;
        --secondary-color: #6c757d;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --info-color: #17a2b8;
    }
    
    .wrapper {
        min-height: 100vh;
    }
    
    .content-wrapper {
        transition: margin-left 0.3s ease;
    }
    
    /* Mejoras para la transición del sidebar */
    .main-sidebar {
        transition: margin-left 0.3s ease, width 0.3s ease;
    }
    
    body.sidebar-collapse .main-sidebar {
        margin-left: -250px;
    }
    
    body.sidebar-collapse .content-wrapper,
    body.sidebar-collapse .main-footer {
        margin-left: 60px;
    }
    
    /* Transiciones suaves para los elementos del sidebar */
    .sidebar .nav-link,
    .sidebar .brand-link {
        transition: all 0.3s ease;
    }
    
    /* Loading overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        z-index: 9999;
        display: none;
    }
    
    .loading-spinner {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    
    /* Accessibility improvements */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0,0,0,0);
        white-space: nowrap;
        border: 0;
    }
    
    /* Focus styles for better accessibility */
    .nav-link:focus,
    .btn:focus,
    .form-control:focus {
        outline: 2px solid var(--primary-color);
        outline-offset: 2px;
    }
    
    /* Alert improvements */
    .alert {
        border-radius: 8px;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .alert .close {
        padding: 0.5rem 1rem;
        opacity: 0.8;
    }
    
    .alert .close:hover {
        opacity: 1;
    }
    
    /* Card improvements */
    .card {
        transition: box-shadow 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    /* Button improvements */
    .btn {
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        transform: translateY(-1px);
    }
    
    /* Form improvements */
    .form-control {
        border-radius: 6px;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    
    .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    /* Table improvements */
    .table {
        border-radius: 8px;
        overflow: hidden;
    }
    
    .table thead th {
        border-top: none;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        
        <!-- Loading Overlay -->
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>
        </div>
        
        <!-- Navbar -->
        <?= $navbarHtml ?>

        <!-- Sidebar -->
        <?= $sidebarHtml ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?= htmlspecialchars($page_title ?? $title ?? '') ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right mb-0">
                                <?php
                                use App\Core\RouteHelper;
                                echo RouteHelper::renderBreadcrumbs();
                                ?>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Alert Messages -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i>
                            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['warning'])): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?= htmlspecialchars($_SESSION['warning']); unset($_SESSION['warning']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['info'])): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle mr-2"></i>
                            <?= htmlspecialchars($_SESSION['info']); unset($_SESSION['info']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Display validation errors -->
                    <?php if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Por favor, corrija los siguientes errores:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($_SESSION['errors'] as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>
                    
                    <!-- Page Content -->
                    <?= $content ?? '' ?>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-block">
                <?php
                use App\Helpers\VersionHelper;
                $versionInfo = VersionHelper::getFullVersionInfo();
                ?>
                <b>Versión</b> <?= VersionHelper::getCurrentVersion() ?>
                <span class="badge badge-primary ml-1">Innova Planilla</span>
                <?php if (!empty($versionInfo['codename'])): ?>
                <small class="text-muted ml-1"><?= htmlspecialchars($versionInfo['codename']) ?></small>
                <?php endif; ?>
            </div>
            <strong>&copy; <?= date('Y') ?>
                <a href="#" class="text-decoration-none">Innova Planilla</a>.
            </strong> 
            Todos los derechos reservados.
        </footer>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <div class="p-3">
                <h5>Configuración</h5>
                <p>Panel de configuración rápida del sistema.</p>
            </div>
        </aside>
    </div>

    <!-- jQuery -->
    <script src="<?= url('plugins/jquery/jquery.min.js') ?>"></script>
    <!-- Bootstrap 4 -->
    <script src="<?= url('plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <!-- AdminLTE App -->
    <script src="<?= url('dist/js/adminlte.min.js') ?>"></script>
    
    <!-- DataTables & Plugins -->
    <script src="<?= url('plugins/datatables/jquery.dataTables.min.js') ?>"></script>
    <script src="<?= url('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') ?>"></script>
    <script src="<?= url('plugins/datatables-responsive/js/dataTables.responsive.min.js') ?>"></script>
    
    <!-- Select2 -->
    <script src="<?= url('plugins/select2/js/select2.full.min.js') ?>"></script>
    
    <!-- Toastr -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- Global Scripts -->
    <script>
    $(document).ready(function() {
        // Configuración global de Toastr
        toastr.options = {
            closeButton: true,
            debug: false,
            newestOnTop: true,
            progressBar: true,
            positionClass: "toast-top-right",
            preventDuplicates: false,
            onclick: null,
            showDuration: "300",
            hideDuration: "500",
            timeOut: "3000",
            extendedTimeOut: "500",
            showEasing: "swing",
            hideEasing: "linear",
            showMethod: "fadeIn",
            hideMethod: "fadeOut"
        };
        // === PERSISTENCIA DEL ESTADO DEL SIDEBAR ===
        
        // Función para guardar el estado del sidebar
        function saveSidebarState() {
            const isCollapsed = $('body').hasClass('sidebar-collapse');
            localStorage.setItem('sidebar-collapsed', isCollapsed);
        }
        
        // Función para restaurar el estado del sidebar
        function restoreSidebarState() {
            const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            if (isCollapsed) {
                $('body').addClass('sidebar-collapse');
            } else {
                $('body').removeClass('sidebar-collapse');
            }
        }
        
        // Restaurar estado al cargar la página
        restoreSidebarState();
        
        // Escuchar eventos de toggle del sidebar
        $(document).on('click', '[data-widget="pushmenu"]', function() {
            // Usar setTimeout para capturar el estado después del cambio
            setTimeout(function() {
                saveSidebarState();
            }, 50);
        });
        
        // También escuchar cambios en el body para detectar otros métodos de toggle
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    saveSidebarState();
                }
            });
        });
        
        // Observar cambios en las clases del body
        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['class']
        });
        
        // === FIN PERSISTENCIA SIDEBAR ===
        
        // === ADMINLTE TREEVIEW - SIN INTERFERENCIA ===
        // Dejar que AdminLTE maneje completamente los toggles del menú
        // El plugin se inicializa automáticamente con data-widget="treeview"
        
        // Auto-hide alerts after 5 seconds
        $('.alert:not(.alert-permanent)').delay(5000).fadeOut('slow');
        
        // Loading overlay functions
        window.showLoading = function() {
            $('#loadingOverlay').fadeIn('fast');
        };
        
        window.hideLoading = function() {
            $('#loadingOverlay').fadeOut('fast');
        };
        
        // Confirm delete function
        window.confirmDelete = function(message = '¿Está seguro que desea eliminar este elemento?') {
            return confirm(message);
        };
        
        // Función global para resetear el estado del sidebar
        window.resetSidebarState = function() {
            localStorage.removeItem('sidebar-collapsed');
            $('body').removeClass('sidebar-collapse');
        };
        
        // Form validation helper
        $('.needs-validation').on('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            $(this).addClass('was-validated');
        });
        
        // Tooltips initialization
        $('[data-toggle="tooltip"]').tooltip();
        
        // Popovers initialization
        $('[data-toggle="popover"]').popover();
        
        // Inicialización robusta del sidebar
        function initializeSidebar() {
            // Esperar un poco para asegurar que el DOM esté completamente listo
            setTimeout(function() {
                // Verificar si el sidebar existe
                if ($('.nav-sidebar').length === 0) {
                    return;
                }
                
                // Forzar inicialización del treeview
                if (typeof $().Treeview !== 'undefined') {
                    $('[data-widget="treeview"]').Treeview('init');
                } else {
                    // Fallback: activar dropdowns manualmente
                    $('.nav-item.has-treeview > .nav-link').on('click', function(e) {
                        e.preventDefault();
                        const parent = $(this).parent();
                        const submenu = parent.find('.nav-treeview');
                        
                        parent.toggleClass('menu-open');
                        submenu.slideToggle(300);
                        $(this).find('.right').toggleClass('fa-angle-left fa-angle-down');
                    });
                }
            }, 100);
        }
        
        // Llamar inicialización del sidebar
        initializeSidebar();
        
        // Sidebar search functionality
        $(document).on('input', '[data-widget="sidebar-search"] input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const menuItems = $('.nav-sidebar .nav-item');
            
            menuItems.each(function() {
                const itemText = $(this).text().toLowerCase();
                if (itemText.includes(searchTerm) || searchTerm === '') {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    });
    
    // Service Worker registration for offline support
    // Service Worker comentado - no existe sw.js
    /*
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').then(function(registration) {
                console.log('SW registered: ', registration);
            }).catch(function(registrationError) {
                console.log('SW registration failed: ', registrationError);
            });
        });
    }
    */
    </script>
    
    <!-- Component Scripts -->
    <?= $navbarScripts ?>
    
    <!-- JavaScript Configuration for Dynamic URLs -->
    <?php if (!empty($jsConfig)): ?>
        <?= $jsConfig ?>
    <?php endif; ?>
    
    <!-- Page Specific Scripts -->
    <?php if (isset($scriptFiles) && is_array($scriptFiles)): ?>
        <!-- New modular JavaScript approach -->
        <?php foreach ($scriptFiles as $scriptFile): ?>
            <script src="<?= url($scriptFile) ?>"></script>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- Legacy inline JavaScript approach -->
        <?= $scripts ?? '' ?>
    <?php endif; ?>
</body>
</html>