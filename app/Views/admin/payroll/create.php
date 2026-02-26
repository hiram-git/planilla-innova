<?php
/**
 * Vista: Crear Nueva Planilla
 */
$title = $data['page_title'] ?? 'Nueva Planilla';
$csrf_token = $data['csrf_token'] ?? '';
?>
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-plus"></i> Crear Nueva Planilla
                </h3>
            </div>
            <form method="POST" action="<?= \App\Core\UrlHelper::payroll('store') ?>" id="payrollForm">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token?>">
                    <input type="hidden" id="tipo_planilla_id" name="tipo_planilla_id" value="">
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción de la Planilla *</label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion" required readonly
                               value="PLANILLA" placeholder="Se generará automáticamente...">
                        <small class="form-text text-muted">
                            <i class="fas fa-magic text-primary"></i>
                            Se genera automáticamente: <strong>PLANILLA [TIPO] [FRECUENCIA] DESDE [FECHA_INICIO] HASTA [FECHA_FIN]</strong>
                        </small>
                    </div>
                    
                    <div class="callout callout-info">
                        <h5><i class="fas fa-info-circle"></i> Tipo de Planilla</h5>
                        <p>Se utilizará el tipo seleccionado en la barra de navegación superior.</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha">Fecha de Planilla *</label>
                                <input type="date" class="form-control" id="fecha" name="fecha" required value="<?= date('Y-m-d') ?>">
                                <small class="form-text text-muted">Fecha de emisión de la planilla</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="frecuencia_id">Frecuencia *</label>
                                <select class="form-control" id="frecuencia_id" name="frecuencia_id" required>
                                    <option value="">Seleccione una frecuencia</option>
                                    <?php if (!empty($data['frecuencias'])): ?>
                                        <?php foreach ($data['frecuencias'] as $frecuencia): ?>
                                            <option value="<?= $frecuencia['id'] ?>" data-codigo="<?= $frecuencia['codigo'] ?>">
                                                <?= htmlspecialchars($frecuencia['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="periodo_inicio">Fecha Inicio del Período *</label>
                                <input type="date" class="form-control" id="periodo_inicio" name="periodo_inicio" required>
                                <small class="form-text text-muted">Fecha de inicio del período laboral</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="periodo_fin">Fecha Fin del Período *</label>
                                <input type="date" class="form-control" id="periodo_fin" name="periodo_fin" required>
                                <small class="form-text text-muted">Fecha final del período laboral</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="callout callout-info">
                        <h5><i class="fas fa-info-circle"></i> Información</h5>
                        <ul class="mb-0">
                            <li>La planilla se creará en estado <strong>PENDIENTE</strong></li>
                            <li>Después de crear, podrá <strong>procesarla</strong> para generar los cálculos</li>
                            <li>El procesamiento incluirá todos los empleados activos del período</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="<?= \App\Core\UrlHelper::payroll('') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Crear Planilla
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts para la vista de creación de planillas -->
<script src="<?= \App\Core\UrlHelper::url('/assets/javascript/modules/payroll/create.js') ?>"></script>

<script>
$(document).ready(function() {
    // =========================================================================
    // GSAP ANIMATIONS - BOTONES
    // =========================================================================

    /**
     * 1. Animar entrada de botones del footer
     */
    function animateFooterButtons() {
        const cancelBtn = $('.card-footer .btn-secondary');
        const submitBtn = $('.card-footer .btn-primary');

        if (cancelBtn.length > 0) {
            gsap.from(cancelBtn, {
                opacity: 0,
                x: -20,
                duration: 0.5,
                delay: 0.3,
                ease: "power2.out",
                clearProps: "all"
            });
        }

        if (submitBtn.length > 0) {
            gsap.from(submitBtn, {
                opacity: 0,
                x: 20,
                duration: 0.5,
                delay: 0.3,
                ease: "power2.out",
                clearProps: "all"
            });
        }
    }

    /**
     * 2. Configurar animación hover en botón Cancelar
     */
    function setupCancelButtonAnimation() {
        const cancelBtn = $('.card-footer .btn-secondary');

        cancelBtn.on('mouseenter', function() {
            gsap.to($(this).find('i'), {
                x: -5,
                duration: 0.3,
                ease: "power2.out"
            });
        });

        cancelBtn.on('mouseleave', function() {
            gsap.to($(this).find('i'), {
                x: 0,
                duration: 0.3,
                ease: "power2.inOut"
            });
        });
    }

    /**
     * 3. Configurar animación hover en botón Crear Planilla
     */
    function setupSubmitButtonAnimation() {
        const submitBtn = $('.card-footer .btn-primary');

        submitBtn.on('mouseenter', function() {
            gsap.to($(this), {
                scale: 1.05,
                duration: 0.3,
                ease: "power2.out"
            });
            gsap.to($(this).find('i'), {
                rotation: 360,
                duration: 0.5,
                ease: "power2.out"
            });
        });

        submitBtn.on('mouseleave', function() {
            gsap.to($(this), {
                scale: 1,
                duration: 0.3,
                ease: "power2.inOut"
            });
            gsap.to($(this).find('i'), {
                rotation: 0,
                duration: 0.3,
                ease: "power2.inOut"
            });
        });
    }

    /**
     * 4. Animación de pulsación al hacer clic en botón submit
     */
    function setupSubmitClickAnimation() {
        const submitBtn = $('.card-footer .btn-primary');

        submitBtn.on('click', function(e) {
            // Solo animar si el formulario es válido
            const tipoPlanillaId = $('#tipo_planilla_id').val();
            const frecuenciaId = $('#frecuencia_id').val();

            if (tipoPlanillaId && frecuenciaId) {
                gsap.to($(this), {
                    scale: 0.95,
                    duration: 0.1,
                    yoyo: true,
                    repeat: 1,
                    ease: "power2.inOut"
                });
            }
        });
    }

    // Ejecutar animaciones
    animateFooterButtons();
    setupCancelButtonAnimation();
    setupSubmitButtonAnimation();
    setupSubmitClickAnimation();

    // =========================================================================
    // VALIDACIÓN DEL FORMULARIO
    // =========================================================================

    $('#payrollForm').on('submit', function(e) {
        const tipoPlanillaId = $('#tipo_planilla_id').val();
        const frecuenciaId = $('#frecuencia_id').val();
        const fecha = $('#fecha').val();
        const periodoInicio = $('#periodo_inicio').val();
        const periodoFin = $('#periodo_fin').val();
        const descripcion = $('#descripcion').val();

        if (!tipoPlanillaId) {
            e.preventDefault();
            alert('Por favor, selecciona un tipo de planilla desde el menú superior.');
            return false;
        }

        if (!frecuenciaId || !fecha || !periodoInicio || !periodoFin || !descripcion) {
            e.preventDefault();
            alert('Por favor, completa todos los campos obligatorios.');
            return false;
        }

        console.log('Formulario válido, enviando...', {
            tipoPlanillaId: tipoPlanillaId,
            frecuenciaId: frecuenciaId,
            fecha: fecha,
            periodoInicio: periodoInicio,
            periodoFin: periodoFin,
            descripcion: descripcion
        });
    });
});
</script>


<style>
.form-group label {
    font-weight: 600;
    color: #495057;
}
.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.card-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
}
.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
}
</style>