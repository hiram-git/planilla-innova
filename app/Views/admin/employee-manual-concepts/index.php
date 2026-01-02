<?php
/**
 * Vista: Lista de Conceptos Manuales de Empleados
 * Version: 3.5.18
 */
?>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $stats['activos'] ?? 0 ?></h3>
                        <p>Activos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $stats['aplicados'] ?? 0 ?></h3>
                        <p>Aplicados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><?= $stats['cancelados'] ?? 0 ?></h3>
                        <p>Cancelados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-ban"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= $stats['empleados_afectados'] ?? 0 ?></h3>
                        <p>Empleados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Listado de Conceptos Manuales
                </h3>
                <div class="card-tools">
                    <a href="<?= \App\Core\UrlHelper::route('panel/employee-manual-concepts/create') ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Concepto Manual
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <form method="GET" action="<?= \App\Core\UrlHelper::route('panel/employee-manual-concepts') ?>" class="form-inline">
                            <div class="form-group mr-2">
                                <label class="mr-2">Estado:</label>
                                <select name="estado" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    <option value="ACTIVO" <?= ($filters['estado'] ?? '') === 'ACTIVO' ? 'selected' : '' ?>>Activo</option>
                                    <option value="APLICADO" <?= ($filters['estado'] ?? '') === 'APLICADO' ? 'selected' : '' ?>>Aplicado</option>
                                    <option value="CANCELADO" <?= ($filters['estado'] ?? '') === 'CANCELADO' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm mr-2">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>

                            <a href="<?= \App\Core\UrlHelper::route('panel/employee-manual-concepts') ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </form>
                    </div>
                </div>

                <!-- DataTable -->
                <table id="manualConceptsTable" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Empleado</th>
                            <th>Código</th>
                            <th>Concepto</th>
                            <th>Tipo</th>
                            <th>Unidad</th>
                            <th>Monto Fijo</th>
                            <th>Desde</th>
                            <th>Hasta</th>
                            <th>Una Vez</th>
                            <th>Estado</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated by DataTables AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

<!-- Scripts -->
<?php
$scripts = "
<script>
$(document).ready(function() {
    // DataTable
    var table = $('#manualConceptsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '" . \App\Core\UrlHelper::route('panel/employee-manual-concepts/data') . "',
            data: function(d) {
                d.estado = $('select[name=estado]').val();
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7 },
            { data: 8 },
            { data: 9 },
            { data: 10 },
            { data: 11, orderable: false }
        ],
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        stateSave: true,
        pageLength: 25
    });

    // Cancelar concepto
    $(document).on('click', '.btn-cancel', function() {
        var id = $(this).data('id');

        Swal.fire({
            title: '¿Cancelar concepto?',
            text: 'El concepto será marcado como cancelado',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('" . \App\Core\UrlHelper::route('panel/employee-manual-concepts') . "/' + id + '/cancel', {
                    csrf_token: '" . ($csrf_token ?? '') . "'
                }, function(response) {
                    if (response.success) {
                        Swal.fire('Cancelado', response.message, 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                });
            }
        });
    });

    // Reactivar concepto
    $(document).on('click', '.btn-reactivate', function() {
        var id = $(this).data('id');

        Swal.fire({
            title: '¿Reactivar concepto?',
            text: 'El concepto volverá a estar activo',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, reactivar',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('" . \App\Core\UrlHelper::route('panel/employee-manual-concepts') . "/' + id + '/reactivate', {
                    csrf_token: '" . ($csrf_token ?? '') . "'
                }, function(response) {
                    if (response.success) {
                        Swal.fire('Reactivado', response.message, 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                });
            }
        });
    });

    // Eliminar concepto
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');

        Swal.fire({
            title: '¿Eliminar concepto?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('" . \App\Core\UrlHelper::route('panel/employee-manual-concepts') . "/' + id + '/delete', {
                    csrf_token: '" . ($csrf_token ?? '') . "'
                }, function(response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                });
            }
        });
    });
});
</script>
";
?>
