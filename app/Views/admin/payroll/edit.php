<?php
/**
 * Vista: Editar Planilla
 */
$title = 'Editar Planilla';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit"></i> Editar Planilla
                </h3>
            </div>
            <form method="POST" action="<?= \App\Core\UrlHelper::payroll($payroll['id'] . '/update') ?>" id="payrollEditForm">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="tipo_planilla_id" value="<?= $payroll['tipo_planilla_id'] ?? '' ?>" id="tipo_planilla_id">
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción de la Planilla *</label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion" required 
                               value="<?= htmlspecialchars($payroll['descripcion'] ?? '') ?>"
                               placeholder="Ej: Planilla Quincena 1 - Enero 2024">
                        <small class="form-text text-muted">Descripción clara e identificable para la planilla</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="frecuencia_id">Frecuencia *</label>
                        <select class="form-control" id="frecuencia_id" name="frecuencia_id" required>
                            <option value="">Seleccione una frecuencia</option>
                            <?php if (!empty($data['frecuencias'])): ?>
                                <?php foreach ($data['frecuencias'] as $frecuencia): ?>
                                    <option value="<?= $frecuencia['id'] ?>" data-codigo="<?= $frecuencia['codigo'] ?>"
                                            <?= (($payroll['frecuencia_id'] ?? '') == $frecuencia['id']) ? ' selected' : '' ?>>
                                        <?= htmlspecialchars($frecuencia['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="form-text text-muted">Frecuencia de aplicación de la planilla</small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Nota:</strong> El tipo de planilla se establece desde el selector en la barra de navegación. 
                        Esta planilla corresponde al tipo: <strong id="payrollTypeDisplay">-</strong>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha">Fecha de Planilla *</label>
                                <input type="date" class="form-control" id="fecha" name="fecha" required 
                                       value="<?= $payroll['fecha'] ?? date('Y-m-d') ?>">
                                <small class="form-text text-muted">Fecha de emisión de la planilla</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select class="form-control" id="estado" name="estado">
                                    <option value="PENDIENTE"<?= (($payroll['estado'] ?? '') == 'PENDIENTE') ? ' selected' : '' ?>>Pendiente</option>
                                    <option value="PROCESADA"<?= (($payroll['estado'] ?? '') == 'PROCESADA') ? ' selected' : '' ?>>Procesada</option>
                                    <option value="CANCELADA"<?= (($payroll['estado'] ?? '') == 'CANCELADA') ? ' selected' : '' ?>>Cancelada</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="periodo_inicio">Fecha Inicio del Período *</label>
                                <input type="date" class="form-control" id="periodo_inicio" name="periodo_inicio" required
                                       value="<?= $payroll['fecha_desde'] ?? '' ?>">
                                <small class="form-text text-muted">Fecha de inicio del período laboral</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="periodo_fin">Fecha Fin del Período *</label>
                                <input type="date" class="form-control" id="periodo_fin" name="periodo_fin" required
                                       value="<?= $payroll['fecha_hasta'] ?? '' ?>">
                                <small class="form-text text-muted">Fecha final del período laboral</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Planilla
                            </button>
                            <a href="<?= \App\Core\UrlHelper::payroll($payroll['id']) ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$scripts = '
<script>
$(document).ready(function() {
    // Mostrar el tipo de planilla actual del navbar
    function displayCurrentPayrollType() {
        const selectedType = window.getSelectedPayrollType ? window.getSelectedPayrollType() : null;
        if (selectedType) {
            $(\'#payrollTypeDisplay\').text(selectedType.name);
        } else {
            $(\'#payrollTypeDisplay\').text(\'No seleccionado\');
        }
    }
    
    // Mostrar tipo al cargar
    displayCurrentPayrollType();
    
    // Actualizar cuando cambie el tipo en navbar
    window.addEventListener(\'payrollTypeChanged\', function(e) {
        displayCurrentPayrollType();
    });
    
    // Validación del formulario
    $(\'#payrollEditForm\').submit(function(e) {
        // Validar que se haya seleccionado un tipo de planilla
        if ($(\'#tipo_planilla_id\').val() === \'\') {
            e.preventDefault();
            alert(\'Debe seleccionar un tipo de planilla.\');
            $(\'#tipo_planilla_id\').focus();
            return false;
        }
        
        // Validar fechas con nombres corregidos
        const fechaInicio = new Date($(\'#periodo_inicio\').val());
        const fechaFin = new Date($(\'#periodo_fin\').val());
        
        if (fechaInicio >= fechaFin) {
            e.preventDefault();
            alert(\'La fecha de inicio debe ser anterior a la fecha fin.\');
            return false;
        }
    });
});
</script>';
?>