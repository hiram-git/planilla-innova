<?php
/**
 * Vista: Editar Concepto Manual de Empleado
 * Version: 3.5.18
 */
?>
        <div class="row">
            <div class="col-md-12">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-alt"></i> Editar Datos del Concepto Manual</h3>
                    </div>

                    <form action="<?= \App\Core\UrlHelper::route('panel/employee-manual-concepts/' . $manual_concept['id'] . '/update') ?>" method="POST" id="manualConceptForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <div class="card-body">
                            <!-- Info no editable -->
                            <div class="alert alert-info">
                                <strong><i class="fas fa-info-circle"></i> Información:</strong>
                                Empleado y Concepto no pueden ser modificados. Para cambiarlos, elimine este registro y cree uno nuevo.
                            </div>

                            <div class="row">
                                <!-- Empleado (Solo lectura) -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Empleado</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname'] . ' (' . $employee['employee_id'] . ')') ?>" readonly>
                                    </div>
                                </div>

                                <!-- Concepto (Solo lectura) -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Concepto</label>
                                        <?php
                                        $concept = $concepts[array_search($manual_concept['concepto_id'], array_column($concepts, 'id'))];
                                        ?>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($concept['descripcion'] ?? 'N/A') ?>" readonly>
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
                                                <option value="<?= $tp['id'] ?>" <?= $manual_concept['tipo_planilla_id'] == $tp['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tp['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Frecuencia -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="frecuencia_id">Frecuencia (Opcional)</label>
                                        <select class="form-control" id="frecuencia_id" name="frecuencia_id">
                                            <option value="">Todas</option>
                                            <?php foreach ($frecuencias as $freq): ?>
                                                <option value="<?= $freq['id'] ?>" <?= $manual_concept['frecuencia_id'] == $freq['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($freq['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Unidad -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="unidad">Unidad</label>
                                        <input type="number" class="form-control" id="unidad" name="unidad"
                                               value="<?= $manual_concept['unidad'] ?>"
                                               step="0.01" min="0">
                                        <small class="form-text text-muted">Ej: 1 día, 2 veces, etc.</small>
                                    </div>
                                </div>

                                <!-- Hora (Opcional) -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="hora">Hora (Opcional)</label>
                                        <input type="number" class="form-control" id="hora" name="hora"
                                               value="<?= $manual_concept['hora'] ?? '' ?>"
                                               step="0.01" min="0">
                                        <small class="form-text text-muted">Horas trabajadas, tardanzas, etc.</small>
                                    </div>
                                </div>

                                <!-- Monto Fijo -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="monto_fijo">Monto Fijo (Opcional)</label>
                                        <input type="number" class="form-control" id="monto_fijo" name="monto_fijo"
                                               value="<?= $manual_concept['monto_fijo'] ?>"
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
                                               value="<?= $manual_concept['fecha_inicio'] ?>" required>
                                    </div>
                                </div>

                                <!-- Fecha Fin -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fecha_fin">Fecha Fin (Opcional)</label>
                                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                               value="<?= $manual_concept['fecha_fin'] ?>">
                                    </div>
                                </div>

                                <!-- Aplica Una Vez -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="aplica_una_vez"
                                                   name="aplica_una_vez" value="1"
                                                   <?= $manual_concept['aplica_una_vez'] ? 'checked' : '' ?>>
                                            <label class="custom-control-label" for="aplica_una_vez">
                                                <strong>Aplicar solo una vez</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones"
                                                  rows="3"><?= htmlspecialchars($manual_concept['observaciones'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Actualizar
                            </button>
                            <a href="<?= \App\Core\UrlHelper::route('panel/employee-manual-concepts') ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<!-- Scripts -->
<?php
$scripts = "
<script>
$(document).ready(function() {
    $('#manualConceptForm').on('submit', function(e) {
        var fechaInicio = new Date($('#fecha_inicio').val());
        var fechaFin = $('#fecha_fin').val() ? new Date($('#fecha_fin').val()) : null;

        if (fechaFin && fechaFin < fechaInicio) {
            e.preventDefault();
            Swal.fire('Error', 'La fecha fin debe ser posterior a la fecha inicio', 'error');
            return false;
        }
    });
});
</script>
";
?>
