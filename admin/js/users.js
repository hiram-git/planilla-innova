$(document).ready(function() {
    // Inicializar DataTable
    $('#usersTable').DataTable({
        responsive: true
    });

    // Reglas de validación
    const validationRules = {
        username: {
            pattern: /^[a-zA-Z0-9_]{3,30}$/,
            message: 'El nombre de usuario debe tener entre 3 y 30 caracteres y solo puede incluir letras, números y guiones bajos.'
        },
        firstname: {
            pattern: /^[a-zA-Z\sáéíóúÁÉÍÓÚñÑ]{1,50}$/,
            message: 'El nombre debe tener entre 1 y 50 caracteres y solo puede incluir letras y espacios.'
        },
        lastname: {
            pattern: /^[a-zA-Z\sáéíóúÁÉÍÓÚñÑ]{1,50}$/,
            message: 'El apellido debe tener entre 1 y 50 caracteres y solo puede incluir letras y espacios.'
        },
        password: {
            pattern: /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?._&]{8,}$/,
            message: 'La contraseña debe tener al menos 8 caracteres, incluyendo letras y números.'
        }
    };

    // Resetear formulario
    function resetForm() {
        $('#userForm')[0].reset();
        $('#reset_form_user_id').val('');
        $('#form_action').val('save');
        $('#form_password').prop('required', true);
        $('.modal-title').text('Nuevo Usuario');
    }

    // Validar formulario
    function validateForm(data) {
        let errors = [];
        if (!validationRules.username.pattern.test(data.form_username)) {
            errors.push(validationRules.username.message);
        }
        if (!validationRules.firstname.pattern.test(data.form_firstname)) {
            errors.push(validationRules.firstname.message);
        }
        if (!validationRules.lastname.pattern.test(data.form_lastname)) {
            errors.push(validationRules.lastname.message);
        }
        if (data.action === 'save' && !data.user_id && !validationRules.password.pattern.test(data.form_password)) {
            // Password validation
            errors.push(validationRules.password.message);
        }
        return errors;
    }

    // Manejar envío del formulario
    $('#userForm').submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        formData.append('action', $('#action').val());

        let data = {};
        formData.forEach((value, key) => {
            if (key !== 'form_photo') {
                data[key] = value.trim();
            }
        });

        let errors = validateForm(data);
        if (errors.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Errores de Validación',
                html: errors.join('<br>'),
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        $.ajax({
            url: 'user_process.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            success: function(response) {
                Swal.fire({
                    icon: response.status,
                    title: response.status === 'success' ? 'Éxito' : 'Error',
                    text: response.message,
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    if (response.status === 'success') {
                        $('#form_userModal').modal('hide');
                        location.reload();
                    }
                });
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la solicitud: ' + error,
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });

    // Manejar clic en botón de edición
    $('button.edit-user').off().on('click', function() {
        let userId = $(this).data('id');
        $.ajax({
            url: 'user_process.php',
            type: 'POST',
            data: { action: 'get', user_id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.status === "success") {
                    $('#user_id').val(response.data.id);
                    $('#form_username').val(response.data.username);
                    $('#form_firstname').val(response.data.firstname);
                    $('#form_lastname').val(response.data.lastname);
                    $('#form_role_id').val(response.data.role_id);
                    $('#form_status').val(response.data.status);
                    $('#form_password').prop('required', false);
                    $('#action').val('save');
                    $('.modal-title').text('Editar Usuario');
                    $('#form_userModal').modal('show');
                }
            }
        });
    });

    // Manejar clic en botón de eliminación
    $('button.delete-user').click(function() {
        let userId = $(this).data('id');
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'user_process.php',
                    type: 'POST',
                    data: { action: 'delete', user_id: userId },
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire({
                            icon: response.status,
                            title: response.status === 'success' ? 'Éxito' : 'Error',
                            text: response.message,
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            if (response.status === 'success') {
                                location.reload();
                            }
                        });
                    }
                });
            }
        });
    });

    // Manejar apertura del modal
    $('#form_userModal').on('show.bs.modal', function(e) {
        $(this).attr('aria-hidden', 'true');
        $('#openModalBtn').focus();
        if ($(e.relatedTarget).hasClass('new-user')) {
            resetForm();
        }
    });
});