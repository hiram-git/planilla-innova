<div class="container-fluid">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Préstamos</h3>
            <a href="<?= \App\Core\UrlHelper::route('panel/loans/create') ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nuevo préstamo
            </a>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empleado</th>
                        <th>Tipo</th>
                        <th>Frecuencia</th>
                        <th>Monto</th>
                        <th>Cuotas</th>
                        <th>Fecha Inicio</th>
                        <th>Creado</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($loans)): ?>
                        <?php foreach ($loans as $loan): ?>
                            <?php
                                $loanTypeKey = $loan['loan_type'] ?? '';
                                $loanTypeLabel = $loanTypes[$loanTypeKey] ?? $loanTypeKey;
                                $statusValue = strtolower($loan['status'] ?? ($loan['estado'] ?? ''));
                                $isCancelled = $statusValue === 'anulado';
                                $statusLabel = $statusValue ? ucfirst($statusValue) : 'Activo';
                                $statusClass = $isCancelled ? 'badge badge-danger' : 'badge badge-success';
                            ?>
                            <tr>
                                <td><?= $loan['id'] ?></td>
                                <td><?= htmlspecialchars($employeesById[$loan['employee_id']] ?? 'Empleado') ?></td>
                                <td><?= htmlspecialchars($loanTypeLabel) ?></td>
                                <td><?= htmlspecialchars($loan['frequency']) ?></td>
                                <td><?= number_format($loan['total_amount'], 2) ?></td>
                                <td><?= $loan['installments_count'] ?></td>
                                <td><?= htmlspecialchars($loan['start_date']) ?></td>
                                <td><?= htmlspecialchars($loan['created_at']) ?></td>
                                <td><span class="<?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= \App\Core\UrlHelper::route('panel/loans/edit/' . $loan['id']) ?>"
                                           class="btn btn-warning btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (!$isCancelled): ?>
                                            <form method="POST"
                                                  action="<?= \App\Core\UrlHelper::route('panel/loans/cancel/' . $loan['id']) ?>"
                                                  style="display: inline;"
                                                  onsubmit="return confirm('¿Desea anular este préstamo?');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Anular">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center">Sin préstamos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
