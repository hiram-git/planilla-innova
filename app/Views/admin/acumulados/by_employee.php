<?php
$pageTitle = $selectedEmployee ? "Acumulados - " . htmlspecialchars(($selectedEmployee['firstname'] ?? '') . ' ' . ($selectedEmployee['lastname'] ?? '')) : "Acumulados por Empleado";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-user-circle mr-2"></i>
                    Acumulados por Empleado
                </h1>
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
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" id="filtroEmpleadoForm">
                    <?php if (isset($tipoPlanillaId) && $tipoPlanillaId): ?>
                        <input type="hidden" name="tipo_planilla" value="<?= htmlspecialchars($tipoPlanillaId) ?>">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="empleado_id">
                                    <i class="fas fa-user"></i> Empleado *
                                </label>
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="tipo_acumulado"><i class="fas fa-tag"></i> Tipo Acumulado</label>
                                <select class="form-control" id="tipo_acumulado" name="tipo_acumulado">
                                    <option value="">Todos</option>
                                    <?php foreach ($tiposAcumulados as $tipo): ?>
                                        <option value="<?= htmlspecialchars($tipo['codigo']) ?>" <?= ($tipoAcumulado ?? '') === $tipo['codigo'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tipo['descripcion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="group_by"><i class="fas fa-layer-group"></i> Agrupar por</label>
                                <select class="form-control" id="group_by" name="group_by">
                                    <option value="concepto" <?= $groupBy === 'concepto' ? 'selected' : '' ?>>Concepto</option>
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
                        <br>
                        <strong>Agrupado por:</strong>
                        <?php
                        $groupLabels = ['concepto' => 'Concepto', 'mes' => 'Mes', 'planilla' => 'Planilla'];
                        echo $groupLabels[$groupBy] ?? 'Concepto';
                        ?>
                        <?php if ($tipoAcumulado): ?>
                            <br>
                            <strong>Tipo Acumulado:</strong>
                            <span class="badge badge-warning">
                                <?php
                                $tipoDesc = 'N/A';
                                foreach ($tiposAcumulados as $tipo) {
                                    if ($tipo['codigo'] === $tipoAcumulado) {
                                        $tipoDesc = $tipo['descripcion'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($tipoDesc);
                                ?>
                            </span>
                        <?php endif; ?>
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

                        // Determinar color según tipo de concepto
                        if ($groupBy === 'concepto') {
                            $tipoConcepto = $agrupado['tipo_concepto'] ?? 'ASIGNACION';
                            $colorClass = $tipoConcepto === 'ASIGNACION' ? 'success' : ($tipoConcepto === 'PATRONAL' ? 'info' : 'danger');
                        } else {
                            $colorClass = 'info';
                        }
                    ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="small-box bg-<?= $colorClass ?>">
                                <div class="inner">
                                    <h3><?= currency_symbol() ?><?= number_format($agrupado['total_monto'], 2) ?></h3>
                                    <p>
                                        <?= htmlspecialchars($agrupado['grupo_descripcion'] ?? 'N/A') ?>
                                        <?php if (isset($agrupado['tipo_acumulado_descripcion'])): ?>
                                            <br>
                                            <span class="badge badge-warning mt-1">
                                                <i class="fas fa-tag"></i> <?= htmlspecialchars($agrupado['tipo_acumulado_descripcion']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                    <div class="small mt-2">
                                        <?php if ($groupBy === 'planilla' && isset($agrupado['fecha_inicio'])): ?>
                                            <i class="fas fa-calendar"></i>
                                            <?= date('d/m/Y', strtotime($agrupado['fecha_inicio'])) ?> -
                                            <?= date('d/m/Y', strtotime($agrupado['fecha_fin'])) ?>
                                            <br>
                                        <?php endif; ?>
                                        <?php if ($groupBy === 'concepto' && isset($agrupado['tipo_concepto'])): ?>
                                            <span class="badge badge-<?= $agrupado['tipo_concepto'] === 'ASIGNACION' ? 'success' : ($agrupado['tipo_concepto'] === 'PATRONAL' ? 'info' : 'danger') ?>">
                                                <?= $agrupado['tipo_concepto'] ?>
                                            </span>
                                            <br>
                                        <?php endif; ?>
                                        <i class="fas fa-chart-pie"></i> <?= number_format($porcentaje, 1) ?>% del total
                                        <br>
                                        <i class="fas fa-file-invoice"></i> <?= $agrupado['total_planillas'] ?? 0 ?> planilla(s)
                                        <?php if (isset($agrupado['total_conceptos']) && $groupBy !== 'concepto'): ?>
                                            |
                                            <i class="fas fa-calculator"></i> <?= $agrupado['total_conceptos'] ?> concepto(s)
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="icon">
                                    <?php if ($groupBy === 'concepto'): ?>
                                        <i class="fas fa-calculator"></i>
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
                    No se encontraron registros para el empleado seleccionado en el período especificado.
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
                        <table class="table table-bordered table-striped table-sm" id="acumuladosTable" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Planilla</th>
                                    <th>Concepto</th>
                                    <th>Tipo Acumulado</th>
                                    <th>Tipo Concepto</th>
                                    <th>Mes</th>
                                    <th>Año</th>
                                    <th class="text-right">Monto</th>
                                    <th>Fecha Período</th>
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
                                        <td class="text-center">
                                            <?= $availableMonths[$detalle['mes']] ?? $detalle['mes'] ?>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars($detalle['ano'] ?? '') ?></td>
                                        <td class="text-right font-weight-bold">
                                            <?= currency_symbol() ?><?= number_format($detalle['monto'], 2) ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($detalle['fecha_inicio']) && !empty($detalle['fecha_fin'])): ?>
                                                <?= date('d/m/Y', strtotime($detalle['fecha_inicio'])) ?> -
                                                <?= date('d/m/Y', strtotime($detalle['fecha_fin'])) ?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Instrucciones:</strong> Seleccione un empleado y configure los filtros para ver los acumulados agrupados.
                <br><small>Puede agrupar por: Concepto, Mes o Planilla</small>
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

<script>
// Esperar a que jQuery esté disponible
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si jQuery está cargado
    if (typeof jQuery === 'undefined') {
        console.error('jQuery no está cargado');
        return;
    }

    // Initialize DataTable
    if (jQuery("#acumuladosTable").length) {
        jQuery("#acumuladosTable").DataTable({
            "responsive": true,
            "pageLength": 50,
            "order": [[4, "desc"], [3, "desc"]], // Ordenar por año y mes
            "language": window.DATATABLES_SPANISH || {}
        });
    }

    // Initialize Select2
    if (jQuery('.select2').length) {
        jQuery('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    }

    // Initialize tooltips
    jQuery('[data-toggle="tooltip"]').tooltip();
});

function exportToCSV() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    const exportUrl = '<?= \App\Core\UrlHelper::route('panel/acumulados/export') ?>?' + params.toString();
    window.open(exportUrl, '_blank');
}
</script>
