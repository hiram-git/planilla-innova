<?php
/**
 * Vista de reporte detallado de tardanzas
 * Muestra tardanzas agrupadas por departamento con información completa del empleado
 */

use App\Core\UrlHelper;
$baseUrl = UrlHelper::base();

// Extraer datos del reporte
$period = $report['period'] ?? [];
$summary = $report['summary'] ?? [];
$byDepartment = $report['by_department'] ?? [];
$topTardinessEmployees = $report['top_tardiness_employees'] ?? [];
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-clock mr-2"></i><?= $page_title ?? 'Reporte de Tardanzas' ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/panel/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/panel/attendance">Asistencias</a></li>
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/panel/attendance/reports">Reportes</a></li>
                    <li class="breadcrumb-item active">Tardanzas</li>
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
                        <button onclick="exportToExcel()" class="btn btn-success" disabled>
                            <i class="fas fa-file-excel mr-2"></i>Exportar Excel (Próximamente)
                        </button>
                    </div>
                </div>

                <!-- Información del Período -->
                <div class="card">
                    <div class="card-header bg-warning">
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
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?= number_format($summary['total_tardiness'] ?? 0) ?></h3>
                                <p>Total Tardanzas</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?= number_format($summary['total_minutes'] ?? 0) ?></h3>
                                <p>Minutos Totales</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?= number_format($summary['avg_minutes'] ?? 0, 1) ?></h3>
                                <p>Promedio Minutos/Tardanza</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="small-box bg-primary">
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

                <!-- Top 10 Empleados con Más Tardanzas -->
                <?php if (!empty($topTardinessEmployees)): ?>
                <div class="card">
                    <div class="card-header bg-orange">
                        <h3 class="card-title"><i class="fas fa-trophy mr-2"></i>Top 10 Empleados con Más Tardanzas</h3>
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
                                        <th class="text-center">Total Tardanzas</th>
                                        <th class="text-center">Minutos Totales</th>
                                        <th class="text-center">Promedio Min.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topTardinessEmployees as $index => $emp): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($emp['employee_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($emp['cedula']) ?></td>
                                        <td><?= htmlspecialchars($emp['full_name']) ?></td>
                                        <td><?= htmlspecialchars($emp['departamento'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($emp['position_name'] ?? 'N/A') ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-warning"><?= $emp['total_tardiness'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-danger"><?= number_format($emp['total_minutes']) ?> min</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-info"><?= number_format($emp['avg_minutes'], 1) ?> min</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tardanzas por Departamento -->
                <?php foreach ($byDepartment as $deptName => $tardiness): ?>
                <div class="card department-section">
                    <div class="card-header bg-secondary">
                        <h3 class="card-title">
                            <i class="fas fa-building mr-2"></i>
                            <?= htmlspecialchars($deptName) ?>
                            <span class="badge badge-light ml-2"><?= count($tardiness) ?> tardanzas</span>
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
                                        <th>Hora Entrada</th>
                                        <th>Hora Esperada</th>
                                        <th>Minutos Tarde</th>
                                        <th>Gravedad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tardiness as $t): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($t['employee_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($t['cedula']) ?></td>
                                        <td><?= htmlspecialchars($t['full_name']) ?></td>
                                        <td><?= htmlspecialchars($t['position_name'] ?? 'N/A') ?></td>
                                        <td><?= date('d/m/Y', strtotime($t['tardiness_date'])) ?></td>
                                        <td><?= date('H:i', strtotime($t['check_in_time'])) ?></td>
                                        <td><?= date('H:i', strtotime($t['expected_time'])) ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-tardiness badge-<?php
                                                $minutes = $t['minutes_late'];
                                                if ($minutes <= 5) echo 'info';
                                                elseif ($minutes <= 15) echo 'warning';
                                                elseif ($minutes <= 30) echo 'orange';
                                                else echo 'danger';
                                            ?>">
                                                <?= number_format($t['minutes_late']) ?> min
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $minutes = $t['minutes_late'];
                                            if ($minutes <= 5) {
                                                echo '<span class="badge badge-info">Leve</span>';
                                            } elseif ($minutes <= 15) {
                                                echo '<span class="badge badge-warning">Moderada</span>';
                                            } elseif ($minutes <= 30) {
                                                echo '<span class="badge badge-orange">Severa</span>';
                                            } else {
                                                echo '<span class="badge badge-danger">Crítica</span>';
                                            }
                                            ?>
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
            <h5><i class="fas fa-info-circle"></i> Sin Tardanzas</h5>
            <p>No se encontraron tardanzas para el período y filtros seleccionados.</p>
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
.badge-tardiness {
    font-size: 0.9rem;
}
</style>

<script>
$(document).ready(function() {
    // Inicializar DataTables para cada tabla
    $('.datatable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[7, 'desc']], // Ordenar por minutos tarde (descendente)
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

function exportToExcel() {
    alert('Exportación a Excel estará disponible próximamente');
}
</script>
<?php
$scripts = ob_get_clean();
?>
