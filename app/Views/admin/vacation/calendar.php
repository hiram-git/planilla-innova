<?php
$pageTitle = "Calendario de Vacaciones " . $year;
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Calendario de Vacaciones <?= $year ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/vacation') ?>">Vacaciones</a></li>
                    <li class="breadcrumb-item active">Calendario</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <!-- Controles del Calendario -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter mr-2"></i>
                    Controles y Filtros
                </h3>
                <div class="card-tools">
                    <div class="btn-group">
                        <a href="<?= \App\Core\UrlHelper::route('panel/vacation/calendar?year=' . ($year - 1)) ?>"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-chevron-left"></i> <?= $year - 1 ?>
                        </a>
                        <button class="btn btn-secondary btn-sm" disabled><?= $year ?></button>
                        <a href="<?= \App\Core\UrlHelper::route('panel/vacation/calendar?year=' . ($year + 1)) ?>"
                           class="btn btn-outline-secondary btn-sm">
                            <?= $year + 1 ?> <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-row">
                            <div class="col-md-4">
                                <label for="viewSelect">Vista:</label>
                                <select id="viewSelect" class="form-control form-control-sm">
                                    <option value="dayGridMonth">Mes</option>
                                    <option value="timeGridWeek">Semana</option>
                                    <option value="timeGridDay">Día</option>
                                    <option value="listMonth">Lista Mensual</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="employeeFilter">Empleado:</label>
                                <select id="employeeFilter" class="form-control form-control-sm">
                                    <option value="">Todos los empleados</option>
                                    <?php
                                    $employees = [];
                                    foreach ($vacation_events as $event) {
                                        $key = $event['employee_id'];
                                        if (!isset($employees[$key])) {
                                            $employees[$key] = $event['firstname'] . ' ' . $event['lastname'];
                                        }
                                    }
                                    foreach ($employees as $id => $name): ?>
                                        <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="statusFilter">Estado:</label>
                                <select id="statusFilter" class="form-control form-control-sm">
                                    <option value="">Todos los estados</option>
                                    <option value="APPROVED">Aprobadas</option>
                                    <option value="TAKEN">Tomadas</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label>Leyenda:</label>
                        <div class="d-flex flex-wrap">
                            <span class="badge badge-success mr-2 mb-1">
                                <i class="fas fa-circle"></i> Aprobadas
                            </span>
                            <span class="badge badge-info mr-2 mb-1">
                                <i class="fas fa-circle"></i> Tomadas
                            </span>
                            <span class="badge badge-danger mr-2 mb-1">
                                <i class="fas fa-circle"></i> Feriados
                            </span>
                            <span class="badge badge-warning mr-2 mb-1">
                                <i class="fas fa-circle"></i> Compensación
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Vacaciones Aprobadas</span>
                        <span class="info-box-number" id="approved-count">
                            <?= count(array_filter($vacation_events, fn($e) => $e['status'] === 'APPROVED')) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-plane"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Vacaciones Tomadas</span>
                        <span class="info-box-number" id="taken-count">
                            <?= count(array_filter($vacation_events, fn($e) => $e['status'] === 'TAKEN')) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Días</span>
                        <span class="info-box-number" id="total-days">
                            <?= array_sum(array_column($vacation_events, 'business_days')) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-heart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Feriados</span>
                        <span class="info-box-number"><?= count($holidays) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendario Principal -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar mr-2"></i>
                    Calendario Empresarial
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="maximize">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>

        <!-- Panel de Conflictos -->
        <div class="card card-warning" id="conflictsPanel" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Posibles Conflictos Detectados
                </h3>
            </div>
            <div class="card-body" id="conflictsList">
                <!-- Se llenará dinámicamente -->
            </div>
        </div>

    </div>
</section>

<!-- Modal de Detalles de Evento -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">Detalles de Vacaciones</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="eventModalBody">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <a href="#" id="eventDetailLink" class="btn btn-info">Ver Detalles Completos</a>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales/es.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');

    // Preparar eventos de vacaciones
    const vacationEvents = [
        <?php foreach ($vacation_events as $event): ?>
        {
            id: 'vacation-<?= $event['id'] ?>',
            title: '<?= htmlspecialchars($event['firstname'] . ' ' . $event['lastname']) ?>',
            start: '<?= $event['start_date'] ?>',
            end: '<?= date('Y-m-d', strtotime($event['end_date'] . ' +1 day')) ?>', // FullCalendar end es exclusivo
            backgroundColor: '<?= $event['status'] === 'APPROVED' ? '#28a745' : ($event['status'] === 'TAKEN' ? '#17a2b8' : '#ffc107') ?>',
            borderColor: '<?= $event['status'] === 'APPROVED' ? '#1e7e34' : ($event['status'] === 'TAKEN' ? '#117a8b' : '#e0a800') ?>',
            extendedProps: {
                employeeId: '<?= $event['employee_id'] ?>',
                employeeName: '<?= htmlspecialchars($event['firstname'] . ' ' . $event['lastname']) ?>',
                status: '<?= $event['status'] ?>',
                businessDays: <?= $event['business_days'] ?>,
                totalDays: <?= $event['total_days'] ?>,
                vacationType: '<?= $event['vacation_type'] ?>',
                requestId: <?= $event['id'] ?>,
                compensationAmount: <?= $event['compensation_amount'] ?? 0 ?>
            }
        },
        <?php endforeach; ?>
    ];

    // Preparar feriados
    const holidayEvents = [
        <?php foreach ($holidays as $holiday): ?>
        {
            id: 'holiday-<?= $holiday['id'] ?>',
            title: '<?= htmlspecialchars($holiday['description']) ?>',
            start: '<?= $holiday['date'] ?>',
            allDay: true,
            backgroundColor: '#dc3545',
            borderColor: '#c82333',
            display: 'background',
            extendedProps: {
                type: 'holiday',
                dayType: '<?= $holiday['day_type'] ?>'
            }
        },
        <?php endforeach; ?>
    ];

    // Combinar todos los eventos
    const allEvents = [...vacationEvents, ...holidayEvents];

    // Inicializar FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        height: 'auto',
        events: allEvents,
        eventClick: function(info) {
            if (info.event.extendedProps.type === 'holiday') {
                // Mostrar información del feriado
                showHolidayInfo(info.event);
            } else {
                // Mostrar información de vacaciones
                showVacationInfo(info.event);
            }
        },
        eventDidMount: function(info) {
            // Añadir tooltip
            if (info.event.extendedProps.type !== 'holiday') {
                info.el.title = `${info.event.extendedProps.employeeName} - ${info.event.extendedProps.businessDays} días`;
            }
        },
        dayCellDidMount: function(info) {
            // Resaltar días con múltiples eventos
            const dayEvents = allEvents.filter(event => {
                const eventStart = new Date(event.start);
                const eventEnd = new Date(event.end || event.start);
                const cellDate = info.date;
                return cellDate >= eventStart && cellDate < eventEnd;
            });

            if (dayEvents.length > 2) {
                info.el.style.backgroundColor = '#fff3cd';
                info.el.title = `${dayEvents.length} eventos este día`;
            }
        }
    });

    calendar.render();

    // Función para mostrar información de vacaciones
    function showVacationInfo(event) {
        const props = event.extendedProps;

        const statusLabels = {
            'APPROVED': '<span class="badge badge-success">Aprobada</span>',
            'TAKEN': '<span class="badge badge-info">Tomada</span>',
            'PENDING': '<span class="badge badge-warning">Pendiente</span>'
        };

        const typeLabels = {
            'ANNUAL': 'Anuales',
            'ACCUMULATED': 'Acumuladas',
            'COMPENSATION': 'Compensación',
            'PROPORTIONAL': 'Proporcionales'
        };

        const modalBody = `
            <div class="row">
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5">Empleado:</dt>
                        <dd class="col-sm-7"><strong>${props.employeeName}</strong></dd>

                        <dt class="col-sm-5">Período:</dt>
                        <dd class="col-sm-7">
                            ${event.start.toLocaleDateString('es-ES')} al
                            ${new Date(event.end.getTime() - 24*60*60*1000).toLocaleDateString('es-ES')}
                        </dd>

                        <dt class="col-sm-5">Estado:</dt>
                        <dd class="col-sm-7">${statusLabels[props.status] || props.status}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5">Tipo:</dt>
                        <dd class="col-sm-7">${typeLabels[props.vacationType] || props.vacationType}</dd>

                        <dt class="col-sm-5">Días Hábiles:</dt>
                        <dd class="col-sm-7"><strong>${props.businessDays} días</strong></dd>

                        <dt class="col-sm-5">Días Totales:</dt>
                        <dd class="col-sm-7">${props.totalDays} días</dd>

                        ${props.compensationAmount > 0 ? `
                        <dt class="col-sm-5">Compensación:</dt>
                        <dd class="col-sm-7"><strong><?= currency_symbol() ?>${props.compensationAmount.toFixed(2)}</strong></dd>
                        ` : ''}
                    </dl>
                </div>
            </div>
        `;

        document.getElementById('eventModalTitle').textContent = `Vacaciones - ${props.employeeName}`;
        document.getElementById('eventModalBody').innerHTML = modalBody;
        document.getElementById('eventDetailLink').href = `<?= \App\Core\UrlHelper::route('panel/vacation/show/') ?>${props.requestId}`;

        $('#eventModal').modal('show');
    }

    // Función para mostrar información de feriados
    function showHolidayInfo(event) {
        const modalBody = `
            <div class="text-center">
                <i class="fas fa-heart fa-3x text-danger mb-3"></i>
                <h4>${event.title}</h4>
                <p class="text-muted">Feriado Nacional</p>
                <p><strong>Fecha:</strong> ${event.start.toLocaleDateString('es-ES')}</p>
                <p><small class="text-muted">Los feriados nacionales no se consideran como días hábiles para vacaciones.</small></p>
            </div>
        `;

        document.getElementById('eventModalTitle').textContent = 'Feriado Nacional';
        document.getElementById('eventModalBody').innerHTML = modalBody;
        document.getElementById('eventDetailLink').style.display = 'none';

        $('#eventModal').modal('show');
    }

    // Filtros
    document.getElementById('viewSelect').addEventListener('change', function() {
        calendar.changeView(this.value);
    });

    document.getElementById('employeeFilter').addEventListener('change', function() {
        const employeeId = this.value;
        filterEvents();
    });

    document.getElementById('statusFilter').addEventListener('change', function() {
        filterEvents();
    });

    function filterEvents() {
        const employeeId = document.getElementById('employeeFilter').value;
        const status = document.getElementById('statusFilter').value;

        // Remover todos los eventos actuales
        calendar.removeAllEvents();

        // Filtrar eventos
        let filteredEvents = [...vacationEvents, ...holidayEvents];

        if (employeeId) {
            filteredEvents = filteredEvents.filter(event =>
                event.extendedProps.type === 'holiday' ||
                event.extendedProps.employeeId === employeeId
            );
        }

        if (status) {
            filteredEvents = filteredEvents.filter(event =>
                event.extendedProps.type === 'holiday' ||
                event.extendedProps.status === status
            );
        }

        // Agregar eventos filtrados
        calendar.addEventSource(filteredEvents);

        // Actualizar estadísticas
        updateStats(filteredEvents.filter(e => e.extendedProps.type !== 'holiday'));
    }

    function updateStats(events) {
        const approved = events.filter(e => e.extendedProps.status === 'APPROVED').length;
        const taken = events.filter(e => e.extendedProps.status === 'TAKEN').length;
        const totalDays = events.reduce((sum, e) => sum + (e.extendedProps.businessDays || 0), 0);

        document.getElementById('approved-count').textContent = approved;
        document.getElementById('taken-count').textContent = taken;
        document.getElementById('total-days').textContent = totalDays;
    }

    // Detectar conflictos (múltiples empleados de vacaciones el mismo día)
    function detectConflicts() {
        const conflicts = {};

        vacationEvents.forEach(event => {
            const start = new Date(event.start);
            const end = new Date(event.end);

            for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
                const dateStr = d.toISOString().split('T')[0];
                if (!conflicts[dateStr]) conflicts[dateStr] = [];
                conflicts[dateStr].push(event);
            }
        });

        const conflictDates = Object.keys(conflicts).filter(date => conflicts[date].length > 1);

        if (conflictDates.length > 0) {
            showConflicts(conflictDates, conflicts);
        }
    }

    function showConflicts(dates, conflicts) {
        let conflictHtml = '<ul class="list-unstyled">';

        dates.slice(0, 5).forEach(date => {
            const employees = conflicts[date].map(e => e.extendedProps.employeeName).join(', ');
            conflictHtml += `
                <li class="mb-2">
                    <strong>${new Date(date).toLocaleDateString('es-ES')}:</strong>
                    <br><small class="text-muted">${employees}</small>
                </li>
            `;
        });

        if (dates.length > 5) {
            conflictHtml += `<li class="text-muted">... y ${dates.length - 5} fechas más</li>`;
        }

        conflictHtml += '</ul>';

        document.getElementById('conflictsList').innerHTML = conflictHtml;
        document.getElementById('conflictsPanel').style.display = 'block';
    }

    // Ejecutar detección de conflictos
    detectConflicts();

    // Resaltar evento específico si se pasa parámetro
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    if (highlightId) {
        setTimeout(() => {
            const event = calendar.getEventById(`vacation-${highlightId}`);
            if (event) {
                calendar.gotoDate(event.start);
                // Simular click para mostrar detalles
                showVacationInfo(event);
            }
        }, 500);
    }
});
</script>

<style>
.fc-event {
    cursor: pointer;
}

.fc-event:hover {
    opacity: 0.8;
}

.fc-daygrid-event {
    border-radius: 3px;
    font-size: 0.8em;
}

.fc-list-event:hover {
    background-color: #f8f9fa;
}

.info-box-number {
    font-size: 1.5rem;
}
</style>