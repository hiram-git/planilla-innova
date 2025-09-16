<?php
$title = 'Tipos de Acumulados';

// JavaScript optimizado modular
$scripts = '
<script src="' . url('assets/js/modules/tipos-acumulados.js', false) . '"></script>
<script>
// Inicializar el módulo con URLs dinámicas
TiposAcumuladosModule.init({
    toggleStatus: "' . url('/panel/tipos-acumulados/toggle-status') . '",
    delete: "' . url('/panel/tipos-acumulados/delete') . '",
    csrfToken: "' . \App\Core\Security::generateToken() . '"
});
</script>';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-database"></i> Gestión de Tipos de Acumulados
                    <a href="<?= url('/panel/tipos-acumulados/create') ?>" class="btn btn-primary btn-sm ml-3">
                        <i class="fas fa-plus"></i> Nuevo Tipo
                    </a>
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 300px;">
                        <select id="filterPeriodicidad" class="form-control" style="width: 120px;">
                            <option value="">Todas las periodicidades</option>
                            <option value="MENSUAL">Mensual</option>
                            <option value="TRIMESTRAL">Trimestral</option>
                            <option value="SEMESTRAL">Semestral</option>
                            <option value="ANUAL">Anual</option>
                            <option value="ESPECIAL">Especial</option>
                        </select>
                        <input type="text" id="searchInput" class="form-control float-right" placeholder="Buscar tipo...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tiposTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Periodicidad</th>
                                <th>Período Actual</th>
                                <th>Reinicio Auto</th>
                                <th>Conceptos</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if (!empty($tiposAcumulados)): ?>
    <?php foreach ($tiposAcumulados as $tipo): 
        // Estado
        $estadoClass = $tipo['activo'] ? 'badge-success' : 'badge-secondary';
        $estadoText = $tipo['activo'] ? 'Activo' : 'Inactivo';
        $estadoIcon = $tipo['activo'] ? 'fas fa-check-circle' : 'fas fa-times-circle';
        
        // Periodicidad
        $periodicidadClass = match($tipo['periodicidad']) {
            'MENSUAL' => 'badge-info',
            'TRIMESTRAL' => 'badge-warning',
            'SEMESTRAL' => 'badge-primary',
            'ANUAL' => 'badge-success',
            'ESPECIAL' => 'badge-danger',
            default => 'badge-secondary'
        };
        
        // Reinicio automático
        $reinicioIcon = $tipo['reinicia_automaticamente'] ? 'fas fa-sync text-success' : 'fas fa-hand-paper text-warning';
        $reinicioText = $tipo['reinicia_automaticamente'] ? 'Automático' : 'Manual';
        
        // Período actual
        $periodoActual = '';
        if ($tipo['fecha_inicio_periodo'] && $tipo['fecha_fin_periodo']) {
            $periodoActual = date('d/m/Y', strtotime($tipo['fecha_inicio_periodo'])) . ' - ' . date('d/m/Y', strtotime($tipo['fecha_fin_periodo']));
        }
    ?>
                            <tr>
                                <td>
                                    <strong class="text-primary"><?= htmlspecialchars($tipo['codigo']) ?></strong>
                                </td>
                                <td>
                                    <div class="tipo-info">
                                        <strong><?= htmlspecialchars($tipo['descripcion']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $periodicidadClass ?>">
                                        <i class="fas fa-calendar-alt"></i> <?= $tipo['periodicidad'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($periodoActual): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?= $periodoActual ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">Sin período definido</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <i class="<?= $reinicioIcon ?>" title="<?= $reinicioText ?>"></i>
                                    <br><small class="text-muted"><?= $reinicioText ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="conceptos-count">
                                        <strong class="text-info"><?= $tipo['conceptos_asociados'] ?? 0 ?></strong>
                                        <br><small class="text-muted">conceptos</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $estadoClass ?>">
                                        <i class="<?= $estadoIcon ?>"></i> <?= $estadoText ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= url('/panel/tipos-acumulados/show/' . $tipo['id']) ?>" 
                                           class="btn btn-info btn-sm"
                                           data-toggle="tooltip" title="Ver Detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= url('/panel/tipos-acumulados/edit/' . $tipo['id']) ?>" 
                                           class="btn btn-warning btn-sm"
                                           data-toggle="tooltip" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn <?= $tipo['activo'] ? 'btn-secondary' : 'btn-success' ?> btn-sm toggle-status" 
                                                data-id="<?= $tipo['id'] ?>"
                                                data-current-status="<?= $tipo['activo'] ?>"
                                                data-toggle="tooltip" title="<?= $tipo['activo'] ? 'Desactivar' : 'Activar' ?>">
                                            <i class="fas <?= $tipo['activo'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                                data-id="<?= $tipo['id'] ?>" 
                                                data-codigo="<?= htmlspecialchars($tipo['codigo']) ?>"
                                                data-conceptos-count="<?= $tipo['conceptos_asociados'] ?? 0 ?>"
                                                data-toggle="tooltip" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
    <?php endforeach; ?>
<?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> No hay tipos de acumulados registrados
                                </td>
                            </tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmar Eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirmar Eliminación</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el tipo <strong id="deleteTipoCodigo"></strong>?</p>
                <div id="warningConceptos" class="text-warning" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Este tipo tiene <span id="conceptosCount"></span> concepto(s) asociado(s).
                </div>
                <p class="text-danger mt-2">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<style>
.tipo-info {
    min-height: 30px;
}

.conceptos-count strong {
    font-size: 1.1em;
}

.info-box {
    display: flex;
    margin-bottom: 20px;
    min-height: 80px;
    padding: .5rem;
    position: relative;
    width: 100%;
}

.info-box .info-box-icon {
    border-radius: .25rem;
    align-items: center;
    display: flex;
    font-size: 1.875rem;
    justify-content: center;
    text-align: center;
    width: 70px;
}

.info-box .info-box-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    line-height: 1.8;
    flex: 1;
    padding: 0 10px;
}

.info-box .info-box-number {
    display: block;
    margin-top: .25rem;
    font-size: 1.125rem;
    font-weight: 700;
}

.info-box .info-box-text {
    display: block;
    font-size: .875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.btn-group .btn {
    border-radius: 0.25rem;
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.badge {
    font-size: 0.75em;
}
</style>