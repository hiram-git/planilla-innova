/**
 * Manejadores para Toggle Switches de módulos DRY Reference
 */
(function() {
    'use strict';

    window.ToggleHandlers = {
        // Inicializar manejadores de toggle
        init: function(module) {
            this.module = module;
            this.setupToggleListeners();
            // Toggle handlers initialized
        },

        // Configurar event listeners para toggles
        setupToggleListeners: function() {
            const self = this;
            
            // Toggle switch handler (usando delegación de eventos)
            $(document).on('change', '.toggle-switch', function() {
                const checkbox = $(this);
                const itemId = checkbox.data('id');
                const itemName = checkbox.data('name') || 'este elemento';
                const newStatus = checkbox.is(':checked') ? 1 : 0;
                
                // Deshabilitar el checkbox temporalmente
                checkbox.prop('disabled', true);
                
                self.performToggle(itemId, newStatus, itemName, checkbox);
            });
        },

        // Realizar toggle via AJAX
        performToggle: function(itemId, newStatus, itemName, checkbox) {
            const self = this;
            const toggleUrl = window.APP_CONFIG.urls.modules[this.module].toggle;
            
            if (!toggleUrl) {
                console.error('Toggle URL not found for module:', this.module);
                checkbox.prop('disabled', false);
                return;
            }

            $.ajax({
                url: `${toggleUrl}/${itemId}`,
                method: 'POST',
                data: {
                    csrf_token: window.APP_CONFIG.config.csrf_token,
                    status: newStatus
                },
                success: function(response) {
                    self.handleToggleSuccess(response, itemName, newStatus, checkbox);
                },
                error: function(xhr, status, error) {
                    self.handleToggleError(xhr, itemName, checkbox);
                }
            });
        },

        // Manejar éxito en toggle
        handleToggleSuccess: function(response, itemName, newStatus, checkbox) {
            // Re-habilitar checkbox
            checkbox.prop('disabled', false);

            // Verificar respuesta del servidor
            if (response.success) {
                const statusText = newStatus ? 'activado' : 'desactivado';
                const message = `${itemName} ha sido ${statusText} correctamente.`;
                
                this.showToast('success', message);
                
                // Actualizar DataTable si existe
                if (window.ReferenceCrud && window.ReferenceCrud.getDataTable()) {
                    // Pequeño delay para que se vea el cambio
                    setTimeout(() => {
                        window.ReferenceCrud.getDataTable().ajax.reload(null, false);
                    }, 500);
                }
            } else {
                // Error del servidor - revertir checkbox
                checkbox.prop('checked', !checkbox.is(':checked'));
                this.showToast('error', response.message || 'Error al actualizar el estado');
            }
        },

        // Manejar error en toggle
        handleToggleError: function(xhr, itemName, checkbox) {
            // Re-habilitar y revertir checkbox
            checkbox.prop('disabled', false);
            checkbox.prop('checked', !checkbox.is(':checked'));
            
            let errorMessage = `Error al actualizar el estado de ${itemName}`;
            
            // Intentar obtener mensaje de error específico
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    errorMessage = response.message;
                }
            } catch (e) {
                // Usar mensaje genérico
            }
            
            this.showToast('error', errorMessage);
            console.error('Toggle error:', xhr);
        },

        // Mostrar notificación toast
        showToast: function(type, message) {
            // Si toastr está disponible
            if (typeof toastr !== 'undefined') {
                toastr[type](message);
                return;
            }

            // Si SweetAlert2 está disponible
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: type === 'success' ? 'Éxito' : 'Error',
                    text: message,
                    icon: type,
                    timer: 3000,
                    showConfirmButton: false
                });
                return;
            }

            // Fallback a alert
            alert(message);
        },

        // Obtener módulo actual
        getCurrentModule: function() {
            return this.module;
        }
    };

})();