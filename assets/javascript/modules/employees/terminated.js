/**
 * Módulo para gestión de empleados terminados
 */
const TerminatedEmployeesModule = {
    // URLs del módulo (se configuran desde APP_CONFIG)
    urls: window.APP_CONFIG?.urls || {},
    table: null,

    // Inicializar módulo
    init() {
        this.initDataTable();
        this.initTooltips();
    },

    initDataTable() {
        const urls = this.urls;

        this.table = $("#terminatedEmployeesTable").DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": urls.panel_url + "/employees/terminated-datatables-ajax" || "/panel/employees/terminated-datatables-ajax",
                "type": "GET",
                "error": (xhr, error, code) => {
                    console.error("Error DataTables:", xhr, error, code);
                    alert("Error al cargar datos de empleados terminados. Revise la consola para más detalles.");
                }
            },
            "columns": [
                { "data": 0, "orderable": false }, // Foto
                { "data": 1 }, // ID Empleado
                { "data": 2 }, // Nombre
                { "data": 3 }, // Cédula
                { "data": 4 }, // Cargo/Posición
                { "data": 5 }, // Fecha Terminación
                { "data": 6 }, // Motivo
                { "data": 7, "orderable": false } // Acciones
            ],
            "order": [[5, "desc"]], // Ordenar por fecha de terminación (más recientes primero)
            "pageLength": 25,
            "responsive": true,
            "language": {
                "url": urls.datatables_spanish || "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json",
                "emptyTable": "No hay empleados dados de baja en el sistema",
                "zeroRecords": "No se encontraron empleados terminados que coincidan con la búsqueda"
            },
            "dom": "Bfrtip",
            "buttons": [
                {
                    "extend": "excel",
                    "text": "Exportar Excel",
                    "className": "btn btn-success btn-sm",
                    "exportOptions": {
                        "columns": [1, 2, 3, 4, 5, 6] // Excluir foto y acciones
                    }
                },
                {
                    "extend": "pdf",
                    "text": "Exportar PDF",
                    "className": "btn btn-danger btn-sm",
                    "exportOptions": {
                        "columns": [1, 2, 3, 4, 5, 6] // Excluir foto y acciones
                    },
                    "customize": (doc) => {
                        doc.content[1].table.widths = ["15%", "25%", "15%", "20%", "15%", "10%"];
                        doc.pageSize = "A4";
                        doc.pageOrientation = "landscape";
                        doc.defaultStyle.fontSize = 8;
                        doc.styles.tableHeader.fontSize = 9;
                        doc.content[0].text = "Empleados Dados de Baja - " + new Date().toLocaleDateString();
                    }
                }
            ]
        });
    },

    initTooltips() {
        $('[data-toggle="tooltip"]').tooltip();
    },

    destroy() {
        if (this.table) {
            this.table.destroy();
            this.table = null;
        }
    }
};

// Inicializar cuando el DOM esté listo
$(document).ready(function() {
    TerminatedEmployeesModule.init();
    window.terminatedEmployeesModule = TerminatedEmployeesModule;
});