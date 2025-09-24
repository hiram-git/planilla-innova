<?php
$pageTitle = "Gestión de Vacaciones";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-umbrella-beach mr-2"></i>
                    Gestión de Vacaciones
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vacaciones</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <!-- Solicitudes Pendientes -->
        <?php if (!empty($pending_requests)): ?>
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock mr-2"></i>
                        Solicitudes Pendientes de Aprobación
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Cédula</th>
                                    <th>Fecha Solicitud</th>
                                    <th>Período Vacaciones</th>
                                    <th>Días Solicitados</th>
                                    <th>Tipo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($request['firstname'] . ' ' . $request['lastname']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($request['employee_id']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($request['employee_id']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($request['request_date'])) ?></td>
                                        <td>
                                            <strong><?= date('d/m/Y', strtotime($request['start_date'])) ?></strong>
                                            al
                                            <strong><?= date('d/m/Y', strtotime($request['end_date'])) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?= $request['business_days'] ?> días hábiles</span>
                                            <br><small class="text-muted"><?= $request['total_days'] ?> días totales</small>
                                        </td>
                                        <td>
                                            <?php
                                            $type_labels = [
                                                'ANNUAL' => '<span class="badge badge-primary">Anuales</span>',
                                                'ACCUMULATED' => '<span class="badge badge-secondary">Acumuladas</span>',
                                                'COMPENSATION' => '<span class="badge badge-success">Compensación</span>',
                                                'PROPORTIONAL' => '<span class="badge badge-info">Proporcionales</span>'
                                            ];
                                            echo $type_labels[$request['vacation_type']] ?? '<span class="badge badge-light">N/A</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?= \App\Core\UrlHelper::route('panel/vacation/show/' . $request['id']) ?>"
                                                   class="btn btn-info" title="Ver Detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form method="POST" action="<?= \App\Core\UrlHelper::route('panel/vacation/approve/' . $request['id']) ?>" style="display: inline;">
                                                    <?= \App\Core\Controller::getCsrfTokenInput() ?>
                                                    <button type="submit" class="btn btn-success" title="Aprobar"
                                                            onclick="return confirm('¿Está seguro de aprobar esta solicitud?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-danger" title="Rechazar"
                                                        data-toggle="modal" data-target="#rejectModal<?= $request['id'] ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>

                                            <!-- Modal de Rechazo -->
                                            <div class="modal fade" id="rejectModal<?= $request['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="<?= \App\Core\UrlHelper::route('panel/vacation/reject/' . $request['id']) ?>">
                                                            <?= \App\Core\Controller::getCsrfTokenInput() ?>
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Rechazar Solicitud</h5>
                                                                <button type="button" class="close" data-dismiss="modal">
                                                                    <span>&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="form-group">
                                                                    <label for="rejection_reason">Razón del rechazo:</label>
                                                                    <textarea class="form-control" name="rejection_reason"
                                                                              rows="3" required
                                                                              placeholder="Indique la razón por la cual se rechaza esta solicitud..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-danger">Rechazar Solicitud</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
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

        <!-- Accesos Rápidos -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= count($employees) ?></h3>
                        <p>Empleados Activos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= count($pending_requests) ?></h3>
                        <p>Solicitudes Pendientes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><i class="fas fa-calendar-alt"></i></h3>
                        <p>Calendario Vacaciones</p>
                    </div>
                    <a href="<?= \App\Core\UrlHelper::route('panel/vacation/calendar') ?>" class="small-box-footer">
                        Ver Calendario <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><i class="fas fa-chart-bar"></i></h3>
                        <p>Reportes</p>
                    </div>
                    <a href="<?= \App\Core\UrlHelper::route('panel/vacation/reports') ?>" class="small-box-footer">
                        Ver Reportes <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Lista de Empleados y Balances -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list mr-2"></i>
                    Empleados y Balance de Vacaciones
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="employeesTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Posición</th>
                                <th>Fecha Ingreso</th>
                                <th>Meses Trabajados</th>
                                <th>Días Ganados</th>
                                <th>Días Tomados</th>
                                <th>Balance Actual</th>
                                <th>Elegible</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) ?></strong>
                                        <br><small class="text-muted">ID: <?= htmlspecialchars($employee['employee_id']) ?></small>
                                        <br><small class="text-muted">Cédula: <?= htmlspecialchars($employee['document_id']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($employee['position_name'] ?? 'Sin Asignar') ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($employee['fecha_ingreso'])) ?>
                                        <br><small class="text-muted"><?= $employee['dias_trabajados'] ?> días</small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $employee['meses_trabajados'] >= 11 ? 'badge-success' : 'badge-warning' ?>">
                                            <?= $employee['meses_trabajados'] ?> meses
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?= number_format($employee['days_earned'], 1) ?> días</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary"><?= number_format($employee['days_taken'], 1) ?> días</span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $employee['current_balance'] > 0 ? 'badge-success' : 'badge-danger' ?>">
                                            <?= number_format($employee['current_balance'], 1) ?> días
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($employee['eligible']): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Elegible
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> No Elegible
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if ($employee['eligible']): ?>
                                                <a href="<?= \App\Core\UrlHelper::route('panel/vacation/create/' . $employee['id']) ?>"
                                                   class="btn btn-primary" title="Nueva Solicitud">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= \App\Core\UrlHelper::route('panel/vacation/balance/' . $employee['id']) ?>"
                                               class="btn btn-info" title="Ver Balance Detallado">
                                                <i class="fas fa-chart-line"></i>
                                            </a>
                                            <a href="<?= \App\Core\UrlHelper::route('panel/employees/show/' . $employee['id']) ?>"
                                               class="btn btn-secondary" title="Ver Empleado">
                                                <i class="fas fa-user"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#employeesTable').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "pageLength": 25,
        "order": [[0, 'asc']],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": [8] } // Columna de acciones no ordenable
        ]
    });

    // Auto-refresh cada 5 minutos para solicitudes pendientes
    setTimeout(function() {
        if (<?= count($pending_requests) ?> > 0) {
            location.reload();
        }
    }, 300000); // 5 minutos
});
</script>