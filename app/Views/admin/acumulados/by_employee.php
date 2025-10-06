<?php
$pageTitle = $selectedEmployee ? "Acumulados - " . htmlspecialchars(($selectedEmployee['firstname'] ?? '') . ' ' . ($selectedEmployee['lastname'] ?? '')) : "Acumulados por Empleado";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Acumulados por Empleado</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/acumulados') ?>">Acumulados</a></li>
                    <li class="breadcrumb-item active">Por Empleado</li>
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
                <form method="GET" action="<?= \App\Core\UrlHelper::route('panel/acumulados/byEmployee') ?>">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="empleado_id">Empleado *</label>
                                <select class="form-control select2" id="empleado_id" name="empleado_id" required>
                                    <option value="">Seleccione un empleado</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?= $employee['id'] ?>" <?= $employee['id'] == ($selectedEmployee['id'] ?? '') ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($employee['document_id'] . ' - ' . $employee['firstname'] . ' ' . $employee['lastname']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="year">Año</label>
                                <select class="form-control" id="year" name="year">
                                    <?php foreach ($availableYears as $yearOption): ?>
                                        <option value="<?= $yearOption ?>" <?= $yearOption == $year ? 'selected' : '' ?>>
                                            <?= $yearOption === 'todos' ? 'Todos' : $yearOption ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="month">Mes</label>
                                <select class="form-control" id="month" name="month">
                                    <option value="">Todos</option>
                                    <?php foreach ($availableMonths as $monthNum => $monthName): ?>
                                        <option value="<?= $monthNum ?>" <?= $monthNum == $month ? 'selected' : '' ?>>
                                            <?= $monthName ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="tipo_acumulado">Tipo Acumulado</label>
                                <select class="form-control" id="tipo_acumulado" name="tipo_acumulado">
                                    <option value="">Todos</option>
                                    <?php foreach ($tiposAcumulados as $tipoOption): ?>
                                        <option value="<?= htmlspecialchars($tipoOption['codigo'] ?? '') ?>" <?= ($tipoOption['codigo'] ?? '') == $tipoAcumulado ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tipoOption['descripcion'] ?? $tipoOption['codigo'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="group_by">Agrupar por</label>
                                <select class="form-control" id="group_by" name="group_by">
                                    <option value="tipo_acumulado" <?= $groupBy === 'tipo_acumulado' ? 'selected' : '' ?>>Tipo de Acumulado</option>
                                    <option value="mes" <?= $groupBy === 'mes' ? 'selected' : '' ?>>Mes</option>
                                    <option value="planilla" <?= $groupBy === 'planilla' ? 'selected' : '' ?>>Planilla</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-1"></i> Buscar
                            </button>
                            <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/byEmployee') ?>" class="btn btn-secondary">
                                <i class="fas fa-times mr-1"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resultados -->
        <?php if ($selectedEmployee): ?>
            <!-- Información del Empleado -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user mr-2"></i>
                        <?= htmlspecialchars(($selectedEmployee['firstname'] ?? '') . ' ' . ($selectedEmployee['lastname'] ?? '')) ?>
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light">
                            <?= htmlspecialchars($selectedEmployee['document_id'] ?? '') ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <strong>Período:</strong> Año <?= $year ?>
                        <?= $month ? ' - ' . $availableMonths[$month] : ' (Todos los meses)' ?>
                        <?= $tipoAcumulado ? ' - Tipo: ' . htmlspecialchars($tipoAcumulado ?? '') : '' ?>
                        <br>
                        <strong>Agrupado por:</strong>
                        <?php
                        $groupLabels = ['tipo_acumulado' => 'Tipo de Acumulado', 'mes' => 'Mes', 'planilla' => 'Planilla'];
                        echo $groupLabels[$groupBy] ?? 'Tipo de Acumulado';
                        ?>
                    </p>
                </div>
            </div>

            <!-- Cards de Totales Agrupados -->
            <?php if (!empty($acumuladosAgrupados)): ?>
                <div class="row">
                    <?php
                    $totalGeneral = array_sum(array_column($acumuladosAgrupados, 'total_monto'));

                    foreach ($acumuladosAgrupados as $agrupado):
                        $porcentaje = $totalGeneral > 0 ? ($agrupado['total_monto'] / $totalGeneral) * 100 : 0;

                        // Determinar color según tipo
                        if ($groupBy === 'tipo_acumulado') {
                            $colorClass = ($agrupado['tipo_concepto'] ?? 'ASIGNACION') === 'ASIGNACION' ? 'success' : 'danger';
                        } else {
                            $colorClass = 'info';
                        }
                    ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="small-box bg-<?= $colorClass ?>">
                                <div class="inner">
                                    <h3><?= currency_symbol() ?><?= number_format($agrupado['total_monto'], 2) ?></h3>
                                    <p><?= htmlspecialchars($agrupado['grupo_descripcion'] ?? 'N/A') ?></p>
                                    <div class="small mt-2">
                                        <?php if ($groupBy === 'planilla' && isset($agrupado['fecha_inicio'])): ?>
                                            <i class="fas fa-calendar"></i>
                                            <?= date('d/m/Y', strtotime($agrupado['fecha_inicio'])) ?> -
                                            <?= date('d/m/Y', strtotime($agrupado['fecha_fin'])) ?>
                                            <br>
                                        <?php endif; ?>
                                        <i class="fas fa-chart-pie"></i> <?= number_format($porcentaje, 1) ?>% del total
                                        <br>
                                        <i class="fas fa-file-invoice"></i> <?= $agrupado['total_planillas'] ?> planilla(s)
                                        <?php if (isset($agrupado['total_conceptos'])): ?>
                                            |
                                            <i class="fas fa-calculator"></i> <?= $agrupado['total_conceptos'] ?> concepto(s)
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="icon">
                                    <?php if ($groupBy === 'tipo_acumulado'): ?>
                                        <i class="fas fa-coins"></i>
                                    <?php elseif ($groupBy === 'mes'): ?>
                                        <i class="fas fa-calendar-alt"></i>
                                    <?php else: ?>
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total General -->
                <div class="row">
                    <div class="col-12">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-primary">
                                <i class="fas fa-calculator"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total General</span>
                                <span class="info-box-number"><?= currency_symbol() ?><?= number_format($totalGeneral, 2) ?></span>
                                <div class="progress">
                                    <div class="progress-bar bg-primary" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">
                                    <?= count($acumuladosAgrupados) ?> grupo(s) de acumulados
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla Detallada Colapsada -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-table mr-2"></i>
                            Detalle Completo de Acumulados
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="display: none;">
                        <div class="table-responsive">
                            <table id="tableAcumuladosDetalle" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Planilla</th>
                                        <th>Concepto</th>
                                        <th>Tipo Acumulado</th>
                                        <th>Tipo Concepto</th>
                                        <th>Mes</th>
                                        <th>Año</th>
                                        <th>Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($acumulados as $detalle): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($detalle['planilla_descripcion'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($detalle['concepto_descripcion'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($detalle['tipo_acumulado'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="badge badge-<?= ($detalle['tipo_concepto'] ?? '') === 'ASIGNACION' ? 'success' : (($detalle['tipo_concepto'] ?? '') === 'PATRONAL' ? 'info' : 'danger') ?>">
                                                    <?= htmlspecialchars($detalle['tipo_concepto'] ?? 'N/A') ?>
                                                </span>
                                            </td>
                                            <td><?= $availableMonths[$detalle['mes']] ?? $detalle['mes'] ?></td>
                                            <td><?= htmlspecialchars($detalle['ano'] ?? '') ?></td>
                                            <td class="text-right"><?= currency_symbol() ?><?= number_format($detalle['monto'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    No hay acumulados para este empleado con los filtros seleccionados.
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Por favor seleccione un empleado para ver sus acumulados.
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- Scripts -->
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione un empleado',
        allowClear: true
    });

    // Inicializar DataTable para detalle
    <?php if (!empty($acumulados)): ?>
    $('#tableAcumuladosDetalle').DataTable({
        responsive: true,
        autoWidth: false,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[5, 'desc'], [4, 'desc']], // Ordenar por año y mes desc
        pageLength: 25
    });
    <?php endif; ?>
});
</script>
