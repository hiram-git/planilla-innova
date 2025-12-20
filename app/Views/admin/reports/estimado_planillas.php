<?php
$page_title = $data['title'] ?? 'Estimado Anual de Planillas';

$content = '
<div class="row">
    <div class="col-12">
        <!-- Mensajes de estado -->
        ';

if (isset($_SESSION['success'])) {
    $content .= '
        <div class="callout callout-success">
            <h5><i class="fas fa-check"></i> Éxito</h5>
            <p>' . $_SESSION['success'] . '</p>
        </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $content .= '
        <div class="callout callout-danger">
            <h5><i class="fas fa-exclamation-triangle"></i> Error</h5>
            <p>' . $_SESSION['error'] . '</p>
        </div>';
    unset($_SESSION['error']);
}

$content .= '
        <div class="card">
            <div class="card-header bg-gradient-primary">
                <h3 class="card-title">
                    <i class="fas fa-calendar-alt"></i>
                    Estimado Anual de Planillas - Año ' . $data['ano_estimado'] . '
                </h3>
                <div class="card-tools">
                    <a href="' . \App\Core\UrlHelper::url('/panel/reports') . '" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left"></i> Volver a Reportes
                    </a>
                    <a href="' . \App\Core\UrlHelper::url('/panel/reports/estimado-anual-planillas-pdf') . (isset($data['tipo_planilla_id']) ? '?tipo_planilla_id=' . $data['tipo_planilla_id'] : '') . '" class="btn btn-sm btn-danger" target="_blank">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <form method="GET" action="' . \App\Core\UrlHelper::url('/panel/reports/estimado-anual-planillas') . '" class="form-inline">
                            <label class="mr-2">Filtrar por Tipo de Planilla:</label>
                            <select name="tipo_planilla_id" class="form-control mr-2" onchange="this.form.submit()">
                                <option value="">Todos los tipos</option>';

foreach ($data['tipos_planilla'] as $tipo) {
    $selected = ($data['tipo_planilla_id'] == $tipo['id']) ? 'selected' : '';
    $content .= '<option value="' . $tipo['id'] . '" ' . $selected . '>' . htmlspecialchars($tipo['nombre']) . '</option>';
}

$content .= '
                            </select>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Información General -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info-circle"></i> Información del Estimado</h5>
                            <p class="mb-0">
                                Este reporte proyecta el costo anual de planillas basándose en la última planilla procesada.
                                Los montos se replican mes a mes para obtener una estimación del costo total anual.
                            </p>
                            <p class="mb-0 mt-2">
                                <strong>Fecha del estimado:</strong> ' . $data['fecha_estimado'] . ' |
                                <strong>Planilla base:</strong> ' . htmlspecialchars($data['ultima_planilla']['descripcion']) . ' |
                                <strong>Empleados:</strong> ' . $data['ultima_planilla']['total_empleados'] . '
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Resumen Ejecutivo Anual -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Asignaciones Anuales</span>
                                <span class="info-box-number">$' . number_format($data['total_anual_asignaciones'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Deducciones Anuales</span>
                                <span class="info-box-number">$' . number_format($data['total_anual_deducciones'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Neto Anual</span>
                                <span class="info-box-number">$' . number_format($data['total_anual_neto'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfica de Proyección Mensual -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-chart-line"></i> Proyección Mensual</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyChart" style="height: 250px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Proyección Mensual -->
                <div class="table-responsive">
                    <table id="table-estimado-planillas" class="table table-bordered table-striped table-hover">
                        <thead class="bg-gradient-primary">
                            <tr>
                                <th>#</th>
                                <th>Mes</th>
                                <th>Empleados</th>
                                <th>Asignaciones</th>
                                <th>Deducciones</th>
                                <th>Neto</th>
                            </tr>
                        </thead>
                        <tbody>';

foreach ($data['proyeccion_mensual'] as $mes) {
    $content .= '
                            <tr>
                                <td class="text-center">' . $mes['mes_numero'] . '</td>
                                <td><strong>' . $mes['mes_nombre'] . ' ' . $data['ano_estimado'] . '</strong></td>
                                <td class="text-center">' . $mes['empleados'] . '</td>
                                <td class="text-right text-success">$' . number_format($mes['asignaciones'], 2) . '</td>
                                <td class="text-right text-danger">$' . number_format($mes['deducciones'], 2) . '</td>
                                <td class="text-right text-primary font-weight-bold">$' . number_format($mes['neto'], 2) . '</td>
                            </tr>';
}

$content .= '
                        </tbody>
                        <tfoot>
                            <tr class="bg-gradient-info">
                                <th colspan="3" class="text-right">TOTAL ANUAL:</th>
                                <th class="text-right">$' . number_format($data['total_anual_asignaciones'], 2) . '</th>
                                <th class="text-right">$' . number_format($data['total_anual_deducciones'], 2) . '</th>
                                <th class="text-right">$' . number_format($data['total_anual_neto'], 2) . '</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Estadísticas Adicionales -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-calculator"></i> Promedios</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td>Promedio Mensual (Neto):</td>
                                        <td class="text-right font-weight-bold">$' . number_format($data['total_anual_neto'] / 12, 2) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Costo por Empleado (Anual):</td>
                                        <td class="text-right font-weight-bold">$' . number_format($data['total_anual_neto'] / $data['ultima_planilla']['total_empleados'], 2) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Costo por Empleado (Mensual):</td>
                                        <td class="text-right font-weight-bold">$' . number_format(($data['total_anual_neto'] / 12) / $data['ultima_planilla']['total_empleados'], 2) . '</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-info-circle"></i> Planilla Base</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td>Descripción:</td>
                                        <td class="text-right">' . htmlspecialchars($data['ultima_planilla']['descripcion']) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Tipo de Planilla:</td>
                                        <td class="text-right">' . htmlspecialchars($data['ultima_planilla']['tipo_planilla_nombre'] ?? 'N/A') . '</td>
                                    </tr>
                                    <tr>
                                        <td>Frecuencia:</td>
                                        <td class="text-right">' . htmlspecialchars($data['ultima_planilla']['frecuencia_nombre'] ?? 'N/A') . '</td>
                                    </tr>
                                    <tr>
                                        <td>Período:</td>
                                        <td class="text-right">' . date('d/m/Y', strtotime($data['ultima_planilla']['fecha_desde'])) . ' - ' . date('d/m/Y', strtotime($data['ultima_planilla']['fecha_hasta'])) . '</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="callout callout-info">
                            <h6><i class="fas fa-exclamation-circle"></i> Notas Importantes</h6>
                            <ul class="mb-0">
                                <li>Este es un <strong>estimado proyectado</strong> basado en la última planilla procesada.</li>
                                <li>Se asume que los costos mensuales permanecen constantes durante todo el año.</li>
                                <li>No se incluyen variaciones por incrementos salariales, nuevas contrataciones o bajas.</li>
                                <li>Los montos pueden variar según cambios en conceptos, deducciones o políticas salariales.</li>
                                <li>Se recomienda actualizar este estimado trimestralmente para mayor precisión.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Firmas -->
                <div class="row mt-5 print-section">
                    <div class="col-md-6 text-center">
                        <div class="mt-5 pt-4">
                            <div class="border-top border-dark d-inline-block" style="width: 250px;"></div>
                            <p class="mb-0 mt-2"><strong>' . htmlspecialchars($data['signatures']['elaborado_por'] ?? 'Por definir') . '</strong></p>
                            <p class="text-muted small">' . htmlspecialchars($data['signatures']['cargo_elaborador'] ?? 'Elaborado por') . '</p>
                        </div>
                    </div>
                    <div class="col-md-6 text-center">
                        <div class="mt-5 pt-4">
                            <div class="border-top border-dark d-inline-block" style="width: 250px;"></div>
                            <p class="mb-0 mt-2"><strong>' . htmlspecialchars($data['signatures']['jefe_recursos_humanos'] ?? 'Por definir') . '</strong></p>
                            <p class="text-muted small">' . htmlspecialchars($data['signatures']['cargo_jefe_rrhh'] ?? 'Autorizado por') . '</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

// Preparar datos para el gráfico
$meses_nombres = [];
$meses_asignaciones = [];
$meses_deducciones = [];
$meses_neto = [];

foreach ($data['proyeccion_mensual'] as $mes) {
    $meses_nombres[] = "'" . $mes['mes_nombre'] . "'";
    $meses_asignaciones[] = $mes['asignaciones'];
    $meses_deducciones[] = $mes['deducciones'];
    $meses_neto[] = $mes['neto'];
}

// Scripts para DataTables y Chart.js
$scripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // DataTable
    $("#table-estimado-planillas").DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        "paging": false,
        "searching": false,
        "info": false,
        "responsive": true,
        "dom": "Bfrtip",
        "buttons": [
            {
                extend: "excel",
                text: "<i class=\'fas fa-file-excel\'></i> Excel",
                className: "btn btn-success btn-sm",
                title: "Estimado_Anual_Planillas_' . date('Y') . '"
            },
            {
                extend: "pdf",
                text: "<i class=\'fas fa-file-pdf\'></i> PDF",
                className: "btn btn-danger btn-sm",
                title: "Estimado Anual de Planillas ' . date('Y') . '",
                orientation: "portrait"
            },
            {
                extend: "print",
                text: "<i class=\'fas fa-print\'></i> Imprimir",
                className: "btn btn-info btn-sm"
            }
        ]
    });

    // Gráfico de Línea
    const ctx = document.getElementById("monthlyChart");
    new Chart(ctx, {
        type: "line",
        data: {
            labels: [' . implode(',', $meses_nombres) . '],
            datasets: [
                {
                    label: "Asignaciones",
                    data: [' . implode(',', $meses_asignaciones) . '],
                    borderColor: "rgb(40, 167, 69)",
                    backgroundColor: "rgba(40, 167, 69, 0.1)",
                    tension: 0.1
                },
                {
                    label: "Deducciones",
                    data: [' . implode(',', $meses_deducciones) . '],
                    borderColor: "rgb(220, 53, 69)",
                    backgroundColor: "rgba(220, 53, 69, 0.1)",
                    tension: 0.1
                },
                {
                    label: "Neto",
                    data: [' . implode(',', $meses_neto) . '],
                    borderColor: "rgb(0, 123, 255)",
                    backgroundColor: "rgba(0, 123, 255, 0.1)",
                    tension: 0.1,
                    borderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "top"
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return "$" + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>';

// Estilos para impresión
$styles = '
<style>
@media print {
    .card-tools, .btn, .dataTables_wrapper .row:first-child, .dataTables_wrapper .row:last-child, form {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .print-section {
        page-break-before: auto;
    }
    #monthlyChart {
        page-break-inside: avoid;
    }
}
.info-box-number {
    font-size: 1.5rem !important;
}
</style>';

include __DIR__ . '/../../layouts/admin.php';
?>
