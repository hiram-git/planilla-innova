<?php
use App\Helpers\PermissionHelper;

$pageTitle = 'Calendario Empresarial - Listado';

$content = '
<section class="content">
    <div class="container-fluid">

        <!-- Selector de Año y Vista Calendario -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="btn-group">
                    <a href="' . \App\Core\UrlHelper::route('panel/business-calendar?year=' . ($year - 1)) . '"
                       class="btn btn-outline-secondary">
                        <i class="fas fa-chevron-left"></i> ' . ($year - 1) . '
                    </a>
                    <button class="btn btn-primary" disabled>' . $year . '</button>
                    <a href="' . \App\Core\UrlHelper::route('panel/business-calendar?year=' . ($year + 1)) . '"
                       class="btn btn-outline-secondary">
                        ' . ($year + 1) . ' <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-md-6 text-right">
                <a href="' . \App\Core\UrlHelper::route('panel/business-calendar?year=' . $year) . '"
                   class="btn btn-info">
                    <i class="fas fa-calendar"></i> Calendario
                </a>
                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modalInitializeYear">
                    <i class="fas fa-calendar-plus"></i> Inicializar Año
                </button>
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalAddSpecialDay">
                    <i class="fas fa-plus"></i> Agregar Día Especial
                </button>
            </div>
        </div>

        <!-- Estadísticas del Año -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>' . $stats['dias_laborables'] . '</h3>
                        <p>Días Laborables</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>' . $stats['feriados'] . '</h3>
                        <p>Feriados Nacionales</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>' . $stats['no_laborables'] . '</h3>
                        <p>Fines de Semana</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-home"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>' . $stats['especiales'] . '</h3>
                        <p>Días Especiales</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Feriados y Días Especiales -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-check mr-2"></i>
                    Feriados Nacionales ' . $year . '
                </h3>
            </div>
            <div class="card-body">
                <table id="holidaysTable" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Día Semana</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>';

$diasSemana = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$dayTypeColors = [
    'FERIADO' => 'danger',
    'DUELO_NACIONAL' => 'dark',
    'ESPECIAL' => 'info',
    'NO_LABORAL' => 'secondary',
    'LABORAL' => 'success'
];

foreach ($holidays as $holiday) {
    $typeColor = $dayTypeColors[$holiday['day_type']] ?? 'secondary';
    $fecha = date('d/m/Y', strtotime($holiday['date_value']));
    $diaSemana = $diasSemana[$holiday['day_of_week']] ?? 'N/A';

    $content .= '
                        <tr>
                            <td><strong>' . $fecha . '</strong></td>
                            <td>' . $diaSemana . '</td>
                            <td>' . htmlspecialchars($holiday['description']) . '</td>
                            <td><span class="badge badge-' . $typeColor . '">' . $holiday['day_type'] . '</span></td>
                            <td><span class="badge badge-secondary">' . $holiday['status'] . '</span></td>
                            <td>';

    // Botón editar para FERIADOS (para configurar si es pagado)
    if ($holiday['day_type'] === 'FERIADO') {
        $isPaidHoliday = isset($holiday['is_paid_holiday']) && $holiday['is_paid_holiday'] == 1 ? 1 : 0;
        $content .= '
                                <button type="button" class="btn btn-sm btn-primary"
                                        onclick="editHoliday(' . $holiday['id'] . ', \'' . htmlspecialchars($holiday['description']) . '\', \'' . $holiday['day_type'] . '\', \'' . $holiday['status'] . '\', ' . $isPaidHoliday . ')">
                                    <i class="fas fa-edit"></i> Editar
                                </button>';
    }
    // Solo permitir eliminar días ESPECIALES (no feriados nacionales)
    elseif ($holiday['day_type'] === 'ESPECIAL') {
        $content .= '
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteSpecialDay(' . $holiday['id'] . ', \'' . htmlspecialchars($holiday['description']) . '\')">
                                    <i class="fas fa-trash"></i>
                                </button>';
    } else {
        $content .= '<span class="text-muted">—</span>';
    }

    $content .= '
                            </td>
                        </tr>';
}

$content .= '
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<!-- Modal: Agregar Día Especial -->
<div class="modal fade" id="modalAddSpecialDay" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="' . \App\Core\UrlHelper::route('panel/business-calendar/store') . '" method="POST">
                <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">
                <div class="modal-header bg-success">
                    <h5 class="modal-title">
                        <i class="fas fa-plus mr-2"></i>
                        Agregar Día Especial
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="date_value">Fecha *</label>
                        <input type="date" class="form-control" id="date_value" name="date_value" required>
                    </div>
                    <div class="form-group">
                        <label for="day_type">Tipo de Día *</label>
                        <select class="form-control" id="day_type" name="day_type" required>
                            <option value="">Seleccione...</option>
                            <option value="FERIADO">Feriado</option>
                            <option value="ESPECIAL">Día Especial</option>
                            <option value="DUELO_NACIONAL">Duelo Nacional</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Estado</label>
                        <select class="form-control" id="status" name="status">
                            <option value="NORMAL">Normal</option>
                            <option value="RECUPERABLE">Recuperable</option>
                            <option value="MEDIO_DIA">Medio Día</option>
                            <option value="HORARIO_ESPECIAL">Horario Especial</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Descripción *</label>
                        <input type="text" class="form-control" id="description" name="description"
                               placeholder="Ej: Día de la Constitución" required>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch custom-switch-on-success">
                            <input type="checkbox" class="custom-control-input" id="is_paid_holiday" name="is_paid_holiday" value="1">
                            <label class="custom-control-label" for="is_paid_holiday">
                                <strong>Feriado Pagado</strong>
                                <br>
                                <small class="text-muted">
                                    Genera automáticamente 8 horas trabajadas en asistencias para todos los empleados
                                </small>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Inicializar Año Completo -->
<div class="modal fade" id="modalInitializeYear" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="' . \App\Core\UrlHelper::route('panel/business-calendar/initializeYear') . '" method="POST" id="formInitializeYear">
                <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-plus mr-2"></i>
                        Inicializar Calendario Anual
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>¿Qué hace esta función?</strong>
                        <p class="mb-0 mt-2">Genera automáticamente todos los días del año seleccionado:</p>
                        <ul class="mb-0 mt-1">
                            <li>Días laborables (Lunes a Viernes)</li>
                            <li>Fines de semana (Sábados y Domingos)</li>
                            <li>Mantiene los feriados nacionales ya existentes</li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <label for="init_year">Año a Inicializar *</label>
                        <select class="form-control" id="init_year" name="year" required>
                            <option value="">Seleccione un año...</option>';

$currentYear = (int)date('Y');
for ($y = 2024; $y <= ($currentYear + 5); $y++) {
    $selected = ($y === $year) ? 'selected' : '';
    $content .= '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
}

$content .= '
                        </select>
                        <small class="form-text text-muted">
                            Solo se agregarán días que no existan. Los días ya registrados (feriados, días especiales) no se modificarán.
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="saturday_half_day" name="saturday_half_day" value="1">
                            <label class="custom-control-label" for="saturday_half_day">
                                <strong>Sábados como medio día laborable</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Si se activa, los sábados se marcarán como días laborables con estado "MEDIO_DIA" en lugar de días no laborables.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-calendar-plus"></i> Inicializar Calendario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Editar Feriado -->
<div class="modal fade" id="modalEditHoliday" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-edit mr-2"></i>
                    Editar Feriado
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_holiday_id">

                <div class="form-group">
                    <label for="edit_description">Descripción *</label>
                    <input type="text" class="form-control" id="edit_description"
                           placeholder="Nombre del feriado" required>
                </div>

                <div class="form-group">
                    <label for="edit_day_type">Tipo de Día</label>
                    <select class="form-control" id="edit_day_type">
                        <option value="FERIADO">Feriado</option>
                        <option value="DUELO_NACIONAL">Duelo Nacional</option>
                        <option value="ESPECIAL">Día Especial</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_status">Estado</label>
                    <select class="form-control" id="edit_status">
                        <option value="NORMAL">Normal</option>
                        <option value="RECUPERABLE">Recuperable</option>
                        <option value="MEDIO_DIA">Medio Día</option>
                        <option value="HORARIO_ESPECIAL">Horario Especial</option>
                    </select>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch custom-switch-on-success">
                        <input type="checkbox" class="custom-control-input" id="edit_is_paid_holiday" value="1">
                        <label class="custom-control-label" for="edit_is_paid_holiday">
                            <strong>Feriado Pagado</strong>
                            <br>
                            <small class="text-muted">
                                Si está activado, se generarán automáticamente 8 horas trabajadas en asistencias para todos los empleados
                            </small>
                        </label>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Nota:</strong> Los feriados pagados generan automáticamente registros de asistencia con 8 horas trabajadas
                    para integración con planillas.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveHolidayChanges()">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Form para eliminar (oculto) -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">
</form>
';

// Scripts que dependen de jQuery - se cargarán después de las librerías
$scripts = '
<script>
$(document).ready(function() {
    $("#holidaysTable").DataTable({
        "language": {
            "url": "' . \App\Core\UrlHelper::url('assets/javascript/datatables-spanish.js') . '"
        },
        "order": [[0, "asc"]],
        "pageLength": 25
    });
});

function deleteSpecialDay(id, description) {
    Swal.fire({
        title: "¿Eliminar día especial?",
        html: "<p>Se eliminará: <strong>" + description + "</strong></p>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            var form = document.getElementById("deleteForm");
            form.action = "' . \App\Core\UrlHelper::route('panel/business-calendar/delete') . '/" + id;
            form.submit();
        }
    });
}

/**
 * Abrir modal de edición de feriado
 */
function editHoliday(id, description, dayType, status, isPaidHoliday) {
    // Establecer valores en el formulario
    document.getElementById("edit_holiday_id").value = id;
    document.getElementById("edit_description").value = description;
    document.getElementById("edit_day_type").value = dayType;
    document.getElementById("edit_status").value = status;
    document.getElementById("edit_is_paid_holiday").checked = (isPaidHoliday == 1);

    // Mostrar modal
    $("#modalEditHoliday").modal("show");
}

/**
 * Guardar cambios del feriado mediante AJAX
 */
function saveHolidayChanges() {
    var holidayId = document.getElementById("edit_holiday_id").value;
    var description = document.getElementById("edit_description").value.trim();
    var dayType = document.getElementById("edit_day_type").value;
    var status = document.getElementById("edit_status").value;
    var isPaidHoliday = document.getElementById("edit_is_paid_holiday").checked ? 1 : 0;

    // Validación básica
    if (!description) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "La descripción es obligatoria"
        });
        return;
    }

    // Mostrar loading
    Swal.fire({
        title: "Guardando...",
        text: "Por favor espere",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Enviar mediante AJAX
    $.ajax({
        url: "' . \App\Core\UrlHelper::route('panel/business-calendar/update') . '/" + holidayId,
        type: "POST",
        data: {
            csrf_token: "' . htmlspecialchars($csrf_token) . '",
            description: description,
            day_type: dayType,
            status: status,
            is_paid_holiday: isPaidHoliday
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "Éxito",
                    text: response.message || "Feriado actualizado exitosamente",
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Cerrar modal y recargar página
                    $("#modalEditHoliday").modal("hide");
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: response.message || "Error al actualizar el feriado"
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("Error AJAX:", error);
            Swal.fire({
                icon: "error",
                title: "Error de Conexión",
                text: "No se pudo conectar con el servidor. Por favor intente nuevamente."
            });
        }
    });
}
</script>
';

require_once __DIR__ . '/../../layouts/admin.php';
