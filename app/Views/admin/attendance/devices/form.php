<?php
/**
 * Vista: Formulario de Dispositivo (Crear/Editar)
 * Ruta: /panel/attendance/devices/create | /panel/attendance/devices/{id}/edit
 */

$isEdit = isset($device) && $device;
$formAction = $isEdit ? "/panel/attendance/devices/{$device['id']}/update" : "/panel/attendance/devices/store";
?>



<!-- Main content -->
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-desktop mr-2"></i>
                            <?= $isEdit ? 'Editar' : 'Nuevo' ?> Dispositivo
                        </h3>
                    </div>
                    <form action="<?= $formAction ?>" method="POST" id="device-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

                        <div class="card-body">
                            <!-- Información Básica -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="device_code">Código del Dispositivo <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control"
                                               id="device_code"
                                               name="device_code"
                                               value="<?= htmlspecialchars($device['device_code'] ?? '') ?>"
                                               required
                                               maxlength="50">
                                        <small class="form-text text-muted">Código único del dispositivo</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="device_name">Nombre del Dispositivo <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control"
                                               id="device_name"
                                               name="device_name"
                                               value="<?= htmlspecialchars($device['device_name'] ?? '') ?>"
                                               required
                                               maxlength="100">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="device_type">Tipo de Dispositivo <span class="text-danger">*</span></label>
                                        <select class="form-control"
                                                id="device_type"
                                                name="device_type"
                                                required>
                                            <option value="">Seleccione...</option>
                                            <option value="API" <?= ($device['device_type'] ?? '') === 'API' ? 'selected' : '' ?>>
                                                API (Sincronización Externa)
                                            </option>
                                            <option value="BIOMETRIC" <?= ($device['device_type'] ?? '') === 'BIOMETRIC' ? 'selected' : '' ?>>
                                                Dispositivo Biométrico
                                            </option>
                                            <option value="TEXT_FILE" <?= ($device['device_type'] ?? '') === 'TEXT_FILE' ? 'selected' : '' ?>>
                                                Archivo de Texto (CSV, TXT)
                                            </option>
                                            <option value="MANUAL" <?= ($device['device_type'] ?? '') === 'MANUAL' ? 'selected' : '' ?>>
                                                Entrada Manual
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="location">Ubicación</label>
                                        <input type="text"
                                               class="form-control"
                                               id="location"
                                               name="location"
                                               value="<?= htmlspecialchars($device['location'] ?? '') ?>"
                                               maxlength="100">
                                        <small class="form-text text-muted">Ubicación física del dispositivo</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="is_active"
                                           name="is_active"
                                           <?= ($device['is_active'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="is_active">Dispositivo Activo</label>
                                </div>
                            </div>

                            <hr>

                            <!-- Configuración específica API -->
                            <div id="api-config" style="display: none;">
                                <h5 class="mb-3"><i class="fas fa-plug mr-2"></i>Configuración API</h5>

                                <div class="form-group">
                                    <label for="api_url">URL del API <span class="text-danger api-required">*</span></label>
                                    <input type="url"
                                           class="form-control"
                                           id="api_url"
                                           name="api_url"
                                           value="<?= htmlspecialchars($device['api_url'] ?? '') ?>"
                                           placeholder="https://api.example.com/attendance">
                                </div>

                                <div class="form-group">
                                    <label for="api_key">API Key</label>
                                    <input type="text"
                                           class="form-control"
                                           id="api_key"
                                           name="api_key"
                                           value="<?= htmlspecialchars($device['api_key'] ?? '') ?>"
                                           placeholder="Token o clave de autenticación">
                                </div>
                            </div>

                            <!-- Configuración específica TEXT_FILE -->
                            <div id="file-config" style="display: none;">
                                <h5 class="mb-3"><i class="fas fa-file-alt mr-2"></i>Configuración Archivo de Texto</h5>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="file_format">Formato de Archivo <span class="text-danger file-required">*</span></label>
                                            <select class="form-control" id="file_format" name="file_format">
                                                <option value="">Seleccione...</option>
                                                <option value="CSV" <?= ($device['file_format'] ?? 'CSV') === 'CSV' ? 'selected' : '' ?>>CSV</option>
                                                <option value="TXT" <?= ($device['file_format'] ?? '') === 'TXT' ? 'selected' : '' ?>>TXT</option>
                                                <option value="DAT" <?= ($device['file_format'] ?? '') === 'DAT' ? 'selected' : '' ?>>DAT</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="delimiter">Delimitador</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="delimiter"
                                                   name="delimiter"
                                                   value="<?= htmlspecialchars($device['delimiter'] ?? ',') ?>"
                                                   maxlength="10"
                                                   placeholder=",">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="date_format">Formato de Fecha</label>
                                            <select class="form-control" id="date_format" name="date_format">
                                                <option value="Y-m-d" <?= ($device['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD (2025-10-17)</option>
                                                <option value="d/m/Y" <?= ($device['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY (17/10/2025)</option>
                                                <option value="m/d/Y" <?= ($device['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY (10/17/2025)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="time_format">Formato de Hora</label>
                                            <select class="form-control" id="time_format" name="time_format">
                                                <option value="H:i:s" <?= ($device['time_format'] ?? 'H:i:s') === 'H:i:s' ? 'selected' : '' ?>>HH:MM:SS (14:30:00)</option>
                                                <option value="H:i" <?= ($device['time_format'] ?? '') === 'H:i' ? 'selected' : '' ?>>HH:MM (14:30)</option>
                                                <option value="h:i A" <?= ($device['time_format'] ?? '') === 'h:i A' ? 'selected' : '' ?>>hh:mm AM/PM (02:30 PM)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-6">
                                    <a href="/panel/attendance/devices" class="btn btn-default">
                                        <i class="fas fa-arrow-left"></i> Volver
                                    </a>
                                </div>
                                <div class="col-6 text-right">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?= $isEdit ? 'Actualizar' : 'Crear' ?> Dispositivo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
$(document).ready(function() {
    // Mostrar/ocultar campos según tipo de dispositivo
    function toggleDeviceTypeFields() {
        const deviceType = $('#device_type').val();

        // Ocultar todos los bloques de configuración
        $('#api-config, #file-config').hide();

        // Remover requerido de todos los campos específicos
        $('#api_url, #file_format').prop('required', false);

        // Mostrar campos específicos según tipo
        if (deviceType === 'API') {
            $('#api-config').show();
            $('#api_url').prop('required', true);
        } else if (deviceType === 'TEXT_FILE') {
            $('#file-config').show();
            $('#file_format').prop('required', true);
        }
    }

    // Ejecutar al cambiar el tipo
    $('#device_type').on('change', toggleDeviceTypeFields);

    // Ejecutar al cargar la página (para modo edición)
    toggleDeviceTypeFields();

    // Validación antes de enviar
    $('#device-form').on('submit', function(e) {
        const deviceType = $('#device_type').val();

        if (!deviceType) {
            e.preventDefault();
            toastr.error('Debe seleccionar un tipo de dispositivo');
            return false;
        }

        if (deviceType === 'API' && !$('#api_url').val()) {
            e.preventDefault();
            toastr.error('La URL del API es requerida para dispositivos tipo API');
            $('#api_url').focus();
            return false;
        }

        if (deviceType === 'TEXT_FILE' && !$('#file_format').val()) {
            e.preventDefault();
            toastr.error('El formato de archivo es requerido para dispositivos tipo TEXT_FILE');
            $('#file_format').focus();
            return false;
        }

        return true;
    });
});
</script>
