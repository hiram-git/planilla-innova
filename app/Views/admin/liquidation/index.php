<?php
$pageTitle = "Liquidaciones de Empleados";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-handshake mr-2"></i>
                    Liquidaciones de Empleados
                </h1>
            </div>
            <div class="col-sm-6">
                <div class="row">
                    <div class="col-12 mb-2">
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/cancelled') ?>"
                           class="btn btn-danger btn-sm float-right">
                            <i class="fas fa-ban mr-2"></i>
                            Ver Liquidaciones Canceladas
                        </a>
                    </div>
                    <div class="col-12">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item active">Liquidaciones</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <!-- Liquidaciones Pendientes -->
        <?php if (!empty($pending_terminations)): ?>
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock mr-2"></i>
                        Liquidaciones Pendientes
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Cédula</th>
                                    <th>Fecha Terminación</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_terminations as $termination): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($termination['firstname'] . ' ' . $termination['lastname']) ?></strong><br>
                                            <small class="text-muted">Ficha: <?= htmlspecialchars($termination['employee_id']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($termination['document_id'] ?? 'N/A') ?></td>
                                        <td><?= date('d/m/Y', strtotime($termination['termination_date'])) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $termination['termination_type'] === 'DESPIDO_SIN_CAUSA' ? 'danger' : 'warning' ?>">
                                                <?= str_replace('_', ' ', $termination['termination_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'PENDIENTE' => 'warning',
                                                'CALCULADA' => 'info',
                                                'PROCESADA' => 'success',
                                                'PAGADA' => 'primary'
                                            ];
                                            $color = $statusColors[$termination['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge badge-<?= $color ?>">
                                                <?= $termination['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($termination['status'] === 'PENDIENTE'): ?>
                                                    <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/calculate/' . $termination['id']) ?>"
                                                       class="btn btn-primary">
                                                        <i class="fas fa-calculator mr-1"></i> Calcular
                                                    </a>
                                                    <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/cancel-form/' . $termination['id']) ?>"
                                                       class="btn btn-danger">
                                                        <i class="fas fa-ban mr-1"></i> Cancelar
                                                    </a>
                                                <?php elseif ($termination['status'] === 'CALCULADA'): ?>
                                                    <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/preview/' . $termination['id']) ?>"
                                                       class="btn btn-info">
                                                        <i class="fas fa-eye mr-1"></i> Ver
                                                    </a>
                                                    <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/generatePayroll/' . $termination['id']) ?>"
                                                       class="btn btn-success">
                                                        <i class="fas fa-file-invoice mr-1"></i> Planilla
                                                    </a>
                                                    <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/cancel-form/' . $termination['id']) ?>"
                                                       class="btn btn-danger">
                                                        <i class="fas fa-ban mr-1"></i> Cancelar
                                                    </a>
                                                <?php elseif ($termination['status'] === 'PROCESADA'): ?>
                                                    <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/preview/' . $termination['id']) ?>"
                                                       class="btn btn-secondary">
                                                        <i class="fas fa-eye mr-1"></i> Ver
                                                    </a>
                                                    <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/cancel-form/' . $termination['id']) ?>"
                                                       class="btn btn-warning">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i> Cancelar
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/preview/' . $termination['id']) ?>"
                                                       class="btn btn-secondary">
                                                        <i class="fas fa-eye mr-1"></i> Ver
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Empleados Activos -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users mr-2"></i>
                    Empleados Activos
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($employees)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="employeesTable">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Cédula</th>
                                    <th>Cargo</th>
                                    <th>Fecha Ingreso</th>
                                    <th>Años Trabajados</th>
                                    <th>Salario</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) ?></strong><br>
                                            <small class="text-muted">Ficha: <?= htmlspecialchars($employee['employee_id']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($employee['document_id'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($employee['position_name'] ?? 'N/A') ?></td>
                                        <td><?= date('d/m/Y', strtotime($employee['fecha_ingreso'])) ?></td>
                                        <td>
                                            <strong><?= $employee['anos_trabajados'] ?> años</strong><br>
                                            <small class="text-muted"><?= number_format($employee['dias_trabajados']) ?> días</small>
                                        </td>
                                        <td>
                                            <?php if ($employee['sueldo_individual']): ?>
                                                <?= currency_symbol() ?><?= number_format($employee['sueldo_individual'], 2) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Según posición</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/create/' . $employee['id']) ?>"
                                               class="btn btn-danger btn-sm liquidate-btn"
                                               data-employee="<?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) ?>">
                                                <i class="fas fa-handshake mr-1"></i> Liquidar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        No hay empleados activos en el sistema.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script src="<?php echo url('assets/javascript/datatables-spanish.js', false); ?>"></script>
<script>
// Esperar a que jQuery esté disponible usando JavaScript puro
(function() {
    function waitForjQuery() {
        if (typeof $ !== 'undefined') {
            // jQuery está disponible, ejecutar código
            $(document).ready(function() {
    // Initialize DataTable
    if ($("#employeesTable").length) {
        $("#employeesTable").DataTable({
            "responsive": true,
            "pageLength": 25,
            "order": [[0, "asc"]], // Ordenar por nombre empleado
            "language": DATATABLES_SPANISH,
            "columnDefs": [
                { "orderable": false, "targets": 6 } // Columna acciones no ordenable
            ]
        });
    }

    // Confirmar liquidación con SweetAlert2
    $('.liquidate-btn').on('click', function(e) {
        e.preventDefault();
        const employeeName = $(this).data('employee');
        const url = $(this).attr('href');

        Swal.fire({
            title: '¿Iniciar Liquidación?',
            html: `
                <div class="text-left">
                    <p><strong>Empleado:</strong> ${employeeName}</p>
                    <hr>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>IMPORTANTE:</strong> Esta acción cambiará el estado del empleado a <strong>TERMINADO</strong>.
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-handshake"></i> Sí, iniciar liquidación',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            reverseButtons: true,
            customClass: {
                popup: 'swal2-medium'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading mientras redirige
                Swal.fire({
                    title: 'Redirigiendo...',
                    text: 'Preparando formulario de liquidación',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Redirigir a la página de creación de liquidación
                window.location.href = url;
            }
        });
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
            }); // Cerrar $(document).ready
        } else {
            // jQuery no está disponible aún, esperar 100ms y reintentar
            setTimeout(waitForjQuery, 100);
        }
    }

    // Iniciar el proceso de espera
    waitForjQuery();
})(); // Cerrar función autoejecutada
</script>

<style>
.swal2-medium {
    width: 550px !important;
}
</style>