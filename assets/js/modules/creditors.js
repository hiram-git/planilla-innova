/**
 * JavaScript Module: Creditors
 * Módulo optimizado para gestión de acreedores
 */

// Namespace para el módulo
window.CreditorsModule = (function() {
    'use strict';
    
    // Variables privadas del módulo
    let dataTableInitialized = false;
    
    // Configuración del DataTable
    const dataTableConfig = {
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "pageLength": 25,
        "language": {
            "search": "Buscar:",
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "No se encontraron acreedores",
            "info": "Mostrando página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "paginate": {
                "first": "Primero",
                "last": "Último", 
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        "order": [[0, "desc"]],
        "columnDefs": [
            { "orderable": false, "targets": [5] } // Acciones no ordenables
        ]
    };
    
    // Funciones privadas
    function initializeDataTable() {
        const table = $('#creditorsTable');
        if (table.length && !$.fn.DataTable.isDataTable(table)) {
            table.DataTable(dataTableConfig);
            dataTableInitialized = true;
        }
    }
    
    function setupDeleteConfirmation() {
        $('#deleteForm').off('submit.creditors').on('submit.creditors', function(e) {
        });
    }
    
    function validateCreditorForm() {
        const form = $('#creditorForm');
        if (!form.length) return;
        
        form.off('submit.validation').on('submit.validation', function(e) {
            const description = $('#description').val().trim();
            
            if (!description) {
                e.preventDefault();
                showValidationError('Por favor ingrese la descripción del acreedor', '#description');
                return false;
            }
            
            if (description.length < 3) {
                e.preventDefault();
                showValidationError('La descripción debe tener al menos 3 caracteres', '#description');
                return false;
            }
        });
    }
    
    function setupCodeSuggestion() {
        $('#description').off('blur.suggestion').on('blur.suggestion', function() {
            const description = $(this).val().trim();
            const creditorIdField = $('#creditor_id');
            const creditorId = creditorIdField.val().trim();
            
            if (description && !creditorId) {
                const code = generateCodeSuggestion(description);
                creditorIdField.attr('placeholder', 'Sugerencia: ' + code);
            }
        });
    }
    
    function generateCodeSuggestion(description) {
        const words = description.split(' ');
        let code = '';
        
        if (words.length >= 2) {
            code = words[0].substring(0, 3).toUpperCase() + 
                   words[1].substring(0, 2).toUpperCase();
        } else {
            code = description.substring(0, 5).toUpperCase();
        }
        
        return code + '001';
    }
    
    function showValidationError(message, focusElement) {
        // Usar toastr si está disponible, sino alert
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else {
            alert(message);
        }
        
        if (focusElement) {
            $(focusElement).focus();
        }
    }
    
    // API pública del módulo
    return {
        // Inicialización principal
        init: function() {
            
            $(document).ready(function() {
                initializeDataTable();
                setupDeleteConfirmation();
                validateCreditorForm();
                setupCodeSuggestion();
            });
        },
        
        // Función para confirmar eliminación (llamada desde HTML)
        confirmDelete: function(creditorId, creditorName, baseUrl) {
            $('#deleteCreditorName').text(creditorName);
            
            const deleteUrl = baseUrl + '/' + creditorId + '/delete';
            $('#deleteForm').attr('action', deleteUrl);
            
            $('#deleteModal').modal('show');
        },
        
        // Reinicializar DataTable si es necesario
        refreshDataTable: function() {
            const table = $('#creditorsTable');
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
CreditorsModule.init();