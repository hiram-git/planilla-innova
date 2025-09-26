<?php
$pageTitle = "Cálculo de Liquidación - " . htmlspecialchars($termination['firstname'] . ' ' . $termination['lastname']);
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-calculator mr-2"></i>
                    Cálculo de Liquidación
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/liquidation') ?>">Liquidaciones</a></li>
                    <li class="breadcrumb-item active">Cálculo</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <!-- Información de la Terminación -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>
                    Información de la Terminación
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Empleado:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($termination['firstname'] . ' ' . $termination['lastname']) ?></dd>

                            <dt class="col-sm-4">Ficha:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($termination['employee_id']) ?></dd>

                            <dt class="col-sm-4">Cédula:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($termination['document_id'] ?? 'N/A') ?></dd>

                            <dt class="col-sm-4">Cargo:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($termination['position_name'] ?? 'N/A') ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Fecha Ingreso:</dt>
                            <dd class="col-sm-7"><?= date('d/m/Y', strtotime($termination['fecha_ingreso'])) ?></dd>

                            <dt class="col-sm-5">Fecha Terminación:</dt>
                            <dd class="col-sm-7"><?= date('d/m/Y', strtotime($termination['termination_date'])) ?></dd>

                            <dt class="col-sm-5">Tipo Terminación:</dt>
                            <dd class="col-sm-7">
                                <span class="badge badge-<?= $termination['termination_type'] === 'DESPIDO_SIN_CAUSA' ? 'danger' : 'warning' ?>">
                                    <?= str_replace('_', ' ', $termination['termination_type']) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-5">Período Trabajado:</dt>
                            <dd class="col-sm-7">
                                <strong>
                                    <?php
                                    // Calcular período detallado
                                    $fecha_ingreso = new DateTime($termination['fecha_ingreso']);
                                    $fecha_termination = new DateTime($termination['termination_date']);
                                    $periodo_interval = $fecha_ingreso->diff($fecha_termination);
                                    ?>
                                    <?= $periodo_interval->y ?> años, <?= $periodo_interval->m ?> meses, <?= $periodo_interval->d ?> días
                                </strong>
                                <br><small class="text-muted"><?= number_format($termination['years_worked'], 2) ?> años totales</small>
                            </dd>
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
                    <div class="small-box-footer">
                        Beneficios del empleado
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
                    <div class="small-box-footer">
                        Descuentos aplicables
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= currency_symbol() ?><?= number_format($totals['total_neto'], 2) ?></h3>
                        <p>Total Neto a Pagar</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="small-box-footer">
                        Monto final de liquidación
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalle de Cálculos -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list-ul mr-2"></i>
                    Detalle de Cálculos
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" onclick="exportToCSV()">
                        <i class="fas fa-download mr-1"></i> Exportar CSV
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="calculationsTable">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th>Descripción</th>
                                <th>Fórmula</th>
                                <th>Tipo</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalAsignaciones = 0;
                            $totalDeducciones = 0;
                            foreach ($calculations as $calc):
                                if ($calc['type'] === 'ASIGNACION' || $calc['type'] === 'A') {
                                    $totalAsignaciones += $calc['amount'];
                                } elseif ($calc['type'] === 'DEDUCCION' || $calc['type'] === 'D') {
                                    $totalDeducciones += $calc['amount'];
                                }
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($calc['concept_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($calc['concept_description']) ?></td>
                                    <td>
                                        <code class="small"><?= htmlspecialchars($calc['formula']) ?></code>
                                    </td>
                                    <td>
                                        <?php
                                        $isAsignacion = ($calc['type'] === 'ASIGNACION' || $calc['type'] === 'A');
                                        $displayType = $isAsignacion ? 'ASIGNACION' : 'DEDUCCION';
                                        ?>
                                        <span class="badge badge-<?= $isAsignacion ? 'success' : 'danger' ?>">
                                            <?= $displayType ?>
                                        </span>
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        <?= currency_symbol() ?><?= number_format($calc['amount'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-light font-weight-bold">
                                <th colspan="4" class="text-right">TOTALES:</th>
                                <th class="text-right">
                                    <div class="text-success">+ <?= currency_symbol() ?><?= number_format($totalAsignaciones, 2) ?></div>
                                    <div class="text-danger">- <?= currency_symbol() ?><?= number_format($totalDeducciones, 2) ?></div>
                                    <hr style="margin: 5px 0;">
                                    <div class="text-primary"><?= currency_symbol() ?><?= number_format($totalAsignaciones - $totalDeducciones, 2) ?></div>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Información Legal -->
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-balance-scale mr-2"></i>
                    Marco Legal - República de Panamá
                </h3>
            </div>
            <div class="card-body">
                <p><strong>Código de Trabajo de Panamá - Artículos aplicables:</strong></p>
                <ul>
                    <li><strong>Art. 225:</strong> Prima de Antigüedad - Una semana de salario por cada año trabajado</li>
                    <li><strong>Art. 224:</strong> Indemnización por Despido:
                        <ul>
                            <li>Primeros 10 años: 3.4 semanas por año</li>
                            <li>Años adicionales: 1 semana por año</li>
                        </ul>
                    </li>
                    <li><strong>Art. 213:</strong> Preaviso - 30 días calendario</li>
                    <li><strong>Art. 68:</strong> Vacaciones proporcionales no disfrutadas</li>
                    <li><strong>Art. 162:</strong> Décimo Tercer Mes proporcional</li>
                </ul>
                <div class="alert alert-info mt-3">
                    <strong>Nota:</strong> Estos cálculos están basados en la legislación laboral vigente de Panamá.
                    Se recomienda verificar con el departamento legal antes de procesar el pago.
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="card">
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-6">
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Volver a Lista
                        </a>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/preview/' . $termination['id']) ?>"
                           class="btn btn-info mr-2">
                            <i class="fas fa-eye mr-1"></i> Vista Previa
                        </a>
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/generatePayroll/' . $termination['id']) ?>"
                           class="btn btn-success">
                            <i class="fas fa-file-invoice mr-1"></i> Generar Planilla
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($("#calculationsTable").length) {
        $("#calculationsTable").DataTable({
            "responsive": true,
            "pageLength": 25,
            "paging": false,
            "searching": false,
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
    const employeeName = '<?= htmlspecialchars($termination['firstname'] . ' ' . $termination['lastname']) ?>';
    const terminationDate = '<?= date('Y-m-d', strtotime($termination['termination_date'])) ?>';

    // Crear CSV con los datos de la liquidación
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Liquidación de: " + employeeName + "\n";
    csvContent += "Fecha de Terminación: " + terminationDate + "\n\n";
    csvContent += "Concepto,Descripción,Tipo,Monto\n";

    <?php foreach ($calculations as $calc): ?>
    csvContent += "<?= addslashes($calc['concept_code']) ?>," +
                  "<?= addslashes($calc['concept_description']) ?>," +
                  "<?= $calc['type'] ?>," +
                  "<?= $calc['amount'] ?>\n";
    <?php endforeach; ?>

    csvContent += "\nTotal Asignaciones,<?= $totals['total_asignaciones'] ?>\n";
    csvContent += "Total Deducciones,<?= $totals['total_deducciones'] ?>\n";
    csvContent += "Total Neto,<?= $totals['total_neto'] ?>\n";

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `liquidacion_${employeeName.replace(/\s+/g, '_')}_${terminationDate}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>