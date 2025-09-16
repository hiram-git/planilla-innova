/**
 * Funciones CRUD comunes para módulos DRY Reference
 */
(function() {
    'use strict';

    window.ReferenceCrud = {
        // Variables globales
        currentModule: '',
        dataTable: null,

        // Inicializar CRUD para módulo específico
        init: function(module, tableSelector = '#dataTable') {
            this.currentModule = module;
            
            // Inicializar DataTable
            this.dataTable = window.DryDataTableConfig.init(module, tableSelector);
            
            if (!this.dataTable) {
                console.error(`Failed to initialize DataTable for module: ${module}`);
                return;
            }

            // Configurar event listeners
            this.setupEventListeners();
            
            // Reference CRUD initialized
        },

        // Configurar event listeners comunes
        setupEventListeners: function() {
            const self = this;
            
            // Botón Editar (usando delegación de eventos)
            $(document).on('click', '.edit-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                self.edit(id);
            });

            // Botón Crear (si existe)
            $(document).on('click', '.create-btn', function(e) {
                e.preventDefault();
                self.create();
            });

            // Refresh button
            $(document).on('click', '.refresh-btn', function(e) {
                e.preventDefault();
                self.refresh();
            });
        },

        // Navegar a crear nuevo registro
        create: function() {
            const createUrl = window.APP_CONFIG.urls.modules[this.currentModule].create;
            if (createUrl) {
                window.location.href = createUrl;
            } else {
                console.error('Create URL not found for module:', this.currentModule);
            }
        },

        // Navegar a editar registro
        edit: function(id) {
            if (!id) {
                console.error('ID is required for edit operation');
                return;
            }

            const editUrl = window.APP_CONFIG.urls.modules[this.currentModule].edit;
            if (editUrl) {
                window.location.href = `${editUrl}/${id}`;
            } else {
                console.error('Edit URL not found for module:', this.currentModule);
            }
        },

        // Actualizar DataTable
        refresh: function() {
            if (this.dataTable) {
                this.dataTable.ajax.reload();
                this.showToast('success', 'Datos actualizados correctamente');
            }
        },

        // Mostrar notificación toast
        showToast: function(type, message) {
            // Si toastr está disponible
            if (typeof toastr !== 'undefined') {
                toastr[type](message);
                return;
            }

            // Fallback a alert
            alert(message);
        },

        // Confirmar acción destructiva
        confirmAction: function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        },

        // Obtener módulo actual
        getCurrentModule: function() {
            return this.currentModule;
        },

        // Obtener referencia del DataTable
        getDataTable: function() {
            return this.dataTable;
        }
    };

})();