<?php
use App\Helpers\PermissionHelper;

$page_title = 'Empleados Dados de Baja';

// Obtener tipo de empresa para mostrar columna condicional
$companyModel = new \App\Models\Company();
$companyConfig = $companyModel->getCompanyConfig();
$isPublicInstitution = ($companyConfig['tipo_institucion'] ?? 'privada') === 'publica';
$columnHeader = $isPublicInstitution ? 'Posición' : 'Cargo';

$content = '
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-user-times mr-2"></i>
                    Empleados Dados de Baja
                </h1>
            </div>
            <div class="col-sm-6">
                <div class="row">
                    <div class="col-12 mb-2">
                        <a href="' . url('/panel/employees') . '"
                           class="btn btn-primary btn-sm float-right">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver a Empleados Activos
                        </a>
                    </div>
                    <div class="col-12">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="' . url('/panel') . '">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="' . url('/panel/employees') . '">Empleados</a></li>
                            <li class="breadcrumb-item active">Dados de Baja</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <!-- Información sobre empleados terminados -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Empleados Terminados:</strong> Esta vista muestra únicamente los empleados que han sido dados de baja o terminados.
            Los empleados activos se encuentran en la <a href="' . url('/panel/employees') . '" class="alert-link">vista principal de empleados</a>.
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-2"></i>
                            Lista de Empleados Terminados
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="terminatedEmployeesTable">
                                <thead>
                                    <tr>
                                        <th>Foto</th>
                                        <th>ID Empleado</th>
                                        <th>Nombre</th>
                                        <th>Cédula</th>
                                        <th>' . $columnHeader . '</th>
                                        <th>Fecha Terminación</th>
                                        <th>Motivo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los datos se cargarán vía AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>';

// Scripts para el módulo usando sistema modular
$scriptFiles = [
    '/plugins/datatables/jquery.dataTables.min.js',
    '/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    '/plugins/datatables-buttons/js/dataTables.buttons.min.js',
    '/plugins/datatables-buttons/js/buttons.bootstrap4.min.js',
    '/plugins/jszip/jszip.min.js',
    '/plugins/pdfmake/pdfmake.min.js',
    '/plugins/pdfmake/vfs_fonts.js',
    '/plugins/datatables-buttons/js/buttons.html5.min.js'
];

use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();
$scripts = $jsConfig . "\n" . JavaScriptHelper::renderScriptTags($scriptFiles);

$scripts .= '
<script>
$(document).ready(function() {
    // Configurar DataTable para empleados terminados
    $("#terminatedEmployeesTable").DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "' . url('/panel/employees/terminated-datatables-ajax') . '",
            "type": "GET",
            "error": function(xhr, error, code) {
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
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json",
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
                "customize": function(doc) {
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

    // Initialize tooltips
    $("[data-toggle=\'tooltip\']").tooltip();
});
</script>';

$styles = '
<link rel="stylesheet" href="' . url('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . url('/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') . '">';

include __DIR__ . '/../../layouts/admin.php';
?>