<?php
use App\Helpers\PermissionHelper;
use App\Helpers\JavaScriptHelper;

$page_title = 'Expedientes del Empleado';

$fullName = trim(($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? ''));
$stats = $stats ?? ['total_files' => 0, 'total_types' => 0, 'total_attachments' => 0];

ob_start();
?>
<div class="row">
    <div class="col-lg-4 col-12">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= (int)($stats['total_files'] ?? 0) ?></h3>
                <p>Total Expedientes</p>
            </div>
            <div class="icon">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-12">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= (int)($stats['total_types'] ?? 0) ?></h3>
                <p>Tipos con Registros</p>
            </div>
            <div class="icon">
                <i class="fas fa-layer-group"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-12">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= (int)($stats['total_attachments'] ?? 0) ?></h3>
                <p>Adjuntos</p>
            </div>
            <div class="icon">
                <i class="fas fa-paperclip"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Expedientes: <?= htmlspecialchars($fullName) ?>
                </h3>
                <div class="card-tools">
                    <a href="<?= \App\Core\UrlHelper::employee() ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <?php if (PermissionHelper::canRead('employees')): ?>
                        <a href="<?= \App\Core\UrlHelper::employee($employee['id']) ?>" class="btn btn-info btn-sm">
                            <i class="fas fa-user"></i> Ver Empleado
                        </a>
                    <?php endif; ?>
                    <?php if (PermissionHelper::canWrite('employee-files')): ?>
                        <a href="<?= \App\Core\UrlHelper::route('panel/employee-files/create/' . $employee['id']) ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Nuevo Expediente
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($files)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> No hay expedientes registrados para este empleado.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="employeeFilesTable">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Subtipo</th>
                                    <th>Fecha Documento</th>
                                    <th>No. Memorando/Oficio</th>
                                    <th>Adjuntos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $file): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($file['type_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($file['subtype_name'] ?? '') ?></td>
                                        <td>
                                            <?php if (!empty($file['document_date'])): ?>
                                                <?= htmlspecialchars(date('d/m/Y', strtotime($file['document_date']))) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($file['document_number'] ?? '') ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-light">
                                                <?= (int)($file['attachments_count'] ?? 0) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (PermissionHelper::canRead('employee-files')): ?>
                                                <a href="<?= \App\Core\UrlHelper::route('panel/employee-files/' . $file['id']) ?>" class="btn btn-info btn-sm" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (PermissionHelper::canWrite('employee-files')): ?>
                                                <a href="<?= \App\Core\UrlHelper::route('panel/employee-files/edit/' . $file['id']) ?>" class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (PermissionHelper::canDelete('employee-files')): ?>
                                                <form method="POST" action="<?= \App\Core\UrlHelper::route('panel/employee-files/' . $file['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este expediente?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

$scriptFiles = [
    '/plugins/datatables/jquery.dataTables.min.js',
    '/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    '/assets/javascript/modules/employee_files/index.js'
];

$scripts = JavaScriptHelper::renderConfigScript() . "\n" . JavaScriptHelper::renderScriptTags($scriptFiles);
$styles = '';

include __DIR__ . '/../../layouts/admin.php';
?>
