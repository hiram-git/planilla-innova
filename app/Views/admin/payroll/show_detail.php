<?php
/**
 * Vista: Detalle de Conceptos por Empleado
 */
$title = 'Detalle de Empleado: ' . htmlspecialchars($detail['employee_name'] ?? 'N/A');
?>

<!-- Botones de navegación y acciones -->
<div class="row mb-3">
    <div class="col-sm-12">
        <div class="float-right">
            <button type="button" class="btn btn-warning" id="regenerateEmployeeBtn" data-payroll-id="<?= $payroll['id'] ?>" data-employee-id="<?= $detail['employee_id'] ?>">
                <i class="fas fa-redo"></i> Regenerar Empleado
            </button>
            <a href="<?= \App\Core\UrlHelper::route('panel/payrolls/' . $payroll['id']) ?>" class="btn btn-secondary ml-2">
                <i class="fas fa-arrow-left"></i> Volver a la Planilla
            </a>
        </div>
    </div>
</div>

<!-- Información del Empleado -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user"></i> Información del Empleado
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Código:</dt>
                            <dd class="col-sm-7"><span class="badge badge-primary"><?= htmlspecialchars($detail['employee_code']) ?></span></dd>
                            
                            <dt class="col-sm-5">Nombre:</dt>
                            <dd class="col-sm-7"><strong><?= htmlspecialchars($detail['employee_name']) ?></strong></dd>
                            
                            <dt class="col-sm-5">Posición:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($detail['position_name'] ?? 'Sin asignar') ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-6">Salario Base:</dt>
                            <dd class="col-sm-6"><?= currency_symbol() ?><?= number_format($detail['salario_base'], 2) ?></dd>
                            
                            <dt class="col-sm-6">Horas Trabajadas:</dt>
                            <dd class="col-sm-6"><?= number_format($detail['horas_trabajadas'], 1) ?>h</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calculator"></i> Resumen
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="description-block">
                            <h5 class="description-header text-success"><?= currency_symbol() ?><?= number_format($totalIncomes, 2) ?></h5>
                            <span class="description-text">Total Ingresos</span>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <div class="description-block">
                            <h5 class="description-header text-danger"><?= currency_symbol() ?><?= number_format($totalDeductions, 2) ?></h5>
                            <span class="description-text">Total Deducciones</span>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <div class="description-block">
                            <h4 class="description-header text-info"><?= currency_symbol() ?><?= number_format($netSalary, 2) ?></h4>
                            <span class="description-text"><strong>Salario Neto</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Conceptos Detallados -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success">
                <h3 class="card-title">
                    <i class="fas fa-plus-circle"></i> Ingresos
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($incomes)): ?>
                                <?php foreach ($incomes as $income): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($income['descripcion']) ?></strong>
                                            <?php if (!empty($income['observaciones'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($income['observaciones']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-success font-weight-bold"><?= currency_symbol() ?><?= number_format($income['monto'], 2) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i> No hay ingresos registrados
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold bg-light">
                                <td>TOTAL INGRESOS</td>
                                <td class="text-right text-success"><?= currency_symbol() ?><?= number_format($totalIncomes, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-danger">
                <h3 class="card-title">
                    <i class="fas fa-minus-circle"></i> Deducciones
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($deductions)): ?>
                                <?php foreach ($deductions as $deduction): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($deduction['descripcion']) ?></strong>
                                            <?php if (!empty($deduction['observaciones'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($deduction['observaciones']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-danger font-weight-bold"><?= currency_symbol() ?><?= number_format($deduction['monto'], 2) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i> No hay deducciones registradas
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold bg-light">
                                <td>TOTAL DEDUCCIONES</td>
                                <td class="text-right text-danger"><?= currency_symbol() ?><?= number_format($totalDeductions, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resumen Final -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-receipt"></i> Resumen Final
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Total Ingresos:</strong></td>
                                    <td class="text-right text-success"><strong><?= currency_symbol() ?><?= number_format($totalIncomes, 2) ?></strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Deducciones:</strong></td>
                                    <td class="text-right text-danger"><strong>- <?= currency_symbol() ?><?= number_format($totalDeductions, 2) ?></strong></td>
                                </tr>
                                <tr class="border-top">
                                    <td class="h5"><strong>Salario Neto:</strong></td>
                                    <td class="text-right h4"><strong class="text-info"><?= currency_symbol() ?><?= number_format($netSalary, 2) ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <?php if (!empty($detail['observaciones'])): ?>
                            <div class="alert alert-info">
                                <h6><i class="fas fa-sticky-note"></i> Observaciones:</h6>
                                <p class="mb-0"><?= htmlspecialchars($detail['observaciones']) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($detail['valores_editados_manual'])): ?>
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-edit"></i> Valores Editados:</h6>
                                <p class="mb-0"><small>Algunos valores fueron editados manualmente.</small></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para regenerar empleado -->
<div class="modal fade" id="regenerateEmployeeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-redo text-warning"></i> Regenerar Empleado
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>¿Está seguro de regenerar la planilla para este empleado?</strong>
                </div>
                <p>Esta acción:</p>
                <ul>
                    <li><i class="fas fa-trash text-danger"></i> Eliminará todos los conceptos actuales del empleado</li>
                    <li><i class="fas fa-calculator text-info"></i> Recalculará todos los conceptos aplicables</li>
                    <li><i class="fas fa-refresh text-warning"></i> Actualizará los montos según las fórmulas actuales</li>
                </ul>
                
                <div class="employee-info bg-light p-3 rounded">
                    <h6><i class="fas fa-user"></i> Empleado a regenerar:</h6>
                    <strong><?= htmlspecialchars($detail['employee_name']) ?></strong>
                    <br><small class="text-muted">Código: <?= htmlspecialchars($detail['employee_code']) ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="confirmRegenerateEmployee">
                    <i class="fas fa-redo"></i> Confirmar Regeneración
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de progreso -->
<div class="modal fade" id="regenerateProgressModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-cog fa-spin text-warning"></i> Regenerando Empleado
                </h4>
            </div>
            <div class="modal-body text-center">
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" 
                         role="progressbar" style="width: 0%">
                        <span class="sr-only">0% Complete</span>
                    </div>
                </div>
                <p id="regenerateProgressText">Preparando regeneración...</p>
                <small class="text-muted" id="regenerateProgressDetail">Por favor espere...</small>
            </div>
        </div>
    </div>
</div>

<style>
.badge {
    font-size: 0.85em;
}
.table th, .table td {
    vertical-align: middle;
}
.description-block {
    text-align: center;
    border-right: 1px solid #f4f4f4;
}
.description-block:last-child {
    border-right: none;
}
.bg-light {
    background-color: #f8f9fa !important;
}
.employee-info {
    border-left: 4px solid #ffc107;
}
</style>

<?php
$scripts = "
<script>
// Usar document.addEventListener para asegurar que el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si jQuery está disponible
    if (typeof jQuery === 'undefined') {
        console.error('jQuery no está cargado');
        return;
    }
    
    // Usar jQuery de forma segura
    jQuery(function($) {
        // Mostrar modal de confirmación
        $('#regenerateEmployeeBtn').click(function() {
            $('#regenerateEmployeeModal').modal('show');
        });
        
        // Confirmar regeneración
        $('#confirmRegenerateEmployee').click(function() {
            const payrollId = $('#regenerateEmployeeBtn').data('payroll-id');
            const employeeId = $('#regenerateEmployeeBtn').data('employee-id');
            
            // Ocultar modal de confirmación y mostrar progreso
            $('#regenerateEmployeeModal').modal('hide');
            $('#regenerateProgressModal').modal('show');
            
            // Iniciar regeneración
            regenerateEmployee(payrollId, employeeId);
        });
        
        // Funciones globales
        window.regenerateEmployee = function(payrollId, employeeId) {
            // Mostrar progreso inicial
            updateProgress(25, 'Eliminando conceptos existentes...', 'Preparando datos del empleado');
            
            const ajaxUrl = '" . \App\Core\UrlHelper::route('panel/payrolls') . "/' + payrollId + '/regenerate-employee';
            
            // Realizar petición AJAX
            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                data: {
                    employee_id: employeeId,
                    csrf_token: '" . ($_SESSION['csrf_token'] ?? '') . "'
                },
                timeout: 60000, // 60 segundos timeout
                success: function(response) {
                    if (response.success) {
                        const conceptsCount = response.concepts_count || 0;
                        const totalIngresos = response.total_ingresos || 0;
                        const totalDeducciones = response.total_deducciones || 0;
                        
                        updateProgress(100, 'Regeneración completada exitosamente', 
                                      conceptsCount + ' conceptos aplicados - Total: Q' + 
                                      (totalIngresos - totalDeducciones).toFixed(2));
                        
                        // Mostrar mensaje de éxito detallado
                        if (typeof toastr !== 'undefined') {
                            const successMessage = 'Empleado: ' + (response.employee_name || 'N/A') + 
                                                  '\\nConceptos aplicados: ' + conceptsCount +
                                                  '\\nIngresos: Q' + totalIngresos.toFixed(2) +
                                                  '\\nDeducciones: Q' + totalDeducciones.toFixed(2);
                            toastr.success(successMessage, 'Regeneración Completa');
                        }
                        
                        // Esperar 3 segundos y recargar la página
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        showError('Error en la regeneración: ' + (response.message || 'Error desconocido'));
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Error de conexión';
                    let debugInfo = '';
                    
                    // Si el status es 200 (éxito) pero llegó a error, puede ser un problema de parsing JSON
                    if (xhr.status === 200) {
                        console.log('Respuesta HTTP 200 en error handler. Response text:', xhr.responseText);
                        
                        // Intentar parsear la respuesta manualmente
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                // Si realmente es exitosa, manejarla como éxito
                                const conceptsCount = response.concepts_count || 0;
                                const totalIngresos = response.total_ingresos || 0;
                                const totalDeducciones = response.total_deducciones || 0;
                                
                                updateProgress(100, 'Regeneración completada exitosamente', 
                                              conceptsCount + ' conceptos aplicados - Total: Q' + 
                                              (totalIngresos - totalDeducciones).toFixed(2));
                                
                                if (typeof toastr !== 'undefined') {
                                    toastr.success('Empleado regenerado exitosamente', 'Regeneración Completa');
                                }
                                
                                setTimeout(function() {
                                    location.reload();
                                }, 3000);
                                return;
                            } else {
                                errorMessage = response.message || 'Error en la respuesta del servidor';
                            }
                        } catch (parseError) {
                            errorMessage = 'Error procesando respuesta del servidor (HTTP 200)';
                            debugInfo = '\\nRespuesta: ' + xhr.responseText.substring(0, 200);
                        }
                    } else if (xhr.responseJSON) {
                        errorMessage = xhr.responseJSON.message || 'Error desconocido del servidor';
                        if (xhr.responseJSON.debug_info) {
                            debugInfo = '\\nDebug: ' + JSON.stringify(xhr.responseJSON.debug_info);
                        }
                    } else if (status === 'timeout') {
                        errorMessage = 'La regeneración está tardando más de lo esperado (timeout de 60s)';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Error de conexión - verificar servidor';
                    } else {
                        errorMessage = 'Error HTTP ' + xhr.status + ': ' + (xhr.statusText || error);
                    }
                    
                    console.error('Error en regeneración:', {
                        status: status,
                        error: error,
                        response: xhr.responseText,
                        xhr: xhr
                    });
                    
                    showError(errorMessage + debugInfo);
                },
                // Simular progreso durante la petición
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    let progress = 25;
                    const progressInterval = setInterval(function() {
                        if (progress < 90) {
                            progress += 5;
                            updateProgress(progress, 'Recalculando conceptos...', 'Aplicando fórmulas y validaciones');
                        }
                    }, 500);
                    
                    xhr.addEventListener('loadend', function() {
                        clearInterval(progressInterval);
                    });
                    
                    return xhr;
                }
            });
        };
        
        window.updateProgress = function(percent, text, detail) {
            $('#regenerateProgressModal .progress-bar').css('width', percent + '%');
            $('#regenerateProgressText').text(text);
            $('#regenerateProgressDetail').text(detail);
        };
        
        window.showError = function(message) {
            $('#regenerateProgressModal').modal('hide');
            
            // Mostrar toast de error
            if (typeof toastr !== 'undefined') {
                toastr.error(message, 'Error en Regeneración', {
                    timeOut: 0,
                    extendedTimeOut: 0,
                    closeButton: true
                });
            } else {
                alert('Error: ' + message);
            }
        };
    });
});
</script>
";
?>