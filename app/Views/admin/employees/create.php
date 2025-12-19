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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edad_display">Edad</label>
                                <input type="text" class="form-control" id="edad_display" readonly
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
                                <label for="gender">Género *</label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="M"' . (($_SESSION['old_data']['gender'] ?? '') === 'M' ? ' selected' : '') . '>Masculino</option>
                                    <option value="F"' . (($_SESSION['old_data']['gender'] ?? '') === 'F' ? ' selected' : '') . '>Femenino</option>
                                </select>
                                ' . (isset($_SESSION['errors']['gender']) ? '<small class="text-danger">' . $_SESSION['errors']['gender'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="fecha_ingreso">Fecha de Ingreso *</label>
                                <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso"
                                       value="' . ($_SESSION['old_data']['fecha_ingreso'] ?? date('Y-m-d')) . '" required>
                                ' . (isset($_SESSION['errors']['fecha_ingreso']) ? '<small class="text-danger">' . $_SESSION['errors']['fecha_ingreso'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="antiguedad_display">Antigüedad</label>
                                <input type="text" class="form-control" id="antiguedad_display" readonly
                                       placeholder="Se calculará automáticamente"
                                       style="background-color: #f4f6f9;">
                                <small class="form-text text-muted">
                                    <i class="fas fa-calendar-check"></i> Calculado automáticamente
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Dirección *</label>
                        <textarea class="form-control" id="address" name="address" rows="2" required>' . ($_SESSION['old_data']['address'] ?? '') . '</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact">Información de Contacto *</label>
                                <input type="text" class="form-control" id="contact" name="contact"
                                       placeholder="Teléfono, celular, etc." value="' . ($_SESSION['old_data']['contact'] ?? '') . '" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="ejemplo@empresa.com"
                                       value="' . ($_SESSION['old_data']['email'] ?? 'empleado' . rand(1000,9999) . '@empresa.local') . '" required>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Email corporativo del empleado (requerido para sincronización de marcaciones)
                                </small>
                                ' . (isset($_SESSION['errors']['email']) ? '<small class="text-danger">' . $_SESSION['errors']['email'] . '</small>' : '') . '
                            </div>
                        </div>
                    </div>

                    <!-- Campo Marca Asistencia -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>
                                    Marca Asistencia <i class="fas fa-info-circle text-info" title="Controla si se sincronizarán las marcaciones del empleado"></i>
                                </label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="marca_asistencia" name="marca_asistencia" value="1" checked>
                                    <label class="custom-control-label" for="marca_asistencia">
                                        <strong>Registra marcaciones</strong> (pago por horas trabajadas)
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-clock text-primary"></i> <strong>Habilitado:</strong> Se sincronizarán marcaciones y se pagará por horas trabajadas<br>
                                    <i class="fas fa-money-bill-wave text-secondary"></i> <strong>Deshabilitado:</strong> Se pagará sueldo quincenal fijo (sin sincronizar marcaciones)
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Campo Permite Horas Extras -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>
                                    Horas Extras <i class="fas fa-info-circle text-info" title="Controla si el empleado es elegible para generar horas extras"></i>
                                </label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="permite_horas_extras" name="permite_horas_extras" value="1" checked>
                                    <label class="custom-control-label" for="permite_horas_extras">
                                        <strong>Permite horas extras</strong> (elegible para overtime)
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-user-clock text-success"></i> <strong>Habilitado:</strong> Horas extras se calcularán y aparecerán como PENDIENTE de aprobación<br>
                                    <i class="fas fa-user-tie text-warning"></i> <strong>Deshabilitado:</strong> Empleado exento (gerentes, ejecutivos) - no genera horas extras
                                </small>
                            </div>
                        </div>
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
                            <div class="col-md-12">
                                <!-- Contenedor para selectores jerárquicos de departamentos -->
                                <div id="departamentos-jerarquicos-container" class="mb-3">
                                    <!-- Los selectores se generarán dinámicamente aquí -->
                                </div>
                                <!-- Campo oculto para mantener compatibilidad con select legacy (fallback) -->
                                <select class="form-control d-none" id="departamento_id_private" name="departamento_id">
                                    <option value="' . ($_SESSION['old_data']['departamento_id'] ?? '') . '">' . ($_SESSION['old_data']['departamento_id'] ?? '') . '</option>
                                </select>
                                ' . (isset($_SESSION['errors']['departamento_id']) ? '<div class="text-danger mt-2"><small>' . $_SESSION['errors']['departamento_id'] . '</small></div>' : '') . '
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cargo_id">Cargo *</label>
                                    <select class="form-control" id="cargo_id" name="cargo_id" disabled>
                                        <option value="">Primero seleccione un departamento...</option>
                                    </select>
                                    <small class="form-text text-muted">Cargos del departamento seleccionado</small>
                                    ' . (isset($_SESSION['errors']['cargo_id']) ? '<small class="text-danger">' . $_SESSION['errors']['cargo_id'] . '</small>' : '') . '
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="funcion_id">Función</label>
                                    <select class="form-control" id="funcion_id" name="funcion_id" disabled>
                                        <option value="">Primero seleccione un cargo...</option>
                                    </select>
                                    <small class="form-text text-muted">Funciones del cargo (opcional)</small>
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
                    </div>

                    <div class="row" id="salary-tarifa-section" style="display: none;">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tarifa_hora">Tarifa por Hora
                                    <i class="fas fa-calculator text-info ml-1" title="Se calcula automáticamente: Sueldo ÷ 26 días ÷ 8 horas"></i>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">' . currency_symbol() . '/h</span>
                                    </div>
                                    <input type="number" class="form-control" id="tarifa_hora" name="tarifa_hora"
                                           step="0.0001" min="0" placeholder="0.0000"
                                           value="' . ($_SESSION['old_data']['tarifa_hora'] ?? '') . '">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="btn_calc_tarifa" title="Calcular automáticamente">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-clock text-primary"></i> Usado en variable <code>TARIFA_HORA</code> del calculador de planillas
                                </small>
                                ' . (isset($_SESSION['errors']['tarifa_hora']) ? '<small class="text-danger">' . $_SESSION['errors']['tarifa_hora'] . '</small>' : '') . '
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="callout callout-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Tarifa por Hora:</strong><br>
                                Se calcula automáticamente como: <strong>Sueldo Individual ÷ 26 días ÷ 8 horas</strong> (26 días laborables × 8 horas/día = 208 horas).<br>
                                Puede modificarlo manualmente según las necesidades del empleado.
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
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Instrucciones:</strong> Configure el salario base y gastos de representación para cada tipo de planilla seleccionado.
                                    </div>
                                    <div id="salaries-container">
                                        <!-- Se llenará dinámicamente con JavaScript -->
                                    </div>
                                </div>
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
window.APP_CONFIG.urls = {
    base: "' . url('', false) . '",
    organizationalApi: "' . url('/panel/organizational', false) . '"
};
window.APP_CONFIG.config = window.APP_CONFIG.config || {};
window.APP_CONFIG.config.csrf_token = "' . ($csrf_token ?? '') . '";
window.APP_CONFIG.old_data = {
    cargo_id: "' . ($_SESSION['old_data']['cargo_id'] ?? '') . '",
    funcion_id: "' . ($_SESSION['old_data']['funcion_id'] ?? '') . '"
};

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
    $("#birthdate").on("change blur", function() {
        var edad = calcularEdad($(this).val());
        $("#edad_display").val(edad);
    });

    // Calcular edad inicial si hay fecha precargada
    if ($("#birthdate").val()) {
        $("#edad_display").val(calcularEdad($("#birthdate").val()));
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
    $("#fecha_ingreso").on("change blur", function() {
        var antiguedad = calcularAntiguedad($(this).val());
        $("#antiguedad_display").val(antiguedad);
    });

    // Calcular antigüedad inicial si hay fecha precargada
    if ($("#fecha_ingreso").val()) {
        $("#antiguedad_display").val(calcularAntiguedad($("#fecha_ingreso").val()));
    }

    // Función para calcular tarifa por hora
    function calcularTarifaHora() {
        var sueldo = parseFloat($("#sueldo_individual").val()) || 0;
        if (sueldo > 0) {
            // Fórmula: Sueldo ÷ 26 días ÷ 8 horas = Sueldo ÷ 208
            var tarifaHora = (sueldo / 26 / 8).toFixed(4);
            $("#tarifa_hora").val(tarifaHora);
            return tarifaHora;
        }
        return 0;
    }

    // Evento: Click en botón calcular tarifa
    $("#btn_calc_tarifa").click(function() {
        var tarifa = calcularTarifaHora();
        if (tarifa > 0) {
            toastr.success("Tarifa calculada: $" + tarifa + "/h", "Cálculo Exitoso");
        } else {
            toastr.warning("Primero ingrese el sueldo individual", "Advertencia");
        }
    });

    // Evento: Cambio en sueldo individual - actualizar tarifa automáticamente
    $("#sueldo_individual").on("blur change", function() {
        var sueldo = parseFloat($(this).val()) || 0;
        if (sueldo > 0) {
            calcularTarifaHora();
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
                    if (fieldName === "birthdate") {
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
    $(document).on("select2:select select2:unselect", "#tipo_planilla", function() {
        setTimeout(function() {
            validateField($("#tipo_planilla"));
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

});
</script>
<script src="' . url('assets/javascript/modules/employees/departamentos-jerarquicos.js', false) . '?v=' . date('siH') . '"></script>
<script src="' . url('assets/javascript/modules/employees/create.js', false) . '?v=' . date('siH') . '"></script>
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