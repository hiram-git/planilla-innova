<?php
$pageTitle = "Acumulados por Empleados - Desglose por Conceptos";
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Acumulados por Empleados</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/acumulados') ?>">Acumulados</a></li>
                    <li class="breadcrumb-item active">Por Empleados</li>
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
                <form method="GET" action="<?= \App\Core\UrlHelper::route('panel/acumulados/allEmployees') ?>">
                    <div class="row">
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
                                <label for="concepto_id">Concepto (Opcional)</label>
                                <select name="concepto_id" id="concepto_id" class="form-control">
                                    <option value="">-- Todos los Conceptos --</option>
                                    <?php foreach ($conceptos as $concepto): ?>
                                        <option value="<?= $concepto['id'] ?>" 
                                                <?= ($selectedConcepto == $concepto['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($concepto['descripcion']) ?> 
                                            (<?= $concepto['tipo_concepto'] == 'A' ? 'Ingreso' : 'Deducción' ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="tipo_concepto">Tipo</label>
                                <select name="tipo_concepto" id="tipo_concepto" class="form-control">
                                    <option value="">-- Todos --</option>
                                    <option value="ASIGNACION" <?= $tipoConcepto == 'ASIGNACION' ? 'selected' : '' ?>>
                                        Ingresos (Asignaciones)
                                    </option>
                                    <option value="DEDUCCION" <?= $tipoConcepto == 'DEDUCCION' ? 'selected' : '' ?>>
                                        Deducciones
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search mr-1"></i>
                                        Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($acumulados)): ?>
            <!-- Resumen estadísticas -->
            <?php 
            $totalEmpleados = count($acumulados);
            $totalIngresos = 0;
            $totalDeducciones = 0;
            $conceptosUnicos = [];
            
            foreach ($acumulados as $empleadoData) {
                foreach ($empleadoData['conceptos'] as $concepto) {
                    $conceptosUnicos[$concepto['concepto_id']] = $concepto['concepto_descripcion'];
                    if ($concepto['tipo_concepto'] == 'ASIGNACION') {
                        $totalIngresos += $concepto['total_acumulado'];
                    } else {
                        $totalDeducciones += $concepto['total_acumulado'];
                    }
                }
            }
            ?>
            
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Resumen General - <?= $year ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="info-box bg-primary">
                                <span class="info-box-icon">
                                    <i class="fas fa-users"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Empleados</span>
                                    <span class="info-box-number"><?= $totalEmpleados ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
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
                        <div class="col-md-3 text-center">
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
                        <div class="col-md-3 text-center">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon">
                                    <i class="fas fa-tags"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Conceptos Únicos</span>
                                    <span class="info-box-number"><?= count($conceptosUnicos) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla principal de acumulados -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-table mr-2"></i>
                        Acumulados por Empleado - Desglose por Conceptos
                    </h3>
                    <div class="card-tools">
                        <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/export') ?>?year=<?= $year ?>&tipo_concepto=<?= urlencode($tipoConcepto) ?>&concepto_id=<?= $selectedConcepto ?>" 
                           class="btn btn-success btn-sm" target="_blank">
                            <i class="fas fa-download mr-1"></i>
                            Exportar CSV
                        </a>
                        <button type="button" class="btn btn-info btn-sm" onclick="window.print()">
                            <i class="fas fa-print mr-1"></i>
                            Imprimir
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="acumuladosTable" class="table table-bordered table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width: 80px;">Cédula</th>
                                    <th style="width: 200px;">Empleado</th>
                                    <th style="width: 250px;">Concepto</th>
                                    <th style="width: 100px;" class="text-center">Tipo</th>
                                    <th style="width: 80px;" class="text-center">Planillas</th>
                                    <th style="width: 100px;" class="text-center">Frecuencia</th>
                                    <th style="width: 120px;" class="text-right">Total Acumulado</th>
                                    <th style="width: 150px;" class="text-center">Última Actualización</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($acumulados as $empleadoData): ?>
                                    <?php 
                                    $empleado = $empleadoData['empleado'];
                                    $conceptos = $empleadoData['conceptos'];
                                    $totalConceptosEmpleado = count($conceptos);
                                    ?>
                                    <?php foreach ($conceptos as $index => $concepto): ?>
                                        <tr>
                                            <?php if ($index === 0): // Solo mostrar datos del empleado en la primera fila ?>
                                                <td rowspan="<?= $totalConceptosEmpleado ?>" class="align-middle bg-light">
                                                    <strong><?= htmlspecialchars($empleado['document_id']) ?></strong>
                                                </td>
                                                <td rowspan="<?= $totalConceptosEmpleado ?>" class="align-middle bg-light">
                                                    <strong><?= htmlspecialchars($empleado['nombre_completo']) ?></strong>
                                                    <?php if (!empty($empleado['position'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($empleado['position']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            
                                            <td>
                                                <strong><?= htmlspecialchars($concepto['concepto_descripcion']) ?></strong>
                                            </td>
                                            
                                            <td class="text-center">
                                                <span class="badge badge-<?= $concepto['tipo_concepto'] == 'ASIGNACION' ? 'success' : 'danger' ?>">
                                                    <?= $concepto['tipo_concepto'] == 'ASIGNACION' ? 'Ingreso' : 'Deducción' ?>
                                                </span>
                                            </td>
                                            
                                            <td class="text-center">
                                                <span class="badge badge-info">
                                                    <?= $concepto['total_planillas'] ?>
                                                </span>
                                            </td>
                                            
                                            <td class="text-center">
                                                <span class="badge badge-secondary">
                                                    <?= $concepto['frecuencia'] ?>
                                                </span>
                                            </td>
                                            
                                            <td class="text-right">
                                                <strong class="text-<?= $concepto['tipo_concepto'] == 'ASIGNACION' ? 'success' : 'danger' ?>">
                                                    $<?= number_format($concepto['total_acumulado'], 2) ?>
                                                </strong>
                                            </td>
                                            
                                            <td class="text-center">
                                                <small>
                                                    <?= date('d/m/Y H:i', strtotime($concepto['fecha_ultimo_calculo'])) ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Fila de totales por empleado -->
                                    <?php 
                                    $totalEmpleadoIngresos = 0;
                                    $totalEmpleadoDeducciones = 0;
                                    foreach ($conceptos as $concepto) {
                                        if ($concepto['tipo_concepto'] == 'ASIGNACION') {
                                            $totalEmpleadoIngresos += $concepto['total_acumulado'];
                                        } else {
                                            $totalEmpleadoDeducciones += $concepto['total_acumulado'];
                                        }
                                    }
                                    ?>
                                    <tr class="bg-secondary text-white">
                                        <td colspan="6" class="text-right">
                                            <strong>TOTALES <?= htmlspecialchars($empleado['nombre_completo']) ?>:</strong>
                                        </td>
                                        <td class="text-right">
                                            <strong>
                                                <span class="text-success">+$<?= number_format($totalEmpleadoIngresos, 2) ?></span>
                                                <span class="text-danger">-$<?= number_format($totalEmpleadoDeducciones, 2) ?></span>
                                                <br>
                                                <span class="text-warning">=$<?= number_format($totalEmpleadoIngresos - $totalEmpleadoDeducciones, 2) ?></span>
                                            </strong>
                                        </td>
                                        <td></td>
                                    </tr>
                                    
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>No se encontraron acumulados</h5>
                    <p class="text-muted">
                        No hay datos de acumulados para los filtros seleccionados en el año <?= $year ?>.
                    </p>
                    <p class="text-muted">
                        <small>
                            Asegúrate de que hay planillas procesadas y cerradas en este período.
                        </small>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Botones de acción -->
        <div class="row">
            <div class="col-12">
                <a href="<?= \App\Core\UrlHelper::route('panel/acumulados') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Volver a Acumulados
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Incluir DataTables para mejor funcionalidad -->
<link rel="stylesheet" href="<?= url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css', false) ?>">

<script src="<?= url('plugins/datatables/jquery.dataTables.min.js', false) ?>"></script>
<script src="<?= url('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js', false) ?>"></script>

<script>
$(document).ready(function() {
    // Configurar DataTable
    $('#acumuladosTable').DataTable({
        "language": {
            "emptyTable": "No hay datos disponibles en la tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
            "infoFiltered": "(filtrado de _MAX_ entradas totales)",
            "lengthMenu": "Mostrar _MENU_ entradas",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        "pageLength": 50,
        "order": [[1, "asc"], [2, "asc"]], // Ordenar por empleado, luego por concepto
        "columnDefs": [
            {
                "targets": [3, 4, 5, 7], // Columnas tipo, planillas, frecuencia, fecha
                "orderable": false
            }
        ],
        "responsive": true,
        "scrollX": true
    });
});
</script>

<style>
@media print {
    .content-header, .card-header, .btn, .breadcrumb, .card-tools { 
        display: none !important; 
    }
    .card { 
        border: none !important; 
        box-shadow: none !important; 
    }
    .table { 
        font-size: 11px; 
    }
    .thead-dark th {
        background-color: #343a40 !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
    }
}

.table th, .table td {
    vertical-align: middle;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.align-middle {
    vertical-align: middle !important;
}
</style>