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
            this.initDocumentGeneration();
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
                responsive: true,
                drawCallback: function(settings) {
                    // GSAP: Animar filas después de cada draw
                    if (typeof window.animateEmployeeDocumentsTableRows === 'function') {
                        // Pequeño delay para asegurar que el DOM esté listo
                        setTimeout(function() {
                            window.animateEmployeeDocumentsTableRows();
                        }, 50);
                    }
                }
            });
        },

        initPayrollTypeListener() {
            window.addEventListener("payrollTypeChanged", function() {
                if (documentsTable) {
                    documentsTable.ajax.reload();
                }
            });
        },

        initDocumentGeneration() {
            const self = this;

            // Delegar eventos de clic en los botones de generación (porque DataTables recarga la tabla)
            $('#employeeDocumentsTable').on('click', '.generate-document', function(e) {
                e.preventDefault();

                const $btn = $(this);
                const employeeId = $btn.data('employee-id');
                const documentType = $btn.data('type');
                const format = $btn.data('format');

                if (!employeeId || !documentType || !format) {
                    alert('Error: Faltan parámetros para generar el documento');
                    return;
                }

                self.generateDocument(employeeId, documentType, format);
            });
        },

        generateDocument(employeeId, documentType, format) {
            const url = `${this.urls.panel_url}/employee-documents/generate?employee_id=${employeeId}&document_type=${documentType}&format=${format}`;

            // Abrir en una nueva ventana/pestaña para descarga
            window.open(url, '_blank');
        }
    };

    window.EmployeeDocumentsModule = EmployeeDocumentsModule;
    EmployeeDocumentsModule.init();
});