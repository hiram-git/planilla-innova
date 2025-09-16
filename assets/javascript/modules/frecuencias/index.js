/**
 * JavaScript específico para índices de módulos DRY Reference
 * Maneja DataTables, toggles de estado y eliminaciones
 */
(function() {
    'use strict';

    // Variables globales del módulo
    let deleteItemId = null;
    let currentModule = '';

    // Inicialización cuando el DOM esté listo
    $(document).ready(function() {
        // Determinar el módulo actual desde la URL
        currentModule = getCurrentModuleFromUrl();
        
        if (!currentModule) {
            console.error('Could not determine current module from URL');
            return;
        }

        // Initializing reference index
        
        // Inicializar componentes
        initializeDataTable();
        initializeToggleHandlers();
        initializeActionHandlers();
        initializeDeleteModal();
    });

    /**
     * Determinar el módulo actual desde la URL
     */
    function getCurrentModuleFromUrl() {
        const path = window.location.pathname;
        const matches = path.match(/\/panel\/([^\/]+)/);
        
        if (matches && matches[1]) {
            const module = matches[1];
            // Verificar que sea un módulo válido
            const validModules = ['cargos', 'funciones', 'partidas', 'horarios', 'frecuencias', 'situaciones'];
            if (validModules.includes(module)) {
                return module;
            }
        }
        
        return null;
    }

    /**
     * Inicializar DataTable
     */
    function initializeDataTable() {
        // Usar la configuración base pero adaptarla para server-side rendering
        const config = {
            processing: false, // No server-side para template rendering
            serverSide: false, // Los datos ya están renderizados
            language: {
                url: window.APP_CONFIG?.urls?.datatables_spanish || '/assets/js/datatables-spanish.json'
            },
            order: [[ 0, "asc" ]], // Ordenar por primera columna (Código)
            pageLength: 25,
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [3, 5] } // Estado y Acciones no ordenables
            ]
        };

        const table = $("#referenceTable").DataTable(config);
        
        // DataTable initialized
        return table;
    }

    /**
     * Inicializar manejadores de toggle de estado
     */
    function initializeToggleHandlers() {
        $(document).on('change', '.status-toggle', function() {
            const toggle = $(this);
            const itemId = toggle.data('id');
            const newStatus = toggle.is(':checked') ? 1 : 0;
            
            // Deshabilitar toggle mientras se procesa
            toggle.prop('disabled', true);
            
            performStatusToggle(itemId, newStatus, toggle);
        });
    }

    /**
     * Realizar cambio de estado via AJAX
     */
    function performStatusToggle(itemId, newStatus, toggle) {
        const toggleUrl = getModuleUrl('toggle-status');
        
        $.ajax({
            url: toggleUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                id: itemId,
                status: newStatus
            }),
            success: function(response) {
                handleToggleSuccess(response, newStatus, toggle);
            },
            error: function(xhr) {
                handleToggleError(xhr, newStatus, toggle);
            },
            complete: function() {
                // Rehabilitar el toggle
                toggle.prop('disabled', false);
            }
        });
    }

    /**
     * Manejar éxito en toggle
     */
    function handleToggleSuccess(response, newStatus, toggle) {
        if (response.success) {
            showToast('success', response.message || 'Estado actualizado correctamente');
        } else {
            // Revertir el toggle en caso de error
            toggle.prop('checked', !newStatus);
            showToast('error', response.message || 'Error al actualizar el estado');
        }
    }

    /**
     * Manejar error en toggle
     */
    function handleToggleError(xhr, newStatus, toggle) {
        // Revertir el toggle en caso de error
        toggle.prop('checked', !newStatus);
        
        let errorMessage = 'Error al actualizar el estado';
        if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
        }
        
        showToast('error', errorMessage);
    }

    /**
     * Inicializar manejadores de acciones (editar)
     */
    function initializeActionHandlers() {
        // Botón editar
        $(document).on('click', '.edit-btn', function(e) {
            e.preventDefault();
            const itemId = $(this).data('id');
            const editUrl = getModuleUrl('edit');
            window.location.href = `${editUrl}/${itemId}`;
        });

        // Botón eliminar
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            const itemId = $(this).data('id');
            const itemName = $(this).data('name') || 'este elemento';
            
            deleteItemId = itemId;
            $('#deleteModal .modal-body p').text(`¿Está seguro que desea eliminar: ${itemName}?`);
            $('#deleteModal').modal('show');
        });
    }

    /**
     * Inicializar modal de eliminación
     */
    function initializeDeleteModal() {
        $('#confirmDelete').click(function() {
            if (deleteItemId) {
                const deleteUrl = getModuleUrl('delete');
                window.location.href = `${deleteUrl}/${deleteItemId}`;
            }
        });
    }

    /**
     * Obtener URL para una acción del módulo actual
     */
    function getModuleUrl(action) {
        // Fallback si no tenemos APP_CONFIG
        if (!window.APP_CONFIG?.urls?.modules?.[currentModule]) {
            const baseUrl = `/panel/${currentModule}`;
            const actionUrls = {
                'edit': `${baseUrl}/edit`,
                'delete': baseUrl,
                'toggle-status': `${baseUrl}/toggle-status`
            };
            return actionUrls[action] || baseUrl;
        }

        // Usar APP_CONFIG si está disponible
        const moduleUrls = window.APP_CONFIG.urls.modules[currentModule];
        const actionMap = {
            'edit': moduleUrls.edit,
            'delete': moduleUrls.index,
            'toggle-status': moduleUrls.toggle || `${moduleUrls.index}/toggle-status`
        };
        
        return actionMap[action] || moduleUrls.index;
    }

    /**
     * Mostrar notificación toast
     */
    function showToast(type, message) {
        // AdminLTE Toasts
        if (typeof $(document).Toasts === 'function') {
            const toastClass = type === 'success' ? 'bg-success' : 'bg-danger';
            const title = type === 'success' ? 'Éxito' : 'Error';
            
            $(document).Toasts('create', {
                class: toastClass,
                title: title,
                body: message,
                autohide: true,
                delay: type === 'success' ? 3000 : 5000
            });
            return;
        }

        // Toastr fallback
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
            return;
        }

        // Alert fallback
        alert(message);
    }

})();