<?php
/**
 * Vista: Crear Nueva Planilla
 */
$title = $data['page_title'] ?? 'Nueva Planilla';
$csrf_token = $data['csrf_token'] ?? '';
?>
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-plus"></i> Crear Nueva Planilla
                </h3>
            </div>
            <form method="POST" action="<?= \App\Core\UrlHelper::payroll('store') ?>" id="payrollForm">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token?>">
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción de la Planilla *</label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion" required 
                               placeholder="Ej: Planilla Quincena 1 - Enero 2024">
                        <small class="form-text text-muted">Descripción clara e identificable para la planilla</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Tipo de Planilla:</strong> Se utilizará el tipo seleccionado en la barra de navegación superior.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha">Fecha de Planilla *</label>
                                <input type="date" class="form-control" id="fecha" name="fecha" required value="<?= date('Y-m-d') ?>">
                                <small class="form-text text-muted">Fecha de emisión de la planilla</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="frecuencia_id">Frecuencia *</label>
                                <select class="form-control" id="frecuencia_id" name="frecuencia_id" required>
                                    <option value="">Seleccione una frecuencia</option>
                                    <?php if (!empty($data['frecuencias'])): ?>
                                        <?php foreach ($data['frecuencias'] as $frecuencia): ?>
                                            <option value="<?= $frecuencia['id'] ?>" data-codigo="<?= $frecuencia['codigo'] ?>">
                                                <?= htmlspecialchars($frecuencia['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="periodo_inicio">Fecha Inicio del Período *</label>
                                <input type="date" class="form-control" id="periodo_inicio" name="periodo_inicio" required>
                                <small class="form-text text-muted">Fecha de inicio del período laboral</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="periodo_fin">Fecha Fin del Período *</label>
                                <input type="date" class="form-control" id="periodo_fin" name="periodo_fin" required>
                                <small class="form-text text-muted">Fecha final del período laboral</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Información</h6>
                        <ul class="mb-0">
                            <li>La planilla se creará en estado <strong>PENDIENTE</strong></li>
                            <li>Después de crear, podrá <strong>procesarla</strong> para generar los cálculos</li>
                            <li>El procesamiento incluirá todos los empleados activos del período</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="<?= \App\Core\UrlHelper::payroll('') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Crear Planilla
                            </button>
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
    // Obtener el tipo de planilla seleccionado en el navbar y establecerlo como campo oculto
    function setPayrollTypeFromNavbar() {
        const selectedType = window.getSelectedPayrollType ? window.getSelectedPayrollType() : null;
        if (selectedType) {
            // Crear campo oculto si no existe
            if (!$(\'#hidden_tipo_planilla_id\').length) {
                $(\'<input>\').attr({
                    type: \'hidden\',
                    id: \'hidden_tipo_planilla_id\',
                    name: \'tipo_planilla_id\',
                    value: selectedType.id
                }).appendTo(\'#payrollForm\');
            } else {
                $(\'#hidden_tipo_planilla_id\').val(selectedType.id);
            }
        }
    }
    
    // Establecer tipo de planilla al cargar la página
    setPayrollTypeFromNavbar();
    
    // Escuchar cambios en el tipo de planilla del navbar
    window.addEventListener(\'payrollTypeChanged\', function(e) {
        setPayrollTypeFromNavbar();
    });
    
    // Configurar períodos automáticos
    $(\'#frecuencia_id\').change(function() {
        const selectedOption = $(this).find(\'option:selected\');
        const tipo = selectedOption.data(\'codigo\');
        const fechaHoy = new Date();
        let fechaInicio, fechaFin;
        
        switch(tipo) {
            case \'quincenal\':
                // Primera o segunda quincena
                const dia = fechaHoy.getDate();
                if (dia <= 15) {
                    // Primera quincena
                    fechaInicio = new Date(fechaHoy.getFullYear(), fechaHoy.getMonth(), 1);
                    fechaFin = new Date(fechaHoy.getFullYear(), fechaHoy.getMonth(), 15);
                } else {
                    // Segunda quincena
                    fechaInicio = new Date(fechaHoy.getFullYear(), fechaHoy.getMonth(), 16);
                    fechaFin = new Date(fechaHoy.getFullYear(), fechaHoy.getMonth() + 1, 0);
                }
                break;
                
            case \'mensual\':
                fechaInicio = new Date(fechaHoy.getFullYear(), fechaHoy.getMonth(), 1);
                fechaFin = new Date(fechaHoy.getFullYear(), fechaHoy.getMonth() + 1, 0);
                break;
                
            case \'semanal\':
                // Lunes a domingo de la semana actual
                const diaActual = fechaHoy.getDay();
                const diasAlLunes = diaActual === 0 ? 6 : diaActual - 1;
                fechaInicio = new Date(fechaHoy);
                fechaInicio.setDate(fechaHoy.getDate() - diasAlLunes);
                fechaFin = new Date(fechaInicio);
                fechaFin.setDate(fechaInicio.getDate() + 6);
                break;
                
            default:
                return; // Otras frecuencias, fechas manuales
        }
        
        if (fechaInicio && fechaFin) {
            $(\'#periodo_inicio\').val(fechaInicio.toISOString().split(\'T\')[0]);
            $(\'#periodo_fin\').val(fechaFin.toISOString().split(\'T\')[0]);
            
            // Generar descripción automática
            generarDescripcionAutomatica(tipo, fechaInicio, fechaFin);
        }
    });
    
    function generarDescripcionAutomatica(tipo, inicio, fin) {
        const meses = [\'Enero\', \'Febrero\', \'Marzo\', \'Abril\', \'Mayo\', \'Junio\',
                      \'Julio\', \'Agosto\', \'Septiembre\', \'Octubre\', \'Noviembre\', \'Diciembre\'];
        const mes = meses[inicio.getMonth()];
        const año = inicio.getFullYear();
        
        let descripcion = \'\';
        
        switch(tipo) {
            case \'quincenal\':
                const esSegundaQuincena = inicio.getDate() > 15;
                descripcion = `Planilla ${esSegundaQuincena ? \'Segunda\' : \'Primera\'} Quincena - ${mes} ${año}`;
                break;
            case \'mensual\':
                descripcion = `Planilla Mensual - ${mes} ${año}`;
                break;
            case \'semanal\':
                const semana = Math.ceil(inicio.getDate() / 7);
                descripcion = `Planilla Semanal ${semana} - ${mes} ${año}`;
                break;
        }
        
        if (descripcion) {
            $(\'#descripcion\').val(descripcion);
        }
    }
    
    // Inicializar con primera frecuencia disponible
    const firstFrequency = $(\'#frecuencia_id option[data-codigo="quincenal"]\').first();
    if (firstFrequency.length) {
        $(\'#frecuencia_id\').val(firstFrequency.val()).trigger(\'change\');
    }
    
    // Validación del formulario
    $(\'#payrollForm\').on(\'submit\', function(e) {
        const inicio = new Date($(\'#periodo_inicio\').val());
        const fin = new Date($(\'#periodo_fin\').val());
        
        if (inicio >= fin) {
            e.preventDefault();
            alert(\'La fecha de inicio debe ser anterior a la fecha de fin del período.\');
            return false;
        }
        
        // Verificar que el período no sea muy extenso (más de 2 meses)
        const diffMeses = (fin.getFullYear() - inicio.getFullYear()) * 12 + (fin.getMonth() - inicio.getMonth());
        if (diffMeses > 2) {
            e.preventDefault();
            if (!confirm(\'El período seleccionado es muy extenso (más de 2 meses). ¿Desea continuar?\')) {
                return false;
            }
        }
    });
    
    // Validación en tiempo real de fechas
    $(\'#periodo_inicio, #periodo_fin\').on(\'change\', function() {
        const inicio = $(\'#periodo_inicio\').val();
        const fin = $(\'#periodo_fin\').val();
        
        if (inicio && fin) {
            const fechaInicio = new Date(inicio);
            const fechaFin = new Date(fin);
            
            if (fechaInicio >= fechaFin) {
                $(this).addClass(\'is-invalid\');
                if (!$(this).next(\'.invalid-feedback\').length) {
                    $(this).after(\'<div class="invalid-feedback">La fecha de inicio debe ser anterior a la fecha fin</div>\');
                }
            } else {
                $(\'#periodo_inicio, #periodo_fin\').removeClass(\'is-invalid\');
                $(\'.invalid-feedback\').remove();
            }
        }
    });
    
});
</script>';
?>

<style>
.form-group label {
    font-weight: 600;
    color: #495057;
}
.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.alert-info {
    border-left: 4px solid #17a2b8;
}
.card-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
}
.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
}
</style>