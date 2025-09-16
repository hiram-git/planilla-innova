<?php
/**
 * Vista: Lista de Acreedores
 */
$title = $data['title'] ?? 'Gestión de Acreedores';
?>

<!-- Estadísticas resumidas -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= $data['stats']['total_acreedores'] ?? 0 ?></h3>
                <p>Total Acreedores</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= $data['stats']['total_deducciones'] ?? 0 ?></h3>
                <p>Deducciones Activas</p>
            </div>
            <div class="icon">
                <i class="fas fa-minus-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= currency_symbol() ?><?= number_format($data['stats']['monto_total'] ?? 0, 2) ?></h3>
                <p>Monto Total</p>
            </div>
            <div class="icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= $data['stats']['empleados_con_deducciones'] ?? 0 ?></h3>
                <p>Empleados con Deducciones</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tarjeta principal -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-building"></i> Lista de Acreedores
        </h3>
        <div class="card-tools">
            <a href="<?= \App\Core\UrlHelper::panel('creditors/create') ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nuevo Acreedor
            </a>
            <a href="<?= \App\Core\UrlHelper::panel('deductions') ?>" class="btn btn-success btn-sm">
                <i class="fas fa-minus-circle"></i> Ver Deducciones
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="creditorsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Código</th>
                        <th>Empleados</th>
                        <th>Monto Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['creditors'] ?? [] as $creditor): ?>
                    <tr>
                        <td><?= htmlspecialchars($creditor['id']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($creditor['description']) ?></strong>
                        </td>
                        <td>
                            <?= htmlspecialchars($creditor['creditor_id'] ?: 'N/A') ?>
                        </td>
                        <td>
                            <?php if (($creditor['empleados_asignados'] ?? 0) > 0): ?>
                                <span class="badge badge-info">
                                    <?= $creditor['empleados_asignados'] ?> empleados
                                </span>
                            <?php else: ?>
                                <span class="badge badge-light">Sin asignaciones</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= currency_symbol() ?><?= number_format($creditor['monto_total_asignado'] ?? 0, 2) ?></strong>
                        </td>
                        <td>
                            <div class="btn-group" role="group" aria-label="Acciones">
                                <a href="<?= \App\Core\UrlHelper::panel('creditors/' . $creditor['id'] . '/edit') ?>" 
                                   class="btn btn-warning btn-sm" 
                                   title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-danger btn-sm" 
                                        onclick="CreditorsModule.confirmDelete(<?= $creditor['id'] ?>, '<?= htmlspecialchars($creditor['description']) ?>', '<?= \App\Core\UrlHelper::panel('creditors') ?>')"
                                        title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer">
        <div class="row">
            <div class="col-sm-6">
                <p class="text-muted">
                    Total: <strong><?= count($data['creditors'] ?? []) ?></strong> acreedores
                </p>
            </div>
            <div class="col-sm-6">
                <div class="float-right">
                    <small class="text-muted">
                        Última actualización: <?= date('d/m/Y H:i:s') ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el acreedor <strong id="deleteCreditorName"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    <strong>Nota:</strong> No podrá eliminar acreedores con deducciones activas asignadas.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <form id="deleteForm" method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Eliminar Acreedor
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
$styles = '<link rel="stylesheet" href="' . url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css', false) . '">
<style>
.small-box {
    border-radius: 0.5rem;
}

.small-box .icon {
    font-size: 3rem;
    opacity: 0.8;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.badge {
    font-size: 0.8em;
}

.table td {
    vertical-align: middle;
}
</style>';

$scripts = '
<script src="' . url('plugins/datatables/jquery.dataTables.min.js', false) . '"></script>
<script src="' . url('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js', false) . '"></script>
<script src="' . url('assets/js/modules/creditors.js', false) . '"></script>';
?>