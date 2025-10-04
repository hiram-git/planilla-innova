<?php
$pageTitle = "Acumulados - {$tipo['descripcion']}";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?= htmlspecialchars($tipo['descripcion']) ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/acumulados') ?>">Acumulados</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($tipo['descripcion']) ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <!-- Información del Tipo -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?= htmlspecialchars($tipo['descripcion']) ?> - Año <?= $selectedYear ?>
                </h3>
                <div class="card-tools">
                    <form method="GET" class="form-inline">
                        <div class="input-group input-group-sm">
                            <select name="year" class="form-control" onchange="this.form.submit()">
                                <?php foreach ($years as $year): ?>
                                    <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="input-group-append">
                                <label class="input-group-text">Año</label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($acumulados)): ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon">
                                    <i class="fas fa-list-alt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Conceptos</span>
                                    <span class="info-box-number"><?= count($acumulados) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Acumulado</span>
                                    <span class="info-box-number">$<?= number_format(array_sum(array_column($acumulados, 'total_acumulado')), 2) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon">
                                    <i class="fas fa-users"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Empleados</span>
                                    <span class="info-box-number"><?= array_sum(array_column($acumulados, 'total_empleados')) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-secondary">
                                <span class="info-box-icon">
                                    <i class="fas fa-file-invoice"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Planillas</span>
                                    <span class="info-box-number"><?= array_sum(array_column($acumulados, 'total_planillas')) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h5>No hay acumulados para este tipo en <?= $selectedYear ?></h5>
                        <p class="text-muted">
                            No se encontraron registros de <strong><?= htmlspecialchars($tipo['descripcion']) ?></strong> para el año <?= $selectedYear ?>.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($acumulados)): ?>
            <!-- Detalle por Concepto -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-table mr-2"></i>
                        Detalle por Concepto - Agrupado por Planilla, Año y Empleado
                    </h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="acumulados-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Concepto</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-right">Total Acumulado</th>
                                <th class="text-center">Empleados</th>
                                <th class="text-center">Planillas</th>
                                <th class="text-center">Años</th>
                                <th class="text-center">Última Actualización</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($acumulados as $acumulado): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($acumulado['concepto_codigo']) ?></strong></td>
                                    <td><?= htmlspecialchars($acumulado['concepto_descripcion']) ?></td>
                                    <td class="text-center">
                                        <?php if ($acumulado['tipo_concepto'] === 'ASIGNACION'): ?>
                                            <span class="badge badge-success"><i class="fas fa-plus-circle"></i> Asignación</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><i class="fas fa-minus-circle"></i> Deducción</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right font-weight-bold text-<?= $acumulado['tipo_concepto'] === 'ASIGNACION' ? 'success' : 'danger' ?>">
                                        $<?= number_format($acumulado['total_acumulado'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info" title="Total de empleados con este concepto">
                                            <i class="fas fa-users"></i> <?= $acumulado['total_empleados'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary" title="Total de planillas procesadas">
                                            <i class="fas fa-file-invoice"></i> <?= $acumulado['total_planillas'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small title="Años procesados: <?= htmlspecialchars($acumulado['anos_procesados']) ?>">
                                            <?= $acumulado['anos_procesados'] ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <small><?= date('d/m/Y H:i', strtotime($acumulado['fecha_ultimo_calculo'])) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/by-concept') ?>?concepto_id=<?= $acumulado['concepto_id'] ?>&year=<?= $selectedYear ?>"
                                           class="btn btn-sm btn-info" title="Ver detalles del concepto">
                                            <i class="fas fa-eye"></i> Ver Detalle
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-light font-weight-bold">
                                <td colspan="3" class="text-right">TOTALES:</td>
                                <td class="text-right text-primary">
                                    $<?= number_format(array_sum(array_column($acumulados, 'total_acumulado')), 2) ?>
                                </td>
                                <td class="text-center"><?= array_sum(array_column($acumulados, 'total_empleados')) ?></td>
                                <td class="text-center"><?= array_sum(array_column($acumulados, 'total_planillas')) ?></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Botones de Acción -->
        <div class="row">
            <div class="col-12">
                <a href="<?= \App\Core\UrlHelper::route('panel/acumulados') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Volver a Acumulados
                </a>
                <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/byType') ?>?tipo=<?= $tipo['codigo'] ?>&year=<?= $selectedYear ?>" class="btn btn-primary ml-2">
                    <i class="fas fa-filter mr-1"></i>
                    Vista Avanzada
                </a>
                <?php if (!empty($acumulados)): ?>
                    <button type="button" class="btn btn-info ml-2" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i>
                        Imprimir
                    </button>
                    <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/export') ?>?tipo=<?= $tipo['codigo'] ?>&year=<?= $selectedYear ?>" 
                       class="btn btn-success ml-2" target="_blank">
                        <i class="fas fa-download mr-1"></i>
                        Exportar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>


<script src="<?php echo url('assets/javascript/datatables-spanish.js', false); ?>"></script>
<script>
$(document).ready(function() {
    <?php if (!empty($acumulados)): ?>
    $('#acumulados-table').DataTable({
        language: {
            DATATABLES_SPANISH
        },
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true
    });
    <?php endif; ?>
});
</script>

<style>
@media print {
    .content-header, .card-header, .btn, .breadcrumb { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .table { font-size: 12px; }
}
</style>