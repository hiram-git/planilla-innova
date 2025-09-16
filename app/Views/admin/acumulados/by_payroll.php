<?php
$pageTitle = "Acumulados por Planilla - {$payroll['descripcion']}";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Acumulados por Planilla</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/payrolls') ?>">Planillas</a></li>
                    <li class="breadcrumb-item active">Acumulados</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Info de la Planilla -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calculator mr-2"></i>
                    Información de la Planilla
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Planilla:</strong><br>
                        <?= htmlspecialchars($payroll['descripcion']) ?>
                    </div>
                    <div class="col-md-2">
                        <strong>Estado:</strong><br>
                        <span class="badge badge-<?= $payroll['estado'] == 'CERRADA' ? 'success' : ($payroll['estado'] == 'PROCESADA' ? 'warning' : 'secondary') ?>">
                            <?= $payroll['estado'] ?>
                        </span>
                    </div>
                    <div class="col-md-2">
                        <strong>Fecha:</strong><br>
                        <?= date('d/m/Y', strtotime($payroll['fecha'])) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Período:</strong><br>
                        <?= date('d/m/Y', strtotime($payroll['fecha_desde'])) ?> - 
                        <?= date('d/m/Y', strtotime($payroll['fecha_hasta'])) ?>
                    </div>
                    <div class="col-md-2">
                        <strong>Empleados:</strong><br>
                        <span class="badge badge-info"><?= $totalEmpleados ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($acumulados)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                    <h5>No hay acumulados para esta planilla</h5>
                    <p class="text-muted">
                        <?php if ($payroll['estado'] == 'PENDIENTE'): ?>
                            La planilla debe estar procesada o cerrada para generar acumulados.
                        <?php else: ?>
                            Esta planilla no generó registros de acumulados.
                        <?php endif; ?>
                    </p>
                    <a href="<?= \App\Core\UrlHelper::route('panel/payrolls') ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Volver a Planillas
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Resumen por Tipo de Acumulado -->
            <?php
            $resumenTipos = [];
            foreach ($acumulados as $empleadoData) {
                foreach ($empleadoData['acumulados'] as $tipoId => $acumulado) {
                    if (!isset($resumenTipos[$tipoId])) {
                        $resumenTipos[$tipoId] = [
                            'descripcion' => $acumulado['tipo_descripcion'],
                            'codigo' => $acumulado['tipo_codigo'],
                            'total' => 0,
                            'empleados' => 0
                        ];
                    }
                    $resumenTipos[$tipoId]['total'] += $acumulado['total_acumulado'];
                    $resumenTipos[$tipoId]['empleados']++;
                }
            }
            ?>

            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie mr-2"></i>
                        Resumen por Tipo de Acumulado
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($resumenTipos as $tipoId => $resumen): ?>
                            <div class="col-md-4 col-sm-6 mb-3">
                                <div class="info-box bg-gradient-primary">
                                    <span class="info-box-icon">
                                        <i class="fas fa-coins"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text"><?= htmlspecialchars($resumen['descripcion']) ?></span>
                                        <span class="info-box-number"><?= currency_symbol() ?><?= number_format($resumen['total'], 2) ?></span>
                                        <span class="progress-description">
                                            <?= $resumen['empleados'] ?> empleado(s) afectado(s)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Detalle por Empleado -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users mr-2"></i>
                        Detalle por Empleado
                    </h3>
                </div>
                <div class="card-body">
                    <?php foreach ($acumulados as $empleadoId => $empleadoData): ?>
                        <div class="card card-outline card-secondary mb-3">
                            <div class="card-header">
                                <h4 class="card-title mb-0">
                                    <i class="fas fa-user mr-2"></i>
                                    <?= htmlspecialchars($empleadoData['empleado']) ?>
                                    <small class="text-muted">(<?= htmlspecialchars($empleadoData['document_id']) ?>)</small>
                                </h4>
                            </div>
                            <div class="card-body">
                                <?php foreach ($empleadoData['acumulados'] as $tipoId => $acumulado): ?>
                                    <div class="mb-4">
                                        <h5 class="text-primary">
                                            <i class="fas fa-tag mr-1"></i>
                                            <?= htmlspecialchars($acumulado['tipo_descripcion']) ?>
                                            <span class="badge badge-primary ml-2">
                                                <?= currency_symbol() ?><?= number_format($acumulado['total_acumulado'], 2) ?>
                                            </span>
                                        </h5>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Concepto</th>
                                                        <th class="text-right">Monto Concepto</th>
                                                        <th class="text-center">Referencia</th>
                                                        <th class="text-right">Monto Acumulado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($acumulado['conceptos'] as $concepto): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($concepto['concepto_descripcion']) ?></td>
                                                            <td class="text-right"><?= currency_symbol() ?><?= number_format($concepto['monto_concepto'], 2) ?></td>
                                                            <td class="text-center"><?= number_format($concepto['factor_acumulacion'], 4) ?></td>
                                                            <td class="text-right font-weight-bold"><?= currency_symbol() ?><?= number_format($concepto['monto_acumulado'], 2) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Botones de Acción -->
        <div class="row">
            <div class="col-12">
                <a href="<?= \App\Core\UrlHelper::route('panel/payrolls') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Volver a Planillas
                </a>
                <?php if (!empty($acumulados)): ?>
                    <button type="button" class="btn btn-info ml-2" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i>
                        Imprimir
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
@media print {
    .content-header, .card-header, .btn, .breadcrumb { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .table { font-size: 12px; }
}
</style>