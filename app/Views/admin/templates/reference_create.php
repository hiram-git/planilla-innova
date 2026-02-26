<?php
$page_title = 'Agregar ' . $singular_name;

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Agregar ' . $singular_name . '</h3>
                <div class="card-tools">
                    <a href="' . url('panel/' . $route_name, false) . '" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            <form action="' . url('panel/' . $route_name, false) . '" method="POST">
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
                                <label for="codigo">Código *</label>
                                <input type="text" class="form-control" id="codigo" name="codigo" 
                                       value="' . ($_SESSION['old_data']['codigo'] ?? '') . '" required>
                                <small class="form-text text-muted">Código único del ' . strtolower($singular_name) . '</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="' . ($_SESSION['old_data']['nombre'] ?? '') . '" required>
                                <small class="form-text text-muted">Nombre descriptivo del ' . strtolower($singular_name) . '</small>
                            </div>
                        </div>
                    </div>';

// Campos específicos para horarios
if ($route_name === 'schedules') {
    $content .= '
                    <div class="card card-outline card-primary mt-2">
                        <div class="card-header py-2 d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0"><i class="fas fa-clock"></i> Horario y tolerancias</h3>
                            <span class="text-sm text-muted">Entrada, almuerzo y salida en una vista compacta</span>
                        </div>
                        <div class="card-body pt-3 pb-2">
                            <div class="row">
                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <label class="font-weight-bold mb-1" for="time_in">Entrada *</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="text" class="form-control timepicker text-center" id="time_in" name="time_in"
                                               value="' . ($_SESSION['old_data']['time_in'] ?? '') . '" required>
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
                                                <input type="number" class="form-control" id="time_in_tolerance_before"
                                                       name="time_in_tolerance_before" min="0" max="60" step="1"
                                                       value="' . ($_SESSION['old_data']['time_in_tolerance_before'] ?? 0) . '"
                                                       placeholder="Antes">
                                            </div>
                                            <small class="text-muted">Tolerancia de entrada</small>
                                        </div>
                                        <div class="col-6 pl-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                                </div>
                                                <input type="number" class="form-control" id="time_in_tolerance_after"
                                                       name="time_in_tolerance_after" min="0" max="60" step="1"
                                                       value="' . ($_SESSION['old_data']['time_in_tolerance_after'] ?? 0) . '"
                                                       placeholder="Después">
                                            </div>
                                            <small class="text-muted">Tolerancia de salida</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <label class="font-weight-bold mb-1" for="salida_almuerzo">Salida almuerzo</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="text" class="form-control timepicker text-center" id="salida_almuerzo" name="salida_almuerzo"
                                               value="' . ($_SESSION['old_data']['salida_almuerzo'] ?? '') . '">
                                        <div class="input-group-append">
                                            <div class="input-group-text"><i class="far fa-clock"></i></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 pr-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="lunch_out_tolerance_before"
                                                       name="lunch_out_tolerance_before" min="0" max="60" step="1"
                                                       value="' . ($_SESSION['old_data']['lunch_out_tolerance_before'] ?? 0) . '"
                                                       placeholder="Antes">
                                            </div>
                                            <small class="text-muted">Tolerancia de entrada</small>
                                        </div>
                                        <div class="col-6 pl-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="lunch_out_tolerance_after"
                                                       name="lunch_out_tolerance_after" min="0" max="60" step="1"
                                                       value="' . ($_SESSION['old_data']['lunch_out_tolerance_after'] ?? 0) . '"
                                                       placeholder="Después">
                                            </div>
                                            <small class="text-muted">Tolerancia de salida</small>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Opcional</small>
                                </div>

                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <label class="font-weight-bold mb-1" for="entrada_almuerzo">Regreso almuerzo</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="text" class="form-control timepicker text-center" id="entrada_almuerzo" name="entrada_almuerzo"
                                               value="' . ($_SESSION['old_data']['entrada_almuerzo'] ?? '') . '">
                                        <div class="input-group-append">
                                            <div class="input-group-text"><i class="far fa-clock"></i></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 pr-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="lunch_in_tolerance_before"
                                                       name="lunch_in_tolerance_before" min="0" max="60" step="1"
                                                       value="' . ($_SESSION['old_data']['lunch_in_tolerance_before'] ?? 0) . '"
                                                       placeholder="Antes">
                                            </div>
                                            <small class="text-muted">Tolerancia de entrada</small>
                                        </div>
                                        <div class="col-6 pl-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="lunch_in_tolerance_after"
                                                       name="lunch_in_tolerance_after" min="0" max="60" step="1"
                                                       value="' . ($_SESSION['old_data']['lunch_in_tolerance_after'] ?? 0) . '"
                                                       placeholder="Después">
                                            </div>
                                            <small class="text-muted">Tolerancia de salida</small>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Opcional</small>
                                </div>

                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <label class="font-weight-bold mb-1" for="time_out">Salida *</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="text" class="form-control timepicker text-center" id="time_out" name="time_out"
                                               value="' . ($_SESSION['old_data']['time_out'] ?? '') . '" required>
                                        <div class="input-group-append">
                                            <div class="input-group-text"><i class="far fa-clock"></i></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 pr-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="time_out_tolerance_before"
                                                       name="time_out_tolerance_before" min="0" max="60" step="1"
                                                       value="' . ($_SESSION['old_data']['time_out_tolerance_before'] ?? 0) . '"
                                                       placeholder="Antes">
                                            </div>
                                            <small class="text-muted">Tolerancia de entrada</small>
                                        </div>
                                        <div class="col-6 pl-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-clock"></i></span></div>
                                                <input type="number" class="form-control" id="time_out_tolerance_after"
                                                       name="time_out_tolerance_after" min="0" max="60" step="1"
                                                       value="' . ($_SESSION['old_data']['time_out_tolerance_after'] ?? 0) . '"
                                                       placeholder="Después">
                                            </div>
                                            <small class="text-muted">Tolerancia de salida</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>';
}

$content .= '
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3">' . ($_SESSION['old_data']['descripcion'] ?? '') . '</textarea>
                        <small class="form-text text-muted">Descripción opcional del ' . strtolower($singular_name) . '</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar ' . $singular_name . '
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
    });
    </script>';
}

// GSAP Animations para botones
$scripts .= '
<script>
// ========================================
// GSAP ANIMATIONS - Create Form Buttons
// ========================================

$(document).ready(function() {
    if (typeof gsap !== "undefined") {
        setupCreateFormButtonAnimations();
    }
});

function setupCreateFormButtonAnimations() {
    // Seleccionar todos los botones del formulario
    const backButton = $(".card-tools .btn-secondary");
    const submitButton = $(".card-footer .btn-primary");
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

    // Hover effect en botón "Guardar" (Primary)
    submitButton.on({
        "mouseenter": function() {
            gsap.to(this, {
                scale: 1.05,
                boxShadow: "0 5px 15px rgba(0,123,255,0.4)",
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
