<?php
$old = $_SESSION['old_data'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old_data'], $_SESSION['errors']);

$page_title = 'Nuevo Campo Adicional';

$content = '
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Datos del Campo Adicional</h3>
            </div>
            <form action="' . url('/panel/additional-fields/store') . '" method="POST" id="createFieldForm">
                <input type="hidden" name="csrf_token" value="' . ($csrf_token ?? '') . '">

                <div class="card-body">
                    <!-- Código -->
                    <div class="form-group">
                        <label for="codigo">Código <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control ' . (isset($errors['codigo']) ? 'is-invalid' : '') . '"
                               id="codigo"
                               name="codigo"
                               value="' . htmlspecialchars($old['codigo'] ?? '') . '"
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
                               value="' . htmlspecialchars($old['etiqueta'] ?? '') . '"
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
                                  placeholder="Descripción opcional del campo">' . htmlspecialchars($old['descripcion'] ?? '') . '</textarea>';
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
    $selected = ($old['tipo_dato'] ?? '') === $tipo ? 'selected' : '';
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
                                       value="' . htmlspecialchars($old['valor_defecto'] ?? '') . '"
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
                                       value="' . htmlspecialchars($old['orden'] ?? $siguiente_orden ?? 1) . '"
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
                                           ' . (($old['activo'] ?? true) ? 'checked' : '') . '>
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
                        <i class="fas fa-save"></i> Guardar Campo
                    </button>
                    <a href="' . url('/panel/additional-fields') . '" class="btn btn-default">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Ayuda lateral -->
    <div class="col-md-4">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle"></i> Ayuda</h3>
            </div>
            <div class="card-body">
                <h5>Tipos de Datos</h5>
                <ul class="list-unstyled">
                    <li><strong>Texto:</strong> Cualquier texto alfanumérico</li>
                    <li><strong>Numérico:</strong> Valores numéricos (enteros o decimales)</li>
                    <li><strong>Fecha:</strong> Fechas en formato AAAA-MM-DD</li>
                    <li><strong>Sí/No:</strong> Valores booleanos (checkbox)</li>
                </ul>

                <hr>

                <h5>Uso en Fórmulas</h5>
                <p>Puedes usar este campo en fórmulas de conceptos:</p>
                <pre class="bg-light p-2"><code>ADICIONALES("CODIGO")</code></pre>
                <p class="small text-muted">
                    Solo campos <strong>numéricos</strong> y <strong>Sí/No</strong> retornan valores numéricos en fórmulas.
                </p>

                <hr>

                <h5>Ejemplos</h5>
                <ul class="small">
                    <li><code>BONO_TRANSPORTE</code>: Monto de bono</li>
                    <li><code>TIENE_DEPENDIENTES</code>: Sí/No</li>
                    <li><code>NIVEL_EDUCATIVO</code>: Texto</li>
                </ul>
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

    // Actualizar hint de valor por defecto según tipo de dato
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

    // Validación del formulario
    $("#createFieldForm").on("submit", function(e) {
        var codigo = $("#codigo").val().trim();
        var etiqueta = $("#etiqueta").val().trim();
        var tipoDato = $("#tipo_dato").val();

        if (!codigo) {
            e.preventDefault();
            Swal.fire("Error", "El código es obligatorio", "error");
            return false;
        }

        if (!codigo.match(/^[A-Z0-9_]+$/)) {
            e.preventDefault();
            Swal.fire("Error", "El código solo puede contener letras mayúsculas, números y guiones bajos", "error");
            return false;
        }

        if (!etiqueta) {
            e.preventDefault();
            Swal.fire("Error", "La etiqueta es obligatoria", "error");
            return false;
        }

        if (!tipoDato) {
            e.preventDefault();
            Swal.fire("Error", "Debe seleccionar un tipo de dato", "error");
            return false;
        }

        return true;
    });
});
</script>';

include __DIR__ . '/../../layouts/admin.php';
