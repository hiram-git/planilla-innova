<?php
/**
 * Vista principal generador de reportes de asistencias
 * Permite seleccionar tipo de reporte, período y tipo de planilla
 */

use App\Core\UrlHelper;
$baseUrl = UrlHelper::base();
?>

<!-- Content -->
<div class="content">
    <div class="container-fluid">

        <!-- Info Box -->
        <div class="callout callout-info">
            <h5><i class="fas fa-info-circle"></i> Generador de Reportes de Asistencias</h5>
            <p>Seleccione el tipo de reporte, período y tipo de planilla para generar reportes detallados de marcaciones, ausencias y tardanzas.</p>
        </div>

        <!-- Selector de Tipo de Reporte -->
        <div class="row">
            <div class="col-md-3">
                <div class="card report-card" data-report-type="punches" style="cursor: pointer; transition: all 0.3s;">
                    <div class="card-body text-center">
                        <i class="fas fa-fingerprint fa-3x text-primary mb-3"></i>
                        <h4>Reporte de Marcaciones</h4>
                        <p class="text-muted">Detalle completo de marcaciones (entrada/salida) con tardanzas, ausencias y horas trabajadas</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card report-card" data-report-type="absences" style="cursor: pointer; transition: all 0.3s;">
                    <div class="card-body text-center">
                        <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                        <h4>Reporte de Ausencias</h4>
                        <p class="text-muted">Listado detallado de ausencias por empleado, departamento y tipo (justificadas/injustificadas)</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card report-card" data-report-type="tardiness" style="cursor: pointer; transition: all 0.3s;">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                        <h4>Reporte de Tardanzas</h4>
                        <p class="text-muted">Listado de tardanzas con minutos de retraso, promedio y distribución por empleado y departamento</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card report-card" data-report-type="combined" style="cursor: pointer; transition: all 0.3s;">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-3x text-success mb-3"></i>
                        <h4>Reporte Combinado</h4>
                        <p class="text-muted">Análisis completo de asistencias: ausencias + tardanzas con score de desempeño por empleado</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Parámetros -->
        <div class="card" id="report-form-card" style="display: none;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Parámetros del Reporte</h3>
            </div>
            <div class="card-body">
                <form id="reportForm">
                    <input type="hidden" id="report_type" name="report_type" value="">

                    <div class="row">
                        <!-- Rango de Fechas -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">Fecha Inicio <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">Fecha Fin <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>

                        <!-- Tipo de Planilla -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tipo_planilla_id">Tipo de Planilla</label>
                                <select class="form-control select2" id="tipo_planilla_id" name="tipo_planilla_id">
                                    <option value="">-- Todos los tipos --</option>
                                    <?php foreach ($tipos_planilla as $tipo): ?>
                                        <option value="<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['descripcion']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Filtro opcional por tipo de planilla</small>
                            </div>
                        </div>

                        <!-- Formato de Salida -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="format">Formato</label>
                                <select class="form-control" id="format" name="format">
                                    <option value="view">Vista Web</option>
                                    <option value="excel">Excel (.xlsx)</option>
                                    <option value="pdf" disabled>PDF (próximamente)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Campo adicional para tardanzas -->
                    <div class="row" id="tardiness-options" style="display: none;">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="min_minutes">Minutos Mínimos</label>
                                <input type="number" class="form-control" id="min_minutes" name="min_minutes" value="1" min="1">
                                <small class="form-text text-muted">Mínimo de minutos de tardanza para incluir</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-chart-bar mr-2"></i>Generar Reporte
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg" id="btnCancel">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php
// Iniciar captura de scripts
ob_start();
?>
<style>
.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}
.report-card.selected {
    border: 2px solid #007bff;
    background-color: #f0f8ff;
}
</style>

<script>
$(document).ready(function() {
    const baseUrl = '<?= $baseUrl ?>';
    let selectedReportType = null;

    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: '-- Seleccione --',
        allowClear: true
    });

    // Intentar leer tipo de planilla desde sessionStorage (navbar)
    const savedTipoPlanillaId = sessionStorage.getItem('tipoPlanillaId');
    if (savedTipoPlanillaId && savedTipoPlanillaId !== 'todos') {
        $('#tipo_planilla_id').val(savedTipoPlanillaId).trigger('change');
    }

    // Establecer fecha fin como hoy y fecha inicio como hace 30 días
    const today = new Date().toISOString().split('T')[0];
    const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    $('#end_date').val(today);
    $('#start_date').val(thirtyDaysAgo);

    // Seleccionar tipo de reporte
    $('.report-card').on('click', function() {
        $('.report-card').removeClass('selected');
        $(this).addClass('selected');

        selectedReportType = $(this).data('report-type');
        $('#report_type').val(selectedReportType);

        // Mostrar formulario
        $('#report-form-card').slideDown();

        // Mostrar/ocultar opciones específicas
        if (selectedReportType === 'tardiness') {
            $('#tardiness-options').show();
        } else {
            $('#tardiness-options').hide();
        }

        // Scroll suave al formulario
        $('html, body').animate({
            scrollTop: $('#report-form-card').offset().top - 100
        }, 500);
    });

    // Cancelar
    $('#btnCancel').on('click', function() {
        $('.report-card').removeClass('selected');
        $('#report-form-card').slideUp();
        $('#reportForm')[0].reset();
        selectedReportType = null;
    });

    // Submit formulario
    $('#reportForm').on('submit', function(e) {
        e.preventDefault();

        if (!selectedReportType) {
            Swal.fire({
                icon: 'warning',
                title: 'Tipo de Reporte Requerido',
                text: 'Por favor seleccione un tipo de reporte primero'
            });
            return;
        }

        // Validar fechas
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Fechas Requeridas',
                text: 'Por favor ingrese fecha de inicio y fin'
            });
            return;
        }

        if (startDate > endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Rango de Fechas Inválido',
                text: 'La fecha de inicio debe ser menor o igual a la fecha de fin'
            });
            return;
        }

        // Construir URL
        const tipoPlanillaId = $('#tipo_planilla_id').val();
        const format = $('#format').val();
        const minMinutes = $('#min_minutes').val();

        let url = `${baseUrl}/panel/attendance/reports/${selectedReportType}?start_date=${startDate}&end_date=${endDate}&format=${format}`;

        if (tipoPlanillaId) {
            url += `&tipo_planilla_id=${tipoPlanillaId}`;
        }

        if (selectedReportType === 'tardiness' && minMinutes) {
            url += `&min_minutes=${minMinutes}`;
        }

        // Redirigir o abrir en nueva ventana según formato
        if (format === 'view') {
            window.location.href = url;
        } else {
            window.open(url, '_blank');
        }
    });
});
</script>
<?php
$scripts = ob_get_clean();
?>
