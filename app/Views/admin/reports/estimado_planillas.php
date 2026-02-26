<?php
$page_title = $data['title'] ?? 'Estimado Anual de Planillas';

$content = '
<div class="row">
    <div class="col-12">
        <!-- Mensajes de estado -->
        ';

if (isset($_SESSION['success'])) {
    $content .= '
        <div class="callout callout-success">
            <h5><i class="fas fa-check"></i> Éxito</h5>
            <p>' . $_SESSION['success'] . '</p>
        </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $content .= '
        <div class="callout callout-danger">
            <h5><i class="fas fa-exclamation-triangle"></i> Error</h5>
            <p>' . $_SESSION['error'] . '</p>
        </div>';
    unset($_SESSION['error']);
}

$content .= '
        <div class="card">
            <div class="card-header bg-gradient-primary">
                <h3 class="card-title">
                    <i class="fas fa-calendar-alt"></i>
                    Estimado Anual de Planillas - Año ' . $data['ano_estimado'] . '
                </h3>
                <div class="card-tools">
                    <a href="' . \App\Core\UrlHelper::url('/panel/reports') . '" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left"></i> Volver a Reportes
                    </a>
                    <a href="' . \App\Core\UrlHelper::url('/panel/reports/estimado-anual-planillas-pdf') . (isset($data['tipo_planilla_id']) ? '?tipo_planilla_id=' . $data['tipo_planilla_id'] : '') . '" class="btn btn-sm btn-danger" target="_blank">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <form method="GET" action="' . \App\Core\UrlHelper::url('/panel/reports/estimado-anual-planillas') . '" class="form-inline">
                            <label class="mr-2">Filtrar por Tipo de Planilla:</label>
                            <select name="tipo_planilla_id" class="form-control mr-2" onchange="this.form.submit()">
                                <option value="">Todos los tipos</option>';

foreach ($data['tipos_planilla'] as $tipo) {
    $selected = ($data['tipo_planilla_id'] == $tipo['id']) ? 'selected' : '';
    $content .= '<option value="' . $tipo['id'] . '" ' . $selected . '>' . htmlspecialchars($tipo['nombre']) . '</option>';
}

$content .= '
                            </select>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Información General -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info-circle"></i> Información del Estimado</h5>
                            <p class="mb-0">
                                Este reporte proyecta el costo anual de planillas basándose en la última planilla procesada.
                                Los montos se replican mes a mes para obtener una estimación del costo total anual.
                            </p>
                            <p class="mb-0 mt-2">
                                <strong>Fecha del estimado:</strong> ' . $data['fecha_estimado'] . ' |
                                <strong>Planilla base:</strong> ' . htmlspecialchars($data['ultima_planilla']['descripcion']) . ' |
                                <strong>Empleados:</strong> ' . $data['ultima_planilla']['total_empleados'] . '
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Resumen Ejecutivo Anual -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Asignaciones Anuales</span>
                                <span class="info-box-number">$' . number_format($data['total_anual_asignaciones'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Deducciones Anuales</span>
                                <span class="info-box-number">$' . number_format($data['total_anual_deducciones'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Neto Anual</span>
                                <span class="info-box-number">$' . number_format($data['total_anual_neto'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfica de Proyección Mensual -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-chart-line"></i> Proyección Mensual</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyChart" style="height: 250px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Proyección Mensual -->
                <div class="table-responsive">
                    <table id="table-estimado-planillas" class="table table-bordered table-striped table-hover">
                        <thead class="bg-gradient-primary">
                            <tr>
                                <th>#</th>
                                <th>Mes</th>
                                <th>Empleados</th>
                                <th>Asignaciones</th>
                                <th>Deducciones</th>
                                <th>Neto</th>
                            </tr>
                        </thead>
                        <tbody>';

foreach ($data['proyeccion_mensual'] as $mes) {
    $content .= '
                            <tr>
                                <td class="text-center">' . $mes['mes_numero'] . '</td>
                                <td><strong>' . $mes['mes_nombre'] . ' ' . $data['ano_estimado'] . '</strong></td>
                                <td class="text-center">' . $mes['empleados'] . '</td>
                                <td class="text-right text-success">$' . number_format($mes['asignaciones'], 2) . '</td>
                                <td class="text-right text-danger">$' . number_format($mes['deducciones'], 2) . '</td>
                                <td class="text-right text-primary font-weight-bold">$' . number_format($mes['neto'], 2) . '</td>
                            </tr>';
}

$content .= '
                        </tbody>
                        <tfoot>
                            <tr class="bg-gradient-info">
                                <th colspan="3" class="text-right">TOTAL ANUAL:</th>
                                <th class="text-right">$' . number_format($data['total_anual_asignaciones'], 2) . '</th>
                                <th class="text-right">$' . number_format($data['total_anual_deducciones'], 2) . '</th>
                                <th class="text-right">$' . number_format($data['total_anual_neto'], 2) . '</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Estadísticas Adicionales -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-calculator"></i> Promedios</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td>Promedio Mensual (Neto):</td>
                                        <td class="text-right font-weight-bold">$' . number_format($data['total_anual_neto'] / 12, 2) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Costo por Empleado (Anual):</td>
                                        <td class="text-right font-weight-bold">$' . number_format($data['total_anual_neto'] / $data['ultima_planilla']['total_empleados'], 2) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Costo por Empleado (Mensual):</td>
                                        <td class="text-right font-weight-bold">$' . number_format(($data['total_anual_neto'] / 12) / $data['ultima_planilla']['total_empleados'], 2) . '</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-info-circle"></i> Planilla Base</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td>Descripción:</td>
                                        <td class="text-right">' . htmlspecialchars($data['ultima_planilla']['descripcion']) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Tipo de Planilla:</td>
                                        <td class="text-right">' . htmlspecialchars($data['ultima_planilla']['tipo_planilla_nombre'] ?? 'N/A') . '</td>
                                    </tr>
                                    <tr>
                                        <td>Frecuencia:</td>
                                        <td class="text-right">' . htmlspecialchars($data['ultima_planilla']['frecuencia_nombre'] ?? 'N/A') . '</td>
                                    </tr>
                                    <tr>
                                        <td>Período:</td>
                                        <td class="text-right">' . date('d/m/Y', strtotime($data['ultima_planilla']['fecha_desde'])) . ' - ' . date('d/m/Y', strtotime($data['ultima_planilla']['fecha_hasta'])) . '</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="callout callout-info">
                            <h6><i class="fas fa-exclamation-circle"></i> Notas Importantes</h6>
                            <ul class="mb-0">
                                <li>Este es un <strong>estimado proyectado</strong> basado en la última planilla procesada.</li>
                                <li><strong>' . $data['frecuencia_texto'] . '</strong></li>
                                <li>Se excluyen décimo tercer mes y otras frecuencias especiales.</li>
                                <li>Se asume que los costos mensuales permanecen constantes durante todo el año.</li>
                                <li>No se incluyen variaciones por incrementos salariales, nuevas contrataciones o bajas.</li>
                                <li>Los montos pueden variar según cambios en conceptos, deducciones o políticas salariales.</li>
                                <li>Se recomienda actualizar este estimado trimestralmente para mayor precisión.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Firmas -->
                <div class="row mt-5 print-section">
                    <div class="col-md-6 text-center">
                        <div class="mt-5 pt-4">
                            <div class="border-top border-dark d-inline-block" style="width: 250px;"></div>
                            <p class="mb-0 mt-2"><strong>' . htmlspecialchars($data['signatures']['elaborado_por'] ?? 'Por definir') . '</strong></p>
                            <p class="text-muted small">' . htmlspecialchars($data['signatures']['cargo_elaborador'] ?? 'Elaborado por') . '</p>
                        </div>
                    </div>
                    <div class="col-md-6 text-center">
                        <div class="mt-5 pt-4">
                            <div class="border-top border-dark d-inline-block" style="width: 250px;"></div>
                            <p class="mb-0 mt-2"><strong>' . htmlspecialchars($data['signatures']['jefe_recursos_humanos'] ?? 'Por definir') . '</strong></p>
                            <p class="text-muted small">' . htmlspecialchars($data['signatures']['cargo_jefe_rrhh'] ?? 'Autorizado por') . '</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

// Preparar datos para el gráfico
$meses_nombres = [];
$meses_asignaciones = [];
$meses_deducciones = [];
$meses_neto = [];

foreach ($data['proyeccion_mensual'] as $mes) {
    $meses_nombres[] = "'" . $mes['mes_nombre'] . "'";
    $meses_asignaciones[] = $mes['asignaciones'];
    $meses_deducciones[] = $mes['deducciones'];
    $meses_neto[] = $mes['neto'];
}

// Scripts para DataTables y Chart.js
$scripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // Inicializar DataTable
    const table = $("#table-estimado-planillas").DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        "paging": false,
        "searching": false,
        "info": false,
        "responsive": true,
        "dom": "Bfrtip",
        "buttons": [
            {
                extend: "excel",
                text: "<i class=\'fas fa-file-excel\'></i> Excel",
                className: "btn btn-success btn-sm",
                title: "Estimado_Anual_Planillas_' . date('Y') . '"
            },
            {
                extend: "pdf",
                text: "<i class=\'fas fa-file-pdf\'></i> PDF",
                className: "btn btn-danger btn-sm",
                title: "Estimado Anual de Planillas ' . date('Y') . '",
                orientation: "portrait"
            },
            {
                extend: "print",
                text: "<i class=\'fas fa-print\'></i> Imprimir",
                className: "btn btn-info btn-sm"
            }
        ],
        "drawCallback": function(settings) {
            // GSAP: Animar filas después de cada draw
            setTimeout(function() {
                animateTableRows();
            }, 50);
        }
    });

    // Gráfico de Línea
    const ctx = document.getElementById("monthlyChart");
    new Chart(ctx, {
        type: "line",
        data: {
            labels: [' . implode(',', $meses_nombres) . '],
            datasets: [
                {
                    label: "Asignaciones",
                    data: [' . implode(',', $meses_asignaciones) . '],
                    borderColor: "rgb(40, 167, 69)",
                    backgroundColor: "rgba(40, 167, 69, 0.1)",
                    tension: 0.1
                },
                {
                    label: "Deducciones",
                    data: [' . implode(',', $meses_deducciones) . '],
                    borderColor: "rgb(220, 53, 69)",
                    backgroundColor: "rgba(220, 53, 69, 0.1)",
                    tension: 0.1
                },
                {
                    label: "Neto",
                    data: [' . implode(',', $meses_neto) . '],
                    borderColor: "rgb(0, 123, 255)",
                    backgroundColor: "rgba(0, 123, 255, 0.1)",
                    tension: 0.1,
                    borderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "top"
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return "$" + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // ============================================
    // GSAP ANIMATIONS
    // ============================================
    if (typeof gsap !== "undefined") {
        // Flag para controlar animación inicial de la tabla
        let isInitialTableLoad = true;

        // 1. Animar Callouts informativos
        function animateCallouts() {
            const callouts = $(".callout");

            if (callouts.length > 0) {
                gsap.set(callouts, { opacity: 0, x: -20 });

                gsap.to(callouts, {
                    opacity: 1,
                    x: 0,
                    duration: 0.5,
                    stagger: 0.15,
                    ease: "power2.out",
                    clearProps: "all"
                });
            }
        }

        // 2. Animar Info-Boxes
        function animateInfoBoxes() {
            const infoBoxes = $(".info-box");

            if (infoBoxes.length > 0) {
                gsap.set(infoBoxes, { opacity: 0, y: 30 });

                gsap.to(infoBoxes, {
                    opacity: 1,
                    y: 0,
                    duration: 0.6,
                    stagger: 0.1,
                    ease: "power2.out",
                    clearProps: "all"
                });
            }
        }

        // 3. Animar iconos de Info-Boxes (hover)
        function setupInfoBoxIconAnimations() {
            const icons = $(".info-box-icon i");

            icons.on("mouseenter", function() {
                gsap.to(this, {
                    rotation: 360,
                    scale: 1.2,
                    duration: 0.5,
                    ease: "power2.inOut"
                });
            });

            icons.on("mouseleave", function() {
                gsap.to(this, {
                    rotation: 0,
                    scale: 1,
                    duration: 0.3,
                    ease: "power2.out"
                });
            });
        }

        // 4. Animar botones del header
        function setupHeaderButtonAnimations() {
            const headerButtons = $(".card-tools .btn");

            headerButtons.on("mouseenter", function() {
                let shadowColor = "rgba(108,117,125,0.4)"; // Default

                if ($(this).hasClass("btn-danger")) {
                    shadowColor = "rgba(220,53,69,0.4)";
                } else if ($(this).hasClass("btn-light")) {
                    shadowColor = "rgba(248,249,250,0.4)";
                }

                gsap.to(this, {
                    scale: 1.05,
                    boxShadow: "0 5px 15px " + shadowColor,
                    duration: 0.3,
                    ease: "power2.out"
                });
            });

            headerButtons.on("mouseleave", function() {
                gsap.to(this, {
                    scale: 1,
                    boxShadow: "none",
                    duration: 0.3,
                    ease: "power2.out"
                });
            });
        }

        // 5. Animar formulario de filtros
        function setupFilterFormAnimations() {
            const filterForm = $(".form-inline");
            const filterButton = $(".form-inline .btn-primary");

            if (filterForm.length > 0) {
                gsap.from(filterForm, {
                    opacity: 0,
                    y: -10,
                    duration: 0.4,
                    delay: 0.3,
                    ease: "power2.out",
                    clearProps: "all"
                });
            }

            filterButton.on("mouseenter", function() {
                gsap.to(this, {
                    scale: 1.05,
                    boxShadow: "0 5px 15px rgba(0,123,255,0.4)",
                    duration: 0.3,
                    ease: "power2.out"
                });
            });

            filterButton.on("mouseleave", function() {
                gsap.to(this, {
                    scale: 1,
                    boxShadow: "none",
                    duration: 0.3,
                    ease: "power2.out"
                });
            });
        }

        // 6. Animar container del gráfico
        function animateChart() {
            const chartCard = $("#monthlyChart").closest(".card");

            if (chartCard.length > 0) {
                gsap.from(chartCard, {
                    opacity: 0,
                    y: 30,
                    duration: 0.6,
                    delay: 0.5,
                    ease: "power2.out",
                    clearProps: "all"
                });
            }
        }

        // 7. Animar filas de la tabla
        function animateTableRows() {
            const rows = $("#table-estimado-planillas tbody tr");

            if (rows.length === 0) return;

            gsap.set(rows, { opacity: 0, y: 0 });

            if (isInitialTableLoad) {
                // Primera carga: animación elaborada
                gsap.set(rows, { y: 20 });
                gsap.to(rows, {
                    opacity: 1,
                    y: 0,
                    duration: 0.4,
                    stagger: 0.05,
                    ease: "power2.out",
                    clearProps: "all"
                });

                isInitialTableLoad = false;
            } else {
                // Recargas: fade rápido
                gsap.to(rows, {
                    opacity: 1,
                    duration: 0.3,
                    stagger: 0.02,
                    ease: "power1.out",
                    clearProps: "all"
                });
            }

            // Animar números con efecto especial
            setupNumberAnimations();
        }

        // 8. Animar números en las celdas
        function setupNumberAnimations() {
            const numberCells = $("#table-estimado-planillas tbody td.text-right");

            numberCells.each(function(index) {
                gsap.fromTo(this,
                    {
                        scale: 0.8,
                        opacity: 0
                    },
                    {
                        scale: 1,
                        opacity: 1,
                        duration: 0.3,
                        delay: index * 0.01,
                        ease: "back.out(1.7)",
                        clearProps: "all"
                    }
                );
            });
        }

        // 9. Animar cards de estadísticas (Promedios y Planilla Base)
        function animateStatsCards() {
            const statsCards = $(".card.bg-light");

            if (statsCards.length > 0) {
                gsap.set(statsCards, { opacity: 0, y: 20 });

                gsap.to(statsCards, {
                    opacity: 1,
                    y: 0,
                    duration: 0.5,
                    stagger: 0.15,
                    delay: 0.7,
                    ease: "power2.out",
                    clearProps: "all"
                });
            }

            // Animar números dentro de las cards con efecto especial
            const statsNumbers = $(".card.bg-light .font-weight-bold");
            statsNumbers.each(function(index) {
                gsap.fromTo(this,
                    {
                        scale: 0.9,
                        opacity: 0
                    },
                    {
                        scale: 1,
                        opacity: 1,
                        duration: 0.4,
                        delay: 0.9 + (index * 0.05),
                        ease: "back.out(1.5)",
                        clearProps: "all"
                    }
                );
            });
        }

        // 10. Animar botones de exportación DataTables
        function setupExportButtonAnimations() {
            setTimeout(function() {
                const exportButtons = ".dt-buttons .btn";

                $(document).on("mouseenter", exportButtons, function() {
                    let shadowColor = "rgba(108,117,125,0.4)";

                    if ($(this).hasClass("btn-success")) {
                        shadowColor = "rgba(40,167,69,0.4)";
                    } else if ($(this).hasClass("btn-danger")) {
                        shadowColor = "rgba(220,53,69,0.4)";
                    } else if ($(this).hasClass("btn-info")) {
                        shadowColor = "rgba(23,162,184,0.4)";
                    }

                    gsap.to(this, {
                        scale: 1.05,
                        boxShadow: "0 5px 15px " + shadowColor,
                        duration: 0.3,
                        ease: "power2.out"
                    });
                });

                $(document).on("mouseleave", exportButtons, function() {
                    gsap.to(this, {
                        scale: 1,
                        boxShadow: "none",
                        duration: 0.3,
                        ease: "power2.out"
                    });
                });
            }, 500);
        }

        // Ejecutar animaciones iniciales
        animateCallouts();
        setTimeout(animateInfoBoxes, 200);
        setupInfoBoxIconAnimations();
        setupHeaderButtonAnimations();
        setupFilterFormAnimations();
        animateChart();
        setupExportButtonAnimations();
        setTimeout(animateTableRows, 600);
        setTimeout(animateStatsCards, 700);
    }
});
</script>';

// Estilos para impresión
$styles = '
<style>
@media print {
    .card-tools, .btn, .dataTables_wrapper .row:first-child, .dataTables_wrapper .row:last-child, form {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .print-section {
        page-break-before: auto;
    }
    #monthlyChart {
        page-break-inside: avoid;
    }
}
.info-box-number {
    font-size: 1.5rem !important;
}
</style>';

include __DIR__ . '/../../layouts/admin.php';
?>
