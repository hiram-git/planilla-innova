<?php
$title = 'Editar Tipo de Acumulado';

// JavaScript optimizado modular para edición
$scripts = '
<script src="' . url('assets/js/modules/tipos-acumulados.js', false) . '"></script>
<script>
// Esperar a que jQuery esté disponible
(function checkjQuery() {
    if (typeof $ === "undefined") {
        setTimeout(checkjQuery, 50);
        return;
    }
    
    $(document).ready(function() {
        // Inicializar módulo
        TiposAcumuladosModule.init({
            toggleStatus: "' . url('/panel/tipos-acumulados/toggle-status') . '",
            delete: "' . url('/panel/tipos-acumulados/delete') . '",
            checkDuplicate: "' . url('/panel/tipos-acumulados/check-duplicate') . '",
            update: "' . url('/panel/tipos-acumulados/update') . '",
            csrfToken: "' . \App\Core\Security::generateToken() . '"
        });
        
        // Funcionalidad específica del edit
        initEditForm();
    });
})();

function initEditForm() {
    // Validación en tiempo real del código
    $("#codigo").on("input", function() {
        let value = $(this).val().toUpperCase();
        $(this).val(value.replace(/[^A-Z0-9_]/g, ""));
    });

    // Validación de fechas
    $("#fecha_inicio_periodo, #fecha_fin_periodo").on("change", function() {
        let fechaInicio = $("#fecha_inicio_periodo").val();
        let fechaFin = $("#fecha_fin_periodo").val();
        
        if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
            if (typeof toastr !== "undefined") {
                toastr.error("La fecha de inicio no puede ser posterior a la fecha de fin", "Error de validación");
            } else {
                alert("La fecha de inicio no puede ser posterior a la fecha de fin");
            }
            $(this).val("");
        }
    });

    // Envío del formulario
    $("#editForm").on("submit", function(e) {
        e.preventDefault();
        
        let formData = $(this).serialize();
        
        $.ajax({
            url: "' . url('/panel/tipos-acumulados/update/' . $tipoAcumulado['id']) . '",
            method: "POST",
            data: formData + "&csrf_token=' . \App\Core\Security::generateToken() . '",
            dataType: "json",
            beforeSend: function() {
                $("button[type=submit]").prop("disabled", true).html("<i class=\"fas fa-spinner fa-spin\"></i> Actualizando...");
                $(".invalid-feedback").hide();
                $(".is-invalid").removeClass("is-invalid");
            },
            success: function(response) {
                if (response.success) {
                    if (typeof toastr !== "undefined") {
                        toastr.success("Tipo de acumulado actualizado exitosamente", "Éxito", {
                            timeOut: 3000,
                            onHidden: function() {
                                window.location.href = "' . url('/panel/tipos-acumulados') . '";
                            }
                        });
                    } else {
                        alert("Tipo de acumulado actualizado exitosamente");
                        window.location.href = "' . url('/panel/tipos-acumulados') . '";
                    }
                } else {
                    if (response.errors) {
                        Object.keys(response.errors).forEach(function(field) {
                            $("#" + field).addClass("is-invalid");
                            $("#" + field).siblings(".invalid-feedback").text(response.errors[field]).show();
                        });
                    } else {
                        let errorMsg = "Error: " + (response.message || "No se pudo actualizar el tipo de acumulado");
                        if (typeof toastr !== "undefined") {
                            toastr.error(errorMsg, "Error");
                        } else {
                            alert(errorMsg);
                        }
                    }
                }
            },
            error: function() {
                if (typeof toastr !== "undefined") {
                    toastr.error("Error de conexión. Inténtelo de nuevo.", "Error de conexión");
                } else {
                    alert("Error de conexión. Inténtelo de nuevo.");
                }
            },
            complete: function() {
                $("button[type=submit]").prop("disabled", false).html("<i class=\"fas fa-save\"></i> Actualizar Tipo");
            }
        });
    });
}
</script>';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit"></i> Editar Tipo de Acumulado: <strong><?= htmlspecialchars($tipoAcumulado['codigo']) ?></strong>
                </h3>
                <div class="card-tools">
                    <a href="<?= url('/panel/tipos-acumulados') ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>
                </div>
            </div>
            <form id="editForm">
                <input type="hidden" name="id" value="<?= $tipoAcumulado['id'] ?>">
                <div class="card-body">
                    <div class="row">
                        <!-- Código -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="codigo">
                                    Código <span class="text-danger">*</span>
                                    <small class="text-muted">(Solo mayúsculas, números y guiones bajos)</small>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="codigo" 
                                       name="codigo" 
                                       value="<?= htmlspecialchars($tipoAcumulado['codigo']) ?>"
                                       maxlength="20"
                                       pattern="[A-Z0-9_]+"
                                       style="text-transform: uppercase;"
                                       required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <!-- Descripción -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="descripcion">
                                    Descripción <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="descripcion" 
                                       name="descripcion" 
                                       value="<?= htmlspecialchars($tipoAcumulado['descripcion']) ?>"
                                       placeholder="Descripción del tipo de acumulado"
                                       maxlength="100"
                                       required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Periodicidad -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="periodicidad">
                                    Periodicidad <span class="text-danger">*</span>
                                    <small class="text-muted d-block">Cada cuánto se reinicia el acumulado</small>
                                </label>
                                <select class="form-control" id="periodicidad" name="periodicidad" required>
                                    <option value="">Seleccionar periodicidad...</option>
                                    <option value="MENSUAL" <?= $tipoAcumulado['periodicidad'] == 'MENSUAL' ? 'selected' : '' ?>>Mensual</option>
                                    <option value="TRIMESTRAL" <?= $tipoAcumulado['periodicidad'] == 'TRIMESTRAL' ? 'selected' : '' ?>>Trimestral</option>
                                    <option value="SEMESTRAL" <?= $tipoAcumulado['periodicidad'] == 'SEMESTRAL' ? 'selected' : '' ?>>Semestral</option>
                                    <option value="ANUAL" <?= $tipoAcumulado['periodicidad'] == 'ANUAL' ? 'selected' : '' ?>>Anual</option>
                                    <option value="ESPECIAL" <?= $tipoAcumulado['periodicidad'] == 'ESPECIAL' ? 'selected' : '' ?>>Especial</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <!-- Reinicia Automáticamente -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reinicia_automaticamente">Reinicio Automático</label>
                                <select class="form-control" id="reinicia_automaticamente" name="reinicia_automaticamente">
                                    <option value="1" <?= $tipoAcumulado['reinicia_automaticamente'] ? 'selected' : '' ?>>Sí - Se reinicia automáticamente</option>
                                    <option value="0" <?= !$tipoAcumulado['reinicia_automaticamente'] ? 'selected' : '' ?>>No - Reinicio manual</option>
                                </select>
                                <small class="form-text text-muted">
                                    Los acumulados con reinicio automático se resetean al iniciar cada período
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Fecha Inicio Período -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_inicio_periodo">
                                    Fecha Inicio Período
                                    <small class="text-muted">(Opcional)</small>
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_inicio_periodo" 
                                       name="fecha_inicio_periodo" 
                                       value="<?= $tipoAcumulado['fecha_inicio_periodo'] ?>">
                            </div>
                        </div>
                        
                        <!-- Fecha Fin Período -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_fin_periodo">
                                    Fecha Fin Período
                                    <small class="text-muted">(Opcional)</small>
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_fin_periodo" 
                                       name="fecha_fin_periodo" 
                                       value="<?= $tipoAcumulado['fecha_fin_periodo'] ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="activo">Estado</label>
                                <select class="form-control" id="activo" name="activo">
                                    <option value="1" <?= $tipoAcumulado['activo'] ? 'selected' : '' ?>>Activo</option>
                                    <option value="0" <?= !$tipoAcumulado['activo'] ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Información:</strong>
                                <ul class="mb-0 mt-2">
                                    <li><strong>Código:</strong> Identificador único, se usa en los conceptos para asociar acumulados</li>
                                    <li><strong>Periodicidad:</strong> Define cada cuánto tiempo se reinicia el acumulado (si está configurado)</li>
                                    <li><strong>Reinicio Automático:</strong> Si está habilitado, el acumulado se resetea automáticamente</li>
                                    <li><strong>Fechas:</strong> Definen el período actual del acumulado (opcional)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Tipo
                            </button>
                            <a href="<?= url('/panel/tipos-acumulados') ?>" class="btn btn-secondary ml-2">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> 
                                Creado: <?= date('d/m/Y H:i', strtotime($tipoAcumulado['created_at'])) ?>
                                <?php if ($tipoAcumulado['updated_at'] != $tipoAcumulado['created_at']): ?>
                                    <br>Actualizado: <?= date('d/m/Y H:i', strtotime($tipoAcumulado['updated_at'])) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<style>
.alert-info ul {
    padding-left: 1.2rem;
}

.alert-info li {
    margin-bottom: 0.25rem;
}

.form-text {
    font-size: 0.8rem;
}

.card-footer small {
    line-height: 1.4;
}
</style>