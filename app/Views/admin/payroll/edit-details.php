<?php
/**
 * Vista: Editar Detalles de Planilla
 */
$title = 'Editar Detalles: ' . htmlspecialchars($payroll['descripcion']);
?>

<div class="container-fluid">
            <!-- Información de la planilla -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i>
                                Información de la Planilla
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Descripción:</strong><br>
                                    <?= htmlspecialchars($payroll['descripcion']) ?>
                                </div>
                                <div class="col-md-2">
                                    <strong>Estado:</strong><br>
                                    <span class="badge badge-<?= $payroll['estado'] === 'PENDIENTE' ? 'warning' : 'info' ?>">
                                        <?= htmlspecialchars($payroll['estado']) ?>
                                    </span>
                                </div>
                                <div class="col-md-2">
                                    <strong>Fecha:</strong><br>
                                    <?= date('d/m/Y', strtotime($payroll['fecha'])) ?>
                                </div>
                                <div class="col-md-5">
                                    <strong>Período:</strong><br>
                                    <?= date('d/m/Y', strtotime($payroll['fecha_desde'])) ?> al 
                                    <?= date('d/m/Y', strtotime($payroll['fecha_hasta'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Matriz Empleado-Concepto -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit"></i>
                                Edición Detallada - Conceptos por Empleado
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-success" onclick="recalculateAllEmployees()">
                                    <i class="fas fa-calculator"></i> Recalcular Todo
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleConceptsPanel()">
                                    <i class="fas fa-plus"></i> Agregar Conceptos
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Panel para agregar conceptos (inicialmente oculto) -->
                            <div id="add-concepts-panel" class="mb-3" style="display: none;">
                                <div class="card card-secondary">
                                    <div class="card-header">
                                        <h4 class="card-title">Agregar Conceptos a Empleados</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Seleccionar Concepto:</label>
                                                <select id="concept-to-add" class="form-control">
                                                    <option value="">Seleccione un concepto...</option>
                                                    <?php foreach ($concepts as $concept): ?>
                                                        <option value="<?= $concept['id'] ?>" data-tipo="<?= $concept['tipo'] ?>">
                                                            [<?= htmlspecialchars($concept['tipo']) ?>] <?= htmlspecialchars($concept['descripcion']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Valor Inicial (opcional):</label>
                                                <input type="number" id="initial-value" class="form-control" step="0.01" placeholder="Automático">
                                            </div>
                                            <div class="col-md-3">
                                                <label>&nbsp;</label><br>
                                                <button type="button" class="btn btn-primary" onclick="addConceptToSelected()">
                                                    <i class="fas fa-plus"></i> Agregar
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <label>
                                                    <input type="checkbox" id="select-all-employees"> Seleccionar todos los empleados
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de edición -->
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                <table class="table table-striped table-hover table-sm" id="payroll-edit-table">
                                    <thead class="thead-dark sticky-top">
                                        <tr>
                                            <th style="min-width: 150px;">
                                                <input type="checkbox" id="select-all-employees-main"> Empleado
                                            </th>
                                            <?php foreach ($concepts as $concept): ?>
                                                <th style="min-width: 120px; text-align: center;" 
                                                    class="concept-header concept-<?= $concept['tipo'] ?>"
                                                    title="<?= htmlspecialchars($concept['descripcion']) ?>">
                                                    <small class="d-block text-<?= $concept['tipo'] === 'INGRESO' ? 'success' : 'danger' ?>">
                                                        <?= htmlspecialchars($concept['tipo']) ?>
                                                    </small>
                                                    <?= htmlspecialchars(mb_strimwidth($concept['descripcion'], 0, 15, '...')) ?>
                                                </th>
                                            <?php endforeach; ?>
                                            <th style="min-width: 100px;">Totales</th>
                                            <th style="min-width: 100px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employeeConceptMatrix as $employeeId => $employeeData): ?>
                                            <tr data-employee-id="<?= $employeeId ?>" data-detail-id="<?= $employeeData['detail_id'] ?>">
                                                <td class="employee-cell">
                                                    <input type="checkbox" class="employee-select" value="<?= $employeeId ?>">
                                                    <div class="employee-info">
                                                        <strong><?= htmlspecialchars($employeeData['employee_name']) ?></strong><br>
                                                        <small class="text-muted"><?= htmlspecialchars($employeeData['employee_code']) ?></small>
                                                        <?php if ($employeeData['manual_edits']['has_manual_edits']): ?>
                                                            <br><span class="badge badge-warning badge-sm" title="Tiene <?= $employeeData['manual_edits']['edited_concepts_count'] ?> concepto(s) editado(s) manualmente">
                                                                <i class="fas fa-edit"></i> Manual
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                
                                                <?php 
                                                // Crear array indexado por concepto_id para acceso rápido
                                                $employeeConcepts = [];
                                                foreach ($employeeData['concepts'] as $concept) {
                                                    $employeeConcepts[$concept['concepto_id']] = $concept;
                                                }
                                                ?>
                                                
                                                <?php foreach ($concepts as $concept): ?>
                                                    <td class="concept-cell text-center" 
                                                        data-concept-id="<?= $concept['id'] ?>"
                                                        data-detail-id="<?= $employeeData['detail_id'] ?>">
                                                        
                                                        <?php if (isset($employeeConcepts[$concept['id']])): ?>
                                                            <?php 
                                                            $appliedConcept = $employeeConcepts[$concept['id']];
                                                            $isManuallyEdited = in_array($concept['id'], 
                                                                json_decode($details[array_search($employeeData['detail_id'], array_column($details, 'id'))]['conceptos_editados_manual'] ?? '[]', true)
                                                            );
                                                            ?>
                                                            <div class="concept-value-container">
                                                                <input type="number" 
                                                                       class="form-control form-control-sm concept-input <?= $isManuallyEdited ? 'manual-edited' : 'calculated' ?>" 
                                                                       value="<?= number_format($appliedConcept['monto'], 2, '.', '') ?>"
                                                                       data-original-value="<?= number_format($appliedConcept['monto'], 2, '.', '') ?>"
                                                                       step="0.01">
                                                                <div class="concept-actions mt-1">
                                                                    <?php if ($isManuallyEdited): ?>
                                                                        <button type="button" class="btn btn-xs btn-outline-info restore-calculated-btn"
                                                                                title="Restaurar valor calculado">
                                                                            <i class="fas fa-undo"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                    <button type="button" class="btn btn-xs btn-outline-danger remove-concept-btn"
                                                                            title="Quitar concepto">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary add-concept-btn"
                                                                    title="Agregar concepto">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                                
                                                <td class="totals-cell">
                                                    <div class="employee-totals">
                                                        <small class="d-block text-success">
                                                            <strong>Ing: </strong>
                                                            <span class="total-ingresos"><?= number_format($details[array_search($employeeData['detail_id'], array_column($details, 'id'))]['total_ingresos'] ?? 0, 2) ?></span>
                                                        </small>
                                                        <small class="d-block text-danger">
                                                            <strong>Ded: </strong>
                                                            <span class="total-deducciones"><?= number_format($details[array_search($employeeData['detail_id'], array_column($details, 'id'))]['total_deducciones'] ?? 0, 2) ?></span>
                                                        </small>
                                                        <small class="d-block text-primary">
                                                            <strong>Neto: </strong>
                                                            <span class="salario-neto"><?= number_format($details[array_search($employeeData['detail_id'], array_column($details, 'id'))]['salario_neto'] ?? 0, 2) ?></span>
                                                        </small>
                                                    </div>
                                                </td>
                                                
                                                <td class="actions-cell">
                                                    <div class="btn-group-vertical">
                                                        <button type="button" class="btn btn-sm btn-outline-info recalculate-employee-btn"
                                                                title="Recalcular empleado">
                                                            <i class="fas fa-calculator"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary view-details-btn"
                                                                title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="<?= \App\Core\UrlHelper::payroll($payroll['id']) ?>" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Volver a la Planilla
                                    </a>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="button" class="btn btn-success" onclick="saveAllChanges()">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</div>

<!-- CSS personalizado -->
<style>
.concept-header.concept-INGRESO {
    background-color: #d4edda;
}

.concept-header.concept-DEDUCCION {
    background-color: #f8d7da;
}

.concept-input.manual-edited {
    background-color: #fff3cd;
    border-color: #ffeaa7;
}

.concept-input.calculated {
    background-color: #d1ecf1;
    border-color: #bee5eb;
}

.concept-value-container {
    position: relative;
}

.concept-actions {
    display: none;
}

.concept-cell:hover .concept-actions {
    display: block;
}

.employee-info {
    max-width: 150px;
}

.btn-xs {
    padding: 0.125rem 0.25rem;
    font-size: 0.75rem;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

#payroll-edit-table th {
    background-color: #343a40;
    color: white;
}
</style>

<?php
$scripts = '
<script>
let payrollId = ' . $payroll["id"] . ';
let csrfToken = "' . $csrf_token . '";
let unsavedChanges = false;

$(document).ready(function() {
    // Configurar eventos
    setupEventListeners();
    
    // Prevenir pérdida de datos
    window.addEventListener("beforeunload", function(e) {
        if (unsavedChanges) {
            e.preventDefault();
            e.returnValue = "";
        }
    });
});

function setupEventListeners() {
    // Cambios en inputs de conceptos
    $(document).on("input", ".concept-input", function() {
        const $input = $(this);
        const originalValue = parseFloat($input.data("original-value"));
        const currentValue = parseFloat($input.val()) || 0;
        
        if (Math.abs(originalValue - currentValue) > 0.01) {
            $input.addClass("changed");
            unsavedChanges = true;
        } else {
            $input.removeClass("changed");
        }
        
        // Auto-save after 2 seconds of no changes
        clearTimeout($input.data("timeout"));
        $input.data("timeout", setTimeout(function() {
            updateConceptValue($input);
        }, 2000));
    });
    
    // Botón agregar concepto
    $(document).on("click", ".add-concept-btn", function() {
        const $cell = $(this).closest(".concept-cell");
        const conceptId = $cell.data("concept-id");
        const detailId = $cell.data("detail-id");
        addConceptToEmployee(detailId, conceptId, $cell);
    });
    
    // Botón remover concepto
    $(document).on("click", ".remove-concept-btn", function() {
        const $cell = $(this).closest(".concept-cell");
        const conceptId = $cell.data("concept-id");
        const detailId = $cell.data("detail-id");
        removeConceptFromEmployee(detailId, conceptId, $cell);
    });
    
    // Botón restaurar calculado
    $(document).on("click", ".restore-calculated-btn", function() {
        const $cell = $(this).closest(".concept-cell");
        const conceptId = $cell.data("concept-id");
        const detailId = $cell.data("detail-id");
        restoreCalculatedValue(detailId, conceptId, $cell);
    });
    
    // Botón recalcular empleado
    $(document).on("click", ".recalculate-employee-btn", function() {
        const $row = $(this).closest("tr");
        const detailId = $row.data("detail-id");
        recalculateEmployee(detailId, $row);
    });
    
    // Checkboxes de selección
    $("#select-all-employees, #select-all-employees-main").change(function() {
        $(".employee-select").prop("checked", this.checked);
    });
}

function toggleConceptsPanel() {
    $("#add-concepts-panel").toggle();
}

function updateConceptValue($input) {
    const $cell = $input.closest(".concept-cell");
    const conceptId = $cell.data("concept-id");
    const detailId = $cell.data("detail-id");
    const newValue = parseFloat($input.val()) || 0;
    
    showLoadingInCell($cell);
    
    $.ajax({
        url: "/panel/payrolls/update-concept-value",
        method: "POST",
        data: {
            detail_id: detailId,
            concept_id: conceptId,
            value: newValue,
            payroll_id: payrollId,
            csrf_token: csrfToken
        },
        success: function(response) {
            if (response.success) {
                $input.data("original-value", newValue);
                $input.removeClass("changed calculated").addClass("manual-edited");
                
                // Actualizar totales del empleado
                updateEmployeeTotals($cell.closest("tr"));
                
                showToast("Valor actualizado correctamente", "success");
            } else {
                showToast("Error: " + response.message, "error");
                $input.val($input.data("original-value")); // Restaurar valor anterior
            }
        },
        error: function(xhr) {
            const response = JSON.parse(xhr.responseText);
            showToast("Error: " + response.message, "error");
            $input.val($input.data("original-value")); // Restaurar valor anterior
        },
        complete: function() {
            hideLoadingInCell($cell);
        }
    });
}

function addConceptToEmployee(detailId, conceptId, $cell) {
    const initialValue = null; // Por ahora sin valor inicial específico
    
    showLoadingInCell($cell);
    
    $.ajax({
        url: "/panel/payrolls/add-employee-concept",
        method: "POST",
        data: {
            detail_id: detailId,
            concept_id: conceptId,
            initial_value: initialValue,
            payroll_id: payrollId,
            csrf_token: csrfToken
        },
        success: function(response) {
            if (response.success) {
                // Reemplazar botón + con input
                const inputHtml = createConceptInput(response.applied_value, false);
                $cell.html(inputHtml);
                
                // Actualizar totales del empleado
                updateEmployeeTotals($cell.closest("tr"));
                
                showToast("Concepto agregado correctamente", "success");
            } else {
                showToast("Error: " + response.message, "error");
            }
        },
        error: function(xhr) {
            const response = JSON.parse(xhr.responseText);
            showToast("Error: " + response.message, "error");
        },
        complete: function() {
            hideLoadingInCell($cell);
        }
    });
}

function removeConceptFromEmployee(detailId, conceptId, $cell) {
    if (!confirm("¿Está seguro de quitar este concepto del empleado?")) {
        return;
    }
    
    showLoadingInCell($cell);
    
    $.ajax({
        url: "/panel/payrolls/remove-employee-concept",
        method: "POST",
        data: {
            detail_id: detailId,
            concept_id: conceptId,
            payroll_id: payrollId,
            csrf_token: csrfToken
        },
        success: function(response) {
            if (response.success) {
                // Reemplazar input con botón +
                $cell.html("<button type=\"button\" class=\"btn btn-sm btn-outline-secondary add-concept-btn\" title=\"Agregar concepto\"><i class=\"fas fa-plus\"></i></button>");
                
                // Actualizar totales del empleado
                updateEmployeeTotals($cell.closest("tr"));
                
                showToast("Concepto removido correctamente", "success");
            } else {
                showToast("Error: " + response.message, "error");
            }
        },
        error: function(xhr) {
            const response = JSON.parse(xhr.responseText);
            showToast("Error: " + response.message, "error");
        },
        complete: function() {
            hideLoadingInCell($cell);
        }
    });
}

function restoreCalculatedValue(detailId, conceptId, $cell) {
    showLoadingInCell($cell);
    
    $.ajax({
        url: "/panel/payrolls/restore-calculated-value",
        method: "POST",
        data: {
            detail_id: detailId,
            concept_id: conceptId,
            payroll_id: payrollId,
            csrf_token: csrfToken
        },
        success: function(response) {
            if (response.success) {
                const $input = $cell.find(".concept-input");
                $input.val(response.calculated_value);
                $input.data("original-value", response.calculated_value);
                $input.removeClass("manual-edited changed").addClass("calculated");
                
                // Remover botón de restaurar
                $cell.find(".restore-calculated-btn").remove();
                
                // Actualizar totales del empleado
                updateEmployeeTotals($cell.closest("tr"));
                
                showToast("Valor restaurado correctamente", "success");
            } else {
                showToast("Error: " + response.message, "error");
            }
        },
        error: function(xhr) {
            const response = JSON.parse(xhr.responseText);
            showToast("Error: " + response.message, "error");
        },
        complete: function() {
            hideLoadingInCell($cell);
        }
    });
}

function recalculateEmployee(detailId, $row) {
    showLoadingInRow($row);
    
    $.ajax({
        url: "/panel/payrolls/recalculate-employee",
        method: "POST",
        data: {
            detail_id: detailId,
            payroll_id: payrollId,
            csrf_token: csrfToken
        },
        success: function(response) {
            if (response.success) {
                // Actualizar totales visuales
                $row.find(".total-ingresos").text(parseFloat(response.new_totals.total_ingresos).toFixed(2));
                $row.find(".total-deducciones").text(parseFloat(response.new_totals.total_deducciones).toFixed(2));
                $row.find(".salario-neto").text(parseFloat(response.new_totals.salario_neto).toFixed(2));
                
                showToast("Empleado recalculado correctamente", "success");
                
                // Recargar la página para mostrar todos los cambios
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast("Error: " + response.message, "error");
            }
        },
        error: function(xhr) {
            const response = JSON.parse(xhr.responseText);
            showToast("Error: " + response.message, "error");
        },
        complete: function() {
            hideLoadingInRow($row);
        }
    });
}

function createConceptInput(value, isManuallyEdited) {
    const inputClass = isManuallyEdited ? "manual-edited" : "calculated";
    return `
        <div class="concept-value-container">
            <input type="number" 
                   class="form-control form-control-sm concept-input ${inputClass}" 
                   value="${parseFloat(value).toFixed(2)}"
                   data-original-value="${parseFloat(value).toFixed(2)}"
                   step="0.01">
            <div class="concept-actions mt-1">
                ${isManuallyEdited ? "<button type=\"button\" class=\"btn btn-xs btn-outline-info restore-calculated-btn\" title=\"Restaurar valor calculado\"><i class=\"fas fa-undo\"></i></button>" : ""}
                <button type="button" class="btn btn-xs btn-outline-danger remove-concept-btn" title="Quitar concepto"><i class="fas fa-times"></i></button>
            </div>
        </div>
    `;
}

function updateEmployeeTotals($row) {
    // Esta función será llamada después de cada cambio para recalcular visualmente los totales
    // Por simplicidad, se podría hacer una llamada AJAX para obtener los nuevos totales
    // o calcular localmente basado en los inputs visibles
}

function showLoadingInCell($cell) {
    $cell.html("<div class=\"text-center\"><i class=\"fas fa-spinner fa-spin\"></i></div>");
}

function hideLoadingInCell($cell) {
    // El contenido será reemplazado por la función que llame a esto
}

function showLoadingInRow($row) {
    $row.addClass("table-warning");
}

function hideLoadingInRow($row) {
    $row.removeClass("table-warning");
}

function showToast(message, type) {
    // Implementación simple de toast usando AdminLTE
    $(document).Toasts("create", {
        class: type === "success" ? "bg-success" : "bg-danger",
        title: type === "success" ? "Éxito" : "Error",
        body: message,
        autohide: true,
        delay: 3000
    });
}

function saveAllChanges() {
    if (!unsavedChanges) {
        showToast("No hay cambios pendientes", "info");
        return;
    }
    
    // Forzar guardado de todos los inputs modificados
    $(".concept-input.changed").each(function() {
        updateConceptValue($(this));
    });
    
    setTimeout(() => {
        unsavedChanges = false;
        showToast("Todos los cambios han sido guardados", "success");
    }, 2000);
}

function recalculateAllEmployees() {
    if (!confirm("¿Está seguro de recalcular todos los empleados? Esto puede tomar unos momentos.")) {
        return;
    }
    
    const $rows = $("#payroll-edit-table tbody tr");
    let completed = 0;
    const total = $rows.length;
    
    $rows.each(function(index) {
        const $row = $(this);
        const detailId = $row.data("detail-id");
        
        setTimeout(() => {
            recalculateEmployee(detailId, $row);
        }, index * 500); // Espaciar las llamadas para evitar sobrecarga
    });
}
</script>';
?>

