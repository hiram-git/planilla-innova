<?php
$title = 'Acumulados de Empleados';

// JavaScript optimizado modular
$scripts = '
<script>
// Configuración global
window.APP_CONFIG = window.APP_CONFIG || {};
window.APP_CONFIG.acumulados = {
    exportUrl: "' . url('/panel/acumulados/export') . '",
    employeeUrl: "' . url('/panel/acumulados/employee/') . '",
    byTypeUrl: "' . url('/panel/acumulados/by-type/') . '"
};

(function checkjQuery() {
    if (typeof $ === "undefined") {
        setTimeout(checkjQuery, 50);
        return;
    }
    
    $(document).ready(function() {
        initAcumuladosModule();
    });
})();

function initAcumuladosModule() {
    // Filtro por año
    $("#yearFilter").on("change", function() {
        updateFilters();
    });
    
    // Filtro por empleado
    $("#employeeFilter").on("change", function() {
        updateFilters();
    });
    
    // Botón exportar
    $("#exportBtn").on("click", function() {
        exportData();
    });
    
    // Botón para reporte general PDF
    $("#reportGeneralPdfBtn").on("click", function() {
        const year = $("#yearFilter").val() || new Date().getFullYear();
        const url = "' . url('/panel/reports/acumulados-general-pdf') . '?year=" + year;
        window.open(url, "_blank");
    });
    
    // Inicializar DataTable si existe
    if ($("#acumuladosTable").length) {
        $("#acumuladosTable").DataTable({
            "responsive": true,
            "pageLength": 25,
            "order": [[0, "asc"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
            }
        });
    }
}

function updateFilters() {
    const year = $("#yearFilter").val();
    const empleadoId = $("#employeeFilter").val();
    
    let url = window.location.pathname + "?";
    const params = [];
    
    if (year) params.push("year=" + year);
    if (empleadoId) params.push("empleado_id=" + empleadoId);
    
    window.location.href = url + params.join("&");
}

function exportData() {
    const year = $("#yearFilter").val() || new Date().getFullYear();
    const empleadoId = $("#employeeFilter").val() || "";
    const tipoId = $("#tipoFilter").val() || "";
    
    let url = window.APP_CONFIG.acumulados.exportUrl + "?year=" + year;
    if (empleadoId) url += "&empleado_id=" + empleadoId;
    if (tipoId) url += "&tipo_id=" + tipoId;
    
    window.open(url, "_blank");
}

function viewEmployeeDetails(empleadoId) {
    const year = $("#yearFilter").val() || new Date().getFullYear();
    window.location.href = window.APP_CONFIG.acumulados.employeeUrl + empleadoId + "?year=" + year;
}

function viewByType(tipoId) {
    const year = $("#yearFilter").val() || new Date().getFullYear();
    window.location.href = window.APP_CONFIG.acumulados.byTypeUrl + tipoId + "?year=" + year;
}

function generateEmployeePdf(empleadoId) {
    const year = $("#yearFilter").val() || new Date().getFullYear();
    const url = "' . url('/panel/reports/acumulados-empleado-pdf/') . '" + empleadoId + "?year=" + year;
    window.open(url, "_blank");
}

function generateTipoPdf(tipoId) {
    const year = $("#yearFilter").val() || new Date().getFullYear();
    const url = "' . url('/panel/reports/acumulados-tipo-pdf/') . '" + tipoId + "?year=" + year;
    window.open(url, "_blank");
}

</script>';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-piggy-bank"></i> <?= $title ?>
                </h3>
                <div class="card-tools">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-success btn-sm" id="exportBtn">
                            <i class="fas fa-download"></i> Exportar CSV
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" id="reportGeneralPdfBtn">
                            <i class="fas fa-file-pdf"></i> Reporte General PDF
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label for="yearFilter">Año:</label>
                        <select class="form-control" id="yearFilter">
                            <?php foreach ($years as $year): ?>
                                <option value="<?= $year ?>" <?= $year == $selectedYear ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="employeeFilter">Empleado:</label>
                        <select class="form-control" id="employeeFilter">
                            <option value="">Todos los empleados</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= $employee['id'] ?? '' ?>" 
                                        <?= ($selectedEmployee && ($selectedEmployee['id'] ?? '') == ($employee['id'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(($employee['document_id'] ?? '') . ' - ' . ($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary" onclick="updateFilters()">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>

                <?php if ($selectedEmployee): ?>
                    <!-- Vista de empleado específico -->
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5><i class="fas fa-user"></i> <?= htmlspecialchars(($selectedEmployee['firstname'] ?? '') . ' ' . ($selectedEmployee['lastname'] ?? '')) ?></h5>
                                <p class="mb-0">Cédula: <?= htmlspecialchars($selectedEmployee['document_id'] ?? 'N/A') ?> | Año: <?= $selectedYear ?></p>
                            </div>
                            <div>
                                <button type="button" class="btn btn-danger btn-sm" onclick="generateEmployeePdf(<?= $selectedEmployee['id'] ?? 0 ?>)">
                                    <i class="fas fa-file-pdf"></i> Reporte PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($acumulados)): ?>
                        <div class="row">
                            <?php foreach ($acumulados as $acumulado): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-gradient-info">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-coins"></i> <?= htmlspecialchars($acumulado['tipo_codigo'] ?? $acumulado['codigo'] ?? 'N/A') ?>
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <h4><?= htmlspecialchars($acumulado['tipo_descripcion'] ?? $acumulado['descripcion'] ?? 'N/A') ?></h4>
                                            <p class="h3 text-white">
                                                <?= currency_symbol() ?><?= number_format($acumulado['total_acumulado'] ?? 0, 2) ?>
                                            </p>
                                            <small class="text-light">
                                                Conceptos: <?= $acumulado['total_conceptos_incluidos'] ?? 0 ?><br>
                                                Período: <?= ($acumulado['periodo_inicio'] ?? '') ? date('d/m/Y', strtotime($acumulado['periodo_inicio'])) : 'N/A' ?> - 
                                                <?= ($acumulado['periodo_fin'] ?? '') ? date('d/m/Y', strtotime($acumulado['periodo_fin'])) : 'N/A' ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            No hay acumulados registrados para este empleado en el año <?= $selectedYear ?>.
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Vista resumen por tipos de acumulados -->
                    <div class="row">
                        <div class="col-12">
                            <h5>Resumen por Tipos de Acumulados - Año <?= $selectedYear ?></h5>
                        </div>
                    </div>

                    <div class="row">
                        <?php foreach ($tiposAcumulados as $tipo): ?>
                            <div class="col-md-4 mb-3">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3><?= htmlspecialchars($tipo['codigo'] ?? 'N/A') ?></h3>
                                        <p><?= htmlspecialchars($tipo['descripcion'] ?? 'N/A') ?></p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-piggy-bank"></i>
                                    </div>
                                    <div class="small-box-footer p-0">
                                        <a href="#" class="d-inline-block p-2" style="width: 70%" onclick="viewByType(<?= $tipo['id'] ?? 0 ?>)">
                                            Ver Detalles <i class="fas fa-arrow-circle-right"></i>
                                        </a>
                                        <a href="#" class="d-inline-block p-2 border-left" style="width: 30%" onclick="generateTipoPdf(<?= $tipo['id'] ?? 0 ?>)">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Información adicional -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-info-circle"></i> Información de Acumulados
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-check-circle text-success"></i> Tipos Activos</h6>
                                            <ul>
                                                <?php foreach ($tiposAcumulados as $tipo): ?>
                                                    <li><?= htmlspecialchars($tipo['descripcion'] ?? 'N/A') ?> (<?= htmlspecialchars($tipo['periodicidad'] ?? 'N/A') ?>)</li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-calendar-alt"></i> Procesamiento</h6>
                                            <p>Los acumulados se calculan automáticamente al cerrar las planillas.</p>
                                            <p><strong>Año actual:</strong> <?= $selectedYear ?></p>
                                            <p><strong>Total empleados:</strong> <?= count($employees) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>