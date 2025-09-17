<?php
$page_title = 'Agregar Empleado';

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Agregar Nuevo Empleado</h3>
                <div class="card-tools">
                    <a href="' . url('/panel/employees') . '" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            <form action="' . url('/panel/employees/store') . '" method="post" enctype="multipart/form-data">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="' . $csrf_token . '">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="firstname">Nombres *</label>
                                <input type="text" class="form-control" id="firstname" name="firstname" 
                                       value="' . ($_SESSION['old_data']['firstname'] ?? '') . '" required>
                                ' . (isset($_SESSION['errors']['firstname']) ? '<small class="text-danger">' . $_SESSION['errors']['firstname'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lastname">Apellidos *</label>
                                <input type="text" class="form-control" id="lastname" name="lastname" 
                                       value="' . ($_SESSION['old_data']['lastname'] ?? '') . '" required>
                                ' . (isset($_SESSION['errors']['lastname']) ? '<small class="text-danger">' . $_SESSION['errors']['lastname'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="document_id">Cédula *</label>
                                <input type="text" class="form-control" id="document_id" name="document_id" 
                                       value="' . ($_SESSION['old_data']['document_id'] ?? '') . '" required>
                                ' . (isset($_SESSION['errors']['document_id']) ? '<small class="text-danger">' . $_SESSION['errors']['document_id'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="clave_seguro_social">Clave Seguro Social</label>
                                <input type="text" class="form-control" id="clave_seguro_social" name="clave_seguro_social" 
                                       value="' . ($_SESSION['old_data']['clave_seguro_social'] ?? '') . '" 
                                       placeholder="Ej: 12-34-567890">
                                ' . (isset($_SESSION['errors']['clave_seguro_social']) ? '<small class="text-danger">' . $_SESSION['errors']['clave_seguro_social'] . '</small>' : '') . '
                                <small class="form-text text-muted">Opcional. Clave única del empleado en el seguro social</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="birthdate">Fecha de Nacimiento *</label>
                                <input type="date" class="form-control" id="birthdate" name="birthdate" 
                                       value="' . ($_SESSION['old_data']['birthdate'] ?? '') . '" required>
                                ' . (isset($_SESSION['errors']['birthdate']) ? '<small class="text-danger">' . $_SESSION['errors']['birthdate'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender">Género *</label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="M"' . (($_SESSION['old_data']['gender'] ?? '') === 'M' ? ' selected' : '') . '>Masculino</option>
                                    <option value="F"' . (($_SESSION['old_data']['gender'] ?? '') === 'F' ? ' selected' : '') . '>Femenino</option>
                                </select>
                                ' . (isset($_SESSION['errors']['gender']) ? '<small class="text-danger">' . $_SESSION['errors']['gender'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_ingreso">Fecha de Ingreso</label>
                                <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" 
                                       value="' . ($_SESSION['old_data']['fecha_ingreso'] ?? date('Y-m-d')) . '">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Dirección</label>
                        <textarea class="form-control" id="address" name="address" rows="2">' . ($_SESSION['old_data']['address'] ?? '') . '</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact">Información de Contacto</label>
                        <input type="text" class="form-control" id="contact" name="contact" 
                               placeholder="Teléfono, email, etc." value="' . ($_SESSION['old_data']['contact'] ?? '') . '">
                    </div>
                    
                    <!-- Campos condicionales según tipo de empresa -->
                    <div id="public-institution-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="position">Posición *</label>
                                    <select class="form-control" id="position" name="position">
                                        <option value="">Seleccionar posición...</option>';

foreach ($positions as $position) {
    $selected = ($_SESSION['old_data']['position'] ?? '') == $position['id'] ? ' selected' : '';
    $content .= '<option value="' . $position['id'] . '"' . $selected . '>' . htmlspecialchars($position['codigo']) . '</option>';
}

$content .= '                    </select>
                                    ' . (isset($_SESSION['errors']['position']) ? '<small class="text-danger">' . $_SESSION['errors']['position'] . '</small>' : '') . '
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Institución Pública:</strong><br>
                                    La posición determina el sueldo según el presupuesto aprobado.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="private-company-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cargo_id">Cargo *</label>
                                    <select class="form-control" id="cargo_id" name="cargo_id">
                                        <option value="">Seleccionar cargo...</option>';

foreach ($cargos as $cargo) {
    $selected = ($_SESSION['old_data']['cargo_id'] ?? '') == $cargo['id'] ? ' selected' : '';
    $displayText = htmlspecialchars($cargo['codigo'] . ' - ' . $cargo['nombre']);
    $content .= '<option value="' . $cargo['id'] . '"' . $selected . '>' . $displayText . '</option>';
}

$content .= '                    </select>
                                    ' . (isset($_SESSION['errors']['cargo_id']) ? '<small class="text-danger">' . $_SESSION['errors']['cargo_id'] . '</small>' : '') . '
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="funcion_id">Función *</label>
                                    <select class="form-control" id="funcion_id" name="funcion_id">
                                        <option value="">Seleccionar función...</option>';

foreach ($funciones as $funcion) {
    $selected = ($_SESSION['old_data']['funcion_id'] ?? '') == $funcion['id'] ? ' selected' : '';
    $displayText = htmlspecialchars($funcion['codigo'] . ' - ' . $funcion['nombre']);
    $content .= '<option value="' . $funcion['id'] . '"' . $selected . '>' . $displayText . '</option>';
}

$content .= '                    </select>
                                    ' . (isset($_SESSION['errors']['funcion_id']) ? '<small class="text-danger">' . $_SESSION['errors']['funcion_id'] . '</small>' : '') . '
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="partida_id">Partida *</label>
                                    <select class="form-control" id="partida_id" name="partida_id">
                                        <option value="">Seleccionar partida...</option>';

foreach ($partidas as $partida) {
    $selected = ($_SESSION['old_data']['partida_id'] ?? '') == $partida['id'] ? ' selected' : '';
    $displayText = htmlspecialchars($partida['codigo'] . ' - ' . $partida['nombre']);
    $content .= '<option value="' . $partida['id'] . '"' . $selected . '>' . $displayText . '</option>';
}

$content .= '                    </select>
                                    ' . (isset($_SESSION['errors']['partida_id']) ? '<small class="text-danger">' . $_SESSION['errors']['partida_id'] . '</small>' : '') . '
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campo Horario (siempre visible) -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="schedule">Horario *</label>
                                <select class="form-control" id="schedule" name="schedule" required>
                                    <option value="">Seleccionar horario...</option>';

foreach ($schedules as $schedule) {
    $selected = ($_SESSION['old_data']['schedule'] ?? '') == $schedule['id'] ? ' selected' : '';
    $scheduleText = date('h:i A', strtotime($schedule['time_in'])) . ' - ' . date('h:i A', strtotime($schedule['time_out']));
    $content .= '<option value="' . $schedule['id'] . '"' . $selected . '>' . htmlspecialchars($scheduleText) . '</option>';
}

$content .= '                </select>
                                ' . (isset($_SESSION['errors']['schedule']) ? '<small class="text-danger">' . $_SESSION['errors']['schedule'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Espacio para simetría -->
                        </div>
                    </div>
                    
                    <!-- Campo condicional: Sueldo Individual para empresas privadas -->
                    <div class="row" id="salary-section" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sueldo_individual">Sueldo Individual *</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">' . currency_symbol() . '</span>
                                    </div>
                                    <input type="number" class="form-control" id="sueldo_individual" name="sueldo_individual" 
                                           step="0.01" min="0" placeholder="0.00"
                                           value="' . ($_SESSION['old_data']['sueldo_individual'] ?? '') . '">
                                </div>
                                <small class="form-text text-muted">Sueldo específico para este empleado en empresa privada</small>
                                ' . (isset($_SESSION['errors']['sueldo_individual']) ? '<small class="text-danger">' . $_SESSION['errors']['sueldo_individual'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Empresa Privada:</strong><br>
                                El sueldo se asigna individualmente a cada empleado.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="situacion">Situación Laboral *</label>
                                <select class="form-control" id="situacion" name="situacion" required>
                                    <option value="">Seleccionar situación...</option>';
foreach ($situaciones as $situacion) {
    $selected = ($_SESSION['old_data']['situacion'] ?? '1') == $situacion['id'] ? ' selected' : '';
    $content .= '<option value="' . $situacion['id'] . '"' . $selected . '>' . htmlspecialchars($situacion['descripcion']) . '</option>';
}
$content .= '                </select>
                                ' . (isset($_SESSION['errors']['situacion']) ? '<small class="text-danger">' . $_SESSION['errors']['situacion'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_planilla">Tipo de Planilla *</label>
                                <select class="form-control" id="tipo_planilla" name="tipo_planilla" required>
                                    <option value="">Seleccionar tipo...</option>';
foreach ($tipos_planilla as $tipo) {
    // Prioridad: old_data > primer registro activo (will be overridden by JavaScript)
    $selected = ($_SESSION['old_data']['tipo_planilla'] ?? ($tipo['id'] ?? '')) == $tipo['id'] ? ' selected' : '';
    $content .= '<option value="' . $tipo['id'] . '"' . $selected . '>' . htmlspecialchars($tipo['descripcion']) . '</option>';
}
$content .= '                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-sync-alt"></i> Se sincroniza automáticamente con el tipo seleccionado en el navbar
                                </small>
                                ' . (isset($_SESSION['errors']['tipo_planilla']) ? '<small class="text-danger">' . $_SESSION['errors']['tipo_planilla'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="photo">Foto del Empleado</label>
                        <input type="file" class="form-control-file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif">
                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB</small>
                        <div id="photo-preview" style="display: none;" class="mt-2">
                            <img src="" alt="Vista previa" style="max-width: 200px; max-height: 200px;" class="img-thumbnail">
                            <p class="text-muted small mt-1">Vista previa de la foto</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="organigrama_id">Elemento del Organigrama</label>
                        <select class="form-control" id="organigrama_id" name="organigrama_id">
                            <option value="">Seleccionar elemento del organigrama...</option>';

foreach ($organigrama_elementos as $elemento) {
    $selected = ($_SESSION['old_data']['organigrama_id'] ?? '') == $elemento['id'] ? ' selected' : '';
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;', substr_count($elemento['path'] ?? '', '/'));
    $content .= '<option value="' . $elemento['id'] . '"' . $selected . '>' . $indent . htmlspecialchars($elemento['descripcion']) . '</option>';
}

$content .= '                        </select>
                                    <small class="form-text text-muted">Opcional. Elemento del organigrama al que pertenece el empleado</small>
                                    ' . (isset($_SESSION['errors']['organigrama_id']) ? '<small class="text-danger">' . $_SESSION['errors']['organigrama_id'] . '</small>' : '') . '
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Empleado
                    </button>
                    <a href="' . url('/panel/employees') . '" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>';

// Configuración JavaScript para el módulo
$scripts = '
<script>
// Configuración global para el módulo de empleados
window.APP_CONFIG = window.APP_CONFIG || {};
window.APP_CONFIG.company = {
    tipo_institucion: "' . ($company_config['tipo_institucion'] ?? 'privada') . '"
};
window.APP_CONFIG.config = window.APP_CONFIG.config || {};
window.APP_CONFIG.config.csrf_token = "' . ($csrf_token ?? '') . '";
</script>
<script src="' . url('assets/javascript/modules/employees/create.js', false) . '"></script>';

$styles = '';

// Limpiar mensajes de sesión
unset($_SESSION['errors'], $_SESSION['old_data']);

include __DIR__ . '/../../layouts/admin.php';
?>