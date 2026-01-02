<?php
/**
 * Vista: Crear Concepto Manual de Empleado
 * Version: 3.5.18
 */
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-alt"></i> Datos del Concepto Manual</h3>
                    </div>

                    <form action="<?= \App\Core\UrlHelper::route('panel/employee-manual-concepts/store') ?>" method="POST" id="manualConceptForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <div class="card-body">
                            <div class="row">
                                <!-- Empleado -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="employee_id">Empleado <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="employee_id" name="employee_id" required style="width: 100%;">
                                            <option value="">Seleccione un empleado...</option>
                                            <?php foreach ($employees as $emp): ?>
                                                <option value="<?= $emp['id'] ?>" <?= ($employee && $employee['id'] == $emp['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname'] . ' (' . $emp['employee_id'] . ')') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Busque por nombre o código</small>
                                    </div>
                                </div>

                                <!-- Concepto -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="concepto_id">Concepto <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="concepto_id" name="concepto_id" required style="width: 100%;">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($concepts as $concept): ?>
                                                <option value="<?= $concept['id'] ?>"
                                                        data-tipo="<?= $concept['tipo_concepto'] ?>"
                                                        <?= ($formData['concepto_id'] ?? '') == $concept['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($concept['descripcion']) ?> (<?= $concept['tipo_concepto'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Tipo de Planilla -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tipo_planilla_id">Tipo de Planilla (Opcional)</label>
                                        <select class="form-control" id="tipo_planilla_id" name="tipo_planilla_id">
                                            <option value="">Todas</option>
                                            <?php foreach ($tipos_planilla as $tp): ?>
                                                <option value="<?= $tp['id'] ?>" <?= ($formData['tipo_planilla_id'] ?? '') == $tp['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tp['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Deje vacío para aplicar a todos los tipos</small>
                                    </div>
                                </div>

                                <!-- Frecuencia -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="frecuencia_id">Frecuencia (Opcional)</label>
                                        <select class="form-control" id="frecuencia_id" name="frecuencia_id">
                                            <option value="">Todas</option>
                                            <?php foreach ($frecuencias as $freq): ?>
                                                <option value="<?= $freq['id'] ?>" <?= ($formData['frecuencia_id'] ?? '') == $freq['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($freq['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Deje vacío para aplicar a todas las frecuencias</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Unidad -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="unidad">Unidad</label>
                                        <input type="number" class="form-control" id="unidad" name="unidad"
                                               value="<?= $formData['unidad'] ?? '1.00' ?>"
                                               step="0.01" min="0">
                                        <small class="form-text text-muted">Ej: 1 día, 2 veces, etc.</small>
                                    </div>
                                </div>

                                <!-- Hora (Opcional) -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="hora">Hora (Opcional)</label>
                                        <input type="number" class="form-control" id="hora" name="hora"
                                               value="<?= $formData['hora'] ?? '' ?>"
                                               step="0.01" min="0">
                                        <small class="form-text text-muted">Horas trabajadas, tardanzas, etc.</small>
                                    </div>
                                </div>

                                <!-- Monto Fijo -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="monto_fijo">Monto Fijo (Opcional)</label>
                                        <input type="number" class="form-control" id="monto_fijo" name="monto_fijo"
                                               value="<?= $formData['monto_fijo'] ?? '' ?>"
                                               step="0.01" min="0">
                                        <small class="form-text text-muted">Ignora fórmula del concepto</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Fecha Inicio -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fecha_inicio">Fecha Inicio <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                               value="<?= $formData['fecha_inicio'] ?? date('Y-m-d') ?>" required>
                                    </div>
                                </div>

                                <!-- Fecha Fin -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fecha_fin">Fecha Fin (Opcional)</label>
                                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                               value="<?= $formData['fecha_fin'] ?? '' ?>">
                                        <small class="form-text text-muted">Deje vacío para indefinido</small>
                                    </div>
                                </div>

                                <!-- Aplica Una Vez -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="aplica_una_vez"
                                                   name="aplica_una_vez" value="1"
                                                   <?= !empty($formData['aplica_una_vez']) ? 'checked' : '' ?>>
                                            <label class="custom-control-label" for="aplica_una_vez">
                                                <strong>Aplicar solo una vez</strong>
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">Se marcará como aplicado automáticamente</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones"
                                                  rows="3" placeholder="Motivo, referencia, notas adicionales..."><?= htmlspecialchars($formData['observaciones'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Concepto Manual
                            </button>
                            <a href="<?= \App\Core\UrlHelper::route('panel/employee-manual-concepts') ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<!-- Estilos personalizados para Select2 -->
<style>
.select2-container--bootstrap4 .select2-selection--single {
    height: calc(2.25rem + 2px) !important;
}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
    line-height: calc(2.25rem) !important;
    padding-left: 12px !important;
    padding-right: 20px !important;
    display: block !important;
}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
    color: #6c757d !important;
    line-height: calc(2.25rem) !important;
}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__clear {
    height: 20px !important;
    width: 20px !important;
    line-height: 18px !important;
    margin-top: 7px !important;
    margin-right: 5px !important;
    font-size: 18px !important;
    position: absolute !important;
    right: 20px !important;
}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
    height: calc(2.25rem + 2px) !important;
    top: 0 !important;
    right: 1px !important;
}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b {
    margin-top: 0 !important;
    top: 50% !important;
}
</style>

<!-- Scripts -->
<?php
$scripts = "
<script>
$(document).ready(function() {
    // Select2 para empleados con búsqueda local
    $('#employee_id').select2({
        placeholder: 'Seleccione un empleado...',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap4'
    });

    // Select2 para conceptos
    $('#concepto_id').select2({
        placeholder: 'Seleccione un concepto...',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap4'
    });

    // Validación del formulario
    $('#manualConceptForm').on('submit', function(e) {
        var fechaInicio = new Date($('#fecha_inicio').val());
        var fechaFin = $('#fecha_fin').val() ? new Date($('#fecha_fin').val()) : null;

        if (fechaFin && fechaFin < fechaInicio) {
            e.preventDefault();
            Swal.fire('Error', 'La fecha fin debe ser posterior a la fecha inicio', 'error');
            return false;
        }

        var unidad = parseFloat($('#unidad').val() || 0);
        var montoFijo = parseFloat($('#monto_fijo').val() || 0);

        if (montoFijo === 0 && unidad === 0) {
            e.preventDefault();
            Swal.fire('Error', 'Debe especificar una unidad o un monto fijo', 'error');
            return false;
        }
    });
});
</script>
";
?>
