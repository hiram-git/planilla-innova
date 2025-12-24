<?php
$pageTitle = "Detalle de Planilla de Liquidación";

// Obtener información del empleado liquidado de los detalles
$employee_info = null;
if (!empty($details)) {
    $first_detail = $details[0];
    $employee_info = [
        'name' => $first_detail['firstname'] . ' ' . $first_detail['lastname'],
        'cedula' => $first_detail['document_id'] ?? $first_detail['cedula'] ?? 'N/A'
    ];
}

$accumulatedConceptCodes = ['LIQ001', 'LIQ002', 'LIQ005', 'LIQ007'];
$liquidationAccumulations = $liquidationAccumulations ?? [];

// Scripts específicos de esta página - se cargarán después de jQuery
ob_start();
?>
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

function exportToCSV() {
    const payrollId = '<?= $payroll['id'] ?>';
    const description = '<?= addslashes($payroll['descripcion']) ?>';
    const employeeName = '<?= $employee_info ? addslashes($employee_info['name']) : 'N/A' ?>';

    // Crear CSV con los datos de la liquidación
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Planilla de Liquidación: " + description + "\n";
    csvContent += "Empleado: " + employeeName + "\n";
    csvContent += "Periodo: <?= date('d/m/Y', strtotime($payroll['fecha_desde'])) ?> - <?= date('d/m/Y', strtotime($payroll['fecha_hasta'])) ?>\n\n";
    csvContent += "Concepto,Descripción,Tipo,Monto\n";

    <?php foreach ($details as $detail): ?>
    csvContent += "<?= addslashes($detail['concepto']) ?>," +
                  "<?= addslashes($detail['concepto_descripcion']) ?>," +
                  "<?= $detail['tipo'] === 'A' ? 'ASIGNACIÓN' : 'DEDUCCIÓN' ?>," +
                  "<?= $detail['monto'] ?>\n";
    <?php endforeach; ?>

    csvContent += "\n--- RESUMEN ---\n";
    csvContent += "Total Asignaciones,<?= $totals['total_asignaciones'] ?>\n";
    csvContent += "Total Deducciones,<?= $totals['total_deducciones'] ?>\n";
    csvContent += "Total Neto a Pagar,<?= $totals['total_neto'] ?>\n";

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `liquidacion_${employeeName.replace(/\s+/g, '_')}_${payrollId}_<?= date('Y-m-d', strtotime($payroll['fecha'])) ?>.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
<?php
$scripts = ob_get_clean();
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-8">
                <h1 class="m-0">
                    <i class="fas fa-file-invoice mr-2"></i>
                    Planilla de Liquidación #<?= $payroll['id'] ?>
                </h1>
                <?php if ($employee_info): ?>
                <p class="text-muted mt-1 mb-0">
                    <i class="fas fa-user mr-1"></i>
                    <strong><?= htmlspecialchars($employee_info['name']) ?></strong>
                    <span class="ml-2">
                        <i class="fas fa-id-card mr-1"></i>
                        <?= htmlspecialchars($employee_info['cedula']) ?>
                    </span>
                </p>
                <?php endif; ?>
            </div>
            <div class="col-sm-4">
                <div class="row">
                    <div class="col-12 mb-2">
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/payrolls') ?>"
                           class="btn btn-secondary btn-sm float-right ml-2">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver a Planillas
                        </a>
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/payroll-excel/' . $payroll['id']) ?>"
                           class="btn btn-info btn-sm float-right ml-2"
                           title="Descargar Excel">
                            <i class="fas fa-file-excel mr-2"></i>
                            Excel
                        </a>
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/payroll-pdf/' . $payroll['id']) ?>"
                           class="btn btn-success btn-sm float-right"
                           target="_blank"
                           title="Generar PDF">
                            <i class="fas fa-file-pdf mr-2"></i>
                            PDF
                        </a>
                    </div>
                    <div class="col-12">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/liquidation') ?>">Liquidaciones</a></li>
                            <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/liquidation/payrolls') ?>">Planillas</a></li>
                            <li class="breadcrumb-item active">Detalle #<?= $payroll['id'] ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <!-- Información de la Planilla -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>
                    Información de la Planilla
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Descripción:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($payroll['descripcion']) ?></dd>

                            <dt class="col-sm-5">Tipo de Planilla:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($payroll['tipo_planilla_nombre'] ?? 'N/A') ?></dd>

                            <dt class="col-sm-5">Frecuencia:</dt>
                            <dd class="col-sm-7">
                                <span class="badge badge-info">
                                    <?= htmlspecialchars($payroll['frecuencia_nombre'] ?? 'Liquidación') ?>
                                </span>
                            </dd>

                            <dt class="col-sm-5">Estado:</dt>
                            <dd class="col-sm-7">
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
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Fecha Planilla:</dt>
                            <dd class="col-sm-7"><?= date('d/m/Y', strtotime($payroll['fecha'])) ?></dd>

                            <dt class="col-sm-5">Periodo Desde:</dt>
                            <dd class="col-sm-7"><?= date('d/m/Y', strtotime($payroll['fecha_desde'])) ?></dd>

                            <dt class="col-sm-5">Periodo Hasta:</dt>
                            <dd class="col-sm-7"><?= date('d/m/Y', strtotime($payroll['fecha_hasta'])) ?></dd>

                            <dt class="col-sm-5">Fecha Creación:</dt>
                            <dd class="col-sm-7"><?= date('d/m/Y H:i:s', strtotime($payroll['created_at'])) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen de Totales -->
        <div class="row">
            <div class="col-lg-4 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= currency_symbol() ?><?= number_format($totals['total_asignaciones'], 2) ?></h3>
                        <p>Total Asignaciones</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-plus"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= currency_symbol() ?><?= number_format($totals['total_deducciones'], 2) ?></h3>
                        <p>Total Deducciones</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-minus"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= currency_symbol() ?><?= number_format($totals['total_neto'], 2) ?></h3>
                        <p>Total Neto</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conceptos de Liquidación -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle"></i> Asignaciones
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th>Descripción</th>
                                        <th class="text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $asignaciones = array_filter($details, fn($d) => $d['tipo'] === 'A');
                                    if (!empty($asignaciones)):
                                    ?>
                                        <?php foreach ($asignaciones as $asignacion): ?>
                                            <?php
                                            $showAccumulated = in_array($asignacion['concepto'], $accumulatedConceptCodes, true);
                                            if ($showAccumulated):
                                                $accumulatedData = $liquidationAccumulations[$asignacion['concepto']] ?? null;
                                            ?>
                                                <tr class="bg-light">
                                                    <td colspan="3">
                                                        <table class="table table-sm table-bordered mb-0">
                                                            <thead class="thead-light">
                                                                <tr>
                                                                    <th width="40%">Mes</th>
                                                                    <th width="60%" class="text-right">Acumulado</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (!empty($accumulatedData['months'])): ?>
                                                                    <?php foreach ($accumulatedData['months'] as $month): ?>
                                                                        <tr>
                                                                            <td><?= htmlspecialchars($month['label']) ?></td>
                                                                            <td class="text-right"><?= currency_symbol() ?><?= number_format($month['amount'], 2) ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                    <tr class="font-weight-bold">
                                                                        <td>Total acumulado</td>
                                                                        <td class="text-right"><?= currency_symbol() ?><?= number_format($accumulatedData['total'], 2) ?></td>
                                                                    </tr>
                                                                <?php else: ?>
                                                                    <tr>
                                                                        <td colspan="2" class="text-center text-muted">Sin acumulados</td>
                                                                    </tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($asignacion['concepto']) ?></strong>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($asignacion['concepto_descripcion']) ?>
                                                </td>
                                                <td class="text-right">
                                                    <span class="text-success font-weight-bold"><?= currency_symbol() ?><?= number_format($asignacion['monto'], 2) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> No hay asignaciones registradas
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold bg-light">
                                        <td colspan="2">TOTAL ASIGNACIONES</td>
                                        <td class="text-right text-success"><?= currency_symbol() ?><?= number_format($totals['total_asignaciones'], 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger">
                        <h3 class="card-title">
                            <i class="fas fa-minus-circle"></i> Deducciones
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th>Descripción</th>
                                        <th class="text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $deducciones = array_filter($details, fn($d) => $d['tipo'] === 'D');
                                    if (!empty($deducciones)):
                                    ?>
                                        <?php foreach ($deducciones as $deduccion): ?>
                                            <?php
                                            $showAccumulated = in_array($deduccion['concepto'], $accumulatedConceptCodes, true);
                                            if ($showAccumulated):
                                                $accumulatedData = $liquidationAccumulations[$deduccion['concepto']] ?? null;
                                            ?>
                                                <tr class="bg-light">
                                                    <td colspan="3">
                                                        <table class="table table-sm table-bordered mb-0">
                                                            <thead class="thead-light">
                                                                <tr>
                                                                    <th width="40%">Mes</th>
                                                                    <th width="60%" class="text-right">Acumulado</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (!empty($accumulatedData['months'])): ?>
                                                                    <?php foreach ($accumulatedData['months'] as $month): ?>
                                                                        <tr>
                                                                            <td><?= htmlspecialchars($month['label']) ?></td>
                                                                            <td class="text-right"><?= currency_symbol() ?><?= number_format($month['amount'], 2) ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                    <tr class="font-weight-bold">
                                                                        <td>Total acumulado</td>
                                                                        <td class="text-right"><?= currency_symbol() ?><?= number_format($accumulatedData['total'], 2) ?></td>
                                                                    </tr>
                                                                <?php else: ?>
                                                                    <tr>
                                                                        <td colspan="2" class="text-center text-muted">Sin acumulados</td>
                                                                    </tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($deduccion['concepto']) ?></strong>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($deduccion['concepto_descripcion']) ?>
                                                </td>
                                                <td class="text-right">
                                                    <span class="text-danger font-weight-bold"><?= currency_symbol() ?><?= number_format($deduccion['monto'], 2) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> No hay deducciones registradas
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold bg-light">
                                        <td colspan="2">TOTAL DEDUCCIONES</td>
                                        <td class="text-right text-danger"><?= currency_symbol() ?><?= number_format($totals['total_deducciones'], 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen Final de Liquidación -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-receipt"></i> Resumen Final de Liquidación
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-success btn-sm" onclick="exportToCSV()">
                                <i class="fas fa-download mr-1"></i> Exportar CSV
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Total Asignaciones:</strong></td>
                                            <td class="text-right text-success"><strong><?= currency_symbol() ?><?= number_format($totals['total_asignaciones'], 2) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Deducciones:</strong></td>
                                            <td class="text-right text-danger"><strong>- <?= currency_symbol() ?><?= number_format($totals['total_deducciones'], 2) ?></strong></td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="h5"><strong>Total Neto a Pagar:</strong></td>
                                            <td class="text-right h4"><strong class="text-primary"><?= currency_symbol() ?><?= number_format($totals['total_neto'], 2) ?></strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <!-- Información Legal -->
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-balance-scale"></i> Marco Legal:</h6>
                                    <p class="mb-1"><small>
                                        <strong>Código de Trabajo de Panamá</strong><br>
                                        • Art. 225: Prima de Antigüedad<br>
                                        • Art. 224: Indemnización por Despido<br>
                                        • Art. 213: Preaviso<br>
                                        • Art. 162: XIII Mes proporcional
                                    </small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
