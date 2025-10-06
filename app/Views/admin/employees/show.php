<?php
use App\Helpers\PermissionHelper;

$page_title = 'Detalles del Empleado';

// Obtener configuración de empresa para determinar qué cargo/salario mostrar
$companyModel = new \App\Models\Company();
$isEmpresaConPosiciones = $companyModel->isEmpresaConPosiciones();

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
                        <p class="text-muted">' . htmlspecialchars(
                            $isEmpresaConPosiciones
                                ? ($employee['position_name'] ?? 'Sin posición')
                                : ($employee['cargo_name'] ?? 'Sin cargo')
                        ) . '</p>
                    </div>
                    
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-info-circle text-primary"></i> Información Personal</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>ID Empleado:</strong></td>
                                        <td>' . htmlspecialchars($employee['employee_id'] ?? 'No asignado') . '</td>
                                    </tr>' .
                                    ($isEmpresaConPosiciones && !empty($employee['position_code']) ? '
                                    <tr>
                                        <td><strong>Código Posición:</strong></td>
                                        <td><span class="badge badge-info">' . htmlspecialchars($employee['position_code']) . '</span></td>
                                    </tr>' : '') . '
                                    <tr>
                                        <td><strong>Cédula:</strong></td>
                                        <td>' . htmlspecialchars($employee['document_id'] ?? 'No especificada') . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Número de Seguro Social:</strong></td>
                                        <td>
                                            ' . htmlspecialchars($employee['clave_seguro_social'] ?? 'No especificado') . '
                                            ' . (($employee['clave_seguro_social'] ?? '') === ($employee['document_id'] ?? '')
                                                ? '<span class="badge badge-info ml-2"><i class="fas fa-check-circle"></i> Mismo que cédula</span>'
                                                : '') . '
                                        </td>
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
                                        <td><strong>Cargo:</strong></td>
                                        <td>' . htmlspecialchars(
                                            $isEmpresaConPosiciones
                                                ? ($employee['position_name'] ?? 'No asignado')
                                                : ($employee['cargo_name'] ?? 'No asignado')
                                        ) . '</td>
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
                                        <td>' . ($employee['moneda_simbolo'] ?? 'Q.') . ' ' . number_format(
                                            $isEmpresaConPosiciones
                                                ? ($employee['position_salary'] ?? 0)
                                                : ($employee['sueldo_individual'] ?? 0), 2
                                        ) . '</td>
                                    </tr>';

if (!empty($employee['gastos_representacion']) && $employee['gastos_representacion'] > 0) {
    $content .= '
                                    <tr>
                                        <td><strong>Gastos Representación:</strong></td>
                                        <td>' . ($employee['moneda_simbolo'] ?? 'Q.') . ' ' . number_format($employee['gastos_representacion'], 2) . '</td>
                                    </tr>';
}

$content .= '
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
                                        <td><strong>Tipo(s) de Planilla:</strong></td>
                                        <td>';

// Mostrar tipos de planilla (puede ser múltiples separados por comas)
if (!empty($employee['tipo_planilla_id'])) {
    $tipoPlanillaModel = new \App\Models\TipoPlanilla();
    $tipoIds = explode(',', $employee['tipo_planilla_id']);
    $badges = [];

    foreach ($tipoIds as $tipoId) {
        $tipoId = trim($tipoId);
        if (is_numeric($tipoId)) {
            $tipoPlanilla = $tipoPlanillaModel->find($tipoId);
            if ($tipoPlanilla) {
                $badges[] = '<span class="badge badge-primary">' . htmlspecialchars($tipoPlanilla['descripcion']) . '</span>';
            }
        }
    }

    $content .= !empty($badges) ? implode(' ', $badges) : '<span class="text-muted">No especificado</span>';
} else {
    $content .= '<span class="text-muted">No especificado</span>';
}

$content .= '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fecha Creación:</strong></td>
                                        <td>' . ($employee['created_on'] ? date('d/m/Y H:i', strtotime($employee['created_on'])) : 'No disponible') . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Elemento Organigrama:</strong></td>
                                        <td>' . (!empty($employee['organigrama_descripcion']) ? htmlspecialchars($employee['organigrama_descripcion']) : '<span class="text-muted">No asignado</span>') . '</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Sección de Información de Terminación -->';

if (!empty($employee['termination_date'])) {
    $content .= '
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5><i class="fas fa-sign-out-alt text-warning"></i> Información de Terminación</h5>
                                <div class="callout callout-warning">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm mb-0">
                                                <tr>
                                                    <td><strong>Fecha de Terminación:</strong></td>
                                                    <td><span class="badge badge-danger">' . date('d/m/Y', strtotime($employee['termination_date'])) . '</span></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Tipo de Terminación:</strong></td>
                                                    <td>';

    // Formatear el tipo de terminación
    $terminationType = '';
    switch($employee['termination_type']) {
        case 'DESPIDO_CON_CAUSA':
            $terminationType = '<span class="badge badge-danger">Despido con Causa</span>';
            break;
        case 'DESPIDO_SIN_CAUSA':
            $terminationType = '<span class="badge badge-warning">Despido sin Causa</span>';
            break;
        case 'RENUNCIA':
            $terminationType = '<span class="badge badge-info">Renuncia</span>';
            break;
        case 'MUTUO_ACUERDO':
            $terminationType = '<span class="badge badge-secondary">Mutuo Acuerdo</span>';
            break;
        default:
            $terminationType = '<span class="badge badge-secondary">No especificado</span>';
    }

    $content .= $terminationType . '</td>
                                                </tr>';

    if (!empty($employee['termination_status'])) {
        $content .= '
                                                <tr>
                                                    <td><strong>Estado de Liquidación:</strong></td>
                                                    <td>';

        // Formatear el estado de liquidación
        $terminationStatus = '';
        switch($employee['termination_status']) {
            case 'PENDIENTE':
                $terminationStatus = '<span class="badge badge-warning">Pendiente</span>';
                break;
            case 'CALCULADA':
                $terminationStatus = '<span class="badge badge-info">Calculada</span>';
                break;
            case 'PROCESADA':
                $terminationStatus = '<span class="badge badge-primary">Procesada</span>';
                break;
            case 'PAGADA':
                $terminationStatus = '<span class="badge badge-success">Pagada</span>';
                break;
            case 'CANCELADA':
                $terminationStatus = '<span class="badge badge-secondary">Cancelada</span>';
                break;
            default:
                $terminationStatus = '<span class="badge badge-secondary">No especificado</span>';
        }

        $content .= $terminationStatus . '</td>
                                                </tr>';
    }

    $content .= '
                                            </table>
                                        </div>
                                        <div class="col-md-6">';

    if (!empty($employee['termination_reason'])) {
        $content .= '
                                            <strong>Motivo de Terminación:</strong><br>
                                            <div class="mt-2 p-2 termination-reason">
                                                ' . nl2br(htmlspecialchars($employee['termination_reason'])) . '
                                            </div>';
    } else {
        $content .= '<p class="text-muted">No se especificó motivo</p>';
    }

    $content .= '
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
}

$content .= '
                        
                        <!-- Sección adicional si hay observaciones -->
                        ' . (!empty($employee['observaciones']) ? '
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5><i class="fas fa-sticky-note text-info"></i> Observaciones</h5>
                                <div class="card">
                                    <div class="card-body">
                                        ' . nl2br(htmlspecialchars($employee['observaciones'])) . '
                                    </div>
                                </div>
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
.callout-warning {
    border-left: 4px solid #f39c12;
}
.callout-warning .table {
    background-color: transparent;
}
.callout-warning .table td {
    border-top-color: rgba(0,0,0,0.1);
}
.termination-reason {
    background-color: rgba(255,255,255,0.8);
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 4px;
    font-size: 0.95rem;
}
</style>';

include __DIR__ . '/../../layouts/admin.php';
?>