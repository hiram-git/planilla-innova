<?php
/**
 * Vista de reporte detallado de marcaciones
 * Muestra marcaciones (entradas/salidas) con tardanzas, ausencias y horas trabajadas
 */

use App\Core\UrlHelper;
$baseUrl = UrlHelper::base();

// Extraer datos del reporte
$period = $report['period'] ?? [];
$summary = $report['summary'] ?? [];
$byDepartment = $report['by_department'] ?? [];
$stats = $report['stats'] ?? [];
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-fingerprint mr-2"></i><?= $page_title ?? 'Reporte de Marcaciones' ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/panel/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/panel/attendance">Asistencias</a></li>
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/panel/attendance/reports">Reportes</a></li>
                    <li class="breadcrumb-item active">Marcaciones</li>
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
                <a href="<?= $baseUrl ?>/panel/attendance/reports/punches?start_date=<?= $period['start_date'] ?>&end_date=<?= $period['end_date'] ?><?= $period['tipo_planilla_id'] ? '&tipo_planilla_id=' . $period['tipo_planilla_id'] : '' ?>&format=excel" class="btn btn-success">
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
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= number_format($summary['total_punches'] ?? 0) ?></h3>
                        <p>Total Marcaciones</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= number_format($summary['total_on_time'] ?? 0) ?></h3>
                        <p>Marcaciones a Tiempo</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= number_format($summary['total_late'] ?? 0) ?></h3>
                        <p>Con Tardanza</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= number_format($summary['total_absences'] ?? 0) ?></h3>
                        <p>Ausencias Detectadas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas Adicionales -->
        <div class="row">
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-business-time"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Horas Totales Trabajadas</span>
                        <span class="info-box-number"><?= number_format($stats['total_hours_worked'] ?? 0, 1) ?>h</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box bg-gradient-warning">
                    <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Horas Extras Total</span>
                        <span class="info-box-number"><?= number_format($stats['total_overtime'] ?? 0, 1) ?>h</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box bg-gradient-success">
                    <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Promedio Horas/Día</span>
                        <span class="info-box-number"><?= number_format($stats['avg_hours_per_day'] ?? 0, 1) ?>h</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="info-box bg-gradient-danger">
                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Minutos Tardanza Total</span>
                        <span class="info-box-number"><?= number_format($stats['total_tardiness_minutes'] ?? 0) ?> min</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Marcaciones por Departamento -->
        <?php foreach ($byDepartment as $deptName => $punches): ?>
        <div class="card department-section">
            <div class="card-header bg-secondary">
                <h3 class="card-title">
                    <i class="fas fa-building mr-2"></i>
                    <?= htmlspecialchars($deptName) ?>
                    <span class="badge badge-light ml-2"><?= count($punches) ?> registros</span>
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
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Horas</th>
                                <th>Tardanza</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($punches as $p): ?>
                            <tr class="<?= ($p['is_absent'] ?? false) ? 'table-danger' : (($p['is_late'] ?? false) ? 'table-warning' : '') ?>">
                                <td><strong><?= htmlspecialchars($p['employee_code']) ?></strong></td>
                                <td><?= htmlspecialchars($p['cedula']) ?></td>
                                <td><?= htmlspecialchars($p['full_name']) ?></td>
                                <td><?= htmlspecialchars($p['position_name'] ?? 'N/A') ?></td>
                                <td><?= date('d/m/Y', strtotime($p['date'])) ?></td>
                                <td>
                                    <?php if ($p['is_absent'] ?? false): ?>
                                        <span class="badge badge-danger">AUSENTE</span>
                                    <?php else: ?>
                                        <?= $p['time_in'] ? date('H:i', strtotime($p['date'] . ' ' . $p['time_in'])) : '<span class="text-muted">--:--</span>' ?>
                                        <?php if (($p['is_late'] ?? false) && ($p['tardiness_minutes'] ?? 0) > 0): ?>
                                            <br><small class="text-warning">(<?= $p['tardiness_minutes'] ?> min tarde)</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= !($p['is_absent'] ?? false) && $p['time_out'] ? date('H:i', strtotime($p['date'] . ' ' . $p['time_out'])) : '<span class="text-muted">--:--</span>' ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!($p['is_absent'] ?? false)): ?>
                                        <strong><?= number_format($p['total_hours'] ?? 0, 1) ?>h</strong>
                                        <?php if (($p['overtime_hours'] ?? 0) > 0): ?>
                                            <br><small class="text-info">(+<?= number_format($p['overtime_hours'], 1) ?>h extras)</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">--</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($p['is_absent'] ?? false): ?>
                                        <span class="badge badge-danger">AUSENCIA</span>
                                    <?php elseif (($p['is_late'] ?? false) && ($p['tardiness_minutes'] ?? 0) > 0): ?>
                                        <span class="badge badge-warning"><?= number_format($p['tardiness_minutes']) ?> min</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">A tiempo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($p['is_absent'] ?? false): ?>
                                        <span class="badge badge-pill badge-danger">
                                            <i class="fas fa-user-times"></i> Ausente
                                        </span>
                                    <?php elseif ($p['is_late'] ?? false): ?>
                                        <span class="badge badge-pill badge-warning">
                                            <i class="fas fa-clock"></i> Tarde
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-pill badge-success">
                                            <i class="fas fa-check"></i> Normal
                                        </span>
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
            <h5><i class="fas fa-info-circle"></i> Sin Marcaciones</h5>
            <p>No se encontraron marcaciones para el período y filtros seleccionados.</p>
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
.table-warning {
    background-color: #fff3cd !important;
}
.table-danger {
    background-color: #f8d7da !important;
}
</style>

<script>
$(document).ready(function() {
    // Inicializar DataTables para cada tabla
    $('.datatable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[4, 'desc']], // Ordenar por fecha (descendente)
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
