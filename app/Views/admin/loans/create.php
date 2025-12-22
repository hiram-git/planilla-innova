<div class="container-fluid">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Nuevo Préstamo</h3>
        </div>
        <form action="<?= \App\Core\UrlHelper::route('panel/loans') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="card-body">
                <div class="form-group">
                    <label>Empleado</label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>">
                                <?= htmlspecialchars($emp['employee_id'] . ' - ' . $emp['firstname'] . ' ' . $emp['lastname']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tipo de préstamo</label>
                    <input type="text" name="loan_type" class="form-control" placeholder="Personal, Vehículo..." required>
                </div>

                <div class="form-group">
                    <label>Frecuencia de descuento</label>
                    <select name="frequency" class="form-control" required>
                        <option value="semanal">Semanal</option>
                        <option value="quincenal">Quincenal</option>
                        <option value="mensual" selected>Mensual</option>
                    </select>
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="allow_december" name="allow_december" checked>
                    <label class="form-check-label" for="allow_december">Descuenta en diciembre</label>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Monto total</label>
                        <input type="number" step="0.01" min="0.01" name="total_amount" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Cantidad de cuotas</label>
                        <input type="number" min="1" name="installments_count" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Fecha de inicio</label>
                    <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Guardar y generar cuotas</button>
                <a href="<?= \App\Core\UrlHelper::route('panel/loans') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
