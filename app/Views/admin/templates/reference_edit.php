<?php
$page_title = 'Editar ' . $singular_name;

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Editar ' . $singular_name . '</h3>
                <div class="card-tools">
                    <a href="' . url('panel/' . $route_name, false) . '" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            <form action="' . url('panel/' . $route_name . '/' . $item['id'], false) . '" method="POST">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="' . $csrf_token . '">';

if (isset($_SESSION['errors'])) {
    $content .= '
                    <div class="alert alert-danger">
                        <ul class="mb-0">';
    foreach ($_SESSION['errors'] as $error) {
        $content .= '<li>' . htmlspecialchars($error) . '</li>';
    }
    $content .= '
                        </ul>
                    </div>';
    unset($_SESSION['errors']);
}

$content .= '
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_codigo">Código *</label>
                                <input type="text" class="form-control" id="edit_codigo" name="edit_codigo" 
                                       value="' . htmlspecialchars($item['codigo']) . '" required>
                                <small class="form-text text-muted">Código único del ' . strtolower($singular_name) . '</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_nombre">Nombre *</label>
                                <input type="text" class="form-control" id="edit_nombre" name="edit_nombre" 
                                       value="' . htmlspecialchars($item['nombre']) . '" required>
                                <small class="form-text text-muted">Nombre descriptivo del ' . strtolower($singular_name) . '</small>
                            </div>
                        </div>
                    </div>';

// Campos específicos para horarios
if ($route_name === 'schedules') {
    $content .= '
                    <div class="card card-outline card-primary mb-3 mt-2">
                        <div class="card-header py-2 d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0"><i class="fas fa-clock"></i> Horario y tolerancias</h3>
                            <span class="text-sm text-muted">Entrada, almuerzo y salida en una vista compacta</span>
                        </div>
                        <div class="card-body pt-3 pb-2">
                            <div class="row">
                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <label class="font-weight-bold mb-1" for="edit_time_in">Entrada *</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="text" class="form-control timepicker text-center" id="edit_time_in" name="edit_time_in"
                                               value="' . $item['time_in'] . '" required>
                                        <div class="input-group-append">
                                            <div class="input-group-text"><i class="far fa-clock"></i></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 pr-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                                </div>
                                                <input type="number" class="form-control" id="edit_time_in_tolerance_before"
                                                       name="edit_time_in_tolerance_before" min="0" max="60" step="1"
                                                       value="' . (int)($item['time_in_tolerance_before'] ?? 0) . '"
                                                       placeholder="Antes">
                                            </div>
                                            <small class="text-muted">Tolerancia de entrada</small>
                                        </div>
                                        <div class="col-6 pl-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                                </div>
                                                <input type="number" class="form-control" id="edit_time_in_tolerance_after"
                                                       name="edit_time_in_tolerance_after" min="0" max="60" step="1"
                                                       value="' . (int)($item['time_in_tolerance_after'] ?? 0) . '"
                                                       placeholder="Después">
                                            </div>
                                            <small class="text-muted">Tolerancia de salida</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <label class="font-weight-bold mb-1" for="edit_salida_almuerzo">Salida almuerzo</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="text" class="form-control timepicker text-center" id="edit_salida_almuerzo" name="edit_salida_almuerzo"
                                               value="' . ($item['salida_almuerzo'] ?? '') . '">
                                        <div class="input-group-append">
                                            <div class="input-group-text"><i class="far fa-clock"></i></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 pr-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="edit_lunch_out_tolerance_before"
                                                       name="edit_lunch_out_tolerance_before" min="0" max="60" step="1"
                                                       value="' . (int)($item['lunch_out_tolerance_before'] ?? 0) . '"
                                                       placeholder="Antes">
                                            </div>
                                            <small class="text-muted">Tolerancia de entrada</small>
                                        </div>
                                        <div class="col-6 pl-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="edit_lunch_out_tolerance_after"
                                                       name="edit_lunch_out_tolerance_after" min="0" max="60" step="1"
                                                       value="' . (int)($item['lunch_out_tolerance_after'] ?? 0) . '"
                                                       placeholder="Después">
                                            </div>
                                            <small class="text-muted">Tolerancia de salida</small>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Opcional</small>
                                </div>

                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <label class="font-weight-bold mb-1" for="edit_entrada_almuerzo">Regreso almuerzo</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="text" class="form-control timepicker text-center" id="edit_entrada_almuerzo" name="edit_entrada_almuerzo"
                                               value="' . ($item['entrada_almuerzo'] ?? '') . '">
                                        <div class="input-group-append">
                                            <div class="input-group-text"><i class="far fa-clock"></i></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 pr-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="edit_lunch_in_tolerance_before"
                                                       name="edit_lunch_in_tolerance_before" min="0" max="60" step="1"
                                                       value="' . (int)($item['lunch_in_tolerance_before'] ?? 0) . '"
                                                       placeholder="Antes">
                                            </div>
                                            <small class="text-muted">Tolerancia de entrada</small>
                                        </div>
                                        <div class="col-6 pl-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="edit_lunch_in_tolerance_after"
                                                       name="edit_lunch_in_tolerance_after" min="0" max="60" step="1"
                                                       value="' . (int)($item['lunch_in_tolerance_after'] ?? 0) . '"
                                                       placeholder="Después">
                                            </div>
                                            <small class="text-muted">Tolerancia de salida</small>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Opcional</small>
                                </div>

                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <label class="font-weight-bold mb-1" for="edit_time_out">Salida *</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="text" class="form-control timepicker text-center" id="edit_time_out" name="edit_time_out"
                                               value="' . $item['time_out'] . '" required>
                                        <div class="input-group-append">
                                            <div class="input-group-text"><i class="far fa-clock"></i></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 pr-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="edit_time_out_tolerance_before"
                                                       name="edit_time_out_tolerance_before" min="0" max="60" step="1"
                                                       value="' . (int)($item['time_out_tolerance_before'] ?? 0) . '"
                                                       placeholder="Antes">
                                            </div>
                                            <small class="text-muted">Tolerancia de entrada</small>
                                        </div>
                                        <div class="col-6 pl-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="edit_time_out_tolerance_after"
                                                       name="edit_time_out_tolerance_after" min="0" max="60" step="1"
                                                       value="' . (int)($item['time_out_tolerance_after'] ?? 0) . '"
                                                       placeholder="Después">
                                            </div>
                                            <small class="text-muted">Tolerancia de salida</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-2">

                            <div class="row align-items-start">
                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="edit_lunch_flexible" name="edit_lunch_flexible" value="1" ' . (!empty($item['lunch_flexible']) ? 'checked' : '') . '>
                                        <label class="custom-control-label font-weight-bold" for="edit_lunch_flexible">
                                            <i class="fas fa-utensils"></i> Almuerzo flexible
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Descuenta minutos fijos sin penalizar exceso ni tardanza por almuerzo. Los campos rígidos de almuerzo quedan como referencia visual.
                                    </small>
                                </div>
                                <div class="col-lg-3 col-md-6 col-12 mb-3" id="edit_lunch_flexible_wrapper" style="' . (empty($item['lunch_flexible']) ? 'display: none;' : '') . '">
                                    <label class="font-weight-bold mb-1" for="edit_lunch_flexible_minutes">Minutos de almuerzo</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="edit_lunch_flexible_minutes"
                                               name="edit_lunch_flexible_minutes" min="1" max="120" step="1"
                                               value="' . (int)($item['lunch_flexible_minutes'] ?? 0) . '"
                                               placeholder="ej. 30">
                                        <div class="input-group-append">
                                            <span class="input-group-text">min</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">Requerido si almuerzo flexible está activo (1–120)</small>
                                </div>
                            </div>
                        </div>
                    </div>';
}

$content .= '
                    <div class="form-group">
                        <label for="edit_descripcion">Descripción</label>
                        <textarea class="form-control" id="edit_descripcion" name="edit_descripcion" rows="3">' . htmlspecialchars($item['descripcion'] ?? '') . '</textarea>
                        <small class="form-text text-muted">Descripción opcional del ' . strtolower($singular_name) . '</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="edit_activo" name="edit_activo" ' . ($item['activo'] ? 'checked' : '') . '>
                            <label class="custom-control-label" for="edit_activo">' . $singular_name . ' activo</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Actualizar ' . $singular_name . '
                    </button>
                    <a href="' . url('panel/' . $route_name, false) . '" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>';

// JavaScript para horarios si es necesario
$scripts = '';

if ($route_name === 'schedules') {
    $scripts .= '
    <script src="' . url('plugins/inputmask/jquery.inputmask.min.js', false) . '"></script>
    <script>
    $(document).ready(function() {
        $(".timepicker").inputmask("99:99", {
            placeholder: "__:__",
            insertMode: false,
            showMaskOnHover: false,
            showMaskOnFocus: true
        });

        // Toggle visibilidad de "Minutos de almuerzo" según checkbox de almuerzo flexible
        var $flexCheckbox = $("#edit_lunch_flexible");
        var $flexWrapper  = $("#edit_lunch_flexible_wrapper");
        var $flexInput    = $("#edit_lunch_flexible_minutes");

        function toggleFlexibleLunch() {
            if ($flexCheckbox.is(":checked")) {
                $flexWrapper.show();
            } else {
                $flexWrapper.hide();
                $flexInput.val(0);
            }
        }

        $flexCheckbox.on("change", toggleFlexibleLunch);
        toggleFlexibleLunch();
    });
    </script>';
}

// GSAP Animations para botones
$scripts .= '
<script>
// ========================================
// GSAP ANIMATIONS - Edit Form Buttons
// ========================================

$(document).ready(function() {
    if (typeof gsap !== "undefined") {
        setupEditFormButtonAnimations();
    }
});

function setupEditFormButtonAnimations() {
    // Seleccionar todos los botones del formulario
    const backButton = $(".card-tools .btn-secondary");
    const submitButton = $(".card-footer .btn-success");
    const cancelButton = $(".card-footer .btn-secondary");

    // Animación inicial de entrada para los botones del footer
    gsap.fromTo([submitButton, cancelButton],
        {
            opacity: 0,
            y: 10
        },
        {
            opacity: 1,
            y: 0,
            duration: 0.4,
            stagger: 0.1,
            ease: "power2.out",
            clearProps: "transform,y"
        }
    );

    // Hover effect en botón "Volver"
    backButton.on({
        "mouseenter": function() {
            gsap.to(this, {
                scale: 1.05,
                boxShadow: "0 5px 15px rgba(108,117,125,0.4)",
                duration: 0.3,
                ease: "power2.out"
            });
        },
        "mouseleave": function() {
            gsap.to(this, {
                scale: 1,
                boxShadow: "none",
                duration: 0.3,
                ease: "power2.out"
            });
        }
    });

    // Hover effect en botón "Actualizar" (Success)
    submitButton.on({
        "mouseenter": function() {
            gsap.to(this, {
                scale: 1.05,
                boxShadow: "0 5px 15px rgba(40,167,69,0.4)",
                duration: 0.3,
                ease: "power2.out"
            });
        },
        "mouseleave": function() {
            gsap.to(this, {
                scale: 1,
                boxShadow: "none",
                duration: 0.3,
                ease: "power2.out"
            });
        }
    });

    // Hover effect en botón "Cancelar"
    cancelButton.on({
        "mouseenter": function() {
            gsap.to(this, {
                scale: 1.05,
                boxShadow: "0 5px 15px rgba(108,117,125,0.4)",
                duration: 0.3,
                ease: "power2.out"
            });
        },
        "mouseleave": function() {
            gsap.to(this, {
                scale: 1,
                boxShadow: "none",
                duration: 0.3,
                ease: "power2.out"
            });
        }
    });

    // Animación de iconos dentro de botones (rotación 360°)
    const buttonIcons = $(".card-tools .btn i, .card-footer .btn i");
    buttonIcons.off("mouseenter.gsap").on("mouseenter.gsap", function() {
        gsap.to(this, {
            rotation: 360,
            duration: 0.5,
            ease: "power2.inOut"
        });
    });
}
</script>';

unset($_SESSION['old_data']);

$styles = '';

include __DIR__ . '/../../layouts/admin.php';
?>
