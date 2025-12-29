<?php
use App\Helpers\PermissionHelper;

$page_title = 'Detalle de Expediente';
$employeeName = trim(($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? ''));
$backUrl = \App\Core\UrlHelper::employee(($employee['id'] ?? 0) . '/expedientes');
$extra_fields = $extra_fields ?? [];

ob_start();
?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Expediente de <?= htmlspecialchars($employeeName) ?></h3>
                <div class="card-tools">
                    <a href="<?= $backUrl ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <?php if (PermissionHelper::canWrite('employee-files')): ?>
                        <a href="<?= \App\Core\UrlHelper::route('panel/employee-files/edit/' . $file['id']) ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-tag"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tipo</span>
                                <span class="info-box-number"><?= htmlspecialchars($file['type_name'] ?? '') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-tags"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Subtipo</span>
                                <span class="info-box-number"><?= htmlspecialchars($file['subtype_name'] ?? '') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <strong>Fecha del Documento</strong>
                        <p class="text-muted">
                            <?= !empty($file['document_date']) ? htmlspecialchars(date('d/m/Y', strtotime($file['document_date']))) : '' ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <strong>No. Memorando/Oficio</strong>
                        <p class="text-muted"><?= htmlspecialchars($file['document_number'] ?? '') ?></p>
                    </div>
                    <div class="col-md-4">
                        <strong>Creado</strong>
                        <p class="text-muted">
                            <?= !empty($file['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($file['created_at']))) : '' ?>
                        </p>
                    </div>
                </div>

                <?php if (!empty($file['observations'])): ?>
                    <div class="callout callout-info">
                        <h5>Observaciones</h5>
                        <p><?= nl2br(htmlspecialchars($file['observations'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($extra_fields)): ?>
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Detalles adicionales</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <?php foreach ($extra_fields as $key => $value): ?>
                                    <dt class="col-sm-4"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></dt>
                                    <dd class="col-sm-8"><?= htmlspecialchars((string)$value) ?></dd>
                                <?php endforeach; ?>
                            </dl>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Adjuntos</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($attachments)): ?>
                            <span class="text-muted">No hay adjuntos registrados.</span>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($attachments as $attachment): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-paperclip mr-2"></i>
                                            <?= htmlspecialchars($attachment['original_name'] ?? '') ?>
                                        </span>
                                        <a href="<?= \App\Core\UrlHelper::route('panel/employee-files/downloadAttachment/' . $attachment['id']) ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-download"></i> Descargar
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

$scripts = '';
$styles = '';

include __DIR__ . '/../../layouts/admin.php';
?>
