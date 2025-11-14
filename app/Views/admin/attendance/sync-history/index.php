<?php
/**
 * Vista: Historial de Sincronizaciones
 * Ruta: /panel/attendance/sync-history
 * Estructura: Cabecera (tabla sync_log) + Detalle (modal con headers/details)
 */
?>


<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <!-- Estadísticas Generales -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $stats['total_syncs'] ?? 0 ?></h3>
                        <p>Total Sincronizaciones</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $stats['successful_syncs'] ?? 0 ?></h3>
                        <p>Exitosas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= $stats['failed_syncs'] ?? 0 ?></h3>
                        <p>Fallidas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= number_format($stats['total_records_inserted'] ?? 0) ?></h3>
                        <p>Registros Insertados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row">
            <div class="col-12">
                <div class="card collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="display: none;">
                        <form method="GET" action="/panel/attendance/sync-history">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_status">Estado:</label>
                                        <select class="form-control" id="filter_status" name="status">
                                            <option value="">Todos</option>
                                            <option value="SUCCESS" <?= ($filters['status'] ?? '') === 'SUCCESS' ? 'selected' : '' ?>>Exitoso</option>
                                            <option value="FAILED" <?= ($filters['status'] ?? '') === 'FAILED' ? 'selected' : '' ?>>Fallido</option>
                                            <option value="PARTIAL" <?= ($filters['status'] ?? '') === 'PARTIAL' ? 'selected' : '' ?>>Parcial</option>
                                            <option value="RUNNING" <?= ($filters['status'] ?? '') === 'RUNNING' ? 'selected' : '' ?>>En Ejecución</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_date_from">Fecha Desde:</label>
                                        <input type="date" class="form-control" id="filter_date_from" name="date_from"
                                               value="<?= $filters['date_from'] ?? '' ?>">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_date_to">Fecha Hasta:</label>
                                        <input type="date" class="form-control" id="filter_date_to" name="date_to"
                                               value="<?= $filters['date_to'] ?? '' ?>">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-search"></i> Filtrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Sincronizaciones -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Historial de Sincronizaciones</h3>
                    </div>
                    <div class="card-body">
                        <table id="sync-history-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Fecha/Hora Inicio</th>
                                    <th>Duración</th>
                                    <th>Estado</th>
                                    <th>Registros</th>
                                    <th>Errores</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sync_logs)): ?>
                                    <?php foreach ($sync_logs as $log): ?>
                                        <tr>
                                            <td><code>#<?= $log['id'] ?></code></td>
                                            <td>
                                                <?php
                                                $typeLabels = [
                                                    'full' => 'Completa',
                                                    'today' => 'Día Actual',
                                                    'daterange' => 'Rango Fechas',
                                                    'file_import' => 'Importación'
                                                ];
                                                echo $typeLabels[$log['sync_type']] ?? $log['sync_type'];
                                                ?>
                                            </td>
                                            <td>
                                                <?= date('d/m/Y H:i:s', strtotime($log['start_time'])) ?>
                                            </td>
                                            <td>
                                                <?php
                                                if ($log['duration_seconds']) {
                                                    $minutes = floor($log['duration_seconds'] / 60);
                                                    $seconds = $log['duration_seconds'] % 60;
                                                    echo $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'SUCCESS' => 'success',
                                                    'FAILED' => 'danger',
                                                    'PARTIAL' => 'warning',
                                                    'RUNNING' => 'info'
                                                ];
                                                $statusLabels = [
                                                    'SUCCESS' => 'Exitoso',
                                                    'FAILED' => 'Fallido',
                                                    'PARTIAL' => 'Parcial',
                                                    'RUNNING' => 'En Ejecución'
                                                ];
                                                $color = $statusColors[$log['status']] ?? 'secondary';
                                                $label = $statusLabels[$log['status']] ?? $log['status'];
                                                ?>
                                                <span class="badge badge-<?= $color ?>">
                                                    <?= $label ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <strong>Obtenidos:</strong> <?= $log['records_fetched'] ?? 0 ?><br>
                                                    <strong>Insertados:</strong> <?= $log['records_inserted'] ?? 0 ?><br>
                                                    <strong>Actualizados:</strong> <?= $log['records_updated'] ?? 0 ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($log['errors_count'] > 0): ?>
                                                    <span class="badge badge-danger">
                                                        <?= $log['errors_count'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button"
                                                        class="btn btn-info btn-sm btn-view-detail"
                                                        data-sync-id="<?= $log['id'] ?>"
                                                        title="Ver Detalle">
                                                    <i class="fas fa-eye"></i> Ver Detalle
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No hay sincronizaciones registradas</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Detalle de Sincronización -->
<div class="modal fade" id="syncDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle"></i> Detalle de Sincronización #<span id="modal-sync-id"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="sync-detail-content">
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-3">Cargando información...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
// Iniciar captura de estilos
ob_start();
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<?php
$styles = ob_get_clean();

// Iniciar captura de scripts
ob_start();
?>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    // Base URL del proyecto
    const baseUrl = '<?= \App\Core\UrlHelper::base() ?>';

    // Inicializar DataTable solo si hay registros
    const hasData = $('#sync-history-table tbody tr').length > 0 &&
                    !$('#sync-history-table tbody tr td[colspan]').length;

    if (hasData) {
        $('#sync-history-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[2, 'desc']], // Ordenar por fecha descendente
            pageLength: 25
        });
    }

    // Ver detalle de sincronización
    $(document).on('click', '.btn-view-detail', function() {
        const syncId = $(this).data('sync-id');

        // Mostrar modal
        $('#modal-sync-id').text(syncId);
        $('#syncDetailModal').modal('show');

        // Cargar detalle via AJAX
        $.ajax({
            url: `${baseUrl}/panel/attendance/sync-history/${syncId}`,
            method: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    renderSyncDetail(response.data);
                } else {
                    $('#sync-detail-content').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            ${response.message || 'Error al cargar detalle'}
                        </div>
                    `);
                }
            },
            error: function() {
                $('#sync-detail-content').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error al cargar la información del servidor
                    </div>
                `);
            }
        });
    });

    // Función para renderizar el detalle
    function renderSyncDetail(data) {
        const log = data.log;
        const summary = data.summary;
        const headers = data.headers || [];
        const detailStats = data.detail_stats || [];
        const errors = data.errors || [];

        let html = `
            <!-- Información General -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-calendar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Fecha/Hora</span>
                            <span class="info-box-number">${formatDateTime(log.start_time)}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Duración</span>
                            <span class="info-box-number">${formatDuration(log.duration_seconds)}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-database"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Headers</span>
                            <span class="info-box-number">${summary.total_headers}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Detalles</span>
                            <span class="info-box-number">${summary.total_details}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-chart-bar"></i> Estadísticas de Procesamiento</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Registros Obtenidos:</strong> ${log.records_fetched || 0}
                                </div>
                                <div class="col-md-3">
                                    <strong>Registros Insertados:</strong> ${log.records_inserted || 0}
                                </div>
                                <div class="col-md-3">
                                    <strong>Registros Actualizados:</strong> ${log.records_updated || 0}
                                </div>
                                <div class="col-md-3">
                                    <strong>Registros Omitidos:</strong> ${log.records_skipped || 0}
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <strong>Empleados Únicos:</strong> ${summary.unique_employees || 0}
                                </div>
                                <div class="col-md-3">
                                    <strong>Tardanzas:</strong> ${summary.total_late || 0}
                                </div>
                                <div class="col-md-3">
                                    <strong>Ausencias:</strong> ${summary.total_absent || 0}
                                </div>
                                <div class="col-md-3">
                                    <strong>Errores:</strong> <span class="badge badge-${log.errors_count > 0 ? 'danger' : 'success'}">${log.errors_count || 0}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Headers creados
        if (headers.length > 0) {
            html += `
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-calendar-alt"></i> Fechas Procesadas (${headers.length})</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Dispositivo</th>
                                            <th>Total Registros</th>
                                            <th>Empleados</th>
                                            <th>Puntuales</th>
                                            <th>Tarde</th>
                                            <th>Ausentes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
            `;

            headers.forEach(header => {
                html += `
                    <tr>
                        <td><strong>${formatDate(header.attendance_date)}</strong></td>
                        <td>${header.device_name || '-'}</td>
                        <td>${header.total_records}</td>
                        <td>${header.total_employees}</td>
                        <td><span class="badge badge-success">${header.total_on_time}</span></td>
                        <td><span class="badge badge-warning">${header.total_late}</span></td>
                        <td><span class="badge badge-danger">${header.total_absent}</span></td>
                    </tr>
                `;
            });

            html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Errores
        if (errors.length > 0) {
            html += `
                <div class="row">
                    <div class="col-12">
                        <div class="card card-danger">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-exclamation-triangle"></i> Errores Encontrados</h5>
                            </div>
                            <div class="card-body">
                                <pre class="bg-light p-3 border">${JSON.stringify(errors, null, 2)}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        $('#sync-detail-content').html(html);
    }

    // Helpers de formato
    function formatDateTime(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    function formatDuration(seconds) {
        if (!seconds) return '-';
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return minutes > 0 ? `${minutes}m ${secs}s` : `${secs}s`;
    }
});
</script>
<?php
$scripts = ob_get_clean();
?>
