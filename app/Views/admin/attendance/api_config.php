

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Alertas -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="icon fas fa-check"></i> <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="icon fas fa-ban"></i> <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="icon fas fa-exclamation-triangle"></i> <?= $_SESSION['warning'] ?>
            </div>
            <?php unset($_SESSION['warning']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Estadísticas -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $data['sync_stats']['total_syncs'] ?? 0 ?></h3>
                        <p>Sincronizaciones (7 días)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $data['sync_stats']['successful_syncs'] ?? 0 ?></h3>
                        <p>Exitosas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $data['sync_stats']['total_records_inserted'] ?? 0 ?></h3>
                        <p>Registros Insertados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= $data['sync_stats']['failed_syncs'] ?? 0 ?></h3>
                        <p>Fallos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 1: CONFIGURACIÓN API -->
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cog"></i> Configuración API</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <form action="<?= \App\Core\UrlHelper::route('/panel/attendance-api-config/save') ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">

                        <div class="card-body">
                            <div class="form-group">
                                <label for="api_provider">Proveedor API</label>
                                <input type="text" class="form-control" id="api_provider" name="api_provider"
                                       value="<?= $data['config']['api_provider'] ?? 'api' ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label for="api_key">API Key <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($_SESSION['errors']['api_key']) ? 'is-invalid' : '' ?>"
                                       id="api_key" name="api_key"
                                       value="<?= $_SESSION['old_data']['api_key'] ?? $data['config']['api_key'] ?? '' ?>"
                                       required>
                                <?php if (isset($_SESSION['errors']['api_key'])): ?>
                                    <span class="invalid-feedback"><?= $_SESSION['errors']['api_key'] ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="app_id">App ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($_SESSION['errors']['app_id']) ? 'is-invalid' : '' ?>"
                                       id="app_id" name="app_id"
                                       value="<?= $_SESSION['old_data']['app_id'] ?? $data['config']['app_id'] ?? '' ?>"
                                       required>
                                <?php if (isset($_SESSION['errors']['app_id'])): ?>
                                    <span class="invalid-feedback"><?= $_SESSION['errors']['app_id'] ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="api_url">API URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control <?= isset($_SESSION['errors']['api_url']) ? 'is-invalid' : '' ?>"
                                       id="api_url" name="api_url"
                                       value="<?= $_SESSION['old_data']['api_url'] ?? $data['config']['api_url'] ?? 'https://app.api.com/api' ?>"
                                       required>
                                <?php if (isset($_SESSION['errors']['api_url'])): ?>
                                    <span class="invalid-feedback"><?= $_SESSION['errors']['api_url'] ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="sync_interval_minutes">Intervalo de Sincronización (minutos)</label>
                                <input type="number" class="form-control"
                                       id="sync_interval_minutes" name="sync_interval_minutes"
                                       value="<?= $data['config']['sync_interval_minutes'] ?? 15 ?>"
                                       min="1" max="1440">
                                <small class="form-text text-muted">Mínimo: 1 minuto, Máximo: 1440 minutos (24 horas)</small>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="sync_enabled" name="sync_enabled"
                                           <?= (!empty($data['config']['sync_enabled'])) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="sync_enabled">Sincronización Automática Habilitada</label>
                                </div>
                            </div>

                            <hr>

                            <div class="form-group">
                                <label for="webhook_url">Webhook URL (opcional)</label>
                                <input type="url" class="form-control" id="webhook_url" name="webhook_url"
                                       value="<?= $data['config']['webhook_url'] ?? '' ?>"
                                       placeholder="https://tu-dominio.com/webhooks/api/attendance">
                                <small class="form-text text-muted">URL para recibir notificaciones en tiempo real</small>
                            </div>

                            <div class="form-group">
                                <label for="webhook_secret">Webhook Secret (opcional)</label>
                                <input type="text" class="form-control" id="webhook_secret" name="webhook_secret"
                                       value="<?= $data['config']['webhook_secret'] ?? '' ?>"
                                       placeholder="Secret para validar webhooks">
                            </div>

                            <hr>

                            <div class="form-group">
                                <label for="config_json">Parámetros de Configuración Adicionales (JSON)</label>
                                <?php
                                // Limpiar y validar el JSON antes de mostrarlo
                                $configJsonValue = $_SESSION['old_data']['config_json'] ?? $data['config']['config_json'] ?? '';
                                if (!empty($configJsonValue)) {
                                    $trimmed = trim($configJsonValue);
                                    // Intentar decodificar para verificar si es válido
                                    $decoded = @json_decode($trimmed, true);
                                    if (json_last_error() !== JSON_ERROR_NONE) {
                                        // Si no es JSON válido, mostrar campo vacío
                                        $configJsonValue = '';
                                    } else {
                                        // Si es válido, formatearlo bonito
                                        $configJsonValue = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                    }
                                }
                                ?>
                                <textarea class="form-control <?= isset($_SESSION['errors']['config_json']) ? 'is-invalid' : '' ?>"
                                          id="config_json" name="config_json"
                                          rows="6"
                                          placeholder='{"parametro1": "valor1", "parametro2": "valor2"}'><?= htmlspecialchars($configJsonValue) ?></textarea>
                                <small class="form-text text-muted">
                                    Configuraciones adicionales en formato JSON (opcional).
                                    Ejemplo: {"timeout": 30, "retry_attempts": 3, "debug_mode": false}
                                    <br><strong>Dejar vacío si no necesita configuración adicional.</strong>
                                </small>
                                <?php if (isset($_SESSION['errors']['config_json'])): ?>
                                    <span class="invalid-feedback"><?= $_SESSION['errors']['config_json'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                            <button type="button" class="btn btn-info" id="btnTestConnection">
                                <i class="fas fa-plug"></i> Probar Conexión
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: HISTORIAL DE SINCRONIZACIONES (Más recientes primero) -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history"></i> Historial de Sincronizaciones (Últimas 20)</h3>
                        <div class="card-tools">
                            <form action="<?= \App\Core\UrlHelper::route('/panel/attendance-api-config/clean-logs') ?>" method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                                <input type="hidden" name="days" value="30">
                                <button type="submit" class="btn btn-tool" title="Limpiar logs > 30 días"
                                        onclick="return confirm('¿Eliminar logs de hace más de 30 días?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Inicio</th>
                                    <th>Duración</th>
                                    <th>Obtenidos</th>
                                    <th>Insertados</th>
                                    <th>Actualizados</th>
                                    <th>Errores</th>
                                    <th>Estado</th>
                                    <th>Origen</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($data['sync_logs'])): ?>
                                    <?php foreach ($data['sync_logs'] as $log): ?>
                                        <tr>
                                            <td><?= $log['id'] ?></td>
                                            <td><span class="badge badge-info"><?= $log['sync_type'] ?></span></td>
                                            <td><?= date('d/m/Y H:i', strtotime($log['start_time'])) ?></td>
                                            <td><?= $log['duration_seconds'] ? $log['duration_seconds'] . 's' : '-' ?></td>
                                            <td><?= $log['records_fetched'] ?></td>
                                            <td><?= $log['records_inserted'] ?></td>
                                            <td><?= $log['records_updated'] ?></td>
                                            <td>
                                                <?php if ($log['errors_count'] > 0): ?>
                                                    <span class="badge badge-danger"><?= $log['errors_count'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusBadge = [
                                                    'SUCCESS' => 'success',
                                                    'FAILED' => 'danger',
                                                    'PARTIAL' => 'warning',
                                                    'RUNNING' => 'info'
                                                ];
                                                $badgeClass = $statusBadge[$log['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge badge-<?= $badgeClass ?>"><?= $log['status'] ?></span>
                                            </td>
                                            <td><small><?= $log['triggered_by'] ?></small></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info btn-view-log" data-log-id="<?= $log['id'] ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">No hay logs de sincronización</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Modal Detalles de Log -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Detalles de Sincronización</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <p>Cargando...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
unset($_SESSION['errors']);
unset($_SESSION['old_data']);
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validar JSON de configuración antes de enviar el formulario
    const configForm = document.querySelector('form[action*="attendance-api-config/save"]');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            const configJsonField = document.getElementById('config_json');
            const jsonValue = configJsonField.value.trim();

            if (jsonValue) {
                try {
                    JSON.parse(jsonValue);
                    // JSON válido
                } catch (error) {
                    e.preventDefault();
                    alert('Error en JSON de configuración: ' + error.message);
                    configJsonField.focus();
                    return false;
                }
            }
        });
    }

    // Probar conexión
    document.getElementById('btnTestConnection').addEventListener('click', function() {
        const btn = this;
        const apiKey = document.getElementById('api_key').value;
        const appId = document.getElementById('app_id').value;
        const apiUrl = document.getElementById('api_url').value;
        const csrfToken = '<?= $data['csrf_token'] ?>';

        if (!apiKey || !appId || !apiUrl) {
            alert('Complete todos los campos requeridos');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';

        fetch('<?= \App\Core\UrlHelper::route('/panel/attendance-api-config/test-connection') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `csrf_token=${encodeURIComponent(csrfToken)}&api_key=${encodeURIComponent(apiKey)}&app_id=${encodeURIComponent(appId)}&api_url=${encodeURIComponent(apiUrl)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug"></i> Probar Conexión';
        });
    });

    // Ver detalles de log
    document.querySelectorAll('.btn-view-log').forEach(btn => {
        btn.addEventListener('click', function() {
            const logId = this.dataset.logId;
            const csrfToken = '<?= $data['csrf_token'] ?>';

            fetch('<?= \App\Core\UrlHelper::route('/panel/attendance-api-config/log-details') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=${encodeURIComponent(csrfToken)}&log_id=${logId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const log = data.log;
                    let html = `
                        <dl class="row">
                            <dt class="col-sm-4">ID:</dt><dd class="col-sm-8">${log.id}</dd>
                            <dt class="col-sm-4">Tipo:</dt><dd class="col-sm-8"><span class="badge badge-info">${log.sync_type}</span></dd>
                            <dt class="col-sm-4">Estado:</dt><dd class="col-sm-8"><span class="badge badge-${log.status === 'SUCCESS' ? 'success' : 'danger'}">${log.status}</span></dd>
                            <dt class="col-sm-4">Inicio:</dt><dd class="col-sm-8">${log.start_time}</dd>
                            <dt class="col-sm-4">Fin:</dt><dd class="col-sm-8">${log.end_time || 'N/A'}</dd>
                            <dt class="col-sm-4">Duración:</dt><dd class="col-sm-8">${log.duration_seconds || 0} segundos</dd>
                            <dt class="col-sm-4">Registros Obtenidos:</dt><dd class="col-sm-8">${log.records_fetched}</dd>
                            <dt class="col-sm-4">Registros Insertados:</dt><dd class="col-sm-8">${log.records_inserted}</dd>
                            <dt class="col-sm-4">Registros Actualizados:</dt><dd class="col-sm-8">${log.records_updated}</dd>
                            <dt class="col-sm-4">Registros Omitidos:</dt><dd class="col-sm-8">${log.records_skipped}</dd>
                            <dt class="col-sm-4">Errores:</dt><dd class="col-sm-8">${log.errors_count}</dd>
                            <dt class="col-sm-4">Mensaje:</dt><dd class="col-sm-8">${log.status_message || 'N/A'}</dd>
                        </dl>
                    `;

                    if (log.errors && log.errors.length > 0) {
                        html += '<hr><h6>Detalles de Errores:</h6><ul>';
                        log.errors.forEach(error => {
                            html += `<li class="text-danger"><small>${error}</small></li>`;
                        });
                        html += '</ul>';
                    }

                    document.getElementById('logDetailsContent').innerHTML = html;
                    $('#logDetailsModal').modal('show');
                } else {
                    alert('Error al cargar detalles: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });
    });
});
</script>
