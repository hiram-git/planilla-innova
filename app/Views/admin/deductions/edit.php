<?php
/**
 * Vista: Editar Deducción
 */
$title = 'Editar Deducción';

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
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? \App\Core\Security::generateToken() ?>">
                
                <div class="card-body">
                    <?php if (isset($editRestrictions['inGeneratedPayroll']) && $editRestrictions['inGeneratedPayroll']): ?>
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Restricciones de Edición</h5>
                        <p><?= htmlspecialchars($editRestrictions['reason'] ?? 'Esta deducción tiene restricciones de edición') ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Información del Empleado (Superior) -->
                    <div class="row mb-4" id="employeeInfo" style="display: block;">
                        <div class="col-md-12">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-user"></i> Información del Empleado
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Nombre:</strong> 
                                            <span id="empName"><?= htmlspecialchars($deduction['employee_name'] ?? 'Cargando...') ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Código:</strong> 
                                            <span id="empCode"><?= htmlspecialchars($deduction['employee_code'] ?? 'N/A') ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Puesto:</strong> 
                                            <span id="empPosition">Cargando...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información Principal (Solo lectura para empleado y acreedor) -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="employee_display">Empleado</label>
                                <input type="text" class="form-control" id="employee_display" 
                                       value="<?= htmlspecialchars($deduction['employee_name'] ?? 'Empleado ID: ' . $deduction['employee_id']) ?>" 
                                       readonly>
                                <input type="hidden" name="employee_id" value="<?= htmlspecialchars($deduction['employee_id']) ?>">
                                <small class="form-text text-muted">El empleado no se puede cambiar</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="creditor_display">Acreedor</label>
                                <?php 
                                $creditorName = 'Acreedor no encontrado';
                                if (!empty($creditors) && is_array($creditors)) {
                                    foreach ($creditors as $creditor) {
                                        if ($creditor['id'] == $deduction['creditor_id']) {
                                            $creditorName = $creditor['description'];
                                            if (!empty($creditor['codigo'])) {
                                                $creditorName .= ' (' . $creditor['codigo'] . ')';
                                            }
                                            break;
                                        }
                                    }
                                }
                                ?>
                                <input type="text" class="form-control" id="creditor_display" 
                                       value="<?= htmlspecialchars($creditorName) ?>" readonly>
                                <input type="hidden" name="creditor_id" value="<?= htmlspecialchars($deduction['creditor_id']) ?>">
                                <small class="form-text text-muted">El acreedor no se puede cambiar</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="description">Descripción</label>
                                <input type="text" class="form-control" id="description" name="description"
                                       value="<?= htmlspecialchars($deduction['description'] ?? '') ?>"
                                       placeholder="Ej: Préstamo personal, Seguro médico, etc."
                                       <?= !$editRestrictions['canEditDescription'] ? 'readonly' : '' ?>>
                                <small class="form-text text-muted">Descripción detallada de la deducción</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="amount">Monto *</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><?= $currency_symbol ?? 'Q' ?></span>
                                    </div>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           step="0.01" min="0.01" max="999999.99" required
                                           value="<?= htmlspecialchars($deduction['amount']) ?>"
                                           placeholder="0.00"
                                           <?= !$editRestrictions['canEditAmount'] ? 'readonly' : '' ?>>
                                </div>
                                <small class="form-text text-muted">Monto a descontar por planilla</small>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Actualización -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Información de Edición</h5>
                                <ul class="mb-0">
                                    <li><strong>Empleado y Acreedor:</strong> No se pueden modificar para mantener la integridad de los datos.</li>
                                    <li><strong>Descripción y Monto:</strong> Solo se pueden editar si la deducción no está en una planilla procesada.</li>
                                    <li>Los cambios se aplicarán en las próximas planillas a procesar.</li>
                                    <li>Para pausar temporalmente una deducción, establezca el monto en 0.01.</li>
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
                                <i class="fas fa-save"></i> Actualizar Deducción
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<?php 
// Estilos específicos para esta vista
$styles = '
<style>
/* Fix para breadcrumbs duplicados */
.content-header .breadcrumb:not(:first-child) {
    display: none !important;
}

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

/* Readonly field styling */
.form-control[readonly] {
    background-color: #f8f9fa !important;
    border-color: #ced4da !important;
}

.form-control[disabled] {
    background-color: #e9ecef !important;
    border-color: #ced4da !important;
}
</style>';

// Scripts específicos para esta vista
$scripts = '
<script>
// Configuración para JavaScript
window.DEDUCTIONS_URLS = {
    searchEmployees: "' . \App\Core\UrlHelper::panel('deductions/search-employees') . '",
    employeeInfo: "' . \App\Core\UrlHelper::panel('deductions/employee-info') . '",
    checkDuplicate: "' . \App\Core\UrlHelper::panel('deductions/check-duplicate') . '"
};

window.CURRENT_EMPLOYEE = {
    id: "' . htmlspecialchars($deduction['employee_id']) . '",
    text: "' . htmlspecialchars($deduction['employee_name'] ?? ('Empleado ID: ' . $deduction['employee_id'])) . '",
    deductionId: ' . $deduction['id'] . '
};

window.EDIT_RESTRICTIONS = {
    canEditEmployee: ' . ($editRestrictions['canEditEmployee'] ? 'true' : 'false') . ',
    canEditCreditor: ' . ($editRestrictions['canEditCreditor'] ? 'true' : 'false') . ',
    canEditDescription: ' . ($editRestrictions['canEditDescription'] ? 'true' : 'false') . ',
    canEditAmount: ' . ($editRestrictions['canEditAmount'] ? 'true' : 'false') . ',
    inGeneratedPayroll: ' . ($editRestrictions['inGeneratedPayroll'] ? 'true' : 'false') . ',
    reason: ' . json_encode($editRestrictions['reason'] ?? 'Sin restricciones') . '
};
</script>
<script src="' . url('assets/js/modules/deductions.js', false) . '"></script>
<script src="' . url('assets/javascript/modules/deductions/edit.js', false) . '"></script>';
?>