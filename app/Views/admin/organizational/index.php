<?php 
use App\Helpers\JavaScriptHelper;

$page_title = $page_title ?? 'Organigrama Empresarial';
$hierarchy = $hierarchy ?? [];
$statistics = $statistics ?? [];

function renderOrganizationalTree($elements, $level = 0) {
    $html = '<ul class="organizational-tree">';
    
    foreach ($elements as $element) {
        $html .= '<li class="tree-item" data-id="' . $element['id'] . '">';
        $html .= '<div class="tree-node level-' . $level . '">';
        
        // Icono expandir/contraer si tiene hijos
        if (!empty($element['children'])) {
            $html .= '<i class="fas fa-chevron-down toggle-icon" onclick="toggleNode(this)"></i>';
        } else {
            $html .= '<i class="fas fa-circle leaf-icon"></i>';
        }
        
        // Información del elemento
        $html .= '<span class="node-title">' . htmlspecialchars($element['descripcion']) . '</span>';
        
        // Botones de acción
        $html .= '<div class="node-actions">';
        
        // Botón de editar (siempre visible)
        $html .= '<a href="' . \App\Core\UrlHelper::url('panel/organizational/edit/' . $element['id']) . '" class="btn btn-sm btn-primary" title="Editar">';
        $html .= '<i class="fas fa-edit"></i>';
        $html .= '</a>';
        
        // Botón de eliminar (solo si no tiene hijos)
        if (empty($element['children'])) {
            $html .= '<button class="btn btn-sm btn-danger ml-1" onclick="deleteElement(' . $element['id'] . ')" title="Eliminar">';
            $html .= '<i class="fas fa-trash"></i>';
            $html .= '</button>';
        } else {
            $html .= '<button class="btn btn-sm btn-secondary ml-1" disabled title="No se puede eliminar: tiene elementos hijos">';
            $html .= '<i class="fas fa-ban"></i>';
            $html .= '</button>';
        }
        
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Renderizar hijos recursivamente
        if (!empty($element['children'])) {
            $html .= renderOrganizationalTree($element['children'], $level + 1);
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    return $html;
}

$content = '

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        
        <!-- Organigrama -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-project-diagram mr-2"></i>
                            Estructura Organizacional
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-sm btn-primary" onclick="expandAll()">
                                <i class="fas fa-expand-alt"></i> Expandir Todo
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary ml-1" onclick="collapseAll()">
                                <i class="fas fa-compress-alt"></i> Colapsar Todo
                            </button>
                            <a href="' . \App\Core\UrlHelper::url('panel/organizational/create') . '" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus mr-2"></i>Agregar Elemento
                            </a>
                        </div>
                    </div>
                    <div class="card-body">';

                    if (!empty($hierarchy)) {
                        $content .= '<div id="organizational-tree">' . renderOrganizationalTree($hierarchy) . '</div>';
                    } else {
                        $content .= '
                        <div class="alert alert-info">
                            <h5><i class="icon fas fa-info"></i> ¡Comience creando su estructura organizacional!</h5>
                            No hay elementos en la estructura organizacional.
                            <a href="' . \App\Core\UrlHelper::url('panel/organizational/create') . '" class="alert-link">Crear primer elemento</a>
                        </div>';
                    }

                    $content .= '
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Acciones Rápidas -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Buscar Elemento</h3>
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchInput" placeholder="Buscar por descripción...">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="button" onclick="searchElements()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div id="searchResults" class="mt-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Acciones Rápidas</h3>
                    </div>
                    <div class="card-body">
                        <div class="btn-group-vertical w-100">
                            <a href="' . \App\Core\UrlHelper::url('panel/organizational/create') . '" class="btn btn-primary mb-2">
                                <i class="fas fa-plus mr-2"></i>Agregar Elemento
                            </a>
                            <button type="button" class="btn btn-info mb-2" onclick="exportChart()">
                                <i class="fas fa-download mr-2"></i>Exportar Organigrama
                            </button>
                            <button type="button" class="btn btn-success mb-2" onclick="printChart()">
                                <i class="fas fa-print mr-2"></i>Imprimir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Información</h3>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <strong>Consejos:</strong><br>
                            • Haga clic en <i class="fas fa-chevron-down"></i> para expandir/colapsar<br>
                            • Use <i class="fas fa-edit text-primary"></i> para editar cualquier elemento<br>
                            • Solo elementos sin hijos muestran <i class="fas fa-trash text-danger"></i><br>
                            • Los elementos padre muestran <i class="fas fa-ban text-secondary"></i> (protegidos)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>';

// CSS externo
$styles = '<link rel="stylesheet" href="' . url('/assets/css/organizational.css') . '">';

// JavaScript externo con configuración
$scriptFiles = [
    '/assets/javascript/modules/organizational/index.js'
];

$jsConfig = JavaScriptHelper::renderConfigScript([
    'baseUrl' => \App\Core\UrlHelper::base()
]);

$scripts = $jsConfig . "\n" . JavaScriptHelper::renderScriptTags($scriptFiles);

// Agregar script para notificaciones toastr desde sesión
$sessionNotifications = '<script type="text/javascript">
$(document).ready(function() {';

if (isset($_SESSION['success'])) {
    $sessionNotifications .= '
    toastr.success("' . addslashes($_SESSION['success']) . '", "Operación Exitosa");';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $sessionNotifications .= '
    toastr.error("' . addslashes($_SESSION['error']) . '", "Error");';
    unset($_SESSION['error']);
}

$sessionNotifications .= '
});
</script>';

$scripts .= "\n" . $sessionNotifications;

include __DIR__ . '/../../layouts/admin.php';
?>