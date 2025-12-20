<!-- Main content -->
<div class="container-fluid">

    <?php if (isset($_SESSION['success'])): ?>
        <div class="callout callout-success">
            <h5><i class="fas fa-check-circle"></i> Importación Exitosa</h5>
            <p><?= $_SESSION['success'] ?></p>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="callout callout-danger">
            <h5><i class="fas fa-exclamation-triangle"></i> Error de Importación</h5>
            <p><?= $_SESSION['error'] ?></p>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['import_errors'])): ?>
        <div class="callout callout-warning">
            <h5><i class="fas fa-exclamation-triangle"></i> Errores encontrados</h5>
            <ul class="mb-0">
                <?php foreach ($_SESSION['import_errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php unset($_SESSION['import_errors']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Instrucciones -->
        <div class="col-md-6">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Instrucciones</h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="time-label">
                            <span class="bg-info">Proceso</span>
                        </div>

                        <div>
                            <i class="fas fa-download bg-green"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-clock"></i> Paso 1</span>
                                <h3 class="timeline-header">
                                    <a href="<?= \App\Core\UrlHelper::route('panel/accumulated/import/template') ?>" class="btn btn-success btn-sm mr-2">
                                        <i class="fas fa-sync"></i> Plantilla Dinámica
                                    </a>
                                </h3>
                                <div class="timeline-body">
                                    Descargue la plantilla con los catálogos actualizados de empleados, conceptos, frecuencias y tipos de acumulado.
                                </div>
                            </div>
                        </div>

                        <div>
                            <i class="fas fa-edit bg-blue"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-clock"></i> Paso 2</span>
                                <h3 class="timeline-header">Completar Datos</h3>
                                <div class="timeline-body">
                                    <ul>
                                        <li>Complete: Código Empleado, Código Concepto, Monto, Mes (1-12), Año (YYYY), Frecuencia (ID o código).</li>
                                        <li>Opcionales: Planilla ID (si no aplica dejar vacío) y Tipo Acumulado.</li>
                                        <li>Frecuencia acepta ID o código: quincenal, mensual, semanal, XIII, LIQUIDACION, VACACIONES.</li>
                                        <li>Tipo de concepto se calcula automáticamente desde el concepto.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div>
                            <i class="fas fa-upload bg-orange"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-clock"></i> Paso 3</span>
                                <h3 class="timeline-header">Subir y Procesar</h3>
                                <div class="timeline-body">
                                    Use el formulario para cargar el Excel. El sistema evitará duplicados por empleado + concepto + mes + año + planilla + tipo acumulado.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h5><i class="fas fa-exclamation-triangle text-warning"></i> Importantes:</h5>
                        <ul class="text-sm mb-0">
                            <li>Requeridos: Código Empleado, Código Concepto, Monto, Mes, Año, Frecuencia.</li>
                            <li>Planilla ID es opcional. Si se deja vacío se guardará como 0 (importación manual).</li>
                            <li>Tipo Acumulado opcional. Use código de tipo_acumulado o el código del concepto.</li>
                            <li>Revise catálogos en la hoja "Referencias" antes de importar.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de importación -->
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-upload"></i> Subir Archivo Excel</h3>
                </div>
                <form action="<?= \App\Core\UrlHelper::route('panel/accumulated/import/process') ?>" method="POST" enctype="multipart/form-data" id="importForm">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="excel_file">Archivo Excel (.xlsx)</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                                    <label class="custom-file-label" for="excel_file">Seleccionar archivo...</label>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Solo archivos Excel (.xlsx, .xls). Tamaño máximo: 5MB.
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="confirm_backup" required>
                                <label class="custom-control-label" for="confirm_backup">
                                    Confirmo que he realizado respaldo antes de importar.
                                </label>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Campos esperados</h6>
                            <ul class="mb-0 text-sm">
                                <li>Código Empleado | Código Concepto | Monto | Mes | Año | Frecuencia.</li>
                                <li>Opcionales: Planilla ID | Tipo Acumulado.</li>
                                <li>Frecuencia puede ser ID (1,2,3...) o código (quincenal, mensual...).</li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                            <i class="fas fa-upload"></i> Importar Acumulados
                        </button>
                        <a href="<?= \App\Core\UrlHelper::route('panel/acumulados') ?>" class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-arrow-left"></i> Volver a Acumulados
                        </a>
                    </div>
                </form>
            </div>

            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar"></i> Estadísticas</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="description-block border-right">
                                <span class="description-percentage text-success">
                                    <i class="fas fa-users"></i>
                                </span>
                                <h5 class="description-header"><?= number_format($totalEmployees ?? 0) ?></h5>
                                <span class="description-text">Empleados</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="description-block">
                                <span class="description-percentage text-info">
                                    <i class="fas fa-database"></i>
                                </span>
                                <h5 class="description-header"><?= number_format($totalAcumulados ?? 0) ?></h5>
                                <span class="description-text">Acumulados actuales</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#excel_file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Seleccionar archivo...');

        if (this.files[0] && this.files[0].size > 5 * 1024 * 1024) {
            alert('El archivo es demasiado grande. Máximo 5MB permitido.');
            $(this).val('');
            $(this).next('.custom-file-label').html('Seleccionar archivo...');
        }
    });

    $('#importForm').on('submit', function(e) {
        var fileInput = $('#excel_file')[0];
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Por favor seleccione un archivo Excel.');
            return false;
        }

        if (!$('#confirm_backup').is(':checked')) {
            e.preventDefault();
            alert('Debe confirmar que realizó respaldo antes de importar.');
            return false;
        }

        $('#submitBtn').html('<i class="fas fa-spinner fa-spin"></i> Procesando...').prop('disabled', true);
        $('<div class="alert alert-info mt-3" id="progressAlert">' +
          '<i class="fas fa-spinner fa-spin"></i> Procesando archivo, por favor espere...' +
          '</div>').insertAfter('#importForm');
    });
});
</script>
