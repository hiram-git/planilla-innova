<?php
$page_title = 'Centro de Reportes';

$content = '
<div class="row">
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
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>No hay planillas disponibles</strong><br>
                            Para generar reportes, primero debe procesar al menos una planilla.
                            <a href="' . \App\Core\UrlHelper::url('/panel/payrolls') . '" class="btn btn-sm btn-primary ml-2">
                                <i class="fas fa-plus"></i> Ir a Planillas
                            </a>
                        </div>';
                        } else {
                            $content .= '
                        <div class="table-responsive">
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
                                            <span class="badge badge-info">
                                                <i class="fas fa-users"></i> ' . ($payroll['total_empleados'] ?? 0) . '
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge ' . $estadoClass . '">
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
                                                   title="PDF Planilla">
                                                    <i class="fas fa-file-pdf fa-lg"></i>
                                                </a>
                                                <a href="' . \App\Core\UrlHelper::url('/panel/reports/comprobantes-planilla/' . $payroll['id']) . '" 
                                                   class="btn btn-success btn-report-action" 
                                                   target="_blank"
                                                   data-toggle="tooltip" 
                                                   data-placement="top" 
                                                   title="Comprobantes">
                                                    <i class="fas fa-receipt fa-lg"></i>
                                                </a>
                                                <a href="' . \App\Core\UrlHelper::url('/panel/reports/reporte-acreedores/' . $payroll['id']) . '" 
                                                   class="btn btn-warning btn-report-action" 
                                                   target="_blank"
                                                   data-toggle="tooltip" 
                                                   data-placement="top" 
                                                   title="Acreedores">
                                                    <i class="fas fa-building fa-lg"></i>
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
                                   class="btn btn-primary" 
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
                                   class="btn btn-success">
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
$scripts = '
<script>
// Configuración para el módulo de reportes
window.REPORTS_CONFIG = {
    animations: {
        buttonRestoreDelay: 3000,
        toastTimeout: 5000
    },
    debug: ' . (getenv('APP_ENV') === 'development' ? 'true' : 'false') . '
};
</script>
<script src="' . url('assets/javascript/modules/reports/index.js', false) . '"></script>';

// Estilos específicos para esta vista  
$styles = '
<link rel="stylesheet" href="' . url('assets/css/modules/reports.css', false) . '">';
<style>
/* Layout principal */
.card {
    transition: all 0.3s ease;
    border-radius: 10px;
    border: 1px solid rgba(0,0,0,0.125);
}

.card:hover {
    transform: translateY(-2px);
}

/* Tabla de reportes */
.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}

.table-responsive {
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Grupo de botones de reportes */
.btn-group-reports {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
}

.btn-report-action {
    padding: 10px 14px;
    border-radius: 8px;
    transition: all 0.2s ease;
    position: relative;
    min-width: 44px;
    text-align: center;
}

.btn-report-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.btn-report-action i {
    font-size: 1.2em;
}

.btn-report-action.disabled {
    opacity: 0.6;
    pointer-events: none;
    transform: none;
}

/* Estados y badges */
.badge {
    font-size: 0.85em;
    padding: 0.5em 0.75em;
    font-weight: 500;
}

.info-box {
    min-height: 90px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-radius: 8px;
}

.alert {
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Tooltips optimizados */
.tooltip {
    pointer-events: none;
    z-index: 1070;
}

.tooltip-inner {
    font-size: 0.875rem;
    padding: 6px 10px;
    border-radius: 6px;
    font-weight: 500;
}

/* Responsive design */
@media (max-width: 768px) {
    .btn-group-reports {
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }
    
    .btn-report-action {
        width: 100%;
        justify-content: center;
        font-size: 0.9rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .tooltip-inner {
        font-size: 0.8rem;
    }
}

@media (max-width: 576px) {
    .btn-report-action {
        padding: 12px 16px;
        font-size: 0.85rem;
    }
    
    .table td, .table th {
        padding: 0.5rem;
    }
}

/* Animaciones mejoradas */
@keyframes buttonClick {
    0% { transform: scale(1); }
    50% { transform: scale(0.95); }
    100% { transform: scale(1); }
}

.btn-report-action:active {
    animation: buttonClick 0.15s ease;
}

/* Loading states */
.btn-report-action .fa-spinner {
    animation: fa-spin 1s infinite linear;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .card, .btn-report-action {
        transition: none;
    }
    
    .card:hover {
        transform: none;
    }
    
    .btn-report-action:hover {
        transform: none;
    }
}
</style>';

include __DIR__ . '/../layouts/admin.php';
?>