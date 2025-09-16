<?php
use App\Helpers\PermissionHelper;

$page_title = 'Detalles del Empleado';

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Detalles del Empleado: ' . htmlspecialchars(($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? '')) . '</h3>
                <div class="card-tools">
                    <a href="' . \App\Core\UrlHelper::employee() . '" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    ' . PermissionHelper::createButton('employees', [
                        'url' => \App\Core\UrlHelper::employee('edit/' . $employee['id']),
                        'text' => 'Editar',
                        'class' => 'btn btn-primary btn-sm',
                        'icon' => 'fas fa-edit'
                    ]) . '
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="employee-photo mb-3">';
                        
if (!empty($employee['photo'])) {
    $photoUrl = \App\Core\UrlHelper::url('images/' . $employee['photo']);
    $content .= '<img src="' . htmlspecialchars($photoUrl) . '" alt="Foto del empleado" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">';
} else {
    $content .= '<div class="bg-light d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto;">
                    <i class="fas fa-user fa-4x text-muted"></i>
                 </div>';
}

$content .= '
                        </div>
                        <h4>' . htmlspecialchars(($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? '')) . '</h4>
                        <p class="text-muted">' . htmlspecialchars($employee['position_name'] ?? 'Sin posición') . '</p>
                    </div>
                    
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-info-circle text-primary"></i> Información Personal</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>ID Empleado:</strong></td>
                                        <td>' . htmlspecialchars($employee['id']) . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Cédula:</strong></td>
                                        <td>' . htmlspecialchars($employee['document_id'] ?? 'No especificada') . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Clave Seguro Social:</strong></td>
                                        <td>' . htmlspecialchars($employee['clave_seguro_social'] ?? 'No especificada') . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td>' . htmlspecialchars($employee['contact_info'] ?? 'No especificado') . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Género:</strong></td>
                                        <td>' . htmlspecialchars($employee['gender'] ?? 'No especificado') . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dirección:</strong></td>
                                        <td>' . htmlspecialchars($employee['address'] ?? 'No especificada') . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fecha Nacimiento:</strong></td>
                                        <td>' . ($employee['birthdate'] ? date('d/m/Y', strtotime($employee['birthdate'])) : 'No especificada') . '</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="col-md-6">
                                <h5><i class="fas fa-briefcase text-success"></i> Información Laboral</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Posición:</strong></td>
                                        <td>' . htmlspecialchars($employee['position_name'] ?? 'No asignada') . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Horario:</strong></td>
                                        <td>';
                                        
if ($employee['time_in'] && $employee['time_out']) {
    $content .= date('h:i A', strtotime($employee['time_in'])) . ' - ' . date('h:i A', strtotime($employee['time_out']));
} else {
    $content .= 'No asignado';
}

$content .= '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Salario Base:</strong></td>
                                        <td>' . ($employee['moneda_simbolo'] ?? 'Q.') . ' ' . number_format($employee['position_salary'] ?? 0, 2) . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fecha Ingreso:</strong></td>
                                        <td>' . ($employee['fecha_ingreso'] ? date('d/m/Y', strtotime($employee['fecha_ingreso'])) : 'No especificada') . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Situación:</strong></td>
                                        <td>';
                                        
// Mostrar la situación del empleado desde la tabla situaciones
if (!empty($employee['situacion_nombre'])) {
    // Determinar el color del badge basado en la situación
    $situacionNombre = strtolower($employee['situacion_nombre']);
    if (strpos($situacionNombre, 'activo') !== false || strpos($situacionNombre, 'trabajando') !== false) {
        $statusClass = 'badge-success';
    } elseif (strpos($situacionNombre, 'vacaciones') !== false || strpos($situacionNombre, 'permiso') !== false) {
        $statusClass = 'badge-info';
    } elseif (strpos($situacionNombre, 'suspendido') !== false || strpos($situacionNombre, 'inactivo') !== false) {
        $statusClass = 'badge-danger';
    } else {
        $statusClass = 'badge-secondary';
    }
    $content .= '<span class="badge ' . $statusClass . '">' . htmlspecialchars($employee['situacion_nombre']) . '</span>';
} else {
    $content .= '<span class="badge badge-warning">Sin situación asignada</span>';
}

$content .= '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fecha Creación:</strong></td>
                                        <td>' . ($employee['created_on'] ? date('d/m/Y H:i', strtotime($employee['created_on'])) : 'No disponible') . '</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Sección adicional si hay más información -->
                        ' . ((!empty($employee['observaciones']) || !empty($employee['fecha_salida'])) ? '
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5><i class="fas fa-sticky-note text-info"></i> Información Adicional</h5>
                                <table class="table table-sm">
                                    ' . (!empty($employee['fecha_salida']) ? '
                                    <tr>
                                        <td><strong>Fecha Salida:</strong></td>
                                        <td>' . date('d/m/Y', strtotime($employee['fecha_salida'])) . '</td>
                                    </tr>
                                    ' : '') . '
                                    ' . (!empty($employee['observaciones']) ? '
                                    <tr>
                                        <td><strong>Observaciones:</strong></td>
                                        <td>' . nl2br(htmlspecialchars($employee['observaciones'])) . '</td>
                                    </tr>
                                    ' : '') . '
                                </table>
                            </div>
                        </div>
                        ' : '') . '
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-6">
                        <a href="' . \App\Core\UrlHelper::employee() . '" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Listado
                        </a>
                    </div>
                    <div class="col-md-6 text-right">
                        ' . PermissionHelper::createButton('employees', [
                            'url' => \App\Core\UrlHelper::employee('edit/' . $employee['id']),
                            'text' => 'Editar Empleado',
                            'class' => 'btn btn-primary',
                            'icon' => 'fas fa-edit'
                        ]) . '
                        ' . (PermissionHelper::canDelete('employees') ? 
                            '<button type="button" class="btn btn-danger ml-2" onclick="confirmDelete(' . $employee['id'] . ', \'' . addslashes(($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? '')) . '\')">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>' : '') . '
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

$scripts = '
<script>
function confirmDelete(employeeId, employeeName) {
    if (confirm("¿Está seguro que desea eliminar al empleado " + employeeName + "?")) {
        window.location.href = "' . \App\Core\UrlHelper::employee() . '/" + employeeId + "/delete";
    }
}
</script>';

$styles = '
<style>
.employee-photo img {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.table td {
    padding: 0.5rem;
    border-top: 1px solid #dee2e6;
}
.table td:first-child {
    width: 40%;
    font-weight: 500;
}
</style>';

include __DIR__ . '/../../layouts/admin.php';
?>