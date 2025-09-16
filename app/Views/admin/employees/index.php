<?php
use App\Helpers\PermissionHelper;

$page_title = 'Gestión de Empleados';

// Obtener tipo de empresa para mostrar columna condicional
$companyModel = new \App\Models\Company();
$companyConfig = $companyModel->getCompanyConfig();
$isPublicInstitution = ($companyConfig['tipo_institucion'] ?? 'privada') === 'publica';
$columnHeader = $isPublicInstitution ? 'Posición' : 'Cargo';

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Empleados</h3>
                <div class="card-tools">
                    ' . PermissionHelper::createButton('employees', [
                        'url' => url('/panel/employees/create'),
                        'text' => 'Agregar Empleado',
                        'class' => 'btn btn-primary btn-sm'
                    ]) . '
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="employeesTable">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>ID Empleado</th>
                                <th>Nombre</th>
                                <th>Cédula</th>
                                <th>' . $columnHeader . '</th>
                                <th>Horario</th>
                                <th>Fecha Creación</th>
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

<!-- Modal de confirmación -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirmar Eliminación</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar este empleado?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
            </div>
        </div>
    </div>
</div>';

// Scripts para el módulo usando sistema modular
$scriptFiles = [
    '/plugins/datatables/jquery.dataTables.min.js',
    '/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    '/assets/javascript/modules/employees/index.js'
];

use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();
$scripts = $jsConfig . "\n" . JavaScriptHelper::renderScriptTags($scriptFiles);

$styles = '
<link rel="stylesheet" href="' . url('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">';

include __DIR__ . '/../../layouts/admin.php';
?>