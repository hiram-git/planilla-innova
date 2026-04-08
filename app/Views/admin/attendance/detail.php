<?php
/**
 * Vista: Detalle de Marcaciones del Día
 * Ruta: /panel/attendance/detail/{date}
 * REFACTORIZADO: Usa AttendanceHeader y AttendanceDetail
 */
if (isset($header['processed_at']) && is_string($header['processed_at']) && strtotime($header['processed_at']) !== false) {
    $fecha = date('d/m/Y H:i', strtotime($header['processed_at']));
} else {
    $fecha = 'Fecha no disponible o inválida';
}
?>


<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <!-- Cabecera del Día -->
        <div class="row">
            <div class="col-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-day"></i>
                            Marcaciones del <strong><?= date('d/m/Y', strtotime($date)) ?></strong>
                            (<?php
                                $dayNames = [
                                    'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
                                    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
                                ];
                                echo $dayNames[date('l', strtotime($date))] ?? date('l', strtotime($date));
                            ?>)
                        </h3>
                        <div class="card-tools">
                            <a href="<?= \App\Core\UrlHelper::panel('attendance') ?>" class="btn btn-secondary " title="Volver al listado">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <strong><i class="fas fa-desktop mr-1"></i> Dispositivo:</strong>
                                <span class="text-muted">
                                    <?= htmlspecialchars($header['device_name'] ?? 'N/A') ?>
                                </span>
                            </div>
                            <div class="col-md-4">
                                <strong><i class="fas fa-sync-alt mr-1"></i> Sincronizado desde:</strong>
                                <span class="text-muted">
                                    <?= $header['synced_from'] ?? 'N/A' ?>
                                </span>
                            </div>
                            <div class="col-md-4">
                                <strong><i class="fas fa-check-circle mr-1"></i> Estado:</strong>
                                <?php if ($header['is_processed']): ?>
                                    <span class="badge badge-success">Procesado</span>
                                    <small class="text-muted">
                                        (<?= $fecha ?>)
                                    </small>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pendiente</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas del Día -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= $header['total_employees'] ?></h3>
                        <p>Total Empleados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $header['total_on_time'] ?></h3>
                        <p>A Tiempo</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $header['total_late'] ?></h3>
                        <p>Tardanzas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= $header['total_absent'] ?></h3>
                        <p>Ausentes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros y Acciones -->
        <div class="row">
            <div class="col-12">
                <div class="card collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="display: none;">
                        <form method="GET" action="/panel/attendance/detail/<?= $date ?>">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="filter_status">Estado:</label>
                                        <select class="form-control" id="filter_status" name="status">
                                            <option value="">Todos</option>
                                            <option value="PRESENT" <?= ($current_status_filter === 'PRESENT') ? 'selected' : '' ?>>Presente</option>
                                            <option value="LATE" <?= ($current_status_filter === 'LATE') ? 'selected' : '' ?>>Tarde</option>
                                            <option value="ABSENT" <?= ($current_status_filter === 'ABSENT') ? 'selected' : '' ?>>Ausente</option>
                                            <option value="INCOMPLETE" <?= ($current_status_filter === 'INCOMPLETE') ? 'selected' : '' ?>>Incompleto</option>
                                            <option value="JUSTIFIED" <?= ($current_status_filter === 'JUSTIFIED') ? 'selected' : '' ?>>Justificado</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Filtrar
                                            </button>
                                            <a href="/panel/attendance/detail/<?= $date ?>" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Limpiar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalle de Marcaciones -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Detalle de Marcaciones (<?= count($details) ?>)</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-danger btn-sm btn-process-day" title="Procesar marcaciones completas del día">
                                <i class="fas fa-cogs"></i> Procesar Marcaciones
                            </button>
                            <button type="button" class="btn btn-warning btn-sm btn-process-all-calculations" title="Procesar solo cálculos de marcaciones existentes">
                                <i class="fas fa-calculator"></i> Procesar Cálculos
                            </button>
                            <!--<button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <a href="<?= \App\Core\UrlHelper::panel('attendance/export-excel/' . $date) ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-file-excel"></i> Exportar Excel
                            </a>
                            <a href="<?= \App\Core\UrlHelper::panel('attendance/export-pdf/' . $date) ?>" class="btn btn-danger btn-sm">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>-->
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-striped" id="attendanceDetailTable">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Horario</th>
                                    <th>Hora Entrada</th>
                                    <th>Salida Almuerzo</th>
                                    <th>Entrada Almuerzo</th>
                                    <th>Hora Salida</th>
                                    <th>Tardanza</th>
                                    <th>Horas Trabajadas</th>
                                    <th>Horas Extras</th>
                                    <th>Puntualidad</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($details)): ?>
                                    <?php foreach ($details as $detail): ?>
                                        <?php
                                        // Badges de estado
                                        $statusColors = [
                                            'PRESENT' => 'success',
                                            'ABSENT' => 'danger',
                                            'LATE' => 'warning',
                                            'INCOMPLETE' => 'info',
                                            'JUSTIFIED' => 'secondary'
                                        ];
                                        $statusLabels = [
                                            'PRESENT' => 'Presente',
                                            'ABSENT' => 'Ausente',
                                            'LATE' => 'Tarde',
                                            'INCOMPLETE' => 'Incompleto',
                                            'JUSTIFIED' => 'Justificado'
                                        ];
                                        $statusColor = $statusColors[$detail['status']] ?? 'secondary';
                                        $statusLabel = $statusLabels[$detail['status']] ?? $detail['status'];

                                        // Formateo de horas
                                        $timeIn = $detail['time_in'] ? date('H:i', strtotime($detail['time_in'])) : '-';
                                        $timeOut = $detail['time_out'] ? date('H:i', strtotime($detail['time_out'])) : '<span class="text-muted"><i>Pendiente</i></span>';
                                        $lunchOut = $detail['lunch_out'] ? date('H:i', strtotime($detail['lunch_out'])) : '<span class="text-muted">-</span>';
                                        $lunchIn = $detail['lunch_in'] ? date('H:i', strtotime($detail['lunch_in'])) : '<span class="text-muted">-</span>';
                                        $hoursWorked = $detail['hours_worked'] > 0 ? number_format($detail['hours_worked'], 2) . 'h' : '-';

                                        // Tardanza
                                        $tardiness = '';
                                        if ($detail['is_late'] && $detail['tardiness_minutes'] > 0) {
                                            $tardiness = '<span class="badge badge-danger">' . $detail['tardiness_minutes'] . ' min</span>';
                                        } else {
                                            $tardiness = '<span class="badge badge-success">0</span>';
                                        }

                                        // Horario
                                        $scheduleInfo = '-';
                                        if ($detail['scheduled_time_in']) {
                                            $scheduleInfo = date('H:i', strtotime($detail['scheduled_time_in']));
                                            if ($detail['scheduled_time_out']) {
                                                $scheduleInfo .= ' - ' . date('H:i', strtotime($detail['scheduled_time_out']));
                                            }
                                        }

                                        // Indicador de cálculo procesado y horas extras
                                        $punctualityDisplay = '';
                                        $overtimeDisplay = '-';

                                        if (!empty($detail['calculation'])) {
                                            $calc = $detail['calculation'];
                                            $score = $calc['punctuality_score'] ?? 0;
                                            $isPerfect = $calc['is_perfect_attendance'] ?? 0;
                                            $overtimeHours  = $calc['overtime_hours'] ?? 0;
                                            $overtime25     = $calc['overtime_25_hours'] ?? 0;
                                            $overtime50     = $calc['overtime_50_hours'] ?? 0;
                                            $holidayHours   = $calc['holiday_hours'] ?? 0;

                                            // Color del badge según score
                                            $scoreColor = 'success';
                                            if ($score < 50) $scoreColor = 'danger';
                                            elseif ($score < 80) $scoreColor = 'warning';

                                            $punctualityDisplay = '<span class="badge badge-' . $scoreColor . '" title="Cálculo procesado - Score: ' . $score . '">';
                                            $punctualityDisplay .= '<i class="fas fa-check-circle"></i> ' . $score . '%';
                                            $punctualityDisplay .= '</span>';

                                            if ($isPerfect) {
                                                $punctualityDisplay .= ' <i class="fas fa-star text-warning" title="Asistencia Perfecta"></i>';
                                            }

                                            // Mostrar horas: días de descanso/feriado → holiday_hours (150%)
                                            // Días laborables → overtime_hours con desglose 25%/50%
                                            if ($holidayHours > 0) {
                                                $totalHolidayDisplay = $holidayHours + $overtimeHours;
                                                $overtimeDisplay = '<span class="badge badge-warning" title="Día de descanso/feriado - Art. 48">';
                                                $overtimeDisplay .= number_format($totalHolidayDisplay, 2) . 'h';
                                                $overtimeDisplay .= '</span>';
                                                $overtimeDisplay .= '<br><small class="text-muted">Descanso +150%: ' . number_format($holidayHours, 2) . 'h</small>';
                                                if ($overtimeHours > 0) {
                                                    $overtimeDisplay .= '<br><small class="text-muted">Extras descanso: ' . number_format($overtimeHours, 2) . 'h</small>';
                                                }
                                            } elseif ($overtimeHours > 0) {
                                                $overtimeDisplay = '<span class="badge badge-primary" title="Horas extras totales">';
                                                $overtimeDisplay .= number_format($overtimeHours, 2) . 'h';
                                                $overtimeDisplay .= '</span>';

                                                if ($overtime25 > 0 || $overtime50 > 0) {
                                                    $overtimeDisplay .= '<br><small class="text-muted">';
                                                    if ($overtime25 > 0) $overtimeDisplay .= '+25%: ' . number_format($overtime25, 2) . 'h ';
                                                    if ($overtime50 > 0) $overtimeDisplay .= '+50%: ' . number_format($overtime50, 2) . 'h';
                                                    $overtimeDisplay .= '</small>';
                                                }
                                            } else {
                                                $overtimeDisplay = '<span class="badge badge-secondary">0h</span>';
                                            }

                                            // Actualizar horas trabajadas con el cálculo
                                            if (isset($calc['total_hours'])) {
                                                $hoursWorked = number_format($calc['total_hours'], 2) . 'h';
                                            }

                                            // Actualizar tardanza con el cálculo
                                            if (isset($calc['tardiness_minutes'])) {
                                                if ($calc['tardiness_minutes'] > 0) {
                                                    $tardiness = '<span class="badge badge-danger">' . $calc['tardiness_minutes'] . ' min</span>';
                                                } else {
                                                    $tardiness = '<span class="badge badge-success">0</span>';
                                                }
                                            }
                                        } else {
                                            $punctualityDisplay = '<span class="badge badge-secondary" title="Cálculo pendiente">';
                                            $punctualityDisplay .= '<i class="fas fa-calculator"></i> Calcular';
                                            $punctualityDisplay .= '</span>';
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars($detail['firstname'] . ' ' . $detail['lastname']) ?>
                                                </strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($detail['employee_number'] ?? '') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small><?= $scheduleInfo ?></small>
                                            </td>
                                            <td>
                                                <span class="text-primary">
                                                    <strong><?= $timeIn ?></strong>
                                                </span>
                                            </td>
                                            <td><?= $lunchOut ?></td>
                                            <td><?= $lunchIn ?></td>
                                            <td><?= $timeOut ?></td>
                                            <td><?= $tardiness ?></td>
                                            <td><?= $hoursWorked ?></td>
                                            <td class="overtime-hours" data-detail-id="<?= $detail['id'] ?>">
                                                <?= $overtimeDisplay ?>
                                            </td>
                                            <td class="punctuality-score" data-detail-id="<?= $detail['id'] ?>">
                                                <?= $punctualityDisplay ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $statusColor ?>">
                                                    <?= $statusLabel ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button"
                                                            class="btn btn-primary btn-calculate"
                                                            data-id="<?= $detail['id'] ?>"
                                                            title="Calcular Métricas">
                                                        <i class="fas fa-calculator"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-info btn-edit-detail"
                                                            data-id="<?= $detail['id'] ?>"
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($detail['status'] === 'ABSENT'): ?>
                                                        <button type="button"
                                                                class="btn btn-warning btn-justify"
                                                                data-id="<?= $detail['id'] ?>"
                                                                title="Justificar Ausencia">
                                                            <i class="fas fa-file-alt"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($detail['status'] === 'JUSTIFIED'): ?>
                                                        <button type="button"
                                                                class="btn btn-secondary btn-edit-justification"
                                                                data-id="<?= $detail['id'] ?>"
                                                                title="Ver/Editar Justificación">
                                                            <i class="fas fa-file-medical-alt"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button"
                                                            class="btn btn-danger btn-delete-detail"
                                                            data-id="<?= $detail['id'] ?>"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12" class="text-center text-muted">
                                            <i class="fas fa-info-circle"></i> No hay marcaciones registradas para esta fecha
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt"></i> Justificar Ausencia
                    <small id="justify_modal_subtitle" class="ml-2 text-dark-50"></small>
                </h5>
                <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
            </div>
            <form id="form-justify-absence" enctype="multipart/form-data">
                <input type="hidden" id="justify_detail_id" name="detail_id">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="justify_type">
                                    <i class="fas fa-clipboard-list"></i> Tipo de Justificación
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="justify_type" name="justification_type" required>
                                    <option value="">-- Seleccione --</option>
                                    <option value="MEDICAL">Médica (Incapacidad)</option>
                                    <option value="PERMISSION">Permiso Autorizado</option>
                                    <option value="VACATION">Vacaciones</option>
                                    <option value="PERSONAL">Personal</option>
                                    <option value="OTHER">Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="justify_notes">
                                    <i class="fas fa-sticky-note"></i> Observaciones
                                </label>
                                <textarea class="form-control" id="justify_notes" name="justification_notes" rows="3" placeholder="Ingrese observaciones o detalles de la justificación..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="justify_document">
                                    <i class="fas fa-file-pdf"></i> Adjuntar Documento (PDF)
                                    <small class="text-muted">(Opcional - Máximo 1MB)</small>
                                </label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="justify_document" name="justification_document" accept=".pdf">
                                    <label class="custom-file-label" for="justify_document">Seleccionar archivo PDF...</label>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Solo archivos PDF, tamaño máximo 1MB
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Preview del PDF -->
                    <div id="pdf-preview-container" class="row mt-3" style="display: none;">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-eye"></i> Vista Previa del Documento
                                        <button type="button" class="btn btn-sm btn-danger float-right" id="btn-remove-pdf">
                                            <i class="fas fa-times"></i> Quitar
                                        </button>
                                    </h6>
                                </div>
                                <div class="card-body p-2">
                                    <div id="pdf-info" class="alert alert-info mb-2">
                                        <i class="fas fa-file-pdf text-danger"></i>
                                        <strong id="pdf-filename"></strong>
                                        <span id="pdf-filesize" class="text-muted"></span>
                                    </div>
                                    <embed id="pdf-embed" type="application/pdf" width="100%" height="400px" />
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-check"></i> Justificar Ausencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Marcación -->
<div class="modal fade" id="editDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Marcación <small id="edit_modal_subtitle" class="ml-2 text-white-50"></small></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="form-edit-detail">
                <input type="hidden" id="edit_detail_id" name="detail_id">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hora Entrada:</label>
                                <input type="time" class="form-control" id="edit_time_in" name="time_in">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hora Salida:</label>
                                <input type="time" class="form-control" id="edit_time_out" name="time_out">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    Salida Almuerzo:
                                    <small class="text-muted">(Opcional)</small>
                                </label>
                                <input type="time" class="form-control" id="edit_lunch_out" name="lunch_out">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    Entrada Almuerzo:
                                    <small class="text-muted">(Opcional)</small>
                                </label>
                                <input type="time" class="form-control" id="edit_lunch_in" name="lunch_in">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notas:</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Justificación -->
<div class="modal fade" id="editJustificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title">
                    <i class="fas fa-file-medical-alt"></i> Ver/Editar Justificación
                    <small id="edit_justification_modal_subtitle" class="ml-2 text-white-50"></small>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="form-edit-justification" enctype="multipart/form-data">
                <input type="hidden" id="edit_justification_detail_id" name="detail_id">
                <input type="hidden" id="edit_justification_remove_document" name="remove_document" value="0">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <div class="modal-body">
                    <!-- Tipo de Justificación -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_justify_type">
                                    <i class="fas fa-clipboard-list"></i> Tipo de Justificación
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="edit_justify_type" name="justification_type" required>
                                    <option value="">-- Seleccione --</option>
                                    <option value="MEDICAL">Médica (Incapacidad)</option>
                                    <option value="PERMISSION">Permiso Autorizado</option>
                                    <option value="VACATION">Vacaciones</option>
                                    <option value="PERSONAL">Personal</option>
                                    <option value="OTHER">Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_justify_notes">
                                    <i class="fas fa-sticky-note"></i> Observaciones
                                </label>
                                <textarea class="form-control" id="edit_justify_notes" name="justification_notes" rows="3" placeholder="Ingrese observaciones o detalles de la justificación..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Documento Actual (si existe) -->
                    <div id="edit_current_document_container" class="row mb-3" style="display: none;">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-file-pdf text-danger"></i> Documento Actual
                                        <button type="button" class="btn btn-sm btn-danger float-right" id="btn-remove-current-pdf" title="Eliminar documento actual">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </h6>
                                </div>
                                <div class="card-body p-2">
                                    <div id="edit_current_pdf_info" class="alert alert-info mb-2">
                                        <i class="fas fa-file-pdf text-danger"></i>
                                        <strong id="edit_current_pdf_filename"></strong>
                                    </div>
                                    <embed id="edit_current_pdf_embed" type="application/pdf" width="100%" height="400px" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nuevo Documento (reemplazo opcional) -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_justify_document">
                                    <i class="fas fa-file-pdf"></i> <span id="edit_document_label">Reemplazar Documento (PDF)</span>
                                    <small class="text-muted">(Opcional - Máximo 1MB)</small>
                                </label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="edit_justify_document" name="justification_document" accept=".pdf">
                                    <label class="custom-file-label" for="edit_justify_document">Seleccionar nuevo archivo PDF...</label>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Solo archivos PDF, tamaño máximo 1MB
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Preview del Nuevo PDF -->
                    <div id="edit_pdf_preview_container" class="row mt-3" style="display: none;">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-eye"></i> Vista Previa del Nuevo Documento
                                        <button type="button" class="btn btn-sm btn-danger float-right" id="btn-remove-edit-pdf">
                                            <i class="fas fa-times"></i> Quitar
                                        </button>
                                    </h6>
                                </div>
                                <div class="card-body p-2">
                                    <div id="edit_pdf_info" class="alert alert-info mb-2">
                                        <i class="fas fa-file-pdf text-danger"></i>
                                        <strong id="edit_pdf_filename"></strong>
                                        <span id="edit_pdf_filesize" class="text-muted"></span>
                                    </div>
                                    <embed id="edit_pdf_embed" type="application/pdf" width="100%" height="400px" />
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Justificación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Iniciar captura de estilos
ob_start();
?>
<link rel="stylesheet" href="<?= url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css', false) ?>">
<?php
$styles = ob_get_clean();

// Iniciar captura de scripts
ob_start();
?>
<script src="<?= url('plugins/datatables/jquery.dataTables.min.js', false) ?>"></script>
<script src="<?= url('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js', false) ?>"></script>

<script>
$(document).ready(function() {
    // Base URL del proyecto
    const baseUrl = '<?= \App\Core\UrlHelper::base() ?>';

    // DataTable - solo inicializar si hay datos
    const $table = $('#attendanceDetailTable');
    const hasData = $table.find('tbody tr').length > 0 && !$table.find('tbody tr td[colspan]').length;

    if (hasData) {
        $table.DataTable({
            language: {
                url: '<?= url('assets/js/datatables-spanish.json', false) ?>'
            },
            order: [[0, 'asc']],
            pageLength: 50
        });
    }

    // Helper: extraer HH:MM
    function toTimeHM(value) {
        if (!value) return '';
        // Acepta 'HH:MM', 'HH:MM:SS' o 'YYYY-mm-dd HH:MM:SS'
        const m = String(value).match(/(\d{2}:\d{2})(?::\d{2})?$/);
        return m ? m[1] : '';
    }

    // Editar marcación: cargar datos y abrir modal
    $(document).on('click', '.btn-edit-detail', function() {
        const detailId = $(this).data('id');
        const $modal = $('#editDetailModal');
        const $subtitle = $('#edit_modal_subtitle');

        // Reset form
        $('#form-edit-detail')[0].reset();
        $('#edit_detail_id').val(detailId);
        $subtitle.text('');

        $.ajax({
            url: `${baseUrl}/panel/attendance/detail/${detailId}/get`,
            method: 'POST',
            success: function(response) {
                if (response && response.success && response.data) {
                    const d = response.data;
                    // Título con empleado y fecha
                    const emp = d.employee || {};
                    const name = emp.name || '';
                    const code = emp.code ? ` (${emp.code})` : '';
                    $subtitle.text(`${name}${code} — ${d.date}`);

                    // Campos de tiempo
                    $('#edit_time_in').val(toTimeHM(d.time_in));
                    $('#edit_time_out').val(toTimeHM(d.time_out));
                    $('#edit_lunch_out').val(toTimeHM(d.lunch_out));
                    $('#edit_lunch_in').val(toTimeHM(d.lunch_in));

                    // Notas
                    $('#edit_notes').val(d.notes || '');

                    $modal.modal('show');
                } else {
                    toastr.error(response && response.message ? response.message : 'No se pudo obtener la marcación');
                }
            },
            error: function(xhr) {
                toastr.error('Error al obtener la marcación');
                console.error(xhr);
            }
        });
    });

    $('#form-edit-detail').on('submit', function(e) {
        e.preventDefault();
        const detailId = $('#edit_detail_id').val();

        $.ajax({
            url: `${baseUrl}/panel/attendance/detail/${detailId}/update`,
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#editDetailModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Error al actualizar marcación');
            }
        });
    });

    // Justificar ausencia - Abrir modal
    $(document).on('click', '.btn-justify', function() {
        const detailId = $(this).data('id');
        const $modal = $('#justifyAbsenceModal');

        // Reset form y preview
        $('#form-justify-absence')[0].reset();
        $('#justify_detail_id').val(detailId);
        $('#pdf-preview-container').hide();
        $('#pdf-embed').attr('src', '');
        $('.custom-file-label').text('Seleccionar archivo PDF...');

        $modal.modal('show');
    });

    // Validar y mostrar preview del PDF
    $('#justify_document').on('change', function(e) {
        const file = e.target.files[0];

        if (!file) {
            $('#pdf-preview-container').hide();
            $('.custom-file-label').text('Seleccionar archivo PDF...');
            return;
        }

        // Validar tipo de archivo
        if (file.type !== 'application/pdf') {
            toastr.error('Solo se permiten archivos PDF');
            $(this).val('');
            $('.custom-file-label').text('Seleccionar archivo PDF...');
            return;
        }

        // Validar tamaño (1MB = 1048576 bytes)
        if (file.size > 1048576) {
            toastr.error('El archivo no debe superar 1MB de tamaño');
            $(this).val('');
            $('.custom-file-label').text('Seleccionar archivo PDF...');
            return;
        }

        // Actualizar label con nombre del archivo
        $('.custom-file-label').text(file.name);

        // Mostrar información del archivo
        $('#pdf-filename').text(file.name);
        $('#pdf-filesize').text(' (' + (file.size / 1024).toFixed(2) + ' KB)');

        // Crear URL del archivo para preview
        const fileURL = URL.createObjectURL(file);
        $('#pdf-embed').attr('src', fileURL);
        $('#pdf-preview-container').show();
    });

    // Quitar archivo PDF
    $('#btn-remove-pdf').on('click', function() {
        $('#justify_document').val('');
        $('.custom-file-label').text('Seleccionar archivo PDF...');
        $('#pdf-preview-container').hide();
        $('#pdf-embed').attr('src', '');
    });

    // Submit del formulario de justificación
    $('#form-justify-absence').on('submit', function(e) {
        e.preventDefault();

        const detailId = $('#justify_detail_id').val();
        const formData = new FormData(this);

        // Validar tipo de justificación
        if (!$('#justify_type').val()) {
            toastr.error('Debe seleccionar un tipo de justificación');
            return;
        }

        $.ajax({
            url: `${baseUrl}/panel/attendance/detail/${detailId}/justify`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#justifyAbsenceModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Error al justificar ausencia';
                toastr.error(errorMsg);
            }
        });
    });

    // Eliminar marcación
    $(document).on('click', '.btn-delete-detail', function() {
        const detailId = $(this).data('id');

        function ejecutarDelete() {
            $.ajax({
                url: `${baseUrl}/panel/attendance/detail/${detailId}/delete`,
                method: 'POST',
                data: { csrf_token: '<?= $csrf_token ?? '' ?>' },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Error al eliminar marcación');
                }
            });
        }

        // Verificar silenciosamente si hay horas extras aprobadas
        $.ajax({
            url: `${baseUrl}/panel/attendance/detail/${detailId}/check-overtime`,
            method: 'GET',
            success: function(check) {
                if (!check.success) {
                    toastr.error(check.message || 'Error al verificar horas extras');
                    return;
                }

                if (check.has_approved) {
                    // Tiene horas extras → título y advertencia según el status
                    const statusLabels = {
                        'APPROVED': 'aprobadas',
                        'PENDING':  'pendientes de aprobación',
                        'REJECTED': 'rechazadas',
                    };
                    const statusLabel = statusLabels[check.overtime_status] || 'calculadas';

                    Swal.fire({
                        title: '⚠️ Horas extras ' + statusLabel,
                        html: `<p>La marcación tiene <strong>horas ${statusLabel}</strong>:</p>
                               <ul class="text-left mt-2">
                                   <li>Horas al 25%: <strong>${parseFloat(check.overtime_25).toFixed(2)}h</strong></li>
                                   <li>Horas al 50%: <strong>${parseFloat(check.overtime_50).toFixed(2)}h</strong></li>
                                   <li>Total: <strong>${parseFloat(check.total_hours).toFixed(2)}h</strong></li>
                               </ul>
                               <p class="mt-2 text-danger">Al eliminar la marcación, también se eliminarán estas horas extras.</p>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, eliminar todo',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) ejecutarDelete();
                    });
                } else {
                    // Sin horas aprobadas → confirmación simple
                    Swal.fire({
                        title: '¿Está seguro?',
                        text: '¿Desea eliminar esta marcación?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) ejecutarDelete();
                    });
                }
            },
            error: function() {
                toastr.error('Error al verificar horas extras');
            }
        });
    });

    // ========================================
    // CALCULADORES DE ASISTENCIAS
    // ========================================

    // Calcular métricas de una marcación individual
    $(document).on('click', '.btn-calculate', function() {
        const detailId = $(this).data('id');
        const btn = $(this);
        const originalHtml = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: `${baseUrl}/panel/attendance/detail/${detailId}/calculate`,
            method: 'POST',
            data: {
                csrf_token: '<?= $csrf_token ?? '' ?>'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);

                    // Actualizar columna de puntualidad
                    const scoreCell = $(`.punctuality-score[data-detail-id="${detailId}"]`);
                    const score = response.data.punctuality_score;
                    const isPerfect = response.data.is_perfect_attendance;
                    let badgeClass = 'success';

                    if (score < 50) badgeClass = 'danger';
                    else if (score < 80) badgeClass = 'warning';

                    let html = `<span class="badge badge-${badgeClass}" title="Score: ${score}">
                                    <i class="fas fa-check-circle"></i> ${score}%
                                </span>`;

                    if (isPerfect) {
                        html += ' <i class="fas fa-star text-warning" title="Asistencia Perfecta"></i>';
                    }

                    scoreCell.html(html);

                    // Actualizar columna de horas extras
                    const overtimeCell = $(`.overtime-hours[data-detail-id="${detailId}"]`);
                    const overtimeHours = response.data.overtime_hours || 0;
                    const overtime25 = response.data.overtime_25_hours || 0;
                    const overtime50 = response.data.overtime_50_hours || 0;

                    let overtimeHtml = '';
                    if (overtimeHours > 0) {
                        overtimeHtml = `<span class="badge badge-primary" title="Horas extras totales">${overtimeHours.toFixed(2)}h</span>`;

                        if (overtime25 > 0 || overtime50 > 0) {
                            overtimeHtml += '<br><small class="text-muted">';
                            if (overtime25 > 0) {
                                overtimeHtml += `+25%: ${overtime25.toFixed(2)}h `;
                            }
                            if (overtime50 > 0) {
                                overtimeHtml += `+50%: ${overtime50.toFixed(2)}h`;
                            }
                            overtimeHtml += '</small>';
                        }
                    } else {
                        overtimeHtml = '<span class="badge badge-secondary">0h</span>';
                    }

                    overtimeCell.html(overtimeHtml);

                    // Mostrar modal con detalles
                    showCalculationDetails(response.data);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Error al calcular métricas');
                console.error(xhr);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Procesar marcaciones completas del día (ausencias + omisiones + cálculos)
    $('.btn-process-day').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();

        // Obtener tipo de planilla desde sessionStorage (navbar)
        const selectedPayrollType = sessionStorage.getItem("selectedPayrollType");

        if (!selectedPayrollType) {
            Swal.fire({
                title: 'Tipo de Planilla Requerido',
                text: 'Debe seleccionar un tipo de planilla desde el menú superior antes de procesar.',
                icon: 'warning'
            });
            return;
        }

        let tipoPlanillaData;
        try {
            tipoPlanillaData = JSON.parse(selectedPayrollType);
        } catch (e) {
            console.error("Error parsing selectedPayrollType:", e);
            Swal.fire({
                title: 'Error',
                text: 'Error al leer el tipo de planilla seleccionado.',
                icon: 'error'
            });
            return;
        }

        const tipoPlanillaId = tipoPlanillaData.id;
        const tipoPlanillaText = tipoPlanillaData.descripcion || tipoPlanillaData.nombre || 'Tipo de Planilla';

        Swal.fire({
            title: '¿Procesar Marcaciones del Día?',
            html: `
                <div class="text-left">
                    <p><strong>Tipo de Planilla: <span class="badge badge-primary">${tipoPlanillaText}</span></strong></p>
                    <div class="mb-2"><strong>Opciones:</strong></div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="opt_process_records" checked>
                        <label class="custom-control-label" for="opt_process_records">Procesar marcaciones desde registros (guardar en detalle y marcar como procesadas)</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="opt_detect_absences" checked>
                        <label class="custom-control-label" for="opt_detect_absences">Detectar ausencias y crear registros</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="opt_mark_omissions" checked>
                        <label class="custom-control-label" for="opt_mark_omissions">Marcar omisiones (solo entrada o salida)</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="opt_recalculate" checked>
                        <label class="custom-control-label" for="opt_recalculate">Recalcular métricas (horas, tardanza, extras)</label>
                    </div>
                    <p class="text-warning mt-3"><i class="fas fa-exclamation-triangle"></i> Este proceso puede tomar varios minutos.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-cogs"></i> Sí, procesar',
            cancelButtonText: 'Cancelar',
            width: '600px'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

                $.ajax({
                    url: `${baseUrl}/panel/attendance/process-day`,
                    method: 'POST',
                    data: {
                        csrf_token: '<?= $csrf_token ?? '' ?>',
                        date: '<?= $date ?>',
                        tipo_planilla_id: tipoPlanillaId,
                        process_records: $('#opt_process_records').is(':checked') ? 1 : 0,
                        detect_absences: $('#opt_detect_absences').is(':checked') ? 1 : 0,
                        mark_omissions: $('#opt_mark_omissions').is(':checked') ? 1 : 0,
                        recalculate: $('#opt_recalculate').is(':checked') ? 1 : 0
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Procesamiento Completado!',
                                html: `
                                    <div class="text-left">
                                        <h5 class="mb-3">Resumen del Procesamiento:</h5>

                                        <div class="alert alert-info">
                                            <strong><i class="fas fa-user-times"></i> Ausencias:</strong><br>
                                            <ul class="mb-0 mt-1">
                                                <li>Detectadas: <strong>${response.data.absences_detected || 0}</strong></li>
                                                <li>Creadas: <strong>${response.data.absences_created || 0}</strong></li>
                                            </ul>
                                        </div>

                                        <div class="alert alert-warning">
                                            <strong><i class="fas fa-exclamation-triangle"></i> Omisiones:</strong><br>
                                            <ul class="mb-0 mt-1">
                                                <li>Detectadas: <strong>${response.data.omissions_detected || 0}</strong></li>
                                                <li>Marcadas: <strong>${response.data.omissions_marked || 0}</strong></li>
                                            </ul>
                                        </div>

                                        <div class="alert alert-success">
                                            <strong><i class="fas fa-calculator"></i> Cálculos:</strong><br>
                                            <ul class="mb-0 mt-1">
                                                <li>Procesadas: <strong>${response.data.calculations_processed || 0}</strong></li>
                                                <li>Guardadas: <strong>${response.data.calculations_saved || 0}</strong></li>
                                                <li>Errores: <strong class="text-danger">${response.data.calculations_errors || 0}</strong></li>
                                            </ul>
                                        </div>

                                        <div class="alert alert-primary">
                                            <strong><i class="fas fa-chart-bar"></i> Total:</strong><br>
                                            Empleados procesados: <strong>${response.data.total_employees || 0}</strong>
                                        </div>
                                    </div>
                                `,
                                icon: 'success',
                                confirmButtonText: 'Aceptar',
                                width: '700px'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message || 'Error al procesar marcaciones',
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error al procesar marcaciones del día';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        console.error('Error al procesar día:', xhr);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalHtml);
                    }
                });
            }
        });
    });

    // Procesar cálculos de todo el día (solo cálculos)
    $('.btn-process-all-calculations').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();

        Swal.fire({
            title: '¿Procesar cálculos del día completo?',
            text: 'Se calcularán las métricas de todas las marcaciones del día',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, procesar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

                $.ajax({
                    url: `${baseUrl}/panel/attendance/process-calculations`,
                    method: 'POST',
                    data: {
                        csrf_token: '<?= $csrf_token ?? '' ?>',
                        date: '<?= $date ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Éxito!',
                                html: `
                                    <p>${response.message}</p>
                                    <ul class="text-left">
                                        <li>Total Procesadas: <strong>${response.data.total_processed}</strong></li>
                                        <li>Guardadas: <strong class="text-success">${response.data.saved}</strong></li>
                                        <li>Errores: <strong class="text-danger">${response.data.errors}</strong></li>
                                    </ul>
                                `,
                                icon: 'success',
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Error al procesar cálculos', 'error');
                        console.error(xhr);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalHtml);
                    }
                });
            }
        });
    });

    // Mostrar modal con detalles de cálculo (todos los campos relevantes)
    function showCalculationDetails(data) {
        const fmtH = (v) => (v != null ? Number(v).toFixed(2) + 'h' : '-');
        const fmtM = (v) => (v != null ? Number(v).toFixed(0) + ' min' : '-');
        const fmtTime = (v) => v ? v : '-';
        const badge = (cond, yes = 'Sí', no = 'No') => cond ? `<span class="badge badge-success">${yes}</span>` : `<span class="badge badge-secondary">${no}</span>`;
        const statusBadge = () => {
            const isHoliday = !!data.is_holiday;
            const isWeekend = !!data.is_weekend;
            const isWorking = !!data.is_working_day;
            let chips = [];
            if (data.day_type) chips.push(`<span class="badge badge-info">${data.day_type}</span>`);
            if (isHoliday) chips.push('<span class="badge badge-danger">Feriado</span>');
            if (isWeekend && !isHoliday) chips.push('<span class="badge badge-warning">Fin de semana</span>');
            if (isWorking && !isHoliday) chips.push('<span class="badge badge-primary">Laboral</span>');
            return chips.join(' ');
        };

        const html = `
            <div class="text-left" style="max-height: 600px; overflow-y: auto;">

                <!-- SECCIÓN 1: TIPO DE DÍA -->
                <div class="card mb-3">
                    <div class="card-header bg-info">
                        <h6 class="mb-0"><i class="fas fa-calendar-day"></i> Tipo de Día</h6>
                    </div>
                    <div class="card-body">
                        ${statusBadge()}
                    </div>
                </div>

                <!-- SECCIÓN 2: DATOS DE MARCACIONES (attendance_detail) -->
                <div class="card mb-3">
                    <div class="card-header bg-primary">
                        <h6 class="mb-0"><i class="fas fa-fingerprint"></i> Marcaciones Registradas (attendance_detail)</h6>
                    </div>
                    <div class="card-body p-2">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th colspan="2" class="text-center">Marcaciones Reales</th>
                                    <th colspan="2" class="text-center">Horario Programado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Entrada:</strong></td>
                                    <td><span class="text-primary"><strong>${fmtTime(data.time_in)}</strong></span></td>
                                    <td><strong>Prog. Entrada:</strong></td>
                                    <td>${fmtTime(data.scheduled_time_in)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Salida Almuerzo:</strong></td>
                                    <td><span class="text-warning">${fmtTime(data.lunch_out)}</span></td>
                                    <td><strong>Prog. Salida Almuerzo:</strong></td>
                                    <td>${fmtTime(data.scheduled_lunch_out)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Entrada Almuerzo:</strong></td>
                                    <td><span class="text-success">${fmtTime(data.lunch_in)}</span></td>
                                    <td><strong>Prog. Entrada Almuerzo:</strong></td>
                                    <td>${fmtTime(data.scheduled_lunch_in)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Salida:</strong></td>
                                    <td><span class="text-danger"><strong>${fmtTime(data.time_out)}</strong></span></td>
                                    <td><strong>Prog. Salida:</strong></td>
                                    <td>${fmtTime(data.scheduled_time_out)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SECCIÓN 3: CÁLCULOS DE HORAS (attendance_calculations) -->
                <div class="card mb-3">
                    <div class="card-header bg-success">
                        <h6 class="mb-0"><i class="fas fa-clock"></i> Cálculos de Horas (attendance_calculations)</h6>
                    </div>
                    <div class="card-body p-2">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Concepto</th>
                                    <th class="text-right">Valor</th>
                                    <th>Concepto</th>
                                    <th class="text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Horas Totales:</strong></td>
                                    <td class="text-right"><span class="badge badge-primary">${fmtH(data.total_hours)}</span></td>
                                    <td><strong>Horas Regulares:</strong></td>
                                    <td class="text-right"><span class="badge badge-info">${fmtH(data.regular_hours)}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Horas Extras Total:</strong></td>
                                    <td class="text-right"><span class="badge badge-warning">${fmtH(data.overtime_hours)}</span></td>
                                    <td><strong>Horas Extras 25%:</strong></td>
                                    <td class="text-right"><span class="badge badge-warning">${fmtH(data.overtime_25_hours)}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Horas Extras 50%:</strong></td>
                                    <td class="text-right"><span class="badge badge-warning">${fmtH(data.overtime_50_hours)}</span></td>
                                    <td><strong>Horas Nocturnas:</strong></td>
                                    <td class="text-right"><span class="badge badge-dark">${fmtH(data.night_hours)}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Horas en Feriado:</strong></td>
                                    <td class="text-right"><span class="badge badge-danger">${fmtH(data.holiday_hours)}</span></td>
                                    <td><strong>Tiempo Almuerzo:</strong></td>
                                    <td class="text-right"><span class="badge badge-secondary">${fmtM(data.lunch_time_minutes)}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SECCIÓN 4: PUNTUALIDAD Y TARDANZAS (attendance_calculations) -->
                <div class="card mb-3">
                    <div class="card-header bg-warning">
                        <h6 class="mb-0"><i class="fas fa-user-clock"></i> Puntualidad y Tardanzas (attendance_calculations)</h6>
                    </div>
                    <div class="card-body p-2">
                        <table class="table table-sm table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <td width="35%"><strong>Tardanza:</strong></td>
                                    <td width="15%" class="text-right">
                                        ${data.tardiness_minutes > 0 ?
                                            `<span class="badge badge-danger">${fmtM(data.tardiness_minutes)}</span>` :
                                            `<span class="badge badge-success">0 min</span>`
                                        }
                                    </td>
                                    <td width="35%"><strong>Llegó Tarde:</strong></td>
                                    <td width="15%" class="text-right">${badge(!!data.is_late)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Salida Anticipada:</strong></td>
                                    <td class="text-right">
                                        ${data.early_departure_minutes > 0 ?
                                            `<span class="badge badge-warning">${fmtM(data.early_departure_minutes)}</span>` :
                                            `<span class="badge badge-success">0 min</span>`
                                        }
                                    </td>
                                    <td><strong>Exceso Almuerzo:</strong></td>
                                    <td class="text-right">
                                        ${data.lunch_exceeded_minutes > 0 ?
                                            `<span class="badge badge-warning">${fmtM(data.lunch_exceeded_minutes)}</span>` :
                                            `<span class="badge badge-success">0 min</span>`
                                        }
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Score de Puntualidad:</strong></td>
                                    <td class="text-right">
                                        <span class="badge badge-${(data.punctuality_score||0) >= 80 ? 'success' : ((data.punctuality_score||0) >= 50 ? 'warning' : 'danger')}">
                                            ${data.punctuality_score||0}%
                                        </span>
                                    </td>
                                    <td><strong>Asistencia Perfecta:</strong></td>
                                    <td class="text-right">
                                        ${data.is_perfect_attendance ?
                                            `<span class="badge badge-success"><i class="fas fa-star"></i> Sí</span>` :
                                            `<span class="badge badge-secondary">No</span>`
                                        }
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ausente:</strong></td>
                                    <td class="text-right">${badge(!!data.is_absent)}</td>
                                    <td><strong>Estado:</strong></td>
                                    <td class="text-right">
                                        <span class="badge badge-${data.is_absent ? 'danger' : 'success'}">
                                            ${data.is_absent ? 'Ausente' : 'Presente'}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SECCIÓN 5: INFORMACIÓN ADICIONAL -->
                ${data.notes || data.calculation_details ? `
                <div class="card mb-2">
                    <div class="card-header bg-secondary">
                        <h6 class="mb-0"><i class="fas fa-sticky-note"></i> Notas Adicionales</h6>
                    </div>
                    <div class="card-body p-2">
                        ${data.notes ? `<p class="mb-1"><strong>Notas:</strong> ${data.notes}</p>` : ''}
                        ${data.calculation_version ? `<p class="mb-1"><small class="text-muted">Versión de Cálculo: ${data.calculation_version}</small></p>` : ''}
                        ${data.calculated_at ? `<p class="mb-0"><small class="text-muted">Calculado: ${data.calculated_at}</small></p>` : ''}
                    </div>
                </div>
                ` : ''}

            </div>
        `;

        Swal.fire({
            title: '<i class="fas fa-calculator"></i> Detalle Completo de Marcación y Cálculos',
            html: html,
            icon: 'info',
            confirmButtonText: 'Cerrar',
            width: '900px',
            customClass: {
                container: 'calculation-details-modal'
            }
        });
    }

    // ========================================
    // EDITAR JUSTIFICACIÓN
    // ========================================

    // Abrir modal de edición de justificación con datos existentes
    $(document).on('click', '.btn-edit-justification', function() {
        const detailId = $(this).data('id');
        const $modal = $('#editJustificationModal');

        // Reset form y hidden fields
        $('#form-edit-justification')[0].reset();
        $('#edit_justification_detail_id').val(detailId);
        $('#edit_justification_remove_document').val('0');

        // Ocultar previews
        $('#edit_current_document_container').hide();
        $('#edit_pdf_preview_container').hide();
        $('#edit_current_pdf_embed').attr('src', '');
        $('#edit_pdf_embed').attr('src', '');
        $('#edit_document_label').text('Adjuntar Documento (PDF)');

        // Cargar datos de justificación existente
        $.ajax({
            url: `${baseUrl}/panel/attendance/detail/${detailId}/get-justification`,
            method: 'POST',
            data: {
                csrf_token: '<?= $csrf_token ?? '' ?>'
            },
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;

                    // Cargar tipo y notas
                    $('#edit_justify_type').val(data.justification_type || '');
                    $('#edit_justify_notes').val(data.justification_notes || '');

                    // Subtítulo con empleado y fecha
                    const employeeName = data.employee_name || '';
                    const employeeCode = data.employee_code || '';
                    const date = data.date || '';
                    $('#edit_justification_modal_subtitle').text(`${employeeName} (${employeeCode}) — ${date}`);

                    // Si existe documento PDF, mostrarlo
                    if (data.justification_document) {
                        const documentPath = `${baseUrl}/${data.justification_document}`;
                        const filename = data.justification_document.split('/').pop();

                        $('#edit_current_pdf_filename').text(filename);
                        $('#edit_current_pdf_embed').attr('src', documentPath);
                        $('#edit_current_document_container').show();
                        $('#edit_document_label').text('Reemplazar Documento (PDF)');
                    } else {
                        $('#edit_document_label').text('Adjuntar Documento (PDF)');
                    }

                    $modal.modal('show');
                } else {
                    toastr.error(response.message || 'No se pudo cargar la justificación');
                }
            },
            error: function(xhr) {
                toastr.error('Error al cargar la justificación');
                console.error(xhr);
            }
        });
    });

    // Botón para eliminar documento PDF actual
    $('#btn-remove-current-pdf').on('click', function() {
        Swal.fire({
            title: '¿Eliminar documento actual?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#edit_justification_remove_document').val('1');
                $('#edit_current_document_container').hide();
                $('#edit_document_label').text('Adjuntar Documento (PDF)');
                toastr.info('El documento será eliminado al guardar los cambios');
            }
        });
    });

    // Validar y mostrar preview del nuevo PDF en edición
    $('#edit_justify_document').on('change', function(e) {
        const file = e.target.files[0];
        const $label = $(this).next('.custom-file-label');

        if (!file) {
            $('#edit_pdf_preview_container').hide();
            $label.text('Seleccionar nuevo archivo PDF...');
            return;
        }

        // Validar tipo de archivo
        if (file.type !== 'application/pdf') {
            toastr.error('Solo se permiten archivos PDF');
            $(this).val('');
            $label.text('Seleccionar nuevo archivo PDF...');
            return;
        }

        // Validar tamaño (1MB = 1048576 bytes)
        if (file.size > 1048576) {
            toastr.error('El archivo no debe superar 1MB de tamaño');
            $(this).val('');
            $label.text('Seleccionar nuevo archivo PDF...');
            return;
        }

        // Actualizar label con nombre del archivo
        $label.text(file.name);

        // Mostrar información del archivo
        $('#edit_pdf_filename').text(file.name);
        $('#edit_pdf_filesize').text(' (' + (file.size / 1024).toFixed(2) + ' KB)');

        // Crear URL del archivo para preview
        const fileURL = URL.createObjectURL(file);
        $('#edit_pdf_embed').attr('src', fileURL);
        $('#edit_pdf_preview_container').show();
    });

    // Quitar nuevo archivo PDF seleccionado
    $('#btn-remove-edit-pdf').on('click', function() {
        $('#edit_justify_document').val('');
        $('#edit_justify_document').next('.custom-file-label').text('Seleccionar nuevo archivo PDF...');
        $('#edit_pdf_preview_container').hide();
        $('#edit_pdf_embed').attr('src', '');
    });

    // Submit del formulario de edición de justificación
    $('#form-edit-justification').on('submit', function(e) {
        e.preventDefault();

        const detailId = $('#edit_justification_detail_id').val();
        const formData = new FormData(this);

        // Validar tipo de justificación
        if (!$('#edit_justify_type').val()) {
            toastr.error('Debe seleccionar un tipo de justificación');
            return;
        }

        $.ajax({
            url: `${baseUrl}/panel/attendance/detail/${detailId}/update-justification`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#editJustificationModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Error al actualizar justificación';
                toastr.error(errorMsg);
            }
        });
    });

    // (Revertido) El modal existente se usa tras cálculo por botón Calcular
});
</script>

<style media="print">
    .main-sidebar, .main-header, .content-header .breadcrumb, .card-tools, .no-print, .btn-group {
        display: none !important;
    }
    .content-wrapper {
        margin-left: 0 !important;
    }
</style>
<?php
$scripts = ob_get_clean();
?>
