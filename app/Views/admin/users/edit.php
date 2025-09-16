<?php
$page_title = $title ?? 'Editar Usuario';
$scripts = '
<script>
$(document).ready(function() {
    $(".custom-file-input").on("change", function() {
        const fileName = $(this)[0].files[0]?.name || "Seleccionar archivo...";
        $(this).next(".custom-file-label").text(fileName);
    });

    $("#editUserForm").on("submit", function(e) {
        const password = $("#password").val();
        const confirmPassword = $("#confirm_password").val();

        if (password && password !== confirmPassword) {
            e.preventDefault();
            toastr.error("Las contraseñas no coinciden", "Error de Validación");
            return false;
        }

        if (password && password.length < 6) {
            e.preventDefault();
            toastr.error("La contraseña debe tener al menos 6 caracteres", "Error de Validación");
            return false;
        }

        const username = $("#username").val();
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            e.preventDefault();
            toastr.error("El username solo puede contener letras, números y guión bajo", "Error de Validación");
            return false;
        }
    });

    $("#confirm_password").on("keyup", function() {
        const password = $("#password").val();
        const confirmPassword = $(this).val();
        
        if (confirmPassword && password !== confirmPassword) {
            $(this).addClass("is-invalid");
        } else {
            $(this).removeClass("is-invalid");
        }
    });
});

function togglePassword(fieldId) {
    const field = $("#" + fieldId);
    const button = field.next(".input-group-append").find("button");
    const icon = button.find("i");
    
    if (field.attr("type") === "password") {
        field.attr("type", "text");
        icon.removeClass("fa-eye").addClass("fa-eye-slash");
    } else {
        field.attr("type", "password");
        icon.removeClass("fa-eye-slash").addClass("fa-eye");
    }
}

function confirmResetPassword() {
    $("#resetPasswordModal").modal("show");
}
</script>';
?>

<div class="row">
    <div class="col-12">
        <!-- Mensajes de alerta -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Formulario principal -->
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-edit"></i> Editar Usuario: <?= htmlspecialchars($user['username']) ?>
                        </h3>
                    </div>
                    
                    <form id="editUserForm" action="<?= \App\Core\UrlHelper::url('/panel/users/'.$user['id'].'/update') ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                        
                        <div class="card-body">
                            <div class="row">
                                <!-- Username -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">
                                            Nombre de Usuario <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            </div>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="username" 
                                                   name="username" 
                                                   placeholder="Nombre de usuario único"
                                                   value="<?= htmlspecialchars($user['username']) ?>"
                                                   required
                                                   pattern="[a-zA-Z0-9_]+"
                                                   title="Solo letras, números y guión bajo">
                                        </div>
                                        <small class="form-text text-muted">
                                            Solo letras, números y guión bajo. Mínimo 3 caracteres.
                                        </small>
                                    </div>
                                </div>

                                <!-- Rol -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role_id">
                                            Rol <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                                            </div>
                                            <select class="form-control" id="role_id" name="role_id" required>
                                                <option value="">Seleccione un rol...</option>
                                                <?php foreach ($roles ?? [] as $role): ?>
                                                    <option value="<?= $role['id'] ?>" <?= $user['role_id'] == $role['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($role['name']) ?>
                                                        <?php if (!empty($role['description'])): ?>
                                                            - <?= htmlspecialchars($role['description']) ?>
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Nombre -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="firstname">
                                            Nombre <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                            </div>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="firstname" 
                                                   name="firstname" 
                                                   placeholder="Nombre"
                                                   value="<?= htmlspecialchars($user['firstname']) ?>"
                                                   required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Apellido -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lastname">
                                            Apellido <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                            </div>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="lastname" 
                                                   name="lastname" 
                                                   placeholder="Apellido"
                                                   value="<?= htmlspecialchars($user['lastname']) ?>"
                                                   required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Cambiar Contraseña</h5>
                                <p class="mb-0">Deje los campos de contraseña vacíos si no desea cambiar la contraseña actual.</p>
                            </div>

                            <div class="row">
                                <!-- Contraseña -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Nueva Contraseña</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            </div>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="password" 
                                                   name="password" 
                                                   placeholder="Nueva contraseña (opcional)"
                                                   minlength="6">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            Opcional. Si se proporciona, mínimo 6 caracteres.
                                        </small>
                                    </div>
                                </div>

                                <!-- Confirmar Contraseña -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password">Confirmar Nueva Contraseña</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            </div>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="confirm_password" 
                                                   name="confirm_password" 
                                                   placeholder="Confirmar nueva contraseña"
                                                   minlength="6">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado -->
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="status" 
                                           name="status" 
                                           <?= $user['status'] ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="status">
                                        Usuario activo
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Los usuarios inactivos no pueden iniciar sesión en el sistema.
                                </small>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-save"></i> Actualizar Usuario
                                    </button>
                                    <button type="button" class="btn btn-info ml-2" onclick="confirmResetPassword()">
                                        <i class="fas fa-key"></i> Reset Password
                                    </button>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="<?= \App\Core\UrlHelper::url('/panel/users') ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> Volver a la Lista
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Información del usuario -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-circle"></i> Información del Usuario
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <?php if (!empty($user['photo'])): ?>
                                <img src="<?= htmlspecialchars($user['photo']) ?>" 
                                     alt="Foto de <?= htmlspecialchars($user['firstname']) ?>" 
                                     class="img-circle img-fluid" 
                                     style="max-width: 100px;">
                            <?php else: ?>
                                <div class="img-circle bg-secondary d-inline-flex align-items-center justify-content-center" 
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-user text-white fa-2x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <dl class="row">
                            <dt class="col-sm-5">ID:</dt>
                            <dd class="col-sm-7"><?= $user['id'] ?></dd>
                            
                            <dt class="col-sm-5">Username:</dt>
                            <dd class="col-sm-7"><code><?= htmlspecialchars($user['username']) ?></code></dd>
                            
                            <dt class="col-sm-5">Nombre:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></dd>
                            
                            <dt class="col-sm-5">Rol:</dt>
                            <dd class="col-sm-7">
                                <?php if (!empty($user['role_name'])): ?>
                                    <span class="badge badge-info">
                                        <?= htmlspecialchars($user['role_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Sin rol</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-5">Estado:</dt>
                            <dd class="col-sm-7">
                                <?php if ($user['status']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactivo</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-5">Creado:</dt>
                            <dd class="col-sm-7">
                                <small><?= date('d/m/Y', strtotime($user['created_on'])) ?></small>
                            </dd>
                        </dl>
                    </div>
                </div>

                <!-- Acciones rápidas -->
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-tools"></i> Acciones Rápidas
                        </h3>
                    </div>
                    <div class="card-body">
                        <a href="<?= \App\Core\UrlHelper::url('/panel/users/'.$user['id']) ?>" class="btn btn-info btn-block">
                            <i class="fas fa-eye"></i> Ver Detalles Completos
                        </a>
                        <button type="button" class="btn btn-danger btn-block" onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                            <i class="fas fa-trash"></i> Eliminar Usuario
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white">
                    <i class="fas fa-key"></i> Reset Password
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= \App\Core\UrlHelper::url('/panel/users/'.$user['id'].'/reset-password') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="new_password">Nueva Contraseña:</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Esta acción enviará una nueva contraseña al usuario.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Resetear Password
                    </button>
                </div>
            </form>
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
                    <strong>Nota:</strong> Esta acción marcará al usuario como inactivo.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
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
.is-invalid {
    border-color: #dc3545;
}

.input-group .btn {
    height: calc(2.25rem + 2px);
}

.img-circle {
    border-radius: 50%;
}

dt {
    font-weight: 600;
}

code {
    color: #e83e8c;
}
</style>