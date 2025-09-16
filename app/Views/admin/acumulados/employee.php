<?php
$title = 'Acumulados: ' . $employee['firstname'] . ' ' . $employee['lastname'];

// JavaScript
$scripts = '
<script>
(function checkjQuery() {
    if (typeof $ === "undefined") {
        setTimeout(checkjQuery, 50);
        return;
    }
    
    $(document).ready(function() {
        // Filtro por año
        $("#yearFilter").on("change", function() {
            const year = $(this).val();
            window.location.href = window.location.pathname + "?year=" + year;
        });
        
        // Exportar datos del empleado
        $("#exportEmployee").on("click", function() {
            const year = $("#yearFilter").val() || new Date().getFullYear();
            const url = "' . url('/panel/acumulados/export') . '?empleado_id=' . $employee['id'] . '&year=" + year;
            window.open(url, "_blank");
        });
        
        // Generar PDF del empleado
        $("#pdfEmployee").on("click", function() {
            const year = $("#yearFilter").val() || new Date().getFullYear();
            const url = "' . url('/panel/reports/acumulados-empleado-pdf/' . $employee['id']) . '?year=" + year;
            window.open(url, "_blank");
        });
    });
})();
</script>';
?>

<div class="row">
    <div class="col-12">
        <!-- Header del empleado -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) ?>
                </h3>
                <div class="card-tools">
                    <a href="<?= url('/panel/acumulados') ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-success btn-sm" id="exportEmployee">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" id="pdfEmployee">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Cédula:</strong> <?= htmlspecialchars($employee['document_id']) ?></p>
                        <p><strong>Fecha de Ingreso:</strong> <?= date('d/m/Y', strtotime($employee['fecha_ingreso'])) ?></p>
                    </div>
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
                </div>
            </div>
        </div>

        <!-- XIII Mes Calculator -->
        <?php if ($xiiiMes['monto_xiii_mes'] > 0): ?>
        <div class="card bg-gradient-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-gift"></i> XIII Mes (Décimo Tercer Mes) - <?= $selectedYear ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h4 class="text-white">Monto XIII Mes</h4>
                        <h2 class="text-white"><?= currency_symbol() ?><?= number_format($xiiiMes['monto_xiii_mes'], 2) ?></h2>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-light">Salario Anual Acumulado</h6>
                        <p class="text-white"><?= currency_symbol() ?><?= number_format($xiiiMes['salario_anual_acumulado'], 2) ?></p>
                        
                        <h6 class="text-light">Días Base Legal</h6>
                        <p class="text-white"><?= $xiiiMes['dias_base'] ?> días</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-light">Días No Laborados</h6>
                        <p class="text-white"><?= $xiiiMes['dias_no_laborados'] ?> días</p>
                        
                        <h6 class="text-light">Días a Pagar</h6>
                        <p class="text-white"><?= $xiiiMes['dias_a_pagar'] ?> días</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <small class="text-light">
                            <i class="fas fa-info-circle"></i> <?= $xiiiMes['observaciones'] ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Acumulados del empleado -->
        <div class="row">
            <?php if (!empty($acumulados)): ?>
                <?php foreach ($acumulados as $acumulado): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-header bg-info">
                                <h3 class="card-title text-white">
                                    <i class="fas fa-coins"></i> <?= htmlspecialchars($acumulado['codigo']) ?>
                                </h3>
                            </div>
                            <div class="card-body">
                                <h5><?= htmlspecialchars($acumulado['descripcion']) ?></h5>
                                
                                <div class="mb-2">
                                    <h4 class="text-info">
                                        <?= currency_symbol() ?><?= number_format($acumulado['total_acumulado'], 2) ?>
                                    </h4>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Conceptos Incluidos</small>
                                        <div class="font-weight-bold"><?= $acumulado['total_conceptos_incluidos'] ?></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Periodicidad</small>
                                        <div class="font-weight-bold"><?= htmlspecialchars($acumulado['periodicidad']) ?></div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-2">
                                    <small class="text-muted">Período</small>
                                    <div><?= date('d/m/Y', strtotime($acumulado['periodo_inicio'])) ?> - <?= date('d/m/Y', strtotime($acumulado['periodo_fin'])) ?></div>
                                </div>
                                
                                <?php if ($acumulado['ultima_planilla']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Última Planilla</small>
                                    <div><?= htmlspecialchars($acumulado['ultima_planilla']) ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <div>
                                    <small class="text-muted">Actualizado</small>
                                    <div><?= date('d/m/Y H:i', strtotime($acumulado['fecha_ultimo_calculo'])) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Sin Acumulados</h5>
                        <p>No se encontraron acumulados para este empleado en el año <?= $selectedYear ?>.</p>
                        <p>Los acumulados se generan automáticamente al procesar y cerrar planillas.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Información adicional -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Información Adicional
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Legislación Panameña</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> XIII Mes: 122 días de salario base</li>
                            <li><i class="fas fa-check text-success"></i> Vacaciones: Según días laborados</li>
                            <li><i class="fas fa-check text-success"></i> Prima de Antigüedad: Por años de servicio</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Procesamiento Automático</h6>
                        <p>Los acumulados se calculan y actualizan automáticamente cuando se cierran las planillas mensuales.</p>
                        <p><strong>Año seleccionado:</strong> <?= $selectedYear ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>