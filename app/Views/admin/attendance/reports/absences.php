<?php
/**
 * Vista de reporte detallado de ausencias
 * Muestra ausencias agrupadas por departamento con información completa del empleado
 */

use App\Core\UrlHelper;
$baseUrl = UrlHelper::base();

// Extraer datos del reporte
$period = $report['period'] ?? [];
$summary = $report['summary'] ?? [];
$byDepartment = $report['by_department'] ?? [];
$topAbsentEmployees = $report['top_absent_employees'] ?? [];
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-times mr-2"></i><?= $page_title ?? 'Reporte de Ausencias' ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/panel/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/panel/attendance">Asistencias</a></li>
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/panel/attendance/reports">Reportes</a></li>
                    <li class="breadcrumb-item active">Ausencias</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <!-- Botones de Acción -->
        <div class="row mb-3 no-print">
            <div class="col-md-12">
                <a href="<?= $baseUrl ?>/panel/attendance/reports" class="btn btn-default">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print mr-2"></i>Imprimir
                </button>
                <a href="<?= $baseUrl ?>/panel/attendance/reports/absences?start_date=<?= $period['start_date'] ?>&end_date=<?= $period['end_date'] ?><?= $period['tipo_planilla_id'] ? '&tipo_planilla_id=' . $period['tipo_planilla_id'] : '' ?>&format=excel" class="btn btn-success">
                    <i class="fas fa-file-excel mr-2"></i>Exportar Excel
                </a>
            </div>
        </div>

        <!-- Información del Período -->
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Período del Reporte</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Fecha Inicio:</strong><br>
                        <?= date('d/m/Y', strtotime($period['start_date'])) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Fecha Fin:</strong><br>
                        <?= date('d/m/Y', strtotime($period['end_date'])) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Tipo de Planilla:</strong><br>
                        <?= $period['tipo_planilla_id'] ? "ID: " . $period['tipo_planilla_id'] : 'Todos los tipos' ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Fecha Generación:</strong><br>
                        <?= date('d/m/Y H:i:s') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen Estadístico -->
        <div class="row">
            <div class="col-md-3">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= number_format($summary['total_absences'] ?? 0) ?></h3>
                        <p>Total Ausencias</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= number_format($summary['unjustified'] ?? 0) ?></h3>
                        <p>Injustificadas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= number_format($summary['justified'] ?? 0) ?></h3>
                        <p>Justificadas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= number_format($summary['affected_employees'] ?? 0) ?></h3>
                        <p>Empleados Afectados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 10 Empleados con Más Ausencias -->
        <?php if (!empty($topAbsentEmployees)): ?>
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title"><i class="fas fa-trophy mr-2"></i>Top 10 Empleados con Más Ausencias</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ID Empleado</th>
                                <th>Cédula</th>
                                <th>Nombre Completo</th>
                                <th>Departamento</th>
                                <th>Cargo</th>
                                <th class="text-center">Total Ausencias</th>
                                <th class="text-center">Injustificadas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topAbsentEmployees as $index => $emp): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><strong><?= htmlspecialchars($emp['employee_code']) ?></strong></td>
                                <td><?= htmlspecialchars($emp['cedula']) ?></td>
                                <td><?= htmlspecialchars($emp['full_name']) ?></td>
                                <td><?= htmlspecialchars($emp['departamento'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($emp['position_name'] ?? 'N/A') ?></td>
                                <td class="text-center">
                                    <span class="badge badge-danger"><?= $emp['total_absences'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-warning"><?= $emp['unjustified_count'] ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ausencias por Departamento -->
        <?php foreach ($byDepartment as $deptName => $absences): ?>
        <div class="card department-section">
            <div class="card-header bg-secondary">
                <h3 class="card-title">
                    <i class="fas fa-building mr-2"></i>
                    <?= htmlspecialchars($deptName) ?>
                    <span class="badge badge-light ml-2"><?= count($absences) ?> ausencias</span>
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm datatable">
                        <thead>
                            <tr class="bg-light">
                                <th>ID</th>
                                <th>Cédula</th>
                                <th>Apellidos y Nombres</th>
                                <th>Cargo</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Justificación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($absences as $absence): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($absence['employee_code']) ?></strong></td>
                                <td><?= htmlspecialchars($absence['cedula']) ?></td>
                                <td><?= htmlspecialchars($absence['full_name']) ?></td>
                                <td><?= htmlspecialchars($absence['position_name'] ?? 'N/A') ?></td>
                                <td><?= date('d/m/Y', strtotime($absence['absence_date'])) ?></td>
                                <td>
                                    <?php
                                    $typeClass = '';
                                    $typeText = '';
                                    switch ($absence['absence_type']) {
                                        case 'JUSTIFIED':
                                            $typeClass = 'success';
                                            $typeText = 'Justificada';
                                            break;
                                        case 'UNJUSTIFIED':
                                            $typeClass = 'danger';
                                            $typeText = 'Injustificada';
                                            break;
                                        case 'PENDING':
                                            $typeClass = 'warning';
                                            $typeText = 'Pendiente';
                                            break;
                                        default:
                                            $typeClass = 'secondary';
                                            $typeText = $absence['absence_type'];
                                    }
                                    ?>
                                    <span class="badge badge-<?= $typeClass ?>"><?= $typeText ?></span>
                                </td>
                                <td>
                                    <?php if ($absence['justified'] && $absence['justification_type']): ?>
                                        <small><?= htmlspecialchars($absence['justification_type']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($byDepartment)): ?>
        <div class="callout callout-info">
            <h5><i class="fas fa-info-circle"></i> Sin Ausencias</h5>
            <p>No se encontraron ausencias para el período y filtros seleccionados.</p>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php
// Iniciar captura de scripts
ob_start();
?>
<style>
@media print {
    .no-print { display: none; }
}
.department-section {
    page-break-inside: avoid;
}
</style>

<script>
$(document).ready(function() {
    // Inicializar DataTables para cada tabla
    $('.datatable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[1, 'asc']], // Ordenar por apellido
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});
</script>
<?php
$scripts = ob_get_clean();
?>
