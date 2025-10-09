<?php
$page_title = 'Editar Empleado';

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Editar Empleado: ' . htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) . '</h3>
                <div class="card-tools">
                    <a href="' . url('/panel/employees') . '" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            <form action="' . url('/panel/employees/' . $employee['id'] . '/update') . '" method="post" enctype="multipart/form-data">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="' . $csrf_token . '">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_firstname">Nombres *</label>
                                <input type="text" class="form-control" id="edit_firstname" name="edit_firstname" 
                                       value="' . ($_SESSION['old_data']['edit_firstname'] ?? $employee['firstname']) . '" required>
                                ' . (isset($_SESSION['errors']['edit_firstname']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_firstname'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_lastname">Apellidos *</label>
                                <input type="text" class="form-control" id="edit_lastname" name="edit_lastname" 
                                       value="' . ($_SESSION['old_data']['edit_lastname'] ?? $employee['lastname']) . '" required>
                                ' . (isset($_SESSION['errors']['edit_lastname']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_lastname'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_document_id">Cédula *</label>
                                <input type="text" class="form-control" id="edit_document_id" name="edit_document_id" 
                                       value="' . ($_SESSION['old_data']['edit_document_id'] ?? $employee['document_id']) . '" required>
                                ' . (isset($_SESSION['errors']['edit_document_id']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_document_id'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_clave_seguro_social">
                                    Número de Seguro Social *
                                    <button type="button" class="btn btn-xs btn-outline-secondary ml-2" id="btn_copy_cedula_edit" title="Copiar número de cédula">
                                        <i class="fas fa-copy"></i> Usar misma cédula
                                    </button>
                                </label>
                                <input type="text" class="form-control" id="edit_clave_seguro_social" name="edit_clave_seguro_social"
                                       value="' . ($_SESSION['old_data']['edit_clave_seguro_social'] ?? ($employee['clave_seguro_social'] ?? '')) . '"
                                       placeholder="Ej: 8-123-456 o número asignado" required>
                                ' . (isset($_SESSION['errors']['edit_clave_seguro_social']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_clave_seguro_social'] . '</small>' : '') . '
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
                                <label for="edit_birthdate">Fecha de Nacimiento *</label>
                                <input type="date" class="form-control" id="edit_birthdate" name="edit_birthdate" 
                                       value="' . ($_SESSION['old_data']['edit_birthdate'] ?? $employee['birthdate']) . '" required>
                                ' . (isset($_SESSION['errors']['edit_birthdate']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_birthdate'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_gender">Género *</label>
                                <select class="form-control" id="edit_gender" name="edit_gender" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="M"' . (($_SESSION['old_data']['edit_gender'] ?? $employee['gender']) === 'M' ? ' selected' : '') . '>Masculino</option>
                                    <option value="F"' . (($_SESSION['old_data']['edit_gender'] ?? $employee['gender']) === 'F' ? ' selected' : '') . '>Femenino</option>
                                </select>
                                ' . (isset($_SESSION['errors']['edit_gender']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_gender'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_fecha_ingreso">Fecha de Ingreso</label>
                                <input type="date" class="form-control" id="edit_fecha_ingreso" name="edit_fecha_ingreso" 
                                       value="' . ($_SESSION['old_data']['edit_fecha_ingreso'] ?? $employee['fecha_ingreso']) . '">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_address">Dirección</label>
                        <textarea class="form-control" id="edit_address" name="edit_address" rows="2">' . ($_SESSION['old_data']['edit_address'] ?? $employee['address']) . '</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_contact">Información de Contacto</label>
                        <input type="text" class="form-control" id="edit_contact" name="edit_contact" 
                               placeholder="Teléfono, email, etc." value="' . ($_SESSION['old_data']['edit_contact'] ?? $employee['contact_info']) . '">
                    </div>
                    
                    <!-- Campos condicionales según tipo de empresa -->
                    <div id="edit-public-institution-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_position">Posición *</label>
                                    <select class="form-control" id="edit_position" name="edit_position">
                                        <option value="">Seleccionar posición...</option>';

foreach ($positions as $position) {
    $selected = ($_SESSION['old_data']['edit_position'] ?? $employee['position_id']) == $position['id'] ? ' selected' : '';
    $content .= '<option value="' . $position['id'] . '"' . $selected . '>' . htmlspecialchars($position['codigo']) . '</option>';
}

$content .= '                    </select>
                                    ' . (isset($_SESSION['errors']['edit_position']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_position'] . '</small>' : '') . '
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
                    
                    <div id="edit-private-company-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_cargo_id">Cargo *</label>
                                    <select class="form-control" id="edit_cargo_id" name="edit_cargo_id">
                                        <option value="">Seleccionar cargo...</option>';

foreach ($cargos as $cargo) {
    $selected = ($_SESSION['old_data']['edit_cargo_id'] ?? $employee['cargo_id']) == $cargo['id'] ? ' selected' : '';
    $displayText = htmlspecialchars($cargo['codigo'] . ' - ' . $cargo['nombre']);
    $content .= '<option value="' . $cargo['id'] . '"' . $selected . '>' . $displayText . '</option>';
}

$content .= '                    </select>
                                    ' . (isset($_SESSION['errors']['edit_cargo_id']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_cargo_id'] . '</small>' : '') . '
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_funcion_id">Función *</label>
                                    <select class="form-control" id="edit_funcion_id" name="edit_funcion_id">
                                        <option value="">Seleccionar función...</option>';

foreach ($funciones as $funcion) {
    $selected = ($_SESSION['old_data']['edit_funcion_id'] ?? $employee['funcion_id']) == $funcion['id'] ? ' selected' : '';
    $displayText = htmlspecialchars($funcion['codigo'] . ' - ' . $funcion['nombre']);
    $content .= '<option value="' . $funcion['id'] . '"' . $selected . '>' . $displayText . '</option>';
}

$content .= '                    </select>
                                    ' . (isset($_SESSION['errors']['edit_funcion_id']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_funcion_id'] . '</small>' : '') . '
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_partida_id">Partida *</label>
                                    <select class="form-control" id="edit_partida_id" name="edit_partida_id">
                                        <option value="">Seleccionar partida...</option>';

foreach ($partidas as $partida) {
    $selected = ($_SESSION['old_data']['edit_partida_id'] ?? $employee['partida_id']) == $partida['id'] ? ' selected' : '';
    $displayText = htmlspecialchars($partida['codigo'] . ' - ' . $partida['nombre']);
    $content .= '<option value="' . $partida['id'] . '"' . $selected . '>' . $displayText . '</option>';
}

$content .= '                    </select>
                                    ' . (isset($_SESSION['errors']['edit_partida_id']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_partida_id'] . '</small>' : '') . '
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campo Horario (siempre visible) -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_schedule">Horario *</label>
                                <select class="form-control" id="edit_schedule" name="edit_schedule" required>
                                    <option value="">Seleccionar horario...</option>';

foreach ($schedules as $schedule) {
    $selected = ($_SESSION['old_data']['edit_schedule'] ?? $employee['schedule_id']) == $schedule['id'] ? ' selected' : '';
    $scheduleText = date('h:i A', strtotime($schedule['time_in'])) . ' - ' . date('h:i A', strtotime($schedule['time_out']));
    $content .= '<option value="' . $schedule['id'] . '"' . $selected . '>' . htmlspecialchars($scheduleText) . '</option>';
}

$content .= '                </select>
                                ' . (isset($_SESSION['errors']['edit_schedule']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_schedule'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Espacio para simetría -->
                        </div>
                    </div>
                    
                    <!-- Campo condicional: Sueldo Individual para empresas privadas -->
                    <div class="row" id="edit-salary-section" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_sueldo_individual">Sueldo Individual *</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">' . currency_symbol() . '</span>
                                    </div>
                                    <input type="number" class="form-control" id="edit_sueldo_individual" name="edit_sueldo_individual"
                                           step="0.01" min="0" placeholder="0.00"
                                           value="' . ($_SESSION['old_data']['edit_sueldo_individual'] ?? ($employee['sueldo_individual'] ?? '')) . '">
                                </div>
                                <small class="form-text text-muted">Sueldo específico para este empleado en empresa privada</small>
                                ' . (isset($_SESSION['errors']['edit_sueldo_individual']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_sueldo_individual'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_gastos_representacion">Gastos de Representación</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">' . currency_symbol() . '</span>
                                    </div>
                                    <input type="number" class="form-control" id="edit_gastos_representacion" name="edit_gastos_representacion"
                                           step="0.01" min="0" placeholder="0.00"
                                           value="' . ($_SESSION['old_data']['edit_gastos_representacion'] ?? ($employee['gastos_representacion'] ?? '')) . '">
                                </div>
                                <small class="form-text text-muted">Gastos de representación asignados al empleado</small>
                                ' . (isset($_SESSION['errors']['edit_gastos_representacion']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_gastos_representacion'] . '</small>' : '') . '
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
                                <label for="edit_situacion">Situación Laboral *</label>
                                <select class="form-control" id="edit_situacion" name="edit_situacion" required>
                                    <option value="">Seleccionar situación...</option>';
foreach ($situaciones as $situacion) {
    $selected = ($_SESSION['old_data']['edit_situacion'] ?? ($employee['situacion_id'] ?? '')) == $situacion['id'] ? ' selected' : '';
    $content .= '<option value="' . $situacion['id'] . '"' . $selected . '>' . htmlspecialchars($situacion['descripcion']) . '</option>';
}
$content .= '                </select>
                                ' . (isset($_SESSION['errors']['edit_situacion']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_situacion'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_tipo_planilla">Tipos de Planilla *</label>
                                <select class="form-control" id="edit_tipo_planilla" name="edit_tipo_planilla[]" multiple="multiple" required style="width: 100%;">
';
// Obtener valores seleccionados previamente (pueden venir como string separado por comas o como array)
$selectedValues = [];
if (isset($_SESSION['old_data']['edit_tipo_planilla'])) {
    $oldData = $_SESSION['old_data']['edit_tipo_planilla'];
    if (is_array($oldData)) {
        $selectedValues = $oldData;
    } else if (is_string($oldData) && !empty($oldData)) {
        $selectedValues = explode(',', $oldData);
    }
} else if (!empty($employee['tipo_planilla_id'])) {
    // Si viene de la BD, puede ser un string separado por comas
    if (is_string($employee['tipo_planilla_id'])) {
        $selectedValues = explode(',', $employee['tipo_planilla_id']);
    } else {
        $selectedValues = [$employee['tipo_planilla_id']];
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
                                ' . (isset($_SESSION['errors']['edit_tipo_planilla']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_tipo_planilla'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>

                    <!-- Campos de Contrato -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_tipo_contrato">Tipo de Contrato *</label>
                                <select class="form-control" id="edit_tipo_contrato" name="edit_tipo_contrato" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="INDEFINIDO"' . (($_SESSION['old_data']['edit_tipo_contrato'] ?? ($employee['tipo_contrato'] ?? 'INDEFINIDO')) == 'INDEFINIDO' ? ' selected' : '') . '>Indefinido</option>
                                    <option value="DEFINIDO"' . (($_SESSION['old_data']['edit_tipo_contrato'] ?? ($employee['tipo_contrato'] ?? '')) == 'DEFINIDO' ? ' selected' : '') . '>Definido</option>
                                    <option value="PROYECTO"' . (($_SESSION['old_data']['edit_tipo_contrato'] ?? ($employee['tipo_contrato'] ?? '')) == 'PROYECTO' ? ' selected' : '') . '>Por Proyecto</option>
                                    <option value="TEMPORAL"' . (($_SESSION['old_data']['edit_tipo_contrato'] ?? ($employee['tipo_contrato'] ?? '')) == 'TEMPORAL' ? ' selected' : '') . '>Temporal</option>
                                </select>
                                ' . (isset($_SESSION['errors']['edit_tipo_contrato']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_tipo_contrato'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6" id="edit-contract-number-section">
                            <div class="form-group">
                                <label for="edit_numero_contrato">Número de Contrato</label>
                                <input type="text" class="form-control" id="edit_numero_contrato" name="edit_numero_contrato"
                                       placeholder="Ej: CT-2025-001" maxlength="50"
                                       value="' . ($_SESSION['old_data']['edit_numero_contrato'] ?? ($employee['numero_contrato'] ?? '')) . '">
                                <small class="form-text text-muted">Número identificador del contrato</small>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="edit-contract-dates-section">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_fecha_inicio_contrato">Fecha Inicio Contrato</label>
                                <input type="date" class="form-control" id="edit_fecha_inicio_contrato" name="edit_fecha_inicio_contrato"
                                       value="' . ($_SESSION['old_data']['edit_fecha_inicio_contrato'] ?? ($employee['fecha_inicio_contrato'] ?? '')) . '">
                                <small class="form-text text-muted">Fecha de inicio del contrato actual</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_fecha_vencimiento_contrato">Fecha Vencimiento Contrato</label>
                                <input type="date" class="form-control" id="edit_fecha_vencimiento_contrato" name="edit_fecha_vencimiento_contrato"
                                       value="' . ($_SESSION['old_data']['edit_fecha_vencimiento_contrato'] ?? ($employee['fecha_vencimiento_contrato'] ?? '')) . '">
                                <small class="form-text text-muted">Requerido para contratos definidos, por proyecto o temporales</small>
                                ' . (isset($_SESSION['errors']['edit_fecha_vencimiento_contrato']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_fecha_vencimiento_contrato'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>

                    <!-- Campos de Forma de Pago -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_forma_pago">Forma de Pago *</label>
                                <select class="form-control" id="edit_forma_pago" name="edit_forma_pago" required>
                                    <option value="">Seleccionar forma...</option>
                                    <option value="EFECTIVO"' . (($_SESSION['old_data']['edit_forma_pago'] ?? ($employee['forma_pago'] ?? 'EFECTIVO')) == 'EFECTIVO' ? ' selected' : '') . '>Efectivo</option>
                                    <option value="CHEQUE"' . (($_SESSION['old_data']['edit_forma_pago'] ?? ($employee['forma_pago'] ?? '')) == 'CHEQUE' ? ' selected' : '') . '>Cheque</option>
                                    <option value="ACH"' . (($_SESSION['old_data']['edit_forma_pago'] ?? ($employee['forma_pago'] ?? '')) == 'ACH' ? ' selected' : '') . '>ACH (Transferencia)</option>
                                </select>
                                ' . (isset($_SESSION['errors']['edit_forma_pago']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_forma_pago'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_tipo_cuenta">Tipo de Cuenta</label>
                                <select class="form-control" id="edit_tipo_cuenta" name="edit_tipo_cuenta">
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="AHORROS"' . (($_SESSION['old_data']['edit_tipo_cuenta'] ?? ($employee['tipo_cuenta'] ?? '')) == 'AHORROS' ? ' selected' : '') . '>Ahorros</option>
                                    <option value="CORRIENTE"' . (($_SESSION['old_data']['edit_tipo_cuenta'] ?? ($employee['tipo_cuenta'] ?? '')) == 'CORRIENTE' ? ' selected' : '') . '>Corriente</option>
                                </select>
                                <small class="form-text text-muted">Requerido para cheque y ACH</small>
                                ' . (isset($_SESSION['errors']['edit_tipo_cuenta']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_tipo_cuenta'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>

                    <div class="row" id="edit-bank-details" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_banco">Banco *</label>
                                <input type="text" class="form-control" id="edit_banco" name="edit_banco"
                                       placeholder="Ej: Banco General" maxlength="100"
                                       value="' . ($_SESSION['old_data']['edit_banco'] ?? ($employee['banco'] ?? '')) . '">
                                <small class="form-text text-muted">Nombre del banco para cheque o ACH</small>
                                ' . (isset($_SESSION['errors']['edit_banco']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_banco'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_numero_cuenta">Número de Cuenta *</label>
                                <input type="text" class="form-control" id="edit_numero_cuenta" name="edit_numero_cuenta"
                                       placeholder="Ej: 03-01-01-123456789" maxlength="50"
                                       value="' . ($_SESSION['old_data']['edit_numero_cuenta'] ?? ($employee['numero_cuenta'] ?? '')) . '">
                                <small class="form-text text-muted">Número de cuenta bancaria</small>
                                ' . (isset($_SESSION['errors']['edit_numero_cuenta']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_numero_cuenta'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_photo">Foto del Empleado</label>
                                <input type="file" class="form-control-file" id="edit_photo" name="edit_photo" accept="image/*">
                                <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB</small>';

if (!empty($employee['photo'])) {
    $photoUrl = url('images/' . $employee['photo'], false);
    $content .= '<div class="mt-2">
                                    <img src="' . $photoUrl . '" alt="Foto actual" 
                                         style="max-width: 150px; max-height: 150px;" class="img-thumbnail">
                                    <p class="text-muted small">Foto actual</p>
                                </div>';
}

$content .= '            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_organigrama_id">Elemento del Organigrama</label>
                                <select class="form-control" id="edit_organigrama_id" name="edit_organigrama_id">
                                    <option value="">Seleccionar elemento del organigrama...</option>';

foreach ($organigrama_elementos as $elemento) {
    $selected = ($_SESSION['old_data']['edit_organigrama_id'] ?? ($employee['organigrama_id'] ?? '')) == $elemento['id'] ? ' selected' : '';
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;', substr_count($elemento['path'] ?? '', '/'));
    $content .= '<option value="' . $elemento['id'] . '"' . $selected . '>' . $indent . htmlspecialchars($elemento['descripcion']) . '</option>';
}

$content .= '                        </select>
                                    <small class="form-text text-muted">Opcional. Elemento del organigrama al que pertenece el empleado</small>
                                    ' . (isset($_SESSION['errors']['edit_organigrama_id']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_organigrama_id'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Empleado
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

// Función para alternar campos según el tipo de empresa (fallback si el módulo falla)
function initializeEmployeeEditFallback() {
    function toggleFieldsByCompanyType() {
        const tipoInstitucion = window.APP_CONFIG?.company?.tipo_institucion || "privada";
        console.log("Tipo de institución (fallback):", tipoInstitucion);

        if (tipoInstitucion === "privada") {
            // Empresa privada: mostrar cargos, funciones, partidas y sueldo individual (SIN posición)
            $("#edit-private-company-fields").show();
            $("#edit-salary-section").show();
            $("#edit-public-institution-fields").hide();

            // Hacer obligatorios los campos de empresa privada
            $("#edit_cargo_id, #edit_funcion_id, #edit_partida_id, #edit_sueldo_individual").prop("required", true);
            $("#edit_position").prop("required", false);

        } else {
            // Institución pública: mostrar solo posición
            $("#edit-public-institution-fields").show();
            $("#edit-private-company-fields").hide();
            $("#edit-salary-section").hide();

            // Hacer obligatorio solo el campo de posición
            $("#edit_position").prop("required", true);
            $("#edit_cargo_id, #edit_funcion_id, #edit_partida_id, #edit_sueldo_individual").prop("required", false);
        }
    }

    // Ejecutar al cargar la página
    toggleFieldsByCompanyType();

    // Validación del formulario
    $("#edit_position").change(function() {
        var positionId = $(this).val();
        if (positionId) {
            console.log("Posición seleccionada: " + positionId);
        }
    });

    // Previsualización de imagen
    $("#edit_photo").change(function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                // Crear preview si no existe
                if (!$("#edit-photo-preview").length) {
                    $("#edit_photo").after("<div id=\"edit-photo-preview\" class=\"mt-2\"><img src=\"\" style=\"max-width: 200px; max-height: 200px;\" class=\"img-thumbnail\"><p class=\"text-muted small\">Nueva foto</p></div>");
                }
                $("#edit-photo-preview img").attr("src", e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });
}

// Inicializar fallback si existe jQuery
if (typeof $ !== "undefined") {
    $(document).ready(function() {
        // Inicializar Select2 para el campo de tipo de planilla (fallback inline)
        if (typeof $.fn.select2 !== "undefined") {
            $("#edit_tipo_planilla").select2({
                theme: "bootstrap4",
                placeholder: "Seleccione uno o más tipos de planilla",
                allowClear: true,
                width: "100%",
                language: {
                    noResults: function() {
                        return "No se encontraron resultados";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                }
            });
        }

        initializeEmployeeEditFallback();

        // Funcionalidad copiar cédula a seguro social
        $("#btn_copy_cedula_edit").click(function() {
            var cedula = $("#edit_document_id").val().trim();
            if (cedula) {
                $("#edit_clave_seguro_social").val(cedula);
                toastr.success("Número de cédula copiado al seguro social", "Copiado");
            } else {
                toastr.warning("Primero ingrese el número de cédula", "Advertencia");
            }
        });
    });
}
</script>
<script src="' . asset('javascript/modules/employees/edit.js') . '"></script>';

$styles = '';

// Limpiar mensajes de sesión
unset($_SESSION['errors'], $_SESSION['old_data']);

include __DIR__ . '/../../layouts/admin.php';
?>