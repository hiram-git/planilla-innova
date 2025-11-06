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
                            <dd class="col-sm-8"><?= htmlspecialchars($employee['cargo_nombre'] ?? 'N/A') ?></dd>
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
                <!-- Balance Año Actual -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5><i class="fas fa-calendar-alt mr-2"></i>Balance Año <?= $vacation_data['current_year'] ?></h5>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-calendar-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Días Anuales</span>
                                <span class="info-box-number"><?= $vacation_data['annual_balance']['dias_vacaciones_anuales'] ?? 30 ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Días Pagados</span>
                                <span class="info-box-number"><?= number_format($vacation_data['annual_balance']['dias_pagados_year'] ?? 0, 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-umbrella-beach"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Días Disfrutados</span>
                                <span class="info-box-number"><?= number_format($vacation_data['annual_balance']['dias_disfrutados_year'] ?? 0, 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fas fa-piggy-bank"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Saldo Disponible</span>
                                <span class="info-box-number"><?= number_format($vacation_data['annual_balance']['saldo_disponible_year'] ?? 30, 1) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Totales Generales Acumulativos -->
                <div class="row">
                    <div class="col-12">
                        <h5><i class="fas fa-chart-line mr-2"></i>Totales Generales Acumulativos</h5>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-secondary">
                            <span class="info-box-icon"><i class="fas fa-coins"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Días Pagados</span>
                                <span class="info-box-number"><?= number_format($vacation_data['general_totals']['total_dias_pagados'] ?? 0, 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-secondary">
                            <span class="info-box-icon"><i class="fas fa-calendar-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Días Disfrutados</span>
                                <span class="info-box-number"><?= number_format($vacation_data['general_totals']['total_dias_disfrutados'] ?? 0, 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-secondary">
                            <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Saldo Total Acumulado</span>
                                <span class="info-box-number"><?= number_format($vacation_data['general_totals']['total_saldo_acumulado'] ?? 0, 1) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (($vacation_data['annual_balance']['saldo_disponible_year'] ?? 30) <= 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Atención:</strong> Este empleado no tiene días de vacaciones disponibles para el año <?= $vacation_data['current_year'] ?>.
                    </div>
                <?php endif; ?>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="callout callout-info">
                            <h5>Valor por Día de Vacaciones</h5>
                            <p><?= currency_symbol() ?><?= number_format($vacation_data['daily_salary'], 2) ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="callout callout-success">
                            <h5>Acumulación Mensual</h5>
                            <p><?= number_format($vacation_data['accrual_rate'], 1) ?> días</p>
                        </div>
                    </div>
                </div>
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
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="date_range">Período de Vacaciones (rango) *</label>
                                <input type="text" class="form-control" id="date_range" placeholder="Seleccione rango de fechas" required>
                                <small class="form-text text-muted">
                                    Las vacaciones deben solicitarse con al menos 15 días de anticipación. Se calcularán los días solicitados a partir del rango.
                                </small>
                                <!-- Campos reales para el backend -->
                                <input type="hidden" id="start_date" name="start_date">
                                <input type="hidden" id="end_date" name="end_date">
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
                                <label for="estado_solicitud">Estado</label>
                                <select name="estado_solicitud" id="estado_solicitud" class="form-control">
                                    <option value="PENDING" selected>Pendiente</option>
                                    <option value="APPROVED">Aprobada</option>
                                    <option value="REJECTED">Rechazada</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Año de Vacaciones -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ano_vacaciones">Año de Vacaciones *</label>
                                <select name="ano_vacaciones" id="ano_vacaciones" class="form-control" required>
                                    <?php
                                    $current_year = date('Y');
                                    $start_year = $current_year - 5;
                                    $end_year = $current_year + 2;
                                    for ($year = $end_year; $year >= $start_year; $year--):
                                    ?>
                                        <option value="<?= $year ?>" <?= $year == $current_year ? 'selected' : '' ?>>
                                            <?= $year ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <small class="form-text text-muted">Año al que corresponden las vacaciones</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dias_calculados_fechas">Días Calculados por Fechas</label>
                                <input type="number" name="dias_calculados_fechas" id="dias_calculados_fechas"
                                       class="form-control" readonly value="0"
                                       title="Días calculados automáticamente entre fecha inicio y fin">
                                <small class="form-text text-muted">Calculado automáticamente (fecha fin - fecha inicio + 1)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Nuevos campos de vacaciones solicitados -->
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-check mr-2"></i>
                                Detalle de Vacaciones
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="dias_vacaciones_anuales">Días de Vacaciones Anuales</label>
                                        <input type="number" name="dias_vacaciones_anuales" id="dias_vacaciones_anuales"
                                               class="form-control" value="30" readonly
                                               title="Días de vacaciones correspondientes por año según legislación panameña">
                                        <small class="form-text text-muted">30 días por año trabajado</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="saldo_vacaciones">Saldo Vacaciones</label>
                                        <input type="number" name="saldo_vacaciones" id="saldo_vacaciones"
                                               class="form-control" value="<?= htmlspecialchars($vacation_data['current_balance']) ?>" readonly
                                               title="Saldo actual de días de vacaciones disponibles">
                                        <small class="form-text text-muted">Días acumulados disponibles</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="dias_solicitados_pagar">Días Solicitados por Pagar</label>
                                        <input type="number" name="dias_solicitados_pagar" id="dias_solicitados_pagar"
                                               class="form-control" min="0" max="<?= htmlspecialchars($vacation_data['current_balance']) ?>"
                                               placeholder="0" value="0">
                                        <small class="form-text text-muted">Días a compensar monetariamente</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="dias_vacaciones_disfrute">Días Vacaciones de Disfrute</label>
                                        <input type="number" name="dias_vacaciones_disfrute" id="dias_vacaciones_disfrute"
                                               class="form-control" min="0" max="<?= htmlspecialchars($vacation_data['current_balance']) ?>"
                                               placeholder="0" value="0">
                                        <small class="form-text text-muted">Días para tomar como descanso</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="saldo_dias_disfrute">Saldo Días de Disfrute</label>
                                        <input type="number" name="saldo_dias_disfrute" id="saldo_dias_disfrute"
                                               class="form-control" value="<?= htmlspecialchars($vacation_data['current_balance']) ?>" readonly
                                               title="Saldo disponible para disfrute">
                                        <small class="form-text text-muted">Saldo disponible para disfrute</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="dias_solicitados_disfrute">Días Solicitados de Disfrute</label>
                                        <input type="number" name="dias_solicitados_disfrute" id="dias_solicitados_disfrute"
                                               class="form-control" min="0" max="<?= htmlspecialchars($vacation_data['current_balance']) ?>"
                                               placeholder="0" value="0">
                                        <small class="form-text text-muted">Días de disfrute solicitados</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="total_dias_solicitados">Total Días Solicitados</label>
                                        <input type="number" name="total_dias_solicitados" id="total_dias_solicitados"
                                               class="form-control" readonly title="Total de días solicitados (pago + disfrute)">
                                        <small class="form-text text-muted">Suma automática de días por pagar + disfrute</small>
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
// Verificar que jQuery esté disponible
(function waitForjQuery() {
    if (typeof $ === 'undefined') {
        setTimeout(waitForjQuery, 50);
        return;
    }

    // jQuery está disponible, inicializar
    $(document).ready(function() {
    console.log('jQuery cargado correctamente en vacation/create');

    // Cargar DateRangePicker por CDN y configurar
    (function initDateRangePicker() {
        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
        document.head.appendChild(css);

        function loadScript(src, onload) {
            const s = document.createElement('script');
            s.src = src; s.onload = onload; document.body.appendChild(s);
        }

        loadScript('https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js', function() {
            loadScript('https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', function() {
                const $input = $('#date_range');
                const minStart = moment().add(1, 'day');
                $input.daterangepicker({
                    locale: {
                        format: 'YYYY-MM-DD',
                        applyLabel: 'Aplicar',
                        cancelLabel: 'Cancelar',
                        daysOfWeek: ['Do','Lu','Ma','Mi','Ju','Vi','Sa'],
                        monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
                        firstDay: 1
                    },
                    autoUpdateInput: false,
                    minDate: minStart,
                });

                $input.on('apply.daterangepicker', function(ev, picker) {
                    const start = picker.startDate.clone().startOf('day');
                    const end = picker.endDate.clone().startOf('day');
                    $(this).val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
                    $('#start_date').val(start.format('YYYY-MM-DD')).trigger('change');
                    $('#end_date').val(end.format('YYYY-MM-DD')).trigger('change');

                    // Calcular días solicitados (inclusive)
                    const total = end.diff(start, 'days') + 1;
                    $('#total_dias_solicitados').val(total).trigger('input');

                    // Por defecto, igualar días de disfrute al total si el usuario no lo cambió manualmente
                    const $disfrute = $('#dias_solicitados_disfrute');
                    const prevDefault = $disfrute.data('prev-default');
                    const currentVal = parseFloat($disfrute.val() || '0');
                    if (!prevDefault || currentVal === prevDefault || currentVal === 0) {
                        $disfrute.val(total);
                        $disfrute.data('prev-default', total);
                    }

                    updateCalculation();
                    updateTotalDiasSolicitados();
                });
            });
        });
    })();

    const currentBalance = <?= $vacation_data['current_balance'] ?? 0 ?>;
    const dailySalary = <?= $vacation_data['daily_salary'] ?? 0 ?>;
    const annualBalance = <?= $vacation_data['annual_balance']['saldo_disponible_year'] ?? 30 ?>;
    const currentYear = <?= $vacation_data['current_year'] ?? date('Y') ?>;

    // Calcular días y actualizar resumen cuando cambien las fechas
    function updateCalculation() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const vacationType = $('#vacation_type').val();

        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);

            if (end >= start) {
                // Validar anticipación de 15 días
                const today = new Date();
                const daysDifference = Math.ceil((start - today) / (1000 * 60 * 60 * 24));

                // Limpiar alertas previas
                $('#anticipation-warning').remove();

                if (daysDifference < 15) {
                    // Mostrar advertencia de anticipación
                    const warningHtml = `
                        <div id="anticipation-warning" class="alert alert-warning mt-2">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Advertencia:</strong> Las vacaciones deben solicitarse con al menos 15 días de anticipación.
                            <br><small>Días de anticipación actual: ${daysDifference} días</small>
                        </div>
                    `;
                    $('#start_date').closest('.form-group').append(warningHtml);
                    $('#submitBtn').prop('disabled', true);
                } else {
                    // Anticipación válida, continuar con cálculos
                    $('#submitBtn').prop('disabled', false);
                }

                // Calcular días totales
                const totalDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;

                // Estimar días hábiles (aproximado - el servidor calculará exacto)
                const businessDays = Math.floor(totalDays * 5/7);

                // Actualizar campo de días calculados por fechas
                $('#dias_calculados_fechas').val(totalDays);

                // Auto-llenar días de disfrute si no hay días por pagar especificados
                const diasPagar = parseInt($('#dias_solicitados_pagar').val()) || 0;
                const diasDisfrute = parseInt($('#dias_solicitados_disfrute').val()) || 0;

                // Si no hay días de disfrute especificados y tenemos fechas, usar los días calculados
                if (diasDisfrute === 0 && totalDays > 0) {
                    $('#dias_solicitados_disfrute').val(totalDays);
                }

                // Actualizar interfaz
                $('#total-days').text(totalDays);
                $('#business-days').text(businessDays);
                $('#remaining-balance').text((currentBalance - totalDays).toFixed(1));

                // Recalcular totales de vacaciones
                updateTotalDiasSolicitados();

                // Calcular compensación si aplica
                const compensationAmount = diasPagar * dailySalary;
                $('#compensation-amount').text('<?= currency_symbol() ?>' + compensationAmount.toFixed(2));

            } else {
                // Fechas inválidas
                $('#dias_calculados_fechas').val(0);
                $('#total-days').text(0);
                $('#business-days').text(0);
                $('#submitBtn').prop('disabled', true);
            }
        } else {
            // Sin fechas
            $('#dias_calculados_fechas').val(0);
            $('#total-days').text(0);
            $('#business-days').text(0);
            $('#submitBtn').prop('disabled', true);
        }
    }

    // Event listeners
    $('#start_date, #end_date, #vacation_type').on('change', function() {
        updateCalculation();
        // Pequeño delay para asegurar que los valores se actualicen
        setTimeout(updateTotalDiasSolicitados, 100);
    });

    // Función para obtener balance anual según el año seleccionado
    function getAnnualBalanceForYear(selectedYear) {
        if (selectedYear == currentYear) {
            return annualBalance;
        }
        // Para otros años, asumir 30 días disponibles (se puede mejorar con AJAX)
        return 30;
    }

    // Función para actualizar el total de días solicitados
    function updateTotalDiasSolicitados() {
        const diasPagar = parseInt($('#dias_solicitados_pagar').val()) || 0;
        const diasDisfrute = parseInt($('#dias_solicitados_disfrute').val()) || 0;
        const diasVacacionesAnuales = parseInt($('#dias_vacaciones_anuales').val()) || 30;
        const selectedYear = parseInt($('#ano_vacaciones').val()) || currentYear;
        const yearBalance = getAnnualBalanceForYear(selectedYear);
        // Total de días solicitados: tomado del rango de fechas seleccionado
        const total = parseInt($('#dias_calculados_fechas').val()) || 0;

        $('#total_dias_solicitados').val(total);

        // Variables para validación
        let isValid = true;
        let errorMessages = [];

        // Limpiar clases de error previas
        $('#dias_solicitados_pagar, #dias_solicitados_disfrute, #total_dias_solicitados').removeClass('is-invalid');

        // 1. Validar que días por pagar no excedan días anuales de vacaciones (30)
        if (diasPagar > diasVacacionesAnuales) {
            $('#dias_solicitados_pagar').addClass('is-invalid');
            errorMessages.push(`Días por pagar (${diasPagar}) no pueden exceder los días anuales de vacaciones (${diasVacacionesAnuales})`);
            isValid = false;
        }

        // 2. Validar que días por pagar no excedan el saldo anual disponible
        if (diasPagar > yearBalance) {
            $('#dias_solicitados_pagar').addClass('is-invalid');
            errorMessages.push(`Días por pagar (${diasPagar}) no pueden exceder el saldo disponible del año ${selectedYear} (${yearBalance})`);
            isValid = false;
        }

        // 3. Calcular saldo disponible para disfrute (saldo anual - días por pagar)
        const saldoDisponibleDisfrute = Math.max(0, yearBalance - diasPagar);
        $('#saldo_dias_disfrute').val(saldoDisponibleDisfrute);

        // 4. Validar que días de disfrute no excedan días anuales de vacaciones (30)
        if (diasDisfrute > diasVacacionesAnuales) {
            $('#dias_solicitados_disfrute').addClass('is-invalid');
            errorMessages.push(`Días de disfrute (${diasDisfrute}) no pueden exceder los días anuales de vacaciones (${diasVacacionesAnuales})`);
            isValid = false;
        }

        // 5. Validar que días de disfrute no excedan el saldo disponible para disfrute
        if (diasDisfrute > saldoDisponibleDisfrute) {
            $('#dias_solicitados_disfrute').addClass('is-invalid');
            errorMessages.push(`Días de disfrute (${diasDisfrute}) no pueden exceder el saldo disponible para disfrute del año ${selectedYear} (${saldoDisponibleDisfrute})`);
            isValid = false;
        }

        // 6. Validar que el total no exceda el saldo anual disponible
        if (total > yearBalance) {
            $('#total_dias_solicitados').addClass('is-invalid');
            errorMessages.push(`Total de días solicitados (${total}) no puede exceder el saldo disponible del año ${selectedYear} (${yearBalance})`);
            isValid = false;
        }

        // 7. Validar que el total no exceda los días anuales de vacaciones
        if (total > diasVacacionesAnuales) {
            $('#total_dias_solicitados').addClass('is-invalid');
            errorMessages.push(`Total de días solicitados (${total}) no puede exceder los días anuales de vacaciones (${diasVacacionesAnuales})`);
            isValid = false;
        }

        // Mostrar/ocultar mensajes de error
        const errorContainer = $('#vacation-errors');
        if (errorMessages.length > 0) {
            if (errorContainer.length === 0) {
                // Crear contenedor de errores si no existe
                const errorHtml = `
                    <div id="vacation-errors" class="alert alert-danger" style="display: none;">
                        <h5><i class="icon fas fa-ban"></i> Errores de Validación:</h5>
                        <ul id="error-list"></ul>
                    </div>
                `;
                $('#calculation-summary').closest('.form-group').before(errorHtml);
            }

            $('#error-list').empty();
            errorMessages.forEach(function(message) {
                $('#error-list').append(`<li>${message}</li>`);
            });
            $('#vacation-errors').show();
        } else {
            errorContainer.hide();
        }

        // Habilitar/deshabilitar botón de envío
        if (isValid && total > 0) {
            $('#submitBtn').prop('disabled', false);
            $('#calculation-summary').removeClass('text-danger').addClass('text-success');
        } else {
            $('#submitBtn').prop('disabled', true);
            $('#calculation-summary').removeClass('text-success').addClass('text-danger');
        }

        // Actualizar compensación monetaria basada en días por pagar
        const compensationAmount = diasPagar * dailySalary;
        $('#compensation-amount').text('<?= currency_symbol() ?>' + compensationAmount.toFixed(2));
    }

    // Event listeners para los nuevos campos de vacaciones
    $('#dias_solicitados_pagar, #dias_solicitados_disfrute, #dias_vacaciones_anuales, #ano_vacaciones').on('input change', function() {
        updateTotalDiasSolicitados();
        updateCalculation();
    });

    // Event listener específico para cambio de año
    $('#ano_vacaciones').on('change', function() {
        const selectedYear = parseInt($(this).val());
        const yearBalance = getAnnualBalanceForYear(selectedYear);

        // Actualizar los valores máximos de los campos
        $('#dias_solicitados_pagar').attr('max', yearBalance);
        $('#dias_solicitados_disfrute').attr('max', yearBalance);

        // Mostrar información del año seleccionado
        showYearInfo(selectedYear, yearBalance);

        // Recalcular validaciones
        updateTotalDiasSolicitados();
    });

    // Función para mostrar información del año seleccionado
    function showYearInfo(year, balance) {
        // Crear o actualizar info box del año seleccionado
        let infoBox = $('#year-info-box');
        if (infoBox.length === 0) {
            const infoHtml = `
                <div id="year-info-box" class="alert alert-info mt-2">
                    <h6><i class="fas fa-info-circle mr-2"></i>Información del Año Seleccionado</h6>
                    <p id="year-info-content"></p>
                </div>
            `;
            $('#ano_vacaciones').closest('.form-group').append(infoHtml);
            infoBox = $('#year-info-box');
        }

        $('#year-info-content').html(`
            <strong>Año ${year}:</strong> ${balance} días disponibles<br>
            <small>Cada año laboral otorga 30 días de vacaciones según legislación panameña</small>
        `);
    }

    // Validación inicial al cargar la página
    updateTotalDiasSolicitados();

    // Mostrar información inicial del año actual
    showYearInfo(currentYear, annualBalance);

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
        const diasPagar = parseInt($('#dias_solicitados_pagar').val()) || 0;
        const diasDisfrute = parseInt($('#dias_solicitados_disfrute').val()) || 0;
        const totalDiasSolicitados = parseInt($('#total_dias_solicitados').val()) || 0;
        const diasVacacionesAnuales = parseInt($('#dias_vacaciones_anuales').val()) || 30;

        // Ejecutar validación final
        updateTotalDiasSolicitados();

        // Validaciones específicas para envío
        if (totalDiasSolicitados === 0) {
            e.preventDefault();
            alert('Debe solicitar al menos un día de vacaciones (por pagar o por disfrute).');
            return false;
        }

        if (totalDiasSolicitados > currentBalance) {
            e.preventDefault();
            alert(`Total de días solicitados (${totalDiasSolicitados}) excede el balance disponible (${currentBalance}).`);
            return false;
        }

        if (totalDiasSolicitados > diasVacacionesAnuales) {
            e.preventDefault();
            alert(`Total de días solicitados (${totalDiasSolicitados}) excede los días anuales de vacaciones (${diasVacacionesAnuales}).`);
            return false;
        }

        if (diasPagar > diasVacacionesAnuales) {
            e.preventDefault();
            alert(`Días por pagar (${diasPagar}) no pueden exceder los días anuales de vacaciones (${diasVacacionesAnuales}).`);
            return false;
        }

        if (diasDisfrute > diasVacacionesAnuales) {
            e.preventDefault();
            alert(`Días de disfrute (${diasDisfrute}) no pueden exceder los días anuales de vacaciones (${diasVacacionesAnuales}).`);
            return false;
        }

        // Validar saldo disponible para disfrute
        const saldoDisponibleDisfrute = currentBalance - diasPagar;
        if (diasDisfrute > saldoDisponibleDisfrute) {
            e.preventDefault();
            alert(`Días de disfrute (${diasDisfrute}) exceden el saldo disponible para disfrute (${saldoDisponibleDisfrute}).`);
            return false;
        }

        // Validación de campos requeridos adicionales
        const anoVacaciones = $('#ano_vacaciones').val();
        const diasCalculadosFechas = parseInt($('#dias_calculados_fechas').val()) || 0;

        if (!anoVacaciones) {
            e.preventDefault();
            alert('Debe seleccionar el año de vacaciones.');
            return false;
        }

        if (diasCalculadosFechas === 0) {
            e.preventDefault();
            alert('Debe especificar fechas de inicio y fin para calcular los días.');
            return false;
        }

        // Validación de anticipación de 15 días
        const startDate = $('#start_date').val();
        if (startDate) {
            const start = new Date(startDate);
            const today = new Date();
            const daysDifference = Math.ceil((start - today) / (1000 * 60 * 60 * 24));

            if (daysDifference < 15) {
                e.preventDefault();
                alert(`Las vacaciones deben solicitarse con al menos 15 días de anticipación.\nDías de anticipación actual: ${daysDifference} días.`);
                return false;
            }
        }

        // Validación de fechas solo si se solicitan días de disfrute
        if (diasDisfrute > 0) {
            if (businessDays < 1) {
                e.preventDefault();
                alert('Debe especificar al menos 1 día hábil en las fechas cuando solicita días de disfrute.');
                return false;
            }
        }

        // Mensaje de confirmación personalizado según el tipo de solicitud
        let confirmMessage = '¿Está seguro de enviar esta solicitud de vacaciones?\n\n';
        if (diasPagar > 0 && diasDisfrute > 0) {
            confirmMessage += `• ${diasPagar} días por pagar (compensación monetaria)\n`;
            confirmMessage += `• ${diasDisfrute} días de disfrute\n`;
            confirmMessage += `• Total: ${totalDiasSolicitados} días`;
        } else if (diasPagar > 0) {
            confirmMessage += `• ${diasPagar} días por pagar (compensación monetaria únicamente)`;
        } else if (diasDisfrute > 0) {
            confirmMessage += `• ${diasDisfrute} días de disfrute únicamente`;
        }

        return confirm(confirmMessage);
    });
    }); // Fin de $(document).ready
})(); // Fin de waitForjQuery

</script>
