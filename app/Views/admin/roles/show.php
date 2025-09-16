<?php
$page_title = $title ?? 'Detalles del Rol';
$scripts = '
<script>
$(document).ready(function() {
    // Mostrar/ocultar detalles de módulos
    $(".toggle-module-details").on("click", function() {
        const moduleId = $(this).data("module-id");
        $("#module-details-" + moduleId).slideToggle();
        const icon = $(this).find("i.fa-chevron-down, i.fa-chevron-up");
        icon.toggleClass("fa-chevron-down fa-chevron-up");
    });
    
    // Filtros de permisos
    $("#filterRead").change(function() {
        filterPermissions();
    });
    
    $("#filterWrite").change(function() {
        filterPermissions();
    });
    
    $("#filterDelete").change(function() {
        filterPermissions();
    });
    
    function filterPermissions() {
        const showRead = $("#filterRead").is(":checked");
        const showWrite = $("#filterWrite").is(":checked");
        const showDelete = $("#filterDelete").is(":checked");
        
        $(".module-row").each(function() {
            const hasRead = $(this).find(".perm-read").length > 0;
            const hasWrite = $(this).find(".perm-write").length > 0;
            const hasDelete = $(this).find(".perm-delete").length > 0;
            
            let show = true;
            
            if (showRead && !hasRead) show = false;
            if (showWrite && !hasWrite) show = false;
            if (showDelete && !hasDelete) show = false;
            
            if (show) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
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

function exportPermissions() {
    const roleData = {
        id: '.($role['id'] ?? 0).',
        name: "'.htmlspecialchars($role['name'] ?? '').'",
        permissions: []
    };
    
    $(".module-row").each(function() {
        const moduleId = $(this).data("module-id");
        const moduleName = $(this).find(".module-name").text();
        const hasRead = $(this).find(".perm-read").length > 0;
        const hasWrite = $(this).find(".perm-write").length > 0;
        const hasDelete = $(this).find(".perm-delete").length > 0;
        
        if (hasRead || hasWrite || hasDelete) {
            roleData.permissions.push({
                module_id: moduleId,
                module_name: moduleName,
                read: hasRead,
                write: hasWrite,
                delete: hasDelete
            });
        }
    });
    
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(roleData, null, 2));
    const downloadAnchorNode = document.createElement("a");
    downloadAnchorNode.setAttribute("href", dataStr);
    downloadAnchorNode.setAttribute("download", "rol_" + roleData.name.toLowerCase().replace(/\s+/g, "_") + "_permisos.json");
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
    
    toastr.success("Configuración de permisos exportada exitosamente", "Exportación Completa");
}
</script>';
?>

<div class="row">
    <div class="col-12">
        <div class="row">
            <!-- Información Principal del Rol -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <div class="profile-user-img img-fluid img-circle bg-primary d-flex align-items-center justify-content-center mx-auto" 
                                 style="width: 128px; height: 128px;">
                                <i class="fas fa-user-shield text-white fa-3x"></i>
                            </div>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($role['name'] ?? '') ?>
                            <?php if (!empty($role['is_admin'])): ?>
                                <br><span class="badge badge-warning mt-1">
                                    <i class="fas fa-crown"></i> Administrador
                                </span>
                            <?php endif; ?>
                        </h3>

                        <p class="text-muted text-center">
                            <?= !empty($role['description']) ? htmlspecialchars($role['description']) : '<em>Sin descripción</em>' ?>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-key mr-1"></i> ID</b>
                                <span class="float-right">
                                    <code>#<?= $role['id'] ?? 0 ?></code>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-toggle-on mr-1"></i> Estado</b>
                                <span class="float-right">
                                    <?php if (!empty($role['status'])): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-shield-alt mr-1"></i> Módulos</b>
                                <span class="float-right">
                                    <span class="badge badge-info">
                                        <?= count($role['permissions'] ?? []) ?> de <?= count($modules ?? []) ?>
                                    </span>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-users mr-1"></i> Usuarios Asignados</b>
                                <span class="float-right">
                                    <span class="badge badge-<?= ($role['users_count'] ?? 0) > 0 ? 'primary' : 'secondary' ?>">
                                        <?= $role['users_count'] ?? 0 ?>
                                    </span>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-calendar mr-1"></i> Creado</b>
                                <span class="float-right">
                                    <small><?= date('d/m/Y', strtotime($role['created_at'] ?? 'now')) ?></small>
                                </span>
                            </li>
                        </ul>

                        <div class="row">
                            <div class="col-6">
                                <a href="<?= \App\Core\UrlHelper::url('/panel/roles/'.($role['id'] ?? 0).'/edit') ?>" class="btn btn-warning btn-block">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-success btn-block" onclick="confirmClone(<?= $role['id'] ?? 0 ?>, '<?= htmlspecialchars($role['name'] ?? '') ?>')">
                                    <i class="fas fa-copy"></i> Clonar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Matriz de Permisos -->
            <div class="col-md-8">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-shield-alt"></i> Matriz de Permisos
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-primary btn-sm" onclick="exportPermissions()">
                                <i class="fas fa-download"></i> Exportar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filtros de permisos -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="btn-toolbar" role="toolbar">
                                    <div class="btn-group btn-group-toggle mr-2" data-toggle="buttons">
                                        <label class="btn btn-outline-info btn-sm">
                                            <input type="checkbox" id="filterRead"> <i class="fas fa-eye"></i> Solo Lectura
                                        </label>
                                        <label class="btn btn-outline-warning btn-sm">
                                            <input type="checkbox" id="filterWrite"> <i class="fas fa-edit"></i> Solo Escritura
                                        </label>
                                        <label class="btn btn-outline-danger btn-sm">
                                            <input type="checkbox" id="filterDelete"> <i class="fas fa-trash"></i> Solo Eliminación
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($role['permissions'])): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 50%;">Módulo</th>
                                            <th style="width: 16%;" class="text-center">
                                                <i class="fas fa-eye text-info"></i> Lectura
                                            </th>
                                            <th style="width: 17%;" class="text-center">
                                                <i class="fas fa-edit text-warning"></i> Escritura
                                            </th>
                                            <th style="width: 17%;" class="text-center">
                                                <i class="fas fa-trash text-danger"></i> Eliminación
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modules as $moduleId => $module): ?>
                                            <?php if (isset($role['permissions'][$moduleId])): ?>
                                                <?php $perms = $role['permissions'][$moduleId]; ?>
                                                <tr class="module-row" data-module-id="<?= $moduleId ?>">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="<?= htmlspecialchars($module['icon']) ?> text-primary mr-2"></i>
                                                            <div>
                                                                <strong class="module-name"><?= htmlspecialchars($module['name']) ?></strong>
                                                                <?php if (!empty($module['description'])): ?>
                                                                    <br><small class="text-muted"><?= htmlspecialchars($module['description']) ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if (!empty($perms['read'])): ?>
                                                            <span class="badge badge-success perm-read">
                                                                <i class="fas fa-check"></i> Permitido
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">
                                                                <i class="fas fa-times"></i> Denegado
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if (!empty($perms['write'])): ?>
                                                            <span class="badge badge-success perm-write">
                                                                <i class="fas fa-check"></i> Permitido
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">
                                                                <i class="fas fa-times"></i> Denegado
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if (!empty($perms['delete'])): ?>
                                                            <span class="badge badge-success perm-delete">
                                                                <i class="fas fa-check"></i> Permitido
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">
                                                                <i class="fas fa-times"></i> Denegado
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle"></i> Sin Permisos</h5>
                                <p class="mb-0">
                                    Este rol no tiene permisos asignados. 
                                    <a href="<?= \App\Core\UrlHelper::url('/panel/roles/'.($role['id'] ?? 0).'/edit') ?>" class="alert-link">
                                        Haga clic aquí para configurar permisos
                                    </a>.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Estadísticas Detalladas -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie"></i> Estadísticas Detalladas
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $totalModules = count($modules);
                            $permittedModules = count($role['permissions'] ?? []);
                            $readPerms = 0;
                            $writePerms = 0;
                            $deletePerms = 0;
                            
                            foreach ($role['permissions'] ?? [] as $perms) {
                                if (!empty($perms['read'])) $readPerms++;
                                if (!empty($perms['write'])) $writePerms++;
                                if (!empty($perms['delete'])) $deletePerms++;
                            }
                            
                            $coveragePercentage = $totalModules > 0 ? round(($permittedModules / $totalModules) * 100) : 0;
                            ?>
                            
                            <div class="col-md-3">
                                <div class="info-box bg-info">
                                    <span class="info-box-icon"><i class="fas fa-th-large"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Cobertura</span>
                                        <span class="info-box-number"><?= $coveragePercentage ?>%</span>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?= $coveragePercentage ?>%"></div>
                                        </div>
                                        <span class="progress-description">
                                            <?= $permittedModules ?> de <?= $totalModules ?> módulos
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon"><i class="fas fa-eye"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Permisos de Lectura</span>
                                        <span class="info-box-number"><?= $readPerms ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="info-box bg-warning">
                                    <span class="info-box-icon"><i class="fas fa-edit"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Permisos de Escritura</span>
                                        <span class="info-box-number"><?= $writePerms ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="info-box bg-danger">
                                    <span class="info-box-icon"><i class="fas fa-trash"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Permisos de Eliminación</span>
                                        <span class="info-box-number"><?= $deletePerms ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación y Acciones -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <a href="<?= \App\Core\UrlHelper::url('/panel/roles') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a la Lista
                            </a>
                            <a href="<?= \App\Core\UrlHelper::url('/panel/roles/'.($role['id'] ?? 0).'/edit') ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar Rol
                            </a>
                            <button type="button" class="btn btn-success" onclick="confirmClone(<?= $role['id'] ?? 0 ?>, '<?= htmlspecialchars($role['name'] ?? '') ?>')">
                                <i class="fas fa-copy"></i> Clonar Rol
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $role['id'] ?? 0 ?>, '<?= htmlspecialchars($role['name'] ?? '') ?>')">
                                <i class="fas fa-trash"></i> Eliminar Rol
                            </button>
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
.profile-user-img {
    width: 128px;
    height: 128px;
    object-fit: cover;
}

.badge {
    font-size: 0.8em;
}

.info-box {
    margin-bottom: 1rem;
}

.card-outline {
    border-top: 3px solid;
}

code {
    color: #e83e8c;
}

.list-group-item {
    border: 1px solid rgba(0,0,0,.125);
}

.btn-toolbar .btn-group {
    margin-bottom: 0.5rem;
}

.table td {
    vertical-align: middle;
}

.progress {
    height: 1.2rem;
}

.info-box-content {
    padding: 5px 10px;
}

.description-block {
    text-align: center;
}

.btn-group .btn {
    margin-right: 5px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}
</style>