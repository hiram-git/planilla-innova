<?php
use App\Core\UrlHelper;

$scheduleOptions = '';
foreach ($schedules as $sch) {
    $scheduleOptions .= '<option value="' . $sch['id'] . '">' . $sch['codigo'] . ' (' . date('h:i A', strtotime($sch['time_in'])) . ' - ' . date('h:i A', strtotime($sch['time_out'])) . ')</option>';
}

$eventsUrl = UrlHelper::url('panel/personal-schedule/events/' . $employee['id']);
$saveUrl = UrlHelper::url('panel/personal-schedule/saveDay');
$deleteUrl = UrlHelper::url('panel/personal-schedule/deleteDay');
$initializeUrl = UrlHelper::url('panel/personal-schedule/initialize');
$importUrl = UrlHelper::url('panel/personal-schedule/import');
$backUrl = UrlHelper::employee();
$employeeId = (int)$employee['id'];

$content = <<<HTML
<section class="content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-6">
                <a href="{$backUrl}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Empleados
                </a>
            </div>
            <div class="col-md-6 text-right">
                <button type="button" class="btn btn-info mr-2" data-toggle="modal" data-target="#modalInitialize">
                    <i class="fas fa-magic"></i> Inicializar Rango
                </button>
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalImport">
                    <i class="fas fa-file-upload"></i> Importar Horarios
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#calendar-tab" data-toggle="tab">Calendario</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="active tab-pane" id="calendar-tab">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal: Inicializar rango -->
<div class="modal fade" id="modalInitialize" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Inicializar Rango de Fechas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formInitialize">
                    <div class="form-group">
                        <label>Rango de Fechas</label>
                        <div class="row">
                            <div class="col-6">
                                <label><small>Desde</small></label>
                                <input type="date" class="form-control" id="initStartDate" required>
                            </div>
                            <div class="col-6">
                                <label><small>Hasta</small></label>
                                <input type="date" class="form-control" id="initEndDate" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Horario a Asignar</label>
                        <select class="form-control" id="initScheduleId" required>
                            <option value="">-- Seleccionar Horario --</option>
                            {$scheduleOptions}
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnInitialize">Inicializar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Importar horarios -->
<div class="modal fade" id="modalImport" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importar Horarios</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formImport">
                    <div class="form-group">
                        <label>Archivo CSV</label>
                        <input type="file" class="form-control-file" id="importFile" accept=".csv">
                        <small class="form-text text-muted">Formato: Cedula, Fecha (YYYY-MM-DD), Codigo Horario</small>
                    </div>
                </form>
                <div id="importResult" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnImport">Importar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar dia -->
<div class="modal fade" id="modalDaySchedule" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Horario: <span id="modalDateDisplay"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formDaySchedule">
                    <input type="hidden" id="editDate" name="date">
                    <input type="hidden" id="employeeId" name="employee_id" value="{$employeeId}">

                    <div class="form-group">
                        <label>Horario</label>
                        <select class="form-control" id="scheduleSelect" name="schedule_id">
                            <option value="">-- Seleccionar Horario --</option>
                            {$scheduleOptions}
                        </select>
                        <small class="text-muted">Seleccione un horario para anular el predeterminado de este dia.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger mr-auto" id="btnResetDay" title="Volver al horario predeterminado">Restaurar Predeterminado</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSaveDay">Guardar</button>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales/es.global.min.js"></script>

<style>
    .schedule-legend .legend-pill {
        color: #fff;
        font-weight: 600;
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 12px;
        margin-left: 6px;
        margin-bottom: 6px;
        display: inline-flex;
        align-items: center;
    }
    #calendar .fc-bg-event {
        opacity: 0.18;
    }
    .fc-badge-business {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        color: #fff;
        background: #6c757d;
        line-height: 1.2;
        white-space: normal;
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var calendarEl = document.getElementById("calendar");
    var employeeId = {$employeeId};

    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: "es",
        initialView: "dayGridMonth",
        height: "auto",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,listMonth"
        },
        events: "{$eventsUrl}",
        dateClick: function(info) {
            openModal(info.dateStr);
        },
        eventClick: function(info) {
            if (info.event.extendedProps && info.event.extendedProps.source === "business") {
                return;
            }
            var dateStr = info.event.startStr.split("T")[0];
            openModal(dateStr, info.event);
        },
        eventDidMount: function(info) {
            var props = info.event.extendedProps || {};
            var tips = [];
            if (props.day_type) tips.push("Tipo: " + props.day_type);
            if (props.business_status) tips.push("Estado: " + props.business_status);
            if (props.description && props.source === "business") tips.push(props.description);
            if (tips.length) {
                info.el.setAttribute("title", tips.join(" | "));
            }
        },
        eventContent: function(arg) {
            var props = arg.event.extendedProps || {};
            // Reemplazar texto itálico por badge en eventos de calendario empresarial
            if (props.source === "business") {
                var color = arg.event.backgroundColor || "#6c757d";
                var textColor = "#fff";
                // Mejorar contraste: si es el gris de no laborables, usar texto oscuro
                if (color.toLowerCase() === "#6c757d") {
                    textColor = "#212529";
                }
                return {
                    html: '<span class="fc-badge-business" style="background:' + color + ';color:' + textColor + ';">' + arg.event.title + '</span>'
                };
            }
            return true; // usar render por defecto
        },
        editable: false
    });

    calendar.render();

    function openModal(dateStr, event) {
        $("#modalDateDisplay").text(dateStr);
        $("#editDate").val(dateStr);
        $("#scheduleSelect").val("");

        if (event && event.extendedProps && event.extendedProps.schedule_id && !event.extendedProps.is_default) {
            $("#scheduleSelect").val(event.extendedProps.schedule_id);
        }

        $("#modalDaySchedule").modal("show");
    }

    $("#btnSaveDay").on("click", function() {
        var scheduleId = $("#scheduleSelect").val();
        var date = $("#editDate").val();

        if (!scheduleId) {
            Swal.fire("Error", "Seleccione un horario", "error");
            return;
        }

        $.ajax({
            url: "{$saveUrl}",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                employee_id: employeeId,
                date: date,
                schedule_id: scheduleId
            }),
            success: function(res) {
                if(res.success) {
                    $("#modalDaySchedule").modal("hide");
                    calendar.refetchEvents();
                    Swal.fire("Guardado", "Horario actualizado", "success");
                } else {
                    Swal.fire("Error", res.message || "Error al guardar", "error");
                }
            }
        });
    });

    $("#btnResetDay").on("click", function() {
        var date = $("#editDate").val();

        Swal.fire({
            title: "Restaurar horario por defecto?",
            text: "Se eliminara la asignacion personalizada para este dia.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Si, restaurar"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{$deleteUrl}",
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({
                        employee_id: employeeId,
                        date: date
                    }),
                    success: function(res) {
                        if(res.success) {
                            $("#modalDaySchedule").modal("hide");
                            calendar.refetchEvents();
                            Swal.fire("Restaurado", "Horario restaurado al valor por defecto", "success");
                        } else {
                            Swal.fire("Error", res.message || "Error al restaurar", "error");
                        }
                    }
                });
            }
        });
    });

    $("#btnInitialize").on("click", function() {
        var start = $("#initStartDate").val();
        var end = $("#initEndDate").val();
        var scheduleId = $("#initScheduleId").val();

        if (!start || !end || !scheduleId) {
             Swal.fire("Error", "Complete todos los campos", "error");
             return;
        }

        $.ajax({
            url: "{$initializeUrl}",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                employee_id: employeeId,
                start_date: start,
                end_date: end,
                schedule_id: scheduleId
            }),
            success: function(res) {
                 if (res.success) {
                     $("#modalInitialize").modal("hide");
                     calendar.refetchEvents();
                     Swal.fire("Exito", res.message, "success");
                 } else {
                     Swal.fire("Error", res.message || "Error al inicializar", "error");
                 }
            }
        });
    });

    $("#btnImport").on("click", function() {
        var fileInput = document.getElementById("importFile");
        if (fileInput.files.length === 0) {
            Swal.fire("Error", "Seleccione un archivo", "error");
            return;
        }

        var formData = new FormData();
        formData.append("file", fileInput.files[0]);

        var btnImport = $(this);
        var originalBtnText = btnImport.text();
        btnImport.prop("disabled", true).text("Importando...");
        $("#importResult").html("");

        $.ajax({
            url: "{$importUrl}",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                btnImport.prop("disabled", false).text(originalBtnText);

                if(res.success) {
                    $("#modalImport").modal("hide");
                    calendar.refetchEvents();
                    Swal.fire("Exito", res.message, "success");
                } else {
                    $("#importResult").html('<div class="alert alert-danger">' + (res.message || "Error al importar") + '</div>');
                }
            },
            error: function() {
                btnImport.prop("disabled", false).text(originalBtnText);
                Swal.fire("Error", "Error en la solicitud", "error");
            }
        });
    });
});
</script>
HTML;

require_once __DIR__ . '/../../layouts/admin.php';
