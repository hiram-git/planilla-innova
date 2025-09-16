<?php
$page_title = 'Agregar Posición';

$content = '
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Agregar Nueva Posición</h3>
            </div>
            <form method="POST" action="' . \App\Core\UrlHelper::position('store') . '">
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

$oldData = $_SESSION['old_data'] ?? [];
unset($_SESSION['old_data']);

$content .= '
                    <div class="form-group">
                        <label for="codigo">Código *</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="codigo" name="codigo" 
                                   value="' . htmlspecialchars($oldData['codigo'] ?? ($suggested_code ?? '')) . '" required>
                            ' . (isset($suggested_code) ? '<div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById(\'codigo\').value=\'' . $suggested_code . '\'" title="Usar código sugerido">
                                    <i class="fas fa-magic"></i>
                                </button>
                            </div>' : '') . '
                        </div>
                        ' . (isset($suggested_code) ? '<small class="text-muted">Código sugerido: <strong>' . $suggested_code . '</strong></small>' : '') . '
                    </div>
                    
                    <div class="form-group">
                        <label for="partida">Partida *</label>
                        <select class="form-control" id="partida" name="partida" required>
                            <option value="">Seleccione una partida</option>';

foreach ($partidas as $partida) {
    $selected = (isset($oldData['partida']) && $oldData['partida'] == $partida['id']) ? 'selected' : '';
    $content .= '<option value="' . $partida['id'] . '" ' . $selected . '>' . htmlspecialchars($partida['partida'] ?? $partida['descripcion']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="cargo">Cargo *</label>
                        <select class="form-control" id="cargo" name="cargo" required>
                            <option value="">Seleccione un cargo</option>';

foreach ($cargos as $cargo) {
    $selected = (isset($oldData['cargo']) && $oldData['cargo'] == $cargo['id']) ? 'selected' : '';
    $content .= '<option value="' . $cargo['id'] . '" ' . $selected . '>' . htmlspecialchars($cargo['descripcion']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="funcion">Función *</label>
                        <select class="form-control" id="funcion" name="funcion" required>
                            <option value="">Seleccione una función</option>';

foreach ($funciones as $funcion) {
    $selected = (isset($oldData['funcion']) && $oldData['funcion'] == $funcion['id']) ? 'selected' : '';
    $content .= '<option value="' . $funcion['id'] . '" ' . $selected . '>' . htmlspecialchars($funcion['descripcion']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="sueldo">Sueldo *</label>
                        <input type="number" class="form-control" id="sueldo" name="sueldo" 
                               step="0.01" min="0" value="' . htmlspecialchars($oldData['sueldo'] ?? '') . '" required>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
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