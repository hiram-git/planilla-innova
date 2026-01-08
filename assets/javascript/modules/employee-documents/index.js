/**
 * Modulo: Documentos laborales
 * Funcionalidades: DataTables AJAX, filtro por tipo de planilla
 */

$(document).ready(function() {
    let documentsTable = null;

    const EmployeeDocumentsModule = {
        urls: window.APP_CONFIG?.urls || {},

        init() {
            this.initDataTable();
            this.initPayrollTypeListener();
        },

        initDataTable() {
            const urls = this.urls;

            documentsTable = $("#employeeDocumentsTable").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: urls.panel_url + "/employee-documents/datatables-ajax" || "/panel/employee-documents/datatables-ajax",
                    type: "GET",
                    data: function(d) {
                        const selectedType = window.getSelectedPayrollType ? window.getSelectedPayrollType() : null;
                        if (selectedType) {
                            d.tipo_planilla_id = selectedType.id;
                        }
                        return d;
                    },
                    error: function(xhr, error, code) {
                        console.error("Error DataTables:", xhr, error, code);
                        alert("Error al cargar datos de empleados. Revise la consola para mas detalles.");
                    }
                },
                columns: [
                    { data: 0, orderable: false },
                    { data: 1 },
                    { data: 2 },
                    { data: 3 },
                    { data: 4 },
                    { data: 5, orderable: false }
                ],
                language: {
                    url: urls.datatables_spanish || "/assets/js/datatables-spanish.json",
                    processing: "Procesando...",
                    loadingRecords: "Cargando empleados..."
                },
                order: [[1, "asc"]],
                pageLength: 25,
                responsive: true
            });
        },

        initPayrollTypeListener() {
            window.addEventListener("payrollTypeChanged", function() {
                if (documentsTable) {
                    documentsTable.ajax.reload();
                }
            });
        }
    };

    window.EmployeeDocumentsModule = EmployeeDocumentsModule;
    EmployeeDocumentsModule.init();
});