<?php
$pageTitle = $selectedTipo ? "Acumulados - " . htmlspecialchars($selectedTipo['descripcion']) : "Acumulados por Tipo de Acumulado";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-layer-group mr-2"></i>
                    Acumulados por Tipo de Acumulado
                </h1>
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
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" id="filtroTipoForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_acumulado">
                                    <i class="fas fa-layer-group"></i> Tipo de Acumulado *
                                </label>
                                <select class="form-control select2" id="tipo_acumulado" name="tipo_acumulado" required>
                                    <option value="">Seleccione un tipo de acumulado</option>
                                    <?php foreach ($tiposAcumulados as $tipo): ?>
                                        <option value="<?= $tipo['codigo'] ?>" <?= $tipo['codigo'] == ($tipoAcumuladoCodigo ?? '') ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tipo['descripcion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="year"><i class="fas fa-calendar-alt"></i> Año</label>
                                <select class="form-control" id="year" name="year">
                                    <?php foreach ($availableYears as $yearOption): ?>
                                        <option value="<?= $yearOption ?>" <?= $yearOption == $year ? 'selected' : '' ?>>
                                            <?= $yearOption ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="month"><i class="fas fa-calendar"></i> Mes</label>
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
                                <label for="group_by"><i class="fas fa-layer-group"></i> Agrupar por</label>
                                <select class="form-control" id="group_by" name="group_by">
                                    <option value="empleado" <?= $groupBy === 'empleado' ? 'selected' : '' ?>>Empleado</option>
                                    <option value="mes" <?= $groupBy === 'mes' ? 'selected' : '' ?>>Mes</option>
                                    <option value="ano" <?= $groupBy === 'ano' ? 'selected' : '' ?>>Año</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-1"></i> Buscar
                            </button>
                            <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/byType') ?>" class="btn btn-secondary">
                                <i class="fas fa-times mr-1"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resultados -->
        <?php if ($selectedTipo): ?>
            <!-- Información del Tipo -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>
                        <?= htmlspecialchars($selectedTipo['descripcion']) ?>
                    </h3>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <strong>Período:</strong> Año <?= $year ?>
                        <?= $month ? ' - ' . $availableMonths[$month] : ' (Todos los meses)' ?>
                        <br>
                        <strong>Agrupado por:</strong>
                        <?php
                        $groupLabels = ['empleado' => 'Empleado', 'mes' => 'Mes', 'ano' => 'Año'];
                        echo $groupLabels[$groupBy] ?? 'Empleado';
                        ?>
                    </p>
                </div>
            </div>

            <!-- Cards de Totales Agrupados -->
            <?php if (!empty($acumuladosAgrupados)): ?>
                <div class="row">
                    <?php
                    $totalGeneral = array_sum(array_column($acumuladosAgrupados, 'total_monto'));
                    $colorClass = 'info';

                    foreach ($acumuladosAgrupados as $agrupado):
                        $porcentaje = $totalGeneral > 0 ? ($agrupado['total_monto'] / $totalGeneral) * 100 : 0;
                    ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="small-box bg-<?= $colorClass ?>">
                                <div class="inner">
                                    <h3><?= currency_symbol() ?><?= number_format($agrupado['total_monto'], 2) ?></h3>
                                    <p><?= htmlspecialchars($agrupado['grupo_descripcion']) ?></p>
                                    <div class="small mt-2">
                                        <?php if ($groupBy === 'empleado'): ?>
                                            <i class="fas fa-id-card"></i> <?= htmlspecialchars($agrupado['document_id'] ?? 'N/A') ?>
                                            <br>
                                        <?php endif; ?>
                                        <i class="fas fa-chart-pie"></i> <?= number_format($porcentaje, 1) ?>% del total
                                        <br>
                                        <i class="fas fa-file-invoice"></i> <?= $agrupado['total_planillas'] ?> planilla(s)
                                        |
                                        <i class="fas fa-users"></i> <?= $agrupado['total_empleados'] ?> empleado(s)
                                    </div>
                                </div>
                                <div class="icon">
                                    <?php if ($groupBy === 'empleado'): ?>
                                        <i class="fas fa-user"></i>
                                    <?php elseif ($groupBy === 'mes'): ?>
                                        <i class="fas fa-calendar"></i>
                                    <?php else: ?>
                                        <i class="fas fa-calendar-alt"></i>
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
                            <span class="info-box-icon bg-<?= $colorClass ?>">
                                <i class="fas fa-calculator"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text"><strong>TOTAL GENERAL</strong></span>
                                <span class="info-box-number">
                                    <?= currency_symbol() ?><?= number_format($totalGeneral, 2) ?>
                                </span>
                                <small>
                                    <?= count($acumuladosAgrupados) ?> <?= $groupLabels[$groupBy] ?? 'grupo' ?>(s)
                                    | <?= count($acumulados) ?> registro(s) en total
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    No se encontraron registros para el tipo de acumulado seleccionado en el período especificado.
                </div>
            <?php endif; ?>

            <!-- Tabla Detallada (opcional, colapsada por defecto) -->
            <?php if (!empty($acumulados)): ?>
                <div class="card collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-table mr-2"></i>
                            Detalle de Registros (<?= count($acumulados) ?> registros)
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-success btn-sm mr-2" onclick="exportToCSV()">
                                <i class="fas fa-download mr-1"></i> Exportar CSV
                            </button>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm" id="acumuladosTable">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Cédula</th>
                                        <th>Concepto</th>
                                        <th>Mes</th>
                                        <th>Año</th>
                                        <th class="text-right">Monto</th>
                                        <th>Planilla</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($acumulados as $acumulado): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($acumulado['nombre_empleado']) ?></td>
                                            <td><?= htmlspecialchars($acumulado['document_id']) ?></td>
                                            <td><?= htmlspecialchars($acumulado['concepto_descripcion']) ?></td>
                                            <td class="text-center">
                                                <?= $availableMonths[$acumulado['mes']] ?? $acumulado['mes'] ?>
                                            </td>
                                            <td class="text-center"><?= $acumulado['ano'] ?></td>
                                            <td class="text-right font-weight-bold">
                                                <?= currency_symbol() ?><?= number_format($acumulado['monto'], 2) ?>
                                            </td>
                                            <td><?= htmlspecialchars($acumulado['planilla_descripcion'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Instrucciones:</strong> Seleccione un tipo de acumulado y configure los filtros para ver los acumulados agrupados.
                <br><small>Puede agrupar por: Empleado, Mes o Año</small>
            </div>
        <?php endif; ?>

        <!-- Botones de Acción -->
        <div class="row mt-3">
            <div class="col-12">
                <a href="<?= \App\Core\UrlHelper::route('panel/acumulados') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver a Acumulados
                </a>
            </div>
        </div>
    </div>
</section>


<script src="<?php echo url('assets/javascript/datatables-spanish.js', false); ?>"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($("#acumuladosTable").length) {
        $("#acumuladosTable").DataTable({
            "responsive": true,
            "pageLength": 50,
            "order": [[5, "desc"]], // Ordenar por monto
            "language": {
                DATATABLES_SPANISH
            }
        });
    }

    // Initialize Select2
    if ($('.select2').length) {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    }

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

function exportToCSV() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    const exportUrl = '<?= \App\Core\UrlHelper::route('panel/acumulados/export') ?>?' + params.toString();
    window.open(exportUrl, '_blank');
}
</script>
