<?php
/**
 * Vista: Control de Sincronización con 3 Tabs
 * Ruta: /panel/attendance/sync
 * REFACTORIZADO: Tabs para API, Archivo Texto, Manual
 */
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?= $page_title ?? 'Control de Sincronización' ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/panel/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/panel/attendance">Asistencia</a></li>
                    <li class="breadcrumb-item active">Sincronizar</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <!-- Panel de Estado (Sidebar) -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success"><i class="fas fa-clock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Última Sincronización</span>
                                        <span class="info-box-number">
                                            <?php
                                            if (!empty($recent_syncs) && isset($recent_syncs[0])) {
                                                echo date('d/m/Y H:i', strtotime($recent_syncs[0]['start_time']));
                                            } else {
                                                echo 'Nunca';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-check-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Estado Actual</span>
                                        <span class="info-box-number">
                                            <?php
                                            if (!empty($recent_syncs) && isset($recent_syncs[0])) {
                                                $status = $recent_syncs[0]['status'];
                                                echo $status === 'SUCCESS' ? 'Exitoso' : ($status === 'FAILED' ? 'Fallido' : $status);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-database"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Registros Procesados Hoy</span>
                                        <span class="info-box-number">
                                            <?php
                                            $todayRecords = 0;
                                            if (!empty($recent_syncs)) {
                                                foreach ($recent_syncs as $sync) {
                                                    if (date('Y-m-d', strtotime($sync['start_time'])) === date('Y-m-d')) {
                                                        $todayRecords += $sync['records_inserted'] ?? 0;
                                                    }
                                                }
                                            }
                                            echo $todayRecords;
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <a href="/panel/attendance/sync-history" class="btn btn-info btn-block">
                                    <i class="fas fa-history"></i> Ver Historial Completo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs de Sincronización -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary card-tabs">
                    <div class="card-header p-0 pt-1">
                        <ul class="nav nav-tabs" id="sync-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="api-tab" data-toggle="pill" href="#tab-api" role="tab">
                                    <i class="fas fa-plug"></i> Sincronización API
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="file-tab" data-toggle="pill" href="#tab-file" role="tab">
                                    <i class="fas fa-file-upload"></i> Importar Archivo
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="manual-tab" data-toggle="pill" href="#tab-manual" role="tab">
                                    <i class="fas fa-keyboard"></i> Registro Manual
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="sync-tabsContent">

                            <!-- TAB 1: Sincronización API -->
                            <div class="tab-pane fade show active" id="tab-api" role="tabpanel">
                                <h4 class="mb-3"><i class="fas fa-sync-alt"></i> Sincronizar desde API Externa</h4>

                                <?php if (empty($api_devices)): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        No hay dispositivos API configurados.
                                        <a href="/panel/attendance/devices/create" class="alert-link">Crear dispositivo API</a>
                                    </div>
                                <?php else: ?>

                                    <div class="row">
                                        <!-- Selector de Dispositivo -->
                                        <div class="col-md-12 mb-3">
                                            <div class="form-group">
                                                <label for="api_device_id">Dispositivo API:</label>
                                                <select class="form-control" id="api_device_id">
                                                    <?php foreach ($api_devices as $device): ?>
                                                        <option value="<?= $device['id'] ?>">
                                                            <?= htmlspecialchars($device['device_name']) ?> (<?= htmlspecialchars($device['device_code']) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Botones de Sincronización -->
                                        <div class="col-md-4">
                                            <div class="info-box bg-success clickable" data-sync-type="full">
                                                <span class="info-box-icon"><i class="fas fa-sync-alt"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Sincronización Completa</span>
                                                    <span class="info-box-number">Todos los registros</span>
                                                    <button type="button" class="btn btn-light btn-sm mt-2 btn-sync-api" data-type="full">
                                                        <i class="fas fa-play"></i> Sincronizar Todo
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="info-box bg-info">
                                                <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Día Actual</span>
                                                    <span class="info-box-number"><?= date('d/m/Y') ?></span>
                                                    <button type="button" class="btn btn-light btn-sm mt-2 btn-sync-api" data-type="today">
                                                        <i class="fas fa-play"></i> Sincronizar Hoy
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="info-box bg-primary">
                                                <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Rango de Fechas</span>
                                                    <span class="info-box-number">Personalizado</span>
                                                    <button type="button" class="btn btn-light btn-sm mt-2" data-toggle="modal" data-target="#dateRangeModal">
                                                        <i class="fas fa-calendar"></i> Seleccionar Rango
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Nota:</strong> La sincronización puede tardar varios minutos dependiendo de la cantidad de registros.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- TAB 2: Importar Archivo Texto -->
                            <div class="tab-pane fade" id="tab-file" role="tabpanel">
                                <h4 class="mb-3"><i class="fas fa-file-upload"></i> Importar Archivo de Texto</h4>

                                <?php if (empty($file_devices)): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        No hay dispositivos de archivo de texto configurados.
                                        <a href="/panel/attendance/devices/create" class="alert-link">Crear dispositivo TEXT_FILE</a>
                                    </div>
                                <?php else: ?>

                                    <form id="form-import-file" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

                                        <div class="form-group">
                                            <label for="file_device_id">Dispositivo:</label>
                                            <select class="form-control" id="file_device_id" name="device_id" required>
                                                <option value="">Seleccione un dispositivo...</option>
                                                <?php foreach ($file_devices as $device): ?>
                                                    <option value="<?= $device['id'] ?>"
                                                            data-format="<?= htmlspecialchars($device['file_format'] ?? 'CSV') ?>"
                                                            data-delimiter="<?= htmlspecialchars($device['delimiter'] ?? ',') ?>">
                                                        <?= htmlspecialchars($device['device_name']) ?> (<?= htmlspecialchars($device['file_format'] ?? 'CSV') ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="attendance_file">Archivo:</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="attendance_file" name="file"
                                                       accept=".txt,.csv,.dat" required>
                                                <label class="custom-file-label" for="attendance_file">Seleccionar archivo...</label>
                                            </div>
                                            <small class="form-text text-muted">
                                                Formatos aceptados: .txt, .csv, .dat (máximo 10MB)
                                            </small>
                                        </div>

                                        <div id="file-preview" class="mt-3" style="display: none;">
                                            <h5>Vista Previa (primeras 10 líneas)</h5>
                                            <pre id="file-preview-content" class="bg-light p-3 border"></pre>
                                        </div>

                                        <div class="text-right">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload"></i> Importar Archivo
                                            </button>
                                        </div>
                                    </form>

                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Instrucciones:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Seleccione el dispositivo que corresponde al formato del archivo</li>
                                            <li>El sistema mapeará las columnas automáticamente según la configuración del dispositivo</li>
                                            <li>Se validarán las fechas y horas antes de importar</li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- TAB 3: Registro Manual -->
                            <div class="tab-pane fade" id="tab-manual" role="tabpanel">
                                <h4 class="mb-3"><i class="fas fa-keyboard"></i> Registro Manual de Marcación</h4>

                                <form id="form-manual-entry" method="POST" action="/panel/attendance/manual-entry">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="employee_id">Empleado: <span class="text-danger">*</span></label>
                                                <select class="form-control select2" id="employee_id" name="employee_id" required>
                                                    <option value="">Seleccione un empleado...</option>
                                                    <!-- TODO: Cargar empleados dinámicamente -->
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="attendance_date">Fecha: <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="attendance_date" name="date"
                                                       value="<?= date('Y-m-d') ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="time_in">Hora de Entrada:</label>
                                                <input type="time" class="form-control" id="time_in" name="time_in">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="time_out">Hora de Salida:</label>
                                                <input type="time" class="form-control" id="time_out" name="time_out">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="notes">Notas:</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                                  placeholder="Observaciones adicionales..."></textarea>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Guardar Marcación
                                        </button>
                                    </div>
                                </form>

                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Importante:</strong> El registro manual debe usarse únicamente en casos excepcionales.
                                    Todas las marcaciones manuales quedarán registradas en el sistema con trazabilidad.
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Rango de Fechas -->
<div class="modal fade" id="dateRangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title"><i class="fas fa-calendar-alt"></i> Sincronizar por Rango de Fechas</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="start_date">Fecha Inicio:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= date('Y-m-01') ?>">
                </div>

                <div class="form-group">
                    <label for="end_date">Fecha Fin:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sync-daterange">
                    <i class="fas fa-sync-alt"></i> Sincronizar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
$(document).ready(function() {
    // Select2 para empleados
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: 'Seleccione un empleado...'
    });

    // Sincronización API
    $('.btn-sync-api').on('click', function() {
        const syncType = $(this).data('type');
        const deviceId = $('#api_device_id').val();
        const btn = $(this);

        if (!deviceId) {
            toastr.error('Debe seleccionar un dispositivo');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sincronizando...');

        $.ajax({
            url: '/panel/attendance/sync-now',
            method: 'POST',
            data: {
                csrf_token: '<?= $csrf_token ?? '' ?>',
                sync_type: syncType,
                device_id: deviceId
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    toastr.warning(response.message);
                }
            },
            error: function() {
                toastr.error('Error al ejecutar sincronización');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-play"></i> ' + (syncType === 'full' ? 'Sincronizar Todo' : 'Sincronizar Hoy'));
            }
        });
    });

    // Sincronización por rango de fechas
    $('.btn-sync-daterange').on('click', function() {
        const deviceId = $('#api_device_id').val();
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        if (!deviceId) {
            toastr.error('Debe seleccionar un dispositivo');
            return;
        }

        if (!startDate || !endDate) {
            toastr.error('Debe seleccionar ambas fechas');
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            toastr.error('La fecha de inicio no puede ser mayor que la fecha fin');
            return;
        }

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sincronizando...');

        $.ajax({
            url: '/panel/attendance/sync-now',
            method: 'POST',
            data: {
                csrf_token: '<?= $csrf_token ?? '' ?>',
                sync_type: 'daterange',
                device_id: deviceId,
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                $('#dateRangeModal').modal('hide');
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    toastr.warning(response.message);
                }
            },
            error: function() {
                toastr.error('Error al ejecutar sincronización');
            },
            complete: function() {
                $('.btn-sync-daterange').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Sincronizar');
            }
        });
    });

    // Preview de archivo
    $('#attendance_file').on('change', function() {
        const file = this.files[0];
        if (file) {
            $('.custom-file-label').text(file.name);

            // Leer primeras líneas
            const reader = new FileReader();
            reader.onload = function(e) {
                const text = e.target.result;
                const lines = text.split('\n').slice(0, 10).join('\n');
                $('#file-preview-content').text(lines);
                $('#file-preview').show();
            };
            reader.readAsText(file);
        }
    });

    // Importar archivo
    $('#form-import-file').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        $.ajax({
            url: '/panel/attendance/import-file',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#form-import-file')[0].reset();
                    $('#file-preview').hide();
                } else {
                    toastr.warning(response.message);
                }
            },
            error: function() {
                toastr.error('Error al importar archivo');
            }
        });
    });
});
</script>
