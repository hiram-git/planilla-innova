/**
 * JavaScript Module: Deductions
 * Módulo optimizado para gestión de deducciones
 */

// Namespace para el módulo
window.DeductionsModule = (function() {
    'use strict';
    
    // Variables privadas del módulo
    let dataTableInitialized = false;
    let select2Initialized = false;
    
    // URLs base (se configuran dinámicamente)
    let baseUrls = {};
    
    // Configuración del DataTable
    const dataTableConfig = {
        "responsive": true,
        "pageLength": 25,
        "order": [[1, "asc"]],
        "language": {
            "search": "Buscar:",
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "No se encontraron deducciones",
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
        "columnDefs": [
            { "orderable": false, "targets": [6] } // Acciones no ordenables
        ]
    };
    
    // Configuración Select2 para empleados
    const select2Config = {
        placeholder: "Buscar empleado por nombre o código...",
        allowClear: true,
        minimumInputLength: 2,
        escapeMarkup: function (markup) { 
            return markup; 
        },
        templateResult: function(employee) {
            if (employee.loading) {
                return employee.text;
            }
            
            if (employee.data) {
                return $(
                    "<div class=\"select2-employee\">" +
                    "<strong>" + employee.data.firstname + " " + employee.data.lastname + "</strong>" +
                    "<br><small class=\"text-muted\">Código: " + employee.data.code + 
                    (employee.data.position_name ? " | " + employee.data.position_name : "") + "</small>" +
                    "</div>"
                );
            }
            
            return employee.text;
        },
        templateSelection: function(employee) {
            if (employee.data) {
                return employee.data.firstname + " " + employee.data.lastname + " (" + employee.data.code + ")";
            }
            return employee.text;
        }
    };
    
    // Funciones privadas
    function initializeDataTable() {
        const table = $('#deductionsTable');
        if (table.length && !$.fn.DataTable.isDataTable(table)) {
            table.DataTable(dataTableConfig);
            dataTableInitialized = true;
            
            // Setup filtros después de inicializar
            setupTableFilters(table.DataTable());
        }
    }
    
    function setupTableFilters(dataTable) {
        // Filtro por acreedor
        $("#filterCreditor").off('change.deductions').on("change.deductions", function() {
            const filterValue = $(this).val();
            if (filterValue) {
                dataTable.column(2).search(filterValue).draw();
            } else {
                dataTable.column(2).search("").draw();
            }
        });

        // Búsqueda personalizada
        $("#searchInput").off('keyup.deductions').on("keyup.deductions", function() {
            dataTable.search($(this).val()).draw();
        });
    }
    
    function initializeSelect2() {
        const employeeSelect = $('#employee_id');
        if (employeeSelect.length && !select2Initialized && baseUrls.searchEmployees) {
            
            const config = Object.assign({}, select2Config, {
                ajax: {
                    url: baseUrls.searchEmployees,
                    dataType: "json",
                    delay: 300,
                    data: function (params) {
                        return {
                            search: params.term || "",
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        
                        return {
                            results: data.results.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text,
                                    data: item
                                };
                            }),
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                }
            });
            
            // Para edit, no permitir limpiar
            if (window.location.href.includes('/edit')) {
                config.allowClear = false;
            }
            
            employeeSelect.select2(config);
            select2Initialized = true;
        }
    }
    
    function setupDeleteConfirmation() {
        $(document).off('click.deductions', '.delete-deduction').on('click.deductions', '.delete-deduction', function() {
            const deductionId = $(this).data("id");
            const employee = $(this).data("employee");
            const creditor = $(this).data("creditor");
            
            $("#deleteEmployee").text(employee);
            $("#deleteCreditor").text(creditor);
            $("#deleteModal").modal("show");
            
            $("#confirmDelete").off("click.deductions").on("click.deductions", function() {
                deleteDeduction(deductionId);
            });
        });
    }
    
    function deleteDeduction(deductionId) {
        if (!baseUrls.delete || !baseUrls.csrfToken) {
            showError('Error de configuración del sistema');
            return;
        }
        
        $.ajax({
            url: baseUrls.delete + "/" + deductionId + "/delete",
            method: "POST",
            dataType: "json",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            },
            data: {
                csrf_token: baseUrls.csrfToken
            },
            beforeSend: function() {
                $("#confirmDelete").prop("disabled", true).html("<i class=\"fas fa-spinner fa-spin\"></i> Eliminando...");
            },
            success: function(response) {
                if (response.success) {
                    $("#deleteModal").modal("hide");
                    showSuccess(response.message || "Deducción eliminada exitosamente");
                    
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showError("Error: " + (response.message || "No se pudo eliminar la deducción"));
                    $("#confirmDelete").prop("disabled", false).html("Eliminar");
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = "Error de conexión. No se pudo eliminar la deducción.";
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        let errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.message) {
                            errorMessage = errorResponse.message;
                        }
                    } catch (e) {
                        // Si no es JSON válido, usar mensaje genérico
                    }
                }
                
                showError(errorMessage);
                $("#confirmDelete").prop("disabled", false).html("Eliminar");
            }
        });
    }
    
    function validateDeductionForm() {
        const form = $('#deductionForm');
        if (!form.length) return;
        
        const isEditMode = window.location.href.includes('/edit');
        
        form.off('submit.validation').on('submit.validation', function(e) {
            const employeeId = $("#employee_id").val();
            const creditorId = $("#creditor_id").val();
            const description = $("#description").val().trim();
            const amount = $("#amount").val();

            // En modo edit, ser más flexible con la validación del empleado
            if (!employeeId) {
                if (!isEditMode) {
                    e.preventDefault();
                    showValidationError("Por favor seleccione un empleado", "#employee_id");
                    return false;
                } else {
                    // En edit, si el campo está vacío, intentar obtener el valor del HTML
                    const hiddenEmployeeId = $('input[name="employee_id"]').val() || 
                                           $('#employee_id option:selected').val() ||
                                           $('#employee_id').attr('data-employee-id');
                    
                    if (!hiddenEmployeeId) {
                        // En edit mode, permitir continuar pero con advertencia
                    }
                }
            }

            if (!creditorId) {
                e.preventDefault();
                showValidationError("Por favor seleccione un acreedor", "#creditor_id");
                return false;
            }

            if (!description) {
                e.preventDefault();
                showValidationError("Por favor ingrese la descripción de la deducción", "#description");
                return false;
            }

            if (description.length < 3) {
                e.preventDefault();
                showValidationError("La descripción debe tener al menos 3 caracteres", "#description");
                return false;
            }

            if (!amount || parseFloat(amount) <= 0) {
                e.preventDefault();
                showValidationError("El monto debe ser mayor a cero", "#amount");
                return false;
            }

            if (parseFloat(amount) > 999999.99) {
                e.preventDefault();
                showValidationError("El monto excede el límite máximo permitido (Q999,999.99)", "#amount");
                return false;
            }
        });
    }
    
    function setupEmployeeInfoLoader() {
        $("#employee_id").off('change.employeeInfo').on('change.employeeInfo', function() {
            const employeeId = $(this).val();
            if (employeeId) {
                loadEmployeeInfo(employeeId);
            } else {
                $("#employeeInfo").hide();
            }
        });
    }
    
    function loadEmployeeInfo(employeeId) {
        if (!baseUrls.employeeInfo) return;
        
        $.ajax({
            url: baseUrls.employeeInfo,
            method: "GET",
            data: { employee_id: employeeId },
            success: function(response) {
                if (response.success) {
                    const emp = response.employee;
                    $("#empName").text(emp.firstname + " " + emp.lastname);
                    $("#empPosition").text(emp.position_name || "N/A");
                    $("#empCurrentDeductions").text(emp.total_deductions || "0");
                    $("#empTotalDeductions").text("Q " + (parseFloat(emp.deductions_amount || 0).toFixed(2)));
                    $("#empBaseSalary").text("Q " + (parseFloat(emp.salary || 0).toFixed(2)));
                    $("#employeeInfo").show();
                } else {
                    $("#employeeInfo").hide();
                }
            },
            error: function() {
                $("#employeeInfo").hide();
            }
        });
    }
    
    function setupValidationHelpers() {
        // Validación en tiempo real del monto
        $("#amount").off('input.validation').on('input.validation', function() {
            const amount = parseFloat($(this).val());
            let feedback = $(this).siblings(".invalid-feedback");
            
            if (feedback.length === 0) {
                $(this).after("<div class=\"invalid-feedback\"></div>");
                feedback = $(this).siblings(".invalid-feedback");
            }
            
            if (isNaN(amount) || amount <= 0) {
                $(this).addClass("is-invalid");
                feedback.text("El monto debe ser mayor a cero");
            } else if (amount > 999999.99) {
                $(this).addClass("is-invalid");
                feedback.text("El monto excede el límite máximo (Q999,999.99)");
            } else {
                $(this).removeClass("is-invalid");
                feedback.text("");
            }
        });

        // Validar combinación empleado-acreedor (solo en create)
        if (!window.location.href.includes('/edit')) {
            $("#employee_id, #creditor_id").off('change.duplicate').on('change.duplicate', function() {
                const employeeId = $("#employee_id").val();
                const creditorId = $("#creditor_id").val();
                
                if (employeeId && creditorId) {
                    checkDuplicateDeduction(employeeId, creditorId);
                }
            });
        }

        // Auto-generate description suggestion (solo para create)
        if (!window.location.href.includes('/edit')) {
            $("#creditor_id").off('change.suggestion').on('change.suggestion', function() {
                const selectedText = $(this).find("option:selected").text();
                const currentDescription = $("#description").val().trim();
                
                if (selectedText && selectedText !== "Seleccione un acreedor..." && !currentDescription) {
                    const creditorName = selectedText.replace(/\s*\([^)]*\)/, "");
                    $("#description").attr("placeholder", "Sugerencia: Descuento " + creditorName);
                }
            });
        }
    }
    
    function checkDuplicateDeduction(employeeId, creditorId) {
        if (!baseUrls.checkDuplicate) return;
        
        $.ajax({
            url: baseUrls.checkDuplicate,
            method: "GET",
            data: { 
                employee_id: employeeId,
                creditor_id: creditorId
            },
            success: function(response) {
                if (response.exists) {
                    showValidationError("Ya existe una deducción para este empleado y acreedor");
                    $("#creditor_id").val("").focus();
                }
            },
            error: function() {
            }
        });
    }
    
    function showValidationError(message, focusElement) {
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else {
            alert(message);
        }
        
        if (focusElement) {
            $(focusElement).focus();
        }
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
        }
    }
    
    // API pública del módulo
    return {
        // Inicialización principal
        init: function(urls = {}) {
            baseUrls = urls;
            
            $(document).ready(function() {
                initializeDataTable();
                
                // Solo inicializar Select2 si hay URLs configuradas
                if (baseUrls.searchEmployees) {
                    initializeSelect2();
                    setupEmployeeInfoLoader();
                }
                
                setupDeleteConfirmation();
                validateDeductionForm();
                setupValidationHelpers();
                
                // Initialize tooltips
                $("[data-toggle=\"tooltip\"]").tooltip();
            });
        },
        
        // Configurar URLs dinámicamente
        setUrls: function(urls) {
            baseUrls = Object.assign(baseUrls, urls);
        },
        
        // Reinicializar componentes si es necesario
        refreshDataTable: function() {
            const table = $('#deductionsTable');
            if ($.fn.DataTable.isDataTable(table)) {
                table.DataTable().destroy();
                dataTableInitialized = false;
            }
            initializeDataTable();
        },
        
        refreshSelect2: function() {
            const employeeSelect = $('#employee_id');
            if (employeeSelect.data('select2')) {
                employeeSelect.select2('destroy');
                select2Initialized = false;
            }
            initializeSelect2();
        },
        
        // Función para inicializar Select2 manualmente (útil para edit)
        initSelect2: function() {
            if (baseUrls.searchEmployees) {
                initializeSelect2();
                setupEmployeeInfoLoader();
            }
        },
        
        // Estado del módulo
        isInitialized: function() {
            return dataTableInitialized;
        }
    };
})();

// Auto-inicialización del módulo (URLs se configuran desde las vistas)
DeductionsModule.init();