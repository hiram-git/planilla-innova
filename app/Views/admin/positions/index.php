<?php
/**
 * Vista: Lista de Posiciones
 */
$title = 'Posiciones';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-sitemap"></i> Lista de Posiciones
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal">
                        <i class="fas fa-plus"></i> Agregar Posición
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="positionsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Código</th>
                                <th>Cargo</th>
                                <th>Sueldo</th>
                                <th>Partida</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($positions as $position): ?>
                                <tr>
                                    <td><?= htmlspecialchars($position['posid']) ?></td>
                                    <td><?= htmlspecialchars($position['codigo']) ?></td>
                                    <td><?= htmlspecialchars($position['descripcion_cargo'] ?? 'Sin cargo') ?></td>
                                    <td>$<?= number_format($position['sueldo'], 2) ?></td>
                                    <td><?= htmlspecialchars($position['descripcion_partida'] ?? 'Sin partida') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-btn" data-id="<?= $position['posid'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn" 
                                                data-id="<?= $position['posid'] ?>" 
                                                data-description="<?= htmlspecialchars($position['codigo']) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Agregar Posición</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="form-group">
                        <label for="codigo">Código *</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="codigo" name="codigo" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="generateCodeBtn" title="Generar código sugerido">
                                    <i class="fas fa-magic"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Código sugerido: <span id="suggestedCode"></span></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="partida">Partida *</label>
                        <select class="form-control" id="partida" name="partida" required>
                            <option value="">Seleccione una partida</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="cargo">Cargo *</label>
                        <select class="form-control" id="cargo" name="cargo" required>
                            <option value="">Seleccione un cargo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="funcion">Función *</label>
                        <select class="form-control" id="funcion" name="funcion" required>
                            <option value="">Seleccione una función</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="sueldo">Sueldo *</label>
                        <input type="number" class="form-control" id="sueldo" name="sueldo" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Editar Posición</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="form-group">
                        <label for="edit_codigo">Código *</label>
                        <input type="text" class="form-control" id="edit_codigo" name="edit_codigo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_partida">Partida *</label>
                        <select class="form-control" id="edit_partida" name="edit_partida" required>
                            <option value="">Seleccione una partida</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_cargo">Cargo *</label>
                        <select class="form-control" id="edit_cargo" name="edit_cargo" required>
                            <option value="">Seleccione un cargo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_funcion">Función *</label>
                        <select class="form-control" id="edit_funcion" name="edit_funcion" required>
                            <option value="">Seleccione una función</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_sueldo">Sueldo *</label>
                        <input type="number" class="form-control" id="edit_sueldo" name="edit_sueldo" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirmar Eliminación</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar la posición?</p>
                <h5 id="deletePositionName" class="text-center font-weight-bold"></h5>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
            </div>
        </div>
    </div>
</div>


<style>
.table th, .table td {
    vertical-align: middle;
}
.btn-group .btn {
    margin-right: 2px;
}
.btn-group .btn:last-child {
    margin-right: 0;
}
</style>

<?php 
$styles = '<link rel="stylesheet" href="' . \App\Core\UrlHelper::url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">';

$scripts = '
<script src="' . \App\Core\UrlHelper::url('plugins/datatables/jquery.dataTables.min.js') . '"></script>
<script src="' . \App\Core\UrlHelper::url('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') . '"></script>
<script src="' . url('assets/js/modules/positions.js', false) . '"></script>
<script>
// Configurar URLs para el módulo
PositionsModule.setUrls({
    create: \'' . \App\Core\UrlHelper::position('create') . '\',
    update: \'' . \App\Core\UrlHelper::position() . '/{id}/update\',
    delete: \'' . \App\Core\UrlHelper::position() . '/{id}/delete\',
    getNextCode: \'' . \App\Core\UrlHelper::position('getNextCode') . '\',
    getOptions: \'' . \App\Core\UrlHelper::position('getOptions') . '\',
    getRow: \'' . \App\Core\UrlHelper::position('getRow') . '\',
    csrfToken: \'' . $csrf_token . '\'
});
</script>';
?>