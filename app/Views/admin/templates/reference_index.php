<?php
$page_title = 'Gestión de ' . $plural_name;

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de ' . $plural_name . '</h3>
                <div class="card-tools">
                    <a href="' . url('panel/' . $route_name . '/create', false) . '" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Agregar ' . $singular_name . '
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="referenceTable">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>';

foreach ($items as $item) {
    $checked = $item['activo'] ? 'checked' : '';
    $description = !empty($item['descripcion']) ? htmlspecialchars($item['descripcion']) : '<em>Sin descripción</em>';
    
    $content .= '
                            <tr>
                                <td><strong>' . htmlspecialchars($item['codigo']) . '</strong></td>
                                <td>' . htmlspecialchars($item['nombre']) . '</td>
                                <td>' . $description . '</td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input status-toggle" 
                                               id="status-' . $item['id'] . '" 
                                               data-id="' . $item['id'] . '" 
                                               ' . $checked . '>
                                        <label class="custom-control-label" for="status-' . $item['id'] . '"></label>
                                    </div>
                                </td>
                                <td>' . date('d/m/Y', strtotime($item['created_at'])) . '</td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-btn" data-id="' . $item['id'] . '">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="' . $item['id'] . '" data-name="' . htmlspecialchars($item['nombre']) . '">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>';
}

$content .= '
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
                <p>¿Está seguro que desea eliminar este registro?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
            </div>
        </div>
    </div>
</div>';

// Configuración JavaScript usando el nuevo sistema modular
use App\Helpers\JavaScriptHelper;

$jsConfig = JavaScriptHelper::renderConfigScript();
$scriptFiles = [
    '/plugins/datatables/jquery.dataTables.min.js',
    '/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    '/assets/javascript/modules/reference-index.js'
];

$styles = '
<link rel="stylesheet" href="' . url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css', false) . '">';

include __DIR__ . '/../../layouts/admin.php';
?>