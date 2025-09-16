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
                                    <i class="fas fa-users"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Empleados</span>
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
                                    <i class="fas fa-calculator"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Promedio</span>
                                    <span class="info-box-number">$<?= number_format(array_sum(array_column($acumulados, 'total_acumulado')) / count($acumulados), 2) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-secondary">
                                <span class="info-box-icon">
                                    <i class="fas fa-hashtag"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Conceptos Totales</span>
                                    <span class="info-box-number"><?= array_sum(array_column($acumulados, 'total_conceptos_incluidos')) ?></span>
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
            <!-- Detalle por Empleado -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-table mr-2"></i>
                        Detalle por Empleado
                    </h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped" id="acumulados-table">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Cédula</th>
                                <th class="text-center">Período</th>
                                <th class="text-right">Total Acumulado</th>
                                <th class="text-center">Conceptos</th>
                                <th class="text-center">Última Planilla</th>
                                <th class="text-center">Última Actualización</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($acumulados as $acumulado): ?>
                                <tr>
                                    <td><?= htmlspecialchars($acumulado['nombre_empleado']) ?></td>
                                    <td><?= htmlspecialchars($acumulado['document_id']) ?></td>
                                    <td class="text-center">
                                        <?= date('d/m/Y', strtotime($acumulado['periodo_inicio'])) ?> - 
                                        <?= date('d/m/Y', strtotime($acumulado['periodo_fin'])) ?>
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        $<?= number_format($acumulado['total_acumulado'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?= $acumulado['total_conceptos_incluidos'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?= $acumulado['ultima_planilla'] ? htmlspecialchars($acumulado['ultima_planilla']) : '<em>N/A</em>' ?>
                                    </td>
                                    <td class="text-center">
                                        <?= date('d/m/Y H:i', strtotime($acumulado['fecha_ultimo_calculo'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/byEmployee') ?>?empleado_id=<?= $acumulado['empleado_id'] ?>&year=<?= $selectedYear ?>" 
                                           class="btn btn-sm btn-info" title="Ver detalles del empleado">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
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

<script>
$(document).ready(function() {
    <?php if (!empty($acumulados)): ?>
    $('#acumulados-table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
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