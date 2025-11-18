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
                    <!-- Entrada al Trabajo -->
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header py-2">
                            <h3 class="card-title"><i class="fas fa-sign-in-alt"></i> Entrada al Trabajo</h3>
                        </div>
                        <div class="card-body py-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
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
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="edit_time_in_tolerance_before">Tolerancia Antes <small class="text-muted">(min)</small></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="edit_time_in_tolerance_before"
                                                   name="edit_time_in_tolerance_before" min="0" max="60" step="1"
                                                   value="' . (int)($item['time_in_tolerance_before'] ?? 0) . '">
                                            <div class="input-group-append">
                                                <div class="input-group-text"><i class="fas fa-stopwatch"></i></div>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Puede marcar X min antes</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="edit_time_in_tolerance_after">Gracia Tardanza <small class="text-muted">(min)</small></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="edit_time_in_tolerance_after"
                                                   name="edit_time_in_tolerance_after" min="0" max="60" step="1"
                                                   value="' . (int)($item['time_in_tolerance_after'] ?? 0) . '">
                                            <div class="input-group-append">
                                                <div class="input-group-text"><i class="fas fa-stopwatch"></i></div>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Sin marcar tardanza</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Salida del Trabajo -->
                    <div class="card card-outline card-danger mb-3">
                        <div class="card-header py-2">
                            <h3 class="card-title"><i class="fas fa-sign-out-alt"></i> Salida del Trabajo</h3>
                        </div>
                        <div class="card-body py-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
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
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="edit_time_out_tolerance_before">Tolerancia Antes <small class="text-muted">(min)</small></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="edit_time_out_tolerance_before"
                                                   name="edit_time_out_tolerance_before" min="0" max="60" step="1"
                                                   value="' . (int)($item['time_out_tolerance_before'] ?? 0) . '">
                                            <div class="input-group-append">
                                                <div class="input-group-text"><i class="fas fa-stopwatch"></i></div>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Puede salir X min antes</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="edit_time_out_tolerance_after">Tolerancia Después <small class="text-muted">(min)</small></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="edit_time_out_tolerance_after"
                                                   name="edit_time_out_tolerance_after" min="0" max="60" step="1"
                                                   value="' . (int)($item['time_out_tolerance_after'] ?? 0) . '">
                                            <div class="input-group-append">
                                                <div class="input-group-text"><i class="fas fa-stopwatch"></i></div>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Permitido después</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Salida a Almuerzo -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header py-2">
                            <h3 class="card-title"><i class="fas fa-utensils"></i> Salida a Almuerzo</h3>
                        </div>
                        <div class="card-body py-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="edit_salida_almuerzo">Hora Salida Almuerzo</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control timepicker" id="edit_salida_almuerzo" name="edit_salida_almuerzo"
                                                   value="' . ($item['salida_almuerzo'] ?? '') . '">
                                            <div class="input-group-append">
                                                <div class="input-group-text"><i class="far fa-clock"></i></div>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Opcional</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="edit_lunch_out_tolerance_before">Tolerancia Antes <small class="text-muted">(min)</small></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="edit_lunch_out_tolerance_before"
                                                   name="edit_lunch_out_tolerance_before" min="0" max="60" step="1"
                                                   value="' . (int)($item['lunch_out_tolerance_before'] ?? 0) . '">
                                            <div class="input-group-append">
                                                <div class="input-group-text"><i class="fas fa-stopwatch"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="edit_lunch_out_tolerance_after">Tolerancia Después <small class="text-muted">(min)</small></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="edit_lunch_out_tolerance_after"
                                                   name="edit_lunch_out_tolerance_after" min="0" max="60" step="1"
                                                   value="' . (int)($item['lunch_out_tolerance_after'] ?? 0) . '">
                                            <div class="input-group-append">
                                                <div class="input-group-text"><i class="fas fa-stopwatch"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Regreso de Almuerzo -->
                    <div class="card card-outline card-success mb-3">
                        <div class="card-header py-2">
                            <h3 class="card-title"><i class="fas fa-utensils"></i> Regreso de Almuerzo</h3>
                        </div>
                        <div class="card-body py-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="edit_entrada_almuerzo">Hora Regreso Almuerzo</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control timepicker" id="edit_entrada_almuerzo" name="edit_entrada_almuerzo"
                                                   value="' . ($item['entrada_almuerzo'] ?? '') . '">
                                            <div class="input-group-append">
                                                <div class="input-group-text"><i class="far fa-clock"></i></div>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Opcional</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="edit_lunch_in_tolerance_before">Tolerancia Antes <small class="text-muted">(min)</small></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="edit_lunch_in_tolerance_before"
                                                   name="edit_lunch_in_tolerance_before" min="0" max="60" step="1"
                                                   value="' . (int)($item['lunch_in_tolerance_before'] ?? 0) . '">
                                            <div class="input-group-append">
                                                <div class="input-group-text"><i class="fas fa-stopwatch"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="edit_lunch_in_tolerance_after">Gracia Tardanza <small class="text-muted">(min)</small></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="edit_lunch_in_tolerance_after"
                                                   name="edit_lunch_in_tolerance_after" min="0" max="60" step="1"
                                                   value="' . (int)($item['lunch_in_tolerance_after'] ?? 0) . '">
                                            <div class="input-group-append">
                                                <div class="input-group-text"><i class="fas fa-stopwatch"></i></div>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Sin marcar tardanza</small>
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