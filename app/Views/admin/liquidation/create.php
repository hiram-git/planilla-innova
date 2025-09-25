<?php
// Incluir helpers para funciones CSRF
require_once __DIR__ . '/../../../Core/helpers.php';

$pageTitle = "Nueva Liquidación - " . htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']);
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-handshake mr-2"></i>
                    Nueva Liquidación
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/liquidation') ?>">Liquidaciones</a></li>
                    <li class="breadcrumb-item active">Nueva</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <!-- Información del Empleado -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user mr-2"></i>
                    Información del Empleado
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Nombre:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) ?></dd>

                            <dt class="col-sm-4">Ficha:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($employee['employee_id']) ?></dd>

                            <dt class="col-sm-4">Cédula:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($employee['document_id'] ?? 'N/A') ?></dd>

                            <dt class="col-sm-4">Cargo:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($employee['position_name'] ?? 'N/A') ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Fecha Ingreso:</dt>
                            <dd class="col-sm-8"><?= date('d/m/Y', strtotime($employee['fecha_ingreso'])) ?></dd>

                            <dt class="col-sm-4">Período Trabajado:</dt>
                            <dd class="col-sm-8" id="periodo-trabajado">
                                <strong><?= $periodo_trabajado['anos'] ?> años, <?= $periodo_trabajado['meses'] ?> meses, <?= $periodo_trabajado['dias'] ?> días</strong>
                                <br><small class="text-muted">Hasta hoy: <?= number_format($periodo_trabajado['total_dias_laborables']) ?> días laborables</small>
                            </dd>

                            <dt class="col-sm-4">Salario:</dt>
                            <dd class="col-sm-8">
                                <?php if ($employee['sueldo_individual']): ?>
                                    <?= currency_symbol() ?><?= number_format($employee['sueldo_individual'], 2) ?>
                                <?php else: ?>
                                    <span class="text-muted">Según posición</span>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4">Estado:</dt>
                            <dd class="col-sm-8">
                                <span class="badge badge-success">ACTIVO</span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Liquidación -->
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Datos de Terminación
                </h3>
            </div>
            <form method="POST" action="<?= \App\Core\UrlHelper::route('panel/liquidation/store') ?>">
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="termination_date">Fecha de Terminación *</label>
                                <input type="date"
                                       class="form-control <?= isset($errors['termination_date']) ? 'is-invalid' : '' ?>"
                                       id="termination_date"
                                       name="termination_date"
                                       value="<?= date('Y-m-d') ?>"
                                       max="<?= date('Y-m-d') ?>"
                                       min="<?= $employee['fecha_ingreso'] ?>"
                                       required>
                                <?php if (isset($errors['termination_date'])): ?>
                                    <div class="invalid-feedback"><?= $errors['termination_date'] ?></div>
                                <?php endif; ?>
                                <small class="form-text text-muted">
                                    No puede ser anterior a la fecha de ingreso ni futura
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="termination_type">Tipo de Terminación *</label>
                                <select class="form-control <?= isset($errors['termination_type']) ? 'is-invalid' : '' ?>"
                                        id="termination_type"
                                        name="termination_type"
                                        required>
                                    <option value="">Seleccione un tipo</option>
                                    <option value="DESPIDO_SIN_CAUSA">Despido sin Causa</option>
                                    <option value="DESPIDO_CON_CAUSA">Despido con Causa</option>
                                    <option value="RENUNCIA">Renuncia</option>
                                    <option value="MUTUO_ACUERDO">Mutuo Acuerdo</option>
                                </select>
                                <?php if (isset($errors['termination_type'])): ?>
                                    <div class="invalid-feedback"><?= $errors['termination_type'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="notice_period_days">Días de Preaviso</label>
                                <input type="number"
                                       class="form-control <?= isset($errors['notice_period_days']) ? 'is-invalid' : '' ?>"
                                       id="notice_period_days"
                                       name="notice_period_days"
                                       value="30"
                                       min="0"
                                       max="365">
                                <?php if (isset($errors['notice_period_days'])): ?>
                                    <div class="invalid-feedback"><?= $errors['notice_period_days'] ?></div>
                                <?php endif; ?>
                                <small class="form-text text-muted">
                                    Días de preaviso según ley (default: 30 días)
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Espacio para futuros campos adicionales -->
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reason">Motivo de la Terminación <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors['reason']) ? 'is-invalid' : '' ?>"
                                  id="reason"
                                  name="reason"
                                  rows="3"
                                  required
                                  placeholder="Describa brevemente el motivo de la terminación..."><?= old('reason') ?></textarea>
                        <?php if (isset($errors['reason'])): ?>
                            <div class="invalid-feedback"><?= $errors['reason'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Información Legal -->
                    <div class="callout callout-warning">
                        <h5><i class="fas fa-balance-scale mr-2"></i>Información Legal - Panamá</h5>
                        <p><strong>Según el Código de Trabajo de Panamá, este empleado tiene derecho a:</strong></p>
                        <ul class="mb-0">
                            <li><strong>Prima de Antigüedad:</strong> <?= $anos_trabajados ?> semanas de salario</li>
                            <li><strong>Indemnización:</strong>
                                <?php if ($anos_trabajados <= 10): ?>
                                    <?= number_format($anos_trabajados * 3.4, 1) ?> semanas de salario
                                <?php else: ?>
                                    <?= number_format((10 * 3.4) + (($anos_trabajados - 10) * 1), 1) ?> semanas de salario
                                <?php endif; ?>
                            </li>
                            <li><strong>Preaviso:</strong> 30 días de salario (si aplica)</li>
                            <li><strong>Vacaciones Proporcionales:</strong> Días no disfrutados del año actual</li>
                            <li><strong>XIII Mes Proporcional:</strong> Proporcional a meses trabajados en el año</li>
                        </ul>
                    </div>

                    <!-- Calendario Empresarial -->
                    <div class="callout callout-info">
                        <h5><i class="fas fa-calendar-alt mr-2"></i>Calendario Empresarial - Días Laborables</h5>
                        <p><strong>Consideraciones para el cálculo de días laborables:</strong></p>
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-times-circle text-danger mr-1"></i>Días No Laborables:</h6>
                                <ul class="list-unstyled small">
                                    <li>• Feriados Nacionales de Panamá</li>
                                    <li>• Sábados y Domingos</li>
                                    <li>• Días de Duelo Nacional</li>
                                    <li>• Días Declarados No Laborables</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-check-circle text-success mr-1"></i>Días Laborables:</h6>
                                <ul class="list-unstyled small">
                                    <li>• Lunes a Viernes regulares</li>
                                    <li>• Días recuperables declarados</li>
                                    <li>• Días con horario especial</li>
                                </ul>
                            </div>
                        </div>
                        <div class="alert alert-sm alert-info mt-2 mb-0">
                            <small><i class="fas fa-info-circle mr-1"></i>Los cálculos de preaviso e indemnización consideran únicamente días laborables según la legislación panameña.</small>
                        </div>

                        <!-- Cálculos Actualizados de Días Laborables -->
                        <div class="mt-3">
                            <h6><i class="fas fa-calculator text-primary mr-1"></i>Cálculos Actualizados:</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-box info-box-sm">
                                        <span class="info-box-icon bg-success"><i class="fas fa-briefcase"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Días Trabajados</span>
                                            <span class="info-box-number" id="dias-trabajados">
                                                <?= $periodo_trabajado['total_dias_laborables'] ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box info-box-sm">
                                        <span class="info-box-icon bg-warning"><i class="fas fa-calendar-times"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Días Preaviso</span>
                                            <span class="info-box-number" id="dias-preaviso">30</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box info-box-sm">
                                        <span class="info-box-icon bg-info"><i class="fas fa-calendar-day"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Próximo Laboral</span>
                                            <span class="info-box-number small" id="proximo-laboral">
                                                <?php
                                                // Temporal: BusinessCalendar no implementado aún
                                                echo 'N/A';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="<?= \App\Core\UrlHelper::route('panel/liquidation') ?>"
                               class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Cancelar
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="submit" class="btn btn-danger" id="submitBtn">
                                <i class="fas fa-handshake mr-1"></i> Crear Liquidación
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.all.min.js"></script>

<script>
// Asegurar que jQuery esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si jQuery está disponible
    if (typeof $ === 'undefined') {
        console.error('jQuery no está cargado');
        return;
    }

    $(document).ready(function() {
    // Manejar cambio de tipo de terminación
    $('#termination_type').on('change', function() {
        const type = $(this).val();
        const noticeGroup = $('#notice_period_days').closest('.form-group');

        if (type === 'DESPIDO_CON_CAUSA') {
            $('#notice_period_days').val(0);
            noticeGroup.find('.form-text').text('Despido con causa: sin preaviso');
            $('#dias-preaviso').text('0');
        } else if (type === 'RENUNCIA') {
            $('#notice_period_days').val(15);
            noticeGroup.find('.form-text').text('Renuncia: 15 días de preaviso (opcional)');
            $('#dias-preaviso').text('15');
        } else {
            $('#notice_period_days').val(30);
            noticeGroup.find('.form-text').text('Días de preaviso según ley (default: 30 días)');
            $('#dias-preaviso').text('30');
        }

        updateBusinessDaysCalculation();
    });

    // Actualizar cálculos cuando cambie la fecha de terminación
    $('#termination_date').on('change', function() {
        updateBusinessDaysCalculation();
    });

    // Función para actualizar cálculos de días laborables con AJAX
    function updateBusinessDaysCalculation() {
        const terminationDate = $('#termination_date').val();
        const startDate = '<?= $employee['fecha_ingreso'] ?>';

        if (terminationDate) {
            // Mostrar loading
            $('#dias-trabajados').html('<i class="fas fa-spinner fa-spin"></i>');
            $('#periodo-trabajado').html('<i class="fas fa-spinner fa-spin text-muted"></i> Calculando...');

            // Calcular período con AJAX
            $.ajax({
                url: '<?= \App\Core\UrlHelper::route('panel/liquidation/calculate-period') ?>',
                method: 'POST',
                data: {
                    start_date: startDate,
                    end_date: terminationDate,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Actualizar días trabajados
                        $('#dias-trabajados').text(response.formatted.dias_laborables);

                        // Actualizar período completo
                        $('#periodo-trabajado').html(
                            '<strong>' + response.formatted.periodo_completo + '</strong>' +
                            '<br><small class="text-muted">Total: ' + response.formatted.dias_laborables + ' días laborables</small>'
                        );

                        // Actualizar cálculos legales basados en años completos
                        updateLegalCalculations(response.period.anos_completos);

                        // Verificar si es día laboral
                        const terminationDay = new Date(terminationDate).getDay();
                        const isWorking = terminationDay >= 1 && terminationDay <= 5;
                        const badge = isWorking ?
                            '<span class="badge badge-success">Laboral</span>' :
                            '<span class="badge badge-warning">No Laboral</span>';

                        // Actualizar indicador
                        let indicator = $('#working-day-indicator');
                        if (indicator.length === 0) {
                            $('#termination_date').after('<div id="working-day-indicator" class="mt-1"></div>');
                            indicator = $('#working-day-indicator');
                        }
                        indicator.html('<small>Tipo de día: ' + badge + '</small>');

                    } else {
                        console.error('Error:', response.error);
                        $('#dias-trabajados').text('Error');
                        $('#periodo-trabajado').html('<span class="text-danger">Error en cálculo</span>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    $('#dias-trabajados').text('Error');
                    $('#periodo-trabajado').html('<span class="text-danger">Error de conexión</span>');
                }
            });
        }
    }

    // Función para actualizar cálculos legales
    function updateLegalCalculations(anos) {
        // Actualizar prima de antigüedad
        $('.callout-warning ul li').first().html('<strong>Prima de Antigüedad:</strong> ' + anos + ' semanas de salario');

        // Actualizar indemnización
        let indemnizacion;
        if (anos <= 10) {
            indemnizacion = (anos * 3.4).toFixed(1);
        } else {
            indemnizacion = ((10 * 3.4) + ((anos - 10) * 1)).toFixed(1);
        }
        $('.callout-warning ul li').eq(1).html('<strong>Indemnización:</strong> ' + indemnizacion + ' semanas de salario');
    }

    // Ejecutar cálculo inicial al cargar la página
    updateBusinessDaysCalculation();

    // Confirmación antes de enviar
    $('#submitBtn').on('click', function(e) {
        e.preventDefault();

        const employeeName = '<?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) ?>';
        const terminationType = $('#termination_type option:selected').text();
        const terminationDate = $('#termination_date').val();

        if (!terminationType || !terminationDate) {
            Swal.fire({
                title: 'Campos Incompletos',
                text: 'Por favor complete todos los campos obligatorios.',
                icon: 'error',
                confirmButtonText: 'Entendido',
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
            return;
        }

        // Obtener días trabajados para mostrar en confirmación
        const diasTrabajados = $('#dias-trabajados').text() || 'N/A';
        const diasPreaviso = $('#dias-preaviso').text() || '30';

        // Usar SweetAlert2 para confirmación elegante
        Swal.fire({
            title: '¿Confirmar Liquidación?',
            html: `
                <div class="text-left">
                    <p><strong>Empleado:</strong> ${employeeName}</p>
                    <p><strong>Tipo de terminación:</strong> ${terminationType}</p>
                    <p><strong>Fecha de terminación:</strong> ${new Date(terminationDate).toLocaleDateString('es-PA')}</p>
                    <p><strong>Días trabajados:</strong> ${diasTrabajados} días laborables</p>
                    <p><strong>Días de preaviso:</strong> ${diasPreaviso} días</p>
                    <hr>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>ADVERTENCIA:</strong> Esta acción cambiará el estado del empleado a TERMINADO y no se puede deshacer.
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fas fa-handshake"></i> Sí, crear liquidación',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            focusConfirm: false,
            customClass: {
                confirmButton: 'btn btn-danger btn-lg',
                cancelButton: 'btn btn-secondary btn-lg'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading mientras se procesa
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Creando liquidación del empleado',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar formulario
                $(this).closest('form').submit();
            }
        });
    });

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
});
</script>