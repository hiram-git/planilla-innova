<?php
$page_title = 'Asistencia';

$content = '
<div class="row">
    <div class="col-sm-12">
        <div class="float-sm-right">
            <a href="' . \App\Core\UrlHelper::attendance('reports') . '" class="btn btn-info btn-sm">
                <i class="fas fa-chart-bar"></i> Reportes
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Registros de Asistencia</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal">
                        <i class="fas fa-plus"></i> Nuevo Registro
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="attendanceTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Empleado</th>
                                <th>Código</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Horas</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>';

foreach ($attendances as $attendance) {
    $status = $attendance['status'] == 1 
        ? '<span class="badge badge-success"><i class="fas fa-check"></i> A tiempo</span>' 
        : '<span class="badge badge-warning"><i class="fas fa-clock"></i> Tarde</span>';
        
    $timeOut = $attendance['time_out'] && $attendance['time_out'] !== '00:00:00' 
        ? date('h:i A', strtotime($attendance['time_out'])) 
        : '<span class="text-muted">Pendiente</span>';
        
    $hours = $attendance['num_hr'] > 0 ? number_format($attendance['num_hr'], 1) . 'h' : '-';
    
    $content .= '
                            <tr>
                                <td>' . date('d/m/Y', strtotime($attendance['date'])) . '</td>
                                <td>
                                    <strong>' . htmlspecialchars($attendance['firstname'] . ' ' . $attendance['lastname']) . '</strong>
                                </td>
                                <td>' . htmlspecialchars($attendance['employee_id']) . '</td>
                                <td>
                                    <span class="text-primary">' . date('h:i A', strtotime($attendance['time_in'])) . '</span>
                                </td>
                                <td>' . $timeOut . '</td>
                                <td>' . $hours . '</td>
                                <td>' . $status . '</td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-btn" data-id="' . $attendance['id'] . '">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="' . $attendance['id'] . '" data-employee="' . htmlspecialchars($attendance['firstname'] . ' ' . $attendance['lastname']) . '" data-date="' . date('d/m/Y', strtotime($attendance['date'])) . '">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>';
}

$content .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Agregar Registro de Asistencia</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="' . $csrf_token . '">
                    
                    <div class="form-group">
                        <label for="employee_id">Empleado *</label>
                        <select class="form-control" id="employee_id" name="employee_id" required>
                            <option value="">Seleccione un empleado</option>';

foreach ($employees as $employee) {
    $content .= '<option value="' . $employee['id'] . '">' . htmlspecialchars($employee['employee_id'] . ' - ' . $employee['firstname'] . ' ' . $employee['lastname']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Fecha *</label>
                        <input type="date" class="form-control" id="date" name="date" value="' . date('Y-m-d') . '" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="time_in">Hora de Entrada *</label>
                                <input type="time" class="form-control" id="time_in" name="time_in" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="time_out">Hora de Salida</label>
                                <input type="time" class="form-control" id="time_out" name="time_out">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Editar Registro de Asistencia</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="' . $csrf_token . '">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="form-group">
                        <label>Empleado</label>
                        <p class="form-control-static" id="edit_employee_name"></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_date">Fecha *</label>
                        <input type="date" class="form-control" id="edit_date" name="edit_date" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_time_in">Hora de Entrada *</label>
                                <input type="time" class="form-control" id="edit_time_in" name="edit_time_in" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_time_out">Hora de Salida</label>
                                <input type="time" class="form-control" id="edit_time_out" name="edit_time_out">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirmar Eliminación</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el registro de asistencia?</p>
                <div class="text-center">
                    <strong id="deleteEmployeeName"></strong><br>
                    <span id="deleteDate" class="text-muted"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
            </div>
        </div>
    </div>
</div>';

$scripts = '
<script src="' . url('plugins/datatables/jquery.dataTables.min.js', false) . '"></script>
<script src="' . url('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js', false) . '"></script>
<script>
$(document).ready(function() {
    // DataTable
    $("#attendanceTable").DataTable({
        "language": {
            "url": "' . url('assets/js/datatables-spanish.json', false) . '"
        },
        "order": [[0, "desc"]]
    });

    // Edit button click
    $(".edit-btn").click(function() {
        const id = $(this).data("id");
        $("#editModal").modal("show");
        loadAttendanceData(id);
    });

    // Delete button click
    $(".delete-btn").click(function() {
        const id = $(this).data("id");
        const employee = $(this).data("employee");
        const date = $(this).data("date");
        
        $("#deleteEmployeeName").text(employee);
        $("#deleteDate").text(date);
        $("#deleteModal").modal("show");
        
        $("#confirmDelete").off("click").on("click", function() {
            window.location.href = "' . \App\Core\UrlHelper::attendance('delete') . '/" + id;
        });
    });

    // Add form submit
    $("#addForm").submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.post("' . \App\Core\UrlHelper::attendance('store') . '", formData)
            .done(function() {
                location.reload();
            })
            .fail(function(xhr) {
                alert("Error al agregar registro de asistencia");
            });
    });

    // Edit form submit
    $("#editForm").submit(function(e) {
        e.preventDefault();
        const id = $("#edit_id").val();
        const formData = $(this).serialize();
        
        $.post("' . \App\Core\UrlHelper::attendance('update') . '/" + id, formData)
            .done(function() {
                location.reload();
            })
            .fail(function(xhr) {
                alert("Error al actualizar registro de asistencia");
            });
    });

    // Load attendance data for editing
    function loadAttendanceData(id) {
        $.post("' . \App\Core\UrlHelper::attendance('getRow') . '", { id: id })
            .done(function(response) {
                $("#edit_id").val(response.id);
                $("#edit_employee_name").text(response.firstname + " " + response.lastname);
                $("#edit_date").val(response.date);
                $("#edit_time_in").val(response.time_in);
                $("#edit_time_out").val(response.time_out);
            })
            .fail(function() {
                alert("Error al cargar datos del registro");
            });
    }
});
</script>';

$styles = '
<link rel="stylesheet" href="' . url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css', false) . '">';

include __DIR__ . '/../../layouts/admin.php';
?>