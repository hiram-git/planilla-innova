<?php
$pageTitle = "Balance de Vacaciones - " . htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']);
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-chart-line mr-2"></i>
                    Balance de Vacaciones
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/vacation') ?>">Vacaciones</a></li>
                    <li class="breadcrumb-item active">Balance</li>
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
                    <?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
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
                                $diff = date_diff(date_create($employee['fecha_ingreso']), date_create());
                                echo "<strong>{$diff->y} años, {$diff->m} meses, {$diff->d} días</strong>";
                                ?>
                            </dd>

                            <dt class="col-sm-4">Estado:</dt>
                            <dd class="col-sm-8">
                                <span class="badge badge-<?= $vacation_data['eligible'] ? 'success' : 'warning' ?>">
                                    <?= $vacation_data['eligible'] ? 'ELEGIBLE' : 'NO ELEGIBLE' ?>
                                </span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance Actual -->
        <div class="row">
            <div class="col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Balance Disponible</span>
                        <span class="info-box-number"><?= number_format($vacation_data['current_balance'], 1) ?></span>
                        <span class="info-box-more">días para usar</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-gift"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Días Ganados</span>
                        <span class="info-box-number"><?= number_format($vacation_data['days_earned'], 1) ?></span>
                        <span class="info-box-more">total acumulado</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-minus-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Días Tomados</span>
                        <span class="info-box-number"><?= number_format($vacation_data['days_taken'], 1) ?></span>
                        <span class="info-box-more">días pagados</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fas fa-money-bill"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Valor Diario</span>
                        <span class="info-box-number"><?= currency_symbol() ?><?= number_format($vacation_data['daily_salary'], 0) ?></span>
                        <span class="info-box-more">por día de vacaciones</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de Días de Disfrute (Informativo) -->
        <div class="row">
            <div class="col-md-6">
                <div class="info-box bg-purple">
                    <span class="info-box-icon"><i class="fas fa-umbrella-beach"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Días Disfrute Tomados</span>
                        <span class="info-box-number"><?= number_format($vacation_data['days_enjoyed'], 1) ?></span>
                        <span class="info-box-more">informativo (no afecta balance)</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-box bg-secondary">
                    <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Días Disfrute Pendientes</span>
                        <span class="info-box-number"><?= number_format($vacation_data['days_enjoyed_pending'], 1) ?></span>
                        <span class="info-box-more">informativo (no afecta balance)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Explicación del Cálculo -->
        <div class="row">
            <div class="col-12">
                <div class="callout callout-info">
                    <h5><i class="fas fa-calculator mr-2"></i>Cálculo del Balance</h5>
                    <p class="mb-0">
                        <strong>Balance Disponible</strong> = Días Ganados - Días Tomados (Pagados)
                        = <strong><?= number_format($vacation_data['days_earned'], 1) ?></strong>
                        - <strong><?= number_format($vacation_data['days_taken'], 1) ?></strong>
                        = <strong class="text-success"><?= number_format($vacation_data['current_balance'], 1) ?> días</strong>
                    </p>
                    <p class="text-muted mb-0 mt-1">
                        <small><i class="fas fa-info-circle"></i> Los días disfrutados son informativos. Solo los días PAGADOS restan del balance.</small>
                    </p>
                </div>
            </div>
        </div>

        <!-- Valor Total de Vacaciones Acumuladas -->
        <div class="row">
            <div class="col-12">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calculator mr-2"></i>
                            Valor Monetario de Vacaciones Acumuladas
                        </h3>
                    </div>
                    <div class="card-body text-center">
                        <h2 class="text-success">
                            <?= currency_symbol() ?><?= number_format($vacation_data['current_balance'] * $vacation_data['daily_salary'], 2) ?>
                        </h2>
                        <p class="text-muted">
                            <?= number_format($vacation_data['current_balance'], 1) ?> días × <?= currency_symbol() ?><?= number_format($vacation_data['daily_salary'], 2) ?> por día
                        </p>
                        <p class="text-muted mb-0">
                            <small><i class="fas fa-info-circle"></i> <strong>Salario diario calculado</strong> con base en el promedio de SALARIO_BASE de los últimos 11 meses: <strong>ACUMULADOS("SALARIO_BASE") ÷ 11 ÷ 30</strong></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance Anual -->
        <?php if (!empty($annual_balances)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Balance por Año
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" id="btnGenerateMissingYears">
                            <i class="fas fa-magic mr-1"></i> Generar Años Faltantes
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Año</th>
                                    <th>Días Anuales</th>
                                    <th>Días Pagados</th>
                                    <th>Días Disfrutados (Info)</th>
                                    <th>Saldo Disponible</th>
                                    <th>Solicitudes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($annual_balances as $balance): ?>
                                    <tr>
                                        <td><strong><?= $balance['year'] ?></strong></td>
                                        <td>
                                            <span class="badge badge-success"><?= number_format($balance['dias_vacaciones_anuales'] ?? 30, 1) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning"><?= number_format($balance['dias_pagados_year'] ?? 0, 1) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?= number_format($balance['dias_disfrutados_year'] ?? 0, 1) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= ($balance['saldo_disponible_year'] ?? 0) > 0 ? 'primary' : 'secondary' ?>">
                                                <?= number_format($balance['saldo_disponible_year'] ?? 0, 1) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light"><?= $balance['solicitudes_count'] ?? 0 ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Historial de Solicitudes -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-2"></i>
                    Historial de Solicitudes de Vacaciones
                </h3>
                <div class="card-tools">
                    <?php if ($vacation_data['eligible'] && $vacation_data['current_balance'] > 0): ?>
                        <a href="<?= \App\Core\UrlHelper::route('panel/vacation/create/' . $employee['id']) ?>"
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> Nueva Solicitud
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($vacation_history)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha Solicitud</th>
                                    <th>Período Vacaciones</th>
                                    <th>Días</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Monto Compensación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vacation_history as $request): 
                                    ?>
                                    <tr>
                                        <td><strong>#<?= $request['id'] ?></strong></td>
                                        <td><?= date('d/m/Y', strtotime($request['request_date'])) ?></td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($request['start_date'])) ?>
                                            al
                                            <?= date('d/m/Y', strtotime($request['end_date'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?= $request['dias_solicitados_pagar'] ?? 0 ?> solicitados</span>
                                            <br><small class="text-muted"><?= $request['total_days'] ?> totales</small>
                                        </td>
                                        <td>
                                            <?php
                                            $type_labels = [
                                                'ANNUAL' => '<span class="badge badge-primary">Anuales</span>',
                                                'ACCUMULATED' => '<span class="badge badge-secondary">Acumuladas</span>',
                                                'COMPENSATION' => '<span class="badge badge-success">Compensación</span>',
                                                'PROPORTIONAL' => '<span class="badge badge-info">Proporcionales</span>'
                                            ];
                                            echo $type_labels[$request['vacation_type']] ?? '<span class="badge badge-light">N/A</span>';
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
                                        <td>
                                            <?php if ($request['compensation_amount'] > 0): ?>
                                                <strong><?= currency_symbol() ?><?= number_format($request['compensation_amount'], 2) ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= \App\Core\UrlHelper::route('panel/vacation/show/' . $request['id']) ?>"
                                               class="btn btn-sm btn-info" title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($request['status'] === 'APPROVED' && $request['compensation_amount'] > 0): ?>
                                                <?php if (empty($request['payroll_id'])): ?>
                                                    <!-- Solo mostrar botón si no existe planilla generada -->
                                                    <button type="button"
                                                            class="btn btn-sm btn-success btn-generate-payroll"
                                                            data-request-id="<?= $request['id'] ?>"
                                                            data-employee-name="<?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) ?>"
                                                            data-compensation="<?= number_format($request['compensation_amount'], 2) ?>"
                                                            data-dias-solicitados="<?= $request['dias_solicitados_pagar'] ?? 0 ?>"
                                                            title="Generar Planilla de Vacaciones">
                                                        <i class="fas fa-file-invoice-dollar"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <!-- Mostrar enlace a planilla existente -->
                                                    <a href="<?= \App\Core\UrlHelper::route('panel/payrolls/' . $request['payroll_id']) ?>"
                                                       class="btn btn-sm btn-secondary"
                                                       title="Ver Planilla Generada #<?= $request['payroll_id'] ?>">
                                                        <i class="fas fa-file-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay solicitudes de vacaciones registradas</h5>
                        <?php if ($vacation_data['eligible'] && $vacation_data['current_balance'] > 0): ?>
                            <p class="text-muted">Este empleado puede solicitar hasta <?= number_format($vacation_data['current_balance'], 1) ?> días de vacaciones.</p>
                            <a href="<?= \App\Core\UrlHelper::route('panel/vacation/create/' . $employee['id']) ?>"
                               class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i> Crear Primera Solicitud
                            </a>
                        <?php else: ?>
                            <p class="text-muted">
                                <?php if (!$vacation_data['eligible']): ?>
                                    El empleado aún no cumple con los 11 meses mínimos requeridos.
                                <?php else: ?>
                                    El empleado no tiene días de vacaciones disponibles.
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Marco Legal -->
        <div class="card card-light">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-gavel mr-2"></i>
                    Marco Legal - Legislación Panameña
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Derechos del Empleado:</h6>
                        <ul>
                            <li>30 días de vacaciones por cada 11 meses trabajados</li>
                            <li>Derecho a acumulación de vacaciones no disfrutadas</li>
                            <li>Compensación monetaria con autorización</li>
                            <li>Período mínimo de 15 días consecutivos</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Cálculo del Valor:</h6>
                        <ul>
                            <li>Base: Salario ordinario + gastos de representación</li>
                            <li>Fórmula: (Salario mensual ÷ 30) × días de vacaciones</li>
                            <li>Solo días hábiles (excluyendo fines de semana y feriados)</li>
                            <li>Proporcional si no completó 11 meses</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Navegación -->
        <div class="row">
            <div class="col-12">
                <div class="card-footer">
                    <a href="<?= \App\Core\UrlHelper::route('panel/vacation') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Volver a Lista
                    </a>

                    <a href="<?= \App\Core\UrlHelper::route('panel/employees/show/' . $employee['id']) ?>" class="btn btn-info">
                        <i class="fas fa-user mr-1"></i> Ver Empleado
                    </a>

                    <?php if ($vacation_data['eligible'] && $vacation_data['current_balance'] > 0): ?>
                        <a href="<?= \App\Core\UrlHelper::route('panel/vacation/create/' . $employee['id']) ?>" class="btn btn-primary">
                            <i class="fas fa-plus mr-1"></i> Nueva Solicitud
                        </a>
                    <?php endif; ?>

                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print mr-1"></i> Imprimir Balance
                    </button>
                </div>
            </div>
        </div>

    </div>
</section>

<style>
    /* Color púrpura personalizado para info-box */
    .info-box.bg-purple {
        background-color: #6f42c1 !important;
        color: #fff !important;
    }
    .info-box.bg-purple .info-box-icon {
        background-color: rgba(0, 0, 0, 0.1);
    }
    .info-box.bg-purple .info-box-text,
    .info-box.bg-purple .info-box-number,
    .info-box.bg-purple .info-box-more {
        color: #fff !important;
    }
</style>

<style media="print">
    .card-tools, .btn, .breadcrumb, .card-footer {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
</style>

<script>
// Esperar a que jQuery esté disponible antes de usar "$"
(function() {
    function waitForjQuery() {
        if (typeof window.jQuery !== 'undefined') {
            jQuery(function($) {
                // Generar años faltantes de vacaciones
                $('#btnGenerateMissingYears').on('click', function() {
                    const employeeId = <?= $employee['id'] ?>;

                    Swal.fire({
            title: '¿Generar Años Faltantes?',
            html: `
                <p>Esta acción detectará y creará automáticamente los años de vacaciones faltantes para el empleado <strong><?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) ?></strong>.</p>
                <div class="text-left mt-3">
                    <h6>¿Qué hace esta función?</h6>
                    <ul>
                        <li>Calcula desde qué año tiene derecho a vacaciones (11 meses después del ingreso)</li>
                        <li>Detecta qué años faltan hasta el año actual</li>
                        <li>Crea registros con 30 días anuales y saldo disponible = 30</li>
                        <li>No modifica años que ya existen</li>
                    </ul>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Fecha de ingreso: <?= date('d/m/Y', strtotime($employee['fecha_ingreso'])) ?>
                    </small>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-magic mr-1"></i> Generar Años',
            cancelButtonText: '<i class="fas fa-times mr-1"></i> Cancelar',
            showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: `<?= \App\Core\UrlHelper::route('panel/vacation/generate-missing-years/') ?>${employeeId}`,
                            method: 'POST',
                            dataType: 'json',
                            data: {
                                csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                            }
                        }).fail(function(xhr) {
                            Swal.showValidationMessage(
                                `Error: ${xhr.responseJSON?.message || xhr.statusText || 'No se pudo conectar con el servidor'}`
                            );
                        });
                    },
            allowOutsideClick: () => !Swal.isLoading()
                    }).then((result) => {
                        if (result.isConfirmed && result.value) {
                            const response = result.value;

                            if (response.success) {
                                let detailsHTML = `
                                    <div class="text-left">
                                        <p><strong>${response.message}</strong></p>
                                `;

                                if (response.count > 0) {
                                    detailsHTML += `
                                        <table class="table table-sm mt-2">
                                            <tr><th>Años Creados:</th><td class="text-success">${response.count}</td></tr>
                                            <tr><th>Años:</th><td>${response.years_created.join(', ')}</td></tr>
                                            <tr><th>Rango:</th><td>${response.first_year} - ${response.current_year}</td></tr>
                                        </table>
                                    `;
                                } else {
                                    detailsHTML += `
                                        <div class="alert alert-info mt-2">
                                            <i class="fas fa-check-circle mr-1"></i> ${response.message}
                                        </div>
                                    `;
                                }

                                detailsHTML += '</div>';

                                Swal.fire({
                                    title: response.count > 0 ? 'Años Generados Exitosamente' : 'Información',
                                    html: detailsHTML,
                                    icon: response.count > 0 ? 'success' : 'info',
                                    confirmButtonText: response.count > 0 ? 'Recargar Página' : 'Entendido'
                                }).then(() => {
                                    if (response.count > 0) {
                                        window.location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message || 'Error desconocido',
                                    icon: 'error',
                                    confirmButtonText: 'Entendido'
                                });
                            }
                        }
                    });
                });

                // Generar planilla de vacaciones desde solicitud aprobada
                $('.btn-generate-payroll').on('click', function() {
                    const requestId = $(this).data('request-id');
                    const employeeName = $(this).data('employee-name');
                    const compensation = $(this).data('compensation');
                    const diasSolicitados = $(this).data('dias-solicitados');

                    // Obtener tipo_planilla_id desde sessionStorage (guardado por navbar)
                    const selectedPayrollType = sessionStorage.getItem('selectedPayrollType');

                    if (!selectedPayrollType) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Tipo de Planilla Requerido',
                            html: 'Debe seleccionar un tipo de planilla desde el menú de navegación antes de generar la planilla de vacaciones.',
                            confirmButtonText: 'Entendido'
                        });
                        return;
                    }

                    // Parsear el JSON para obtener el ID
                    const payrollTypeData = JSON.parse(selectedPayrollType);
                    const tipoPlanillaId = payrollTypeData.id;

                    Swal.fire({
                        title: '¿Generar Planilla de Vacaciones?',
                        html: `
                            <div class="text-left">
                                <p>Se creará una planilla de vacaciones con los siguientes datos:</p>
                                <table class="table table-sm mt-2">
                                    <tr><th>Empleado:</th><td>${employeeName}</td></tr>
                                    <tr><th>Solicitud:</th><td>#${requestId}</td></tr>
                                    <tr><th>Días Solicitados:</th><td>${diasSolicitados}</td></tr>
                                    <tr><th>Monto Compensación:</th><td><?= currency_symbol() ?>${compensation}</td></tr>
                                    <tr><th>Frecuencia:</th><td>Vacaciones</td></tr>
                                </table>
                                <div class="alert alert-info mt-2">
                                    <i class="fas fa-info-circle mr-1"></i> La planilla se generará con el concepto "VACACIONES" y se agregará el detalle para este empleado.
                                </div>
                            </div>
                        `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-file-invoice-dollar mr-1"></i> Generar Planilla',
                        cancelButtonText: '<i class="fas fa-times mr-1"></i> Cancelar',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return $.ajax({
                                url: `<?= \App\Core\UrlHelper::route('panel/vacation/generate-payroll/') ?>${requestId}`,
                                method: 'POST',
                                dataType: 'json',
                                data: {
                                    csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>',
                                    tipo_planilla_id: tipoPlanillaId
                                }
                            }).fail(function(xhr) {
                                Swal.showValidationMessage(
                                    `Error: ${xhr.responseJSON?.message || xhr.statusText || 'No se pudo conectar con el servidor'}`
                                );
                            });
                        },
                        allowOutsideClick: () => !Swal.isLoading()
                    }).then((result) => {
                        if (result.isConfirmed && result.value) {
                            const response = result.value;

                            if (response.success) {
                                Swal.fire({
                                    title: 'Planilla Generada',
                                    html: `
                                        <div class="text-left">
                                            <p><strong>${response.message}</strong></p>
                                            <div class="alert alert-success mt-2">
                                                <i class="fas fa-check-circle mr-1"></i> Planilla ID: #${response.payroll_id}
                                            </div>
                                        </div>
                                    `,
                                    icon: 'success',
                                    confirmButtonText: 'Ver Planilla'
                                }).then(() => {
                                    // Redirigir a la planilla generada
                                    window.location.href = `<?= \App\Core\UrlHelper::route('panel/payrolls/') ?>${response.payroll_id}`;
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message || 'Error desconocido al generar planilla',
                                    icon: 'error',
                                    confirmButtonText: 'Entendido'
                                });
                            }
                        }
                    });
                });
            });
        } else {
            setTimeout(waitForjQuery, 100);
        }
    }
    waitForjQuery();
})();
</script>
