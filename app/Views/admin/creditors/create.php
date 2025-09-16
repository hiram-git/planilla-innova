<?php
/**
 * Vista: Crear Acreedor
 */
$title = $data['title'] ?? 'Crear Acreedor';
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
                    <i class="fas fa-plus-circle"></i> Crear Nuevo Acreedor
                </h3>
            </div>
            <form id="creditorForm" action="<?= \App\Core\UrlHelper::panel('creditors/store') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="description">Descripción *</label>
                                <input type="text" class="form-control" id="description" name="description" required
                                       placeholder="Ej: Banco Industrial, IGSS, Cooperativa San José">
                                <small class="form-text text-muted">Nombre o descripción del acreedor</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="creditor_id">Código del Acreedor</label>
                                <input type="text" class="form-control" id="creditor_id" name="creditor_id"
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
                                            <option value="<?= htmlspecialchars($key) ?>">
                                                <?= htmlspecialchars($tipo) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="PERSONAL">Préstamo Personal</option>
                                        <option value="VEHICULAR">Préstamo Vehicular</option>
                                        <option value="HIPOTECARIO">Crédito Hipotecario</option>
                                        <option value="EMBARGO">Embargo de Sueldo</option>
                                        <option value="JUDICIAL">Retención Judicial</option>
                                        <option value="PENSION">Pensión Alimenticia</option>
                                        <option value="SEGURO">Seguro/Prima</option>
                                        <option value="COOPERATIVA">Cooperativa</option>
                                        <option value="OTRO">Otro</option>
                                    <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">Clasificación del tipo de acreedor</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select class="form-control" id="estado" name="estado">
                                    <option value="1" selected>Activo</option>
                                    <option value="0">Inactivo</option>
                                    <option value="2">Pausado</option>
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
                                          placeholder="Notas adicionales, condiciones especiales, etc."></textarea>
                                <small class="form-text text-muted">Información adicional sobre el acreedor (opcional)</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Información</h5>
                                <ul class="mb-0">
                                    <li>Una vez creado el acreedor, podrá asignar deducciones específicas a empleados.</li>
                                    <li>El código del acreedor es opcional pero recomendado para identificación rápida.</li>
                                    <li>Los acreedores inactivos no aparecerán en las listas de asignación.</li>
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
                                <i class="fas fa-save"></i> Crear Acreedor
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