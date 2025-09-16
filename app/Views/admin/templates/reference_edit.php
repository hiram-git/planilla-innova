<?php
$page_title = 'Editar ' . $singular_name;

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Editar ' . $singular_name . '</h3>
                <div class="card-tools">
                    <a href="' . url('panel/' . $route_name, false) . '" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            <form action="' . url('panel/' . $route_name . '/' . $item['id'], false) . '" method="POST">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="' . $csrf_token . '">';

if (isset($_SESSION['errors'])) {
    $content .= '
                    <div class="alert alert-danger">
                        <ul class="mb-0">';
    foreach ($_SESSION['errors'] as $error) {
        $content .= '<li>' . htmlspecialchars($error) . '</li>';
    }
    $content .= '
                        </ul>
                    </div>';
    unset($_SESSION['errors']);
}

$content .= '
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_codigo">Código *</label>
                                <input type="text" class="form-control" id="edit_codigo" name="edit_codigo" 
                                       value="' . htmlspecialchars($item['codigo']) . '" required>
                                <small class="form-text text-muted">Código único del ' . strtolower($singular_name) . '</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_nombre">Nombre *</label>
                                <input type="text" class="form-control" id="edit_nombre" name="edit_nombre" 
                                       value="' . htmlspecialchars($item['nombre']) . '" required>
                                <small class="form-text text-muted">Nombre descriptivo del ' . strtolower($singular_name) . '</small>
                            </div>
                        </div>
                    </div>';

// Campos específicos para horarios
if ($route_name === 'schedules') {
    $content .= '
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_time_in">Hora de Entrada *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control timepicker" id="edit_time_in" name="edit_time_in" 
                                           value="' . $item['time_in'] . '" required>
                                    <div class="input-group-append">
                                        <div class="input-group-text"><i class="far fa-clock"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_time_out">Hora de Salida *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control timepicker" id="edit_time_out" name="edit_time_out" 
                                           value="' . $item['time_out'] . '" required>
                                    <div class="input-group-append">
                                        <div class="input-group-text"><i class="far fa-clock"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>';
}

$content .= '
                    <div class="form-group">
                        <label for="edit_descripcion">Descripción</label>
                        <textarea class="form-control" id="edit_descripcion" name="edit_descripcion" rows="3">' . htmlspecialchars($item['descripcion'] ?? '') . '</textarea>
                        <small class="form-text text-muted">Descripción opcional del ' . strtolower($singular_name) . '</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="edit_activo" name="edit_activo" ' . ($item['activo'] ? 'checked' : '') . '>
                            <label class="custom-control-label" for="edit_activo">' . $singular_name . ' activo</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Actualizar ' . $singular_name . '
                    </button>
                    <a href="' . url('panel/' . $route_name, false) . '" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>';

// JavaScript para horarios si es necesario
if ($route_name === 'schedules') {
    $scripts = '
    <script src="' . url('plugins/inputmask/jquery.inputmask.min.js', false) . '"></script>
    <script>
    $(document).ready(function() {
        $(".timepicker").inputmask("99:99", {
            placeholder: "__:__",
            insertMode: false,
            showMaskOnHover: false,
            showMaskOnFocus: true
        });
    });
    </script>';
} else {
    $scripts = '';
}

unset($_SESSION['old_data']);

$styles = '';

include __DIR__ . '/../../layouts/admin.php';
?>