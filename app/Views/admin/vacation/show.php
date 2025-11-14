<?php
$pageTitle = "Solicitud de Vacaciones #" . $request['id'];
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-file-alt mr-2"></i>
                    Solicitud de Vacaciones #<?= $request['id'] ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/vacation') ?>">Vacaciones</a></li>
                    <li class="breadcrumb-item active">Solicitud #<?= $request['id'] ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <!-- Estado de la Solicitud -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="callout callout-<?= $request['status'] === 'APPROVED' ? 'success' : ($request['status'] === 'REJECTED' ? 'danger' : 'warning') ?>">
                    <h5>
                        <i class="fas fa-<?= $request['status'] === 'APPROVED' ? 'check' : ($request['status'] === 'REJECTED' ? 'times' : 'clock') ?>"></i>
                        Estado:
                        <?php
                        $status_labels = [
                            'PENDING' => 'Pendiente de Aprobación',
                            'APPROVED' => 'Aprobada',
                            'REJECTED' => 'Rechazada',
                            'TAKEN' => 'Vacaciones Tomadas',
                            'CANCELLED' => 'Cancelada'
                        ];
                        echo $status_labels[$request['status']] ?? 'Desconocido';
                        ?>
                    </h5>
                    <?php if ($request['status'] === 'APPROVED' && $request['approver_name']): ?>
                        <p>Aprobada por: <strong><?= htmlspecialchars($request['approver_name']) ?></strong>
                           el <?= date('d/m/Y H:i', strtotime($request['approved_at'])) ?></p>
                    <?php elseif ($request['status'] === 'REJECTED'): ?>
                        <p>Rechazada por: <strong><?= htmlspecialchars($request['approver_name'] ?? 'N/A') ?></strong>
                           el <?= date('d/m/Y H:i', strtotime($request['approved_at'])) ?></p>
                        <?php if ($request['rejection_reason']): ?>
                            <p><strong>Razón del rechazo:</strong> <?= htmlspecialchars($request['rejection_reason']) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Detalles de la Solicitud -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>
                            Detalles de la Solicitud
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-5">Empleado:</dt>
                                    <dd class="col-sm-7">
                                        <strong><?= htmlspecialchars($request['firstname'] . ' ' . $request['lastname']) ?></strong>
                                        <br><small class="text-muted">ID: <?= htmlspecialchars($request['employee_id']) ?></small>
                                    </dd>

                                    <dt class="col-sm-5">Cargo:</dt>
                                    <dd class="col-sm-7"><?= htmlspecialchars($request['cargo_nombre'] ?? 'N/A') ?></dd>

                                    <dt class="col-sm-5">Fecha Solicitud:</dt>
                                    <dd class="col-sm-7"><?= date('d/m/Y', strtotime($request['request_date'])) ?></dd>

                                    <dt class="col-sm-5">Tipo Vacaciones:</dt>
                                    <dd class="col-sm-7">
                                        <?php
                                        $type_labels = [
                                            'ANNUAL' => '<span class="badge badge-primary">Anuales</span>',
                                            'ACCUMULATED' => '<span class="badge badge-secondary">Acumuladas</span>',
                                            'COMPENSATION' => '<span class="badge badge-success">Compensación</span>',
                                            'PROPORTIONAL' => '<span class="badge badge-info">Proporcionales</span>'
                                        ];
                                        echo $type_labels[$request['vacation_type']] ?? '<span class="badge badge-light">N/A</span>';
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-5">Período:</dt>
                                    <dd class="col-sm-7">
                                        <strong><?= date('d/m/Y', strtotime($request['start_date'])) ?></strong>
                                        al
                                        <strong><?= date('d/m/Y', strtotime($request['end_date'])) ?></strong>
                                    </dd>

                                    <dt class="col-sm-5">Días Solicitados:</dt>
                                    <dd class="col-sm-7"><strong><?= $request['total_days'] ?> días</strong></dd>

                                    <dt class="col-sm-5">Fecha Ingreso:</dt>
                                    <dd class="col-sm-7"><?= date('d/m/Y', strtotime($request['fecha_ingreso'])) ?></dd>

                                    <dt class="col-sm-5">Años Trabajados:</dt>
                                    <dd class="col-sm-7"><?= number_format($request['years_worked'], 1) ?> años</dd>
                                </dl>
                            </div>
                        </div>

                        <?php if ($request['comments']): ?>
                            <hr>
                            <h6><i class="fas fa-comment mr-1"></i> Comentarios:</h6>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($request['comments'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cálculos Detallados -->
                <?php if (!empty($calculations)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calculator mr-2"></i>
                                Cálculos y Auditoría
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Tipo de Cálculo</th>
                                            <th>Base</th>
                                            <th>Días Calculados</th>
                                            <th>Monto</th>
                                            <th>Fórmula Aplicada</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($calculations as $calc): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($calc['calculation_type']) ?></td>
                                                <td><?= number_format($calc['calculation_base'], 2) ?></td>
                                                <td><?= number_format($calc['days_calculated'], 1) ?></td>
                                                <td><?= currency_symbol() ?><?= number_format($calc['amount_calculated'], 2) ?></td>
                                                <td><small class="text-muted"><?= htmlspecialchars($calc['formula_used'] ?? 'N/A') ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <!-- Balance del Empleado -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-balance-scale mr-2"></i>
                            Balance Actual
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <h3 class="text-<?= $current_balance > 0 ? 'success' : 'danger' ?>">
                                <?= number_format($current_balance, 1) ?> días
                            </h3>
                            <p class="text-muted">Disponibles actualmente</p>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">Desglose:</small>
                            <ul class="list-unstyled mb-0">
                                <li><i class="fas fa-plus text-success"></i> Días acumulados: <?= number_format($request['accumulated_days'], 1) ?></li>
                                <li><i class="fas fa-minus text-warning"></i> Días solicitados: <?= number_format($request['total_days'], 1) ?></li>
                                <li><i class="fas fa-equals text-info"></i> Balance resultante: <?= number_format($request['remaining_days'], 1) ?></li>
                            </ul>
                        </div>

                        <?php if ($request['vacation_type'] === 'COMPENSATION' && $request['compensation_amount'] > 0): ?>
                            <hr>
                            <div class="text-center">
                                <h5>Compensación Monetaria</h5>
                                <h4 class="text-success"><?= currency_symbol() ?><?= number_format($request['compensation_amount'], 2) ?></h4>
                                <small class="text-muted">
                                    <?= currency_symbol() ?><?= number_format($request['daily_salary'], 2) ?> × <?= $request['total_days'] ?> días
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones -->
                <?php if ($request['status'] === 'PENDING'): ?>
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cogs mr-2"></i>
                                Acciones
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <form id="approveForm" method="POST" action="<?= \App\Core\UrlHelper::route('panel/vacation/approve/' . $request['id']) ?>" class="mb-2">
                                    <?= \App\Core\Controller::getCsrfTokenInput() ?>
                                    <button type="button" class="btn btn-success btn-block btn-approve-vacation">
                                        <i class="fas fa-check mr-1"></i> Aprobar Solicitud
                                    </button>
                                </form>

                                <form id="rejectForm" method="POST" action="<?= \App\Core\UrlHelper::route('panel/vacation/reject/' . $request['id']) ?>">
                                    <?= \App\Core\Controller::getCsrfTokenInput() ?>
                                    <input type="hidden" name="rejection_reason" id="rejection_reason_hidden">
                                    <button type="button" class="btn btn-danger btn-block btn-reject-vacation">
                                        <i class="fas fa-times mr-1"></i> Rechazar Solicitud
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal removido: se usa SweetAlert para rechazo/aprobación -->
                <?php endif; ?>

                <!-- Información Legal -->
                <div class="card card-light">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-gavel mr-2"></i>
                            Marco Legal
                        </h3>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <ul class="list-unstyled mb-0">
                                <li><i class="fas fa-check text-success"></i> Código de Trabajo de Panamá</li>
                                <li><i class="fas fa-check text-success"></i> 30 días por 11 meses trabajados</li>
                                <li><i class="fas fa-check text-success"></i> Mínimo 15 días consecutivos</li>
                                <li><i class="fas fa-check text-success"></i> Compensación autorizada</li>
                            </ul>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Navegación -->
        <div class="row">
            <div class="col-12">
                <div class="card-footer">
                    <a href="<?= \App\Core\UrlHelper::route('panel/vacation') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Volver a Lista
                    </a>

                    <a href="<?= \App\Core\UrlHelper::route('panel/vacation/balance/' . $request['employee_id']) ?>" class="btn btn-info">
                        <i class="fas fa-chart-line mr-1"></i> Ver Balance Empleado
                    </a>

                    <?php if ($request['status'] === 'APPROVED'): ?>
                        <a href="<?= \App\Core\UrlHelper::route('panel/vacation/calendar') ?>?highlight=<?= $request['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-calendar mr-1"></i> Ver en Calendario
                        </a>
                    <?php endif; ?>

                    <a href="<?= \App\Core\UrlHelper::url('panel/vacation/show/' . $request['id'] . '/pdf') ?>" class="btn btn-danger float-right">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </a>
                </div>
            </div>
        </div>

    </div>
</section>

<?php ob_start(); ?>
<script>
$(document).ready(function() {
    // Aprobar con SweetAlert
    $(document).on('click', '.btn-approve-vacation', function() {
        const $btn = $(this);
        if ($btn.data('submitting')) return;
        Swal.fire({
            title: '¿Aprobar solicitud?',
            text: 'Esta acción descontará los días aprobados del balance del empleado.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Sí, aprobar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('approveForm');
                if (form) {
                    $btn.data('submitting', true).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
                    form.submit();
                } else { toastr.error('No se encontró el formulario de aprobación'); }
            }
        });
    });

    // Rechazar con SweetAlert (textarea)
    $(document).on('click', '.btn-reject-vacation', function() {
        const $btn = $(this);
        if ($btn.data('submitting')) return;
        Swal.fire({
            title: 'Rechazar solicitud',
            input: 'textarea',
            inputLabel: 'Razón del rechazo',
            inputPlaceholder: 'Explique detalladamente por qué se rechaza esta solicitud...',
            inputAttributes: { 'aria-label': 'Razón del rechazo' },
            inputValidator: (value) => {
                if (!value || value.trim().length === 0) {
                    return 'La razón del rechazo es obligatoria';
                }
            },
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-times"></i> Rechazar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('rejectForm');
                const hidden = document.getElementById('rejection_reason_hidden');
                if (form && hidden) {
                    hidden.value = result.value;
                    $btn.data('submitting', true).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
                    form.submit();
                } else {
                    toastr.error('No se pudo preparar el formulario de rechazo');
                }
            }
        });
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
