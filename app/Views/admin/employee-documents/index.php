<?php
$page_title = 'Documentos laborales';

$companyModel = new \App\Models\Company();
$companyConfig = $companyModel->getCompanyConfig();
$isEmpresaConPosiciones = ($companyConfig['tipo_institucion'] ?? 'privada') === 'publica';
$columnHeader = $isEmpresaConPosiciones ? 'Posicion' : 'Cargo';

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Documentos laborales por empleado</h3>
                <div class="card-tools">
                    <span class="badge badge-success"><i class="fas fa-check"></i> Sistema Activo</span>
                </div>
            </div>
            <div class="card-body">
                <div class="callout callout-success">
                    <h5><i class="icon fas fa-check"></i> Generación de Documentos Disponible</h5>
                    <p class="mb-0">Seleccione un empleado de la tabla y haga clic en el botón "Documentos" para generar cartas de trabajo y contratos en formato PDF o Word.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="employeeDocumentsTable">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>ID Empleado</th>
                                <th>Nombre</th>
                                <th>Cedula</th>
                                <th>' . $columnHeader . '</th>
                                <th>Documentos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargaran via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>';

$scriptFiles = [
    '/plugins/datatables/jquery.dataTables.min.js',
    '/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    '/assets/javascript/modules/employee-documents/index.js'
];

use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();
$scripts = $jsConfig . "\n" . JavaScriptHelper::renderScriptTags($scriptFiles);

$styles = '
<link rel="stylesheet" href="' . url('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">';

include __DIR__ . '/../../layouts/admin.php';
?>