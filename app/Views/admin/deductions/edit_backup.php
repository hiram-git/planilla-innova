<?php
$title = 'Editar Deducción';
?>

<div class="row">
    <div class="col-sm-12">
        <div class="float-right">
            <a href="<?= \App\Core\UrlHelper::panel('deductions') ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver a Deducciones
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit"></i> Editar Deducción
                </h3>
                <div class="card-tools">
                    <span class="badge badge-warning">
                        ID: <?= htmlspecialchars($deduction['id']) ?>
                    </span>
                </div>
            </div>
            <form id="deductionForm" action="<?= \App\Core\UrlHelper::panel('deductions/' . $deduction['id'] . '/update') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="card-body">
                    <!-- Alerta de restricciones -->
                    <?php if (isset($editRestrictions['inGeneratedPayroll']) && $editRestrictions['inGeneratedPayroll']): ?>
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Edición Restringida</h5>
                        <p><strong>Razón:</strong> <?= htmlspecialchars($editRestrictions['reason'] ?? 'Deducción con restricciones') ?></p>
                        <p><small>Esta deducción está asociada a una planilla ya generada. La edición está limitada para mantener la integridad de los registros.</small></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Información Principal -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="employee_id">Empleado *</label>
                                <select class="form-control select2" id="employee_id" name="employee_id" required 
                                        <?= (isset($editRestrictions['canEditEmployee']) && !$editRestrictions['canEditEmployee']) ? 'disabled' : '' ?>>
                                    <!-- Opción seleccionada se cargará dinámicamente -->
                                </select>
                                <small class="form-text text-muted">
                                    <?= (isset($editRestrictions['canEditEmployee']) && $editRestrictions['canEditEmployee']) ? 'Busque por nombre o código de empleado' : 'Campo bloqueado: ' . ($editRestrictions['reason'] ?? 'Restricción activa') ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="creditor_id">Acreedor *</label>
                                <select class="form-control" id="creditor_id" name="creditor_id" required
                                        <?= (isset($editRestrictions['canEditCreditor']) && !$editRestrictions['canEditCreditor']) ? 'disabled' : '' ?>>
                                    <option value="">Seleccione un acreedor...</option>
                                    <?php if (!empty($creditors)): ?>
                                        <?php foreach ($creditors as $creditor): ?>
                                            <?php $selected = ($creditor['id'] == $deduction['creditor_id']) ? 'selected' : ''; ?>
                                            <option value="<?= htmlspecialchars($creditor['id']) ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($creditor['description']) ?>
                                                <?php if (!empty($creditor['codigo'])): ?>
                                                    (<?= htmlspecialchars($creditor['codigo']) ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">
                                    <?= (isset($editRestrictions['canEditCreditor']) && $editRestrictions['canEditCreditor']) ? 'Institución o empresa acreedora' : 'Campo bloqueado: ' . ($editRestrictions['reason'] ?? 'Restricción activa') ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="description">Descripción *</label>
                                <input type="text" class="form-control" id="description" name="description" required
                                       value="<?= htmlspecialchars($deduction['description']) ?>"
                                       placeholder="Ej: Préstamo personal, Seguro médico, etc."
                                       <?= (isset($editRestrictions['canEditDescription']) && !$editRestrictions['canEditDescription']) ? 'readonly' : '' ?>>
                                <small class="form-text text-muted">
                                    <?= (isset($editRestrictions['canEditDescription']) && $editRestrictions['canEditDescription']) ? 'Descripción detallada de la deducción' : 'Campo de solo lectura: ' . ($editRestrictions['reason'] ?? 'Restricción activa') ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="amount">Monto *</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><?= currency_symbol() ?></span>
                                    </div>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           step="0.01" min="0.01" max="999999.99" required
                                           value="<?= htmlspecialchars($deduction['amount']) ?>"
                                           placeholder="0.00"
                                           <?= (isset($editRestrictions['canEditAmount']) && !$editRestrictions['canEditAmount']) ? 'readonly' : '' ?>>
                                </div>
                                <small class="form-text text-muted">
                                    <?= (isset($editRestrictions['canEditAmount']) && $editRestrictions['canEditAmount']) ? 'Monto a descontar por planilla' : 'Campo de solo lectura: ' . ($editRestrictions['reason'] ?? 'Restricción activa') ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de Cambios -->
                    <?php if (!empty($deduction['updated_at']) && $deduction['updated_at'] != $deduction['created_at']): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-history"></i> Historial</h6>
                                <small>
                                    <strong>Creada:</strong> <?= date('d/m/Y H:i', strtotime($deduction['created_at'] ?? 'now')) ?><br>
                                    <strong>Última modificación:</strong> <?= date('d/m/Y H:i', strtotime($deduction['updated_at'] ?? 'now')) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Información de Validación -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle"></i> Importante</h5>
                                <ul class="mb-0">
                                    <li>Los cambios se aplicarán en las próximas planillas a procesar.</li>
                                    <li>Si cambia el empleado o acreedor, verifique que no exista otra deducción duplicada.</li>
                                    <li>Las planillas ya procesadas no se verán afectadas por estos cambios.</li>
                                </ul>
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
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Información del Empleado (se carga dinámicamente) -->
<div class="row" id="employeeInfo" style="display: none;">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user"></i> Información del Empleado
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Nombre:</strong> <span id="empName"></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Puesto:</strong> <span id="empPosition"></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Otras Deducciones:</strong> <span id="empOtherDeductions"></span>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <strong>Total Otras Deducciones:</strong> <span id="empTotalOther" class="text-danger"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Salario Base:</strong> <span id="empBaseSalary" class="text-success"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$styles = '
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Select2 Bootstrap 4 custom styling */
.select2-container {
    width: 100% !important;
}

.select2-container--default .select2-selection--single {
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    height: calc(1.5em + 0.75rem + 2px);
    padding: 0;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.select2-container--default .select2-selection--single:focus,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #495057;
    padding-left: 0.75rem;
    padding-right: 2rem;
    line-height: calc(1.5em + 0.75rem);
    background: transparent;
    border: none;
}

.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #6c757d;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + 0.75rem);
    position: absolute;
    top: 1px;
    right: 1px;
    width: 20px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #495057 transparent transparent transparent;
    border-style: solid;
    border-width: 5px 4px 0 4px;
    height: 0;
    left: 50%;
    margin-left: -4px;
    margin-top: -2px;
    position: absolute;
    top: 50%;
    width: 0;
}

.select2-dropdown {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
}

.select2-results__option {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.select2-results__option--highlighted {
    background-color: #007bff;
    color: white;
}

/* Custom styling for employee results */
.select2-employee {
    line-height: 1.2;
}

.select2-employee strong {
    color: inherit;
    font-weight: 600;
}

.select2-employee small {
    display: block;
    margin-top: 2px;
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Estilos para campos con restricciones */
.readonly-field {
    background-color: #f8f9fa !important;
    cursor: not-allowed !important;
}

.disabled-field {
    background-color: #e9ecef !important;
    opacity: 0.6 !important;
    cursor: not-allowed !important;
}

.readonly-field:focus,
.disabled-field:focus {
    box-shadow: none !important;
    border-color: #ced4da !important;
}
</style>';

$scripts = '
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="' . url('assets/js/modules/deductions.js', false) . '"></script>
<script>
// Configurar URLs para el módulo
DeductionsModule.setUrls({
    searchEmployees: "' . \App\Core\UrlHelper::panel('deductions/search-employees') . '",
    employeeInfo: "' . \App\Core\UrlHelper::panel('deductions/employee-info') . '",
    checkDuplicate: "' . \App\Core\UrlHelper::panel('deductions/check-duplicate') . '"
});

// Configuración específica para edit
$(document).ready(function() {
    // Configurar módulo para edit mode
    if (typeof DeductionsModule !== "undefined") {
        // Forzar inicialización de Select2 para edit
        DeductionsModule.initSelect2();
        
        // Configurar empleado actual después de la inicialización
        setTimeout(function() {
            var employeeSelect = $("#employee_id");
            var currentEmployee = {
                id: "' . htmlspecialchars($deduction['employee_id']) . '",
                text: "' . htmlspecialchars($deduction['employee_name'] ?? ('Empleado ID: ' . $deduction['employee_id'])) . '"
            };
            
            // Si Select2 está inicializado, usarlo; sino, usar select normal
            if (employeeSelect.data("select2")) {
                // Para Select2: crear opción y seleccionar
                var option = new Option(currentEmployee.text, currentEmployee.id, true, true);
                employeeSelect.append(option).trigger("change");
            } else {
                // Para select normal: agregar opción estática
                employeeSelect.append("<option value=\"" + currentEmployee.id + "\" selected>" + currentEmployee.text + "</option>");
            }

            // Cargar información del empleado
            loadEmployeeInfoEdit("' . htmlspecialchars($deduction['employee_id']) . '", ' . $deduction['id'] . ');
        }, 500);
    }
    
    // Aplicar restricciones de edición basadas en el backend
    applyEditRestrictions();
});
</script>

<?php 
// Definir restricciones por defecto si no existen
if (!isset($editRestrictions)) {
    $editRestrictions = [
        'canEditEmployee' => true,
        'canEditCreditor' => true,
        'canEditDescription' => true,
        'canEditAmount' => true,
        'inGeneratedPayroll' => false,
        'reason' => 'Sin restricciones'
    ];
}
?>

<script>
// Función para aplicar restricciones de edición
function applyEditRestrictions() {
    var editRestrictions = {
        canEditEmployee: <?= isset($editRestrictions['canEditEmployee']) && $editRestrictions['canEditEmployee'] ? 'true' : 'false' ?>,
        canEditCreditor: <?= isset($editRestrictions['canEditCreditor']) && $editRestrictions['canEditCreditor'] ? 'true' : 'false' ?>,
        canEditDescription: <?= isset($editRestrictions['canEditDescription']) && $editRestrictions['canEditDescription'] ? 'true' : 'false' ?>,
        canEditAmount: <?= isset($editRestrictions['canEditAmount']) && $editRestrictions['canEditAmount'] ? 'true' : 'false' ?>,
        inGeneratedPayroll: <?= isset($editRestrictions['inGeneratedPayroll']) && $editRestrictions['inGeneratedPayroll'] ? 'true' : 'false' ?>,
        reason: <?= json_encode($editRestrictions['reason'] ?? 'Sin restricciones') ?>
    };
    
    if (!editRestrictions.canEditDescription) {
        $("#description").prop("readonly", true).addClass("readonly-field");
    }
    
    if (!editRestrictions.canEditAmount) {
        $("#amount").prop("readonly", true).addClass("readonly-field");
    }
    
    if (!editRestrictions.canEditEmployee) {
        $("#employee_id").prop("disabled", true).addClass("disabled-field");
    }
    
    if (!editRestrictions.canEditCreditor) {
        $("#creditor_id").prop("disabled", true).addClass("disabled-field");
    }
    
    console.log('Edit restrictions applied:', editRestrictions);
}

// Función específica para edit que excluye la deducción actual
function loadEmployeeInfoEdit(employeeId, excludeDeductionId) {
    $.ajax({
        url: "' . \App\Core\UrlHelper::panel('deductions/employee-info') . '",
        method: "GET",
        data: { 
            employee_id: employeeId,
            exclude_id: excludeDeductionId
        },
        success: function(response) {
            if (response.success) {
                var emp = response.employee;
                $("#empName").text(emp.firstname + " " + emp.lastname);
                $("#empPosition").text(emp.position_name || "N/A");
                $("#empOtherDeductions").text(emp.total_deductions || "0");
                $("#empTotalOther").text("Q " + (parseFloat(emp.deductions_amount || 0).toFixed(2)));
                $("#empBaseSalary").text("Q " + (parseFloat(emp.salary || 0).toFixed(2)));
                $("#employeeInfo").show();
            } else {
                $("#employeeInfo").hide();
            }
        },
        error: function() {
            $("#employeeInfo").hide();
            console.log("Error cargando información del empleado");
        }
    });
}
</script>';
?>