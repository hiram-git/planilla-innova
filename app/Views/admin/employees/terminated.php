<?php
use App\Helpers\PermissionHelper;

$page_title = 'Empleados Dados de Baja';

// Obtener tipo de empresa para mostrar columna condicional
$companyModel = new \App\Models\Company();
$companyConfig = $companyModel->getCompanyConfig();
$isEmpresaConPosiciones = ($companyConfig['tipo_institucion'] ?? 'privada') === 'publica';
$columnHeader = 'Cargo';

$content = '
<!-- Botón para volver a empleados activos -->
<div class="row mb-3">
    <div class="col-12">
        <a href="' . url('/panel/employees') . '" class="btn btn-primary btn-sm">
            <i class="fas fa-arrow-left mr-2"></i>
            Volver a Empleados Activos
        </a>
    </div>
</div>

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
</div>';

// Scripts para el módulo usando sistema modular
$scriptFiles = [
    '/plugins/datatables/jquery.dataTables.min.js',
    '/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    '/plugins/datatables-buttons/js/dataTables.buttons.min.js',
    '/plugins/datatables-buttons/js/buttons.bootstrap4.min.js',
    '/plugins/jszip/jszip.min.js',
    '/plugins/pdfmake/pdfmake.min.js',
    '/plugins/pdfmake/vfs_fonts.js',
    '/plugins/datatables-buttons/js/buttons.html5.min.js',
    '/assets/javascript/modules/employees/terminated.js'
];

use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();
$scripts = $jsConfig . "\n" . JavaScriptHelper::renderScriptTags($scriptFiles);

$styles = '
<link rel="stylesheet" href="' . url('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . url('/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') . '">';

include __DIR__ . '/../../layouts/admin.php';
?>