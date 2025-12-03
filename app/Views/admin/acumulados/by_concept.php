<?php
$pageTitle = $selectedConcepto ? "Acumulados - " . htmlspecialchars($selectedConcepto['descripcion']) : "Acumulados por Concepto";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-calculator mr-2"></i>
                    Acumulados por Concepto
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/acumulados') ?>">Acumulados</a></li>
                    <li class="breadcrumb-item active">Por Concepto</li>
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
                <form method="GET" id="filtroConceptoForm">
                    <?php if (isset($tipoPlanillaId) && $tipoPlanillaId): ?>
                        <input type="hidden" name="tipo_planilla" value="<?= htmlspecialchars($tipoPlanillaId) ?>">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="concepto_id">
                                    <i class="fas fa-calculator"></i> Concepto
                                </label>
                                <select class="form-control select2" id="concepto_id" name="concepto_id">
                                    <option value="">Seleccione un concepto</option>
                                    <option value="all" <?= ($conceptoId ?? '') === 'all' ? 'selected' : '' ?>>
                                        <strong>📊 TODOS LOS CONCEPTOS</strong>
                                    </option>
                                    <?php
                                    $currentTipo = '';
                                    foreach ($conceptos as $concepto):
                                        if ($concepto['tipo_concepto'] !== $currentTipo):
                                            if ($currentTipo !== '') echo '</optgroup>';
                                            $currentTipo = $concepto['tipo_concepto'];
                                            $tipoLabel = $currentTipo === 'ASIGNACION' ? 'Asignaciones' : ($currentTipo === 'PATRONAL' ? 'Patronales' : 'Deducciones');
                                            echo "<optgroup label=\"{$tipoLabel}\">";
                                        endif;
                                    ?>
                                        <option value="<?= $concepto['id'] ?>" <?= $concepto['id'] == ($conceptoId ?? '') ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($concepto['descripcion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if ($currentTipo !== '') echo '</optgroup>'; ?>
                                </select>
                                <small class="form-text text-muted">
                                    Seleccione "TODOS LOS CONCEPTOS" para ver un resumen agrupado de todos los conceptos
                                </small>
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
                                    <option value="empleado" <?= $groupBy === 'empleado' ? 'selected' : '' ?>>Empleado</option>
                                    <option value="planilla" <?= $groupBy === 'planilla' ? 'selected' : '' ?>>Planilla</option>
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
                            <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/byConcepto') ?>" class="btn btn-secondary">
                                <i class="fas fa-times mr-1"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resultados -->
        <?php if ($selectedConcepto): ?>
            <!-- Información del Concepto -->
            <div class="card <?= $selectedConcepto['tipo_concepto'] === 'ALL' ? 'card-primary' : 'card-info' ?>">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas <?= $selectedConcepto['tipo_concepto'] === 'ALL' ? 'fa-list' : 'fa-info-circle' ?> mr-2"></i>
                        <?= htmlspecialchars($selectedConcepto['descripcion']) ?>
                    </h3>
                    <div class="card-tools">
                        <?php if ($selectedConcepto['tipo_concepto'] === 'ALL'): ?>
                            <span class="badge badge-light">
                                <i class="fas fa-layer-group"></i> Vista Agrupada
                            </span>
                        <?php else: ?>
                            <span class="badge <?= $selectedConcepto['tipo_concepto'] === 'ASIGNACION' ? 'badge-success' : ($selectedConcepto['tipo_concepto'] === 'PATRONAL' ? 'badge-info' : 'badge-danger') ?>">
                                <?= $selectedConcepto['tipo_concepto'] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <strong>Período:</strong> Año <?= $year ?>
                        <?= $month ? ' - ' . $availableMonths[$month] : ' (Todos los meses)' ?>
                        <br>
                        <?php if ($selectedConcepto['tipo_concepto'] === 'ALL'): ?>
                            <strong>Agrupado por:</strong> Concepto (mostrando totales de todos los conceptos)
                        <?php else: ?>
                            <strong>Agrupado por:</strong>
                            <?php
                            $groupLabels = ['empleado' => 'Empleado', 'planilla' => 'Planilla', 'ano' => 'Año'];
                            echo $groupLabels[$groupBy] ?? 'Empleado';
                            ?>
                        <?php endif; ?>
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

                    // Determinar color según el contexto
                    if ($selectedConcepto['tipo_concepto'] === 'ALL') {
                        // Para "TODOS LOS CONCEPTOS", el color se determina por cada concepto individual
                        $colorClass = 'info'; // Color por defecto, se sobreescribe por concepto
                    } else {
                        // Para concepto específico, color según tipo
                        $colorClass = $selectedConcepto['tipo_concepto'] === 'ASIGNACION' ? 'success' : ($selectedConcepto['tipo_concepto'] === 'PATRONAL' ? 'info' : 'danger');
                    }

                    foreach ($acumuladosAgrupados as $agrupado):
                        $porcentaje = $totalGeneral > 0 ? ($agrupado['total_monto'] / $totalGeneral) * 100 : 0;

                        // Para "TODOS LOS CONCEPTOS", determinar color por tipo de concepto
                        if ($selectedConcepto['tipo_concepto'] === 'ALL') {
                            $colorClass = $agrupado['tipo_concepto'] === 'ASIGNACION' ? 'success' : ($agrupado['tipo_concepto'] === 'PATRONAL' ? 'info' : 'danger');
                        }
                    ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="small-box bg-<?= $colorClass ?>">
                                <div class="inner">
                                    <h3><?= currency_symbol() ?><?= number_format($agrupado['total_monto'], 2) ?></h3>
                                    <p>
                                        <?= htmlspecialchars($agrupado['grupo_descripcion']) ?>
                                        <?php if ($selectedConcepto['tipo_concepto'] === 'ALL' && isset($agrupado['tipo_concepto'])): ?>
                                            <br>
                                            <span class="badge badge-light mt-1">
                                                <?= $agrupado['tipo_concepto'] ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (isset($agrupado['tipo_acumulado_descripcion'])): ?>
                                            <br>
                                            <span class="badge badge-warning mt-1">
                                                <i class="fas fa-tag"></i> <?= htmlspecialchars($agrupado['tipo_acumulado_descripcion']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                    <div class="small mt-2">
                                        <?php if ($groupBy === 'empleado'): ?>
                                            <i class="fas fa-id-card"></i> <?= htmlspecialchars($agrupado['document_id'] ?? 'N/A') ?>
                                            <br>
                                        <?php elseif ($groupBy === 'planilla'): ?>
                                            <?php if (isset($agrupado['frecuencia_nombre'])): ?>
                                                <i class="fas fa-clock"></i> <strong><?= htmlspecialchars($agrupado['frecuencia_nombre']) ?></strong>
                                                <br>
                                            <?php endif; ?>
                                            <?php if (isset($agrupado['fecha_inicio']) && isset($agrupado['fecha_fin'])): ?>
                                                <i class="fas fa-calendar"></i>
                                                <?= date('d/m/Y', strtotime($agrupado['fecha_inicio'])) ?> -
                                                <?= date('d/m/Y', strtotime($agrupado['fecha_fin'])) ?>
                                                <br>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <i class="fas fa-chart-pie"></i> <?= number_format($porcentaje, 1) ?>% del total
                                        <br>
                                        <i class="fas fa-users"></i> <?= $agrupado['total_empleados'] ?> empleado(s)
                                    </div>
                                </div>
                                <div class="icon">
                                    <?php if ($selectedConcepto['tipo_concepto'] === 'ALL'): ?>
                                        <i class="fas fa-calculator"></i>
                                    <?php elseif ($groupBy === 'empleado'): ?>
                                        <i class="fas fa-user"></i>
                                    <?php elseif ($groupBy === 'planilla'): ?>
                                        <i class="fas fa-file-invoice-dollar"></i>
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
                    No se encontraron registros para el concepto seleccionado en el período especificado.
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
                                <i class="fas fa-file-csv mr-1"></i> Exportar CSV
                            </button>
                            <button type="button" class="btn btn-primary btn-sm mr-2" onclick="exportToExcel()">
                                <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                            </button>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm table-hover" id="acumuladosTable" style="width:100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Cédula</th>
                                        <th style="width: 35%">Concepto</th>
                                        <th>Planilla</th>
                                        <th>Mes</th>
                                        <th>Año</th>
                                        <th style="width: 15%">Monto</th>
                                        <th>Período</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($acumulados as $acumulado): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($acumulado['nombre_empleado']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($acumulado['document_id']) ?></td>
                                            <td>
                                                <i class="fas fa-calculator mr-1"></i>
                                                <small><?= htmlspecialchars($acumulado['concepto_descripcion'] ?? 'N/A') ?> | <?= htmlspecialchars($acumulado['tipo_acumulado_descripcion'] ?? 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($acumulado['planilla_descripcion'] ?? 'N/A') ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-secondary badge-sm">
                                                    <?= $availableMonths[$acumulado['mes']] ?? $acumulado['mes'] ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <strong><?= $acumulado['ano'] ?></strong>
                                            </td>
                                            <td class="text-right font-weight-bold text-primary">
                                                <?= currency_symbol() ?><?= number_format($acumulado['monto'], 2) ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($acumulado['fecha_inicio']) && !empty($acumulado['fecha_fin'])): ?>
                                                    <small style="font-size: 0.7rem;">
                                                        <?= date('d/m', strtotime($acumulado['fecha_inicio'])) ?>
                                                        <br>
                                                        <?= date('d/m', strtotime($acumulado['fecha_fin'])) ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="thead-light">
                                    <tr>
                                        <th colspan="6" class="text-right">
                                            <strong>TOTAL:</strong>
                                        </th>
                                        <th class="text-right text-primary">
                                            <strong>
                                                <?= currency_symbol() ?><?= number_format(array_sum(array_column($acumulados, 'monto')), 2) ?>
                                            </strong>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Instrucciones:</strong> Seleccione un concepto y configure los filtros para ver los acumulados agrupados.
                <br><small>Puede agrupar por: Empleado, Planilla o Año</small>
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

    // Initialize DataTable - Configuración básica responsive
    if (jQuery("#acumuladosTable").length) {
        jQuery("#acumuladosTable").DataTable({
            "responsive": true,
            "pageLength": 50,
            "order": [[2, "asc"], [6, "desc"]], // Ordenar por concepto y luego por monto
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

function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    const exportUrl = '<?= \App\Core\UrlHelper::route('panel/acumulados/exportExcel') ?>?' + params.toString();
    window.open(exportUrl, '_blank');
}
</script>
