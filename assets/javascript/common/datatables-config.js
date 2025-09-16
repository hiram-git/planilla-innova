/**
 * Configuraciones base para DataTables - Módulos DRY Reference
 */
(function() {
    'use strict';

    // Configuración base para DataTables de módulos de referencia
    window.DryDataTableConfig = {
        // Configuración común para todos los módulos DRY
        getBaseConfig: function(module, customConfig = {}) {
            const baseConfig = {
                processing: true,
                serverSide: true,
                ajax: {
                    url: window.APP_CONFIG.urls.modules[module].datatables_ajax,
                    type: "GET",
                    error: function(xhr, error, code) {
                        console.error(`Error loading ${module} data:`, error);
                        alert(`Error al cargar datos de ${module}. Revise la consola para más detalles.`);
                    }
                },
                columns: [
                    { data: 0 }, // ID
                    { data: 1 }, // Código
                    { data: 2 }, // Nombre
                    { data: 3, orderable: false }, // Estado (toggle)
                    { data: 4, orderable: false }  // Acciones
                ],
                language: {
                    url: window.APP_CONFIG.urls.datatables_spanish,
                    processing: "Procesando...",
                    loadingRecords: `Cargando ${module}...`
                },
                order: [[ 0, "asc" ]], // Ordenar por ID
                pageLength: 25,
                responsive: true,
                dom: 'Bfrtip',
                buttons: []
            };

            // Merge con configuración personalizada si se proporciona
            return $.extend(true, {}, baseConfig, customConfig);
        },

        // Configuraciones específicas por módulo
        getModuleConfig: function(module) {
            const configs = {
                'cargos': {
                    language: {
                        loadingRecords: "Cargando cargos...",
                        emptyTable: "No hay cargos disponibles"
                    }
                },
                'funciones': {
                    language: {
                        loadingRecords: "Cargando funciones...",
                        emptyTable: "No hay funciones disponibles"
                    }
                },
                'partidas': {
                    language: {
                        loadingRecords: "Cargando partidas...",
                        emptyTable: "No hay partidas disponibles"
                    }
                },
                'horarios': {
                    language: {
                        loadingRecords: "Cargando horarios...",
                        emptyTable: "No hay horarios disponibles"
                    }
                },
                'frecuencias': {
                    language: {
                        loadingRecords: "Cargando frecuencias...",
                        emptyTable: "No hay frecuencias disponibles"
                    }
                },
                'situaciones': {
                    language: {
                        loadingRecords: "Cargando situaciones...",
                        emptyTable: "No hay situaciones disponibles"
                    }
                }
            };

            return configs[module] || {};
        },

        // Inicializar DataTable para módulo específico
        init: function(module, tableSelector = '#dataTable', customConfig = {}) {
            if (!window.APP_CONFIG.urls.modules[module]) {
                console.error(`Module ${module} not found in APP_CONFIG`);
                return null;
            }

            const moduleConfig = this.getModuleConfig(module);
            const finalConfig = this.getBaseConfig(module, $.extend(true, {}, moduleConfig, customConfig));
            
            return $(tableSelector).DataTable(finalConfig);
        }
    };

})();