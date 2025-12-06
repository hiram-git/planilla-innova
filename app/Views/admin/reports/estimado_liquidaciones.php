<?php
$page_title = $data['title'] ?? 'Estimado Anual de Liquidaciones';

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
            <div class="card-header bg-gradient-warning">
                <h3 class="card-title">
                    <i class="fas fa-hand-holding-usd"></i>
                    Estimado Anual de Liquidaciones
                </h3>
                <div class="card-tools">
                    <a href="' . \App\Core\UrlHelper::url('/panel/reports') . '" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left"></i> Volver a Reportes
                    </a>
                    <button type="button" class="btn btn-sm btn-light" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Información General -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info-circle"></i> Información del Estimado</h5>
                            <p class="mb-0">
                                Este reporte calcula el monto estimado de liquidación para todos los empleados activos
                                <strong>sin que estén dados de baja</strong>. Es útil para estimar la provisión anual
                                necesaria para cubrir posibles liquidaciones y para análisis de costos laborales futuros.
                            </p>
                            <p class="mb-0 mt-2">
                                <strong>Fecha del estimado:</strong> ' . $data['fecha_estimado'] . ' |
                                <strong>Empleados incluidos:</strong> ' . $data['total_employees'] . '
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Resumen Ejecutivo -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-plus-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Asignaciones</span>
                                <span class="info-box-number">$' . number_format($data['total_general_asignaciones'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-minus-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Deducciones</span>
                                <span class="info-box-number">$' . number_format($data['total_general_deducciones'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Neto Estimado</span>
                                <span class="info-box-number">$' . number_format($data['total_general_neto'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Detalles por Empleado -->
                <div class="table-responsive">
                    <table id="table-estimado-liquidaciones" class="table table-bordered table-striped table-hover">
                        <thead class="bg-gradient-warning">
                            <tr>
                                <th>Cédula</th>
                                <th>Empleado</th>
                                <th>Cargo</th>
                                <th>Años Trabajados</th>
                                <th>Salario Base</th>
                                <th>Asignaciones</th>
                                <th>Deducciones</th>
                                <th>Neto Estimado</th>
                            </tr>
                        </thead>
                        <tbody>';

foreach ($data['estimates'] as $estimate) {
    $employee = $estimate['employee'];
    $years_worked = number_format($employee['years_worked'], 1);

    $content .= '
                            <tr>
                                <td>' . htmlspecialchars($employee['document_id'] ?? 'N/A') . '</td>
                                <td>' . htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) . '</td>
                                <td>' . htmlspecialchars($employee['position_name']) . '</td>
                                <td class="text-center">' . $years_worked . '</td>
                                <td class="text-right">$' . number_format($employee['sueldo_individual'], 2) . '</td>
                                <td class="text-right text-success">$' . number_format($estimate['total_asignaciones'], 2) . '</td>
                                <td class="text-right text-danger">$' . number_format($estimate['total_deducciones'], 2) . '</td>
                                <td class="text-right text-primary font-weight-bold">$' . number_format($estimate['neto'], 2) . '</td>
                            </tr>';
}

$content .= '
                        </tbody>
                        <tfoot>
                            <tr class="bg-gradient-info">
                                <th colspan="5" class="text-right">TOTAL GENERAL:</th>
                                <th class="text-right">$' . number_format($data['total_general_asignaciones'], 2) . '</th>
                                <th class="text-right">$' . number_format($data['total_general_deducciones'], 2) . '</th>
                                <th class="text-right">$' . number_format($data['total_general_neto'], 2) . '</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Notas -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="callout callout-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Notas Importantes</h6>
                            <ul class="mb-0">
                                <li>Este es un <strong>estimado proyectado</strong> y no representa liquidaciones reales procesadas.</li>
                                <li>Los cálculos se basan en la fecha actual como fecha hipotética de terminación.</li>
                                <li>No se incluyen empleados que ya tienen liquidaciones calculadas o procesadas.</li>
                                <li>Los montos pueden variar según cambios en salarios, antigüedad o conceptos aplicables.</li>
                                <li>Se recomienda actualizar este estimado periódicamente para mantener provisiones precisas.</li>
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

// Scripts para DataTables
$scripts = '
<script>
$(document).ready(function() {
    $("#table-estimado-liquidaciones").DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        "order": [[7, "desc"]], // Ordenar por neto estimado descendente
        "pageLength": 25,
        "responsive": true,
        "dom": "Bfrtip",
        "buttons": [
            {
                extend: "excel",
                text: "<i class=\'fas fa-file-excel\'></i> Excel",
                className: "btn btn-success btn-sm",
                title: "Estimado_Anual_Liquidaciones_' . date('Y-m-d') . '"
            },
            {
                extend: "pdf",
                text: "<i class=\'fas fa-file-pdf\'></i> PDF",
                className: "btn btn-danger btn-sm",
                title: "Estimado Anual de Liquidaciones",
                orientation: "landscape",
                pageSize: "LEGAL"
            },
            {
                extend: "print",
                text: "<i class=\'fas fa-print\'></i> Imprimir",
                className: "btn btn-info btn-sm"
            }
        ]
    });
});
</script>';

// Estilos para impresión
$styles = '
<style>
@media print {
    .card-tools, .btn, .dataTables_wrapper .row:first-child, .dataTables_wrapper .row:last-child {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .print-section {
        page-break-before: auto;
    }
}
.info-box-number {
    font-size: 1.5rem !important;
}
</style>';

include __DIR__ . '/../../layouts/admin.php';
?>
