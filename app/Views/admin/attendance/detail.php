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
                            <a href="<?= \App\Core\UrlHelper::panel('attendance') ?>" class="btn btn-tool" title="Volver al listado">
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
                            <button type="button" class="btn btn-warning btn-sm btn-process-all-calculations" title="Procesar cálculos de todo el día">
                                <i class="fas fa-calculator"></i> Procesar Cálculos Día
                            </button>
                            <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <a href="/panel/attendance/export-excel/<?= $date ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-file-excel"></i> Exportar Excel
                            </a>
                            <a href="/panel/attendance/export-pdf/<?= $date ?>" class="btn btn-danger btn-sm">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-striped" id="attendanceDetailTable">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Horario</th>
                                    <th>Hora Entrada</th>
                                    <th>Hora Salida</th>
                                    <th>Tardanza</th>
                                    <th>Horas Trabajadas</th>
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

                                        // Indicador de cálculo procesado
                                        $punctualityDisplay = '';
                                        if (!empty($detail['calculation'])) {
                                            $calc = $detail['calculation'];
                                            $score = $calc['punctuality_score'] ?? 0;
                                            $isPerfect = $calc['is_perfect_attendance'] ?? 0;

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
                                            <td><?= $timeOut ?></td>
                                            <td><?= $tardiness ?></td>
                                            <td><?= $hoursWorked ?></td>
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
                                                                title="Justificar">
                                                            <i class="fas fa-file-alt"></i>
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
                                        <td colspan="8" class="text-center text-muted">
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

<!-- Modal Editar Marcación -->
<div class="modal fade" id="editDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Marcación</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="form-edit-detail">
                <input type="hidden" id="edit_detail_id" name="detail_id">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Hora Entrada:</label>
                        <input type="time" class="form-control" id="edit_time_in" name="time_in">
                    </div>
                    <div class="form-group">
                        <label>Hora Salida:</label>
                        <input type="time" class="form-control" id="edit_time_out" name="time_out">
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

<!-- Scripts -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    // DataTable
    $('#attendanceDetailTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[0, 'asc']],
        pageLength: 50
    });

    // Editar marcación
    $(document).on('click', '.btn-edit-detail', function() {
        const detailId = $(this).data('id');
        // TODO: Cargar datos actuales de la marcación
        $('#edit_detail_id').val(detailId);
        $('#editDetailModal').modal('show');
    });

    $('#form-edit-detail').on('submit', function(e) {
        e.preventDefault();
        const detailId = $('#edit_detail_id').val();

        $.ajax({
            url: `/panel/attendance/detail/${detailId}/update`,
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

    // Justificar ausencia
    $(document).on('click', '.btn-justify', function() {
        const detailId = $(this).data('id');

        Swal.fire({
            title: 'Justificar Ausencia',
            html: `
                <select id="justify-type" class="swal2-input">
                    <option value="MEDICAL">Médica</option>
                    <option value="PERMISSION">Permiso</option>
                    <option value="VACATION">Vacaciones</option>
                    <option value="OTHER">Otro</option>
                </select>
                <textarea id="justify-notes" class="swal2-textarea" placeholder="Notas de justificación..."></textarea>
            `,
            showCancelButton: true,
            confirmButtonText: 'Justificar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                return {
                    type: document.getElementById('justify-type').value,
                    notes: document.getElementById('justify-notes').value
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/panel/attendance/detail/${detailId}/justify`,
                    method: 'POST',
                    data: {
                        csrf_token: '<?= $csrf_token ?? '' ?>',
                        justification_type: result.value.type,
                        justification_notes: result.value.notes
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('Error al justificar ausencia');
                    }
                });
            }
        });
    });

    // Eliminar marcación
    $(document).on('click', '.btn-delete-detail', function() {
        const detailId = $(this).data('id');

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
            if (result.isConfirmed) {
                $.ajax({
                    url: `/panel/attendance/detail/${detailId}/delete`,
                    method: 'POST',
                    data: {
                        csrf_token: '<?= $csrf_token ?? '' ?>'
                    },
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
            url: `/panel/attendance/detail/${detailId}/calculate`,
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
                                    ${score}%
                                </span>`;

                    if (isPerfect) {
                        html += ' <i class="fas fa-star text-warning" title="Asistencia Perfecta"></i>';
                    }

                    scoreCell.html(html);

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

    // Procesar cálculos de todo el día
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
                    url: '/panel/attendance/process-calculations',
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

    // Mostrar modal con detalles de cálculo
    function showCalculationDetails(data) {
        const overtimeColor = data.overtime_hours > 0 ? 'text-primary' : 'text-muted';
        const lateColor = data.is_late ? 'text-danger' : 'text-success';

        Swal.fire({
            title: '<i class="fas fa-chart-bar"></i> Métricas Calculadas',
            html: `
                <div class="text-left">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Horas Totales:</strong></td>
                            <td>${data.total_hours}h</td>
                        </tr>
                        <tr>
                            <td><strong>Horas Extras:</strong></td>
                            <td class="${overtimeColor}">${data.overtime_hours}h</td>
                        </tr>
                        <tr>
                            <td><strong>Tardanza:</strong></td>
                            <td class="${lateColor}">${data.tardiness_minutes} min</td>
                        </tr>
                        <tr>
                            <td><strong>Score de Puntualidad:</strong></td>
                            <td>
                                <span class="badge badge-${data.punctuality_score >= 80 ? 'success' : (data.punctuality_score >= 50 ? 'warning' : 'danger')}">
                                    ${data.punctuality_score}%
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Asistencia Perfecta:</strong></td>
                            <td>${data.is_perfect_attendance ? '<i class="fas fa-check text-success"></i> Sí' : '<i class="fas fa-times text-danger"></i> No'}</td>
                        </tr>
                    </table>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Cerrar',
            width: '500px'
        });
    }
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
