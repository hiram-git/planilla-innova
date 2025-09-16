<?php
$page_title = 'Editar Posición';

$content = '
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Editar Posición</h3>
            </div>
            <form method="POST" action="' . \App\Core\UrlHelper::position($position['posid'] ?? $position['id']) . '">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="' . $csrf_token . '">';

if (isset($_SESSION['errors'])) {
    $content .= '
                    <div class="alert alert-danger">
                        <ul class="mb-0">';
    foreach ($_SESSION['errors'] as $error) {
        $content .= '<li>' . htmlspecialchars($error) . '</li>';
    }
    $content .= '
                        </ul>
                    </div>';
    unset($_SESSION['errors']);
}

$oldData = $_SESSION['old_data'] ?? $position;
unset($_SESSION['old_data']);

$content .= '
                    <div class="form-group">
                        <label for="edit_codigo">Código *</label>
                        <input type="text" class="form-control" id="edit_codigo" name="edit_codigo" 
                               value="' . htmlspecialchars($oldData['codigo'] ?? '') . '" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_partida">Partida *</label>
                        <select class="form-control" id="edit_partida" name="edit_partida" required>
                            <option value="">Seleccione una partida</option>';
foreach ($partidas as $partida) {
    $selected = (isset($oldData['id_partida']) && $oldData['id_partida'] == $partida['id']) ? 'selected' : '';
    $content .= '<option value="' . $partida['id'] . '" ' . $selected . '>' . htmlspecialchars($partida['partida'] ?? $partida['descripcion']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_cargo">Cargo *</label>
                        <select class="form-control" id="edit_cargo" name="edit_cargo" required>
                            <option value="">Seleccione un cargo</option>';

foreach ($cargos as $cargo) {
    $selected = (isset($oldData['id_cargo']) && $oldData['id_cargo'] == $cargo['id']) ? 'selected' : '';
    $content .= '<option value="' . $cargo['id'] . '" ' . $selected . '>' . htmlspecialchars($cargo['descripcion']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_funcion">Función *</label>
                        <select class="form-control" id="edit_funcion" name="edit_funcion" required>
                            <option value="">Seleccione una función</option>';

foreach ($funciones as $funcion) {
    $selected = (isset($oldData['id_funcion']) && $oldData['id_funcion'] == $funcion['id']) ? 'selected' : '';
    $content .= '<option value="' . $funcion['id'] . '" ' . $selected . '>' . htmlspecialchars($funcion['descripcion']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_sueldo">Sueldo *</label>
                        <input type="number" class="form-control" id="edit_sueldo" name="edit_sueldo" 
                               step="0.01" min="0" value="' . htmlspecialchars($oldData['sueldo'] ?? '') . '" required>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Actualizar
                    </button>
                    <a href="' . \App\Core\UrlHelper::position() . '" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>';

$scripts = '';
$styles = '';

include __DIR__ . '/../../layouts/admin.php';
?>