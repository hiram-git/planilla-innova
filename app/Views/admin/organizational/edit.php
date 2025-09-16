<?php 
$page_title = $page_title ?? 'Editar Elemento Organizacional';
$element = $element ?? [];
$elementsFlat = $elementsFlat ?? [];

$content = '
<!-- Content Header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-edit mr-2"></i>
                            Editar Información
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-info">
                                <i class="fas fa-route mr-1"></i>
                                ' . htmlspecialchars($element['path'] ?? '') . '
                            </span>
                        </div>
                    </div>
                    
                    <form action="' . \App\Core\UrlHelper::url('panel/organizational/edit/' . ($element['id'] ?? '')) . '" method="POST">
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
                                       value="' . htmlspecialchars($_POST['descripcion'] ?? $element['descripcion'] ?? '') . '"
                                       required>
                                <small class="form-text text-muted">
                                    Cambiar la descripción actualizará automáticamente el path del elemento
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="id_padre">
                                    <i class="fas fa-sitemap mr-1"></i>
                                    Elemento Padre (Opcional)
                                </label>
                                <select class="form-control select2" id="id_padre" name="id_padre" style="width: 100%;">
                                    <option value="">-- Sin elemento padre (Raíz) --</option>';

foreach ($elementsFlat as $flatElement) {
    // No mostrar el elemento actual ni sus descendientes para evitar ciclos
    if ($flatElement['id'] == $element['id'] || 
        strpos($flatElement['path'], $element['path']) === 0) {
        continue;
    }
    
    $selected = (($_POST['id_padre'] ?? $element['id_padre'] ?? '') == $flatElement['id']) ? 'selected' : '';
    $indent = str_repeat('└─ ', substr_count($flatElement['path'], '/') - 2);
    $content .= '<option value="' . $flatElement['id'] . '" ' . $selected . '>' . $indent . htmlspecialchars($flatElement['descripcion']) . '</option>';
}

$content .= '
                                </select>
                                <small class="form-text text-muted">
                                    Cambiar el padre moverá este elemento y todos sus hijos a la nueva ubicación
                                </small>
                            </div>
                            
                            <div class="callout callout-warning">
                                <h6><i class="icon fas fa-exclamation-triangle"></i> Importante:</h6>
                                <ul class="mb-0">
                                    <li>Al cambiar la descripción, el <strong>path se regenerará automáticamente</strong></li>
                                    <li>Al cambiar el padre, <strong>todos los elementos hijos se moverán</strong> con este elemento</li>
                                    <li>No puede seleccionar como padre a sí mismo o a sus descendientes</li>
                                    <li>Los cambios se reflejarán inmediatamente en el organigrama</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save mr-2"></i>
                                        Guardar Cambios
                                    </button>
                                    <button type="reset" class="btn btn-warning ml-2">
                                        <i class="fas fa-undo mr-2"></i>
                                        Restaurar
                                    </button>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="' . \App\Core\UrlHelper::url('panel/organizational') . '" class="btn btn-outline-secondary mr-2">
                                        <i class="fas fa-arrow-left mr-2"></i>
                                        Volver al Organigrama
                                    </a>
                                    <button type="button" class="btn btn-danger" onclick="confirmDelete(' . ($element['id'] ?? 0) . ')">
                                        <i class="fas fa-trash mr-2"></i>
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Elementos Relacionados</h3>
                    </div>
                    <div class="card-body">
                        <h6>Padre Actual:</h6>';

if (!empty($element['id_padre'])) {
    $parent = null;
    foreach ($elementsFlat as $flatElement) {
        if ($flatElement['id'] == $element['id_padre']) {
            $parent = $flatElement;
            break;
        }
    }
    
    if ($parent) {
        $content .= '
                        <div class="callout callout-info">
                            <i class="fas fa-arrow-up mr-1"></i>
                            ' . htmlspecialchars($parent['descripcion']) . '
                        </div>';
    }
} else {
    $content .= '
                        <div class="callout callout-secondary">
                            <i class="fas fa-crown mr-1"></i>
                            Elemento raíz (sin padre)
                        </div>';
}

$content .= '
                        <h6 class="mt-3">Elementos Hijos:</h6>';

$children = array_filter($elementsFlat, function($item) use ($element) {
    return $item['id_padre'] == $element['id'];
});

if (!empty($children)) {
    $content .= '<div class="list-group">';
    foreach ($children as $child) {
        $content .= '
                                <div class="list-group-item list-group-item-action">
                                    <i class="fas fa-arrow-down mr-1"></i>
                                    ' . htmlspecialchars($child['descripcion']) . '
                                    <a href="' . \App\Core\UrlHelper::url('panel/organizational/edit/' . $child['id']) . '" 
                                       class="btn btn-sm btn-outline-primary float-right">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>';
    }
    $content .= '</div>
                        <div class="callout callout-warning mt-2">
                            <small>
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                No se puede eliminar este elemento porque tiene ' . count($children) . ' elemento(s) hijo(s)
                            </small>
                        </div>';
} else {
    $content .= '
                        <div class="callout callout-light">
                            <small class="text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Sin elementos hijos
                            </small>
                        </div>';
}

$content .= '
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

// Preparar datos para JavaScript
$elementData = [
    'id' => $element['id'] ?? 0,
    'descripcion' => $element['descripcion'] ?? '',
    'path' => $element['path'] ?? '',
    'id_padre' => $element['id_padre'] ?? null,
    'hasChildren' => !empty($children)
];

$elementsData = [];
foreach ($elementsFlat as $flatElement) {
    $elementsData[] = [
        'id' => $flatElement['id'],
        'descripcion' => $flatElement['descripcion'],
        'path' => $flatElement['path']
    ];
}

// JavaScript externo con configuración
$scriptFiles = [
    '/plugins/select2/js/select2.full.min.js',
    '/assets/javascript/modules/organizational/edit.js'
];

$jsConfig = JavaScriptHelper::renderConfigScript([
    'baseUrl' => \App\Core\UrlHelper::base(),
    'element' => $elementData,
    'elements' => $elementsData
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