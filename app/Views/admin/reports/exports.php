<?php
$page_title = 'Reportes de Exportación';

$content = '
<div class="row">
    <div class="col-12">
        <!-- Mensajes de estado -->
        ';
        
if (isset($_SESSION['success'])) {
    $content .= '
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ' . $_SESSION['success'] . '
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $content .= '
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ' . $_SESSION['error'] . '
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>';
    unset($_SESSION['error']);
}

$content .= '
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-download"></i>
                    Exportar Datos del Sistema
                </h3>
                <div class="card-tools">
                    <a href="' . \App\Core\UrlHelper::url('/panel/reports') . '" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Reportes
                    </a>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Selecciona el tipo de datos que deseas exportar. Los archivos se descargarán en formato CSV compatible con Excel.
                </p>

                <div class="row">
                    <!-- Exportar Empleados -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-primary export-card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-users fa-3x text-primary"></i>
                                </div>
                                <h5 class="card-title">Empleados</h5>
                                <p class="card-text">
                                    Exportar listado completo de empleados con información personal, 
                                    posiciones, salarios y estado.
                                </p>
                                <div class="mt-auto">
                                    <a href="' . \App\Core\UrlHelper::url('/panel/reports/export/employees') . '" 
                                       class="btn btn-primary btn-block export-btn">
                                        <i class="fas fa-download"></i>
                                        Exportar Empleados
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Exportar Acreedores -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-warning export-card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-building fa-3x text-warning"></i>
                                </div>
                                <h5 class="card-title">Acreedores</h5>
                                <p class="card-text">
                                    Exportar listado de acreedores y proveedores con información 
                                    de contacto y estado.
                                </p>
                                <div class="mt-auto">
                                    <a href="' . \App\Core\UrlHelper::url('/panel/reports/export/creditors') . '" 
                                       class="btn btn-warning btn-block export-btn">
                                        <i class="fas fa-download"></i>
                                        Exportar Acreedores
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Exportar Conceptos -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-success export-card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-calculator fa-3x text-success"></i>
                                </div>
                                <h5 class="card-title">Conceptos</h5>
                                <p class="card-text">
                                    Exportar catálogo de conceptos con fórmulas, tipos, 
                                    categorías y configuraciones.
                                </p>
                                <div class="mt-auto">
                                    <a href="' . \App\Core\UrlHelper::url('/panel/reports/export/concepts') . '" 
                                       class="btn btn-success btn-block export-btn">
                                        <i class="fas fa-download"></i>
                                        Exportar Conceptos
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Información sobre las Exportaciones</h6>
                            <ul class="mb-0">
                                <li><strong>Formato:</strong> Los archivos se exportan en formato CSV con codificación UTF-8</li>
                                <li><strong>Compatibilidad:</strong> Compatible con Excel, Google Sheets y otros editores de hojas de cálculo</li>
                                <li><strong>Contenido:</strong> Se incluyen todos los registros activos e inactivos</li>
                                <li><strong>Nombre de archivo:</strong> Incluye fecha y hora de generación automáticamente</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-gradient-success">
                            <div class="card-header">
                                <h3 class="card-title text-white">
                                    <i class="fas fa-chart-bar"></i> Estado de Exportaciones
                                </h3>
                            </div>
                            <div class="card-body text-white">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="description-block border-right">
                                            <i class="fas fa-users fa-2x"></i>
                                            <h5 class="description-header">CSV</h5>
                                            <span class="description-text">EMPLEADOS</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="description-block border-right">
                                            <i class="fas fa-building fa-2x"></i>
                                            <h5 class="description-header">CSV</h5>
                                            <span class="description-text">ACREEDORES</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="description-block">
                                            <i class="fas fa-calculator fa-2x"></i>
                                            <h5 class="description-header">CSV</h5>
                                            <span class="description-text">CONCEPTOS</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

// Scripts y estilos para el módulo usando sistema modular
$scriptFiles = [
    '/assets/javascript/modules/reports/exports.js'
];

use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();
$scripts = $jsConfig . "\n" . JavaScriptHelper::renderScriptTags($scriptFiles);

$styles = '<link rel="stylesheet" href="' . url('/assets/css/modules/reports/exports.css') . '">';

include __DIR__ . '/../../layouts/admin.php';
?>