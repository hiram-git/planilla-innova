<?php
/**
 * Vista: Reportes de Vacaciones (Esqueleto)
 * Ruta: /panel/vacation/reports
 */

use App\Core\UrlHelper;

$filters = $filters ?? [];
$f_emp   = $filters['employee_id'] ?? '';
$f_stat  = $filters['status'] ?? '';
$f_type  = $filters['vacation_type'] ?? '';
$f_from  = $filters['start_date'] ?? '';
$f_to    = $filters['end_date'] ?? '';
$f_year  = $filters['year'] ?? date('Y');

?>

<div class="content">
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-primary card-outline">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-alt"></i> Reportes de Vacaciones
                        </h3>
                        <div class="card-tools">
                            <a href="<?= UrlHelper::route('panel/vacation') ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="<?= UrlHelper::route('panel/vacation/reports') ?>" class="mb-3" id="reportFilters">
                            <div class="form-row">
                                <div class="col-md-3 mb-2">
                                    <label>Empleado</label>
                                    <select name="employee_id" class="form-control">
                                        <option value="">Todos</option>
                                        <?php foreach (($employees ?? []) as $emp): ?>
                                            <?php $selected = ($f_emp == $emp['id']) ? 'selected' : ''; ?>
                                            <option value="<?= $emp['id'] ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname'] . ' (' . $emp['employee_id'] . ')') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label>Estado</label>
                                    <select name="status" class="form-control">
                                        <option value="">Todos</option>
                                        <?php
                                        $statuses = ['PENDING' => 'Pendiente', 'APPROVED' => 'Aprobada', 'REJECTED' => 'Rechazada'];
                                        foreach ($statuses as $key => $label):
                                            $sel = ($f_stat === $key) ? 'selected' : '';
                                            echo "<option value=\"{$key}\" {$sel}>{$label}</option>";
                                        endforeach;
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label>Tipo</label>
                                    <select name="vacation_type" class="form-control">
                                        <option value="">Todos</option>
                                        <?php
                                        $types = ['ANNUAL' => 'Anual', 'COMPENSATION' => 'Compensación'];
                                        foreach ($types as $key => $label):
                                            $sel = ($f_type === $key) ? 'selected' : '';
                                            echo "<option value=\"{$key}\" {$sel}>{$label}</option>";
                                        endforeach;
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label>Año</label>
                                    <input type="number" name="year" class="form-control" value="<?= htmlspecialchars($f_year) ?>" min="2000" max="2100">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label>Rango de Fechas (solicitud)</label>
                                    <div class="d-flex">
                                        <input type="date" name="start_date" class="form-control mr-1" value="<?= htmlspecialchars($f_from) ?>">
                                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($f_to) ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex mt-2">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search"></i> Generar
                                </button>
                                <a href="<?= UrlHelper::route('panel/vacation/reports') ?>" class="btn btn-secondary mr-2">
                                    <i class="fas fa-eraser"></i> Limpiar
                                </a>
                                <a href="<?= UrlHelper::route('panel/vacation/reports') . '?format=excel' ?>" class="btn btn-success mr-2">
                                    <i class="fas fa-file-excel"></i> Exportar Excel
                                </a>
                                <a href="<?= UrlHelper::route('panel/vacation/reports') . '?format=pdf' ?>" class="btn btn-danger mr-2">
                                    <i class="fas fa-file-pdf"></i> Exportar PDF
                                </a>
                                <button type="button" id="btnSummary" class="btn btn-info">
                                    <i class="fas fa-chart-pie"></i> Resumen Anual
                                </button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="vacationReportsTable">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Fecha Solicitud</th>
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                        <th>Días Totales</th>
                                        <th>Días Hábiles</th>
                                        <th>Días por Pagar</th>
                                        <th>Días de Disfrute</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Monto</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($requests)): ?>
                                        <?php foreach ($requests as $r): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? '')) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars($r['employee_id'] ?? '') ?></small>
                                                </td>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['request_date']))) ?></td>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['start_date']))) ?></td>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['end_date']))) ?></td>
                                                <td><?= (int)($r['total_days'] ?? 0) ?></td>
                                                <td><?= (int)($r['business_days'] ?? 0) ?></td>
                                                <td><?= (float)($r['dias_solicitados_pagar'] ?? 0) ?></td>
                                                <td><?= (float)($r['dias_solicitados_disfrute'] ?? 0) ?></td>
                                                <td><span class="badge badge-secondary"><?= htmlspecialchars($r['vacation_type'] ?? '-') ?></span></td>
                                                <td>
                                                    <?php
                                                    $sc = [
                                                        'PENDING' => 'warning',
                                                        'APPROVED' => 'success',
                                                        'REJECTED' => 'danger'
                                                    ];
                                                    $s  = $r['status'] ?? 'PENDING';
                                                    $bc = $sc[$s] ?? 'secondary';
                                                    ?>
                                                    <span class="badge badge-<?= $bc ?>"><?= htmlspecialchars($s) ?></span>
                                                </td>
                                                <td><?= currency_symbol() ?><?= number_format((float)($r['compensation_amount'] ?? 0), 2) ?></td>
                                                <td>
                                                    <a class="btn btn-xs btn-primary" href="<?= UrlHelper::route('panel/vacation/show/' . $r['id']) ?>" title="Ver detalle">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
// Styles
ob_start();
?>
<link rel="stylesheet" href="<?= url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css', false) ?>">
<?php
$styles = ob_get_clean();

// Scripts
ob_start();
?>
<script src="<?= url('plugins/datatables/jquery.dataTables.min.js', false) ?>"></script>
<script src="<?= url('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js', false) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#vacationReportsTable').DataTable({
        language: { url: '<?= url('assets/js/datatables-spanish.json', false) ?>' },
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Botón de resumen anual (placeholder)
    document.getElementById('btnSummary').addEventListener('click', function() {
        Swal.fire({
            icon: 'info',
            title: 'Resumen Anual',
            text: 'Funcionalidad en preparación. Seleccione un año y genere el reporte.',
            confirmButtonText: 'Entendido'
        });
    });
});
</script>
<?php
$scripts = ob_get_clean();
?>

