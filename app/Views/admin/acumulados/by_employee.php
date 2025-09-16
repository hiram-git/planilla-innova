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
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="empleado_id">Empleado</label>
                                <select name="empleado_id" id="empleado_id" class="form-control select2">
                                    <option value="">-- Seleccionar Empleado --</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?= $employee['id'] ?>" 
                                                <?= ($selectedEmployee && $selectedEmployee['id'] == $employee['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? '')) ?> (<?= htmlspecialchars($employee['document_id'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="year">Año</label>
                                <select name="year" id="year" class="form-control">
                                    <?php foreach ($availableYears as $availableYear): ?>
                                        <option value="<?= $availableYear ?>" <?= $year == $availableYear ? 'selected' : '' ?>>
                                            <?= $availableYear ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search mr-1"></i>
                                        Buscar
                                    </button>
                                    <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/byEmployee') ?>" class="btn btn-secondary ml-2">
                                        <i class="fas fa-times mr-1"></i>
                                        Limpiar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selectedEmployee): ?>
            <!-- Información del Empleado -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user mr-2"></i>
                        Información del Empleado
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Nombre Completo:</strong><br>
                            <?= htmlspecialchars(($selectedEmployee['firstname'] ?? '') . ' ' . ($selectedEmployee['lastname'] ?? '')) ?>
                        </div>
                        <div class="col-md-2">
                            <strong>Cédula:</strong><br>
                            <?= htmlspecialchars($selectedEmployee['document_id'] ?? '') ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Posición:</strong><br>
                            <?= htmlspecialchars($selectedEmployee['position'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-2">
                            <strong>Estado:</strong><br>
                            <span class="badge badge-<?= ($selectedEmployee['active'] ?? true) ? 'success' : 'secondary' ?>">
                                <?= ($selectedEmployee['active'] ?? true) ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>
                        <div class="col-md-2">
                            <strong>Año Consultado:</strong><br>
                            <span class="badge badge-primary"><?= $year ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($acumulados)): ?>
                <?php 
                // Agrupar acumulados por tipo de concepto y calcular totales
                $acumuladosPorTipo = [];
                $totalIngresos = 0;
                $totalDeducciones = 0;
                
                foreach ($acumulados as $acumulado) {
                    $tipo = $acumulado['tipo_concepto'] ?? 'OTRO';
                    if (!isset($acumuladosPorTipo[$tipo])) {
                        $acumuladosPorTipo[$tipo] = [];
                    }
                    $acumuladosPorTipo[$tipo][] = $acumulado;
                    
                    // Calcular totales
                    if ($tipo === 'ASIGNACION') {
                        $totalIngresos += $acumulado['total_acumulado'] ?? 0;
                    } else {
                        $totalDeducciones += $acumulado['total_acumulado'] ?? 0;
                    }
                }
                ?>
                
                <!-- Resumen General -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie mr-2"></i>
                            Resumen de Acumulados <?= $year ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon">
                                        <i class="fas fa-plus-circle"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Ingresos</span>
                                        <span class="info-box-number">$<?= number_format($totalIngresos, 2) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="info-box bg-danger">
                                    <span class="info-box-icon">
                                        <i class="fas fa-minus-circle"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Deducciones</span>
                                        <span class="info-box-number">$<?= number_format($totalDeducciones, 2) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="info-box bg-primary">
                                    <span class="info-box-icon">
                                        <i class="fas fa-calculator"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Neto Acumulado</span>
                                        <span class="info-box-number">$<?= number_format($totalIngresos - $totalDeducciones, 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalle por Concepto -->
                <?php foreach ($acumuladosPorTipo as $tipo => $conceptos): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-<?= $tipo === 'ASIGNACION' ? 'plus-circle text-success' : 'minus-circle text-danger' ?> mr-2"></i>
                                <?= $tipo === 'ASIGNACION' ? 'Ingresos (Asignaciones)' : 'Deducciones' ?>
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-<?= $tipo === 'ASIGNACION' ? 'success' : 'danger' ?>">
                                    <?= count($conceptos) ?> concepto(s)
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Concepto</th>
                                            <th class="text-center">Planillas</th>
                                            <th class="text-center">Frecuencia</th>
                                            <th class="text-right">Total Acumulado</th>
                                            <th class="text-center">Última Actualización</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $subtotal = 0;
                                        foreach ($conceptos as $concepto): 
                                            $subtotal += $concepto['total_acumulado'] ?? 0;
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($concepto['concepto_descripcion'] ?? 'N/A') ?></strong>
                                                    <?php if (!empty($concepto['ultima_planilla'])): ?>
                                                        <br><small class="text-muted">
                                                            Última: <?= htmlspecialchars($concepto['ultima_planilla']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-info">
                                                        <?= $concepto['total_planillas'] ?? 0 ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-secondary">
                                                        <?= $concepto['frecuencia'] ?? 'N/A' ?>
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    <strong class="text-<?= $tipo === 'ASIGNACION' ? 'success' : 'danger' ?>">
                                                        $<?= number_format($concepto['total_acumulado'] ?? 0, 2) ?>
                                                    </strong>
                                                </td>
                                                <td class="text-center">
                                                    <small>
                                                        <?= ($concepto['fecha_ultimo_calculo'] ?? '') ? date('d/m/Y H:i', strtotime($concepto['fecha_ultimo_calculo'])) : 'N/A' ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light">
                                            <td colspan="3"><strong>SUBTOTAL <?= $tipo ?></strong></td>
                                            <td class="text-right">
                                                <strong class="h5 text-<?= $tipo === 'ASIGNACION' ? 'success' : 'danger' ?>">
                                                    $<?= number_format($subtotal, 2) ?>
                                                </strong>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Cálculos Especiales según Legislación Panameña -->
                <?php if (!empty($acumuladosPorTipo['ASIGNACION'])): ?>
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-gavel mr-2"></i>
                                Cálculos Legislación Panameña
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Calcular XIII Mes básico (asumiendo conceptos de sueldo)
                            $sueldosBase = 0;
                            foreach ($acumuladosPorTipo['ASIGNACION'] as $concepto) {
                                // Solo sueldos base para XIII Mes (generalmente conceptos 1, 2, 3)
                                if (in_array($concepto['concepto_id'] ?? 0, [1, 2, 3])) {
                                    $sueldosBase += $concepto['total_acumulado'] ?? 0;
                                }
                            }
                            $xiiiMes = $sueldosBase / 3; // XIII Mes = Salario anual ÷ 3
                            ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box bg-warning">
                                        <span class="info-box-icon">
                                            <i class="fas fa-gift"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">XIII Mes Teórico</span>
                                            <span class="info-box-number">$<?= number_format($xiiiMes, 2) ?></span>
                                            <span class="progress-description">
                                                Basado en sueldos acumulados: $<?= number_format($sueldosBase, 2) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-info">
                                        <span class="info-box-icon">
                                            <i class="fas fa-umbrella-beach"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Vacaciones Proporcionales</span>
                                            <span class="info-box-number">$<?= number_format($sueldosBase / 12, 2) ?></span>
                                            <span class="progress-description">
                                                Aproximado por mes trabajado
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Nota:</strong> Los cálculos mostrados son estimaciones basadas en los acumulados del año. 
                                Para cálculos exactos se debe considerar días no trabajados, ausencias y otras referencias según el Código de Trabajo de Panamá.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h5>No hay acumulados para este empleado en <?= $year ?></h5>
                        <p class="text-muted">
                            <strong><?= htmlspecialchars(($selectedEmployee['firstname'] ?? '') . ' ' . ($selectedEmployee['lastname'] ?? '')) ?></strong> no tiene registros de acumulados para el año <?= $year ?>.
                        </p>
                        <p class="text-muted">
                            <small>
                                Esto puede deberse a que el empleado no tuvo conceptos acumulables en las planillas procesadas 
                                o no se han cerrado planillas que generen acumulados para este período.
                            </small>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>Selecciona un empleado</h5>
                    <p class="text-muted">
                        Usa el filtro de arriba para seleccionar un empleado específico y ver sus acumulados.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Botones de Acción -->
        <div class="row">
            <div class="col-12">
                <a href="<?= \App\Core\UrlHelper::route('panel/acumulados') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Volver a Acumulados
                </a>
                <?php if ($selectedEmployee && !empty($acumulados)): ?>
                    <button type="button" class="btn btn-info ml-2" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i>
                        Imprimir
                    </button>
                    <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/export') ?>?empleado_id=<?= $selectedEmployee['id'] ?? '' ?>&year=<?= $year ?>" 
                       class="btn btn-success ml-2" target="_blank">
                        <i class="fas fa-download mr-1"></i>
                        Exportar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    // Inicializar Select2 para el selector de empleados
    $('#empleado_id').select2({
        placeholder: "-- Buscar empleado por nombre o cédula --",
        allowClear: true,
        width: '100%'
    });
});
</script>

<style>
@media print {
    .content-header, .card-header, .btn, .breadcrumb { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .table { font-size: 12px; }
}
</style>