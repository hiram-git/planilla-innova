<?php
$pageTitle = "Detalle de Planilla de Liquidación";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-file-invoice mr-2"></i>
                    Detalle de Planilla #<?= $payroll['id'] ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <div class="row">
                    <div class="col-12 mb-2">
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/payrolls') ?>"
                           class="btn btn-secondary btn-sm float-right ml-2">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver a Planillas
                        </a>
                        <a href="<?= \App\Core\UrlHelper::route('panel/reports/payroll/' . $payroll['id']) ?>"
                           class="btn btn-success btn-sm float-right"
                           target="_blank">
                            <i class="fas fa-file-pdf mr-2"></i>
                            Generar PDF
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

        <!-- Detalle por Empleado -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list-ul mr-2"></i>
                    Detalle de Conceptos por Empleado
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" onclick="exportToCSV()">
                        <i class="fas fa-download mr-1"></i> Exportar CSV
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="payrollDetailTable">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Cédula</th>
                                <th>Concepto</th>
                                <th>Descripción</th>
                                <th>Tipo</th>
                                <th class="text-right">Monto</th>
                                <th>Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $current_employee = '';
                            $employee_total_asignaciones = 0;
                            $employee_total_deducciones = 0;

                            foreach ($details as $index => $detail):
                                $employee_name = $detail['firstname'] . ' ' . $detail['lastname'];
                                $is_new_employee = ($current_employee !== $employee_name);

                                if ($is_new_employee && $current_employee !== '') {
                                    // Mostrar totales del empleado anterior
                                    $employee_neto = $employee_total_asignaciones - $employee_total_deducciones;
                            ?>
                                <tr class="bg-light font-weight-bold border-top-2">
                                    <td colspan="5" class="text-right">TOTAL <?= $current_employee ?>:</td>
                                    <td class="text-right">
                                        <div class="text-success">+ <?= currency_symbol() ?><?= number_format($employee_total_asignaciones, 2) ?></div>
                                        <div class="text-danger">- <?= currency_symbol() ?><?= number_format($employee_total_deducciones, 2) ?></div>
                                        <hr style="margin: 2px 0;">
                                        <div class="text-primary"><?= currency_symbol() ?><?= number_format($employee_neto, 2) ?></div>
                                    </td>
                                    <td></td>
                                </tr>
                            <?php
                                    // Resetear totales
                                    $employee_total_asignaciones = 0;
                                    $employee_total_deducciones = 0;
                                }

                                $current_employee = $employee_name;

                                // Acumular totales del empleado
                                if ($detail['tipo'] === 'A') {
                                    $employee_total_asignaciones += $detail['monto'];
                                } else {
                                    $employee_total_deducciones += $detail['monto'];
                                }
                            ?>
                                <tr>
                                    <td><?= $is_new_employee ? '<strong>' . htmlspecialchars($employee_name) . '</strong>' : '' ?></td>
                                    <td><?= $is_new_employee ? htmlspecialchars($detail['document_id'] ?? $detail['cedula'] ?? 'N/A') : '' ?></td>
                                    <td><strong><?= htmlspecialchars($detail['concepto']) ?></strong></td>
                                    <td><?= htmlspecialchars($detail['concepto_descripcion']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $detail['tipo'] === 'A' ? 'success' : 'danger' ?>">
                                            <?= $detail['tipo'] === 'A' ? 'ASIGNACIÓN' : 'DEDUCCIÓN' ?>
                                        </span>
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        <span class="text-<?= $detail['tipo'] === 'A' ? 'success' : 'danger' ?>">
                                            <?= $detail['tipo'] === 'A' ? '+' : '-' ?><?= currency_symbol() ?><?= number_format($detail['monto'], 2) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($detail['referencia'] ?? '') ?></td>
                                </tr>
                            <?php
                            endforeach;

                            // Mostrar totales del último empleado
                            if ($current_employee !== '') {
                                $employee_neto = $employee_total_asignaciones - $employee_total_deducciones;
                            ?>
                                <tr class="bg-light font-weight-bold border-top-2">
                                    <td colspan="5" class="text-right">TOTAL <?= $current_employee ?>:</td>
                                    <td class="text-right">
                                        <div class="text-success">+ <?= currency_symbol() ?><?= number_format($employee_total_asignaciones, 2) ?></div>
                                        <div class="text-danger">- <?= currency_symbol() ?><?= number_format($employee_total_deducciones, 2) ?></div>
                                        <hr style="margin: 2px 0;">
                                        <div class="text-primary"><?= currency_symbol() ?><?= number_format($employee_neto, 2) ?></div>
                                    </td>
                                    <td></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-primary text-white font-weight-bold">
                                <th colspan="5" class="text-right">TOTALES GENERALES:</th>
                                <th class="text-right">
                                    <div>+ <?= currency_symbol() ?><?= number_format($totals['total_asignaciones'], 2) ?></div>
                                    <div>- <?= currency_symbol() ?><?= number_format($totals['total_deducciones'], 2) ?></div>
                                    <hr style="margin: 5px 0; border-color: rgba(255,255,255,0.3);">
                                    <div><?= currency_symbol() ?><?= number_format($totals['total_neto'], 2) ?></div>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($("#payrollDetailTable").length) {
        $("#payrollDetailTable").DataTable({
            "responsive": true,
            "pageLength": 50,
            "paging": false,
            "searching": true,
            "info": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
            }
        });
    }

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

function exportToCSV() {
    const payrollId = '<?= $payroll['id'] ?>';
    const description = '<?= addslashes($payroll['descripcion']) ?>';

    // Crear CSV con los datos de la planilla
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Planilla de Liquidación: " + description + "\n";
    csvContent += "Periodo: <?= date('d/m/Y', strtotime($payroll['fecha_desde'])) ?> - <?= date('d/m/Y', strtotime($payroll['fecha_hasta'])) ?>\n\n";
    csvContent += "Empleado,Cédula,Concepto,Descripción,Tipo,Monto,Referencia\n";

    <?php foreach ($details as $detail): ?>
    csvContent += "<?= addslashes($detail['firstname'] . ' ' . $detail['lastname']) ?>," +
                  "<?= addslashes($detail['document_id'] ?? $detail['cedula'] ?? 'N/A') ?>," +
                  "<?= addslashes($detail['concepto']) ?>," +
                  "<?= addslashes($detail['concepto_descripcion']) ?>," +
                  "<?= $detail['tipo'] === 'A' ? 'ASIGNACIÓN' : 'DEDUCCIÓN' ?>," +
                  "<?= $detail['monto'] ?>," +
                  "<?= addslashes($detail['referencia'] ?? '') ?>\n";
    <?php endforeach; ?>

    csvContent += "\nTotal Asignaciones,<?= $totals['total_asignaciones'] ?>\n";
    csvContent += "Total Deducciones,<?= $totals['total_deducciones'] ?>\n";
    csvContent += "Total Neto,<?= $totals['total_neto'] ?>\n";

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `planilla_liquidacion_${payrollId}_<?= date('Y-m-d', strtotime($payroll['fecha'])) ?>.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>