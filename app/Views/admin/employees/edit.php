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
                                <label for="edit_clave_seguro_social">Clave Seguro Social</label>
                                <input type="text" class="form-control" id="edit_clave_seguro_social" name="edit_clave_seguro_social" 
                                       value="' . ($_SESSION['old_data']['edit_clave_seguro_social'] ?? ($employee['clave_seguro_social'] ?? '')) . '" 
                                       placeholder="Ej: 12-34-567890">
                                ' . (isset($_SESSION['errors']['edit_clave_seguro_social']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_clave_seguro_social'] . '</small>' : '') . '
                                <small class="form-text text-muted">Opcional. Clave única del empleado en el seguro social</small>
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
                                <label for="edit_tipo_planilla">Tipo de Planilla *</label>
                                <select class="form-control" id="edit_tipo_planilla" name="edit_tipo_planilla" required>
                                    <option value="">Seleccionar tipo...</option>';
foreach ($tipos_planilla as $tipo) {
    $selected = ($_SESSION['old_data']['edit_tipo_planilla'] ?? ($employee['tipo_planilla_id'] ?? '')) == $tipo['id'] ? ' selected' : '';
    $content .= '<option value="' . $tipo['id'] . '"' . $selected . '>' . htmlspecialchars($tipo['descripcion']) . '</option>';
}
$content .= '                </select>
                                ' . (isset($_SESSION['errors']['edit_tipo_planilla']) ? '<small class="text-danger">' . $_SESSION['errors']['edit_tipo_planilla'] . '</small>' : '') . '
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
});
</script>';

$styles = '';

// Limpiar mensajes de sesión
unset($_SESSION['errors'], $_SESSION['old_data']);

include __DIR__ . '/../../layouts/admin.php';
?>