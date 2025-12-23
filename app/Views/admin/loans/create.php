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
                    <label>Acreedor</label>
                    <select name="creditor_id" class="form-control" required>
                        <option value="">Seleccione</option>
                        <?php if (!empty($creditors)): ?>
                            <?php foreach ($creditors as $creditor): ?>
                                <option value="<?= $creditor['id'] ?>">
                                    <?= htmlspecialchars($creditor['description']) ?>
                                    <?php if (!empty($creditor['creditor_id'])): ?>
                                        (<?= htmlspecialchars($creditor['creditor_id']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                    <label>Fecha del primer descuento</label>
                    <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    <small class="form-text text-muted">Formato esperado: DD/MM/YYYY</small>
                </div>

                <div class="mt-4">
                    <h5>Tabla de cuotas</h5>
                    <div id="installmentsError" class="alert alert-warning" style="display: none;"></div>
                    <div class="table-responsive" id="installmentsTableWrapper" style="display: none;">
                        <table class="table table-bordered table-sm" id="installmentsTable">
                            <thead>
                                <tr>
                                    <th>Numero de cuota</th>
                                    <th>Fecha de descuento</th>
                                    <th>Valor de la cuota</th>
                                    <th>Saldo inicial</th>
                                    <th>Amortizacion</th>
                                    <th>Saldo final</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="mt-3" id="installmentsSummary" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Valor cuota fija:</strong>
                                <span id="summaryFixedAmount"></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Total amortizado:</strong>
                                <span id="summaryTotalAmortized"></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Fecha ultimo descuento:</strong>
                                <span id="summaryLastDate"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-info" id="generate_installments">Generar cuotas</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="<?= \App\Core\UrlHelper::route('panel/loans') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
$scripts = '
<script>
(function () {
    const generateButton = document.getElementById("generate_installments");
    if (!generateButton) {
        return;
    }

    const errorBox = document.getElementById("installmentsError");
    const tableWrapper = document.getElementById("installmentsTableWrapper");
    const tableBody = document.querySelector("#installmentsTable tbody");
    const summaryWrapper = document.getElementById("installmentsSummary");
    const summaryFixedAmount = document.getElementById("summaryFixedAmount");
    const summaryTotal = document.getElementById("summaryTotalAmortized");
    const summaryLastDate = document.getElementById("summaryLastDate");

    const form = generateButton.closest("form");
    const totalAmountInput = form.querySelector("[name=\'total_amount\']");
    const installmentsInput = form.querySelector("[name=\'installments_count\']");
    const frequencySelect = form.querySelector("[name=\'frequency\']");
    const startDateInput = form.querySelector("[name=\'start_date\']");
    const allowDecemberInput = form.querySelector("[name=\'allow_december\']");

    function formatMoney(cents) {
        return (cents / 100).toFixed(2);
    }

    function formatDate(date) {
        const day = String(date.getDate()).padStart(2, "0");
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const year = date.getFullYear();
        return day + "/" + month + "/" + year;
    }

    function parseDate(value) {
        if (!value) {
            return null;
        }
        const parsed = new Date(value + "T00:00:00");
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    function addFrequency(date, frequency) {
        const next = new Date(date.getTime());
        if (frequency === "semanal") {
            next.setDate(next.getDate() + 7);
            return next;
        }
        if (frequency === "quincenal") {
            next.setDate(next.getDate() + 15);
            return next;
        }
        const day = next.getDate();
        next.setMonth(next.getMonth() + 1);
        if (next.getDate() !== day) {
            next.setDate(0);
        }
        return next;
    }

    function showError(message) {
        if (!errorBox) {
            return;
        }
        errorBox.textContent = message;
        errorBox.style.display = "block";
    }

    function clearError() {
        if (!errorBox) {
            return;
        }
        errorBox.textContent = "";
        errorBox.style.display = "none";
    }

    generateButton.addEventListener("click", function () {
        clearError();

        const totalAmount = parseFloat(totalAmountInput.value);
        const installmentsCount = parseInt(installmentsInput.value, 10);
        const frequency = frequencySelect.value;
        const startDate = parseDate(startDateInput.value);
        const allowDecember = allowDecemberInput ? allowDecemberInput.checked : true;

        if (!totalAmount || totalAmount <= 0 || !installmentsCount || installmentsCount <= 0 || !startDate) {
            showError("Complete monto total, cantidad de cuotas y fecha del primer descuento para generar la tabla.");
            return;
        }

        const totalCents = Math.round(totalAmount * 100);
        const baseCents = Math.round(totalCents / installmentsCount);
        let remainingCents = totalCents;
        let currentDate = new Date(startDate.getTime());
        let lastDate = null;

        tableBody.innerHTML = "";

        for (let i = 1; i <= installmentsCount; i += 1) {
            const dueDate = new Date(currentDate.getTime());
            if (!allowDecember && dueDate.getMonth() === 11) {
                dueDate.setFullYear(dueDate.getFullYear() + 1);
                dueDate.setMonth(0, 1);
            }

            const amountCents = i < installmentsCount ? baseCents : remainingCents;
            const initialCents = remainingCents;
            remainingCents -= amountCents;
            const finalCents = remainingCents;

            const row = document.createElement("tr");
            row.innerHTML =
                "<td>" + i + "</td>" +
                "<td>" + formatDate(dueDate) + "</td>" +
                "<td>" + formatMoney(amountCents) + "</td>" +
                "<td>" + formatMoney(initialCents) + "</td>" +
                "<td>" + formatMoney(amountCents) + "</td>" +
                "<td>" + formatMoney(finalCents) + "</td>";
            tableBody.appendChild(row);

            lastDate = dueDate;
            currentDate = addFrequency(currentDate, frequency);
        }

        tableWrapper.style.display = "";
        summaryWrapper.style.display = "";
        summaryFixedAmount.textContent = formatMoney(baseCents);
        summaryTotal.textContent = formatMoney(totalCents);
        summaryLastDate.textContent = lastDate ? formatDate(lastDate) : "";
    });
})();
</script>';
?>
