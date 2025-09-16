<?php
/**
 * Vista: Detalle de Planilla
 */
$title = 'Detalle de Planilla: ' . htmlspecialchars($payroll['descripcion']);

// Configurar badge del estado
$statusClass = '';
$statusIcon = '';
switch ($payroll['estado']) {
    case 'PENDIENTE':
        $statusClass = 'badge-warning';
        $statusIcon = 'fas fa-clock';
        break;
    case 'PROCESADA':
        $statusClass = 'badge-success';
        $statusIcon = 'fas fa-check';
        break;
    case 'CERRADA':
    case 'cerrada':
        $statusClass = 'badge-primary';
        $statusIcon = 'fas fa-lock';
        break;
    case 'ANULADA':
        $statusClass = 'badge-danger';
        $statusIcon = 'fas fa-times';
        break;
}
?>

<!-- Botones de acción -->
<div class="row mb-3">
    <div class="col-sm-12">
        <div class="float-right">
            <div class="btn-group" role="group">
                <?php if ($payroll['estado'] === 'PENDIENTE'): ?>
                    <a href="<?= \App\Core\UrlHelper::route('panel/payrolls/' . $payroll['id'] . '/edit') ?>" 
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                <?php elseif ($payroll['estado'] === 'PROCESADA'): ?>
                    <a href="<?= \App\Core\UrlHelper::route('panel/payrolls/' . $payroll['id'] . '/editDetails') ?>" 
                       class="btn btn-warning btn-sm">
                        <i class="fas fa-table"></i> Editar Detalles
                    </a>
                <?php endif; ?>
                
                <a href="<?= \App\Core\UrlHelper::route('panel/payrolls') ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Información de la planilla -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Información de la Planilla
                </h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">ID:</dt>
                    <dd class="col-sm-9"><strong><?= $payroll['id'] ?></strong></dd>
                    
                    <dt class="col-sm-3">Descripción:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($payroll['descripcion']) ?></dd>
                    
                    <dt class="col-sm-3">Estado:</dt>
                    <dd class="col-sm-9">
                        <span class="badge <?= $statusClass ?>">
                            <i class="<?= $statusIcon ?>"></i> <?= $payroll['estado'] ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-3">Fecha:</dt>
                    <dd class="col-sm-9"><?= !empty($payroll['fecha']) ? date('d/m/Y', strtotime($payroll['fecha'])) : 'N/A' ?></dd>
                    
                    <dt class="col-sm-3">Período:</dt>
                    <dd class="col-sm-9">
                        <?= !empty($payroll['fecha_desde']) ? date('d/m/Y', strtotime($payroll['fecha_desde'])) : 'N/A' ?> 
                        al 
                        <?= !empty($payroll['fecha_hasta']) ? date('d/m/Y', strtotime($payroll['fecha_hasta'])) : 'N/A' ?>
                    </dd>

                    <?php if (!empty($payroll['observaciones'])): ?>
                        <dt class="col-sm-3">Observaciones:</dt>
                        <dd class="col-sm-9"><?= nl2br(htmlspecialchars($payroll['observaciones'])) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($payroll['fecha_procesamiento'])): ?>
                        <dt class="col-sm-3">Procesada:</dt>
                        <dd class="col-sm-9"><?= date('d/m/Y H:i', strtotime($payroll['fecha_procesamiento'])) ?></dd>
                    <?php elseif ($payroll['estado'] !== 'PENDIENTE'): ?>
                        <dt class="col-sm-3">Procesada:</dt>
                        <dd class="col-sm-9"><?= !empty($payroll['created_at']) ? date('d/m/Y H:i', strtotime($payroll['created_at'])) : 'N/A' ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie"></i> Estadísticas
                </h3>
            </div>
            <div class="card-body">
                <?php if ($stats && $stats['total_empleados'] > 0): ?>
                    <div class="row">
                        <div class="col-6 text-center">
                            <div class="border-right">
                                <div class="h4 text-primary"><?= $stats['total_empleados'] ?></div>
                                <small class="text-muted">Empleados</small>
                            </div>
                        </div>
                        <div class="col-6 text-center">
                            <div class="h4 text-success"><?= currency_symbol() ?><?= number_format($stats['total_neto'], 2) ?></div>
                            <small class="text-muted">Total Neto</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="text-success">
                                <i class="fas fa-plus-circle"></i> Ingresos
                            </div>
                            <div class="h5"><?= currency_symbol() ?><?= number_format($stats['total_ingresos'], 2) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-danger">
                                <i class="fas fa-minus-circle"></i> Deducciones
                            </div>
                            <div class="h5"><?= currency_symbol() ?><?= number_format($stats['total_deducciones'], 2) ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <p>No hay empleados procesados en esta planilla</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Lista de empleados -->
<?php if ($payroll['estado'] !== 'PENDIENTE' || (isset($stats) && $stats['total_empleados'] > 0)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users"></i> Empleados en la Planilla
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" onclick="refreshEmployeesTable()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="employeesTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Posición</th>
                                <th>Ingresos</th>
                                <th>Deducciones</th>
                                <th>Neto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h4>No hay empleados en esta planilla</h4>
                <?php if ($payroll['estado'] === 'PENDIENTE'): ?>
                    <p class="text-muted">Procese la planilla para generar los datos de empleados.</p>
                    <button type="button" class="btn btn-success" id="processBtn2" 
                            data-id="<?= $payroll['id'] ?>" 
                            data-description="<?= htmlspecialchars($payroll['descripcion']) ?>">
                        <i class="fas fa-play"></i> Procesar Planilla
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal de Progreso General -->
<div class="modal fade" id="progressModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h4 class="modal-title text-white">
                    <i class="fas fa-cog fa-spin"></i> <span id="progressModalTitle">Procesando</span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <h5 id="progressStatus">Procesando...</h5>
                    <p class="text-muted">Por favor espere mientras se completa el proceso.</p>
                </div>
                
                <!-- Barra de progreso -->
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" id="progressBar" style="width: 0%">
                        <span id="progressPercentage">0%</span>
                    </div>
                </div>
                
                <!-- Información de progreso -->
                <div class="row text-center">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1">Tiempo Transcurrido</h6>
                                <span id="progressTimer">00:00</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1">Estado Actual</h6>
                                <span id="progressDetails">Iniciando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php 
$styles = '<link rel="stylesheet" href="' . url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css', false) . '">';

$scripts = '
<script src="' . url('plugins/datatables/jquery.dataTables.min.js', false) . '"></script>
<script src="' . url('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js', false) . '"></script>
<script>
$(document).ready(function() {
    // Configuración de la vista
    const PAYROLL_CONFIG = {
        id: ' . $payroll['id'] . ',
        description: "' . addslashes($payroll['descripcion']) . '",
        estado: "' . $payroll['estado'] . '",
        urls: {
            employeesData: "' . \App\Core\Config::get('app.url') . '/panel/payrolls/' . $payroll['id'] . '/employees-data",
            regenerateEmployee: "' . \App\Core\Config::get('app.url') . '/panel/payrolls/' . $payroll['id'] . '/regenerate-employee"
        },
        csrfToken: "' . \App\Core\Security::generateToken() . '"
    };

    // COMPATIBILIDAD: Crear window.PAYROLL_CONFIG para el módulo
    window.PAYROLL_CONFIG = PAYROLL_CONFIG;

    // Configuración de DataTable en español
    const spanishConfig = {
        "emptyTable": "No hay datos disponibles en la tabla",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
        "infoFiltered": "(filtrado de _MAX_ entradas totales)",
        "lengthMenu": "Mostrar _MENU_ entradas",
        "loadingRecords": "Cargando...",
        "processing": "Procesando...",
        "search": "Buscar:",
        "zeroRecords": "No se encontraron registros coincidentes",
        "paginate": {
            "first": "Primero",
            "last": "Último",
            "next": "Siguiente", 
            "previous": "Anterior"
        }
    };

    // Inicializar DataTable de empleados
    window.employeesDataTable = $("#employeesTable").DataTable({
        "processing": true,
        "serverSide": true,
        "language": spanishConfig,
        "ajax": {
            "url": PAYROLL_CONFIG.urls.employeesData,
            "dataSrc": function(json) {
                console.log("DataTable AJAX response:", json);
                if (json.error) {
                    console.error("Server error:", json.error);
                }
                return json.data;
            },
            "error": function(xhr, error, code) {
                console.error("DataTable AJAX error:", {xhr, error, code});
            }
        },
        "columns": [
            { 
                "title": "Empleado", 
                "data": null,
                "render": function(data, type, row) {
                    return row[0] || row.employee_name || "N/A";
                }
            },
            { 
                "title": "Posición", 
                "data": null,
                "render": function(data, type, row) {
                    return row[1] || row.position_name || "Sin posición";
                }
            },
            { 
                "title": "Total Ingresos", 
                "data": null,
                "render": function(data, type, row) {
                    return row[2] || row.total_ingresos || "$0.00";
                }
            },
            { 
                "title": "Total Deducciones", 
                "data": null,
                "render": function(data, type, row) {
                    return row[3] || row.total_deducciones || "$0.00";
                }
            },
            { 
                "title": "Salario Neto", 
                "data": null,
                "render": function(data, type, row) {
                    return row[4] || row.salario_neto || "$0.00";
                }
            },
            { 
                "title": "Acciones", 
                "data": null,
                "orderable": false,
                "render": function(data, type, row) {
                    return row[5] || row.actions || "";
                }
            }
        ],
        "pageLength": 25,
        "order": [[0, "asc"]],
        "responsive": true,
        "autoWidth": false
    });

    // Función para regenerar empleado
    window.regenerateEmployee = function(employeeId) {
        if (!confirm("¿Está seguro que desea regenerar este empleado? Esto eliminará sus datos actuales y los recalculará.")) {
            return;
        }

        $.ajax({
            url: PAYROLL_CONFIG.urls.regenerateEmployee,
            type: "POST",
            data: {
                employee_id: employeeId,
                csrf_token: PAYROLL_CONFIG.csrfToken
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    alert("Empleado regenerado exitosamente");
                    window.employeesDataTable.ajax.reload();
                } else {
                    alert("Error: " + (response.message || "Error desconocido"));
                }
            },
            error: function(xhr, status, error) {
                console.error("Error regenerating employee:", {xhr, status, error});
                alert("Error regenerando empleado");
            }
        });
    };

});
</script>';
?>

<style>
.badge {
    font-size: 0.85em;
}
.table th, .table td {
    vertical-align: middle;
}
.border-right {
    border-right: 1px solid #dee2e6 !important;
}
.btn-group .btn {
    margin-right: 2px;
}
.btn-group .btn:last-child {
    margin-right: 0;
}
</style>