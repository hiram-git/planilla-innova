<?php
$pageTitle = $selectedEmployee ? "Acumulados - " . htmlspecialchars($selectedEmployee['firstname'] . ' ' . $selectedEmployee['lastname']) : "Acumulados por Empleado";
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
                                <label for="year">Año</label>
                                <select class="form-control" id="year" name="year">
                                    <?php foreach ($availableYears as $yearOption): ?>
                                        <option value="<?= $yearOption ?>" <?= $yearOption == $year ? 'selected' : '' ?>>
                                            <?= $yearOption === 'todos' ? 'Todos los años' : $yearOption ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="tipo_acumulado">Tipo Acumulado (Opcional)</label>
                                <select class="form-control" id="tipo_acumulado" name="tipo_acumulado">
                                    <option value="">Todos los tipos</option>
                                    <?php foreach ($tiposAcumulados as $tipoOption): ?>
                                        <option value="<?= htmlspecialchars($tipoOption) ?>" <?= $tipoOption == $tipoAcumulado ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tipoOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="empleado_id">Empleado *</label>
                                <select class="form-control" id="empleado_id" name="empleado_id" required>
                                    <option value="">Seleccione un empleado</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?= $employee['id'] ?>" <?= $employee['id'] == ($empleadoId ?? '') ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($employee['document_id'] . ' - ' . $employee['firstname'] . ' ' . $employee['lastname']) ?>
                                        </option>
                                    <?php endforeach; ?>
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

        <!-- Totales Superiores (cuando hay empleado seleccionado) -->
        <?php if ($selectedEmployee && $totales): ?>
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= currency_symbol() ?><?= number_format($totales['ASIGNACION']['total_monto'], 2) ?></h3>
                            <p>Total Asignaciones</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="small-box-footer">
                            <?= $totales['ASIGNACION']['total_conceptos'] ?> conceptos, <?= $totales['ASIGNACION']['total_registros'] ?> registros
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= currency_symbol() ?><?= number_format($totales['DEDUCCION']['total_monto'], 2) ?></h3>
                            <p>Total Deducciones</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-minus"></i>
                        </div>
                        <div class="small-box-footer">
                            <?= $totales['DEDUCCION']['total_conceptos'] ?> conceptos, <?= $totales['DEDUCCION']['total_registros'] ?> registros
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= currency_symbol() ?><?= number_format($totales['NETO'], 2) ?></h3>
                            <p>Total Neto</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="small-box-footer">
                            Asignaciones - Deducciones
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= count($acumulados) ?></h3>
                            <p>Total Registros</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="small-box-footer">
                            Período: <?= $year ?><?= $month ? ' - ' . $availableMonths[$month] : '' ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Información del Empleado Seleccionado -->
        <?php if ($selectedEmployee): ?>
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user mr-2"></i>
                        Información del Empleado
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Nombre:</strong> <?= htmlspecialchars($selectedEmployee['firstname'] . ' ' . $selectedEmployee['lastname']) ?><br>
                            <strong>Cédula:</strong> <?= htmlspecialchars($selectedEmployee['document_id'] ?? 'N/A') ?><br>
                            <strong>Cargo:</strong> <?= htmlspecialchars($selectedEmployee['position_name'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Período:</strong> Año <?= $year ?><?= $month ? ' - Mes ' . $availableMonths[$month] : '' ?><br>
                            <strong>Estado:</strong> <span class="badge badge-success">Activo</span><br>
                            <strong>Fecha Consulta:</strong> <?= date('d/m/Y H:i') ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Detalle de Registros -->
        <?php if ($selectedEmployee): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-table mr-2"></i>
                        Detalle de Acumulados - <?= htmlspecialchars($selectedEmployee['firstname'] . ' ' . $selectedEmployee['lastname']) ?>
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
                                        <th>Mes</th>
                                        <th>Acumulado</th>
                                        <th>Tipo</th>
                                        <th>Planilla</th>
                                        <th class="text-right">Monto</th>
                                        <th>Fecha Período</th>
                                        <th>Creado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totalAsignaciones = 0;
                                    $totalDeducciones = 0;
                                    foreach ($acumulados as $acumulado):
                                        if ($acumulado['tipo_concepto'] === 'ASIGNACION') {
                                            $totalAsignaciones += $acumulado['monto'];
                                        } else {
                                            $totalDeducciones += $acumulado['monto'];
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-center">
                                                <?= $availableMonths[$acumulado['mes']] ?? $acumulado['mes'] ?>
                                            </td>
                                            <td><?= htmlspecialchars($acumulado['tipo_acumulado'] ?? '') ?></td>
                                            <td>
                                                <span class="badge <?= $acumulado['tipo_concepto'] === 'ASIGNACION' ? 'badge-success' : 'badge-danger' ?>">
                                                    <?= $acumulado['tipo_concepto'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($acumulado['planilla_descripcion'] ?? 'N/A') ?>
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
                                        <th colspan="4" class="text-right">TOTALES:</th>
                                        <th class="text-right">
                                            <div class="text-success">+ <?= currency_symbol() ?><?= number_format($totalAsignaciones, 2) ?></div>
                                            <div class="text-danger">- <?= currency_symbol() ?><?= number_format($totalDeducciones, 2) ?></div>
                                            <hr style="margin: 5px 0;">
                                            <div class="text-info font-weight-bold"><?= currency_symbol() ?><?= number_format($totalAsignaciones - $totalDeducciones, 2) ?></div>
                                        </th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            No se encontraron registros de acumulados para este empleado en el período especificado.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                Seleccione un empleado y período para ver sus acumulados detallados.
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
            "order": [[0, "desc"]], // Ordenar por mes descendente
            "language": {
                DATATABLES_SPANISH
            }
        });
    }

    // Initialize Select2 for employee dropdown
    $('#empleado_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Buscar empleado...',
        allowClear: true
    });

    // Auto-submit form when employee changes (same as search button)
    $('#empleado_id').on('change', function() {
        if ($(this).val()) {
            // Submit form automatically when employee is selected
            $('form').submit();
        }
    });

    // Auto-submit when year filter changes (for better UX)
    $('#year, #tipo_acumulado').on('change', function() {
        if ($('#empleado_id').val()) {
            // Only auto-submit if employee is already selected
            $('form').submit();
        }
    });

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