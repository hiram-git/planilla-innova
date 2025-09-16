<?php 
$page_title = $page_title ?? 'Nuevo Elemento Organizacional';
$elementsFlat = $elementsFlat ?? [];

$content = '

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Información del Elemento
                        </h3>
                    </div>
                    
                    <form action="' . \App\Core\UrlHelper::url('panel/organizational/create') . '" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                        <div class="card-body">';

// Los mensajes de sesión se manejan con toastr al final del archivo

$content .= '
                            <div class="form-group">
                                <label for="descripcion">
                                    <i class="fas fa-tag mr-1"></i>
                                    Descripción <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="descripcion" 
                                       name="descripcion" 
                                       placeholder="Ej: Junta Directiva, Recursos Humanos, Finanzas..."
                                       value="' . htmlspecialchars($_POST['descripcion'] ?? '') . '"
                                       required>
                                <small class="form-text text-muted">
                                    Nombre descriptivo del elemento organizacional
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="id_padre">
                                    <i class="fas fa-sitemap mr-1"></i>
                                    Elemento Padre (Opcional)
                                </label>
                                <select class="form-control select2" id="id_padre" name="id_padre" style="width: 100%;">
                                    <option value="">-- Sin elemento padre (Raíz) --</option>';

foreach ($elementsFlat as $element) {
    $selected = (($_POST['id_padre'] ?? '') == $element['id']) ? 'selected' : '';
    $indent = str_repeat('└─ ', substr_count($element['path'], '/') - 2);
    $content .= '<option value="' . $element['id'] . '" ' . $selected . '>' . $indent . htmlspecialchars($element['descripcion']) . '</option>';
}

$content .= '
                                </select>
                                <small class="form-text text-muted">
                                    Seleccione el elemento padre en la jerarquía. Deje vacío para crear un elemento raíz.
                                </small>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="icon fas fa-info"></i> Información importante:</h6>
                                <ul class="mb-0">
                                    <li>El <strong>path</strong> (ruta) se generará automáticamente basado en la descripción</li>
                                    <li>Si selecciona un padre, el elemento se ubicará como hijo de ese elemento</li>
                                    <li>Los elementos raíz aparecerán en el nivel superior del organigrama</li>
                                    <li>La jerarquía se puede modificar posteriormente editando los elementos</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save mr-2"></i>
                                        Crear Elemento
                                    </button>
                                    <button type="reset" class="btn btn-secondary ml-2">
                                        <i class="fas fa-undo mr-2"></i>
                                        Limpiar
                                    </button>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="' . \App\Core\UrlHelper::url('panel/organizational') . '" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left mr-2"></i>
                                        Volver al Organigrama
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Vista Previa de Estructura</h3>
                    </div>
                    <div class="card-body">
                        <div id="preview-structure">';

if (!empty($elementsFlat)) {
    $content .= '<ul class="list-unstyled">';
    foreach ($elementsFlat as $element) {
        $marginLeft = (substr_count($element['path'], '/') - 2) * 20;
        $content .= '<li style="margin-left: ' . $marginLeft . 'px;">
                        <i class="fas fa-angle-right mr-1"></i>
                        ' . htmlspecialchars($element['descripcion']) . '
                    </li>';
    }
    $content .= '</ul>';
} else {
    $content .= '<p class="text-muted">No hay elementos en la estructura aún.</p>';
}

$content .= '
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Consejos</h3>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h6>💡 Sugerencias:</h6>
                            <ul class="mb-0 small">
                                <li>Comience con elementos de alto nivel (Junta Directiva, Dirección General)</li>
                                <li>Use nombres descriptivos y claros</li>
                                <li>Mantenga una jerarquía lógica</li>
                                <li>Puede reorganizar la estructura posteriormente</li>
                            </ul>
                        </div>
                        
                        <div class="callout callout-warning">
                            <h6>⚠️ Recordatorio:</h6>
                            <p class="mb-0 small">
                                La descripción se usará para generar automáticamente el path del elemento.
                                Use caracteres alfanuméricos y espacios.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>';

use App\Helpers\JavaScriptHelper;

// CSS externo
$styles = '
<link rel="stylesheet" href="' . url('/plugins/select2/css/select2.min.css') . '">
<link rel="stylesheet" href="' . url('/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . url('/assets/css/organizational.css') . '">';

// JavaScript externo con configuración
$scriptFiles = [
    '/plugins/select2/js/select2.full.min.js',
    '/assets/javascript/modules/organizational/create.js'
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