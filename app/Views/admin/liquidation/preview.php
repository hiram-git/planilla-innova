<?php
$pageTitle = "Vista Previa de Liquidación - " . htmlspecialchars($termination['firstname'] . ' ' . $termination['lastname']);
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.min.css">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.all.min.js"></script>

<!-- Base URL for JavaScript -->
<script>
    window.BASE_URL = '<?= getBaseUrl() ?>';
</script>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-eye mr-2"></i>
                    Vista Previa de Liquidación
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('panel/liquidation') ?>">Liquidaciones</a></li>
                    <li class="breadcrumb-item active">Vista Previa</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <!-- Vista Previa Estilo Documento Oficial -->
        <div class="card">
            <div class="card-body" id="liquidationDocument">

                <!-- Encabezado Empresa -->
                <div class="text-center mb-4">
                    <h2 class="mb-1">LIQUIDACIÓN DE PRESTACIONES LABORALES</h2>
                    <h4 class="text-muted">República de Panamá</h4>
                    <hr>
                </div>

                <!-- Información del Empleado -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5><i class="fas fa-user mr-2"></i>DATOS DEL EMPLEADO</h5>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="30%"><strong>Nombre:</strong></td>
                                <td><?= htmlspecialchars($termination['firstname'] . ' ' . $termination['lastname']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Ficha:</strong></td>
                                <td><?= htmlspecialchars($termination['employee_id']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Cédula:</strong></td>
                                <td><?= htmlspecialchars($termination['document_id'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Cargo:</strong></td>
                                <td><?= htmlspecialchars($termination['position_name'] ?? 'N/A') ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-calendar mr-2"></i>DATOS DE LA TERMINACIÓN</h5>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="40%"><strong>Fecha Ingreso:</strong></td>
                                <td><?= date('d/m/Y', strtotime($termination['fecha_ingreso'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Fecha Terminación:</strong></td>
                                <td><?= date('d/m/Y', strtotime($termination['termination_date'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Años Trabajados:</strong></td>
                                <td><strong><?= number_format($termination['years_worked'], 2) ?> años</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Tipo Terminación:</strong></td>
                                <td>
                                    <span class="badge badge-<?= $termination['termination_type'] === 'DESPIDO_SIN_CAUSA' ? 'danger' : 'warning' ?>">
                                        <?= str_replace('_', ' ', $termination['termination_type']) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Motivo -->
                <?php if (!empty($termination['reason'])): ?>
                <div class="mb-4">
                    <h5><i class="fas fa-clipboard mr-2"></i>MOTIVO DE LA TERMINACIÓN</h5>
                    <p class="border p-3 bg-light"><?= nl2br(htmlspecialchars($termination['reason'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Detalle de Liquidación -->
                <div class="mb-4">
                    <h5><i class="fas fa-calculator mr-2"></i>DETALLE DE LIQUIDACIÓN</h5>

                    <!-- Asignaciones -->
                    <h6 class="text-success mt-3"><i class="fas fa-plus mr-2"></i>ASIGNACIONES</h6>
                    <table class="table table-bordered table-striped">
                        <thead class="thead-light">
                            <tr>
                                <th width="20%">Concepto</th>
                                <th width="50%">Descripción</th>
                                <th width="30%" class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalAsignaciones = 0;
                            foreach ($calculations as $calc):
                                if ($calc['calculated_amount'] > 0):
                                    // Determinar tipo basado en el tipo_concepto de la base de datos
                                    $isAsignacion = ($calc['tipo_concepto'] === 'ASIGNACION' || $calc['tipo_concepto'] === 'A');

                                    if ($isAsignacion):
                                        $totalAsignaciones += $calc['calculated_amount'];
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($calc['concept_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($calc['concept_description']) ?></td>
                                    <td class="text-right"><strong><?= currency_symbol() ?><?= number_format($calc['calculated_amount'], 2) ?></strong></td>
                                </tr>
                            <?php
                                    endif; // Cierra if ($isAsignacion)
                                endif; // Cierra if ($calc['calculated_amount'] > 0)
                            endforeach; // Cierra foreach
                            ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-success text-white">
                                <th colspan="2" class="text-right">TOTAL ASIGNACIONES:</th>
                                <th class="text-right"><?= currency_symbol() ?><?= number_format($totalAsignaciones, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Deducciones -->
                    <h6 class="text-danger mt-4"><i class="fas fa-minus mr-2"></i>DEDUCCIONES</h6>
                    <table class="table table-bordered table-striped">
                        <thead class="thead-light">
                            <tr>
                                <th width="20%">Concepto</th>
                                <th width="50%">Descripción</th>
                                <th width="30%" class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalDeducciones = 0;
                            foreach ($calculations as $calc):
                                if ($calc['calculated_amount'] > 0):
                                    // Determinar tipo basado en el tipo_concepto de la base de datos
                                    $isDeduccion = ($calc['tipo_concepto'] === 'DEDUCCION' || $calc['tipo_concepto'] === 'D');

                                    if ($isDeduccion):
                                        $totalDeducciones += $calc['calculated_amount'];
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($calc['concept_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($calc['concept_description']) ?></td>
                                    <td class="text-right"><strong><?= currency_symbol() ?><?= number_format($calc['calculated_amount'], 2) ?></strong></td>
                                </tr>
                            <?php
                                    endif; // Cierra if ($isDeduccion)
                                endif; // Cierra if ($calc['calculated_amount'] > 0)
                            endforeach; // Cierra foreach
                            ?>
                            <?php if ($totalDeducciones == 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No hay deducciones aplicables</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-danger text-white">
                                <th colspan="2" class="text-right">TOTAL DEDUCCIONES:</th>
                                <th class="text-right"><?= currency_symbol() ?><?= number_format($totalDeducciones, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Total Neto -->
                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <table class="table table-bordered">
                                <tr class="bg-primary text-white">
                                    <th class="text-right">TOTAL NETO A PAGAR:</th>
                                    <th class="text-right h4">
                                        <?= currency_symbol() ?><?= number_format($totalAsignaciones - $totalDeducciones, 2) ?>
                                    </th>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Marco Legal -->
                <div class="mb-4">
                    <h5><i class="fas fa-balance-scale mr-2"></i>MARCO LEGAL</h5>
                    <p class="small text-muted">
                        Esta liquidación se basa en las disposiciones del Código de Trabajo de la República de Panamá,
                        específicamente los artículos 224 (Indemnización), 225 (Prima de Antigüedad), 213 (Preaviso),
                        68 (Vacaciones) y 162 (Décimo Tercer Mes). Los cálculos han sido realizados conforme a la
                        legislación laboral vigente.
                    </p>
                </div>

                <!-- Declaración -->
                <div class="mb-4">
                    <h5><i class="fas fa-file-signature mr-2"></i>DECLARACIÓN</h5>
                    <p class="text-justify">
                        El empleado declara que con el pago de las sumas arriba detalladas, queda completamente satisfecho
                        y finiquitado por todos los conceptos que pudiera reclamar por razón del contrato de trabajo que
                        mantuvo con la empresa, renunciando expresamente a cualquier acción o reclamo adicional.
                    </p>
                </div>

                <!-- Firmas -->
                <div class="row mt-5">
                    <div class="col-md-6 text-center">
                        <div style="border-top: 1px solid #000; margin-top: 80px; padding-top: 10px;">
                            <strong>EMPLEADO</strong><br>
                            <?= htmlspecialchars($termination['firstname'] . ' ' . $termination['lastname']) ?><br>
                            Cédula: <?= htmlspecialchars($termination['document_id'] ?? 'N/A') ?>
                        </div>
                    </div>
                    <div class="col-md-6 text-center">
                        <div style="border-top: 1px solid #000; margin-top: 80px; padding-top: 10px;">
                            <strong>EMPRESA</strong><br>
                            Representante Legal<br>
                            Fecha: <?= date('d/m/Y') ?>
                        </div>
                    </div>
                </div>

                <!-- Pie de página -->
                <div class="text-center mt-4">
                    <small class="text-muted">
                        Documento generado automáticamente el <?= date('d/m/Y H:i') ?> por el Sistema de Planillas
                    </small>
                </div>
            </div>

            <!-- Acciones del Documento -->
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-6">
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Volver
                        </a>
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/calculate/' . $termination['id']) ?>"
                           class="btn btn-info">
                            <i class="fas fa-calculator mr-1"></i> Ver Cálculos
                        </a>
                        <?php if (in_array($termination['status'], ['CALCULADA', 'PROCESADA'])): ?>
                        <button type="button" class="btn btn-warning" onclick="recalculateLiquidation(<?= $termination['id'] ?>)">
                            <i class="fas fa-sync-alt mr-1"></i> Recalcular
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="button" class="btn btn-primary" onclick="printDocument()">
                            <i class="fas fa-print mr-1"></i> Imprimir
                        </button>
                        <a href="<?= \App\Core\UrlHelper::route('panel/liquidation/generatePayroll/' . $termination['id']) ?>"
                           class="btn btn-success">
                            <i class="fas fa-file-invoice mr-1"></i> Generar Planilla
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Esperar a que jQuery esté disponible usando JavaScript puro
(function() {
    function waitForjQuery() {
        if (typeof $ !== 'undefined') {
            // jQuery está disponible, ejecutar código
            $(document).ready(function() {

    // Función para imprimir documento
    window.printDocument = function() {
        // Ocultar acciones y elementos no imprimibles
        const cardFooter = document.querySelector('.card-footer');
        const breadcrumb = document.querySelector('.breadcrumb');
        const contentHeader = document.querySelector('.content-header');

        cardFooter.style.display = 'none';
        breadcrumb.style.display = 'none';
        contentHeader.style.display = 'none';

        // Imprimir
        window.print();

        // Restaurar elementos
        cardFooter.style.display = '';
        breadcrumb.style.display = '';
        contentHeader.style.display = '';
    };

    // CSS para impresión
    const printStyles = `
        @media print {
            .content-header,
            .card-footer,
            .breadcrumb,
            .btn {
                display: none !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            body {
                font-size: 12px;
            }

            .table {
                font-size: 11px;
            }

            h2, h4, h5, h6 {
                margin-top: 10px;
                margin-bottom: 10px;
            }
        }
    `;

    // Agregar estilos de impresión
    const styleSheet = document.createElement("style");
    styleSheet.type = "text/css";
    styleSheet.innerText = printStyles;
    document.head.appendChild(styleSheet);

    // Función para recalcular liquidación
    window.recalculateLiquidation = function(terminationId) {
    Swal.fire({
        title: '¿Recalcular Liquidación?',
        html: `
            <div class="text-left">
                <p><strong>Esta acción:</strong></p>
                <ul>
                    <li>✅ Calculará nuevamente todos los conceptos</li>
                    <li>✅ Aplicará las fórmulas más actualizadas</li>
                    <li>✅ Reemplazará los cálculos existentes</li>
                    <li>📝 Registrará la acción en el historial</li>
                </ul>
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Advertencia:</strong> Los cálculos anteriores se perderán.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-sync-alt"></i> Sí, recalcular',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        reverseButtons: true,
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading mientras se procesa
            Swal.fire({
                title: 'Recalculando...',
                text: 'Procesando nuevos cálculos de liquidación',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Crear formulario para envío POST con CSRF
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.BASE_URL + `/panel/liquidation/${terminationId}/recalculate`;

            // Agregar CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = csrfToken.getAttribute('content');
                form.appendChild(csrfInput);
            }

            document.body.appendChild(form);
            form.submit();
        }
    });
    }; // Cerrar window.recalculateLiquidation
            }); // Cerrar $(document).ready
        } else {
            // jQuery no está disponible aún, esperar 100ms y reintentar
            setTimeout(waitForjQuery, 100);
        }
    }

    // Iniciar el proceso de espera
    waitForjQuery();
})(); // Cerrar función autoejecutada
</script>