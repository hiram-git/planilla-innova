<?php
/**
 * Vista: Cancelar Liquidación
 * Descripción: Formulario para cancelar una liquidación con motivo obligatorio
 * Versión: 3.3.1
 */

// Incluir helper para funciones auxiliares
require_once __DIR__ . '/../../../helpers.php';
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-ban text-danger mr-2"></i>
                    Cancelar Liquidación
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/panel">Panel</a></li>
                    <li class="breadcrumb-item"><a href="/panel/liquidation">Liquidaciones</a></li>
                    <li class="breadcrumb-item active">Cancelar</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Información del Empleado -->
                <div class="card card-outline card-danger">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user mr-2"></i>
                            Información del Empleado
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nombre:</strong> <?= htmlspecialchars($termination['firstname'] . ' ' . $termination['lastname']) ?></p>
                                <p><strong>ID Empleado:</strong> <?= htmlspecialchars($termination['employee_id']) ?></p>
                                <p><strong>Documento:</strong> <?= htmlspecialchars($termination['document_id']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Posición:</strong> <?= htmlspecialchars($termination['position_name'] ?? 'Sin posición') ?></p>
                                <p><strong>Fecha Ingreso:</strong> <?= date('d/m/Y', strtotime($termination['fecha_ingreso'])) ?></p>
                                <p><strong>Salario:</strong> B/. <?= number_format($termination['sueldo_individual'], 2) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de la Liquidación -->
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-handshake mr-2"></i>
                            Datos de la Liquidación
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Estado Actual:</strong>
                                    <span class="badge badge-<?=
                                        $termination['status'] == 'PENDIENTE' ? 'warning' :
                                        ($termination['status'] == 'CALCULADA' ? 'info' :
                                        ($termination['status'] == 'PROCESADA' ? 'primary' : 'success'))
                                    ?>">
                                        <?= htmlspecialchars($termination['status']) ?>
                                    </span>
                                </p>
                                <p><strong>Fecha de Terminación:</strong> <?= date('d/m/Y', strtotime($termination['termination_date'])) ?></p>
                                <p><strong>Tipo de Terminación:</strong> <?= htmlspecialchars($termination['termination_type']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Años Trabajados:</strong> <?= number_format($termination['years_worked'], 2) ?> años</p>
                                <p><strong>Días de Preaviso:</strong> <?= $termination['notice_period_days'] ?> días</p>
                                <p><strong>Fecha de Creación:</strong> <?= date('d/m/Y H:i', strtotime($termination['created_at'])) ?></p>
                            </div>
                        </div>

                        <?php if (!empty($termination['reason'])): ?>
                        <div class="row">
                            <div class="col-12">
                                <p><strong>Motivo Original:</strong></p>
                                <div class="bg-light p-2 rounded">
                                    <?= htmlspecialchars($termination['reason']) ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulario de Cancelación -->
                <div class="card card-outline card-danger">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Motivo de Cancelación
                        </h3>
                    </div>
                    <form action="/panel/liquidation/cancel/<?= $termination['id'] ?>" method="POST" id="cancelForm">
                        <div class="card-body">
                            <?= csrf_field() ?>

                            <!-- Advertencia -->
                            <div class="callout callout-danger">
                                <h5><i class="fas fa-exclamation-triangle mr-2"></i>ADVERTENCIA - Cancelación de Liquidación</h5>
                                <p><strong>Esta acción tendrá los siguientes efectos:</strong></p>
                                <ul class="mb-2">
                                    <?php if (in_array($termination['status'], ['PENDIENTE', 'CALCULADA'])): ?>
                                    <li>✅ El empleado <strong>regresará al estado ACTIVO</strong></li>
                                    <li>✅ Se eliminarán las fechas de terminación del empleado</li>
                                    <li>✅ Se preservarán todos los cálculos para auditoría</li>
                                    <?php else: ?>
                                    <li>⚠️ El empleado <strong>mantendrá su estado de TERMINADO</strong></li>
                                    <li>⚠️ Solo se marcará la liquidación como cancelada</li>
                                    <li>⚠️ Los pagos realizados deben gestionarse manualmente</li>
                                    <?php endif; ?>
                                    <li>📝 Se registrará la cancelación en el historial</li>
                                    <li>🔒 Esta acción <strong>NO se puede deshacer</strong></li>
                                </ul>
                            </div>

                            <!-- Campo de motivo -->
                            <div class="form-group">
                                <label for="cancel_reason">Motivo de la Cancelación <span class="text-danger">*</span></label>
                                <textarea class="form-control"
                                          id="cancel_reason"
                                          name="cancel_reason"
                                          rows="4"
                                          required
                                          placeholder="Describa detalladamente el motivo por el cual se está cancelando esta liquidación. Este motivo quedará registrado en el historial para auditoría."><?= old('cancel_reason') ?></textarea>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Este motivo será visible en el historial de la liquidación y reportes de auditoría.
                                </small>
                            </div>

                            <!-- Confirmación adicional -->
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="confirmCancel" required>
                                    <label class="custom-control-label" for="confirmCancel">
                                        <strong>Confirmo que deseo cancelar esta liquidación y entiendo las consecuencias</strong>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-between">
                            <a href="/panel/liquidation" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Regresar
                            </a>

                            <button type="submit" class="btn btn-danger btn-lg" id="submitBtn" disabled>
                                <i class="fas fa-ban mr-2"></i>
                                Cancelar Liquidación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript -->
<script>
$(document).ready(function() {
    // Habilitar/deshabilitar botón según checkbox
    $('#confirmCancel').change(function() {
        $('#submitBtn').prop('disabled', !this.checked);
    });

    // Confirmación con SweetAlert2 antes de enviar
    $('#cancelForm').on('submit', function(e) {
        e.preventDefault();

        const reason = $('#cancel_reason').val().trim();
        const employeeName = '<?= htmlspecialchars($termination['firstname'] . ' ' . $termination['lastname']) ?>';
        const status = '<?= htmlspecialchars($termination['status']) ?>';

        if (reason.length < 10) {
            Swal.fire({
                title: 'Motivo insuficiente',
                text: 'Por favor proporcione un motivo más detallado (mínimo 10 caracteres)',
                icon: 'warning',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        Swal.fire({
            title: '¿Confirmar Cancelación?',
            html: `
                <div class="text-left">
                    <p><strong>Empleado:</strong> ${employeeName}</p>
                    <p><strong>Estado actual:</strong> <span class="badge badge-warning">${status}</span></p>
                    <p><strong>Motivo:</strong></p>
                    <div class="bg-light p-2 rounded text-left" style="max-height: 100px; overflow-y: auto;">
                        ${reason}
                    </div>
                    <hr>
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>ADVERTENCIA:</strong> Esta acción NO se puede deshacer.
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-ban"></i> Sí, cancelar liquidación',
            cancelButtonText: '<i class="fas fa-times"></i> No cancelar',
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading mientras se procesa
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Cancelando liquidación',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar formulario
                this.submit();
            }
        });
    });
});
</script>