<?php
/**
 * Vista: Ausencias Pendientes de Justificación
 * Ruta: /panel/attendance/pending-absences-view
 */
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?= $page_title ?? 'Ausencias Pendientes' ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/panel/dashboard">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/panel/attendance">Marcaciones</a></li>
                    <li class="breadcrumb-item active">Ausencias Pendientes</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <!-- Estadísticas -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $stats['total_pending'] ?></h3>
                        <p>Total Pendientes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= $stats['unjustified'] ?></h3>
                        <p>Injustificadas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $stats['pending'] ?></h3>
                        <p>Por Revisar</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= $stats['employees_affected'] ?></h3>
                        <p>Empleados Afectados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="/panel/attendance/pending-absences-view" method="GET" id="filter-form">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="employee_id">Empleado:</label>
                                        <select class="form-control select2" id="employee_id" name="employee_id" style="width: 100%;">
                                            <option value="">Todos los empleados</option>
                                            <?php if (!empty($employees)): ?>
                                                <?php foreach ($employees as $employee): ?>
                                                    <option value="<?= $employee['id'] ?>"
                                                            <?= ($filters['employee_id'] == $employee['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="start_date">Fecha Inicio:</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date"
                                               value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="end_date">Fecha Fin:</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                               value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listado de Ausencias Pendientes -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-times"></i> Ausencias Pendientes (<?= count($absences) ?>)
                        </h3>
                        <div class="card-tools">
                            <a href="/panel/attendance" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Volver a Marcaciones
                            </a>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-striped" id="absencesTable">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Fecha Ausencia</th>
                                    <th>Día</th>
                                    <th>Tipo</th>
                                    <th>Día Laboral</th>
                                    <th>Detectado</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($absences)): ?>
                                    <?php foreach ($absences as $absence): ?>
                                        <?php
                                        // Determinar badges
                                        $typeColors = [
                                            'UNJUSTIFIED' => 'danger',
                                            'PENDING' => 'warning',
                                            'JUSTIFIED' => 'success'
                                        ];
                                        $typeLabels = [
                                            'UNJUSTIFIED' => 'Injustificada',
                                            'PENDING' => 'Pendiente',
                                            'JUSTIFIED' => 'Justificada'
                                        ];
                                        $typeColor = $typeColors[$absence['absence_type']] ?? 'secondary';
                                        $typeLabel = $typeLabels[$absence['absence_type']] ?? $absence['absence_type'];

                                        // Formatear fecha
                                        $date = $absence['absence_date'];
                                        $timestamp = strtotime($date);
                                        $dayName = date('l', $timestamp);
                                        $dayNames = [
                                            'Monday' => 'Lun', 'Tuesday' => 'Mar', 'Wednesday' => 'Mié',
                                            'Thursday' => 'Jue', 'Friday' => 'Vie', 'Saturday' => 'Sáb', 'Sunday' => 'Dom'
                                        ];
                                        $dayNameSpanish = $dayNames[$dayName] ?? $dayName;
                                        $dateFormatted = date('d/m/Y', $timestamp);

                                        // Día laboral
                                        $isWorkingDay = $absence['is_working_day'] ?? 1;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($absence['employee_name'] ?? 'N/A') ?></strong>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($absence['employee_code'] ?? '') ?></small>
                                            </td>
                                            <td><?= $dateFormatted ?></td>
                                            <td><?= $dayNameSpanish ?></td>
                                            <td>
                                                <span class="badge badge-<?= $typeColor ?>">
                                                    <?= $typeLabel ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($isWorkingDay): ?>
                                                    <span class="badge badge-info">Sí</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('d/m/Y H:i', strtotime($absence['detected_at'])) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($absence['resolved']): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> Resuelto
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> Pendiente
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!$absence['resolved']): ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-primary btn-justify-absence"
                                                            data-id="<?= $absence['id'] ?>"
                                                            data-employee="<?= htmlspecialchars($absence['employee_name'] ?? '') ?>"
                                                            data-date="<?= $dateFormatted ?>"
                                                            title="Justificar Ausencia">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="fas fa-info-circle"></i> No hay ausencias pendientes
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Justificar Ausencia -->
<div class="modal fade" id="justifyAbsenceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle"></i> Justificar Ausencia
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="form-justify-absence">
                <input type="hidden" id="justify_absence_id" name="absence_id">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Empleado:</strong> <span id="justify_employee_name"></span><br>
                        <strong>Fecha:</strong> <span id="justify_absence_date"></span>
                    </div>

                    <div class="form-group">
                        <label for="justification_type">Tipo de Justificación:</label>
                        <select class="form-control" id="justification_type" name="justification_type" required>
                            <option value="">Seleccione...</option>
                            <option value="MEDICAL">Médica</option>
                            <option value="PERMISSION">Permiso Autorizado</option>
                            <option value="VACATION">Vacaciones</option>
                            <option value="BEREAVEMENT">Duelo</option>
                            <option value="MATERNITY">Maternidad/Paternidad</option>
                            <option value="OTHER">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="justification_notes">Notas / Explicación:</label>
                        <textarea class="form-control" id="justification_notes" name="justification_notes" rows="4" placeholder="Ingrese detalles de la justificación..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="justification_document">Documento de Respaldo (opcional):</label>
                        <input type="text" class="form-control" id="justification_document" name="justification_document" placeholder="Ruta o referencia del documento">
                        <small class="form-text text-muted">Puede ingresar la ruta del archivo o referencia del documento.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Justificar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: 'Seleccione un empleado...',
        allowClear: true
    });

    // Inicializar DataTable
    $('#absencesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[1, 'desc']], // Ordenar por fecha descendente
        pageLength: 25
    });

    // Abrir modal de justificación
    $(document).on('click', '.btn-justify-absence', function() {
        const absenceId = $(this).data('id');
        const employeeName = $(this).data('employee');
        const absenceDate = $(this).data('date');

        $('#justify_absence_id').val(absenceId);
        $('#justify_employee_name').text(employeeName);
        $('#justify_absence_date').text(absenceDate);
        $('#justifyAbsenceModal').modal('show');
    });

    // Procesar formulario de justificación
    $('#form-justify-absence').on('submit', function(e) {
        e.preventDefault();

        const absenceId = $('#justify_absence_id').val();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalHtml = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        $.ajax({
            url: `/panel/attendance/absence/${absenceId}/justify`,
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#justifyAbsenceModal').modal('hide');
                    form[0].reset();
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    toastr.error(response.message || 'Error al justificar ausencia');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error al justificar ausencia';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
                console.error('Error:', xhr);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
</script>
