<?php
use App\Helpers\PermissionHelper;

$pageTitle = 'Calendario Empresarial';

$content = '
<section class="content">
    <div class="container-fluid">

        <!-- Controles -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="btn-group">
                    <a href="' . \App\Core\UrlHelper::route('panel/business-calendar/calendar?year=' . ($year - 1)) . '"
                       class="btn btn-outline-secondary">
                        <i class="fas fa-chevron-left"></i> ' . ($year - 1) . '
                    </a>
                    <button class="btn btn-primary" disabled>' . $year . '</button>
                    <a href="' . \App\Core\UrlHelper::route('panel/business-calendar/calendar?year=' . ($year + 1)) . '"
                       class="btn btn-outline-secondary">
                        ' . ($year + 1) . ' <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-md-6 text-right">
                <a href="' . \App\Core\UrlHelper::route('panel/business-calendar/listado?year=' . $year) . '"
                   class="btn btn-info">
                    <i class="fas fa-list"></i> Listado
                </a>
            </div>
        </div>

        <!-- Leyenda de Colores -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>
                    Leyenda
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <span class="badge badge-success mr-2">■</span> Días Laborables
                    </div>
                    <div class="col-md-2">
                        <span class="badge badge-danger mr-2">■</span> Feriados Nacionales
                    </div>
                    <div class="col-md-2">
                        <span class="badge badge-secondary mr-2">■</span> Fines de Semana
                    </div>
                    <div class="col-md-2">
                        <span class="badge badge-dark mr-2">■</span> Duelo Nacional
                    </div>
                    <div class="col-md-2">
                        <span class="badge badge-info mr-2">■</span> Días Especiales
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendario -->
        <div class="card">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>

    </div>
</section>

<!-- Modal: Detalle del Día -->
<div class="modal fade" id="modalDayDetail" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-day mr-2"></i>
                    Detalle del Día
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDayDetailBody">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales/es.global.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var calendarEl = document.getElementById("calendar");

    // Eventos del calendario
    var eventsData = ' . json_encode($calendar_events) . ';

    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: "es",
        initialView: "dayGridMonth",
        initialDate: "' . $year . '-01-01",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,dayGridWeek,listMonth"
        },
        buttonText: {
            today: "Hoy",
            month: "Mes",
            week: "Semana",
            list: "Lista"
        },
        height: "auto",
        events: eventsData,
        eventClick: function(info) {
            // Mostrar detalles en modal
            var event = info.event;
            var html = `
                <table class="table">
                    <tr>
                        <th>Fecha:</th>
                        <td>${new Date(event.start).toLocaleDateString("es-PA")}</td>
                    </tr>
                    <tr>
                        <th>Descripción:</th>
                        <td><strong>${event.title}</strong></td>
                    </tr>
                    <tr>
                        <th>Tipo:</th>
                        <td><span class="badge" style="background-color: ${event.backgroundColor}">${event.extendedProps.dayType}</span></td>
                    </tr>
                    <tr>
                        <th>Estado:</th>
                        <td><span class="badge badge-secondary">${event.extendedProps.status}</span></td>
                    </tr>
                </table>
            `;

            document.getElementById("modalDayDetailBody").innerHTML = html;
            $("#modalDayDetail").modal("show");
        },
        // Personalización visual
        dayMaxEvents: true,
        navLinks: true,
        editable: false,
        selectable: false
    });

    calendar.render();
});
</script>
';

require_once __DIR__ . '/../../layouts/admin.php';
