<?php
$page_title = $data['title'] ?? 'Estimado Anual de Liquidaciones';

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
            <div class="card-header bg-gradient-warning">
                <h3 class="card-title">
                    <i class="fas fa-hand-holding-usd"></i>
                    Estimado Anual de Liquidaciones
                </h3>
                <div class="card-tools">
                    <a href="' . \App\Core\UrlHelper::url('/panel/reports') . '" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left"></i> Volver a Reportes
                    </a>
                    <a href="' . \App\Core\UrlHelper::url('/panel/reports/estimado-anual-liquidaciones-pdf') . '" class="btn btn-sm btn-danger" target="_blank">
                        <i class="fas fa-file-pdf"></i> Generar PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Información General -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info-circle"></i> Información del Estimado</h5>
                            <p class="mb-0">
                                Este reporte calcula el monto estimado de liquidación para todos los empleados activos
                                <strong>sin que estén dados de baja</strong>. Es útil para estimar la provisión anual
                                necesaria para cubrir posibles liquidaciones y para análisis de costos laborales futuros.
                            </p>
                            <p class="mb-0 mt-2">
                                <strong>Fecha del estimado:</strong> ' . $data['fecha_estimado'] . ' |
                                <strong>Empleados incluidos:</strong> ' . $data['total_employees'] . '
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Resumen Ejecutivo -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-plus-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Asignaciones</span>
                                <span class="info-box-number">$' . number_format($data['total_general_asignaciones'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-minus-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Deducciones</span>
                                <span class="info-box-number">$' . number_format($data['total_general_deducciones'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Neto Estimado</span>
                                <span class="info-box-number">$' . number_format($data['total_general_neto'], 2) . '</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Detalles por Empleado -->
                <div class="table-responsive">
                    <table id="table-estimado-liquidaciones" class="table table-bordered table-striped table-hover">
                        <thead class="bg-gradient-warning">
                            <tr>
                                <th>Cédula</th>
                                <th>Empleado</th>
                                <th>Cargo</th>
                                <th>Años Trabajados</th>
                                <th>Salario Base</th>
                                <th>Asignaciones</th>
                                <th>Deducciones</th>
                                <th>Neto Estimado</th>
                            </tr>
                        </thead>
                        <tbody>';

foreach ($data['estimates'] as $estimate) {
    $employee = $estimate['employee'];
    $years_worked = number_format($employee['years_worked'], 1);

    $content .= '
                            <tr>
                                <td>' . htmlspecialchars($employee['document_id'] ?? 'N/A') . '</td>
                                <td>' . htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']) . '</td>
                                <td>' . htmlspecialchars($employee['position_name']) . '</td>
                                <td class="text-center">' . $years_worked . '</td>
                                <td class="text-right">$' . number_format($employee['sueldo_individual'], 2) . '</td>
                                <td class="text-right text-success">$' . number_format($estimate['total_asignaciones'], 2) . '</td>
                                <td class="text-right text-danger">$' . number_format($estimate['total_deducciones'], 2) . '</td>
                                <td class="text-right text-primary font-weight-bold">$' . number_format($estimate['neto'], 2) . '</td>
                            </tr>';
}

$content .= '
                        </tbody>
                        <tfoot>
                            <tr class="bg-gradient-info">
                                <th colspan="5" class="text-right">TOTAL GENERAL:</th>
                                <th class="text-right">$' . number_format($data['total_general_asignaciones'], 2) . '</th>
                                <th class="text-right">$' . number_format($data['total_general_deducciones'], 2) . '</th>
                                <th class="text-right">$' . number_format($data['total_general_neto'], 2) . '</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Notas -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="callout callout-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Notas Importantes</h6>
                            <ul class="mb-0">
                                <li>Este es un <strong>estimado proyectado</strong> y no representa liquidaciones reales procesadas.</li>
                                <li>Los cálculos se basan en la fecha actual como fecha hipotética de terminación.</li>
                                <li>No se incluyen empleados que ya tienen liquidaciones calculadas o procesadas.</li>
                                <li>Los montos pueden variar según cambios en salarios, antigüedad o conceptos aplicables.</li>
                                <li>Se recomienda actualizar este estimado periódicamente para mantener provisiones precisas.</li>
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

// Scripts para DataTables y GSAP
$scripts = '
<script>
$(document).ready(function() {
    // Inicializar DataTable
    const table = $("#table-estimado-liquidaciones").DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        "order": [[7, "desc"]], // Ordenar por neto estimado descendente
        "pageLength": 25,
        "responsive": true,
        "dom": "Bfrtip",
        "buttons": [
            {
                extend: "excel",
                text: "<i class=\'fas fa-file-excel\'></i> Excel",
                className: "btn btn-success btn-sm",
                title: "Estimado_Anual_Liquidaciones_' . date('Y-m-d') . '"
            },
            {
                extend: "pdf",
                text: "<i class=\'fas fa-file-pdf\'></i> PDF",
                className: "btn btn-danger btn-sm",
                title: "Estimado Anual de Liquidaciones",
                orientation: "landscape",
                pageSize: "LEGAL"
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

        // 5. Animar filas de la tabla
        function animateTableRows() {
            const rows = $("#table-estimado-liquidaciones tbody tr");

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

                // Animar controles de paginación
                gsap.to(".dataTables_info, .dataTables_paginate", {
                    opacity: 1,
                    duration: 0.5,
                    delay: 0.3
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

        // 6. Animar números en las celdas
        function setupNumberAnimations() {
            const numberCells = $("#table-estimado-liquidaciones tbody td.text-right");

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

        // 7. Animar botones de exportación DataTables
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
        setupExportButtonAnimations();

        // Ejecutar animación de tabla en la carga inicial
        setTimeout(animateTableRows, 400);
    }
});
</script>';

// Estilos para impresión
$styles = '
<style>
@media print {
    .card-tools, .btn, .dataTables_wrapper .row:first-child, .dataTables_wrapper .row:last-child {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .print-section {
        page-break-before: auto;
    }
}
.info-box-number {
    font-size: 1.5rem !important;
}
</style>';

include __DIR__ . '/../../layouts/admin.php';
?>
