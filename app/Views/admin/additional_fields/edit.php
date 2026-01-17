<?php
$old = $_SESSION['old_data'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old_data'], $_SESSION['errors']);

// Usar datos del campo o old_data
$codigo = $old['codigo'] ?? $field['codigo'] ?? '';
$etiqueta = $old['etiqueta'] ?? $field['etiqueta'] ?? '';
$descripcion = $old['descripcion'] ?? $field['descripcion'] ?? '';
$tipo_dato = $old['tipo_dato'] ?? $field['tipo_dato'] ?? '';
$valor_defecto = $old['valor_defecto'] ?? $field['valor_defecto'] ?? '';
$orden = $old['orden'] ?? $field['orden'] ?? 1;
$activo = isset($old['activo']) ? $old['activo'] : ($field['activo'] ?? true);

$page_title = 'Editar Campo Adicional';

// Mensajes de sesión
$successMsg = '';
$errorMsg = '';

if (isset($_SESSION['success'])) {
    $successMsg = '
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> ' . $_SESSION['success'] . '
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $errorMsg = '
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> ' . $_SESSION['error'] . '
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>';
    unset($_SESSION['error']);
}

$content = '
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Editar Campo: ' . htmlspecialchars($field['etiqueta']) . '</h3>
            </div>
            ' . $successMsg . $errorMsg . '
            <form action="' . url('/panel/additional-fields/update/' . $field['id']) . '" method="POST" id="editFieldForm">
                <input type="hidden" name="csrf_token" value="' . ($csrf_token ?? '') . '">

                <div class="card-body">
                    <!-- Código -->
                    <div class="form-group">
                        <label for="codigo">Código <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control ' . (isset($errors['codigo']) ? 'is-invalid' : '') . '"
                               id="codigo"
                               name="codigo"
                               value="' . htmlspecialchars($codigo) . '"
                               placeholder="Ej: BONO_TRANSPORTE"
                               maxlength="50"
                               style="text-transform: uppercase;"
                               required>
                        <small class="form-text text-muted">
                            Solo letras mayúsculas, números y guiones bajos. Se usa en fórmulas.
                        </small>';
if (isset($errors['codigo'])) {
    $content .= '<div class="invalid-feedback">' . $errors['codigo'] . '</div>';
}
$content .= '
                    </div>

                    <!-- Etiqueta -->
                    <div class="form-group">
                        <label for="etiqueta">Etiqueta <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control ' . (isset($errors['etiqueta']) ? 'is-invalid' : '') . '"
                               id="etiqueta"
                               name="etiqueta"
                               value="' . htmlspecialchars($etiqueta) . '"
                               placeholder="Ej: Bono de Transporte"
                               maxlength="100"
                               required>
                        <small class="form-text text-muted">
                            Nombre visible del campo en formularios.
                        </small>';
if (isset($errors['etiqueta'])) {
    $content .= '<div class="invalid-feedback">' . $errors['etiqueta'] . '</div>';
}
$content .= '
                    </div>

                    <!-- Descripción -->
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control ' . (isset($errors['descripcion']) ? 'is-invalid' : '') . '"
                                  id="descripcion"
                                  name="descripcion"
                                  rows="2"
                                  maxlength="255"
                                  placeholder="Descripción opcional del campo">' . htmlspecialchars($descripcion) . '</textarea>';
if (isset($errors['descripcion'])) {
    $content .= '<div class="invalid-feedback">' . $errors['descripcion'] . '</div>';
}
$content .= '
                    </div>

                    <div class="row">
                        <!-- Tipo de Dato -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_dato">Tipo de Dato <span class="text-danger">*</span></label>
                                <select class="form-control ' . (isset($errors['tipo_dato']) ? 'is-invalid' : '') . '"
                                        id="tipo_dato"
                                        name="tipo_dato"
                                        required>
                                    <option value="">Seleccionar...</option>';
foreach ($tipos_dato as $tipo) {
    $selected = $tipo_dato === $tipo ? 'selected' : '';
    $label = $tipo === 'NUMERO' ? 'Numérico' : $tipo;
    $label = $tipo === 'BOOLEAN' ? 'Sí/No' : $label;
    $content .= '<option value="' . $tipo . '" ' . $selected . '>' . $label . '</option>';
}
$content .= '
                                </select>';
if (isset($errors['tipo_dato'])) {
    $content .= '<div class="invalid-feedback">' . $errors['tipo_dato'] . '</div>';
}
$content .= '
                            </div>
                        </div>

                        <!-- Valor por Defecto -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="valor_defecto">Valor por Defecto</label>
                                <input type="text"
                                       class="form-control ' . (isset($errors['valor_defecto']) ? 'is-invalid' : '') . '"
                                       id="valor_defecto"
                                       name="valor_defecto"
                                       value="' . htmlspecialchars($valor_defecto) . '"
                                       placeholder="Opcional"
                                       maxlength="255">
                                <small class="form-text text-muted" id="valor_defecto_hint">
                                    Valor inicial para nuevos empleados.
                                </small>';
if (isset($errors['valor_defecto'])) {
    $content .= '<div class="invalid-feedback">' . $errors['valor_defecto'] . '</div>';
}
$content .= '
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Orden -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="orden">Orden de Visualización</label>
                                <input type="number"
                                       class="form-control"
                                       id="orden"
                                       name="orden"
                                       value="' . htmlspecialchars($orden) . '"
                                       min="0">
                                <small class="form-text text-muted">
                                    Define el orden en el formulario de empleados.
                                </small>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Estado</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="activo"
                                           name="activo"
                                           ' . ($activo ? 'checked' : '') . '>
                                    <label class="custom-control-label" for="activo">
                                        Campo activo
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Solo los campos activos aparecen en formularios.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Campo
                    </button>
                    <a href="' . url('/panel/additional-fields') . '" class="btn btn-default">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Panel lateral con estadísticas -->
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> Estadísticas de Uso</h3>
            </div>
            <div class="card-body">
                <div class="info-box bg-light">
                    <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Empleados usando este campo</span>
                        <span class="info-box-number">' . ($stats['empleados_usando'] ?? 0) . '</span>
                    </div>
                </div>';

if (($stats['empleados_usando'] ?? 0) > 0) {
    $content .= '
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <small>
                        Este campo tiene valores asignados. Si cambias el tipo de dato,
                        los valores existentes pueden no ser válidos.
                    </small>
                </div>';
}

$content .= '
            </div>
        </div>

        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle"></i> Ayuda</h3>
            </div>
            <div class="card-body">
                <h5>Uso en Fórmulas</h5>
                <p>Código actual para usar en fórmulas:</p>
                <pre class="bg-light p-2"><code>ADICIONALES("' . htmlspecialchars($codigo) . '")</code></pre>
                <p class="small text-muted">
                    Solo campos <strong>numéricos</strong> y <strong>Sí/No</strong> retornan valores numéricos.
                </p>
            </div>
        </div>
    </div>
</div>';

$scripts = '
<script>
$(document).ready(function() {
    // Convertir código a mayúsculas automáticamente
    $("#codigo").on("input", function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g, "");
    });

    // Actualizar hint según tipo de dato
    $("#tipo_dato").on("change", function() {
        var tipo = $(this).val();
        var hint = "";
        var valorInput = $("#valor_defecto");

        switch(tipo) {
            case "NUMERO":
                hint = "Ejemplo: 50 o 125.50";
                valorInput.attr("type", "text").attr("placeholder", "0");
                break;
            case "FECHA":
                hint = "Formato: AAAA-MM-DD (Ej: 2026-01-16)";
                valorInput.attr("type", "date").attr("placeholder", "");
                break;
            case "BOOLEAN":
                hint = "Valor: 0 (No) o 1 (Sí)";
                valorInput.attr("type", "text").attr("placeholder", "0");
                break;
            case "TEXTO":
            default:
                hint = "Cualquier texto";
                valorInput.attr("type", "text").attr("placeholder", "");
                break;
        }

        $("#valor_defecto_hint").text(hint);
    });

    // Trigger change inicial
    $("#tipo_dato").trigger("change");

    // Validación del formulario
    $("#editFieldForm").on("submit", function(e) {
        var codigo = $("#codigo").val().trim();
        var etiqueta = $("#etiqueta").val().trim();
        var tipoDato = $("#tipo_dato").val();

        if (!codigo || !etiqueta || !tipoDato) {
            e.preventDefault();
            Swal.fire("Error", "Por favor complete todos los campos obligatorios", "error");
            return false;
        }

        return true;
    });
});
</script>';

include __DIR__ . '/../../layouts/admin.php';
