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

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>' . $total_employees . '</h3>
                <p>Total Empleados</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="' . \App\Core\UrlHelper::employee() . '" class="small-box-footer">
                Ver empleados <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>' . $employees_present . '/' . $total_employees . '</h3>
                <p>Presentes Hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="small-box-footer">
                <strong>' . $attendance_percentage . '%</strong> de asistencia hoy
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>' . $active_employees . '</h3>
                <p>Colaboradores Activos</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="small-box-footer">
                Últimos 30 días
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-success">
            <div class="inner">
                <h3>' . $monthly_punctuality . '%</h3>
                <p>Puntualidad Mensual</p>
            </div>
            <div class="icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="small-box-footer">
                ' . date('F Y') . '
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
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Promedio Diario</span>
                                <span class="info-box-number">' . $monthly_stats['average_daily'] . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Presentes</span>
                                <span class="info-box-number">' . $monthly_stats['total_present'] . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box bg-gradient-warning">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Tardanzas</span>
                                <span class="info-box-number">' . $monthly_stats['total_late'] . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box bg-gradient-danger">
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
        </div>
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
</div>';

$scripts = '
<script src="' . url('plugins/chart.js/Chart.min.js', false) . '"></script>
<script>
$(document).ready(function() {
    // Configurar gráfica de asistencia
    var attendanceData = ' . json_encode($attendance_chart_data) . ';
    
    if (attendanceData && attendanceData.length > 0) {
        var ctx = document.getElementById("attendanceChart").getContext("2d");
        var attendanceChart = new Chart(ctx, {
            type: "line",
            data: {
                labels: attendanceData.map(function(item) { return item.formatted_date; }),
                datasets: [{
                    label: "Empleados Presentes",
                    backgroundColor: "rgba(60,141,188,0.2)",
                    borderColor: "rgba(60,141,188,1)",
                    pointBackgroundColor: "rgba(60,141,188,1)",
                    pointBorderColor: "#fff",
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderColor: "rgba(60,141,188,1)",
                    data: attendanceData.map(function(item) { return item.present; }),
                    fill: true,
                    tension: 0.4
                }, {
                    label: "Llegadas Tarde",
                    backgroundColor: "rgba(255,193,7,0.2)",
                    borderColor: "rgba(255,193,7,1)",
                    pointBackgroundColor: "rgba(255,193,7,1)",
                    pointBorderColor: "#fff",
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderColor: "rgba(255,193,7,1)",
                    data: attendanceData.map(function(item) { return item.late; }),
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
                                var percentage = total > 0 ? ((present / total) * 100).toFixed(1) : 0;
                                return "Total registros: " + total + "\\nPorcentaje presente: " + percentage + "%";
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