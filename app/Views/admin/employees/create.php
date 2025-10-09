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
                                <label for="clave_seguro_social">
                                    Número de Seguro Social *
                                    <button type="button" class="btn btn-xs btn-outline-secondary ml-2" id="btn_copy_cedula" title="Copiar número de cédula">
                                        <i class="fas fa-copy"></i> Usar misma cédula
                                    </button>
                                </label>
                                <input type="text" class="form-control" id="clave_seguro_social" name="clave_seguro_social"
                                       value="' . ($_SESSION['old_data']['clave_seguro_social'] ?? '') . '"
                                       placeholder="Ej: 8-123-456 o número asignado" required>
                                ' . (isset($_SESSION['errors']['clave_seguro_social']) ? '<small class="text-danger">' . $_SESSION['errors']['clave_seguro_social'] . '</small>' : '') . '
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle text-info"></i>
                                    En Panamá, generalmente es el mismo número de cédula. Si tiene número propio, ingréselo manualmente.
                                </small>
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
                                <div class="callout callout-info">
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
                            <div class="form-group">
                                <label for="gastos_representacion">Gastos de Representación</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">' . currency_symbol() . '</span>
                                    </div>
                                    <input type="number" class="form-control" id="gastos_representacion" name="gastos_representacion"
                                           step="0.01" min="0" placeholder="0.00"
                                           value="' . ($_SESSION['old_data']['gastos_representacion'] ?? '') . '">
                                </div>
                                <small class="form-text text-muted">Gastos de representación asignados al empleado</small>
                                ' . (isset($_SESSION['errors']['gastos_representacion']) ? '<small class="text-danger">' . $_SESSION['errors']['gastos_representacion'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="callout callout-info">
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
                                <label for="tipo_planilla">Tipos de Planilla *</label>
                                <select class="form-control" id="tipo_planilla" name="tipo_planilla[]" multiple="multiple" required style="width: 100%;">
';
// Obtener valores seleccionados previamente (pueden venir como string separado por comas o como array)
$selectedValues = [];
if (isset($_SESSION['old_data']['tipo_planilla'])) {
    $oldData = $_SESSION['old_data']['tipo_planilla'];
    if (is_array($oldData)) {
        $selectedValues = $oldData;
    } else if (is_string($oldData) && !empty($oldData)) {
        $selectedValues = explode(',', $oldData);
    }
}

foreach ($tipos_planilla as $tipo) {
    $selected = in_array($tipo['id'], $selectedValues) ? ' selected' : '';
    $content .= '<option value="' . $tipo['id'] . '"' . $selected . '>' . htmlspecialchars($tipo['descripcion']) . '</option>';
}
$content .= '                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-list-ul"></i> Puede seleccionar múltiples tipos de planilla para este empleado
                                </small>
                                ' . (isset($_SESSION['errors']['tipo_planilla']) ? '<small class="text-danger">' . $_SESSION['errors']['tipo_planilla'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>

                    <!-- Campos de Contrato -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_contrato">Tipo de Contrato *</label>
                                <select class="form-control" id="tipo_contrato" name="tipo_contrato" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="INDEFINIDO"' . (($_SESSION['old_data']['tipo_contrato'] ?? 'INDEFINIDO') == 'INDEFINIDO' ? ' selected' : '') . '>Indefinido</option>
                                    <option value="DEFINIDO"' . (($_SESSION['old_data']['tipo_contrato'] ?? '') == 'DEFINIDO' ? ' selected' : '') . '>Definido</option>
                                    <option value="PROYECTO"' . (($_SESSION['old_data']['tipo_contrato'] ?? '') == 'PROYECTO' ? ' selected' : '') . '>Por Proyecto</option>
                                    <option value="TEMPORAL"' . (($_SESSION['old_data']['tipo_contrato'] ?? '') == 'TEMPORAL' ? ' selected' : '') . '>Temporal</option>
                                </select>
                                ' . (isset($_SESSION['errors']['tipo_contrato']) ? '<small class="text-danger">' . $_SESSION['errors']['tipo_contrato'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6" id="contract-number-section">
                            <div class="form-group">
                                <label for="numero_contrato">Número de Contrato</label>
                                <input type="text" class="form-control" id="numero_contrato" name="numero_contrato"
                                       placeholder="Ej: CT-2025-001" maxlength="50"
                                       value="' . ($_SESSION['old_data']['numero_contrato'] ?? '') . '">
                                <small class="form-text text-muted">Número identificador del contrato</small>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="contract-dates-section">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_inicio_contrato">Fecha Inicio Contrato</label>
                                <input type="date" class="form-control" id="fecha_inicio_contrato" name="fecha_inicio_contrato"
                                       value="' . ($_SESSION['old_data']['fecha_inicio_contrato'] ?? '') . '">
                                <small class="form-text text-muted">Fecha de inicio del contrato actual</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_vencimiento_contrato">Fecha Vencimiento Contrato</label>
                                <input type="date" class="form-control" id="fecha_vencimiento_contrato" name="fecha_vencimiento_contrato"
                                       value="' . ($_SESSION['old_data']['fecha_vencimiento_contrato'] ?? '') . '">
                                <small class="form-text text-muted">Requerido para contratos definidos, por proyecto o temporales</small>
                                ' . (isset($_SESSION['errors']['fecha_vencimiento_contrato']) ? '<small class="text-danger">' . $_SESSION['errors']['fecha_vencimiento_contrato'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>

                    <!-- Campos de Forma de Pago -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="forma_pago">Forma de Pago *</label>
                                <select class="form-control" id="forma_pago" name="forma_pago" required>
                                    <option value="">Seleccionar forma...</option>
                                    <option value="EFECTIVO"' . (($_SESSION['old_data']['forma_pago'] ?? 'EFECTIVO') == 'EFECTIVO' ? ' selected' : '') . '>Efectivo</option>
                                    <option value="CHEQUE"' . (($_SESSION['old_data']['forma_pago'] ?? '') == 'CHEQUE' ? ' selected' : '') . '>Cheque</option>
                                    <option value="ACH"' . (($_SESSION['old_data']['forma_pago'] ?? '') == 'ACH' ? ' selected' : '') . '>ACH (Transferencia)</option>
                                </select>
                                ' . (isset($_SESSION['errors']['forma_pago']) ? '<small class="text-danger">' . $_SESSION['errors']['forma_pago'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_cuenta">Tipo de Cuenta</label>
                                <select class="form-control" id="tipo_cuenta" name="tipo_cuenta">
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="AHORROS"' . (($_SESSION['old_data']['tipo_cuenta'] ?? '') == 'AHORROS' ? ' selected' : '') . '>Ahorros</option>
                                    <option value="CORRIENTE"' . (($_SESSION['old_data']['tipo_cuenta'] ?? '') == 'CORRIENTE' ? ' selected' : '') . '>Corriente</option>
                                </select>
                                <small class="form-text text-muted">Requerido para cheque y ACH</small>
                                ' . (isset($_SESSION['errors']['tipo_cuenta']) ? '<small class="text-danger">' . $_SESSION['errors']['tipo_cuenta'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>

                    <div class="row" id="bank-details" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="banco">Banco *</label>
                                <input type="text" class="form-control" id="banco" name="banco"
                                       placeholder="Ej: Banco General" maxlength="100"
                                       value="' . ($_SESSION['old_data']['banco'] ?? '') . '">
                                <small class="form-text text-muted">Nombre del banco para cheque o ACH</small>
                                ' . (isset($_SESSION['errors']['banco']) ? '<small class="text-danger">' . $_SESSION['errors']['banco'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="numero_cuenta">Número de Cuenta *</label>
                                <input type="text" class="form-control" id="numero_cuenta" name="numero_cuenta"
                                       placeholder="Ej: 03-01-01-123456789" maxlength="50"
                                       value="' . ($_SESSION['old_data']['numero_cuenta'] ?? '') . '">
                                <small class="form-text text-muted">Número de cuenta bancaria</small>
                                ' . (isset($_SESSION['errors']['numero_cuenta']) ? '<small class="text-danger">' . $_SESSION['errors']['numero_cuenta'] . '</small>' : '') . '
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

// Funcionalidad copiar cédula a seguro social
$(document).ready(function() {
    $("#btn_copy_cedula").click(function() {
        var cedula = $("#document_id").val().trim();
        if (cedula) {
            $("#clave_seguro_social").val(cedula);
            toastr.success("Número de cédula copiado al seguro social", "Copiado");
        } else {
            toastr.warning("Primero ingrese el número de cédula", "Advertencia");
        }
    });

    // Auto-copiar cédula al seguro social si está vacío
    $("#document_id").on("blur", function() {
        var cedula = $(this).val().trim();
        var seguro = $("#clave_seguro_social").val().trim();
        if (cedula && !seguro) {
            $("#clave_seguro_social").val(cedula);
        }
    });
});
</script>
<script src="' . url('assets/javascript/modules/employees/create.js', false) . '"></script>';

$styles = '';

// Limpiar mensajes de sesión
unset($_SESSION['errors'], $_SESSION['old_data']);

include __DIR__ . '/../../layouts/admin.php';
?>