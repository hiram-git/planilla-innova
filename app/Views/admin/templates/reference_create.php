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
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="salida_almuerzo">Salida a Almuerzo</label>
                                <div class="input-group">
                                    <input type="text" class="form-control timepicker" id="salida_almuerzo" name="salida_almuerzo"
                                           value="' . ($_SESSION['old_data']['salida_almuerzo'] ?? '') . '">
                                    <div class="input-group-append">
                                        <div class="input-group-text"><i class="far fa-clock"></i></div>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Opcional - Hora programada para salir a almuerzo</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="entrada_almuerzo">Entrada de Almuerzo</label>
                                <div class="input-group">
                                    <input type="text" class="form-control timepicker" id="entrada_almuerzo" name="entrada_almuerzo"
                                           value="' . ($_SESSION['old_data']['entrada_almuerzo'] ?? '') . '">
                                    <div class="input-group-append">
                                        <div class="input-group-text"><i class="far fa-clock"></i></div>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Opcional - Hora programada para regresar de almuerzo</small>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Tolerancias -->
                    <div class="card card-outline card-info mt-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-stopwatch"></i> Configuración de Tolerancias (minutos)
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Tolerancias:</strong> Configure los minutos de tolerancia permitidos para cada tipo de marcación.
                                Un valor de 0 significa que no hay tolerancia. Valores por defecto: 0 minutos.
                            </div>

                            <!-- Tolerancias de Entrada -->
                            <h5 class="text-primary"><i class="fas fa-sign-in-alt"></i> Entrada al Trabajo</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="time_in_tolerance_before">Tolerancia Antes (minutos)</label>
                                        <input type="number" class="form-control" id="time_in_tolerance_before"
                                               name="time_in_tolerance_before" min="0" max="60" step="1"
                                               value="' . ($_SESSION['old_data']['time_in_tolerance_before'] ?? 0) . '">
                                        <small class="form-text text-muted">Minutos permitidos antes de la hora programada</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="time_in_tolerance_after">Tolerancia Después (minutos)</label>
                                        <input type="number" class="form-control" id="time_in_tolerance_after"
                                               name="time_in_tolerance_after" min="0" max="60" step="1"
                                               value="' . ($_SESSION['old_data']['time_in_tolerance_after'] ?? 0) . '">
                                        <small class="form-text text-muted">Minutos permitidos después sin marcar tardanza (gracia)</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Tolerancias de Salida -->
                            <h5 class="text-danger mt-3"><i class="fas fa-sign-out-alt"></i> Salida del Trabajo</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="time_out_tolerance_before">Tolerancia Antes (minutos)</label>
                                        <input type="number" class="form-control" id="time_out_tolerance_before"
                                               name="time_out_tolerance_before" min="0" max="60" step="1"
                                               value="' . ($_SESSION['old_data']['time_out_tolerance_before'] ?? 0) . '">
                                        <small class="form-text text-muted">Minutos permitidos para salir antes sin marcar salida anticipada</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="time_out_tolerance_after">Tolerancia Después (minutos)</label>
                                        <input type="number" class="form-control" id="time_out_tolerance_after"
                                               name="time_out_tolerance_after" min="0" max="60" step="1"
                                               value="' . ($_SESSION['old_data']['time_out_tolerance_after'] ?? 0) . '">
                                        <small class="form-text text-muted">Minutos permitidos después de la hora de salida</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Tolerancias de Almuerzo (Salida) -->
                            <h5 class="text-warning mt-3"><i class="fas fa-utensils"></i> Salida a Almuerzo</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lunch_out_tolerance_before">Tolerancia Antes (minutos)</label>
                                        <input type="number" class="form-control" id="lunch_out_tolerance_before"
                                               name="lunch_out_tolerance_before" min="0" max="60" step="1"
                                               value="' . ($_SESSION['old_data']['lunch_out_tolerance_before'] ?? 0) . '">
                                        <small class="form-text text-muted">Minutos permitidos antes de la hora de salida a almuerzo</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lunch_out_tolerance_after">Tolerancia Después (minutos)</label>
                                        <input type="number" class="form-control" id="lunch_out_tolerance_after"
                                               name="lunch_out_tolerance_after" min="0" max="60" step="1"
                                               value="' . ($_SESSION['old_data']['lunch_out_tolerance_after'] ?? 0) . '">
                                        <small class="form-text text-muted">Minutos permitidos después de la hora de salida a almuerzo</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Tolerancias de Almuerzo (Entrada) -->
                            <h5 class="text-success mt-3"><i class="fas fa-utensils"></i> Regreso de Almuerzo</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lunch_in_tolerance_before">Tolerancia Antes (minutos)</label>
                                        <input type="number" class="form-control" id="lunch_in_tolerance_before"
                                               name="lunch_in_tolerance_before" min="0" max="60" step="1"
                                               value="' . ($_SESSION['old_data']['lunch_in_tolerance_before'] ?? 0) . '">
                                        <small class="form-text text-muted">Minutos permitidos para regresar antes del almuerzo</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lunch_in_tolerance_after">Tolerancia Después (minutos)</label>
                                        <input type="number" class="form-control" id="lunch_in_tolerance_after"
                                               name="lunch_in_tolerance_after" min="0" max="60" step="1"
                                               value="' . ($_SESSION['old_data']['lunch_in_tolerance_after'] ?? 0) . '">
                                        <small class="form-text text-muted">Minutos permitidos después sin marcar tardanza de almuerzo</small>
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