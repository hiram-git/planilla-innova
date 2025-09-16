$(document).ready(function() {
    // Inicializar DataTable
    $('#rolesTable').DataTable({
        responsive: true
    });

    // Reglas de validación
    const validationRules = {
        name: {
            pattern: /^[a-zA-Z0-9\sáéíóúÁÉÍÓÚñÑ]{3,50}$/,
            message: 'El nombre debe tener entre 3 y 50 caracteres y solo puede incluir letras, números y espacios.'
        },
        description: {
            pattern: /^[\s\S]{0,255}$/,
            message: 'La descripción no puede exceder los 255 caracteres.'
        }
    };

    // Resetear formulario
    function resetForm() {
        $('#roleForm')[0].reset();
        $('#role_id').val('');
        $('#action').val('save');
        $('.modal-title').text('Nuevo Rol');
        $('input[type="checkbox"]').prop('checked', false);
    }

    // Validar formulario
    function validateForm(data) {
        let errors = [];
        if (!validationRules.name.pattern.test(data.name)) {
            errors.push(validationRules.name.message);
        }
        if (!validationRules.description.pattern.test(data.description)) {
            errors.push(validationRules.description.message);
        }
        return errors;
    }

    // Manejar "Seleccionar todos los permisos" (global)
    $('#select-all-permissions').change(function() {
        let isChecked = $(this).is(':checked');
        $('.select-all, .perm-checkbox, #select-all-read, #select-all-write, #select-all-delete').prop('checked', isChecked);
    });

    // Manejar "Seleccionar todos" por módulo
    $('.select-all').change(function() {
        let menuId = $(this).data('menu-id');
        let isChecked = $(this).is(':checked');
        $(`input.perm-checkbox[data-menu-id="${menuId}"]`).prop('checked', isChecked);
        updateTypeCheckboxes();
        updateGlobalCheckbox();
    });

    // Manejar checkboxes por tipo de permiso (lectura, escritura, eliminación)
    $('#select-all-read').change(function() {
        let isChecked = $(this).is(':checked');
        $('.perm-read').prop('checked', isChecked);
        updateModuleCheckboxes();
        updateGlobalCheckbox();
    });

    $('#select-all-write').change(function() {
        let isChecked = $(this).is(':checked');
        $('.perm-write').prop('checked', isChecked);
        updateModuleCheckboxes();
        updateGlobalCheckbox();
    });

    $('#select-all-delete').change(function() {
        let isChecked = $(this).is(':checked');
        $('.perm-delete').prop('checked', isChecked);
        updateModuleCheckboxes();
        updateGlobalCheckbox();
    });

    // Actualizar estado de los checkboxes por módulo cuando cambian los permisos individuales
    $('.perm-checkbox').change(function() {
        let menuId = $(this).data('menu-id');
        let allChecked = $(`input.perm-checkbox[data-menu-id="${menuId}"]`).length === 
                        $(`input.perm-checkbox[data-menu-id="${menuId}"]:checked`).length;
        $(`input.select-all[data-menu-id="${menuId}"]`).prop('checked', allChecked);
        updateTypeCheckboxes();
        updateGlobalCheckbox();
    });

    // Actualizar estado de los checkboxes por tipo de permiso
    function updateTypeCheckboxes() {
        let allReadChecked = $('.perm-read').length === $('.perm-read:checked').length;
        $('#select-all-read').prop('checked', allReadChecked);
        let allWriteChecked = $('.perm-write').length === $('.perm-write:checked').length;
        $('#select-all-write').prop('checked', allWriteChecked);
        let allDeleteChecked = $('.perm-delete').length === $('.perm-delete:checked').length;
        $('#select-all-delete').prop('checked', allDeleteChecked);
    }

    // Actualizar estado de los checkboxes por módulo
    function updateModuleCheckboxes() {
        $('.select-all').each(function() {
            let menuId = $(this).data('menu-id');
            let allChecked = $(`input.perm-checkbox[data-menu-id="${menuId}"]`).length === 
                            $(`input.perm-checkbox[data-menu-id="${menuId}"]:checked`).length;
            $(this).prop('checked', allChecked);
        });
    }

    // Actualizar estado del checkbox global
    function updateGlobalCheckbox() {
        let allPermissionsChecked = $('.perm-checkbox').length === $('.perm-checkbox:checked').length;
        $('#select-all-permissions').prop('checked', allPermissionsChecked);
    }

    // Manejar envío del formulario
    $('#roleForm').submit(function(e) {
        e.preventDefault();
        let formData = $(this).serializeArray();
        let data = {};
        formData.forEach(item => {
            if (item.name.includes('permissions')) {
                let matches = item.name.match(/permissions\[(\d+)\]\[(\w+)\]/);
                if (matches) {
                    let menuId = matches[1], permType = matches[2];
                    if (!data.permissions) data.permissions = {};
                    if (!data.permissions[menuId]) data.permissions[menuId] = {};
                    data.permissions[menuId][permType] = item.value;
                }
            } else {
                data[item.name] = item.value.trim();
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
            url: 'role_process.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                Swal.fire({
                    icon: response.status,
                    title: response.status === 'success' ? 'Éxito' : 'Error',
                    text: response.message,
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    if (response.status === 'success') {
                        $('#roleModal').modal('hide');
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
    $('.edit-role').click(function() {
        let roleId = $(this).data('id');
        $.ajax({
            url: 'role_process.php',
            type: 'POST',
            data: { action: 'get', role_id: roleId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#role_id').val(response.data.id);
                    $('#name').val(response.data.name);
                    $('#description').val(response.data.description);
                    $('#status').val(response.data.status);
                    $('input[type="checkbox"]').prop('checked', false);
                    $.each(response.data.permissions, function(menuId, perms) {
                        if (perms.read) $(`input[name="permissions[${menuId}][read]"]`).prop('checked', true);
                        if (perms.write) $(`input[name="permissions[${menuId}][write]"]`).prop('checked', true);
                        if (perms.delete) $(`input[name="permissions[${menuId}][delete]"]`).prop('checked', true);
                        let allChecked = perms.read && perms.write && perms.delete;
                        $(`input.select-all[data-menu-id="${menuId}"]`).prop('checked', allChecked);
                    });
                    updateTypeCheckboxes();
                    updateGlobalCheckbox();
                    $('#action').val('save');
                    $('.modal-title').text('Editar Rol');
                    $('#roleModal').modal('show');
                }
            }
        });
    });

    // Manejar clic en botón de eliminación
    $('.delete-role').click(function() {
        let roleId = $(this).data('id');
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
                    url: 'role_process.php',
                    type: 'POST',
                    data: { action: 'delete', role_id: roleId },
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
    $('#roleModal').on('show.bs.modal', function(e) {
        if (!$(e.relatedTarget).hasClass('edit-role')) {
            resetForm();
        }
    });
});