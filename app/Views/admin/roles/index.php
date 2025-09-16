<?php
$page_title = $title ?? 'Gestión de Roles';
$scripts = '
<script>
$(document).ready(function() {
    // Inicializar DataTable
    $("#rolesTable").DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "pageLength": 25,
        "language": {
            "search": "Buscar:",
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "No se encontraron roles",
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
            { "orderable": false, "targets": [4] }
        ]
    });

    // Toggle de estado
    $(".status-toggle").on("change", function() {
        const roleId = $(this).data("id");
        const newStatus = $(this).prop("checked") ? 1 : 0;
        const toggle = $(this);
        const label = $(this).next("label");

        $.ajax({
            url: "'.htmlspecialchars(\App\Core\UrlHelper::url('/panel/roles/toggle-status')).'",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                id: roleId,
                status: newStatus
            }),
            success: function(response) {
                if (response.success) {
                    label.text(newStatus ? "Activo" : "Inactivo");
                    toastr.success(
                        newStatus ? "Rol activado exitosamente" : "Rol desactivado exitosamente",
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

function confirmDelete(roleId, roleName) {
    $("#deleteRoleName").text(roleName);
    $("#deleteForm").attr("action", "'.htmlspecialchars(\App\Core\UrlHelper::url('/panel/roles')).'/\" + roleId + \"/delete\");
    $("#deleteModal").modal("show");
}

function confirmClone(roleId, roleName) {
    $("#cloneRoleName").text(roleName);
    $("#cloneForm").attr("action", "'.htmlspecialchars(\App\Core\UrlHelper::url('/panel/roles')).'/\" + roleId + \"/clone\");
    $("#cloneModal").modal("show");
}
</script>';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-shield"></i> Gestión de Roles y Permisos
                </h3>
                <div class="card-tools">
                    <a href="<?= \App\Core\UrlHelper::url('/panel/roles/create') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Rol
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="rolesTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Permisos</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles ?? [] as $role): ?>
                            <tr>
                                <td><?= $role['id'] ?? 0 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($role['name'] ?? '') ?></strong>
                                    <?php if (!empty($role['is_admin'])): ?>
                                        <span class="badge badge-warning ml-1">
                                            <i class="fas fa-crown"></i> Admin
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= !empty($role['description']) ? htmlspecialchars($role['description']) : '<em class="text-muted">Sin descripción</em>' ?>
                                </td>
                                <td>
                                    <div class="progress" style="width: 100px;">
                                        <?php 
                                        $totalModules = count($modules ?? []);
                                        $roleModules = count($role['permissions'] ?? []);
                                        $percentage = $totalModules > 0 ? round(($roleModules / $totalModules) * 100) : 0;
                                        ?>
                                        <div class="progress-bar <?= $percentage >= 70 ? 'bg-success' : ($percentage >= 40 ? 'bg-warning' : 'bg-danger') ?>" 
                                             style="width: <?= $percentage ?>%">
                                            <?= $percentage ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted"><?= $roleModules ?>/<?= $totalModules ?> módulos</small>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" 
                                               class="custom-control-input status-toggle" 
                                               id="status_<?= $role['id'] ?? 0 ?>"
                                               data-id="<?= $role['id'] ?? 0 ?>"
                                               <?= !empty($role['status']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="status_<?= $role['id'] ?? 0 ?>">
                                            <?= !empty($role['status']) ? 'Activo' : 'Inactivo' ?>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= \App\Core\UrlHelper::url('/panel/roles/'.($role['id'] ?? 0)) ?>" 
                                           class="btn btn-info btn-sm" 
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= \App\Core\UrlHelper::url('/panel/roles/'.($role['id'] ?? 0).'/edit') ?>" 
                                           class="btn btn-warning btn-sm" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-success btn-sm" 
                                                onclick="confirmClone(<?= $role['id'] ?? 0 ?>, '<?= htmlspecialchars($role['name'] ?? '') ?>')"
                                                title="Clonar rol">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm" 
                                                onclick="confirmDelete(<?= $role['id'] ?? 0 ?>, '<?= htmlspecialchars($role['name'] ?? '') ?>')"
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
                            Total: <strong><?= count($roles ?? []) ?></strong> roles
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
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el rol <strong id="deleteRoleName"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    <strong>Nota:</strong> Esta acción eliminará el rol y todos sus permisos asociados.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <form id="deleteForm" method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Eliminar Rol
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de clonación -->
<div class="modal fade" id="cloneModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">
                    <i class="fas fa-copy"></i> Clonar Rol
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="cloneForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                <div class="modal-body">
                    <p>Crear una copia del rol <strong id="cloneRoleName"></strong>:</p>
                    <div class="form-group">
                        <label for="new_name">Nombre del nuevo rol:</label>
                        <input type="text" class="form-control" id="new_name" name="new_name" required>
                    </div>
                    <div class="form-group">
                        <label for="new_description">Descripción (opcional):</label>
                        <textarea class="form-control" id="new_description" name="new_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-copy"></i> Clonar Rol
                    </button>
                </div>
            </form>
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

.progress {
    height: 1.5rem;
}

.badge {
    font-size: 0.8em;
}

.table td {
    vertical-align: middle;
}
</style>