/**
 * JavaScript Module: Tipos de Acumulados
 * Módulo optimizado para gestión de tipos de acumulados
 */

// Namespace para el módulo
window.TiposAcumuladosModule = (function() {
    'use strict';
    
    // Variables privadas del módulo
    let dataTableInitialized = false;
    let currentDeleteId = null;
    
    // URLs base (se configuran dinámicamente)
    let baseUrls = {};
    
    // Configuración del DataTable
    const dataTableConfig = {
        "responsive": true,
        "pageLength": 25,
        "order": [[0, "asc"]],
        "language": {
            "decimal": "",
            "emptyTable": "No hay datos disponibles en la tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
            "infoFiltered": "(filtrado de _MAX_ entradas totales)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ entradas",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": activar para ordenar la columna de manera ascendente",
                "sortDescending": ": activar para ordenar la columna de manera descendente"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [7] } // Disable ordering on actions column
        ]
    };
    
    // Variables DOM cacheadas
    let $table, $filterPeriodicidad, $searchInput, $deleteModal;
    
    // Inicializar DataTable
    function initDataTable() {
        if (dataTableInitialized) return;
        
        $table = $('#tiposTable');
        if ($table.length) {
            $table.DataTable(dataTableConfig);
            dataTableInitialized = true;
            console.log('DataTable inicializado correctamente');
        }
    }
    
    // Configurar filtros
    function initFilters() {
        $filterPeriodicidad = $('#filterPeriodicidad');
        $searchInput = $('#searchInput');
        
        // Filtro por periodicidad
        $filterPeriodicidad.on('change', function() {
            const filterValue = $(this).val();
            const table = $table.DataTable();
            
            if (filterValue) {
                table.column(2).search(filterValue).draw();
            } else {
                table.column(2).search('').draw();
            }
        });

        // Búsqueda personalizada
        $searchInput.on('keyup', function() {
            const table = $table.DataTable();
            table.search($(this).val()).draw();
        });
    }
    
    // Configurar eventos de estado
    function initStatusToggle() {
        $(document).on('click', '.toggle-status', function() {
            const id = $(this).data('id');
            const currentStatus = $(this).data('current-status');
            const newStatusText = currentStatus ? 'desactivar' : 'activar';
            
            showConfirmation(
                `¿Está seguro que desea ${newStatusText} este tipo de acumulado?`,
                () => toggleStatus(id, $(this))
            );
        });
    }
    
    // Alternar estado
    function toggleStatus(id, $button) {
        const url = `${baseUrls.toggleStatus}/${id}`;
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                csrf_token: baseUrls.csrfToken || ''
            },
            dataType: 'json',
            beforeSend: function() {
                $button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Estado cambiado exitosamente');
                    // Recargar la página para reflejar cambios
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showError('Error: ' + (response.message || 'No se pudo cambiar el estado'));
                }
            },
            error: function() {
                showError('Error de conexión. Inténtelo de nuevo.');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    }
    
    // Configurar eliminación
    function initDeleteFunctionality() {
        $deleteModal = $('#deleteModal');
        
        // Abrir modal de eliminación
        $(document).on('click', '.delete-btn', function() {
            currentDeleteId = $(this).data('id');
            const codigo = $(this).data('codigo');
            const conceptosCount = $(this).data('conceptos-count') || 0;
            
            $('#deleteTipoCodigo').text(codigo);
            
            // Mostrar advertencia si tiene conceptos asociados
            if (conceptosCount > 0) {
                $('#conceptosCount').text(conceptosCount);
                $('#warningConceptos').show();
            } else {
                $('#warningConceptos').hide();
            }
            
            $deleteModal.modal('show');
        });
        
        // Confirmar eliminación
        $('#confirmDelete').on('click', function() {
            if (currentDeleteId) {
                deleteRecord(currentDeleteId);
            }
        });
    }
    
    // Eliminar registro
    function deleteRecord(id) {
        const url = `${baseUrls.delete}/${id}`;
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                csrf_token: baseUrls.csrfToken || ''
            },
            dataType: 'json',
            beforeSend: function() {
                $('#confirmDelete').prop('disabled', true);
            },
            success: function(response) {
                $deleteModal.modal('hide');
                
                if (response.success) {
                    showSuccess('Tipo de acumulado eliminado exitosamente');
                    // Recargar la página
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showError('Error: ' + (response.message || 'No se pudo eliminar el registro'));
                }
            },
            error: function() {
                $deleteModal.modal('hide');
                showError('Error de conexión. Inténtelo de nuevo.');
            },
            complete: function() {
                $('#confirmDelete').prop('disabled', false);
                currentDeleteId = null;
            }
        });
    }
    
    // Funciones helper para notificaciones
    function showSuccess(message) {
        if (typeof toastr !== 'undefined') {
            toastr.success(message, 'Éxito', {
                timeOut: 3000,
                positionClass: 'toast-top-right'
            });
        } else {
            alert(message);
        }
    }
    
    function showError(message) {
        if (typeof toastr !== 'undefined') {
            toastr.error(message, 'Error', {
                timeOut: 5000,
                positionClass: 'toast-top-right'
            });
        } else {
            alert('Error: ' + message);
        }
    }
    
    function showInfo(message) {
        if (typeof toastr !== 'undefined') {
            toastr.info(message, 'Información', {
                timeOut: 4000,
                positionClass: 'toast-top-right'
            });
        } else {
            alert(message);
        }
    }
    
    function showConfirmation(message, callback) {
        // Para confirmaciones usamos el confirm nativo ya que toastr no tiene confirmación
        // Pero podemos usar SweetAlert2 si está disponible en el futuro
        if (confirm(message)) {
            callback();
        }
    }
    
    // API Pública del módulo
    return {
        // Inicializar módulo
        init: function(urls = {}) {
            // Configurar URLs
            baseUrls = {
                toggleStatus: urls.toggleStatus || '',
                delete: urls.delete || '',
                csrfToken: urls.csrfToken || '',
                ...urls
            };
            
            // Esperar a que jQuery esté disponible
            function checkjQuery() {
                if (typeof $ === 'undefined') {
                    setTimeout(checkjQuery, 50);
                    return;
                }
                
                $(document).ready(function() {
                    initDataTable();
                    initFilters();
                    initStatusToggle();
                    initDeleteFunctionality();
                    console.log('TiposAcumuladosModule inicializado correctamente');
                });
            }
            
            checkjQuery();
        },
        
        // Recargar tabla
        reload: function() {
            if (dataTableInitialized) {
                $table.DataTable().ajax.reload();
            }
        },
        
        // Destruir instancia
        destroy: function() {
            if (dataTableInitialized) {
                $table.DataTable().destroy();
                dataTableInitialized = false;
            }
        }
    };
})();