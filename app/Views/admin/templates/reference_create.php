<?php
$page_title = 'Agregar ' . $singular_name;

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Agregar ' . $singular_name . '</h3>
                <div class="card-tools">
                    <a href="' . url('panel/' . $route_name, false) . '" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            <form action="' . url('panel/' . $route_name, false) . '" method="POST">
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
                                <label for="codigo">Código *</label>
                                <input type="text" class="form-control" id="codigo" name="codigo" 
                                       value="' . ($_SESSION['old_data']['codigo'] ?? '') . '" required>
                                <small class="form-text text-muted">Código único del ' . strtolower($singular_name) . '</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="' . ($_SESSION['old_data']['nombre'] ?? '') . '" required>
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
                                <label for="time_in">Hora de Entrada *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control timepicker" id="time_in" name="time_in" 
                                           value="' . ($_SESSION['old_data']['time_in'] ?? '') . '" required>
                                    <div class="input-group-append">
                                        <div class="input-group-text"><i class="far fa-clock"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="time_out">Hora de Salida *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control timepicker" id="time_out" name="time_out" 
                                           value="' . ($_SESSION['old_data']['time_out'] ?? '') . '" required>
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
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3">' . ($_SESSION['old_data']['descripcion'] ?? '') . '</textarea>
                        <small class="form-text text-muted">Descripción opcional del ' . strtolower($singular_name) . '</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar ' . $singular_name . '
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