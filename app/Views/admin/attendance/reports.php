<?php
$page_title = 'Reportes de Asistencia';

$content = '
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filtros de Búsqueda</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="' . \App\Core\UrlHelper::attendance('reports') . '">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">Fecha Inicio</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="' . $start_date . '">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">Fecha Fin</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="' . $end_date . '">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="employee_id">Empleado</label>
                                <select class="form-control" id="employee_id" name="employee_id">
                                    <option value="">Todos los empleados</option>';

foreach ($employees as $employee) {
    $selected = ($selected_employee == $employee['id']) ? 'selected' : '';
    $content .= '<option value="' . $employee['id'] . '" ' . $selected . '>' . htmlspecialchars($employee['employee_id'] . ' - ' . $employee['firstname'] . ' ' . $employee['lastname']) . '</option>';
}

$content .= '
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Resultados del Reporte</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <h5>Resumen del Período: ' . date('d/m/Y', strtotime($start_date)) . ' al ' . date('d/m/Y', strtotime($end_date)) . '</h5>';

// Calcular estadísticas
$totalDias = 0;
$totalHoras = 0;
$diasTrabajados = 0;
$llegadasTarde = 0;
$empleadosUnicos = [];

foreach ($attendances as $attendance) {
    $totalDias++;
    $totalHoras += $attendance['num_hr'];
    if ($attendance['time_in']) {
        $diasTrabajados++;
    }
    if ($attendance['status'] == 0) {
        $llegadasTarde++;
    }
    $empleadosUnicos[$attendance['employee_id']] = true;
}

$promedioHoras = $diasTrabajados > 0 ? round($totalHoras / $diasTrabajados, 1) : 0;
$porcentajePuntualidad = $totalDias > 0 ? round((($totalDias - $llegadasTarde) / $totalDias) * 100, 1) : 0;

$content .= '
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="description-block">
                                    <h5 class="description-header">' . $totalDias . '</h5>
                                    <span class="description-text">Total Registros</span>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="description-block">
                                    <h5 class="description-header">' . count($empleadosUnicos) . '</h5>
                                    <span class="description-text">Empleados</span>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="description-block">
                                    <h5 class="description-header">' . $promedioHoras . 'h</h5>
                                    <span class="description-text">Promedio Horas</span>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="description-block">
                                    <h5 class="description-header">' . $porcentajePuntualidad . '%</h5>
                                    <span class="description-text">Puntualidad</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="reportTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Empleado</th>
                                <th>Código</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Horas</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>';

foreach ($attendances as $attendance) {
    $status = $attendance['status'] == 1 
        ? '<span class="badge badge-success">A tiempo</span>' 
        : '<span class="badge badge-warning">Tarde</span>';
        
    $timeOut = $attendance['time_out'] && $attendance['time_out'] !== '00:00:00' 
        ? date('h:i A', strtotime($attendance['time_out'])) 
        : '<span class="text-muted">Pendiente</span>';
        
    $hours = $attendance['num_hr'] > 0 ? number_format($attendance['num_hr'], 1) . 'h' : '-';
    
    $content .= '
                            <tr>
                                <td>' . date('d/m/Y', strtotime($attendance['date'])) . '</td>
                                <td>' . htmlspecialchars($attendance['firstname'] . ' ' . $attendance['lastname']) . '</td>
                                <td>' . htmlspecialchars($attendance['employee_id']) . '</td>
                                <td>' . date('h:i A', strtotime($attendance['time_in'])) . '</td>
                                <td>' . $timeOut . '</td>
                                <td>' . $hours . '</td>
                                <td>' . $status . '</td>
                            </tr>';
}

if (empty($attendances)) {
    $content .= '
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> No se encontraron registros para los criterios seleccionados
                                </td>
                            </tr>';
}

$content .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>';

$scripts = '
<script src="<?= url('plugins/datatables/jquery.dataTables.min.js', false) ?>"></script>
<script src="<?= url('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js', false) ?>"></script>
<script>
$(document).ready(function() {
    // DataTable
    $("#reportTable").DataTable({
        "language": {
            "url": "<?= url('assets/js/datatables-spanish.json', false) ?>"
        },
        "order": [[0, "desc"]],
        "pageLength": 50
    });
});

function exportToExcel() {
    // Crear tabla temporal para exportar
    var table = document.getElementById("reportTable");
    var html = table.outerHTML;
    
    // Crear blob con contenido HTML
    var blob = new Blob([html], {
        type: "application/vnd.ms-excel"
    });
    
    // Crear enlace de descarga
    var a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "reporte_asistencia_' . date('Y-m-d') . '.xls";
    a.click();
}
</script>';

$styles = '
<link rel="stylesheet" href="<?= url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css', false) ?>">
<style>
@media print {
    .card-tools, .breadcrumb, .btn { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
}
.description-block {
    text-align: center;
    margin-bottom: 15px;
}
.description-header {
    font-size: 2rem;
    font-weight: bold;
    color: #007bff;
}
.description-text {
    text-transform: uppercase;
    font-weight: 600;
    color: #6c757d;
}
</style>';

include __DIR__ . '/../../layouts/admin.php';
?>