<?php
$pageTitle = "Nueva Solicitud de Vacaciones - " . htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']);
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-umbrella-beach mr-2"></i>
                    Nueva Solicitud de Vacaciones
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/vacation') ?>">Vacaciones</a></li>
                    <li class="breadcrumb-item active">Nueva Solicitud</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <!-- Información del Empleado -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user mr-2"></i>
                    Información del Empleado
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Nombre:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) ?></dd>

                            <dt class="col-sm-4">Ficha:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($employee['employee_id']) ?></dd>

                            <dt class="col-sm-4">Cédula:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($employee['document_id'] ?? 'N/A') ?></dd>

                            <dt class="col-sm-4">Cargo:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($employee['position_name'] ?? 'N/A') ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Fecha Ingreso:</dt>
                            <dd class="col-sm-8"><?= date('d/m/Y', strtotime($employee['fecha_ingreso'])) ?></dd>

                            <dt class="col-sm-4">Tiempo Laborado:</dt>
                            <dd class="col-sm-8">
                                <?php
                                $meses = floor((time() - strtotime($employee['fecha_ingreso'])) / (30 * 24 * 60 * 60));
                                $anos = floor($meses / 12);
                                $meses_restantes = $meses % 12;
                                echo "<strong>{$anos} años, {$meses_restantes} meses</strong>";
                                ?>
                            </dd>

                            <dt class="col-sm-4">Salario Base:</dt>
                            <dd class="col-sm-8">
                                <?php if ($employee['sueldo_individual']): ?>
                                    <?= currency_symbol() ?><?= number_format($employee['sueldo_individual'], 2) ?>
                                <?php else: ?>
                                    <span class="text-muted">Según posición</span>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4">Estado:</dt>
                            <dd class="col-sm-8">
                                <span class="badge badge-success"><?= htmlspecialchars($employee['situacion_nombre'] ?? 'Activo') ?></span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance de Vacaciones -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-balance-scale mr-2"></i>
                    Balance de Vacaciones
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-gift"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Días Ganados</span>
                                <span class="info-box-number"><?= number_format($vacation_data['days_earned'], 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-piggy-bank"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Balance Actual</span>
                                <span class="info-box-number"><?= number_format($vacation_data['current_balance'], 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-calendar-plus"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Acumulación Mensual</span>
                                <span class="info-box-number"><?= number_format($vacation_data['accrual_rate'], 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fas fa-money-bill"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Valor Día</span>
                                <span class="info-box-number"><?= currency_symbol() ?><?= number_format($vacation_data['daily_salary'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($vacation_data['current_balance'] <= 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Atención:</strong> Este empleado no tiene días de vacaciones disponibles.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario de Solicitud -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-plus mr-2"></i>
                    Datos de la Solicitud
                </h3>
            </div>
            <form method="POST" action="<?= \App\Core\UrlHelper::route('panel/vacation/store') ?>" id="vacationForm">
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">Fecha de Inicio *</label>
                                <input type="date"
                                       class="form-control <?= isset($errors['start_date']) ? 'is-invalid' : '' ?>"
                                       id="start_date"
                                       name="start_date"
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                       required>
                                <?php if (isset($errors['start_date'])): ?>
                                    <div class="invalid-feedback"><?= $errors['start_date'] ?></div>
                                <?php endif; ?>
                                <small class="form-text text-muted">
                                    Las vacaciones deben solicitarse con al menos 15 días de anticipación.
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_date">Fecha de Fin *</label>
                                <input type="date"
                                       class="form-control <?= isset($errors['end_date']) ? 'is-invalid' : '' ?>"
                                       id="end_date"
                                       name="end_date"
                                       min="<?= date('Y-m-d', strtotime('+2 days')) ?>"
                                       required>
                                <?php if (isset($errors['end_date'])): ?>
                                    <div class="invalid-feedback"><?= $errors['end_date'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vacation_type">Tipo de Vacaciones *</label>
                                <select class="form-control <?= isset($errors['vacation_type']) ? 'is-invalid' : '' ?>"
                                        id="vacation_type"
                                        name="vacation_type"
                                        required>
                                    <option value="">Seleccione...</option>
                                    <option value="ANNUAL" selected>Vacaciones Anuales</option>
                                    <option value="ACCUMULATED">Vacaciones Acumuladas</option>
                                    <option value="COMPENSATION">Compensación Monetaria</option>
                                    <option value="PROPORTIONAL">Vacaciones Proporcionales</option>
                                </select>
                                <?php if (isset($errors['vacation_type'])): ?>
                                    <div class="invalid-feedback"><?= $errors['vacation_type'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Resumen de Cálculo</label>
                                <div class="bg-light p-3 rounded">
                                    <div id="calculation-summary">
                                        <p class="mb-1"><strong>Días Totales:</strong> <span id="total-days">0</span></p>
                                        <p class="mb-1"><strong>Días Hábiles:</strong> <span id="business-days">0</span></p>
                                        <p class="mb-1"><strong>Balance Después:</strong> <span id="remaining-balance"><?= number_format($vacation_data['current_balance'], 1) ?></span></p>
                                        <p class="mb-0"><strong>Monto Compensación:</strong> <span id="compensation-amount"><?= currency_symbol() ?>0.00</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comments">Comentarios/Observaciones</label>
                        <textarea class="form-control"
                                  id="comments"
                                  name="comments"
                                  rows="3"
                                  placeholder="Indique cualquier observación adicional sobre la solicitud..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Información Legal:</h5>
                        <ul class="mb-0">
                            <li>Según la legislación panameña, tiene derecho a 30 días de vacaciones por cada 11 meses trabajados.</li>
                            <li>Las vacaciones deben ser disfrutadas en períodos mínimos de 15 días consecutivos.</li>
                            <li>Las vacaciones pueden ser compensadas monetariamente con autorización del empleado.</li>
                            <li>El período de vacaciones se calcula en días hábiles (excluyendo fines de semana y feriados).</li>
                        </ul>
                    </div>

                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="<?= \App\Core\UrlHelper::route('panel/vacation') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Cancelar
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                <i class="fas fa-paper-plane mr-1"></i> Enviar Solicitud
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Historial de Solicitudes Anteriores -->
        <?php if (!empty($previous_requests)): ?>
            <div class="card card-secondary collapsed-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history mr-2"></i>
                        Historial de Solicitudes Anteriores
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha Solicitud</th>
                                    <th>Período</th>
                                    <th>Días</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($previous_requests as $request): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($request['request_date'])) ?></td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($request['start_date'])) ?>
                                            al
                                            <?= date('d/m/Y', strtotime($request['end_date'])) ?>
                                        </td>
                                        <td><?= $request['business_days'] ?> días</td>
                                        <td>
                                            <?php
                                            $types = [
                                                'ANNUAL' => 'Anuales',
                                                'ACCUMULATED' => 'Acumuladas',
                                                'COMPENSATION' => 'Compensación',
                                                'PROPORTIONAL' => 'Proporcionales'
                                            ];
                                            echo $types[$request['vacation_type']] ?? $request['vacation_type'];
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_badges = [
                                                'PENDING' => '<span class="badge badge-warning">Pendiente</span>',
                                                'APPROVED' => '<span class="badge badge-success">Aprobada</span>',
                                                'REJECTED' => '<span class="badge badge-danger">Rechazada</span>',
                                                'TAKEN' => '<span class="badge badge-info">Tomada</span>',
                                                'CANCELLED' => '<span class="badge badge-secondary">Cancelada</span>'
                                            ];
                                            echo $status_badges[$request['status']] ?? '<span class="badge badge-light">N/A</span>';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
$(document).ready(function() {
    const currentBalance = <?= $vacation_data['current_balance'] ?>;
    const dailySalary = <?= $vacation_data['daily_salary'] ?>;

    // Calcular días y actualizar resumen cuando cambien las fechas
    function updateCalculation() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const vacationType = $('#vacation_type').val();

        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);

            if (end > start) {
                // Calcular días totales
                const totalDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;

                // Estimar días hábiles (aproximado - el servidor calculará exacto)
                const businessDays = Math.floor(totalDays * 5/7);

                // Actualizar interfaz
                $('#total-days').text(totalDays);
                $('#business-days').text(businessDays);
                $('#remaining-balance').text((currentBalance - businessDays).toFixed(1));

                // Calcular compensación si aplica
                if (vacationType === 'COMPENSATION') {
                    const compensation = businessDays * dailySalary;
                    $('#compensation-amount').text('<?= currency_symbol() ?>' + compensation.toFixed(2));
                } else {
                    $('#compensation-amount').text('<?= currency_symbol() ?>0.00');
                }

                // Validar balance
                if (businessDays <= currentBalance && businessDays > 0) {
                    $('#submitBtn').prop('disabled', false);
                    $('#calculation-summary').removeClass('text-danger').addClass('text-success');
                } else {
                    $('#submitBtn').prop('disabled', true);
                    $('#calculation-summary').removeClass('text-success').addClass('text-danger');
                }
            } else {
                $('#submitBtn').prop('disabled', true);
            }
        } else {
            $('#submitBtn').prop('disabled', true);
        }
    }

    // Event listeners
    $('#start_date, #end_date, #vacation_type').on('change', updateCalculation);

    // Validación de fechas mínimas
    $('#start_date').on('change', function() {
        const startDate = $(this).val();
        if (startDate) {
            const minEndDate = new Date(startDate);
            minEndDate.setDate(minEndDate.getDate() + 1);
            $('#end_date').attr('min', minEndDate.toISOString().split('T')[0]);
        }
    });

    // Validación antes de enviar
    $('#vacationForm').on('submit', function(e) {
        const businessDays = parseInt($('#business-days').text());

        if (businessDays > currentBalance) {
            e.preventDefault();
            alert('Los días solicitados exceden el balance disponible.');
            return false;
        }

        if (businessDays < 1) {
            e.preventDefault();
            alert('Debe solicitar al menos 1 día hábil.');
            return false;
        }

        return confirm('¿Está seguro de enviar esta solicitud de vacaciones?');
    });
});
</script>