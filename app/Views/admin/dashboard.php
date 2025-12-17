<?php
$page_title = 'Panel de Control';

$content = '
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line mr-1"></i>
                    Dashboard Ejecutivo - ' . date('d/m/Y') . '
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Tabs para Dashboard Principal y Acumulados -->
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-tabs">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="dashboard-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="dashboard-tab" data-toggle="pill" href="#dashboard-principal" role="tab" aria-controls="dashboard-principal" aria-selected="true">
                            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard Principal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="acumulados-tab" data-toggle="pill" href="#dashboard-acumulados" role="tab" aria-controls="dashboard-acumulados" aria-selected="false">
                            <i class="fas fa-piggy-bank mr-1"></i> Acumulados
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="dashboard-tabContent">
                    <!-- Tab Dashboard Principal -->
                    <div class="tab-pane fade show active" id="dashboard-principal" role="tabpanel" aria-labelledby="dashboard-tab">';

// Indicador de filtro activo
$filtroActivo = $tipo_seleccionado ? ' <span class="badge badge-warning"><i class="fas fa-filter"></i> Filtrado</span>' : '';

// Debug visible para el usuario
if ($tipo_seleccionado) {
    $tipoNombre = '';
    foreach ($tipos_planilla as $t) {
        if ($t['id'] == $tipo_seleccionado) {
            $tipoNombre = $t['descripcion'];
            break;
        }
    }
    $content .= '<div class="row">
        <div class="col-12">
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-filter"></i> Filtro Activo</h5>
                Mostrando datos únicamente para: <strong>' . htmlspecialchars($tipoNombre) . '</strong> (ID: ' . $tipo_seleccionado . ')
                <br><small>Total empleados en este tipo: ' . $total_employees . '</small>
            </div>
        </div>
    </div>';
}

$content .= '<div class="row">
    <!-- 1. Total Empleados -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>' . $total_employees . '</h3>
                <p>Total Empleados' . $filtroActivo . '</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="' . \App\Core\UrlHelper::employee() . '" class="small-box-footer">
                Ver empleados <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- 2. Colaboradores Activos -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>' . $active_employees . '</h3>
                <p>Colaboradores Activos' . $filtroActivo . '</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="small-box-footer">
                Últimos 30 días
            </div>
        </div>
    </div>

    <!-- 3. Puntualidad Mensual -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-success">
            <div class="inner">
                <h3>' . $monthly_punctuality . '%</h3>
                <p>Puntualidad Mensual' . $filtroActivo . '</p>
            </div>
            <div class="icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="small-box-footer">
                ' . date('F Y') . '
            </div>
        </div>
    </div>

    <!-- 4. Presentes Hoy -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>' . $employees_present . '/' . $total_employees . '</h3>
                <p>Presentes Hoy' . $filtroActivo . '</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="small-box-footer">
                <strong>' . $attendance_percentage . '%</strong> de asistencia hoy
            </div>
        </div>
    </div>
</div>

<!-- Gráfica de Asistencia -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-area mr-1"></i>
                    Reporte Total de Asistencia - Últimos 30 Días
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart" id="chartContainer">
                    <canvas id="attendanceChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
                <div class="row mt-3">
                    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Promedio Diario</span>
                                <span class="info-box-number">' . $monthly_stats['average_daily'] . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Presentes</span>
                                <span class="info-box-number">' . $monthly_stats['total_present'] . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                        <div class="info-box bg-gradient-warning">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tardanzas</span>
                                <span class="info-box-number">' . $monthly_stats['total_late'] . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                        <div class="info-box bg-gradient-danger">
                            <span class="info-box-icon"><i class="fas fa-user-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Ausencias</span>
                                <span class="info-box-number">' . ($monthly_stats['total_absent'] ?? 0) . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                        <div class="info-box bg-gradient-primary">
                            <span class="info-box-icon"><i class="fas fa-award"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Score Puntualidad</span>
                                <span class="info-box-number">' . ($monthly_stats['avg_punctuality_score'] ?? 0) . '/100</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                        <div class="info-box bg-gradient-secondary">
                            <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">% Puntualidad</span>
                                <span class="info-box-number">' . $monthly_stats['punctuality_percentage'] . '%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-day mr-1"></i>
                    Asistencias de Hoy - ' . date('d/m/Y') . '
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Fecha</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Horas</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>';

// Obtener asistencias de hoy en lugar de recientes
$todayAttendanceForTable = [];
foreach ($recent_attendance as $attendance) {
    if ($attendance['date'] === date('Y-m-d')) {
        $todayAttendanceForTable[] = $attendance;
    }
}

if (!empty($todayAttendanceForTable)) {
    foreach ($todayAttendanceForTable as $attendance) {
        
        $status = $attendance['status'] == 1 
            ? '<span class="badge badge-success"><i class="fas fa-check"></i> A tiempo</span>' 
            : '<span class="badge badge-warning"><i class="fas fa-clock"></i> Tarde</span>';
            
        $timeOut = $attendance['time_out'] && $attendance['time_out'] !== '00:00:00' 
            ? date('h:i A', strtotime($attendance['time_out'])) 
            : '<span class="text-muted">Pendiente</span>';
            
        $hours = $attendance['num_hr'] > 0 ? number_format($attendance['num_hr'], 1) . 'h' : '-';
        
        $content .= '
                            <tr>
                                <td>
                                    <strong>' . htmlspecialchars($attendance['firstname'] . ' ' . $attendance['lastname']) . '</strong>
                                </td>
                                <td>' . date('d/m/Y', strtotime($attendance['date'])) . '</td>
                                <td><span class="text-primary">' . date('h:i A', strtotime($attendance['time_in'])) . '</span></td>
                                <td>' . $timeOut . '</td>
                                <td>' . $hours . '</td>
                                <td>' . $status . '</td>
                            </tr>';
    }
} else {
    $content .= '
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> No hay registros de asistencia para hoy
                                </td>
                            </tr>';
}

$content .= '
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="' . \App\Core\UrlHelper::attendance() . '" class="btn btn-primary btn-sm">
                    <i class="fas fa-list"></i> Ver todos los registros
                </a>
            </div>
        </div>';

// Widget: Contratos Próximos a Vencer
//if (!empty($expiring_contracts)) {
    $content .= '
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-contract"></i> Contratos Próximos a Vencer
                </h3>
                <div class="card-tools">
                    <span class="badge badge-warning">' . count($expiring_contracts) . '</span>
                </div>
            </div>
            <div class="card-body p-0">';

    $content .= '
                <ul class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">';

    $displayedCount = 0;
    if (!empty($expiring_contracts)) {
    foreach ($expiring_contracts as $contract) {
        if ($displayedCount >= 5) break;

        $diasRestantes = $contract['dias_restantes'];
        $badgeClass = 'badge-info';
        $badgeText = $diasRestantes . ' días';

        if ($diasRestantes <= 15) {
            $badgeClass = 'badge-danger';
            $badgeText = 'URGENTE';
        } elseif ($diasRestantes <= 30) {
            $badgeClass = 'badge-warning';
            $badgeText = 'PRONTO';
        }

        $cargo = $contract['cargo'] ?? 'Sin cargo';
        $tipo = $contract['tipo_contrato'];
        $fecha = date('d/m/Y', strtotime($contract['fecha_vencimiento_contrato']));

        $content .= '
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>' . htmlspecialchars($contract['nombre_completo']) . '</strong>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-briefcase"></i> ' . htmlspecialchars($cargo) . ' |
                                    <i class="fas fa-calendar"></i> ' . $fecha . ' |
                                    <span class="badge badge-secondary">' . $tipo . '</span>
                                </small>
                            </div>
                            <span class="badge ' . $badgeClass . '">' . $badgeText . '</span>
                        </div>
                    </li>';
        $displayedCount++;
        }

        $content .= '
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">Mostrando contratos que vencen en los próximos 60 días</small>
                </div>
            </div>';
    } else {

    $content .= '
                </ul>
            </div>
            <div class="card-footer text-center">
                <small class="text-muted">No hay Contratos para mostrar</small>
            </div>
        </div>';
    }
//}

// Widget de Alertas (Solo si hay alertas activas)
if ($alert_stats['total'] > 0) {
    $content .= '
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle"></i> Alertas Activas
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light">' . $alert_stats['total'] . '</span>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">';

    if (!empty($active_alerts)) {
        $count = 0;
        foreach ($active_alerts as $alert) {
            if ($count >= 3) break; // Solo mostrar 3 alertas

            $badgeClass = $alert['severity'] == 'CRITICAL' ? 'danger' : ($alert['severity'] == 'WARNING' ? 'warning' : 'info');
            $alertType = str_replace('_', ' ', $alert['alert_type']);

            $content .= '
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>' . htmlspecialchars($alert['firstname'] . ' ' . $alert['lastname']) . '</strong>
                                <br><small class="text-muted">' . htmlspecialchars(ucwords(strtolower($alertType))) . '</small>
                            </div>
                            <span class="badge badge-' . $badgeClass . '">' . $alert['severity'] . '</span>
                        </div>
                    </li>';
            $count++;
        }
    }

    $content .= '
                </ul>
            </div>
            <div class="card-footer text-center">
                <a href="' . \App\Core\UrlHelper::route('panel/attendance/pending-absences') . '" class="small">Ver todas las alertas</a>
            </div>
        </div>';
}

$content .= '
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie mr-1"></i>
                    Estadísticas Rápidas
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info">
                                <i class="fas fa-building"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Cargos</span>
                                <span class="info-box-number">' . $total_cargos . '</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-success">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Horarios</span>
                                <span class="info-box-number">' . $total_schedules . '</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="progress-group">
                            <span class="float-left"><b>Asistencia Hoy</b></span>
                            <span class="float-right"><b>' . $employees_present . '/' . $total_employees . '</b></span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-primary" style="width: ' . $attendance_percentage . '%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-6">
                        <a href="' . \App\Core\UrlHelper::position() . '" class="btn btn-info btn-sm btn-block">
                            <i class="fas fa-sitemap"></i> Estructura
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="' . \App\Core\UrlHelper::schedule() . '" class="btn btn-success btn-sm btn-block">
                            <i class="fas fa-clock"></i> Horarios
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-1"></i>
                    Accesos Rápidos
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-2">
                        <a href="' . \App\Core\UrlHelper::employee('create') . '" class="btn btn-primary btn-sm btn-block">
                            <i class="fas fa-user-plus"></i><br>
                            <small>Nuevo Empleado</small>
                        </a>
                    </div>
                    <div class="col-6 mb-2">
                        <a href="' . \App\Core\UrlHelper::position('create') . '" class="btn btn-info btn-sm btn-block">
                            <i class="fas fa-plus"></i><br>
                            <small>Nueva Posición</small>
                        </a>
                    </div>
                    <div class="col-6 mb-2">
                        <a href="' . \App\Core\UrlHelper::timeclock() . '" class="btn btn-warning btn-sm btn-block">
                            <i class="fas fa-stopwatch"></i><br>
                            <small>Marcaciones</small>
                        </a>
                    </div>
                    <div class="col-6 mb-2">
                        <a href="' . \App\Core\UrlHelper::attendance() . '" class="btn btn-success btn-sm btn-block">
                            <i class="fas fa-chart-line"></i><br>
                            <small>Reportes</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                    </div>
                    <!-- Fin Tab Dashboard Principal -->

                    <!-- Tab Dashboard Acumulados -->
                    <div class="tab-pane fade" id="dashboard-acumulados" role="tabpanel" aria-labelledby="acumulados-tab">';

// Indicador de filtro activo en tab Acumulados
if ($tipo_seleccionado) {
    $tipoNombre = '';
    foreach ($tipos_planilla as $t) {
        if ($t['id'] == $tipo_seleccionado) {
            $tipoNombre = $t['descripcion'];
            break;
        }
    }
    $content .= '<div class="row">
        <div class="col-12">
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-filter"></i> Filtro Activo</h5>
                Mostrando acumulados únicamente para: <strong>' . htmlspecialchars($tipoNombre) . '</strong>
                <br><small>Año: ' . ($acumulados_data['year'] ?? date('Y')) . '</small>
            </div>
        </div>
    </div>';
}

$content .= '
                        <div class="row">
                            <div class="col-12">
                                <h5>Resumen por Conceptos - Año ' . ($acumulados_data['year'] ?? date('Y')) . '</h5>
                            </div>
                        </div>

                        <div class="row">';

// Mostrar conceptos de acumulados ordenados y coloreados por tipo
if (!empty($acumulados_data['tipos_acumulados'])) {
    // Ordenar conceptos: Asignaciones (A) primero, luego Deducciones (D), luego Patronales (P)
    $conceptosOrdenados = $acumulados_data['tipos_acumulados'];
    usort($conceptosOrdenados, function($a, $b) {
        $orden = ['A' => 1, 'D' => 2, 'P' => 3];
        $tipoA = $a['tipo_concepto'] ?? 'Z';
        $tipoB = $b['tipo_concepto'] ?? 'Z';
        $orderA = $orden[$tipoA] ?? 999;
        $orderB = $orden[$tipoB] ?? 999;
        if ($orderA === $orderB) {
            return strcmp($a['descripcion'] ?? '', $b['descripcion'] ?? '');
        }
        return $orderA - $orderB;
    });

    foreach ($conceptosOrdenados as $concepto) {
        // Asignar color según tipo de concepto
        $tipoConcepto = $concepto['tipo_concepto'] ?? '';
        switch ($tipoConcepto) {
            case 'A': // Asignación
                $color = 'success'; // Verde
                $icon = 'fa-plus-circle';
                $tipoBadge = '<span class="badge badge-light">Asignación</span>';
                break;
            case 'D': // Deducción
                $color = 'danger'; // Rojo
                $icon = 'fa-minus-circle';
                $tipoBadge = '<span class="badge badge-light">Deducción</span>';
                break;
            case 'P': // Patronal
                $color = 'info'; // Azul
                $icon = 'fa-building';
                $tipoBadge = '<span class="badge badge-light">Patronal</span>';
                break;
            default:
                $color = 'secondary'; // Gris por defecto
                $icon = 'fa-receipt';
                $tipoBadge = '';
                break;
        }

        // Construir URL con parámetros
        $urlParams = [
            'concepto_id' => $concepto['concepto_id'] ?? '',
            'year' => $acumulados_data['year'] ?? date('Y'),
            'month' => '',
            'group_by' => 'empleado',
            'tipo_planilla' => $tipo_seleccionado ?? ''
        ];
        $detailUrl = \App\Core\UrlHelper::route('panel/acumulados/byConcepto') . '?' . http_build_query($urlParams);

        $content .= '
                            <div class="col-md-4 mb-3">
                                <div class="small-box bg-' . $color . '">
                                    <div class="inner">
                                        <h3>' . currency_symbol() . number_format($concepto['total_acumulado'] ?? 0, 2) . '</h3>
                                        <p>' . htmlspecialchars($concepto['descripcion'] ?? 'N/A') . ' ' . $tipoBadge . $filtroActivo . '</p>
                                        <small class="d-block">
                                            <i class="fas fa-users"></i> ' . ($concepto['total_empleados'] ?? 0) . ' empleados |
                                            <i class="fas fa-file-invoice"></i> ' . ($concepto['total_planillas'] ?? 0) . ' planillas
                                        </small>
                                    </div>
                                    <div class="icon">
                                        <i class="fas ' . $icon . '"></i>
                                    </div>
                                    <div class="small-box-footer">
                                        <a href="' . $detailUrl . '" class="text-white">
                                            <i class="fas fa-eye"></i> Ver Detalles
                                        </a>
                                    </div>
                                </div>
                            </div>';
    }
} else {
    $content .= '
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    No hay acumulados registrados para el año actual.
                                </div>
                            </div>';
}

$content .= '
                        </div>

                        <!-- Información adicional de acumulados -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-info-circle"></i> Información de Acumulados
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-circle text-success"></i> Conceptos Activos</h6>
                                                <ul>';

if (!empty($acumulados_data['tipos_acumulados'])) {
    $conceptosUnicos = [];
    foreach ($acumulados_data['tipos_acumulados'] as $concepto) {
        $conceptoId = $concepto['concepto_id'] ?? '';
        if ($conceptoId && !isset($conceptosUnicos[$conceptoId])) {
            $conceptosUnicos[$conceptoId] = $concepto['descripcion'] ?? 'N/A';
            $content .= '<li>' . htmlspecialchars($concepto['descripcion'] ?? 'N/A') . '</li>';
        }
    }
} else {
    $content .= '<li>No hay conceptos de acumulados configurados</li>';
}

$content .= '
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-calendar-alt"></i> Procesamiento</h6>
                                                <p>Los acumulados se calculan automáticamente al cerrar las planillas.</p>
                                                <p><strong>Año actual:</strong> ' . ($acumulados_data['year'] ?? date('Y')) . '</p>
                                                <p><strong>Total empleados con acumulados:</strong> ' . count($acumulados_data['empleados_acumulados'] ?? []) . '</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-link"></i> Accesos Rápidos
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <a href="' . \App\Core\UrlHelper::route('panel/acumulados') . '" class="btn btn-primary btn-sm mb-2">
                                                <i class="fas fa-piggy-bank"></i> Ver Todos los Acumulados
                                            </a>
                                            <a href="' . \App\Core\UrlHelper::route('panel/acumulados/byEmployee') . '" class="btn btn-info btn-sm mb-2">
                                                <i class="fas fa-user"></i> Acumulados por Empleado
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Fin Tab Acumulados -->
                </div>
            </div>
        </div>
    </div>
</div>';

$scripts = '
<script src="' . url('plugins/chart.js/Chart.min.js', false) . '"></script>
<script>
$(document).ready(function() {
    // Initialize dashboard filter from sessionStorage (like payrolls)
    const urlParams = new URLSearchParams(window.location.search);
    const urlTipoPlanilla = urlParams.get("tipo_planilla");
    const selectedPayrollType = sessionStorage.getItem("selectedPayrollType");

    // If there\'s a selected type in sessionStorage but not in URL, reload with filter
    if (selectedPayrollType && !urlTipoPlanilla) {
        try {
            const payrollTypeData = JSON.parse(selectedPayrollType);
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set("tipo_planilla", payrollTypeData.id);
            console.log("Dashboard: Aplicando filtro desde sessionStorage:", payrollTypeData);
            window.location.href = currentUrl.toString();
            return; // Stop execution, will reload
        } catch (e) {
            console.error("Error parsing selectedPayrollType:", e);
        }
    }

    // Listen for payroll type changes from navbar
    window.addEventListener("payrollTypeChanged", function(event) {
        const payrollTypeData = event.detail;
        console.log("Dashboard: Tipo de planilla cambiado:", payrollTypeData);

        // Reload dashboard with new filter
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set("tipo_planilla", payrollTypeData.id);
        window.location.href = currentUrl.toString();
    });

    // Configurar gráfica de asistencia
    var attendanceData = ' . json_encode($attendance_chart_data) . ';

    if (attendanceData && attendanceData.length > 0) {
        var ctx = document.getElementById("attendanceChart").getContext("2d");
        var attendanceChart = new Chart(ctx, {
            type: "line",
            data: {
                labels: attendanceData.map(function(item) { return item.formatted_date; }),
                datasets: [{
                    label: "A Tiempo",
                    backgroundColor: "rgba(40,167,69,0.2)",
                    borderColor: "rgba(40,167,69,1)",
                    pointBackgroundColor: "rgba(40,167,69,1)",
                    pointBorderColor: "#fff",
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderColor: "rgba(40,167,69,1)",
                    data: attendanceData.map(function(item) { return item.on_time || 0; }),
                    fill: true,
                    tension: 0.4
                }, {
                    label: "Tarde",
                    backgroundColor: "rgba(255,193,7,0.2)",
                    borderColor: "rgba(255,193,7,1)",
                    pointBackgroundColor: "rgba(255,193,7,1)",
                    pointBorderColor: "#fff",
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderColor: "rgba(255,193,7,1)",
                    data: attendanceData.map(function(item) { return item.late; }),
                    fill: false,
                    tension: 0.4
                }, {
                    label: "Ausencias",
                    backgroundColor: "rgba(220,53,69,0.2)",
                    borderColor: "rgba(220,53,69,1)",
                    pointBackgroundColor: "rgba(220,53,69,1)",
                    pointBorderColor: "#fff",
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderColor: "rgba(220,53,69,1)",
                    data: attendanceData.map(function(item) { return item.absent || 0; }),
                    fill: false,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "rgba(0,0,0,.125)"
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: "top"
                    },
                    tooltip: {
                        mode: "index",
                        intersect: false,
                        callbacks: {
                            title: function(context) {
                                var index = context[0].dataIndex;
                                return "Fecha: " + new Date(attendanceData[index].date).toLocaleDateString("es-ES");
                            },
                            afterBody: function(context) {
                                var index = context[0].dataIndex;
                                var total = attendanceData[index].total;
                                var present = attendanceData[index].present;
                                var absent = attendanceData[index].absent || 0;
                                var punctuality = attendanceData[index].avg_punctuality || 0;
                                var percentage = total > 0 ? ((present / total) * 100).toFixed(1) : 0;
                                return "Total empleados: " + total + "\\nPresentes: " + present + "\\nAusentes: " + absent + "\\n% Asistencia: " + percentage + "%\\nScore Puntualidad: " + punctuality + "/100";
                            }
                        }
                    }
                },
                interaction: {
                    mode: "nearest",
                    axis: "x",
                    intersect: false
                }
            }
        });
    }
    
    // Auto-refresh del dashboard cada 5 minutos
    setTimeout(function() {
        location.reload();
    }, 300000); // 5 minutos
    
    // Mostrar hora actual
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString("es-ES");
        // Actualizando hora del sistema
    }
    
    // Actualizar cada minuto
    setInterval(updateTime, 60000);
    updateTime();
    
    // Animación de contadores
    $(".info-box-number").each(function() {
        const $this = $(this);
        const countTo = parseInt($this.text().replace(/[^0-9]/g, ""));
        if (countTo > 0) {
            $({ countNum: 0 }).animate({
                countNum: countTo
            }, {
                duration: 2000,
                easing: "linear",
                step: function() {
                    const suffix = $this.text().includes("%") ? "%" : "";
                    $this.text(Math.floor(this.countNum) + suffix);
                },
                complete: function() {
                    const suffix = $this.text().includes("%") ? "%" : "";
                    $this.text(countTo + suffix);
                }
            });
        }
    });
});
</script>';

$styles = '
<style>
/* Alinear tabs a la derecha */
.nav-tabs {
    justify-content: flex-end;
}

.small-box .icon {
    top: 10px;
}
.progress-group {
    margin-bottom: 10px;
}
.info-box-content {
    padding: 5px 10px;
}
.card-tools .btn-tool {
    color: #6c757d;
}

/* Mejoras visuales para el dashboard */
.small-box {
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}
.small-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

.info-box {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card {
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}

.chart {
    position: relative;
}

/* Animaciones para los números */
.info-box-number, .small-box h3 {
    font-weight: bold;
    transition: all 0.3s ease;
}

/* Gradientes mejorados */
.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8, #138496) !important;
}
.bg-gradient-success {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}
.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14) !important;
}
.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545, #e83e8c) !important;
}

/* Responsive mejoras */
@media (max-width: 768px) {
    .small-box h3 {
        font-size: 1.5rem;
    }
    .info-box-number {
        font-size: 1.2rem;
    }
}

/* Efectos de hover en botones */
.btn {
    transition: all 0.3s ease;
}
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Mejoras en la tabla */
.table {
    border-radius: 8px;
    overflow: hidden;
}
.table thead th {
    background: linear-gradient(135deg, #495057, #6c757d);
    color: white;
    border: none;
}

</style>';

include __DIR__ . '/../layouts/admin.php';
?>