<!-- Main content -->
<div class="container-fluid">

            <!-- Mensajes de estado -->
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

            <!-- Errores de importación -->
            <?php if (isset($_SESSION['import_errors'])): ?>
                <div class="callout callout-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> Errores Encontrados Durante la Importación</h5>
                    <div class="mb-0">
                        <strong>Se encontraron los siguientes problemas:</strong>
                        <ul class="mt-2">
                            <?php foreach ($_SESSION['import_errors'] as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Revise el archivo Excel y corregir los datos antes de volver a importar.
                        </small>
                    </div>
                </div>
                <?php unset($_SESSION['import_errors']); ?>
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
                                    <span class="bg-info">Proceso de Importación</span>
                                </div>

                                <div>
                                    <i class="fas fa-download bg-green"></i>
                                    <div class="timeline-item">
                                        <span class="time"><i class="fas fa-clock"></i> Paso 1</span>
                                        <h3 class="timeline-header">
                                            <a href="<?= \App\Core\UrlHelper::route('panel/employees/import_template') ?>" class="btn btn-success btn-sm mr-2">
                                                <i class="fas fa-sync"></i> Plantilla Dinámica
                                            </a>
                                            <a href="<?= \App\Core\UrlHelper::route('template_empleados.xlsx') ?>" class="btn btn-info btn-sm" download>
                                                <i class="fas fa-download"></i> Plantilla Base
                                            </a>
                                        </h3>
                                        <div class="timeline-body">
                                            <strong>Plantilla Dinámica:</strong> Genera archivo con IDs actuales de la base de datos.<br>
                                            <strong>Plantilla Base:</strong> Archivo estático con ejemplos y referencias genéricas.
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
                                                <li>Abrir el archivo en Excel</li>
                                                <li>Eliminar las filas de ejemplo</li>
                                                <li>Completar los datos de empleados</li>
                                                <li>Consultar la hoja "Referencias" para IDs válidos</li>
                                                <li>Revisar la hoja "Instrucciones" para detalles</li>
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
                                            Usar el formulario de la derecha para subir el archivo completado.
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <i class="fas fa-check bg-green"></i>
                                    <div class="timeline-item">
                                        <span class="time"><i class="fas fa-clock"></i> Paso 4</span>
                                        <h3 class="timeline-header">Verificar Resultados</h3>
                                        <div class="timeline-body">
                                            El sistema mostrará un resumen de empleados importados y errores encontrados.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h5><i class="fas fa-exclamation-triangle text-warning"></i> Importantes:</h5>
                                <ul class="text-sm">
                                    <li><strong>Campos obligatorios</strong> están marcados con * en la plantilla</li>
                                    <li><strong>Códigos de empleado</strong> deben ser únicos</li>
                                    <li><strong>Fechas</strong> formato: YYYY-MM-DD (ej: 2023-12-31)</li>
                                    <li><strong>Datos bancarios</strong> obligatorios para forma de pago CHEQUE/ACH</li>
                                    <li><strong>Fecha vencimiento</strong> obligatoria para contratos definidos</li>
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
                        <form action="" method="POST" enctype="multipart/form-data" id="importForm">
                            <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="excel_file">Archivo Excel (.xlsx)</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="excel_file" name="excel_file"
                                                   accept=".xlsx,.xls" required>
                                            <label class="custom-file-label" for="excel_file">Seleccionar archivo...</label>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        Solo archivos Excel (.xlsx, .xls). Tamaño máximo: 5MB
                                    </small>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="confirm_backup" required>
                                        <label class="custom-control-label" for="confirm_backup">
                                            Confirmo que he realizado respaldo de datos antes de la importación
                                        </label>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Antes de importar:</h6>
                                    <ul class="mb-0 text-sm">
                                        <li>Verificar que los datos estén completos y correctos</li>
                                        <li>Eliminar filas de ejemplo de la plantilla</li>
                                        <li>Realizar respaldo de la base de datos</li>
                                        <li>Revisar IDs en la hoja "Referencias"</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                                    <i class="fas fa-upload"></i> Importar Empleados
                                </button>
                                <a href="<?= \App\Core\UrlHelper::route('panel/employees') ?>" class="btn btn-secondary btn-block mt-2">
                                    <i class="fas fa-arrow-left"></i> Volver a Empleados
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Información adicional -->
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
                                        <h5 class="description-header" id="totalEmployees">
                                            <?= number_format($employeeCount ?? 0) ?>
                                        </h5>
                                        <span class="description-text">Empleados Actuales</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="description-block">
                                        <span class="description-percentage text-info">
                                            <i class="fas fa-plus"></i>
                                        </span>
                                        <h5 class="description-header" id="importedToday">0</h5>
                                        <span class="description-text">Importados Hoy</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</div>

<!-- Scripts -->
<script>
$(document).ready(function() {
    // Mostrar nombre del archivo seleccionado
    $('#excel_file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);

        // Validar tamaño del archivo (5MB)
        if (this.files[0] && this.files[0].size > 5 * 1024 * 1024) {
            alert('El archivo es demasiado grande. Máximo 5MB permitido.');
            $(this).val('');
            $(this).next('.custom-file-label').html('Seleccionar archivo...');
        }
    });

    // Validación del formulario
    $('#importForm').on('submit', function(e) {
        var fileInput = $('#excel_file')[0];
        var confirmBackup = $('#confirm_backup').is(':checked');

        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Por favor seleccione un archivo Excel.');
            return false;
        }

        if (!confirmBackup) {
            e.preventDefault();
            alert('Debe confirmar que ha realizado respaldo antes de importar.');
            return false;
        }

        // Mostrar spinner en el botón
        $('#submitBtn').html('<i class="fas fa-spinner fa-spin"></i> Procesando...').prop('disabled', true);

        // Mostrar mensaje de progreso
        $('<div class="alert alert-info mt-3" id="progressAlert">' +
          '<i class="fas fa-spinner fa-spin"></i> Procesando archivo... ' +
          'Esto puede tomar varios minutos dependiendo del tamaño del archivo.' +
          '</div>').insertAfter('#importForm');
    });

    // Prevenir doble envío
    $('#importForm').on('submit', function() {
        $(this).find('button[type="submit"]').prop('disabled', true);
    });
});
</script>