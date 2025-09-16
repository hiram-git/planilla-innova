/**
 * Módulo para la vista de detalle de planilla
 * Simplificado - Solo maneja visualización de empleados y regeneración
 */

import { BaseModule } from '../../common/base-module.js';

export class PayrollShowModule extends BaseModule {
    constructor() {
        super();
        this.state = {
            currentPayrollId: null,
            payrollData: {},
            employeesDataTable: null
        };
    }

    /**
     * Inicializar el módulo
     */
    init() {
        super.init();
        this.log('Initializing PayrollShowModule...');
        
        // Establecer configuración desde el config global
        if (window.PAYROLL_CONFIG) {
            this.setConfig(window.PAYROLL_CONFIG);
            this.state.currentPayrollId = window.PAYROLL_CONFIG.id;
            this.state.payrollData = {
                id: window.PAYROLL_CONFIG.id,
                description: window.PAYROLL_CONFIG.description,
                estado: window.PAYROLL_CONFIG.estado
            };
        }

        this.initializeDataTables();
        this.bindEvents();
    }

    /**
     * Inicializar DataTables para empleados
     */
    initializeDataTables() {
        const employeesUrl = this.getConfig('urls.employeesData');
        
        if (!employeesUrl) {
            this.error('No employees URL found in configuration');
            return;
        }

        this.log('Initializing DataTables with URL:', employeesUrl);

        // Configuración del DataTable en español
        const spanishConfig = {
            "emptyTable": "No hay datos disponibles en la tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            "infoEmpty": "Mostrando 0 a 0 de 0 entradas", 
            "infoFiltered": "(filtrado de _MAX_ entradas totales)",
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
            }
        };

        // Verificar si el DataTable ya está inicializado
        if ($.fn.DataTable.isDataTable('#employeesTable')) {
            $('#employeesTable').DataTable().destroy();
            this.log('Destroyed existing DataTable');
        }

        try {
            this.state.employeesDataTable = $("#employeesTable").DataTable({
                "processing": true,
                "serverSide": true,
                "language": spanishConfig,
                "ajax": {
                    "url": employeesUrl,
                    "dataSrc": function(json) {
                        console.log("DataTable AJAX response:", json);
                        if (json.error) {
                            console.error("Server error:", json.error);
                        }
                        return json.data || [];
                    },
                    "error": function(xhr, error, code) {
                        console.error("DataTable AJAX error:", {xhr, error, code});
                    }
                },
                "columns": [
                    { 
                        "title": "Empleado", 
                        "data": null,
                        "render": function(data, type, row) {
                            return row[0] || row.employee_name || 'N/A';
                        }
                    },
                    { 
                        "title": "Posición", 
                        "data": null,
                        "render": function(data, type, row) {
                            return row[1] || row.position_name || 'Sin posición';
                        }
                    },
                    { 
                        "title": "Total Ingresos", 
                        "data": null,
                        "render": function(data, type, row) {
                            return row[2] || row.total_ingresos || '$0.00';
                        }
                    },
                    { 
                        "title": "Total Deducciones", 
                        "data": null,
                        "render": function(data, type, row) {
                            return row[3] || row.total_deducciones || '$0.00';
                        }
                    },
                    { 
                        "title": "Salario Neto", 
                        "data": null,
                        "render": function(data, type, row) {
                            return row[4] || row.salario_neto || '$0.00';
                        }
                    },
                    { 
                        "title": "Acciones", 
                        "data": null,
                        "orderable": false,
                        "render": function(data, type, row) {
                            return row[5] || row.actions || '';
                        }
                    }
                ],
                "pageLength": 25,
                "order": [[0, "asc"]],
                "responsive": true,
                "autoWidth": false
            });

            this.log('DataTable initialized successfully');

        } catch (error) {
            this.error('Error initializing DataTable:', error);
        }
    }

    /**
     * Enlazar eventos de la interfaz
     */
    bindEvents() {
        // Solo eventos para regenerar empleados ya que eliminamos los botones de cambio de estado
        this.bindEvent('.btn-regenerate-employee', 'click', this.handleRegenerateEmployee.bind(this));
    }

    /**
     * Manejar regeneración de empleado específico
     */
    handleRegenerateEmployee(e) {
        e.preventDefault();
        
        const employeeId = $(e.currentTarget).data('employee-id');
        const payrollId = this.state.currentPayrollId;
        
        if (!employeeId || !payrollId) {
            this.showError('Datos faltantes para regenerar empleado');
            return;
        }

        if (!confirm('¿Está seguro que desea regenerar este empleado? Esto eliminará sus datos actuales y los recalculará.')) {
            return;
        }

        const regenerateUrl = this.getConfig('urls.regenerateEmployee');
        
        this.makeAjaxRequest(regenerateUrl, 'POST', {
            payroll_id: payrollId,
            employee_id: employeeId,
            csrf_token: this.getConfig('csrf_token')
        })
            .then(response => {
                if (response.success) {
                    this.showSuccess('Empleado regenerado exitosamente');
                    this.refreshDataTable();
                } else {
                    this.showError('Error regenerando empleado: ' + (response.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                this.handleAjaxError(error.xhr, error.error, 'Error regenerando empleado');
            });
    }

    /**
     * Refrescar DataTable de empleados
     */
    refreshDataTable() {
        if (this.state.employeesDataTable) {
            this.state.employeesDataTable.ajax.reload();
        }
    }

    /**
     * Limpiar recursos al destruir el módulo
     */
    destroy() {
        if (this.state.employeesDataTable) {
            this.state.employeesDataTable.destroy();
            this.state.employeesDataTable = null;
        }
        
        super.destroy();
    }
}