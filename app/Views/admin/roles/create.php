<?php
$page_title = $title ?? 'Crear Rol';
$scripts = '
<script>
$(document).ready(function() {
    // Validación del formulario
    $("#createRoleForm").on("submit", function(e) {
        const name = $("#name").val().trim();
        const description = $("#description").val().trim();
        
        if (name.length < 3) {
            e.preventDefault();
            toastr.error("El nombre del rol debe tener al menos 3 caracteres", "Error de Validación");
            $("#name").focus();
            return false;
        }
        
        if (name.length > 50) {
            e.preventDefault();
            toastr.error("El nombre del rol no puede exceder 50 caracteres", "Error de Validación");
            $("#name").focus();
            return false;
        }
        
        if (description.length > 255) {
            e.preventDefault();
            toastr.error("La descripción no puede exceder 255 caracteres", "Error de Validación");
            $("#description").focus();
            return false;
        }
        
        // Verificar que al menos un permiso esté seleccionado
        if ($(".perm-checkbox:checked").length === 0) {
            e.preventDefault();
            toastr.error("Debe seleccionar al menos un permiso para el rol", "Error de Validación");
            return false;
        }
        
        return true;
    });
    
    // Manejar "Seleccionar todos los permisos" (global)
    $("#select-all-permissions").change(function() {
        const isChecked = $(this).is(":checked");
        $(".select-all, .perm-checkbox, #select-all-read, #select-all-write, #select-all-delete").prop("checked", isChecked);
    });

    // Manejar "Seleccionar todos" por módulo
    $(".select-all").change(function() {
        const moduleId = $(this).data("module-id");
        const isChecked = $(this).is(":checked");
        $(".perm-checkbox[data-module-id=\"" + moduleId + "\"]").prop("checked", isChecked);
        updateTypeCheckboxes();
        updateGlobalCheckbox();
    });

    // Manejar checkboxes por tipo de permiso
    $("#select-all-read").change(function() {
        const isChecked = $(this).is(":checked");
        $(".perm-read").prop("checked", isChecked);
        updateModuleCheckboxes();
        updateGlobalCheckbox();
    });

    $("#select-all-write").change(function() {
        const isChecked = $(this).is(":checked");
        $(".perm-write").prop("checked", isChecked);
        updateModuleCheckboxes();
        updateGlobalCheckbox();
    });

    $("#select-all-delete").change(function() {
        const isChecked = $(this).is(":checked");
        $(".perm-delete").prop("checked", isChecked);
        updateModuleCheckboxes();
        updateGlobalCheckbox();
    });

    // Actualizar estado cuando cambian permisos individuales
    $(".perm-checkbox").change(function() {
        const moduleId = $(this).data("module-id");
        const moduleCheckboxes = $(".perm-checkbox[data-module-id=\"" + moduleId + "\"]");
        const checkedCount = moduleCheckboxes.filter(":checked").length;
        const allChecked = checkedCount === moduleCheckboxes.length;
        
        $(".select-all[data-module-id=\"" + moduleId + "\"]").prop("checked", allChecked);
        updateTypeCheckboxes();
        updateGlobalCheckbox();
    });

    function updateTypeCheckboxes() {
        const allReadChecked = $(".perm-read").length === $(".perm-read:checked").length;
        $("#select-all-read").prop("checked", allReadChecked);
        
        const allWriteChecked = $(".perm-write").length === $(".perm-write:checked").length;
        $("#select-all-write").prop("checked", allWriteChecked);
        
        const allDeleteChecked = $(".perm-delete").length === $(".perm-delete:checked").length;
        $("#select-all-delete").prop("checked", allDeleteChecked);
    }

    function updateModuleCheckboxes() {
        $(".select-all").each(function() {
            const moduleId = $(this).data("module-id");
            const moduleCheckboxes = $(".perm-checkbox[data-module-id=\"" + moduleId + "\"]");
            const checkedCount = moduleCheckboxes.filter(":checked").length;
            const allChecked = checkedCount === moduleCheckboxes.length;
            $(this).prop("checked", allChecked);
        });
    }

    function updateGlobalCheckbox() {
        const allPermissionsChecked = $(".perm-checkbox").length === $(".perm-checkbox:checked").length;
        $("#select-all-permissions").prop("checked", allPermissionsChecked);
    }
    
    // Contador de caracteres
    $("#name").on("input", function() {
        const length = $(this).val().length;
        $("#nameCounter").text(length + "/50");
        if (length > 50) {
            $(this).addClass("is-invalid");
        } else {
            $(this).removeClass("is-invalid");
        }
    });
    
    $("#description").on("input", function() {
        const length = $(this).val().length;
        $("#descriptionCounter").text(length + "/255");
        if (length > 255) {
            $(this).addClass("is-invalid");
        } else {
            $(this).removeClass("is-invalid");
        }
    });
});

function resetForm() {
    $("#createRoleForm")[0].reset();
    $(".is-invalid").removeClass("is-invalid");
    $("#nameCounter").text("0/50");
    $("#descriptionCounter").text("0/255");
}
</script>';
?>

<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-shield"></i> Crear Nuevo Rol
                </h3>
            </div>
            
            <form id="createRoleForm" action="<?= \App\Core\UrlHelper::url('/panel/roles/store') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                
                <div class="card-body">
                    <div class="row">
                        <!-- Información básica del rol -->
                        <div class="col-md-4">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-info-circle"></i> Información del Rol
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="name">
                                            Nombre del Rol <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="name" 
                                               name="name" 
                                               placeholder="Ej: Administrador, Supervisor, etc."
                                               required
                                               maxlength="50">
                                        <small class="form-text text-muted">
                                            <span id="nameCounter">0/50</span> caracteres
                                        </small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description">Descripción</label>
                                        <textarea class="form-control" 
                                                  id="description" 
                                                  name="description" 
                                                  rows="4"
                                                  placeholder="Describe las responsabilidades de este rol..."
                                                  maxlength="255"></textarea>
                                        <small class="form-text text-muted">
                                            <span id="descriptionCounter">0/255</span> caracteres
                                        </small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="status" 
                                                   name="status" 
                                                   checked>
                                            <label class="custom-control-label" for="status">
                                                Rol activo
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">
                                            Los roles inactivos no pueden ser asignados a usuarios.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel de ayuda -->
                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-lightbulb"></i> Ayuda
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <h6>Tipos de Permisos</h6>
                                    <ul class="list-unstyled">
                                        <li>
                                            <i class="fas fa-eye text-info"></i> <strong>Lectura</strong>
                                            <br><small>Ver información del módulo</small>
                                        </li>
                                        <li class="mt-2">
                                            <i class="fas fa-edit text-warning"></i> <strong>Escritura</strong>
                                            <br><small>Crear y editar registros</small>
                                        </li>
                                        <li class="mt-2">
                                            <i class="fas fa-trash text-danger"></i> <strong>Eliminación</strong>
                                            <br><small>Eliminar registros</small>
                                        </li>
                                    </ul>
                                    
                                    <hr>
                                    
                                    <h6>Consejos</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success"></i> Use nombres descriptivos</li>
                                        <li><i class="fas fa-check text-success"></i> Otorgue solo permisos necesarios</li>
                                        <li><i class="fas fa-check text-success"></i> Documente el propósito del rol</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Matriz de permisos -->
                        <div class="col-md-8">
                            <div class="card card-warning">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-shield-alt"></i> Matriz de Permisos
                                    </h3>
                                    <div class="card-tools">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="select-all-permissions">
                                            <label class="custom-control-label" for="select-all-permissions">
                                                <strong>Seleccionar todos</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width: 40%;">
                                                        <i class="fas fa-th-list"></i> Módulo
                                                    </th>
                                                    <th style="width: 15%;" class="text-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="select-all-module">
                                                            <label class="custom-control-label" for="select-all-module">Todos</label>
                                                        </div>
                                                    </th>
                                                    <th style="width: 15%;" class="text-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="select-all-read">
                                                            <label class="custom-control-label" for="select-all-read">
                                                                <i class="fas fa-eye text-info"></i> Lectura
                                                            </label>
                                                        </div>
                                                    </th>
                                                    <th style="width: 15%;" class="text-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="select-all-write">
                                                            <label class="custom-control-label" for="select-all-write">
                                                                <i class="fas fa-edit text-warning"></i> Escritura
                                                            </label>
                                                        </div>
                                                    </th>
                                                    <th style="width: 15%;" class="text-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="select-all-delete">
                                                            <label class="custom-control-label" for="select-all-delete">
                                                                <i class="fas fa-trash text-danger"></i> Eliminación
                                                            </label>
                                                        </div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($modules as $moduleId => $module): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="<?= htmlspecialchars($module['icon']) ?> text-primary mr-2"></i>
                                                            <div>
                                                                <strong><?= htmlspecialchars($module['name']) ?></strong>
                                                                <?php if (!empty($module['description'])): ?>
                                                                    <br><small class="text-muted"><?= htmlspecialchars($module['description']) ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" 
                                                                   class="custom-control-input select-all" 
                                                                   id="select_all_<?= $moduleId ?>"
                                                                   data-module-id="<?= $moduleId ?>">
                                                            <label class="custom-control-label" for="select_all_<?= $moduleId ?>"></label>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" 
                                                                   class="custom-control-input perm-checkbox perm-read" 
                                                                   id="read_<?= $moduleId ?>"
                                                                   name="permissions[<?= $moduleId ?>][read]" 
                                                                   value="1"
                                                                   data-module-id="<?= $moduleId ?>">
                                                            <label class="custom-control-label" for="read_<?= $moduleId ?>"></label>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" 
                                                                   class="custom-control-input perm-checkbox perm-write" 
                                                                   id="write_<?= $moduleId ?>"
                                                                   name="permissions[<?= $moduleId ?>][write]" 
                                                                   value="1"
                                                                   data-module-id="<?= $moduleId ?>">
                                                            <label class="custom-control-label" for="write_<?= $moduleId ?>"></label>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" 
                                                                   class="custom-control-input perm-checkbox perm-delete" 
                                                                   id="delete_<?= $moduleId ?>"
                                                                   name="permissions[<?= $moduleId ?>][delete]" 
                                                                   value="1"
                                                                   data-module-id="<?= $moduleId ?>">
                                                            <label class="custom-control-label" for="delete_<?= $moduleId ?>"></label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Seleccione los permisos que tendrá este rol en cada módulo del sistema.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Crear Rol
                            </button>
                            <button type="button" class="btn btn-secondary ml-2" onclick="resetForm()">
                                <i class="fas fa-undo"></i> Limpiar
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="<?= \App\Core\UrlHelper::url('/panel/roles') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a la Lista
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.is-invalid {
    border-color: #dc3545;
}

.table th {
    border-top: none;
    font-weight: 600;
}

.custom-control-label {
    cursor: pointer;
}

.card-body .table {
    margin-bottom: 0;
}

.table td {
    vertical-align: middle;
}

.table-responsive {
    max-height: 500px;
    overflow-y: auto;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.card .card-header .card-tools {
    margin-left: auto;
}
</style>