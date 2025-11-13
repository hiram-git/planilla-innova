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
                <button type="button" class="btn btn-success" id="btnSyncCalendar" title="Sincronizar calendario desde API Base44">
                    <i class="fas fa-sync-alt"></i> Sincronizar desde API
                </button>
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
                    Leyenda de Badges
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <span class="badge badge-success mr-2">Lab</span> Días Laborables
                    </div>
                    <div class="col-md-2">
                        <span class="badge badge-secondary mr-2">Desc</span> Fines de Semana
                    </div>
                    <div class="col-md-2">
                        <span class="badge badge-danger mr-2">Fer</span> Feriados Nacionales
                    </div>
                    <div class="col-md-2">
                        <span class="badge badge-dark mr-2">Duelo</span> Duelo Nacional
                    </div>
                    <div class="col-md-2">
                        <span class="badge badge-info mr-2">Esp</span> Días Especiales
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <small class="text-muted">
                            <i class="fas fa-tag mr-1"></i>
                            Cada día del calendario muestra un badge indicando su tipo. Los eventos especiales aparecen además como eventos completos.
                        </small>
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

<style>
    .fc-daygrid-day-top {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
    .day-type-badge {
        font-size: 0.65rem;
        padding: 2px 6px;
        margin-top: 2px;
        margin-left: 2px;
        font-weight: 600;
        border-radius: 3px;
        display: inline-block;
    }
    .badge-laboral { background-color: #28a745; color: white; }
    .badge-no-laboral { background-color: #6c757d; color: white; }
    .badge-feriado { background-color: #dc3545; color: white; }
    .badge-duelo { background-color: #343a40; color: white; }
    .badge-especial { background-color: #17a2b8; color: white; }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var calendarEl = document.getElementById("calendar");

    // Eventos del calendario
    var eventsData = ' . json_encode($calendar_events) . ';

    // Días indexados por fecha
    var daysByDate = ' . json_encode($days_by_date) . ';

    // Mapeo de tipos de día a clases CSS
    var dayTypeClasses = {
        "LABORAL": "badge-laboral",
        "NO_LABORAL": "badge-no-laboral",
        "FERIADO": "badge-feriado",
        "DUELO_NACIONAL": "badge-duelo",
        "ESPECIAL": "badge-especial"
    };

    // Mapeo de tipos de día a etiquetas cortas
    var dayTypeLabels = {
        "LABORAL": "Lab",
        "NO_LABORAL": "Desc",
        "FERIADO": "Fer",
        "DUELO_NACIONAL": "Duelo",
        "ESPECIAL": "Esp"
    };

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
        // Agregar badges en cada celda del día
        dayCellDidMount: function(info) {
            // Obtener fecha en formato YYYY-MM-DD
            var dateStr = info.date.toISOString().split("T")[0];

            // Buscar información del día
            var dayInfo = daysByDate[dateStr];

            if (dayInfo && dayInfo.day_type) {
                var dayType = dayInfo.day_type;
                var badgeClass = dayTypeClasses[dayType] || "badge-secondary";
                var badgeLabel = dayTypeLabels[dayType] || dayType;

                // Crear badge
                var badge = document.createElement("span");
                badge.className = "day-type-badge " + badgeClass;
                badge.textContent = badgeLabel;
                badge.title = dayType + (dayInfo.description ? ": " + dayInfo.description : "");

                // Agregar al contenedor del día
                var dayTop = info.el.querySelector(".fc-daygrid-day-top");
                if (dayTop) {
                    dayTop.appendChild(badge);
                }
            }
        },
        // Personalización visual
        dayMaxEvents: true,
        navLinks: true,
        editable: false,
        selectable: false
    });

    calendar.render();

    // Sincronización del calendario desde API Base44
    document.getElementById("btnSyncCalendar").addEventListener("click", function() {
        Swal.fire({
            title: "¿Sincronizar Calendario?",
            html: `
                <p>¿Desea sincronizar el calendario empresarial desde la API de Base44?</p>
                <div class="text-left mt-3">
                    <h6>Opciones:</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="replaceExisting" value="1">
                        <label class="form-check-label" for="replaceExisting">
                            Reemplazar registros existentes del año ' . $year . '
                        </label>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Esta acción sincronizará feriados y días especiales desde Base44.
                        Si marca "Reemplazar", se eliminarán los registros actuales del año antes de importar los nuevos.
                    </small>
                </div>
            `,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<i class=\"fas fa-sync-alt mr-1\"></i> Sincronizar",
            cancelButtonText: "<i class=\"fas fa-times mr-1\"></i> Cancelar",
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const replace = document.getElementById("replaceExisting").checked;

                return $.ajax({
                    url: "' . \App\Core\UrlHelper::route('panel/business-calendar/sync-api') . '",
                    method: "POST",
                    dataType: "json",
                    data: {
                        year: ' . $year . ',
                        replace: replace,
                        csrf_token: "' . ($csrf_token ?? '') . '"
                    }
                }).fail(function(xhr) {
                    Swal.showValidationMessage(
                        `Error de conexión: ${xhr.statusText || "No se pudo conectar con el servidor"}`
                    );
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                const response = result.value;

                if (response.success) {
                    // Mostrar resultado exitoso
                    let detailsHTML = `
                        <div class="text-left">
                            <p><strong>${response.message}</strong></p>
                            <table class="table table-sm mt-2">
                                <tr><th>Obtenidos:</th><td>${response.stats.fetched}</td></tr>
                                <tr><th>Insertados:</th><td class="text-success">${response.stats.inserted}</td></tr>
                                <tr><th>Actualizados:</th><td class="text-info">${response.stats.updated}</td></tr>
                                <tr><th>Omitidos:</th><td class="text-muted">${response.stats.skipped}</td></tr>
                                ${response.stats.deleted > 0 ? `<tr><th>Eliminados:</th><td class="text-warning">${response.stats.deleted}</td></tr>` : ""}
                                ${response.stats.errors > 0 ? `<tr><th>Errores:</th><td class="text-danger">${response.stats.errors}</td></tr>` : ""}
                            </table>
                        </div>
                    `;

                    Swal.fire({
                        title: "Sincronización Exitosa",
                        html: detailsHTML,
                        icon: "success",
                        confirmButtonText: "Recargar Página"
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: "Error en Sincronización",
                        text: response.message || "Error desconocido",
                        icon: "error",
                        confirmButtonText: "Entendido"
                    });
                }
            }
        });
    });
});
</script>
';

require_once __DIR__ . '/../../layouts/admin.php';
