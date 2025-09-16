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

include __DIR__ . '/../layouts/admin.php';
?>