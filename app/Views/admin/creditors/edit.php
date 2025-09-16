<?php
/**
 * Vista: Editar Acreedor
 */
$title = $data['title'] ?? 'Editar Acreedor';
$creditor = $data['creditor'] ?? [];
?>

<div class="row">
    <div class="col-sm-12">
        <div class="float-right">
            <a href="<?= \App\Core\UrlHelper::panel('creditors') ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver a Acreedores
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit"></i> Editar Acreedor
                </h3>
                <div class="card-tools">
                    <span class="badge badge-warning">
                        ID: <?= htmlspecialchars($creditor['id'] ?? '') ?>
                    </span>
                </div>
            </div>
            <form id="creditorForm" action="<?= \App\Core\UrlHelper::panel('creditors/' . ($creditor['id'] ?? '') . '/update') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="description">Descripción *</label>
                                <input type="text" class="form-control" id="description" name="description" required
                                       value="<?= htmlspecialchars($creditor['description'] ?? '') ?>"
                                       placeholder="Ej: Banco Industrial, IGSS, Cooperativa San José">
                                <small class="form-text text-muted">Nombre o descripción del acreedor</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="creditor_id">Código del Acreedor</label>
                                <input type="text" class="form-control" id="creditor_id" name="creditor_id"
                                       value="<?= htmlspecialchars($creditor['creditor_id'] ?? '') ?>"
                                       placeholder="Ej: BI001, IGSS, COOP001">
                                <small class="form-text text-muted">Código interno del acreedor (opcional)</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo">Tipo de Acreedor</label>
                                <select class="form-control" id="tipo" name="tipo">
                                    <option value="">Seleccione un tipo...</option>
                                    <?php if (!empty($data['tipos'])): ?>
                                        <?php foreach ($data['tipos'] as $key => $tipo): ?>
                                            <?php $selected = ($creditor['tipo'] ?? '') === $key ? 'selected' : ''; ?>
                                            <option value="<?= htmlspecialchars($key) ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($tipo) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php 
                                        $tipos_default = [
                                            'PERSONAL' => 'Préstamo Personal',
                                            'VEHICULAR' => 'Préstamo Vehicular',
                                            'HIPOTECARIO' => 'Crédito Hipotecario',
                                            'EMBARGO' => 'Embargo de Sueldo',
                                            'JUDICIAL' => 'Retención Judicial',
                                            'PENSION' => 'Pensión Alimenticia',
                                            'SEGURO' => 'Seguro/Prima',
                                            'COOPERATIVA' => 'Cooperativa',
                                            'OTRO' => 'Otro'
                                        ];
                                        foreach ($tipos_default as $key => $tipo): 
                                            $selected = ($creditor['tipo'] ?? '') === $key ? 'selected' : '';
                                        ?>
                                            <option value="<?= htmlspecialchars($key) ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($tipo) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">Clasificación del tipo de acreedor</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select class="form-control" id="estado" name="estado">
                                    <?php 
                                    $currentEstado = $creditor['activo'] ?? 1;
                                    ?>
                                    <option value="1" <?= $currentEstado == 1 ? 'selected' : '' ?>>Activo</option>
                                    <option value="0" <?= $currentEstado == 0 ? 'selected' : '' ?>>Inactivo</option>
                                    <option value="2" <?= $currentEstado == 2 ? 'selected' : '' ?>>Pausado</option>
                                </select>
                                <small class="form-text text-muted">Estado actual del acreedor</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                          placeholder="Notas adicionales, condiciones especiales, etc."><?= htmlspecialchars($creditor['observaciones'] ?? '') ?></textarea>
                                <small class="form-text text-muted">Información adicional sobre el acreedor (opcional)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de Cambios -->
                    <?php if (!empty($creditor['updated_at']) && $creditor['updated_at'] != $creditor['created_at']): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-history"></i> Historial</h6>
                                <small>
                                    <strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($creditor['created_at'] ?? 'now')) ?><br>
                                    <strong>Última modificación:</strong> <?= date('d/m/Y H:i', strtotime($creditor['updated_at'] ?? 'now')) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle"></i> Importante</h5>
                                <ul class="mb-0">
                                    <li>Los cambios afectarán las deducciones activas asignadas a este acreedor.</li>
                                    <li>Si desactiva el acreedor, no aparecerá en nuevas asignaciones.</li>
                                    <li>Las deducciones existentes no se eliminarán automáticamente.</li>
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

<?php 
$scripts = '<script src="' . url('assets/js/modules/creditors.js', false) . '"></script>';
?>