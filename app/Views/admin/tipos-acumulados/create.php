<?php
$title = 'Crear Tipo de Acumulado';

// JavaScript optimizado modular para crear
$scripts = '
<script>
// Esperar a que jQuery esté disponible
(function checkjQuery() {
    if (typeof $ === "undefined") {
        setTimeout(checkjQuery, 50);
        return;
    }
    
    $(document).ready(function() {
        initCreateForm();
    });
})();

function initCreateForm() {
    // Transform code to uppercase
    $("#codigo").on("input", function() {
        let value = $(this).val().toUpperCase();
        $(this).val(value.replace(/[^A-Z0-9_]/g, ""));
        
        // Clear previous validation
        $(this).removeClass("is-invalid");
        
        if (value.length > 2) {
            checkDuplicate(value);
        }
    });

    // Validate date range
    $("#fecha_inicio_periodo, #fecha_fin_periodo").on("change", function() {
        validateDateRange();
    });

    // Form submission
    $("#createForm").on("submit", function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitForm();
        }
    });

    function checkDuplicate(codigo) {
        $.ajax({
            url: "' . url('/panel/tipos-acumulados/check-duplicate') . '",
            method: "GET",
            data: { codigo: codigo },
            success: function(response) {
                if (response.exists) {
                    $("#codigo").addClass("is-invalid");
                    $("#codigo").siblings(".invalid-feedback").text("Ya existe un tipo de acumulado con este código");
                }
            }
        });
    }

    function validateDateRange() {
        var fechaInicio = $("#fecha_inicio_periodo").val();
        var fechaFin = $("#fecha_fin_periodo").val();
        
        if (fechaInicio && fechaFin && fechaInicio >= fechaFin) {
            $("#fecha_fin_periodo").addClass("is-invalid");
            $("#fecha_fin_periodo").siblings(".invalid-feedback").text("La fecha de fin debe ser posterior a la fecha de inicio");
            return false;
        } else {
            $("#fecha_fin_periodo").removeClass("is-invalid");
            return true;
        }
    }

    function validateForm() {
        var isValid = true;
        
        // Clear previous validation
        $(".is-invalid").removeClass("is-invalid");
        
        // Required fields validation
        $("input[required], select[required]").each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass("is-invalid");
                $(this).siblings(".invalid-feedback").text("Este campo es obligatorio");
                isValid = false;
            }
        });
        
        // Date range validation
        if (!validateDateRange()) {
            isValid = false;
        }
        
        return isValid;
    }

    function submitForm() {
        $("#submitBtn").prop("disabled", true).html("<i class=\"fas fa-spinner fa-spin\"></i> Guardando...");
        
        $.ajax({
            url: "' . url('/panel/tipos-acumulados/store') . '",
            method: "POST",
            data: $("#createForm").serialize() + "&csrf_token=' . \App\Core\Security::generateToken() . '",
            dataType: "json",
            success: function(response) {
                console.log("Response received:", response);
                if (response && response.success) {
                    if (typeof toastr !== "undefined") {
                        toastr.success("Tipo de acumulado creado exitosamente", "Éxito", {
                            timeOut: 3000,
                            onHidden: function() {
                                window.location.href = "' . url('/panel/tipos-acumulados') . '";
                            }
                        });
                    } else {
                        alert("Tipo de acumulado creado exitosamente");
                        window.location.href = "' . url('/panel/tipos-acumulados') . '";
                    }
                } else {
                    let errorMsg = "Error: " + (response.message || "No se pudo crear el tipo");
                    if (typeof toastr !== "undefined") {
                        toastr.error(errorMsg, "Error");
                    } else {
                        alert(errorMsg);
                    }
                    
                    // Show field errors
                    if (response.errors) {
                        $.each(response.errors, function(field, message) {
                            $("#" + field).addClass("is-invalid");
                            $("#" + field).siblings(".invalid-feedback").text(message);
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", {xhr: xhr, status: status, error: error, responseText: xhr.responseText});
                
                let errorMessage = "Error de conexión. No se pudo crear el tipo.";
                
                // Try to parse error response
                try {
                    let errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                } catch (e) {
                    // Keep default message
                }
                
                if (typeof toastr !== "undefined") {
                    toastr.error(errorMessage, "Error de conexión");
                } else {
                    alert(errorMessage);
                }
            },
            complete: function() {
                $("#submitBtn").prop("disabled", false).html("<i class=\"fas fa-save\"></i> Guardar Tipo de Acumulado");
            }
        });
    }
}
</script>';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-plus"></i> Crear Nuevo Tipo de Acumulado
                </h3>
                <div class="card-tools">
                    <a href="<?= url('/panel/tipos-acumulados') ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>
                </div>
            </div>
            <form id="createForm">
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
                                       placeholder="ej: VACAC, AGUINALDO, BONO14"
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
                                       placeholder="ej: Acumulado de Vacaciones"
                                       maxlength="100"
                                       required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Periodicidad -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="periodicidad">
                                    Periodicidad <span class="text-danger">*</span>
                                    <small class="text-muted d-block">¿Cada cuánto se reinicia?</small>
                                </label>
                                <select class="form-control" id="periodicidad" name="periodicidad" required>
                                    <option value="ANUAL" selected>Anual</option>
                                    <option value="MENSUAL">Mensual</option>
                                    <option value="TRIMESTRAL">Trimestral</option>
                                    <option value="SEMESTRAL">Semestral</option>
                                    <option value="ESPECIAL">Especial (No automático)</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <!-- Fecha Inicio -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fecha_inicio_periodo">
                                    Fecha Inicio Período
                                    <small class="text-muted d-block">(Opcional)</small>
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_inicio_periodo" 
                                       name="fecha_inicio_periodo">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <!-- Fecha Fin -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fecha_fin_periodo">
                                    Fecha Fin Período
                                    <small class="text-muted d-block">(Opcional)</small>
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_fin_periodo" 
                                       name="fecha_fin_periodo">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Configuración -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Configuración</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="reinicia_automaticamente" 
                                           name="reinicia_automaticamente" 
                                           checked>
                                    <label class="custom-control-label" for="reinicia_automaticamente">
                                        Reiniciar automáticamente
                                        <small class="text-muted d-block">Se reinicia al cambiar de período</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Estado -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Estado Inicial</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="activo" 
                                           name="activo" 
                                           checked>
                                    <label class="custom-control-label" for="activo">
                                        Activo
                                        <small class="text-muted d-block">Disponible para asignar a conceptos</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ayuda -->
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Información sobre Periodicidades:</h6>
                                <ul class="mb-0">
                                    <li><strong>ANUAL:</strong> Para aguinaldo, bono 14, vacaciones (reinicia cada año)</li>
                                    <li><strong>MENSUAL:</strong> Para acumulados que se reinician cada mes</li>
                                    <li><strong>TRIMESTRAL:</strong> Reinicia cada 3 meses</li>
                                    <li><strong>SEMESTRAL:</strong> Reinicia cada 6 meses</li>
                                    <li><strong>ESPECIAL:</strong> Para indemnizaciones (no se reinicia automáticamente)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <i class="fas fa-save"></i> Guardar Tipo de Acumulado
                    </button>
                    <a href="<?= url('/panel/tipos-acumulados') ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
