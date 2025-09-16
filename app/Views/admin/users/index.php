<?php
$page_title = $title ?? 'Gestión de Usuarios';
$scripts = '
<script>
$(document).ready(function() {
    // Inicializar DataTable
    $("#usersTable").DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "pageLength": 25,
        "language": {
            "search": "Buscar:",
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "No se encontraron usuarios",
            "info": "Mostrando página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        "order": [[0, "desc"]],
        "columnDefs": [
            { "orderable": false, "targets": [5, 6] }
        ]
    });

    // Toggle de estado
    $(".status-toggle").on("change", function() {
        const userId = $(this).data("id");
        const newStatus = $(this).prop("checked") ? 1 : 0;
        const toggle = $(this);
        const label = $(this).next("label");

        $.ajax({
            url: "'.\App\Core\UrlHelper::url('/panel/users/toggle-status').'",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                id: userId,
                status: newStatus
            }),
            success: function(response) {
                if (response.success) {
                    label.text(newStatus ? "Activo" : "Inactivo");
                    toastr.success(
                        newStatus ? "Usuario activado exitosamente" : "Usuario desactivado exitosamente",
                        "Estado Actualizado"
                    );
                } else {
                    toggle.prop("checked", !newStatus);
                    toastr.error(response.message || "Error al cambiar estado", "Error");
                }
            },
            error: function() {
                toggle.prop("checked", !newStatus);
                toastr.error("Error de conexión al servidor", "Error");
            }
        });
    });
});

function confirmDelete(userId, username) {
    $("#deleteUsername").text(username);
    $("#deleteForm").attr("action", "'.\App\Core\UrlHelper::url('/panel/users').'/" + userId + "/delete");
    $("#deleteModal").modal("show");
}
</script>';
?>

<div class="row">    
    <div class="col-12">
            <!-- Mensajes de alerta -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Tarjeta principal -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i> Lista de Usuarios
                    </h3>
                    <div class="card-tools">
                        <a href="<?= \App\Core\UrlHelper::url('/panel/users/create') ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Nuevo Usuario
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="usersTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Nombre Completo</th>
                                    <th>Rol</th>
                                    <th>Fecha Creación</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users ?? [] as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                        <?php if (!empty($user['photo'])): ?>
                                            <i class="fas fa-camera text-info ml-1" title="Tiene foto"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></td>
                                    <td>
                                        <?php if (!empty($user['role_name'])): ?>
                                            <span class="badge badge-info">
                                                <?= htmlspecialchars($user['role_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Sin rol</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d/m/Y', strtotime($user['created_on'])) ?>
                                    </td>
                                    <td>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" 
                                                   class="custom-control-input status-toggle" 
                                                   id="status_<?= $user['id'] ?>"
                                                   data-id="<?= $user['id'] ?>"
                                                   <?= $user['status'] ? 'checked' : '' ?>>
                                            <label class="custom-control-label" for="status_<?= $user['id'] ?>">
                                                <?= $user['status'] ? 'Activo' : 'Inactivo' ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group" aria-label="Acciones">
                                            <a href="<?= \App\Core\UrlHelper::url('/panel/users/'.$user['id']) ?>" 
                                               class="btn btn-info btn-sm" 
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= \App\Core\UrlHelper::url('/panel/users/'.$user['id'].'/edit') ?>" 
                                               class="btn btn-warning btn-sm" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm" 
                                                    onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-sm-6">
                            <p class="text-muted">
                                Total: <strong><?= count($users ?? []) ?></strong> usuarios
                            </p>
                        </div>
                        <div class="col-sm-6">
                            <div class="float-right">
                                <small class="text-muted">
                                    Última actualización: <?= date('d/m/Y H:i:s') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el usuario <strong id="deleteUsername"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    <strong>Nota:</strong> Esta acción marcará al usuario como inactivo pero no eliminará su historial.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <form id="deleteForm" method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Eliminar Usuario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.custom-switch {
    padding-left: 3.25rem;
}

.custom-control-label::before {
    width: 2.25rem;
    height: 1.25rem;
    border-radius: 0.75rem;
}

.custom-control-label::after {
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.badge {
    font-size: 0.8em;
}
</style>