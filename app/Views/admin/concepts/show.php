<?php
$title = 'Detalle del Concepto';

// Obtener estadísticas si están disponibles
$vecesUsado = intval($stats['veces_usado'] ?? 0);
$totalMonto = floatval($stats['total_monto'] ?? 0);
$promedioMonto = floatval($stats['promedio_monto'] ?? 0);

// Determinar clase y icono del tipo
$typeClass = ($concept['tipo_concepto'] ?? '') === 'A' ? 'success' : (($concept['tipo_concepto'] ?? '') === 'D' ? 'danger' : 'info');
$typeIcon = ($concept['tipo_concepto'] ?? '') === 'A' ? 'plus' : (($concept['tipo_concepto'] ?? '') === 'D' ? 'minus' : 'cog');
$typeText = ($concept['tipo_concepto'] ?? '') === 'A' ? 'Ingreso' : (($concept['tipo_concepto'] ?? '') === 'D' ? 'Deducción' : 'Mixto');
?>

<div class="row">
    <div class="col-sm-12">
        <div class="float-right">
            <a href="<?= \App\Core\UrlHelper::concept($concept['id'] . '/edit') ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="<?= \App\Core\UrlHelper::concept('') ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver a Conceptos
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Información Principal -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Información del Concepto
                </h3>
                <div class="card-tools">
                    <span class="badge badge-<?= $typeClass ?> badge-lg">
                        <i class="fas fa-<?= $typeIcon ?>"></i>
                        <?= $typeText ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <label>ID del Concepto:</label>
                            <span class="value">#<?= $concept['id'] ?></span>
                        </div>
                        <div class="info-item">
                            <label>Código:</label>
                            <span class="value"><?= htmlspecialchars($concept['concepto'] ?? 'N/A') ?></span>
                        </div>
                        <div class="info-item">
                            <label>Descripción:</label>
                            <span class="value"><?= htmlspecialchars($concept['descripcion'] ?? '') ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <label>Tipo de Concepto:</label>
                            <span class="badge badge-<?= $typeClass ?>">
                                <i class="fas fa-<?= $typeIcon ?>"></i> <?= $typeText ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <label>Unidad:</label>
                            <span class="value"><?= ucfirst($concept['unidad'] ?? 'monto') ?></span>
                        </div>
                        <div class="info-item">
                            <label>Imprime Detalles:</label>
                            <span class="badge badge-<?= ($concept['imprime_detalles'] ?? 0) ? 'success' : 'secondary' ?>">
                                <i class="fas fa-<?= ($concept['imprime_detalles'] ?? 0) ? 'print' : 'times' ?>"></i>
                                <?= ($concept['imprime_detalles'] ?? 0) ? 'Sí' : 'No' ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <label>Se Prorratea:</label>
                            <span class="badge badge-<?= ($concept['prorratea'] ?? 0) ? 'success' : 'secondary' ?>">
                                <i class="fas fa-<?= ($concept['prorratea'] ?? 0) ? 'check' : 'times' ?>"></i>
                                <?= ($concept['prorratea'] ?? 0) ? 'Sí' : 'No' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Fórmula o Valor Fijo -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5><i class="fas fa-calculator"></i> Cálculo</h5>
                        <hr>
                        <?php if (!empty($concept['formula'])): ?>
                            <div class="info-item">
                                <label>Fórmula de Cálculo:</label>
                                <div class="formula-display">
                                    <code><?= htmlspecialchars($concept['formula']) ?></code>
                                    <button type="button" class="btn btn-sm btn-outline-info ml-2" id="testFormula" 
                                            data-formula="<?= htmlspecialchars($concept['formula']) ?>">
                                        <i class="fas fa-play"></i> Probar Fórmula
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas y Configuración -->
    <div class="col-md-4">
        <!-- Estadísticas de Uso -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i> Estadísticas de Uso
                </h3>
            </div>
            <div class="card-body text-center">
                <div class="stat-item">
                    <div class="stat-value text-primary">
                        <i class="fas fa-hashtag"></i>
                        <span class="h3"><?= $vecesUsado ?></span>
                    </div>
                    <div class="stat-label">Veces Usado</div>
                </div>
                
                <div class="stat-item mt-3">
                    <div class="stat-value text-success">
                        <i class="fas fa-dollar-sign"></i>
                        <span class="h4"><?= currency_symbol() ?> <?= number_format($totalMonto, 2) ?></span>
                    </div>
                    <div class="stat-label">Total Aplicado</div>
                </div>
                
                <?php if ($vecesUsado > 0): ?>
                <div class="stat-item mt-3">
                    <div class="stat-value text-info">
                        <i class="fas fa-chart-line"></i>
                        <span class="h5"><?= currency_symbol() ?> <?= number_format($promedioMonto, 2) ?></span>
                    </div>
                    <div class="stat-label">Promedio</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Configuración Avanzada -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cogs"></i> Configuración
                </h3>
            </div>
            <div class="card-body">
                <div class="config-item">
                    <label>Modifica Valor:</label>
                    <span class="badge badge-<?= ($concept['modifica_valor'] ?? 0) ? 'success' : 'secondary' ?>">
                        <i class="fas fa-<?= ($concept['modifica_valor'] ?? 0) ? 'edit' : 'lock' ?>"></i>
                        <?= ($concept['modifica_valor'] ?? 0) ? 'Permitido' : 'No Permitido' ?>
                    </span>
                </div>
                
                <!-- Mostrar relaciones si existen -->
                <?php if (!empty($concept['tipos_planilla_rel'])): ?>
                <div class="config-item">
                    <label>Tipos de Planilla:</label>
                    <div class="relations">
                        <?php foreach ($concept['tipos_planilla_rel'] as $tipo): ?>
                            <span class="badge badge-primary mr-1 mb-1">
                                <?= htmlspecialchars($tipo['nombre']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($concept['frecuencias_rel'])): ?>
                <div class="config-item">
                    <label>Frecuencias:</label>
                    <div class="relations">
                        <?php foreach ($concept['frecuencias_rel'] as $frecuencia): ?>
                            <span class="badge badge-success mr-1 mb-1">
                                <?= htmlspecialchars($frecuencia['nombre']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($concept['situaciones_rel'])): ?>
                <div class="config-item">
                    <label>Situaciones:</label>
                    <div class="relations">
                        <?php foreach ($concept['situaciones_rel'] as $situacion): ?>
                            <span class="badge badge-warning mr-1 mb-1">
                                <?= htmlspecialchars($situacion['nombre']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
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

<style>
.info-item {
    margin-bottom: 1rem;
}

.info-item label {
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 0.25rem;
}

.info-item .value {
    font-weight: 500;
}

.formula-display {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.375rem;
    border: 1px solid #e9ecef;
}

.formula-display code {
    background: none;
    color: #198754;
    font-size: 1.1em;
    font-weight: 500;
}

.stat-item {
    padding: 0.5rem 0;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
}

.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.config-item {
    margin-bottom: 1rem;
}

.config-item label {
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 0.25rem;
}

.relations {
    margin-top: 0.25rem;
}

.badge-lg {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}
</style>


<script>
$(document).ready(function() {
    // Probar fórmula
    $('#testFormula').on('click', function() {
        var formula = $(this).data('formula');
        
        if (!formula) {
            alert('No hay fórmula para probar');
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
                        .html('<i class="fas fa-check"></i> <strong>Resultado:</strong> <?= currency_symbol() ?>' + response.result)
                        .show();
                    
                    if (response.variables) {
                        $('#varSalario').text('<?= currency_symbol() ?> ' + (response.variables.SALARIO || '0.00'));
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
});
</script>