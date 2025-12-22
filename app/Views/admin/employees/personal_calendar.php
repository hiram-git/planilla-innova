<?php
use App\Core\UrlHelper;

$content = '
<section class="content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-6">
                 <a href="' . UrlHelper::employee() . '" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Empleados
                 </a>
            </div>
            <div class="col-md-6 text-right">
            <div class="col-md-6 text-right">
                <button type="button" class="btn btn-info mr-2" data-toggle="modal" data-target="#modalInitialize">
                    <i class="fas fa-magic"></i> Inicializar Rango
                </button>
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalImport">
                    <i class="fas fa-file-upload"></i> Importar Horarios
                </button>
            </div>
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

    </div>
</section>

<!-- Modal: Initialize Range -->
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
                            ';
                            foreach ($schedules as $sch) {
                                $content .= '<option value="' . $sch['id'] . '">' . $sch['codigo'] . ' (' . date('h:i A', strtotime($sch['time_in'])) . ' - ' . date('h:i A', strtotime($sch['time_out'])) . ')</option>';
                            }
                            $content .= '
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

<!-- Modal: Import Schedules -->
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
                        <small class="form-text text-muted">Formato: Cédula, Fecha (YYYY-MM-DD), Código Horario</small>
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

<!-- Modal: Edit Day Schedule -->
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
                    <input type="hidden" id="employeeId" name="employee_id" value="' . $employee['id'] . '">
                    
                    <div class="form-group">
                        <label>Horario</label>
                        <select class="form-control" id="scheduleSelect" name="schedule_id">
                            <option value="">-- Seleccionar Horario --</option>
                            '; 
                            foreach ($schedules as $sch) {
                                $content .= '<option value="' . $sch['id'] . '">' . $sch['codigo'] . ' (' . date('h:i A', strtotime($sch['time_in'])) . ' - ' . date('h:i A', strtotime($sch['time_out'])) . ')</option>';
                            }
                            $content .= '
                        </select>
                        <small class="text-muted">Seleccione un horario para anular el predeterminado de este día.</small>
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    var calendarEl = document.getElementById("calendar");
    var employeeId = ' . $employee['id'] . ';
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: "es",
        initialView: "dayGridMonth",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,listMonth"
        },
        events: "' . UrlHelper::url('panel/personal-schedule/events/' . $employee['id']) . '",
        dateClick: function(info) {
            openModal(info.dateStr);
        },
        eventClick: function(info) {
            // If clicking an event, open modal for that day
            // We use start date normalized
            var dateStr = info.event.startStr.split("T")[0];
            openModal(dateStr, info.event);
        },
        editable: false
    });
    
    calendar.render();
    
    function openModal(dateStr, event = null) {
        $("#modalDateDisplay").text(dateStr);
        $("#editDate").val(dateStr);
        
        // Reset selection
        $("#scheduleSelect").val("");
        
        if (event && event.extendedProps.schedule_id && !event.extendedProps.is_default) {
             $("#scheduleSelect").val(event.extendedProps.schedule_id);
        }
        
        $("#modalDaySchedule").modal("show");
    }
    
    $("#btnSaveDay").click(function() {
        var scheduleId = $("#scheduleSelect").val();
        var date = $("#editDate").val();
        
        if (!scheduleId) {
            Swal.fire("Error", "Seleccione un horario", "error");
            return;
        }
        
        $.ajax({
            url: "' . UrlHelper::url('panel/personal-schedule/saveDay') . '",
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
    
    $("#btnResetDay").click(function() {
        var date = $("#editDate").val();
        
        Swal.fire({
            title: "¿Restaurar horario por defecto?",
            text: "Se eliminará la asignación personalizada para este día.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, restaurar"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "' . UrlHelper::url('panel/personal-schedule/deleteDay') . '",
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
    
        });
    });
    
    // Initialize Logic
    $("#btnInitialize").click(function() {
        var start = $("#initStartDate").val();
        var end = $("#initEndDate").val();
        var scheduleId = $("#initScheduleId").val();
        
        if (!start || !end || !scheduleId) {
             Swal.fire("Error", "Complete todos los campos", "error");
             return;
        }
        
        $.ajax({
            url: "' . UrlHelper::url('panel/personal-schedule/initialize') . '",
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
                     Swal.fire("Éxito", res.message, "success");
                 } else {
                     Swal.fire("Error", res.message || "Error al inicializar", "error");
                 }
            }
        });
    });
    
    // Import Logic
    $("#btnImport").click(function() {
        var fileInput = document.getElementById("importFile");
        if (fileInput.files.length === 0) {
            Swal.fire("Error", "Seleccione un archivo", "error");
            return;
        }
        
        var formData = new FormData();
        formData.append("file", fileInput.files[0]);
        
        // Show loading state
        let originalBtnText = $(this).text();
        $(this).prop("disabled", true).text("Importando...");
        $("#importResult").html("");
        
        $.ajax({
            url: "' . UrlHelper::url('panel/personal-schedule/import') . '",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $("#btnImport").prop("disabled", false).text(originalBtnText);
                
                if(res.success) {
                    $("#modalImport").modal("hide");
                    calendar.refetchEvents();
                    Swal.fire("Éxito", res.message, "success");
                } else {
                    $("#importResult").html(\'<div class="alert alert-danger">\' + res.message + \'</div>\');
                }
            },
            error: function() {
                $("#btnImport").prop("disabled", false).text(originalBtnText);
                Swal.fire("Error", "Error en la solicitud", "error");
            }
        });
    });
});
</script>
';

require_once __DIR__ . '/../../layouts/admin.php';
