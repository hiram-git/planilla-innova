<?php
/**
 * Vista: Listado de Marcaciones por Día (Cabecera)
 * Ruta: /panel/attendance
 * REFACTORIZADO: Usa AttendanceHeader en lugar de Attendance
 */
?>



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
                        <p>Total Marcaciones por empleado</p>
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
                        <form action="<?= \App\Core\UrlHelper::route('/panel/attendance') ?>" method="GET">
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
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#processRangeModal">
                                <i class="fas fa-cogs"></i> Procesar Marcaciones
                            </button>
                            <a href="<?= \App\Core\UrlHelper::route('/panel/attendance/sync') ?>" class="btn btn-success btn-sm">
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
                                                    <a  href="<?= \App\Core\UrlHelper::panel('attendance/detail/' . $date ) ?>"
                                                       class="btn btn-info" title="Ver Detalle">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <!--<button type="button"
                                                            class="btn btn-success btn-export"
                                                            data-date="<?= $date ?>"
                                                            title="Exportar Excel">
                                                        <i class="fas fa-file-excel"></i>
                                                    </button>-->
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

<!-- Modal Procesar Marcaciones -->
<div class="modal fade" id="processRangeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-cogs"></i> Procesar Marcaciones por Rango
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="form-process-range">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Este proceso consolidará las marcaciones del rango seleccionado:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Procesa registros de attendance_records a attendance_detail</li>
                            <li>Detecta ausencias (empleados sin marcación)</li>
                            <li>Marca omisiones (solo entrada o solo salida)</li>
                            <li>Calcula métricas de asistencia (horas, tardanzas, extras)</li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <label for="process_date_range">Rango de Fechas:</label>
                        <input type="text" class="form-control" id="process_date_range" name="date_range" required readonly>
                        <input type="hidden" id="process_start_date" name="start_date">
                        <input type="hidden" id="process_end_date" name="end_date">
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Opciones de Procesamiento:</label>

                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="process_records" name="process_records" value="1" checked>
                            <label class="custom-control-label" for="process_records">
                                <i class="fas fa-database text-primary"></i> Procesar marcaciones
                            </label>
                        </div>

                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="detect_absences" name="detect_absences" value="1" checked>
                            <label class="custom-control-label" for="detect_absences">
                                <i class="fas fa-user-times text-danger"></i> Detectar ausencias (empleados sin marcación)
                            </label>
                        </div>

                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="mark_omissions" name="mark_omissions" value="1" checked>
                            <label class="custom-control-label" for="mark_omissions">
                                <i class="fas fa-exclamation-triangle text-warning"></i> Marcar omisiones (solo entrada o solo salida)
                            </label>
                        </div>

                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="recalculate" name="recalculate" value="1" checked>
                            <label class="custom-control-label" for="recalculate">
                                <i class="fas fa-calculator text-success"></i> Recalcular métricas (horas, tardanzas, extras)
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Advertencia:</strong> Este proceso puede tardar varios minutos dependiendo del rango de fechas seleccionado.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-cogs"></i> Procesar Rango
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Iniciar captura de scripts
ob_start();
?>
<script>
$(document).ready(function() {
    // Base URL del proyecto
    const baseUrl = '<?= \App\Core\UrlHelper::base() ?>';

    // Configurar moment.js en español
    moment.locale('es');

    // Inicializar daterangepicker para el modal de procesamiento
    const today = moment();
    const startOfMonth = moment().startOf('month');

    $('#process_date_range').daterangepicker({
        startDate: startOfMonth,
        endDate: today,
        locale: {
            format: 'DD/MM/YYYY',
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            fromLabel: 'Desde',
            toLabel: 'Hasta',
            customRangeLabel: 'Personalizado',
            weekLabel: 'S',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'],
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            firstDay: 1
        },
        opens: 'center',
        maxDate: today,
        ranges: {
            'Hoy': [moment(), moment()],
            'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
            'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
            'Este mes': [moment().startOf('month'), moment().endOf('month')],
            'Mes pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, function(start, end, label) {
        // Callback cuando se selecciona el rango
        $('#process_start_date').val(start.format('YYYY-MM-DD'));
        $('#process_end_date').val(end.format('YYYY-MM-DD'));
    });

    // Inicializar los campos hidden con los valores por defecto
    $('#process_start_date').val(startOfMonth.format('YYYY-MM-DD'));
    $('#process_end_date').val(today.format('YYYY-MM-DD'));

    // Resetear el daterangepicker cuando se cierra el modal
    $('#processRangeModal').on('hidden.bs.modal', function() {
        const today = moment();
        const startOfMonth = moment().startOf('month');

        $('#process_date_range').data('daterangepicker').setStartDate(startOfMonth);
        $('#process_date_range').data('daterangepicker').setEndDate(today);
        $('#process_start_date').val(startOfMonth.format('YYYY-MM-DD'));
        $('#process_end_date').val(today.format('YYYY-MM-DD'));

        // Resetear checkboxes
        $('#process_records, #detect_absences, #mark_omissions, #recalculate').prop('checked', true);
    });

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
        window.location.href = `${baseUrl}/panel/attendance/export-excel/${date}`;
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

    // Procesar marcaciones por rango
    $('#form-process-range').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalHtml = submitBtn.html();

        // Validar fechas
        const startDate = $('#process_start_date').val();
        const endDate = $('#process_end_date').val();

        if (!startDate || !endDate) {
            toastr.error('Debe especificar ambas fechas');
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            toastr.error('La fecha inicial debe ser menor o igual a la fecha final');
            return;
        }

        // Calcular días en el rango
        const daysDiff = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24)) + 1;

        // Formato de fechas para mostrar
        const startDateFormatted = moment(startDate).format('DD/MM/YYYY');
        const endDateFormatted = moment(endDate).format('DD/MM/YYYY');

        // Confirmar acción
        Swal.fire({
            title: '¿Procesar Marcaciones?',
            html: `
                <p>Se procesarán las marcaciones desde <strong>${startDateFormatted}</strong> hasta <strong>${endDateFormatted}</strong></p>
                <p class="text-info"><i class="fas fa-calendar-day"></i> Total de días: <strong>${daysDiff}</strong></p>
                <p class="text-muted">Este proceso puede tardar varios minutos. Se procesarán todos los días del rango seleccionado.</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-cogs"></i> Sí, procesar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

                $.ajax({
                    url: `${baseUrl}/panel/attendance/process-range`,
                    method: 'POST',
                    data: form.serialize(),
                    timeout: 600000, // 10 minutos timeout
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Procesamiento Completado!',
                                html: `
                                    <div class="text-left">
                                        <p><strong>${response.message}</strong></p>
                                        ${response.data ? `
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="text-primary">Procesamiento</h6>
                                                    <ul class="mb-0">
                                                        <li>Días procesados: <strong>${response.data.days_processed || 0}</strong></li>
                                                        <li>Días con errores: <strong class="text-danger">${response.data.days_with_errors || 0}</strong></li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-success">Resultados</h6>
                                                    <ul class="mb-0">
                                                        <li>Details actualizados: <strong>${response.data.total_details_updated || 0}</strong></li>
                                                        <li>Ausencias creadas: <strong class="text-danger">${response.data.total_absences_created || 0}</strong></li>
                                                        <li>Omisiones marcadas: <strong class="text-warning">${response.data.total_omissions_marked || 0}</strong></li>
                                                        <li>Cálculos guardados: <strong class="text-info">${response.data.total_calculations_saved || 0}</strong></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        ` : ''}
                                    </div>
                                `,
                                icon: 'success',
                                confirmButtonText: 'Aceptar',
                                width: '600px'
                            }).then(() => {
                                $('#processRangeModal').modal('hide');
                                form[0].reset();
                                // Recargar página para actualizar estadísticas
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Error al procesar marcaciones', 'error');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error al procesar marcaciones';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        console.error('Error al procesar marcaciones:', xhr);
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
<?php
$scripts = ob_get_clean();
?>
