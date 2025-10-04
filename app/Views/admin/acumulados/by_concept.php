<?php
$pageTitle = $selectedConcepto ? "Acumulados - " . htmlspecialchars($selectedConcepto['descripcion']) : "Acumulados por Concepto";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Acumulados por Concepto</h1>
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
            </div>
            <div class="card-body">
                <form method="GET" action="<?= \App\Core\UrlHelper::route('panel/acumulados/byType') ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="year">Año</label>
                                <select class="form-control" id="year" name="year">
                                    <?php foreach ($availableYears as $yearOption): ?>
                                        <option value="<?= $yearOption ?>" <?= $yearOption == $year ? 'selected' : '' ?>>
                                            <?= $yearOption ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="month">Mes (Opcional)</label>
                                <select class="form-control" id="month" name="month">
                                    <option value="">Todos los meses</option>
                                    <?php foreach ($availableMonths as $monthNum => $monthName): ?>
                                        <option value="<?= $monthNum ?>" <?= $monthNum == $month ? 'selected' : '' ?>>
                                            <?= $monthName ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="concepto_id">Concepto *</label>
                                <select class="form-control" id="concepto_id" name="concepto_id" required>
                                    <option value="">Seleccione un concepto</option>
                                    <?php
                                    $currentTipo = '';
                                    foreach ($conceptos as $concepto):
                                        if ($concepto['tipo_concepto'] !== $currentTipo):
                                            if ($currentTipo !== '') echo '</optgroup>';
                                            $currentTipo = $concepto['tipo_concepto'];
                                            $tipoLabel = $currentTipo === 'ASIGNACION' ? 'Asignaciones' : 'Deducciones';
                                            echo "<optgroup label=\"{$tipoLabel}\">";
                                        endif;
                                    ?>
                                        <option value="<?= $concepto['id'] ?>" <?= $concepto['id'] == ($conceptoId ?? '') ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($concepto['descripcion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if ($currentTipo !== '') echo '</optgroup>'; ?>
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
        <?php if ($selectedConcepto): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-table mr-2"></i>
                        Registros de <?= htmlspecialchars($selectedConcepto['descripcion']) ?>
                        - Año <?= $year ?>
                        <?= $month ? ' - ' . $availableMonths[$month] : '' ?>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" onclick="exportToCSV()">
                            <i class="fas fa-download mr-1"></i> Exportar CSV
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($acumulados)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="acumuladosTable">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Cédula</th>
                                        <th>Mes</th>
                                        <th>Planilla</th>
                                        <th>Tipo</th>
                                        <th class="text-right">Monto</th>
                                        <th>Fecha Período</th>
                                        <th>Creado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totalMonto = 0;
                                    foreach ($acumulados as $acumulado):
                                        $totalMonto += $acumulado['monto'];
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($acumulado['nombre_empleado']) ?></td>
                                            <td><?= htmlspecialchars($acumulado['document_id']) ?></td>
                                            <td class="text-center">
                                                <?= $availableMonths[$acumulado['mes']] ?? $acumulado['mes'] ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($acumulado['planilla_descripcion'] ?? 'N/A') ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $acumulado['tipo_concepto'] === 'ASIGNACION' ? 'badge-success' : 'badge-danger' ?>">
                                                    <?= $acumulado['tipo_concepto'] ?>
                                                </span>
                                            </td>
                                            <td class="text-right font-weight-bold">
                                                <?= currency_symbol() ?><?= number_format($acumulado['monto'], 2) ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($acumulado['fecha_desde']) && !empty($acumulado['fecha_hasta'])): ?>
                                                    <?= date('d/m/Y', strtotime($acumulado['fecha_desde'])) ?> -
                                                    <?= date('d/m/Y', strtotime($acumulado['fecha_hasta'])) ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= !empty($acumulado['created_at']) ? date('d/m/Y H:i', strtotime($acumulado['created_at'])) : 'N/A' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <th colspan="5" class="text-right">TOTAL:</th>
                                        <th class="text-right">
                                            <?= currency_symbol() ?><?= number_format($totalMonto, 2) ?>
                                        </th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Resumen -->
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info">
                                            <i class="fas fa-users"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Empleados</span>
                                            <span class="info-box-number"><?= count(array_unique(array_column($acumulados, 'employee_id'))) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success">
                                            <i class="fas fa-list"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Registros</span>
                                            <span class="info-box-number"><?= count($acumulados) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon <?= $selectedConcepto['tipo_concepto'] === 'ASIGNACION' ? 'bg-success' : 'bg-danger' ?>">
                                            <i class="fas fa-calculator"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Acumulado</span>
                                            <span class="info-box-number"><?= currency_symbol() ?><?= number_format($totalMonto, 2) ?></span>
                                        </div>
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
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                Seleccione un concepto y período para ver los registros de acumulados.
            </div>
        <?php endif; ?>
    </div>
</section>


<script src="<?php echo url('assets/javascript/datatables-spanish.js', false); ?>"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($("#acumuladosTable").length) {
        $("#acumuladosTable").DataTable({
            "responsive": true,
            "pageLength": 25,
            "order": [[7, "desc"]], // Ordenar por fecha creación
            "language": {
                DATATABLES_SPANISH
            }
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