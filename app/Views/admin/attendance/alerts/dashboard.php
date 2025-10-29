<?php
/**
 * Dashboard de Alertas Legales
 * Vista completa con estadísticas, filtros y gestión de alertas
 */

use App\Core\UrlHelper;
$baseUrl = UrlHelper::base();

$summary = $summary ?? [];
$criticalAlerts = $criticalAlerts ?? [];
$alerts = $alerts ?? [];
$alertsByType = $alertsByType ?? [];
$employees = $employees ?? [];
$filters = $filters ?? [];

// Definir colores y textos para cada tipo de alerta
$alertTypeConfig = [
    'EXCESO_JORNADA_DIARIA' => ['color' => 'danger', 'icon' => 'fa-clock', 'label' => 'Exceso Jornada Diaria'],
    'EXCESO_JORNADA_SEMANAL' => ['color' => 'danger', 'icon' => 'fa-calendar-week', 'label' => 'Exceso Jornada Semanal'],
    'EXCESO_JORNADA_NOCTURNA' => ['color' => 'warning', 'icon' => 'fa-moon', 'label' => 'Exceso Jornada Nocturna'],
    'TIEMPO_COMIDA_INSUFICIENTE' => ['color' => 'warning', 'icon' => 'fa-utensils', 'label' => 'Tiempo Comida Insuficiente'],
    'AUSENCIAS_INJUSTIFICADAS_GRAVES' => ['color' => 'danger', 'icon' => 'fa-user-times', 'label' => 'Ausencias Graves'],
    'TARDANZAS_RECURRENTES' => ['color' => 'info', 'icon' => 'fa-hourglass-half', 'label' => 'Tardanzas Recurrentes'],
    'TRABAJO_FERIADO_SIN_COMPENSACION' => ['color' => 'info', 'icon' => 'fa-calendar-check', 'label' => 'Trabajo Feriado'],
    'DIAS_CONSECUTIVOS_SIN_DESCANSO' => ['color' => 'warning', 'icon' => 'fa-tired', 'label' => 'Sin Descanso']
];
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-exclamation-triangle mr-2"></i><?= $page_title ?? 'Dashboard de Alertas Legales' ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/panel/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/panel/attendance">Asistencias</a></li>
                    <li class="breadcrumb-item active">Alertas</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <!-- Estadísticas Generales -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= number_format($summary['total_alerts'] ?? 0) ?></h3>
                        <p>Total Alertas (7 días)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bell"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= number_format($summary['critical_count'] ?? 0) ?></h3>
                        <p>Alertas Críticas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= number_format($summary['pending_count'] ?? 0) ?></h3>
                        <p>Pendientes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= number_format($summary['affected_employees'] ?? 0) ?></h3>
                        <p>Empleados Afectados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas Críticas Recientes -->
        <?php if (!empty($criticalAlerts)): ?>
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle mr-2"></i>Alertas Críticas Recientes</h5>
            <ul class="mb-0">
                <?php foreach (array_slice($criticalAlerts, 0, 5) as $alert): ?>
                    <li>
                        <strong><?= htmlspecialchars($alert['employee_name'] ?? 'N/A') ?></strong> -
                        <?= htmlspecialchars($alert['title']) ?>
                        (<?= date('d/m/Y', strtotime($alert['date'])) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filtros</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= $baseUrl ?>/panel/attendance/alerts" id="filterForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Severidad</label>
                                <select name="severity" class="form-control">
                                    <option value="">Todas</option>
                                    <option value="CRITICAL" <?= ($filters['severity'] ?? '') === 'CRITICAL' ? 'selected' : '' ?>>Crítico</option>
                                    <option value="WARNING" <?= ($filters['severity'] ?? '') === 'WARNING' ? 'selected' : '' ?>>Advertencia</option>
                                    <option value="INFO" <?= ($filters['severity'] ?? '') === 'INFO' ? 'selected' : '' ?>>Información</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo de Alerta</label>
                                <select name="alert_type" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($alertTypeConfig as $type => $config): ?>
                                        <option value="<?= $type ?>" <?= ($filters['alert_type'] ?? '') === $type ? 'selected' : '' ?>>
                                            <?= $config['label'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Empleado</label>
                                <select name="employee_id" class="form-control select2">
                                    <option value="">Todos</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?= $emp['id'] ?>" <?= ($filters['employee_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($emp['employee_id'] . ' - ' . $emp['firstname'] . ' ' . $emp['lastname']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-2"></i>Filtrar
                            </button>
                            <a href="<?= $baseUrl ?>/panel/attendance/alerts" class="btn btn-default">
                                <i class="fas fa-times mr-2"></i>Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Distribución por Tipo de Alerta -->
        <?php if (!empty($alertsByType)): ?>
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Alertas por Tipo (Últimos 7 días)</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($alertsByType as $typeData): ?>
                        <?php
                        $config = $alertTypeConfig[$typeData['alert_type']] ?? ['color' => 'secondary', 'icon' => 'fa-bell', 'label' => $typeData['alert_type']];
                        ?>
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box bg-<?= $config['color'] ?>">
                                <span class="info-box-icon"><i class="fas <?= $config['icon'] ?>"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text"><?= $config['label'] ?></span>
                                    <span class="info-box-number"><?= $typeData['count'] ?></span>
                                    <?php if ($typeData['critical_count'] > 0): ?>
                                        <span class="badge badge-light"><?= $typeData['critical_count'] ?> críticas</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabla de Alertas -->
        <div class="card">
            <div class="card-header bg-secondary">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Alertas Activas (Últimos 30 días)</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($alerts)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="alertsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Empleado</th>
                                    <th>Departamento</th>
                                    <th>Tipo</th>
                                    <th>Título</th>
                                    <th>Severidad</th>
                                    <th>Estado</th>
                                    <th>Artículo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alerts as $alert): ?>
                                    <?php
                                    $severityColors = [
                                        'CRITICAL' => 'danger',
                                        'WARNING' => 'warning',
                                        'INFO' => 'info'
                                    ];
                                    $statusColors = [
                                        'PENDING' => 'warning',
                                        'ACKNOWLEDGED' => 'info',
                                        'RESOLVED' => 'success',
                                        'DISMISSED' => 'secondary'
                                    ];
                                    $config = $alertTypeConfig[$alert['alert_type']] ?? ['color' => 'secondary', 'icon' => 'fa-bell', 'label' => $alert['alert_type']];
                                    ?>
                                    <tr>
                                        <td><?= $alert['id'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($alert['date'])) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($alert['employee_code']) ?></strong><br>
                                            <small><?= htmlspecialchars($alert['employee_name']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($alert['department_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $config['color'] ?>">
                                                <i class="fas <?= $config['icon'] ?> mr-1"></i>
                                                <?= $config['label'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($alert['title']) ?>
                                            <button type="button" class="btn btn-xs btn-link" data-toggle="popover"
                                                    data-content="<?= htmlspecialchars($alert['message']) ?>"
                                                    data-trigger="hover">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $severityColors[$alert['severity']] ?? 'secondary' ?>">
                                                <?= $alert['severity'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $statusColors[$alert['status']] ?? 'secondary' ?>">
                                                <?= $alert['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($alert['article_reference'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars($alert['article_reference']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($alert['status'] === 'PENDING'): ?>
                                                    <button type="button" class="btn btn-info btn-acknowledge" data-id="<?= $alert['id'] ?>" title="Reconocer">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($alert['status'] !== 'RESOLVED'): ?>
                                                    <button type="button" class="btn btn-success btn-resolve" data-id="<?= $alert['id'] ?>" title="Resolver">
                                                        <i class="fas fa-check-double"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-secondary btn-dismiss" data-id="<?= $alert['id'] ?>" title="Descartar">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-check-circle fa-2x mb-3"></i>
                        <h4>¡Sin alertas activas!</h4>
                        <p class="mb-0">No hay alertas pendientes o reconocidas en este momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php
// Scripts
ob_start();
?>
<script src="<?php echo url('assets/javascript/datatables-spanish.js', false); ?>"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    const alertsTable = $('#alertsTable').DataTable({
        language: DATATABLES_SPANISH,
        order: [[6, 'desc'], [1, 'desc']], // Ordenar por severidad y fecha
        pageLength: 25,
        columnDefs: [
            { orderable: false, targets: 9 } // Columna acciones no ordenable
        ]
    });

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Initialize Popovers
    $('[data-toggle="popover"]').popover({
        html: true,
        placement: 'left'
    });

    // Reconocer Alerta
    $(document).on('click', '.btn-acknowledge', function() {
        const alertId = $(this).data('id');

        Swal.fire({
            title: 'Reconocer Alerta',
            input: 'textarea',
            inputLabel: 'Notas (opcional)',
            inputPlaceholder: 'Agregar notas sobre esta alerta...',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check mr-2"></i> Reconocer',
            cancelButtonText: '<i class="fas fa-times mr-2"></i> Cancelar',
            confirmButtonColor: '#17a2b8',
            preConfirm: (notes) => {
                return $.ajax({
                    url: '<?= $baseUrl ?>/panel/attendance/alert/' + alertId + '/acknowledge',
                    type: 'POST',
                    data: {
                        notes: notes,
                        csrf_token: '<?= $csrf_token ?? '' ?>'
                    },
                    dataType: 'json'
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value.success) {
                    Swal.fire('Éxito', result.value.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', result.value.message, 'error');
                }
            }
        }).catch((error) => {
            Swal.fire('Error', 'Error al reconocer alerta: ' + error.responseText, 'error');
        });
    });

    // Resolver Alerta
    $(document).on('click', '.btn-resolve', function() {
        const alertId = $(this).data('id');

        Swal.fire({
            title: 'Resolver Alerta',
            input: 'textarea',
            inputLabel: 'Notas de resolución (opcional)',
            inputPlaceholder: 'Describir cómo se resolvió la alerta...',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check-double mr-2"></i> Resolver',
            cancelButtonText: '<i class="fas fa-times mr-2"></i> Cancelar',
            confirmButtonColor: '#28a745',
            preConfirm: (notes) => {
                return $.ajax({
                    url: '<?= $baseUrl ?>/panel/attendance/alert/' + alertId + '/resolve',
                    type: 'POST',
                    data: {
                        notes: notes,
                        csrf_token: '<?= $csrf_token ?? '' ?>'
                    },
                    dataType: 'json'
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value.success) {
                    Swal.fire('Éxito', result.value.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', result.value.message, 'error');
                }
            }
        }).catch((error) => {
            Swal.fire('Error', 'Error al resolver alerta: ' + error.responseText, 'error');
        });
    });

    // Descartar Alerta
    $(document).on('click', '.btn-dismiss', function() {
        const alertId = $(this).data('id');

        Swal.fire({
            title: 'Descartar Alerta',
            input: 'textarea',
            inputLabel: 'Razón del descarte (opcional)',
            inputPlaceholder: 'Explicar por qué se descarta esta alerta...',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-times mr-2"></i> Descartar',
            cancelButtonText: '<i class="fas fa-ban mr-2"></i> Cancelar',
            confirmButtonColor: '#6c757d',
            preConfirm: (reason) => {
                return $.ajax({
                    url: '<?= $baseUrl ?>/panel/attendance/alert/' + alertId + '/dismiss',
                    type: 'POST',
                    data: {
                        reason: reason,
                        csrf_token: '<?= $csrf_token ?? '' ?>'
                    },
                    dataType: 'json'
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value.success) {
                    Swal.fire('Éxito', result.value.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', result.value.message, 'error');
                }
            }
        }).catch((error) => {
            Swal.fire('Error', 'Error al descartar alerta: ' + error.responseText, 'error');
        });
    });
});
</script>
<?php
$scripts = ob_get_clean();
?>
