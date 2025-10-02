<?php
$pageTitle = $title ?? 'Seleccionar Planilla';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?= htmlspecialchars($title) ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/reports') ?>">Centro de Reportes</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($reportType ?? 'Reporte') ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-excel mr-2"></i>
                    Seleccionar Planilla para Generar <?= htmlspecialchars(strtoupper($reportType ?? 'Reporte')) ?>
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($planillas)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        No hay planillas procesadas o cerradas disponibles para generar reportes.
                    </div>
                    <div class="text-center">
                        <a href="<?= \App\Core\UrlHelper::route('panel/reports') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Volver al Centro de Reportes
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Seleccione la planilla para la cual desea generar el reporte <?= htmlspecialchars(strtoupper($reportType ?? '')) ?>.
                        Solo se muestran planillas con estado "Procesada" o "Cerrada".
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="planillasTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Descripción</th>
                                    <th>Tipo</th>
                                    <th>Período</th>
                                    <th>Estado</th>
                                    <th width="120">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($planillas as $planilla): ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge badge-primary"><?= htmlspecialchars($planilla['id']) ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($planilla['descripcion']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= htmlspecialchars($planilla['tipo_descripcion'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <small>
                                                <strong>Desde:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($planilla['fecha_desde']))) ?><br>
                                                <strong>Hasta:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($planilla['fecha_hasta']))) ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $badgeClass = 'secondary';
                                            if ($planilla['estado_texto'] === 'Procesada') $badgeClass = 'success';
                                            if ($planilla['estado_texto'] === 'Cerrada') $badgeClass = 'primary';
                                            ?>
                                            <span class="badge badge-<?= $badgeClass ?>">
                                                <?= htmlspecialchars($planilla['estado_texto']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= \App\Core\UrlHelper::route('panel/excel/generate' . ucfirst($reportType) . '/' . $planilla['id']) ?>"
                                               class="btn btn-success btn-sm"
                                               title="Generar <?= htmlspecialchars(strtoupper($reportType)) ?>">
                                                <i class="fas fa-file-excel mr-1"></i>
                                                Generar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-center">
                        <a href="<?= \App\Core\UrlHelper::route('panel/reports') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Volver al Centro de Reportes
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($("#planillasTable").length) {
        $("#planillasTable").DataTable({
            "responsive": true,
            "pageLength": 15,
            "order": [[0, "desc"]], // Ordenar por ID descendente
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
            },
            "columnDefs": [
                { "orderable": false, "targets": -1 } // Deshabilitar ordenamiento en columna de acciones
            ]
        });
    }
});
</script>