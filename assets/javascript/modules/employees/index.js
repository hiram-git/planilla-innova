/**
 * Módulo: Gestión de Empleados
 * Funcionalidades: DataTables AJAX, botones editar/eliminar, modal confirmación, filtros
 */

$(document).ready(function() {
    // Variables globales del módulo
    let employeesTable = null;
    let deleteEmployeeId = null;

    // Configuración del módulo
    const EmployeesModule = {
        // URLs del módulo (se configuran desde APP_CONFIG)
        urls: window.APP_CONFIG?.urls || {},

        // Inicializar módulo
        init() {
            this.initDataTable();
            this.initEventHandlers();
            this.initPayrollTypeListener();
        },

        // Configurar DataTable de empleados
        initDataTable() {
            const urls = this.urls;
            
            employeesTable = $("#employeesTable").DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": urls.panel_url + "/employees/datatables-ajax" || "/panel/employees/datatables-ajax",
                    "type": "GET",
                    "data": function(d) {
                        // Agregar el tipo de planilla seleccionado como parámetro
                        const selectedType = window.getSelectedPayrollType ? window.getSelectedPayrollType() : null;
                        if (selectedType) {
                            d.tipo_planilla_id = selectedType.id;
                        }
                        return d;
                    },
                    "error": function(xhr, error, code) {
                        console.error("Error DataTables:", xhr, error, code);
                        alert("Error al cargar datos de empleados. Revise la consola para más detalles.");
                    }
                },
                "columns": [
                    { "data": 0, "orderable": false }, // Foto
                    { "data": 1 }, // ID Empleado
                    { "data": 2 }, // Nombre
                    { "data": 3 }, // Cédula
                    { "data": 4 }, // Posición
                    { "data": 5, "orderable": false }, // Horario
                    { "data": 6 }, // Fecha Creación
                    { "data": 7, "orderable": false } // Acciones
                ],
                "language": {
                    "url": urls.datatables_spanish || "/assets/js/datatables-spanish.json",
                    "processing": "Procesando...",
                    "loadingRecords": "Cargando empleados..."
                },
                "order": [[ 1, "asc" ]], // Ordenar por ID Empleado
                "pageLength": 25,
                "responsive": true
            });
        },

        // Configurar event handlers
        initEventHandlers() {
            const urls = this.urls;

            // Botón editar empleado
            $("#employeesTable").on("click", ".edit-btn", function() {
                const employeeId = $(this).data("id");
                const editUrl = urls.panel_url ? 
                    `${urls.panel_url}/employees/edit/${employeeId}` : 
                    `/panel/employees/edit/${employeeId}`;
                window.location.href = editUrl;
            });

            // Botón eliminar empleado
            $("#employeesTable").on("click", ".delete-btn", function() {
                const employeeId = $(this).data("id");
                const employeeName = $(this).data("name");
                deleteEmployeeId = employeeId;
                $("#deleteModal .modal-body p").text(`¿Está seguro que desea eliminar el empleado ${employeeName}?`);
                $("#deleteModal").modal("show");
            });

            // Confirmar eliminación
            $("#confirmDelete").click(function() {
                if (deleteEmployeeId) {
                    const deleteUrl = urls.panel_url ? 
                        `${urls.panel_url}/employees/${deleteEmployeeId}/delete` : 
                        `/panel/employees/${deleteEmployeeId}/delete`;
                    window.location.href = deleteUrl;
                }
            });
        },

        // Escuchar cambios en el tipo de planilla
        initPayrollTypeListener() {
            window.addEventListener("payrollTypeChanged", function(e) {
                if (employeesTable) {
                    employeesTable.ajax.reload();
                }
            });
        },

        // Método público para recargar tabla
        reloadTable() {
            if (employeesTable) {
                employeesTable.ajax.reload();
            }
        }
    };

    // Exponer métodos públicos
    window.EmployeesModule = EmployeesModule;

    // Inicializar módulo
    EmployeesModule.init();
});