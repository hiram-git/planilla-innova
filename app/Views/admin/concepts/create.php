<?php
$title = 'Nuevo Concepto';
?>

<div class="row">
    <div class="col-sm-12">
        <div class="float-right">
            <a href="<?= \App\Core\UrlHelper::concept('') ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver a Conceptos
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-plus-circle"></i> Crear Nuevo Concepto de Nómina
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info">
                        Concepto de Nómina
                    </span>
                </div>
            </div>
            <form id="conceptForm" action="<?= \App\Core\UrlHelper::concept('store') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="card-body">
                    <!-- Información Básica -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="concepto">Concepto</label>
                                <input type="text" class="form-control" id="concepto" name="concepto"
                                       placeholder="Código del concepto">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="descripcion">Descripción *</label>
                                <input type="text" class="form-control" id="descripcion" name="descripcion" required
                                       placeholder="Descripción del concepto">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tipo de Concepto y Unidad -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo de Concepto *</label><br>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_concepto" id="asignacion" value="A">
                                    <label class="form-check-label" for="asignacion">Asignación</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_concepto" id="deduccion" value="D">
                                    <label class="form-check-label" for="deduccion">Deducción</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_concepto" id="patronal" value="C">
                                    <label class="form-check-label" for="patronal">Patronal</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Unidad</label><br>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="unidad" id="monto" value="monto" checked>
                                    <label class="form-check-label" for="monto">Monto</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="unidad" id="horas" value="horas">
                                    <label class="form-check-label" for="horas">Horas</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="unidad" id="porcentaje" value="porcentaje">
                                    <label class="form-check-label" for="porcentaje">%</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="unidad" id="dias" value="dias">
                                    <label class="form-check-label" for="dias">Días</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fórmula de Cálculo -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="formula">Fórmula de Cálculo</label>
                                <div class="input-group">
                                    <textarea class="form-control" id="formula" name="formula" rows="3"
                                              placeholder="Ej: SALARIO * 0.125" required></textarea>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-info" type="button" id="testFormula">
                                            <i class="fas fa-play"></i> Probar
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Variables disponibles: SALARIO, HORAS, ANTIGUEDAD, FICHA. Funciones: SI(), ACREEDOR()</small>
                                <div id="formulaValidation" class="alert" style="display: none; margin-top: 10px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Opciones de Configuración -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="imprime_detalles" id="imprime_detalles" value="1">
                                    <label class="form-check-label" for="imprime_detalles">¿Se imprimen detalles?</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="prorratea" id="prorratea" value="1">
                                    <label class="form-check-label" for="prorratea">¿Se prorratea?</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="modifica_valor" id="modifica_valor" value="1">
                                    <label class="form-check-label" for="modifica_valor">¿Permite modificar el valor?</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="valor_referencia" id="valor_referencia" value="1">
                                    <label class="form-check-label" for="valor_referencia">¿Valor de referencia?</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="monto_calculo" id="monto_calculo" value="1">
                                    <label class="form-check-label" for="monto_calculo">¿Usar cálculo de monto?</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="monto_cero" id="monto_cero" value="1">
                                    <label class="form-check-label" for="monto_cero">¿Permitir monto cero?</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuraciones avanzadas con checkboxes agrupados -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5><i class="fas fa-cogs"></i> Configuraciones Avanzadas</h5>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-weight-bold">
                                    <i class="fas fa-calendar-alt text-primary"></i> Tipos de Planilla
                                </label>
                                <div class="card card-outline card-primary" style="min-height: 120px;">
                                    <div class="card-body p-2">
                                        <?php if (!empty($tipos_planilla)): ?>
                                            <?php foreach ($tipos_planilla as $tipo): ?>
                                                <?php $checked = ($tipo['codigo'] ?? '') === 'quincenal' ? 'checked' : ''; ?>
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input" type="checkbox" name="tipos_planilla[]" 
                                                           id="tipo_<?= $tipo['id'] ?>" value="<?= $tipo['id'] ?>" <?= $checked ?>>
                                                    <label class="form-check-label" for="tipo_<?= $tipo['id'] ?>">
                                                        <?= htmlspecialchars($tipo['nombre']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-weight-bold">
                                    <i class="fas fa-clock text-success"></i> Frecuencias
                                </label>
                                <div class="card card-outline card-success" style="min-height: 120px;">
                                    <div class="card-body p-2">
                                        <?php if (!empty($frecuencias)): ?>
                                            <?php foreach ($frecuencias as $frecuencia): ?>
                                                <?php $checked = ($frecuencia['codigo'] ?? '') === 'siempre' ? 'checked' : ''; ?>
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input" type="checkbox" name="frecuencias[]" 
                                                           id="freq_<?= $frecuencia['id'] ?>" value="<?= $frecuencia['id'] ?>" <?= $checked ?>>
                                                    <label class="form-check-label" for="freq_<?= $frecuencia['id'] ?>">
                                                        <?= htmlspecialchars($frecuencia['nombre']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-weight-bold">
                                    <i class="fas fa-user-check text-warning"></i> Situaciones del Empleado
                                </label>
                                <div class="card card-outline card-warning" style="min-height: 120px;">
                                    <div class="card-body p-2">
                                        <?php if (!empty($situaciones)): ?>
                                            <?php foreach ($situaciones as $situacion): ?>
                                                <?php $checked = ($situacion['codigo'] ?? '') === 'activo' ? 'checked' : ''; ?>
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input" type="checkbox" name="situaciones[]" 
                                                           id="sit_<?= $situacion['id'] ?>" value="<?= $situacion['id'] ?>" <?= $checked ?>>
                                                    <label class="form-check-label" for="sit_<?= $situacion['id'] ?>">
                                                        <?= htmlspecialchars($situacion['nombre']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuración de Acumulados -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card card-outline card-success">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-database text-success"></i> Configuración de Acumulados
                                    </h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>¿Qué son los acumulados?</strong><br>
                                        Los acumulados permiten que este concepto contribuya al cálculo de aguinaldo, bono 14, vacaciones, 
                                        indemnizaciones y otros beneficios laborales. Configure qué tipos de acumulados aplicarán y con qué referencia.
                                    </div>
                                    
                                    <div id="acumulados-container">
                                        <!-- Aquí se cargarán dinámicamente los tipos de acumulados -->
                                        <div class="text-center">
                                            <i class="fas fa-spinner fa-spin"></i> Cargando tipos de acumulados...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuración de Reportes -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-file-pdf text-danger"></i> Configuración de Reportes PDF
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="incluir_reporte" 
                                                           name="incluir_reporte" value="1" checked>
                                                    <label class="custom-control-label" for="incluir_reporte">
                                                        <strong>Incluir en Reportes PDF</strong>
                                                    </label>
                                                </div>
                                                <small class="text-muted">Si está desactivado, este concepto no aparecerá en los reportes PDF</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="categoria_reporte">Categoría en Reporte</label>
                                                <select class="form-control" id="categoria_reporte" name="categoria_reporte">
                                                    <option value="otro" selected>Otro</option>
                                                    <option value="seguro_social">Seguro Social</option>
                                                    <option value="seguro_educativo">Seguro Educativo</option>
                                                    <option value="impuesto_renta">Impuesto sobre la Renta</option>
                                                    <option value="otras_deducciones">Otras Deducciones</option>
                                                </select>
                                                <small class="text-muted">Categoría para agrupar totales en el reporte</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="orden_reporte">Orden en Reporte</label>
                                                <input type="number" class="form-control" id="orden_reporte" name="orden_reporte" 
                                                       value="0" min="0" max="999">
                                                <small class="text-muted">Orden de aparición (0 = automático)</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-info">
                                                <h6><i class="fas fa-info-circle"></i> Información sobre Categorías</h6>
                                                <ul class="mb-0">
                                                    <li><strong>Seguro Social:</strong> Se totaliza en la columna "S.Social"</li>
                                                    <li><strong>Seguro Educativo:</strong> Se totaliza en la columna "S.Educativo"</li>
                                                    <li><strong>Impuesto Renta:</strong> Se totaliza en la columna "Imp.Renta"</li>
                                                    <li><strong>Otras Deducciones:</strong> Se totaliza en la columna "Otras Ded."</li>
                                                    <li><strong>Otro:</strong> Solo se incluye en los totales generales</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Crear Concepto
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Probar Fórmula -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Probar Fórmula</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="testEmployee">Empleado para Prueba</label>
                    <select class="form-control" id="testEmployee">
                        <option value="">Seleccione un empleado...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Fórmula a Probar</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-code"></i></span>
                        </div>
                        <input type="text" class="form-control" id="testFormulaInput" readonly>
                    </div>
                </div>
                
                <div id="testResult" class="alert" style="display: none;"></div>
                
                <div id="employeeVariables" style="display: none;">
                    <h6>Variables del Empleado:</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>SALARIO:</strong> <span id="varSalario">Q 0.00</span>
                        </div>
                        <div class="col-md-4">
                            <strong>HORAS:</strong> <span id="varHoras">0</span>
                        </div>
                        <div class="col-md-4">
                            <strong>ANTIGUEDAD:</strong> <span id="varAntiguedad">0</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="runTest">Ejecutar Prueba</button>
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function() {
    // Form validation
    $('#conceptForm').on('submit', function(e) {
        // Validar que haya al menos descripción y tipo
        if (!$('#descripcion').val() || !$('input[name="tipo_concepto"]:checked').val()) {
            e.preventDefault();
            alert('Por favor complete los campos requeridos: Descripción y Tipo de Concepto');
            return false;
        }

        // Validar que la fórmula no esté vacía
        var formula = $('#formula').val().trim();

        if (!formula) {
            e.preventDefault();
            alert('Debe proporcionar una fórmula de cálculo');
            return false;
        }
    });

    // Probar fórmula
    $('#testFormula').on('click', function() {
        var formula = $('#formula').val().trim();
        
        if (!formula) {
            alert('Ingrese una fórmula para probar');
            return;
        }
        
        $('#testFormulaInput').val(formula);
        $('#testModal').modal('show');
        
        // Load employees
        loadEmployeesForTest();
    });

    // Ejecutar prueba de fórmula
    $('#runTest').click(function() {
        var employeeId = $('#testEmployee').val();
        var formula = $('#testFormulaInput').val();
        
        if (!employeeId) {
            alert('Por favor seleccione un empleado');
            return;
        }

        if (!formula) {
            alert('No hay fórmula para probar');
            return;
        }

        testFormula(employeeId, formula);
    });

    // Load employees for testing
    function loadEmployeesForTest() {
        $.ajax({
            url: '<?= \App\Core\UrlHelper::panel('employees/options') ?>',
            method: 'GET',
            success: function(response) {
                var options = '<option value="">Seleccione un empleado...</option>';
                response.forEach(function(employee) {
                    options += '<option value="' + employee.id + '">' + employee.firstname + ' ' + employee.lastname + '</option>';
                });
                $('#testEmployee').html(options);
            },
            error: function() {
                console.log('Error loading employees');
            }
        });
    }

    // Test formula function
    function testFormula(employeeId, formula) {
        $('#testResult').hide();
        $('#employeeVariables').hide();
        
        $.ajax({
            url: '<?= \App\Core\UrlHelper::concept('test-formula') ?>',
            method: 'POST',
            data: {
                employee_id: employeeId,
                formula: formula,
                csrf_token: '<?= \App\Core\Security::generateToken() ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#testResult')
                        .removeClass('alert-danger')
                        .addClass('alert-success')
                        .html('<i class="fas fa-check"></i> <strong>Resultado:</strong> Q' + response.result)
                        .show();
                    
                    if (response.variables) {
                        $('#varSalario').text('Q ' + (response.variables.SALARIO || '0.00'));
                        $('#varHoras').text(response.variables.HORAS || '0');
                        $('#varAntiguedad').text(response.variables.ANTIGUEDAD || '0');
                        $('#employeeVariables').show();
                    }
                } else {
                    $('#testResult')
                        .removeClass('alert-success')
                        .addClass('alert-danger')
                        .html('<i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> ' + response.message)
                        .show();
                }
            },
            error: function() {
                $('#testResult')
                    .removeClass('alert-success')
                    .addClass('alert-danger')
                    .html('<i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> No se pudo probar la fórmula')
                    .show();
            }
        });
    }
    
    // Cargar tipos de acumulados disponibles
    function loadTiposAcumulados() {
        $.ajax({
            url: '<?= url('/panel/tipos-acumulados/options') ?>',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.results && response.results.length > 0) {
                    renderAcumuladosForm(response.results);
                } else {
                    $('#acumulados-container').html(
                        '<div class="alert alert-warning">' +
                        '<i class="fas fa-exclamation-triangle"></i> ' +
                        'No hay tipos de acumulados configurados. ' +
                        '<a href="<?= url('/panel/tipos-acumulados/create') ?>" target="_blank">Crear uno nuevo</a>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#acumulados-container').html(
                    '<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-triangle"></i> ' +
                    'Error al cargar tipos de acumulados' +
                    '</div>'
                );
            }
        });
    }
    
    function renderAcumuladosForm(tiposAcumulados) {
        let html = '<div class="row">';
        
        tiposAcumulados.forEach(function(tipo, index) {
            const colClass = index % 2 === 0 ? 'col-md-6' : 'col-md-6';
            
            html += `
                <div class="${colClass}">
                    <div class="card card-outline card-light mb-3">
                        <div class="card-body p-3">
                            <div class="form-check">
                                <input class="form-check-input acumulado-checkbox" 
                                       type="checkbox" 
                                       name="acumulados[${tipo.id}][incluir]" 
                                       id="acumulado_${tipo.id}" 
                                       value="1"
                                       onchange="toggleAcumuladoConfig(${tipo.id})">
                                <label class="form-check-label font-weight-bold" for="acumulado_${tipo.id}">
                                    ${tipo.text}
                                </label>
                            </div>
                            
                            <div id="config_${tipo.id}" class="acumulado-config mt-3" style="display: none;">
                                <div class="row">
                                    <div class="col-8">
                                        <label class="form-label text-sm">Referencia de Acumulación (%)</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="acumulados[${tipo.id}][factor]" 
                                                   value="100" 
                                                   min="0" 
                                                   max="999" 
                                                   step="0.01"
                                                   placeholder="100">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label text-sm">&nbsp;</label>
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="acumulados[${tipo.id}][activo]" 
                                                   value="1" 
                                                   checked>
                                            <label class="form-check-label text-sm">Activo</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <label class="form-label text-sm">Observaciones</label>
                                        <textarea class="form-control" 
                                                  name="acumulados[${tipo.id}][observaciones]" 
                                                  rows="2" 
                                                  placeholder="Observaciones opcionales sobre este acumulado..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        // Agregar botón de ayuda
        html += `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-light">
                        <h6><i class="fas fa-lightbulb text-warning"></i> Ejemplos de Referencias:</h6>
                        <ul class="mb-0 text-sm">
                            <li><strong>100%:</strong> El concepto se acumula completamente (salario base, comisiones)</li>
                            <li><strong>50%:</strong> Solo se acumula la mitad del concepto</li>
                            <li><strong>150%:</strong> Se acumula el concepto más recargos (horas extra con recargo)</li>
                            <li><strong>0%:</strong> El concepto no contribuye a este acumulado</li>
                        </ul>
                    </div>
                </div>
            </div>
        `;
        
        $('#acumulados-container').html(html);
    }
    
    // Mostrar/ocultar configuración de acumulado
    window.toggleAcumuladoConfig = function(tipoId) {
        const checkbox = document.getElementById(`acumulado_${tipoId}`);
        const config = document.getElementById(`config_${tipoId}`);
        
        if (checkbox.checked) {
            $(config).slideDown();
        } else {
            $(config).slideUp();
        }
    };
    
    // Cargar tipos de acumulados al cargar la página
    $(document).ready(function() {
        loadTiposAcumulados();
    });
});
</script>