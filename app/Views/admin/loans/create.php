<!-- Vista: Crear Préstamo - REFACTORIZADO COMPACTO V3.5.14 -->
<div class="container-fluid">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="icon fas fa-exclamation-triangle"></i>
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-hand-holding-usd"></i> Nuevo Préstamo</h3>
        </div>
        <form action="<?= \App\Core\UrlHelper::route('panel/loans') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="card-body">

                <!-- SECCIÓN 1: Información del Préstamo -->
                <div class="row">
                    <!-- Empleado -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-user text-primary"></i> Empleado</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                                </div>
                                <select name="employee_id" id="employee_id" class="form-control select2" required>
                                    <option value="">Seleccione empleado...</option>
                                    <?php if (!empty($employees)): ?>
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?= $emp['id'] ?>">
                                                <?= htmlspecialchars($emp['employee_id'] . ' - ' . $emp['firstname'] . ' ' . $emp['lastname']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Acreedor -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-university text-info"></i> Acreedor</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-landmark"></i></span>
                                </div>
                                <select name="creditor_id" class="form-control select2" required>
                                    <option value="">Seleccione acreedor...</option>
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
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Tipo de Préstamo -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-tags text-success"></i> Tipo de Préstamo</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-file-contract"></i></span>
                                </div>
                                <select name="loan_type" class="form-control" required>
                                    <option value="">Seleccione tipo...</option>
                                    <?php if (!empty($loan_types)): ?>
                                        <?php foreach ($loan_types as $key => $tipo): ?>
                                            <option value="<?= htmlspecialchars($key) ?>">
                                                <?= htmlspecialchars($tipo) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="PERSONAL">Prestamo Personal</option>
                                        <option value="VEHICULAR">Prestamo Vehicular</option>
                                        <option value="HIPOTECARIO">Credito Hipotecario</option>
                                        <option value="EMBARGO">Embargo de Sueldo</option>
                                        <option value="JUDICIAL">Retencion Judicial</option>
                                        <option value="PENSION">Pension Alimenticia</option>
                                        <option value="SEGURO">Seguro/Prima</option>
                                        <option value="COOPERATIVA">Cooperativa</option>
                                        <option value="OTRO">Otro</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Frecuencia -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt text-warning"></i> Frecuencia de Descuento</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                </div>
                                <select name="frequency" class="form-control" required>
                                    <option value="semanal">Semanal (cada 7 días)</option>
                                    <option value="quincenal">Quincenal (cada 15 días)</option>
                                    <option value="mensual" selected>Mensual (cada mes)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: Montos y Plazos -->
                <hr class="my-3">
                <h5 class="text-muted"><i class="fas fa-calculator"></i> Cálculo del Préstamo</h5>

                <div class="row">
                    <!-- Monto Total -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-dollar-sign text-success"></i> Monto Total</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                </div>
                                <input type="number" step="0.01" min="0.01" name="total_amount"
                                       class="form-control" placeholder="0.00" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">.00</span>
                                </div>
                            </div>
                            <small class="text-muted">Monto total a financiar</small>
                        </div>
                    </div>

                    <!-- Cantidad de Cuotas -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-list-ol text-info"></i> Cantidad de Cuotas</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                </div>
                                <input type="number" min="1" name="installments_count"
                                       class="form-control" placeholder="12" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">cuotas</span>
                                </div>
                            </div>
                            <small class="text-muted">Número de pagos</small>
                        </div>
                    </div>

                    <!-- Fecha Primer Descuento -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-calendar-day text-primary"></i> Primer Descuento</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                                </div>
                                <input type="date" name="start_date" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <small class="text-muted">Fecha de la 1ra cuota</small>
                        </div>
                    </div>
                </div>

                <!-- Checkbox Diciembre -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                            <input type="checkbox" class="custom-control-input" id="allow_december"
                                   name="allow_december" checked>
                            <label class="custom-control-label" for="allow_december">
                                <i class="fas fa-calendar-times"></i> Descontar en Diciembre
                                <small class="text-muted">(Si está desactivado, las cuotas de diciembre se pasan a enero)</small>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: Tabla de Cuotas -->
                <hr class="my-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-muted mb-0"><i class="fas fa-table"></i> Tabla de Cuotas</h5>
                    <button type="button" class="btn btn-info btn-sm" id="generate_installments">
                        <i class="fas fa-cogs"></i> Generar Cuotas
                    </button>
                </div>

                <!-- Error Box -->
                <div id="installmentsError" class="alert alert-warning alert-dismissible" style="display: none;">
                    <button type="button" class="close" onclick="this.parentElement.style.display='none'">&times;</button>
                    <i class="icon fas fa-exclamation-triangle"></i>
                    <span></span>
                </div>

                <!-- Tabla -->
                <div class="table-responsive" id="installmentsTableWrapper" style="display: none;">
                    <table class="table table-bordered table-hover table-sm" id="installmentsTable">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center"><i class="fas fa-hashtag"></i> #</th>
                                <th class="text-center"><i class="fas fa-calendar"></i> Fecha</th>
                                <th class="text-right"><i class="fas fa-money-bill"></i> Cuota</th>
                                <th class="text-right"><i class="fas fa-balance-scale"></i> Saldo Inicial</th>
                                <th class="text-right"><i class="fas fa-minus-circle"></i> Amortización</th>
                                <th class="text-right"><i class="fas fa-calculator"></i> Saldo Final</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <!-- Resumen -->
                <div class="mt-3" id="installmentsSummary" style="display: none;">
                    <div class="card bg-light">
                        <div class="card-body py-2">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <strong><i class="fas fa-coins text-success"></i> Cuota Fija:</strong>
                                    <br><span class="h5 text-success" id="summaryFixedAmount"></span>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="fas fa-money-check-alt text-info"></i> Total Amortizado:</strong>
                                    <br><span class="h5 text-info" id="summaryTotalAmortized"></span>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="fas fa-calendar-check text-primary"></i> Último Descuento:</strong>
                                    <br><span class="h5 text-primary" id="summaryLastDate"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer con Botones -->
            <div class="card-footer bg-light">
                <div class="row">
                    <div class="col-md-6">
                        <a href="<?= \App\Core\UrlHelper::route('panel/loans') ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Préstamo
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$scripts = '
<script>
$(document).ready(function() {
    // Inicializar Select2 para todos los selects
    $(".select2").select2({
        theme: "bootstrap4",
        width: "100%"
    });

    // Escuchar cambios en el tipo de planilla para recargar la página
    window.addEventListener("payrollTypeChanged", function(e) {
        // Recargar la página para obtener empleados filtrados por el nuevo tipo de planilla
        window.location.reload();
    });
});

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
        return "$" + (cents / 100).toFixed(2);
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
        errorBox.querySelector("span").textContent = message;
        errorBox.style.display = "block";
    }

    function clearError() {
        if (!errorBox) {
            return;
        }
        errorBox.querySelector("span").textContent = "";
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
                "<td class=\"text-center\">" + i + "</td>" +
                "<td class=\"text-center\">" + formatDate(dueDate) + "</td>" +
                "<td class=\"text-right font-weight-bold text-success\">" + formatMoney(amountCents) + "</td>" +
                "<td class=\"text-right\">" + formatMoney(initialCents) + "</td>" +
                "<td class=\"text-right text-primary\">" + formatMoney(amountCents) + "</td>" +
                "<td class=\"text-right font-weight-bold\">" + formatMoney(finalCents) + "</td>";
            tableBody.appendChild(row);

            lastDate = dueDate;
            currentDate = addFrequency(currentDate, frequency);
        }

        tableWrapper.style.display = "";
        summaryWrapper.style.display = "";
        summaryFixedAmount.textContent = formatMoney(baseCents);
        summaryTotal.textContent = formatMoney(totalCents);
        summaryLastDate.textContent = lastDate ? formatDate(lastDate) : "";

        // Scroll suave a la tabla
        tableWrapper.scrollIntoView({ behavior: "smooth", block: "nearest" });
    });
})();
</script>';
?>
