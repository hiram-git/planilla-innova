<?php
use App\Helpers\JavaScriptHelper;

$page_title = $file ? 'Editar Expediente' : 'Nuevo Expediente';
$employeeName = trim(($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? ''));
$formAction = $file
    ? \App\Core\UrlHelper::route('panel/employee-files/update/' . $file['id'])
    : \App\Core\UrlHelper::route('panel/employee-files/store');
$backUrl = \App\Core\UrlHelper::employee(($employee['id'] ?? 0) . '/expedientes');

ob_start();
?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($employeeName) ?></h3>
                <div class="card-tools">
                    <a href="<?= $backUrl ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form id="employee-file-form" action="<?= $formAction ?>" method="POST" enctype="multipart/form-data" data-file-id="<?= (int)($file['id'] ?? 0) ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <input type="hidden" name="employee_id" value="<?= (int)($employee['id'] ?? 0) ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo de Expediente</label>
                                <select name="type_id" id="type_id" class="form-control select2" style="width: 100%;" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($types as $type): ?>
                                        <option value="<?= (int)$type['id'] ?>" <?= isset($file['type_id']) && (int)$file['type_id'] === (int)$type['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Subtipo</label>
                                <select name="subtype_id" id="subtype_id" class="form-control select2" style="width: 100%;" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($subtypes as $subtype): ?>
                                        <option value="<?= (int)$subtype['id'] ?>" <?= isset($file['subtype_id']) && (int)$file['subtype_id'] === (int)$subtype['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($subtype['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha del Documento</label>
                                <input type="text" name="document_date" class="form-control date-picker" value="<?= htmlspecialchars($file['document_date'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Número de Memorando/Oficio</label>
                                <input type="text" name="document_number" class="form-control" value="<?= htmlspecialchars($file['document_number'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observations" class="form-control" rows="3"><?= htmlspecialchars($file['observations'] ?? '') ?></textarea>
                    </div>

                    <div id="dynamic-fields-container">
                        <?= $dynamic_fields_html ?? '' ?>
                    </div>

                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Adjuntos</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Adjuntar archivos (PDF o imágenes)</label>
                                <div class="custom-file">
                                    <input type="file" name="attachments[]" class="custom-file-input" multiple accept=".pdf,image/*">
                                    <label class="custom-file-label">Seleccionar archivos</label>
                                </div>
                            </div>

                            <?php if (!empty($attachments)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Archivo</th>
                                                <th>Etiqueta</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attachments as $attachment): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($attachment['original_name'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($attachment['label'] ?? 'Adjunto') ?></td>
                                                    <td>
                                                        <a href="<?= \App\Core\UrlHelper::route('panel/employee-files/downloadAttachment/' . $attachment['id']) ?>" class="btn btn-info btn-sm" title="Descargar">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <div class="form-check d-inline-block ml-2">
                                                            <input class="form-check-input" type="checkbox" name="remove_attachments[]" value="<?= (int)$attachment['id'] ?>" id="remove-<?= (int)$attachment['id'] ?>">
                                                            <label class="form-check-label" for="remove-<?= (int)$attachment['id'] ?>">Eliminar</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="<?= $backUrl ?>" class="btn btn-secondary mr-2">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

$scriptFiles = [
    '/plugins/bs-custom-file-input/bs-custom-file-input.min.js',
    '/assets/javascript/modules/employee_files/index.js'
];

$jsConfig = JavaScriptHelper::renderConfigScript();
$scripts = $jsConfig . "\n" . JavaScriptHelper::renderScriptTags($scriptFiles);
$styles = '';

include __DIR__ . '/../../layouts/admin.php';
?>
