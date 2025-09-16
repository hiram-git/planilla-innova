<?php
$title = 'Detalle Tipo de Acumulado';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-eye"></i> Detalle del Tipo de Acumulado: <strong><?= htmlspecialchars($tipoAcumulado['codigo']) ?></strong>
                </h3>
                <div class="card-tools">
                    <a href="<?= url('/panel/tipos-acumulados/edit/' . $tipoAcumulado['id']) ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="<?= url('/panel/tipos-acumulados') ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Información Principal -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-info-circle"></i> Información General</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-code"></i> Código:</strong>
                                        <p class="text-muted"><?= htmlspecialchars($tipoAcumulado['codigo']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-tag"></i> Descripción:</strong>
                                        <p class="text-muted"><?= htmlspecialchars($tipoAcumulado['descripcion']) ?></p>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-calendar-alt"></i> Periodicidad:</strong>
                                        <p>
                                            <?php
                                            $periodicidadClass = match($tipoAcumulado['periodicidad']) {
                                                'MENSUAL' => 'badge-info',
                                                'TRIMESTRAL' => 'badge-warning', 
                                                'SEMESTRAL' => 'badge-primary',
                                                'ANUAL' => 'badge-success',
                                                'ESPECIAL' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $periodicidadClass ?>">
                                                <?= $tipoAcumulado['periodicidad'] ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-sync"></i> Reinicio Automático:</strong>
                                        <p>
                                            <?php if ($tipoAcumulado['reinicia_automaticamente']): ?>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Automático
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-hand-paper"></i> Manual
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-power-off"></i> Estado:</strong>
                                        <p>
                                            <?php if ($tipoAcumulado['activo']): ?>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle"></i> Activo
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-times-circle"></i> Inactivo
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Período Actual -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-clock"></i> Período Actual</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-calendar-check"></i> Fecha Inicio:</strong>
                                        <p class="text-muted">
                                            <?php if ($tipoAcumulado['fecha_inicio_periodo']): ?>
                                                <?= date('d/m/Y', strtotime($tipoAcumulado['fecha_inicio_periodo'])) ?>
                                            <?php else: ?>
                                                <span class="text-muted">No definida</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-calendar-times"></i> Fecha Fin:</strong>
                                        <p class="text-muted">
                                            <?php if ($tipoAcumulado['fecha_fin_periodo']): ?>
                                                <?= date('d/m/Y', strtotime($tipoAcumulado['fecha_fin_periodo'])) ?>
                                            <?php else: ?>
                                                <span class="text-muted">No definida</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <?php if ($tipoAcumulado['fecha_inicio_periodo'] && $tipoAcumulado['fecha_fin_periodo']): ?>
                                <div class="row">
                                    <div class="col-12">
                                        <strong><i class="fas fa-hourglass-half"></i> Duración del Período:</strong>
                                        <p class="text-muted">
                                            <?php 
                                            $inicio = new DateTime($tipoAcumulado['fecha_inicio_periodo']);
                                            $fin = new DateTime($tipoAcumulado['fecha_fin_periodo']);
                                            $diferencia = $inicio->diff($fin);
                                            echo $diferencia->days . ' días';
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Estadísticas -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-chart-bar"></i> Estadísticas</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-box mb-3">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-list"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Conceptos Asociados</span>
                                        <span class="info-box-number"><?= $tipoAcumulado['conceptos_asociados'] ?? 0 ?></span>
                                    </div>
                                </div>

                                <?php if (($tipoAcumulado['conceptos_asociados'] ?? 0) > 0): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Este tipo de acumulado está siendo utilizado por <strong><?= $tipoAcumulado['conceptos_asociados'] ?></strong> concepto(s).
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Información del Sistema -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-cogs"></i> Información del Sistema</h5>
                            </div>
                            <div class="card-body">
                                <p><strong><i class="fas fa-plus-circle"></i> Creado:</strong><br>
                                   <small class="text-muted"><?= date('d/m/Y H:i:s', strtotime($tipoAcumulado['created_at'])) ?></small></p>
                                
                                <?php if ($tipoAcumulado['updated_at'] != $tipoAcumulado['created_at']): ?>
                                <p><strong><i class="fas fa-edit"></i> Última Actualización:</strong><br>
                                   <small class="text-muted"><?= date('d/m/Y H:i:s', strtotime($tipoAcumulado['updated_at'])) ?></small></p>
                                <?php endif; ?>
                                
                                <p><strong><i class="fas fa-key"></i> ID:</strong><br>
                                   <small class="text-muted"><?= $tipoAcumulado['id'] ?></small></p>
                            </div>
                        </div>

                        <!-- Descripción de Periodicidad -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-question-circle"></i> ¿Qué significa esto?</h5>
                            </div>
                            <div class="card-body">
                                <small>
                                    <strong>Periodicidad <?= $tipoAcumulado['periodicidad'] ?>:</strong><br>
                                    <?php
                                    $descripcionPeriodicidad = match($tipoAcumulado['periodicidad']) {
                                        'MENSUAL' => 'Se reinicia cada mes',
                                        'TRIMESTRAL' => 'Se reinicia cada 3 meses',
                                        'SEMESTRAL' => 'Se reinicia cada 6 meses', 
                                        'ANUAL' => 'Se reinicia cada año',
                                        'ESPECIAL' => 'Reinicio según necesidad específica',
                                        default => 'Periodicidad no definida'
                                    };
                                    echo $descripcionPeriodicidad;
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-box {
    display: flex;
    margin-bottom: 1rem;
    min-height: 80px;
    padding: .5rem;
    position: relative;
    width: 100%;
    background: #fff;
    border-radius: 0.25rem;
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
}

.info-box .info-box-icon {
    border-radius: .25rem;
    align-items: center;
    display: flex;
    font-size: 1.875rem;
    justify-content: center;
    text-align: center;
    width: 70px;
    color: #fff;
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

.bg-info {
    background-color: #17a2b8 !important;
}

.card {
    margin-bottom: 1rem;
}
</style>