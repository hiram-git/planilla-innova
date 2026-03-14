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
                        <h5><i class="fas fa-calendar-alt mr-2"></i>Balance Año <span id="balance-year-display"><?= $vacation_data['current_year'] ?></span></h5>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-calendar-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Días Anuales</span>
                                <span class="info-box-number" id="balance-dias-anuales"><?= $vacation_data['annual_balance']['dias_vacaciones_anuales'] ?? 30 ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Días Pagados</span>
                                <span class="info-box-number" id="balance-dias-pagados"><?= number_format($vacation_data['annual_balance']['dias_pagados_year'] ?? 0, 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-umbrella-beach"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Días Disfrutados</span>
                                <span class="info-box-number" id="balance-dias-disfrutados"><?= number_format($vacation_data['annual_balance']['dias_disfrutados_year'] ?? 0, 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fas fa-piggy-bank"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Saldo Disponible</span>
                                <span class="info-box-number" id="balance-saldo-disponible"><?= number_format($vacation_data['annual_balance']['saldo_disponible_year'] ?? 30, 1) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Totales Generales Acumulativos (Calculados desde vacation_annual_balances) -->
                <?php
                // Calcular totales sumando todos los años del historial
                $total_dias_pagados = 0;
                $total_dias_disfrutados = 0;
                $total_saldo_acumulado = 0;
                if (!empty($vacation_data['vacation_history'])) {
                    foreach ($vacation_data['vacation_history'] as $year_record) {
                        $total_dias_pagados += $year_record['dias_pagados_year'] ?? 0;
                        $total_dias_disfrutados += $year_record['dias_disfrutados_year'] ?? 0;
                        $total_saldo_acumulado += $year_record['saldo_disponible_year'] ?? 0;
                    }
                }
                ?>
                <div class="row">
                    <div class="col-12">
                        <h5><i class="fas fa-chart-line mr-2"></i>Totales Generales Acumulativos (Todos los Años)</h5>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-secondary">
                            <span class="info-box-icon"><i class="fas fa-coins"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Días Pagados (Todos los Años)</span>
                                <span class="info-box-number"><?= number_format($total_dias_pagados, 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-secondary">
                            <span class="info-box-icon"><i class="fas fa-calendar-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Días Disfrutados (Informativo)</span>
                                <span class="info-box-number"><?= number_format($total_dias_disfrutados, 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Saldo Total Acumulado Disponible</span>
                                <span class="info-box-number"><?= number_format($total_saldo_acumulado, 1) ?></span>
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
                    <!--
                    <div class="col-md-6">
                        <div class="callout callout-info">
                            <h5>Valor por Día de Vacaciones</h5>
                            <p><?= currency_symbol() ?><?= number_format($vacation_data['daily_salary'], 2) ?></p>
                        </div>
                    </div>
                    -->
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
            <form method="POST" action="<?= \App\Core\UrlHelper::route('panel/vacation/store') ?>" id="vacationForm" autocomplete="off">
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="date_range">Período de Vacaciones (rango) *</label>
                                <input type="text" class="form-control" id="date_range" placeholder="Seleccione rango de fechas" required autocomplete="off">
                                <small class="form-text text-muted">
                                    Se calcularán los días solicitados a partir del rango de fechas seleccionado.
                                </small>
                                <!-- Campos reales para el backend -->
                                <input type="hidden" id="start_date" name="start_date" autocomplete="off">
                                <input type="hidden" id="end_date" name="end_date" autocomplete="off">
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
                                       title="Días calculados automáticamente entre fecha inicio y fin" autocomplete="off">
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
                                               class="form-control" value="30" readonly autocomplete="off"
                                               title="Días de vacaciones correspondientes por año según legislación panameña">
                                        <small class="form-text text-muted">30 días por año trabajado</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="saldo_vacaciones">Saldo Vacaciones</label>
                                        <input type="number" name="saldo_vacaciones" id="saldo_vacaciones"
                                               class="form-control" value="<?= htmlspecialchars($vacation_data['current_balance']) ?>" readonly autocomplete="off"
                                               title="Saldo actual de días de vacaciones disponibles">
                                        <small class="form-text text-muted">Días acumulados disponibles</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="dias_solicitados_pagar">Días Solicitados por Pagar</label>
                                        <input type="number" name="dias_solicitados_pagar" id="dias_solicitados_pagar"
                                               class="form-control" min="0" step="0.1"
                                               placeholder="0" value="0" autocomplete="off">
                                        <small class="form-text text-muted">Editable manualmente - Se llena automáticamente al seleccionar fechas, pero puede cambiarse a 0 o cualquier otro valor</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="dias_vacaciones_disfrute">Días Vacaciones de Disfrute</label>
                                        <input type="number" name="dias_vacaciones_disfrute" id="dias_vacaciones_disfrute"
                                               class="form-control" value="<?= $vacation_data['annual_balance']['dias_vacaciones_anuales'] ?? 30 ?>" readonly autocomplete="off"
                                               title="Días de vacaciones anuales (siempre 30)">
                                        <small class="form-text text-muted">Días anuales (igual a Días Vacaciones Anuales)</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="saldo_dias_disfrute">Saldo Días de Disfrute</label>
                                        <?php
                                        $diasDisfrutados = $vacation_data['annual_balance']['dias_disfrutados_year'] ?? 0;
                                        $diasAnuales = $vacation_data['annual_balance']['dias_vacaciones_anuales'] ?? 30;
                                        $saldoDisfrute = $diasAnuales - $diasDisfrutados;
                                        ?>
                                        <input type="number" name="saldo_dias_disfrute" id="saldo_dias_disfrute"
                                               class="form-control" value="<?= $saldoDisfrute ?>" readonly autocomplete="off"
                                               title="Saldo disponible para disfrute">
                                        <small class="form-text text-muted">Días anuales - Días disfrutados del año</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="dias_solicitados_disfrute">Días Solicitados de Disfrute</label>
                                        <input type="number" name="dias_solicitados_disfrute" id="dias_solicitados_disfrute"
                                               class="form-control" min="0" max="<?= $saldoDisfrute ?>"
                                               placeholder="0" value="0" autocomplete="off">
                                        <small class="form-text text-muted">Editable - Coloque 0 si no tomará vacaciones (máx: <?= $saldoDisfrute ?>)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Resumen de Cálculo</label>
                                        <div class="bg-light p-3 rounded">
                                            <div id="calculation-summary">
                                                <p class="mb-1"><strong>Días del Rango:</strong> <span id="total-days">0</span></p>
                                                <p class="mb-1"><strong>Días por Pagar:</strong> <span id="summary-dias-pagar">0</span></p>
                                                <p class="mb-0"><strong>Días de Disfrute:</strong> <span id="summary-dias-disfrute">0</span></p>
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
                                  placeholder="Indique cualquier observación adicional sobre la solicitud..." autocomplete="off"></textarea>
                    </div>

                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Información Legal:</h5>
                        <ul class="mb-0">
                            <li>Según la legislación panameña, tiene derecho a 30 días de vacaciones por cada 11 meses trabajados.</li>
                            <li>Las vacaciones deben ser disfrutadas en períodos mínimos de 15 días consecutivos.</li>
                            <li>Las vacaciones pueden ser compensadas monetariamente con autorización del empleado.</li>
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
                // Permitir seleccionar fechas desde el inicio del año seleccionado
                const selectedYear = parseInt($('#ano_vacaciones').val()) || currentYear;
                const minStart = moment(selectedYear + '-01-01');

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

                    // Calcular días del rango (inclusive)
                    const total = end.diff(start, 'days') + 1;
                    $('#dias_calculados_fechas').val(total);

                    // Por defecto: Asignar días solo a "disfrute" (caso más común)
                    // Dejar "por pagar" en 0 (el usuario puede editarlo manualmente)
                    $('#dias_solicitados_pagar').val(0);

                    // Asignar el total del rango a "Días de Disfrute"
                    // (el usuario puede editar manualmente si excede el saldo)
                    $('#dias_solicitados_disfrute').val(total);

                    updateCalculation();
                    updateValidations();
                });
            });
        });
    })();

    const currentBalance = <?= $vacation_data['current_balance'] ?? 0 ?>;
    const dailySalary = <?= $vacation_data['daily_salary'] ?? 0 ?>;
    const annualBalance = <?= $vacation_data['annual_balance']['saldo_disponible_year'] ?? 30 ?>;
    const currentYear = <?= $vacation_data['current_year'] ?? date('Y') ?>;
    const diasDisfrutadosYear = <?= $vacation_data['annual_balance']['dias_disfrutados_year'] ?? 0 ?>;
    const diasAnuales = <?= $vacation_data['annual_balance']['dias_vacaciones_anuales'] ?? 30 ?>;
    const saldoDisfrute = diasAnuales - diasDisfrutadosYear;

    // Calcular días del rango y actualizar resumen
    function updateCalculation() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);

            if (end >= start) {
                // Calcular días totales del rango
                const totalDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;

                // Actualizar campo de días calculados por fechas
                $('#dias_calculados_fechas').val(totalDays);
                $('#total-days').text(totalDays);
            } else {
                // Fechas inválidas
                $('#dias_calculados_fechas').val(0);
                $('#total-days').text(0);
            }
        } else {
            // Sin fechas
            $('#dias_calculados_fechas').val(0);
            $('#total-days').text(0);
        }

        // Actualizar resumen
        updateSummary();
        updateValidations();
    }

    // Actualizar resumen de cálculo
    function updateSummary() {
        const diasPagar = parseInt($('#dias_solicitados_pagar').val()) || 0;
        const diasDisfrute = parseInt($('#dias_solicitados_disfrute').val()) || 0;

        $('#summary-dias-pagar').text(diasPagar);
        $('#summary-dias-disfrute').text(diasDisfrute);
    }

    // Event listeners
    $('#start_date, #end_date, #vacation_type').on('change', function() {
        updateCalculation();
    });

    // Función para obtener balance anual según el año seleccionado
    function getAnnualBalanceForYear(selectedYear) {
        if (selectedYear == currentYear) {
            return annualBalance;
        }
        // Para otros años, asumir 30 días disponibles (se puede mejorar con AJAX)
        return 30;
    }

    // Función de validaciones independientes
    function updateValidations() {
        const diasPagar = parseInt($('#dias_solicitados_pagar').val()) || 0;
        const diasDisfrute = parseInt($('#dias_solicitados_disfrute').val()) || 0;
        const saldoVacaciones = parseInt($('#saldo_vacaciones').val()) || 0;
        const saldoDiasDisfrute = parseInt($('#saldo_dias_disfrute').val()) || 0;
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const hasPeriod = startDate && endDate;

        // Variables para validación
        let isValid = true;
        let errorMessages = [];

        // Limpiar clases de error previas
        $('#dias_solicitados_pagar, #dias_solicitados_disfrute').removeClass('is-invalid');

        // 1. Validar que días por pagar no excedan el SALDO DE VACACIONES
        if (diasPagar > saldoVacaciones) {
            $('#dias_solicitados_pagar').addClass('is-invalid');
            errorMessages.push(`Días por pagar (${diasPagar}) no pueden exceder el Saldo de Vacaciones disponible (${saldoVacaciones})`);
            isValid = false;
        }

        // 2. Validar que días de disfrute no excedan el SALDO DE DÍAS DE DISFRUTE
        const saldoMaximoDisfrute = diasAnuales - diasDisfrutadosYear;
        if (diasDisfrute > saldoMaximoDisfrute) {
            $('#dias_solicitados_disfrute').addClass('is-invalid');
            errorMessages.push(`Días de disfrute (${diasDisfrute}) no pueden exceder el Saldo de Días de Disfrute (${saldoMaximoDisfrute})`);
            isValid = false;
        }

        // 3. Validar que se haya seleccionado un período si hay días solicitados
        if (!hasPeriod && (diasPagar > 0 || diasDisfrute > 0)) {
            errorMessages.push('Debe seleccionar un período de vacaciones (rango de fechas)');
            isValid = false;
        }

        // Mostrar/ocultar mensajes de error
        let errorContainer = $('#vacation-errors');
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
                errorContainer = $('#vacation-errors'); // Actualizar referencia
            }

            $('#error-list').empty();
            errorMessages.forEach(function(message) {
                $('#error-list').append(`<li>${message}</li>`);
            });
            errorContainer.show();
        } else {
            if (errorContainer.length > 0) {
                errorContainer.hide();
            }
        }

        // Habilitar/deshabilitar botón de envío
        // Requiere: período válido, validaciones pasadas, y al menos días por pagar o disfrute > 0
        if (isValid && hasPeriod && (diasPagar > 0 || diasDisfrute > 0)) {
            $('#submitBtn').prop('disabled', false);
        } else {
            $('#submitBtn').prop('disabled', true);
        }

        // Actualizar saldo de días de disfrute dinámicamente
        const nuevoSaldoDisfrute = Math.max(0, diasAnuales - diasDisfrutadosYear - diasDisfrute);
        $('#saldo_dias_disfrute').val(nuevoSaldoDisfrute);
    }

    // Event listeners para los campos de días solicitados
    $('#dias_solicitados_pagar, #dias_solicitados_disfrute').on('input change', function() {
        updateSummary();
        updateValidations();
    });

    // Event listener específico para cambio de año - con AJAX para obtener balance real
    $('#ano_vacaciones').on('change', function() {
        const selectedYear = parseInt($(this).val());
        const employeeId = <?= $employee['id'] ?>;

        // Actualizar minDate del daterangepicker según el año seleccionado
        const newMinDate = moment(selectedYear + '-01-01');
        $('#date_range').data('daterangepicker').minDate = newMinDate;
        // Limpiar fechas seleccionadas anteriormente
        $('#date_range').val('');
        $('#start_date').val('');
        $('#end_date').val('');
        $('#dias_calculados_fechas').val(0);
        $('#dias_solicitados_pagar').val(0);
        $('#dias_solicitados_disfrute').val(0);

        // Mostrar indicador de carga
        const $yearSelect = $(this);
        $yearSelect.prop('disabled', true);

        // Construir URL usando el helper de PHP
        const baseUrl = '<?= \App\Core\UrlHelper::route("panel/vacation/annual-balance") ?>';
        const requestUrl = baseUrl + '/' + employeeId + '?year=' + selectedYear;

        // Hacer AJAX call para obtener balance del año seleccionado
        $.ajax({
            url: requestUrl,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;

                    // Actualizar la sección de Balance de Vacaciones (info-boxes superiores)
                    $('#balance-year-display').text(selectedYear);
                    $('#balance-dias-anuales').text(data.dias_vacaciones_anuales);
                    $('#balance-dias-pagados').text(parseFloat(data.dias_pagados_year).toFixed(1));
                    $('#balance-dias-disfrutados').text(parseFloat(data.dias_disfrutados_year).toFixed(1));
                    $('#balance-saldo-disponible').text(parseFloat(data.saldo_disponible_year).toFixed(1));

                    // Actualizar saldo de vacaciones con el saldo específico del año
                    $('#saldo_vacaciones').val(data.saldo_disponible_year);

                    // Actualizar días de vacaciones anuales (siempre 30)
                    $('#dias_vacaciones_anuales').val(data.dias_vacaciones_anuales);
                    $('#dias_vacaciones_disfrute').val(data.dias_vacaciones_anuales);

                    // Calcular saldo disponible para disfrute del año específico
                    const saldoDisfrute = data.dias_vacaciones_anuales - data.dias_disfrutados_year;
                    $('#saldo_dias_disfrute').val(saldoDisfrute);

                    // Actualizar los valores máximos de los campos
                    $('#dias_solicitados_pagar').attr('max', data.saldo_disponible_year);
                    $('#dias_solicitados_disfrute').attr('max', saldoDisfrute);

                    // Mostrar información del año seleccionado con datos reales
                    showYearInfo(selectedYear, data.saldo_disponible_year, data);

                    // Recalcular validaciones con los nuevos valores
                    updateValidations();

                    console.log('Balance actualizado para año ' + selectedYear + ':', data);
                } else {
                    console.error('Error en respuesta del servidor:', response);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'No se pudo obtener el balance del año seleccionado',
                        confirmButtonText: 'Entendido'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor para obtener el balance del año',
                    confirmButtonText: 'Entendido'
                });
            },
            complete: function() {
                // Re-habilitar el select
                $yearSelect.prop('disabled', false);
            }
        });
    });

    // Función para mostrar información del año seleccionado
    function showYearInfo(year, balance, data) {
        // Crear o actualizar info box del año seleccionado
        let infoBox = $('#year-info-box');
        if (infoBox.length === 0) {
            const infoHtml = `
                <div id="year-info-box" class="alert alert-info mt-2">
                    <h6><i class="fas fa-info-circle mr-2"></i>Información del Año Seleccionado</h6>
                    <div id="year-info-content"></div>
                </div>
            `;
            $('#ano_vacaciones').closest('.form-group').append(infoHtml);
            infoBox = $('#year-info-box');
        }

        // Mostrar información detallada si tenemos los datos
        let infoHTML = `<strong>Año ${year}:</strong> ${balance} días disponibles`;

        if (data) {
            infoHTML += `<br>
                <div class="row mt-2">
                    <div class="col-6"><small><strong>Días Anuales:</strong> ${data.dias_vacaciones_anuales}</small></div>
                    <div class="col-6"><small><strong>Días Pagados:</strong> ${data.dias_pagados_year}</small></div>
                    <div class="col-6"><small><strong>Días Disfrutados:</strong> ${data.dias_disfrutados_year}</small></div>
                    <div class="col-6"><small><strong>Saldo Total Acumulado:</strong> ${data.total_accumulated_balance}</small></div>
                </div>
            `;
        } else {
            infoHTML += `<br><small>Cada año laboral otorga 30 días de vacaciones según legislación panameña</small>`;
        }

        $('#year-info-content').html(infoHTML);
    }

    // Inicializar valores al cargar la página
    $('#dias_vacaciones_disfrute').val(diasAnuales);
    $('#saldo_dias_disfrute').val(saldoDisfrute);

    // Validación inicial al cargar la página
    updateValidations();

    // Mostrar información inicial del año actual (sin datos detallados)
    showYearInfo(currentYear, annualBalance, null);

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
        const diasPagar = parseInt($('#dias_solicitados_pagar').val()) || 0;
        const diasDisfrute = parseInt($('#dias_solicitados_disfrute').val()) || 0;
        const saldoVacaciones = parseInt($('#saldo_vacaciones').val()) || 0;
        const diasVacacionesAnuales = parseInt($('#dias_vacaciones_anuales').val()) || 30;

        // Ejecutar validación final
        updateValidations();

        // Validar que se haya seleccionado un periodo de vacaciones
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        if (!startDate || !endDate) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Periodo de Vacaciones Requerido',
                text: 'Debe seleccionar un rango de fechas para el periodo de vacaciones.',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        // Validación: Al menos debe haber días por pagar O días de disfrute
        if (diasPagar === 0 && diasDisfrute === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Debe solicitar al menos un día de vacaciones (por pagar o por disfrute).',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        // Validar que días por pagar no excedan el saldo de vacaciones
        if (diasPagar > saldoVacaciones) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Saldo Insuficiente',
                html: `Días por pagar (<strong>${diasPagar}</strong>) no pueden exceder el Saldo de Vacaciones disponible (<strong>${saldoVacaciones}</strong>).`,
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        // COMENTADO: Validaciones de balance - permitir sin validar saldo
        /*
        if (totalDiasSolicitados > currentBalance) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Balance Insuficiente',
                text: `Total de días solicitados (${totalDiasSolicitados}) excede el balance disponible (${currentBalance}).`,
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        if (totalDiasSolicitados > diasVacacionesAnuales) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Límite Excedido',
                text: `Total de días solicitados (${totalDiasSolicitados}) excede los días anuales de vacaciones (${diasVacacionesAnuales}).`,
                confirmButtonText: 'Entendido'
            });
            return false;
        }
        */

        // COMENTADO: Validación días por pagar - permitir editar sin validar límite anual
        /*
        if (diasPagar > diasVacacionesAnuales) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Límite Excedido',
                text: `Días por pagar (${diasPagar}) no pueden exceder los días anuales de vacaciones (${diasVacacionesAnuales}).`,
                confirmButtonText: 'Entendido'
            });
            return false;
        }
        */

        if (diasDisfrute > diasVacacionesAnuales) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Límite Excedido',
                text: `Días de disfrute (${diasDisfrute}) no pueden exceder los días anuales de vacaciones (${diasVacacionesAnuales}).`,
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        // Validar saldo disponible para disfrute (días anuales - días disfrutados)
        const saldoDisponibleDisfrute = diasAnuales - diasDisfrutadosYear;
        if (diasDisfrute > saldoDisponibleDisfrute) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Saldo Insuficiente',
                text: `Días de disfrute solicitados (${diasDisfrute}) exceden el saldo disponible para disfrute (${saldoDisponibleDisfrute}).`,
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        // Validación de campos requeridos adicionales
        const anoVacaciones = $('#ano_vacaciones').val();

        if (!anoVacaciones) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Campo Requerido',
                text: 'Debe seleccionar el año de vacaciones.',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        if (diasCalculadosFechas === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Fechas Requeridas',
                text: 'Debe especificar fechas de inicio y fin para calcular los días.',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        // COMENTADO: Validación de anticipación de 15 días - Se permite para solicitudes históricas
        /*
        const startDate = $('#start_date').val();
        if (startDate) {
            const start = new Date(startDate);
            const today = new Date();
            const daysDifference = Math.ceil((start - today) / (1000 * 60 * 60 * 24));

            if (daysDifference < 15) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Anticipación Insuficiente',
                    html: `Las vacaciones deben solicitarse con al menos 15 días de anticipación.<br><small>Días de anticipación actual: ${daysDifference} días.</small>`,
                    confirmButtonText: 'Entendido'
                });
                return false;
            }
        }
        */

        // Nota: No se valida días hábiles, solo días totales del rango

        // Mensaje de confirmación personalizado según el tipo de solicitud
        e.preventDefault();
        let confirmHTML = '<div class="text-left"><ul class="mb-0">';
        if (diasPagar > 0 && diasDisfrute > 0) {
            confirmHTML += `<li><strong>${diasPagar}</strong> días por pagar (compensación monetaria)</li>`;
            confirmHTML += `<li><strong>${diasDisfrute}</strong> días de disfrute</li>`;
        } else if (diasPagar > 0) {
            confirmHTML += `<li><strong>${diasPagar}</strong> días por pagar (compensación monetaria únicamente)</li>`;
        } else if (diasDisfrute > 0) {
            confirmHTML += `<li><strong>${diasDisfrute}</strong> días de disfrute únicamente</li>`;
        }
        confirmHTML += '</ul></div>';

        Swal.fire({
            icon: 'question',
            title: '¿Confirmar Solicitud?',
            html: confirmHTML,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-paper-plane mr-1"></i> Enviar Solicitud',
            cancelButtonText: '<i class="fas fa-times mr-1"></i> Cancelar',
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#vacationForm')[0].submit();
            }
        });

        return false;
    });
    }); // Fin de $(document).ready
})(); // Fin de waitForjQuery

</script>
