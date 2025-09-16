<?php
$title = 'Gestión de Deducciones';

$scripts = '
<script src="' . url('assets/js/modules/deductions.js', false) . '"></script>
<script>
// Configurar URLs para el módulo
DeductionsModule.setUrls({
    delete: "' . \App\Core\UrlHelper::panel('deductions') . '",
    csrfToken: "' . \App\Core\Security::generateToken() . '"
});
</script>';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <a href="<?= \App\Core\UrlHelper::panel('deductions/create') ?>" class="btn btn-primary btn-sm ml-3">
                        <i class="fas fa-plus"></i> Nueva Deducción
                    </a>
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <select id="filterCreditor" class="form-control" style="width: 120px;">
                            <option value="">Todos los acreedores</option>
                            <?php if (!empty($creditors)): ?>
                                <?php foreach ($creditors as $creditor): ?>
                                    <option value="<?= htmlspecialchars($creditor['description']) ?>">
                                        <?= htmlspecialchars($creditor['description']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <input type="text" id="searchInput" class="form-control float-right" placeholder="Buscar deducción...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Estadísticas Rápidas -->
                <?php if (!empty($stats)): ?>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Deducciones</span>
                                <span class="info-box-number"><?= number_format($stats['total_deducciones']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Monto Total</span>
                                <span class="info-box-number"><?= $currency_symbol ?? 'Q' ?> <?= number_format($stats['monto_total'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Empleados</span>
                                <span class="info-box-number"><?= number_format($stats['empleados_con_deducciones']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-building"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Acreedores</span>
                                <span class="info-box-number"><?= number_format($stats['acreedores_activos']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table id="deductionsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empleado</th>
                                <th>Acreedor</th>
                                <th>Descripción</th>
                                <th>Monto</th>
                                <th>Puesto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($deductions)): ?>
                            <?php foreach ($deductions as $deduction): ?>
                                <tr>
                                    <td><?= htmlspecialchars($deduction['id']) ?></td>
                                    <td>
                                        <div class="employee-info">
                                            <strong><?= htmlspecialchars($deduction['firstname'] . ' ' . $deduction['lastname']) ?></strong>
                                            <br><small class="text-muted">Código: <?= htmlspecialchars($deduction['emp_code'] ?? $deduction['employee_id']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?= htmlspecialchars($deduction['creditor_name']) ?>
                                        </span>
                                        <br><small class="text-muted">ID: <?= htmlspecialchars($deduction['creditor_code'] ?? $deduction['creditor_id']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($deduction['description']) ?></td>
                                    <td>
                                        <strong class="text-danger"><?= $currency_symbol ?? 'Q' ?> <?= number_format($deduction['amount'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($deduction['position_name'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= \App\Core\UrlHelper::panel('deductions/' . $deduction['id'] . '/edit') ?>" 
                                               class="btn btn-warning btn-sm"
                                               data-toggle="tooltip" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm delete-deduction" 
                                                    data-id="<?= $deduction['id'] ?>" 
                                                    data-employee="<?= htmlspecialchars($deduction['firstname'] . ' ' . $deduction['lastname']) ?>"
                                                    data-creditor="<?= htmlspecialchars($deduction['creditor_name']) ?>"
                                                    data-toggle="tooltip" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> No hay deducciones registradas
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmar Eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirmar Eliminación</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar la deducción?</p>
                <div class="alert alert-info">
                    <strong>Empleado:</strong> <span id="deleteEmployee"></span><br>
                    <strong>Acreedor:</strong> <span id="deleteCreditor"></span>
                </div>
                <p class="text-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
            </div>
        </div>
    </div>
</div>


<style>
.employee-info {
    min-height: 40px;
}

.info-box {
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: .25rem;
    background-color: #fff;
    display: flex;
    margin-bottom: 1rem;
    min-height: 80px;
    padding: .5rem;
    position: relative;
    width: 100%;
}

.info-box .info-box-icon {
    border-radius: .25rem;
    align-items: center;
    display: flex;
    font-size: 1.875rem;
    justify-content: center;
    text-align: center;
    width: 70px;
}

.info-box .info-box-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    line-height: 1.8;
    flex: 1;
    padding: 0 10px;
}

.info-box .info-box-number {
    display: block;
    margin-top: .25rem;
    font-size: 1.125rem;
    font-weight: 700;
}

.info-box .info-box-text {
    display: block;
    font-size: .875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.btn-group .btn {
    border-radius: 0.25rem;
}
</style>