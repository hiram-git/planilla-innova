<?php
/**
 * Vista: Liquidaciones Canceladas
 * Descripción: Lista de todas las liquidaciones que han sido canceladas
 * Versión: 3.3.1
 */

// Incluir helper para funciones auxiliares
require_once __DIR__ . '/../../../helpers.php';
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-ban text-danger mr-2"></i>
                    Liquidaciones Canceladas
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/panel">Panel</a></li>
                    <li class="breadcrumb-item"><a href="/panel/liquidation">Liquidaciones</a></li>
                    <li class="breadcrumb-item active">Canceladas</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Estadísticas -->
        <div class="row mb-3">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= count($cancelled_liquidations) ?></h3>
                        <p>Total Canceladas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-ban"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= count(array_filter($cancelled_liquidations, function($l) { return $l['previous_status'] == 'PENDIENTE'; })) ?></h3>
                        <p>Desde Pendiente</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= count(array_filter($cancelled_liquidations, function($l) { return $l['previous_status'] == 'CALCULADA'; })) ?></h3>
                        <p>Desde Calculada</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= count(array_filter($cancelled_liquidations, function($l) { return $l['previous_status'] == 'PROCESADA'; })) ?></h3>
                        <p>Desde Procesada</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-tools mr-2"></i>
                            Acciones Rápidas
                        </h3>
                    </div>
                    <div class="card-body">
                        <a href="/panel/liquidation" class="btn btn-primary">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver a Liquidaciones Activas
                        </a>
                        <button type="button" class="btn btn-success" onclick="exportCancelledList()">
                            <i class="fas fa-file-excel mr-2"></i>
                            Exportar Lista
                        </button>
                        <button type="button" class="btn btn-info" onclick="refreshData()">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Actualizar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Liquidaciones Canceladas -->
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list mr-2"></i>
                    Historial de Cancelaciones
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="table_search" class="form-control float-right" placeholder="Buscar empleado..." id="searchInput">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <?php if (empty($cancelled_liquidations)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-smile text-success" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-muted">¡Excelente!</h4>
                    <p class="text-muted">No hay liquidaciones canceladas en el sistema.</p>
                    <a href="/panel/liquidation" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Ir a Liquidaciones Activas
                    </a>
                </div>
                <?php else: ?>
                <table class="table table-hover text-nowrap" id="cancelledTable">
                    <thead class="bg-danger">
                        <tr>
                            <th>Empleado</th>
                            <th>Documento</th>
                            <th>Posición</th>
                            <th>Estado Anterior</th>
                            <th>Fecha Cancelación</th>
                            <th>Cancelado Por</th>
                            <th>Motivo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cancelled_liquidations as $liquidation): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-times text-danger mr-2"></i>
                                    <div>
                                        <strong><?= htmlspecialchars($liquidation['firstname'] . ' ' . $liquidation['lastname']) ?></strong>
                                        <br>
                                        <small class="text-muted">ID: <?= htmlspecialchars($liquidation['employee_id']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($liquidation['document_id']) ?></td>
                            <td>
                                <span class="badge badge-secondary">
                                    <?= htmlspecialchars($liquidation['position_name'] ?? 'Sin posición') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?=
                                    $liquidation['previous_status'] == 'PENDIENTE' ? 'warning' :
                                    ($liquidation['previous_status'] == 'CALCULADA' ? 'info' :
                                    ($liquidation['previous_status'] == 'PROCESADA' ? 'primary' : 'success'))
                                ?>">
                                    <?= htmlspecialchars($liquidation['previous_status']) ?>
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-calendar-alt text-muted mr-1"></i>
                                <?= date('d/m/Y', strtotime($liquidation['cancelled_at'])) ?>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?= date('H:i', strtotime($liquidation['cancelled_at'])) ?>
                                </small>
                            </td>
                            <td>
                                <i class="fas fa-user text-muted mr-1"></i>
                                <?= htmlspecialchars($liquidation['cancelled_by_name'] . ' ' . $liquidation['cancelled_by_lastname']) ?>
                            </td>
                            <td>
                                <div style="max-width: 200px;">
                                    <span class="text-truncate d-block" title="<?= htmlspecialchars($liquidation['cancel_reason']) ?>">
                                        <?= htmlspecialchars(substr($liquidation['cancel_reason'], 0, 50)) ?>
                                        <?= strlen($liquidation['cancel_reason']) > 50 ? '...' : '' ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-info btn-sm" onclick="viewDetails(<?= $liquidation['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="viewHistory(<?= $liquidation['id'] ?>)">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Modal para Detalles -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle mr-2"></i>
                    Detalles de Cancelación
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
$(document).ready(function() {
    // Funcionalidad de búsqueda
    $('#searchInput').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('#cancelledTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});

// Función para ver detalles
function viewDetails(terminationId) {
    // Aquí se podría hacer una llamada AJAX para obtener detalles completos
    $('#detailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
    $('#detailsModal').modal('show');

    // Simulación de carga (en implementación real sería AJAX)
    setTimeout(() => {
        $('#detailsContent').html(`
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                Funcionalidad de detalles completos en desarrollo.
            </div>
            <p>ID de Liquidación: ${terminationId}</p>
        `);
    }, 500);
}

// Función para ver historial
function viewHistory(terminationId) {
    window.location.href = `/panel/liquidation/history/${terminationId}`;
}

// Función para exportar
function exportCancelledList() {
    Swal.fire({
        title: 'Exportar Lista',
        text: 'Funcionalidad de exportación en desarrollo',
        icon: 'info',
        confirmButtonText: 'Entendido'
    });
}

// Función para actualizar
function refreshData() {
    window.location.reload();
}
</script>