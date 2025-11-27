<?php
$page_title = 'Acceso Denegado';

// Obtener módulo solicitado desde sesión
$requested_module = $_SESSION['access_denied_module'] ?? 'este módulo';
$requested_module_name = $_SESSION['access_denied_module_name'] ?? ucfirst($requested_module);

// Limpiar variable de sesión
unset($_SESSION['access_denied_module']);
unset($_SESSION['access_denied_module_name']);

// Obtener módulos permitidos del usuario
$allowed_modules = $permissions ?? [];

$content = '
<div class="row">
    <div class="col-12">
        <!-- Mensaje de Acceso Denegado -->
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-lock mr-2"></i>
                    Acceso Denegado
                </h3>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <i class="fas fa-ban fa-5x text-danger"></i>
                    </div>
                    <div class="col-md-10">
                        <h4 class="mb-3">No tienes permisos para acceder a este módulo</h4>
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Módulo solicitado:</strong> ' . htmlspecialchars($requested_module_name) . '
                        </div>
                        <p class="text-muted">
                            Para solicitar acceso a este módulo, contacta con el administrador del sistema.
                            A continuación puedes ver los módulos a los que tienes acceso.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-th-large mr-2"></i>
                    Accesos Rápidos - Módulos Disponibles
                </h3>
            </div>
            <div class="card-body">
                <div class="row">';

// Mostrar módulos permitidos en cards
if (!empty($allowed_modules)) {
    foreach ($allowed_modules as $url => $module) {
        // Solo mostrar módulos con permiso de lectura
        if (!$module['read']) {
            continue;
        }

        $moduleName = htmlspecialchars($module['name']);

        // Obtener info del menú para ícono
        $menuInfo = $menu_items[$url] ?? null;
        $icon = $menuInfo['icon'] ?? 'fas fa-folder';
        $description = $menuInfo['description'] ?? 'Módulo del sistema';

        // Determinar color de la card según el tipo de módulo
        $colorClass = 'primary';
        if (strpos($url, 'employee') !== false) {
            $colorClass = 'info';
        } elseif (strpos($url, 'payroll') !== false) {
            $colorClass = 'success';
        } elseif (strpos($url, 'attendance') !== false) {
            $colorClass = 'warning';
        } elseif (strpos($url, 'report') !== false) {
            $colorClass = 'secondary';
        } elseif (strpos($url, 'user') !== false || strpos($url, 'role') !== false) {
            $colorClass = 'danger';
        }

        $moduleUrl = \App\Core\UrlHelper::route('panel/' . $url);

        $content .= '
                    <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                        <div class="card card-' . $colorClass . ' card-outline h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="' . $icon . ' fa-3x text-' . $colorClass . '"></i>
                                </div>
                                <h5 class="card-title font-weight-bold mb-2">' . $moduleName . '</h5>
                                <p class="card-text text-muted small mb-3">' . htmlspecialchars($description) . '</p>
                                <a href="' . $moduleUrl . '" class="btn btn-' . $colorClass . ' btn-sm btn-block">
                                    <i class="fas fa-arrow-right mr-1"></i> Acceder
                                </a>
                            </div>
                        </div>
                    </div>';
    }
} else {
    $content .= '
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            No tienes módulos disponibles asignados. Contacta con el administrador.
                        </div>
                    </div>';
}

$content .= '
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="' . \App\Core\UrlHelper::route('panel/dashboard') . '" class="btn btn-primary btn-lg">
                    <i class="fas fa-home mr-2"></i>
                    Volver al Dashboard
                </a>
            </div>
        </div>
    </div>
</div>';

$styles = '
<style>
/* Animaciones y efectos para las cards de módulos */
.card-outline {
    border-width: 2px;
    transition: all 0.3s ease;
}

.card-outline:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Efecto de hover en iconos */
.card-outline i.fa-3x {
    transition: all 0.3s ease;
}

.card-outline:hover i.fa-3x {
    transform: scale(1.1);
}

/* Garantizar altura uniforme en cards */
.h-100 {
    height: 100% !important;
}

/* Mejorar espaciado en dispositivos pequeños */
@media (max-width: 576px) {
    .col-12.mb-3 {
        margin-bottom: 1rem !important;
    }
}

/* Animación de entrada */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.5s ease-out;
}

/* Estilo para el mensaje de acceso denegado */
.fa-ban {
    opacity: 0.7;
}

/* Botones con mejor hover */
.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
</style>';

$scripts = '
<script>
$(document).ready(function() {
    // Agregar animación escalonada a las cards
    $(".card-outline").each(function(index) {
        $(this).css({
            "animation-delay": (index * 0.05) + "s"
        });
    });

    // Agregar efecto de pulse al icono de acceso denegado
    setInterval(function() {
        $(".fa-ban").fadeOut(1000).fadeIn(1000);
    }, 2000);
});
</script>';

include __DIR__ . '/../layouts/admin.php';
?>
