<?php
/**
 * Vista: Listado de Marcaciones por Día (Cabecera)
 * Ruta: /panel/attendance
 * REFACTORIZADO: Usa AttendanceHeader en lugar de Attendance
 */
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?= $page_title ?? 'Marcaciones de Asistencia' ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/panel/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Marcaciones</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <!-- Cards de Estadísticas -->
        <?php if (!empty($stats)): ?>
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $stats['total_days'] ?? 0 ?></h3>
                        <p>Días con Marcaciones</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= number_format($stats['total_employee_records'] ?? 0) ?></h3>
                        <p>Total Empleados Únicos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= number_format($stats['avg_punctuality'] ?? 0, 1) ?>%</h3>
                        <p>Promedio Puntualidad</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= number_format($stats['total_absent'] ?? 0) ?></h3>
                        <p>Total Ausencias</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
                        <form action="/panel/attendance" method="GET">
                            <div class="row">
                                <!-- Filtro por Año -->
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="year">Año:</label>
                                        <select class="form-control" id="year" name="year">
                                            <?php
                                            if (!empty($available_years)) {
                                                foreach ($available_years as $year_option) {
                                                    $selected = ($year_option == $current_year) ? 'selected' : '';
                                                    echo "<option value=\"{$year_option}\" {$selected}>{$year_option}</option>";
                                                }
                                            } else {
                                                echo "<option value=\"" . date('Y') . "\">" . date('Y') . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Filtro por Mes -->
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="month">Mes:</label>
                                        <select class="form-control" id="month" name="month">
                                            <?php
                                            $months = [
                                                '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo',
                                                '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
                                                '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre',
                                                '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
                                            ];
                                            foreach ($months as $num => $name) {
                                                $selected = ($num == $current_month) ? 'selected' : '';
                                                echo "<option value=\"{$num}\" {$selected}>{$name}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Filtro por Dispositivo -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="device_id">Dispositivo:</label>
                                        <select class="form-control" id="device_id" name="device_id">
                                            <option value="">Todos los dispositivos</option>
                                            <?php if (!empty($devices)): ?>
                                                <?php foreach ($devices as $device): ?>
                                                    <option value="<?= $device['id'] ?>"
                                                            <?= ($current_device_id == $device['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($device['device_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Filtro por Rango de Fechas -->
                                <div class="col-md-5">
                                    <label>O filtrar por rango de fechas:</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <input type="date" class="form-control" id="start_date" name="start_date"
                                                       value="<?= htmlspecialchars($start_date ?? '') ?>" placeholder="Fecha inicio">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <input type="date" class="form-control" id="end_date" name="end_date"
                                                       value="<?= htmlspecialchars($end_date ?? '') ?>" placeholder="Fecha fin">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                    <a href="/panel/attendance" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listado de Marcaciones por Día (Cabecera) -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Marcaciones por Día</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#detectAbsencesModal">
                                <i class="fas fa-user-times"></i> Detectar Ausencias
                            </button>
                            <a href="/panel/attendance/sync" class="btn btn-success btn-sm">
                                <i class="fas fa-sync-alt"></i> Sincronizar
                            </a>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Día</th>
                                    <th>Dispositivo</th>
                                    <th>Total Empleados</th>
                                    <th>Puntuales</th>
                                    <th>Tarde</th>
                                    <th>Ausentes</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($headers)): ?>
                                    <?php foreach ($headers as $header): ?>
                                        <?php
                                        $date = $header['attendance_date'];
                                        $timestamp = strtotime($date);
                                        $dayName = date('l', $timestamp);
                                        $dayNames = [
                                            'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
                                            'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
                                        ];
                                        $dayNameSpanish = $dayNames[$dayName] ?? $dayName;
                                        $dateFormatted = date('d/m/Y', $timestamp);
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= $dateFormatted ?></strong>
                                            </td>
                                            <td><?= $dayNameSpanish ?></td>
                                            <td>
                                                <?php if ($header['device_name']): ?>
                                                    <span class="badge badge-info">
                                                        <?= htmlspecialchars($header['device_name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?= $header['total_employees'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <?= $header['total_on_time'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $header['total_late'] > 0 ? 'warning' : 'secondary' ?>">
                                                    <?= $header['total_late'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $header['total_absent'] > 0 ? 'danger' : 'secondary' ?>">
                                                    <?= $header['total_absent'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($header['is_processed']): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> Procesado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-clock"></i> Pendiente
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/panel/attendance/detail/<?= $date ?>"
                                                       class="btn btn-info" title="Ver Detalle">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button"
                                                            class="btn btn-success btn-export"
                                                            data-date="<?= $date ?>"
                                                            title="Exportar Excel">
                                                        <i class="fas fa-file-excel"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-warning btn-reprocess"
                                                            data-id="<?= $header['id'] ?>"
                                                            title="Reprocesar">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            <i class="fas fa-info-circle"></i> No hay marcaciones registradas para el período seleccionado
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

<!-- Modal Detectar Ausencias -->
<div class="modal fade" id="detectAbsencesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-user-times"></i> Detectar Ausencias
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="form-detect-absences">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Este proceso detectará empleados sin marcación en días laborables y los registrará como ausencias.
                    </div>

                    <div class="form-group">
                        <label>Fecha Inicial:</label>
                        <input type="date" class="form-control" id="detect_start_date" name="start_date" required>
                    </div>

                    <div class="form-group">
                        <label>Fecha Final:</label>
                        <input type="date" class="form-control" id="detect_end_date" name="end_date" required>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="save_to_db" name="save_to_db" value="1" checked>
                            <label class="custom-control-label" for="save_to_db">
                                Guardar ausencias en base de datos
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-search"></i> Detectar Ausencias
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
$(document).ready(function() {
    // Si se ingresa fecha de rango, limpiar mes/año
    const startDate = $('#start_date');
    const endDate = $('#end_date');
    const year = $('#year');
    const month = $('#month');

    startDate.on('change', function() {
        if (this.value) {
            year.val('');
            month.val('');
        }
    });

    endDate.on('change', function() {
        if (this.value) {
            year.val('');
            month.val('');
        }
    });

    // Si se cambia mes/año, limpiar rango de fechas
    year.on('change', function() {
        if (this.value) {
            startDate.val('');
            endDate.val('');
        }
    });

    month.on('change', function() {
        if (this.value) {
            startDate.val('');
            endDate.val('');
        }
    });

    // Exportar a Excel
    $('.btn-export').on('click', function() {
        const date = $(this).data('date');
        window.location.href = `/panel/attendance/export-excel/${date}`;
    });

    // Reprocesar
    $('.btn-reprocess').on('click', function() {
        const headerId = $(this).data('id');

        Swal.fire({
            title: '¿Reprocesar marcaciones?',
            text: 'Se recalcularán las estadísticas del día',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, reprocesar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // TODO: Implementar endpoint de reprocesamiento
                toastr.info('Funcionalidad de reprocesamiento - En desarrollo');
            }
        });
    });

    // Detectar ausencias
    $('#form-detect-absences').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalHtml = submitBtn.html();

        // Validar fechas
        const startDate = $('#detect_start_date').val();
        const endDate = $('#detect_end_date').val();

        if (!startDate || !endDate) {
            toastr.error('Debe especificar ambas fechas');
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            toastr.error('La fecha inicial debe ser menor o igual a la fecha final');
            return;
        }

        // Confirmar acción
        Swal.fire({
            title: '¿Detectar Ausencias?',
            html: `
                <p>Se detectarán ausencias desde <strong>${startDate}</strong> hasta <strong>${endDate}</strong></p>
                <p class="text-muted">Este proceso puede tardar varios minutos dependiendo del rango de fechas.</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, detectar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

                $.ajax({
                    url: '/panel/attendance/detect-absences',
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Ausencias Detectadas!',
                                html: `
                                    <div class="text-left">
                                        <p><strong>${response.message}</strong></p>
                                        ${response.data ? `
                                            <ul class="mb-0">
                                                <li>Empleados procesados: <strong>${response.data.employees_processed || response.data.detected || 0}</strong></li>
                                                <li>Ausencias detectadas: <strong class="text-warning">${response.data.total_detected || response.data.detected || 0}</strong></li>
                                                <li>Guardadas en BD: <strong class="text-success">${response.data.total_saved || response.data.saved || 0}</strong></li>
                                            </ul>
                                        ` : ''}
                                    </div>
                                `,
                                icon: 'success',
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                $('#detectAbsencesModal').modal('hide');
                                form[0].reset();
                                // Recargar página para actualizar estadísticas
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Error al detectar ausencias', 'error');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error al detectar ausencias';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        console.error('Error al detectar ausencias:', xhr);
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalHtml);
                    }
                });
            }
        });
    });
});
</script>
