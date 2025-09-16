<?php
/**
 * Vista: Crear Deducción
 */
$title = $data['title'] ?? 'Crear Deducción';
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
                    <i class="fas fa-plus-circle"></i> Crear Nueva Deducción
                </h3>
                <div class="card-tools">
                    <span class="badge badge-warning">
                        Asignación de Deducción
                    </span>
                </div>
            </div>
            <form id="deductionForm" action="<?= \App\Core\UrlHelper::panel('deductions/store') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? \App\Core\Security::generateToken() ?>">
                
                <div class="card-body">
                    <!-- Información Principal -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="employee_id">Empleado *</label>
                                <select class="form-control select2" id="employee_id" name="employee_id" required>
                                    <option value="">Buscar empleado...</option>
                                </select>
                                <small class="form-text text-muted">Busque por nombre o código de empleado</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="creditor_id">Acreedor *</label>
                                <select class="form-control" id="creditor_id" name="creditor_id" required>
                                    <option value="">Seleccione un acreedor...</option>
                                    <?php if (!empty($creditors) && is_array($creditors)): ?>
                                        <?php foreach ($creditors as $creditor): ?>
                                            <option value="<?= htmlspecialchars($creditor['id']) ?>">
                                                <?= htmlspecialchars($creditor['description']) ?>
                                                <?php if (!empty($creditor['codigo'])): ?>
                                                    (<?= htmlspecialchars($creditor['codigo']) ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">Institución o empresa acreedora</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="description">Descripción *</label>
                                <input type="text" class="form-control" id="description" name="description" required
                                       placeholder="Ej: Préstamo personal, Seguro médico, etc.">
                                <small class="form-text text-muted">Descripción detallada de la deducción</small>
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
                                           placeholder="0.00">
                                </div>
                                <small class="form-text text-muted">Monto a descontar por planilla</small>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Validación -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Información Importante</h5>
                                <ul class="mb-0">
                                    <li>Una vez creada la deducción, se aplicará automáticamente en las próximas planillas.</li>
                                    <li>No se pueden crear deducciones duplicadas para el mismo empleado y acreedor.</li>
                                    <li>El monto debe ser mayor a cero y no exceder Q999,999.99.</li>
                                    <li>Para modificar o pausar una deducción, use la opción de edición.</li>
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
                                <i class="fas fa-save"></i> Crear Deducción
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
                        <strong>Deducciones Actuales:</strong> <span id="empCurrentDeductions"></span>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <strong>Total Deducciones:</strong> <span id="empTotalDeductions" class="text-danger"></span>
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
</script>';
?>