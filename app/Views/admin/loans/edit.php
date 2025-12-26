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
        $needsCustomOption = $currentType && !array_key_exists($selectedType, $loanTypes);
        $statusValue = strtolower($loan['status'] ?? ($loan['estado'] ?? ''));
        $isCancelled = $statusValue === 'anulado';
        $statusLabel = $statusValue ? ucfirst($statusValue) : 'Activo';
        $statusClass = $isCancelled ? 'badge badge-danger' : 'badge badge-success';
        $frequencyLabels = ['semanal' => 'Semanal', 'quincenal' => 'Quincenal', 'mensual' => 'Mensual'];
        $frequencyLabel = $frequencyLabels[$loan['frequency']] ?? ($loan['frequency'] ?? '');
    ?>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Editar Prestamo</h3>
        </div>

        <form id="loanEditForm" action="<?= \App\Core\UrlHelper::route('panel/loans/update/' . $loan['id']) ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="card-body">
                <?php if ($isCancelled): ?>
                    <div class="alert alert-warning">
                        Este prestamo esta anulado y no puede modificarse.
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
                    <select name="loan_type" class="form-control" required <?= $isCancelled ? 'disabled' : '' ?>>
                        <option value="">Seleccione un tipo...</option>
                        <?php if ($needsCustomOption): ?>
                            <option value="<?= htmlspecialchars($currentType) ?>" selected>
                                <?= htmlspecialchars($currentType) ?>
                            </option>
                        <?php endif; ?>
                        <?php if (!empty($loanTypes)): ?>
                            <?php foreach ($loanTypes as $key => $tipo): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $selectedType === $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="PERSONAL" <?= $selectedType === 'PERSONAL' ? 'selected' : '' ?>>Prestamo Personal</option>
                            <option value="VEHICULAR" <?= $selectedType === 'VEHICULAR' ? 'selected' : '' ?>>Prestamo Vehicular</option>
                            <option value="HIPOTECARIO" <?= $selectedType === 'HIPOTECARIO' ? 'selected' : '' ?>>Credito Hipotecario</option>
                            <option value="EMBARGO" <?= $selectedType === 'EMBARGO' ? 'selected' : '' ?>>Embargo de Sueldo</option>
                            <option value="JUDICIAL" <?= $selectedType === 'JUDICIAL' ? 'selected' : '' ?>>Retencion Judicial</option>
                            <option value="PENSION" <?= $selectedType === 'PENSION' ? 'selected' : '' ?>>Pension Alimenticia</option>
                            <option value="SEGURO" <?= $selectedType === 'SEGURO' ? 'selected' : '' ?>>Seguro/Prima</option>
                            <option value="COOPERATIVA" <?= $selectedType === 'COOPERATIVA' ? 'selected' : '' ?>>Cooperativa</option>
                            <option value="OTRO" <?= $selectedType === 'OTRO' ? 'selected' : '' ?>>Otro</option>
                        <?php endif; ?>
                    </select>
                    <small class="form-text text-muted">Solo se permite editar el tipo del prestamo.</small>
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
        </form>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <div>
                <?php if (!$isCancelled): ?>
                    <button type="submit" form="loanEditForm" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                <?php endif; ?>
                <a href="<?= \App\Core\UrlHelper::route('panel/loans') ?>" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
            <?php if (!$isCancelled): ?>
                <form method="POST"
                      action="<?= \App\Core\UrlHelper::route('panel/loans/cancel/' . $loan['id']) ?>"
                      onsubmit="return confirm('Desea anular este prestamo?');">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban"></i> Anular prestamo
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
