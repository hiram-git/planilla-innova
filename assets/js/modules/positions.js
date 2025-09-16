/**
 * JavaScript Module: Positions
 * Módulo optimizado para gestión de posiciones
 */

// Namespace para el módulo
window.PositionsModule = (function() {
    'use strict';
    
    // Variables privadas del módulo
    let dataTableInitialized = false;
    let optionsCache = {};
    
    // URLs base (se configuran dinámicamente)
    let baseUrls = {};
    
    // Configuración del DataTable
    const dataTableConfig = {
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "language": {
            "search": "Buscar:",
            "zeroRecords": "No se encontraron posiciones",
            "info": "Mostrando página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    };
    
    // Funciones privadas
    function initializeDataTable() {
        const table = $('#positionsTable');
        if (table.length && !$.fn.DataTable.isDataTable(table)) {
            table.DataTable(dataTableConfig);
            dataTableInitialized = true;
        }
    }
    
    function setupModalEvents() {
        // Modal agregar - cargar opciones al mostrar
        $('#addModal').off('show.bs.modal.positions').on('show.bs.modal.positions', function() {
            loadOptions('partida', '#partida');
            loadOptions('cargo', '#cargo');
            loadOptions('funcion', '#funcion');
            loadSuggestedCode();
        });

        // Botón generar código sugerido
        $('#generateCodeBtn').off('click.positions').on('click.positions', function() {
            loadSuggestedCode();
        });
    }
    
    function setupButtonEvents() {
        // Edit button - usar delegación de eventos
        $(document).off('click.positions', '.edit-btn').on('click.positions', '.edit-btn', function() {
            const id = $(this).data('id');
            $('#editModal').modal('show');
            loadOptionsForEdit(id);
        });

        // Delete button - usar delegación de eventos
        $(document).off('click.positions', '.delete-btn').on('click.positions', '.delete-btn', function() {
            const id = $(this).data('id');
            const description = $(this).data('description');
            $('#deletePositionName').text(description);
            $('#confirmDelete').data('id', id);
            $('#deleteModal').modal('show');
        });

        // Confirm delete
        $('#confirmDelete').off('click.positions').on('click.positions', function() {
            const id = $(this).data('id');
            deletePosition(id);
        });
    }
    
    function setupFormSubmissions() {
        // Form agregar
        $('#addForm').off('submit.positions').on('submit.positions', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            
            $.ajax({
                url: baseUrls.create,
                type: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    $('#addForm button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess('Posición creada exitosamente');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showError('Error: ' + response.message);
                        $('#addForm button[type="submit"]').prop('disabled', false).html('Guardar');
                    }
                },
                error: function(xhr, status, error) {
                    showError('Error al crear la posición: ' + error);
                    $('#addForm button[type="submit"]').prop('disabled', false).html('Guardar');
                }
            });
        });

        // Form editar
        $('#editForm').off('submit.positions').on('submit.positions', function(e) {
            e.preventDefault();
            const id = $('#edit_id').val();
            const formData = $(this).serialize();
            
            $.ajax({
                url: baseUrls.update.replace('{id}', id),
                type: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    $('#editForm button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess('Posición actualizada exitosamente');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showError('Error: ' + response.message);
                        $('#editForm button[type="submit"]').prop('disabled', false).html('Actualizar');
                    }
                },
                error: function(xhr, status, error) {
                    showError('Error al actualizar la posición: ' + error);
                    $('#editForm button[type="submit"]').prop('disabled', false).html('Actualizar');
                }
            });
        });
    }
    
    function loadSuggestedCode() {
        if (!baseUrls.getNextCode) return;
        
        $.post(baseUrls.getNextCode)
        .done(function(response) {
            if (response.code) {
                $('#suggestedCode').text(response.code);
                $('#codigo').attr('placeholder', response.code);
            }
        })
        .fail(function() {
        });
    }
    
    function deletePosition(id) {
        if (!baseUrls.delete || !baseUrls.csrfToken) {
            showError('Error de configuración del sistema');
            return;
        }
        
        $.ajax({
            url: baseUrls.delete.replace('{id}', id),
            type: 'POST',
            data: {
                csrf_token: baseUrls.csrfToken
            },
            dataType: 'json',
            beforeSend: function() {
                $('#confirmDelete').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Eliminando...');
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    showSuccess('Posición eliminada exitosamente');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError('Error: ' + response.message);
                    $('#confirmDelete').prop('disabled', false).html('Eliminar');
                }
            },
            error: function(xhr, status, error) {
                showError('Error al eliminar la posición: ' + error);
                $('#confirmDelete').prop('disabled', false).html('Eliminar');
            }
        });
    }
    
    function loadOptions(type, selectId) {
        // Verificar cache primero
        if (optionsCache[type]) {
            populateSelect(selectId, optionsCache[type], type);
            return;
        }
        
        if (!baseUrls.getOptions) {
            console.error('URL getOptions no configurada');
            return;
        }
        
        $.post(baseUrls.getOptions, { type: type })
        .done(function(response) {
            // Guardar en cache
            optionsCache[type] = response;
            populateSelect(selectId, response, type);
        })
        .fail(function(xhr, status, error) {
            console.error("Error loading " + type + " options:", xhr.responseText);
        });
    }
    
    function populateSelect(selectId, options, type) {
        const select = $(selectId);
        select.empty().append('<option value="">Seleccione ' + type + '</option>');
        
        $.each(options, function(index, item) {
            select.append('<option value="' + item.id + '">' + item.descripcion + '</option>');
        });
    }
    
    function loadOptionsForEdit(positionId) {
        let optionsLoaded = 0;
        const totalOptions = 3;
        
        function checkAllLoaded() {
            optionsLoaded++;
            if (optionsLoaded === totalOptions) {
                loadPositionData(positionId);
            }
        }
        
        loadOptionsWithCallback('partida', '#edit_partida', checkAllLoaded);
        loadOptionsWithCallback('cargo', '#edit_cargo', checkAllLoaded);
        loadOptionsWithCallback('funcion', '#edit_funcion', checkAllLoaded);
    }
    
    function loadOptionsWithCallback(type, selectId, callback) {
        // Verificar cache primero
        if (optionsCache[type]) {
            populateSelect(selectId, optionsCache[type], type);
            if (callback) callback();
            return;
        }
        
        if (!baseUrls.getOptions) {
            if (callback) callback();
            return;
        }
        
        $.post(baseUrls.getOptions, { type: type })
        .done(function(response) {
            // Guardar en cache
            optionsCache[type] = response;
            populateSelect(selectId, response, type);
            if (callback) callback();
        })
        .fail(function(xhr, status, error) {
            console.error("Error loading " + type + " options:", xhr.responseText);
            if (callback) callback(); // Continuar aunque falle
        });
    }
    
    function loadPositionData(id) {
        if (!baseUrls.getRow) {
            showError('Error de configuración del sistema');
            return;
        }
        
        $.post(baseUrls.getRow, { id: id })
        .done(function(response) {
            $("#edit_id").val(response.posid);
            $("#edit_codigo").val(response.codigo);
            $("#edit_sueldo").val(response.sueldo);
            
            // Establecer valores seleccionados después de que las opciones estén cargadas
            $("#edit_partida").val(response.id_partida);
            $("#edit_cargo").val(response.id_cargo);
            $("#edit_funcion").val(response.id_funcion);
        })
        .fail(function() {
            showError("Error al cargar datos de la posición");
        });
    }
    
    function showSuccess(message) {
        if (typeof toastr !== 'undefined') {
            toastr.success(message);
        } else if ($(document).Toasts) {
            $(document).Toasts("create", {
                class: "bg-success",
                title: "Éxito",
                body: message
            });
        } else {
            alert(message);
        }
    }
    
    function showError(message) {
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else if ($(document).Toasts) {
            $(document).Toasts("create", {
                class: "bg-danger",
                title: "Error",
                body: message
            });
        } else {
            alert(message);
        }
    }
    
    function clearCache() {
        optionsCache = {};
    }
    
    // API pública del módulo
    return {
        // Inicialización principal
        init: function(urls = {}) {
            baseUrls = urls;
            
            $(document).ready(function() {
                initializeDataTable();
                setupModalEvents();
                setupButtonEvents();
                setupFormSubmissions();
            });
        },
        
        // Configurar URLs dinámicamente
        setUrls: function(urls) {
            baseUrls = Object.assign(baseUrls, urls);
        },
        
        // Limpiar cache de opciones
        clearOptionsCache: function() {
            clearCache();
        },
        
        // Recargar opciones específicas
        reloadOptions: function(type, selectId) {
            delete optionsCache[type];
            loadOptions(type, selectId);
        },
        
        // Reinicializar DataTable
        refreshDataTable: function() {
            const table = $('#positionsTable');
            if ($.fn.DataTable.isDataTable(table)) {
                table.DataTable().destroy();
                dataTableInitialized = false;
            }
            initializeDataTable();
        },
        
        // Estado del módulo
        isInitialized: function() {
            return dataTableInitialized;
        }
    };
})();

// Auto-inicialización del módulo
PositionsModule.init();