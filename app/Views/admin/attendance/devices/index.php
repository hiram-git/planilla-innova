<?php
/**
 * Vista: Gestión de Dispositivos de Marcación
 * Ruta: /panel/attendance/devices
 */
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?= $title ?? 'Dispositivos de Marcación' ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/panel/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/panel/attendance">Asistencia</a></li>
                    <li class="breadcrumb-item active">Dispositivos</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <!-- Estadísticas -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $stats['total'] ?? 0 ?></h3>
                        <p>Total Dispositivos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-desktop"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $stats['active'] ?? 0 ?></h3>
                        <p>Activos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $stats['api_devices'] ?? 0 ?></h3>
                        <p>Tipo API</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-plug"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><?= $stats['file_devices'] ?? 0 ?></h3>
                        <p>Tipo Archivo</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listado de Dispositivos -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Dispositivos</h3>
                        <div class="card-tools">
                            <a href="/panel/attendance/devices/create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Agregar Dispositivo
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="devices-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Ubicación</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($devices)): ?>
                                    <?php foreach ($devices as $device): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($device['device_code']) ?></code></td>
                                            <td><?= htmlspecialchars($device['device_name']) ?></td>
                                            <td>
                                                <?php
                                                $typeColors = [
                                                    'API' => 'primary',
                                                    'BIOMETRIC' => 'success',
                                                    'TEXT_FILE' => 'warning',
                                                    'MANUAL' => 'secondary'
                                                ];
                                                $color = $typeColors[$device['device_type']] ?? 'secondary';
                                                ?>
                                                <span class="badge badge-<?= $color ?>">
                                                    <?= $device['device_type'] ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($device['location'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($device['is_active']): ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/panel/attendance/devices/<?= $device['id'] ?>/edit"
                                                       class="btn btn-info"
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <?php if ($device['device_type'] === 'API'): ?>
                                                        <button type="button"
                                                                class="btn btn-warning btn-test-connection"
                                                                data-id="<?= $device['id'] ?>"
                                                                title="Test Conexión">
                                                            <i class="fas fa-network-wired"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <button type="button"
                                                            class="btn btn-<?= $device['is_active'] ? 'secondary' : 'success' ?> btn-toggle-status"
                                                            data-id="<?= $device['id'] ?>"
                                                            title="<?= $device['is_active'] ? 'Desactivar' : 'Activar' ?>">
                                                        <i class="fas fa-power-off"></i>
                                                    </button>

                                                    <button type="button"
                                                            class="btn btn-danger btn-delete-device"
                                                            data-id="<?= $device['id'] ?>"
                                                            data-name="<?= htmlspecialchars($device['device_name']) ?>"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No hay dispositivos registrados</td>
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

<!-- Scripts -->
<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#devices-table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[1, 'asc']]
    });

    // Test de conexión API
    $(document).on('click', '.btn-test-connection', function() {
        const deviceId = $(this).data('id');
        const btn = $(this);

        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: `/panel/attendance/devices/${deviceId}/test`,
            method: 'POST',
            data: {
                csrf_token: '<?= $csrf_token ?? '' ?>'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Error al realizar test de conexión');
            },
            complete: function() {
                btn.prop('disabled', false);
                btn.html('<i class="fas fa-network-wired"></i>');
            }
        });
    });

    // Toggle estado
    $(document).on('click', '.btn-toggle-status', function() {
        const deviceId = $(this).data('id');
        const btn = $(this);

        $.ajax({
            url: `/panel/attendance/devices/${deviceId}/toggle`,
            method: 'POST',
            data: {
                csrf_token: '<?= $csrf_token ?? '' ?>'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Error al cambiar estado');
            }
        });
    });

    // Eliminar dispositivo
    $(document).on('click', '.btn-delete-device', function() {
        const deviceId = $(this).data('id');
        const deviceName = $(this).data('name');

        Swal.fire({
            title: '¿Está seguro?',
            html: `¿Desea eliminar el dispositivo <strong>${deviceName}</strong>?<br><br>` +
                  '<span class="text-danger">Esta acción no se puede deshacer.</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/panel/attendance/devices/${deviceId}/delete`,
                    method: 'POST',
                    data: {
                        csrf_token: '<?= $csrf_token ?? '' ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('Error al eliminar dispositivo');
                    }
                });
            }
        });
    });
});
</script>
