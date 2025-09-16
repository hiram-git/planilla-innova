<?php
$page_title = $title ?? 'Crear Usuario';
$scripts = '
<script>
$(document).ready(function() {
    $(".custom-file-input").on("change", function() {
        const fileName = $(this)[0].files[0]?.name || "Seleccionar archivo...";
        $(this).next(".custom-file-label").text(fileName);
    });

    $("#createUserForm").on("submit", function(e) {
        const password = $("#password").val();
        const confirmPassword = $("#confirm_password").val();

        if (password !== confirmPassword) {
            e.preventDefault();
            toastr.error("Las contraseñas no coinciden", "Error de Validación");
            return false;
        }

        if (password.length < 6) {
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

function resetForm() {
    $("#createUserForm")[0].reset();
    $(".custom-file-label").text("Seleccionar archivo...");
    $(".is-invalid").removeClass("is-invalid");
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
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-plus"></i> Información del Usuario
                            </h3>
                        </div>
                        
                        <form id="createUserForm" action="<?= \App\Core\UrlHelper::url('/panel/users/store') ?>" method="POST" enctype="multipart/form-data">
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
                                                        <option value="<?= $role['id'] ?>">
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
                                                       required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Contraseña -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password">
                                                Contraseña <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                </div>
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="password" 
                                                       name="password" 
                                                       placeholder="Contraseña"
                                                       required
                                                       minlength="6">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">
                                                Mínimo 6 caracteres. Se recomienda usar letras, números y símbolos.
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Confirmar Contraseña -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="confirm_password">
                                                Confirmar Contraseña <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                </div>
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="confirm_password" 
                                                       name="confirm_password" 
                                                       placeholder="Confirmar contraseña"
                                                       required
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

                                <!-- Foto (opcional) -->
                                <div class="form-group">
                                    <label for="photo">Foto de Perfil</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" 
                                                   class="custom-file-input" 
                                                   id="photo" 
                                                   name="photo"
                                                   accept="image/*">
                                            <label class="custom-file-label" for="photo">Seleccionar archivo...</label>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        Opcional. Formatos soportados: JPG, PNG, GIF. Tamaño máximo: 2MB.
                                    </small>
                                </div>

                                <!-- Estado -->
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" 
                                               class="custom-control-input" 
                                               id="status" 
                                               name="status" 
                                               checked>
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
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Crear Usuario
                                        </button>
                                        <button type="button" class="btn btn-secondary ml-2" onclick="resetForm()">
                                            <i class="fas fa-undo"></i> Limpiar
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
                    <!-- Panel de ayuda -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Información
                            </h3>
                        </div>
                        <div class="card-body">
                            <h5>Requisitos del Usuario</h5>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Username único en el sistema</li>
                                <li><i class="fas fa-check text-success"></i> Contraseña mínima de 6 caracteres</li>
                                <li><i class="fas fa-check text-success"></i> Rol asignado para permisos</li>
                                <li><i class="fas fa-check text-success"></i> Nombre y apellido completos</li>
                            </ul>

                            <hr>

                            <h5>Roles Disponibles</h5>
                            <ul class="list-unstyled">
                                <?php foreach ($roles ?? [] as $role): ?>
                                <li>
                                    <span class="badge badge-info"><?= htmlspecialchars($role['name']) ?></span>
                                    <?php if (!empty($role['description'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($role['description']) ?></small>
                                    <?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar"></i> Estadísticas
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Usuarios</span>
                                    <span class="info-box-number"><?= count($users ?? []) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>

<style>
.info-box {
    margin-bottom: 1rem;
}

.custom-file-label::after {
    content: "Buscar";
}

.is-invalid {
    border-color: #dc3545;
}

.input-group .btn {
    height: calc(2.25rem + 2px);
}
</style>