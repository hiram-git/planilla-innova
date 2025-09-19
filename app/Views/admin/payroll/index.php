<?php
/**
 * Vista: Lista de Planillas
 */
$title = $data['page_title'] ?? 'Gestión de Planillas';
$csrf_token = $data['csrf_token'] ?? '';
?>

<!-- Tarjeta principal -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Planillas</h3>
                <div class="card-tools">
                    <a href="<?= \App\Core\UrlHelper::payroll('create') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Nueva Planilla
                    </a>
                </div>
            </div>
            <div class="card-body">
                
                <div class="table-responsive">
                    <table id="payrollsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Descripción</th>
                                <th>Tipo Planilla</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Total Empleados</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payrolls as $payroll): ?>
                                <?php
                                $statusClass = [
                                    'PENDIENTE' => 'badge-warning',
                                    'PROCESADA' => 'badge-success', 
                                    'CERRADA' => 'badge-info',
                                    'ANULADA' => 'badge-danger'
                                ];
                                $badgeClass = $statusClass[$payroll['estado']] ?? 'badge-secondary';
                                $fechaPlanilla = !empty($payroll['fecha']) ? date('d/m/Y', strtotime($payroll['fecha'])) : 'N/A';
                                ?>
                                <tr>
                                    <td><strong><?= $payroll['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($payroll['descripcion'] ?? 'Sin descripción') ?></td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <?= htmlspecialchars($payroll['tipo_planilla_nombre'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td><?= $fechaPlanilla ?></td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>"><?= $payroll['estado'] ?? 'PENDIENTE' ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?= $payroll['total_empleados'] ?? 0 ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= \App\Core\UrlHelper::payroll($payroll['id']) ?>" 
                                               class="btn btn-info btn-sm" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($payroll['estado'] == 'PENDIENTE'): ?>
                                                <button type="button" class="btn btn-success btn-sm process-btn" 
                                                        data-id="<?= $payroll['id'] ?>" 
                                                        data-description="<?= htmlspecialchars($payroll['descripcion']) ?>"
                                                        title="Procesar planilla">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($payroll['estado'] == 'PROCESADA'): ?>
                                                <button type="button" class="btn btn-warning btn-sm reprocess-btn" 
                                                        data-id="<?= $payroll['id'] ?>" 
                                                        data-description="<?= htmlspecialchars($payroll['descripcion']) ?>"
                                                        title="Reprocesar planilla">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                                <button type="button" class="btn btn-primary btn-sm close-btn" 
                                                        data-id="<?= $payroll['id'] ?>" 
                                                        data-description="<?= htmlspecialchars($payroll['descripcion']) ?>"
                                                        title="Cerrar planilla">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($payroll['estado'] == 'CERRADA'): ?>
                                                <button type="button" class="btn btn-warning btn-sm reopen-btn" 
                                                        data-id="<?= $payroll['id'] ?>" 
                                                        data-description="<?= htmlspecialchars($payroll['descripcion']) ?>"
                                                        title="Abrir planilla cerrada">
                                                    <i class="fas fa-unlock"></i> Abrir
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($payroll['estado'] == 'PROCESADA'): ?>
                                                <button type="button" class="btn btn-secondary btn-sm mark-pending-btn" 
                                                        data-id="<?= $payroll['id'] ?>" 
                                                        data-description="<?= htmlspecialchars($payroll['descripcion']) ?>"
                                                        title="Marcar como pendiente">
                                                    <i class="fas fa-clock"></i> Pendiente
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array($payroll['estado'], ['PENDIENTE', 'PROCESADA'])): ?>
                                                <button type="button" class="btn btn-danger btn-sm cancel-btn" 
                                                        data-id="<?= $payroll['id'] ?>" 
                                                        data-description="<?= htmlspecialchars($payroll['descripcion']) ?>"
                                                        title="Anular planilla">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <a href="<?= \App\Core\UrlHelper::payroll($payroll['id'] . '/edit') ?>" 
                                               class="btn btn-warning btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if (in_array($payroll['estado'], ['PROCESADA', 'CERRADA'])): ?>
                                                <a href="<?= \App\Core\UrlHelper::route('panel/acumulados/byPayroll/' . $payroll['id']) ?>" 
                                                   class="btn btn-info btn-sm" title="Ver acumulados de esta planilla">
                                                    <i class="fas fa-calculator"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                                    data-id="<?= $payroll['id'] ?>" 
                                                    data-description="<?= htmlspecialchars($payroll['descripcion']) ?>"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
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
                    <div class="col-sm-6">
                        <p class="text-muted mb-0">
                            Total: <strong><?= count($payrolls) ?></strong> planillas
                        </p>
                    </div>
                    <div class="col-sm-6">
                        <div class="float-right">
                            <small class="text-muted">
                                Última actualización: <?= date('d/m/Y H:i:s') ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Procesar -->
<div class="modal fade" id="processModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h4 class="modal-title text-white">
                    <i class="fas fa-play"></i> Procesar Planilla
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" id="modalCloseBtn">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Fase de confirmación -->
                <div id="confirmationPhase">
                    <p>¿Está seguro que desea procesar la planilla <strong id="processPayrollName"></strong>?</p>
                    <div class="callout callout-info">
                        <h5><i class="fas fa-info-circle"></i> Información</h5>
                        <p>Este proceso calculará automáticamente los conceptos para todos los empleados.</p>
                    </div>
                </div>
                
                <!-- Fase de procesamiento -->
                <div id="processingPhase" style="display: none;">
                    <div class="text-center mb-3">
                        <h5><i class="fas fa-cog fa-spin"></i> Procesando Planilla...</h5>
                        <p class="text-muted">Por favor espere, este proceso puede tomar varios minutos.</p>
                    </div>
                    
                    <!-- Barra de progreso -->
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                             role="progressbar" id="progressBar" style="width: 0%">
                            <span id="progressText">0%</span>
                        </div>
                    </div>
                    
                    <!-- Información de progreso -->
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1">Empleados</h6>
                                    <span id="employeesProgress">0 / 0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1">Conceptos</h6>
                                    <span id="conceptsProgress">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1">Tiempo</h6>
                                    <span id="timeProgress">00:00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fase actual -->
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            <span id="currentPhase">Iniciando procesamiento...</span>
                        </small>
                    </div>
                </div>
                
                <!-- Fase de completado -->
                <div id="completedPhase" style="display: none;">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-success">¡Procesamiento Completado!</h5>
                        <div id="completionStats" class="mt-3"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div id="confirmationButtons">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirmProcess">
                        <i class="fas fa-play"></i> Procesar
                    </button>
                </div>
                <div id="processingButtons" style="display: none;">
                    <button type="button" class="btn btn-warning" id="cancelProcess" disabled>
                        <i class="fas fa-stop"></i> Cancelando...
                    </button>
                </div>
                <div id="completedButtons" style="display: none;">
                    <button type="button" class="btn btn-primary" onclick="PayrollModule.reloadDataTable()">
                        <i class="fas fa-sync"></i> Actualizar Lista
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reprocesar -->
<div class="modal fade" id="reprocessModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h4 class="modal-title text-white">
                    <i class="fas fa-redo"></i> Reprocesar Planilla
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" id="reprocessModalCloseBtn">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Fase de confirmación -->
                <div id="reprocessConfirmationPhase">
                    <p>¿Está seguro que desea reprocesar la planilla <strong id="reprocessPayrollName"></strong>?</p>
                    <div class="callout callout-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Advertencia</h5>
                        <p>Esto eliminará los datos actuales y recalculará todos los conceptos.</p>
                    </div>
                </div>
                
                <!-- Fase de procesamiento -->
                <div id="reprocessProcessingPhase" style="display: none;">
                    <div class="text-center mb-3">
                        <h5><i class="fas fa-cog fa-spin"></i> Reprocesando Planilla...</h5>
                        <p class="text-muted">Por favor espere, este proceso puede tomar varios minutos.</p>
                    </div>
                    
                    <!-- Barra de progreso -->
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" 
                             role="progressbar" id="reprocessProgressBar" style="width: 0%">
                            <span id="reprocessProgressText">0%</span>
                        </div>
                    </div>
                    
                    <!-- Información de progreso -->
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1">Empleados</h6>
                                    <span id="reprocessEmployeesProgress">0 / 0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1">Conceptos</h6>
                                    <span id="reprocessConceptsProgress">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1">Tiempo</h6>
                                    <span id="reprocessTimeProgress">00:00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fase actual -->
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            <span id="reprocessCurrentPhase">Iniciando reprocesamiento...</span>
                        </small>
                    </div>
                </div>
                
                <!-- Fase de completado -->
                <div id="reprocessCompletedPhase" style="display: none;">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-success">¡Reprocesamiento Completado!</h5>
                        <div id="reprocessCompletionStats" class="mt-3"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div id="reprocessConfirmationButtons">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="confirmReprocess">
                        <i class="fas fa-redo"></i> Reprocesar
                    </button>
                </div>
                <div id="reprocessProcessingButtons" style="display: none;">
                    <button type="button" class="btn btn-danger" id="cancelReprocess" disabled>
                        <i class="fas fa-stop"></i> Cancelando...
                    </button>
                </div>
                <div id="reprocessCompletedButtons" style="display: none;">
                    <button type="button" class="btn btn-primary" onclick="PayrollModule.reloadDataTable()">
                        <i class="fas fa-sync"></i> Actualizar Lista
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cerrar -->
<div class="modal fade" id="closeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title text-white">
                    <i class="fas fa-lock"></i> Cerrar Planilla
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea cerrar la planilla <strong id="closePayrollName"></strong>?</p>
                <div class="callout callout-info">
                    <h5><i class="fas fa-info-circle"></i> Información</h5>
                    <p>Una vez cerrada, no se podrá modificar.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmClose">
                    <i class="fas fa-lock"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Anular -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h4 class="modal-title text-white">
                    <i class="fas fa-ban"></i> Anular Planilla
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea anular la planilla <strong id="cancelPayrollName"></strong>?</p>
                <div class="callout callout-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Peligro</h5>
                    <p>Esta acción no se puede deshacer.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmCancel">
                    <i class="fas fa-ban"></i> Anular
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h4 class="modal-title text-white">
                    <i class="fas fa-trash"></i> Eliminar Planilla
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar la planilla <strong id="deletePayrollName"></strong>?</p>
                <div class="callout callout-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Peligro</h5>
                    <p>Esta acción eliminará permanentemente todos los datos relacionados.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reabrir Planilla -->
<div class="modal fade" id="reopenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h4 class="modal-title text-white">
                    <i class="fas fa-unlock"></i> Reabrir Planilla
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea reabrir la planilla <strong id="reopenPayrollName"></strong>?</p>
                <div class="callout callout-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> Advertencia</h5>
                    <p>Esta acción realizará un rollback automático de los acumulados procesados.</p>
                </div>
                <div class="form-group">
                    <label for="reopenMotivo">Motivo de reapertura <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reopenMotivo" rows="3" 
                              placeholder="Ingrese el motivo por el cual necesita reabrir esta planilla..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="confirmReopen">
                    <i class="fas fa-unlock"></i> Reabrir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Marcar como Pendiente -->
<div class="modal fade" id="markPendingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary">
                <h4 class="modal-title text-white">
                    <i class="fas fa-clock"></i> Marcar como Pendiente
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea marcar la planilla <strong id="markPendingPayrollName"></strong> como PENDIENTE?</p>
                <div class="callout callout-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> ¡ATENCIÓN!</h5>
                    <p>Esta acción eliminará automáticamente:</p>
                    <ul class="mt-2 mb-0">
                        <li>Todos los detalles de planilla procesados</li>
                        <li>Todos los acumulados generados</li>
                        <li>Registros consolidados asociados</li>
                    </ul>
                </div>
                <div class="callout callout-info">
                    <h5><i class="fas fa-info-circle"></i> Información</h5>
                    <p>La planilla quedará completamente limpia y lista para ser reprocesada desde cero.</p>
                </div>
                <div class="form-group">
                    <label for="markPendingMotivo">Motivo del cambio <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="markPendingMotivo" rows="3" 
                              placeholder="Ingrese el motivo por el cual necesita marcar esta planilla como pendiente..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="confirmMarkPending">
                    <i class="fas fa-clock"></i> Marcar como Pendiente
                </button>
            </div>
        </div>
    </div>
</div>

<?php 
$styles = '<link rel="stylesheet" href="' . url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css', false) . '">';

// Scripts para el módulo usando sistema modular
$scriptFiles = [
    '/plugins/datatables/jquery.dataTables.min.js',
    '/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    '/assets/javascript/modules/payroll/index.js'
];

use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();

// Configuración específica para el módulo de planillas
$payrollConfig = [
    'csrfToken' => $csrf_token, // Usar camelCase para que coincida con JS
    'csrf_token' => $csrf_token, // También mantener snake_case por compatibilidad
    'tiposPlanilla' => $tipos_planilla ?? [], 
    'urls' => [
        'payrolls' => \App\Core\UrlHelper::route('panel/payrolls')
    ]
];

$payrollConfigScript = '<script>
if (typeof window.APP_CONFIG === "undefined") {
    window.APP_CONFIG = {};
}
// Merge the payroll config with existing APP_CONFIG
Object.assign(window.APP_CONFIG, ' . json_encode($payrollConfig, JSON_UNESCAPED_SLASHES) . ');
</script>';

$scripts = $jsConfig . "\n" . $payrollConfigScript . "\n" . JavaScriptHelper::renderScriptTags($scriptFiles);
?>

<style>
.badge {
    font-size: 0.85em;
}
.table th, .table td {
    vertical-align: middle;
}
.btn-group .btn {
    margin-right: 2px;
}
.btn-group .btn:last-child {
    margin-right: 0;
}
</style>