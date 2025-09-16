<?php
// Verificar variables necesarias
$selectedTipo = $selectedTipo ?? null;
$acumulados = $acumulados ?? [];
$year = $year ?? date('Y');
$tiposAcumulados = $tiposAcumulados ?? [];
$availableYears = $availableYears ?? [date('Y')];

$pageTitle = $selectedTipo ? "Acumulados - {$selectedTipo['descripcion']}" : "Acumulados por Tipo";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Acumulados por Tipo</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/acumulados') ?>">Acumulados</a></li>
                    <li class="breadcrumb-item active">Por Tipo</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <!-- Filtros -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter mr-2"></i>
                    Filtros de Búsqueda
                </h3>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= \App\Core\UrlHelper::route('panel/acumulados/byType') ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tipo">Tipo de Acumulado</label>
                                <select name="tipo" id="tipo" class="form-control">
                                    <option value="">-- Seleccionar Tipo --</option>
                                    <?php foreach ($tiposAcumulados as $tipo): ?>
                                        <option value="<?= htmlspecialchars($tipo['codigo']) ?>" 
                                                <?= ($selectedTipo && $selectedTipo['codigo'] == $tipo['codigo']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tipo['descripcion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="year">Año</label>
                                <select name="year" id="year" class="form-control">
                                    <?php foreach ($availableYears as $availableYear): ?>
                                        <option value="<?= $availableYear ?>" <?= $year == $availableYear ? 'selected' : '' ?>>
                                            <?= $availableYear ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search mr-1"></i>
                                        Filtrar
                                    </button>
                                    <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/byType') ?>" class="btn btn-secondary ml-2">
                                        <i class="fas fa-times mr-1"></i>
                                        Limpiar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selectedTipo && !empty($acumulados)): ?>
            <!-- Información del Tipo -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>
                        <?= htmlspecialchars($selectedTipo['descripcion']) ?> - Año <?= $year ?>
                    </h3>
                </div>
                <div class="card-body">
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
                                    <span class="info-box-number">$<?= number_format(count($acumulados) > 0 ? array_sum(array_column($acumulados, 'total_acumulado')) / count($acumulados) : 0, 2) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-secondary">
                                <span class="info-box-icon">
                                    <i class="fas fa-hashtag"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Planillas</span>
                                    <span class="info-box-number"><?= array_sum(array_column($acumulados, 'total_planillas')) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                                <th class="text-center">Frecuencia</th>
                                <th class="text-right">Total Acumulado</th>
                                <th class="text-center">Planillas Procesadas</th>
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
                                        <span class="badge badge-primary"><?= htmlspecialchars($acumulado['frecuencia'] ?? 'N/A') ?></span>
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        $<?= number_format($acumulado['total_acumulado'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?= $acumulado['total_planillas'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?= !empty($acumulado['ultima_planilla']) ? htmlspecialchars($acumulado['ultima_planilla']) : '<em>N/A</em>' ?>
                                    </td>
                                    <td class="text-center">
                                        <?= !empty($acumulado['fecha_ultimo_calculo']) ? date('d/m/Y H:i', strtotime($acumulado['fecha_ultimo_calculo'])) : '<em>N/A</em>' ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/byEmployee') ?>?empleado_id=<?= $acumulado['employee_id'] ?>&year=<?= $year ?>" 
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

        <?php elseif ($selectedTipo): ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                    <h5>No hay acumulados para este tipo en <?= $year ?></h5>
                    <p class="text-muted">
                        No se encontraron registros de <strong><?= htmlspecialchars($selectedTipo['descripcion']) ?></strong> para el año <?= $year ?>.
                    </p>
                    <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/byType') ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Volver a Filtros
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>Selecciona un tipo de acumulado</h5>
                    <p class="text-muted">
                        Usa los filtros de arriba para seleccionar un tipo específico de acumulado y ver los detalles.
                    </p>
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
                <?php if ($selectedTipo && !empty($acumulados)): ?>
                    <button type="button" class="btn btn-info ml-2" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i>
                        Imprimir
                    </button>
                    <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/export') ?>?year=<?= $year ?>" 
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
    <?php if ($selectedTipo && !empty($acumulados)): ?>
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