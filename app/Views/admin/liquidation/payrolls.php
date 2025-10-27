<?php
$pageTitle = "Planillas de Liquidación";

// Definir scripts que se cargarán al final del layout
ob_start();
?>
<script src="<?php echo url('assets/javascript/datatables-spanish.js', false); ?>"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($("#payrollsTable").length) {
        $("#payrollsTable").DataTable({
            "responsive": true,
            "pageLength": 25,
            "order": [[0, "desc"]], // Ordenar por ID descendente (más recientes primero)
            "language": DATATABLES_SPANISH,
            "columnDefs": [
                { "orderable": false, "targets": 10 } // Columna acciones no ordenable
            ]
        });
    }

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Manejar botón de revertir planilla
    $(document).on('click', '.btn-revert-payroll', function(e) {
        e.preventDefault();

        const terminationId = $(this).data('termination-id');
        const description = $(this).data('payroll-description');

        Swal.fire({
            title: '¿Revertir Planilla de Liquidación?',
            html: `
                <p class="mb-3">Esta acción eliminará la planilla generada y volverá la liquidación a estado <strong>CALCULADA</strong>.</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Planilla:</strong> ${description}
                </div>
                <p class="mb-0">Podrá generar una nueva planilla después de hacer los ajustes necesarios.</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-undo mr-2"></i> Sí, Revertir',
            cancelButtonText: '<i class="fas fa-times mr-2"></i> Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear formulario para enviar con CSRF token
                const form = $('<form>', {
                    'method': 'POST',
                    'action': '<?= \App\Core\UrlHelper::route('panel/liquidation/') ?>' + terminationId + '/revertPayroll'
                });

                // Agregar CSRF token
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': 'csrf_token',
                    'value': '<?= $_SESSION['csrf_token'] ?? '' ?>'
                }));

                // Agregar al body y enviar
                $('body').append(form);
                form.submit();
            }
        });
    });
});
</script>
<?php
$scripts = ob_get_clean();
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-file-invoice mr-2"></i>
                    Planillas de Liquidación
                </h1>
            </div>
            <div class="col-sm-6">
                <div class="row">
                    <div class="col-12 mb-2">
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation') ?>"
                           class="btn btn-primary btn-sm float-right">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver a Liquidaciones
                        </a>
                    </div>
                    <div class="col-12">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/liquidation') ?>">Liquidaciones</a></li>
                            <li class="breadcrumb-item active">Planillas</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <!-- Información de Filtro -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Planillas de Liquidación:</strong> Esta vista muestra únicamente las planillas generadas para liquidaciones de empleados,
            filtradas por la frecuencia de liquidación. El periodo abarca los últimos 11 meses desde la fecha de terminación según la legislación panameña.
        </div>

        <!-- Estadísticas Generales -->
        <?php if (!empty($payrolls)): ?>
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= count($payrolls) ?></h3>
                        <p>Planillas Generadas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= array_sum(array_column($payrolls, 'total_empleados')) ?></h3>
                        <p>Empleados Liquidados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= currency_symbol() ?><?= number_format(array_sum(array_column($payrolls, 'total_asignaciones')), 2) ?></h3>
                        <p>Total Asignaciones</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= currency_symbol() ?><?= number_format(array_sum(array_column($payrolls, 'total_neto')), 2) ?></h3>
                        <p>Total Neto</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista de Planillas de Liquidación -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list mr-2"></i>
                    Historial de Planillas de Liquidación
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($payrolls)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="payrollsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Descripción</th>
                                    <th>Periodo</th>
                                    <th>Frecuencia</th>
                                    <th>Estado</th>
                                    <th>Empleados</th>
                                    <th>Total Asignaciones</th>
                                    <th>Total Deducciones</th>
                                    <th>Total Neto</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payrolls as $payroll): ?>
                                    <tr>
                                        <td><strong>#<?= $payroll['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($payroll['descripcion']) ?></td>
                                        <td>
                                            <small>
                                                <strong>Desde:</strong> <?= date('d/m/Y', strtotime($payroll['fecha_desde'])) ?><br>
                                                <strong>Hasta:</strong> <?= date('d/m/Y', strtotime($payroll['fecha_hasta'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= htmlspecialchars($payroll['frecuencia_nombre'] ?? 'Liquidación') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'PENDIENTE' => 'warning',
                                                'PROCESADA' => 'success',
                                                'CERRADA' => 'secondary'
                                            ];
                                            $color = $statusColors[$payroll['estado']] ?? 'primary';
                                            ?>
                                            <span class="badge badge-<?= $color ?>">
                                                <?= $payroll['estado'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-primary">
                                                <?= $payroll['total_empleados'] ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-success font-weight-bold">
                                                <?= currency_symbol() ?><?= number_format($payroll['total_asignaciones'], 2) ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-danger font-weight-bold">
                                                <?= currency_symbol() ?><?= number_format($payroll['total_deducciones'], 2) ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-primary font-weight-bold">
                                                <?= currency_symbol() ?><?= number_format($payroll['total_neto'], 2) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= date('d/m/Y H:i', strtotime($payroll['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/payroll-detail/' . $payroll['id']) ?>"
                                                   class="btn btn-info"
                                                   title="Ver Detalle">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= \App\Core\UrlHelper::route('panel/reports/planilla-pdf/' . $payroll['id']) ?>"
                                                   class="btn btn-success"
                                                   title="Generar PDF"
                                                   target="_blank">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                <?php if ($payroll['liquidation_status'] === 'PROCESADA' && !empty($payroll['termination_id'])): ?>
                                                    <button type="button"
                                                            class="btn btn-warning btn-revert-payroll"
                                                            data-termination-id="<?= $payroll['termination_id'] ?>"
                                                            data-payroll-description="<?= htmlspecialchars($payroll['descripcion']) ?>"
                                                            title="Revertir Planilla">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h4>No hay planillas de liquidación</h4>
                        <p class="mb-0">
                            Las planillas se generan automáticamente cuando se procesan las liquidaciones de empleados.<br>
                            <a href="<?= \App\Core\UrlHelper::route('panel/liquidation') ?>" class="btn btn-primary mt-2">
                                <i class="fas fa-plus mr-1"></i> Ir a Liquidaciones
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>