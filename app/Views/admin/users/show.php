<?php
$page_title = $title ?? 'Detalles del Usuario';
$scripts = '
<script>
$(document).ready(function() {
    // Mostrar/ocultar permisos
    $(".toggle-permissions").on("click", function() {
        const moduleId = $(this).data("module");
        $("#permissions-" + moduleId).slideToggle();
        const icon = $(this).find("i");
        icon.toggleClass("fa-chevron-down fa-chevron-up");
    });
});

function confirmDelete(userId, username) {
    $("#deleteUsername").text(username);
    $("#deleteForm").attr("action", "'.htmlspecialchars(\App\Core\UrlHelper::url('/panel/users')).'/" + userId + "/delete");
    $("#deleteModal").modal("show");
}
</script>';
?>

<div class="row">
    <div class="col-12">
        <div class="row">
            <!-- Información Principal -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <?php if (!empty($user['photo'])): ?>
                                <img src="<?= htmlspecialchars($user['photo']) ?>" 
                                     alt="Foto de <?= htmlspecialchars($user['firstname']) ?>" 
                                     class="profile-user-img img-fluid img-circle">
                            <?php else: ?>
                                <div class="profile-user-img img-fluid img-circle bg-secondary d-flex align-items-center justify-content-center" 
                                     style="width: 128px; height: 128px; margin: 0 auto;">
                                    <i class="fas fa-user text-white fa-3x"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?>
                        </h3>

                        <p class="text-muted text-center">
                            <code>@<?= htmlspecialchars($user['username']) ?></code>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-user-shield mr-1"></i> Rol</b>
                                <span class="float-right">
                                    <?php if (!empty($role['name'])): ?>
                                        <span class="badge badge-info">
                                            <?= htmlspecialchars($role['name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Sin rol</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-toggle-on mr-1"></i> Estado</b>
                                <span class="float-right">
                                    <?php if ($user['status']): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-calendar mr-1"></i> Miembro desde</b>
                                <span class="float-right">
                                    <?= date('d/m/Y', strtotime($user['created_on'])) ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-key mr-1"></i> ID</b>
                                <span class="float-right">
                                    <code>#<?= $user['id'] ?></code>
                                </span>
                            </li>
                        </ul>

                        <div class="row">
                            <div class="col-6">
                                <a href="<?= \App\Core\UrlHelper::url('/panel/users/'.$user['id'].'/edit') ?>" class="btn btn-warning btn-block">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-danger btn-block" onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Rol y Permisos -->
            <div class="col-md-8">
                <?php if (!empty($role)): ?>
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-shield"></i> Información del Rol
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">Nombre:</dt>
                                        <dd class="col-sm-7">
                                            <span class="badge badge-info badge-lg">
                                                <?= htmlspecialchars($role['name']) ?>
                                            </span>
                                        </dd>
                                        
                                        <dt class="col-sm-5">Descripción:</dt>
                                        <dd class="col-sm-7">
                                            <?= !empty($role['description']) ? htmlspecialchars($role['description']) : '<em class="text-muted">Sin descripción</em>' ?>
                                        </dd>
                                        
                                        <dt class="col-sm-5">Estado del Rol:</dt>
                                        <dd class="col-sm-7">
                                            <?php if ($role['status']): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-info">
                                        <span class="info-box-icon"><i class="fas fa-shield-alt"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Permisos Asignados</span>
                                            <span class="info-box-number"><?= count($permissions ?? []) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Permisos Detallados -->
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list-check"></i> Permisos del Usuario
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($permissions)): ?>
                            <div class="row">
                                <?php foreach ($modules as $moduleId => $moduleInfo): ?>
                                    <?php if (isset($permissions[$moduleId])): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card card-outline card-primary">
                                                <div class="card-header">
                                                    <button class="btn btn-sm btn-link p-0 toggle-permissions" 
                                                            data-module="<?= $moduleId ?>"
                                                            style="text-decoration: none;">
                                                        <h6 class="mb-0">
                                                            <i class="<?= htmlspecialchars($moduleInfo['icon']) ?> mr-2"></i>
                                                            <?= htmlspecialchars($moduleInfo['name']) ?>
                                                            <i class="fas fa-chevron-down float-right mt-1"></i>
                                                        </h6>
                                                    </button>
                                                </div>
                                                <div class="card-body p-2" id="permissions-<?= $moduleId ?>" style="display: none;">
                                                    <?php 
                                                    $modulePermissions = $permissions[$moduleId];
                                                    $permissionLabels = ['read' => 'Leer', 'write' => 'Escribir', 'delete' => 'Eliminar'];
                                                    ?>
                                                    
                                                    <div class="row">
                                                        <?php foreach ($permissionLabels as $perm => $label): ?>
                                                            <div class="col-4">
                                                                <?php if ($modulePermissions[$perm]): ?>
                                                                    <span class="badge badge-success">
                                                                        <i class="fas fa-check"></i> <?= $label ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-secondary">
                                                                        <i class="fas fa-times"></i> <?= $label ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle"></i> Sin Permisos</h5>
                                <p class="mb-0">
                                    Este usuario no tiene permisos asignados. 
                                    Esto puede deberse a que no tiene un rol asignado o su rol no tiene permisos configurados.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Estadísticas y Actividad -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i> Resumen de Actividad
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon"><i class="fas fa-calendar-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Días como Usuario</span>
                                        <span class="info-box-number">
                                            <?= ceil((time() - strtotime($user['created_on'])) / (60*60*24)) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-info">
                                    <span class="info-box-icon"><i class="fas fa-shield-alt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Módulos con Acceso</span>
                                        <span class="info-box-number">
                                            <?= count($permissions ?? []) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <a href="<?= \App\Core\UrlHelper::url('/panel/users') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a la Lista
                            </a>
                            <a href="<?= \App\Core\UrlHelper::url('/panel/users/'.$user['id'].'/edit') ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar Usuario
                            </a>
                            <?php if (!empty($role)): ?>
                                <a href="<?= \App\Core\UrlHelper::url('/panel/roles/'.$role['id']) ?>" class="btn btn-info">
                                    <i class="fas fa-user-shield"></i> Ver Rol
                                </a>
                            <?php endif; ?>
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
.profile-user-img {
    width: 128px;
    height: 128px;
    object-fit: cover;
}

.badge-lg {
    font-size: 1em;
    padding: 0.5rem 0.75rem;
}

.info-box {
    margin-bottom: 1rem;
}

.card-outline {
    border-top: 3px solid;
}

.toggle-permissions {
    width: 100%;
    text-align: left;
}

code {
    color: #e83e8c;
}

dt {
    font-weight: 600;
}

.list-group-item {
    border: 1px solid rgba(0,0,0,.125);
}

.card-header .btn-link {
    color: inherit;
}

.card-header .btn-link:hover {
    color: inherit;
    text-decoration: none;
}
</style>