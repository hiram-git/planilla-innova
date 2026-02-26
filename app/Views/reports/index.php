<?php
$page_title = 'Centro de Reportes';

$content = '
<div class="row reports-container">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line mr-1"></i>
                    Centro de Reportes del Sistema
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <h4><i class="fas fa-file-pdf text-danger"></i> Reportes de Planillas</h4>
                        <p class="text-muted">Genere reportes detallados de planillas procesadas en formato PDF profesional.</p>
                        
';
                        
                        if (empty($payrolls)) {
                            $content .= '
                        <div class="alert alert-info alert-reports">
                            <i class="fas fa-info-circle"></i>
                            <strong>No hay planillas disponibles</strong><br>
                            Para generar reportes, primero debe procesar al menos una planilla.
                            <a href="' . \App\Core\UrlHelper::url('/panel/payrolls') . '" class="btn btn-sm btn-primary ml-2">
                                <i class="fas fa-plus"></i> Ir a Planillas
                            </a>
                        </div>';
                        } else {
                            $content .= '
                        <div class="table-responsive reports-table">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Planilla</th>
                                        <th>Período</th>
                                        <th>Tipo</th>
                                        <th>Empleados</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>';
                        
                        foreach ($payrolls as $payroll) {
                            $estadoClass = '';
                            $estadoIcon = '';
                            switch ($payroll['estado']) {
                                case 'PROCESADA':
                                    $estadoClass = 'badge-success';
                                    $estadoIcon = 'fas fa-check-circle';
                                    break;
                                case 'CERRADA':
                                    $estadoClass = 'badge-secondary';
                                    $estadoIcon = 'fas fa-lock';
                                    break;
                                default:
                                    $estadoClass = 'badge-warning';
                                    $estadoIcon = 'fas fa-clock';
                            }
                            
                            $content .= '
                                    <tr>
                                        <td>
                                            <strong>' . htmlspecialchars($payroll['descripcion']) . '</strong><br>
                                            <small class="text-muted">ID: ' . $payroll['id'] . '</small>
                                        </td>
                                        <td>
                                            ' . date('d/m/Y', strtotime($payroll['fecha_inicio'])) . '<br>
                                            <small class="text-muted">al ' . date('d/m/Y', strtotime($payroll['fecha_fin'])) . '</small>
                                        </td>
                                        <td>' . htmlspecialchars($payroll['tipo_descripcion'] ?? 'N/A') . '</td>
                                        <td>
                                            <span class="badge badge-employees">
                                                <i class="fas fa-users"></i> ' . ($payroll['total_empleados'] ?? 0) . '
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-status ' . $estadoClass . '">
                                                <i class="' . $estadoIcon . '"></i> ' . $payroll['estado'] . '
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group-reports" role="group">
                                                <a href="' . \App\Core\UrlHelper::url('/panel/reports/planilla-pdf/' . $payroll['id']) . '" 
                                                   class="btn btn-danger btn-report-action" 
                                                   target="_blank"
                                                   data-toggle="tooltip" 
                                                   data-placement="top" 
                                                   title="Generar PDF de Planilla">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                <a href="' . \App\Core\UrlHelper::url('/panel/reports/planilla-excel-panama/' . $payroll['id']) . '" 
                                                   class="btn btn-info btn-report-action" 
                                                   target="_blank"
                                                   data-toggle="tooltip" 
                                                   data-placement="top" 
                                                   title="Excel Panamá (4 Hojas)">
                                                    <i class="fas fa-file-excel"></i>
                                                </a>
                                                <a href="' . \App\Core\UrlHelper::url('/panel/reports/comprobantes-planilla/' . $payroll['id']) . '" 
                                                   class="btn btn-success btn-report-action" 
                                                   target="_blank"
                                                   data-toggle="tooltip" 
                                                   data-placement="top" 
                                                   title="Comprobantes de Pago">
                                                    <i class="fas fa-receipt"></i>
                                                </a>
                                                <a href="' . \App\Core\UrlHelper::url('/panel/reports/reporte-acreedores/' . $payroll['id']) . '"
                                                   class="btn btn-warning btn-report-action"
                                                   target="_blank"
                                                   data-toggle="tooltip"
                                                   data-placement="top"
                                                   title="Reporte de Acreedores">
                                                    <i class="fas fa-building"></i>
                                                </a>
                                                <a href="' . \App\Core\UrlHelper::url('/panel/reports/informe03/' . $payroll['id']) . '"
                                                   class="btn btn-secondary btn-report-action"
                                                   target="_blank"
                                                   data-toggle="tooltip"
                                                   data-placement="top"
                                                   title="Informe 03 - Reporte Gubernamental">
                                                    <i class="fas fa-file-contract"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>';
                        }
                        
                            $content .= '
                                </tbody>
                            </table>
                        </div>';
                        }
                        
                        $content .= '
                    </div>
                </div>
                
                <!-- Reportes Adicionales -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-building text-primary"></i>
                                    Reporte General de Acreedores
                                </h3>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Genere un reporte consolidado de todos los acreedores con datos de todas las planillas.</p>
                                <a href="' . \App\Core\UrlHelper::url('/panel/reports/reporte-acreedores') . '" 
                                   class="btn btn-primary btn-report-action" 
                                   target="_blank">
                                    <i class="fas fa-file-pdf"></i> Generar Reporte General
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-download text-success"></i>
                                    Exportar Datos
                                </h3>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Exporte empleados, acreedores y conceptos en formato CSV para Excel.</p>
                                <a href="' . \App\Core\UrlHelper::url('/panel/reports/exports') . '" 
                                   class="btn btn-success btn-report-action">
                                    <i class="fas fa-download"></i> Ver Exportaciones
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

';

// Scripts específicos para esta vista
use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();

$scripts = $jsConfig . '
<script>
// Configuración para el módulo de reportes
window.REPORTS_CONFIG = {
    animations: {
        buttonRestoreDelay: 3000,
        toastTimeout: 5000
    },
    debug: ' . (getenv('APP_ENV') === 'development' ? 'true' : 'false') . '
};

// ========================================
// GSAP ANIMATIONS - Reports Page
// ========================================

// Función global para animar filas de la tabla de reportes
window.animateReportsTableRows = function() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const rows = $(".reports-table tbody tr");

    // Si no hay filas, no hacer nada
    if (rows.length === 0) {
        return;
    }

    // Primero, asegurar que las filas estén ocultas
    gsap.set(rows, { opacity: 0, y: 20 });

    // Animar filas con fade-in + slide-up
    gsap.to(rows, {
        opacity: 1,
        y: 0,
        duration: 0.4,
        stagger: 0.05,
        ease: "power2.out",
        clearProps: "transform,y"
    });

    // Animar iconos de acciones
    setupReportsActionButtonAnimations();
};

// Función para animar botones de acción y badges
function setupReportsActionButtonAnimations() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const badges = $(".reports-table .badge");
    const buttons = $(".btn-report-action");
    const icons = $(".btn-report-action i");

    // Animar badges de estado
    badges.each(function(index) {
        gsap.fromTo(this,
            {
                scale: 0,
                opacity: 0
            },
            {
                scale: 1,
                opacity: 1,
                duration: 0.4,
                delay: index * 0.02,
                ease: "back.out(2)",
                clearProps: "transform,scale"
            }
        );
    });

    // Hover effects en botones de acción
    buttons.off("mouseenter.gsap mouseleave.gsap").on({
        "mouseenter.gsap": function() {
            if (!$(this).prop("disabled")) {
                let shadowColor = "rgba(108,117,125,0.4)"; // Default

                if ($(this).hasClass("btn-danger")) {
                    shadowColor = "rgba(220,53,69,0.4)";
                } else if ($(this).hasClass("btn-info")) {
                    shadowColor = "rgba(23,162,184,0.4)";
                } else if ($(this).hasClass("btn-success")) {
                    shadowColor = "rgba(40,167,69,0.4)";
                } else if ($(this).hasClass("btn-warning")) {
                    shadowColor = "rgba(255,193,7,0.4)";
                } else if ($(this).hasClass("btn-secondary")) {
                    shadowColor = "rgba(108,117,125,0.4)";
                } else if ($(this).hasClass("btn-primary")) {
                    shadowColor = "rgba(0,123,255,0.4)";
                }

                gsap.to(this, {
                    scale: 1.15,
                    boxShadow: "0 5px 15px " + shadowColor,
                    duration: 0.2,
                    ease: "power2.out"
                });
            }
        },
        "mouseleave.gsap": function() {
            gsap.to(this, {
                scale: 1,
                boxShadow: "none",
                duration: 0.2,
                ease: "power2.out"
            });
        }
    });

    // Animación para iconos dentro de botones
    icons.off("mouseenter.gsap").on("mouseenter.gsap", function() {
        if (!$(this).closest(".btn").prop("disabled")) {
            gsap.to(this, {
                rotation: 360,
                duration: 0.5,
                ease: "power2.inOut"
            });
        }
    });
}

// Animar cards de reportes adicionales
function animateReportsCards() {
    if (typeof gsap === "undefined") {
        return;
    }

    const cards = $(".reports-container .card");

    if (cards.length > 0) {
        gsap.set(cards, { opacity: 0, y: 20 });
        gsap.to(cards, {
            opacity: 1,
            y: 0,
            duration: 0.5,
            stagger: 0.1,
            ease: "power2.out",
            clearProps: "transform,y"
        });
    }
}

// Animar alert cuando no hay planillas
function animateReportsAlert() {
    if (typeof gsap === "undefined") {
        return;
    }

    const alert = $(".alert-reports");

    if (alert.length > 0) {
        gsap.set(alert, { opacity: 0, x: -20 });
        gsap.to(alert, {
            opacity: 1,
            x: 0,
            duration: 0.5,
            ease: "power2.out",
            clearProps: "transform,x"
        });
    }
}

// Ejecutar animaciones al cargar la página
$(document).ready(function() {
    if (typeof gsap !== "undefined") {
        // Animar cards principales
        setTimeout(animateReportsCards, 100);

        // Animar alert si existe
        setTimeout(animateReportsAlert, 200);

        // Animar tabla de reportes
        setTimeout(animateReportsTableRows, 300);
    }
});
</script>
<script src="' . url('assets/javascript/modules/reports/index.js', false) . '"></script>';

// Estilos específicos para esta vista
$styles = '
<link rel="stylesheet" href="' . url('assets/css/modules/reports.css', false) . '">
<style>
/* Reducir tamaño de botones de acción para evitar salto de línea */
.btn-report-action {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
    line-height: 1.2;
    border-radius: 0.2rem;
}

.btn-report-action i {
    font-size: 1rem;
}

/* Ajustar spacing del grupo de botones */
.btn-group-reports {
    display: flex;
    gap: 0.25rem;
    flex-wrap: nowrap;
}

/* Reducir padding de la celda de acciones */
.reports-table tbody td:last-child {
    padding: 0.5rem 0.3rem;
    white-space: nowrap;
}

/* Asegurar que badges sean más compactos */
.reports-table .badge {
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
}

.badge-employees {
    font-size: 0.7rem;
}
</style>';

include __DIR__ . '/../layouts/admin.php';
?>