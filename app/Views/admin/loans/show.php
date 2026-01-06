<div class="container-fluid">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php
        $loanTypes = $loan_types ?? [];
        $currentType = $loan['loan_type'] ?? '';
        $selectedType = $currentType;
        if (!array_key_exists($selectedType, $loanTypes)) {
            $matchedKey = array_search($selectedType, $loanTypes, true);
            if ($matchedKey !== false) {
                $selectedType = $matchedKey;
            }
        }
        $loanTypeLabel = $loanTypes[$selectedType] ?? $currentType;
        $statusValue = strtolower($loan['status'] ?? ($loan['estado'] ?? ''));
        $isCancelled = $statusValue === 'anulado';
        $statusLabel = $statusValue ? ucfirst($statusValue) : 'Activo';
        $statusClass = $isCancelled ? 'badge badge-danger' : 'badge badge-success';
        $frequencyLabels = ['semanal' => 'Semanal', 'quincenal' => 'Quincenal', 'mensual' => 'Mensual'];
        $frequencyLabel = $frequencyLabels[$loan['frequency']] ?? ($loan['frequency'] ?? '');
    ?>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Ver Prestamo</h3>
        </div>

        <div class="card-body">
            <?php if ($isCancelled): ?>
                <div class="alert alert-warning">
                    Este prestamo esta anulado.
                </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Empleado</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($employeeName) ?>" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label>Acreedor</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($creditorName) ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Frecuencia</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($frequencyLabel) ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label>Monto total</label>
                    <input type="text" class="form-control" value="<?= number_format($loan['total_amount'], 2) ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label>Cuotas</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($loan['installments_count']) ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Fecha inicio</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($loan['start_date']) ?>" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label>Estado</label>
                    <div>
                        <span class="<?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Tipo de prestamo</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($loanTypeLabel) ?>" readonly>
            </div>

            <div class="mt-4">
                <h5>Cuotas del prestamo</h5>
                <?php if (!empty($installments)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Numero</th>
                                    <th>Vencimiento</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($installments as $installment): ?>
                                    <?php
                                        $statusValue = strtolower($installment['status'] ?? '');
                                        $statusLabel = $statusValue ? ucfirst($statusValue) : 'Generada';
                                        $statusClass = $statusValue === 'pagada'
                                            ? 'badge badge-success'
                                            : ($statusValue === 'anulado' || $statusValue === 'cancelada'
                                                ? 'badge badge-danger'
                                                : 'badge badge-warning');
                                        $dueDate = $installment['due_date'] ?? '';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($installment['installment_number'] ?? '') ?></td>
                                        <td><?= $dueDate ? htmlspecialchars(date('d/m/Y', strtotime($dueDate))) : '' ?></td>
                                        <td><?= number_format((float)($installment['amount'] ?? 0), 2) ?></td>
                                        <td><span class="<?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light">Sin cuotas registradas.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-footer">
            <a href="<?= \App\Core\UrlHelper::route('panel/loans') ?>" class="btn btn-secondary">
                Volver
            </a>
        </div>
    </div>
</div>
