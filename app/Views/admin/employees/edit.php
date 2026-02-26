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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_edad_display">Edad</label>
                                <input type="text" class="form-control" id="edit_edad_display" readonly
                                       placeholder="Se calculará automáticamente"
                                       style="background-color: #f4f6f9;">
                                <small class="form-text text-muted">
                                    <i class="fas fa-calculator"></i> Se calcula automáticamente desde la fecha de nacimiento
                                </small>
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="edit_fecha_ingreso">Fecha de Ingreso *</label>
                                <input type="date" class="form-control" id="edit_fecha_ingreso" name="edit_fecha_ingreso"
                                       value="' . ($_SESSION['old_data']['edit_fecha_ingreso'] ?? $employee['fecha_ingreso']) . '" required>
                                ' . (isset($_SESSION['errors']['edit_fecha_ingreso']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_fecha_ingreso'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="edit_antiguedad_display">Antigüedad</label>
                                <input type="text" class="form-control" id="edit_antiguedad_display" readonly
                                       placeholder="Se calculará automáticamente"
                                       style="background-color: #f4f6f9;">
                                <small class="form-text text-muted">
                                    <i class="fas fa-calendar-check"></i> Calculado automáticamente
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_address">Dirección *</label>
                        <textarea class="form-control" id="edit_address" name="edit_address" rows="2" required>' . ($_SESSION['old_data']['edit_address'] ?? $employee['address']) . '</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_contact">Información de Contacto *</label>
                                <input type="text" class="form-control" id="edit_contact" name="edit_contact"
                                       placeholder="Teléfono, celular, etc." value="' . ($_SESSION['old_data']['edit_contact'] ?? $employee['contact_info']) . '" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="edit_email"
                                       placeholder="ejemplo@empresa.com"
                                       value="' . ($_SESSION['old_data']['edit_email'] ?? ($employee['email'] ?? '')) . '" required>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Email corporativo del empleado (requerido para sincronización de marcaciones)
                                </small>
                                ' . (isset($_SESSION['errors']['edit_email']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_email'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>

                    <!-- Opciones de Asistencia y Bonos -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-user-clock"></i> Opciones de Asistencia y Bonos
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Marca Asistencia -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>
                                                    <i class="fas fa-clock text-primary"></i> Marca Asistencia
                                                </label>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="edit_marca_asistencia" name="edit_marca_asistencia" value="1" ' . ((isset($employee['marca_asistencia']) && $employee['marca_asistencia'] == 1) ? 'checked' : '') . '>
                                                    <label class="custom-control-label" for="edit_marca_asistencia">
                                                        Registra marcaciones
                                                    </label>
                                                </div>
                                                <small class="form-text text-muted">
                                                    Pago por horas trabajadas
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Permite Horas Extras -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>
                                                    <i class="fas fa-user-clock text-success"></i> Horas Extras
                                                </label>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="edit_permite_horas_extras" name="edit_permite_horas_extras" value="1" ' . ((isset($employee['permite_horas_extras']) && $employee['permite_horas_extras'] == 1) ? 'checked' : '') . '>
                                                    <label class="custom-control-label" for="edit_permite_horas_extras">
                                                        Permite horas extras
                                                    </label>
                                                </div>
                                                <small class="form-text text-muted">
                                                    Elegible para overtime
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Tiene Bono Asistencia -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>
                                                    <i class="fas fa-award text-warning"></i> Bono de Asistencia
                                                </label>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="edit_tiene_bono_asistencia" name="edit_tiene_bono_asistencia" value="1" ' . ((isset($employee['tiene_bono_asistencia']) && $employee['tiene_bono_asistencia'] == 1) ? 'checked' : '') . '>
                                                    <label class="custom-control-label" for="edit_tiene_bono_asistencia">
                                                        Recibe bono de asistencia
                                                    </label>
                                                </div>
                                                <small class="form-text text-muted">
                                                    Elegible para bonos por puntualidad
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                            <div class="col-md-12">
                                <!-- Contenedor para selectores jerárquicos de departamentos -->
                                <div id="edit-departamentos-jerarquicos-container" class="mb-3">
                                    <!-- Los selectores se generarán dinámicamente aquí -->
                                </div>
                                <!-- Campo oculto que será actualizado por JavaScript con el departamento seleccionado -->
                                <input type="hidden" id="edit_departamento_id_hidden" name="edit_departamento_id" value="' . ($_SESSION['old_data']['edit_departamento_id'] ?? ($employee['departamento_id'] ?? '')) . '">
                                <!-- Select oculto para referencia del JS (NO se envía en el form) -->
                                <select class="form-control d-none" id="edit_departamento_id_private">
                                    <option value="' . ($_SESSION['old_data']['edit_departamento_id'] ?? ($employee['departamento_id'] ?? '')) . '">' . ($_SESSION['old_data']['edit_departamento_id'] ?? ($employee['departamento_id'] ?? '')) . '</option>
                                </select>
                                ' . (isset($_SESSION['errors']['edit_departamento_id']) ? '<div class="text-danger mt-2"><small>' . $_SESSION['errors']['edit_departamento_id'] . '</small></div>' : '') . '
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_cargo_id">Cargo *</label>
                                    <select class="form-control" id="edit_cargo_id" name="edit_cargo_id" disabled>
                                        <option value="">Primero seleccione un departamento...</option>
                                    </select>
                                    <small class="form-text text-muted">Cargos del departamento seleccionado</small>
                                    ' . (isset($_SESSION['errors']['edit_cargo_id']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_cargo_id'] . '</small>' : '') . '
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_funcion_id">Función</label>
                                    <select class="form-control" id="edit_funcion_id" name="edit_funcion_id" disabled>
                                        <option value="">Primero seleccione un cargo...</option>
                                    </select>
                                    <small class="form-text text-muted">Funciones del cargo (opcional)</small>
                                    ' . (isset($_SESSION['errors']['edit_funcion_id']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_funcion_id'] . '</small>' : '') . '
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_partida_id">Partida</label>
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

                    <!-- Salarios por Tipo de Planilla -->
                    <div class="row" id="salaries-section" style="display: none;">
                        <div class="col-md-12">
                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-money-bill-wave"></i> Salarios por Tipo de Planilla
                                    </h3>
                                    <div class="card-tools">
                                        <span class="badge badge-info">
                                            <span id="salaries-count-badge">0</span> configurados
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="salaries-container">
                                        <!-- Se llenará dinámicamente con JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="edit-salary-tarifa-section" style="display: none;">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_tarifa_hora">Tarifa por Hora
                                    <i class="fas fa-calculator text-info ml-1" title="Se calcula automáticamente: Sueldo ÷ 26 días ÷ 8 horas"></i>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">' . currency_symbol() . '/h</span>
                                    </div>
                                    <input type="number" class="form-control" id="edit_tarifa_hora" name="edit_tarifa_hora"
                                           step="0.0001" min="0" placeholder="0.0000"
                                           value="' . ($_SESSION['old_data']['edit_tarifa_hora'] ?? ($employee['tarifa_hora'] ?? '')) . '">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="btn_calc_tarifa_edit" title="Calcular automáticamente">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-clock text-primary"></i> Usado en variable <code>TARIFA_HORA</code> del calculador de planillas
                                </small>
                                ' . (isset($_SESSION['errors']['edit_tarifa_hora']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_tarifa_hora'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="callout callout-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Tarifa por Hora:</strong><br>
                                Se calcula automáticamente como: <strong>Sueldo Individual ÷ 26 días ÷ 8 horas</strong> (26 días laborables × 8 horas/día).<br>
                                Puede modificarlo manualmente según las necesidades del empleado.
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

';

// ====================================
// CAMPOS ADICIONALES PERSONALIZADOS
// ====================================
$additionalFieldsHtml = '';
if (!empty($additional_fields)) {
    // Crear un mapa de valores existentes por field_id para acceso rápido
    $valuesMap = [];
    if (!empty($additional_field_values)) {
        foreach ($additional_field_values as $value) {
            $valuesMap[$value['field_id']] = $value['valor'];
        }
    }

    $additionalFieldsHtml = '
                    <!-- Campos Adicionales Personalizados -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-plus-square"></i> Campos Adicionales Personalizados
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">';

    foreach ($additional_fields as $field) {
        $fieldName = 'additional_fields[' . $field['id'] . ']';
        // Prioridad: old_data > valor guardado en BD > valor por defecto del campo
        $oldValue = $_SESSION['old_data']['additional_fields'][$field['id']] ?? ($valuesMap[$field['id']] ?? $field['valor_defecto'] ?? '');

        if ($field['tipo_dato'] === 'NUMERO') {
            $additionalFieldsHtml .= '
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="field_' . $field['id'] . '">' . htmlspecialchars($field['etiqueta']) . '</label>
                                                <input type="number" class="form-control" id="field_' . $field['id'] . '"
                                                       name="' . $fieldName . '" step="0.01"
                                                       value="' . htmlspecialchars($oldValue) . '" placeholder="Ej: 150">
                                                <small class="form-text text-muted">' . htmlspecialchars($field['descripcion'] ?: 'Valor numérico') . '</small>
                                            </div>
                                        </div>';
        } elseif ($field['tipo_dato'] === 'FECHA') {
            $additionalFieldsHtml .= '
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="field_' . $field['id'] . '">' . htmlspecialchars($field['etiqueta']) . '</label>
                                                <input type="date" class="form-control" id="field_' . $field['id'] . '"
                                                       name="' . $fieldName . '" value="' . htmlspecialchars($oldValue) . '">
                                                <small class="form-text text-muted">' . htmlspecialchars($field['descripcion'] ?: 'Seleccione una fecha') . '</small>
                                            </div>
                                        </div>';
        } elseif ($field['tipo_dato'] === 'BOOLEAN') {
            $checked = ($oldValue == '1' || $oldValue === true || $oldValue === '1') ? 'checked' : '';
            $additionalFieldsHtml .= '
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>' . htmlspecialchars($field['etiqueta']) . '</label>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="field_' . $field['id'] . '"
                                                           name="' . $fieldName . '" value="1" ' . $checked . '>
                                                    <label class="custom-control-label" for="field_' . $field['id'] . '">
                                                        ' . htmlspecialchars($field['descripcion'] ?: 'Activar/Desactivar') . '
                                                    </label>
                                                </div>
                                            </div>
                                        </div>';
        } else { // TEXTO
            $additionalFieldsHtml .= '
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="field_' . $field['id'] . '">' . htmlspecialchars($field['etiqueta']) . '</label>
                                                <input type="text" class="form-control" id="field_' . $field['id'] . '"
                                                       name="' . $fieldName . '" value="' . htmlspecialchars($oldValue) . '"
                                                       maxlength="255" placeholder="Ingresar texto">
                                                <small class="form-text text-muted">' . htmlspecialchars($field['descripcion'] ?: 'Texto libre') . '</small>
                                            </div>
                                        </div>';
        }
    }

    $additionalFieldsHtml .= '
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>';
}

$content .= $additionalFieldsHtml . '

                    <div class="form-group">
                        <label for="edit_photo">Foto del Empleado</label>
                        <input type="file" class="form-control-file" id="edit_photo" name="edit_photo" accept="image/*">
                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB</small>';

if (!empty($employee['photo'])) {
    $photoUrl = \App\Core\TenantStorage::getPublicImageUrl($employee['photo']);
    $content .= '<div class="mt-2">
                            <img src="' . $photoUrl . '" alt="Foto actual"
                                 style="max-width: 150px; max-height: 150px;" class="img-thumbnail">
                            <p class="text-muted small">Foto actual</p>
                        </div>';
}

$content .= '    </div>
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
window.APP_CONFIG.urls = {
    base: "' . url('', false) . '",
    organizationalApi: "' . url('/panel/organizational', false) . '"
};
window.APP_CONFIG.config = window.APP_CONFIG.config || {};
window.APP_CONFIG.config.csrf_token = "' . ($csrf_token ?? '') . '";
window.APP_CONFIG.old_data = {
    edit_cargo_id: "' . ($_SESSION['old_data']['edit_cargo_id'] ?? ($employee['cargo_id'] ?? '')) . '",
    edit_funcion_id: "' . ($_SESSION['old_data']['edit_funcion_id'] ?? ($employee['funcion_id'] ?? '')) . '"
};

// Función para alternar campos según el tipo de empresa (fallback si el módulo falla)
function initializeEmployeeEditFallback() {
    function toggleFieldsByCompanyType() {
        const tipoInstitucion = window.APP_CONFIG?.company?.tipo_institucion || "privada";
        console.log("Tipo de institución (fallback):", tipoInstitucion);

        if (tipoInstitucion === "privada") {
            // Empresa privada: mostrar cargos, funciones, partidas y sueldo individual (SIN posición)
            $("#edit-private-company-fields").show();
            $("#edit-salary-section").show();
            $("#edit-salary-tarifa-section").show();
            $("#edit-public-institution-fields").hide();

            // Hacer obligatorios los campos de empresa privada (función y partida son opcionales)
            $("#edit_cargo_id, #edit_sueldo_individual").prop("required", true);
            $("#edit_funcion_id, #edit_partida_id").prop("required", false);  // Función y Partida son opcionales
            $("#edit_position").prop("required", false);

        } else {
            // Institución pública: mostrar solo posición
            $("#edit-public-institution-fields").show();
            $("#edit-private-company-fields").hide();
            $("#edit-salary-section").hide();
            $("#edit-salary-tarifa-section").hide();

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

        // Calcular edad a partir de fecha de nacimiento
        function calcularEdad(fechaNacimiento) {
            if (!fechaNacimiento) return "";

            var hoy = new Date();
            var nacimiento = new Date(fechaNacimiento);
            var edad = hoy.getFullYear() - nacimiento.getFullYear();
            var mes = hoy.getMonth() - nacimiento.getMonth();

            // Ajustar si el cumpleaños no ha ocurrido este año
            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }

            return edad >= 0 ? edad + " años" : "";
        }

        // Actualizar edad cuando cambia la fecha de nacimiento
        $("#edit_birthdate").on("change blur", function() {
            var edad = calcularEdad($(this).val());
            $("#edit_edad_display").val(edad);
        });

        // Calcular edad inicial si hay fecha precargada
        if ($("#edit_birthdate").val()) {
            $("#edit_edad_display").val(calcularEdad($("#edit_birthdate").val()));
        }

        // Calcular antigüedad a partir de fecha de ingreso
        function calcularAntiguedad(fechaIngreso) {
            if (!fechaIngreso) return "";

            var hoy = new Date();
            var ingreso = new Date(fechaIngreso);

            // Validar que la fecha no sea futura
            if (ingreso > hoy) {
                return "Fecha futura no válida";
            }

            // Calcular diferencia en milisegundos
            var anos = hoy.getFullYear() - ingreso.getFullYear();
            var meses = hoy.getMonth() - ingreso.getMonth();
            var dias = hoy.getDate() - ingreso.getDate();

            // Ajustar días negativos
            if (dias < 0) {
                meses--;
                // Obtener el último día del mes anterior
                var ultimoDiaMesAnterior = new Date(hoy.getFullYear(), hoy.getMonth(), 0).getDate();
                dias += ultimoDiaMesAnterior;
            }

            // Ajustar meses negativos
            if (meses < 0) {
                anos--;
                meses += 12;
            }

            // Construir el texto de antigüedad (solo incluir valores > 0)
            var partes = [];
            if (anos > 0) {
                partes.push(anos + (anos === 1 ? " año" : " años"));
            }
            if (meses > 0) {
                partes.push(meses + (meses === 1 ? " mes" : " meses"));
            }
            if (dias > 0) {
                partes.push(dias + (dias === 1 ? " día" : " días"));
            }

            if (partes.length === 0) {
                return "0 días";
            }

            return partes.join(", ");
        }

        // Actualizar antigüedad cuando cambia la fecha de ingreso
        $("#edit_fecha_ingreso").on("change blur", function() {
            var antiguedad = calcularAntiguedad($(this).val());
            $("#edit_antiguedad_display").val(antiguedad);
        });

        // Calcular antigüedad inicial si hay fecha precargada
        if ($("#edit_fecha_ingreso").val()) {
            $("#edit_antiguedad_display").val(calcularAntiguedad($("#edit_fecha_ingreso").val()));
        }

        // Función para calcular tarifa por hora
        function calcularTarifaHora() {
            var sueldo = parseFloat($("#edit_sueldo_individual").val()) || 0;
            if (sueldo > 0) {
                // Fórmula: Sueldo ÷ 26 días ÷ 8 horas = Sueldo ÷ 208
                var tarifaHora = (sueldo / 26 / 8).toFixed(4);
                $("#edit_tarifa_hora").val(tarifaHora);
                return tarifaHora;
            }
            return 0;
        }

        // Botón calcular tarifa_hora
        $("#btn_calc_tarifa_edit").click(function() {
            var tarifa = calcularTarifaHora();
            if (tarifa > 0) {
                toastr.success("Tarifa por hora calculada: " + tarifa, "Calculado");
            } else {
                toastr.warning("Primero ingrese el sueldo individual", "Advertencia");
            }
        });

        // Auto-calcular tarifa_hora cuando cambia el sueldo
        $("#edit_sueldo_individual").on("blur change", function() {
            var sueldo = parseFloat($(this).val()) || 0;
            if (sueldo > 0) {
                //calcularTarifaHora();
            }
        });

        // ====================================
        // VALIDACIÓN EN TIEMPO REAL DE CAMPOS
        // ====================================

        // Función para validar campo individual
        function validateField($field) {
            var fieldType = $field.attr("type") || $field.prop("tagName").toLowerCase();
            var fieldName = $field.attr("name");
            var fieldValue = $field.val();
            var isRequired = $field.prop("required");
            var isValid = true;
            var errorMessage = "";
            var isSelect2 = $field.hasClass("select2-hidden-accessible");

            // Validación para campos requeridos vacíos
            if (isRequired && (!fieldValue || (typeof fieldValue === "string" && fieldValue.trim() === "") || (Array.isArray(fieldValue) && fieldValue.length === 0))) {
                isValid = false;
                errorMessage = "Este campo es obligatorio";
            }

            // Validaciones específicas por tipo de campo
            if (isValid && fieldValue && (typeof fieldValue === "string" ? fieldValue.trim() !== "" : fieldValue.length > 0)) {
                switch (fieldType) {
                    case "email":
                        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(fieldValue)) {
                            isValid = false;
                            errorMessage = "Ingrese un email válido";
                        }
                        break;

                    case "date":
                        if (fieldName === "edit_birthdate") {
                            var birthDate = new Date(fieldValue);
                            var today = new Date();
                            var age = today.getFullYear() - birthDate.getFullYear();
                            if (age < 18 || age > 100) {
                                isValid = false;
                                errorMessage = "La edad debe estar entre 18 y 100 años";
                            }
                        }
                        break;

                    case "number":
                        if (fieldValue && parseFloat(fieldValue) < 0) {
                            isValid = false;
                            errorMessage = "El valor debe ser positivo";
                        }
                        break;

                    case "text":
                    case "textarea":
                        // Validación de longitud mínima
                        if (typeof fieldValue === "string" && fieldValue.trim().length < 2) {
                            isValid = false;
                            errorMessage = "Mínimo 2 caracteres";
                        }
                        break;

                    case "select":
                    case "select-one":
                    case "select-multiple":
                        if (isRequired && (!fieldValue || fieldValue === "" || (Array.isArray(fieldValue) && fieldValue.length === 0))) {
                            isValid = false;
                            errorMessage = "Debe seleccionar una opción";
                        }
                        break;
                }
            }

            // Aplicar clases visuales
            if (isSelect2) {
                // Para Select2, aplicar clases al contenedor visible
                var $select2Container = $field.next(".select2-container").find(".select2-selection");

                if (isValid) {
                    $field.removeClass("is-invalid").addClass("is-valid");
                    $select2Container.removeClass("is-invalid").addClass("is-valid");
                    $field.parent().find(".invalid-feedback").remove();
                } else {
                    $field.removeClass("is-valid").addClass("is-invalid");
                    $select2Container.removeClass("is-valid").addClass("is-invalid");

                    // Agregar o actualizar mensaje de error
                    var $errorDiv = $field.parent().find(".invalid-feedback");
                    if ($errorDiv.length === 0) {
                        $field.parent().append("<div class=\'invalid-feedback\'>" + errorMessage + "</div>");
                    } else {
                        $errorDiv.text(errorMessage);
                    }
                }
            } else {
                // Para campos normales
                if (isValid) {
                    $field.removeClass("is-invalid").addClass("is-valid");
                    $field.siblings(".invalid-feedback").remove();
                } else {
                    $field.removeClass("is-valid").addClass("is-invalid");

                    // Agregar o actualizar mensaje de error
                    var $errorDiv = $field.siblings(".invalid-feedback");
                    if ($errorDiv.length === 0) {
                        $field.after("<div class=\'invalid-feedback\'>" + errorMessage + "</div>");
                    } else {
                        $errorDiv.text(errorMessage);
                    }
                }
            }

            return isValid;
        }

        // Validar campos de texto y textarea
        $("input[type=text], input[type=email], input[type=date], input[type=number], textarea").on("blur change", function() {
            validateField($(this));
        });

        // Validar selects
        $("select").on("change", function() {
            validateField($(this));
        });

        // Validación especial para Select2
        $(document).on("select2:select select2:unselect", "#edit_tipo_planilla", function() {
            setTimeout(function() {
                validateField($("#edit_tipo_planilla"));
            }, 100);
        });

        // Validar todo el formulario antes de enviar
        $("form").on("submit", function(e) {
            var isFormValid = true;
            var $firstInvalidField = null;

            // Validar todos los campos requeridos y visibles
            $(this).find("input:required:visible, select:required:visible, textarea:required:visible").each(function() {
                var $field = $(this);

                // Solo validar si el campo está visible y no está deshabilitado
                if ($field.is(":visible") && !$field.is(":disabled")) {
                    if (!validateField($field)) {
                        isFormValid = false;
                        if ($firstInvalidField === null) {
                            $firstInvalidField = $field;
                        }
                    }
                }
            });

            // Si el formulario no es válido, prevenir envío y hacer scroll al primer error
            if (!isFormValid) {
                e.preventDefault();
                if ($firstInvalidField) {
                    $("html, body").animate({
                        scrollTop: $firstInvalidField.offset().top - 100
                    }, 500);
                    $firstInvalidField.focus();
                }

                toastr.error("Por favor corrija los errores en el formulario antes de enviar", "Validación");
                return false;
            }
        });

        // Limpiar validación cuando el usuario empieza a escribir
        $("input, textarea").on("input", function() {
            var $field = $(this);
            if ($field.hasClass("is-invalid") || $field.hasClass("is-valid")) {
                // Remover clases si el campo está siendo editado
                if ($field.val().trim().length > 0) {
                    $field.removeClass("is-invalid is-valid");
                    $field.siblings(".invalid-feedback").remove();
                }
            }
        });

        // ============================================================================
        // SELECTS DEPENDIENTES: Departamento → Cargo → Función
        // ============================================================================

        function loadCargosByDepartamento(departamentoId, selectedCargoId = null) {
            const $cargoSelect = $("#edit_cargo_id");
            const $funcionSelect = $("#edit_funcion_id");

            if (!departamentoId) {
                $cargoSelect.html("<option value=\\"\\">Primero seleccione un departamento...</option>").prop("disabled", true);
                $funcionSelect.html("<option value=\\"\\">Primero seleccione un cargo...</option>").prop("disabled", true);
                return;
            }

            $cargoSelect.html("<option value=\\"\\">Cargando cargos...</option>").prop("disabled", true);
            $funcionSelect.html("<option value=\\"\\">Primero seleccione un cargo...</option>").prop("disabled", true);

            $.ajax({
                url: "' . url('/panel/organizational/getCargosByDepartamento') . '/" + departamentoId,
                method: "GET",
                dataType: "json",
                success: function(response) {
                    if (response.success && response.cargos) {
                        let options = "<option value=\\"\\">Seleccionar cargo...</option>";
                        response.cargos.forEach(function(cargo) {
                            const selected = (selectedCargoId && cargo.id == selectedCargoId) ? " selected" : "";
                            options += `<option value="${cargo.id}"${selected}>${cargo.codigo} - ${cargo.nombre}</option>`;
                        });
                        $cargoSelect.html(options).prop("disabled", false);

                        if (selectedCargoId) {
                            loadFuncionesByCargo(selectedCargoId);
                        }
                    } else {
                        $cargoSelect.html("<option value=\\"\\">No hay cargos en este departamento</option>").prop("disabled", true);
                        toastr.warning("No se encontraron cargos para este departamento", "Advertencia");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error loading cargos:", error);
                    $cargoSelect.html("<option value=\\"\\">Error al cargar cargos</option>").prop("disabled", true);
                    toastr.error("Error al cargar los cargos del departamento", "Error");
                }
            });
        }

        function loadFuncionesByCargo(cargoId, selectedFuncionId = null) {
            const $funcionSelect = $("#edit_funcion_id");

            if (!cargoId) {
                $funcionSelect.html("<option value=\\"\\">Primero seleccione un cargo...</option>").prop("disabled", true);
                return;
            }

            $funcionSelect.html("<option value=\\"\\">Cargando funciones...</option>").prop("disabled", true);

            $.ajax({
                url: "' . url('/panel/organizational/getFuncionesByCargo') . '/" + cargoId,
                method: "GET",
                dataType: "json",
                success: function(response) {
                    if (response.success && response.funciones) {
                        let options = "<option value=\\"\\">Seleccionar función (opcional)...</option>";
                        response.funciones.forEach(function(funcion) {
                            const selected = (selectedFuncionId && funcion.id == selectedFuncionId) ? " selected" : "";
                            const label = funcion.cargo_id === null ? " (Genérica)" : "";
                            options += `<option value="${funcion.id}"${selected}>${funcion.codigo} - ${funcion.nombre}${label}</option>`;
                        });
                        $funcionSelect.html(options).prop("disabled", false);
                    } else {
                        $funcionSelect.html("<option value=\\"\\">No hay funciones disponibles</option>").prop("disabled", false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error loading funciones:", error);
                    $funcionSelect.html("<option value=\\"\\">Error al cargar funciones</option>").prop("disabled", true);
                    toastr.error("Error al cargar las funciones del cargo", "Error");
                }
            });
        }

        // Event handlers for dependent selects
        $("#edit_departamento_id_private").on("change", function() {
            const departamentoId = $(this).val();
            loadCargosByDepartamento(departamentoId);
        });

        $("#edit_cargo_id").on("change", function() {
            const cargoId = $(this).val();
            loadFuncionesByCargo(cargoId);
        });

        // Load pre-selected values on page load (for edit form)
        const editDepartamentoId = $("#edit_departamento_id_private").val();
        const editCargoId = ' . json_encode($_SESSION['old_data']['edit_cargo_id'] ?? ($employee['cargo_id'] ?? '')) . ';
        const editFuncionId = ' . json_encode($_SESSION['old_data']['edit_funcion_id'] ?? ($employee['funcion_id'] ?? '')) . ';

        if (editDepartamentoId) {
            loadCargosByDepartamento(editDepartamentoId, editCargoId || null);
        }

        // ====================================
        // GSAP ANIMATIONS - Button Hover Effects
        // ====================================
        if (typeof gsap !== "undefined") {
            // Efecto hover en botones principales
            const primaryButtons = $(".card-footer .btn-primary");
            const secondaryButtons = $(".card-footer .btn-secondary, .card-tools .btn-secondary");

            // Animar botón principal (Actualizar)
            primaryButtons.each(function() {
                const btn = this;
                $(btn).on("mouseenter", function() {
                    gsap.to(btn, {
                        scale: 1.05,
                        boxShadow: "0 5px 15px rgba(0,123,255,0.4)",
                        duration: 0.3,
                        ease: "power2.out"
                    });
                });

                $(btn).on("mouseleave", function() {
                    gsap.to(btn, {
                        scale: 1,
                        boxShadow: "none",
                        duration: 0.3,
                        ease: "power2.out"
                    });
                });
            });

            // Animar botones secundarios (Volver, Cancelar)
            secondaryButtons.each(function() {
                const btn = this;
                $(btn).on("mouseenter", function() {
                    gsap.to(btn, {
                        scale: 1.05,
                        boxShadow: "0 5px 15px rgba(108,117,125,0.3)",
                        duration: 0.3,
                        ease: "power2.out"
                    });
                });

                $(btn).on("mouseleave", function() {
                    gsap.to(btn, {
                        scale: 1,
                        boxShadow: "none",
                        duration: 0.3,
                        ease: "power2.out"
                    });
                });
            });
        }
    });
}

// Datos de salarios existentes para el empleado
window.EMPLOYEE_SALARIES = ' . json_encode($employee_salaries ?? []) . ';
</script>
<script src="' . url('assets/javascript/modules/employees/departamentos-jerarquicos.js', false) . '?v=' . date('siH') . '"></script>
<script src="' . asset('javascript/modules/employees/edit.js') . '?v=' . date('siH') . '"></script>
<script src="' . url('assets/javascript/modules/employees/salaries-inline.js', false) . '?v=' . date('siH') . '"></script>';

$styles = '
<style>
/* Estilos para validación de formularios */

/* Campo válido - borde verde */
.form-control.is-valid,
.custom-select.is-valid {
    border-color: #28a745 !important;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'8\' height=\'8\' viewBox=\'0 0 8 8\'%3e%3cpath fill=\'%2328a745\' d=\'M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z\'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-valid:focus,
.custom-select.is-valid:focus {
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
}

/* Campo inválido - borde rojo */
.form-control.is-invalid,
.custom-select.is-invalid {
    border-color: #dc3545 !important;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' fill=\'none\' stroke=\'%23dc3545\' viewBox=\'0 0 12 12\'%3e%3ccircle cx=\'6\' cy=\'6\' r=\'4.5\'/%3e%3cpath stroke-linejoin=\'round\' d=\'M5.8 3.6h.4L6 6.5z\'/%3e%3ccircle cx=\'6\' cy=\'8.2\' r=\'.6\' fill=\'%23dc3545\' stroke=\'none\'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-invalid:focus,
.custom-select.is-invalid:focus {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

/* Mensaje de error */
.invalid-feedback {
    display: block !important;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 80%;
    color: #dc3545;
    font-weight: 500;
}

/* Mensaje de éxito */
.valid-feedback {
    display: block !important;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 80%;
    color: #28a745;
}

/* Select2 válido */
.select2-container--bootstrap4 .select2-selection.is-valid {
    border-color: #28a745 !important;
}

.select2-container--bootstrap4 .select2-selection.is-invalid {
    border-color: #dc3545 !important;
}

/* Textarea válido/inválido */
textarea.form-control.is-valid,
textarea.form-control.is-invalid {
    background-position: top calc(0.375em + 0.1875rem) right calc(0.375em + 0.1875rem);
}

/* Animación suave para las transiciones */
.form-control,
.custom-select {
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

/* Icono de alerta para campos obligatorios */
label[for] {
    font-weight: 500;
}

label[for]:has(+ input:required)::after,
label[for]:has(+ select:required)::after,
label[for]:has(+ textarea:required)::after {
    content: " *";
    color: #dc3545;
    font-weight: bold;
}

/* Mejorar visibilidad del feedback en modo oscuro */
@media (prefers-color-scheme: dark) {
    .invalid-feedback {
        color: #ff6b6b;
    }

    .valid-feedback {
        color: #51cf66;
    }
}
</style>
';

// Limpiar mensajes de sesión
unset($_SESSION['errors'], $_SESSION['old_data']);

include __DIR__ . '/../../layouts/admin.php';
?>
